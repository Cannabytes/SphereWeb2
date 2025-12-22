<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 31.08.2022 / 16:56:36
 */

namespace Ofey\Logan22\controller\statistic;

use Ofey\Logan22\model\statistic\statistic as statistic_model;
use Ofey\Logan22\model\server\server;

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

    public static function show_json_stats() {
        header('Content-Type: application/json; charset=utf-8');
        $servers = server::getServerAll();
        $all_stats = [];
        foreach ($servers as $server) {
            $data = $server->getCache('statistic');
            if ($data != null) {
                $meta = [];
                $meta['server_name'] = $server->getName();
                $meta['chronicle'] = $server->getChronicle();
                $meta['rate_exp'] = $server->getRateExp();
                $all_stats[$server->getName()] = [
                    'meta' => $meta,
                    'statistic' => $data
                ];
            }
        }
        echo json_encode($all_stats, JSON_UNESCAPED_UNICODE);
    }

}