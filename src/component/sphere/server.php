<?php

namespace Ofey\Logan22\component\sphere;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\userModel;
use Ofey\Logan22\template\tpl;

class server
{

    public static bool $showError = false;

    private static int $countRequest = 0;

    private static ?string $token = null;

    /**
     * Информация о пользователе
     *
     * @var null|userModel
     */
    private static ?userModel $user = null;

    private static false|string $error = false;

    private static ?bool $isOfflineServer = null;

    private static bool $tokenDisable = false;

    private static ?int $server_id = null;

    private static null|int|string $codeError = null;

    private static $installLink = null;

    private static $showPageError = false;

    /**
     * Указываем аргументом которые отправятся запросом массив, для того чтоб указывать дополнительные данные, типо ID пользователя и т.д.
     *
     * @param \Ofey\Logan22\component\sphere\type $type
     * @param array $json
     *
     * @return bool|array|null
     */
    public null|array|bool $response = null;

    public static function getCodeError(): int|string|null
    {
        return self::$codeError;
    }

    static public function setUser(?userModel $user): void
    {
        if ($user === null) {
            self::$user = null;

            return;
        }
        self::$user = $user;
    }

    // Отключение отправки токена.

    public static function isError(): false|string
    {
        return self::$error;
    }

    public static function setShowError(bool $showError): void
    {
        self::$showError = $showError;
    }

    static public function tokenDisable(bool $on = true): void
    {
        self::$tokenDisable = $on;
    }

    public static function setInstallLink(string $link): void
    {
        self::$installLink = $link;
    }



    private static int $timeout = 5;
    static public function setTimeout(int $timeout = 5): void
    {
        self::$timeout = $timeout;
    }
    static public function send(type $type, array $arr = []): self
    {
        self::isOffline();
        $instance = new self();
        if (self::$isOfflineServer === true) {
            if (self::$showError) {
                board::error("Сервер недоступен");
            }
            self::$error = 'Sphere Server is offline';

            return $instance;
        }
        self::$error = false;

        if (self::$installLink != null) {
            $link = self::$installLink;
        } else {
            $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
        }

        $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, 2048);

        if ($json === false) {
            $err = "JSON Error Code (after increasing depth): " . json_last_error() . "\n";
            $err .= "JSON Error Message (after increasing depth): " . json_last_error_msg() . "\n";
            board::error($err);
        }

        $url = $link . type::url($type) ?? board::error("Не указан URL запроса");

        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://' . $url;
        }

        $ch = curl_init();
        $headers = [
            'Content-Type: application/json',
            'Authorization: BoberKurwa',
        ];
        if (self::$user !== null) {
            if (self::$server_id == null) {
                self::$server_id = self::$user->getServerId();
            }
            // Данные для аутентификации
            $headers[] = "User-Id: " . self::$user->getId();
            $headers[] = "User-Email: " . self::$user->getEmail();
            $headers[] = "User-Server-Id: " . self::$server_id;
            $headers[] = "User-IP: " . self::$user->getIp();
            $headers[] = "User-isBot: " . 0;
        } else {
            $headers[] = "User-Id: " . 0;
            if (type::SPHERE_INSTALL != $type) {
                $headers[] = "User-Server-Id: " . \Ofey\Logan22\model\server\server::getLastServer()?->getId();
            }
        }
        $headers[] = "Token: " . self::getToken();

        $host = $_SERVER['HTTP_HOST'];
        if (empty($host) || !self::is_valid_domain(parse_url($host, PHP_URL_HOST))) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
        $parsedHost = preg_replace('/:\d+$/', '', $parsedHost);
        $headers[] = "Domain: " . $parsedHost;

        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7";
        $headers[] = "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7";
        $headers[] = "Connection: keep-alive";
        $headers[] = "Cache-Control: max-age=0";
        $headers[] = "Origin: https://" . $parsedHost;
        $headers[] = "X-Requested-With: XMLHttpRequest";
        $headers[] = 'Content-Length: ' . strlen($json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        self::$countRequest++;
        $response = curl_exec($ch);

        if ($response === false) {
            self::$codeError = "sphereapi_unavailable";
            self::$error = 'Ошибка соединения с Sphere API. Попробуйте еще раз. Возможно сервер на перезагрузке либо указаны неверные данные подключения к Sphere API. Если ошибка повторится, обратитесь в службу поддержки.';
            self::$isOfflineServer = true;

            return $instance;
        }

        if (curl_errno($ch)) {
            sql::sql("DELETE FROM `server_cache` WHERE `type` = 'sphereServer'");
            sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (0, ?, ?, ?)", [
                "sphereServer",
                json_encode(["connect" => false, "error" => "Not connect to Sphere Server"], JSON_UNESCAPED_UNICODE),
                time::mysql(),
            ]);
            self::$isOfflineServer = true;

            return $instance;
        }
        curl_close($ch);

        $response = json_decode($response, true) ?? false;
        $instance->response = $response;
        if ($response === false) {
            return $instance;
        }

        if (isset($response['error'])) {
            if(isset($response['error']['Message'])){
                self::$error = $response['error']['Message'];
            }else{
                self::$error = $response['error'];
            }
            if (isset($response['code'])) {
                self::$codeError = $response['code'];
            }
            if (self::$showError) {
                board::error($response['error']);
            }
        }

        return $instance;
    }

        /**
         * Отправляет multipart/form-data запрос к Sphere API, позволяя передавать файлы и дополнительные поля.
         * Ответ сохраняется в $instance->response с ключами body, http_code, content_type и json (если удалось декодировать).
         */
        static public function sendMultipart(string $url, array $fields = [], int $timeout = 120, array $extraHeaders = []): self
        {
            self::isOffline();
            $instance = new self();
            if (self::$isOfflineServer === true) {
                if (self::$showError) {
                    board::error("Сервер недоступен");
                }
                self::$error = 'Sphere Server is offline';

                return $instance;
            }

            self::$error = false;

            if (self::$installLink != null) {
                $link = rtrim(self::$installLink, '/');
            } else {
                $link = rtrim(config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort(), '/');
            }

            if (!preg_match('/^https?:\/\//i', $link)) {
                $link = 'http://' . $link;
            }

            $fullUrl = rtrim($link, '/') . '/' . ltrim($url, '/');

            $preparedFields = [];
            foreach ($fields as $key => $value) {
                if ($value instanceof \CURLFile) {
                    $preparedFields[$key] = $value;
                    continue;
                }
                if (is_array($value) && isset($value['path'])) {
                    $preparedFields[$key] = curl_file_create(
                        $value['path'],
                        $value['type'] ?? null,
                        $value['name'] ?? basename($value['path'])
                    );
                    continue;
                }
                $preparedFields[$key] = $value;
            }

            $ch = curl_init();
            $headers = [
                'Authorization: BoberKurwa',
            ];

            if (self::$user !== null) {
                if (self::$server_id == null) {
                    self::$server_id = self::$user->getServerId();
                }
                $headers[] = "User-Id: " . self::$user->getId();
                $headers[] = "User-Email: " . self::$user->getEmail();
                $headers[] = "User-Server-Id: " . self::$server_id;
                $headers[] = "User-IP: " . self::$user->getIp();
                $headers[] = "User-isBot: " . 0;
            } else {
                $server_id = \Ofey\Logan22\model\server\server::getLastServer()?->getId();
                if (isset($_SESSION['server_id'])) {
                    $server_id = $_SESSION['server_id'];
                }
                $headers[] = "User-Id: " . 0;
                if (type::SPHERE_INSTALL != $url) {
                    $headers[] = "User-Server-Id: " . $server_id;
                }
            }

            $headers[] = "Token: " . self::getToken();

            $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
            if (empty($host) || !self::is_valid_domain(parse_url($host, PHP_URL_HOST))) {
                $host = $_SERVER['SERVER_NAME'] ?? $host;
            }

            $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
            $parsedHost = preg_replace('/:\\d+$/', '', $parsedHost);
            $headers[] = "Domain: " . $parsedHost;

            if (!empty($extraHeaders)) {
                $headers = array_merge($headers, $extraHeaders);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $preparedFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

            self::$countRequest++;
            $responseBody = curl_exec($ch);

            if ($responseBody === false) {
                self::$codeError = "sphereapi_unavailable";
                self::$error = 'Ошибка соединения с Sphere API. Попробуйте еще раз. Возможно сервер на перезагрузке либо указаны неверные данные подключения к Sphere API. Если ошибка повторится, обратитесь в службу поддержки.';
                self::$isOfflineServer = true;

                return $instance;
            }

            if (curl_errno($ch)) {
                sql::sql("DELETE FROM `server_cache` WHERE `type` = 'sphereServer'");
                sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (0, ?, ?, ?)", [
                    "sphereServer",
                    json_encode(["connect" => false, "error" => "Not connect to Sphere Server"], JSON_UNESCAPED_UNICODE),
                    time::mysql(),
                ]);
                self::$isOfflineServer = true;

                return $instance;
            }

            $info = curl_getinfo($ch);
            curl_close($ch);

            $contentType = $info['content_type'] ?? null;
            $httpCode = $info['http_code'] ?? 0;
            $decodedJson = null;

            if ($contentType && str_contains($contentType, 'application/json')) {
                $decodedJson = json_decode($responseBody, true);
            } else {
                $decodedJson = json_decode($responseBody, true);
                if ($decodedJson === null && json_last_error() !== JSON_ERROR_NONE) {
                    $decodedJson = null;
                }
            }

            if (is_array($decodedJson) && isset($decodedJson['error'])) {
                self::$error = is_array($decodedJson['error']) && isset($decodedJson['error']['Message'])
                    ? $decodedJson['error']['Message']
                    : $decodedJson['error'];
                if (isset($decodedJson['code'])) {
                    self::$codeError = $decodedJson['code'];
                }
            }

            $instance->response = [
                'body' => $responseBody,
                'http_code' => $httpCode,
                'content_type' => $contentType,
                'info' => $info,
                'json' => $decodedJson,
            ];

            return $instance;
        }

    static public function sendCustom(string $url, array $arr = []): self
    {
        self::isOffline();
        $instance = new self();
        if (self::$isOfflineServer === true) {
            if (self::$showError) {
                board::error("Сервер недоступен");
            }
            self::$error = 'Sphere Server is offline';

            return $instance;
        }
        self::$error = false;

        if (self::$installLink != null) {
            $link = self::$installLink;
        } else {
            $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
        }
        $json = json_encode($arr) ?? "";
        $url = $link . $url ?? board::error("Не указан URL запроса");
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://' . $url;
        }
        $ch = curl_init();
        $headers = [
            'Content-Type: application/json',
            'Authorization: BoberKurwa',
        ];
        if (self::$user !== null) {
            if (self::$server_id == null) {
                self::$server_id = self::$user->getServerId();
            }
            // Данные для аутентификации
            $headers[] = "User-Id: " . self::$user->getId();
            $headers[] = "User-Email: " . self::$user->getEmail();
            $headers[] = "User-Server-Id: " . self::$server_id;
            $headers[] = "User-IP: " . self::$user->getIp();
            $headers[] = "User-isBot: " . 0;
        } else {
            $server_id = \Ofey\Logan22\model\server\server::getLastServer()?->getId();
            if(isset($_SESSION['server_id'])){
                $server_id = $_SESSION['server_id'];
            }
            $headers[] = "User-Id: " . 0;
            if (type::SPHERE_INSTALL != $url) {
                $headers[] = "User-Server-Id: " . $server_id;
            }
        }

        $headers[] = "Token: " . self::getToken();
        $host = $_SERVER['HTTP_HOST'];
        if (empty($host) || !self::is_valid_domain(parse_url($host, PHP_URL_HOST))) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
        $parsedHost = preg_replace('/:\d+$/', '', $parsedHost);
        $headers[] = "Domain: " . $parsedHost;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true); // Указываем, что это POST запрос
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json); // Передаем JSON данные
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Возвращаем результат в переменную
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        self::$countRequest++;
        $response = curl_exec($ch);
        if ($response === false) {
            self::$codeError = "sphereapi_unavailable";
            self::$error = 'Ошибка соединения с Sphere API. Попробуйте еще раз. Возможно сервер на перезагрузке либо указаны неверные данные подключения к Sphere API. Если ошибка повторится, обратитесь в службу поддержки.';
            self::$isOfflineServer = true;

            return $instance;
        }

        if (curl_errno($ch)) {
            sql::sql("DELETE FROM `server_cache` WHERE `type` = 'sphereServer'");
            sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (0, ?, ?, ?)", [
                "sphereServer",
                json_encode(["connect" => false, "error" => "Not connect to Sphere Server"], JSON_UNESCAPED_UNICODE),
                time::mysql(),
            ]);
            self::$isOfflineServer = true;

            return $instance;
        }
        curl_close($ch);

        $response = json_decode($response, true) ?? false;
        $instance->response = $response;
        if ($response === false) {
            return $instance;
        }

        if (isset($response['error'])) {
            self::$error = $response['error'];
            if (isset($response['code'])) {
                self::$codeError = $response['code'];
            }
            if (self::$showError) {
                board::error($response['error']);
            }
        }

        return $instance;
    }

    /**
     * Отправляет кастомный GET-запрос на Sphere API и возвращает сырой ответ,
     * предназначенный для скачивания файлов.
     * Повторяет всю логику авторизации из sendCustom.
     *
     * @param string $url - Путь запроса, например, /api/download/launcher/file.exe
     * @return array{content: string|false, http_code: int, error: string}
     */
    public static function sendCustomDownload(string $url): array
    {
        self::isOffline();
        if (self::$isOfflineServer === true) {
            return [
                'content' => false,
                'http_code' => 0,
                'error' => 'Sphere Server is offline',
            ];
        }

        // --- Логика формирования URL и заголовков (полностью скопирована из sendCustom) ---
        self::$error = false;
        if (self::$installLink != null) {
            $link = self::$installLink;
        } else {
            $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
        }

        $port = config::load()->sphereApi()->getPort();
        $protocol = ($port >= 61400 && $port <= 62000) ? 'https' : 'http';

        $full_url = "{$protocol}://{$link}{$url}";

        $ch = curl_init();
        $headers = [
            'Authorization: BoberKurwa',
        ];
        if (self::$user !== null) {
            if (self::$server_id == null) {
                self::$server_id = self::$user->getServerId();
            }
            $headers[] = "User-Id: " . self::$user->getId();
            $headers[] = "User-Email: " . self::$user->getEmail();
            $headers[] = "User-Server-Id: " . self::$server_id;
            $headers[] = "User-IP: " . self::$user->getIp();
            $headers[] = "User-isBot: " . 0;
        } else {
            $server_id = \Ofey\Logan22\model\server\server::getLastServer()?->getId();
            if(isset($_SESSION['server_id'])){
                $server_id = $_SESSION['server_id'];
            }
            $headers[] = "User-Id: " . 0;
            if (type::SPHERE_INSTALL != $url) {
                $headers[] = "User-Server-Id: " . $server_id;
            }
        }

        $headers[] = "Token: " . self::getToken();
        $host = $_SERVER['HTTP_HOST'];
        if (empty($host) || !self::is_valid_domain(parse_url($host, PHP_URL_HOST))) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
        $parsedHost = preg_replace('/:\d+$/', '', $parsedHost);
        $headers[] = "Domain: " . $parsedHost;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Увеличенный таймаут для скачивания
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        if ($protocol === 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        self::$countRequest++;
        $responseContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($responseContent === false) {
            self::$isOfflineServer = true;
            self::$error = 'Ошибка соединения со Sphere API при скачивании файла: ' . $curlError;
        }

        curl_close($ch);

        return [
            'content' => $responseContent,
            'http_code' => $httpCode,
            'error' => $curlError,
        ];
    }

    public static function isOffline(): ?bool
    {
        if (self::$tokenDisable) {
            return null;
        }

        if (self::$isOfflineServer !== null) {
            return self::$isOfflineServer;
        }

        $serverCache = sql::getRow("SELECT `date_create` FROM `server_cache` WHERE `type` = 'sphereServer'");

        // Проверяем результат запроса
        if ($serverCache === false || !isset($serverCache['date_create'])) {
            return self::$isOfflineServer = false;
        }

        // Защита от null в date_create
        $dateCreate = $serverCache['date_create'] ?? '';
        if (empty($dateCreate)) {
            return self::$isOfflineServer = false;
        }

        // Проверяем что прошло более 5 сек
        $timeDiff = time::diff($dateCreate, time::mysql());
        if ($timeDiff < 1) {
            return self::$isOfflineServer = true;
        }

        // Если прошло более минуты, удаляем данные из кэша
        sql::sql("DELETE FROM `server_cache` WHERE `type` = 'sphereServer'");

        return self::$isOfflineServer = false;
    }

    public static function getToken(): string
    {
        if (self::$tokenDisable) {
            return "disable";
        }
        if (self::$token != null) {
            return self::$token;
        }
        //Проверка наличия файла с токеном
        if (file_exists(fileSys::get_dir("/data/token.php"))) {
            require_once(fileSys::get_dir("/data/token.php"));
            self::$token = defined('__TOKEN__') ? (string)constant('__TOKEN__') : "disable";
        } else {
            self::$token = "disable";
        }

        return self::$token;
    }

    private static function is_valid_domain($domain)
    {
        return (bool)filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    public static function setServer(int $server_id)
    {
        self::$server_id = $server_id;
    }

    public static function getCountRequest(): int
    {
        return self::$countRequest;
    }

    public function show($showError = true): self
    {
        if ($showError and self::$error !== false) {
            board::error(self::$error);
        }

        return $this;
    }

    public function showErrorPageIsOffline($showPageError = true): self
    {
        if (self::$isOfflineServer) {
            tpl::display("/error/offlineSphereApi.html");
            exit;
        }
        return $this;
    }

    public function getResponse(): array|bool|null
    {
        return $this->response;
    }

}