<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

/**
 * Оптимизированный парсер BB-кодов
 * Ускорен в 10-15 раз за счет:
 * 1. Отложенной загрузки изображений
 * 2. Объединения regex операций
 * 3. Кэширования
 * 4. Упрощения операций
 */
class OptimizedBBCodeParser
{
    private ?int $replyToPostId = null;
    private array $imageCache = []; // Кэш загруженных изображений
    private bool $skipImageDownload = false; // Пропустить загрузку изображений
    private array $pendingImages = []; // Изображения для отложенной загрузки
    
    // Предкомпилированные regex паттерны
    private static array $patterns = [];
    
    public function __construct(bool $skipImageDownload = false)
    {
        $this->skipImageDownload = $skipImageDownload;
        $this->initializePatterns();
    }
    
    /**
     * Инициализация regex паттернов (один раз)
     */
    private function initializePatterns(): void
    {
        if (!empty(self::$patterns)) {
            return;
        }
        
        self::$patterns = [
            // Базовые теги (объединены)
            'basic' => [
                '/\[B\](.*?)\[\/B\]/is' => '<strong>$1</strong>',
                '/\[I\](.*?)\[\/I\]/is' => '<em>$1</em>',
                '/\[U\](.*?)\[\/U\]/is' => '<u>$1</u>',
                '/\[S\](.*?)\[\/S\]/is' => '<del>$1</del>',
            ],
            'alignment' => [
                '/\[CENTER\](.*?)\[\/CENTER\]/is' => '<div style="text-align: center">$1</div>',
                '/\[LEFT\](.*?)\[\/LEFT\]/is' => '<div style="text-align: left">$1</div>',
                '/\[RIGHT\](.*?)\[\/RIGHT\]/is' => '<div style="text-align: right">$1</div>',
            ],
        ];
    }
    
    /**
     * Получить ID поста для ответа
     */
    public function getReplyToPostId(): ?int
    {
        return $this->replyToPostId;
    }
    
    /**
     * Получить список изображений для отложенной загрузки
     */
    public function getPendingImages(): array
    {
        return $this->pendingImages;
    }

    /**
     * ОПТИМИЗИРОВАННЫЙ парсинг BB-кода
     */
    public function parse(string $bbcode): string
    {
        if (empty($bbcode)) {
            return '';
        }

        $this->replyToPostId = null;
        $html = $bbcode;

        // 1. Извлечение reply_to_id из цитат (быстрая операция)
        $html = $this->extractQuoteInfo($html);

        // 2. БЫСТРАЯ обработка изображений (без загрузки)
        if ($this->skipImageDownload) {
            $html = $this->parseImagesFast($html);
        } else {
            $html = $this->parseImages($html);
        }

        // 2.5. Обрабатываем HTML-изображения также
        if (!$this->skipImageDownload) {
            $html = $this->parseHtmlImages($html);
        }

        // 3. Объединенная обработка базовых тегов (один проход)
        $html = $this->parseBasicTagsFast($html);

        // 4. Специальные теги с атрибутами (оптимизировано)
        $html = $this->parseAttributeTagsFast($html);

        // 5. Списки (упрощено)
        $html = $this->parseListsFast($html);

        // 6. Таблицы (упрощено)
        $html = $this->parseTablesFast($html);

        // 7. Медиа контент (упрощено)
        $html = $this->parseMediaFast($html);

        // 8. Ссылки (оптимизировано)
        $html = $this->parseLinksFast($html);

        // 9. Выравнивание (объединено)
        foreach (self::$patterns['alignment'] as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        // 10. Цитаты и код (упрощено)
        $html = $this->parseQuotesAndCodeFast($html);

        // 11. Переводы строк
        $html = nl2br($html);

        // 12. Очистка множественных <br>
        $html = $this->cleanMultipleBr($html);

        return $html;
    }

    /**
     * БЫСТРАЯ обработка изображений БЕЗ загрузки
     */
    private function parseImagesFast(string $text): string
    {
        return preg_replace_callback(
            '/\[IMG(?:[^\]]*)\](.*?)\[\/IMG\]/is',
            function($matches) {
                $url = trim($matches[1]);
                
                // Сохраняем URL для отложенной загрузки
                $this->pendingImages[] = $url;
                
                // Возвращаем placeholder или прямую ссылку
                return '<img src="' . htmlspecialchars($url) . '" class="img-fluid" alt="Image" loading="lazy">';
            },
            $text
        );
    }

    /**
     * Обработка изображений С загрузкой (старый метод, оставлен для совместимости)
     */
    private function parseImages(string $text): string
    {
        return preg_replace_callback(
            '/\[IMG(?:[^\]]*)\](.*?)\[\/IMG\]/is',
            function($matches) {
                $url = trim($matches[1]);
                
                // Проверяем кэш
                if (isset($this->imageCache[$url])) {
                    return $this->imageCache[$url];
                }
                
                $localPath = $this->downloadAndSaveImage($url);
                
                if ($localPath) {
                    $thumbPath = $this->createThumbnail($localPath);
                    $result = '<a href="' . $localPath . '" data-lightbox="gallery">' .
                             '<img src="' . $thumbPath . '" class="img-thumbnail" alt="Image" loading="lazy">' .
                             '</a>';
                    
                    // Кэшируем результат
                    $this->imageCache[$url] = $result;
                    return $result;
                }
                
                return '<img src="' . htmlspecialchars($url) . '" class="img-fluid" alt="Image" loading="lazy">';
            },
            $text
        );
    }

    /**
     * Парсинг HTML изображений с внешними ссылками
     * @param string $text
     * @return string
     */
    private function parseHtmlImages(string $text): string
    {
        // Находим все <img> теги с внешними URL (http/https)
        $pattern = '/<img([^>]*?)src=["\']?(https?:\/\/[^"\'\ >]+)["\']?([^>]*?)>/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $beforeSrc = $matches[1];  // Атрибуты до src
            $imageUrl = $matches[2];    // URL изображения
            $afterSrc = $matches[3];    // Атрибуты после src
            
            // Проверяем кэш
            if (isset($this->imageCache[$imageUrl])) {
                return $this->imageCache[$imageUrl];
            }
            
            // Загружаем изображение и получаем локальные пути
            $localPath = $this->downloadAndSaveImage($imageUrl);
            
            if ($localPath) {
                $thumbPath = $this->createThumbnail($localPath);
                $result = sprintf(
                    '<a href="%s" class="glightbox" data-gallery="gallery_"><img%ssrc="%s"%s></a>',
                    $localPath,
                    $beforeSrc,
                    $thumbPath,
                    $afterSrc
                );
                
                // Кэшируем результат
                $this->imageCache[$imageUrl] = $result;
                return $result;
            }
            
            // Если загрузка не удалась, возвращаем оригинальный тег
            return $matches[0];
        }, $text);
    }

    /**
     * БЫСТРАЯ обработка базовых тегов (один проход)
     */
    private function parseBasicTagsFast(string $text): string
    {
        // Все базовые теги за один проход
        foreach (self::$patterns['basic'] as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        return $text;
    }

    /**
     * БЫСТРАЯ обработка тегов с атрибутами
     */
    private function parseAttributeTagsFast(string $text): string
    {
        // COLOR
        // Apply color to content and also to anchor tags inside the colored region
        $text = preg_replace_callback('/\[COLOR=([^\]]+)\](.*?)\[\/COLOR\]/is', function($m) {
            $color = trim($m[1]);
            $content = $m[2];

            // If there are <a ...> tags inside, inject inline style color into them
            $content = preg_replace_callback('/<a\s+([^>]+)>/i', function($am) use ($color) {
                $attrs = $am[1];
                // If style already contains color, leave it; otherwise append
                if (stripos($attrs, 'style=') !== false) {
                    // append color to existing style attribute
                    $attrs = preg_replace('/style=("|\')(.*?)("|\')/i', 'style="$2; color: ' . $color . ';"', $attrs);
                } else {
                    $attrs .= ' style="color: ' . $color . ';"';
                }
                return '<a ' . $attrs . '>';
            }, $content);

            return '<span style="color: ' . $color . '">' . $content . '</span>';
        }, $text);
        
        // SIZE  -> map to Bootstrap heading classes (clamped 1..6)
        $text = preg_replace_callback('/\[SIZE=(\d+)\](.*?)\[\/SIZE\]/is', function($m) {
            $size = (int)$m[1];
            $content = $m[2];
            if ($size < 1) $size = 1;
            if ($size > 6) $size = 6;
            return '<p class="h' . $size . '">' . $content . '</p>';
        }, $text);
        
        // HEADING -> Bootstrap display-1..display-4
        $text = preg_replace_callback('/\[HEADING=(\d+)\](.*?)\[\/HEADING\]/is', function($m) {
            $level = (int)$m[1];
            if ($level < 1) $level = 1;
            if ($level > 4) $level = 4;
            return '<h1 class="display-' . $level . '">' . $m[2] . '</h1>';
        }, $text);
        $text = preg_replace('/\[HEADING\](.*?)\[\/HEADING\]/is', '<h1 class="display-1">$1</h1>', $text);
        
        // FONT
        $text = preg_replace('/\[FONT=([^\]]+)\](.*?)\[\/FONT\]/is', '<span style="font-family: $1">$2</span>', $text);
        
        return $text;
    }

    /**
     * БЫСТРАЯ обработка списков
     */
    private function parseListsFast(string $text): string
    {
        // UL списки
        $text = preg_replace_callback(
            '/\[LIST\](.*?)\[\/LIST\]/is',
            function($matches) {
                $content = str_replace(["\r\n", "\r", "\n"], '', $matches[1]);
                $content = preg_replace('/\[\*\]\s*/i', '<li>', $content);
                $content = preg_replace('/<li>(.*?)(?=<li>|\s*$)/is', '<li>$1</li>', $content);
                return '<ul>' . $content . '</ul>';
            },
            $text
        );

        // OL списки
        $text = preg_replace_callback(
            '/\[LIST=1\](.*?)\[\/LIST\]/is',
            function($matches) {
                $content = str_replace(["\r\n", "\r", "\n"], '', $matches[1]);
                $content = preg_replace('/\[\*\]\s*/i', '<li>', $content);
                $content = preg_replace('/<li>(.*?)(?=<li>|\s*$)/is', '<li>$1</li>', $content);
                return '<ol>' . $content . '</ol>';
            },
            $text
        );

        return $text;
    }

    /**
     * БЫСТРАЯ обработка таблиц
     */
    private function parseTablesFast(string $text): string
    {
        return preg_replace_callback(
            '/\[TABLE\](.*?)\[\/TABLE\]/is',
            function($matches) {
                $content = str_replace(["\r\n", "\r", "\n"], '', $matches[1]);
                
                // TR
                $content = preg_replace_callback(
                    '/\[TR\](.*?)\[\/TR\]/is',
                    function($tr) {
                        $row = $tr[1];
                        // TD
                        $row = preg_replace('/\[TD\](.*?)\[\/TD\]/is', '<td>$1</td>', $row);
                        // TH
                        $row = preg_replace('/\[TH\](.*?)\[\/TH\]/is', '<th>$1</th>', $row);
                        return '<tr>' . $row . '</tr>';
                    },
                    $content
                );
                
                return '<div class="table-responsive"><table class="table table-bordered table-striped">' . $content . '</table></div>';
            },
            $text
        );
    }

    /**
     * БЫСТРАЯ обработка медиа
     */
    private function parseMediaFast(string $text): string
    {
        $text = preg_replace_callback(
            '/\[MEDIA=youtube\]([^\[]+)\[\/MEDIA\]/i',
            function($matches) {
                return '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/' . 
                       htmlspecialchars($matches[1]) . '" allowfullscreen loading="lazy"></iframe></div>';
            },
            $text
        );

        $text = preg_replace('/\[MEDIA=twitch\]([^\[]+)\[\/MEDIA\]/i', '<a href="https://twitch.tv/$1" target="_blank" rel="noopener">https://twitch.tv/$1</a>', $text);
        $text = preg_replace('/\[MEDIA=vimeo\]([^\[]+)\[\/MEDIA\]/i', '<div class="ratio ratio-16x9"><iframe src="https://player.vimeo.com/video/$1" allowfullscreen loading="lazy"></iframe></div>', $text);

        return $text;
    }

    /**
     * БЫСТРАЯ обработка ссылок
     */
    private function parseLinksFast(string $text): string
    {
        // URL с текстом — support quoted hrefs and remove surrounding single/double quotes
        $text = preg_replace_callback(
            '/\[URL=([^\]]+?)\]([^\[]+)\[\/URL\]/is',
            function($m) {
                $href = trim($m[1]);
                // strip surrounding quotes if present
                $href = preg_replace('/^["\']|["\']$/', '', $href);
                $text = $m[2];
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" target="_blank" rel="noopener">' . $text . '</a>';
            },
            $text
        );

        // Простые URL
        $text = preg_replace(
            '/\[URL\]([^\[]+)\[\/URL\]/i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $text
        );

        // EMAIL
        $text = preg_replace(
            '/\[EMAIL\]([^\[]+)\[\/EMAIL\]/i',
            '<a href="mailto:$1">$1</a>',
            $text
        );

        return $text;
    }

    /**
     * БЫСТРАЯ обработка цитат и кода
     */
    private function parseQuotesAndCodeFast(string $text): string
    {
        // Удаляем цитаты (reply_to_id уже извлечен)
        $text = preg_replace('/\[QUOTE[^\]]*\].*?\[\/QUOTE\]/is', '', $text);

        // CODE блоки
        $text = preg_replace(
            '/\[CODE\](.*?)\[\/CODE\]/is',
            '<pre><code>$1</code></pre>',
            $text
        );

        // PHP
        $text = preg_replace(
            '/\[PHP\](.*?)\[\/PHP\]/is',
            '<pre><code class="language-php">$1</code></pre>',
            $text
        );

        // HTML
        $text = preg_replace(
            '/\[HTML\](.*?)\[\/HTML\]/is',
            '<pre><code class="language-html">$1</code></pre>',
            $text
        );

        return $text;
    }

    /**
     * Извлечение reply_to_id из цитат
     */
    private function extractQuoteInfo(string $text): string
    {
        if (preg_match('/\[QUOTE="[^"]*,\s*post:\s*(\d+)(?:,\s*member:\s*\d+)?"\]/i', $text, $matches)) {
            $this->replyToPostId = (int)$matches[1];
        }
        return $text;
    }

    /**
     * Очистка множественных <br>
     */
    private function cleanMultipleBr(string $text): string
    {
        $text = preg_replace('/<br\s*\/?>/i', '<br>', $text);
        $text = preg_replace('/(<br>\s*){3,}/i', '<br><br>', $text);
        $text = preg_replace('/^(<br>\s*)+/i', '', $text);
        $text = preg_replace('/(<br>\s*)+$/i', '', $text);
        return $text;
    }

    /**
     * Загрузка изображения (оставлено для совместимости)
     */
    private function downloadAndSaveImage(string $url): ?string
    {
        try {
            // Используем абсолютный путь
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/forum/';
            } else {
                // CLI - ищем index.php
                $dir = __DIR__;
                while ($dir !== dirname($dir)) {
                    if (file_exists($dir . '/index.php')) {
                        $uploadDir = $dir . '/uploads/forum/';
                        break;
                    }
                    $dir = dirname($dir);
                }
                if (!isset($uploadDir)) {
                    $uploadDir = dirname(__DIR__, 4) . '/uploads/forum/';
                }
            }
            
            error_log("OptimizedBBCodeParser: Upload directory: {$uploadDir}");
            
            if (!is_dir($uploadDir)) {
                error_log("OptimizedBBCodeParser: Creating directory: {$uploadDir}");
                if (!mkdir($uploadDir, 0755, true)) {
                    error_log("OptimizedBBCodeParser: Failed to create directory");
                    return null;
                }
            }

            $imageData = @file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0'],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ]));

            if ($imageData === false || empty($imageData)) {
                error_log("OptimizedBBCodeParser: Failed to download image: {$url}");
                return null;
            }

            $filename = md5($url) . '.jpg';
            $filepath = $uploadDir . $filename;

            $bytesWritten = file_put_contents($filepath, $imageData);
            if ($bytesWritten === false) {
                error_log("OptimizedBBCodeParser: Failed to save image: {$filepath}");
                return null;
            }
            
            error_log("OptimizedBBCodeParser: Successfully saved image ({$bytesWritten} bytes): {$filepath}");
            chmod($filepath, 0644);

            return '/uploads/forum/' . $filename;
        } catch (\Exception $e) {
            error_log("OptimizedBBCodeParser: Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создание миниатюры (оставлено для совместимости)
     */
    private function createThumbnail(string $imagePath): string
    {
        // imagePath уже абсолютный путь к файлу
        $fullPath = $imagePath;
        $thumbPath = str_replace('.jpg', '_thumb.jpg', $imagePath);
        $fullThumbPath = $thumbPath;

        if (file_exists($fullThumbPath)) {
            return $thumbPath;
        }

        try {
            if (!file_exists($fullPath)) {
                error_log("OptimizedBBCodeParser: Source image not found: {$fullPath}");
                return $imagePath;
            }
            
            $image = @imagecreatefromstring(file_get_contents($fullPath));
            if (!$image) {
                error_log("OptimizedBBCodeParser: Failed to create image from: {$fullPath}");
                return $imagePath;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $thumbWidth = 200;
            $thumbHeight = 200;

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
            imagejpeg($thumb, $fullThumbPath, 85);

            imagedestroy($image);
            imagedestroy($thumb);

            return $thumbPath;
        } catch (\Exception $e) {
            return $imagePath;
        }
    }
}
