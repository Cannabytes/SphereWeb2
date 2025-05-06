<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 30.08.2022 / 0:33:14
 */

namespace Ofey\Logan22\controller\donate;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class pay
{

    public static function pay(): void
    {
        if(server::get_count_servers()==0){
            error::error404("Ошибка: Необходимо подключить хотя бы один сервер и настроить его платежную систему.");
            return;
        }
        $donateSysNames    = [];
        $donate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->getDonateConfig();
        foreach ($donate->getDonateSystems() as $system) {
            if ( ! $system->isEnable()) {
                continue;
            }
            $donateSysNames[] = $system;
        }

        tpl::addVar("donate_history_pay_self", donate::donate_history_pay_self());
        tpl::addVar("title", lang::get_phrase(233));
        tpl::addVar("pay_system_default", $donate->getPaySystemDefault());

        $donateSum = sql::run("SELECT SUM(point) AS `count` FROM `donate_history_pay` WHERE user_id = ?", [user::self()->getId()])->fetch()['count'] ?? 0;
        $bonusTable   = $donate->getTableCumulativeDiscountSystem();
        $percent      = 0;
        foreach ($bonusTable as $row) {
            if ($donateSum >= $row['coin']) {
                $percent = $row['percent'];
            } else {
                break;
            }
        }
        $donate_history_pay = sql::getRows("SELECT * FROM `donate_history_pay` WHERE user_id = ? ORDER BY id DESC", [user::self()->getId()]);

        tpl::addVar("donate_history_pay", $donate_history_pay);
        tpl::addVar("count_all_donate_bonus", $donateSum);
        tpl::addVar("count_all_donate_bonus_percent", $percent);
        tpl::addVar("donateSysNames", $donateSysNames);
        tpl::display("/pay.html");
    }

    public static function shop(): void
    {
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableShop()) {
            error::error404("Отключено");
        }

        tpl::addVar("title", lang::get_phrase(233));
        tpl::display("/shop.html");
    }


    public static function buyShopItem(): void
    {
        validation::user_protection();
        if ( ! \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableShop()) {
            board::error("Отключено");
        }
        if ( ! user::self()->isAuth()) {
            board::notice(false, lang::get_phrase(234));
        }
        donate::buyShopItem();
    }

}