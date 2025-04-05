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

    private null|int $technicalSupportThreadId = null;
    private null|int $donationCreditingThreadId = null;
    private null|int $translationGameThreadId = null;
    private null|int $registrationUserThreadId = null;
    private null|int $registrationAccountThreadId = null;
    private null|int $forgetPasswordThreadId = null;
    private null|int $changeAccountPasswordThreadId = null;
    private null|int $changeUserPasswordThreadId = null;
    private null|int $syncAccountThreadId = null;
    private null|int $addStreamThreadId = null;
    private null|int $useWheelThreadId = null;
    private null|int $useBonusCodeThreadId = null;
    private null|int $buyStartPackThreadId = null;
    private null|int $buyShopThreadId = null;
    private null|int $sendWarehouseToGameThreadId = null;
    private null|int $sendPlayerToVillageThreadId = null;

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

        $this->technicalSupportThreadId = filter_var($setting['technicalSupportThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->donationCreditingThreadId = filter_var($setting['donationCreditingThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->translationGameThreadId = filter_var($setting['translationGameThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->registrationUserThreadId = filter_var($setting['registrationUserThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->registrationAccountThreadId = filter_var($setting['registrationAccountThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->forgetPasswordThreadId = filter_var($setting['forgetPasswordThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->changeAccountPasswordThreadId = filter_var($setting['changeAccountPasswordThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->changeUserPasswordThreadId = filter_var($setting['changeUserPasswordThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->syncAccountThreadId = filter_var($setting['syncAccountThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->addStreamThreadId = filter_var($setting['addStreamThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->useWheelThreadId = filter_var($setting['useWheelThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->useBonusCodeThreadId = filter_var($setting['useBonusCodeThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->buyStartPackThreadId = filter_var($setting['buyStartPackThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->buyShopThreadId = filter_var($setting['buyShopThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->sendWarehouseToGameThreadId = filter_var($setting['sendWarehouseToGameThreadId'] ?? null, FILTER_VALIDATE_INT);
        $this->sendPlayerToVillageThreadId = filter_var($setting['sendPlayerToVillageThreadId'] ?? null, FILTER_VALIDATE_INT);

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
        return $this->telegramTokenApi !== null ? trim($this->telegramTokenApi) : null;
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

    public function getTechnicalSupportThreadId(): ?int
    {
        return $this->technicalSupportThreadId;
    }

    public function getDonationCreditingThreadId(): ?int
    {
        return $this->donationCreditingThreadId;
    }

    public function getTranslationGameThreadId(): ?int
    {
        return $this->translationGameThreadId;
    }

    public function getRegistrationUserThreadId(): ?int
    {
        return $this->registrationUserThreadId;
    }

    public function getRegistrationAccountThreadId(): ?int
    {
        return $this->registrationAccountThreadId;
    }

    public function getForgetPasswordThreadId(): ?int
    {
        return $this->forgetPasswordThreadId;
    }

    public function getChangeAccountPasswordThreadId(): ?int
    {
        return $this->changeAccountPasswordThreadId;
    }

    public function getChangeUserPasswordThreadId(): ?int
    {
        return $this->changeUserPasswordThreadId;
    }

    public function getSyncAccountThreadId(): ?int
    {
        return $this->syncAccountThreadId;
    }

    public function getAddStreamThreadId(): ?int
    {
        return $this->addStreamThreadId;
    }

    public function getUseWheelThreadId(): ?int
    {
        return $this->useWheelThreadId;
    }

    public function getUseBonusCodeThreadId(): ?int
    {
        return $this->useBonusCodeThreadId;
    }

    public function getBuyStartPackThreadId(): ?int
    {
        return $this->buyStartPackThreadId;
    }

    public function getBuyShopThreadId(): ?int
    {
        return $this->buyShopThreadId;
    }

    public function getSendWarehouseToGameThreadId(): ?int
    {
        return $this->sendWarehouseToGameThreadId;
    }

    public function getSendPlayerToVillageThreadId(): ?int
    {
        return $this->sendPlayerToVillageThreadId;
    }

}