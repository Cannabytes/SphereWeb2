<?php

namespace backup;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\component\alert\board;
use ReflectionClass;

// Ensure plugin classes are loaded when this file is included by the router
require_once __DIR__ . '/installer.php';
require_once __DIR__ . '/BackupManager.php';
require_once __DIR__ . '/ArchiveHandler.php';
require_once __DIR__ . '/custom_twig.php';

class backup
{
    private ?string $nameClass = null;
    private string $backupDir = 'uploads/backup';
    private const BACKUP_TABLE = 'backup_tasks';

    /**
     * Получить имя класса плагина
     */
    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }
        return $this->nameClass;
    }

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Инициализировать таблицу БД при необходимости
        try {
            if (!self::isTableExists(self::BACKUP_TABLE)) {
                installer::createTables();
                installer::createDirectories();
            } else {
                installer::migrateDatabase();
            }
        } catch (\Exception $e) {
            // Silent error - table may already exist
        }

        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
            'pluginActive' => (bool)plugin::getPluginActive($this->getNameClass()) ?? false,
        ]);
    }

    /**
     * Получить диагностику хостинга
     */
    public static function getHostDiagnostics(): array
    {
        $diagnostics = [
            'php_version' => [
                'label' => 'PHP Version',
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'error',
                'message' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'Compatible' : 'PHP 7.4+ required',
            ],
            'memory_limit' => [
                'label' => 'Memory Limit',
                'value' => ini_get('memory_limit'),
                'status' => 'ok',
                'message' => 'Current PHP memory limit',
            ],
            'max_execution_time' => [
                'label' => 'Max Execution Time',
                'value' => ini_get('max_execution_time') . 's',
                'status' => 'ok',
                'message' => 'Current timeout setting',
            ],
            'available_memory' => [
                'label' => 'Available System Memory',
                'value' => self::formatBytes(self::getAvailableMemory()),
                'status' => self::getAvailableMemory() > 52428800 ? 'ok' : 'warning', // 50MB
                'message' => self::getAvailableMemory() > 52428800 ? 'Sufficient' : 'Low memory available',
            ],
            'free_disk_space' => [
                'label' => 'Free Disk Space',
                'value' => (function() {
                    $path = realpath('.') ?: __DIR__;
                    $free = @disk_free_space($path);
                    return self::formatBytes($free === false ? 0 : $free);
                })(),
                'status' => (function() {
                    $path = realpath('.') ?: __DIR__;
                    $free = @disk_free_space($path);
                    return ($free !== false && $free > 1073741824) ? 'ok' : 'warning';
                })(), // 1GB
                'message' => (function() {
                    $path = realpath('.') ?: __DIR__;
                    $free = @disk_free_space($path);
                    return ($free !== false && $free > 1073741824) ? 'Sufficient' : 'Low disk space';
                })(),
            ],
            'backup_dir_exists' => [
                'label' => 'Backup Directory',
                'value' => is_dir('uploads/backup') ? 'Exists' : 'Not exists',
                'status' => is_dir('uploads/backup') ? 'ok' : 'warning',
                'message' => is_dir('uploads/backup') ? 'Ready' : 'Will be created',
            ],
            'backup_dir_writable' => [
                'label' => 'Backup Directory Writable',
                'value' => is_writable('uploads/backup') ? 'Yes' : 'No',
                'status' => is_writable('uploads/backup') ? 'ok' : 'error',
                'message' => is_writable('uploads/backup') ? 'Writable' : 'Not writable',
            ],
        ];

        // Проверка поддержаиваемых расширений
        $supported = self::getSupportedArchiveFormats();
        $supportedList = is_array($supported) ? implode(', ', array_values($supported)) : (string)$supported;
        $diagnostics['extensions'] = [
            'label' => 'Archive Extensions',
            'value' => $supportedList,
            'status' => (is_array($supported) && count($supported) > 0) ? 'ok' : 'error',
            'message' => (is_array($supported) && count($supported) > 0) ? 'Available' : 'No extensions',
        ];

        // Информация о БД
        try {
                // Use a non-reserved alias for DATABASE() to avoid syntax errors on some servers
                $dbRow = sql::getRow("SELECT VERSION() as version, DATABASE() as db_name");
                if ($dbRow) {
                    $diagnostics['mysql_version'] = [
                        'label' => 'MySQL Version',
                        'value' => $dbRow['version'] ?? 'Unknown',
                        'status' => 'ok',
                        'message' => 'Database server version',
                    ];
                    $diagnostics['database_name'] = [
                        'label' => 'Database Name',
                        'value' => $dbRow['db_name'] ?? 'Unknown',
                        'status' => 'ok',
                        'message' => 'Current database',
                    ];
                } else {
                    $err = sql::exception();
                    $errInfo = sql::errorInfo();
                    $msg = '';
                    if ($err instanceof \PDOException) {
                        $msg = $err->getMessage();
                    } elseif (is_array($errInfo)) {
                        $msg = implode(' | ', $errInfo);
                    } else {
                        $msg = (string)$errInfo;
                    }

                    $diagnostics['mysql_version'] = [
                        'label' => 'MySQL Version',
                        'value' => 'Unknown',
                        'status' => 'warning',
                        'message' => $msg ?: 'No DB connection',
                    ];
                    $diagnostics['database_name'] = [
                        'label' => 'Database Name',
                        'value' => 'Unknown',
                        'status' => 'warning',
                        'message' => $msg ?: 'No DB connection',
                    ];
                }

            // Получить размер БД
            $sizeInfo = sql::getRow("SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()");
            $diagnostics['database_size'] = [
                'label' => 'Database Size',
                'value' => (($sizeInfo['size_mb'] ?? 0) . ' MB'),
                'status' => 'ok',
                'message' => 'Current database size',
            ];
        } catch (\Exception $e) {
            // Silent error
        }

        return $diagnostics;
    }

    /**
     * Получить поддерживаемые форматы архивов
     */
    public static function getSupportedArchiveFormats(): array
    {
        $formats = [];

        if (extension_loaded('zip')) {
            $formats['zip'] = 'ZIP (.zip)';
        }

        if (extension_loaded('zlib')) {
            $formats['gzip'] = 'GZIP (.tar.gz)';
            $formats['bzip2'] = 'BZIP2 (.tar.bz2)';
        }

        // Возвращаем хотя бы ZIP как fallback
        if (empty($formats)) {
            $formats['zip'] = 'ZIP (.zip)';
        }

        return $formats;
    }

    /**
     * Получить доступную память системы
     */
    private static function getAvailableMemory(): float
    {
        if (function_exists('shell_exec')) {
            // Не используем shell_exec, так как может быть запрещено
        }

        // Fallback: используем memory_limit
        $limit = self::returnBytes(ini_get('memory_limit'));
        return $limit > 0 ? $limit : 134217728; // Default 128MB
    }

    /**
     * Конвертировать строку памяти в байты
     */
    private static function returnBytes(string $value): float
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int)$value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Форматировать байты в читаемый формат
     */
    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Получить таблицы БД
     */
    public static function getDatabaseTables(): array
    {
        try {
            $dbName = defined('DB_NAME') ? DB_NAME : '';
            if (empty($dbName)) {
                return [];
            }
            
            $result = sql::run("SHOW TABLES FROM `" . $dbName . "`");
            $tables = [];
            while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            return $tables;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получить структуру БД (CREATE TABLE)
     */
    public static function getTableStructure(string $tableName): string
    {
        try {
            // Экранировать имя таблицы для безопасности
            $escapedTable = '`' . str_replace('`', '``', $tableName) . '`';
            
            $result = sql::run("SHOW CREATE TABLE " . $escapedTable);
            $row = $result->fetch(\PDO::FETCH_NUM);
            return $row[1] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Экспортировать таблицу в SQL
     */
    public static function exportTableSQL(string $tableName, int $offset = 0, int $limit = 1000): array
    {
        try {
            // Получить CREATE TABLE
            $createTableSQL = self::getTableStructure($tableName);

            // Получить данные - экранировать имя таблицы
            $escapedTable = '`' . str_replace('`', '``', $tableName) . '`';
            $result = sql::run("SELECT * FROM " . $escapedTable . " LIMIT " . (int)$offset . ", " . (int)$limit);
            $rows = $result->fetchAll(\PDO::FETCH_ASSOC);

            $output = '';

            // Если это первый batch, добавить CREATE TABLE
            if ($offset === 0) {
                $output .= $createTableSQL . ";\n\n";
            }

            // Добавить INSERT statements
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', array_map(function($c) { return str_replace('`', '``', $c); }, $columns)) . '`';

                // Create a single multi-row INSERT for this batch to reduce file size and speed up import
                $tuples = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            // Escape single quote and backslash
                            $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
                            $values[] = "'" . $escaped . "'";
                        }
                    }
                    $tuples[] = '(' . implode(', ', $values) . ')';
                }

                if (!empty($tuples)) {
                    $output .= "INSERT INTO " . $escapedTable . " (" . $columnList . ") VALUES \n" . implode(",\n", $tuples) . ";\n";
                }
            }

            return [
                'success' => true,
                'sql' => $output,
                'rowCount' => count($rows),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sql' => '',
                'rowCount' => 0,
            ];
        }
    }

    /**
     * Получить список файлов для бекапа
     */
    public static function getFilesToBackup(string $rootPath = '.', int $maxDepth = 5, int $currentDepth = 0, array $exclude = []): array
    {
        $files = [];
        $defaultExclude = [
            'vendor',
            'node_modules',
            '.git',
            '.env',
            'uploads/backup',
            'uploads/avatar',
            'uploads/images',
            '__pycache__',
            '.idea',
            '.vscode',
        ];

        $exclude = array_merge($defaultExclude, $exclude);

        if ($currentDepth >= $maxDepth) {
            return $files;
        }

        try {
            $items = @scandir($rootPath);
            if ($items === false) {
                return $files;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $rootPath . DIRECTORY_SEPARATOR . $item;
                $shouldExclude = false;

                foreach ($exclude as $exc) {
                    if (strpos($fullPath, $exc) !== false) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if ($shouldExclude) {
                    continue;
                }

                if (is_dir($fullPath)) {
                    $files = array_merge($files, self::getFilesToBackup($fullPath, $maxDepth, $currentDepth + 1, $exclude));
                } elseif (is_file($fullPath) && is_readable($fullPath)) {
                    $files[] = [
                        'path' => $fullPath,
                        'size' => filesize($fullPath),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silent error
        }

        return $files;
    }

    /**
     * Получить размер всех файлов для бекапа
     */
    public static function getFilesBackupSize(): float
    {
        $files = self::getFilesToBackup();
        $size = 0;
        foreach ($files as $file) {
            $size += $file['size'] ?? 0;
        }
        return $size;
    }

    /**
     * Проверить доступ администратора
     */
    private function requireAdmin(): bool
    {
        // Use the common admin validation so unauthorized users are redirected
        \Ofey\Logan22\model\admin\validation::user_protection('admin');
        return true;
    }

    /**
     * Проверить существование таблицы
     */
    private static function isTableExists(string $tableName): bool
    {
        try {
            $result = sql::run("SHOW TABLES LIKE '" . $tableName . "'");
            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Отправить JSON ответ
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Админ-панель (главная страница)
     */
    public function adminPanel(): void
    {
        $this->requireAdmin();

        // Получить диагностику
        $diagnostics = self::getHostDiagnostics();
        $backups = sql::getRows("SELECT * FROM " . self::BACKUP_TABLE . " ORDER BY created_at DESC LIMIT 20");
        $backupCount = sql::run("SELECT COUNT(*) as count FROM " . self::BACKUP_TABLE)->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0;

        // Получить информацию о форматах архивов
        $supportedFormats = self::getSupportedArchiveFormats();

        tpl::addVar([
            'diagnostics' => $diagnostics,
            'backups' => $backups,
            'backupCount' => $backupCount,
            'supportedFormats' => $supportedFormats,
            'databaseSize' => self::formatBytes($this->getDatabaseSize()),
            'filesSize' => self::formatBytes(self::getFilesBackupSize()),
        ]);

        tpl::displayPlugin('backup/tpl/admin/index.html');
    }

    /**
     * Получить размер базы данных
     */
    private function getDatabaseSize(): float
    {
        try {
            $sizeInfo = sql::run("SELECT 
                ROUND(SUM(data_length + index_length), 0) as size 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()")->fetch(\PDO::FETCH_ASSOC);
            return $sizeInfo['size'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * API: инициировать новый бекап
     */
    public function apiInitBackup(): void
    {
        $this->requireAdmin();

        $type = $_POST['type'] ?? 'db';
        $format = $_POST['format'] ?? 'zip';

        if (!in_array($type, ['db', 'site', 'db_and_files'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid backup type']);
        }

        if (!array_key_exists($format, self::getSupportedArchiveFormats())) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid format']);
        }

        $manager = new BackupManager();
        $result = $manager->initBackup($type, $format);

        $this->jsonResponse($result);
    }

    /**
     * API: запустить бекап
     */
    public function apiStartBackup(): void
    {
        $this->requireAdmin();

        $taskId = (int)($_POST['taskId'] ?? 0);

        if ($taskId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid task ID']);
        }

        $manager = new BackupManager();
        $result = $manager->startBackup($taskId);

        $this->jsonResponse($result);
    }

    /**
     * API: получить статус задачи
     */
    public function apiGetStatus($taskId): void
    {
        $this->requireAdmin();

        $taskId = (int)$taskId;

        if ($taskId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid task ID']);
        }

        $manager = new BackupManager();
        $result = $manager->getTaskStatus($taskId);

        $this->jsonResponse($result);
    }

    /**
     * API: список бекапов
     */
    public function apiListBackups(): void
    {
        $this->requireAdmin();

        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $manager = new BackupManager();
        $result = $manager->getBackups($limit, $offset);

        $this->jsonResponse($result);
    }

    /**
     * API: удалить бекап
     */
    public function apiDeleteBackup(): void
    {
        $this->requireAdmin();

        $taskId = (int)($_POST['taskId'] ?? 0);

        if ($taskId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid task ID']);
        }

        $manager = new BackupManager();
        $result = $manager->deleteBackup($taskId);

        $this->jsonResponse($result);
    }

    /**
     * API: скачать бекап
     */
    public function apiDownloadBackup($taskId): void
    {
        $this->requireAdmin();

        $taskId = (int)$taskId;

        if ($taskId <= 0) {
            http_response_code(404);
            exit;
        }

        $manager = new BackupManager();
        $result = $manager->downloadBackup($taskId);

        if (!$result['success']) {
            http_response_code(404);
            exit;
        }

        $filePath = $result['filePath'];
        $fileName = $result['fileName'];
        // Безопасная потоковая выдача файла с очисткой буферов и логированием
        try {
            if (!file_exists($filePath) || !is_readable($filePath)) {
                http_response_code(404);
                $this->jsonResponse(['success' => false, 'error' => 'File not found']);
            }

            $size = filesize($filePath);
            $mime = function_exists('mime_content_type') ? @mime_content_type($filePath) : 'application/octet-stream';

            // Очистить все буферы вывода
            while (ob_get_level()) {
                @ob_end_clean();
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $size);

            $fp = fopen($filePath, 'rb');
            if ($fp === false) {
                throw new \Exception('Failed to open file for reading: ' . $filePath);
            }

            // Stream in 8KB chunks
            while (!feof($fp) && connection_status() === CONNECTION_NORMAL) {
                $buffer = fread($fp, 8192);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                @flush();
            }

            fclose($fp);
            exit;
        } catch (\Exception $e) {
            // Логируем для диагностики
            try {
                $logDir = (new BackupManager())->backupDir ?? 'uploads/backup';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . DIRECTORY_SEPARATOR . 'backup_errors.log';
                $entry = '[' . date('Y-m-d H:i:s') . '] Download error for task ' . $taskId . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
            } catch (\Throwable $t) {
                // ignore
            }

            http_response_code(500);
            $this->jsonResponse(['success' => false, 'error' => 'Server error during file download']);
        }
    }

    /**
     * API: получить диагностику
     */
    public function apiGetDiagnostics(): void
    {
        $this->requireAdmin();

        $diagnostics = self::getHostDiagnostics();

        $this->jsonResponse([
            'success' => true,
            'diagnostics' => $diagnostics,
        ]);
    }
}
