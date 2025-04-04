<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 17.08.2022 / 12:13:03
 */

namespace Ofey\Logan22\controller\admin;

use DateTime;
use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\chronicle\client;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\servername\servername;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\model\admin\server;
use Ofey\Logan22\model\admin\update_cache;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\config\template;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\install\install;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class options
{

    //POST
    public static function delete_server(): void
    {
        $server_id = $_POST['serverId'] ?? board::error("Server id is empty");

        $serverInfo = \Ofey\Logan22\model\server\server::getServer($server_id);
        if ($serverInfo->getId() != $server_id) {
            redirect::location("/admin/server/list");
        }

        $servers_id = \Ofey\Logan22\component\sphere\server::send(type::SERVER_LIST)->show(false)->getResponse();
        if (isset($servers_id['error']) or $servers_id === null) {
            $sphereAPIError = true;
            $servers_id['servers'] = [];
        }

        foreach ($servers_id['ids'] as $sid) {
            if ($serverInfo->getId() == $sid) {
                $response = \Ofey\Logan22\component\sphere\server::send(type::DELETE_SERVER, [
                    "id" => (int)$sid,
                ])->show()->getResponse();
                if ($response['success']) {
                    try {
                        sql::run("DELETE FROM `servers` WHERE `id` = ?", [$sid]);
                        sql::run("DELETE FROM `server_data` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `settings` WHERE `serverId` = ?", [$sid]);
                        sql::run("DELETE FROM `donate` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `bonus_code` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `player_accounts` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `shop_items` WHERE `serverId` = ?", [$sid]);
                        sql::run("DELETE FROM `startpacks` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `server_description` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `server_cache` WHERE `server_id` = ?", [$sid]);
                        sql::run("DELETE FROM `warehouse` WHERE `server_id` = ?", [$sid]);
                    } catch (\Exception $e) {
                        board::error($e->getMessage());
                    }
                    board::redirect("/admin/server/list");
                    board::success("Сервер удален");
                }
            }
        }

        sql::run("DELETE FROM `servers` WHERE `id` = ?", [$server_id]);
        board::redirect("/admin/server/list");
        board::success("Сервер удален");
    }

    //POST - Регистрация сервера
    public static function create_server(): void
    {
        $loginArr = [];

        $name = $_POST['name'] ?? "Bartz_" . random_int(1, 999);
        $rateExp = (int)$_POST['rateExp'] ?? 1;
        $rateSp = (int)$_POST['rateSp'] ?? 1;
        $rateAdena = (int)$_POST['rateAdena'] ?? 1;
        $rateDrop = (int)$_POST['rateDrop'] ?? 1;
        $rateSpoil = 1;
        $version_client = $_POST['version_client'] ?? board::error("No select  client");
        $collection = $_POST['collection'] ?? board::error("No select L2j SQL base");
        $dateStartServer = $_POST['dateStartServer'] ?? null;
        $knowledgeBase = $_POST['knowledge_base'] ?? null;
        $maxOnline = $_POST['max_online'] ?? 200;
        $timezone = $_POST['timezone_server'] ?? "Africa/Abidjan";
        $resetHWID = $_POST['resetHWID'] ?? false;
        $showStatusBar = filter_var($_POST['showStatusBar'] ?? false, FILTER_VALIDATE_BOOL);
        $enableStatusServer = filter_var($_POST['enableStatusServer'] ?? false, FILTER_VALIDATE_BOOL);
        $statusLoginServerIP = $_POST['statusLoginServerIP'] ?? "";
        $statusLoginServerPort = (int)$_POST['statusLoginServerPort'] ?? 2106;
        $statusGameServerIP = $_POST['statusGameServerIP'] ?? "";
        $statusGameServerPort = (int)$_POST['statusGameServerPort'] ?? 7777;

        if (!filter_var($statusLoginServerIP, FILTER_VALIDATE_IP)) {
            if ($enableStatusServer) {
                board::error("IP адрес для проверки логин-сервера недействителен.");
            } else {
                $statusLoginServerIP = "0.0.0.0";
            }
        }
        if (!filter_var($statusGameServerIP, FILTER_VALIDATE_IP)) {
            if ($enableStatusServer) {
                board::error("IP адрес для проверки гейм сервера недействителен.");
            } else {
                $statusGameServerIP = "0.0.0.0";
            }
        }


        if (isset($_POST['loginserver'])) {
            $loginserver = (int)$_POST['loginserver'] ?? 0;
        } else {
            board::error("Выберите подключение к БД LoginServer");
        }

        if (isset($_POST['gameserver'])) {
            $gameserver = (int)$_POST['gameserver'] ?? 0;
        } else {
            board::error("Добавьте данные БД к GameServer");
        }

        $statusServer = [
            "enable" => $enableStatusServer,
            "statusLoginServerIP" => $statusLoginServerIP,
            "statusLoginServerPort" => $statusLoginServerPort,
            "statusGameServerIP" => $statusGameServerIP,
            "statusGameServerPort" => $statusGameServerPort,
        ];

        $data = \Ofey\Logan22\component\sphere\server::send(type::ADD_NEW_SERVER, array_merge([
            "loginServerID" => $loginserver,
            "gameServerID" => $gameserver,
            "collection" => $collection,
            "statusServer" => $statusServer,
        ], $loginArr))->show()->getResponse();

        if (isset($data['id'])) {
            $id = $data['id'];
            $loginServerID = $data['loginServerID'];
            $gameServerID = $data['gameServerID'];

            $data = [
                "id" => $id,
                "login_id" => $loginServerID,
                "game_id" => $gameServerID,
                "name" => $name,
                "rateExp" => $rateExp,
                "rateSp" => $rateSp,
                "rateAdena" => $rateAdena,
                "rateDrop" => $rateDrop,
                "rateSpoil" => $rateSpoil,
                "chronicle" => $version_client,
                "collection" => $collection,
                "showStatusBar" => $showStatusBar,
                "statusServer" => $statusServer,
                "dateStartServer" => $dateStartServer,
                "knowledgeBase" => fileSys::modifyString($knowledgeBase),
                "maxOnline" => $maxOnline,
                "timezone" => $timezone,
                "resetHWID" => $resetHWID,
            ];

            sql::run("INSERT INTO `servers` (`id`, `data`) VALUES (?, ?)", [$id, json_encode($data)]);

            board::redirect("/admin/server/set/donate/{$id}");
            board::success(lang::get_phrase(243));
        }

        board::error(lang::get_phrase("error"));
    }

    // Обновление коллекции
    static public function updateCollection(): void
    {
        $platform = $_POST['platform'];
        $version_client = $_POST['version_client'];
        $serverId = (int)$_POST['serverId'];
        $collection = $_POST['collection'];
        $response = \Ofey\Logan22\component\sphere\server::send(type::UPDATE_COLLECTION, [
            "id" => $serverId,
            "name" => $collection,
        ])->show(true)->getResponse();
        if ($response['success']) {
            $server = \Ofey\Logan22\model\server\server::getServer((int)$_POST['serverId']);
            $server->setChronicle($_POST['version_client']);
            $server->setCollection($_POST['collection']);
            $server->save();

            //Сохраним во внутренней бд
            $post = json_encode([
                'platform' => $platform,
                'version_client' => $version_client,
                'collection' => $collection,
            ], JSON_UNESCAPED_UNICODE);
            sql::sql("DELETE FROM `server_cache` WHERE `server_id` = ? AND `type` = 'collection_data'", [$serverId]);
            sql::sql("INSERT INTO `server_cache` ( `server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)", [
                $serverId,
                'collection_data',
                $post,
                time::mysql(),
            ]);

            board::success("Коллекция обновлена");
        }
    }

    static public function server_edit($server_id = null): void
    {
        if ($server_id == null) {
            redirect::location("/admin/server/list");
        }
        $server = \Ofey\Logan22\model\server\server::getServer($server_id);
        $database = \Ofey\Logan22\component\sphere\server::send(type::GET_DATABASE_LIST)->show()->getResponse();

        $defaultDB = $database['defaultDB'];
        $gameServers = $database['gameservers'];
        $loginServers = $database['loginservers'];


        foreach ($defaultDB as $db) {
            if ($db['id'] == $server->getId()) {
                foreach ($loginServers as &$loginServer) {
                    if ($loginServer['id'] == $db['loginServerID']) {
                        $loginServer['default'] = true;
                    }
                }
                foreach ($gameServers as &$gameserver) {
                    if ($gameserver['id'] == $db['gameServerID']) {
                        $gameserver['default'] = true;
                    }
                }
            }
        }

        $collections = \Ofey\Logan22\component\sphere\server::sendCustom("/api/server/collection/get")->getResponse();

        tpl::addVar([
            'defaultDB' => $defaultDB,
            'gameservers' => $gameServers,
            'loginservers' => $loginServers,
            "chronicleBaseList" => fileSys::dir_list("src/component/image/icon/items"),

            'client_list_default' => client::all(),
            'timezone_list_default' => timezone::all(),

            "server" => $server,
            "collections" => json_encode($collections['collections']),
        ]);
        tpl::display("/admin/server_edit.html");
    }

    static public function server_edit_save()
    {
        $serverId = (int)$_POST['id'] ?? board::error("Server id is empty");
        $name = $_POST['name'] ?? board::error("Set server name");
        $rateExp = $_POST['rateExp'] ?? 1;
        $rateSp = $_POST['rateSp'] ?? 1;
        $rateAdena = $_POST['rateAdena'] ?? 1;
        $rateDrop = $_POST['rateDrop'] ?? 1;
        $version_client = $_POST['version_client'] ?? board::error("Set version game");
        $collection = $_POST['collection'] ?? board::error("Set l2j emulator");
        $showStatusBar = filter_var($_POST['showStatusBar'] ?? false, FILTER_VALIDATE_BOOL);
        $enableStatusServer = filter_var($_POST['enableStatusServer'] ?? false, FILTER_VALIDATE_BOOL);
        $statusLoginServerIP = $_POST['statusLoginServerIP'] ?? "";
        $statusLoginServerPort = (int)$_POST['statusLoginServerPort'] ?? 2106;
        $statusGameServerIP = $_POST['statusGameServerIP'] ?? "";
        $statusGameServerPort = (int)$_POST['statusGameServerPort'] ?? 7777;

        $statusLoginServerIP = preg_replace("/^https?:\/\//", "", $statusLoginServerIP);
        $statusGameServerIP = preg_replace("/^https?:\/\//", "", $statusGameServerIP);

        if (!filter_var($statusLoginServerIP, FILTER_VALIDATE_IP)) {
            $resolvedIP = gethostbyname($statusLoginServerIP);
            if (!filter_var($resolvedIP, FILTER_VALIDATE_IP)) {
                if ($enableStatusServer) {
                    board::error("IP адрес или домен логин-сервера недействителен.");
                }
            }
        }

        if (!filter_var($statusGameServerIP, FILTER_VALIDATE_IP)) {
            $resolvedIP = gethostbyname($statusGameServerIP);
            if (!filter_var($resolvedIP, FILTER_VALIDATE_IP)) {
                if ($enableStatusServer) {
                    board::error("Указанный адрес игрового сервера недействителен.");
                }
            }
        }




        $loginServerID = $_POST['loginserver'] ?? board::error("Set DB LoginServer");
        $gameserverID = $_POST['gameserver'] ?? board::error("Set DB GameServer");
        $dateStartServer = $_POST['dateStartServer'] ?? null;
        $knowledge_base = $_POST['knowledge_base'] ?? board::error("Select the knowledge base for your game version");
        $maxOnline = $_POST['max_online'] ?? 200;
        $timezone = $_POST['timezone_server'] ?? "Europe/Kyiv";
        $resetHWID = $_POST['resetHWID'] ?? false;

        if (!\Ofey\Logan22\model\server\server::getServer($serverId)) {
            board::error("Server not find");
        }

        $statusServer = [
            "enable" => $enableStatusServer,
            "statusLoginServerIP" => $statusLoginServerIP,
            "statusLoginServerPort" => $statusLoginServerPort,
            "statusGameServerIP" => $statusGameServerIP,
            "statusGameServerPort" => $statusGameServerPort,
        ];

        $server = \Ofey\Logan22\model\server\server::getServer($serverId);

        $data = [
            "id" => $serverId,
            "login_id" => $loginServerID,
            "game_id" => $gameserverID,
            "name" => $name,
            "rateExp" => $rateExp,
            "rateSp" => $rateSp,
            "rateAdena" => $rateAdena,
            "rateDrop" => $rateDrop,
            "chronicle" => $version_client,
            "collection" => $collection,
            "showStatusBar" => $showStatusBar,
            "statusServer" => $statusServer,
            "dateStartServer" => $dateStartServer,
            "knowledgeBase" => fileSys::modifyString($knowledge_base),
            "maxOnline" => $maxOnline,
            "timezone" => $timezone,
            "resetHWID" => $resetHWID,
            "default" => $server->isDefault(),
            'position' => $server->getPosition(),
        ];

        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        sql::run(
            "UPDATE `servers` SET `data` = ? WHERE `id` = ?",
            [
                $data,
                $serverId,
            ]
        );
        //Смена ID логин и гейм сервера
        $data = \Ofey\Logan22\component\sphere\server::send(type::CONNECT_DB_UPDATE, [
            "serverId" => $serverId,
            "loginServerID" => (int)$loginServerID,
            "gameServerID" => (int)$gameserverID,
            "collection" => $collection,
            "statusServer" => [
                "enable" => (bool)filter_var($enableStatusServer, FILTER_VALIDATE_BOOLEAN),
                "statusLoginServerIP" => $statusLoginServerIP,
                "statusLoginServerPort" => (int)$statusLoginServerPort,
                "statusGameServerIP" => $statusGameServerIP,
                "statusGameServerPort" => (int)$statusGameServerPort,
            ],
        ])->show()->getResponse();

        if (isset($data["success"])) {
            board::redirect("/admin/server/list");
            board::success("Данные сервера обновлены");
        }

        if (isset($data["error"])) {
            board::error($data['error']);
        }

    }

    static public function servers_show(): void
    {
        validation::user_protection("admin");

        $servers = \Ofey\Logan22\model\server\server::getServerAll();
        if (!$servers) {
            redirect::location("/admin/server/add/new");
        }

        $sphereAPIError = null;
        $info = \Ofey\Logan22\component\sphere\server::send(type::SERVER_FULL_INFO)->show(false)->getResponse();
        if (isset($info['error']) or $info === null) {
            $sphereAPIError = true;
            $info['servers'] = [];
        }

        if(isset($info['servers'])){
            foreach ($info['servers'] as $server) {
                $id = $server['id'];
                \Ofey\Logan22\model\server\server::loadStatusServer($server);
                $getServer = \Ofey\Logan22\model\server\server::getServer($id, $server);
                if ($getServer == null) {
                    $data = [
                        "id" => $id,
                        "name" => "NoName",
                        "rateExp" => 1,
                        "rateSp" => 1,
                        "rateAdena" => 1,
                        "rateDrop" => 1,
                        "rateSpoil" => 1,
                        "chronicle" => "NoSetChronicle",
                        "source" => "",
                        "disabled" => $server['disabled'],
                        "request_count" => $server['request_count'],
                        "count_errors" => $server['count_errors'],
                        "enabled" => $server['enabled'],
                    ];
                    sql::run("INSERT INTO `servers` (`id`, `data`) VALUES (?, ?)", [$id, json_encode($data)]);
                }else{
                    $getServer->setDisabled($server['enabled']);
                }
            }
        }
        if (!$sphereAPIError) {
            tpl::addVar([
                "launcher" => $info['launcher'] ?? null,
                "license" => $info['license'] ?? null,
                "licenseActive" => $info['licenseActive'] ?? null,
                "roulette" => $info['roulette'] ?? null,
                "rouletteActive" => $info['rouletteActive'] ?? false,
                "balance" => (float)$info['balance'] ?? 0,
                "servers" => $info['servers'],
                "sphere_last_commit" => $info['last_commit'],
            ]);
        }

        tpl::addVar([
            'sphereServers' => $servers,
            'client_list_default' => client::all(),
            'timezone_list_default' => timezone::all(),
            "title" => lang::get_phrase(221),
        ]);
        tpl::display("/admin/server_list.html");
    }

    public static function saveGeneral(): void
    {
        $server_id = $_POST['serverId'] ?? board::error("Server id is empty");
        $isDefault = (bool)$_POST['isDefault'] ?? false;

        //Отменяем дефолтные сервера у других
        foreach (\Ofey\Logan22\model\server\server::getServerAll() as $server) {
            $server->setIsDefault(false);
            $server->save();
        }

        $data = json_encode([
            "id" => $server_id,
            "isDefault" => $isDefault,
            "name" => $_POST['name'] ?? board::error("Server name is empty"),
            "rateExp" => $_POST['rateExp'] ?? board::error("Server rateExp is empty"),
            "rateSp" => $_POST['rateSp'] ?? board::error("Server rateSp is empty"),
            "rateAdena" => $_POST['rateAdena'] ?? board::error("Server rateAdena is empty"),
            "rateDrop" => $_POST['rateDrop'] ?? board::error("Server rateDrop is empty"),
            "rateSpoil" => 1,
            "chronicle" => $_POST['version_client'] ?? board::error("Server chronicle is empty"),
            "source" => $_POST['sql_base_source'] ?? board::error("Server source is empty"),
        ], JSON_UNESCAPED_UNICODE);
        sql::run(
            "UPDATE `servers` SET `data` = ? WHERE `id` = ?",
            [
                $data,
                $server_id,
            ]
        );

        if (isset($_POST['statusServer'])) {
            $status = $_POST['statusServer'];
            if ($status['enableStatusServer']) {
                $loginServerPort = filter_var(
                    $status['statusLoginServerPort'],
                    FILTER_VALIDATE_INT
                ) ? (int)$status['statusLoginServerPort'] : -1;
                $gameServerPort = filter_var(
                    $status['statusGameServerPort'],
                    FILTER_VALIDATE_INT
                ) ? (int)$status['statusGameServerPort'] : -1;

                \Ofey\Logan22\component\sphere\server::setServer($server_id);
                $data = \Ofey\Logan22\component\sphere\server::send(type::UPDATE_STATUS_SERVER, [
                    "enableStatusServer" => filter_var($status['enableStatusServer'], FILTER_VALIDATE_BOOLEAN),
                    "statusLoginServerIP" => $status['statusLoginServerIP'] ?? "",
                    "statusLoginServerPort" => $loginServerPort,
                    "statusGameServerIP" => $status['statusGameServerIP'] ?? "",
                    "statusGameServerPort" => $gameServerPort,
                ])->show()->getResponse();
            }
        }

        $server = \Ofey\Logan22\model\server\server::getServer($server_id);

        board::success(lang::get_phrase(217));
    }

    public static function saveOther(): void
    {
        $server_id = $_POST['serverId'];
        $start_time = $_POST['date_start_server'];
        $max_online = $_POST['max_online'] ?? board::error("Server max_online is empty");
        $knowledge_base = $_POST['knowledge_base'] ?? board::error("Server knowlege_base is empty");
        $knowledge_base = fileSys::modifyString($knowledge_base);
        $resetHWID = $_POST['resetHWID'] ?? false;
        $timezone = $_POST['timezone'] ?? board::error("Server timezone is empty");

        if (empty($start_time)) {
            $startDate = time::mysql();
            // У меня дата такого формата May 29, 2024 16:00
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $startDate);
            $startDate = $dateTime->modify('+2 months')->format('Y-m-d H:i:s');
        } else {
            $dateTime = DateTime::createFromFormat('Y-m-d H:i', $start_time);
            $startDate = $dateTime->format('Y-m-d H:i:s');
        }

        //Удаление старых данных
        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", ['date_start_server', $server_id]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id` ) VALUES (?, ?, ?);", ['date_start_server', $startDate, $server_id]);

        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", ['max_online', $server_id]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id` ) VALUES (?, ?, ?);", ['max_online', $max_online, $server_id]);

        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", ['knowledge_base', $server_id]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id` ) VALUES (?, ?, ?);", ['knowledge_base', $knowledge_base, $server_id]
        );

        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", ['resetHWID', $server_id]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id` ) VALUES (?, ?, ?);", ['resetHWID', $resetHWID, $server_id]);

        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", ['timezone', $server_id]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id` ) VALUES (?, ?, ?);", ['timezone', $timezone, $server_id]);

        board::success(lang::get_phrase(217));
    }

    static public function add_new_mysql_connect_to_server(): void
    {
        $type = $_POST['type'] ?? "";
        $host = $_POST['host'] ?? board::error("No select host");
        $port = $_POST['port'] ?? 3306;
        $user = $_POST['user'] ?? board::error("No select user");
        $password = $_POST['password'] ?? "";
        $name = $_POST['name'] ?? board::error("No select name");

        $data = \Ofey\Logan22\component\sphere\server::send(type::ADD_NEW_CONNECT_DB, [
            "type" => $type,
            "host" => $host,
            "port" => $port,
            "user" => $user,
            "password" => $password,
            "name" => $name
        ])->show(true)->getResponse();
        if (isset($data['id'])) {
            board::alert([
                'success' => true,
                'id' => $data['id'],
            ]);
        }
    }

    //Добавление БД логин-сервера

    static public function saveMySQL(): void
    {
        $server_id = $_POST['serverId'];
        $loginserver_id = $_POST['loginserver_id'] ?? 0;
        $login_host = $_POST['login_host'] ?? board::error("No select login host");
        $login_port = $_POST['login_port'] ?? 3306;
        $login_user = $_POST['login_user'] ?? board::error("No select login user");
        $login_password = $_POST['login_password'] ?? "";
        $login_name = $_POST['login_name'] ?? board::error("No select login name");

        $game_host = $_POST['game_host'] ?? board::error("No select game host");
        $game_port = $_POST['game_port'] ?? 3306;
        $game_user = $_POST['game_user'] ?? board::error("No select game user");
        $game_password = $_POST['game_password'] ?? "";
        $game_name = $_POST['game_name'] ?? board::error("No select game name");

        $arr = [
            "loginserver_id" => (int)$loginserver_id,
            "login_host" => $login_host,
            "login_port" => $login_port,
            "login_user" => $login_user,
            "login_password" => $login_password,
            "login_name" => $login_name,

            "game_host" => $game_host,
            "game_port" => $game_port,
            "game_user" => $game_user,
            "game_password" => $game_password,
            "game_name" => $game_name,

            "serverId" => $server_id,
        ];

        $data = \Ofey\Logan22\component\sphere\server::send(type::CONNECT_DB_UPDATE, $arr)->show()->getResponse();

        $save_data_MySQL = filter_var($_POST['save_data_MySQL'], FILTER_VALIDATE_BOOL);
        sql::sql("DELETE FROM `server_cache` WHERE `server_id` = ? AND `type` = 'mysql_data_connect'", [$server_id]);
        if ($save_data_MySQL) {
            $jsonData = json_encode($arr);
            sql::sql("INSERT INTO `server_cache` ( `server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)", [
                $server_id,
                'mysql_data_connect',
                $jsonData,
                time::mysql(),
            ]);
        }

        board::success(lang::get_phrase(217));
    }

    //Сохранение настроек для подключения к базе данных MySQL

    static public function new_server(): void
    {
        validation::user_protection("admin");
        $gameServers = \Ofey\Logan22\component\sphere\server::send(type::GET_GAME_SERVERS)->show()->getResponse();
        $loginServers = \Ofey\Logan22\component\sphere\server::send(type::GET_LOGIN_SERVERS)->show()->getResponse();
        $collections = \Ofey\Logan22\component\sphere\server::sendCustom("/api/server/collection/get")->getResponse();

        tpl::addVar([
            'gameservers' => $gameServers,
            'loginservers' => $loginServers,
            'servername_list_default' => servername::all(),
            'client_list_default' => client::all(),
            'timezone_list_default' => timezone::all(),
            "title" => lang::get_phrase(221),
            "chronicleBaseList" => fileSys::dir_list("src/component/image/icon/items"),
            "collections" => json_encode($collections['collections']),
        ]);
        tpl::display("/admin/server_add.html");
    }

    static public function server_show()
    {
        validation::user_protection("admin");
        tpl::addVar([
            'servername_list_default' => servername::all(),
            'client_list_default' => client::all(),
            'timezone_list_default' => timezone::all(),
        ]);
        tpl::display("/admin/setting.html");
    }

    public static function saveConfigDonate(): void
    {
        $post = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if (!$post) {
            board::error("Ошибка парсинга JSON");
        }
        $data = json_decode($post, true);
        foreach ($data['donateSystems'] as $i => $system) {
            $sysData = reset($system);
            if (!$sysData['inputs']) {
                unset($data['donateSystems'][$i]);
            }
        }
        $server_id = $data['serverId'] ?? board::error("Нет ID сервера");
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);
        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_donate__' AND serverId = ? ", [
            $server_id,
        ]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_donate__', ?, ?, ?)", [
            $post,
            $server_id,
            time::mysql(),
        ]);
        board::success("Настройки сохранены");
    }

    public static function saveConfigReferral(): void
    {
        $post = json_encode($_POST);
        $serverId = $_POST['server_id'];
        if (!$post) {
            board::error("Ошибка парсинга JSON");
        }
        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_referral__' AND serverId = ? ", [
            $serverId,
        ]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_referral__', ?, ?, ?)", [
            $post,
            $serverId,
            time::mysql(),
        ]);
        board::success("Настройки сохранены");
    }

    public static function test_connect_db()
    {
        validation::user_protection("admin");
        if (install::test_connect_mysql(
            $_POST['host'] ?? "127.0.0.1",
            $_POST['port'] ?? 3306,
            $_POST['userModel'] ?? "root",
            $_POST['password'] ?? "",
            $_POST['name'] ?? ""
        )) {
            board::notice(true, lang::get_phrase(222));
        } else {
            board::notice(false, lang::get_phrase(223));
        }
    }

    public static function removeLoginserver(): void
    {
        validation::user_protection("admin");
        $data = \Ofey\Logan22\component\sphere\server::send(type::DELETE_LOGINSERVER, [
            "loginId" => (int)$_POST['loginId'],
        ])->show()->getResponse();
        if (isset($data["success"])) {
            board::alert([
                'message' => "Удалено",
                'ok' => true,
            ]);
        }
    }

    public static function test_connect_db_selected_name(): void
    {
        validation::user_protection("admin");

        $host = $_POST['host'] ?? '';
        $port = $_POST['port'] ?? '';
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';

        // Проверка на пустые значения
        if (empty($host) || empty($port) || empty($user)) {
            board::alert([
                'type' => 'notice',
                'ok' => false,
                'message' => "Отсутствуют необходимые параметры для подключения к базе данных",
            ]);
            return;
        }

        $data = \Ofey\Logan22\component\sphere\server::send(type::CONNECT_DB, [
            "host" => $host,
            "port" => $port,
            "user" => $user,
            "password" => $password,
        ])->getResponse();

        if (isset($data["databases"])) {
            board::alert([
                'type' => 'notice',
                'ok' => true,
                'message' => "Соединение с базой данных успешно",
                'databases' => $data["databases"],
            ]);
            return;
        }

        board::alert([
            'type' => 'notice',
            'ok' => false,
            'message' => "Не удалось соединиться с базой данных",
        ]);
    }

    //Проверка соединения с базой данных игрового сервера MySQL

    public static function server_list()
    {
        validation::user_protection("admin");
        tpl::display("/admin/server/servers.html");
    }

    public static function description_save()
    {
        validation::user_protection("admin");
        server::add_description();
    }

    public static function description_default_page_save()
    {
        validation::user_protection("admin");
        server::description_default();
    }

    public static function cache_save()
    {
        validation::user_protection("admin");
        update_cache::save();
    }

    public static function getTemplateInfo()
    {
        $template = $_POST['template'] ?? board::error("No select template");
        $readmeJson = "template/{$template}/readme.json";
        $img = "/src/template/sphere/assets/images/none.png";
        if (file_exists($readmeJson)) {
            $jsonContents = file_get_contents($readmeJson);
            echo $jsonContents;
        }
    }

    //Show info about template

    public static function changePositionServer(): void
    {
        if (isset($_POST['positions'])) {
            $positions = $_POST['positions'];
            foreach ($positions as $data) {
                $server_id = $data['id'];
                $position = $data['position'];
                $server = \Ofey\Logan22\model\server\server::getServer($server_id);
                $server->setPosition($position)->save();
            }
            echo 'ok';
        }
    }

    public static function setDefaultServer()
    {
        $serverId = $_POST['id'] ?? board::error("Не получен ID сервера");
        foreach (\Ofey\Logan22\model\server\server::getServerAll() as $server) {
            if ($server->isDefault()) {
                $server->setIsDefault(0);
                $server->save();
            }
            if ($serverId == $server->getId()) {
                $server->setIsDefault(1);
                $server->save();
            }
        }
    }

    static private function filterUniqueIds($objects, $serversFullInfo): array
    {
        // Получаем все ID из первого массива
        $objectIds = array_map(function ($object) {
            return $object->getId(); // Предполагается, что в классе есть метод getId()
        }, $objects);

        // Получаем массив только с ID из $serversFullInfo
        $ids = array_map(function ($server) {
            return $server['id'];
        }, $serversFullInfo);
        // Фильтруем второй массив, исключая ID, которые есть в первом массиве
        $filteredIds = array_filter($ids, function ($id) use ($objectIds) {
            return !in_array($id, $objectIds);
        });

        return $filteredIds;
    }

    static public function getServerFunction(null|int $serverId = null): void
    {
        if ($serverId == null or !\Ofey\Logan22\model\server\server::getServer($serverId)){
            board::error("Сервер не найден");
        }

        tpl::addVar([
            "serverId" => $serverId,
        ]);
        tpl::display("/admin/server-functions.html");
    }

    /**
     * Удаляет предметы из склада по заданным параметрам
     */
    static public function removeItemsWarehouse(): void
    {

        // Получаем данные из тела запроса в формате JSON
        $requestData = json_decode(file_get_contents('php://input'), true);
        // Проверяем наличие и валидность ID сервера
        if (empty($requestData['serverId']) || !is_numeric($requestData['serverId'])) {
            http_response_code(400);
            board::alert([
                'success' => false,
                'error' => 'Некорректный ID сервера'
            ]);
        }

        $serverId = (int)$requestData['serverId'];
        $server = \Ofey\Logan22\model\server\server::getServer($serverId);
        if (!$server) {
            http_response_code(404);
            board::alert([
                'success' => false,
                'error' => 'Сервер не найден'
            ]);
        }

        // Проверяем наличие и валидность массива ID предметов
        $itemIds = $requestData['itemIds'] ?? [];

        // Валидация массива ID предметов
        if (!empty($itemIds) && (!is_array($itemIds) || count(array_filter($itemIds, 'is_numeric')) !== count($itemIds))) {
            http_response_code(400);
            board::alert([
                'success' => false,
                'error' => 'Некорректный формат ID предметов'
            ]);
        }

        try {
            if (!empty($itemIds)) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $params = array_merge($itemIds, [$serverId]);
                $result = sql::run(
                    "DELETE FROM warehouse WHERE item_id IN ($placeholders) AND server_id = ?",
                    $params
                );

                $deletedCount = $result->rowCount();

                board::alert([
                    'success' => true,
                    'message' => "Удалено $deletedCount предметов со склада",
                    'deletedCount' => $deletedCount
                ]);
            } else {
                $result = sql::run("DELETE FROM warehouse WHERE server_id = ?", [$serverId]);
                $deletedCount = $result->rowCount();
                board::alert([
                    'success' => true,
                    'message' => "Склад полностью очищен. Удалено $deletedCount предметов",
                    'deletedCount' => $deletedCount
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            board::alert([
                'success' => false,
                'error' => 'Ошибка при удалении предметов со склада: ' . $e->getMessage()
            ]);
        }
    }

    static public function getAllItemsInWarehouse(): void
    {
        $serverId = $_POST['server_id'];
        $rows = sql::getRows("SELECT `user_id`, `item_id`, `count`, `enchant`, `phrase` FROM `warehouse` WHERE server_id = ?", [$serverId]);
        foreach($rows AS &$item){
            $user_id = $item['user_id'];
            $itemInfo = item::getItem($item['item_id']);
            if (!$itemInfo){
                $itemInfo = new item();
                $itemInfo->setId($item['item_id']);
                $itemInfo->setCount($item['count']);
                $itemInfo->setItemName("No Item Name");
                $itemInfo->setIcon(fileSys::localdir("/uploads/images/icon/NOIMAGE.webp"));
            }
            $item['item_name'] = $itemInfo->getItemName();
            $item['item_icon'] = $itemInfo->getIcon();
            $item['userInfo'] = user::getUserId($user_id)->toArray();
            $item['phrase'] = lang::get_phrase($item['phrase']);
        }
        echo json_encode($rows);
    }


    // Настройки стакуемых предметов сервера
    public static function saveStackItems(): void
    {
        $allowAllItemsStacking = filter_var($_POST['allowAllItemsStacking'], FILTER_VALIDATE_BOOLEAN);
        $allowAllItemsSplitting = filter_var($_POST['allowAllItemsSplitting'], FILTER_VALIDATE_BOOLEAN);
        $stackableItems = $_POST['stackableItems'] ?? [];
        $splittableItems = $_POST['splittableItems'] ?? [];
        \Ofey\Logan22\model\server\server::getServer()->stackableItem()->set($allowAllItemsStacking, $allowAllItemsSplitting, $stackableItems, $splittableItems);
        \Ofey\Logan22\model\server\server::getServer()->save();
        board::notice(true, "Настройки сохранены");
    }

    public static function stackInventoryItems(): void
    {
        user::self()->stackInventoryItems();
    }

    public static function saveRegistrationBonusItems(): void
    {
        $serverId = $_POST['serverId'] ?? -1;
        if(!\Ofey\Logan22\model\server\server::getServer($serverId)){
            board::notice(false, "Сервер не найден");
        }
        $server = \Ofey\Logan22\model\server\server::getServer($serverId);
        $server->bonus()->setRegistrationBonusItems($_POST['enabled'], $_POST['issueAllItems'], $_POST['bonus_items']);
        $server->save();
        board::notice(true, "Настройки сохранены");
    }

}
