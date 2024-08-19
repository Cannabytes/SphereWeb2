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


    public static function get_exp($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['exp'];
    }

    public static function get_clan($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['clan'];
    }



    public static function get_castle($server_id = 0)
    {
        self::getStatistic($server_id);
        return self::$statistic[$server_id]['castle'];
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
        if (self::$statistic === false) {
            return false;
        }

        if (is_array(self::$statistic) && isset(self::$statistic[$server_id])) {
            return self::$statistic[$server_id];
        }

        if ($server_id === null) {
            $server_id = user::self()->getServerId();
        }

        \Ofey\Logan22\component\sphere\server::setUser(user::self());
        self::$statistic[$server_id] = \Ofey\Logan22\component\sphere\server::send(type::STATISTIC, ['id' => $server_id])->getResponse() ?? false;

        return self::$statistic[$server_id];
    }


}