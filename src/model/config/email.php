<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

class email
{

    private ?string $url = "";

    private string $emailHost = "";

    private string $emailUsername = "";

    private string $emailPassword = "";

    private string $emailFrom = "";

    private string $emailPort = "";

    private bool $emailSMTPAuth = true;

    private string $emailProtocol = "SMTP";

    public function __construct($setting)
    {
        $this->url = !empty($setting['url']) ? $setting['url'] : "";
        $this->emailHost = $setting['emailHost'] ?? '';
        $this->emailUsername = $setting['emailUsername'] ?? '';
        $this->emailPassword = $setting['emailPassword'] ?? '';
        $this->emailFrom = $setting['emailFrom'] ?? '';
        $this->emailPort = $setting['emailPort'] ?? '';
        $this->emailProtocol = $setting['emailProtocol'] ?? '';
        $this->emailSMTPAuth = filter_var(
            $setting['emailSMTPAuth'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function getURL(): string
    {
        return $this->url;
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

    public function getEmailFrom(): string
    {
        if ($this->emailFrom == '') {
            return $this->emailHost;
        }
        return $this->emailFrom;
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