<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 09.09.2022 / 18:53:03
 */

namespace Ofey\Logan22\model\server;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class server
{

    /**
     * @var serverModel[]|null
     */
    private static ?array $server_info = null;

    private static array $get_default_desc_page_id = [];
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
    public static function getServerIds(): ?array
    {
        if(self::getServerAll()==null){
            return null;
        }
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

    public static function getDefault(): serverModel|array|null
    {
        return self::getServer(server::getDefaultServer());
    }

    /**
     * @param $id
     *
     * @return serverModel|null
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
        if (config::load()->enabled()->isEnableEmulation()) {
            $data = include_once "src/component/emulation/data/data.php";
            foreach ($data as $server) {
                $serverId = $server['id'];
                $page = self::get_default_desc_page_id($serverId);
                self::$server_info[$serverId] = new serverModel($server, [], $page);
                $serverStatus = new serverStatus();
                $serverStatus->setServerId($serverId);
                $serverStatus->setLoginServer($server['serverStatus']['loginserver']);
                $serverStatus->setGameServer($server['serverStatus']['gameserver']);
                $serverStatus->setGameServerRealConnection($server['serverStatus']['gameserver']);
                $serverStatus->setOnline($server['serverStatus']['online'] ?? 200);
                $serverStatus->setEnable(filter_var($server['serverStatus']['isEnableStatus'] ?? true, FILTER_VALIDATE_BOOLEAN));

                self::$server_info[$serverId]->serverStatus = $serverStatus;
            }
        } else {

            // Получаем все серверы из базы данных
            $servers = sql::getRows("SELECT * FROM `servers`");
            if($servers==[]){
                return null;
            }

            foreach ($servers as $server) {
                $server = json_decode($server['data'], true);
                $serverId = $server['id'];
                $server_data = sql::getRows("SELECT * FROM `server_data` WHERE `server_id` = ?", [$serverId]);
                $page = self::get_default_desc_page_id($serverId);
                self::$server_info[$serverId] = new serverModel($server, $server_data, $page);
            }

            if(!user::self()->isAdmin()){
                foreach(self::$server_info AS $id=>$server){
                    if($server->isDisabled()){
                       unset(self::$server_info[$id]);
                    }
                }
            }

            if(self::$server_info==[]){
                return null;
            }

            self::loadStatusServer(null);

            if (!empty(self::$server_info)) {
                foreach (self::$server_info as $info) {
                    foreach (self::$arrayStatus as $status) {
                        if ($status->getServerId() == $info->getId()) {
                            $info->serverStatus = $status;
                        }
                    }
                }
                uasort(self::$server_info, function ($a, $b) {
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

    static public function loadStatusServer($status = null, $isAdmin = false): void
    {
        if ($status != null) {
            $serverStatus = new serverStatus();
            $serverStatus->setEnable($status['isEnableStatus']);
            $serverStatus->setServerId($status['id']);
            $serverStatus->setLoginServer($status['loginServer'] ?? false);
            $serverStatus->setGameServer($status['gameServer'] ?? false);
            $serverStatus->setGameServerRealConnection($status['gameServer'] ?? false);
            $serverStatus->setEnableLoginServerMySQL($status['loginServerDB'] ?? false);
            $serverStatus->setEnableGameServerMySQL($status['gameServerDB'] ?? false);
            $serverStatus->setOnline($status['online'] ?? 0);
            $serverStatus->setGameIPStatusServer($status['gameServerIP'] ?? '0.0.0.0');
            $serverStatus->setGamePortStatusServer($status['gameServerPort'] ?? -1);
            $serverStatus->setLoginIPStatusServer($status['loginServerIP'] ?? '0.0.0.0');
            $serverStatus->setLoginPortStatusServer($status['loginServerPort'] ?? -1);
            $serverStatus->save();
            self::$arrayStatus[$status['id']] = $serverStatus;
            return;
        }
        if (self::$firstLoadServer) {
            return;
        }
        self::$firstLoadServer = true;
        $update = false;
        
        // Получаем все серверы
        $servers = self::getServerAll();
        if (!$servers) {
            return;
        }
        
        // Проверяем кэш для каждого сервера
        foreach ($servers as $serverId => $serverInfo) {
            $cacheDir = fileSys::get_dir('uploads/cache/server/' . $serverId);
            $needsUpdate = true;
            
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*.json');
                if (!empty($files)) {
                    rsort($files);
                    $latestFile = $files[0];
                    $unixTime = (int)basename($latestFile, '.json');
                    $currentTime = time();
                    $totalSeconds = $currentTime - $unixTime;
                    if ($totalSeconds < 60) {
                        $cacheData = json_decode(file_get_contents($latestFile), true);
                        if ($cacheData) {
                            $serverStatus = new serverStatus();
                            $serverStatus->setServerId($serverId);
                            $serverStatus->setEnable($cacheData['isEnable']);
                            $serverStatus->setLoginServer($cacheData['loginServer'] ?? false);
                            $serverStatus->setGameServer($cacheData['gameServer'] ?? false);
                            $serverStatus->setGameServerRealConnection($cacheData['gameServerRealConnection'] ?? false);
                            $serverStatus->setEnableLoginServerMySQL($cacheData['loginServerDB'] ?? false);
                            $serverStatus->setEnableGameServerMySQL($cacheData['gameServerDB'] ?? false);
                            $serverStatus->setOnline($cacheData['online'] ?? 0, true);
                            $serverStatus->setGameIPStatusServer($cacheData['gameServerIP'] ?? '0.0.0.0');
                            $serverStatus->setGamePortStatusServer($cacheData['gameServerPort'] ?? -1);
                            $serverStatus->setLoginIPStatusServer($cacheData['loginServerIP'] ?? '0.0.0.0');
                            $serverStatus->setLoginPortStatusServer($cacheData['loginServerPort'] ?? -1);
                            self::$arrayStatus[$serverId] = $serverStatus;
                            $needsUpdate = false; 
                        }
                    }
                }
            }
            if ($needsUpdate) {
                $serverStatusAll = \Ofey\Logan22\component\sphere\server::send(type::GET_STATUS_SERVER_ALL, [])->getResponse();
                if(isset($serverStatusAll['license'])){
                        $jsonFile = fileSys::get_dir('uploads/cache/license.json');
                        $data = [
                            'license' => $serverStatusAll['license'],
                        ];
                        file_put_contents($jsonFile, json_encode($data));
                }
                if (isset($serverStatusAll['status'])) {
                    $serverStatusAll = $serverStatusAll['status'];
                    foreach ($serverStatusAll as $server_id => $status) {
                        $serverStatus = new serverStatus();
                        $serverStatus->setServerId($server_id);
                        $serverStatus->setEnable($status['isEnableStatus']);
                        $serverStatus->setLoginServer($status['loginServer'] ?? false);
                        $serverStatus->setGameServer($status['gameServer'] ?? false);
                        $serverStatus->setGameServerRealConnection($status['gameServer'] ?? false);
                        $serverStatus->setEnableLoginServerMySQL($status['loginServerDB'] ?? false);
                        $serverStatus->setEnableGameServerMySQL($status['gameServerDB'] ?? false);
                        $serverStatus->setOnline($status['online'] ?? 0);
                        $serverStatus->setGameIPStatusServer($status['gameServerIP'] ?? '0.0.0.0');
                        $serverStatus->setGamePortStatusServer($status['gameServerPort'] ?? -1);
                        $serverStatus->setLoginIPStatusServer($status['loginServerIP'] ?? '0.0.0.0');
                        $serverStatus->setLoginPortStatusServer($status['loginServerPort'] ?? -1);
                        $serverStatus->save();
                        self::$arrayStatus[$server_id] = $serverStatus;
                    }
                }
            }
        }

    }

    /**
     * Проверка существования включенных серверов
     * @return void
     * @throws Exception
     */
    public static function checkActiveServers(): void
    {
        foreach (server::getServerAll() as $server) {
            if ($server->isEnabled()) {
                return;
            }
        }
        board::error("К сожалению, этот сервер отключен администратором");
    }

    public static function getDefaultServer(): ?int
    {
        $servers = self::getServerAll();
        if (is_array($servers)) {
            foreach ($servers as $server) {
                if ($server->isDefault()) {
                    return $server->getId();
                }
            }
        }

        $lastServer = server::getLastServer();
        if ($lastServer !== null) {
            return $lastServer->getId();
        }

        return null;
    }



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