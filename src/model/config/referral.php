<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class referral
{

    private $enable;

    private $time_game;

    private $level;

    private $pvp;

    private $pk;

    private $bonus_amount;

    private $enable_referral_donate_bonus;

    private $procent_donate_bonus;

    private bool $existConfig = false;

    public function __construct()
    {
        $sql        = "SELECT id, `key`, setting, serverId, dateUpdate FROM `settings` WHERE `serverId` = ? AND `key` = '__config_referral__'";
        $configData = sql::getRow($sql, [
          user::self()->getServerId(),
        ]);
        if ( ! $configData) {
            return self::$instance;
        }
        $this->existConfig = true;
        $this->parse($configData['setting']);
    }

    private function parse($json)
    {
        $data = json_decode($json);
        if ($data) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    if (($value === "true" || $value === "false")) {
                        $value      = ($value === "true");
                        $this->$key = $value;
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    /**
     * @return null
     */
    public static function getInstance(): null
    {
        return self::$instance;
    }

    /**
     * Возвращает информацию о том была ли загружена конфигурация.
     * Если конфигурация не загружена, будет использоваться настройки по-умолчанию
     *
     * @return bool
     */
    public function isExistConfig(): bool
    {
        return $this->existConfig;
    }

    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * @return mixed
     */
    public function getTimeGame()
    {
        return $this->time_game;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getPvp()
    {
        return $this->pvp;
    }

    /**
     * @return mixed
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * @return mixed
     */
    public function getBonusAmount()
    {
        return $this->bonus_amount;
    }

    /**
     * @return mixed
     */
    public function getEnableDonateBonusReferral()
    {
        return $this->enable_referral_donate_bonus;
    }

    /**
     * @return mixed
     */
    public function getProcentDonateBonus()
    {
        return $this->procent_donate_bonus;
    }

}