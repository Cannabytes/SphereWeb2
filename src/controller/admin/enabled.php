<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;

class enabled
{

    static function setEnabled(): void
    {
        $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOL);
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $server = \Ofey\Logan22\component\sphere\server::send(type::SET_SERVER_ENABLED, [
            'enabled' => $enabled,
            'id' => $id,
        ])->show()->getResponse();
        if($server['success']){
            $id = $server['id'];
            $cur_server = \Ofey\Logan22\model\server\server::getServer($id);
            if($server['status'] == 'enabled'){
                $cur_server->setDisabled(false);
                $message = "Сервер включен. Началась загрузка данных.";
            }else{
                $cur_server->setDisabled(true);
                $message = "Сервер выключен";
            }
            $cur_server->save();
            board::reload();
            board::success($message);
        }
    }

}