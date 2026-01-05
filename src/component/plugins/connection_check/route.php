<?php
use Ofey\Logan22\component\plugins\connection_check;

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/connection/check",
        "file"    => "connection_check.php",
        "call"    => function() {
            (new connection_check\connection_check())->show();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/connection/check/process",
        "file"    => "connection_check.php",
        "call"    => function() {
            (new connection_check\connection_check())->process();
        },
    ],
];
