<?php

use Ofey\Logan22\component\plugins\anons_l2wow_com;
use Ofey\Logan22\model\admin\validation;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/anons_l2wow_com",
        "file" => "anons_l2wow_com.php",
        "call" => function () {
            validation::user_protection("admin");
            (new anons_l2wow_com\anons_l2wow_com())->setting();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/anons_l2wow_com/setting/save",
        "file" => "anons_l2wow_com.php",
        "call" => function () {
            validation::user_protection("admin");
            (new anons_l2wow_com\anons_l2wow_com())->save();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/anons_l2wow_com/{serverId}",
        "file" => "anons_l2wow_com.php",
        "call" => function (...$params) {
            $serverId = $params[0] ?? 0;
            (new anons_l2wow_com\anons_l2wow_com())->receiveVote($serverId);
        },
    ],
];

