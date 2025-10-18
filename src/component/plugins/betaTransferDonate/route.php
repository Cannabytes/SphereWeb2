<?php

use betaTransferDonate\betaTransferDonate;

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/donate/betatransfer",
        "file"    => "betaTransferDonate.php",
        "call"    => function() {
            (new betaTransferDonate())->show();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/betatransfer/donate",
        "file"    => "betaTransferDonate.php",
        "call"    => function() {
            (new betaTransferDonate())->adminSettings();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/donate/betatransfer/create",
        "file"    => "betaTransferDonate.php",
        "call"    => function() {
            (new betaTransferDonate())->createPayment();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/betatransfer",
        "file"    => "betaTransferDonate.php",
        "call"    => function() {
            (new betaTransferDonate())->webhook();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/betatransfer/save",
        "file"    => "betaTransferDonate.php",
        "call"    => function() {
            (new betaTransferDonate())->saveSettings();
        },
    ],
];
