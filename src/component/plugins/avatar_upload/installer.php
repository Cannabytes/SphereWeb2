<?php

namespace Ofey\Logan22\component\plugins\avatar_upload;

/**
 * Установочный класс для плагина Avatar Upload
 * Проверяет требования и создает необходимые директории
 */
class installer
{
    /**
     * Проверка требований системы
     */
    public static function checkRequirements(): array
    {
        $requirements = [];
        
        // Проверка PHP версии
        $requirements['php_version'] = [
            'name' => 'PHP Version (>= 7.4)',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'message' => 'Current version: ' . PHP_VERSION
        ];
        
        // Проверка GD библиотеки
        $requirements['gd_library'] = [
            'name' => 'GD Library',
            'status' => extension_loaded('gd'),
            'message' => extension_loaded('gd') ? 'GD Library installed' : 'GD Library not found'
        ];
        
        // Проверка поддержки WebP
        if (extension_loaded('gd')) {
            $requirements['webp_support'] = [
                'name' => 'WebP Support',
                'status' => function_exists('imagewebp'),
                'message' => function_exists('imagewebp') ? 'WebP supported' : 'WebP not supported'
            ];
        }
        
        // Проверка директории uploads
        $uploadDir = 'uploads/avatar/';
        $requirements['upload_directory'] = [
            'name' => 'Upload Directory',
            'status' => is_dir(dirname($uploadDir)) && is_writable(dirname($uploadDir)),
            'message' => is_writable(dirname($uploadDir)) ? 'Writable' : 'Not writable'
        ];
        
        return $requirements;
    }
    
    /**
     * Создание необходимых директорий
     */
    public static function createDirectories(): bool
    {
        $uploadDir = 'uploads/avatar/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return false;
            }
        }
        
        // Создаем .htaccess для защиты
        $htaccess = $uploadDir . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Защита директории аватаров\n";
            $content .= "Options -Indexes\n";
            $content .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phps|cgi|pl|exe)$\">\n";
            $content .= "    Order allow,deny\n";
            $content .= "    Deny from all\n";
            $content .= "</FilesMatch>\n";
            file_put_contents($htaccess, $content);
        }
        
        return true;
    }
    
    /**
     * Проверка статуса плагина
     */
    public static function getStatus(): array
    {
        $requirements = self::checkRequirements();
        $allPassed = true;
        
        foreach ($requirements as $req) {
            if (!$req['status']) {
                $allPassed = false;
                break;
            }
        }
        
        return [
            'ready' => $allPassed,
            'requirements' => $requirements
        ];
    }
}
