<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class email
{

    private string $emailHost = "";

    private string $emailUsername = "";

    private string $emailPassword = "";

    private string $emailPort  = "";

    private bool $emailSMTPAuth = true;

    private string $emailProtocol = "SMTP";

    public function __construct()
    {
        $configData          = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__config_email__'"
        );
        if($configData){
            $setting             = json_decode($configData['setting'], true);
            $this->emailHost     = $setting['emailHost'];
            $this->emailUsername = $setting['emailUsername'];
            $this->emailPassword = $setting['emailPassword'];
            $this->emailPort     = $setting['emailPort'];
            $this->emailProtocol = $setting['emailProtocol'];
            $this->emailSMTPAuth = filter_var(
              $setting['emailSMTPAuth'],
              FILTER_VALIDATE_BOOLEAN
            );
        }
    }

    public function getHost(): string
    {
        return $this->emailHost;
    }

    public function getUsername(): string
    {
        return $this->emailUsername;
    }

    public function getPassword(): string
    {
        return $this->emailPassword;
    }

    public function getPort(): string
    {
        return $this->emailPort;
    }

    public function isSmtpAuth(): bool
    {
        return $this->emailSMTPAuth;
    }

    public function getProtocol(): string
    {
        return $this->emailProtocol;
    }

}