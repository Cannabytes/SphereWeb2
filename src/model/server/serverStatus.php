<?php

namespace Ofey\Logan22\model\server;

class serverStatus
{

    private int $online = 0;

    private bool $gameServer = false;

    private bool $loginServer = false;

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

    public function setLoginServer(bool $status)
    {
        return $this->loginServer = $status;
    }

}
