<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:05:18
 */

namespace Ofey\Logan22\component\session;

class session
{

    public static function init(): void
    {
        ini_set('session.gc_maxlifetime', 86400 * 365);
        ini_set('session.cookie_lifetime', 86400 * 365);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        session_start();
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

    //Все сессии гостя, у которых в начале стоит var_ вернем

    public static function add_var($nameVar, $value)
    {
        self::add("var_$nameVar", $value);
    }

    public static function add($key, $value)
    {
        $_SESSION[$key] = $value;
    }

}