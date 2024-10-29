<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class background
{

    private null|string $login = null;

    private null|string $registration = null;

    private null|string $forget = null;

    public function __construct()
    {
        $sql        = "SELECT id, `key`, `setting`, `serverId`, `dateUpdate` FROM `settings` WHERE `key` = '__config_background__'";
        $configData = sql::getRow($sql);
        if(!$configData){
            return;
        }
        $setting = json_decode($configData['setting'], true);

        $this->login        = $setting['login'] ?? null;
        $this->registration = $setting['registration'] ?? null;
        $this->forget       = $setting['forget'] ?? null;
    }

    public function login()
    {
        return $this->login;
    }

    public function registration()
    {
        return $this->registration;
    }

    public function forget()
    {
        return $this->forget;
    }

}