<?php

$routes = [
    // Администраторская панель
    [
        "method"  => "GET",
        "pattern" => "/admin/plugin/referral_links",
        "file"    => "ReferralLinks.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\referral_links\ReferralLinks())->show();
        },
    ],

    // Добавление кастомной ссылки
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/referral_links/add",
        "file"    => "ReferralLinks.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\referral_links\ReferralLinks())->addLink();
        },
    ],

    // Обновление кастомной ссылки
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/referral_links/update",
        "file"    => "ReferralLinks.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\referral_links\ReferralLinks())->updateLink();
        },
    ],

    // Удаление кастомной ссылки
    [
        "method"  => "POST",
        "pattern" => "/admin/plugin/referral_links/delete",
        "file"    => "ReferralLinks.php",
        "call"    => function () {
            (new \Ofey\Logan22\component\plugins\referral_links\ReferralLinks())->deleteLink();
        },
    ],
];
