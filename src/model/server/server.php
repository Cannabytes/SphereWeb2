<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 09.09.2022 / 18:53:03
 */

namespace Ofey\Logan22\model\server;

use Exception;
use Ofey\Logan22\component\base\base;
use Ofey\Logan22\component\restapi\restapi;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use trash\sdb;

class server
{

    /**
     * @var serverModel[]|null
     */
    private static ?array $server_info = null;

    private static array $get_default_desc_page_id = [];
    private static mixed $arrayServerStatus;
    static private $firstLoadServer = false;
    /**
     * @var serverStatus[]|null
     */
    static private ?array $arrayStatus = [];

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
    public static function getServer($id = null, $serverStatus = null): ?serverModel
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
        if (\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()) {
            $data = include_once "src/component/emulation/data/data.php";
            foreach ($data as $server) {
                $serverId = $server['id'];
                $page = self::get_default_desc_page_id($serverId);
                self::$server_info[$serverId] = new serverModel($server, [], $page);

                $serverStatus = new serverStatus();
                $serverStatus->setServerId($serverId);
                $serverStatus->setLoginServer($server['serverStatus']['loginserver']);
                $serverStatus->setGameServer($server['serverStatus']['loginserver']);
                $serverStatus->setOnline($server['serverStatus']['online'] ?? 200);
                $serverStatus->setEnable(filter_var($server['serverStatus']['isEnableStatus'] ?? true, FILTER_VALIDATE_BOOLEAN));
                serverModel::$arrayServerStatus[$serverId] = $serverStatus;
            }
        } else {

            // Получаем все серверы из базы данных
            $servers = sql::getRows("SELECT * FROM `servers`");
            foreach ($servers as $server) {
                $server = json_decode($server['data'], true);
                $serverId = $server['id'];
                $server_data = sql::getRows("SELECT * FROM `server_data` WHERE `server_id` = ?", [$serverId]);
                $page = self::get_default_desc_page_id($serverId);
                self::$server_info[$serverId] = new serverModel($server, $server_data, $page);
            }
            self::loadStatusServer($serverStatus);
            if(!empty(self::$server_info)){
                foreach(self::$server_info AS $info){
                    foreach(self::$arrayStatus AS $status){
                        if($status->getServerId()==$info->getId()){
                            $info->serverStatus = $status;
                        }
                    }
                }
                uasort(self::$server_info, function($a, $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
            }

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

    static public function loadStatusServer($status = null): void
    {
        if ($status != null){
            $serverStatus = new serverStatus();
            $serverStatus->setServerId($status['id']);
            $serverStatus->setLoginServer($status['loginServer'] ?? false);
            $serverStatus->setGameServer($status['gameServer'] ?? false);
            $serverStatus->setEnableLoginServerMySQL($status['loginServerDB'] ?? false);
            $serverStatus->setEnableGameServerMySQL($status['gameServerDB'] ?? false);
            $serverStatus->setOnline($cache['online'] ?? 0);
            $serverStatus->setGameIPStatusServer($status['gameServerIP'] ?? '0.0.0.0');
            $serverStatus->setGamePortStatusServer($status['gameServerPort'] ?? -1);
            $serverStatus->setLoginIPStatusServer($status['loginServerIP'] ?? '0.0.0.0');
            $serverStatus->setLoginPortStatusServer($status['loginServerPort'] ?? -1);
            $serverStatus->save();
            self::$arrayStatus[$status['id']] = $serverStatus;
        }
        if (self::$firstLoadServer) {
            return;
        }
        self::$firstLoadServer = true;
        $update = false;
        $serverCache = sql::getRows(
            "SELECT `server_id`, `data`, `date_create` FROM `server_cache` WHERE `type` = 'status' ORDER BY `id` DESC", []
        );
        if ($serverCache) {
            /**
             * Если прошло меньше минуты, тогда выводим данные из кэша
             */
            foreach ($serverCache as $cache) {
                $totalSeconds = time::diff(time::mysql(), $cache['date_create']);
                if ($totalSeconds >= config::load()->cache()->getStatus()) {
                    $update = true;
                    break;
                }
            }
            if ($update) {
                $serverStatusAll = \Ofey\Logan22\component\sphere\server::send(type::GET_STATUS_SERVER_ALL, [])->getResponse();
                if(isset($serverStatusAll['status'])){
                    $serverStatusAll = $serverStatusAll['status'];
                    sql::sql("DELETE FROM `server_cache` WHERE `type` = 'status'");

                    $config = config::load();
                    $onlineCheating = $config->onlineCheating()->isEnabled();
                    $minOnline = $config->onlineCheating()->getMinOnlineShow();
                    $maxOnline = $config->onlineCheating()->getMaxOnlineShow();

                    foreach($serverStatusAll AS $server_id => $status){
                        $online = $status['online'] ?? 0;

                        if ($onlineCheating && $online == 0) {
                            $online = mt_rand($minOnline, $maxOnline);
                        }

                        $serverStatus = new serverStatus();
                        $serverStatus->setServerId($server_id);
                        $serverStatus->setLoginServer($status['loginServer'] ?? false);
                        $serverStatus->setGameServer($status['gameServer'] ?? false);
                        $serverStatus->setEnableLoginServerMySQL($status['loginServerDB'] ?? false);
                        $serverStatus->setEnableGameServerMySQL($status['gameServerDB'] ?? false);
                        $serverStatus->setOnline($online);
                        $serverStatus->setGameIPStatusServer($status['gameServerIP'] ?? '0.0.0.0');
                        $serverStatus->setGamePortStatusServer($status['gameServerPort'] ?? -1);
                        $serverStatus->setLoginIPStatusServer($status['loginServerIP'] ?? '0.0.0.0');
                        $serverStatus->setLoginPortStatusServer($status['loginServerPort'] ?? -1);
                        $serverStatus->save();
                        self::$arrayStatus[$server_id] = $serverStatus;
                    }
                }
            } else {
                foreach ($serverCache as $cache) {
                    $server_id = $cache['server_id'];
                    $cache = json_decode($cache['data'], true);
                    $serverStatus = new serverStatus();
                    $serverStatus->setServerId($server_id);
                    $serverStatus->setLoginServer($cache['loginServer'] ?? false);
                    $serverStatus->setGameServer($cache['gameServer'] ?? false);
                    $serverStatus->setEnableLoginServerMySQL($cache['loginServerDB'] ?? false);
                    $serverStatus->setEnableGameServerMySQL($cache['gameServerDB'] ?? false);
                    $serverStatus->setOnline($cache['online'] ?? 0);
                    $serverStatus->setGameIPStatusServer($cache['gameServerIP'] ?? '0.0.0.0');
                    $serverStatus->setGamePortStatusServer($cache['gameServerPort'] ?? -1);
                    $serverStatus->setLoginIPStatusServer($cache['loginServerIP'] ?? '0.0.0.0');
                    $serverStatus->setLoginPortStatusServer($cache['loginServerPort'] ?? -1);
                    self::$arrayStatus[$server_id] = $serverStatus;
                }
            }
        }

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

    public static function getDefaultServer(): ?int
    {
       foreach(self::getServerAll() AS $server){
           if ($server->isDefault()){
               return $server->getId();
           }
       }
        $lastServer = server::getLastServer();
        if ($lastServer !== null) {
            return $lastServer->getId();
        }
        return 0;
    }

    public static function server_info($id = null): bool|array
    {
        return server::getServer($id);
    }

    public static function get_count_servers(): int
    {
        $server = server::getServerAll();
        if ($server == null) {
            return 0;
        }
        return count($server);
    }


}