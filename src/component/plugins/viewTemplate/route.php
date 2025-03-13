<?php
$routes = [

    [
        "method" => "GET",
        "pattern" => "/admin/view/template/(.*)",
        "file" => "viewTemplate.php",
        "call" => function ($template) {
            (new \Ofey\Logan22\component\plugins\viewTemplate\viewTemplate())->show($template);
        }
    ],

];
