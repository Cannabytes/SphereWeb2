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
        $json = json_encode($arr) ?? "";
        $url = $link . type::url($type) ?? board::error("Не указан URL запроса");
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
            self::$token = __TOKEN__;
        } else {
            self::$token = "disable";
        }

        return self::$token;
    }

    private static function is_valid_domain($domain)
    {
        return (bool)filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    static public function downloadFile(type $type, array $arr = []): self
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

        // Устанавливаем ссылку для API
        if (self::$installLink != null) {
            $link = self::$installLink;
        } else {
            $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
        }

        // Формируем URL запроса для скачивания файла
        $url = $link . type::url($type) ?? board::error("Не указан URL запроса");

        // Инициализация cURL
        $ch = curl_init();
        $headers = [
            'Authorization: BoberKurwa',
        ];

        // Данные пользователя (если имеются)
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

        $json = json_encode($arr);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json); // Передаем JSON данные
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Получаем результат запроса
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Увеличиваем таймаут для скачивания файла
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); // Поддержка сжатых данных

        // Выполняем запрос
        $fileData = curl_exec($ch);
        // Проверка на ошибки cURL
        if (curl_errno($ch)) {
            board::error("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return $instance;
        }

        // Закрытие соединения cURL
        curl_close($ch);

        // Если файл не получен
        if ($fileData === false) {
            board::error("Ошибка выполнения запроса");
            return $instance;
        }

        // Если файл получен, сохраняем его
        if (empty($fileData)) {
            board::error("Пустой ответ от сервера");
            return $instance;
        }

        //некоторые хосты кэширует файлы, по этому делает все архивы новые
        $rand = mt_rand(1, 9999999);
        $savePath = "uploads/data_{$rand}.zip";
        $result = file_put_contents($savePath, $fileData);

        if ($result === false) {
            board::error("Не удалось сохранить файл на сервере");
            return $instance;
        }

        // Устанавливаем ответ и путь к файлу
        $instance->response = [
            'file' => $savePath
        ];

        return $instance;
    }

    static public function uploadFile($filePath, type $type, array $arr = []): self
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

        // Устанавливаем ссылку для API
        if (self::$installLink != null) {
            $link = self::$installLink;
        } else {
            $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
        }

        // Формируем URL запроса для загрузки файла
        $url = $link . type::url($type) ?? board::error("Не указан URL запроса");

        // Инициализация cURL
        $ch = curl_init();
        $headers = [
            'Authorization: BoberKurwa',
        ];

        // Данные пользователя (если имеются)
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

        // Проверяем существование файла перед отправкой
        if (!file_exists($filePath) || !is_readable($filePath)) {
            board::error("Файл не существует или не доступен для чтения");
            return $instance;
        }

        // Подготавливаем файл для отправки
        $cFile = curl_file_create($filePath); // Создаём объект для файла

        $postFields = [
            'file' => $cFile,
            'data' => json_encode($arr) // дополнительные данные, если есть
        ];

        // Инициализируем запрос cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Получаем ответ
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Увеличиваем таймаут
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); // Поддержка сжатых данных
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); // Отправляем данные

        // Выполняем запрос
        $response = curl_exec($ch);

        // Проверка на ошибки cURL
        if (curl_errno($ch)) {
            board::error("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return $instance;
        }

        // Закрытие соединения cURL
        curl_close($ch);

        // Проверка ответа
        if ($response === false) {
            board::error("Ошибка выполнения запроса");
            return $instance;
        }

        // Устанавливаем ответ
        $instance->response = [
            'status' => 'success',
            'message' => 'Файл успешно загружен на сервер',
            'response' => $response // Ответ от сервера, если есть
        ];

        return $instance;
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