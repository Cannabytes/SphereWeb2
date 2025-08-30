<?php

use Ofey\Logan22\component\plugins\library;

$routes = [

    [
        "method" => "GET",
        "pattern" => "/library",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->show();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/weapons",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->weapons();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/weapons/data/(.*)",
        "file" => "library.php",
        "call" => function ($type) {
            (new library\library())->weaponsData($type);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/weapons/(.*)",
        "file" => "library.php",
        "call" => function ($type) {
            (new library\library())->weapons($type);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/armors",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->armors();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/armors/(.*)",
        "file" => "library.php",
        "call" => function ($bp) {
            (new library\library())->armors($bp);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/jewelry",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->jewelry();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/jewelry/(.*)",
        "file" => "library.php",
        "call" => function ($bp) {
            (new library\library())->jewelry($bp);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/items/recipes",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->recipes();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/items/etcitems",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->etcitems();
        },
    ],
    // Pretty URLs for etcitems: more specific (with page) FIRST to avoid greediness
    [
        "method" => "GET",
        "pattern" => "/library/items/etcitems/type/([^/]+)/page/([^/]+)",
        "file" => "library.php",
        "call" => function ($type, $page) {
            (new library\library())->etcitems($type, $page);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/items/etcitems/type/([^/]+)",
        "file" => "library.php",
        "call" => function ($type) {
            (new library\library())->etcitems($type, null);
        },
    ],
    // Page without type (defaults to 'other')
    [
        "method" => "GET",
        "pattern" => "/library/items/etcitems/page/([^/]+)",
        "file" => "library.php",
        "call" => function ($page) {
            (new library\library())->etcitems(null, $page);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/library/npcs",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->npcs();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/monsters",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->npcsMonsters();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/monsters/(.*)",
        "file" => "library.php",
        "call" => function ($range) {
            (new library\library())->npcsMonsters($range);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/raidboses",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->npcsRaidboses();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/other",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->npcsOther();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/type/(.*)",
        "file" => "library.php",
        "call" => function ($type) {
            (new library\library())->npcsType($type);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/data",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->npcsData();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/library/npcs/data/(.*)",
        "file" => "library.php",
        "call" => function ($filter) {
            (new library\library())->npcsData($filter);
        },
    ],

    // NPC detailed view by id
    [
        "method" => "GET",
        "pattern" => "/library/npc/id/(\d+)",
        "file" => "library.php",
        "call" => function ($id) {
            (new library\library())->npcView((int)$id);
        },
    ],

    // Armor sets page
    [
        "method" => "GET",
        "pattern" => "/library/category/armorsets",
        "file" => "library.php",
        "call" => function () {
            (new library\library())->armorsets();
        },
    ],

];
