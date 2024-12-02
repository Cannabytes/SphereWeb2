<?php

use Ofey\Logan22\component\plugins\items_increase;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/statistic/item/increase",
        "file" => "items_increase.php",
        "call" => function () {
            (new items_increase\items_increase())->show();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/statistic/item/increase/(\d+)",
        "file" => "items_increase.php",
        "call" => function ($id) {
            (new items_increase\items_increase())->show($id);
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/statistic/item/increase/add",
        "file" => "items_increase.php",
        "call" => function () {
            (new items_increase\items_increase())->addItem();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/statistic/item/increase/delete",
        "file" => "items_increase.php",
        "call" => function () {
            (new items_increase\items_increase())->DeleteItem();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/balance/pay/item/increase",
        "file" => "items_increase.php",
        "call" => function () {
            (new items_increase\items_increase())->pay();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/statistic/item/increase/save",
        "file" => "items_increase.php",
        "call" => function () {
            (new items_increase\items_increase())->save();
        },
    ],


];
