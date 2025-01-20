<?php

namespace Ofey\Logan22\model\donate;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class donateConfig
{

    private bool $existConfig = false;

    private ?string $paySystemDefault = null;

    private int $minSummaPaySphereCoin = 1;

    private int $maxSummaPaySphereCoin = 99998;

    private int|float $sphereCoinCost = 1;

    private int|float $ratioUSD = 1;

    private int|float $ratioEUR = 1.05;

    private int|float $ratioUAH = 38.5;

    private int|float $ratioRUB = 100;

    private bool $enableCumulativeDiscountSystem = false;

    private array $table_CumulativeDiscountSystem = [];

    private bool $enableOneTimeBonus = false;

    private array $table_EnableOneTimeBonus = [];

    private bool $enableSystemDiscountsOnItemPurchases = false;

    private array $table_SystemDiscountsOnItemPurchases = [];

    private bool $enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = false;

    private array $table_IncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = [];

    private array $table_ListOfItemsForDiscount = [];

    private bool $rewardForDonatingItems = false;

    private array $tableItemsBonus = [];

    private donateSystem|array $donateSystems;

    private ?array $instance = null;

    public function __construct()
    {
        $sql                         = "SELECT * FROM `settings` WHERE `key` = '__config_donate__'";
        $configData                  = sql::getRow($sql, [
          user::self()->getServerId(),
        ]);
        $this->existConfig = true;
        $this->parse($configData['setting']);
    }

    private function parse($json)
    {
        $data = json_decode($json);
        if ($data) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    if ($key === 'tableItemsBonus') {
                        $this->tableItemsBonus = $this->parseTableItemsBonus($value);
                    }
                    if ($key === 'donateSystems') {
                        $this->donateSystems = $this->parseDonateSystem($value);
                    } elseif (is_string($value) && ($value === "true" || $value === "false")) {
                        $value     = ($value === "true");
                        $this->instance[$key] = $value;
                    } else {
                        $this->instance[$key] = $value;
                    }
                }
            }
        }
    }

    private function parseTableItemsBonus($tableItemsBonus): array
    {
        $result = [];
        foreach ($tableItemsBonus as $key => $items) {
            foreach ($items as $item) {
                if (property_exists($item, 'id') && property_exists($item, 'count') && property_exists($item, 'enchant')) {
                    $bonusItem      = new donateItemBonus($item->id, $item->count, $item->enchant);
                    $result[$key][] = $bonusItem;
                }
            }
        }
        return $result;
    }

    private function parseDonateSystem(mixed $value)
    {
        $donateSys = [];
        foreach ($value as $system) {
            $properties             = get_object_vars($system);
            $systemName             = array_keys($properties)[0];
            $donateSys[$systemName] = new donateSystem($system->$systemName->enable, $system->$systemName->inputs);
        }
        return $donateSys;
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
    public function getPaySystemDefault()
    {
        return $this->paySystemDefault;
    }

    /**
     * @return mixed
     */
    public function getMinSummaPaySphereCoin()
    {
        return $this->minSummaPaySphereCoin;
    }

    /**
     * @return mixed
     */
    public function getMaxSummaPaySphereCoin()
    {
        return $this->maxSummaPaySphereCoin;
    }

    /**
     * @return mixed
     */
    public function getSphereCoinCost()
    {
        return $this->sphereCoinCost;
    }

    /**
     * @return mixed
     */
    public function getRatioUSD()
    {
        return $this->ratioUSD;
    }

    /**
     * @return mixed
     */
    public function getRatioEUR()
    {
        return $this->ratioEUR;
    }

    /**
     * @return mixed
     */
    public function getRatioUAH()
    {
        return $this->ratioUAH;
    }

    /**
     * @return mixed
     */
    public function getRatioRUB()
    {
        return $this->ratioRUB;
    }

    /**
     * @return mixed
     */
    public function getEnableCumulativeDiscountSystem()
    {
        return $this->enableCumulativeDiscountSystem;
    }

    /**
     * @return array
     */
    public function getTableCumulativeDiscountSystem(): array
    {
        return $this->table_CumulativeDiscountSystem;
    }

    /**
     * @return mixed
     */
    public function getEnableOneTimeBonus()
    {
        return $this->enableOneTimeBonus;
    }

    /**
     * @return array
     */
    public function getTableEnableOneTimeBonus(): array
    {
        return $this->table_EnableOneTimeBonus;
    }

    /**
     * @return mixed
     */
    public function getEnableSystemDiscountsOnItemPurchases()
    {
        return $this->enableSystemDiscountsOnItemPurchases;
    }

    /**
     * @return array
     */
    public function getTableSystemDiscountsOnItemPurchases(): array
    {
        return $this->table_SystemDiscountsOnItemPurchases;
    }

    /**
     * @return mixed
     */
    public function getEnableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity()
    {
        return $this->enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;
    }

    /**
     * @return array
     */
    public function getTableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity(): array
    {
        return $this->table_IncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;
    }

    /**
     * @return array
     */
    public function getTableListOfItemsForDiscount(): array
    {
        return $this->table_ListOfItemsForDiscount;
    }

    /**
     * @return bool
     */
    public function isRewardForDonatingItems(): bool
    {
        return $this->rewardForDonatingItems;
    }

    /**
     * @return mixed
     */
    public function getTableItemsBonus($toArray = null)
    {
        if ($toArray) {
            return json_decode(json_encode($this->tableItemsBonus), true);
        }

        return $this->tableItemsBonus;
    }

    public function getDonateSystems($sysName = null)
    {
        if ($sysName === null) {
            return $this->donateSystems;
        }

        return $this->donateSystems[$sysName] ?? "None";
    }

}