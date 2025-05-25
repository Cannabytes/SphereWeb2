<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/cardlink/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new cardlink())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/cardlink",
        "file"    => "pay.php",
        "call"    => function() {
            (new cardlink())->webhook();
        },
    ],

];

