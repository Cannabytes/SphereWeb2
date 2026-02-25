<?php

use pally\pally;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/pally",
        "file" => "pally.php",
        "call" => function() {
            (new pally())->admin();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/pally/save",
        "file" => "pally.php",
        "call" => function() {
            (new pally())->saveSettings();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/pally/payment",
        "file" => "pally.php",
        "call" => function() {
            (new pally())->payment();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/pally/payment/(\d+)",
        "file" => "pally.php",
        "call" => function($count) {
            (new pally())->payment((int)$count);
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/pally/payment/create",
        "file" => "pally.php",
        "call" => function() {
            (new pally())->createPayment();
        },
    ],
    [
        "method" => "POST",
        "pattern" => "/pally/webhook",
        "file" => "pally.php",
        "call" => function() {
            (new pally())->webhook();
        },
    ],
];
