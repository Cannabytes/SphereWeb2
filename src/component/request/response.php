<?php
/**
 * Приходит запрос от сферы апи на случай каких либо проверок
 * ULR: /response/request
 */

namespace Ofey\Logan22\component\request;

use Ofey\Logan22\model\stream\streamcheck;

class response
{

    public static function get(): void
    {
        header("HTTP/1.0 200 OK");
        //Автопроверка стримов
        streamcheck::autoCheckLiveStream();
    }

}