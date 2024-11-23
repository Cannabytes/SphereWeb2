<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\user\user;

class referral
{

    private static $instance;

    private $enable;

    private string|int $time_game = 0;

    private string|int $level = 0;

    private string|int $pvp = 0;

    private string|int $pk = 0;

    private float|int|string $bonus_amount = 0;

    private $enable_referral_donate_bonus;

    private $procent_donate_bonus;

    private $leader_bonus_items = null;

    private $slave_bonus_items = null;

    private int $serverId = -1;

    private bool $existConfig = false;

    private null|array $itemsSlave = null;

    private null|array $itemsLeader = null;

    public function __construct($id)
    {
        $sql        = "SELECT id, `key`, `setting`, `serverId`, `dateUpdate` FROM `settings` WHERE `serverId` = ? AND `key` = '__config_referral__'";
        $configData = sql::getRow($sql, [$id]);

        if ( ! $configData) {
            return self::$instance;
        }
        $this->existConfig = true;
        $this->serverId = $configData['serverId'];
        $this->parse($configData['setting']);
    }

    public function getServerId(): int
    {
        return $this->serverId;
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

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

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
    public function isEnable()
    {
        return \Ofey\Logan22\controller\config\config::load()->enabled()->isEnableReferral();
    }

    /**
     * @return mixed
     */
    public function getTimeGame(): int
    {
        return (int)$this->time_game;
    }

    /**
     * @return mixed
     */
    public function getLevel(): int
    {
        return (int)$this->level;
    }

    /**
     * @return mixed
     */
    public function getPvp(): int
    {
        return (int)$this->pvp;
    }

    /**
     * @return mixed
     */
    public function getPk()
    {
        return (int)$this->pk;
    }


    public function getBonusAmount(): int|float
    {
        if (is_string($this->bonus_amount)) {
            if ($this->bonus_amount==""){
                $this->bonus_amount = 0;
                return $this->bonus_amount;
            }
            if (ctype_digit($this->bonus_amount)) {
                return (int)$this->bonus_amount;
            }
            if (is_numeric($this->bonus_amount)) {
                return (float)$this->bonus_amount;
            }
        }
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

    public function getSlaveBonusItems(): ?array
    {
        if ($this->itemsSlave != null) {
            return $this->itemsSlave;
        }
        if($this->slave_bonus_items == null) {
            $this->itemsSlave = null;
            return $this->itemsSlave;
        }
        foreach ($this->slave_bonus_items as $item) {
            $enchant  = (int)$item->enchant ?? 0;
            $count    = (int)$item->count ?? 0;
            $itemData = item::getItem($item->item_id);
            $itemData->setCount($count);
            $itemData->setEnchant($enchant);
            $this->itemsSlave[] = $itemData;
        }

        return $this->itemsSlave;
    }

    /**
     * @return null|item[]
     */
    public function getLeaderBonusItems(): ?array
    {
        if ($this->itemsLeader !== null) {
            return $this->itemsLeader;
        }

        if (!is_array($this->leader_bonus_items)) {
            return null;
        }

        foreach ($this->leader_bonus_items as $item) {
            $enchant  = (int)$item->enchant ?? 0;
            $count    = (int)$item->count ?? 0;
            $itemData = item::getItem($item->item_id);
            if($itemData==null){
                $itemData = item::getItem(17);
            }
            $itemData->setCount($count);
            $itemData->setEnchant($enchant);
            $this->itemsLeader[] = $itemData;
        }

        return $this->itemsLeader;
    }


}