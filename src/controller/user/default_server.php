<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 31.08.2022 / 14:41:02
 */

namespace Ofey\Logan22\controller\user;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\controller\registration\user;
use Ofey\Logan22\model\server\server;

class default_server {

    /**
     * Установка сервера по умолчанию пользователю
     */
    static public function change(): void
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: board::notice(false, "No ID server");
        $server = server::getServer($id);
        if(!$server){
            board::error("Server does not exist");
        }
        if(\Ofey\Logan22\model\user\user::self()->isGuest()){
            $_SESSION['server_id'] = $id;
        }else{
            \Ofey\Logan22\model\user\user::getUserId()->setServerId($server->getId());
        }
        board::notice(true, lang::get_phrase(254, $server->getName(), $server->getRateExp()));
    }
}