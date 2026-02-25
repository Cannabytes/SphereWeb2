<?php

use severpay\severpay;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/severpay",
        "file" => "severpay.php",
        "call" => function() {
            (new severpay())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/severpay/save",
        "file" => "severpay.php",
        "call" => function() {
            (new severpay())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/severpay/payment",
        "file" => "severpay.php",
        "call" => function() {
            (new severpay())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/severpay/payment/(\d+)",
        "file" => "severpay.php",
        "call" => function($count) {
            (new severpay())->payment($count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/severpay/payment/create",
        "file" => "severpay.php",
        "call" => function() {
            (new severpay())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/severpay/webhook",
        "file" => "severpay.php",
        "call" => function() {
            (new severpay())->webhook();
        },
    ],
];
