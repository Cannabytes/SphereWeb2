<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 13.09.2022 / 17:13:31
 */

namespace Ofey\Logan22\component\mail;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\user\forget\forget;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class mail
{
    public static function notice_success_registration_html(): string
    {
        $html_ru = '<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добро пожаловать</title>
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
        <!-- Шапка письма -->
        <div class="header">
            <h1>Добро пожаловать в наш проект!</h1>
        </div>

        <!-- Основное содержание -->
        <div class="content">
            <p>Здравствуйте,</p>
            <p>Мы рады приветствовать вас в нашем сообществе! Спасибо за регистрацию на нашем сайте.</p>
            <p>Теперь у вас есть доступ ко всем возможностям нашего проекта, и мы надеемся, что вы найдете здесь много полезного и интересного.</p>

            <p style="text-align: center;">
                <a href="%site%" class="cta-button" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Посетить сайт</a>
            </p>

            <p>Если у вас возникли какие-либо вопросы, наша поддержка всегда готова помочь.</p>
            <p>Спасибо, что выбрали нас!</p>
        </div>

        <div class="footer">
            <p>Вы получили это письмо, потому что зарегистрировались на нашем сайте.</p>
        </div>
    </div>
</body>

</html>';

        $html_en = '<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
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
        <!-- Header -->
        <div class="header">
            <h1>Welcome to our project!</h1>
        </div>

        <!-- Main content -->
        <div class="content">
            <p>Hello,</p>
            <p>We are excited to welcome you to our community! Thank you for registering on our website.</p>
            <p>You now have access to all the features of our project, and we hope you will find many useful and interesting things here.</p>

            <p style="text-align: center;">
                <a href="%site%" class="cta-button" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Visit Website</a>
            </p>

            <p>If you have any questions, our support team is always here to help.</p>
            <p>Thank you for choosing us!</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>You received this email because you registered on our website.</p>
        </div>
    </div>
</body>

</html>';

        return match (user::self()->getLang()) {
            'ru' => $html_ru,
            default => $html_en,
        };
    }


    public static function getTemplates()
    {
        $lang = user::self()->getLang();
        $emailTpl = sql::getRow("SELECT * FROM  `settings` WHERE `key` = '__config_template_email_{$lang}_'");
        if ($emailTpl) {
            return json_decode($emailTpl['setting'], true);
        }
        return [
            'send_notice_for_registration' => false,
            'notice_reg_subject' => lang::get_phrase('successful_registration_wish_successful_game'),
            'notice_success_registration_html' => self::notice_success_registration_html(),
            'notice_registration_html' => forget::notice_registration_html(),
            'forget_password_html'   => forget::forget_password_html_default(),
        ];
    }

    /**
     * Отправка тестового письма.
     */
    public static function sendTest(): void
    {
        $email    = $_POST['email'];
        $subject  = $_POST['header'];
        $body     = $_POST['body'];
        $host     = $_POST['host'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $from     = $_POST['from'];
        $port     = $_POST['port'];
        $protocol = $_POST['protocol'];
        $smtpAuth = filter_var($_POST['smtpAuth'], FILTER_VALIDATE_BOOLEAN);

        $mail = new PHPMailer(true);

        try {
            self::configureMailer($mail, $host, $username, $password, $port, $protocol, $smtpAuth);

            $mail->setFrom($from, "Проверка отправки почты");
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = 'Enabled HTML';

            if ($mail->send()) {
                board::response("notice", ["message" => "Письмо отправлено на почту {$email}", "ok" => true]);
            } else {
                board::error("Не удалось отправить письмо на почту {$email}. {$mail->ErrorInfo}");
            }
        } catch (Exception $e) {
            board::error("Не удалось отправить письмо на почту {$email}. {$mail->ErrorInfo}");
        }
    }

    /**
     * Настройка PHPMailer.
     */
    private static function configureMailer(
      PHPMailer $mail,
      string $host,
      string $username,
      string $password,
      int $port,
      string $protocol,
      bool $smtpAuth
    ): void {
        $mail->isSMTP();
        $mail->CharSet     = "UTF-8";
        $mail->SMTPDebug   = 0;
        $mail->Debugoutput = function ($str, $level) {
            $GLOBALS['status'][] = $str;
        };
        $mail->SMTPAuth    = $smtpAuth ?? true;
        $mail->Host        = $host;
        $mail->Username    = $username;
        $mail->Password    = $password;
        $mail->SMTPSecure  = $protocol;
        $mail->Port        = $port;
    }

    /**
     * Отправка письма.
     */
    public static function send(string $email, string $content, string $subject, $isShowError = true): void
    {
        $mail = new PHPMailer(true);

        try {
            $config = config::load()->email();

            if (empty($config->getHost()) || empty($config->getUsername()) || empty($config->getPassword()) || empty(
              $config->getPort()
              ) || empty($config->isSmtpAuth()) || empty($config->getProtocol())) {
                board::error("Не заполнены данные для отправки почты.");

                return;
            }

            self::configureMailer(
              $mail,
              $config->getHost(),
              $config->getUsername(),
              $config->getPassword(),
              $config->getPort(),
              $config->getProtocol(),
              $config->isSmtpAuth()
            );

            $mail->setFrom($config->getEmailFrom(), $_SERVER["SERVER_NAME"]);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content;
            $mail->AltBody = 'Enabled HTML';

            if($isShowError) {
                if ($mail->send()) {
                    board::response("notice", ["message" => "Письмо отправлено на почту {$email}", "ok" => true]);
                } else {
                    board::error("Не удалось отправить письмо на почту {$email}. {$mail->ErrorInfo}");
                }
            }else{
                $mail->send();
            }

        } catch (Exception $e) {
            if($isShowError){
                board::error("Не удалось отправить письмо на почту {$email}. {$mail->ErrorInfo}");
            }
        }
    }

}
