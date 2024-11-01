<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    //Роутер для создания ссылки перехода на сайт оплаты
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/paypal/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new paypal())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/transfer/paypal",
        "file"    => "pay.php",
        "call"    => function() {
            (new paypal())->webhook();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/paypal",
        "file"    => "pay.php",
        "call"    => function() {
            (new paypal())->webhook();
        },
    ],
];

