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
    private bool $enableLoginServerMySQL = false;
    private bool $enableGameServerMySQL = false;
    private int $portGameStatusServer = -1;
    private int $portLoginStatusServer = -1;
    private string $gameIPStatusServer = '0.0.0.0';
    private string $loginIPStatusServer = '0.0.0.0';

    public function save(): void
    {

        $data = [
            'online' => $this->online,
            'isEnable' => $this->isEnable(),
            'gameServer' => $this->getGameServer(),
            'loginServer' => $this->getLoginServer(),
            'gameServerIP' => $this->getGameIPStatusServer(),
            'gameServerPort' => $this->getGamePortStatusServer(),
            'loginServerIP' => $this->getLoginIPStatusServer(),
            'loginServerPort' => $this->getLoginPortStatusServer(),
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

    public function getOnline(): int
    {
        $online = $this->online;

        if (config::load()->onlineCheating()->isEnabled()) {
            $cheatingDetails = config::load()->onlineCheating()->getCheatingDetails();
            $currentTime = new DateTime();
            foreach ($cheatingDetails as $key => $details) {
                foreach ($details as $index => $detail) {
                    if ($detail->getTime() == "" or $detail->getMultiplier() == "") {
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

    public function isEnable(): bool
    {
        return $this->isEnableStatus;
    }

    public function setEnable(bool $isEnableStatus): void
    {
        $this->isEnableStatus = $isEnableStatus;
    }

    public function isEnableLoginServerMySQL(): bool
    {
        return $this->enableLoginServerMySQL;
    }

    public function setEnableLoginServerMySQL(bool $b)
    {
        $this->enableLoginServerMySQL = $b;
    }

    public function isEnableGameServerMySQL(): bool
    {
        return $this->enableGameServerMySQL;
    }

    public function setEnableGameServerMySQL(bool $b)
    {
        $this->enableGameServerMySQL = $b;
    }

    public function setGamePortStatusServer($port = -1): int
    {
        return $this->portGameStatusServer = $port;
    }

    public function getGamePortStatusServer(): int
    {
        return $this->portGameStatusServer;
    }

    public function setLoginPortStatusServer($port = -1): int
    {
        return $this->portLoginStatusServer = $port;
    }

    public function getLoginPortStatusServer(): int
    {
        return $this->portLoginStatusServer;
    }

    public function getGameIPStatusServer(): string
    {
        return $this->gameIPStatusServer ?? '0.0.0.0';
    }

    public function setGameIPStatusServer($ip = '0.0.0.0'): string
    {
        return $this->gameIPStatusServer = $ip;
    }

    public function getLoginIPStatusServer(): string
    {
        return $this->loginIPStatusServer;
    }

    public function setLoginIPStatusServer($ip = '0.0.0.0'): string
    {
        return $this->loginIPStatusServer = $ip;
    }


}
