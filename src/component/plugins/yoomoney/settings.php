<?php
/**
 * Настройки плагина YooMoney
 * Система приема платежей через YooMoney (ЮMoney)
 */

return [
    // Основная информация о плагине
    "PLUGIN_NAME" => "YooMoney",
    "PLUGIN_VERSION" => "1.0.0",
    "PLUGIN_AUTHOR" => "Logan22",
    "PLUGIN_DESCRIPTION" => "yoomoney_desc",
    "PLUGIN_PHRASE_ID" => "yoomoney",
    
    // Управление плагином
    "PLUGIN_HIDE" => false,
    "PLUGIN_ENABLE" => true,
    
    // Иконка плагина (Bootstrap Icons)
    "PLUGIN_ICON" => "bi bi-wallet2",
    
    // Категория плагина
    "PLUGIN_CATEGORY" => "paysystem",
    
    // Административная страница плагина
    "PLUGIN_ADMIN_PAGE" => "/admin/plugin/yoomoney",
    
    // Стоимость плагина (-1 = бесплатно)
    "PLUGIN_COST" => -1,

    // Меню плагина для пользователей
    "PLUGIN_USER_PANEL_SHOW" => ["DONATE_MENU"],

    "PLUGIN_LINK" => "/yoomoney/payment",
];
