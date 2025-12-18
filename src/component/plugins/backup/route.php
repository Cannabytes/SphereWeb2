<?php

use backup\backup;
use backup\BackupManager;

$routes = [
    // Admin panel - главная страница
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/backup",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->adminPanel();
        },
    ],

    // API: инициировать новый бекап
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/backup/api/init",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->apiInitBackup();
        },
    ],

    // API: запустить процесс бекапа
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/backup/api/start",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->apiStartBackup();
        },
    ],

    // API: получить статус задачи
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/backup/api/status/(\d+)",
        "file" => "backup.php",
        "call" => function($taskId) {
            (new backup())->apiGetStatus($taskId);
        },
    ],

    // API: получить список бекапов
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/backup/api/list",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->apiListBackups();
        },
    ],

    // API: удалить бекап
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/backup/api/delete",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->apiDeleteBackup();
        },
    ],

    // API: скачать бекап
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/backup/download/(\d+)",
        "file" => "backup.php",
        "call" => function($taskId) {
            (new backup())->apiDownloadBackup($taskId);
        },
    ],

    // API: получить диагностику
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/backup/api/diagnostics",
        "file" => "backup.php",
        "call" => function() {
            (new backup())->apiGetDiagnostics();
        },
    ],
];
