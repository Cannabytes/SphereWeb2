<?php

use lucera_traders\lucera_traders;

$routes = [
       [
            "method"  => "GET",
            "pattern" => "/traders",
            "file"    => "lucera_traders.php",
            "call"    => function() {
                (new lucera_traders())->show();
            },
       ],
       [
            "method"  => "GET",
            "pattern" => "/admin/plugin/traders/lucera",
            "file"    => "lucera_traders.php",
            "call"    => function() {
                (new lucera_traders())->setting();
            },
       ],

];
