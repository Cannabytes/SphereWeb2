<?php

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/server_description",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->setting();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/server_description/add",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->addSectionPage();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/save",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->save();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/add",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->addSection();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/delete",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->deleteSection();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/server_description/edit/(\\d+)",
        "file"    => "server_description.php",
        "call"    => function ($id) {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->edit($id);
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/move",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->move();
        },
    ],
    // Маршруты для управления категориями
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/category/add",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->addCategory();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/category/edit",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->editCategory();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/category/delete",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->deleteCategory();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/category/move",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->moveCategory();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/server_description/upload/image",
        "file"    => "server_description.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->uploadImage();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/wiki/([^/]+)",
        "file"    => "server_description.php",
        "call"    => function ($serverName) {
            (new \Ofey\Logan22\component\plugins\server_description\server_description())->view($serverName);
        },
    ],
];
