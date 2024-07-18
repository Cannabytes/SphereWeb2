<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\user;
use Ofey\Logan22\template\tpl;

class users {

    public static function getUserInfo($id): void
    {

        $userInfo = \Ofey\Logan22\model\user\user::getUserId($id);
        tpl::addVar("userInfo", $userInfo);

        $logs = sql::getRows("SELECT `id`, `time`, phrase, `variables` FROM logs_all WHERE user_id = ? ORDER BY id DESC LIMIT 1000", [$id]);

        foreach($logs AS &$log){
            $s = json_decode($log['variables']);
            $values = is_array($s) ? array_values($s) : [$s];
            $log['message'] = lang::get_phrase($log['phrase'], ...$values);
        }

        tpl::addVar("logs", $logs);

        $donate_history_pay = sql::getRows("SELECT id, point, message, pay_system, id_admin_pay, `date` FROM donate_history_pay WHERE user_id = ? ORDER BY id DESC;", [$id]);
        tpl::addVar("donate_history_pay", $donate_history_pay);

//        exit;
        tpl::display("/admin/user_profile.html");
    }

    public static function showAll(): void {
        validation::user_protection("admin");
        tpl::addVar("users", user::All());
        tpl::display("/admin/users/users.html");
    }

    public static function edit(): void {
        validation::user_protection("admin");

        $id = $_POST["id"] ?? board::error("No POST id");
        $email = $_POST["email"] ?? board::error("No POST email");
        $name = $_POST["name"] ?? board::error("No POST name");
        $donate = $_POST["donate"] ?? board::error("No POST donate");
        $password = $_POST["password"] ?? "";
        $group = $_POST["group"] ?? "user";

        //Проверка Email на валидацию
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::error("Invalid email");
        }

        if (!is_numeric($donate)) {
            board::error("Invalid donate");
        }

        $user = \Ofey\Logan22\model\user\user::getUserId($id);

        if ($password != "") {
            $user->setPassword($password);
        }

        $sql = "UPDATE users SET email = ?, name = ?, donate_point = ?, access_level = ? WHERE id = ?";
        $ok = sql::sql($sql, [$email, $name, $donate, $group, $id]);
        if ($ok) {
            board::success("User edited");
        } else {
            board::error( "Failed to edit user");
        }


    }

}