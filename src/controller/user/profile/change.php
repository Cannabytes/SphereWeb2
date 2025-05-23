<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 29.08.2022 / 12:36:26
 */

namespace Ofey\Logan22\controller\user\profile;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class change
{

    public static function save()
    {
        $isChange = false;
        $isChangePassword = false;
        $name = $_POST['name'] ?? board::error("Некорректное имя");
        $timezone = $_POST['timezone'] ?? board::error("Некорректное время");
        $password = $_POST['password'] ?? "";
        $newPassword = $_POST['new_password'] ?? "";

        if (user::self()->getName() != $name) {
            if (self::valid_name($name)) {
                if (mb_substr($name, 0, 4) == "user") {
                    board::error("Имя не может начинаться с user");
                }
                if (mb_substr($name, 0, 5) == "admin") {
                    board::error("Имя не может начинаться с admin");
                }
                if (mb_substr($name, 0, 9) == "moderator") {
                    board::error("Имя не может начинаться с moderator");
                }
                user::self()->setName($name);
                $isChange = true;
            }
        }

        if(mb_strlen($newPassword) < 4 || mb_strlen($newPassword) > 32){
            board::error(lang::get_phrase('password_max_min_sim', 4, 32));
        }

        if (!empty($_POST['new_password'])) {
            if(user::self()->getPassword() == "GOOGLE"){
                user::self()->setPassword($newPassword);
                $isChange = true;
                $isChangePassword = true;
            }else{
                /**
                 * Проверяем старый пароль
                 */
                if (password_verify($password, user::self()->getPassword())) {
                    user::self()->setPassword($newPassword);
                    $isChange = true;
                    $isChangePassword = true;
                } else {
                    board::error("Старый пароль неправильный");
                }
            }
        }

        //Проверка существование таймзоны
        if (!empty($timezone)) {
            if (in_array(timezone::replace_old_timezone($timezone), timezone::all())) {
                user::self()->setTimezone(timezone::replace_old_timezone($timezone));
                $isChange = true;
            } else {
                board::error("Некорректная таймзона");
            }
        }

        if ($isChange) {

            if (\Ofey\Logan22\controller\config\config::load()->notice()->isChangeUserPassword()) {
                $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_change_user_password');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                ]);
                telegram::sendTelegramMessage($msg, \Ofey\Logan22\controller\config\config::load()->notice()->getChangeUserPasswordThreadId());
            }

            user::self()->addLog(logTypes::LOG_USER_CHANGE_PROFILE, "LOG_USER_CHANGE_PROFILE", []);
            if ($isChangePassword) {
                $_SESSION['password'] = $newPassword;
            }
            board::redirect();
            board::success(lang::get_phrase(217));
        } else {
            board::error("Нечего сохранять");
        }
    }

    private static function valid_name($name): bool
    {
        $error = [];
        $ok = true;
        if (2 > mb_strlen($name)) {
            $error[] = lang::get_phrase(188);
            $ok = false;
        }
        if (16 < mb_strlen($name)) {
            $error[] = lang::get_phrase(189);
            $ok = false;
        }
        if (!preg_match("/^[a-zA-Z0-9_\-]+$/u", $name)) {
            $error[] = lang::get_phrase(190);
            $ok = false;
        }
        if ($ok) {
            if (!ctype_alpha($name[0])) {
                $error[] = "Имя должно начинаться с буквы";
                $ok = false;
            }
        }

        //Проверка существования пользователя с таким ником
        if (sql::run("SELECT `id` FROM `users` WHERE `name` = ?", [$name])->rowCount() > 0) {
            $error[] = "Пользователь с таким ником уже существует";
            $ok = false;
        }

        if (!$ok) {
            $error = implode("\n", $error);
            board::error($error);
        }

        return $ok;
    }

    //Смена аватарки на свой
    public static function show_avatar_page(): void
    {
        validation::user_protection();
        $avatarList = fileSys::file_list('uploads/avatar', ['jpeg']);
        foreach ($avatarList as $key => $value) {
            if (mb_substr($value, 0, 5) == "user_") {
                unset($avatarList[$key]);
            }
            if (mb_substr($value, 0, 6) == "thumb_") {
                unset($avatarList[$key]);
            }
        }
        tpl::addVar([
            "title" => lang::get_phrase(192),
            "avatars" => $avatarList,
        ]);
        tpl::display("select_avatar.html");
    }


    public static function save_avatar(): void
    {
        validation::user_protection();
        $avatar = $_POST['avatar'] ?? null;
        if ($avatar == null) {
            board::notice(false, lang::get_phrase(194));
        }
        if (62 < mb_strlen($avatar))
            board::notice(false, lang::get_phrase(195));
        if (!file_exists(fileSys::localdir("/uploads/avatar/" . $avatar, true)))
            board::notice(false, lang::get_phrase(196));
        user::self()->setAvatar($avatar)->addLog(logTypes::LOG_CHANGE_AVATAR, "LOG_CHANGE_AVATAR", []);
        board::alert([
            'ok' => true,
            'message' => lang::get_phrase(197),
            'src' => ("/uploads/avatar/" . $avatar),
        ]);
    }


}