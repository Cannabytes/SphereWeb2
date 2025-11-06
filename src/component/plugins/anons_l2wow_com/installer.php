<?php

namespace Ofey\Logan22\component\plugins\anons_l2wow_com;

use Ofey\Logan22\model\db\sql;

/**
 * Установочный класс для плагина L2WOW.COM Integration
 * Создает необходимые таблицы в базе данных
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
        
        // Проверка PDO
        $requirements['pdo'] = [
            'name' => 'PDO Extension',
            'status' => extension_loaded('pdo'),
            'message' => extension_loaded('pdo') ? 'PDO installed' : 'PDO not found'
        ];
        
        // Проверка MySQL
        $requirements['pdo_mysql'] = [
            'name' => 'PDO MySQL Driver',
            'status' => extension_loaded('pdo_mysql'),
            'message' => extension_loaded('pdo_mysql') ? 'PDO MySQL installed' : 'PDO MySQL not found'
        ];
        
        // Проверка доступа к базе данных
        try {
            sql::run("SELECT 1");
            $requirements['database_connection'] = [
                'name' => 'Database Connection',
                'status' => true,
                'message' => 'Database accessible'
            ];
        } catch (\Exception $e) {
            $requirements['database_connection'] = [
                'name' => 'Database Connection',
                'status' => false,
                'message' => 'Database not accessible: ' . $e->getMessage()
            ];
        }
        
        return $requirements;
    }
    
    /**
     * Создание необходимых таблиц
     */
    public static function createTables(): bool
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `plugin_anons_l2wow_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `server_id` INT(11) NOT NULL,
                `character_name` VARCHAR(255) NOT NULL,
                `vote_count` INT(11) NOT NULL,
                `items` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `user_id` (`user_id`),
                INDEX `server_id` (`server_id`),
                INDEX `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            sql::run($sql);
            return true;
        } catch (\Exception $e) {
            error_log("Error creating plugin_anons_l2wow_log table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удаление таблиц при удалении плагина
     */
    public static function dropTables(): bool
    {
        try {
            sql::run("DROP TABLE IF EXISTS `plugin_anons_l2wow_log`");
            return true;
        } catch (\Exception $e) {
            error_log("Error dropping plugin_anons_l2wow_log table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка существования таблицы
     */
    public static function tableExists(): bool
    {
        try {
            $result = sql::run("SHOW TABLES LIKE 'plugin_anons_l2wow_log'")->fetch();
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
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
        
        // Проверяем существование таблицы
        $tableExists = self::tableExists();
        
        return [
            'ready' => $allPassed,
            'requirements' => $requirements,
            'table_exists' => $tableExists
        ];
    }
    
    /**
     * Полная установка плагина
     */
    public static function install(): bool
    {
        $status = self::getStatus();
        
        if (!$status['ready']) {
            return false;
        }
        
        // Создаем таблицы
        return self::createTables();
    }
    
    /**
     * Полное удаление плагина
     */
    public static function uninstall(): bool
    {
        return self::dropTables();
    }
}

