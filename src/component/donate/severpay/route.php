<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/severpay/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new severpay())->create_link();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/severpay",
        "file"    => "pay.php",
        "call"    => function() {
            (new severpay())->webhook();
        },
    ],
];
