<?php

namespace Ofey\Logan22\controller\registration;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\comparison;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\model\user\user;

class account
{

    // POST: /registration/account
    public static function requestNewAccount()
    {
        $login = request::setting(
            'login',
            new request_config(min: 4, max: 16, rules: "/^[a-zA-Z0-9_]+$/")
        );
        $prefixEnable = config::load()->registration()->getEnablePrefix();
        $prefixType = config::load()->registration()->getPrefixType();
        if ($prefixEnable) {
            $prefix = $_SESSION['account_prefix'] ?? "";
            $login = $prefixType == "prefix" ? $prefix . $login : $login . $prefix;
            unset($_SESSION['account_prefix']);
        }
        $password = request::setting(
            'password',
            new request_config(min: 4, max: 32, rules: "/^[a-zA-Z0-9_]+$/")
        );
        $password_hide = !isset($_POST['password_hide']) || !filter_var($_POST['password_hide'], FILTER_VALIDATE_BOOLEAN);

        if (server::getServerAll() == null) {
            board::error("Not Server");
        }

        server::checkActiveServers();
        if (\Ofey\Logan22\model\user\user::self()->isAuth()) {
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
                if (config::load()->registration()->getEnableLoadFileRegistration()) {
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

                if (config::load()->notice()->isRegistrationAccount()) {
                    $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_registration_account');
                    $msg = strtr($template, [
                        '{name}' => user::self()->getName(),
                        '{email}' => user::self()->getEmail(),
                        '{login}' => $login,
                        '{server}' => $serverInfo->getName() . " x" . $serverInfo->getRateExp(),
                    ]);
                    telegram::sendTelegramMessage($msg, config::load()->notice()->getRegistrationAccountThreadId());
                }

                \Ofey\Logan22\model\user\user::self()->addLog(logTypes::LOG_REGISTRATION_ACCOUNT, 533, [$login]);
                board::response(
                    "notice_registration",
                    [
                        "ok" => true,
                        "message" => lang::get_phrase(207),
                        "isDownload" => config::load()->registration()->isMassRegistration(),
                        "title" => $_SERVER['SERVER_NAME'] . " - " . $login . ".txt",
                        "content" => $content,
                        "redirect" => fileSys::localdir("/accounts"),
                    ]
                );
            }
        }
    }


}