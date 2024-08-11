<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;

class server
{

    //Переподключение к серверу, если сервер был отключен
    public static function server_reconnect(): void
    {
        $server_id = $_POST['serverId'] ?? board::error("Не передан ID сервера");
        $sphere = \Ofey\Logan22\component\sphere\server::send(type::SERVER_RECONNECT, ['id' => (int)$server_id])->show()->getResponse();
        if(isset($sphere['success'])){
            if($sphere['success']){
                board::reload();
                board::success("Сервер был включен");
            }
        }
    }

}