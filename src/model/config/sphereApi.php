<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\fileSys\fileSys;

class sphereApi
{

    private string $ip = '167.235.239.166';

    private int $port = 80;

    public function __construct($setting = null)
    {
        //Проверка на существоване token.php
        if (!file_exists(fileSys::get_dir('/data/token.php'))) {
            return;
        }
        $this->ip = $setting['ip'] ?? $this->ip;
        $this->port = $setting['port'] ?? $this->port;
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