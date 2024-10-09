<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/morune/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new morune())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/morune",
        "file"    => "pay.php",
        "call"    => function() {
            (new morune())->transfer();
        },
    ],

];

