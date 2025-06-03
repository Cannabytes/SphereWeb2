<?php

namespace Ofey\Logan22\controller\user\forget;

use DateTime;
use Ofey\Logan22\component\account\generation;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\mail\mail;
use Ofey\Logan22\component\request\url;
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
        if ( ! $user) {
            board::notice(false, lang::get_phrase(282));
        }

        config::load()->captcha()->validator();

        $data = sql::getRow(
          "SELECT `id`, `email`, `active`, `date` FROM `users_password_forget` WHERE email=? ORDER BY `id` DESC LIMIT 1",
          [
            $email,
          ]
        );
        if ($data) {
            if (time::diff($data['date'], time::mysql()) < 10 * 60) {
                board::notice(false, "Сброс пароля можно делать каждые 10 минут");
            }
        }

        $code = generation::password(mt_rand(8, 32), false);
        $link = url::host("/forget/password/reset/" . $code);

        $lang = user::self()->getLang();

        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_template_email_{$lang}_'");
        if ($configData) {
            $setting              = json_decode($configData['setting'], true);
            $forget_reg_subject   = $setting['forget_reg_subject'] ?? self::notice_registration_html();
            $forget_password_html = $setting['forget_password_html'] ?? self::forget_password_html_default();
        } else {
            $forget_reg_subject   = self::notice_registration_html();
            $forget_password_html = self::forget_password_html_default();
        }

        $forget_password_html = str_replace(["\n", "\t"], "", $forget_password_html);

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

    public static function notice_registration_html(): string
    {
        return lang::get_phrase(169);
    }

    public static function forget_password_html_default(): string
    {
        $html_en = '<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #007bff;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 20px;
            font-size: 16px;
            color: #333333;
            line-height: 1.5;
        }

        .content p {
            margin: 0 0 15px;
        }

        .cta-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .cta-button:hover {
            background-color: #218838;
        }

        .social-icons {
            text-align: center;
            padding: 20px 0;
        }

        .social-icons a {
            margin: 0 10px;
            display: inline-block;
        }

        .social-icons img {
            width: 32px;
        }

        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #dddddd;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h1>Password Reset</h1>
        </div>

        <div class="content">
            <p>Hello,</p>
            <p>To reset your password, please click the following link:</p>
            
            <p style="text-align: center;">
                <a href="%link%" class="cta-button" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Reset Password</a>
            </p>
            
            <p>This link will expire in <strong>%expire_time%</strong> minutes.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
        </div>

        <div class="footer">
            <p>You are receiving this email because you requested a password reset on our website.</p>
        </div>
    </div>
</body>

</html>';

        $html_ru = '<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #007bff;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 20px;
            font-size: 16px;
            color: #333333;
            line-height: 1.5;
        }

        .content p {
            margin: 0 0 15px;
        }

        .cta-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .cta-button:hover {
            background-color: #218838;
        }

        .social-icons {
            text-align: center;
            padding: 20px 0;
        }

        .social-icons a {
            margin: 0 10px;
            display: inline-block;
        }

        .social-icons img {
            width: 32px;
        }

        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #dddddd;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <h1>Сброс пароля</h1>
        </div>

        <div class="content">
            <p>Здравствуйте,</p>
            <p>Для сброса пароля, перейдите по следующей ссылке:</p>
            
			<p style="text-align: center;">
				<a href="%link%" class="cta-button" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Сбросить пароль</a>
			</p>
            
            <p>Время действия ссылки: <strong>%expire_time%</strong> минут.</p>
            <p>Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p>
        </div>

        <div class="footer">
            <p>Вы получили это письмо, потому что запросили сброс пароля на нашем сайте.</p>
        </div>
    </div>
</body>

</html>';

        return match (user::self()->getLang()) {
            'ru' => $html_ru,
            default => $html_en,
        };
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
            (int)false,
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