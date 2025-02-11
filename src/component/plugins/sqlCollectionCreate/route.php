<?php

use Ofey\Logan22\component\plugins\sqlCollectionCreater\libs\collection;

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/admin/collection",
        "file"    => "libs/collection.php",
        "call"    => function() {
           (new collection)->show();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/admin/collection/editor/{name}",
        "file"    => "libs/collection.php",
        "call"    => function($name) {
           (new collection)->edit($name);
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/collection/check",
        "file"    => "libs/collection.php",
        "call"    => function() {
            (new collection)->checkQuery();
        }
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/collection/save",
        "file"    => "libs/collection.php",
        "call"    => function() {
            (new collection)->save();
        }
    ]
];