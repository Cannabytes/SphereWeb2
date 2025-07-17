<?php

namespace Ofey\Logan22\controller\registration;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\finger\finger;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\mail\mail;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\auth\registration;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\template\tpl;

class user
{

    /**
     * Обработка регистрации нового пользователя
     *
     * @return void
     * @throws \Exception
     */
    public static function add(): void
    {
        if (\Ofey\Logan22\model\user\user::self()->isAuth()) {
            board::error(lang::get_phrase("error"));
        }
        $email = $_POST['email'] ?? null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::notice(false, lang::get_phrase(213));
        }
        $password = request::setting('password', new request_config(min: 4, max: 32, rules: "/^[a-zA-Z0-9_]+$/"));
        $account_name = isset($_POST['account']) && trim($_POST['account']) !== '' ? trim($_POST['account']) : null;


        if ($account_name != null) {
            player_account::valid_password($password);
        }

        $minOfChars = config::load()->registration()->getMinimumNumberOfCharactersRegistrationAccount();
        $prefixEnable = config::load()->registration()->getEnablePrefix();
        if ($prefixEnable) {
            $minOfChars += mb_strlen($_SESSION['account_prefix']);
        }

        if($account_name){
            $account_name = request::setting(
                'account',
                new request_config(
                    min: $minOfChars,
                    max: config::load()->registration()->getMaximumNumberOfCharactersRegistrationAccount(),
                    rules: "/^[a-zA-Z0-9_]+$/"
                )
            );
        }

        config::load()->captcha()->validator();
        if (auth::is_user($email)) {
            board::response("notice", ["message" => lang::get_phrase(201, $email), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false,]);
        }

        $account_name = registration::add($email, $password, $account_name);
        if (session::get("HTTP_REFERER")) {
            sql::run('INSERT INTO `user_variables` (`server_id`, `user_id`, `var`, `val`) VALUES (?, ?, ?, ?)', [0, $_SESSION['id'], "HTTP_REFERER", session::get("HTTP_REFERER"),]);
        }

        \Ofey\Logan22\model\user\user::self()->setId($_SESSION['id']);
        \Ofey\Logan22\model\user\user::self()->addLog(logTypes::LOG_REGISTRATION_USER, "LOG_REGISTRATION_USER", [$email]);

        $requestFinger = $_POST['finger'] ?? "";
        $finger = finger::createFingerHash($requestFinger);

        auth::addAuthLog($_SESSION['id'], $finger);

        $mailTemplate = mail::getTemplates();

        if (filter_var($mailTemplate['send_notice_for_registration'], FILTER_VALIDATE_BOOLEAN)) {
            $config = config::load()->email();
            if (!empty($config->getHost()) || !empty($config->getUsername()) || !empty($config->getPassword()) || !empty($config->getPort()) || !empty($config->isSmtpAuth()) || !empty($config->getProtocol())) {
                $html = str_replace(["\n", "\t"], "", $mailTemplate['notice_success_registration_html']);
                $html = str_replace(['%site%',], [url::scheme() . "://" . $_SERVER['SERVER_NAME'],], $html);
                mail::send($email, $html, $mailTemplate['notice_reg_subject'], false);
            }
        }

        $content = trim(config::load()->lang()->getPhrase(config::load()->registration()->getPhraseRegistrationDownloadFile())) ?? "";

        if (config::load()->notice()->isRegistrationUser()) {
            $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_registration_user');
            $msg = strtr($template, ['{email}' => $email,]);
            if (session::get("HTTP_REFERER")) {
                $msg .= "<br>Referrer: " . session::get("HTTP_REFERER");
            }
            telegram::sendTelegramMessage($msg, config::load()->notice()->getRegistrationUserThreadId());
        }


        //Выдаем бонусы при регистрации
        foreach (server::getServerAll() as $server) {
            if ($server->bonus()->isRegistrationBonus()) {
                $items = $server->bonus()->getRegistrationBonusItems();
                $ifIssueAllItems = $server->bonus()->isIssueAllItems();
                 // Если выдаем все предметы
                if($ifIssueAllItems){
                    foreach ($items as $item) {
                        \Ofey\Logan22\model\user\user::self()->addToWarehouse($server->getId(), $item->getId(), $item->getCount(), $item->getEnchant(), 'registration_bonus');
                    }
                }else{
                    // выбираем рандомный предмет
                    $item = $items[array_rand($items)];
                    \Ofey\Logan22\model\user\user::self()->addToWarehouse($server->getId(), $item->getId(), $item->getCount(), $item->getEnchant(), 'registration_bonus');
                }
            }
        }

        if (config::load()->registration()->getEnableLoadFileRegistration() and $account_name != null) {
            $serverInfo = server::getDefault();
            if($serverInfo!=null){
                $content = str_replace(["%site_server%", "%server_name%", "%rate_exp%", "%chronicle%", "%email%", "%login%", "%password%",], [$_SERVER['SERVER_NAME'], $serverInfo->getName(), "x" . $serverInfo->getRateExp(), $serverInfo->getChronicle(), $email, $account_name, $password,], $content);
            }
            board::response("notice_registration", ["ok" => true, "message" => lang::get_phrase(207), "isDownload" => config::load()->registration()->getEnableLoadFileRegistration(), "title" => $_SERVER['SERVER_NAME'] . " - " . $email . ".txt", "content" => $content, "redirect" => fileSys::localdir("/main"),]);
        }


        board::response("notice_registration", ["ok" => true, "message" => lang::get_phrase(207), "redirect" => fileSys::localdir("/main"),]);
    }

    public static function show($ref_name = null): void
    {
        validation::user_protection("guest");
        tpl::addVar(['referral_name' => $ref_name,]);
        tpl::display("sign-up.html");
    }

}