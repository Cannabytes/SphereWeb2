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
            'notice_success_registration_html' => forget::notice_success_registration_html(),
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
        $port     = $_POST['port'];
        $protocol = $_POST['protocol'];
        $smtpAuth = filter_var($_POST['smtpAuth'], FILTER_VALIDATE_BOOLEAN);

        $mail = new PHPMailer(true);

        try {
            self::configureMailer($mail, $host, $username, $password, $port, $protocol, $smtpAuth);

            $mail->setFrom($username, "Проверка отправки почты");
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
    public static function send(string $email, string $content, string $subject): void
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

            $mail->setFrom($config->getUsername(), $_SERVER["SERVER_NAME"]);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content;
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

}
