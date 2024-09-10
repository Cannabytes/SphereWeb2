<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 29.08.2022 / 12:36:26
 */

namespace Ofey\Logan22\controller\user\profile;

use DateTime;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\notification\notification;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Verot\Upload\Upload;

class change {


    public static function save() {
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

        if (!empty($_POST['new_password'])) {
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

        //Проверка существование таймзоны
        if(!empty($timezone)){
            if(in_array(timezone::replace_old_timezone($timezone), timezone::all())){
                user::self()->setTimezone(timezone::replace_old_timezone($timezone));
                $isChange = true;
            }else{
                board::error("Некорректная таймзона");
            }
        }

        if($isChange){
            user::self()->addLog(logTypes::LOG_USER_CHANGE_PROFILE, "LOG_USER_CHANGE_PROFILE", []);
            if($isChangePassword){
                $_SESSION['password'] = $newPassword;
            }
            board::redirect();
            board::success("Сохранено");
        }else{
            board::error("Нечего сохранять");
        }
    }

    public static function show_avatar_page(): void {
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

    //Смена аватарки на свой
    public static function set_self_avatar() {
        validation::user_protection();
        tpl::addVar([
          "PRICE_CHANGE_AVATAR" => PRICE_CHANGE_AVATAR,
        ]);
        tpl::display("userModel/profile/set_avatar.html");
    }

    public static function set_self_avatar_load() {
        validation::user_protection();
        $files = $_FILES['files'] ?? null;
        if ($files == null) {
            return;
        }

        if (PRICE_CHANGE_AVATAR > auth::get_donate_point()) {
            board::error("У Вас недостаточно денег. Стоимость смены аватарки " . PRICE_CHANGE_AVATAR . " " . lang::get_phrase("Sphere-Coin") . ".");
        }

        //проверка на наличие файлов
        if ($files) {
            //Из массива $files оставляем только первый массив
            $file = array_map(function ($file) {
                return $file[0];
            }, $files);

            $handle = new Upload($file['tmp_name']);
            if ($handle->uploaded) {
                $handle->allowed = ['image/*'];
                $handle->mime_check = true;
                $handle->file_max_size = 5 * 1024 * 1024; // Разрешенная максимальная загрузка 4mb

                $filename = "user_" . md5(time() . mt_rand(0, 1000000));

                $handle->file_new_name_body = $filename;
                $handle->image_resize = true;
                $handle->image_x = 450;
                $handle->image_ratio_y = true;
                $handle->file_name_body_pre = 'thumb_';
                $handle->image_convert = 'webp';
                $handle->webp_quality = 95;
                $handle->process('./uploads/avatar');
                if (!$handle->processed) {
                    board::notice(false, $handle->error);
                }

                $handle->file_new_name_body = $filename;
                $handle->image_resize = true;
                $handle->image_x = 1200;
                $handle->image_ratio_y = true;
                $handle->image_convert = 'webp';
                $handle->webp_quality = 95;
                $handle->process('./uploads/avatar');
                if ($handle->processed) {
                    $handle->clean();

                    if (mb_substr(auth::get_avatar(), 0, 5) == "user_") {
                        unlink("uploads/avatar/" . auth::get_avatar());
                    }
                    donate::taking_money(PRICE_CHANGE_AVATAR, auth::get_id());
                    auth::set_donate_point(auth::get_donate_point() - PRICE_CHANGE_AVATAR);

                    auth::set_avatar($filename . ".webp");
                    userlog::add("new_avatar", 548);
                    \Ofey\Logan22\model\user\profile\change::set_avatar($filename . ".webp");
                    board::alert([
                      'type' => 'notice_set_avatar',
                      'ok' => true,
                      'message' => lang::get_phrase(197),
                      'src' => "/uploads/avatar/thumb_" . $filename . ".webp",
                      'count_sphere_coin' => auth::get_donate_point(),
                    ]);

                }
                if ($handle->error) {
                    $fileName = $files['file'];
                    $msg = lang::get_phrase(455) . " '" . $fileName . "'\n" . lang::get_phrase(456) . " : " . $handle->error;
                    board::notice(false, $msg);
                }
            }
        }
    }

    public static function show_background_avatar_page(): void {
        validation::user_protection();
        tpl::addVar([
          "title" => lang::get_phrase(193),
          "avatars" => fileSys::file_list('src/template/logan22/assets/images/navatarback'),
        ]);
        tpl::display("userModel/option/select_background_avatar.html");
    }

    public static function save_avatar(): void {
        validation::user_protection();
        echo 10 / 0;
        return;
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
          'src' => fileSys::localdir("/uploads/avatar/" . $avatar),
        ]);
    }


    private static function valid_name($name): bool {
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
        if(sql::run("SELECT `id` FROM `users` WHERE `name` = ?", [$name])->rowCount() > 0){
            $error[] = "Пользователь с таким ником уже существует";
            $ok = false;
        }

        if (!$ok) {
            $error = implode("\n", $error);
            board::error($error);
        }

        return $ok;
    }


}