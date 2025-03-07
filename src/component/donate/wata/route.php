<?php
/**
 * route.php это обязательный файл
 */

$routes = [
    [
        //Метод POST/GET
        "method"  => "POST",
        //Адрес
        "pattern" => "/donate/transfer/wata/createlink",
        //Файл, в которой будет вызвана функция из call
        "file"    => "pay.php",
        //Функция, которая обработкает когда прийдет запрос
        "call"    => function() {
            (new wata())->create_link();
        },
    ],

    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/wata",
        "file"    => "pay.php",
        "call"    => function() {
            (new wata())->webhook();
        },
    ],
    [
        "method"  => "POST",
        "pattern" => "/donate/webhook/watapro",
        "file"    => "pay.php",
        "call"    => function() {
            (new wata())->webhook();
        },
    ],

];

