<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 18:14:01
 */

namespace Ofey\Logan22\controller\account\comparison;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class comparison {

    //Синхронизация внутренней БД аккаунтов, реестра аккаунтов АПИ Сферы и реестра аккаунтов игровой базы
    public static function synchronization(){
         //Получение списка аккаунтов
        $accounts = sql::getRows("SELECT `login`,`password`, `email`, `server_id`,`password_hide` FROM `player_accounts` WHERE email = ?", [user::self()->getEmail()]);

        $sphere = server::send(type::SYNCHRONIZATION, $accounts);
        var_dump($sphere);exit();
//        var_dump($accounts);
        die();
    }

    public static function call($server_id){
        validation::user_protection();
        /**
         * TODO:
         * Проблема теперь что в функции start есть
         * $game_accounts = self::accounts_email($server_info, auth::get_email())->fetchAll();
         * self::accounts_email ищет эмайл на стороне сервера, однона, на некоторых сборках нет графы email, к примеру такой как first team
         * Наверное стоит просто переделать методом ввода логина и пароля
         * TODO: Хотя мне кажется что эта функция вообще уже не рабочая. нужно будет посмотреть по исходникам и удалить
         */
        board::notice(false, 'Отключено, требует теперь переработки');
//
//        \Ofey\Logan22\model\userModel\player\comparison::start($server_id);
//        header('Location: /main');
        die();
    }

}