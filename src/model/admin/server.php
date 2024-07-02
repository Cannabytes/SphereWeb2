<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 17.08.2022 / 19:21:01
 */

namespace Ofey\Logan22\model\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\install\install;
use Ofey\Logan22\model\server\serverModel;

class server {

    //Добавление описания
    public static function add_description() {
        $server_id = (int)$_POST['id'];
        $lang = ($_POST['lang']);
        $page_id = (int)($_POST['page_id']);
        sql::run("DELETE FROM `server_description` WHERE `server_id` = ? AND `lang` = ? ", [
            $server_id,
            $lang,
        ]);
        sql::run("INSERT INTO `server_description` (`server_id`, `lang`, `page_id`) VALUES (?, ?, ?)", [
            $server_id,
            $lang,
            $page_id,
        ]);
        board::notice(true, 'Добавлено');
    }

    public static function description_default() {
        $server_id = $_POST['server_id'];
        $page_id = $_POST['page_id'];
        $lang = $_POST['lang'];
        sql::run("UPDATE `server_description` SET `default` = 0 WHERE `server_id` = ?", [
            $server_id,
        ]);
        sql::run("UPDATE `server_description` SET `default` = 1 WHERE `server_id` = ? AND `lang` = ? AND `page_id` = ?", [
            $server_id,
            $lang,
            $page_id,
        ]);
    }

    public static function server_info($id) {
        return sql::run("SELECT * FROM `server_list` WHERE id=?;", [$id])->fetch();
    }

    public static function get_server_data($id){
        return sql::getRows("SELECT * FROM `server_data` WHERE server_id = ?", [$id]);
    }

 }