<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 31.08.2022 / 16:56:36
 */

namespace Ofey\Logan22\controller\statistic;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\chronicle\race_class;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\statistic\statistic as statistic_model;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class statistic {

    public static function pvp_ajax() {
        echo json_encode(statistic_model::get_pvp());
    }

    public static function pk_ajax() {
        echo json_encode(statistic_model::get_pk());
    }

    public static function clan_ajax() {
        echo json_encode(statistic_model::get_clan());
    }

    public static function player_ajax() {
        echo json_encode(statistic_model::get_players_online_time());
    }


    public static function castle_ajax() {
        echo json_encode(statistic_model::get_castle());
    }

}