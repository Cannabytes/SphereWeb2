<?php

namespace backup;

use Ofey\Logan22\model\db\sql;

class installer
{
    /**
     * Проверить требования системы
     */
    public static function checkRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version (>= 7.4)',
                'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'message' => 'Current: ' . PHP_VERSION,
            ],
            'zip_extension' => [
                'name' => 'ZIP Extension',
                'status' => extension_loaded('zip'),
                'message' => extension_loaded('zip') ? 'Loaded' : 'Not loaded',
            ],
            'gzip_extension' => [
                'name' => 'GZIP Extension',
                'status' => extension_loaded('zlib'),
                'message' => extension_loaded('zlib') ? 'Loaded' : 'Not loaded (optional)',
            ],
            'database_connection' => [
                'name' => 'Database Connection',
                'status' => true,
                'message' => 'Available',
            ],
            'upload_dir_exists' => [
                'name' => 'Upload Directory',
                'status' => is_dir('uploads'),
                'message' => is_dir('uploads') ? 'Exists' : 'Not found',
            ],
            'upload_dir_writable' => [
                'name' => 'Upload Directory Writable',
                'status' => is_writable('uploads'),
                'message' => is_writable('uploads') ? 'Writable' : 'Not writable',
            ],
            'disk_space' => [
                'name' => 'Free Disk Space (>100MB)',
                'status' => disk_free_space('/') > 104857600,
                'message' => backup::formatBytes(disk_free_space('/')) . ' available',
            ],
        ];

        return $requirements;
    }

    /**
     * Создать необходимые директории
     */
    public static function createDirectories(): bool
    {
        $backupDir = 'uploads/backup';

        if (!is_dir($backupDir)) {
            if (!@mkdir($backupDir, 0755, true)) {
                return false;
            }
        }

        // Создать .htaccess для защиты
        $htaccess = $backupDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "Order Deny,Allow\nDeny from all\nAllow from 127.0.0.1\n";
            @file_put_contents($htaccess, $content);
        }

        // Создать index.php для защиты
        $index = $backupDir . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, '<?php // Backup directory' . PHP_EOL);
        }

        return true;
    }

    /**
     * Создать таблицу резервных копий
     */
    public static function createTables(): bool
    {
        try {
            sql::run("CREATE TABLE IF NOT EXISTS `backup_tasks` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `backup_type` ENUM('db', 'site', 'db_and_files') NOT NULL DEFAULT 'db',
                `format` VARCHAR(50) NOT NULL DEFAULT 'zip',
                `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
                `progress` INT NOT NULL DEFAULT 0,
                `start_time` DATETIME NOT NULL,
                `end_time` DATETIME,
                `file_path` VARCHAR(255) NOT NULL,
                `size` BIGINT DEFAULT 0,
                `error_message` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `status_idx` (`status`),
                KEY `created_at_idx` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Удалить таблицу (при удалении плагина)
     */
    public static function dropTables(): bool
    {
        try {
            sql::run("DROP TABLE IF EXISTS `backup_tasks`");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Проверить и обновить структуру таблицы
     */
    public static function migrateDatabase(): bool
    {
        try {
            // Проверить если таблица существует
            $result = sql::run("SHOW TABLES LIKE 'backup_tasks'");
            if ($result->rowCount() === 0) {
                return self::createTables();
            }

            // Проверить и добавить недостающие колонки
            $columns = sql::getRows("SHOW COLUMNS FROM backup_tasks");
            $existingColumns = [];

            foreach ($columns as $column) {
                $existingColumns[] = $column['Field'];
            }

            // Проверить если нужно добавить новые колонки
            $requiredColumns = [
                'id', 'backup_type', 'format', 'status', 'progress',
                'start_time', 'end_time', 'file_path', 'size', 'error_message',
                'created_at', 'updated_at'
            ];

            foreach ($requiredColumns as $column) {
                if (!in_array($column, $existingColumns)) {
                    // Добавить колонку
                    if ($column === 'size') {
                        sql::run("ALTER TABLE backup_tasks ADD COLUMN `size` BIGINT DEFAULT 0");
                    } elseif ($column === 'error_message') {
                        sql::run("ALTER TABLE backup_tasks ADD COLUMN `error_message` TEXT");
                    } elseif ($column === 'updated_at') {
                        sql::run("ALTER TABLE backup_tasks ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Очистить старые файлы бекапов, которые отсутствуют в БД
     */
    public static function cleanupOrphanedFiles(): bool
    {
        try {
            $backupDir = 'uploads/backup';

            if (!is_dir($backupDir)) {
                return true;
            }

            // Получить все файлы из БД
            $backups = sql::getRows("SELECT file_path FROM backup_tasks");
            $dbFiles = [];

            foreach ($backups as $backup) {
                $dbFiles[] = $backup['file_path'];
            }

            // Удалить файлы, которых нет в БД
            $files = @scandir($backupDir);

            if ($files) {
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && $file !== '.htaccess' && $file !== 'index.php') {
                        if (!in_array($file, $dbFiles)) {
                            $filePath = $backupDir . DIRECTORY_SEPARATOR . $file;
                            if (is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
