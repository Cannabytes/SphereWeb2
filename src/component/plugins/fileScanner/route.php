<?php

use Ofey\Logan22\component\plugins\fileScanner\instance as fileScannerAlias;
use Ofey\Logan22\model\admin\validation;

$routes = [
    [
        "method"  => "GET",
        "pattern" => "/admin/filescanner",
        "file"    => "filescanner.php",
        "call"    => function() {
            validation::user_protection("admin");
            (new fileScannerAlias())->index();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/filescanner/scan",
        "file"    => "filescanner.php",
        "call"    => function() {
            validation::user_protection("admin");
            (new fileScannerAlias())->scan();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/admin/filescanner/progress",
        "file"    => "filescanner.php",
        "call"    => function() {
            validation::user_protection("admin");
            (new fileScannerAlias())->getProgressStatus();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/admin/filescanner/update",
        "file"    => "filescanner.php",
        "call"    => function() {
            validation::user_protection("admin");
            (new fileScannerAlias())->updateFiles();
        },
    ],

];