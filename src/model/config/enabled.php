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

    private bool $enable_send_balance_game = true;

    private bool $enable_bonus_code = true;

    private bool $enable_stream = true;

    private bool $enable_emulation = false;

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_enabled__'");
        if($configData){
            $setting    = json_decode($configData['setting'], true);
            $this->enable_news      = (bool)filter_var($setting['enable_news'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_shop      = (bool)filter_var($setting['enable_shop'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_balance   = (bool)filter_var($setting['enable_balance'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_statistic = (bool)filter_var($setting['enable_statistic'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_referral  = (bool)filter_var($setting['enable_referral'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_ticket    = (bool)filter_var($setting['enable_ticket'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_send_balance_game    = (bool)filter_var($setting['enable_send_balance_game'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_bonus_code    = (bool)filter_var($setting['enable_bonus_code'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_stream    = (bool)filter_var($setting['enable_stream'], FILTER_VALIDATE_BOOLEAN);
            $this->enable_emulation    = (bool)filter_var($setting['enable_emulation'], FILTER_VALIDATE_BOOLEAN);
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

    public function isEnableSendBalanceGame(): bool
    {
        return $this->enable_send_balance_game;
    }

    public function isEnableBonusCode(): bool
    {
        return $this->enable_bonus_code;
    }

    public function isEnableStream(): bool
    {
        return $this->enable_stream;
    }

    public function isEnableEmulation(): bool
    {
        return $this->enable_emulation;
    }

}