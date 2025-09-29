<?php

use Ofey\Logan22\component\plugins\wiki;

$routes = [
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/wiki",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->setting();
        },
    ],

    // Drop and Spoil lists
    [
        "method" => "GET",
        "pattern" => "/wiki/items/drop",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->itemsDrop(null); },
    ],
    // Pretty search URLs for drop
    [
        "method" => "GET",
        "pattern" => "/wiki/items/drop/search/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($q) { (new wiki\wiki())->itemsDrop(null, $q); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/drop/search/([^/]+)/page/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($q, $page) { (new wiki\wiki())->itemsDrop((int)$page, $q); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/drop/page/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($page) { (new wiki\wiki())->itemsDrop((int)$page); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/spoil",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->itemsSpoil(null); },
    ],
    // Pretty search URLs for spoil
    [
        "method" => "GET",
        "pattern" => "/wiki/items/spoil/search/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($q) { (new wiki\wiki())->itemsSpoil(null, $q); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/spoil/search/([^/]+)/page/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($q, $page) { (new wiki\wiki())->itemsSpoil((int)$page, $q); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/spoil/page/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($page) { (new wiki\wiki())->itemsSpoil((int)$page); },
    ],

    // Admin: clear cache
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/clear-cache",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminClearCache(); },
    ],

    // Admin: NPC image moderation endpoints (restore moderation handlers)
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/npc/images/approve",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminApproveNpcImage(); },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/npc/images/reject",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminRejectNpcImage(); },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/npc/images/approve-all",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminApproveAllNpcImages(); },
    ],
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/npc/images/reject-all",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminRejectAllNpcImages(); },
    ],

    // Admin: moderation UI (GET)
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/wiki/npc/images/moderation",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->adminNpcImagesModeration(); },
    ],

    // NOTE: The admin CRUD routes for direct SQLite management (items, weapons, armors,
    // sets, NPC CRUD, recipes and image moderation) were removed intentionally.
    // Those features have been deprecated and related handlers/templates were deleted.

    [
        "method" => "GET",
        "pattern" => "/wiki",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->show();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/weapons",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->weapons();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/weapons/data/(.*)",
        "file" => "wiki.php",
        "call" => function ($type) {
            (new wiki\wiki())->weaponsData($type);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/weapons/(.*)",
        "file" => "wiki.php",
        "call" => function ($type) {
            (new wiki\wiki())->weapons($type);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/armors",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->armors();
        },
    ],

    // Other armor (non-core, non-jewelry) — place BEFORE catch-all
    [
        "method" => "GET",
        "pattern" => "/wiki/items/armors/other",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->armorsOther();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/armors/other/(.*)",
        "file" => "wiki.php",
        "call" => function ($bp) {
            (new wiki\wiki())->armorsOther($bp);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/armors/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($bp) {
            (new wiki\wiki())->armors($bp);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/jewelry",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->jewelry();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/jewelry/(.*)",
        "file" => "wiki.php",
        "call" => function ($bp) {
            (new wiki\wiki())->jewelry($bp);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/items/recipes",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->recipes();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/recipes/production/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($id) {
            (new wiki\wiki())->recipeByProduction((int)$id);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/etcitems",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->etcitems();
        },
    ],
    // Pretty URLs for etcitems: more specific (with page) FIRST to avoid greediness
    [
        "method" => "GET",
        "pattern" => "/wiki/items/etcitems/type/([^/]+)/page/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($type, $page) {
            (new wiki\wiki())->etcitems($type, $page);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/etcitems/type/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($type) {
            (new wiki\wiki())->etcitems($type, null);
        },
    ],
    // Page without type (defaults to 'other')
    [
        "method" => "GET",
        "pattern" => "/wiki/items/etcitems/page/([^/]+)",
        "file" => "wiki.php",
        "call" => function ($page) {
            (new wiki\wiki())->etcitems(null, $page);
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/wiki/npcs",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcs();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/monsters",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcsMonsters();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/monsters/(.*)",
        "file" => "wiki.php",
        "call" => function ($range) {
            (new wiki\wiki())->npcsMonsters($range);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/raidboses",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcsRaidboses();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/raidboses/(.*)",
        "file" => "wiki.php",
        "call" => function ($range) {
            (new wiki\wiki())->npcsRaidboses($range);
        },
    ],
    // Epic raid bosses (GrandBoss/boss), ignore is_spawn
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/epicbosses",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcsEpicbosses();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/epicbosses/(.*)",
        "file" => "wiki.php",
        "call" => function ($range) {
            (new wiki\wiki())->npcsEpicbosses($range);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/other",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcsOther();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/type/(.*)",
        "file" => "wiki.php",
        "call" => function ($type) {
            (new wiki\wiki())->npcsType($type);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/data",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->npcsData();
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/search",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->npcsSearch(); },
    ],
    // Simple items live search (JSON)
    [
        "method" => "GET",
        "pattern" => "/wiki/items/search",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->itemsSearch(); },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/npcs/data/(.*)",
        "file" => "wiki.php",
        "call" => function ($filter) {
            (new wiki\wiki())->npcsData($filter);
        },
    ],

    // NPC detailed view by id
    [
        "method" => "GET",
        "pattern" => "/wiki/npc/id/(\d+)",
        "file" => "wiki.php",
        "call" => function ($id) {
            (new wiki\wiki())->npcView((int)$id);
        },
    ],

    // AJAX: return spawn points for NPC (JSON)
    [
        "method" => "GET",
        "pattern" => "/wiki/npc/spawns/(\d+)",
        "file" => "wiki.php",
        "call" => function ($id) {
            (new wiki\wiki())->npcSpawnsAjax((int)$id);
        },
    ],

    // Quest detailed view by id
    [
        "method" => "GET",
        "pattern" => "/wiki/quest/(\d+)",
        "file" => "quest.php",
        "call" => function ($id) {
            (new wiki\quest())->show((int)$id);
        },
    ],

    // Armor sets page
    [
        "method" => "GET",
        "pattern" => "/wiki/category/armorsets",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->armorsets();
        },
    ],

    // Item sources (NPCs that drop/spoil a given item)
    [
        "method" => "GET",
        "pattern" => "/wiki/items/sources/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($itemId) {
            (new wiki\wiki())->itemSources((int)$itemId, null);
        },
    ],
    [
        "method" => "GET",
        "pattern" => "/wiki/items/sources/(\\d+)/(drop|spoil|all)",
        "file" => "wiki.php",
        "call" => function ($itemId, $type) {
            (new wiki\wiki())->itemSources((int)$itemId, $type);
        },
    ],

    // Lightweight item tooltip JSON (GET by path id)
    [
        "method" => "GET",
        "pattern" => "/wiki/item/(\\d+)",
        "file" => "wiki.php",
        "call" => function ($id) { (new wiki\wiki())->itemInfo((int)$id); },
    ],
    // Same via POST body id=... (preferred for hover to avoid caching issues)
    [
        "method" => "POST",
        "pattern" => "/wiki/item",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->itemInfo(0); },
    ],

    // NPC image upload
    [
        "method" => "POST",
        "pattern" => "/wiki/npc/upload-image",
        "file" => "wiki.php",
        "call" => function () { (new wiki\wiki())->uploadNpcImage(); },
    ],

    [
        "method" => "POST",
        "pattern" => "/admin/plugin/wiki/download-db",
        "file" => "wiki.php",
        "call" => function () {
            (new wiki\wiki())->adminDownloadDb();
        },
    ],

];
