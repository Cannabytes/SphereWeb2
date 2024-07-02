<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class donate
{

    public string $paySystemDefault = "freekassa";

    public int $minSummaPaySphereCoin = 1;

    public int $maxSummaPaySphereCoin = 999999;

    public float $sphereCoinCost = 1;

    public float $ratioUSD = 1;

    public float $ratioEUR = 1.09;

    public float $ratioUAH = 40.54;

    public float $ratioRUB = 90.44;

    public bool $enableCumulativeDiscountSystem = false;

    public bool $enableOneTimeBonus = false;

    public bool $enableSystemDiscountsOnItemPurchases = false;

    public bool $enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = false;

    public bool $rewardForDonatingItems = false;

    public array $tableItemsBonus = [];

    public array $tableCumulativeDiscountSystem = [];

    public array $tableEnableOneTimeBonus = [];

    public array $tableSystemDiscountsOnItemPurchases = [];

    public array $tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = [];

    public array $tableListOfItemsForDiscount = [];

    public int $item_id_to_game_transfer = 0;

    public int $count_items_to_game_transfer = 0;

    /**
     * @var donateSystem[]|array
     */
    public array $donateSystems = [];

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE serverId = ? AND `key` = '__config_donate__'", [
          user::self()->getServerId(),
        ]);
        if ($configData) {
            $setting                                                           = json_decode($configData['setting'], true);
            $this->paySystemDefault                                            = $setting['paySystemDefault'];
            $this->minSummaPaySphereCoin                                       = $setting['minSummaPaySphereCoin'];
            $this->maxSummaPaySphereCoin                                       = $setting['maxSummaPaySphereCoin'];
            $this->sphereCoinCost                                              = $setting['sphereCoinCost'];
            $this->ratioUSD                                                    = 1;
            $this->ratioEUR                                                    = $setting['ratioEUR'];
            $this->ratioUAH                                                    = $setting['ratioUAH'];
            $this->ratioRUB                                                    = $setting['ratioRUB'];
            $this->enableCumulativeDiscountSystem                              = filter_var(
              $setting['enableCumulativeDiscountSystem'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->tableCumulativeDiscountSystem                               = $setting['table_CumulativeDiscountSystem'] ?? [];
            $this->enableOneTimeBonus                                          = filter_var(
              $setting['enableOneTimeBonus'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->tableEnableOneTimeBonus                                     = $setting['table_EnableOneTimeBonus'] ?? [];
            $this->enableSystemDiscountsOnItemPurchases                        = filter_var(
              $setting['enableSystemDiscountsOnItemPurchases'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->tableSystemDiscountsOnItemPurchases                         = $setting['table_SystemDiscountsOnItemPurchases'] ?? [];
            $this->enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = filter_var(
              $setting['enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity  = $setting['table_IncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'] ?? [];
            $this->tableListOfItemsForDiscount                                 = $setting['table_ListOfItemsForDiscount'] ?? [];
            $this->rewardForDonatingItems                                      = filter_var(
              $setting['rewardForDonatingItems'],
              FILTER_VALIDATE_BOOLEAN
            );
            $this->tableItemsBonus                                             = $setting['tableItemsBonus'] ?? [];
            $this->donateSystems                                               = $setting['donateSystems'];

            foreach ($this->tableItemsBonus as &$itemsBonus) {
                foreach ($itemsBonus as &$itemBonus) {
                    $id                = $itemBonus['id'];
                    $item              = item::getItem($id);
                    $itemBonus['item'] = $item; // Добавляем объект item к каждому bonus
                }
            }
            $this->donateSystems = $this->parseDonateSystem();
            $this->item_id_to_game_transfer = filter_var($setting['item_id_to_game_transfer'], FILTER_VALIDATE_INT) ?? 0;
            $this->count_items_to_game_transfer = filter_var($setting['count_items_to_game_transfer'], FILTER_VALIDATE_INT) ?? 0;
        } else {
            $all_donate_system = fileSys::get_dir_files("src/component/donate", [
              'basename' => true,
              'fetchAll' => true,
            ]);
            $donateSysNames    = [];
            foreach ($all_donate_system as $system) {
                if ( ! $system::isEnable()) {
                    continue;
                }
                if (method_exists($system, 'forAdmin')) {
                    if ($system::forAdmin() and auth::get_access_level() != 'admin') {
                        continue;
                    }
                }
                $inputs = [];
                if (method_exists($system, 'inputs')) {
                    $inputs = $system::inputs();
                }
                if (method_exists($system, 'getDescription')) {
                    $donateSysNames[] = [
                      'name'   => basename($system),
                      'desc'   => $system::getDescription()[config::load()->lang()->getDefault()] ?? "",
                      'inputs' => $inputs,
                    ];
                } else {
                    $donateSysNames[] = [
                      'name'   => basename($system),
                      'desc'   => basename($system),
                      'inputs' => $inputs,
                    ];
                }
            }

            $donateSys = [];
            foreach ($donateSysNames as $system) {
                $systemName  = $system['name'] ?? "";
                $enable      = $system['enable'] ?? false;
                $inputs      = $system['inputs'];
                $description = $system['description'] ?? "";
                $forAdmin    = $system['forAdmin'] ?? false;
                $donateSys[] = new donateSystem($enable, $systemName, $inputs, $description, $forAdmin);
            }
            $this->donateSystems = $donateSys;
        }
    }

    public function getItemIdToGameTransfer(): int
    {
        return $this->item_id_to_game_transfer;
    }

    public function getCountItemsToGameTransfer(): int
    {
        return $this->count_items_to_game_transfer;
    }

    /**
     * @return donateSystem[]|array
     */
    private function parseDonateSystem(): array
    {
        $donateSys = [];
        foreach ($this->donateSystems as $system) {
            $systemName  = key($system);
            $enable      = $system[$systemName]['enable'] ?? false;
            $inputs      = $system[$systemName]['inputs'] ?? [];
            $description = $system[$systemName]['description'] ?? "";
            $forAdmin    = $system[$systemName]['forAdmin'] ?? false;
            $donateSys[] = new donateSystem($enable, $systemName, $inputs, $description, $forAdmin);
        }

        return $donateSys;
    }

    public function get($name): donateSystem
    {
        foreach ($this->donateSystems as $system) {
            if ($system->getName() == $name) {
                return $system;
            }
        }
        throw new \Exception("System not found");
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
    public function getRatioRUB(): float
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

    private bool $forAdmin = false;

    private string $name = "";

    private array $inputs = [];

    private $description = "";

    public function __construct($enable, $name, $inputs, $description = "", $forAdmin = false)
    {
        $this->enable      = filter_var($enable, FILTER_VALIDATE_BOOLEAN);
        $this->name        = $name;
        $this->description = $description;
        $this->forAdmin    = filter_var($forAdmin, FILTER_VALIDATE_BOOLEAN);
        foreach ($inputs as $name => $value) {
            $this->inputs[$name] = $value;
        }
    }

    public function forAdmin(): bool
    {
        return $this->forAdmin;
    }

    public function getDescription(): string
    {
        return $this->description ?? "";
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
     * @param   string|null  $method
     *
     * @return int|string
     */
    public function getInput(string $method = null): string|int
    {
        if ($method === null) {
            return 'method is null';
        }

        return $this->inputs[$method] ?? "";
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

}