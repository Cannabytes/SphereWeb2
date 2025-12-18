<?php

namespace backup;

class custom_twig
{
    /**
     * Форматировать байты в читаемый формат для использования в шаблонах
     */
    public function formatBytes($bytes, $precision = 2)
    {
        return backup::formatBytes($bytes, $precision);
    }

    /**
     * Получить статус бейдж класс
     */
    public function getStatusBadgeClass($status)
    {
        $classes = [
            'completed' => 'bg-success',
            'in_progress' => 'bg-info',
            'failed' => 'bg-danger',
            'pending' => 'bg-warning',
            'cancelled' => 'bg-secondary',
        ];

        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Получить иконку статуса
     */
    public function getStatusIcon($status)
    {
        $icons = [
            'completed' => '✓',
            'in_progress' => '⟳',
            'failed' => '✗',
            'pending' => '◐',
            'cancelled' => '⊗',
        ];

        return $icons[$status] ?? '?';
    }

    /**
     * Получить тип иконку
     */
    public function getTypeIcon($type)
    {
        $icons = [
            'db' => 'bi-database',
            'site' => 'bi-folder',
            'db_and_files' => 'bi-layers',
        ];

        return $icons[$type] ?? 'bi-file';
    }

    /**
     * Получить формат иконку
     */
    public function getFormatIcon($format)
    {
        $icons = [
            'zip' => 'bi-file-zip',
            'gzip' => 'bi-file-earmark-zip',
            'bzip2' => 'bi-file-earmark-zip',
        ];

        return $icons[$format] ?? 'bi-file-archive';
    }

    /**
     * Получить поддерживаемые форматы для выбора
     */
    public function getSupportedFormats()
    {
        return backup::getSupportedArchiveFormats();
    }

    /**
     * Получить информацию о требованиях
     */
    public function getRequirements()
    {
        return installer::checkRequirements();
    }

    /**
     * Проверить если расширение поддерживается
     */
    public function isExtensionAvailable($extension)
    {
        return extension_loaded($extension);
    }

    /**
     * Получить размер БД
     */
    public function getDatabaseSize()
    {
        try {
            $sizeInfo = \Ofey\Logan22\model\db\sql::run("SELECT 
                ROUND(SUM(data_length + index_length), 0) as size 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()")->fetch(\PDO::FETCH_ASSOC);
            return $sizeInfo['size'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Получить список таблиц БД
     */
    public function getDatabaseTables()
    {
        return backup::getDatabaseTables();
    }

    /**
     * Получить количество таблиц
     */
    public function getDatabaseTableCount()
    {
        return count(backup::getDatabaseTables());
    }

    /**
     * Получить свободное место на диске
     */
    public function getFreeDiskSpace()
    {
        return disk_free_space('/');
    }

    /**
     * Получить общее место на диске
     */
    public function getTotalDiskSpace()
    {
        return disk_total_space('/');
    }

    /**
     * Получить процент используемого места
     */
    public function getDiskUsagePercent()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total > 0) {
            return round((($total - $free) / $total) * 100, 2);
        }

        return 0;
    }

    /**
     * Проверить если достаточно места для бекапа
     */
    public function hasEnoughDiskSpace($requiredSize)
    {
        $free = disk_free_space('/');
        return $free > $requiredSize;
    }

    /**
     * Получить информацию о PHP
     */
    public function getPHPInfo()
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }
}
