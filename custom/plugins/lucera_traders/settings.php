<?php
return [
    "PLUGIN_HIDE" => false,
    "PLUGIN_ENABLE" => true,
    "PLUGIN_NAME" => "Lucera Traders",
    "PLUGIN_VERSION" => "1.0.0",
    "PLUGIN_AUTHOR" => "Logan22",
    "PLUGIN_GITHUB" => "",
    "PLUGIN_DESCRIPTION" => "Плагин показывает список торгашей и то что продают. Только для Люцеры.",
    "PLUGIN_ADMIN_PAGE" => "/admin/plugin/traders/lucera",
    "PLUGIN_ICON" => "bi bi-basket",
    "PLUGIN_LINK" => "/traders",
    "PLUGIN_COST" => 0,

    "INCLUDES" => [
        "PLACE_IN_SPACE_MAIN_1" => "lucera_traders/tpl/main.html",
    ],
];


