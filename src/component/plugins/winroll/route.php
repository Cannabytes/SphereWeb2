<?php
$routes = [

    [
        "method" => "GET",
        "pattern" => "/win/roll",
        "file" => "winroll.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\winRoll\winroll())->show();
        }
    ],

    [
        "method" => "GET",
        "pattern" => "/admin/plugin/winroll",
        "file" => "winroll.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\winRoll\winroll())->setting();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/plugin/winroll/setting/save",
        "file" => "winroll.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\winRoll\winroll())->save();
        },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/plugin/winroll/spin",
        "file" => "winroll.php",
        "call" => function () {
            (new \Ofey\Logan22\component\plugins\winRoll\winroll())->spin();
        },
    ]
];
