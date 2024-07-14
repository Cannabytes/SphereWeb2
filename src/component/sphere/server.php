<?php

namespace Ofey\Logan22\component\sphere;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\userModel;

class server
{

    public static bool $showError = false;

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

    /**
     * Указываем аргументом которые отправятся запросом массив, для того чтоб указывать дополнительные данные, типо ID пользователя и т.д.
     *
     * @param   \Ofey\Logan22\component\sphere\type  $type
     * @param   array                                $json
     *
     * @return bool|array|null
     */
    private null|array|bool $response = null;

    static public function setUser(userModel $user): void
    {
        self::$user = $user;
    }

    public static function isError(): false|string
    {
        return self::$error;
    }

    public static function setShowError(bool $showError): void
    {
        self::$showError = $showError;
    }

    // Отключение отправки токена.

    static public function tokenDisable(bool $on = true): void
    {
        self::$tokenDisable = $on;
    }

    private static function is_valid_domain($domain) {
        return (bool)filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
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



        $json        = json_encode($arr) ?? "";
        $url         = type::url($type) ?? board::error("Не указан URL запроса");
        $ch          = curl_init();
        $headers     = [
          'Content-Type: application/json', // Изменяем тип контента на application/json
          'Authorization: BoberKurwa',
        ];
        if (self::$user !== null) {

            if(self::$server_id==null){
                self::$server_id = self::$user->getServerId();
            }
            // Данные для аутентификации
            $headers[] = "User-Id: " . self::$user->getId();
            $headers[] = "User-Email: " . self::$user->getEmail();
            $headers[] = "User-Server-Id: " . self::$server_id;
            $headers[] = "IP: " . self::$user->getIp();
        } else {
            if (type::SPHERE_INSTALL != $type) {
                $headers[] = "User-Server-Id: " . \Ofey\Logan22\model\server\server::getLastServer()->getId();
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $response = curl_exec($ch);
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
            if (self::$showError or ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
                                                                                    $_SERVER['HTTP_X_REQUESTED_WITH']
                                                                                  ) == 'xmlhttprequest') {
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
        $ss = sql::getRow("SELECT `date_create` FROM `server_cache` WHERE `type` = 'sphereServer'");
        if ($ss === false) {
            return self::$isOfflineServer = false;
        }
        // Проверяем что прошло более 5 сек и если нет, то возвращаем true
        if (time::diff($ss['date_create'], time::mysql()) < 5) {
            return self::$isOfflineServer = true;
        }
        //Если прошло более минуты, тогда удаляем данные из кэша
        sql::sql("DELETE FROM `server_cache` WHERE `type` = 'sphereServer'");

        return self::$isOfflineServer = false;
    }

    private static function getToken(): string
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

    public static function setServer(int $server_id) {
        self::$server_id = $server_id;
    }

    public function show($showError = true): self
    {
        if ($showError and self::$error !== false) {
            board::error(self::$error);
        }

        return $this;
    }

    public function getResponse(): array|bool|null
    {
        return $this->response;
    }

}