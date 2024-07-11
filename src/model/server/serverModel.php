<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
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

    private ?string $dateStartServer = null;

    private string $chronicle;

    private int $chatGameEnabled;

    private int $launcherEnabled;

    private string $timezone;

    private ?string $checkLoginServerHost = null;

    private ?int $checkLoginServerPort = null;

    private ?string $checkGameServerHost = null;

    private ?int $checkGameServerPort = null;

    private array $server_data = [];

    private ?serverDescriptionModel $page;

    private ?serverStatus $serverStatus = null;

    private ?bool $errorConnectDBServer = null;

    private ?string $collection = null;

    public function __construct(array $server, array $server_data = [], ?int $pageId = null)
    {
        $this->id              = $server['id'] ?? null;
        $this->name            = $server['name'] ?? '';
        $this->rateExp         = $server['rateExp'] ?? 1;
        $this->rateSp          = $server['rateSp'] ?? 1;
        $this->rateAdena       = $server['rateAdena'] ?? 1;
        $this->rateDrop        = $server['rateDrop'] ?? 1;
        $this->rateSpoil       = $server['rateSpoil'] ?? 1;
        $this->chronicle       = $server['chronicle'] ?? '';
        $this->chatGameEnabled = $server['chat_game_enabled'] ?? 0;
        $this->launcherEnabled = $server['launcher_enabled'] ?? 0;
        $this->timezone        = $server['timezone'] ?? '';
        $this->collection      = $server['collection'] ?? null;
        if ($server_data) {
            foreach ($server_data as $data) {
                $this->server_data[] = new serverDataModel($data);
            }
        }
        if ($pageId) {
            $this->page = new serverDescriptionModel($pageId);
        }

        return $this;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
    }

    public function getMaxOnline(): int
    {
        foreach ($this->server_data as $data) {
            if ($data->getKey() == 'max_online') {
                return $data->getVal();
            }
        }

        return 200;
    }

    public function getStartServerDate(): ?string
    {
        foreach ($this->server_data as $data) {
            if ($data->getKey() == 'date_start_server') {
                return $data->getVal();
            }
        }

        return null;
    }

    public function getKnowledgeBase($baseName = null): string|bool
    {
        if ($baseName) {
            return fileSys::modifyString($baseName);
        }

        foreach ($this->server_data as $data) {
            if ($data->getKey() == 'knowledge_base') {
                return $data->getVal();
            }
        }

        return 'highFive';
    }

    public function getErrorConnectDBServer(): bool
    {
        return $this->isErrorConnectDBServer();
    }

    public function isErrorConnectDBServer(): bool
    {
        if ($this->errorConnectDBServer !== null) {
            $result                     = $this->errorConnectDBServer;
            $this->errorConnectDBServer = null; // Сбрасываем значение после использования

            return $result;
        }

        $serverCache = sql::getRow(
          "SELECT `data`, `date_create` FROM `server_cache` WHERE `server_id` = ? AND `type` = 'connect' ORDER BY id DESC LIMIT 1",
          [$this->id]
        );

        if ( ! $serverCache) {
            $this->errorConnectDBServer = false;

            return false;
        }

        $totalSeconds = time::diff(time::mysql(), $serverCache['date_create']);

        if ($totalSeconds > config::load()->cache()->getTimeoutConnect()) {
            $this->errorConnectDBServer = false;

            return false;
        }

        $this->errorConnectDBServer = true;

        return $this->errorConnectDBServer;
    }

    /**
     * Проверка, работает ли логин/гейм сервер и получение количества игроков онлайна
     *
     * @return \Ofey\Logan22\model\server\serverStatus
     */
    public function getStatus($forceUpdate = false): serverStatus
    {
        //Когда принудительное обновление включено, мы не используем кэш из бд
        if ( ! $forceUpdate) {
            if ($this->serverStatus !== null) {
                return $this->serverStatus;
            }

            $serverCache = sql::getRow(
              "SELECT `data`, `date_create` FROM `server_cache` WHERE `server_id` = ? AND `type` = 'status' ORDER BY `id` DESC LIMIT 1", [
                $this->getId(),
              ]
            );

            if ($serverCache) {
                /**
                 * Если прошло меньше минуты, тогда выводим данные из кэша
                 */
                $totalSeconds = time::diff(time::mysql(), $serverCache['date_create']);
                if ($totalSeconds < config::load()->cache()->getStatus()) {
                    $serverCache  = json_decode($serverCache['data'], true);
                    $serverStatus = new serverStatus();
                    $serverStatus->setServerId($this->getId());
                    $serverStatus->setLoginServer($serverCache['loginServer']);
                    $serverStatus->setGameServer($serverCache['gameServer']);
                    $serverStatus->setOnline($serverCache['online']);
                    $serverStatus->setEnable(filter_var($serverCache['isEnableStatus'], FILTER_VALIDATE_BOOLEAN));
                    $serverStatus->licenseExpirationDate($serverCache['licenseExpirationDate']);
                    $this->serverStatus = $serverStatus;

                    return $serverStatus;
                }
            }
        }

        $serverStatus = new serverStatus();
        $serverStatus->setServerId($this->getId());
        $sphere = \Ofey\Logan22\component\sphere\server::send(type::GET_STATUS_SERVER)->getResponse();
        if (isset($sphere['error']) or $sphere == null) {
            $serverStatus->setEnable(false);
            $serverStatus->setLoginServer(false);
            $serverStatus->setGameServer(false);
            $serverStatus->setOnline(0);
            $serverStatus->licenseExpirationDate(null);
            $serverStatus->save();
        } else {
            $serverStatus->setEnable(filter_var($sphere['isEnableStatus'], FILTER_VALIDATE_BOOLEAN));
            $serverStatus->setLoginServer($sphere['loginServer']);
            $serverStatus->setGameServer($sphere['gameServer']);
            $serverStatus->setOnline($sphere['online']);
            $serverStatus->licenseExpirationDate($sphere['licenseExpirationDate']);
            $serverStatus->save();
        }
        $this->serverStatus = $serverStatus;

        return $serverStatus;
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
     * @return server
     */
    public function setId(
      int $id
    ): serverModel {
        $this->id = $id;

        return $this;
    }

    public function save()
    {
        $arr = [
          'id'              => $this->id,
          'name'            => $this->name,
          'rateExp'         => $this->rateExp,
          'rateSp'          => $this->rateSp,
          'rateAdena'       => $this->rateAdena,
          'rateDrop'        => $this->rateDrop,
          'rateSpoil'       => $this->rateSpoil,
          'chronicle'       => $this->chronicle,
          'chatGameEnabled' => $this->chatGameEnabled,
          'launcherEnabled' => $this->launcherEnabled,
          'timezone'        => $this->timezone,
          'collection'      => $this->collection,
        ];
        sql::run(
          "UPDATE `servers` SET `data` = ? WHERE `id` = ?",
          [
            json_encode($arr),
            $this->id,
          ]
        );
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
          'check_server_online'           => $this->getCheckserver(),
          'check_LoginServer_online_host' => $this->getCheckLoginServerHost(),
          'check_LoginServer_online_port' => $this->getCheckLoginServerPort(),
          'check_GameServer_online_host'  => $this->getCheckGameServerHost(),
          'check_GameServer_online_port'  => $this->getCheckGameServerPort(),
          'chat_game_enabled'             => $this->getChatGameEnabled(),
          'launcher_enabled'              => $this->getLauncherEnabled(),
          'timezone'                      => $this->getTimezone(),
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
     * @return server
     */
    public function setName(
      string $name
    ): serverModel {
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
     * @return server
     */
    public function setRateExp(
      int $rateExp
    ): serverModel {
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
     * @return server
     */
    public function setRateSp(
      int $rateSp
    ): serverModel {
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
     * @return server
     */
    public function setRateAdena(
      int $rateAdena
    ): serverModel {
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
     * @return server
     */
    public function setrateDrop(
      int $rateDrop
    ): serverModel {
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
     * @return server
     */
    public function setRateSpoil(
      int $rateSpoil
    ): serverModel {
        $this->rateSpoil = $rateSpoil;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getDateStartServer(): ?string
    {
        return $this->dateStartServer;
    }

    /**
     * @param   string  $dateStartServer
     *
     * @return server
     */
    public function setDateStartServer(
      string $dateStartServer
    ): serverModel {
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
     * @return server
     */
    public function setChronicle(
      string $chronicle
    ): serverModel {
        $this->chronicle = $chronicle;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckserver(): int
    {
        return $this->checkserver ?? 0;
    }

    /**
     * @return string|null
     */
    public function getCheckLoginServerHost(): ?string
    {
        return $this->checkLoginServerHost;
    }

    /**
     * @param   string  $checkLoginServerHost
     *
     * @return \Ofey\Logan22\model\server\serverModel
     */
    public function setCheckLoginServerHost(
      string $checkLoginServerHost
    ): serverModel {
        $this->checkLoginServerHost = $checkLoginServerHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckLoginServerPort(): int
    {
        return $this->checkLoginServerPort ?? 9014;
    }

    /**
     * @param   int  $checkLoginServerPort
     *
     * @return server
     */
    public function setCheckLoginServerPort(
      int $checkLoginServerPort
    ): serverModel {
        $this->checkLoginServerPort = $checkLoginServerPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getCheckGameServerHost(): string
    {
        return $this->checkGameServerHost ?? 7777;
    }

    /**
     * @param   string  $checkGameServerHost
     *
     * @return server
     */
    public function setCheckGameServerHost(
      string $checkGameServerHost
    ): serverModel {
        $this->checkGameServerHost = $checkGameServerHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckGameServerPort(): int
    {
        return $this->checkGameServerPort ?? 7777;
    }

    /**
     * @param   int  $checkGameServerPort
     *
     * @return server
     */
    public function setCheckGameServerPort(
      int $checkGameServerPort
    ): serverModel {
        $this->checkGameServerPort = $checkGameServerPort;

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
     * @return server
     */
    public function setChatGameEnabled(
      int $chatGameEnabled
    ): serverModel {
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
     * @return server
     */
    public function setLauncherEnabled(
      int $launcherEnabled
    ): serverModel {
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
     * @return server
     */
    public function setTimezone(
      string $timezone
    ): serverModel {
        $this->timezone = $timezone;

        return $this;
    }

    public function getToken(): string
    {
        return $this->getServerData('token')?->getVal() ?? "";
    }

    public function getServerData(
      $key = null
    ): null|array|serverDataModel {
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

    public function setServerData(
      array $server_data
    ): void {
        $this->server_data = $server_data;
    }

    public function getTokenAdmin(): string
    {
        return $this->getServerData('tokenAdmin')?->getVal() ?? "";
    }

    public function getPage(): ?array
    {
        return $this->page;
    }

    public function setPage(
      ?array $page
    ): void {
        $this->page = $page;
    }

    /**
     * @param   int  $checkserver
     *
     * @return server
     */
    public function setCheckserver(
      int $checkserver
    ): serverModel {
        $this->checkserver = $checkserver;

        return $this;
    }

}
