<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class sphereApi
{

    private string $ip = '167.235.239.166';

    private int $port = 80;

    public function __construct()
    {
        $configData = sql::getRow("SELECT `setting` FROM `settings` WHERE `key` = '__config_sphere_api__'");
        if ($configData) {
            $setting    = json_decode($configData['setting'], true);
            $this->ip   = $setting['ip'] ?? $this->ip;
            $this->port = $setting['port'] ?? $this->port;
        }
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

}