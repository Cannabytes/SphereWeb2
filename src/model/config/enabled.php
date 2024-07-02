<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class enabled
{

    private bool $enable_news = true;

    private bool $enable_shop = true;

    private bool $enable_balance = true;

    private bool $enable_statistic = true;

    private bool $enable_referral = true;

    private bool $enable_ticket = true;

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_enabled__'");
        if($configData){
            $setting    = json_decode($configData['setting'], true);
            $this->enable_news      = filter_var($setting['enable_news'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_shop      = filter_var($setting['enable_shop'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_balance   = filter_var($setting['enable_balance'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_statistic = filter_var($setting['enable_statistic'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_referral  = filter_var($setting['enable_referral'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_ticket    = filter_var($setting['enable_ticket'], FILTER_VALIDATE_BOOLEAN);
        }
    }

    public function isEnableNews(): bool
    {
        return $this->enable_news;
    }

    public function isEnableShop(): bool
    {
        return $this->enable_shop;
    }

    public function isEnableBalance(): bool
    {
        return $this->enable_balance;
    }

    public function isEnableStatistic(): bool
    {
        return $this->enable_statistic;
    }

    public function isEnableReferral(): bool
    {
        return $this->enable_referral;
    }

    public function isEnableTicket(): bool
    {
        return $this->enable_ticket;
    }

}