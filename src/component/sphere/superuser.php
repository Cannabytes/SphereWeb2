<?php

namespace Ofey\Logan22\component\sphere;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\model\user\auth\registration;
use Ofey\Logan22\model\user\user;

class superuser
{

    static public function create(): void
    {
        $server = server::send(type::CREATE_SUPER_USER_EMAIL_CHECK, [
          'email'    => user::self()->getEmail(),
          'password' => $_SESSION['password'],
        ])->show()->getResponse();
        if ($server) {
            if (isset($server['success'])) {
               if ($server['success']) {
                board::success("На Ваш E-Mail отправлено письмо с подтверждением.");
               }
            }
        }
    }

    static public function checkHashEmail($hash = ""): void
    {
        if ($hash == "") {
            redirect::location("/main");
        }
        if (mb_strlen($hash) != 32) {
            redirect::location("/main");
        }
        $server = server::send(type::CHECK_SUPER_USER_EMAIL_CONFIRM, [
          'hash' => $hash,
        ])->show(false)->getResponse();
        if($server['success']){
            $code = $server['code'];
            session::add("super-user", $code);
        }
        redirect::location("/main");
    }

    //Функция отправки файла на проверку супер юзера
    public static function auth()
    {
        $data = file_get_contents('php://input');
        if ( ! $data) {
            board::error("Не удалось прочитать данные");
        }
        $data = explode('=', $data);
        if (count($data) != 2) {
            board::error("Не удалось разобрать данные");
        }
        $auth = server::send(type::AUTH_SUPER_USER, [
          'data' => $data[1],
        ])->show(true)->getResponse();
        if ($auth['success']) {
            $userdata = $auth['data'];
            $user     = user::getUserByEmail($userdata['email']);
            if ($user == null) {
                //Добавляем пользователя
                registration::add($userdata['email'], $userdata['password']);
            } else {
                if (password_verify($userdata['password'], $user->getPassword())) {
                    session::add('id', $user->getId());
                    session::add('email', $user->getEmail());
                    session::add('password', $userdata['password']);
                } else {
                    board::response("notice", ["message" => lang::get_phrase(166), "ok" => false, "reloadCaptcha" => true]);
                }
            }
            board::response("notice", ["message" => lang::get_phrase(165), "ok" => true, "redirect" => ("/main")]);
        }
    }

}