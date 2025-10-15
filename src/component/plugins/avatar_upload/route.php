<?php
use Ofey\Logan22\component\plugins\avatar_upload;

$routes = [
    // Публичные роуты
    [
        "method"  => "GET",
        "pattern" => "/avatar/upload",
        "file"    => "avatar_upload.php",
        "call"    => function() {
            (new avatar_upload\avatar_upload())->show();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/avatar/upload/process",
        "file"    => "avatar_upload.php",
        "call"    => function() {
            (new avatar_upload\avatar_upload())->upload();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/avatar/upload/video",
        "file"    => "avatar_upload.php",
        "call"    => function() {
            (new avatar_upload\avatar_upload())->uploadVideo();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/avatar/get/current",
        "file"    => "avatar_upload.php",
        "call"    => function() {
            (new avatar_upload\avatar_upload())->getCurrentAvatar();
        },
    ],

    // Административные роуты
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/avatar_upload",
        "file" => "avatar_upload.php",
        "call" => function () {
            (new avatar_upload\avatar_upload())->setting();
        },
    ],
];
