<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/aaiopay/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new aaiopay())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/transfer/aaiopay",
        "file"    => "pay.php",
        "call"    => function() {
            (new aaiopay())->transfer();
        },
    ],
];

