<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 14.08.2022 / 23:29:35
 */

namespace Ofey\Logan22\model\user\auth;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\component\plugins\xenforo_importer\system\XenForoPasswordHandler;

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
        
        // Проверяем пароль
        $passwordVerified = false;
        $needsRehash = false;
        
        // Проверка пароля XenForo (с префиксом xenforo:)
        if (XenForoPasswordHandler::isXenForoPassword($user_info['password'])) {
            if (XenForoPasswordHandler::verify($password, $user_info['password'])) {
                $passwordVerified = true;
                $needsRehash = true; // XenForo пароли всегда нужно обновлять
            }
        }
        // Проверка обычного bcrypt пароля
        elseif (password_verify($password, $user_info['password'])) {
            $passwordVerified = true;
            // Проверяем, нужно ли обновить хэш (например, если изменился алгоритм)
            if (password_needs_rehash($user_info['password'], PASSWORD_BCRYPT)) {
                $needsRehash = true;
            }
        }
        
        if ($passwordVerified) {
            // Если пароль нужно обновить (XenForo или устаревший bcrypt)
            if ($needsRehash) {
                $newPasswordHash = password_hash($password, PASSWORD_BCRYPT);
                sql::run(
                    "UPDATE users SET password = ? WHERE id = ?",
                    [$newPasswordHash, $user_info['id']]
                );
            }
            
            // Получаем текущий IP пользователя
            $currentIP = self::getRealIP();
            
            // Обновляем IP если необходимо
            self::updateUserIPIfNeeded($user_info['id'], $currentIP);
            
            // Обновляем географические данные (timezone, country, city) если необходимо
            self::updateUserGeoDataIfNeeded($user_info['id'], $currentIP);
            
            self::addAuthLog($user_info['id']);
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

    public static function addAuthLog(int $userId = 0): void
    {

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

        $signature = null;
        if ($signature == null) {
            $signature = "GOOGLE";
        }

        sql::run("INSERT INTO user_auth_log (user_id, ip, country, city, browser, os, device, user_agent, date, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $userId,
            $ip,
            $country,
            $city,
            $browser,
            $os,
            $device,
            $userAgent,
            time::mysql(),
            $signature,
        ]);

    }

    /**
     * Получает реальный IP-адрес пользователя с учетом различных конфигураций серверов
     * Проверяет множество заголовков для надежного определения IP
     */
    private static function getRealIP(): string
    {
        // Список заголовков для проверки (в порядке приоритета)
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // CloudFlare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_CLIENT_IP',            // Прокси
            'HTTP_X_FORWARDED_FOR',      // Стандартный заголовок прокси
            'HTTP_X_FORWARDED',          // Альтернативный вариант
            'HTTP_X_CLUSTER_CLIENT_IP',  // Кластерные прокси
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // Прямое подключение
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Если IP содержит несколько адресов (прокси-цепочка), берем первый
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Валидация IP-адреса
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                
                // Если валидация не прошла, но IP выглядит корректно (включая приватные сети)
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }
    
    /**
     * Проверяет, нужно ли обновить IP пользователя
     * Обновляет если IP отсутствует, равен 0.0.0.0 или unknown
     */
    private static function updateUserIPIfNeeded(int $userId, string $currentIP): void
    {
        $userInfo = sql::run("SELECT ip FROM users WHERE id = ?", [$userId])->fetch();
        
        if (!$userInfo) {
            return;
        }
        
        $storedIP = $userInfo['ip'] ?? '';
        
        // Проверяем условия для обновления IP
        $needsUpdate = (
            empty($storedIP) || 
            $storedIP === '0.0.0.0' || 
            $storedIP === 'unknown' ||
            $storedIP === '::1' || // localhost IPv6
            $storedIP === '127.0.0.1' // localhost IPv4
        );
        
        if ($needsUpdate && $currentIP !== 'unknown') {
            sql::run("UPDATE users SET ip = ? WHERE id = ?", [$currentIP, $userId]);
        }
    }
    
    /**
     * Обновляет географические данные пользователя (timezone, country, city)
     * Обновляет если данные отсутствуют или некорректны
     */
    private static function updateUserGeoDataIfNeeded(int $userId, string $currentIP): void
    {
        if ($currentIP === 'unknown' || $currentIP === '0.0.0.0' || $currentIP === '127.0.0.1' || $currentIP === '::1') {
            return;
        }
        
        $userInfo = sql::run("SELECT timezone, country, city FROM users WHERE id = ?", [$userId])->fetch();
        
        if (!$userInfo) {
            return;
        }
        
        // Проверяем, нужно ли обновить географические данные
        $needsUpdate = (
            empty($userInfo['timezone']) || 
            empty($userInfo['country']) || 
            empty($userInfo['city']) ||
            $userInfo['timezone'] === 'unknown' ||
            $userInfo['country'] === 'unknown' ||
            $userInfo['city'] === 'unknown'
        );
        
        if ($needsUpdate) {
            $geoData = timezone::get_timezone_ip($currentIP);
            
            if ($geoData !== null && is_array($geoData)) {
                $timezone = $geoData['timezone'] ?? null;
                $country = $geoData['country'] ?? null;
                $city = $geoData['city'] ?? null;
                
                // Обновляем только если получили валидные данные
                if ($timezone || $country || $city) {
                    $updateFields = [];
                    $updateValues = [];
                    
                    if ($timezone && empty($userInfo['timezone'])) {
                        $updateFields[] = "timezone = ?";
                        $updateValues[] = $timezone;
                    }
                    
                    if ($country && empty($userInfo['country'])) {
                        $updateFields[] = "country = ?";
                        $updateValues[] = $country;
                    }
                    
                    if ($city && empty($userInfo['city'])) {
                        $updateFields[] = "city = ?";
                        $updateValues[] = $city;
                    }
                    
                    if (!empty($updateFields)) {
                        $updateValues[] = $userId;
                        $updateSQL = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
                        sql::run($updateSQL, $updateValues);
                    }
                }
            }
        }
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
