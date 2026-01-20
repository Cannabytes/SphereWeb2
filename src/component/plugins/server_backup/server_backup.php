<?php

namespace Ofey\Logan22\component\plugins\server_backup;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server as sphereServer;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class server_backup
{
    /**
     * Display admin panel
     */
    public function show(): void
    {
        validation::user_protection("admin");

        try {
            // Force load servers from database
            server::getServer();
            $userServers = server::getServerAll();
            
            if (!is_array($userServers)) {
                $userServers = [];
            }
        } catch (\Exception $e) {
            error_log("Server Backup Error loading servers: " . $e->getMessage());
            $userServers = [];
        }

        // Get current server time
        $currentTime = sphereServer::sendCustom("/api/current-time", [])->getResponse();
        $schedules = sphereServer::sendCustom("/api/backup/schedule/get")->getResponse();
        $logs = sphereServer::sendCustom("/api/backup/logs/get")->getResponse();

        tpl::addVar([
            'title' => 'server_backup_admin_title',
            'setting' => plugin::getSetting("server_backup"),
            'pluginName' => "server_backup",
            'userServers' => $userServers,
            'currentTime' => $currentTime['server_time'],
            'schedules' => $schedules['schedules'] ?? [],
            'logs' => $logs ?? [],
        ]);
        
        tpl::displayPlugin("/server_backup/tpl/admin/index.html");
    }

    /**
     * Save backup schedule and FTP configuration
     */
    public function saveSchedule(): void
    {
        validation::user_protection("admin");

        $serverId = $_POST['server_id'] ?? null;
        $backupTime = $_POST['backup_time'] ?? null;
        $ftpEnabled = isset($_POST['ftp_enabled']) && $_POST['ftp_enabled'] == '1';
        
        if (!$serverId || !$backupTime) {
            board::error( lang::get_phrase("server_backup_error_invalid_params"));
        }

        // Validate time format HH:MM
        if (!preg_match('/^\d{2}:\d{2}$/', $backupTime)) {
            board::error(lang::get_phrase("server_backup_error_invalid_time"));
        }

        // Prepare FTP config
        $ftpConfig = [
            'ftp_enabled' => $ftpEnabled,
            'ftp_host' => $_POST['ftp_host'] ?? '',
            'ftp_port' => (int)($_POST['ftp_port'] ?? 21),
            'ftp_user' => $_POST['ftp_user'] ?? '',
            'ftp_password' => $_POST['ftp_password'] ?? '',
            'ftp_path' => $_POST['ftp_path'] ?? '/',
            'max_backup_days' => (int)($_POST['max_backup_days'] ?? 30),
        ];

        $params = [
            'server_id' => (int)$serverId,
            'backup_time' => $backupTime,
        ];

        // Add FTP config if enabled
        if ($ftpEnabled) {
            foreach ($ftpConfig as $key => $val) {
                $params[$key] = $val;
            }
        } else {
            $params['ftp_enabled'] = false;
        }

        // Send request to Go API
        $response = sphereServer::sendCustom("/api/backup/schedule/save", $params)->getResponse();

        if (isset($response['error'])) {
            board::error($response['error']);
        }

        board::alert([
            'ok' => true,
            'message' => lang::get_phrase('server_backup_success_schedule_saved'),
            'schedule_id' => $response['schedule_id'] ?? null,
        ]);
    }
 

    /**
     * Test FTP connection
     */
    public function testFTP(): void
    {
        validation::user_protection("admin");

        // Get JSON data from request body
        $jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $ftpHost = $jsonData['ftp_host'] ?? $_POST['ftp_host'] ?? null;
        $ftpPort = (int)($jsonData['ftp_port'] ?? $_POST['ftp_port'] ?? 21);
        $ftpUser = $jsonData['ftp_user'] ?? $_POST['ftp_user'] ?? null;
        $ftpPassword = $jsonData['ftp_password'] ?? $_POST['ftp_password'] ?? null;

        if (!$ftpHost || !$ftpUser) {
            board::error(lang::get_phrase("server_backup_error_ftp_params"));
        }

        $params = [
            'ftp_host' => $ftpHost,
            'ftp_port' => $ftpPort,
            'ftp_user' => $ftpUser,
            'ftp_password' => $ftpPassword,
        ];

        // Send test request to Go API
        $response = sphereServer::sendCustom("/api/backup/schedule/test-ftp", $params)->getResponse();

        if (!$response['ok'] ?? false) {
            board::alert([
                'ok' => false,
                'error' => $response['error'],
            ]);
            return;
        }

        board::alert([
            'ok' => true,
            'message' => lang::get_phrase("server_backup_success_ftp_test"),
        ]);
    }

    /**
     * Create folder on FTP server
     */
    public function createFTPFolder(): void
    {
        validation::user_protection("admin");

        // Get JSON data from request body
        $jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $serverId = $jsonData['server_id'] ?? $_POST['server_id'] ?? null;
        $ftpHost = $jsonData['ftp_host'] ?? $_POST['ftp_host'] ?? null;
        $ftpPort = $jsonData['ftp_port'] ?? $_POST['ftp_port'] ?? 21;
        $ftpUser = $jsonData['ftp_user'] ?? $_POST['ftp_user'] ?? null;
        $ftpPassword = $jsonData['ftp_password'] ?? $_POST['ftp_password'] ?? null;
        $folderName = $jsonData['folder_name'] ?? $_POST['folder_name'] ?? null;
        $ftpPath = $jsonData['ftp_path'] ?? $_POST['ftp_path'] ?? '';

        if (!$serverId || !$folderName) {
            board::error(lang::get_phrase("server_backup_error_invalid_params"));
        }
        
        if (!$ftpHost || !$ftpUser || !$ftpPassword) {
            board::error(lang::get_phrase("server_backup_error_ftp_settings"));
        }

        $params = [
            'server_id' => (int)$serverId,
            'ftp_host' => $ftpHost,
            'ftp_port' => (int)$ftpPort,
            'ftp_user' => $ftpUser,
            'ftp_password' => $ftpPassword,
            'folder_name' => $folderName,
            'ftp_path' => $ftpPath,
        ];

        // Send request to Go API
        $response = sphereServer::sendCustom("/api/backup/schedule/ftp-mkdir", $params)->getResponse();

        if (!$response['ok'] ?? false) {
            board::alert([
                'ok' => false,
                'error' => $response['error'] ?? lang::get_phrase('server_backup_error_ftp_mkdir'),
            ]);
            return;
        }

        board::alert([
            'ok' => true,
            'message' => lang::get_phrase('server_backup_success_ftp_folder'),
        ]);
    }

    /**
     * List FTP folders
     */
    public function listFTPFolders(): void
    {
        validation::user_protection("admin");

        // Get JSON data from request body
        $jsonData = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $serverId = $jsonData['server_id'] ?? $_POST['server_id'] ?? null;
        $ftpHost = $jsonData['ftp_host'] ?? $_POST['ftp_host'] ?? null;
        $ftpPort = $jsonData['ftp_port'] ?? $_POST['ftp_port'] ?? 21;
        $ftpUser = $jsonData['ftp_user'] ?? $_POST['ftp_user'] ?? null;
        $ftpPassword = $jsonData['ftp_password'] ?? $_POST['ftp_password'] ?? null;
        $ftpPath = $jsonData['ftp_path'] ?? $_POST['ftp_path'] ?? '';
        
        if (!$serverId) {
            board::error(lang::get_phrase("server_backup_error_no_server"));
        }
        
        if (!$ftpHost || !$ftpUser || !$ftpPassword) {
            board::error(lang::get_phrase("server_backup_error_ftp_settings"));
        }

        $params = [
            'server_id' => (int)$serverId,
            'ftp_host' => $ftpHost,
            'ftp_port' => (int)$ftpPort,
            'ftp_user' => $ftpUser,
            'ftp_password' => $ftpPassword,
            'ftp_path' => $ftpPath,
        ];

        // Get folders from Go API (POST request)
        $response = sphereServer::sendCustom("/api/backup/schedule/ftp-list", $params)->getResponse();

        if (!$response['ok'] ?? false) {
            board::alert([
                'ok' => false,
                'error' => $response['error'] ?? lang::get_phrase('server_backup_error_ftp_list'),
            ]);
            return;
        }

        board::alert([
            'ok' => true,
            'folders' => $response['folders'] ?? [],
        ]);
    }

    /**
     * Get backup execution logs
     */
    public function getBackupLogs(): void
    {
        validation::user_protection("admin");

        $serverId = $_POST['server_id'] ?? null;
        $limit = (int)($_POST['limit'] ?? 50);
        $offset = (int)($_POST['offset'] ?? 0);
        
        if (!$serverId) {
            board::error("server_backup_error_no_server");
        }

        $params = [
            'server_id' => (int)$serverId,
            'limit' => $limit,
            'offset' => $offset,
        ];

        // Get logs from Go API (POST request)
        $response = sphereServer::sendCustom("/api/backup/logs/get", $params)->getResponse();

        if (isset($response['error'])) {
            board::error($response['error']);
        }

        board::alert([
            'ok' => true,
            'logs' => $response['logs'] ?? [],
            'count' => $response['count'] ?? 0,
        ]);
    }

    /**
     * Delete old backups immediately
     */
    public function deleteOldBackupsNow(): void
    {
        validation::user_protection("admin");

        $serverId = $_POST['server_id'] ?? null;
        
        if (!$serverId) {
            board::error("server_backup_error_no_server");
        }

        board::alert([
            'ok' => true,
            'message' => 'Manual old backup deletion is disabled',
            'local_deleted' => 0,
            'ftp_deleted' => 0,
        ]);
    }

    /**
     * Enable or disable backups for a specific server
     */
    public function enableBackup(): void
    {
        validation::user_protection("admin");

        $serverId = $_POST['server_id'] ?? null;
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] == 1;

        if (!$serverId) {
            board::error("server_backup_error_invalid_params");
        }

        // Validate that the server belongs to this user
        try {
            $server = server::getServer($serverId);
            if (!$server) {
                board::error("server_backup_error_invalid_server");
            }
        } catch (\Exception $e) {
            board::error("server_backup_error_invalid_server");
        }

        // Send request to Go API
        $params = [
            'server_id' => (int)$serverId,
            'enabled' => $enabled ? 1 : 0,
        ];

        $response = sphereServer::sendCustom("/api/backup/enable", $params)->getResponse();

        if (isset($response['error'])) {
            board::error($response['error']);
        }

        board::alert([
            'ok' => true,
            'message' => $enabled ? 'server_backup_enabled' : 'server_backup_disabled',
        ]);
    }
 
    /**
     * Get all backup information for a server (schedule, FTP config, and recent logs)
     * Called when plugin page loads to display all data at once
     */
    public function getServerBackupInfo(): void
    {
        validation::user_protection("admin");

        $serverId = $_POST['server_id'] ?? null;
        
        if (!$serverId) {
            board::error("server_backup_error_no_server");
        }

        $params = [
            'server_id' => (int)$serverId,
        ];

        // Get all info from Go API (schedule, FTP, and logs)
        $response = sphereServer::sendCustom("/api/backup/server-info", $params)->getResponse();

        if (isset($response['error'])) {
            board::error($response['error']);
        }

        board::alert([
            'ok' => true,
            'schedule' => $response['schedule'] ?? null,
            'ftp_info' => $response['ftp_info'] ?? null,
            'logs' => $response['logs'] ?? [],
        ]);
    }
}

