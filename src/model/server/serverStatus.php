<?php

namespace Ofey\Logan22\model\server;

use DateInterval;
use DateTime;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class serverStatus
{

    private int $online = 0;

    private bool $gameServer = false;
    private bool $gameServerRealConnection = false;

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
        $serverId = $this->getServerId();
        $cacheDir = fileSys::get_dir('uploads/cache/server/' . $serverId);
        
        // Удаляем старые файлы кэша (больше 60 сек)
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } else {
            mkdir($cacheDir, 0755, true);
        }
        
        $data = [
            'online' => $this->online,
            'isEnable' => $this->isEnable(),
            'loginServerDB' => $this->isEnableLoginServerMySQL(),
            'gameServerDB' => $this->isEnableGameServerMySQL(),
            'gameServer' => $this->getGameServer(),
            'gameServerRealConnection' => $this->getGameServerRealCollection(),
            'loginServer' => $this->getLoginServer(),
            'gameServerIP' => $this->getGameIPStatusServer(),
            'gameServerPort' => $this->getGamePortStatusServer(),
            'loginServerIP' => $this->getLoginIPStatusServer(),
            'loginServerPort' => $this->getLoginPortStatusServer(),
        ];
        
        $unixTime = time();
        $jsonFile = $cacheDir . '/' . $unixTime . '.json';
        file_put_contents($jsonFile, json_encode($data));
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
        if ($this->getOnline() > 0 ){
            $this->gameServer = true;
        }
        return $this->gameServer;
    }

    public function getGameServerRealCollection(): bool
    {
        return $this->gameServerRealConnection;
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
        return (int)(config::load()->other()->getOnlineMul() * $this->online);
    }

    public function setOnline(int $online, bool $isCache = false ): void
    {
        $onlineCheating = config::load()->onlineCheating()->isEnabled();
        $minOnline = config::load()->onlineCheating()->getMinOnlineShow();
        $maxOnline = config::load()->onlineCheating()->getMaxOnlineShow();

        if ($isCache) {
            $this->online = $online;
            if ($onlineCheating && $online == 0) {
                $online = mt_rand($minOnline, $maxOnline);
            }
        } else {
            if ($onlineCheating && $online == 0) {
                $online = mt_rand($minOnline, $maxOnline);
            }else{
                    if (config::load()->onlineCheating()->isEnabled()) {
                        $cheatingDetails = config::load()->onlineCheating()->getCheatingDetails();
                        $currentTime = new DateTime();
                        foreach ($cheatingDetails as $key => $details) {
                            foreach ($details as $index => $detail) {
                                if ($detail->getTime() == "" or $detail->getMultiplier() == "") {
                                    continue;
                                }
                                $startTime = DateTime::createFromFormat('H:i', $detail->getTime());
                                $startTime->setDate($currentTime->format('Y'), $currentTime->format('m'), $currentTime->format('d'));
                                
                                $nextIndex = $index + 1;
                                if ($nextIndex < count($details)) {
                                    $endTime = DateTime::createFromFormat('H:i', $details[$nextIndex]->getTime());
                                    $endTime->setDate($currentTime->format('Y'), $currentTime->format('m'), $currentTime->format('d'));
                                } else {
                                    $endTime = (clone $startTime)->add(new DateInterval('P1D'))->setTime(0, 0, 0);
                                }
                                if ($currentTime >= $startTime && $currentTime < $endTime) {
                                    $online *= (float)$detail->getMultiplier();
                                    break 2;
                                }
                            }
                        }
                    }
                }
        }

        if(!$this->gameServer and $online >= 1){
            $this->gameServer = true;
        }

        $this->online = $online;
    }

    public function isEnable(): bool
    {
        return $this->isEnableStatus;
    }

    public function setEnable(bool $isEnableStatus = false): void
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

    public function setGameServerRealConnection(mixed $param): void
    {
        $this->gameServerRealConnection = $param;
    }

    public function getGameServerRealConnection(): bool
    {
        return $this->gameServerRealConnection;
    }


}
