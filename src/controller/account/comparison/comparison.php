<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 18:14:01
 */

namespace Ofey\Logan22\controller\account\comparison;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\user\user;

class comparison
{

    //Синхронизация внутренней БД аккаунтов, реестра аккаунтов АПИ Сферы и реестра аккаунтов игровой базы
    public static function synchronization(): void
    {
        try {
            if (empty($_POST['login']) || empty($_POST['password'])) {
                throw new Exception("Логин и пароль обязательны", 400);
            }
            $login = trim($_POST['login']);
            $password = trim($_POST['password']);

            $loginMinLength = 1;
            $loginMaxLength = 21;
            $passwordMinLength = 4;
            $passwordMaxLength = 32;

            if (mb_strlen($login) < $loginMinLength || mb_strlen($login) > $loginMaxLength) {
                throw new Exception(lang::get_phrase('login_max_min_sim', $loginMinLength, $loginMaxLength), 400);
            }

            if (mb_strlen($password) < $passwordMinLength || mb_strlen($password) > $passwordMaxLength) {
                throw new Exception(lang::get_phrase('password_max_min_sim', $passwordMinLength, $passwordMaxLength), 400);
            }

            $response = server::send(type::SYNCHRONIZATION, [
                'email' => user::self()->getEmail(),
                'login' => $login,
                'password' => $password,
            ])->show(false)->getResponse();
            if (isset($response['success'])) {
                if ($response['success']) {
                    user::self()->getLoadAccounts(true);

                    if (\Ofey\Logan22\controller\config\config::load()->notice()->isSyncAccount()) {
                        $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_sync_account');
                        $msg = strtr($template, [
                            '{email}' => user::self()->getEmail(),
                            '{login}' => $login,
                        ]);
                        telegram::sendTelegramMessage($msg);
                    }

                    board::success(lang::get_phrase('Account added'));
                } else {
                    throw new Exception($response['message'], 400);
                }
            } else {
                //error type
                throw match ($response['error']) {
                    2 => new Exception(lang::get_phrase(164), 400),
                    3 => new Exception(lang::get_phrase('You already have this account'), 400),
                    4 => new Exception(lang::get_phrase('This account is linked to another profile'), 400),
                    5 => new Exception(lang::get_phrase('An unexpected error occurred'), 400),
                    6 => new Exception(lang::get_phrase(166), 400),
                    default => new Exception(lang::get_phrase('Unknown error'), 400),
                };
            }
        } catch (Exception $e) {
            if (\Ofey\Logan22\controller\config\config::load()->notice()->isSyncAccount()) {
                $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_sync_account_error');
                $msg = strtr($template, [
                    '{email}' => user::self()->getEmail(),
                    '{login}' => $login,
                    '{error}' => $e->getMessage(),
                ]);
                telegram::sendTelegramMessage($msg);
            }

            board::error($e->getMessage());
        }
    }

}