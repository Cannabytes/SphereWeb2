<?php

namespace Ofey\Logan22\model\config;

class notice
{
    private ?string $telegramTokenApi = null;
    private string $telegramChatID = "";
    private string $noticeLang = "en";
    private bool $isTechnicalSupport = false;
    private bool $isDonationCrediting = false;
    private bool $isTranslationGame = false;
    private bool $isRegistrationUser = false;
    private bool $isRegistrationAccount = false;
    private bool $isForgetPassword = false;
    private bool $isChangeAccountPassword = false;
    private bool $isChangeUserPassword = false;
    private bool $isSyncAccount = false;
    private bool $isAddStream = false;
    private bool $isUseWheel = false;
    private bool $isUseBonusCode = false;
    private bool $isBuyStartPack = false;
    private bool $isBuyShop = false;
    private bool $isSendWarehouseToGame = false;
    private bool $isSendPlayerToVillage = false;

    public function __construct($setting = null)
    {
        if ($setting == null) {
            return;
        }
        $this->telegramTokenApi = $setting['telegramTokenApi'] ?? null;
        $this->telegramChatID = isset($setting['telegramChatID']) ? (string)$setting['telegramChatID'] : "";
        $this->noticeLang = isset($setting['noticeLang']) ? (string)$setting['noticeLang'] : "en";
        $this->isTechnicalSupport = filter_var($setting['isTechnicalSupport'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isDonationCrediting = filter_var($setting['isDonationCrediting'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isTranslationGame = filter_var($setting['isTranslationGame'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isRegistrationUser = filter_var($setting['isRegistrationUser'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isRegistrationAccount = filter_var($setting['isRegistrationAccount'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isForgetPassword = filter_var($setting['isForgetPassword'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isChangeAccountPassword = filter_var($setting['isChangeAccountPassword'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isChangeUserPassword = filter_var($setting['isChangeUserPassword'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isSyncAccount = filter_var($setting['isSyncAccount'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isAddStream = filter_var($setting['isAddStream'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isUseWheel = filter_var($setting['isUseWheel'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isUseBonusCode = filter_var($setting['isUseBonusCode'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isBuyStartPack = filter_var($setting['isBuyStartPack'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isBuyShop = filter_var($setting['isBuyShop'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isSendWarehouseToGame = filter_var($setting['isSendWarehouseToGame'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isSendPlayerToVillage = filter_var($setting['isSendPlayerToVillage'] ?? false, FILTER_VALIDATE_BOOLEAN);
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

    public function getNoticeLang(): string
    {
        return $this->noticeLang;
    }

    //Включена отправка уведомления, если пользователь отправил сообщение в техподдержку
    public function isTechnicalSupport(): ?bool
    {
        return $this->isTechnicalSupport;
    }

    //Включена отправка уведомления, если пользователь пополнил баланс
    public function isDonationCrediting(): ?bool
    {
        return $this->isDonationCrediting;
    }

    public function isTranslationGame(): bool
    {
        return $this->isTranslationGame;
    }

    public function isRegistrationUser(): bool
    {
        return $this->isRegistrationUser;
    }

    public function isRegistrationAccount(): bool
    {
        return $this->isRegistrationAccount;
    }

    public function isForgetPassword(): bool
    {
        return $this->isForgetPassword;
    }

    public function isChangeAccountPassword(): bool
    {
        return $this->isChangeAccountPassword;
    }

    public function isChangeUserPassword(): bool
    {
        return $this->isChangeUserPassword;
    }

    public function isSyncAccount(): bool
    {
        return $this->isSyncAccount;
    }

    public function isAddStream(): bool
    {
        return $this->isAddStream;
    }

    public function isUseWheel(): bool
    {
        return $this->isUseWheel;
    }

    public function isUseBonusCode(): bool
    {
        return $this->isUseBonusCode;
    }

    public function isBuyStartPack(): bool
    {
        return $this->isBuyStartPack;
    }

    public function isBuyShop(): bool
    {
        return $this->isBuyShop;
    }

    public function isSendWarehouseToGame(): bool
    {
        return $this->isSendWarehouseToGame;
    }

    public function isSendPlayerToVillage(): bool
    {
        return $this->isSendPlayerToVillage;
    }

}