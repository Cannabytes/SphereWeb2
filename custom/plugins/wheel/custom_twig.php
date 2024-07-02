<?php

namespace Ofey\Logan22\custom\plugins\wheel;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class custom_twig
{

    public function get_setting($type)
    {
        $data = sql::getRow("SELECT `data` FROM `server_cache` WHERE `type` = ? AND `server_id` = ?;", [$type, user::self()->getServerId()]);
        if ($data) {
            return json_decode($data['data'], true);
        }
    }

}