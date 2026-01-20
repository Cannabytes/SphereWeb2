<?php

namespace Ofey\Logan22\component\plugins\server_backup;

use Ofey\Logan22\model\db\sql;

class installer
{
    /**
     * Create necessary database tables
     */
    public static function createTables(): void
    {
        // No tables needed on PHP side - all data stored on Go API server
        // Go server will have its own tables
    }

    /**
     * Drop plugin tables on uninstall
     */
    public static function dropTables(): void
    {
        // No tables to drop on PHP side
    }

    /**
     * Check system requirements
     */
    public static function checkRequirements(): array
    {
        $errors = [];

        // Check if Go API server is available
        try {
            $response = \Ofey\Logan22\component\sphere\server::sendCustom("/api/backup/status", [
                'task_id' => 'test',
            ])->show()->getResponse();
            
            // If we got response (even error), API is available
        } catch (\Exception $e) {
            $errors[] = "Go API server is not available: " . $e->getMessage();
        }

        return $errors;
    }
}
