<?php

use Ofey\Logan22\component\plugins\sphereLauncher2;

$sl2 = fn() => new sphereLauncher2\sphereLauncher2();

$routes = [

    // ---------- Страницы ----------
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->show();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/compile",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->compilePage();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/archives",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->archivesPage();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugins/sphereLauncher2/config",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->config();
        },
    ],

    // ---------- Настройки сборки ----------
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/settings",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->getSettings();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/settings",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->saveSettings();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/test",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->test();
        },
    ],

    // ---------- Сборки ----------
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/builds",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->listBuilds();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/builds",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->startBuild();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/builds/([a-fA-F0-9]{32})",
        "file" => "sphereLauncher2.php",
        "call" => function ($taskId = '') use ($sl2) {
            $sl2()->buildStatus($taskId);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/builds/([a-fA-F0-9]{32})/download",
        "file" => "sphereLauncher2.php",
        "call" => function ($taskId = '') use ($sl2) {
            $sl2()->downloadBuild($taskId);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/builds/([a-fA-F0-9]{32})/delete",
        "file" => "sphereLauncher2.php",
        "call" => function ($taskId = '') use ($sl2) {
            $sl2()->deleteBuild($taskId);
        },
    ],

    // ---------- Библиотека иконок ----------
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/icons",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->listIcons();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/icons",
        "file" => "sphereLauncher2.php",
        "call" => function () use ($sl2) {
            $sl2()->saveIcon();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/icons/([a-f0-9]{64})/delete",
        "file" => "sphereLauncher2.php",
        "call" => function ($id = '') use ($sl2) {
            $sl2()->deleteIcon($id);
        },
    ],

];
