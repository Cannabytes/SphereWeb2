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

        if (!empty($_POST['new_password'])) {
            $password = $_POST['password'] ?? "";
            $newPassword = $_POST['new_password'];

            if(mb_strlen($newPassword) < 4 || mb_strlen($newPassword) > 32){
                board::error(lang::get_phrase('password_max_min_sim', 4, 32));
            }

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
        if (!preg_match("/^[\p{L}0-9_\-]+$/u", $name)) {
            $error[] = lang::get_phrase(190);
            $ok = false;
        }
        if ($ok) {
            if (!preg_match('/^\p{L}/u', $name)) {
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
        $avatar_input = $_POST['avatar'] ?? null;

        if (empty($avatar_input)) {
            board::notice(false, lang::get_phrase(194));
            return;
        }

        $avatar_filename = basename($avatar_input);

        if (empty($avatar_filename) || $avatar_filename === '.' || $avatar_filename === '..') {
            board::notice(false, lang::get_phrase("Некорректный формат имени файла аватара"));
            return;
        }

        if (mb_strlen($avatar_filename) > 62) {
            board::notice(false, lang::get_phrase(195));
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/u', $avatar_filename)) {
            board::notice(false, lang::get_phrase('Имя файла аватара содержит недопустимые символы'));
            return;
        }

        if (str_starts_with($avatar_filename, '.')) {
            board::notice(false, lang::get_phrase('Имя файла аватара не должно быть скрытым файлом'));
            return;
        }

        $file_info = pathinfo($avatar_filename);
        $extension = strtolower($file_info['extension'] ?? '');
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (empty($extension) || !in_array($extension, $allowed_extensions)) {
            board::notice(false, lang::get_phrase('Недопустимое расширение файла. Разрешены: jpg, jpeg, png, gif'));
            return;
        }

        $path_to_avatar_file = fileSys::localdir("/uploads/avatar/" . $avatar_filename, true);
        if (!file_exists($path_to_avatar_file)) {
            board::notice(false, lang::get_phrase(196)); // "Файл аватара не найден."
            return;
        }

        $real_avatar_root_path = realpath(fileSys::localdir("/uploads/avatar/", true));
        $real_avatar_file_path = realpath($path_to_avatar_file);

        if ($real_avatar_root_path === false || $real_avatar_file_path === false) {
            error_log("Avatar security check: realpath failed for dir or file. Dir: " . fileSys::localdir("/uploads/avatar/", true) . " File: " . $path_to_avatar_file);
            board::notice(false, lang::get_phrase(196));
            return;
        }

        if (!str_starts_with($real_avatar_file_path, $real_avatar_root_path . DIRECTORY_SEPARATOR)) {
            board::notice(false, lang::get_phrase('Файл аватара находится за пределами разрешенной директории'));
            return;
        }

        if (dirname($real_avatar_file_path) !== $real_avatar_root_path) {
            board::notice(false, lang::get_phrase('Файл аватара находится в недопустимой поддиректории'));
            return;
        }

        user::self()->setAvatar($avatar_filename)->addLog(logTypes::LOG_CHANGE_AVATAR, "LOG_CHANGE_AVATAR", []);
        board::alert([
            'ok' => true,
            'message' => lang::get_phrase(197),
            'src' => ("/uploads/avatar/" . $avatar_filename),
        ]);
    }


}