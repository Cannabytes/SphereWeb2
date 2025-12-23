<?php

namespace Ofey\Logan22\model\server;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
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
    private string $platform = "java";
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
    private ?string $itemsSendAvailableFrom = null;
    private ?bool $itemsSendShowTime = true;
    //Позиция сервера при сортировки
    private ?int $position = 0;

    // Есть ли данный сервер на сервере сферы
    private bool $resetHWID = false;
    private ?bool $resetItemsToWarehouse = false;
    private ?bool $showOnlineInStatusServer = true;

    private ?bool $isSphereServer = null;

    private ?donate $donate = null;
    private ?referral $referral = null;

    private ?serverStackable $stackableItem = null;
    private const MACHINE_TIMEZONE = 'UTC';

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
        $this->platform = $server['platform'] ?? 'java';
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
        $this->showOnlineInStatusServer = filter_var($server['showOnlineInStatusServer'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->itemsSendAvailableFrom = $server['itemsSendAvailableFrom'] ?? null;
        $this->itemsSendShowTime = filter_var($server['itemsSendShowTime'] ?? true, FILTER_VALIDATE_BOOLEAN);
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

    public function ShowOnlineInStatusServer(): bool
    {
        return $this->showOnlineInStatusServer;
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
            'itemsSendAvailableFrom' => $this->itemsSendAvailableFrom,
            'itemsSendShowTime' => $this->itemsSendShowTime,
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
            'itemsSendAvailableFrom' => $this->itemsSendAvailableFrom,
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

    public function getPlatform(): string {
        return $this->platform;
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

    public function getMachineTimezone(): string
    {
        $timezone = config::load()->other()->getTimezone();
        if ($timezone) {
            return $timezone;
        }
        return self::MACHINE_TIMEZONE;
    }

    public function getMachineNowIso(): string
    {
        $tz = new DateTimeZone($this->getMachineTimezone());
        $dt = new DateTime('now', $tz);
        return $dt->format(DateTime::ATOM);
    }

    public function getMachineNowHuman(string $format = 'd/m/Y H:i:s'): string
    {
        $tz = new DateTimeZone($this->getMachineTimezone());
        $dt = new DateTime('now', $tz);
        return $dt->format($format);
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

    public function setServerData(string $key, array $server_data): void
    {
        $server_data = json_encode($server_data, JSON_UNESCAPED_UNICODE);
        sql::run("DELETE FROM `server_data` WHERE `key` = ? AND `server_id` = ?;", [$key, self::getId()]);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id`) VALUES (?, ?, ?);", [$key, $server_data, self::getId()]);
    }

    public function getItemsSendAvailableFrom(): ?string
    {
        if ($this->itemsSendAvailableFrom) {
            return $this->itemsSendAvailableFrom;
        }
        $dateTime = $this->getItemsSendDateTime();
        if ($dateTime === null) {
            return null;
        }
        $iso = $dateTime->format(DateTime::ATOM);
        $this->itemsSendAvailableFrom = $iso;
        return $iso;
    }

    public function setItemsSendAvailableFrom(?string $iso8601): void
    {
        $this->itemsSendAvailableFrom = $iso8601;
    }

    public function isItemsSendShowTime(): bool
    {
        return $this->itemsSendShowTime ?? true;
    }

    public function setItemsSendShowTime(bool $showTime): void
    {
        $this->itemsSendShowTime = $showTime;
    }

    public function canSendItemsNow(): bool
    {
        $value = $this->getItemsSendAvailableFrom();
        if ($value === null) {
            return true;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return true;
        }
        return time() >= $timestamp;
    }

    public function getItemsSendLockMessage(): string
    {
        $message = "На данный момент нельзя отправлять предметы в игру.";
        $availableFrom = $this->getItemsSendAvailableFrom();
        if ($availableFrom) {
            // Если установлена опция показывать время - показываем полное сообщение с датой
            if ($this->isItemsSendShowTime()) {
                try {
                    $timezone = $this->getMachineTimezone();
                    $dt = new DateTime($availableFrom);
                    $dt->setTimezone(new DateTimeZone($timezone));
                    $remaining = self::formatRemainingTime($dt);
                    $formatted = $dt->format('d:m:Y H:i');
                    return "Отправка предметов будет доступна {$remaining} ({$formatted} {$timezone})";
                } catch (Exception) {
                    // ignore, return default
                }
            }
            // Если showTime = false, возвращаем простое сообщение
            return $message;
        }
        return $message;
    }

    private function getItemsSendDateTime(): ?DateTime
    {
        $timezoneName = $this->getMachineTimezone();
        $serverTz = new DateTimeZone($timezoneName);

        if (!empty($this->itemsSendAvailableFrom)) {
            try {
                return new DateTime($this->itemsSendAvailableFrom);
            } catch (Exception) {
                try {
                    return new DateTime($this->itemsSendAvailableFrom, $serverTz);
                } catch (Exception) {
                    // fallback to legacy below
                }
            }
        }

        $value = $this->getServerData('items_send_available_from');
        if (!$value) {
            return null;
        }
        $raw = trim((string)$value->getVal());
        if ($raw === '') {
            return null;
        }

        // Try ISO formats first
        $dateTime = DateTime::createFromFormat(DateTime::ATOM, $raw);
        if ($dateTime instanceof DateTime) {
            return $dateTime;
        }

        if (defined('DATE_RFC3339_EXTENDED')) {
            $dateTime = DateTime::createFromFormat(DATE_RFC3339_EXTENDED, $raw);
            if ($dateTime instanceof DateTime) {
                return $dateTime;
            }
        }

        // Legacy formats without timezone
        $legacyFormats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];

        foreach ($legacyFormats as $format) {
            $legacy = DateTime::createFromFormat($format, $raw, $serverTz);
            if ($legacy instanceof DateTime) {
                return $legacy;
            }
        }

        try {
            return new DateTime($raw, $serverTz);
        } catch (Exception) {
            return null;
        }
    }

    private static function formatRemainingTime(DateTime $availableFrom): string
    {
        $now = new DateTime('now', $availableFrom->getTimezone());
        if ($availableFrom <= $now) {
            return "в ближайшее время";
        }

        $diff = $now->diff($availableFrom);
        $parts = [];

        if ($diff->d > 0) {
            $parts[] = $diff->d . ' ' . self::declension($diff->d, ['день', 'дня', 'дней']);
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' ' . self::declension($diff->h, ['час', 'часа', 'часов']);
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i . ' ' . self::declension($diff->i, ['минута', 'минуты', 'минут']);
        }
        if ($diff->s > 0 && empty($parts)) {
            $parts[] = $diff->s . ' ' . self::declension($diff->s, ['секунда', 'секунды', 'секунд']);
        }

        if (empty($parts)) {
            return "в ближайшее время";
        }

        return 'через ' . implode(' и ', array_slice($parts, 0, 2));
    }

    private static function declension(int $number, array $forms): string
    {
        $n = abs($number) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $forms[2];
        }
        if ($n1 > 1 && $n1 < 5) {
            return $forms[1];
        }
        if ($n1 == 1) {
            return $forms[0];
        }
        return $forms[2];
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



    /**
     * Получает данные из файлового кэша
     *
     * @param string|null $type Тип кэша
     * @param int|null $server_id ID сервера
     * @return array|null Массив с ключами 'date' и 'data' или null если кэш не найден
     * @throws InvalidArgumentException
     */
    public function getCache(?string $type = null, ?int $server_id = null, $onlyData = true): ?array
    {
        if ($server_id === null) {
            $server_id = $this->getId();
        }

        if (empty($type) || empty($server_id)) {
            throw new InvalidArgumentException('Type and server_id cannot be empty');
        }

        $cacheFilePath = $this->getCacheFilePath($server_id, $type);
        if (!file_exists($cacheFilePath) || !is_readable($cacheFilePath)) {
            return null;
        }

        try {
            // Получаем эксклюзивную блокировку для чтения
            $fileHandle = fopen($cacheFilePath, 'r');
            if ($fileHandle === false) {
                return null;
            }

            if (!flock($fileHandle, LOCK_SH)) {
                fclose($fileHandle);
                return null;
            }

            $content = file_get_contents($cacheFilePath);
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);

            if ($content === false) {
                return null;
            }

            // Извлекаем данные из PHP файла
            $cacheData = $this->extractDataFromPhpFile($content);
            if ($cacheData === null) {
                return null;
            }

            if($onlyData){
                return $cacheData['data'] ?? null;
            }

            // Возвращаем массив с date и data
            return [
                'date' => $cacheData['date_create'] ?? null,
                'data' => $cacheData['data'] ?? null
            ];

        } catch (Exception $e) {
            error_log("Cache read error for server {$server_id}, type {$type}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Сохраняет данные в файловый кэш
     *
     * @param string $type Тип кэша
     * @param mixed $data Данные для сохранения
     * @param int|null $server_id ID сервера
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setCache(string $type, $data, ?int $server_id = null): bool
    {
        if ($server_id === null) {
            $server_id = $this->getId();
        }

        if (empty($type) || empty($server_id)) {
            throw new InvalidArgumentException('Type and server_id cannot be empty');
        }

        $cacheFilePath = $this->getCacheFilePath($server_id, $type);
        $cacheDir = dirname($cacheFilePath);

        // Создаем директорию если её нет
        if (!$this->ensureDirectoryExists($cacheDir)) {
            $this->appendErrorToErrosTxt("Failed to ensure cache directory {$cacheDir} for server {$server_id}, type {$type}");
            return false;
        }

        $cacheContent = [
            'data' => $data,
            'date_create' => time::mysql(),
            'timestamp' => time()
        ];

        $phpContent = $this->generatePhpCacheFile($cacheContent);

        try {
            // Временный файл для атомарной записи
            $tempFile = $cacheFilePath . '.tmp.' . uniqid();

            $fileHandle = fopen($tempFile, 'w');
            if ($fileHandle === false) {
                $this->appendErrorToErrosTxt("Failed to open temp file {$tempFile} for writing (server {$server_id}, type {$type})");
                return false;
            }

            if (!flock($fileHandle, LOCK_EX)) {
                $this->appendErrorToErrosTxt("Failed to obtain exclusive lock on {$tempFile} (server {$server_id}, type {$type})");
                fclose($fileHandle);
                @unlink($tempFile);
                return false;
            }

            $bytesWritten = fwrite($fileHandle, $phpContent);
            fflush($fileHandle);
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);

            if ($bytesWritten === false) {
                $this->appendErrorToErrosTxt("Failed to write content to {$tempFile} (server {$server_id}, type {$type})");
                @unlink($tempFile);
                return false;
            }

            // Атомарное перемещение файла
            if (!rename($tempFile, $cacheFilePath)) {
                $this->appendErrorToErrosTxt("Failed to rename {$tempFile} to {$cacheFilePath} (server {$server_id}, type {$type})");
                @unlink($tempFile);
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->appendErrorToErrosTxt("Cache write exception for server {$server_id}, type {$type}: " . $e->getMessage());
            error_log("Cache write error for server {$server_id}, type {$type}: " . $e->getMessage());
            @unlink($tempFile ?? '');
            return false;
        }
    }

    /**
     * Удаляет кэш для определенного типа и сервера
     *
     * @param string $type Тип кэша
     * @param int|null $server_id ID сервера
     * @return bool
     */
    public function deleteCache(string $type, $server_id = null): bool
    {
        if ($server_id === null) {
            $server_id = $this->getId();
        }

        $cacheFilePath = $this->getCacheFilePath($server_id, $type);

        if (file_exists($cacheFilePath)) {
            return @unlink($cacheFilePath);
        }

        return true;
    }

    /**
     * Очищает весь кэш для сервера
     *
     * @param int|null $server_id ID сервера
     * @return bool
     */
    public function clearServerCache($server_id = null): bool
    {
        if ($server_id === null) {
            $server_id = $this->getId();
        }

        $serverCacheDir = $this->getServerCacheDir($server_id);

        if (!is_dir($serverCacheDir)) {
            return true;
        }

        try {
            $files = glob($serverCacheDir . '/*.php');
            if ($files === false) {
                return false;
            }

            foreach ($files as $file) {
                if (!@unlink($file)) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Clear cache error for server {$server_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получает путь к файлу кэша
     *
     * @param int $server_id ID сервера
     * @param string $type Тип кэша
     * @return string
     */
    private function getCacheFilePath(int $server_id, string $type): string
    {
        // Санитизация имени типа для безопасности файловой системы
        $safeType = preg_replace('/[^a-zA-Z0-9_-]/', '_', $type);
        return $this->getServerCacheDir($server_id) . '/' . $safeType . '.php';
    }

    /**
     * Получает путь к директории кэша сервера
     *
     * @param int $server_id ID сервера
     * @return string
     */
    private function getServerCacheDir(int $server_id): string
    {
        return fileSys::get_dir('/uploads/cache/server/' . intval($server_id));
    }

    /**
     * Создает директорию если её не существует
     *
     * @param string $directory Путь к директории
     * @return bool
     */
    private function ensureDirectoryExists(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        try {
            $result = mkdir($directory, 0755, true);
            if ($result === false) {
                $this->appendErrorToErrosTxt("Failed to create directory: {$directory}");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Directory creation error: " . $e->getMessage());
            $this->appendErrorToErrosTxt("Directory creation exception for {$directory}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Append an error message to uploads/cache/erros.txt (fallback to error_log on failure)
     */
    private function appendErrorToErrosTxt(string $message): void
    {
        try {
            $cacheDir = fileSys::get_dir('/uploads/cache');
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $errorFile = 'erros.txt';
            $timestamp = date('Y-m-d H:i:s');
            $entry = "[{$timestamp}] " . $message . PHP_EOL;
            @file_put_contents($errorFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("Failed to append to erros.txt: " . $e->getMessage());
        }
    }

    /**
     * Генерирует содержимое PHP файла кэша
     *
     * @param array $data Данные для сохранения
     * @return string
     */
    private function generatePhpCacheFile(array $data): string
    {
        $serializedData = serialize($data);
        $encodedData = base64_encode($serializedData);

        $content = "<?php\n";
        $content .= "// Cache file generated at " . date('Y-m-d H:i:s') . "\n";
        $content .= "// Direct access is prohibited\n";
        $content .= "if (!defined('CACHE_ACCESS')) { http_response_code(403); exit('Access Denied'); }\n\n";
        $content .= "return '" . $encodedData . "';\n";
        $content .= "?>";

        return $content;
    }

    /**
     * Извлекает данные из PHP файла кэша
     *
     * @param string $content Содержимое файла
     * @return array|null
     */
    private function extractDataFromPhpFile(string $content): ?array
    {
        try {
            // Разрешаем доступ к кэшу
            if (!defined('CACHE_ACCESS')) {
                define('CACHE_ACCESS', true);
            }

            // Извлекаем закодированные данные
            preg_match("/return '([^']+)';/", $content, $matches);

            if (empty($matches[1])) {
                return null;
            }

            $encodedData = $matches[1];
            $serializedData = base64_decode($encodedData);
            if ($serializedData === false) {
                return null;
            }

            $data = unserialize($serializedData);

            return is_array($data) ? $data : null;

        } catch (Exception $e) {
            error_log("Cache file parsing error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверяет срок действия кэша
     *
     * @param string $type Тип кэша
     * @param int $maxAge Максимальный возраст в секундах
     * @param int|null $server_id ID сервера
     * @return bool
     */
    public function isCacheExpired(string $type, int $maxAge, $server_id = null): bool
    {
        if ($server_id === null) {
            $server_id = $this->getId();
        }

        $cacheFilePath = $this->getCacheFilePath($server_id, $type);

        if (!file_exists($cacheFilePath)) {
            return true;
        }

        try {
            $content = file_get_contents($cacheFilePath);
            if ($content === false) {
                return true;
            }

            $cacheData = $this->extractDataFromPhpFile($content);

            if ($cacheData === null || !isset($cacheData['timestamp'])) {
                return true;
            }

            return (time() - $cacheData['timestamp']) > $maxAge;

        } catch (Exception $e) {
            error_log("Cache expiry check error for server {$server_id}, type {$type}: " . $e->getMessage());
            return true;
        }
    }


}
