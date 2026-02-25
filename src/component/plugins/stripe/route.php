<?php

use stripe\stripe;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/stripe",
        "file" => "stripe.php",
        "call" => function() {
            (new stripe())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/stripe/save",
        "file" => "stripe.php",
        "call" => function() {
            (new stripe())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/stripe/payment",
        "file" => "stripe.php",
        "call" => function() {
            (new stripe())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/stripe/payment/(\d+)",
        "file" => "stripe.php",
        "call" => function($count) {
            (new stripe())->payment($count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/stripe/payment/create",
        "file" => "stripe.php",
        "call" => function() {
            (new stripe())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/stripe/webhook",
        "file" => "stripe.php",
        "call" => function() {
            (new stripe())->webhook();
        },
    ],
];
