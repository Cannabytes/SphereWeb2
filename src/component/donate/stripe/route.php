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
        "pattern" => "/donate/transfer/stripe/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new stripe())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/stripe",
        "file"    => "pay.php",
        "call"    => function() {
            (new stripe())->webhook();
        },
    ],
];

