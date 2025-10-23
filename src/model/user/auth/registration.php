<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 20.09.2022 / 14:39:16
 */

namespace Ofey\Logan22\model\user\auth;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\referral\referral;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\player_account;

    //Регистрация
class registration {

    //Регистрация
    public static function add($email, $password, $account_name = null) {
        try {
            // Начинаем транзакцию
            sql::beginTransaction();

            $timezone = null;
            $get_timezone_ip = null;
            $user_referral_leader = null;
            if (isset($_POST['referral']) && !empty(trim($_POST['referral']))) {
                $user_referral_leader = \Ofey\Logan22\model\user\user::getUserByName(trim($_POST['referral']));
                if ($user_referral_leader == null) {
                    // Откатываем транзакцию, если нет реферала
                    sql::rollBack();
                    board::notice(false, "Проверьте ник пользователя «" . trim($_POST['referral']) . "», который Вас пригласил, такого у нас нет!");
                }
            }

            /*
             * Пользователь по-умолчанию передает свой timezone (берется из браузера)
             * однако, на февраль 23 года, только 93% браузеров поддерживают timezone, да и пользователь может отправить
             * недостоверные данные.
             * По этому сравним со списоком возможных timezone.
             */
            if (isset($_POST['timezone'])) {
                $timezone = $_POST['timezone'];
                foreach (timezone::all() as $key => $val) {
                    if ($_POST['timezone'] == $key) {
                        $timezone = $key;
                        break;
                    }
                }
            }

            $insertUserSQL = "INSERT INTO `users` (`email`, `password`, `ip`, `timezone`, `last_activity`) VALUES (?, ?, ?, ?, ?)";
            $insertArrays = [
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                $_SERVER['REMOTE_ADDR'],
                $timezone,
                time::mysql(),
            ];

            /**
             * Если по каким-то причинам мы не определили ранее timezone пользователя,
             * тогда воспользуемся сторонними API для определения пользовательских данных по IP, в т.е. timezone
             */
            if ($timezone == null) {
                $get_timezone_ip = timezone::get_timezone_ip($_SERVER['REMOTE_ADDR']);
                if ($get_timezone_ip != null) {
                    $insertUserSQL = "INSERT INTO `users` (`email`, `password`, `name`, `ip`, `timezone`, `country`, `city`, `last_activity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertArrays = [
                        $email,
                        password_hash($password, PASSWORD_BCRYPT),
                        "user-" . substr(md5(uniqid()), mt_rand(2,3), mt_rand(4,5)),
                        $_SERVER['REMOTE_ADDR'],
                        $get_timezone_ip['timezone'],
                        $get_timezone_ip['country'],
                        $get_timezone_ip['city'],
                        time::mysql(),
                    ];
                }
            }

            $insert = sql::run($insertUserSQL, $insertArrays);
            $userID = sql::lastInsertId();

            if ($insert) {
                if ($user_referral_leader != null) {
                    referral::add($userID, $user_referral_leader->getId());
                }


                if (server::get_count_servers() > 0 && $account_name != null) {
                    $user = \Ofey\Logan22\model\user\user::getUserId($userID);
                    \Ofey\Logan22\component\sphere\server::setUser($user);
                    $prefixEnable = \Ofey\Logan22\controller\config\config::load()->registration()->getEnablePrefix();
                    if ($prefixEnable) {
                        $prefixType = \Ofey\Logan22\controller\config\config::load()->registration()->getPrefixType();
                        $prefix = $_SESSION['account_prefix'] ?? "";
                        $account_name = $prefixType == "prefix" ? $prefix . $account_name : $account_name . $prefix;
                    }

                    $sphere = \Ofey\Logan22\component\sphere\server::send(type::REGISTRATION, ['login' => $account_name, 'password' => $password, 'is_password_hide' => true,])->show(false)->getResponse();

                    if(isset($sphere['error'])) {
                        // Откатываем транзакцию, если аккаунт занят
                        sql::rollBack();
                        board::response("notice", ["message" => $sphere['error'], "ok" => false, "reloadCaptcha" => true]);
                    }
                }

                session::add('id', $userID);
                session::add('email', $email);
                session::add('password', $password);

                // Фиксируем транзакцию только если всё прошло успешно
                sql::commit();
            } else {
                // Откатываем транзакцию, если не удалось вставить запись
                sql::rollBack();
                board::notice(false, lang::get_phrase(178));
            }
        } catch (\Exception $e) {
            // Откатываем транзакцию при любой ошибке
            sql::rollBack();
            board::notice(false, "Произошла ошибка при регистрации: " . $e->getMessage());
        }
        return $account_name;
    }

}