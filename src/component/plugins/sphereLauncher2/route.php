<?php

use Ofey\Logan22\component\plugins\sphereLauncher2;

$routes = [

    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->show();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/plugins/sphereLauncher2/config",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->config();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/compile",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->compilePage();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/compile",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->compile();
        },
    ],

    // НОВЫЙ МАРШРУТ ДЛЯ ПОЛУЧЕНИЯ СТАТУСА
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/sphereLauncher2/status/(.*)",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->status();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/plugin/sphereLauncher2/download",
        "file" => "sphereLauncher2.php",
        "call" => function () {
            (new sphereLauncher2\sphereLauncher2())->download();
        },
    ],


];