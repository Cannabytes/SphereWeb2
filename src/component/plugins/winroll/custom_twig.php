<?php

namespace Ofey\Logan22\component\plugins\winroll;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;

class custom_twig
{

    function getLogsWinroll(): array
    {
        $logs = sql::getRows("SELECT `user_id`, `variables` FROM `logs_all` WHERE `type` = 14 and server_id = ? ORDER BY `id` DESC LIMIT 30", [
            \Ofey\Logan22\model\user\user::self()->getServerId(),
        ]);
        if (empty($logs)) {
            return [];
        }
        $result = [];
        foreach ($logs as $log) {
            $var = json_decode($log['variables'], true);
            $user = \Ofey\Logan22\model\user\user::getUserId($log['user_id']);
            $itemId = $var[0];
            $enchant = $var[1];
            $name = $var[2];
            $count = $var[3];
            $username = $user->getName();
            $itemName = item::getItem($itemId)->getItemName();
            $result[] = [
                'username' => $username,
                'item' => $name,
                'count' => $count,
                'enchant' => $enchant,
                'itemName' => $itemName,
            ];
        }
        return $result;
    }

}