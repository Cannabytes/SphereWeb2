<?php

use unitpay\unitpay;

$routes = [
    ["method" => "GET",  "pattern" => "/admin/plugin/unitpay",       "file" => "unitpay.php", "call" => function() { (new unitpay())->admin(); }],
    ["method" => "POST", "pattern" => "/admin/plugin/unitpay/save",  "file" => "unitpay.php", "call" => function() { (new unitpay())->saveSettings(); }],
    ["method" => "GET",  "pattern" => "/unitpay/payment",            "file" => "unitpay.php", "call" => function() { (new unitpay())->payment(); }],
    ["method" => "GET",  "pattern" => "/unitpay/payment/(\d+)",      "file" => "unitpay.php", "call" => function($count) { (new unitpay())->payment((int)$count); }],
    ["method" => "POST", "pattern" => "/unitpay/payment/create",     "file" => "unitpay.php", "call" => function() { (new unitpay())->createPayment(); }],
    ["method" => "GET",  "pattern" => "/unitpay/webhook",            "file" => "unitpay.php", "call" => function() { (new unitpay())->webhook(); }],
    ["method" => "POST", "pattern" => "/unitpay/webhook",            "file" => "unitpay.php", "call" => function() { (new unitpay())->webhook(); }],
];
