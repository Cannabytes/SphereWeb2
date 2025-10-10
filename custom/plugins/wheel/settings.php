<?php
return [
"PLUGIN_HIDE" => false,
"PLUGIN_ENABLE" => true,
"PLUGIN_NAME" => "wheel",
"PLUGIN_PHRASE_ID" => "wheel",
"PLUGIN_VERSION" => "1.0.0",
"PLUGIN_AUTHOR" => "Logan22",
"PLUGIN_GITHUB" => "",
"PLUGIN_DESCRIPTION" => "wheel_description",
"PLUGIN_ADMIN_PAGE" => "/fun/wheel/admin",
"PLUGIN_ADMIN_PAGE_NAME" => "Розыгрыш",
"PLUGIN_ADMIN_PAGE_ICON" => "fa fa-users",
"PLUGIN_COST" => 8,

"PLUGIN_USER_PAGE" => "/fun/wheel",
"PLUGIN_USER_PAGE_NAME" => "Колесо Удачи",
"PLUGIN_USER_PAGE_ICON" => "fa fa-microchip",
"PLUGIN_USER_PAGE_ACCESS" => ["user", "admin"],
"PLUGIN_USER_PANEL_SHOW" => ["MAIN_MENU"],

"INCLUDES" => [
  "PLACE_IN_SPACE_HEADER_1" => "wheel/tpl/add_sidebar.html",
],

];


