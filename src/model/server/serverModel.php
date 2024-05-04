<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\component\restapi\restapi;
use Ofey\Logan22\model\db\sdb;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\player\player_account;

class serverModel
{

    private ?int $id = null;

    private string $name;

    private int $rateExp = 0;

    private int $rateSp = 0;

    private int $rateAdena = 0;

    private int $rateDrop = 0;

    private int $rateSpoil = 0;

    private string $dateStartServer;

    private string $chronicle;

    private string $loginHost;

    private int $loginPort;

    private string $loginUser;

    private string $loginPassword;

    private string $loginName;

    private string $gameHost;

    private int $gamePort;

    private string $gameUser;

    private string $gamePassword;

    private string $gameName;

    private string $collectionSqlBaseName;

    private int $checkserverModel;

    private string $checkLoginserverModelHost;

    private int $checkLoginserverModelPort;

    private string $checkGameserverModelHost;

    private int $checkGameserverModelPort;

    private int $chatGameEnabled;

    private int $launcherEnabled;

    private string $timezone;

    private int $restApiEnable;

    private string $restApiHostname;

    private int $restApiPort;

    private string $restApiKey;

    /**
     * @var serverDataModel[]
     */
    private array $server_data;

    private ?serverDescriptionModel $page;

    private ?serverStatus $serverStatus = null;

    public function __construct(array $server, array $server_data, ?int $pageId = null)
    {
        $this->id                        = $server['id'] ?? null;
        $this->name                      = $server['name'] ?? '';
        $this->rateExp                   = $server['rateExp'] ?? 0;
        $this->rateSp                    = $server['rateSp'] ?? 0;
        $this->rateAdena                 = $server['rate_adena'] ?? 0;
        $this->rateDrop                  = $server['rateDrop'] ?? 0;
        $this->rateSpoil                 = $server['rateSpoil'] ?? 0;
        $this->dateStartServer           = $server['date_start_server'] ?? '';
        $this->chronicle                 = $server['chronicle'] ?? '';
        $this->loginHost                 = $server['login_host'] ?? '';
        $this->loginPort                 = $server['login_port'] ?? 0;
        $this->loginUser                 = $server['login_user'] ?? '';
        $this->loginPassword             = $server['login_password'] ?? '';
        $this->loginName                 = $server['login_name'] ?? '';
        $this->gameHost                  = $server['game_host'] ?? '';
        $this->gamePort                  = $server['game_port'] ?? 0;
        $this->gameUser                  = $server['game_user'] ?? '';
        $this->gamePassword              = $server['game_password'] ?? '';
        $this->gameName                  = $server['game_name'] ?? '';
        $this->collectionSqlBaseName     = $server['collection_sql_base_name'] ?? '';
        $this->checkserverModel          = $server['check_server_online'] ?? 0;
        $this->checkLoginserverModelHost = $server['check_loginserver_online_host'] ?? '';
        $this->checkLoginserverModelPort = $server['check_loginserver_online_port'] ?? 0;
        $this->checkGameserverModelHost  = $server['check_gameserver_online_host'] ?? '';
        $this->checkGameserverModelPort  = $server['check_gameserver_online_port'] ?? 0;
        $this->chatGameEnabled           = $server['chat_game_enabled'] ?? 0;
        $this->launcherEnabled           = $server['launcher_enabled'] ?? 0;
        $this->timezone                  = $server['timezone'] ?? '';
        $this->restApiEnable             = $server['restApiEnable'] ?? 0;
        $this->restApiHostname           = $server['rest_api_hostname'] ?? '';
        $this->restApiPort               = $server['rest_api_port'] ?? 0;
        $this->restApiKey                = $server['rest_api_key'] ?? '';
        if ($server_data) {
            foreach ($server_data as $data) {
                $this->server_data[] = new serverDataModel($data);
            }
        }
        if($pageId) {
            $this->page = new serverDescriptionModel($pageId);
        }
        return $this;
    }

    /**
     * Проверка, работает ли логин/гейм сервер и получение количества игроков онлайна
     *
     * @return \Ofey\Logan22\model\server\serverStatus
     */
    public function getStatus(): serverStatus
    {
        if ($this->serverStatus !== null) {
            return $this->serverStatus;
        }
        $player_count_online = 0;

        $serverModel = new serverStatus();

        if (@fsockopen($this->getCheckLoginserverModelHost(), $this->getCheckLoginserverModelPort(), $errno, $errstr, 1)) {
            $serverModel->setLoginServer(true);
        }

        if (@fsockopen($this->getCheckGameserverModelHost(), $this->getCheckGameserverModelPort(), $errno, $errstr, 1)) {
            $serverModel->setGameServer(true);
            if ($this->getRestApiEnable()) {
                $data = restapi::Send($this, "count_online_player");
                if ($data == "false") {
                    $player_count_online = 0;
                } else {
                    $sonline             = json_decode($data, true);
                    $player_count_online = $sonline[0]['count_online_player'];
                }
            } else {
                $player_count_online = player_account::extracted("count_online_player");
                if ($player_count_online === false) {
                    $player_count_online = 0;
                } elseif ( ! sdb::is_error()) {
                    $player_count_online = $player_count_online->fetch()["count_online_player"];
                } else {
                    $player_count_online = -1;
                }
            }
        }

        $serverModel->setOnline($player_count_online);
        $this->serverStatus = $serverModel;
        return $serverModel;
    }

    /**
     * @return string
     */
    public function getCheckLoginserverModelHost(): string
    {
        return $this->checkLoginserverModelHost;
    }

    /**
     * @param   string  $checkLoginserverModelHost
     *
     * @return serverModel
     */
    public function setCheckLoginserverModelHost(string $checkLoginserverModelHost): serverModel
    {
        $this->checkLoginserverModelHost = $checkLoginserverModelHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckLoginserverModelPort(): int
    {
        return $this->checkLoginserverModelPort;
    }

    /**
     * @param   int  $checkLoginserverModelPort
     *
     * @return serverModel
     */
    public function setCheckLoginserverModelPort(int $checkLoginserverModelPort): serverModel
    {
        $this->checkLoginserverModelPort = $checkLoginserverModelPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getCheckGameserverModelHost(): string
    {
        return $this->checkGameserverModelHost;
    }

    /**
     * @param   string  $checkGameserverModelHost
     *
     * @return serverModel
     */
    public function setCheckGameserverModelHost(string $checkGameserverModelHost): serverModel
    {
        $this->checkGameserverModelHost = $checkGameserverModelHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckGameserverModelPort(): int
    {
        return $this->checkGameserverModelPort;
    }

    /**
     * @param   int  $checkGameserverModelPort
     *
     * @return serverModel
     */
    public function setCheckGameserverModelPort(int $checkGameserverModelPort): serverModel
    {
        $this->checkGameserverModelPort = $checkGameserverModelPort;

        return $this;
    }

    /**
     * @return int
     */
    public function getRestApiEnable(): int
    {
        return $this->restApiEnable;
    }

    /**
     * @param   int  $restApiEnable
     *
     * @return serverModel
     */
    public function setRestApiEnable(int $restApiEnable): serverModel
    {
        $this->restApiEnable = $restApiEnable;

        return $this;
    }

    public function getServerData($key = null): null|array|serverDataModel
    {
        if ($key == null) {
            return $this->server_data;
        }
        if (empty($this->server_data)) {
            return null;
        }
        foreach ($this->server_data as $data) {
            if ($key == $data->getKey()) {
                return $data;
            }
        }

        return null;
    }

    public function setServerData(array $server_data): void
    {
        $this->server_data = $server_data;
    }

    public function getPage(): ?array
    {
        return $this->page;
    }

    public function setPage(?array $page): void
    {
        $this->page = $page;
    }

    public function save(): void
    {
        if ($this->getId() === null) {
            sql::sql("INSERT INTO `servers` (data) VALUES (?)", ['wait...']);
            $this->setId(sql::lastInsertId());
        }
        $jsonData = json_encode($this->getArrayVar());
        sql::sql("UPDATE `servers` SET `data` = ? WHERE `id` = ?", [
          $jsonData,
          $this->getId(),
        ]);
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param   int  $id
     *
     * @return serverModel
     */
    public function setId(int $id): serverModel
    {
        $this->id = $id;

        return $this;
    }

    public function getArrayVar(): array
    {
        return [
          'id'                            => $this->getId(),
          'name'                          => $this->getName(),
          'rateExp'                       => $this->getRateExp(),
          'rateSp'                        => $this->getRateSp(),
          'rate_adena'                    => $this->getRateAdena(),
          'rate_drop_item'                => $this->getrateDrop(),
          'rateSpoil'                     => $this->getRateSpoil(),
          'date_start_server'             => $this->getDateStartServer(),
          'chronicle'                     => $this->getChronicle(),
          'login_host'                    => $this->getLoginHost(),
          'login_port'                    => $this->getLoginPort(),
          'login_user'                    => $this->getLoginUser(),
          'login_password'                => $this->getLoginPassword(),
          'login_name'                    => $this->getLoginName(),
          'game_host'                     => $this->getGameHost(),
          'game_port'                     => $this->getGamePort(),
          'game_user'                     => $this->getGameUser(),
          'game_password'                 => $this->getGamePassword(),
          'game_name'                     => $this->getGameName(),
          'collection_sql_base_name'      => $this->getCollectionSqlBaseName(),
          'check_server_online'           => $this->getCheckserverModel(),
          'check_loginserver_online_host' => $this->getCheckLoginserverModelHost(),
          'check_loginserver_online_port' => $this->getCheckLoginserverModelPort(),
          'check_gameserver_online_host'  => $this->getCheckGameserverModelHost(),
          'check_gameserver_online_port'  => $this->getCheckGameserverModelPort(),
          'chat_game_enabled'             => $this->getChatGameEnabled(),
          'launcher_enabled'              => $this->getLauncherEnabled(),
          'timezone'                      => $this->getTimezone(),
          'restApiEnable'                 => $this->getRestApiEnable(),
          'rest_api_hostname'             => $this->getRestApiHostname(),
          'rest_api_port'                 => $this->getRestApiPort(),
          'rest_api_key'                  => $this->getRestApiKey(),
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param   string  $name
     *
     * @return serverModel
     */
    public function setName(string $name): serverModel
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateExp(): int
    {
        return $this->rateExp;
    }

    /**
     * @param   int  $rateExp
     *
     * @return serverModel
     */
    public function setRateExp(int $rateExp): serverModel
    {
        $this->rateExp = $rateExp;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateSp(): int
    {
        return $this->rateSp;
    }

    /**
     * @param   int  $rateSp
     *
     * @return serverModel
     */
    public function setRateSp(int $rateSp): serverModel
    {
        $this->rateSp = $rateSp;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateAdena(): int
    {
        return $this->rateAdena;
    }

    /**
     * @param   int  $rateAdena
     *
     * @return serverModel
     */
    public function setRateAdena(int $rateAdena): serverModel
    {
        $this->rateAdena = $rateAdena;

        return $this;
    }

    /**
     * @return int
     */
    public function getrateDrop(): int
    {
        return $this->rateDrop;
    }

    /**
     * @param   int  $rateDrop
     *
     * @return serverModel
     */
    public function setrateDrop(int $rateDrop): serverModel
    {
        $this->rateDrop = $rateDrop;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateSpoil(): int
    {
        return $this->rateSpoil;
    }

    /**
     * @param   int  $rateSpoil
     *
     * @return serverModel
     */
    public function setRateSpoil(int $rateSpoil): serverModel
    {
        $this->rateSpoil = $rateSpoil;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateStartServer(): string
    {
        return $this->dateStartServer;
    }

    /**
     * @param   string  $dateStartServer
     *
     * @return serverModel
     */
    public function setDateStartServer(string $dateStartServer): serverModel
    {
        $this->dateStartServer = $dateStartServer;

        return $this;
    }

    /**
     * @return string
     */
    public function getChronicle(): string
    {
        return $this->chronicle;
    }

    /**
     * @param   string  $chronicle
     *
     * @return serverModel
     */
    public function setChronicle(string $chronicle): serverModel
    {
        $this->chronicle = $chronicle;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginHost(): string
    {
        return $this->loginHost;
    }

    /**
     * @param   string  $loginHost
     *
     * @return serverModel
     */
    public function setLoginHost(string $loginHost): serverModel
    {
        $this->loginHost = $loginHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getLoginPort(): int
    {
        return $this->loginPort;
    }

    /**
     * @param   int  $loginPort
     *
     * @return serverModel
     */
    public function setLoginPort(int $loginPort): serverModel
    {
        $this->loginPort = $loginPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUser(): string
    {
        return $this->loginUser;
    }

    /**
     * @param   string  $loginUser
     *
     * @return serverModel
     */
    public function setLoginUser(string $loginUser): serverModel
    {
        $this->loginUser = $loginUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginPassword(): string
    {
        return $this->loginPassword;
    }

    /**
     * @param   string  $loginPassword
     *
     * @return serverModel
     */
    public function setLoginPassword(string $loginPassword): serverModel
    {
        $this->loginPassword = $loginPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginName(): string
    {
        return $this->loginName;
    }

    /**
     * @param   string  $loginName
     *
     * @return serverModel
     */
    public function setLoginName(string $loginName): serverModel
    {
        $this->loginName = $loginName;

        return $this;
    }

    /**
     * @return string
     */
    public function getGameHost(): string
    {
        return $this->gameHost;
    }

    /**
     * @param   string  $gameHost
     *
     * @return serverModel
     */
    public function setGameHost(string $gameHost): serverModel
    {
        $this->gameHost = $gameHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getGamePort(): int
    {
        return $this->gamePort;
    }

    /**
     * @param   int  $gamePort
     *
     * @return serverModel
     */
    public function setGamePort(int $gamePort): serverModel
    {
        $this->gamePort = $gamePort;

        return $this;
    }

    /**
     * @return string
     */
    public function getGameUser(): string
    {
        return $this->gameUser;
    }

    /**
     * @param   string  $gameUser
     *
     * @return serverModel
     */
    public function setGameUser(string $gameUser): serverModel
    {
        $this->gameUser = $gameUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getGamePassword(): string
    {
        return $this->gamePassword;
    }

    /**
     * @param   string  $gamePassword
     *
     * @return serverModel
     */
    public function setGamePassword(string $gamePassword): serverModel
    {
        $this->gamePassword = $gamePassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getGameName(): string
    {
        return $this->gameName;
    }

    /**
     * @param   string  $gameName
     *
     * @return serverModel
     */
    public function setGameName(string $gameName): serverModel
    {
        $this->gameName = $gameName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionSqlBaseName(): string
    {
        return $this->collectionSqlBaseName;
    }

    /**
     * @param   string  $collectionSqlBaseName
     *
     * @return serverModel
     */
    public function setCollectionSqlBaseName(string $collectionSqlBaseName): serverModel
    {
        $this->collectionSqlBaseName = $collectionSqlBaseName;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckserverModel(): int
    {
        return $this->checkserverModel;
    }

    /**
     * @param   int  $checkserverModel
     *
     * @return serverModel
     */
    public function setCheckserverModel(int $checkserverModel): serverModel
    {
        $this->checkserverModel = $checkserverModel;

        return $this;
    }

    /**
     * @return int
     */
    public function getChatGameEnabled(): int
    {
        return $this->chatGameEnabled;
    }

    /**
     * @param   int  $chatGameEnabled
     *
     * @return serverModel
     */
    public function setChatGameEnabled(int $chatGameEnabled): serverModel
    {
        $this->chatGameEnabled = $chatGameEnabled;

        return $this;
    }

    /**
     * @return int
     */
    public function getLauncherEnabled(): int
    {
        return $this->launcherEnabled;
    }

    /**
     * @param   int  $launcherEnabled
     *
     * @return serverModel
     */
    public function setLauncherEnabled(int $launcherEnabled): serverModel
    {
        $this->launcherEnabled = $launcherEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param   string  $timezone
     *
     * @return serverModel
     */
    public function setTimezone(string $timezone): serverModel
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function getRestApiHostname(): string
    {
        return $this->restApiHostname;
    }

    /**
     * @param   string  $restApiHostname
     *
     * @return serverModel
     */
    public function setRestApiHostname(string $restApiHostname): serverModel
    {
        $this->restApiHostname = $restApiHostname;

        return $this;
    }

    /**
     * @return int
     */
    public function getRestApiPort(): int
    {
        return $this->restApiPort;
    }

    /**
     * @param   int  $restApiPort
     *
     * @return serverModel
     */
    public function setRestApiPort(int $restApiPort): serverModel
    {
        $this->restApiPort = $restApiPort;

        return $this;
    }

    //Сохранение в бд

    /**
     * @return string
     */
    public function getRestApiKey(): string
    {
        return $this->restApiKey;
    }

    /**
     * @param   string  $restApiKey
     *
     * @return serverModel
     */
    public function setRestApiKey(string $restApiKey): serverModel
    {
        $this->restApiKey = $restApiKey;

        return $this;
    }

}
