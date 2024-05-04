<?php

namespace Ofey\Logan22\controller\registration;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\comparison;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\template\tpl;

class account
{

    public static function newAccount($server_id = null)
    {
        if ( ! server::get_server_info()) {
            tpl::addVar("title", lang::get_phrase(131));
            tpl::addVar("message", "Not Server");
            tpl::display("page/error.html");
        }
        tpl::addVar([
          'server_id' => $server_id,
        ]);
        tpl::display("/account/registration.html");
    }

    public static function requestNewAccount()
    {
        $login = request::setting(
          'login',
          new request_config(min: 4, max: 16, rules: "/^[a-zA-Z0-9_]+$/")
        );
        $prefixEnable = config::load()->registration()->getEnablePrefix();
        $prefixType = config::load()->registration()->getPrefixType();
        if ($prefixEnable && isset($_POST['prefix']) && $_POST['prefix'] != "off_prefix" && $_POST['prefix'] != "null") {
            $prefix = $_POST['prefix'];
            $login  = $prefixType == "prefix" ? $prefix . $login : $login . $prefix;
        }
        $password      = request::setting(
          'password',
          new request_config(min: 4, max: 60)
        );
        $password_hide = request::checkbox('password_hide');
        if (\Ofey\Logan22\model\user\user::getUserId()->isAuth()) {
            player_account::add($login, $password, $password_hide);
        } else {
            config::load()
                  ->captcha()
                  ->validator();

            $email = request::setting(
              "email",
              new request_config(isEmail: true)
            );
            if ($serverInfo = player_account::add_account_not_user(
              $login,
              $password,
              $password_hide,
              $email
            )) {
                $content = trim(config::load()->lang()->getPhrase(config::load()->registration()->getPhraseRegistrationDownloadFile())) ?? "";
                if (config::load()->registration()->isMassRegistration()) {
                    $content = str_replace(
                      [
                        "%site_server%",
                        "%server_name%",
                        "%rate_exp%",
                        "%chronicle%",
                        "%email%",
                        "%login%",
                        "%password%",
                      ],
                      [
                        $_SERVER['SERVER_NAME'],
                        $serverInfo->getName(),
                        "x" . $serverInfo->getRateExp(),
                        $serverInfo->getChronicle(),
                        $email,
                        $login,
                        $password,
                      ],
                      $content
                    );
                }
                userlog::add("registration", 533, [$email, $login]);
                board::response(
                  "notice_registration",
                  [
                    "ok"         => true,
                    "message"    => lang::get_phrase(207),
                    "isDownload" => config::load()->registration()->isMassRegistration(),
                    "title"      => $_SERVER['SERVER_NAME'] . " - " . $login . ".txt",
                    "content"    => $content,
                    "redirect"   => fileSys::localdir("/accounts"),
                  ]
                );
            }
        }
    }

    public static function sync_add()
    {
        validation::user_protection();
        comparison::sync();
    }

    public static function sync($server_id = null)
    {
        validation::user_protection();
        tpl::display("account/sync.html");
    }

}