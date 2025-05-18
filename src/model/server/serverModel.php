<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\config\donate;
use Ofey\Logan22\model\config\referral;
use Ofey\Logan22\model\db\sql;

class serverModel
{

    static public array $arrayServerStatus = [];
    public ?serverStatus $serverStatus = null;
    private ?int $id = null;
    private ?int $loginId = null;
    private ?int $gameId = null;
    private bool $disabled = false;
    private string $name = 'No Name';
    private int $rateExp = 1;
    private int $rateSp = 1;
    private int $rateAdena = 1;
    private int $rateDrop = 1;
    private int $rateSpoil = 1;
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
    private ?bool $showStatusBar = false;
    private ?bool $errorConnectDBServer = null;
    private ?string $collection = null;
    private ?array $statusServerMem = null;
    private ?bool $default = null;
    private ?string $knowledgeBase = null;
    private ?int $maxOnline = 200;
    //Позиция сервера при сортировки
    private ?int $position = 0;

    // Есть ли данный сервер на сервере сферы
    private bool $resetHWID = false;
    private ?bool $resetItemsToWarehouse = false;

    private ?bool $isSphereServer = null;

    private ?donate $donate = null;
    private ?referral $referral = null;

    private ?serverStackable $stackableItem = null;

    public function __construct(array $server, array $server_data = [], ?int $pageId = null)
    {
        $this->id = $server['id'] ?? null;
        $this->loginId = $server['login_id'] ?? null;
        $this->gameId = $server['game_id'] ?? null;
        $this->disabled = $server['disabled'] ?? false;
        $this->name = $server['name'] ?? '';
        $this->rateExp = filter_var($server['rateExp'] ?? 1, FILTER_VALIDATE_INT);
        $this->rateSp = filter_var($server['rateSp'] ?? 1, FILTER_VALIDATE_INT);
        $this->rateAdena = filter_var($server['rateAdena'] ?? 1, FILTER_VALIDATE_INT);
        $this->rateDrop = filter_var($server['rateDrop'] ?? 1, FILTER_VALIDATE_INT);
        $this->rateSpoil = filter_var($server['rateSpoil'] ?? 1, FILTER_VALIDATE_INT);
        $this->chronicle = $server['chronicle'] ?? '';
        $this->chatGameEnabled = $server['chat_game_enabled'] ?? 0;
        $this->launcherEnabled = $server['launcher_enabled'] ?? 0;
        $this->timezone = $server['timezone'] ?? '';
        $this->collection = $server['collection'] ?? null;
        $this->showStatusBar = $server['showStatusBar'] ?? false;
        $this->statusServerMem = $server['statusServer'] ?? null;
        $this->default = $server['default'] ?? null;
        $this->dateStartServer = $server['dateStartServer'] ?? null;
        $this->knowledgeBase = $server['knowledgeBase'] ?? 'highFive';
        $this->position = filter_var($server['position'] ?? 0, FILTER_VALIDATE_INT);
        $this->maxOnline = filter_var($server['maxOnline'] ?? 200, FILTER_VALIDATE_INT);
        $this->resetHWID = filter_var($server['resetHWID'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->resetItemsToWarehouse = filter_var($server['resetItemsToWarehouse'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($server_data) {
            foreach ($server_data as $data) {
                $this->server_data[] = new serverDataModel($data);
            }
        }
        if ($pageId) {
            $this->page = new serverDescriptionModel($pageId);
        }

        $this->donate = new donate($this->id, $this->knowledgeBase);
        $this->referral = new referral($this->id);
        $this->stackableItem = new serverStackable($server['stackableItem'] ?? null);
        $this->bonus = new serverBonus($server['bonus'] ?? null);
    }

    private ?serverBonus $bonus = null;

    public function bonus(): ?serverBonus
    {
        return $this->bonus;
    }

    public function stackableItem(): ?serverStackable
    {
        return $this->stackableItem;
    }

    public function getReferral(): ?referral
    {
        return $this->referral;
    }

    public function getDonateConfig(): ?donate
    {
        return $this->donate;
    }

    public function donate(): ?donate
    {
        return $this->donate;
    }

    public function isEnabled(): bool
    {
        return !$this->disabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->disabled = $enabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $enabled): void
    {
        $this->disabled = $enabled;
    }

    public function getShowStatusBar(): ?bool
    {
        return $this->showStatusBar;
    }

    public function getLoginId(): ?int
    {
        return $this->loginId;
    }

    // Позиция сортировки сервера
    public function getPosition(): ?int
    {
        return $this->position;
    }

    // Установка позиции
    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getGameId(): ?int
    {
        return $this->gameId;
    }

    public function getStatusServerMem(): ?array
    {
        return $this->statusServerMem;
    }

    public function isResetHWID(): bool
    {
        return $this->resetHWID;
    }

    public function isResetItemsToWarehouse(): bool
    {
        return $this->resetItemsToWarehouse;
    }

    public function isDefault(): ?bool
    {
        return $this->default;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->default = $isDefault;
    }

    public function getIsSphereServer(): ?bool
    {
        return $this->isSphereServer;
    }

    public function setIsSphereServer(bool $isSphereServer): void
    {
        $this->isSphereServer = $isSphereServer;
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
        return (is_numeric($this->maxOnline) && $this->maxOnline > 0) ? (int)$this->maxOnline : 200;
    }

    public function getStartServerDate(): ?string
    {
        return $this->dateStartServer;
    }

    public function getKnowledgeBase(): string|bool
    {
        return $this->knowledgeBase ?? 'highFive';
    }

    public function getStatusServer(): ?serverStatus
    {
        return $this->serverStatus;
    }

    /**
     * Проверка, работает ли логин/гейм сервер и получение количества игроков онлайна
     *
     * @return \Ofey\Logan22\model\server\serverStatus|null
     */
    public function getStatus(): ?serverStatus
    {
        return $this->serverStatus;
    }

    public function save(): void
    {
        $arr = [
            'id' => $this->id,
            'login_id' => $this->loginId,
            'game_id' => $this->gameId,
            'disabled' => $this->disabled,
            'name' => $this->name,
            'rateExp' => $this->rateExp,
            'rateSp' => $this->rateSp,
            'rateAdena' => $this->rateAdena,
            'rateDrop' => $this->rateDrop,
            'rateSpoil' => $this->rateSpoil,
            'chronicle' => $this->chronicle,
            'chatGameEnabled' => $this->chatGameEnabled,
            'launcherEnabled' => $this->launcherEnabled,
            'showStatusBar' => $this->showStatusBar,
            'date_start_server' => $this->dateStartServer,
            'timezone' => $this->timezone,
            'collection' => $this->collection,
            'statusServer' => $this->statusServerMem,
            'default' => $this->default,
            'position' => $this->position,
            'knowledgeBase' => $this->knowledgeBase,
            'stackableItem' => $this->stackableItem()->toArray(),
            'bonus' => $this->bonus()->toArray(),
            'maxOnline' => $this->maxOnline,
        ];
        sql::run(
            "INSERT INTO `servers` (`id`, `data`) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)",
            [
                $this->id,
                json_encode($arr),
            ]
        );
    }


    public function getArrayVar(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'rateExp' => $this->getRateExp(),
            'rateSp' => $this->getRateSp(),
            'rate_adena' => $this->getRateAdena(),
            'rate_drop_item' => $this->getRateDrop(),
            'rateSpoil' => $this->getRateSpoil(),
            'date_start_server' => $this->getDateStartServer(),
            'chronicle' => $this->getChronicle(),
            'check_server_online' => $this->getCheckserver(),
            'check_LoginServer_online_host' => $this->getCheckLoginServerHost(),
            'check_LoginServer_online_port' => $this->getCheckLoginServerPort(),
            'check_GameServer_online_host' => $this->getCheckGameServerHost(),
            'check_GameServer_online_port' => $this->getCheckGameServerPort(),
            'chat_game_enabled' => $this->getChatGameEnabled(),
            'launcher_enabled' => $this->getLauncherEnabled(),
            'timezone' => $this->getTimezone(),
        ];
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
     *
     * @return server
     */
    public function setId(
        int $id
    ): serverModel
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
     *
     * @return server
     */
    public function setName(
        string $name
    ): serverModel
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
     *
     * @return server
     */
    public function setRateExp(
        int $rateExp
    ): serverModel
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
     *
     * @return server
     */
    public function setRateSp(
        int $rateSp
    ): serverModel
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
     *
     * @return server
     */
    public function setRateAdena(
        int $rateAdena
    ): serverModel
    {
        $this->rateAdena = $rateAdena;

        return $this;
    }

    /**
     * @return int
     */
    public function getRateDrop(): int
    {
        return $this->rateDrop;
    }

    /**
     * @param int $rateDrop
     *
     * @return server
     */
    public function setRateDrop(
        int $rateDrop
    ): serverModel
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
     *
     * @return server
     */
    public function setRateSpoil(
        int $rateSpoil
    ): serverModel
    {
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
     * @param string $dateStartServer
     *
     * @return server
     */
    public function setDateStartServer(
        string $dateStartServer
    ): serverModel
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
     *
     * @return server
     */
    public function setChronicle(
        string $chronicle
    ): serverModel
    {
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
     * @param string $checkLoginServerHost
     *
     * @return \Ofey\Logan22\model\server\serverModel
     */
    public function setCheckLoginServerHost(
        string $checkLoginServerHost
    ): serverModel
    {
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
     * @param int $checkLoginServerPort
     *
     * @return server
     */
    public function setCheckLoginServerPort(
        int $checkLoginServerPort
    ): serverModel
    {
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
     * @param string $checkGameServerHost
     *
     * @return server
     */
    public function setCheckGameServerHost(
        string $checkGameServerHost
    ): serverModel
    {
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
     * @param int $checkGameServerPort
     *
     * @return server
     */
    public function setCheckGameServerPort(
        int $checkGameServerPort
    ): serverModel
    {
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
     * @param int $chatGameEnabled
     *
     * @return server
     */
    public function setChatGameEnabled(
        int $chatGameEnabled
    ): serverModel
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
     *
     * @return server
     */
    public function setLauncherEnabled(
        int $launcherEnabled
    ): serverModel
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
     *
     * @return server
     */
    public function setTimezone(
        string $timezone
    ): serverModel
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getToken(): string
    {
        return $this->getServerData('token')?->getVal() ?? "";
    }

    public function getServerData(
        $key = null
    ): null|array|serverDataModel
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

    public function setServerData(
        array $server_data
    ): void
    {
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
    ): void
    {
        $this->page = $page;
    }

    /**
     * @param int $checkserver
     *
     * @return server
     */
    public function setCheckserver(
        int $checkserver
    ): serverModel
    {
        $this->checkserver = $checkserver;

        return $this;
    }

    public function setPluginSetting(string $name, array $setting, $serverId = null): void
    {
        if ($serverId == null) {
            $serverId = $this->getId();
        }
        sql::run("DELETE FROM `settings` WHERE `key` = ? AND `serverId` = ?", [
            $name,
            $serverId,
        ]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)", [
            $name,
            json_encode($setting, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            $serverId,
            time::mysql(),
        ]);
    }

    public function getPluginSetting(string $name, $serverId = null)
    {
        if ($serverId == null) {
            $serverId = $this->getId();
        }
        $setting = sql::getRow("SELECT `setting` FROM `settings` WHERE `key` = ? AND `serverId` = ?", [
            $name,
            $serverId,
        ]);
        if (empty($setting)) {
            return null;
        }
        return json_decode($setting['setting'], true);
    }

    public function getCache(?string $type = null, $server_id = null, $fullData = false)
    {
        if ($server_id == null) {
            $server_id = $this->getId();
        }
        $data = sql::getRow("SELECT `data`, `date_create` FROM `server_cache` WHERE `server_id` = ? AND `type` = ? LIMIT 1 ", [$server_id, $type]);
        if (empty($data)) {
            return null;
        }
        if ($fullData) {
            return $data;
        }
        return json_decode($data['data'], true);
    }

    public function setCache(string $type, $data): void
    {
        sql::sql("DELETE FROM `server_cache` WHERE `server_id` = ? AND `type` = ?", [$this->getId(), $type]);
        sql::run("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)",
            [$this->getId(), $type, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), time::mysql()]);
    }


}
