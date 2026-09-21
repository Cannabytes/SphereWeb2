<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\userModel;

class enabled
{

    private bool $enable_news = true;

    private bool $enable_shop = true;

    private bool $enable_balance = true;

    private bool $balance_character_level_required = false;

    private int $balance_character_min_level = 5;

    private bool $enable_statistic = true;

    private bool $enable_support = true;

    private bool $enable_send_balance_game = true;

    private bool $enable_bonus_code = true;

    private bool $enable_stream = true;

    private bool $enable_emulation = false;

    public function __construct($setting)
    {
            $this->enable_news      = (bool)filter_var($setting['enable_news'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_shop      = (bool)filter_var($setting['enable_shop'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_balance   = (bool)filter_var($setting['enable_balance'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->balance_character_level_required = (bool)filter_var($setting['balance_character_level_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $minLevel = filter_var($setting['balance_character_min_level'] ?? 5, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 255],
            ]);
            $this->balance_character_min_level = $minLevel === false ? 5 : $minLevel;
            $this->enable_statistic = (bool)filter_var($setting['enable_statistic'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_support    = (bool)filter_var($setting['enable_support'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_send_balance_game    = (bool)filter_var($setting['enable_send_balance_game'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_bonus_code    = (bool)filter_var($setting['enable_bonus_code'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_stream    = (bool)filter_var($setting['enable_stream'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->enable_emulation    = (bool)filter_var($setting['enable_emulation'] ?? false, FILTER_VALIDATE_BOOLEAN);
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

    public function isBalanceCharacterLevelRequired(): bool
    {
        return $this->balance_character_level_required;
    }

    public function getBalanceCharacterMinLevel(): int
    {
        return $this->balance_character_min_level;
    }

    public function canTopUpBalance(?userModel $user): bool
    {
        if (!$this->balance_character_level_required) {
            return true;
        }

        if ($user === null || !$user->isAuth()) {
            return false;
        }

        foreach ($user->getLoadAccounts() ?? [] as $account) {
            foreach ($account->getCharacters() as $character) {
                if ($character->getLevel() >= $this->balance_character_min_level) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isEnableStatistic(): bool
    {
        return $this->enable_statistic;
    }

    public function isEnableSupport(): bool
    {
        return $this->enable_support;
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
