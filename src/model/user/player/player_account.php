<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 23.08.2022 / 23:20:45
 */

namespace Ofey\Logan22\model\user\player;

use Exception as ExceptionAlias;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\captcha\captcha;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\encrypt\encrypt;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\server\serverModel;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\auth\registration;
use Ofey\Logan22\model\user\user;

class player_account
{

    //Запрещает или разрешает просмотр выбранного (своего персонажа) другими пользователями
    private static array $characters = [];

    //Имеется ли на персонаже предмет N

    /**
     * @throws ExceptionAlias
     */
    public static function add_account_not_user($login, $password, $password_hide, $email)
    {
        $server_id = user::getUserId()->getServerId();
        self::valid_login($login);
        self::valid_password($password);
        self::valid_email($email);

        if (auth::is_user($email)) {
            board::response("notice", ["message" => lang::get_phrase(201), "ok" => false, "reloadCaptcha" => true]);
        }
        registration::add($email, $password);
        $server = server::getServer($server_id);
        if ($server->getRestApiEnable()) {
            $err = self::account_registration($server_id, [
              $login,
              $password,
              $email,
            ]);
        } else {
            $reQuest = self::getReQuest($server_id, $login);
            $err     = self::account_registration($reQuest, [
              $login,
              encrypt::server_password($password, $reQuest),
              $email,
            ]);
        }
        //TODO: логирование ошибок
        if (is_array($err)) {
            if ( ! $err['ok']) {
                board::response("notice", ["message" => $err['message'], "ok" => false, "reloadCaptcha" => true]);
            }
        }
        self::add_inside_account($login, $password, $email, $_SERVER['REMOTE_ADDR'], $server_id, $password_hide);

        return $reQuest;
    }

    public static function valid_login($login)
    {
        if (3 > mb_strlen($login)) {
            board::response("notice", ["message" => lang::get_phrase(208), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false]);
        }
        if (16 < mb_strlen($login)) {
            board::response("notice", ["message" => lang::get_phrase(209), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false]);
        }
        if ( ! preg_match("/^[a-zA-Z0-9_]+$/", $login) == 1) {
            board::response("notice", ["message" => lang::get_phrase(210), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false]);
        }
    }

    public static function valid_password($password)
    {
        if (4 > mb_strlen($password)) {
            board::notice(false, lang::get_phrase(211));
        }
        if (32 < mb_strlen($password)) {
            board::notice(false, lang::get_phrase(212));
        }
    }

    public static function valid_email($email)
    {
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            board::notice(false, lang::get_phrase(213));
        }
    }

    /**
     * TODO: На будущее переделать, сначала проверить что N аккаунт пуст во внутренне БД и в БД сервера,
     * и только после этого производить регистрацию.
     *
     * @param $login
     * @param $password
     * @param $password_hide
     *
     * @throws ExceptionAlias
     */
    public static function add($login, $password, $password_hide)
    {

        //Проверка префикса
        $prefixEnable = config::load()->registration()->getEnablePrefix();
        if ($prefixEnable) {
            $prefixType = config::load()->registration()->getPrefixType();
            $prefix = $_SESSION['account_prefix'] ?? "";
            $login  = $prefixType == "prefix" ? $prefix . $login : $login . $prefix;
        }

        if (config::load()->registration()->isMassRegistration()) {
            self::add_mass_players($login, $password, $password_hide);
        } else {
            self::add_one_player($login, $password, $password_hide);
        }
    }

    /**
     * @throws ExceptionAlias
     */
    public static function add_mass_players($login, $password, $password_hide)
    {
        self::valid_login($login);
        self::valid_password($password);
        if (self::count_account() >= config::load()->other()->getMaxAccount()) {
            board::response("notice", ["message" => lang::get_phrase(206), "ok" => false, "reloadCaptcha" => true]);
        }
        $sphere = \Ofey\Logan22\component\sphere\server::send(type::REGISTRATION, [
          'login'            => $login,
          'password'         => $password,
          'is_password_hide' => $password_hide,
        ])->show(false)->getResponse();
        if (isset($sphere['error'])) {
            if (isset($sphere['errorCode']) and $sphere['errorCode'] === 0) {
                board::error("Аккаунт занят");
            }
            board::error($sphere['error']);
        }

        if (isset($sphere['success']) and $sphere['success'] === true) {
            self::add_inside_account(
              $login,
              $password,
              user::getUserId()->getEmail(),
              user::getUserId()->getIp(),
              user::getUserId()->getServerId(),
              $password_hide
            );
            $content = trim(config::load()->lang()->getPhrase(config::load()->registration()->getPhraseRegistrationDownloadFile())) ?? "";
            $serversName = "";
            if (config::load()->registration()->isMassRegistration()) {
                $content = str_replace(["%site_server%", "%server_name%", "%rate_exp%", "%chronicle%", "%email%", "%login%", "%password%"],
                  [
                    $_SERVER['SERVER_NAME'],
                    server::getLastServer()->getName(),
                    "x" . server::getLastServer()->getRateExp(),
                    server::getLastServer()->getChronicle(),
                    user::getUserId()->getEmail(),
                    $login,
                    $password,
                  ],
                  $content);
                $serversName .= " " . server::getLastServer()->getName() . " x" . server::getLastServer()->getRateExp();
            }

            if (config::load()->notice()->isRegistrationAccount()) {
                $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_registration_account');
                $msg = strtr($template, [
                    '{name}' => user::self()->getName(),
                    '{email}' => user::self()->getEmail(),
                    '{login}' => $login,
                    '{server}' => $serversName,
                ]);
                telegram::sendTelegramMessage($msg, config::load()->notice()->getRegistrationAccountThreadId());
            }

            user::self()->addLog(logTypes::LOG_REGISTRATION_ACCOUNT, 532, [$login]);
            board::response(
              "notice_registration",
              [
                "ok"         => true,
                "message"    => lang::get_phrase(207),
                "isDownload" => config::load()->registration()->getEnableLoadFileRegistration(),
                "title"      => $_SERVER['SERVER_NAME'] . " - " . $login . ".txt",
                "content"    => trim($content),
                "prefix" => config::load()->registration()->genPrefix(),
              ]
            );
        }
    }

    static function count_account()
    {
        if ( ! auth::get_is_auth()) {
            return;
        }

        return sql::run("SELECT COUNT(*) as `count` FROM player_accounts WHERE email = ?", [
          auth::get_email(),
        ])->fetch()["count"];
    }

    /**
     * @throws ExceptionAlias
     */
    public static function add_inside_account($login, $password, $email, $ip, $server_id, $password_hide)
    {
        if ($password_hide) {
            $password = "";
        }
        return sql::run(
          "INSERT INTO `player_accounts` (`login`, `password`, `email`, `ip`, `server_id`, `password_hide`, `date_create`, `date_update`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
          [
            $login,
            $password,
            $email,
            $ip,
            $server_id,
            (int)$password_hide,
            time::mysql(),
            time::mysql(),
          ]
        );
    }

    public static function add_one_player($login, $password, $password_hide = true)
    {
        self::valid_login($login);
        self::valid_password($password);
        if (self::count_account() >= config::load()->other()->getMaxAccount()) {
            board::response("notice", ["message" => lang::get_phrase(206), "ok" => false, "reloadCaptcha" => true]);
        }

        $sphere = \Ofey\Logan22\component\sphere\server::send(type::REGISTRATION, [
          'login'            => $login,
          'password'         => $password,
          'is_password_hide' => $password_hide,
        ])->show()->getResponse();
        $content = trim(config::load()->lang()->getPhrase(config::load()->registration()->getPhraseRegistrationDownloadFile())) ?? "";
        $server  = server::getServer(user::self()->getServerId());
        $content = str_replace(["%site_server%", "%server_name%", "%rate_exp%", "%chronicle%", "%email%", "%login%", "%password%"],
          [
            $_SERVER['SERVER_NAME'],
            server::getLastServer()->getName(),
            "x" . $server->getRateExp(),
            $server->getChronicle(),
            user::getUserId()->getEmail(),
            $login,
            $password,
          ],
          $content);

        if (config::load()->notice()->isRegistrationAccount()) {
            $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_registration_account');
            $msg = strtr($template, [
                '{name}' => user::self()->getName(),
                '{email}' => user::self()->getEmail(),
                '{login}' => $login,
                '{server}' => $server->getName() . " x" . $server->getRateExp(),
            ]);
            telegram::sendTelegramMessage($msg, config::load()->notice()->getRegistrationAccountThreadId());
        }

        user::self()->addLog(logTypes::LOG_REGISTRATION_ACCOUNT, 532, [$login]);
        board::response(
          "notice_registration",
          [
            "ok"         => true,
            "message"    => lang::get_phrase(207),
            "isDownload" => true,
            "title"      => $_SERVER['SERVER_NAME'] . " - " . $login . ".txt",
            "content"    => trim($content),
          ]
        );
    }

    public static function exist_account_inside($login, $server_id)
    {
        return sql::run("SELECT id, password_hide FROM player_accounts WHERE login = ? AND server_id = ?", [
          $login,
          $server_id,
        ])->fetch();
    }

    public static function show_all_account_player($email = null, $server_id = null)
    {
        if ($email === null) {
            if ( ! auth::get_is_auth()) {
                return;
            }
            $email = auth::get_email();
        }

        if ($server_id === null) {
            return sql::getRows(
              "SELECT id, login, `password`, email, ip, server_id, password_hide, date_create, date_update FROM player_accounts WHERE email = ? ORDER BY date_create",
              [
                $email,
              ]
            );
        }

        if (is_int((int)$server_id)) {
            return sql::getRows(
              "SELECT id, login, `password`, email, ip, server_id, password_hide, date_create, date_update FROM player_accounts WHERE email = ? AND server_id = ? ORDER BY date_create",
              [
                $email,
                $server_id,
              ]
            );
        }

        return sql::getRows(
          "SELECT id, login, `password`, email, ip, server_id, password_hide, date_create, date_update FROM player_accounts WHERE email = ? AND server_id = ? ORDER BY date_create",
          [
            $email,
            auth::get_default_server(),
          ]
        );
    }

    //Возвращаем список всех аккаунтов пользователя
    //$default_server - вернуть данные своих аккаунтов только сервера который по умолчанию

    public static function addItem($server_id, $item_id, $item_count, $item_enchant, $char_name = null)
    {
        if ($char_name == null) {
            board::error(lang::get_phrase('no nickname'));
        }
        $server_info = server::isServer($server_id);
        if ( ! $server_info) {
            board::error(lang::get_phrase(150));
        }

        $player_info = self::get_memory_character($char_name, $server_info);
        if ( ! $player_info) {
            board::error(lang::get_phrase(151, $char_name));
        }
        $player_id = $player_info["player_id"];
        $is_stack  = client_icon::is_stack($item_id);

        if ($server_info->getCollectionSqlBaseName()::need_logout_player_for_item_add()) {
            if ($player_info["online"]) {
                board::error(lang::get_phrase(153, $char_name));
            }
            if ($is_stack) {
                $checkPlayerItem = player_account::check_item_player($server_info, [
                  $item_id,
                  $player_id,
                ]);
                $checkPlayerItem = $checkPlayerItem->fetch();
                if ($checkPlayerItem) {
                    player_account::update_item_count_player($server_info, [
                      ($checkPlayerItem['count'] + $item_count),
                      $checkPlayerItem['object_id'],
                    ]);
                } else {
                    donate::add_item_max_val_id($server_info, $player_id, $item_id, $item_count);
                }
            } else {
                donate::add_item_max_val_id($server_info, $player_id, $item_id, $item_count);
            }
        } else {
            $prepare = [
              $player_id,
              $item_id,
              $item_count,
              $item_enchant,
            ];
            $ok      = self::extracted('add_item', $server_info, $prepare);
            if ( ! $ok) {
                board::notice(false, lang::get_phrase(lang::get_phrase('sending failed')));
            }
        }
    }

    //Кол-во имеющихся аккаунтов

    private static function get_memory_character($char_name, $server_info)
    {
        if (isset(self::$characters[$char_name])) {
            return self::$characters[$char_name];
        }
        $player_info = player_account::is_player($server_info, [$char_name]);
        $player_info = $player_info->fetch();
        if ( ! $player_info) {
            board::error(lang::get_phrase('character not found'));
        }
        $user = player_account::get_show_characters_info($player_info['login']);
        if ($user == null or $user["email"] != user::getUserId()->getEmail()) {
            board::notice(lang::get_phrase(490));
        }
        self::$characters[$char_name] = $player_info;

        return $player_info;
    }

    /**
     * @param      $account_name название аккаунта
     *
     * @return mixed
     * @throws ExceptionAlias
     */
    public static function get_show_characters_info($account_name)
    {
        $info = sql::run("SELECT `email`, `server_id` FROM player_accounts WHERE login = ?", [$account_name])->fetch();
        if ($info == null) {
            return null;
        }

        return $info;
    }

    public static function check_item_player($info, $prepare = [])
    {
        return self::extracted('check_item_player', $info, $prepare);
    }

    public static function update_item_count_player($info, $prepare = [])
    {
        return self::extracted('update_item_count_player', $info, $prepare);
    }

    /**
     * @param          $rest_api_enable
     * @param   mixed  $server_id
     * @param          $login
     * @param          $password
     * @param          $password_hide
     *
     * @return mixed|null
     * @throws ExceptionAlias
     */
    private static function getQuest($rest_api_enable, mixed $server_id, $login, $password, $password_hide): mixed
    {
        if ($rest_api_enable) {
            $err = self::account_registration($server_id, [
              $login,
              $password,
              user::getUserId()->getEmail(),
            ]);
        } else {
            sdb::setShowErrorPage(false);
            $reQuest = self::getReQuest($server_id, $login);
            $err     = self::account_registration($reQuest, [
              $login,
              encrypt::server_password($password, $reQuest),
              user::getUserId()->getEmail(),
            ]);
        }
        if (is_array($err)) {
            if ( ! $err['ok']) {
                board::notice(false, $err['message']);
            }
        }

        return $reQuest;
    }

}