<?php

namespace Ofey\Logan22\controller\registration;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\auth\registration;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\template\tpl;

class user
{

    public static function show($ref_name = null): void
    {
        validation::user_protection("guest");
        tpl::addVar([
          'referral_name' => $ref_name,
        ]);
        //        tpl::display("/userModel/registration/registration.html");
        tpl::display("sign-up.html");
    }

    /**
     * Обработка регистрации нового пользователя
     * @return void
     * @throws \Exception
     */
    public static function add(): void
    {
        if (\Ofey\Logan22\model\user\user::self()->isAuth()) {
            board::error(lang::get_phrase("error"));
        }

        $email    = $_POST['email'] ?? null;
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::notice(false, lang::get_phrase(213));
        }

        $password = request::setting('password', new request_config(max: 32));
        $account_name = $_POST['account'] ?? null;
        if($account_name != null) {
            player_account::valid_login($account_name);
            player_account::valid_password($password);
         }

        config::load()->captcha()->validator();

        if (auth::is_user($email)) {
            board::response(
              "notice",
              [
                "message"       => lang::get_phrase(201, $email),
                "ok"            => false,
                "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false,
              ]
            );
        }

        \Ofey\Logan22\model\user\user::self()->addLog(logTypes::LOG_REGISTRATION_USER, "LOG_REGISTRATION_USER", [$email]);

        registration::add($email, $password, $account_name);
        if(server::get_count_servers() > 0 ) {
            board::redirect("/main");
            $user = \Ofey\Logan22\model\user\user::getUserId($_SESSION['id']);
            \Ofey\Logan22\component\sphere\server::setUser($user);
            player_account::add($account_name, $password, true);
        }

        board::response("notice", [
          "ok" => true,
          "message" => lang::get_phrase(177),
          "redirect" => fileSys::localdir("/main"),
        ]);

    }

}