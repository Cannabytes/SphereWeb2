<?php

namespace Ofey\Logan22\model\server;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class serverStatus
{

    private int $online = 0;

    private bool $gameServer = false;

    private bool $loginServer = false;

    private int $serverId;

    private mixed $licenseExpirationDate = null;

    private bool $isEnableStatus = false;

    public function save(): void
    {
        //Очищаем предыдущии записи
        sql::sql("DELETE FROM `server_cache` WHERE `server_id` = ? AND `type` = 'status'", [$this->getServerId()]);

        $data     = [
          'online'                => $this->getOnline(),
          'gameServer'            => $this->getGameServer(),
          'loginServer'           => $this->getLoginServer(),
          'licenseExpirationDate' => $this->licenseExpirationDate,
          'isEnableStatus'        => $this->isEnableStatus,
        ];
        $jsonData = json_encode($data);
        sql::sql("INSERT INTO `server_cache` ( `server_id`, `type`, `data`, `date_create`) VALUES (?, ?, ?, ?)", [
          $this->getServerId(),
          'status',
          $jsonData,
          time::mysql(),
        ]);
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function setServerId(int $serverId): void
    {
        $this->serverId = $serverId;
    }

    public function getOnline(): int
    {
        return $this->online;
    }

    public function setOnline(int $online): void
    {
        $this->online = $online;
    }

    public function getGameServer(): bool
    {
        return $this->gameServer;
    }

    public function setGameServer(bool $status): bool
    {
        return $this->gameServer = $status;
    }

    public function getLoginServer(): bool
    {
        return $this->loginServer;
    }

    public function setLoginServer(bool $status): bool
    {
        return $this->loginServer = $status;
    }

    public function licenseExpirationDate(mixed $licenseExpirationDate)
    {
        $this->licenseExpirationDate = $licenseExpirationDate;
    }

    public function isEnable(): bool
    {
        return $this->isEnableStatus;
    }

    public function setEnable(bool $isEnableStatus): void
    {
        $this->isEnableStatus = $isEnableStatus;
    }

}
