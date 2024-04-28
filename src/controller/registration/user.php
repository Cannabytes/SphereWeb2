<?php

namespace Ofey\Logan22\controller\registration;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\auth\registration;
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

    public static function add(): void
    {
        $email    = request::setting(
          'email',
          new request_config(isEmail: true)
        );
        $password = request::setting('password', new request_config(max: 32));
        config::load()->captcha()->validator();

        if (auth::is_user($email)) {
            board::response(
              "notice",
              [
                "message"       => lang::get_phrase(201, $email),
                "ok"            => false,
                "reloadCaptcha" => true,
              ]
            );
        }
        registration::add($email, $password, false);
    }

}