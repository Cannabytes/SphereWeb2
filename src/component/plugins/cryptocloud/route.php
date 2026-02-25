<?php

use cryptocloud\cryptocloud;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/cryptocloud",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/cryptocloud/save",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/cryptocloud/payment",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/cryptocloud/payment/(\d+)",
        "file" => "cryptocloud.php",
        "call" => function($count) {
            (new cryptocloud())->payment($count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/cryptocloud/payment/create",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->createPayment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/cryptocloud/payment/return",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->paymentReturn();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/cryptocloud/payment/success",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->paymentSuccess();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/cryptocloud/payment/fail",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->paymentFail();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/cryptocloud/webhook",
        "file" => "cryptocloud.php",
        "call" => function() {
            (new cryptocloud())->webhook();
        },
    ],
];
