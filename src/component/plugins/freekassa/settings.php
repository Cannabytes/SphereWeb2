<?php
/**
 * Настройки плагина FreeKassa
 * Система приема платежей через FreeKassa API
 */

return [
    // Основная информация о плагине
    "PLUGIN_NAME" => "FreeKassa",
    "PLUGIN_VERSION" => "1.0.0",
    "PLUGIN_AUTHOR" => "Logan22",
    "PLUGIN_DESCRIPTION" => "freekassa_desc",
    "PLUGIN_PHRASE_ID" => "freekassa",
    
    // Управление плагином
    "PLUGIN_HIDE" => false,
    "PLUGIN_ENABLE" => true,
    
    // Иконка плагина (Bootstrap Icons)
    "PLUGIN_ICON" => "bi bi-wallet2",
    
    // Категория плагина
    "PLUGIN_CATEGORY" => "paysystem",
    
    // Административная страница плагина
    "PLUGIN_ADMIN_PAGE" => "/admin/plugin/freekassa",
    
    // Стоимость плагина (-1 = бесплатно)
    "PLUGIN_COST" => -1,

    // Меню плагина для пользователей
    "PLUGIN_USER_PANEL_SHOW" => ["DONATE_MENU"],

    "PLUGIN_LINK" => "/freekassa/payment",

];
