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
        if (!$userInfo) {
            board::error("User not found");
        }
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

    //Выдача предмета пользователю от администратора
    static public function addItemUserToWarehouse(): void
    {
        // Проверка прав пользователя
        validation::user_protection("admin");

        // Проверка обязательных параметров
        $serverId = $_POST['serverId'] ?? null;
        $userId = $_POST["userId"] ?? null;
        $itemId = $_POST["itemId"] ?? null;

        // Если какой-либо обязательный параметр отсутствует или пустой
        if (empty($serverId) || empty($userId) || empty($itemId)) {
            board::error("Не все обязательные параметры переданы или они пустые");
            return;
        }

        // Преобразование параметров, если необходимо
        $count = isset($_POST["count"]) ? (int)$_POST["count"] : 1;
        $enchant = isset($_POST["enchant"]) ? (int)$_POST["enchant"] : 0;

        // Проверка, что параметры count и enchant являются целыми числами
        if (!is_int($count) || $count < 1) {
            board::error("Неверное количество предметов");
            return;
        }

        if (!is_int($enchant) || $enchant < 0) {
            board::error("Неверный уровень зачарования");
            return;
        }

        // Добавление предмета в инвентарь пользователя
        $ok = \Ofey\Logan22\model\user\user::getUserId($userId)->addToWarehouse($serverId, $itemId, $count, $enchant, 'issued_by_the_administration');

        if (!$ok['success']) {
            board::error($ok['errorInfo']['message']);
            return;
        }

        board::reload();
        board::success("Предмет выдан");
    }




}