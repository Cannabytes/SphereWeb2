<?php

use freekassa\freekassa;

/**
 * Маршруты плагина FreeKassa
 */

$routes = [
    // Административная панель
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/freekassa",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->admin();
        },
    ],

    // Создание магазина (instance)
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/instance/create",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->createInstance();
        },
    ],

    // Обновление магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/instance/update",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->updateInstance();
        },
    ],

    // Удаление магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/instance/delete",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->deleteInstance();
        },
    ],

    // Сохранение глобальных настроек
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/settings/save",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->saveGlobalSettings();
        },
    ],

    // Получение данных магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/instance/get",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->getInstanceData();
        },
    ],

    // Обновить валюты (методы оплаты) магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/freekassa/instance/refresh_currencies",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->ajaxRefreshCurrencies();
        },
    ],
 
    // Страница оплаты для пользователей
    [
        "method" => "GET",
        "pattern" => "/freekassa/payment",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->payment();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/freekassa/payment/(\d+)",
        "file" => "freekassa.php",
        "call" => function($count) {
            (new freekassa())->payment($count);
        },
    ],

    // Создание платежа
    [
        "method" => "POST",
        "pattern" => "/freekassa/payment/create",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->createPayment();
        },
    ],

    // Webhook для уведомлений от FreeKassa
    [
        "method" => "POST",
        "pattern" => "/freekassa/webhook",
        "file" => "freekassa.php",
        "call" => function() {
            (new freekassa())->webhook();
        },
    ],

];
