<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Modified by Claude.ai
 * Date: 01.05.2025
 */

namespace Ofey\Logan22\component\session;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class session
{
    // Общие настройки для всех типов запросов
    // Период времени в секундах для проверки флуда
    private static int $floodPeriodSeconds = 60;

    // Время жизни сессии в секундах (365 дней)
    private static int|float $sessionLifetime = 86400 * 365;

    //Время жизни сессии для неавторизованных пользователей
    private static int|float $sessionLifetimeGuest = 120;

    // Вероятность срабатывания GC - сборщика мусора (1%)
    private static int $gcProbability = 1;
    private static int $gcDivisor = 100;

    // Настройки для GET запросов
    // Максимальное количество GET запросов за период
    private static int $maxGetActionsPerPeriod = 60;
    // Время бана в секундах при превышении лимита GET запросов
    private static int $getFloodBanSeconds = 60;

    // Настройки для POST запросов
    // Максимальное количество POST запросов за период
    private static int $maxPostActionsPerPeriod = 300;
    // Время бана в секундах при превышении лимита POST запросов
    private static int $postFloodBanSeconds = 160;


    /**
     * Инициализация сессии
     *
     * @return void
     */
    public static function init(): void
    {
        // Проверяем существование таблиц для сессий
        self::ensureSessionTablesExist();

        // Генерируем или получаем ID сессии
        $sessionId = self::getSessionId();

        // Загружаем данные сессии
        self::loadSession($sessionId);

        if(!user::self()->isAdmin()){
            // Проверяем защиту от флуда
//            self::checkFloodProtection();
        }

        // Обработка HTTP_REFERER для статистики
        self::handleHttpReferer();

        // Запускаем сборщик мусора с вероятностью 1%
        self::runGarbageCollectorWithProbability();

        // Регистрируем функцию автосохранения при завершении скрипта
        register_shutdown_function([self::class, 'autoSaveSession']);
    }

    public static function autoSaveSession(): void
    {
        self::saveSession();
    }

    /**
     * Запуск сборщика мусора с заданной вероятностью
     *
     * @return void
     */
    private static function runGarbageCollectorWithProbability(): void
    {
        if (self::$gcProbability <= 0) {
            return;
        }
        $random = mt_rand(0, self::$gcDivisor - 1);
        if ($random < self::$gcProbability) {
            self::gc();
        }
    }


    /**
     * Создание объединенной таблицы для сессий и защиты от флуда
     *
     * @return void
     */
    private static function ensureSessionTablesExist(): void
    {
        if (!file_exists(fileSys::get_dir('/data/db.php'))) {
            return;
        }

        // Объединенная таблица сессий и защиты от флуда
        sql::run("
        CREATE TABLE IF NOT EXISTS `sessions` (
            `session_id` VARCHAR(64) NOT NULL PRIMARY KEY,
            `user_id` INT NULL DEFAULT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `user_agent` TEXT NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL,
            `data` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `get_action_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `post_action_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `get_last_action_time` INT UNSIGNED NULL DEFAULT NULL,
            `post_last_action_time` INT UNSIGNED NULL DEFAULT NULL,
            `get_banned_until` INT UNSIGNED NULL DEFAULT NULL,
            `post_banned_until` INT UNSIGNED NULL DEFAULT NULL,
            INDEX `idx_last_activity` (`last_activity`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_get_banned_until` (`get_banned_until`),
            INDEX `idx_post_banned_until` (`post_banned_until`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    }

    /**
     * Получение или генерация ID сессии
     *
     * @return string
     */
    private static function getSessionId(): string
    {
        if (isset($_COOKIE['sphere_session'])) {
            $sessionId = $_COOKIE['sphere_session'];
            if (!preg_match('/^[a-f0-9]{64}$/', $sessionId)) {
                $sessionId = bin2hex(random_bytes(32));
            }
        } else {
            $sessionId = bin2hex(random_bytes(32));
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https';
        $httpOnly = true;
        $sameSite = 'Lax';

        setcookie('sphere_session', $sessionId, [
            'expires' => time() + self::$sessionLifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ]);

        return $sessionId;
    }

    /**
     * Загрузка данных сессии из БД
     *
     * @param string $sessionId ID сессии
     * @return void
     */
    private static function loadSession(string $sessionId): void
    {
        $sessionData = sql::getRow("
            SELECT `data` 
            FROM `sessions` 
            WHERE `session_id` = ?
        ", [$sessionId]);

        $_SESSION = [];

        if ($sessionData) {
            $data = json_decode($sessionData['data'], true);
            if (is_array($data)) {
                $_SESSION = $data;
            }

            self::updateLastActivity($sessionId);
        } else {
            // Создаем новую сессию
            $ipAddress = self::getIpAddress();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Сanitize user agent to avoid invalid UTF-8 bytes causing SQL errors
            $userAgent = self::sanitizeForDatabase($userAgent);

            sql::run("
                INSERT INTO `sessions` 
                (`session_id`, `ip_address`, `user_agent`, `last_activity`, `data`) 
                VALUES (?, ?, ?, ?, ?)
            ", [
                $sessionId,
                $ipAddress,
                $userAgent,
                time(),
                json_encode([])
            ]);
        }
    }

    /**
     * Обновление времени последней активности
     *
     * @param string $sessionId ID сессии
     * @return void
     */
    private static function updateLastActivity(string $sessionId): void
    {
        // Обновляем timestamp последней активности и данные сессии
        sql::run("
            UPDATE `sessions` 
            SET `last_activity` = ?, `data` = ? 
            WHERE `session_id` = ?
        ", [
            time(),
            json_encode($_SESSION),
            $sessionId
        ]);
    }

    /**
     * Проверка защиты от флуда и применение ограничений
     *
     * @return void
     */
    private static function checkFloodProtection(): void
    {
        if (!isset($_COOKIE['sphere_session'])) {
            return;
        }

        $sessionId = $_COOKIE['sphere_session'];
        $ipAddress = self::getIpAddress();
        $currentTime = time();

        // Определяем тип запроса
        $requestType = $_SERVER['REQUEST_METHOD'] ?? 'OTHER';
        if (!in_array($requestType, ['GET', 'POST'])) {
            return; // Пропускаем проверку для нестандартных методов
        }

        // Выбираем лимиты в зависимости от типа запроса
        $maxActions = $requestType === 'GET'
            ? self::$maxGetActionsPerPeriod
            : self::$maxPostActionsPerPeriod;

        $banSeconds = $requestType === 'GET'
            ? self::$getFloodBanSeconds
            : self::$postFloodBanSeconds;

        // Формируем имена полей в зависимости от типа запроса
        $countField = strtolower($requestType) . '_action_count';
        $timeField = strtolower($requestType) . '_last_action_time';
        $banField = strtolower($requestType) . '_banned_until';

        // Получаем запись сессии
        $sessionRecord = sql::getRow("
        SELECT `$countField`, `$timeField`, `$banField` 
        FROM `sessions` 
        WHERE `session_id` = ?
    ", [$sessionId]);

        if (!$sessionRecord) {
            return;
        }


        if ($sessionRecord[$banField] !== null && $sessionRecord[$banField] > $currentTime) {
            $timeout = $sessionRecord[$banField] - $currentTime;
            if($requestType === 'POST'){
                echo json_encode([
                    'type'    => 'notice',
                    'ok'      => false,
                    'message' => 'Ваша сессия заблокирована. Повторите попытку через ' . ($timeout) . ' секунд.',
                ]);exit;
            }
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . ($timeout));
            tpl::addVar(['blockUntil' => $timeout]);
            tpl::display("flood_blocked_page.html");
            exit;
        }

        // Если бан истек, сбрасываем его
        if ($sessionRecord[$banField] !== null && $sessionRecord[$banField] <= $currentTime) {
            sql::run("
            UPDATE `sessions` 
            SET `$countField` = 1, `$timeField` = ?, `$banField` = NULL 
            WHERE `session_id` = ?
        ", [$currentTime, $sessionId]);
            return;
        }

        // Проверяем, истек ли период проверки флуда
        if ($sessionRecord[$timeField] === null || ($currentTime - $sessionRecord[$timeField]) > self::$floodPeriodSeconds) {
            sql::run("
            UPDATE `sessions` 
            SET `$countField` = 1, `$timeField` = ? 
            WHERE `session_id` = ?
        ", [$currentTime, $sessionId]);
        } else {
            $newActionCount = $sessionRecord[$countField] + 1;

            if ($newActionCount > $maxActions) {
                $bannedUntil = $currentTime + $banSeconds;
                sql::run("
                UPDATE `sessions` 
                SET `$countField` = ?, `$timeField` = ?, `$banField` = ? 
                WHERE `session_id` = ?
            ", [$newActionCount, $currentTime, $bannedUntil, $sessionId]);
                header('HTTP/1.1 429 Too Many Requests');
                header('Retry-After: ' . $banSeconds);
                tpl::addVar(['blockUntil' => $banSeconds]);
                tpl::display("flood_blocked_page.html");
                exit;
            } else {
                sql::run("
                UPDATE `sessions` 
                SET `$countField` = ?, `$timeField` = ? 
                WHERE `session_id` = ?
            ", [$newActionCount, $currentTime, $sessionId]);
            }
        }
    }

    /**
     * Получение IP-адреса пользователя с проверкой прокси
     *
     * @return string
     */
    private static function getIpAddress(): string
    {
        $ip = '';

        // Проверяем различные заголовки для получения IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Проверяем, что IP валидный
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Обработка HTTP_REFERER для статистики
     *
     * @return void
     */
    private static function handleHttpReferer(): void
    {
        if (!file_exists(fileSys::get_dir('/data/db.php'))) {
            return;
        }

        if (!isset($_SESSION['id'])) {
            if (!isset($_SESSION['HTTP_REFERER_SET'])) {
                if (isset($_SESSION['HTTP_REFERER'])) {
                    $_SESSION['HTTP_REFERER'] = self::domainViewsCounter($_SESSION['HTTP_REFERER']);
                } else {
                    if (isset($_SERVER['HTTP_REFERER'])) {
                        $_SESSION['HTTP_REFERER'] = self::domainViewsCounter($_SERVER['HTTP_REFERER']);
                    }
                }
                self::saveSession();
            }
        }
    }

    /**
     * Подсчет переходов с доменов (адаптировано из оригинального класса)
     *
     * @param string $url URL-адрес реферера
     * @return string
     */
    public static function domainViewsCounter($url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'] ?? '';
        } else {
            $host = $url;
        }

        $host = str_replace(['www.', 'http://', 'https://'], '', $host);
        $host = mb_strtolower($host);

        if ($host == "api.sphereweb.com") {
            return "";
        }

        $date = date("Y-m-d");
        $data = sql::getRow("SELECT `data` FROM server_cache WHERE `type` = 'HTTP_REFERER_VIEWS';");

        if ($data) {
            $dataJSONDecode = json_decode($data["data"], true);
            $n = false;

            foreach ($dataJSONDecode as &$val) {
                $referer = $val['referer'];
                if ($host == $referer) {
                    // Проверяем наличие ключа $date и инициализируем его, если отсутствует
                    if (!isset($val['count'][$date])) {
                        $val['count'][$date] = 0;
                    }
                    $val['count'][$date]++;
                    $n = true;
                    break;
                }
            }

            if (!$n) {
                $dataJSONDecode[] = [
                    'referer' => $host,
                    'count' => [
                        $date => 1,
                    ],
                ];
            }

            $dataJSON = json_encode($dataJSONDecode, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sql::run("UPDATE `server_cache` SET `data` = ? WHERE `type` = 'HTTP_REFERER_VIEWS'", [$dataJSON]);
        } else {
            $arr = json_encode([
                [
                    'referer' => $host,
                    'count' => [
                        $date => 1,
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sql::run("INSERT INTO `server_cache` (`type`, `data`) VALUES ('HTTP_REFERER_VIEWS', ?)", [$arr]);
        }

        return $host;
    }

    /**
     * Редактирование значения в сессии
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return bool
     */
    public static function edit($key, $value): bool
    {
        if (isset($_SESSION[$key])) {
            $_SESSION[$key] = self::sanitizeInput($value);
            self::saveSession();
            return true;
        }

        return false;
    }

    /**
     * Получение значения из сессии
     *
     * @param string $key Ключ
     * @return mixed|null
     */
    public static function get($key)
    {
        if (!isset($_SESSION[$key])) {
            return null;
        }

        return $_SESSION[$key];
    }

    /**
     * Удаление ключа из сессии
     *
     * @param string $key Ключ
     * @return bool
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            self::saveSession();
            return true;
        }

        return false;
    }

    /**
     * Очистка сессии, сохраняя язык
     *
     * @return void
     */
    public static function clear()
    {
        $lang = $_SESSION['lang'] ?? null;
        $_SESSION = array();
        if ($lang !== null) {
            $_SESSION['lang'] = $lang;
        }
        self::saveSession();
    }

    /**
     * Полное удаление сессии
     */
    public static function destroy()
    {
        if (!isset($_COOKIE['sphere_session'])) {
            return false;
        }

        $sessionId = $_COOKIE['sphere_session'];

        sql::run("DELETE FROM `sessions` WHERE `session_id` = ?", [$sessionId]);
        sql::run("DELETE FROM `sessions_flood_protection` WHERE `session_id` = ?", [$sessionId]);

        setcookie('sphere_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        $_SESSION = [];
    }

    /**
     * Получение всех переменных гостя
     *
     * @return array
     */
    public static function get_guest_var(): array
    {
        $result = [];
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, "var_")) {
                $var = str_replace("var_", "", $key);
                $result[$var] = $value;
            }
        }

        return $result;
    }

    /**
     * Добавление переменной гостя
     *
     * @param string $nameVar Имя переменной
     * @param mixed $value Значение
     * @return void
     */
    public static function add_var($nameVar, $value): void
    {
        self::add("var_$nameVar", $value);
    }

    /**
     * Добавление значения в сессию
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return void
     */
    public static function add($key, $value): void
    {
        $_SESSION[$key] = self::sanitizeInput($value);
        self::saveSession();
    }

    /**
     * Сохранение данных сессии в БД
     *
     * @return void
     */
    private static function saveSession()
    {
        if (!isset($_COOKIE['sphere_session'])) {
            return;
        }

        $sessionId = $_COOKIE['sphere_session'];
        $userId = $_SESSION['id'] ?? null;

        sql::run("
			UPDATE `sessions` 
			SET `data` = ?, `last_activity` = ?, `user_id` = ? 
			WHERE `session_id` = ?
		", [
            json_encode($_SESSION),
            time(),
            $userId,
            $sessionId
        ]);
    }

    /**
     * Очистка старых сессий
     *
     * @param int|null $maxLifetime Максимальное время жизни сессии в секундах
     * @return bool
     */
    public static function gc(?int $maxLifetime = null): bool
    {
        if ($maxLifetime === null) {
            $maxLifetime = self::$sessionLifetime;
        }

        $cutoff = time() - $maxLifetime;
        $cutGuestOff = time() - self::$sessionLifetimeGuest;

        sql::run("DELETE FROM `sessions` WHERE `user_id` IS NULL and `last_activity` < ?", [$cutGuestOff]);
        sql::run("DELETE FROM `sessions` WHERE `last_activity` < ?", [$cutoff]);

        sql::run("
        UPDATE `sessions` 
        SET `get_banned_until` = NULL 
        WHERE `get_banned_until` IS NOT NULL AND `get_banned_until` < ?
    ", [time()]);

        sql::run("
        UPDATE `sessions` 
        SET `post_banned_until` = NULL 
        WHERE `post_banned_until` IS NOT NULL AND `post_banned_until` < ?
    ", [time()]);

        return true;
    }

    /**
     * Проверка, является ли реферер внутренним
     *
     * @param string $referer URL реферера
     * @return bool
     */
    private static function isInternalReferer(string $referer): bool
    {
        $parsedReferer = parse_url($referer);
        $refererHost = $parsedReferer['host'] ?? '';

        return $refererHost === ($_SERVER['SERVER_NAME'] ?? '');
    }

    /**
     * Безопасная обработка входных данных для защиты от XSS
     *
     * @param mixed $data Входные данные
     * @return mixed
     */
    private static function sanitizeInput($data): mixed
    {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize a string for safe database insertion by removing invalid UTF-8 sequences
     * and replacing non-printable/control characters (except common whitespace).
     *
     * @param string $str
     * @return string
     */
    private static function sanitizeForDatabase(string $str): string
    {
        if ($str === '') {
            return '';
        }

        // Attempt to convert from Windows-1252/ISO-8859-1 to UTF-8 if string is not valid UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            // Try to detect common encodings and convert to UTF-8
            $enc = mb_detect_encoding($str, ['UTF-8', 'CP1252', 'ISO-8859-1', 'ASCII'], true);
            if ($enc && $enc !== 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $enc);
            } else {
                // Fallback: force UTF-8 by ignoring invalid bytes
                $str = mb_convert_encoding($str, 'UTF-8', 'CP1252');
            }
        }

        // Remove invalid UTF-8 sequences that still may remain
        $str = iconv('UTF-8', 'UTF-8//IGNORE', $str) ?: '';

        // Remove control characters except newlines, carriage returns and tabs
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);

        // Trim excessive whitespace
        $str = trim($str);

        return $str;
    }
}