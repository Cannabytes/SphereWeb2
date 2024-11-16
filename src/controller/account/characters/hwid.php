<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\user\user;

class hwid
{

    public static function reset(): void
    {
        $data = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->isResetHWID();
        if ($data) {
            $player_id = $_POST["player_id"] ?? board::error("No player_id");
            $server    = server::send(type::RESET_HWID, [
                "player_id" => (int)$player_id,
            ])->show(true)->getResponse();
            if(isset($server['success'])){
               board::success($server['success']);
            }
            if(isset($server['error'])){
                board::error($server['error']);
            }
        }else{
            board::error("Server not allow reset HWID");
        }
    }

}