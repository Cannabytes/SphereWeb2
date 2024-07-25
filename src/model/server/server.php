<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 09.09.2022 / 18:53:03
 */

namespace Ofey\Logan22\model\server;

use DateTime;
use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\base\base;
use Ofey\Logan22\component\cache\cache;
use Ofey\Logan22\component\cache\dir;
use Ofey\Logan22\component\restapi\restapi;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use ReflectionMethod;
use trash\sdb;

class server
{

    /**
     * @var serverModel[]|null
     */
    private static ?array $server_info = null;

    private static array $get_default_desc_page_id = [];

    public static function isServer($id = null): ?serverModel
    {
        return self::$server_info[$id] ?? null;
    }

    /**
     * Возвращает список ID серверов
     */
    public static function getServerIds(): array
    {
        return array_keys(self::getServerAll());
    }

    public static function clearServerInfo(): void
    {
        self::$server_info = null;
    }

    /**
     * @return serverModel[]|null
     * @throws Exception
     */
    public static function getServerAll(): ?array
    {
        if (self::$server_info != null) {
            return self::$server_info;
        }
        self::getServer();
        if (self::$server_info != null) {
            return self::$server_info;
        }

        return null;
    }

    /**
     * @param $id
     *
     * @return serverModel[]|null
     * @throws Exception
     *
     * Функция возвращаем всю инфу о сервере
     */
    public static function getServer($id = null): ?serverModel
    {
        // Если сервер с данным ID уже существует, возвращаем его
        if (isset(self::$server_info[$id])) {
            return self::$server_info[$id];
        }

        // Если self::$server_info не null и запрашиваемый ID не передан, возвращаем текущий сервер
        if (self::$server_info !== null && $id === null) {
            $server = current(self::$server_info);
            return $server instanceof serverModel ? $server : null;
        }

        // Получаем все серверы из базы данных
        $servers = sql::getRows("SELECT * FROM `servers`");
        foreach ($servers as $server) {
            $server = json_decode($server['data'], true);
            $serverId = $server['id'];
            $server_data = sql::getRows("SELECT * FROM `server_data` WHERE `server_id` = ?", [$serverId]);
            $page = self::get_default_desc_page_id($serverId);
            self::$server_info[$serverId] = new serverModel($server, $server_data, $page);
        }

        // Если запрашиваемый ID передан, возвращаем соответствующий сервер или последний сервер из массива
        if ($id !== null) {
            //Проверить что в $server_info есть записи
            if (empty(self::$server_info)) {
                return null;
            }
            return self::$server_info[$id] ?? (end(self::$server_info) instanceof serverModel ? end(self::$server_info) : null);
        }

        // Если self::$server_info не пуст, возвращаем первый сервер, иначе null
        return !empty(self::$server_info) ? current(self::$server_info) : null;
    }

    public static function get_default_desc_page_id($server_id)
    {
        if (self::$get_default_desc_page_id == []) {
            self::$get_default_desc_page_id = sql::getRows("SELECT server_id, lang, page_id, `default` FROM server_description");
        }
        //Возращаем ID страницы описания согласно языка пользователя
        foreach (self::$get_default_desc_page_id as $row) {
            if ($server_id == $row['server_id']) {
                if ($row['lang'] == config::load()->lang()->lang_user_default()) {
                    return $row['page_id'];
                }
            }
        }
        //Если нет такой страницы, вернем ID страницы по умолчанию
        foreach (self::$get_default_desc_page_id as $row) {
            if ($server_id == $row['server_id']) {
                if ($row['default']) {
                    return $row['page_id'];
                }
            }
        }

        //Если ничего не найдено, вернем NULL
        return null;
    }

    //Кол-во серверов

    /**
     * @return serverModel|null
     * @throws Exception
     */
    public static function getLastServer(): ?serverModel
    {
        if (self::$server_info !== null && is_array(self::$server_info) && !empty(self::$server_info)) {
            $lastServer = end(self::$server_info);
            return $lastServer instanceof serverModel ? $lastServer : null;
        }

        self::getServer();

        // Убедитесь, что self::$server_info инициализировано как массив
        if (self::$server_info === null || !is_array(self::$server_info)) {
            self::$server_info = [];
        }

        $lastServer = end(self::$server_info);
        return $lastServer instanceof serverModel ? $lastServer : null;
    }


    //Страница по умолчанию

    public static function server_info($id = null): bool|array
    {
        return server::getServer($id);
    }

    //Возращаем ID страницы описания

    public static function get_count_servers(): int
    {
        $server = server::getServerAll();
        if ($server == null) {
            return 0;
        }
        return count($server);
    }

    /*
     * Проверка на существования сервера во внутреннем реестре
     */

    public static function preAcross(dir $dir, int $server_id = 0, string $name = null, $second = 60): mixed
    {
        $server_info = server::exist_server_registry($server_id);
        if ( ! $server_info) {
            return [$server_info, false];
        }

        return [
          $server_info,
          cache::read($dir->show_dynamic($server_info->getId(), $name), second: $second),
        ];
    }

    public static function exist_server_registry(int $server_id = 0): bool|serverModel
    {
        if ($server_id == null) {
            $server_id = user::self()->getServerId();
            if ( ! $server_id) {
                return false;
            }
        }

        return self::getServer($server_id);
    }

    //Возвращает экземпляром класса

    public static function across($collection_name, $server_info, $prepare = [])
    {
        if ($server_info['rest_api_enable']) {
            $data = restapi::Send(
              $server_info,
              $collection_name,
              $prepare,
            );
            if ($data == "false") {
                return false;
            }

            return json_decode($data, true)[0];
        }
        $ok = self::acrossBase($collection_name, $server_info, $prepare);
        if ( ! $ok) {
            return $ok;
        }

        return $ok->fetch();
    }

    // Запрос с возвращением единственной записи

    /**
     * @throws Exception
     */
    private static function acrossBase($collection_name, serverModel $server_info, $prepare = [])
    {
        $sqlQuery   = base::get_sql_source($server_info->getCollection(), $collection_name);
        $reflection = new ReflectionMethod($server_info->getCollectionSqlBaseName(), $collection_name);
        $attributes = $reflection->getAttributes();

        $inGameDBQuery = "game";
        foreach ($attributes as $attr) {
            if ('db' == basename($attr->getName())) {
                $inGameDBQuery = $attr->getArguments()[0];
            }
        }
        if (gettype($prepare) == "string") {
            $prepare = [$prepare];
        }
        if ($inGameDBQuery == "login") {
            sdb::set_type('login');
            $ok = sdb::set_connect(
              $server_info->getLoginHost(),
              $server_info->getLoginUser(),
              $server_info->getLoginPassword(),
              $server_info->getLoginName(),
              $server_info->getLoginPort()
            );
        } else {
            sdb::set_type('game');
            $ok = sdb::set_connect(
              $server_info->getGameHost(),
              $server_info->getGameUser(),
              $server_info->getGamePassword(),
              $server_info->getGameName(),
              $server_info->getGamePort()
            );
        }
        if ( ! $ok) {
            return $ok;
        }

        return sdb::run($sqlQuery, $prepare);
    }

    /**
     * Запрос с возвращением всего массива
     *
     * @throws Exception
     */
    public static function acrossAll($collection_name, serverModel $server_info, $prepare = [])
    {
        if ($server_info->getRestApiEnable()) {
            $data = restapi::Send(
              $server_info,
              $collection_name,
              $prepare,
            );
            if ($data == "false") {
                return false;
            }

            return json_decode($data, true);
        }
        $ok = self::acrossBase($collection_name, $server_info, $prepare);
        if ( ! $ok) {
            return false;
        }
        try {
            return $ok->fetchAll();
        } catch (\Error $e) {
            echo "Error: " . $e->getMessage();

            return null;
        }
    }

    //TODO: Проверка TRUE/FALSE исполнения запроса
    public static function acrossBool($collection_name, $server_info, $prepare = []) {}

    //TODO: Для записи в базу
    public static function acrossInsert($collection_name, $server_info, $prepare = []) {}

    public static function get_data($key)
    {
        $server_info = self::getServer(user::getUserId()->getServerId());
        if ( ! $server_info || ! isset($server_info['data'])) {
            return false;
        }

        foreach ($server_info['data'] as $data) {
            if ($data['key'] == $key) {
                return $data['val'];
            }
        }

        return false;
    }

    // $showError - показывать сообщение ошибки, если сервер ещё не запустился.
    public static function is_start_server($server_info, $showError = true): bool
    {
        if (isset($server_info['date_start_server'])) {
            if (new DateTime('now') <= new DateTime($server_info['date_start_server'])) {
                if ($showError) {
                    board::error("Покупка будет возможна после запуска сервера");
                }

                return false;
            }
        }

        return true;
    }

}