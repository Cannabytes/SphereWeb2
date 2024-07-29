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
        $name = $_POST['name'] ?? board::error("Некорректное имя");
        $timezone = $_POST['timezone'] ?? board::error("Некорректное время");
        $password = $_POST['password'] ?? "";
        $newPassword = $_POST['new_password'] ?? "";

        if (user::self()->getName() != $name) {
            if (\Ofey\Logan22\model\user\profile\change::valid_name($name)) {
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
            $_SESSION['password'] = $newPassword;
            board::success("Сохранено");
        }else{
            board::error("Нечего сохранять");
        }

//        \Ofey\Logan22\model\user\profile\change::set();
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

    public static function set_avatar_background(): void {
        validation::user_protection();
        $avatar = $_POST['avatar'];
        if (62 < mb_strlen($avatar))
            board::notice(false, lang::get_phrase(198));
        if (!file_exists("src/template/logan22/assets/images/navatarback/" . $avatar))
            board::notice(false, lang::get_phrase(199));
        \Ofey\Logan22\model\user\profile\change::set_avatar_background($avatar);
        board::notice(true, lang::get_phrase(197));
    }

    public static function change_theme() {
        if (validation::user_protection(need_redirect: false)) {
            if($_POST['theme'] == "true"){
                $theme = "dark";
            }
            if($_POST['theme'] == "false"){
                $theme = "";
            }
            \Ofey\Logan22\model\user\profile\change::set_theme($theme);
        }else{
            if($_POST['theme'] == "true"){
                session::add("var_theme", "dark");
            }
            if($_POST['theme'] == "false"){
                session::add("var_theme", "");
            }
        }
    }

    public static function transfer_money(){
        validation::user_protection();
        if(!ENABLE_SPHERECOIN_TRANSFER){
            board::error("Disabled");
        }
        if(!is_numeric($_POST['count']) || empty($_POST['count']) || str_contains($_POST['count'], '.')){
            board::error("Некорректное значение суммы");
        }
        $moneyCount = $_POST['count'] ?? 0;
        $user = trim($_POST['userModel']);
        if($moneyCount<=0){
            board::error("Сумма должна быть больше нуля");
        }
        if ($moneyCount > auth::get_donate_point()) {
            board::error("У Вас недостаточно денег");
        }
        $userInfo = auth::exist_user_nickname($user);
        if (!$userInfo) {
            board::error("Пользователь не найден");
        }
        if ($userInfo['id'] == auth::get_id()) {
            board::error("Нельзя перевести деньги самому себе");
        }
        $ltt = auth::get_user_variables("last_transfer_transaction");
        if ($ltt) {
            $nowTime = new DateTime();
            $requestTime = new DateTime($ltt['date_create']);
            $second_b = $nowTime->getTimestamp() - $requestTime->getTimestamp();
            //Сделай провреку на 15 секунд
            if ($second_b < 15) {
                board::error("Вы слишком часто переводите деньги");
            }
        }
        userlog::add("money_transfer", 540, [$moneyCount, $userInfo['id']]);
        \Ofey\Logan22\model\user\profile\change::transfer_money($moneyCount, $userInfo['id']);
        sql::sql("INSERT INTO `log_transfer_spherecoin` (`user_sender`, `user_receiving`, `count`) VALUES (?, ?, ?)", [auth::get_id(), $userInfo['id'], $moneyCount]);
        \Ofey\Logan22\model\user\auth\user::set_variable("last_transfer_transaction", null);
        board::success("Перевод успешно выполнен");
    }
}
