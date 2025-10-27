<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

use Exception;

/**
 * Парсер BB-кодов в HTML для импорта из XenForo
 */
class BBCodeParser
{
    private string $uploadPath;
    private string $uploadUrl;
    private array $downloadedImages = [];
    private array $failedImages = [];
    private int $imageCounter = 0;
    private string $galleryId;
    private ?int $replyToPostId = null;

    public function __construct()
    {
        // Путь для сохранения загруженных изображений
        // ВАЖНО: Используем абсолютный путь от DOCUMENT_ROOT или от корня проекта
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            // Веб-контекст
            $this->uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/forum/';
        } else {
            // CLI-контекст - ищем index.php (корень проекта)
            $dir = __DIR__;
            while ($dir !== dirname($dir)) {
                if (file_exists($dir . '/index.php')) {
                    $this->uploadPath = $dir . '/uploads/forum/';
                    break;
                }
                $dir = dirname($dir);
            }
            
            // Если не нашли - используем запасной вариант
            if (!isset($this->uploadPath)) {
                $this->uploadPath = dirname(__DIR__, 4) . '/uploads/forum/';
            }
        }
        
        $this->uploadUrl = '/uploads/forum/';

        // Логируем путь для отладки
        error_log("BBCodeParser: Upload path set to: {$this->uploadPath}");
        
        // Создаем директорию если не существует
        $this->ensureUploadDirectory();
    }

    /**
     * Обеспечить создание директории для загрузки изображений
     * @throws Exception
     */
    private function ensureUploadDirectory(): void
    {
        error_log("BBCodeParser: Checking directory: {$this->uploadPath}");
        
        if (!is_dir($this->uploadPath)) {
            error_log("BBCodeParser: Directory does not exist, attempting to create...");
            
            // Убираем @ чтобы видеть реальные ошибки
            $result = mkdir($this->uploadPath, 0755, true);
            
            if (!$result) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                error_log("BBCodeParser: Failed to create directory: {$errorMsg}");
                throw new Exception("Failed to create upload directory: {$this->uploadPath}. Error: {$errorMsg}");
            }
            
            error_log("BBCodeParser: Successfully created directory: {$this->uploadPath}");
        } else {
            error_log("BBCodeParser: Directory already exists: {$this->uploadPath}");
        }
        
        // Проверяем права на запись
        if (!is_writable($this->uploadPath)) {
            error_log("BBCodeParser: Upload directory is not writable: {$this->uploadPath}");
            throw new Exception("Upload directory is not writable: {$this->uploadPath}");
        }
        
        error_log("BBCodeParser: Directory is writable");
    }

    /**
     * Конвертировать BB-код в HTML
     * @param string $bbcode Текст с BB-кодами
     * @return string HTML-текст
     */
    public function parse(string $bbcode): string
    {
        if (empty($bbcode)) {
            return '';
        }

    // Сбрасываем счетчики для каждого нового сообщения
        $this->replyToPostId = null;
    $this->imageCounter = 0;
    $this->galleryId = 'gallery_' . bin2hex(random_bytes(4));
    $this->failedImages = [];

        $html = $bbcode;

        // ВАЖНО: Извлекаем информацию о цитатах ДО их удаления
        $html = $this->extractQuoteInfo($html);

    // Обрабатываем изображения ПЕРЕД другими тегами
    $html = $this->parseImages($html);

    // Обрабатываем HTML-изображения (если есть)
    $html = $this->parseHtmlImages($html);

    // Обрабатываем прямые ссылки на изображения, которые встречаются в тексте
    $html = $this->parsePlainImageUrls($html);

        // Основные теги форматирования
        $html = $this->parseBasicTags($html);

        // Списки
        $html = $this->parseLists($html);

        // Таблицы
        $html = $this->parseTables($html);

        // Медиа контент (YouTube, Twitch и др.)
        $html = $this->parseMedia($html);

    // Ссылки
    $html = $this->parseLinks($html);

    // Конвертируем ссылки, ведущие напрямую на изображения
    $html = $this->convertLinkedImageUrls($html);

        // Выравнивание
        $html = $this->parseAlignment($html);

        // Цитаты и код (после извлечения информации)
        $html = $this->parseQuotesAndCode($html);

        // Переводы строк
        $html = nl2br($html);

        // Очистка множественных <br> тегов (максимум 2 подряд)
        $html = $this->cleanMultipleBr($html);

        return $html;
    }

    /**
     * Очистка множественных <br> тегов
     * Уменьшает последовательность из 3+ тегов <br> до максимум 2
     * @param string $text
     * @return string
     */
    private function cleanMultipleBr(string $text): string
    {
        // Нормализуем все варианты <br> к единому формату
        $text = preg_replace('/<br\s*\/?>/i', '<br>', $text);
        
        // Заменяем 3 и более <br> подряд (с возможными пробелами между ними) на 2 <br>
        $text = preg_replace('/(<br>\s*){3,}/i', '<br><br>', $text);
        
        // Убираем <br> в начале текста
        $text = preg_replace('/^(<br>\s*)+/i', '', $text);
        
        // Убираем <br> в конце текста
        $text = preg_replace('/(<br>\s*)+$/i', '', $text);
        
        return $text;
    }

    /**
     * Парсинг изображений с загрузкой
     * @param string $text
     * @return string
     */
    private function parseImages(string $text): string
    {
        $pattern = '/\[IMG(?P<attrs>[^\]]*)\](?P<url>.*?)\[\/IMG\]/is';

        return preg_replace_callback($pattern, function ($matches) {
            $originalUrl = trim($matches['url']);
            if ($originalUrl === '') {
                return '';
            }

            $attributes = $this->parseImageAttributes($matches['attrs'] ?? '');
            $downloaded = $this->downloadImage($originalUrl);

            if ($downloaded === null) {
                $this->failedImages[$originalUrl] = true;
            }

            return $this->renderImageHtml($originalUrl, $downloaded, $attributes);
        }, $text);
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
            
            // Загружаем изображение и получаем локальные пути
            $attributes = $this->attributesStringToArray($beforeSrc . ' ' . $afterSrc);
            $downloadedImage = $this->downloadImage($imageUrl);

            if ($downloadedImage === null) {
                $this->failedImages[$imageUrl] = true;
            }

            return $this->renderImageHtml($imageUrl, $downloadedImage, $attributes, true, false);
        }, $text);
    }

    /**
     * Обработка прямых ссылок на изображения в тексте (без BB-кодов)
     * @param string $text
     * @return string
     */
    private function parsePlainImageUrls(string $text): string
    {
        $pattern = '/(^|[\s>])((?:https?:\/\/)[^\s\[\]<>"]+\.(?:jpe?g|png|gif|webp|bmp)(?:\?[^\s<>"]*)?)/i';

        return preg_replace_callback($pattern, function ($matches) {
            $prefix = $matches[1];
            $url = $matches[2];

            $downloadedImage = $this->downloadImage($url);

            if ($downloadedImage === null) {
                $this->failedImages[$url] = true;
            }

            return $prefix . $this->renderImageHtml($url, $downloadedImage, [], true);
        }, $text);
    }

    /**
     * Конвертация ссылок, ведущих напрямую на изображения
     * @param string $text
     * @return string
     */
    private function convertLinkedImageUrls(string $text): string
    {
        $pattern = '/<a\s+([^>]*?)href=["\']?(https?:\/\/[^"\'>]+?\.(?:jpe?g|png|gif|webp|bmp)(?:\?[^"\'>]*)?)["\']?([^>]*)>(.*?)<\/a>/is';

        return preg_replace_callback($pattern, function ($matches) {
            $before = $matches[1];
            $url = $matches[2];
            $after = $matches[3];
            $innerHtml = $matches[4];

            // Если внутри уже есть <img>, то изображение уже обработано
            if (stripos($innerHtml, '<img') !== false) {
                return $matches[0];
            }

            $downloadedImage = $this->downloadImage($url);
            if ($downloadedImage === null) {
                $this->failedImages[$url] = true;
            }

            $rawAttributes = trim($before . ' ' . $after);
            $attributes = $this->rebuildAnchorAttributes($rawAttributes, $downloadedImage['full'] ?? $url);

            $plainInner = trim(strip_tags($innerHtml));

            if ($plainInner === '' || $plainInner === $url) {
                $imageHtml = $this->renderImageHtml($url, $downloadedImage, [], false);
                return '<a' . $attributes . '>' . $imageHtml . '</a>';
            }

            return '<a' . $attributes . '>' . $innerHtml . '</a>';
        }, $text);
    }

    /**
     * Пересобирает строку атрибутов <a> с новым href
     * @param string $rawAttributes
     * @param string $newHref
     * @return string
     */
    private function rebuildAnchorAttributes(string $rawAttributes, string $newHref): string
    {
        $attributes = [];

        if (!empty($rawAttributes)) {
            preg_match_all("/([a-zA-Z0-9_\-:]+)(?:=([\"'])(.*?)\\2|=([^\\s\"'=]+))?/", $rawAttributes, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $name = strtolower($match[1]);
                $value = $match[3] ?? $match[4] ?? null;

                if ($name === 'href') {
                    continue;
                }

                $attributes[$name] = $value;
            }
        }

        $classes = [];
        if (isset($attributes['class']) && $attributes['class'] !== null) {
            $classes = preg_split('/\s+/', $attributes['class'], -1, PREG_SPLIT_NO_EMPTY);
        }

        $classes[] = 'glightbox';
        $attributes['class'] = implode(' ', array_unique($classes));

        if (!isset($attributes['data-gallery'])) {
            $attributes['data-gallery'] = 'gallery_';
        }

        $parts = ['href="' . $newHref . '"'];

        foreach ($attributes as $name => $value) {
            if ($value === null) {
                $parts[] = $name;
            } else {
                $parts[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }

        return ' ' . implode(' ', $parts);
    }

    /**
     * Преобразует атрибуты из BBCode тега [IMG]
     */
    private function parseImageAttributes(string $rawAttributes): array
    {
        $attributes = [];
        $rawAttributes = trim($rawAttributes);

        if ($rawAttributes === '') {
            return $attributes;
        }

        // Формат [IMG=600x400]
        if (str_starts_with($rawAttributes, '=')) {
            $value = substr($rawAttributes, 1);
            if (preg_match('/^(\d+)(?:x|\s+)(\d+)?/i', $value, $match)) {
                $attributes['width'] = (int)$match[1];
                if (!empty($match[2])) {
                    $attributes['height'] = (int)$match[2];
                }
            }
            return $attributes;
        }

        // Формат [IMG width=600 height=400 align=left]
        preg_match_all('/([a-z0-9_-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/i', $rawAttributes, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = $match[3] ?? $match[4] ?? $match[5] ?? '';
            if ($value === '') {
                continue;
            }

            switch ($name) {
                case 'width':
                case 'height':
                    $attributes[$name] = is_numeric($value) ? (int)$value : $value;
                    break;
                case 'align':
                    $attributes['class'][] = 'text-' . strtolower($value);
                    break;
                case 'class':
                    $attributes['class'][] = $value;
                    break;
                case 'alt':
                case 'title':
                case 'style':
                    $attributes[$name] = $value;
                    break;
                default:
                    if (str_starts_with($name, 'data-') || str_starts_with($name, 'aria-')) {
                        $attributes[$name] = $value;
                    }
                    break;
            }
        }

        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = implode(' ', $attributes['class']);
        }

        return $attributes;
    }

    /**
     * Конвертирует строку HTML атрибутов в массив
     */
    private function attributesStringToArray(string $rawAttributes): array
    {
        $attributes = [];
        $rawAttributes = trim($rawAttributes);

        if ($rawAttributes === '') {
            return $attributes;
        }

            preg_match_all('/([a-z0-9_:\-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/i', $rawAttributes, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = $match[3] ?? $match[4] ?? $match[5] ?? '';
            if ($value === '') {
                continue;
            }
            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * Формирует HTML изображение и (опционально) ссылку для лайтбокса
     */
    private function renderImageHtml(string $originalUrl, ?array $downloaded, array $attributes = [], bool $wrapWithAnchor = true, bool $mergeDefaults = true): string
    {
        $fullUrl = $downloaded['full'] ?? $originalUrl;
        $thumbUrl = $downloaded['thumb'] ?? $fullUrl;

        $imgAttributes = [];

        if ($mergeDefaults) {
            $imgAttributes['class'] = 'img-fluid rounded';
        }

        if (isset($attributes['class'])) {
            $imgAttributes['class'] = isset($imgAttributes['class'])
                ? trim($imgAttributes['class'] . ' ' . $attributes['class'])
                : $attributes['class'];
            unset($attributes['class']);
        }

        if (isset($attributes['style'])) {
            $imgAttributes['style'] = isset($imgAttributes['style'])
                ? $imgAttributes['style'] . '; ' . $attributes['style']
                : $attributes['style'];
            unset($attributes['style']);
        }

        foreach (['width', 'height'] as $dimension) {
            if (isset($attributes[$dimension])) {
                $value = $attributes[$dimension];
                if (is_int($value) || ctype_digit((string)$value)) {
                    $value = (int)$value . 'px';
                }
                $imgAttributes['style'] = isset($imgAttributes['style'])
                    ? $imgAttributes['style'] . "; {$dimension}: {$value}"
                    : "{$dimension}: {$value}";
                unset($attributes[$dimension]);
            }
        }

        foreach ($attributes as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $imgAttributes[$key] = $value;
        }

        if (!isset($imgAttributes['alt']) || trim((string)$imgAttributes['alt']) === '') {
            $imgAttributes['alt'] = $this->generateAltText($originalUrl);
        }

        if (!isset($imgAttributes['loading'])) {
            $imgAttributes['loading'] = 'lazy';
        }

        if ($downloaded === null) {
            $imgAttributes['data-remote'] = '1';
        }

        $imgAttributes['src'] = $thumbUrl;

        $imgTag = '<img' . $this->buildHtmlAttributes($imgAttributes) . '>';

        if (!$wrapWithAnchor) {
            return $imgTag;
        }

        $anchorAttributes = [
            'href' => $fullUrl,
            'class' => 'glightbox',
            'data-gallery' => $this->galleryId,
        ];

        return '<a' . $this->buildHtmlAttributes($anchorAttributes) . '>' . $imgTag . '</a>';
    }

    /**
     * Формирует строку атрибутов для HTML тега
     */
    private function buildHtmlAttributes(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = sprintf(' %s="%s"', htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return implode('', $parts);
    }

    /**
     * Генерирует alt-текст на основе имени файла
     */
    private function generateAltText(string $url): string
    {
        $basename = basename(parse_url($url, PHP_URL_PATH) ?? 'image');
        $basename = preg_replace('/\.[a-z0-9]+$/i', '', $basename);
        $basename = str_replace(['_', '-'], ' ', $basename);
        $basename = trim($basename);

        return $basename !== '' ? $basename : 'imported image';
    }

    /**
     * Получить список изображений, которые не удалось загрузить
     */
    public function getFailedImages(): array
    {
        return array_keys($this->failedImages);
    }

    /**
     * Загрузка изображения и создание миниатюры
     * @param string $url
     * @return array|null ['full' => url, 'thumb' => url]
     */
    private function downloadImage(string $url): ?array
    {
        return null;
        // Проверяем, не загружали ли мы уже это изображение
        if (isset($this->downloadedImages[$url])) {
            error_log("BBCodeParser: Image already downloaded (cached): {$url}");
            return $this->downloadedImages[$url];
        }

        try {
            // Убеждаемся, что директория существует
            $this->ensureUploadDirectory();
            
            // Генерируем уникальное имя файла
            $this->imageCounter++;
            $timestamp = time();
            $randomId = mt_rand(100000000, 999999999);
            
            // Загружаем изображение с увеличенным таймаутом
            error_log("BBCodeParser: Downloading image from: {$url}");
            
            $imageContent = $this->fetchImageContent($url);

            if ($imageContent === null) {
                return null;
            }
            
            error_log("BBCodeParser: Downloaded " . strlen($imageContent) . " bytes");

            // Проверяем, что это действительно изображение
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                error_log("Invalid image type for URL {$url}: {$mimeType}");
                return null;
            }
            
            // Определяем расширение файла на основе MIME-типа
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/bmp' => 'bmp',
            ];
            $extension = $extensions[$mimeType] ?? 'png';
            
            $filename = $timestamp . '_' . $randomId . '.' . $extension;
            $thumbFilename = $timestamp . '_' . $randomId . '_thumb.' . $extension;

            $fullPath = $this->uploadPath . $filename;
            $thumbPath = $this->uploadPath . $thumbFilename;

            error_log("BBCodeParser: Saving image to: {$fullPath}");
            
            // Сохраняем оригинал (убираем @ чтобы видеть ошибки)
            $bytesWritten = file_put_contents($fullPath, $imageContent);
            if ($bytesWritten === false) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                error_log("BBCodeParser: Failed to save image to: {$fullPath}. Error: {$errorMsg}");
                return null;
            }
            
            error_log("BBCodeParser: Successfully saved image ({$bytesWritten} bytes): {$fullPath}");
            
            // Устанавливаем права доступа
            chmod($fullPath, 0644);

            // Создаем миниатюру
            if (!$this->createThumbnail($fullPath, $thumbPath)) {
                error_log("Failed to create thumbnail for: {$fullPath}");
                // Не возвращаем null, используем только оригинал
                $result = [
                    'full' => $this->uploadUrl . $filename,
                    'thumb' => $this->uploadUrl . $filename // Используем оригинал вместо миниатюры
                ];
                
                $this->downloadedImages[$url] = $result;
                return $result;
            }
            
            // Устанавливаем права доступа на миниатюру
            @chmod($thumbPath, 0644);

            $result = [
                'full' => $this->uploadUrl . $filename,
                'thumb' => $this->uploadUrl . $thumbFilename
            ];

            $this->downloadedImages[$url] = $result;

            return $result;

        } catch (Exception $e) {
            error_log("Error downloading image {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Попытка загрузить изображение несколькими способами
     */
    private function fetchImageContent(string $url): ?string
    {
        $attempts = 0;
        $maxAttempts = 2;
        $content = null;

        while ($attempts < $maxAttempts) {
            $attempts++;

            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    CURLOPT_HTTPHEADER => [
                        'Accept: image/*,*/*',
                        'Accept-Language: en-US,en;q=0.9',
                        'Referer: ' . parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
                    ],
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                ]);

                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($content !== false && $httpCode >= 200 && $httpCode < 300) {
                    return $content;
                }

                error_log("BBCodeParser: cURL attempt {$attempts} failed for {$url}. HTTP {$httpCode}. Error: {$curlError}");
            }

            // Попытка через file_get_contents как резерв
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'follow_location' => true,
                    'header' => "Accept: image/*,*/*\r\nAccept-Language: en-US,en;q=0.9\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $content = @file_get_contents($url, false, $context);
            if ($content !== false && !empty($content)) {
                return $content;
            }

            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            error_log("BBCodeParser: file_get_contents attempt {$attempts} failed for {$url}. Error: {$errorMsg}");

            usleep(250000); // короткая пауза между попытками
        }

        error_log("BBCodeParser: Failed to download image after {$maxAttempts} attempts: {$url}");
        return null;
    }

    /**
     * Создание миниатюры изображения
     * @param string $sourcePath
     * @param string $thumbPath
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    private function createThumbnail(string $sourcePath, string $thumbPath, int $maxWidth = 200, int $maxHeight = 200): bool
    {
        try {
            // Получаем информацию об изображении
            $imageInfo = getimagesize($sourcePath);
            
            if ($imageInfo === false) {
                // Если не удалось определить тип, копируем оригинал
                copy($sourcePath, $thumbPath);
                return true;
            }

            list($width, $height, $type) = $imageInfo;

            // Создаем изображение из файла в зависимости от типа
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($sourcePath);
                    break;
                default:
                    copy($sourcePath, $thumbPath);
                    return true;
            }

            if ($source === false) {
                copy($sourcePath, $thumbPath);
                return true;
            }

            // Вычисляем новые размеры с сохранением пропорций
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            
            // Если изображение меньше максимальных размеров, оставляем как есть
            if ($ratio >= 1) {
                copy($sourcePath, $thumbPath);
                imagedestroy($source);
                return true;
            }

            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            // Создаем новое изображение
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($thumb === false) {
                imagedestroy($source);
                copy($sourcePath, $thumbPath);
                return true;
            }

            // Для PNG сохраняем прозрачность
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Изменяем размер
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Сохраняем миниатюру
            $result = imagepng($thumb, $thumbPath, 9);

            // Освобождаем память
            imagedestroy($source);
            imagedestroy($thumb);
            
            return $result !== false;

        } catch (Exception $e) {
            error_log("Error creating thumbnail: " . $e->getMessage());
            // В случае ошибки копируем оригинал
            @copy($sourcePath, $thumbPath);
            return false;
        }
    }

    /**
     * Парсинг основных тегов форматирования
     * @param string $text
     * @return string
     */
    private function parseBasicTags(string $text): string
    {
        $replacements = [
            // Жирный
            '/\[B\](.*?)\[\/B\]/is' => '<strong>$1</strong>',
            
            // Курсив
            '/\[I\](.*?)\[\/I\]/is' => '<em>$1</em>',
            
            // Подчеркнутый
            '/\[U\](.*?)\[\/U\]/is' => '<u>$1</u>',
            
            // Зачеркнутый
            '/\[S\](.*?)\[\/S\]/is' => '<s>$1</s>',
            
            // Размер текста -> Bootstrap heading classes (clamped 1..6)
            '/\[SIZE=(\d+)\](.*?)\[\/SIZE\]/is' => function($matches) {
                $size = (int)$matches[1];
                $content = $matches[2];

                // Ограничиваем размер в диапазоне 1..6 (h1..h6)
                if ($size < 1) {
                    $size = 1;
                } elseif ($size > 6) {
                    $size = 6;
                }

                return '<p class="h' . $size . '">' . $content . '</p>';
            },
            
            // Цвет
            '/\[COLOR=([^\]]+)\](.*?)\[\/COLOR\]/is' => '<span style="color: $1">$2</span>',
            
            // Моноширинный шрифт
            '/\[FONT=([^\]]+)\](.*?)\[\/FONT\]/is' => '<span style="font-family: $1">$2</span>',
            
            // Заголовки с классами Bootstrap display-1..display-4
            '/\[HEADING=(\d+)\](.*?)\[\/HEADING\]/is' => function($matches) {
                $level = (int)$matches[1];
                $content = $matches[2];
                if ($level < 1) $level = 1;
                if ($level > 4) $level = 4;
                return '<h1 class="display-' . $level . '">' . $content . '</h1>';
            },
            // Без значения по умолчанию -> display-1
            '/\[HEADING\](.*?)\[\/HEADING\]/is' => '<h1 class="display-1">$1</h1>',
        ];

        foreach ($replacements as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $text = preg_replace_callback($pattern, $replacement, $text);
            } else {
                $text = preg_replace($pattern, $replacement, $text);
            }
        }

        return $text;
    }

    /**
     * Парсинг списков
     * @param string $text
     * @return string
     */
    private function parseLists(string $text): string
    {
        // Обрабатываем [LIST] теги
        $text = preg_replace_callback(
            '/\[LIST\](.*?)\[\/LIST\]/is',
            function($matches) {
                $content = $matches[1];
                
                // ВАЖНО: Удаляем переносы строк, чтобы nl2br() не добавил <br> в список
                $content = str_replace(["\r\n", "\r", "\n"], '', $content);
                
                // Заменяем элементы списка [*]
                $content = preg_replace('/\[\*\]\s*/i', '<li>', $content);
                
                // Закрываем теги li (опционально, для чистоты)
                $content = preg_replace('/<li>(.*?)(?=<li>|\s*$)/is', '<li>$1</li>', $content);
                
                return '<ul>' . $content . '</ul>';
            },
            $text
        );

        // Обрабатываем упорядоченные списки
        $text = preg_replace_callback(
            '/\[LIST=1\](.*?)\[\/LIST\]/is',
            function($matches) {
                $content = $matches[1];
                
                // ВАЖНО: Удаляем переносы строк, чтобы nl2br() не добавил <br> в список
                $content = str_replace(["\r\n", "\r", "\n"], '', $content);
                
                $content = preg_replace('/\[\*\]\s*/i', '<li>', $content);
                $content = preg_replace('/<li>(.*?)(?=<li>|\s*$)/is', '<li>$1</li>', $content);
                return '<ol>' . $content . '</ol>';
            },
            $text
        );

        return $text;
    }

    /**
     * Парсинг таблиц [TABLE][TR][TD]
     * @param string $text
     * @return string
     */
    private function parseTables(string $text): string
    {
        // Обрабатываем таблицы
        $text = preg_replace_callback(
            '/\[TABLE\](.*?)\[\/TABLE\]/is',
            function($matches) {
                $tableContent = $matches[1];
                
                // ВАЖНО: Удаляем все переносы строк из содержимого таблицы
                // чтобы nl2br() потом не добавил <br> внутрь таблицы
                $tableContent = str_replace(["\r\n", "\r", "\n"], '', $tableContent);
                
                // Обрабатываем строки таблицы [TR]
                $tableContent = preg_replace_callback(
                    '/\[TR\](.*?)\[\/TR\]/is',
                    function($trMatches) {
                        $rowContent = $trMatches[1];
                        
                        // Обрабатываем ячейки [TD]
                        $rowContent = preg_replace_callback(
                            '/\[TD\](.*?)\[\/TD\]/is',
                            function($tdMatches) {
                                $cellContent = trim($tdMatches[1]);
                                // Если ячейка пустая, добавляем неразрывный пробел
                                if (empty($cellContent)) {
                                    $cellContent = '&nbsp;';
                                }
                                return '<td>' . $cellContent . '</td>';
                            },
                            $rowContent
                        );
                        
                        // Обрабатываем заголовочные ячейки [TH] (если есть)
                        $rowContent = preg_replace_callback(
                            '/\[TH\](.*?)\[\/TH\]/is',
                            function($thMatches) {
                                $cellContent = trim($thMatches[1]);
                                if (empty($cellContent)) {
                                    $cellContent = '&nbsp;';
                                }
                                return '<th>' . $cellContent . '</th>';
                            },
                            $rowContent
                        );
                        
                        return '<tr>' . $rowContent . '</tr>';
                    },
                    $tableContent
                );
                
                // Оборачиваем в table с классами Bootstrap
                return '<div class="table-responsive"><table class="table table-bordered table-striped">' . $tableContent . '</table></div>';
            },
            $text
        );

        return $text;
    }

    /**
     * Парсинг ссылок
     * @param string $text
     * @return string
     */
    private function parseLinks(string $text): string
    {
        // [URL=link unfurl="true"]text[/URL] или [URL="link" unfurl="true"]text[/URL]
        $text = preg_replace(
            '/\[URL=[\'"]?(.*?)[\'"]?\s+unfurl=[\'"]?true[\'"]?\](.*?)\[\/URL\]/is',
            '<a href="$1" target="_blank">$2</a>',
            $text
        );

        // [URL unfurl="true"]link[/URL]
        $text = preg_replace(
            '/\[URL\s+unfurl=[\'"]?true[\'"]?\](.*?)\[\/URL\]/is',
            '<a href="$1" target="_blank">$1</a>',
            $text
        );

        // [URL=link]text[/URL]
        $text = preg_replace(
            '/\[URL=[\'"]?(.*?)[\'"]?\](.*?)\[\/URL\]/is',
            '<a href="$1" target="_blank">$2</a>',
            $text
        );

        // [URL]link[/URL]
        $text = preg_replace(
            '/\[URL\](.*?)\[\/URL\]/is',
            '<a href="$1" target="_blank">$1</a>',
            $text
        );

        return $text;
    }

    /**
     * Парсинг выравнивания
     * @param string $text
     * @return string
     */
    private function parseAlignment(string $text): string
    {
        $alignments = [
            'LEFT' => 'left',
            'CENTER' => 'center',
            'RIGHT' => 'right',
        ];

        foreach ($alignments as $bbTag => $cssAlign) {
            $text = preg_replace(
                '/\[' . $bbTag . '\](.*?)\[\/' . $bbTag . '\]/is',
                '<div style="text-align: ' . $cssAlign . '">$1</div>',
                $text
            );
        }

        return $text;
    }

    /**
     * Парсинг цитат и кода
     * @param string $text
     * @return string
     */
    /**
     * Извлечение информации о цитатах (для reply_to_id)
     * Удаляет цитаты из сообщения, если это ответ
     * @param string $text
     * @return string
     */
    private function extractQuoteInfo(string $text): string
    {
        // Ищем цитаты с указанием post_id: [QUOTE="username, post: 12345, member: 67890"]...[/QUOTE]
        if (preg_match('/\[QUOTE=[\'"]?.*?post:\s*(\d+).*?[\'"]?\]/is', $text, $matches)) {
            $this->replyToPostId = (int)$matches[1];
            
            // Удаляем ВСЕ цитаты из сообщения, так как это ответ
            $text = preg_replace('/\[QUOTE=?[^\]]*\](.*?)\[\/QUOTE\]/is', '', $text);
            $text = trim($text);
        }

        return $text;
    }

    private function parseQuotesAndCode(string $text): string
    {
        // Если цитаты еще остались (не были удалены в extractQuoteInfo), обрабатываем их
        // Цитаты с автором и post_id: [QUOTE="username, post: 12345"]
        $text = preg_replace(
            '/\[QUOTE=[\'"]?(.*?),\s*post:\s*\d+.*?[\'"]?\](.*?)\[\/QUOTE\]/is',
            '<blockquote class="blockquote"><footer class="blockquote-footer">$1</footer>$2</blockquote>',
            $text
        );

        // Цитаты с автором
        $text = preg_replace(
            '/\[QUOTE=[\'"]?(.*?)[\'"]?\](.*?)\[\/QUOTE\]/is',
            '<blockquote class="blockquote"><footer class="blockquote-footer">$1</footer>$2</blockquote>',
            $text
        );

        // Простые цитаты
        $text = preg_replace(
            '/\[QUOTE\](.*?)\[\/QUOTE\]/is',
            '<blockquote class="blockquote">$1</blockquote>',
            $text
        );

        // Код
        $text = preg_replace(
            '/\[CODE\](.*?)\[\/CODE\]/is',
            '<pre><code>$1</code></pre>',
            $text
        );

        return $text;
    }

    /**
     * Парсинг медиа-контента (YouTube, Twitch и др.)
     * @param string $text
     * @return string
     */
    private function parseMedia(string $text): string
    {
        // [MEDIA=youtube]video_id[/MEDIA]
        $text = preg_replace(
            '/\[MEDIA=youtube\](.*?)\[\/MEDIA\]/is',
            '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="https://www.youtube.com/embed/$1" allowfullscreen></iframe></div>',
            $text
        );

        // [MEDIA=twitch]channel_name[/MEDIA]
        $text = preg_replace(
            '/\[MEDIA=twitch\](.*?)\[\/MEDIA\]/is',
            '<a href="https://twitch.tv/$1" target="_blank" class="twitch-link">https://twitch.tv/$1</a>',
            $text
        );

        // [MEDIA=vimeo]video_id[/MEDIA]
        $text = preg_replace(
            '/\[MEDIA=vimeo\](.*?)\[\/MEDIA\]/is',
            '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="https://player.vimeo.com/video/$1" allowfullscreen></iframe></div>',
            $text
        );

        return $text;
    }

    /**
     * Получить статистику загруженных изображений
     * @return array
     */
    public function getImageStats(): array
    {
        return [
            'total' => count($this->downloadedImages),
            'images' => $this->downloadedImages
        ];
    }

    /**
     * Получить ID поста, на который отвечает текущее сообщение
     * @return int|null
     */
    public function getReplyToPostId(): ?int
    {
        return $this->replyToPostId;
    }
}
