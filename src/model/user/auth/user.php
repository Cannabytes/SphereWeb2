<?php

namespace Ofey\Logan22\model\user\auth;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class user {

    public static function get_variable($var) {
        auth::get_user_variables($var);
    }

    //Установка простого значения переменной пользователя
    //Если server_id не указан, то устанавливается значение для всех серверов, в основном нужен для плагинов, где важен учет сервера
    public static function set_variable($var, $val, $server_id = null) {
        sql::run("DELETE FROM `user_variables` WHERE `var` = ? AND `user_id` = ? AND `server_id` = ?", [$var, auth::get_id(), $server_id]);
        sql::run("INSERT INTO `user_variables` (`server_id`,`user_id`, `var`, `val`, `date_create`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $server_id, auth::get_id(), $var, $val, time::mysql(), time::mysql()
            ]);
        return sql::lastInsertId();
    }

    public static function getUsersByName($name) {
        $name = "$name%";
        return sql::getRows("SELECT `name` FROM users WHERE users.`name` LIKE ?", [$name]);
    }

    //На вход принимает массив id пользователей
    public static function getUsers(array $usersID) {
        $usersID = implode(",", $usersID);
        return sql::getRows("SELECT users.id,	users.`name` FROM users WHERE users.id IN ($usersID)");
    }

    public static function All(): array {
        return sql::getRows("SELECT * FROM users");
    }

    public static function edit($id, $email = null, $name = "", $donate = 0, $password = "", $group = "userModel"): bool {
        if (auth::get_access_level() != $group and auth::get_id() == $id) {
            board::notice(false, "Запрещено менять свою группу администратору");
        }
        if ($password == "") {
            $sql = "UPDATE users SET email = ?, name = ?, donate_point = ?, access_level = ? WHERE id = ?";
            $ok = sql::sql($sql, [$email, $name, $donate, $group, $id]);
            if ($ok) {
                return true;
            } else {
                return false;
            }
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET email = ?, name = ?, donate_point = ?, password = ?, access_level = ? WHERE id = ?";
        $ok = sql::sql($sql, [$email, $name, $donate, $password_hash, $group, $id]);
        if ($ok) {
            if (auth::get_id() == $id) {
                auth::set_password($password);
                auth::apply_password();
            }
            return true;
        } else {
            return false;
        }
    }

}