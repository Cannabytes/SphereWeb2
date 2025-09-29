<?php

namespace Ofey\Logan22\component\plugins\wiki;

/**
 * Lightweight file-based data cache for wiki plugin.
 * Caches results of expensive SQLite reads as PHP arrays (stored in .php files returning the value).
 * Keyed by: selected DB file + logical group + user supplied key.
 * Auto invalidation:
 *  - TTL expiration (default 24h)
 *  - Source DB file mtime newer than cache file -> regenerate
 * Supports storing any serialisable value (array/scalar/object converted to array via var_export).
 */
final class DataCache
{
    /** Base cache directory (inside uploads so it is writable) */
    private static function baseDir(): string
    {
        return \Ofey\Logan22\component\fileSys\fileSys::get_dir('uploads/cache/plugins/wiki/data');
    }

    /** Sanitize a path segment */
    private static function clean(string $segment): string
    {
        $segment = strtolower(trim($segment));
        $segment = preg_replace('/[^a-z0-9._-]+/', '-', $segment);
        if ($segment === null || $segment === '' || $segment === '-' ) {
            return 'd';
        }
        return $segment;
    }

    /** Build full cache path */
    private static function path(string $dbPath, string $group, string $key): string
    {
        $dbFile = basename($dbPath);
        // Use short hash for key to keep filename small but unique
        $hash = substr(sha1($key), 0, 16);
        $safeGroup = self::clean($group);
        $dir = rtrim(self::baseDir(), '/\\') . DIRECTORY_SEPARATOR . self::clean($dbFile) . DIRECTORY_SEPARATOR . $safeGroup;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $hash . '.php';
    }

    /**
     * Remember pattern: tries to load cached value else executes producer and stores result.
     * @template T
     * @param string   $group   Logical group/folder
     * @param string   $key     Unique key inside group (will be hashed)
     * @param callable $producer fn():T  Callback executed on cache miss
     * @param string|null $dbPath Optional DB path (for invalidation); default current selected DB.
     * @param int $ttl Seconds to keep cache (default 24h)
     * @return mixed Cached value
     */
    public static function remember(string $group, string $key, callable $producer, ?string $dbPath = null, int $ttl = 86400): mixed
    {
        $dbPath = $dbPath ?? WikiDb::getSelectedPath();
        $cacheFile = self::path($dbPath, $group, $key);
        $dbMtime = @filemtime($dbPath) ?: 0;
        if (is_file($cacheFile)) {
            $age = time() - (int)@filemtime($cacheFile);
            if ($age <= $ttl) {
                // Invalidate if DB file changed after cache created
                if (@filemtime($cacheFile) >= $dbMtime) {
                    try {
                        /** @noinspection PhpIncludeInspection */
                        $value = include $cacheFile;
                        return $value;
                    } catch (\Throwable $e) {
                        // fall through to regenerate
                    }
                }
            }
        }
        // Miss -> generate
        $value = $producer();
        // Store as PHP returning file for fastest subsequent include
        $export = var_export($value, true);
        $php = "<?php\n// Auto-generated wiki data cache. TTL={$ttl}s Created=".date('c')."\nreturn " . $export . ";";
        @file_put_contents($cacheFile, $php, LOCK_EX);
        return $value;
    }

    /** Force delete cache group for current DB (e.g., after admin DB switch). */
    public static function flushGroup(string $group, ?string $dbPath = null): void
    {
        $dbPath = $dbPath ?? WikiDb::getSelectedPath();
        $dir = dirname(self::path($dbPath, $group, 'x'));
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') as $f) {
                @unlink($f);
            }
        }
    }
}
