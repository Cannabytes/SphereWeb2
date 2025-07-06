<?php

use Ofey\Logan22\component\plugins\pts_character_services;
use Ofey\Logan22\model\admin\validation;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/character/services",
        "file" => "services.php",
        "call" => function () {
            (new pts_character_services\pts_character_services())->show();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/pts_character_services",
        "file" => "pts_character_services.php",
        "call" => function () {
            validation::user_protection("admin");
            (new pts_character_services\pts_character_services())->setting();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/pts_character_services/setting/save",
        "file" => "pts_character_services.php",
        "call" => function () {
            validation::user_protection("admin");
            (new pts_character_services\pts_character_services())->save();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/character/services/change/name",
        "file" => "services.php",
        "call" => function () {
            validation::user_protection();
            (new pts_character_services\pts_character_services())->changeName();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/character/services/move",
        "file" => "services.php",
        "call" => function () {
            validation::user_protection();
            (new pts_character_services\pts_character_services())->moveCharacter();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/character/services/delete",
        "file" => "services.php",
        "call" => function () {
            validation::user_protection();
            (new pts_character_services\pts_character_services())->deleteCharacter();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/character/services/change/color",
        "file" => "services.php",
        "call" => function () {
            validation::user_protection();
            (new pts_character_services\pts_character_services())->changeNameColor();
        },
    ],
];