<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class onlineCheating
{

    private bool $enabled = false;

    private int $minOnlineShow = 0;

    private int $maxOnlineShow = 0;

    private array $cheatingDetails = [];

    public function __construct()
    {
        $configData            = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__config_cheating__'"
        );
        $setting               = json_decode($configData['setting'], true);
        $this->enabled         = filter_var(
          $setting['isEnableOnlineCheaters'],
          FILTER_VALIDATE_BOOLEAN
        );
        $this->minOnlineShow   = (int)$setting['minOnlineShow'];
        $this->maxOnlineShow   = (int)$setting['maxOnlineShow'];

        foreach($setting['cheatingDetails'] AS $online => $values){
            foreach($values AS $value){
                $onlineCheatDetails = new onlineCheatingTimeDetails($value);
                if($onlineCheatDetails!==null){
                    $this->cheatingDetails[$online][] = $onlineCheatDetails;
                }
            }
        }
    }

    /**
     * Включен ли увеличение онлайна
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Минимальный диапазон онлайна, который будет выводиться
     *
     * @return int
     */
    public function getMinOnlineShow(): int
    {
        return $this->minOnlineShow;
    }

    /**
     * Максимальный диапазон онлайна, который будет выводиться
     *
     * @return int
     */
    public function getMaxOnlineShow(): int
    {
        return $this->maxOnlineShow;
    }

    /**
     * Массив, где ключ кол-во онлайн, значение массив, где время и множитель
     *
     * @return onlineCheatingTimeDetails[] массив, где ключи - целые числа
     */
    public function getCheatingDetails(): array
    {
        return $this->cheatingDetails;
    }

}

class onlineCheatingTimeDetails
{
    private string $time;
    private string $multiplier;
    public function __construct($value) {
        $this->time = $value['time'];
        $this->multiplier = $value['multiplier'];
        if($this->time == null or $this->time == ""){
            return null;
        }
        if($this->multiplier == null or $this->multiplier == ""){
            return null;
        }
        return $this;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getMultiplier()
    {
        return $this->multiplier;
    }
}
