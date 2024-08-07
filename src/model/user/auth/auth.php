<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:29:35
 */

namespace Ofey\Logan22\model\user\auth;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\referral\referral;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\player\character;
use Ofey\Logan22\route\Route;
use Ofey\Logan22\template\tpl;

class auth
{

    //Записываем сюда юзеров, которых ранее искали
    public static array $userInfo = [];

    public static array $user_variables = [];

    private static array $usersMemArray = [];

    private static bool $is_auth = false;

    private static int $id;

    private static string $email;

    private static string $name;

    private static string $password;

    private static string $signature;

    private static string $ip_registration;

    private static string $ip;

    private static string $date_create;

    private static string $date_update;

    private static string $access_level = 'guest';

    private static float $donate_point = 0;

    private static string $avatar;

    //ban func

    private static string $avatar_background;

    private static string $timezone;

    private static ?bool $ban_page = false;

    //bonus

    private static ?bool $ban_ticket = false;

    private static ?bool $ban_gallery = false;

    private static ?array $bonus = [];

    public static function get_user_variables($var, $default = null): mixed
    {
        return self::$user_variables[$var] ?? ($default ?? false);
    }

    public static function show_all_variables(): array
    {
        return self::$user_variables;
    }

    public static function get_account_players(): ?array
    {
        return character::get_account_players(true);
    }

    public static function get_email(): string
    {
        return self::$email;
    }

    public static function set_email($email)
    {
        self::$email = $email;
    }

    public static function get_name(): string
    {
        return self::$name;
    }

    public static function set_name($name = '')
    {
        self::$name = $name;
    }

    public static function get_signature(): string
    {
        return self::$signature;
    }

    public static function set_signature($signature = "")
    {
        self::$signature = $signature ?? "";
    }

    public static function get_date_create(): string
    {
        return self::$date_create;
    }

    public static function set_date_create($date_create = '')
    {
        self::$date_create = $date_create;
    }

    public static function get_date_update(): string
    {
        return self::$date_update;
    }

    public static function set_date_update($date_update): void
    {
        self::$date_update = $date_update;
    }

    public static function get_access_level(): string
    {
        return self::$access_level;
    }

    public static function set_access_level($access_level): void
    {
        self::$access_level = $access_level;
    }

    public static function get_donate_point(): float
    {
        return self::$donate_point;
    }

    public static function set_donate_point($donate_point): void
    {
        self::$donate_point = $donate_point;
    }

    public static function get_avatar(): string
    {
        return self::$avatar ?? "none.jpeg";
    }

    public static function set_avatar($avatar = null): void
    {
        if ($avatar == null) {
            self::$avatar = 'none.jpeg';

            return;
        }
        self::$avatar = fileSys::localdir("/uploads/avatar/" . $avatar);
    }

    public static function get_avatar_background(): string
    {
        return self::$avatar_background;
    }

    public static function set_avatar_background($avatar): void
    {
        self::$avatar_background = $avatar;
    }

    /**
     * Применить пароль к текущей сессии
     */
    public static function apply_password()
    {
        session::edit("password", self::get_password());
    }

    public static function get_password(): string
    {
        return self::$password;
    }

    public static function set_password($password)
    {
        self::$password = $password;
    }

    public static function exist_user($email, $nCheck = true)
    {
        if (self::$userInfo != null) {
            return self::$userInfo;
        }
        $sql      = 'SELECT users.*,  users_permission.* FROM users LEFT JOIN users_permission ON users.id = users_permission.user_id WHERE email = ?;';
        $userInfo = sql::run($sql, [$email])->fetch();
        if ( ! $nCheck) {
            return false;
        }
        if ( ! $userInfo) {
            return false;
        }

        return self::$userInfo = $userInfo;
    }

    public static function set_ip_registration($ip_registration)
    {
        self::$ip_registration = $ip_registration ?? "0.0.0.0";
    }

    public static function set_ip($ip)
    {
        self::$ip = $ip;
    }

    private static function set_ban_page(?bool $ban_page): void
    {
        self::$ban_page = (bool)$ban_page;
    }

    private static function set_ban_ticket(?bool $ban_ticket): void
    {
        self::$ban_ticket = (bool)$ban_ticket;
    }

    private static function set_ban_gallery(?bool $ban_gallery): void
    {
        self::$ban_gallery = (bool)$ban_gallery;
    }

    private static function set_user_variables()
    {
        if ( ! self::get_is_auth()) {
            foreach (session::get_guest_var() as $n => $v) {
                self::$user_variables[$n] = [
                  'var' => $n,
                  'val' => $v ?? "",
                ];
            }

            return;
        }

        $vars = sql::getRows("SELECT * FROM `user_variables` WHERE `user_id` = ? AND (`server_id` IS NULL OR `server_id` = ?)", [
          self::get_id(),
          self::get_default_server(),
        ]);
        if ( ! $vars) {
            return;
        }

        foreach ($vars as $var) {
            self::$user_variables[$var['var']] = $var;
        }
    }

    public static function get_is_auth(): bool
    {
        return self::$is_auth;
    }

    public static function set_is_auth($boolean)
    {
        self::$is_auth = $boolean;
    }

    //TODO:Добавить в массив всех пользователей которых мы проверяем

    public static function get_id(): ?string
    {
        return self::$id ?? null;
    }

    public static function set_id(
      $user_id
    ) {
        self::$id = $user_id;
    }

    //Проверка существования пользователя по его никнейму

    /**
     * @return false|mixed|void|null
     * @throws Exception
     *
     * Сервер по умолчанию (если нет, то последний)
     * иначе false
     */
    public static function get_default_server()
    {
        $server_id       = session::get('default_server');
        $get_server_info = server::getServer();
        /*
         * Если нет никакого сервера, ставим последний сервер
         * Однако...если сервера больше больше чем 2, тогда последний сервер проверяем на дату запуска, если она
         * прошла, тогда выставляем последний, иначе предпоследний.
         */ //TODO: Потом сделать в настройках - сервер по умолчанию (для новых пользователей без выбранного сервера и
        //для тех у кого есть сервер, который не актуален/удален/выключен).
        if ($server_id) {
            foreach ($get_server_info as $row) {
                if ($row['id'] == $server_id) {
                    return $server_id;
                }
            }
        }

        //Если нет вообще серверов...
        if ( ! $get_server_info) {
            return false;
        }
        //Дадим пользователю сервер по умолчанию
        if ( ! array_search($server_id, array_column($get_server_info, 'id'))) {
            $get_server_info = end($get_server_info);
            session::add('default_server', $get_server_info['id']);

            return $get_server_info['id'];
        }

        return $server_id;
    }

    public static function get_user_info(
      $user_id
    ) {
        if ($userMem = self::isUserInfoMemory($user_id)) {
            return $userMem;
        }
        $sql                   = 'SELECT users.*,  users_permission.* FROM users LEFT JOIN users_permission ON users.id = users_permission.user_id WHERE id = ?;';
        $userInfo              = sql::run($sql, [$user_id])->fetch();
        self::$usersMemArray[] = $userInfo;

        return $userInfo;
    }

    //Проверка существования юзера

    private static function isUserInfoMemory(
      $user_id
    ): mixed {
        foreach (self::$usersMemArray as $user) {
            if ($user['id'] == $user_id) {
                return $user;
            }
        }

        return false;
    }

    public static function exist_user_nickname(
      $nickname,
      $nCheck = true
    ) {
        return sql::run('SELECT * FROM `users` WHERE `name` = ?;', [$nickname])->fetch();
    }

    /**
     * @param $email
     *
     * @return array|mixed
     * @throws Exception
     * Проверка существования пользователя по E-Mail
     */
    public static function is_user(
      $email
    ) {
        return sql::run('SELECT 1 FROM `users` WHERE `email` = ?;', [$email])->fetch();
    }

    public static function user_enter(): void
    {
        if (\Ofey\Logan22\model\user\user::getUserId()->isAuth()) {
            board::notice(false, lang::get_phrase(160));
        }
        if ( ! isset($_POST['email']) or ! isset($_POST['password'])) {
            board::notice(false, lang::get_phrase(161));
        }
        $email    = request::setting('email', new request_config(isEmail: true));
        $password = request::setting('password', new request_config(max: 32));

        config::load()->captcha()->validator();

        $user_info = self::exist_user($email);
        if ( ! $user_info) {
            board::notice(false, lang::get_phrase(164));
        }
        if (password_verify($password, $user_info['password'])) {
            session::add('id', $user_info['id']);
            session::add('email', $email);
            session::add('password', $password);
            board::response("notice", ["message" => lang::get_phrase(165), "ok" => true, "redirect" => fileSys::localdir("/main")]);
        }
        board::response(
          "notice",
          ["message" => lang::get_phrase(166), "ok" => false, "reloadCaptcha" => config::load()->captcha()->isGoogleCaptcha() == false]
        );
    }

    public static function log_add_user_auth()
    {
        $user = \Ofey\Logan22\model\user\user::getUserId();
        $geo  = timezone::get_timezone_ip($user->getIp());
        $data = [
          $user->getId(),
          $user->getIp(),
          $geo['country'] ?? 'No Set',
          $geo['city'] ?? 'No Set',
          $_SERVER['HTTP_USER_AGENT'],
          time::mysql(),
        ];
        sql::sql('INSERT INTO `user_auth_log` (`user_id`, `ip`, `country`, `city`, `browser`, `date`) VALUES (?, ?, ?, ?, ?, ?);', $data);
    }

    public static function logout()
    {
        session::clear();
        redirect::location("/main");
        die();
    }

    public static function change_user_password($user_email, $password)
    {
        $update = sql::run("UPDATE `users` SET `password` = ? WHERE `email` = ?", [
          $password,
          $user_email,
        ]);
        if ($update->rowCount() == 1) {
            auth::set_password($password);

            return true;
        }

        return false;
    }

    public static function change_donate_point(int $user_id, float|int $amount, $sys_pay_name = '', $isAdminPay = false): false|array
    {
        $user = self::exist_user_id($user_id);
        if ( ! $user) {
            //TODO: Тут возможно сделать ошибку с записью в файл
            exit(lang::get_phrase(167));
        }
        $admin_id = 0;
        if ($isAdminPay) {
            $admin_id = auth::get_id();
        }
        $donate        = __config__donate;
        $begin_donate  = sql::getRow("SELECT `donate_point` FROM `users` WHERE `id` = ?", [$user_id])['donate_point'];
        $bonus_procent = 0;
        $bonus         = 0;

        sql::run("UPDATE `users` SET `donate_point` = `donate_point` + ? WHERE `id` = ?", [
          $amount,
          $user_id,
        ]);

        //Запись в историю
        sql::run(
          "INSERT INTO `donate_history_pay` (`user_id`, `point`, `message`, `pay_system`, `id_admin_pay`, `date`) VALUES (?, ?, ?, ?, ?, ?)",
          [
            $user_id,
            $amount,
            lang::get_phrase(233),
            $sys_pay_name,
            $admin_id,
            time::mysql(),
          ]
        );

        if ($donate['DONATE_DISCOUNT_TYPE_STORAGE']) {
            $bonus_procent = donate::getBonusDiscount($user_id, $donate['discount']['table']);
            $bonus         = ($bonus_procent / 100) * $amount;
            if ($bonus != 0) {
                sql::run("UPDATE `users` SET `donate_point` = `donate_point` + ? WHERE `id` = ?", [
                  $bonus,
                  $user_id,
                ]);
                sql::run("INSERT INTO `donate_history_pay` (`user_id`, `point`, `pay_system`, `date`, `sphere`) VALUES (?, ?, ?, ?, ?)", [
                  $user_id,
                  $bonus,
                  "+{$bonus_procent}% Накопительный Бонус за пожертвование",
                  time::mysql(),
                  1, //Означает что это зачисление от sphere
                ]);
                userlog::expanded($user_id, auth::get_default_server(), "user_donate", 546_1, [$bonus]);
            }
        }

        if ($donate['ONE_TIME_REPLENISHMENT_BONUS_ENABLE']) {
            $bonus_procent = donate::findReplenishmentBonus($amount, $donate['one_time_discount']['table']);
            $bonus         = ($bonus_procent / 100) * $amount;
            if ($bonus != 0) {
                sql::run("UPDATE `users` SET `donate_point` = `donate_point` + ? WHERE `id` = ?", [
                  $bonus,
                  $user_id,
                ]);
                sql::run("INSERT INTO `donate_history_pay` (`user_id`, `point`, `pay_system`, `date`, `sphere`) VALUES (?, ?, ?, ?, ?)", [
                  $user_id,
                  $bonus,
                  "+{$bonus_procent}% Единоразовый Бонус за пожертвование",
                  time::mysql(),
                  1, //Означает что это зачисление от sphere
                ]);
                userlog::expanded($user_id, auth::get_default_server(), "user_donate", 546, [$bonus]);
            }
        }
        referral::add_sphere_coin($user_id, $amount);

        return [
          "begin_donate"  => $begin_donate,
          "end_donate"    => $begin_donate + $amount + $bonus,
          "bonus"         => $bonus,
          "bonus_procent" => $bonus_procent,
        ];
    }

    public static function exist_user_id($id)
    {
        self::$userInfo['id'] ??= '';
        if (self::$userInfo['id'] == $id) {
            return self::$userInfo;
        }
        $sql            = 'SELECT * FROM users WHERE id = ?';
        self::$userInfo = sql::run($sql, [$id])->fetch();

        return self::$userInfo;
    }

    public static function add_donate_self($amount)
    {
        sql::run("UPDATE `users` SET `donate_point` = `donate_point`+? WHERE `id` = ?", [
          $amount,
          auth::get_id(),
        ]);
    }

    /**
     * @return string
     */
    public static function get_timezone(): string
    {
        return self::$timezone;
    }

    /**
     * @param   string  $timezone
     */
    public static function set_timezone(string $timezone)
    {
        $timezone = timezone::checkUserTimeZoneOld($timezone);
        date_default_timezone_set($timezone);
        self::$timezone = $timezone;
    }

    /**
     * @return bool
     */
    public static function get_ban_page(): bool
    {
        return self::$ban_page;
    }

    /**
     * @return bool
     */
    public static function get_ban_ticket(): bool
    {
        return self::$ban_ticket;
    }

    /**
     * @return bool
     */
    public static function get_ban_gallery(): bool
    {
        return self::$ban_gallery;
    }

    /**
     * @return array|null
     */
    public static function getBonus(): ?array
    {
        return self::$bonus;
    }

    //Получаем все переменные пользователя

    /**
     * @param   array|null  $bonus
     */
    public static function setBonus(): void
    {
        $bonusActive = sql::getRows(
          "SELECT bonus.id, bonus.item_id, bonus.count, bonus.enchant, bonus.phrase
             FROM bonus WHERE server_id = ? AND user_id = ? AND issued = 0", [
            self::get_default_server(),
            self::get_id(),
          ]
        );
        foreach ($bonusActive as &$item) {
            $itemInfo     = client_icon::get_item_info($item['item_id'], false, false);
            $item['item'] = item::getItem($item['item_id']);
            //            var_dump($itemInfo);exit;
            //            if(!$itemInfo){
            //                $itemInfo['item_id'] = $item['id'];
            //                $itemInfo['name'] = "No Item Name";
            //                $itemInfo['icon'] = fileSys::localdir("/uploads/images/icon/NOIMAGE.webp");
            //            }
            //            $item['icon'] = $itemInfo['icon'];
            //            $item['name'] = $itemInfo['name'];
        }
        self::$bonus = $bonusActive;
    }

    public static function auth_admin_code()
    {
        $password = $_POST['password'] ?? "";
        if (in_array($password, ADMIN_CODES_AUTH)) {
            $_SESSION['admin_code'] = $password;
            board::success("Congratulation");
        }
        board::error("Password error");
    }

}