<?php

namespace Ofey\Logan22\controller\donate;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\model\donate\donate;

class shop
{

    static function getShopObjectJSON(): void
    {
        $objectId = $_POST['objectId'] ?? board::error("Нет объекта");
        $item = donate::getShopItems($objectId);
        echo json_encode($item);
    }

}