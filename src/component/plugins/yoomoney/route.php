<?php

use yoomoney\yoomoney;

/**
 * Маршруты плагина YooMoney
 */

$routes = [
    // Административная панель
    [
        "method" => "GET",
        "pattern" => "/admin/plugin/yoomoney",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->admin();
        },
    ],

    // Создание магазина (instance)
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/yoomoney/instance/create",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->createInstance();
        },
    ],

    // Обновление магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/yoomoney/instance/update",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->updateInstance();
        },
    ],

    // Удаление магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/yoomoney/instance/delete",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->deleteInstance();
        },
    ],

    // Сохранение глобальных настроек
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/yoomoney/settings/save",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->saveGlobalSettings();
        },
    ],

    // Получение данных магазина
    [
        "method" => "POST",
        "pattern" => "/admin/plugin/yoomoney/instance/get",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->getInstanceData();
        },
    ],

    // Страница оплаты для пользователей
    [
        "method" => "GET",
        "pattern" => "/yoomoney/payment",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->payment();
        },
    ],

    [
        "method" => "GET",
        "pattern" => "/yoomoney/payment/(\d+)",
        "file" => "yoomoney.php",
        "call" => function($count) {
            (new yoomoney())->payment($count);
        },
    ],

    // Создание платежа
    [
        "method" => "POST",
        "pattern" => "/yoomoney/payment/create",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->createPayment();
        },
    ],

    // Webhook для получения уведомлений от YooMoney
    [
        "method" => "POST",
        "pattern" => "/plugin/yoomoney/webhook",
        "file" => "yoomoney.php",
        "call" => function() {
            (new yoomoney())->webhook();
        },
    ],
];
