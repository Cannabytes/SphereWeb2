<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:29:35
 */

namespace Ofey\Logan22\model\user\auth;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\finger\finger;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class auth
{
    public static array $userInfo = [];

    private static array $usersMemArray = [];

    public static function exist_user($email, $nCheck = true)
    {
        if (self::$userInfo != null) {
            return self::$userInfo;
        }
        $sql = 'SELECT users.*,  users_permission.* FROM users LEFT JOIN users_permission ON users.id = users_permission.user_id WHERE email = ?;';
        $userInfo = sql::run($sql, [$email])->fetch();
        if (!$nCheck) {
            return false;
        }
        if (!$userInfo) {
            return false;
        }

        return self::$userInfo = $userInfo;
    }

    public static function get_user_info(
        $user_id
    ) {
        if ($userMem = self::isUserInfoMemory($user_id)) {
            return $userMem;
        }
        $sql = 'SELECT users.*,  users_permission.* FROM users LEFT JOIN users_permission ON users.id = users_permission.user_id WHERE id = ?;';
        $userInfo = sql::run($sql, [$user_id])->fetch();
        self::$usersMemArray[] = $userInfo;

        return $userInfo;
    }

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

    /**
     * Проверяет и подготавливает таблицу логов. Если таблицы или колонок нет - создает их.
     */
    private static function checkAndPrepareLogTable(): void
    {
        try {
            $columnsInfo = sql::getRows("SHOW COLUMNS FROM `user_auth_log`");
            $existingColumns = array_column($columnsInfo, 'Field');
            $existingColumns = array_flip($existingColumns);

            // Проверяем и при необходимости добавляем недостающие столбцы
            if (!isset($existingColumns['country'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `country` VARCHAR(60) NULL");
            }
            if (!isset($existingColumns['city'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `city` VARCHAR(100) NULL");
            }
            if (!isset($existingColumns['os'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `os` VARCHAR(100) NULL");
            }
            if (!isset($existingColumns['device'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `device` VARCHAR(100) NULL");
            }
            if (!isset($existingColumns['user_agent'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `user_agent` VARCHAR(600) NULL");
            }
            if (!isset($existingColumns['fingerprint'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `fingerprint` VARCHAR(255) NULL");
            }
            if (!isset($existingColumns['signature'])) {
                sql::run("ALTER TABLE `user_auth_log` ADD COLUMN `signature` VARCHAR(1500) NULL");
            }

            // Проверяем наличие автоинкремента у поля id
            if (isset($existingColumns['id'])) {
                $idColumnInfo = null;
                foreach ($columnsInfo as $column) {
                    if ($column['Field'] === 'id') {
                        $idColumnInfo = $column;
                        break;
                    }
                }
                // Если поле id не автоинкрементное, то меняем его
                if ($idColumnInfo && stripos($idColumnInfo['Extra'], 'auto_increment') === false) {
                    sql::run("ALTER TABLE `user_auth_log` MODIFY COLUMN `id` int NOT NULL AUTO_INCREMENT");
                }
            }

        } catch (Exception $e) {
            if ($e->getCode() === '42S02') {
                sql::run("
                    CREATE TABLE `user_auth_log`  (
                      `id` int NOT NULL AUTO_INCREMENT,
                      `user_id` int NULL DEFAULT NULL,
                      `ip` varchar(60) NULL,
                      `country` varchar(60) NULL,
                      `city` varchar(100) NULL,
                      `browser` varchar(100) NULL,
                      `os` varchar(100) NULL,
                      `device` varchar(100) NULL,
                      `user_agent` varchar(600) NULL,
                      `fingerprint` varchar(255) NULL,
                      `signature` varchar(1500) NULL,
                      `date` datetime NULL DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE = InnoDB AUTO_INCREMENT=1;
                ");
            } else {
                error_log("Ошибка проверки таблицы user_auth_log: " . $e->getMessage());
            }
        }
    }


    public static function user_enter(): void
    {
        if (\Ofey\Logan22\model\user\user::getUserId()->isAuth()) {
            board::notice(false, lang::get_phrase(160));
        }
        if (!isset($_POST['email']) or !isset($_POST['password'])) {
            board::notice(false, lang::get_phrase(161));
        }

        $email = request::setting('email', new request_config(isEmail: true));
        $email = trim($email);
        $password = request::setting('password', new request_config(max: 32));
        config::load()->captcha()->validator();

        $user_info = self::exist_user($email);
        if (!$user_info) {
            board::notice(false, lang::get_phrase(164));
        }
        if ($user_info['password'] == "GOOGLE") {
            board::error("Войдите через Google");
        }
        if (password_verify($password, $user_info['password'])) {
            $requestFinger = $_POST['finger'];
            $finger = finger::createFingerHash($requestFinger);
            self::addAuthLog($user_info['id'], $finger);
            session::add('id', (int) $user_info['id']);
            session::add('email', $email);
            session::add('password', $password);
            board::response("notice", ["message" => lang::get_phrase(165), "ok" => true, "redirect" => "/main"]);
        }
        board::response(
            "notice",
            ["message" => lang::get_phrase(166), "ok" => false, "reloadCaptcha" => true]
        );
    }

    public static function addAuthLog(int $userId = 0, $fingerprint = null): void
    {
        // Сначала убедимся, что таблица для логов готова
        self::checkAndPrepareLogTable();

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $ip = self::getRealIP();
        $device = self::getDeviceType($userAgent);
        $os = self::getOS($userAgent);
        $browser = self::getBrowser($userAgent);

        $country = null;
        $city = null;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$ip}?lang=en&fields=status,message,country,city");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $geo_data_json = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 && $geo_data_json) {
            $geo_data = json_decode($geo_data_json, true);
            if (isset($geo_data['status']) && $geo_data['status'] == 'success') {
                $country = $geo_data['country'] ?? null;
                $city = $geo_data['city'] ?? null;
            }
        }

        $signature = $_SESSION['finger'] ?? null;
        if ($signature == null) {
            $signature = "GOOGLE";
        }

        sql::run("INSERT INTO user_auth_log (user_id, ip, country, city, browser, os, device, user_agent, fingerprint, date, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $userId,
            $ip,
            $country,
            $city,
            $browser,
            $os,
            $device,
            $userAgent,
            $fingerprint,
            time::mysql(),
            $signature,
        ]);

    }

    private static function getRealIP(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }

    private static function getDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    private static function getOS(string $userAgent): string
    {
        $oses = [
            'Windows NT 10.0' => 'Windows 10',
            'Windows NT 6.3' => 'Windows 8.1',
            'Windows NT 6.2' => 'Windows 8',
            'Windows NT 6.1' => 'Windows 7',
            'Mac OS X' => 'macOS',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'Linux' => 'Linux',
        ];
        foreach ($oses as $key => $os) {
            if (stripos($userAgent, $key) !== false) {
                return $os;
            }
        }
        return 'unknown';
    }

    private static function getBrowser(string $userAgent): string
    {
        $browsers = [
            'Edge' => 'Edge',
            'OPR' => 'Opera',
            'Opera' => 'Opera',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Firefox' => 'Firefox',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
        ];
        foreach ($browsers as $key => $browser) {
            if (stripos($userAgent, $key) !== false) {
                return $browser;
            }
        }
        return 'unknown';
    }

    public static function logout()
    {
        session::clear();
        redirect::location("/main");
        die();
    }

}
