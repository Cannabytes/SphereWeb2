<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 15.09.2022 / 16:49:48
 */

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class donate {

    static public function config() {
        validation::user_protection("admin");
        tpl::addVar([
            'title'       => lang::get_phrase(215),
            'server_list' => server::get_server_info(),
        ]);
        tpl::addVar("products", \Ofey\Logan22\model\donate\donate::products());
        tpl::display("/admin/donate/config.html");
    }

    static public function show() {
        validation::user_protection("admin");
        tpl::addVar([
            'title'       => lang::get_phrase(215),
            'server_list' => server::get_server_info(),
        ]);
        tpl::addVar("products", \Ofey\Logan22\model\donate\donate::products());
        tpl::display("/admin/donate/donate.html");
    }

    public static function add() {
        validation::user_protection("admin");
        tpl::addVar([
            'title'       => lang::get_phrase(216),
            'server_list' => server::get_server_info(),
        ]);
        tpl::display("/admin/donate/add_item.html");
    }

    public static function add_item() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\donate::add_item();
    }

    public static function add_item_pack() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\donate::add_item_pack();
    }

    public static function edit_item() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\donate::edit_item();
    }

    public static function edit_item_pack() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\donate::edit_item_pack();
    }

    public static function remove_item() {
        validation::user_protection("admin");
        $id = request::setting("productId", new request_config(isNumber: true));
        if(\Ofey\Logan22\model\admin\donate::remove_item($id)){
            tpl::addVar("products", \Ofey\Logan22\model\donate\donate::products());
            $async = new async("admin/donate/donate.html");
            $async->block("main-container", "content", "update", true);
            $async->block("title", "title");
            $async->send();;
        }
        board::notice(false, "error");
    }


    public static function add_bonus_money() {
        validation::user_protection("admin");
        $user_id = $_POST['userid'] ?? board::error("Не указан ID пользователя");
        $amount = $_POST['count'] ?? 0;
        $addBonus = filter_var($_POST['addBonus'], FILTER_VALIDATE_BOOLEAN);

        $user = user::getUserId($user_id);
        if ($user) {
            $user_id = $user->getId();
            user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование", "Administrator", user::self()->getId());
            if($addBonus) {
                \Ofey\Logan22\model\donate\donate::addUserBonus($user_id, $amount);
            }
            board::alert([
                "sphereCoin" => user::getUserId($user_id)->getDonate(),
            ]);
        }else{
            board::notice(false, "Не найден пользователь");
        }
    }

    public static function get_history_pay(){
        validation::user_protection("admin");
        $user_id = $_POST['user_id'];
        echo json_encode(\Ofey\Logan22\model\donate\donate::donate_history_pay_self($user_id));
    }


}