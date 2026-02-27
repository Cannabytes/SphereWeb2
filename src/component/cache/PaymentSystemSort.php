<?php

namespace Ofey\Logan22\component\cache;

use Ofey\Logan22\component\fileSys\fileSys;

/**
 * Manages the sort order of payment system plugins.
 * Sort order is persisted to uploads/cache/sort/paysystem.php
 * which returns an ordered array of PLUGIN_DIR_NAME strings.
 */
class PaymentSystemSort
{
    private static function filePath(): string
    {
        return fileSys::get_dir('uploads/cache/sort/paysystem.php');
    }

    /**
     * Read the saved sort order.
     * Returns an ordered array of PLUGIN_DIR_NAME strings, or [] if not set.
     */
    public static function read(): array
    {
        $file = self::filePath();
        if (!file_exists($file)) {
            return [];
        }
        try {
            $order = require $file;
            return is_array($order) ? array_values($order) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Save an ordered array of PLUGIN_DIR_NAME strings as a PHP file.
     */
    public static function save(array $order): bool
    {
        $file = self::filePath();
        $dir  = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $order = array_values(array_filter($order, 'is_string'));
        $phpCode = "<?php\n\nreturn " . var_export($order, true) . ";\n";

        return file_put_contents($file, $phpCode) !== false;
    }

    /**
     * Re-order a plugin list according to the saved order.
     * Plugins not found in the saved order are appended at the end
     * in their original sequence.
     */
    public static function applySortOrder(array $plugins): array
    {
        $order = self::read();
        if (empty($order)) {
            return $plugins;
        }

        $posMap    = array_flip($order);   // dirName => position
        $sorted    = [];
        $appended  = [];

        foreach ($plugins as $plugin) {
            $dirName = $plugin['PLUGIN_DIR_NAME'] ?? '';
            if (isset($posMap[$dirName])) {
                $sorted[$posMap[$dirName]] = $plugin;
            } else {
                $appended[] = $plugin;
            }
        }

        ksort($sorted);
        return array_merge(array_values($sorted), $appended);
    }
}
