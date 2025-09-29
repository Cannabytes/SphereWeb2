<?php

namespace Ofey\Logan22\component\plugins\wiki;

use RuntimeException;
use Ofey\Logan22\model\plugin\plugin;

/**
 * Simple SQLite singleton for plugin DB (highfive.db) to avoid reopening per call.
 */
final class WikiDb
{
    private static ?\SQLite3 $db = null;
    private static ?string $path = null;

    /** Return absolute path to currently selected DB file. */
    public static function getSelectedPath(): string
    {
        if (self::$path !== null) return self::$path;
        $setting = plugin::getSetting('wiki');
        $file = is_array($setting) && !empty($setting['dbFile']) ? basename((string)$setting['dbFile']) : 'highfive.db';
        $path = __DIR__ . '/db/' . $file;
        self::$path = $path;
        return self::$path;
    }

    /**
     * Get shared read-only SQLite3 connection to highfive.db.
     */
    public static function get(): \SQLite3
    {
        if (self::$db instanceof \SQLite3) {
            return self::$db;
        }
    $path = self::getSelectedPath();
        if (!is_file($path)) {
            throw new RuntimeException('SQLite database not found: ' . $path);
        }
        $db = new \SQLite3($path, \SQLITE3_OPEN_READONLY);
        // Set minimal pragmas safe for read-only usage
        @$db->exec('PRAGMA encoding = "UTF-8"');
        // foreign_keys pragma harmless on RO; ignore failures
        @$db->exec('PRAGMA foreign_keys = ON');
        self::$db = $db;
        return self::$db;
    }

    /**
     * Optional: close the connection explicitly (usually not needed in FPM).
     */
    public static function close(): void
    {
        if (self::$db instanceof \SQLite3) {
            try { self::$db->close(); } catch (\Throwable $e) {}
        }
        self::$db = null;
    self::$path = null;
    }
}
