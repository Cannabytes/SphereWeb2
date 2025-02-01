<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;

class other
{

    private bool $openPassword = false;

    private bool $isL2Cursor = false;

    private bool $isExchangeRates = false;

    private ?array $exchangeRates = [];

    private bool $enableTechnicalWork = false;

    private bool $saveStatisticData = false;

    private bool $isAuthShow = false;

    private string $allTitlePage = "";

    private float $onlineMul = 1.0;

    private int $timeoutSaveStatistic = 60 * 5;

    private string $timezone = "Europe/Kyiv";

    private string $messageTechnicalWork = "";

    private string $keywords = "";

    private string $linkMainPage = "/";

    private int $maxAccount = 10;

    private string $contactAdmin = "";

    private bool $autoUpdate = true;
    private bool $isShow404error = false;

    public function __construct($setting)
    {
        $this->openPassword = filter_var($setting['saveOpenPassword'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isL2Cursor = filter_var($setting['isL2Cursor'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isExchangeRates = filter_var($setting['isExchangeRates'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->exchangeRates = is_array($setting['exchangeRates'] ?? null) ? $setting['exchangeRates'] : null;
        $this->enableTechnicalWork = filter_var($setting['enableTechnicalWork'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->saveStatisticData = filter_var($setting['saveStatisticData'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isAuthShow = filter_var($setting['isAuthShow'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->allTitlePage = $setting['allTitlePage'] ?? '';
        $this->onlineMul = (float)($setting['onlinemul'] ?? 1.0);
        $this->timeoutSaveStatistic = (int)(is_array($setting) ? ($setting['timeoutSaveStatistic'] ?? $this->timeoutSaveStatistic) : $this->timeoutSaveStatistic);
        $this->timezone = $setting['timezone'] ?? $this->timezone;
        $this->messageTechnicalWork = $setting['messageTechnicalWork'] ?? $this->messageTechnicalWork;
        $this->keywords = $setting['keywords'] ?? $this->keywords;
        $this->linkMainPage = $setting['linkMainPage'] ?? $this->linkMainPage;
        $this->maxAccount = (int)(is_array($setting) ? ($setting['max_account'] ?? $this->maxAccount) : $this->maxAccount);
        $this->contactAdmin = $setting['contactAdmin'] ?? $this->contactAdmin;
        $this->autoUpdate = filter_var($setting['autoUpdate'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->isShow404error = filter_var($setting['isShow404error'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function isAutoUpdate(): bool
    {
        return $this->autoUpdate;
    }

    public function setAutoUpdate(bool $autoUpdate): void
    {
        $this->autoUpdate = $autoUpdate;
    }

    public function getLinkMainPage(): string
    {
        return $this->linkMainPage;
    }

    public function getOpenPassword(): bool
    {
        return $this->openPassword;
    }

    public function isL2Cursor(): bool
    {
        return $this->isL2Cursor;
    }

    public function isExchangeRates(): bool
    {
        return $this->isExchangeRates;
    }

    public function getExchangeRates(): ?array
    {
        return $this->exchangeRates;
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
        if ($this->onlineMul <= 0) {
            $this->onlineMul = 1.0;
        }
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

    public function save(): void
    {
        $data = json_encode([
            'saveOpenPassword' => $this->openPassword,
            'isL2Cursor' => $this->isL2Cursor,
            'isExchangeRates' => $this->isExchangeRates,
            'exchangeRates' => $this->exchangeRates,
            'enableTechnicalWork' => $this->enableTechnicalWork,
            'saveStatisticData' => $this->saveStatisticData,
            'isAuthShow' => $this->isAuthShow,
            'allTitlePage' => $this->allTitlePage,
            'onlinemul' => $this->onlineMul,
            'timeoutSaveStatistic' => $this->timeoutSaveStatistic,
            'timezone' => $this->timezone,
            'messageTechnicalWork' => $this->messageTechnicalWork,
            'keywords' => $this->keywords,
            'linkMainPage' => $this->linkMainPage,
            'max_account' => $this->maxAccount,
            'contactAdmin' => $this->contactAdmin,
        ]);

        sql::sql("DELETE FROM `settings` WHERE `key` = ? AND serverId = ? ", [
            '__config_other__',
            0,
        ]);

        sql::run(
            "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
            [
                '__config_other__',
                html_entity_decode($data),
                0,
                time::mysql(),
            ]
        );
    }

    public function setRates($rates): void
    {
        $this->exchangeRates = $rates;
    }

    public function isShow404error()
    {
        return $this->isShow404error;
    }

}