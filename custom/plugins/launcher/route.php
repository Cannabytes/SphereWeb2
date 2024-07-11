<?php

use Ofey\Logan22\component\plugins\launcher;

$routes = [
       [
            "method"  => "GET",
            "pattern" => "/launcher",
            "file"    => "launcher.php",
            "call"    => function() {
                (new \launcher\metamask())->show();
            },
       ],
    [
        "method" => "GET",
        "pattern" => "/launcher/{id}",
        "file" => "launcher.php",
        "call" => function ($id) {
            (new \launcher\metamask())->show($id);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/launcher/add",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->add();
        }
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/launcher/add",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->saveConfig();
        }
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/launcher",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->desc();
        }
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/launcher/edit/{id}",
        "file" => "launcher.php",
        "call" => function($id){
            (new \launcher\metamask())->edit($id);
        }
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/launcher/edit",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->editSave();
        }
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/launcher/set/server/default",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->setServerDefault();
        }
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/launcher/remove",
        "file" => "launcher.php",
        "call" => function(){
            (new \launcher\metamask())->removeLauncher();
        }
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/launcher/create/patch",
        "file" => "launcher.php",
        "call" => function () {
            (new \launcher\metamask())->admin();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/admin/launcher/update/patch",
        "file" => "launcher.php",
        "call" => function () {
            (new \launcher\metamask())->updatePatch();
        },
    ],

];
