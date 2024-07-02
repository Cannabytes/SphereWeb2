<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;

class hwid
{

    public static function reset(): void
    {
        $data = \Ofey\Logan22\model\server\server::getServer()->getServerData('resetHWID');
        if ($data->getVal()) {
            $player_id = $_POST["player_id"] ?? board::error("No player_id");
            $server    = server::send(type::RESET_HWID, [
              "player_id" => (int)$player_id,
            ])->getResponse();
            if ($server['success'] == 'success') {
                board::success($server['success']);
            }
        }else{
            board::error("Server not allow reset HWID");
        }
    }

}