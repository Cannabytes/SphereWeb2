<?php

namespace Ofey\Logan22\controller\sphereapi;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class sphereapi
{
    static function index()
    {
        tpl::display("/admin/sphereapi.html");
    }

    static function check()
    {
        config::load()->sphereApi()->setIp($_POST['ip']);
        config::load()->sphereApi()->setPort($_POST['port']);

        $lastCommit = server::send(type::GET_COMMIT_LAST)->show(false)->getResponse();
        if(isset($lastCommit['last_commit'])){
            board::success("Проверка подключения удалась");
        }
        board::error("Проверка подключения не удалась");
    }

    static function save()
    {
        $ip = $_POST['ip'] ?? "";
        $port = $_POST['port'] ?? "";

        //Проверка IP
        if (empty($ip)) {
            board::error("IP пуст");
        }

        //Проверка порта
        if (!filter_var($port, FILTER_VALIDATE_INT)) {
            board::error("Вы указали неверный порт");
        }

        $data = json_encode([
            "ip" => $ip,
            "port" => $port,
        ]);

        sql::run("DELETE FROM `settings` WHERE `key` = '__config_sphere_api__'");

        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_sphere_api__', ?, ?, ?)", [
          $data,
          0,
          time::mysql(),
        ]);

        board::success("Сохранено");

    }
}