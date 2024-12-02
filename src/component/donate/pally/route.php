<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/pally/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new pally())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/pally",
        "file"    => "pay.php",
        "call"    => function() {
            (new pally())->webhook();
        },
    ],

];

