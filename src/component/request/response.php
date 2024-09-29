<?php
/**
 * Приходит запрос от сферы апи на случай каких либо проверок
 * ULR: /response/request
 */

namespace Ofey\Logan22\component\request;

class response
{

    public static function get(): void
    {
        header("HTTP/1.0 200 OK");
    }

}