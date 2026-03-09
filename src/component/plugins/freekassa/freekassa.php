<?php namespace freekassa;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

/**
 * Основной класс плагина FreeKassa
 */
class freekassa extends BasePaymentPlugin
{
    // API URL
    private const API_URL = "https://api.fk.life/v1/";
    
    // Список разрешенных IP адресов FreeKassa для webhook
    private const ALLOWED_IPS = [
        "168.119.157.136",
        "168.119.60.227",
        "178.154.197.79",
        "51.250.54.238"
    ];

    public function __construct()
    {
        $this->initializePlugin();
    }

    protected function isConfigured(): bool
    {
        $shop = $this->getShop();
        return $shop !== null && !empty($shop['shop_id']) && !empty($shop['api_key']);
    }

    /**
     * Инициализация плагина
     */
    private function initializePlugin(): void
    {
        $shop = $this->getPluginSetting("shop");
        if ($shop === null) {
            $this->setPluginSetting("shop", []);
        }
    }

    /**
     * Получить данные магазина
     */
    private function getShop(): ?array
    {
        $shop = $this->getPluginSetting("shop");
        return is_array($shop) && !empty($shop) ? $shop : null;
    }

    /**
     * Административная панель плагина
     */
    public function admin(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $shop = $this->getShop();
        $instances = $shop ? [$shop] : [];

        tpl::addVar([
            "title" => lang::get_phrase("admin_panel", "freekassa"),
            "pluginName" => $this->getNameClass(),
            "instances" => $instances,
            "pluginDescription" => (string)$this->getPluginSetting("PLUGIN_DESCRIPTION", (string)($shop['description'] ?? '')),
            "enabled" => $this->getPluginSetting("enabled", false),
            "selectedCountries" => $this->sanitizeSupportedCountries($this->getPluginSetting("supported_countries", ["world"])),
            "webhookUrl" => $this->getBaseUrl() . "/plugin/freekassa/webhook",
        ]);

        tpl::displayPlugin("/freekassa/tpl/admin.html");
    }

    /**
     * Сохранить глобальные настройки
     */
    public function saveGlobalSettings(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");
        $enabledRaw = $_POST["enabled"] ?? null;
        $enabled = $enabledRaw === null
            ? (bool)$this->getPluginSetting("enabled", false)
            : filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = (bool)$this->getPluginSetting("enabled", false);
        }

        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting("supported_countries", ["world"]));
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $this->getPluginSetting('PLUGIN_DESCRIPTION', '')));

        // Сохраняем статус в собственных настройках плагина
        $this->setPluginSetting("enabled", $enabled);
        $this->setPluginSetting("supported_countries", $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        // Используем системный метод для синхронизации в реестре '__PLUGIN__'
        $_POST['pluginName'] = $this->getNameClass();
        $_POST['setting'] = 'enablePlugin';
        $_POST['value'] = $enabled;
        $_POST['serverId'] = 0;
        \Ofey\Logan22\model\plugin\plugin::__save_activator_plugin();
    }

    /**
     * Создать/обновить магазин
     */
    public function createInstance(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $name = $_POST["name"] ?? "";
        $shop_id = $_POST["shop_id"] ?? "";
        $api_key = $_POST["api_key"] ?? "";
        $secret_word = $_POST["secret_word"] ?? "";
        $secret_word_2 = $_POST["secret_word_2"] ?? "";
        $description = $_POST["description"] ?? "";
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $description));
        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting("supported_countries", ["world"]));

        if (empty($name) || empty($shop_id) || empty($api_key)) {
            board::error(lang::get_phrase("error_invalid_data", "freekassa"));
        }

        $shop = [
            "id" => 1,
            "name" => $name,
            "shop_id" => $shop_id,
            "api_key" => $api_key,
            "secret_word" => $secret_word,
            "secret_word_2" => $secret_word_2,
            "description" => $pluginDescription,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        $this->setPluginSetting("shop", $shop);
        $this->setPluginSetting("PLUGIN_DESCRIPTION", $pluginDescription);
        $this->setPluginSetting("supported_countries", $supportedCountries);
        board::success(lang::get_phrase("instance_created", "freekassa"));
    }

    /**
     * Обновить магазин
     */
    public function updateInstance(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $name = $_POST["name"] ?? "";
        $shop_id = $_POST["shop_id"] ?? "";
        $api_key = $_POST["api_key"] ?? "";
        $secret_word = $_POST["secret_word"] ?? "";
        $secret_word_2 = $_POST["secret_word_2"] ?? "";
        $description = $_POST["description"] ?? "";
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $description));
        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting("supported_countries", ["world"]));

        if (empty($name) || empty($shop_id) || empty($api_key)) {
            board::error(lang::get_phrase("error_invalid_data", "freekassa"));
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase("error_instance_not_found", "freekassa"));
        }

        $shop["name"] = $name;
        $shop["shop_id"] = $shop_id;
        $shop["api_key"] = $api_key;
        $shop["secret_word"] = $secret_word;
        $shop["secret_word_2"] = $secret_word_2;
        $shop["description"] = $pluginDescription;
        $shop["updated_at"] = date("Y-m-d H:i:s");

        $this->setPluginSetting("shop", $shop);
        $this->setPluginSetting("PLUGIN_DESCRIPTION", $pluginDescription);
        $this->setPluginSetting("supported_countries", $supportedCountries);
        board::success(lang::get_phrase("instance_updated", "freekassa"));
    }

    /**
     * Удалить магазин
     */
    public function deleteInstance(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $this->setPluginSetting("shop", []);
        board::success(lang::get_phrase("instance_deleted", "freekassa"));
    }

    /**
     * Получить данные магазина в JSON
     */
    public function getInstanceData(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase("error_instance_not_found", "freekassa"));
        }

        board::alert([
            'ok' => true,
            'instance' => $shop
        ]);
    }

    /**
     * Страница оплаты для пользователей
     */
    public function payment($count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::notice(false, lang::get_phrase("plugin_disabled", "freekassa"));
            } else {
                redirect::location("/main");
            }
            return;
        }
        
        if (!user::self()->isAuth()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                board::response('error', lang::get_phrase(234));
            } else {
                redirect::location("/login");
            }
        }
        
        $shop = $this->getShop();
        if (!$shop) {
            board::notice(false, lang::get_phrase("no_instances", "freekassa"));
        }
        
        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();

        // Получаем список доступных валют
        $currencies = [];
        if ($shop) {
            $currencies = $shop['currencies'] ?? [];
            if (empty($currencies)) {
                $response = $this->apiRequest('currencies', [], $shop);
                if (isset($response['type']) && $response['type'] === 'success') {
                    $currencies = $response['currencies'] ?? [];
                }
            }
        }

        // Fallback на стандартный список валют
        if (empty($currencies)) {
            $currencies = $this->getFallbackCurrencies();
        }

        $sphereCoinCost = $donate->getSphereCoinCost();
        $rateCalc = static fn($r) => round($sphereCoinCost >= 1 ? $r / $sphereCoinCost : $r * $sphereCoinCost, 4);
        $USD_val = $rateCalc($donate->getRatioUSD());
        $EUR_val = $rateCalc($donate->getRatioEUR());
        $RUB_val = $rateCalc($donate->getRatioRUB());
        $UAH_val = $rateCalc($donate->getRatioUAH());
        $userCountry = strtoupper(user::self()->getCountry() ?? '');
        $mainCurrency = match(true) {
            $userCountry === 'UA' => 'UAH',
            $userCountry === 'RU' => 'RUB',
            default               => 'USD',
        };

        tpl::addVar([
            "title"         => lang::get_phrase("payment_title", "freekassa"),
            "instances"     => $shop ? [$shop] : [],
            "currencies"    => $currencies,
            "minAmount"     => $donate->getMinSummaPaySphereCoin(),
            "maxAmount"     => $donate->getMaxSummaPaySphereCoin(),
            "defaultAmount" => $donate->getDefaultSummaPaySphereCoin(),
            "sphereCoinCost" => $sphereCoinCost,
            "donateCount"   => is_null($count) ? null : (int)$count,
            "USD_val"       => $USD_val,
            "EUR_val"       => $EUR_val,
            "RUB_val"       => $RUB_val,
            "UAH_val"       => $UAH_val,
            "mainCurrency"  => $mainCurrency,
        ]);

        tpl::displayPlugin("/freekassa/tpl/payment.html");
    }

    /**
     * Создать новый платеж
     */
    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error(lang::get_phrase("plugin_disabled", "freekassa"));
        }
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method = (int)($_POST['payment_method'] ?? 0);

        if ($amount <= 0) {
            board::error(lang::get_phrase('error_invalid_amount', 'freekassa'));
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'freekassa'));
        }

        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();

        if ($amount < $donate->getMinSummaPaySphereCoin()) {
            board::error("Минимальная сумма: " . $donate->getMinSummaPaySphereCoin());
        }

        $currency = strtoupper(trim($_POST['currency'] ?? 'RUB'));
        if (!preg_match('/^[A-Z0-9]{3,6}$/', $currency)) {
            $currency = 'RUB';
        }

        $amount = donate::sphereCoinSmartCalc($amount, $donate->getRatio($currency), $donate->getSphereCoinCost());

        $paymentId = time() . '_' . user::self()->getId();

        $payload = [
            'shopId' => (int)$shop['shop_id'],
            'nonce' => (int)(microtime(true) * 1000),
            'amount' => $amount,
            'currency' => "RUB",
            'i' => $payment_method,
            'email' => user::self()->getEmail(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'paymentId' => $paymentId,
            'us_userid' => user::self()->getId(),
        ];

        $response = $this->apiRequest('orders/create', $payload, $shop);

        if (isset($response['error'])) {
            board::error($response['error']);
        }

        if (isset($response['type']) && $response['type'] === 'success') {
            $paymentUrl = $response['location'] ?? null;

            if ($paymentUrl) {
                board::response('success', ['url' => $paymentUrl]);
            } else {
                board::error(lang::get_phrase('error_no_payment_url', 'freekassa') ?? 'No payment URL returned');
            }
            return;
        }

        if (isset($response['type']) && $response['type'] === 'error') {
            board::error($response['message'] ?? 'API error');
        }

        board::error($response['message'] ?? 'Unexpected API response');
    }
 

    /**
     * Webhook для получения уведомлений от FreeKassa
     */
    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            board::error(lang::get_phrase("plugin_disabled", "freekassa"));
        }

        $ip = $_SERVER["HTTP_X_REAL_IP"] ?? $_SERVER["REMOTE_ADDR"];
        if (!in_array($ip, self::ALLOWED_IPS, true)) {
            $this->logWebhook('IP_NOT_ALLOWED', ['ip' => $ip]);
            die("Access denied");
        }
        
        $shop = $this->getShop();
        if (!$shop) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'Shop is not configured']);
            die("Shop not configured");
        }
        
        if (empty($shop['secret_word_2'])) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'secret_word_2 is not configured']);
            die("Secret word 2 not configured");
        }

        $required = ['us_userid', 'MERCHANT_ID', 'MERCHANT_ORDER_ID', 'AMOUNT', 'SIGN'];
        foreach ($required as $key) {
            if (!isset($_REQUEST[$key])) {
                $this->logWebhook('INPUT_INVALID', ['reason' => 'Missing required parameter', 'field' => $key]);
                die('wrong input');
            }
        }

        $user_id = (int)$_REQUEST['us_userid'];
        $MERCHANT_ID = (string)$_REQUEST['MERCHANT_ID'];
        $MERCHANT_ORDER_ID = (string)$_REQUEST['MERCHANT_ORDER_ID'];
        $intid = (string)($_REQUEST['intid'] ?? $MERCHANT_ORDER_ID);

        if ($user_id <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $user_id, 'order_id' => $MERCHANT_ORDER_ID]);
            die('wrong user');
        }

        $sign = md5($MERCHANT_ID . ':' . $_REQUEST['AMOUNT'] . ':' . $shop['secret_word_2'] . ':' . $MERCHANT_ORDER_ID);

        if (!hash_equals(strtoupper($sign), strtoupper((string)$_REQUEST['SIGN']))) {
            $this->logWebhook('SIGN_INVALID', ['order_id' => $MERCHANT_ORDER_ID]);
            die('wrong sign');
        }

        try {
            donate::control_uuid($intid, get_called_class());
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'invoice_id' => $intid,
            ], $user_id);
            die('wrong uuid');
        }

        try {
            $amount = donate::currency((float)$_REQUEST['AMOUNT'], "RUB");
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount_input' => (string)$_REQUEST['AMOUNT'],
            ], $user_id);
            die('wrong amount');
        }

        try {
            telegram::telegramNotice(user::getUserId($user_id), (float)$_REQUEST['AMOUNT'], "RUB", $amount, get_called_class());
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: get_called_class());
            donate::addUserBonus($user_id, $amount);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'invoice_id' => $intid,
                'amount' => $amount,
            ], $user_id);
            die('failed');
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'invoice_id' => $intid,
            'amount' => $amount,
            'currency' => 'RUB',
        ], $user_id);

        echo 'YES';
    }

    /**
     * Получить баланс магазина
     */
    public function getBalance(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'freekassa'));
        }
        
        $response = $this->apiRequest('balance', [], $shop);
        
        if (isset($response['error'])) {
            board::error($response['error']);
        }
        
        if (isset($response['type']) && $response['type'] === 'success') {
            board::response('success', ['balance' => $response['balance']]);
        }
        
        board::error($response['message'] ?? 'Unknown error');
    }

    /**
     * Получить список валют
     */
    public function getCurrencies(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'freekassa'));
        }
        
        // Сначала пробуем вернуть сохраненные валюты
        if (!empty($shop['currencies'])) {
            board::response('success', ['currencies' => $shop['currencies']]);
        }

        $response = $this->apiRequest('currencies', [], $shop);
        
        if (isset($response['error'])) {
            board::error($response['error']);
        }
        
        if (isset($response['type']) && $response['type'] === 'success') {
            board::response('success', ['currencies' => $response['currencies']]);
        }
        
        // В случае ошибки API возвращаем стандартный список
        board::response('success', ['currencies' => $this->getFallbackCurrencies()]);
    }

    /**
     * Проверить статус транзакции
     */
    public function transactionStatus(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        $id = $_POST['id'] ?? $_POST['payment_id'] ?? '';

        if (empty($id)) {
            board::error('Transaction id required');
        }

        board::error('Transaction storage disabled');
    }

    /**
     * Выполнить запрос к API FreeKassa
     */
    private function apiRequest(string $method, array $data, array $shop): array
    {
        $data['shopId'] = (int)$shop['shop_id'];
        $data['nonce'] = (int)(microtime(true) * 1000);
        
        ksort($data);
        $signature = hash_hmac('sha256', implode('|', $data), $shop['api_key']);
        $data['signature'] = $signature;

        $ch = curl_init(self::API_URL . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        return json_decode($response, true) ?? ['error' => 'Invalid JSON response'];
    }

    /**
     * Обновить список валют для магазина
     */
    public function ajaxRefreshCurrencies(): void
    {
        \Ofey\Logan22\model\admin\validation::user_protection("admin");
        
        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'freekassa'));
        }
        
        $response = $this->apiRequest('currencies', [], $shop);
        
        if (isset($response['type']) && $response['type'] === 'success') {
            $currencies = $response['currencies'] ?? [];
            $shop['currencies'] = $currencies;
            $shop['currencies_updated_at'] = date('Y-m-d H:i:s');
            
            $this->setPluginSetting("shop", $shop);
            board::success(lang::get_phrase('currencies_updated', 'freekassa'));
        }
        
        board::error($response['message'] ?? 'API Error');
    }

    /**
     * Получить стандартный список валют (fallback)
     */
    private function getFallbackCurrencies(): array
    {
        return [
            ['id' => 1, 'name' => 'Bank Card (RUB)', 'currency' => 'RUB', 'limits' => ['min' => 10, 'max' => 999999]],
            ['id' => 2, 'name' => 'Yandex.Kassa', 'currency' => 'RUB', 'limits' => ['min' => 10, 'max' => 999999]],
        ];
    }
}
