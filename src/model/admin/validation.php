<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 16.08.2022 / 16:36:36
 */

namespace Ofey\Logan22\model\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class validation {

    /**
     * Список категорий, которым разрешен доступ
     * userModel, admin
     * default: all
     */
    public static function user_protection($var = ["user", "moderator", "admin"], $need_redirect = true): bool {
        $user_privilege = user::getUserId()->getAccessLevel();
        if(in_array($user_privilege, (array)$var)) {
            return true;
        }
        if($need_redirect){
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                board::notice(false, "Доступ запрещен");
            }
            redirect::location("/main");
        }
        return false;
    }


    //Проверка, является ли пользователь админом
    public static function is_admin(): bool {
        return auth::get_access_level() == "admin";
    }

    public static function min_len($string, $n = 4): bool {
        return (mb_strlen($string) >= $n);
    }

    public static function max_len($string, $n = 140): bool {
        return (mb_strlen($string) <= $n);
    }

}