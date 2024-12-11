<?php

namespace Ofey\Logan22\model\config;

class notice
{
    private ?string $telegramTokenApi;
    private string $telegramChatID = "";
    private bool $technicalSupport = false;
    private bool $donationCrediting = false;

    public function __construct($setting)
    {
        $this->telegramTokenApi = $setting['telegramTokenApi'] ?? null;
        $this->telegramChatID = (string)$setting['telegramChatID'] ?? "";
        $this->technicalSupport = filter_var($setting['technicalSupport'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->donationCrediting = filter_var($setting['donationCrediting'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    //Проверка включено ли уведомление по телеграмму
    public function isTelegramEnable(): bool
    {
        if ($this->getTelegramTokenApi() == "" or $this->getTelegramTokenApi() == null) {
            return false;
        }
        return true;
    }

    public function getTelegramTokenApi(): ?string
    {
        return trim($this->telegramTokenApi);
    }

    public function getTelegramChatID(): string
    {
        return $this->telegramChatID;
    }

    //Включена отправка уведомления, если пользователь отправил сообщение в техподдержку
    public function getTechnicalSupport(): ?bool
    {
        return $this->technicalSupport;
    }

    //Включена отправка уведомления, если пользователь пополнил баланс
    public function getDonationCrediting(): ?bool
    {
        return $this->donationCrediting;
    }


}