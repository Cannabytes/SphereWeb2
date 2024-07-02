<?php

namespace Ofey\Logan22\controller\user\forget;

use DateTime;
use Ofey\Logan22\component\account\generation;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\mail\mail;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class forget
{

    public static function create()
    {
        $email = $_POST['email'];
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::notice(false, lang::get_phrase(281));
        }

        // Проверка на существование пользователя
        $user = user::getUserByEmail($email);
        if (!$user) {
            board::notice(false, lang::get_phrase(282));
        }

        config::load()->captcha()->validator();

        $data = sql::getRow("SELECT `id`, `email`, `active`, `date` FROM `users_password_forget` WHERE email=? ORDER BY `id` DESC LIMIT 1", [
          $email,
        ]);
        if($data){
            if (time::diff($data['date'], time::mysql()) < 10 * 60) {
                board::notice(false, "Сброс пароля можно делать каждые 10 минут");
            }
        }

        $code = generation::password(mt_rand(8, 32), false);
        $link = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . "/forget/password/reset/" . $code;

        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_template_email__'");
        if ($configData) {
            $setting              = json_decode($configData['setting'], true);
            $forget_reg_subject   = $setting['forget_reg_subject'] ?? lang::get_phrase(169);
            $forget_password_html = $setting['forget_password_html'] ?? "Link: {$link}";
        } else {
            $forget_reg_subject   = lang::get_phrase(169);
            $forget_password_html = "Link: {$link}";
        }

        $forget_password_html = str_replace([
          '%site%',
          '%code%',
          '%link%',
          '%email%',
          '%expire_time%',
        ], [
          $_SERVER['SERVER_NAME'],
          $code,
          $link,
          $email,
          10,
        ], $forget_password_html);

        $date = new DateTime();
        sql::run("INSERT INTO `users_password_forget` (`code`, `email`, `date`, `ip`, `active`) VALUES (?, ?, ?, ?, ?)", [
          $code,
          $email,
          $date->format('Y-m-d H:i:s'),
          $_SERVER['REMOTE_ADDR'],
          true,
        ], true);

        mail::send($email, $forget_password_html, $forget_reg_subject);
    }

    public static function validate($code)
    {
        $data = sql::getRow("SELECT `id`, `email`, `active`, `date` FROM `users_password_forget` WHERE code=? and active=1 LIMIT 1", [
          $code,
        ]);
        if ( ! $data) {
            error::error404();
        }

        sql::run("UPDATE `users_password_forget` SET `active` = ? WHERE `id` = ?", [
          false,
          $data['id'],
        ]);

        $nowTime     = new DateTime();
        $requestTime = new DateTime($data['date']);

        if (($nowTime->getTimestamp() - $requestTime->getTimestamp()) > 10 * 60) {
            tpl::display("forget_new_password_timeout.html");
        }

        $newPassword = generation::password(mt_rand(8, 10), false);
        $ok          = user::getUserByEmail($data['email'])?->setPassword($newPassword);
        if ( ! $ok) {
            error::error404();
        } else {
            tpl::addVar("newPassword", $newPassword);
            tpl::display("forget_new_password.html");
        }
    }

}