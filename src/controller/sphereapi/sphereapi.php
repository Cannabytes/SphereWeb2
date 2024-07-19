<?php

namespace Ofey\Logan22\controller\sphereapi;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class sphereapi
{
    static function index()
    {
        tpl::display("/admin/sphereapi.html");
    }

    static function save()
    {
        $ip = $_POST['ip'] ?? "";
        $port = $_POST['port'] ?? "";

        //Проверка IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            board::error("Вы указали неверный IP адрес");
        }

        //Проверка порта
        if (!filter_var($port, FILTER_VALIDATE_INT)) {
            board::error("Вы указали неверный порт");
        }

        $data = json_encode([
            "ip" => $ip,
            "port" => $port
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