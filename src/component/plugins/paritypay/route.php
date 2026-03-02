<?php

use paritypay\paritypay;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/paritypay",
        "file" => "paritypay.php",
        "call" => function() {
            (new paritypay())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/paritypay/save",
        "file" => "paritypay.php",
        "call" => function() {
            (new paritypay())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/paritypay/payment",
        "file" => "paritypay.php",
        "call" => function() {
            (new paritypay())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/paritypay/payment/(\d+)",
        "file" => "paritypay.php",
        "call" => function($count) {
            (new paritypay())->payment((int)$count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/paritypay/payment/create",
        "file" => "paritypay.php",
        "call" => function() {
            (new paritypay())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/paritypay/webhook",
        "file" => "paritypay.php",
        "call" => function() {
            (new paritypay())->webhook();
        },
    ],
];
