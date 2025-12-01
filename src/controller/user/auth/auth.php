<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:10:17
 */

namespace Ofey\Logan22\controller\user\auth;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class auth {

    public static function index() {
        validation::user_protection("guest");
        tpl::display("sign-in.html");
    }

    // Попытка авторизации пользователя
    public static function auth_request() {
        validation::user_protection("guest");
        \Ofey\Logan22\model\user\auth\auth::user_enter();
    }

    public static function logout() {
        if(user::getUserId()->isAuth()) {
            \Ofey\Logan22\model\user\auth\auth::logout();
        }
        header('Location: /main');
        die();
    }

    /**
     * Восстановление пароля
     */
    public static function forget() {
        validation::user_protection("guest");
        tpl::addVar("title", lang::get_phrase(285));
        tpl::display("userModel/forget/email.html");
    }

    public static function returnToMain() {
        header('Location: /main');
        die();
    }

}