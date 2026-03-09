<?php

namespace yoomoney;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

/**
 * Основной класс плагина YooMoney
 * https://yoomoney.ru/document/api-dlya-razrabotchikov
 */
class yoomoney extends BasePaymentPlugin
{
    // URL для формирования платежа
    private const PAYMENT_URL = 'https://yoomoney.ru/quickpay/confirm.xml';

    // Список разрешенных IP адресов YooMoney для webhook
    private const ALLOWED_IPS = [
    ];

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('shop'))) {
            $this->setPluginSetting('shop', []);
        }
    }

    /**
     * Получить данные магазина
     */
    private function getShop(): ?array
    {
        $shop = $this->getPluginSetting('shop');
        return is_array($shop) && !empty($shop) ? $shop : null;
    }

    protected function isConfigured(): bool
    {
        $shop = $this->getShop();
        return $shop !== null && !empty($shop['receiver']);
    }

    /**
     * Административная панель плагина
     */
    public function admin(): void
    {
        validation::user_protection('admin');

        $shop = $this->getShop();
        $instances = $shop ? [$shop] : [];

        tpl::addVar([
            'title' => lang::get_phrase('admin_panel', 'yoomoney'),
            'pluginName' => $this->getNameClass(),
            'instances' => $instances,
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', (string)($shop['description'] ?? '')),
            'enabled' => $this->getPluginSetting('enabled', false),
            'selectedCountries' => $this->sanitizeSupportedCountries($this->getPluginSetting('supported_countries', ['world'])),
            'webhookUrl' => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/plugin/yoomoney/webhook',
        ]);

        tpl::displayPlugin('/yoomoney/tpl/admin.html');
    }

    /**
     * Сохранить глобальные настройки
     */
    public function saveGlobalSettings(): void
    {
        validation::user_protection('admin');

        $enabledRaw = $_POST['enabled'] ?? null;
        $enabled = $enabledRaw === null
            ? (bool)$this->getPluginSetting('enabled', false)
            : filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = (bool)$this->getPluginSetting('enabled', false);
        }

        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting('supported_countries', ['world']));
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $this->getPluginSetting('PLUGIN_DESCRIPTION', '')));

        // Сохраняем статус в собственных настройках плагина
        $this->setPluginSetting('enabled', $enabled);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        // Используем системный метод для синхронизации в реестре '__PLUGIN__'
        $_POST['pluginName'] = $this->getNameClass();
        $_POST['setting'] = 'enablePlugin';
        $_POST['value'] = $enabled;
        $_POST['serverId'] = 0;
        plugin::__save_activator_plugin();

        board::success(lang::get_phrase('settings_saved', 'yoomoney'));
    }

    /**
     * Создать/обновить магазин
     */
    public function createInstance(): void
    {
        validation::user_protection('admin');

        $name = $_POST['name'] ?? '';
        $shopId = $_POST['shop_id'] ?? '';
        $secretKey = $_POST['secret_key'] ?? '';
        $description = $_POST['description'] ?? '';
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $description));
        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting('supported_countries', ['world']));

        if (empty($name) || empty($shopId) || empty($secretKey)) {
            board::error(lang::get_phrase('error_invalid_data', 'yoomoney'));
        }

        $shop = [
            'id' => 1,
            'name' => $name,
            'shop_id' => $shopId,
            'secret_key' => $secretKey,
            'description' => $pluginDescription,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->setPluginSetting('shop', $shop);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        board::success(lang::get_phrase('instance_created', 'yoomoney'));
    }

    /**
     * Обновить магазин
     */
    public function updateInstance(): void
    {
        validation::user_protection('admin');

        $name = $_POST['name'] ?? '';
        $shopId = $_POST['shop_id'] ?? '';
        $secretKey = $_POST['secret_key'] ?? '';
        $description = $_POST['description'] ?? '';
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $description));
        $supportedCountries = array_key_exists('supported_countries', $_POST)
            ? $this->sanitizeSupportedCountries($_POST['supported_countries'])
            : $this->sanitizeSupportedCountries($this->getPluginSetting('supported_countries', ['world']));

        if (empty($name) || empty($shopId) || empty($secretKey)) {
            board::error(lang::get_phrase('error_invalid_data', 'yoomoney'));
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'yoomoney'));
        }

        $shop['name'] = $name;
        $shop['shop_id'] = $shopId;
        $shop['secret_key'] = $secretKey;
        $shop['description'] = $pluginDescription;
        $shop['updated_at'] = date('Y-m-d H:i:s');

        $this->setPluginSetting('shop', $shop);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        board::success(lang::get_phrase('instance_updated', 'yoomoney'));
    }

    /**
     * Удалить магазин
     */
    public function deleteInstance(): void
    {
        validation::user_protection('admin');

        $this->setPluginSetting('shop', []);
        board::success(lang::get_phrase('instance_deleted', 'yoomoney'));
    }

    /**
     * Получить данные магазина в JSON
     */
    public function getInstanceData(): void
    {
        validation::user_protection('admin');

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'yoomoney'));
        }

        board::alert([
            'ok' => true,
            'instance' => $shop
        ]);
    }

    /**
     * Страница оплаты для пользователей
     */
    public function payment(?int $count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::notice(false, lang::get_phrase('plugin_disabled', 'yoomoney'));
            } else {
                redirect::location('/main');
            }
            return;
        }

        if (!user::self()->isAuth()) {
            if ($this->isAjax()) {
                board::response('error', lang::get_phrase(234));
            } else {
                redirect::location('/login');
            }
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::notice(false, lang::get_phrase('no_instances', 'yoomoney'));
        }

        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();

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
            'title'         => lang::get_phrase('payment_title', 'yoomoney'),
            'instances'     => $shop ? [$shop] : [],
            'minAmount'     => $donate->getMinSummaPaySphereCoin(),
            'maxAmount'     => $donate->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donate->getDefaultSummaPaySphereCoin() : (int)$count,
            'sphereCoinCost' => $sphereCoinCost,
            'donateCount'   => is_null($count) ? null : (int)$count,
            'USD_val'       => $USD_val,
            'EUR_val'       => $EUR_val,
            'RUB_val'       => $RUB_val,
            'UAH_val'       => $UAH_val,
            'mainCurrency'  => $mainCurrency,
        ]);

        tpl::displayPlugin('/yoomoney/tpl/payment.html');
    }

    /**
     * Создать новый платеж
     */
    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error(lang::get_phrase('plugin_disabled', 'yoomoney'));
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        $amount = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            board::error(lang::get_phrase('error_invalid_amount', 'yoomoney'));
        }

        $shop = $this->getShop();
        if (!$shop) {
            board::error(lang::get_phrase('error_instance_not_found', 'yoomoney'));
        }

        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();

        if ($amount < $donate->getMinSummaPaySphereCoin()) {
            board::error('Минимальная сумма: ' . $donate->getMinSummaPaySphereCoin());
        }

        if ($amount > $donate->getMaxSummaPaySphereCoin()) {
            board::error('Максимальная сумма: ' . $donate->getMaxSummaPaySphereCoin());
        }

        $currency = 'RUB';
        $calculatedAmount = donate::sphereCoinSmartCalc($amount, $donate->getRatio($currency), $donate->getSphereCoinCost());

        $params = [
            'receiver' => $shop['shop_id'],
            'sum' => (string)$calculatedAmount,
            'quickpay-form' => 'donate',
            'label' => user::self()->getId(),
            'paymentType' => 'AC',
            'successURL' => \Ofey\Logan22\component\request\url::host('/donate/pay'),
        ];

        $paymentUrl = self::PAYMENT_URL . '?' . http_build_query($params);
        board::response('success', ['url' => $paymentUrl]);
    }

    /**
     * Webhook для получения уведомлений от YooMoney
     */
    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            echo 'disabled';
            exit;
        }

        // Проверка IP
        $clientIp = $clientIp ?? ($_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR']);
        if (!$this->isIpAllowed($clientIp)) {
            $this->logWebhook('IP_NOT_ALLOWED', ['ip' => $clientIp]);
            die('Access denied: IP not allowed');
        }

        $shop = $this->getShop();
        if (!$shop) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'Shop not configured']);
            die('Shop not configured');
        }

        if (empty($shop['secret_key'])) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'Secret key not configured']);
            die('Secret key not configured');
        }

        $notification_type = $_POST['notification_type'] ?? '';
        if ($notification_type != 'card-incoming') {
            $this->logWebhook('EVENT_IGNORED', ['notification_type' => $notification_type]);
            exit();
        }

        $request_hash = $_POST['sha1_hash'] ?? '';
        $withdraw_amount = $_POST['withdraw_amount'] ?? '';
        $operation_id = (string)($_POST['operation_id'] ?? '');
        $amount = (string)($_POST['amount'] ?? '');
        $currency = (string)($_POST['currency'] ?? '');
        $datetime = (string)($_POST['datetime'] ?? '');
        $sender = (string)($_POST['sender'] ?? '');
        $codepro = (string)($_POST['codepro'] ?? '');
        $user_id = (string)($_POST['label'] ?? '');
        $notification_secret = $shop['secret_key'];

        $hash = sha1("{$notification_type}&{$operation_id}&{$amount}&{$currency}&{$datetime}&{$sender}&{$codepro}&{$notification_secret}&{$user_id}");

        if ($hash !== $request_hash) {
            $this->logWebhook('SIGN_INVALID', ['operation_id' => $operation_id, 'user_id' => $user_id]);
            exit("sha1_hash mismatch");
        }

        if (!ctype_digit($user_id) || (int)$user_id <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $user_id, 'operation_id' => $operation_id]);
            exit('invalid user_id');
        }

        $userId = (int)$user_id;

        $currency = 'RUB';
        try {
            donate::control_uuid($operation_id, $this->getNameClass());
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'operation_id' => $operation_id,
            ], $userId);
            exit('duplicate');
        }

        try {
            $donateAmount = donate::currency($withdraw_amount, $currency);
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount' => $withdraw_amount,
                'currency' => $currency,
            ], $userId);
            echo json_encode(['status' => false, 'msg' => 'Currency conversion failed']);
            return;
        }

        try {
            telegram::telegramNotice(user::getUserId($userId), $withdraw_amount, $currency, $donateAmount, $this->getNameClass());
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($userId)
                ->donateAdd($donateAmount)
                ->AddHistoryDonate(amount: $donateAmount, pay_system: $this->getNameClass());
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'operation_id' => $operation_id,
                'amount' => $donateAmount,
            ], $userId);
            echo json_encode(['status' => false, 'msg' => 'Failed to add funds']);
            return;
        }

        try {
            donate::addUserBonus($userId, $donateAmount);
        } catch (\Throwable $e) {
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'operation_id' => $operation_id,
            'amount' => $donateAmount,
            'currency' => $currency,
        ], $userId);

        exit("YES");
    }

    /**
     * Проверка, разрешен ли IP адрес
     */
    private function isIpAllowed(string $ip): bool
    {
        // Если массив пуст - разрешаем все IP (на свой страх и риск)
        if (empty(self::ALLOWED_IPS)) {
            return true;
        }
        foreach (self::ALLOWED_IPS as $allowedIp) {
            if (strpos($allowedIp, '/') !== false) {
                // Это подсеть
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            } else {
                // Это конкретный IP
                if ($ip === $allowedIp) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Проверка, находится ли IP в подсети
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6
            $ip_bin = inet_pton($ip);
            $subnet_bin = inet_pton($subnet);
            $mask = (int)$mask;
            
            $ip_bits = '';
            $subnet_bits = '';
            
            for ($i = 0; $i < strlen($ip_bin); $i++) {
                $ip_bits .= str_pad(decbin(ord($ip_bin[$i])), 8, '0', STR_PAD_LEFT);
                $subnet_bits .= str_pad(decbin(ord($subnet_bin[$i])), 8, '0', STR_PAD_LEFT);
            }
            
            return substr($ip_bits, 0, $mask) === substr($subnet_bits, 0, $mask);
        } else {
            // IPv4
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - (int)$mask);
            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }
    }

    /**
     * Собрать заголовки запроса в виде массива (фоллбек если getallheaders не доступен)
     */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            return is_array($h) ? $h : [];
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}
