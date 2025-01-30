<?php

use Ofey\Logan22\component\plugins\fileScanner\instance as fileScannerAlias;

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/admin/filescanner",
        "file"    => "filescanner.php",
        "call"    => function() {
            (new fileScannerAlias())->index();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/filescanner/scan",
        "file"    => "filescanner.php",
        "call"    => function() {
            (new fileScannerAlias())->scan();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/admin/filescanner/update",
        "file"    => "filescanner.php",
        "call"    => function() {
            (new fileScannerAlias())->updateFiles();
        },
    ],

];