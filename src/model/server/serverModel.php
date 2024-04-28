<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\model\db\sql;

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
    private int $checkServerOnline;
    private string $checkLoginserverOnlineHost;
    private int $checkLoginserverOnlinePort;
    private string $checkGameserverOnlineHost;
    private int $checkGameserverOnlinePort;
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

    private ?array $page;

    public function __construct(array $server, array $server_data, ?array $page = null) {
        $this->id = $server['id'] ?? null;
        $this->name = $server['name'] ?? '';
        $this->rateExp = $server['rateExp'] ?? 0;
        $this->rateSp = $server['rateSp'] ?? 0;
        $this->rateAdena = $server['rate_adena'] ?? 0;
        $this->rateDrop = $server['rateDrop'] ?? 0;
        $this->rateSpoil = $server['rateSpoil'] ?? 0;
        $this->dateStartServer = $server['date_start_server'] ?? '';
        $this->chronicle = $server['chronicle'] ?? '';
        $this->loginHost = $server['login_host'] ?? '';
        $this->loginPort = $server['login_port'] ?? 0;
        $this->loginUser = $server['login_user'] ?? '';
        $this->loginPassword = $server['login_password'] ?? '';
        $this->loginName = $server['login_name'] ?? '';
        $this->gameHost = $server['game_host'] ?? '';
        $this->gamePort = $server['game_port'] ?? 0;
        $this->gameUser = $server['game_user'] ?? '';
        $this->gamePassword = $server['game_password'] ?? '';
        $this->gameName = $server['game_name'] ?? '';
        $this->collectionSqlBaseName = $server['collection_sql_base_name'] ?? '';
        $this->checkServerOnline = $server['check_server_online'] ?? 0;
        $this->checkLoginserverOnlineHost = $server['check_loginserver_online_host'] ?? '';
        $this->checkLoginserverOnlinePort = $server['check_loginserver_online_port'] ?? 0;
        $this->checkGameserverOnlineHost = $server['check_gameserver_online_host'] ?? '';
        $this->checkGameserverOnlinePort = $server['check_gameserver_online_port'] ?? 0;
        $this->chatGameEnabled = $server['chat_game_enabled'] ?? 0;
        $this->launcherEnabled = $server['launcher_enabled'] ?? 0;
        $this->timezone = $server['timezone'] ?? '';
        $this->restApiEnable = $server['restApiEnable'] ?? 0;
        $this->restApiHostname = $server['rest_api_hostname'] ?? '';
        $this->restApiPort = $server['rest_api_port'] ?? 0;
        $this->restApiKey = $server['rest_api_key'] ?? '';
        if($server_data){
            foreach($server_data AS $data){
                $this->server_data[] = new serverDataModel($data);
            }
        }
        $this->page = $page ? new serverDescriptionModel($page) : null;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return serverModel
     */
    public function setId(int $id): serverModel
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
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
     * @param int $rateExp
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
     * @param int $rateSp
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
     * @param int $rateAdena
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
     * @param int $rateDrop
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
     * @param int $rateSpoil
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
     * @param string $dateStartServer
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
     * @param string $chronicle
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
     * @param string $loginHost
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
     * @param int $loginPort
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
     * @param string $loginUser
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
     * @param string $loginPassword
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
     * @param string $loginName
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
     * @param string $gameHost
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
     * @param int $gamePort
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
     * @param string $gameUser
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
     * @param string $gamePassword
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
     * @param string $gameName
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
     * @param string $collectionSqlBaseName
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
    public function getCheckServerOnline(): int
    {
        return $this->checkServerOnline;
    }

    /**
     * @param int $checkServerOnline
     * @return serverModel
     */
    public function setCheckServerOnline(int $checkServerOnline): serverModel
    {
        $this->checkServerOnline = $checkServerOnline;
        return $this;
    }

    /**
     * @return string
     */
    public function getCheckLoginserverOnlineHost(): string
    {
        return $this->checkLoginserverOnlineHost;
    }

    /**
     * @param string $checkLoginserverOnlineHost
     * @return serverModel
     */
    public function setCheckLoginserverOnlineHost(string $checkLoginserverOnlineHost): serverModel
    {
        $this->checkLoginserverOnlineHost = $checkLoginserverOnlineHost;
        return $this;
    }

    /**
     * @return int
     */
    public function getCheckLoginserverOnlinePort(): int
    {
        return $this->checkLoginserverOnlinePort;
    }

    /**
     * @param int $checkLoginserverOnlinePort
     * @return serverModel
     */
    public function setCheckLoginserverOnlinePort(int $checkLoginserverOnlinePort): serverModel
    {
        $this->checkLoginserverOnlinePort = $checkLoginserverOnlinePort;
        return $this;
    }

    /**
     * @return string
     */
    public function getCheckGameserverOnlineHost(): string
    {
        return $this->checkGameserverOnlineHost;
    }

    /**
     * @param string $checkGameserverOnlineHost
     * @return serverModel
     */
    public function setCheckGameserverOnlineHost(string $checkGameserverOnlineHost): serverModel
    {
        $this->checkGameserverOnlineHost = $checkGameserverOnlineHost;
        return $this;
    }

    /**
     * @return int
     */
    public function getCheckGameserverOnlinePort(): int
    {
        return $this->checkGameserverOnlinePort;
    }

    /**
     * @param int $checkGameserverOnlinePort
     * @return serverModel
     */
    public function setCheckGameserverOnlinePort(int $checkGameserverOnlinePort): serverModel
    {
        $this->checkGameserverOnlinePort = $checkGameserverOnlinePort;
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
     * @param int $chatGameEnabled
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
     * @param int $launcherEnabled
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
     * @param string $timezone
     * @return serverModel
     */
    public function setTimezone(string $timezone): serverModel
    {
        $this->timezone = $timezone;
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
     * @param int $restApiEnable
     * @return serverModel
     */
    public function setRestApiEnable(int $restApiEnable): serverModel
    {
        $this->restApiEnable = $restApiEnable;
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
     * @param string $restApiHostname
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
     * @param int $restApiPort
     * @return serverModel
     */
    public function setRestApiPort(int $restApiPort): serverModel
    {
        $this->restApiPort = $restApiPort;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestApiKey(): string
    {
        return $this->restApiKey;
    }

    /**
     * @param string $restApiKey
     * @return serverModel
     */
    public function setRestApiKey(string $restApiKey): serverModel
    {
        $this->restApiKey = $restApiKey;
        return $this;
    }

    public function getServerData($key = null): null|array|serverDataModel
    {
        if($key == null){
            return $this->server_data;
        }
        if(empty($this->server_data)){
            return null;
        }
        foreach($this->server_data AS $data){
            if($key == $data->getKey()){
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

    //Сохранение в бд
    public function save(): void
    {
        if($this->getId() === null){
            sql::sql("INSERT INTO `servers` (data) VALUES (?)", ['wait...']);
            $this->setId(sql::lastInsertId());
        }
        $jsonData = json_encode($this->getArrayVar());
        sql::sql("UPDATE `servers` SET `data` = ? WHERE `id` = ?",[
            $jsonData, $this->getId(),
        ]);
    }

    public function getArrayVar(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'rateExp' => $this->getRateExp(),
            'rateSp' => $this->getRateSp(),
            'rate_adena' => $this->getRateAdena(),
            'rate_drop_item' => $this->getrateDrop(),
            'rateSpoil' => $this->getRateSpoil(),
            'date_start_server' => $this->getDateStartServer(),
            'chronicle' => $this->getChronicle(),
            'login_host' => $this->getLoginHost(),
            'login_port' => $this->getLoginPort(),
            'login_user' => $this->getLoginUser(),
            'login_password' => $this->getLoginPassword(),
            'login_name' => $this->getLoginName(),
            'game_host' => $this->getGameHost(),
            'game_port' => $this->getGamePort(),
            'game_user' => $this->getGameUser(),
            'game_password' => $this->getGamePassword(),
            'game_name' => $this->getGameName(),
            'collection_sql_base_name' => $this->getCollectionSqlBaseName(),
            'check_server_online' => $this->getCheckServerOnline(),
            'check_loginserver_online_host' => $this->getCheckLoginserverOnlineHost(),
            'check_loginserver_online_port' => $this->getCheckLoginserverOnlinePort(),
            'check_gameserver_online_host' => $this->getCheckGameserverOnlineHost(),
            'check_gameserver_online_port' => $this->getCheckGameserverOnlinePort(),
            'chat_game_enabled' => $this->getChatGameEnabled(),
            'launcher_enabled' => $this->getLauncherEnabled(),
            'timezone' => $this->getTimezone(),
            'restApiEnable' => $this->getRestApiEnable(),
            'rest_api_hostname' => $this->getRestApiHostname(),
            'rest_api_port' => $this->getRestApiPort(),
            'rest_api_key' => $this->getRestApiKey(),
        ];
    }


}
