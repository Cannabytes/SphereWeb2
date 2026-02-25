<?php

use paypal\paypal;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/paypal",
        "file" => "paypal.php",
        "call" => function() {
            (new paypal())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/paypal/save",
        "file" => "paypal.php",
        "call" => function() {
            (new paypal())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/paypal/payment",
        "file" => "paypal.php",
        "call" => function() {
            (new paypal())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/paypal/payment/(\d+)",
        "file" => "paypal.php",
        "call" => function($count) {
            (new paypal())->payment($count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/paypal/payment/create",
        "file" => "paypal.php",
        "call" => function() {
            (new paypal())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/paypal/webhook",
        "file" => "paypal.php",
        "call" => function() {
            (new paypal())->webhook();
        },
    ],
];
