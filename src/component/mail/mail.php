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
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class mail {

    public static function send(string $email, string $content, string $subject) {

        $mail = new PHPMailer(true);
        try {
            if (empty(config::load()->email()->getHost()) or empty(config::load()->email()->getUsername()) or empty(config::load()->email()->getPassword()) or empty(config::load()->email()->getPort()) or empty(config::load()->email()->isSmtpAuth()) or empty(config::load()->email()->getProtocol())) {
                board::error("Не заполнены данные для отправки почты.");
            }
            $mail->isSMTP();

            $mail->CharSet = "UTF-8";
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = function ($str, $level) {
                $GLOBALS['status'][] = $str;
            };

            $mail->SMTPAuth = config::load()->email()->isSmtpAuth() ?? true;
            $mail->Host = config::load()->email()->getHost(); // SMTP сервера вашей почты
            $mail->Username = config::load()->email()->getUsername();
            $mail->Password = config::load()->email()->getPassword();
            $mail->SMTPSecure = config::load()->email()->getProtocol();
            $mail->Port = config::load()->email()->getPort();

            $mail->setFrom(config::load()->email()->getUsername(), lang::get_phrase(67));
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;
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