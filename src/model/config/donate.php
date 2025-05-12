<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\config\dsys;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\user\auth\auth;

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

    public int $item_id_to_game_transfer = 4037;

    public int $donate_item_to_game_transfer = 1;

    public int $count_items_to_game_transfer = 1;

    /**
     * @var donateSystem[]|array
     */
    public array $donateSystems = [];

    public function __construct($serverId = 0, $dbVersion = null)
    {
        $config = sql::getRow("SELECT * FROM `settings` WHERE serverId = ? AND `key` = '__config_donate__'", [
            $serverId
        ]);
        if ($config) {
            $setting = json_decode($config['setting'], true);
            $this->paySystemDefault = $setting['paySystemDefault'] ?? "freekassa";
            $this->minSummaPaySphereCoin = filter_var($setting['minSummaPaySphereCoin'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $this->maxSummaPaySphereCoin = filter_var($setting['maxSummaPaySphereCoin'] ?? 999999, FILTER_VALIDATE_INT, ['options' => ['default' => 999999, 'min_range' => 1]]);
            $this->sphereCoinCost = filter_var($setting['sphereCoinCost'] ?? 1, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 1, 'min_range' => 0.1]]);
            $this->ratioUSD = filter_var($setting['ratioUSD'] ?? 1, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 1]]);
            $this->ratioEUR = filter_var($setting['ratioEUR'] ?? 1.09, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 1.09]]);
            $this->ratioUAH = filter_var($setting['ratioUAH'] ?? 40.54, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 40.54]]);
            $this->ratioRUB = filter_var($setting['ratioRUB'] ?? 90.44, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 90.44]]);
            $this->enableCumulativeDiscountSystem = filter_var(
                $setting['enableCumulativeDiscountSystem'],
                FILTER_VALIDATE_BOOLEAN
            );
            $this->tableCumulativeDiscountSystem = $setting['table_CumulativeDiscountSystem'] ?? [];
            $this->enableOneTimeBonus = filter_var(
                $setting['enableOneTimeBonus'],
                FILTER_VALIDATE_BOOLEAN
            );
            $this->tableEnableOneTimeBonus = $setting['table_EnableOneTimeBonus'] ?? [];
            $this->enableSystemDiscountsOnItemPurchases = filter_var(
                $setting['enableSystemDiscountsOnItemPurchases'],
                FILTER_VALIDATE_BOOLEAN
            );
            $this->tableSystemDiscountsOnItemPurchases = $setting['table_SystemDiscountsOnItemPurchases'] ?? [];
            $this->enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = filter_var(
                $setting['enableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'],
                FILTER_VALIDATE_BOOLEAN
            );
            $this->tableIncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity = $setting['table_IncludeOneTimeDiscountsOnThePurchaseOfItemsByQuantity'] ?? [];
            $this->tableListOfItemsForDiscount = $setting['table_ListOfItemsForDiscount'] ?? [];
            $this->rewardForDonatingItems = filter_var(
                $setting['rewardForDonatingItems'],
                FILTER_VALIDATE_BOOLEAN
            );
            $this->tableItemsBonus = isset($setting['tableItemsBonus']) && is_array($setting['tableItemsBonus']) ? $setting['tableItemsBonus'] : [];
            $this->donateSystems = isset($setting['donateSystems']) && is_array($setting['donateSystems']) ? $setting['donateSystems'] : [];

            foreach ($this->tableItemsBonus as &$itemsBonus) {
                foreach ($itemsBonus as &$itemBonus) {
                    $id = $itemBonus['id'];
                    $item = item::getItem($id, $dbVersion);
                    $itemBonus['item'] = $item; // Добавляем объект item к каждому bonus
                }
            }
            $this->donateSystems = $this->parseDonateSystem();
            $this->item_id_to_game_transfer = filter_var($setting['item_id_to_game_transfer'], FILTER_VALIDATE_INT) ?? 0;
            $this->donate_item_to_game_transfer = filter_var($setting['donate_item_to_game_transfer'] ?? 1, FILTER_VALIDATE_INT);
            $this->count_items_to_game_transfer = filter_var($setting['count_items_to_game_transfer'] ?? 1, FILTER_VALIDATE_INT);
        } else {
            $all_donate_system = fileSys::get_dir_files("src/component/donate", [
                'basename' => true,
                'fetchAll' => true,
                'only_non_empty_folders' => true,
            ]);

            $key = array_search("monobank", $all_donate_system);
            if ($key !== false) {
                unset($all_donate_system[$key]);
            }

            $donateSysNames = [];
            foreach ($all_donate_system as $system) {
                if (!class_exists($system)) {
                    continue;
                }

                if (!$system::isEnable()) {
                    continue;
                }

                if (method_exists($system, 'forAdmin')) {
                    if ($system::forAdmin() && auth::get_access_level() != 'admin') {
                        continue;
                    }
                }

                $inputs = [];
                if (method_exists($system, 'inputs')) {
                    $inputs = $system::inputs();
                }

                if (method_exists($system, 'getDescription')) {
                    $donateSysNames[] = [
                        'name' => basename($system),
                        'desc' => $system::getDescription()[config::load()->lang()->getDefault()] ?? "",
                        'inputs' => $inputs,
                        'webhook' => $system::getWebhook(),
                        'country' => $system::getCountry(),
                        'customName' => $system::getCustomName(),
                    ];
                } else {
                    $donateSysNames[] = [
                        'name' => basename($system),
                        'desc' => basename($system),
                        'inputs' => $inputs,
                        'webhook' => $system::getWebhook(),
                        'country' => $system::getCountry(),
                        'customName' => $system::getCustomName(),
                    ];
                }
            }

            $donateSys = [];
            foreach ($donateSysNames as $system) {
                $systemName = $system['name'] ?? "";
                $enable = $system['enable'] ?? false;
                $inputs = $system['inputs'];
                $description = $system['desc'] ?? ""; // Исправлено с 'description' на 'desc'
                $forAdmin = $system['forAdmin'] ?? false;
                $webhook = $system['webhook'];
                $country = $system['country'];
                $customName = $system['customName'] ?? "";
                $currency = $system['currency'] ?? null;
                $donateSys[] = new donateSystem($enable, $systemName, $inputs, $description, $forAdmin, $webhook, 1000, $country, $customName, $currency);
            }

            $this->donateSystems = $donateSys;
        }
    }

    /**
     * @return donateSystem[]|array
     */
    private function parseDonateSystem(): array
    {
        $all_donate_system = fileSys::get_dir_files("src/component/donate", [
            'basename' => true,
            'fetchAll' => true,
            'only_non_empty_folders' => true,
        ]);
        $key = array_search("monobank", $all_donate_system);
        if ($key !== false) {
            unset($all_donate_system[$key]);
        }

        $fileDonateSys = [];
        $donateSys = [];
        foreach ($this->donateSystems as $system) {
            $systemName = key($system);
            $enable = $system[$systemName]['enable'] ?? false;
            $inputs = $system[$systemName]['inputs'] ?? [];
            $description = $system[$systemName]['description'] ?? "";
            $forAdmin = $system[$systemName]['forAdmin'] ?? false;
            $sort = $system[$systemName]['sort'] ?? 1000;
            $country = $system[$systemName]['country'] ?? [];
            $currency = $system[$systemName]['currency'] ?? null;
            if(!isset($system[$systemName]['customName']) or empty($system[$systemName]['customName'])){
                $customName = $systemName;
            }else{
                $customName = $system[$systemName]['customName'] ?? $systemName;
            }
            $donateSys[] = new donateSystem($enable, $systemName, $inputs, $description, $forAdmin, sort: $sort, country: $country, customName: $customName, currency: $currency);
        }
        $donateSys = array_merge($donateSys, $fileDonateSys);
        usort($donateSys, function ($a, $b) {
            return strcmp($a->getSortValue(), $b->getSortValue());
        });
        return $donateSys;
    }

    public function getItemIdToGameTransfer(): int
    {
        return $this->item_id_to_game_transfer;
    }

    public function getDonateItemToGameTransfer(): int
    {
        return $this->donate_item_to_game_transfer;
    }

    public function getCountItemsToGameTransfer(): int
    {
        return $this->count_items_to_game_transfer;
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
        if (config::load()->other()->isExchangeRates()) {
            return config::load()->other()->getExchangeRates()['USD'];
        }
        return $this->ratioUSD;
    }

    /**
     * Получает коэффициент конвертации для указанной валюты
     *
     * @param string $currency Код валюты (RUB, UAH, EUR, USD и т.д.)
     * @return float|int Коэффициент конвертации
     */
    public function getRatio(string $currency): float|int
    {
        $config = config::load()?->other();

        $rate = $config?->isExchangeRates() ? $config->getExchangeRates()[$currency] ?? null : null;

        if ($rate !== null) {
            return $rate;
        }

        return match ($currency) {
            'RUB' => $this->ratioRUB,
            'UAH' => $this->ratioUAH,
            'EUR' => $this->ratioEUR,
            default => $this->ratioUSD,
        };
    }


    /**
     * @return float
     */
    public function getRatioEUR(): float
    {
        if (config::load()->other()->isExchangeRates()) {
            return config::load()->other()->getExchangeRates()['EUR'];
        }
        return $this->ratioEUR;
    }

    /**
     * @return float
     */
    public function getRatioUAH(): float
    {
        if (config::load()->other()->isExchangeRates()) {
            return config::load()->other()->getExchangeRates()['UAH'];
        }
        return $this->ratioUAH;
    }

    /**
     * @return int
     */
    public function getRatioRUB(): float
    {
        if (config::load()->other()->isExchangeRates()) {
            return config::load()->other()->getExchangeRates()['RUB'];
        }
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
     * @param null $sysName
     * @return donateSystem|array|null
     */
    public function getDonateSystems($sysName = null): donateSystem|array|null
    {
        if($sysName!==null){
            foreach($this->donateSystems as $system){
                if($system->getName() == $sysName){
                    return $system;
                }
            }
            return null;
        }
        return $this->donateSystems;
    }

}

class donateSystem
{

    private bool $enable = false;

    private bool $forAdmin = false;

    private string $name = "";

    private array $inputs = [];

    private ?string $webhookUrl = null;

    private string $description = "";

    private int $sortValue = 1000;

    private array $country = [];

    private string $customName = "";

    private ?string $currency = null;

    public function __construct($enable, $name, $inputs, $description = "", $forAdmin = false, $webhookUrl = null, $sort = 1000, $country = [], $customName = "", string|null $currency = null)
    {
        $this->enable = filter_var($enable, FILTER_VALIDATE_BOOLEAN);
        $this->name = $name;
        $this->description = $description;
        $this->forAdmin = filter_var($forAdmin, FILTER_VALIDATE_BOOLEAN);

        $this->webhookUrl = $webhookUrl;
        $this->sortValue = $sort;
        $this->country = $country;
        $this->customName = $customName ?? $name;
        $this->currency = $currency;

        if (!is_array($inputs) && !is_object($inputs)) {
            return;
        }

        foreach ($inputs as $name => $value) {
            $this->inputs[$name] = $value;
        }

    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getCustomName(): string
    {
        return $this->customName;
    }

    public function getCountry(): array
    {
        return $this->country;
    }

    public function getSortValue(): int
    {
        return $this->sortValue ?? 1000;
    }

    public function getWebhookUrl(): string
    {
        if ($this->webhookUrl == null) {
            return url::host("/donate/webhook/" . $this->getName());
        }
        return url::host($this->webhookUrl);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
     * @param string|null $method
     *
     * @return int|string
     */
    public function getInput(?string $method = null): string|int
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

    public function addInput($input, $value): void
    {
        $this->inputs[$input] = $value;
    }

}