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
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class pay
{

    public static function pay(): void
    {
        $all_donate_system = fileSys::get_dir_files("src/component/donate", [
          'basename' => true,
          'fetchAll' => true,
        ]);
        $donateSysNames    = [];

        $donate = \Ofey\Logan22\controller\config\config::load()->donate();
        foreach ($donate->getDonateSystems() as $system) {
            if ( ! $system->isEnable()) {
                continue;
            }
            if (method_exists($system, 'forAdmin')) {
                if ($system->forAdmin() and ! user::self()->isAdmin()) {
                    continue;
                }
            }
            if (method_exists($system, 'getDescription')) {
                $donateSysNames[] = [
                  'name'        => $system->getName(),
                  'description' => $system->getDescription() ,
                ];
            } else {
                $donateSysNames[] = ['name' => $system->getName()];
            }
        }


        tpl::addVar("donate_history_pay_self", donate::donate_history_pay_self());
        tpl::addVar("title", lang::get_phrase(233));

        tpl::addVar("pay_system_default", \Ofey\Logan22\controller\config\config::load()->donate()->getPaySystemDefault());


        $donateSum = sql::run("SELECT SUM(point) AS `count` FROM `donate_history_pay` WHERE user_id = ?", [user::self()->getId()])->fetch()['count'] ?? 0;
        $donateConfig = config::load()->donate();
        $bonusTable   = $donateConfig->getTableCumulativeDiscountSystem();
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

    public static function get_donate_type($type = null)
    {
        $products = donate::products();

        if ($type !== null) {
            $type  = mb_strtolower($type);
            $allow = ["weapon", "armor", "jewelry", "etcitem", "pack"];

            if ($type === "other") {
                $type = "etcitem";
            }

            if ( ! in_array($type, $allow)) {
                $type = null;
            }

            if ($type == 'pack') {
                $pack_all = [];
                foreach ($products as $key => $product) {
                    if ($product["is_pack"]) {
                        $pack_all[] = $product;
                    }
                }
                $products = $pack_all;
                unset($pack_all);
            } else {
                foreach ($products as $key => $product) {
                    if ( ! isset($product['type'])) {
                        $product['type'] = "etcitem";
                    }
                    if ($product['is_pack'] and $type !== 'pack') {
                        unset($products[$key]);
                    }
                    if (isset($product['type']) && $product['type'] !== $type) {
                        unset($products[$key]);
                    } elseif ($type === "armor" && isset($product['bodypart'])) {
                        if ($product['is_pack']) {
                            unset($products[$key]);
                        }
                        if (
                          in_array($product['bodypart'], ["rear;lear", "neck", "rfinger;lfinger"]) ||
                          $product['type'] === "weapon" ||
                          $product['type'] === "etcitem"
                        ) {
                            unset($products[$key]);
                        }
                    } elseif ($type === "jewelry" && isset($product['bodypart'])) {
                        if ( ! in_array($product['bodypart'], ["rear;lear", "rfinger;lfinger", "neck"])) {
                            unset($products[$key]);
                        }
                        if ($product['is_pack']) {
                            unset($products[$key]);
                        }
                    } elseif ($type !== "weapon" && $type !== "armor" && $type !== "jewelry" && isset($product['bodypart'])) {
                        if ( ! in_array($product['bodypart'], ["etcitem"])) {
                            unset($products[$key]);
                        }
                        if ($product['is_pack']) {
                            unset($products[$key]);
                        }
                    }
                }
            }
        }
        $donateInfo = __config__donate;
        $point      = 0;
        if (auth::get_is_auth()) {
            if ($donateInfo['DONATE_DISCOUNT_TYPE_PRODUCT_ENABLE']) {
                $point = donate::getBonusDiscount(auth::get_id(), $donateInfo['discount_product']['table']);
            }
        }
        tpl::addVar("procentProductDiscount", $point);
        tpl::addVar("products", $products);
        tpl::display("/donate/shop.html");
    }

    public static function buyShopItem(): void
    {
        validation::user_protection();
        //        if (!config::getEnableDonate()) error::error404("Отключено");
        if ( ! user::self()->isAuth()) {
            board::notice(false, lang::get_phrase(234));
        }
        donate::buyShopItem();
    }

    public static function currency_exchange_info()
    {
        echo json_encode(__config__donate);
    }

}