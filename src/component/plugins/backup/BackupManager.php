<?php

namespace backup;

use Ofey\Logan22\model\db\sql;

class BackupManager
{
    private const BACKUP_TABLE = 'backup_tasks';
    private const SESSION_KEY = 'backup_current_task';
    private string $backupDir = 'uploads/backup';

    public function __construct()
    {
        // Use the platform session management (do not call native session_start())
        // The platform stores session data in `sphere_session` cookie and DB.

        // Создать директорию если её нет
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Инициировать новый бекап
     */
    public function initBackup(string $type = 'db', string $format = 'zip'): array
    {
        try {
            // Создать запись в БД
            $startTime = date('Y-m-d H:i:s');
            // Append a random suffix (12-14 chars) to prevent predictable backup filenames
            $randLen = random_int(12, 14);
            $rand = $this->randomString($randLen);
            $fileName = date('Y-m-d_H-i-s') . '_' . $type . '_' . $rand . '.' . $this->getFileExtension($format);

            sql::run(
                "INSERT INTO " . self::BACKUP_TABLE . " (backup_type, format, status, progress, start_time, file_path, created_at) 
                 VALUES (?, ?, 'pending', 0, ?, ?, NOW())",
                [$type, $format, $startTime, $fileName]
            );

            $taskId = sql::lastInsertId();

            // Сохранить в сессию
            $_SESSION[self::SESSION_KEY] = [
                'task_id' => $taskId,
                'type' => $type,
                'format' => $format,
                'fileName' => $fileName,
                'status' => 'pending',
                'progress' => 0,
                'startTime' => $startTime,
                'processedTables' => 0,
                'totalTables' => 0,
                'currentTable' => '',
                'sqlBuffer' => '',
            ];

            return [
                'success' => true,
                'taskId' => $taskId,
                'fileName' => $fileName,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a cryptographically secure random string using [A-Za-z0-9]
     */
    private function randomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * Получить расширение файла по формату
     */
    private function getFileExtension(string $format): string
    {
        $extensions = [
            'zip' => 'zip',
            'gzip' => 'tar.gz',
            'bzip2' => 'tar.bz2',
        ];
        return $extensions[$format] ?? 'zip';
    }

    /**
     * Запустить процесс резервного копирования
     */
    public function startBackup(int $taskId): array
    {
        try {
            // Получить информацию о задаче
            $taskResult = sql::run("SELECT * FROM " . self::BACKUP_TABLE . " WHERE id = ?", [$taskId]);
            $task = $taskResult->fetch(\PDO::FETCH_ASSOC);

            if (!$task) {
                return ['success' => false, 'error' => 'Task not found'];
            }

            // Обновить статус на 'in_progress'
            sql::run("UPDATE " . self::BACKUP_TABLE . " SET status = ? WHERE id = ?", ['in_progress', $taskId]);

            $backupType = $task['backup_type'];
            $format = $task['format'];
            $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $task['file_path'];

            // Создать архив
            $archive = new ArchiveHandler($format, $filePath);
            if (!$archive->create()) {
                throw new \Exception('Failed to create archive');
            }

            // Выполнить бекап в зависимости от типа
            if ($backupType === 'db') {
                $this->backupDatabase($archive, $taskId);
            } elseif ($backupType === 'site') {
                $this->backupSite($archive, $taskId);
            } elseif ($backupType === 'db_and_files') {
                $this->backupDatabase($archive, $taskId);
                $this->backupFiles($archive, $taskId);
            }

            // Закрыть архив
            if (!$archive->close()) {
                throw new \Exception('Failed to close archive');
            }

            // Получить размер архива
            $size = $archive->getSize();

            // Обновить статус на 'completed'
            sql::run(
                "UPDATE " . self::BACKUP_TABLE . " SET status = ?, progress = ?, end_time = NOW(), size = ? WHERE id = ?",
                ['completed', 100, $size, $taskId]
            );

            // Очистить сессию
            if (isset($_SESSION[self::SESSION_KEY])) {
                unset($_SESSION[self::SESSION_KEY]);
            }

            return [
                'success' => true,
                'taskId' => $taskId,
                'size' => backup::formatBytes($size),
                'fileName' => $task['file_path'],
            ];
        } catch (\Exception $e) {
            // Обновить статус на 'failed'
            sql::run(
                "UPDATE " . self::BACKUP_TABLE . " SET status = ?, error_message = ? WHERE id = ?",
                ['failed', $e->getMessage(), $taskId]
            );

            // Дополнительно логируем в файл для диагностики
            try {
                $logDir = $this->backupDir;
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . DIRECTORY_SEPARATOR . 'backup_errors.log';
                $entry = '[' . date('Y-m-d H:i:s') . '] Task ' . $taskId . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
                @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
            } catch (\Throwable $t) {
                // ignore
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Создать бекап базы данных
     */
    private function backupDatabase(ArchiveHandler $archive, int $taskId): bool
    {
        $tables = backup::getDatabaseTables();
        $totalTables = count($tables);
        $sqlContent = "-- Database Backup\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- Database: " . (defined('DB_NAME') ? DB_NAME : 'unknown') . "\n";
        $sqlContent .= "-- Host: " . (defined('DB_HOST') ? DB_HOST : 'localhost') . "\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $processedTables = 0;

        foreach ($tables as $table) {
            // Пропустить таблицы резервных копий
            if ($table === self::BACKUP_TABLE) {
                continue;
            }

            // Получить структуру
            $sqlContent .= "-- Table: " . $table . "\n";
            $sqlContent .= backup::getTableStructure($table) . ";\n\n";

            // Получить данные пакетами
            $offset = 0;
            $limit = 1000;
            $totalRows = 0;

            while (true) {
                $result = backup::exportTableSQL($table, $offset, $limit);

                if (!$result['success']) {
                    throw new \Exception('Failed to export table SQL for ' . $table . ': ' . ($result['error'] ?? 'unknown'));
                }

                if (empty($result['sql'])) {
                    break;
                }

                $sqlContent .= $result['sql'];
                $totalRows += $result['rowCount'];

                if ($result['rowCount'] < $limit) {
                    break;
                }

                $offset += $limit;
            }

            $sqlContent .= "\n";
            $processedTables++;

            // Обновить прогресс каждые 5 таблиц
            if ($processedTables % 5 === 0 && $totalTables > 0) {
                $progress = (int)(($processedTables / $totalTables) * 50);
                sql::run(
                    "UPDATE " . self::BACKUP_TABLE . " SET progress = ? WHERE id = ?",
                    [$progress, $taskId]
                );
            }
        }

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Add SQL to archive. Zip entries are compressed by ZipArchive, so avoid gzcompress for ZIP.
        $format = method_exists($archive, 'getFormat') ? $archive->getFormat() : 'zip';
        if ($format === 'zip') {
            // Let ZipArchive handle compression internally
            $ok = $archive->addData($sqlContent, 'database_backup.sql');
            if ($ok !== true) {
                throw new \Exception('Failed to add database SQL to archive');
            }
        } else {
            // For TAR-based archives, compress large SQL blobs to reduce temp size
            if (strlen($sqlContent) > 10485760) { // 10MB
                $sqlCompressed = gzcompress($sqlContent, 6);
                $ok = $archive->addData($sqlCompressed, 'database_backup.sql.gz');
                if ($ok !== true) {
                    throw new \Exception('Failed to add compressed database SQL to archive');
                }
            } else {
                $ok = $archive->addData($sqlContent, 'database_backup.sql');
                if ($ok !== true) {
                    throw new \Exception('Failed to add database SQL to archive');
                }
            }
        }

        return true;
    }

    /**
     * Создать бекап файлов
     */
    private function backupFiles(ArchiveHandler $archive, int $taskId): bool
    {
        $files = backup::getFilesToBackup('.', 5, 0);
        $totalFiles = count($files);
        $processedFiles = 0;

        foreach ($files as $file) {
            $filePath = $file['path'];

            if (!file_exists($filePath) || !is_readable($filePath)) {
                continue;
            }

            // Относительный путь для архива
            $arcPath = str_replace(['\\', '\\\\'], '/', $filePath);
            $arcPath = ltrim($arcPath, './');

            $ok = $archive->addFile($filePath, $arcPath);
            if ($ok !== true) {
                throw new \Exception('Failed to add file to archive: ' . $filePath);
            }

            $processedFiles++;

            // Обновить прогресс каждые 50 файлов
            if ($processedFiles % 50 === 0 && $totalFiles > 0) {
                $progress = 50 + (int)(($processedFiles / $totalFiles) * 50);
                sql::run(
                    "UPDATE " . self::BACKUP_TABLE . " SET progress = ? WHERE id = ?",
                    [$progress, $taskId]
                );
            }
        }

        return true;
    }

    /**
     * Alias для backupFiles
     */
    private function backupSite(ArchiveHandler $archive, int $taskId): bool
    {
        return $this->backupFiles($archive, $taskId);
    }

    /**
     * Получить статус задачи
     */
    public function getTaskStatus(int $taskId): array
    {
        try {
            $result = sql::run("SELECT * FROM " . self::BACKUP_TABLE . " WHERE id = ?", [$taskId]);
            $task = $result->fetch(\PDO::FETCH_ASSOC);

            if (!$task) {
                return ['success' => false, 'error' => 'Task not found'];
            }

            return [
                'success' => true,
                'task' => $task,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить все бекапы
     */
    public function getBackups(int $limit = 50, int $offset = 0): array
    {
        try {
            $result = sql::run(
                "SELECT * FROM " . self::BACKUP_TABLE . " ORDER BY created_at DESC LIMIT ?, ?",
                [$offset, $limit]
            );

            $backups = $result->fetchAll(\PDO::FETCH_ASSOC);

            // Получить общее количество
            $countResult = sql::run("SELECT COUNT(*) as count FROM " . self::BACKUP_TABLE);
            $countRow = $countResult->fetch(\PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'backups' => $backups,
                'total' => $countRow['count'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Удалить бекап
     */
    public function deleteBackup(int $taskId): array
    {
        try {
            $result = sql::run("SELECT * FROM " . self::BACKUP_TABLE . " WHERE id = ?", [$taskId]);
            $task = $result->fetch(\PDO::FETCH_ASSOC);

            if (!$task) {
                return ['success' => false, 'error' => 'Backup not found'];
            }

            $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $task['file_path'];

            // Удалить файл
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // Удалить запись из БД
            sql::run("DELETE FROM " . self::BACKUP_TABLE . " WHERE id = ?", [$taskId]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Скачать бекап
     */
    public function downloadBackup(int $taskId): array
    {
        try {
            $result = sql::run("SELECT * FROM " . self::BACKUP_TABLE . " WHERE id = ?", [$taskId]);
            $task = $result->fetch(\PDO::FETCH_ASSOC);

            if (!$task) {
                return ['success' => false, 'error' => 'Backup not found'];
            }

            $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $task['file_path'];

            if (!file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            return [
                'success' => true,
                'filePath' => $filePath,
                'fileName' => $task['file_path'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
