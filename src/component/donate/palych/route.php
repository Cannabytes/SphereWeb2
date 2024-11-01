<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/palych/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new palych())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/palych",
        "file"    => "pay.php",
        "call"    => function() {
            (new palych())->webhook();
        },
    ],

];

