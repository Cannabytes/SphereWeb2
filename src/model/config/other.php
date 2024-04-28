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

    public function __construct()
    {
        $configData = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__config_other__'"
        );

        $setting = json_decode($configData['setting'], true);

        $this->openPassword = filter_var(
          $setting['saveOpenPassword'],
          FILTER_VALIDATE_BOOLEAN
        );

        $this->enableTechnicalWork = filter_var(
          $setting['enableTechnicalWork'],
          FILTER_VALIDATE_BOOLEAN
        );

        $this->saveStatisticData = filter_var(
          $setting['saveStatisticData'],
          FILTER_VALIDATE_BOOLEAN
        );

        $this->isAuthShow = filter_var(
          $setting['isAuthShow'],
          FILTER_VALIDATE_BOOLEAN
        );

        $this->allTitlePage         = $setting['allTitlePage'];
        $this->onlineMul            = (float)$setting['onlinemul'];
        $this->timeoutSaveStatistic = (int)$setting['timeoutSaveStatistic'];
        $this->timezone             = $setting['timezone'];
        $this->messageTechnicalWork = $setting['messageTechnicalWork'];
        $this->keywords             = $setting['keywords'];
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

}