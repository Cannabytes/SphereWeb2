<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:05:18
 */

namespace Ofey\Logan22\component\session;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\db\sql;

class session
{

    public static function init(): void
    {
        ini_set('session.gc_maxlifetime', 86400 * 365);
        ini_set('session.cookie_lifetime', 86400 * 365);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        session_start();
        if (!file_exists(fileSys::get_dir('/data/db.php'))) {
            return;
        }
        if ( ! isset($_SESSION['id'])) {
            if ( ! isset($_SESSION['HTTP_REFERER'])) {
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $_SESSION['HTTP_REFERER'] = self::domainViewsCounter($_SERVER['HTTP_REFERER']);
                } else {
                    $_SESSION['HTTP_REFERER'] = false;
                }
            } elseif ($_SESSION['HTTP_REFERER'] === false) {
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $_SESSION['HTTP_REFERER'] = self::domainViewsCounter($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public static function domainViewsCounter($url)
    {

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'];
        }else{
            $host = $url;
        }

        $date = date("Y-m-d");
        $data = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS';");
        if ($data) {
            $dataJSONDecode = json_decode($data["data"], true);
            $n              = false;
            foreach ($dataJSONDecode as &$val) {
                $referer = $val['referer'];
                if ($host == $referer) {
                    $val['count'][$date]++;
                    $n = true;
                    break;
                }
            }
            if ( ! $n) {
                $dataJSONDecode[] = [
                  'referer' => $host,
                  'count'   => [
                    $date => 1,
                  ],
                ];
            }
            $dataJSON = json_encode($dataJSONDecode, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sql::run("UPDATE `server_cache` SET `data` = ? WHERE `type` = 'HTTP_REFERER_VIEWS'", [$dataJSON]);
        } else {
            $arr = json_encode([
              [
                'referer' => $host,
                'count'   => [
                  $date => 1,
                ],
              ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sql::run("INSERT INTO `server_cache` (`type`, `data`) VALUES ('HTTP_REFERER_VIEWS', ?)", [$arr]);
        }

        return $host;
    }

    public static function edit($key, $value): bool
    {
        if (isset($_SESSION[$key])) {
            $_SESSION[$key] = $value;

            return true;
        }

        return false;
    }

    public static function get($key)
    {
        if ( ! isset($_SESSION[$key])) {
            return null;
        }

        return $_SESSION[$key];
    }

    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);

            return true;
        }

        return false;
    }

    public static function clear()
    {
        session_destroy();
    }

    public static function get_guest_var()
    {
        $result = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, "var_") === 0) {
                $var          = str_replace("var_", "", $key);
                $result[$var] = $value;
            }
        }

        return $result;
    }

    public static function add_var($nameVar, $value)
    {
        self::add("var_$nameVar", $value);
    }

    //Все сессии гостя, у которых в начале стоит var_ вернем

    public static function add($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    private static function isInternalReferer($referer)
    {
        $parsedReferer = parse_url($referer);
        $refererHost   = $parsedReferer['host'] ?? '';

        return $refererHost === $_SERVER['SERVER_NAME'];
    }

}