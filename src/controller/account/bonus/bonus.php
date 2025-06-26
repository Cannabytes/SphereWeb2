<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 02.12.2022 / 21:18:05
 */

namespace Ofey\Logan22\controller\account\bonus;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class bonus {

    //Бонус код
    public static function code() {
        tpl::display("/bonus/bonus.html");
    }

    public static function receiving(): void {
        validation::user_protection();
        if( ! config::load()->enabled()->isEnableBonusCode()) {
            board::notice(false, "Бонус коды отключены");
        }
        $code = $_POST['code'] ?? board::notice(false, "Не передано значение объекта");
        \Ofey\Logan22\model\bonus\bonus::getCode($code);
    }


}