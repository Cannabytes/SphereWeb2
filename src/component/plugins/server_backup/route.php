<?php

use Ofey\Logan22\component\plugins\server_backup\server_backup;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/server_backup",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->show();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/schedule/save",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->saveSchedule();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/schedule/test-ftp",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->testFTP();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/schedule/ftp-mkdir",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->createFTPFolder();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/schedule/ftp-list",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->listFTPFolders();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/logs/get",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->getBackupLogs();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/server-backup-info",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->getServerBackupInfo();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/delete-old",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->deleteOldBackupsNow();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/enable-backup",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->enableBackup();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/server_backup/api/info",
        "file" => "server_backup.php",
        "call" => function () {
            (new server_backup())->getServerBackupInfo();
        },
    ],
];
