<?php

use primepayments\primepayments;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/primepayments",
        "file" => "primepayments.php",
        "call" => function() {
            (new primepayments())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/primepayments/save",
        "file" => "primepayments.php",
        "call" => function() {
            (new primepayments())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/primepayments/payment",
        "file" => "primepayments.php",
        "call" => function() {
            (new primepayments())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/primepayments/payment/(\d+)",
        "file" => "primepayments.php",
        "call" => function($count) {
            (new primepayments())->payment($count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/primepayments/payment/create",
        "file" => "primepayments.php",
        "call" => function() {
            (new primepayments())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/primepayments/webhook",
        "file" => "primepayments.php",
        "call" => function() {
            (new primepayments())->webhook();
        },
    ],
];
