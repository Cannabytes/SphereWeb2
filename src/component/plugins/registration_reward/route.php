<?php

use Ofey\Logan22\component\plugins\registration_reward;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\user\user;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/registration_reward",
        "file" => "registration_reward.php",
        "call" => function () {
            validation::user_protection("admin");
            (new registration_reward\registration_reward())->setting();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/registration_reward/setting/save",
        "file" => "registration_reward.php",
        "call" => function () {
            validation::user_protection("admin");
            (new registration_reward\registration_reward())->save();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/plugin/registration_reward/try-win",
        "file" => "registration_reward.php",
        "call" => function () {
            if (user::self()->isAuth()) {
                http_response_code(401);
                die(json_encode(['ok' => false, 'message' => 'authorized']));
            }
            (new registration_reward\registration_reward())->tryWin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/plugin/registration_reward/clear-winned",
        "file" => "registration_reward.php",
        "call" => function () {
            if (user::self()->isAuth()) {
                http_response_code(401);
                die(json_encode(['ok' => false, 'message' => 'authorized']));
            }
            (new registration_reward\registration_reward())->clearWinned();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/plugin/registration_reward/items",
        "file" => "registration_reward.php",
        "call" => function () {
            if (user::self()->isAuth()) {
                http_response_code(401);
                die(json_encode(['ok' => false, 'message' => 'authorized']));
            }
            (new registration_reward\registration_reward())->getItemsList();
        },
    ],
];
