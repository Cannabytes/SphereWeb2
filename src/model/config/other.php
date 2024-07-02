<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class other
{

    private bool $openPassword = false;

    private bool $enableTechnicalWork = false;

    private bool $saveStatisticData = false;

    private bool $isAuthShow = false;

    private string $allTitlePage = "";

    private float $onlineMul = 0;

    private int $timeoutSaveStatistic = 0;

    private string $timezone = "";

    private string $messageTechnicalWork = "";

    private string $keywords = "";

    private string $linkMainPage = "/";

    private int $maxAccount = 30;

    private string $contactAdmin = "";

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_other__'");

        if ( ! $configData) {
            $configData = [
              'setting' => json_encode([
                'saveOpenPassword'     => false,
                'enableTechnicalWork'  => false,
                'saveStatisticData'    => false,
                'isAuthShow'           => false,
                'allTitlePage'         => '',
                'onlinemul'            => 1.0,
                'timeoutSaveStatistic' => 60,
                'timezone'             => 'UTC',
                'messageTechnicalWork' => '',
                'keywords'             => '',
                'linkMainPage'         => '/',
                'max_account'          => 30,
                'contactAdmin'         => '',
              ]),
            ];
        }

        $setting = json_decode($configData['setting'], true);

        $this->openPassword         = filter_var($setting['saveOpenPassword'], FILTER_VALIDATE_BOOLEAN);
        $this->enableTechnicalWork  = filter_var($setting['enableTechnicalWork'], FILTER_VALIDATE_BOOLEAN);
        $this->saveStatisticData    = filter_var($setting['saveStatisticData'], FILTER_VALIDATE_BOOLEAN);
        $this->isAuthShow           = filter_var($setting['isAuthShow'], FILTER_VALIDATE_BOOLEAN);
        $this->allTitlePage         = $setting['allTitlePage'];
        $this->onlineMul            = (float)$setting['onlinemul'];
        $this->timeoutSaveStatistic = (int)$setting['timeoutSaveStatistic'];
        $this->timezone             = $setting['timezone'];
        $this->messageTechnicalWork = $setting['messageTechnicalWork'];
        $this->keywords             = $setting['keywords'];
        $this->linkMainPage         = $setting['linkMainPage'] ?? '/';
        $this->maxAccount           = (int)$setting['max_account'] ?? 30;
        $this->contactAdmin         = $setting['contactAdmin'] ?? '';
    }

    public function getLinkMainPage(): string
    {
        return $this->linkMainPage;
    }

    public function getOpenPassword(): bool
    {
        return $this->openPassword;
    }

    public function getEnableTechnicalWork(): bool
    {
        return $this->enableTechnicalWork;
    }

    public function getSaveStatisticData(): bool
    {
        return $this->saveStatisticData;
    }

    public function getIsAuthShow(): bool
    {
        return $this->isAuthShow;
    }

    public function getAllTitlePage(): string
    {
        return $this->allTitlePage;
    }

    public function getOnlineMul(): float
    {
        return $this->onlineMul;
    }

    public function getTimeoutSaveStatistic(): int
    {
        return $this->timeoutSaveStatistic;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getMessageTechnicalWork(): string
    {
        return $this->messageTechnicalWork;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function getMaxAccount(): int
    {
        return $this->maxAccount;
    }

    public function getContactAdmin(): string
    {
        return $this->contactAdmin;
    }

}