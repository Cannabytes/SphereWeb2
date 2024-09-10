<?php

namespace Ofey\Logan22\model\server;

use DateInterval;
use DateTime;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class serverStatus
{

    private int $online = 0;

    private bool $gameServer = false;

    private bool $loginServer = false;

    private int $serverId;

    private bool $isEnableStatus = false;

    private ?bool $disabled = null;

    public function save(): void
    {
        //Очищаем предыдущии записи
        sql::sql("DELETE FROM `server_cache` WHERE `server_id` = ? AND `type` = 'status'", [$this->getServerId()]);

        $data     = [
          'online'                => $this->online,
          'gameServer'            => $this->getGameServer(),
          'loginServer'           => $this->getLoginServer(),
          'isEnableStatus'        => $this->isEnableStatus,
          'disabled'              => $this->disabled
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
        $online = $this->online;

        if (config::load()->onlineCheating()->isEnabled()) {
            $cheatingDetails = config::load()->onlineCheating()->getCheatingDetails();
            $currentTime = new DateTime();
            foreach ($cheatingDetails as $key => $details) {
                foreach ($details as $index => $detail) {
                    if($detail->getTime() == "" OR $detail->getMultiplier() == ""){
                        continue;
                    }
                    $startTime = DateTime::createFromFormat('H:i', $detail->getTime());
                    $nextIndex = $index + 1;
                    if ($nextIndex < count($details)) {
                        $endTime = DateTime::createFromFormat('H:i', $details[$nextIndex]->getTime());
                    } else {
                        $endTime = (clone $startTime)->add(new DateInterval('PT30M'));
                    }
                    if ($currentTime >= $startTime && $currentTime < $endTime) {
                        $online *= (float)$detail->getMultiplier();
                        break 2;
                    }
                }
            }
        }
        return (int)config::load()->other()->getOnlineMul() * $online;
    }

    public function setOnline(int $online): void
    {
        $this->online = $online;
    }

    public function getGameServer(): bool
    {
        if( ! $this->gameServer AND $this->online >= 1){
            return true;
        }
        return $this->gameServer;
    }

    public function setGameServer(bool $status): bool
    {
        return $this->gameServer = $status;
    }

    public function getLoginServer(): bool
    {
        if( ! $this->loginServer AND $this->online >= 1){
            return true;
        }
        return $this->loginServer;
    }

    public function setLoginServer(bool $status): bool
    {
        return $this->loginServer = $status;
    }

    public function isEnable(): bool
    {
        return $this->isEnableStatus;
    }

    public function setEnable(bool $isEnableStatus): void
    {
        $this->isEnableStatus = $isEnableStatus;
    }


    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): void
    {
        $this->disabled = $disabled;
    }


}
