<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 31.08.2022 / 17:09:15
 */

namespace Ofey\Logan22\model\statistic;

use Error;
use Exception;
use Ofey\Logan22\component\cache\cache;
use Ofey\Logan22\component\cache\dir;
use Ofey\Logan22\component\cache\timeout;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\image\crest;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\server\serverModel;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\player\character;
use Ofey\Logan22\model\user\user;

class statistic
{

    private static null|array|false $pvp = null;

    private static null|array|false $pk = null;

    //Добавляет массиву параметр t/f - разрешено ли просматривать профиль

    private static null|array|false $players_online_time = null;

    private static null|array|false $clan = null;

    private static null|array|false $heroes = null;

    private static null|array|false $castle = null;

    private static null|array|false $players_block = null;

    private static null|array|false $players_heroes = null;

    private static ?array $get_player_info = null;

    private static ?array $top_counter = null;

    private static ?array $class = null;

    private static null|false|array $statistic = null;

    public static function get_pvp($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['pvp'] ?? null;
    }


    public static function get_pk($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['pk'];
    }

    public static function get_players_online_time($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['online'];
    }

    public static function get_clan($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['clan'];
    }

    public static function get_heroes($server_id = 0)
    {
        if (isset(self::$heroes[$server_id]) && self::$heroes[$server_id]) {
            return self::$heroes[$server_id];
        }
        try {
            return self::$heroes[$server_id] = self::get_data_statistic(
              dir::statistic_heroes,
              'statistic_top_heroes',
              $server_id,
              second: timeout::statistic_heroes->time()
            );
        } catch (Error $e) {
        }
    }

    public static function get_castle($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['castle'];
    }


    public static function get_player_info($player_name, $server_id = 0)
    {
        if (isset(self::$get_player_info[$server_id]) && self::$get_player_info[$server_id]) {
            return self::$get_player_info[$server_id];
        }
        try {
            return self::$get_player_info[$server_id] = self::get_data_statistic_player(
              dir::statistic_player_info,
              'statistic_player_info',
              player_name: $player_name,
              server_id: $server_id,
              acrossAll: false,
              prepare: [$player_name],
              second: timeout::statistic_player_info->time()
            );
        } catch (Error $e) {
        }
    }

    private static function get_data_statistic_player(
      dir $dir,
      string $collection_sql_name,
      string $player_name = null,
      int $server_id = 0,
      bool $acrossAll = true,
      bool $crest_convert = true,
      $prepare = [],
      $second = 60
    ) {
        [
          $server_info,
          $json,
        ] = server::preAcross($dir, $server_id, $player_name);
        if ($server_info == null) {
            return null;
        }
        if ($json) {
            return $json;
        }
        if ($acrossAll) {
            $data = server::acrossAll($collection_sql_name, $server_info, $prepare);
        } else {
            $data = server::across($collection_sql_name, $server_info, $prepare);
        }
        if ($data === false) {
            return null;
        }
        if ($data) {
            if ($crest_convert) {
                crest::conversion($data, rest_api_enable: $server_info['rest_api_enable']);
            }
            cache::save($dir->show_dynamic($server_info['id'], $player_name), $data);
        }

        return $data;
    }

    public static function timeHasPassed($seconds, $reduce = false): string
    {
        $days    = floor($seconds / 86400);
        $seconds %= 86400;
        $hours   = floor($seconds / 3600);
        $seconds %= 3600;
        $minutes = floor($seconds / 60);
        $seconds %= 60;

        $result = '';
        if ($days > 0) {
            $d = \Ofey\Logan22\component\lang\lang::get_phrase('d');
            $daysStr = \Ofey\Logan22\component\lang\lang::get_phrase('days');
            $result .= $days . ($reduce ? " {$d}. " : " {$daysStr}, ");
        }
        if ($hours > 0) {
            $h = \Ofey\Logan22\component\lang\lang::get_phrase('h');
            $hoursStr = \Ofey\Logan22\component\lang\lang::get_phrase('hours');
            $result .= $hours . ($reduce ? " {$h}. " : " {$hoursStr}, ");
        }
        if ($minutes > 0) {
            $m = \Ofey\Logan22\component\lang\lang::get_phrase('m');
            $minutesStr = \Ofey\Logan22\component\lang\lang::get_phrase('minutes');
            $result .= $minutes . ($reduce ? " {$m}. " : " {$minutesStr}, ");
        }

        $s = \Ofey\Logan22\component\lang\lang::get_phrase('s');
        $secondsStr = \Ofey\Logan22\component\lang\lang::get_phrase('seconds');
        $result .= $seconds . ($reduce ? " {$s}. " : " {$secondsStr}");

        return $result;
    }



    private static function getStatistic($server_id = null)
    {
        if(self::$statistic === false){
            return false;
        }
        if(self::$statistic[$server_id] !== null){
            return self::$statistic[$server_id];
        }
        if($server_id == null){
            $server_id = user::self()->getServerId();
        }
        \Ofey\Logan22\component\sphere\server::setUser(user::self());
        self::$statistic[$server_id] = \Ofey\Logan22\component\sphere\server::send(type::STATISTIC, ['id'=>$server_id])->getResponse() ?? false;
    }

}