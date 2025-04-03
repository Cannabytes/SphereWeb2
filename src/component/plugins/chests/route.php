<?php
use Ofey\Logan22\component\plugins\chests;

// Текущие роуты
$routes = [
    // Публичные роуты
    [
        "method"  => "GET",
        "pattern" => "/chests",
        "file"    => "chests.php",
        "call"    => function() {
            (new chests\chests())->show();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/fun/chests/callback",
        "file"    => "chests.php",
        "call"    => function() {
            (new chests\chests())->callback();
        },
    ],

    // Административные роуты
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/chests",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->setting();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/chests/setting/save",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->save();
        },
    ],

    // API роуты для работы с кейсами в административной панели
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/chests/get/all",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->getAllCases();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/chests/get",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->getCase();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/chests/delete",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->deleteCase();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/chests/update/order",
        "file" => "chests.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\chests\chests())->updateCasesOrder();
        },
    ],


];