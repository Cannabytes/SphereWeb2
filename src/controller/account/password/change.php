<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 16:47:23
 */

namespace Ofey\Logan22\controller\account\password;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class change
{

    public static function password(): void
    {
        validation::user_protection();
        $login         = $_POST['login'] ?? board::error('Login not received');
        $password_hide = false;
        if(!isset($_POST['password_hide'])){
            $password_hide = true;
        }
        $password = request::setting(
            'password',
            new request_config(min: 4, max: 32, rules: "/^[a-zA-Z0-9_]+$/")
        );

        $accountFind = false;
        //Проверяем существование аккаунта
        foreach (user::self()->getAccounts() AS $account){
            if($account->getAccount()==$login){
                $accountFind = true;
                break;
            }
        }
        if(!$accountFind){
            board::error(lang::get_phrase(164));
        }

        $response      = \Ofey\Logan22\component\sphere\server::send(type::ACCOUNT_PLAYER_CHANGE_PASSWORD, [
          'login'         => $login,
          'password'      => $password,
          'password_hide' => $password_hide,
        ])->show()->getResponse();
        if (isset($response['success']) && $response['success'] === true) {

            if (config::load()->notice()->isChangeAccountPassword()) {
                $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_change_account_password');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                    '{login}' => $login,
                ]);
                telegram::sendTelegramMessage($msg, config::load()->notice()->getChangeAccountPasswordThreadId());
            }

            user::self()->addLog(logTypes::LOG_CHANGE_ACCOUNT_PASSWORD, "LOG_CHANGE_ACCOUNT_PASSWORD", [$login]);
            board::success(lang::get_phrase(181));
        }
    }

    static public function show($login, $server_id): void
    {
        validation::user_protection();
        $exist_account_inside = player_account::exist_account_inside($login, $server_id);
        if ( ! $exist_account_inside) {
            redirect::location("/main");
            die();
        }
        tpl::addVar([
          'login'         => $login,
          'server_id'     => $server_id,
          'password_hide' => $exist_account_inside['password_hide'],
        ]);
        tpl::display("account/change_password.html");
    }

}