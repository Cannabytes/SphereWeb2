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
        $configData = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__config_cheating__'"
        );
        if ($configData) {
            $setting             = json_decode($configData['setting'], true);
            $this->enabled       = filter_var(
              $setting['isEnableOnlineCheaters'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->minOnlineShow = (int)$setting['minOnlineShow'];
            $this->maxOnlineShow = (int)$setting['maxOnlineShow'];

            foreach ($setting['cheatingDetails'] as $online => $values) {
                foreach ($values as $value) {
                    $onlineCheatDetails = new onlineCheatingTimeDetails($value);
                    if ($onlineCheatDetails !== null) {
                        $this->cheatingDetails[$online][] = $onlineCheatDetails;
                    }
                }
            }
        }else{
            $this->cheatingDetails[1] = [
                new onlineCheatingTimeDetails([
                    'time'       => '00:00',
                    'multiplier' => '1.3',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '00:30',
                    'multiplier' => '1.34',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '01:00',
                    'multiplier' => '1.35',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '01:30',
                    'multiplier' => '1.41',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '02:00',
                    'multiplier' => '1.5',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '02:30',
                    'multiplier' => '1.6',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '03:00',
                    'multiplier' => '1.7',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '03:30',
                    'multiplier' => '1.8',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '04:00',
                    'multiplier' => '1.9',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '04:30',
                    'multiplier' => '2',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '05:00',
                    'multiplier' => '2.1',
                ]),
            ];
            $this->cheatingDetails[20] = [
                new onlineCheatingTimeDetails([
                    'time'       => '00:00',
                    'multiplier' => '1.4',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '00:30',
                    'multiplier' => '1.47',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '01:00',
                    'multiplier' => '1.55',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '01:30',
                    'multiplier' => '1.57',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '02:00',
                    'multiplier' => '1.59',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '02:30',
                    'multiplier' => '1.66',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '03:00',
                    'multiplier' => '1.7',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '03:30',
                    'multiplier' => '1.8',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '04:00',
                    'multiplier' => '1.9',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '04:30',
                    'multiplier' => '2',
                ]),
                new onlineCheatingTimeDetails([
                    'time'       => '05:00',
                    'multiplier' => '2.1',
                ]),
            ];
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

    public function __construct($value)
    {
        $this->time       = $value['time'];
        $this->multiplier = $value['multiplier'];
        if ($this->time == null or $this->time == "") {
            return null;
        }
        if ($this->multiplier == null or $this->multiplier == "") {
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
