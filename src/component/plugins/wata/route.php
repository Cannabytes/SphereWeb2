<?php

use wata\wata;

$routes = [
    [
        'method' => 'GET',
        'pattern' => '/admin/plugin/wata',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->admin();
        },
    ],
    [
        'method' => 'POST',
        'pattern' => '/admin/plugin/wata/save',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->saveSettings();
        },
    ],
    [
        'method' => 'GET',
        'pattern' => '/wata/payment',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->payment();
        },
    ],
    [
        'method' => 'GET',
        'pattern' => '/wata/payment/(\d+)',
        'file' => 'wata.php',
        'call' => function($count) {
            (new wata())->payment((int)$count);
        },
    ],
    [
        'method' => 'POST',
        'pattern' => '/wata/payment/create',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->createPayment();
        },
    ],
    [
        'method' => 'POST',
        'pattern' => '/wata/webhook',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->webhook();
        },
    ],
    [
        'method' => 'POST',
        'pattern' => '/plugin/wata/webhook',
        'file' => 'wata.php',
        'call' => function() {
            (new wata())->webhook();
        },
    ],
];