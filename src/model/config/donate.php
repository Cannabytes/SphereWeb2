<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\user\user;

class donate
{

    public readonly string $paySystemDefault;

    public readonly int $minSummaPaySphereCoin;

    public readonly int $maxSummaPaySphereCoin;

    public readonly float $sphereCoinCost;

    public float $ratioUSD = 1;

    public readonly float $ratioEUR;

    public readonly float $ratioUAH;

    public readonly int $ratioRUB;

    public readonly bool $enableCumulativeDiscountSystem;

    public readonly bool $enableOneTimeBonus;

    public readonly bool $enableSystemDiscountsOnItemPurchases;

    public readonly bool $enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;

    public readonly bool $rewardForDonatingItems;

    public array $tableItemsBonus;
    public array $tableCumulativeDiscountSystem;
    public array $tableEnableOneTimeBonus;
    public array $tableSystemDiscountsOnItemPurchases;
    public array $tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;
    public array $tableListOfItemsForDiscount;

    /**
     * @var donateSystem[]|array
     */
    public array $donateSystems;

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE serverId = ? AND `key` = '__config_donate__'", [
          user::self()->getServerId(),
        ]);
        $setting    = json_decode($configData['setting'], true);
        $this->paySystemDefault = $setting['paySystemDefault'];
        $this->minSummaPaySphereCoin = $setting['minSummaPaySphereCoin'];
        $this->maxSummaPaySphereCoin = $setting['maxSummaPaySphereCoin'];
        $this->sphereCoinCost = $setting['sphereCoinCost'];
        $this->ratioUSD = 1;
        $this->ratioEUR = $setting['ratioEUR'];
        $this->ratioUAH = $setting['ratioUAH'];
        $this->ratioRUB = $setting['ratioRUB'];
        $this->enableCumulativeDiscountSystem = filter_var($setting['enableCumulativeDiscountSystem'], FILTER_VALIDATE_BOOLEAN);
        $this->tableCumulativeDiscountSystem = $setting['table_CumulativeDiscountSystem'];
        $this->enableOneTimeBonus = filter_var($setting['enableOneTimeBonus'], FILTER_VALIDATE_BOOLEAN);
        $this->tableEnableOneTimeBonus = $setting['table_EnableOneTimeBonus'];
        $this->enableSystemDiscountsOnItemPurchases = filter_var($setting['enableSystemDiscountsOnItemPurchases'], FILTER_VALIDATE_BOOLEAN);
        $this->tableSystemDiscountsOnItemPurchases = $setting['table_SystemDiscountsOnItemPurchases'];
        $this->enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = filter_var($setting['enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'], FILTER_VALIDATE_BOOLEAN);
        $this->tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = $setting['table_IncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'];
        $this->tableListOfItemsForDiscount = $setting['table_ListOfItemsForDiscount'];
        $this->rewardForDonatingItems = $setting['rewardForDonatingItems'];
        $this->tableItemsBonus = $setting['tableItemsBonus'];
        $this->donateSystems = $setting['donateSystems'];

        foreach ($this->tableItemsBonus as &$itemsBonus) {
            foreach ($itemsBonus as &$itemBonus) {
                $id = $itemBonus['id'];
                $item = item::getItem($id);
                $itemBonus['item'] = $item; // Добавляем объект item к каждому bonus
            }
        }
        $this->donateSystems = $this->parseDonateSystem();
    }

    /**
     * @return array
     */
    public function getTableListOfItemsForDiscount(): array
    {
        return $this->tableListOfItemsForDiscount;
    }

    /**
     * @return array
     */
    public function getTableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity(): array
    {
        return $this->tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;
    }

    /**
     * @return array
     */
    public function getTableSystemDiscountsOnItemPurchases(): array
    {
        return $this->tableSystemDiscountsOnItemPurchases;
    }

    /**
     * @return mixed
     */
    public function getTableEnableOneTimeBonus(): mixed
    {
        return $this->tableEnableOneTimeBonus;
    }

    /**
     * @return mixed
     */
    public function getTableCumulativeDiscountSystem(): mixed
    {
        return $this->tableCumulativeDiscountSystem;
    }

    /**
     * @return donateSystem[]|array
     */
    private function parseDonateSystem(): array
    {
        $donateSys = [];
        foreach ($this->donateSystems as $system) {
            $systemName             = key($system);
            $enable = $system[$systemName]['enable'];
            $inputs = $system[$systemName]['inputs'];
            $donateSys[] = new donateSystem($enable, $systemName, $inputs);
        }
        return $donateSys;
    }

    /**
     * @return string
     */
    public function getPaySystemDefault(): string
    {
        return $this->paySystemDefault;
    }

    /**
     * @return int
     */
    public function getMinSummaPaySphereCoin(): int
    {
        return $this->minSummaPaySphereCoin;
    }

    /**
     * @return int
     */
    public function getMaxSummaPaySphereCoin(): int
    {
        return $this->maxSummaPaySphereCoin;
    }

    /**
     * @return float
     */
    public function getSphereCoinCost(): float
    {
        return $this->sphereCoinCost;
    }

    /**
     * @return float
     */
    public function getRatioUSD(): float
    {
        return $this->ratioUSD;
    }

    /**
     * @return float
     */
    public function getRatioEUR(): float
    {
        return $this->ratioEUR;
    }

    /**
     * @return float
     */
    public function getRatioUAH(): float
    {
        return $this->ratioUAH;
    }

    /**
     * @return int
     */
    public function getRatioRUB(): int
    {
        return $this->ratioRUB;
    }

    /**
     * @return bool
     */
    public function isEnableCumulativeDiscountSystem(): bool
    {
        return $this->enableCumulativeDiscountSystem;
    }

    /**
     * @return bool
     */
    public function isEnableOneTimeBonus(): bool
    {
        return $this->enableOneTimeBonus;
    }

    /**
     * @return bool
     */
    public function isEnableSystemDiscountsOnItemPurchases(): bool
    {
        return $this->enableSystemDiscountsOnItemPurchases;
    }

    /**
     * @return bool
     */
    public function isEnableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity(): bool
    {
        return $this->enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity;
    }

    /**
     * @return bool
     */
    public function isRewardForDonatingItems(): bool
    {
        return $this->rewardForDonatingItems;
    }

    /**
     * @return array
     */
    public function getTableItemsBonus(): array
    {
        return $this->tableItemsBonus;
    }

    /**
     * @return array|donateSystem[]
     */
    public function getDonateSystems(): array
    {
        return $this->donateSystems;
    }



}

class donateSystem
{

    private bool $enable = false;
    private string $name = "";
    private array $inputs = [];

    public function __construct($enable, $name, $inputs)
    {
        $this->enable = filter_var($enable, FILTER_VALIDATE_BOOLEAN);
        $this->name = $name;
        foreach ($inputs as $name => $value) {
            $this->inputs[$name] = $value;
        }
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|string
     */
    public function getInputs($method = null)
    {
        if ($method === null) {
            return $this->inputs;
        }
        return $this->inputs[$method] ?? "";
    }

}