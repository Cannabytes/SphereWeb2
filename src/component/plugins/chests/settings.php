<?php

return [
    "PLUGIN_HIDE" => false,
    "PLUGIN_ENABLE" => true,
    "PLUGIN_NAME" => "chests",
    "PLUGIN_PHRASE_ID" => "chests_name",
    "PLUGIN_VERSION" => "1.0.0",
    "PLUGIN_AUTHOR" => "Logan22",
    "PLUGIN_DESCRIPTION" => "chests_description_plugin",
    "PLUGIN_ADMIN_PAGE" => "/admin/plugin/chests",
    "PLUGIN_ICON" => "bi bi-box2-heart-fill",
    "PLUGIN_LINK" => "/chests",
    "PLUGIN_COST" => -1,

    "SORT" => 1,
    "INCLUDES" => [
        "PLACE_IN_SPACE_MAIN_1" => "chests/tpl/html_chests.html",
    ],
];

