<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

use Ofey\Logan22\model\db\sql;
use PDO;

/**
 * Пакетная загрузка изображений в фоновом режиме
 * Использует multi-curl для параллельной загрузки
 * 
 * Как использовать:
 * 1. Сначала импортируйте посты в быстром режиме (без загрузки изображений)
 * 2. Затем запустите этот класс для загрузки всех изображений в фоне
 */
class BatchImageDownloader
{
    private int $parallelDownloads = 5; // Количество одновременных загрузок
    
    /**
     * Найти все посты с внешними изображениями
     */
    public function findPostsWithExternalImages(): array
    {
        // Use REGEXP to find img tags with http/https src (supports single or double quotes and varying attributes)
        $posts = sql::getRows(
            "SELECT id, message FROM forum_posts WHERE message REGEXP '<img[^>]*src=[\"\']https?://'"
        );
        
        $result = [];
        foreach ($posts as $post) {
            // Extract all src URLs from img tags, supporting single/double quotes or unquoted (best-effort)
            preg_match_all('/<img[^>]*\bsrc=\s*(?:"|\')?(https?:\/\/[^\"\'\s>]+)(?:"|\')?[^>]*>/i', $post['message'], $matches);
            if (!empty($matches[1])) {
                $result[] = [
                    'post_id' => $post['id'],
                    'message' => $post['message'],
                    'images' => $matches[1]
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Загрузить изображения параллельно (multi-curl)
     */
    public function downloadImagesParallel(array $urls): array
    {
        $results = [];
        $chunks = array_chunk($urls, $this->parallelDownloads);
        
        foreach ($chunks as $chunk) {
            $multiHandle = curl_multi_init();
            $curlHandles = [];
            
            // Добавляем запросы
            foreach ($chunk as $url) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0'
                ]);
                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$url] = $ch;
            }
            
            // Выполняем параллельно
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);
            
            // Собираем результаты
            foreach ($curlHandles as $url => $ch) {
                $content = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($httpCode === 200 && $content) {
                    $results[$url] = $content;
                } else {
                    $results[$url] = null;
                }
                
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
            
            curl_multi_close($multiHandle);
        }
        
        return $results;
    }
    
    /**
     * Сохранить изображения и создать миниатюры
     */
    public function saveImages(array $downloads): array
    {
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
        
        error_log("BatchImageDownloader: Upload directory: {$uploadDir}");
        
        if (!is_dir($uploadDir)) {
            error_log("BatchImageDownloader: Creating directory: {$uploadDir}");
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("BatchImageDownloader: Failed to create directory");
                return [];
            }
        }
        
        $mapping = [];
        
        foreach ($downloads as $url => $imageData) {
            if (!$imageData) {
                continue;
            }
            
            $filename = md5($url) . '.jpg';
            $filepath = $uploadDir . $filename;
            $webPath = '/uploads/forum/' . $filename;
            
            $bytesWritten = file_put_contents($filepath, $imageData);
            if ($bytesWritten === false) {
                error_log("BatchImageDownloader: Failed to save image from: {$url}");
                continue;
            }
            
            error_log("BatchImageDownloader: Saved image ({$bytesWritten} bytes): {$filepath}");
            chmod($filepath, 0644);
            
            // Создаем миниатюру
            $thumbPath = $this->createThumbnail($webPath);
            
            $mapping[$url] = [
                'full' => $webPath,
                'thumb' => $thumbPath
            ];
        }
        
        return $mapping;
    }
    
    /**
     * Обновить посты: заменить внешние URL на локальные
     */
    public function updatePostImages(int $postId, string $message, array $imageMapping): void
    {
        $updated = $message;

        foreach ($imageMapping as $url => $paths) {
            // Build a regex that matches an <img ... src=("|')?URL("|')? ...> tag and replace it
            $escapedUrl = preg_quote($url, '/');
            $pattern = '/<img[^>]*\bsrc=\s*(?:"|\')?' . $escapedUrl . '(?:"|\')?[^>]*>/i';

            $replacement = '<a href="' . $paths['full'] . '" data-lightbox="gallery">'
                         . '<img src="' . $paths['thumb'] . '" class="img-thumbnail" alt="Image" loading="lazy">'
                         . '</a>';

            $updated = preg_replace($pattern, $replacement, $updated);
        }
        
        if ($updated !== $message) {
            sql::run("UPDATE forum_posts SET message = ? WHERE id = ?", [$updated, $postId]);
        }
    }
    
    /**
     * Полный процесс загрузки всех изображений
     */
    public function processAllImages(): array
    {
        $stats = [
            'posts_processed' => 0,
            'images_downloaded' => 0,
            'images_failed' => 0,
            'time_taken' => 0
        ];
        
        $startTime = microtime(true);
        
        echo "Поиск постов с внешними изображениями...\n";
        $postsWithImages = $this->findPostsWithExternalImages();
        echo "Найдено постов: " . count($postsWithImages) . "\n\n";
        
        foreach ($postsWithImages as $post) {
            echo "Обработка поста #{$post['post_id']} (" . count($post['images']) . " изображений)...\n";
            
            // Загружаем изображения параллельно
            $downloads = $this->downloadImagesParallel($post['images']);
            
            // Сохраняем
            $imageMapping = $this->saveImages($downloads);
            
            // Обновляем пост
            $this->updatePostImages($post['post_id'], $post['message'], $imageMapping);
            
            $stats['posts_processed']++;
            $stats['images_downloaded'] += count(array_filter($downloads));
            $stats['images_failed'] += count($downloads) - count(array_filter($downloads));
            
            echo "✓ Загружено: " . count(array_filter($downloads)) . " / " . count($downloads) . "\n\n";
        }
        
        $stats['time_taken'] = round(microtime(true) - $startTime, 2);
        
        return $stats;
    }
    
    /**
     * Создание миниатюры
     */
    private function createThumbnail(string $imagePath): string
    {
        // imagePath уже абсолютный путь к файлу
        $fullPath = $imagePath;
        $thumbPath = str_replace('.jpg', '_thumb.jpg', $imagePath);
        $fullThumbPath = $thumbPath;

        try {
            if (!file_exists($fullPath)) {
                error_log("BatchImageDownloader: Source image not found: {$fullPath}");
                return $imagePath;
            }
            
            $image = @imagecreatefromstring(file_get_contents($fullPath));
            if (!$image) {
                error_log("BatchImageDownloader: Failed to create image from: {$fullPath}");
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
