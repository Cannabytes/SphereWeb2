<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class background
{

    private null|string $login = null;

    private null|string $registration = null;

    private null|string $forget = null;

    public function __construct($setting)
    {
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