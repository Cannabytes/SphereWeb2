<?php

namespace paypal;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\ip;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class paypal extends BasePaymentPlugin
{
    private const API_URL_LIVE = 'https://api-m.paypal.com';
    private const API_URL_SANDBOX = 'https://api-m.sandbox.paypal.com';

    private const ALLOWED_CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CNY', 'INR', 'MXN', 'BRL', 'RUB'];

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('accounts'))) {
            $this->setPluginSetting('accounts', []);
        }
    }

    protected function isConfigured(): bool
    {
        $accounts = $this->getAccounts();
        return !empty($accounts);
    }

    private function getAccounts(): array
    {
        $rows = $this->getPluginSetting('accounts', []);
        if (!is_array($rows)) {
            return [];
        }

        return $this->sanitizeAccounts($rows);
    }

    private function sanitizeAccounts(array $rows): array
    {
        $result = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $clientId = trim((string)($row['client_id'] ?? ''));
            $clientSecret = trim((string)($row['client_secret'] ?? ''));
            $mode = strtoupper(trim((string)($row['mode'] ?? 'LIVE')));
            $currency = strtoupper(trim((string)($row['currency'] ?? 'USD')));

            if ($clientId === '' || $clientSecret === '') {
                continue;
            }

            if (!in_array($mode, ['LIVE', 'SANDBOX'], true)) {
                $mode = 'LIVE';
            }

            if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
                $currency = 'USD';
            }

            $result[] = [
                'id' => $index,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'mode' => $mode,
                'currency' => $currency,
            ];
        }

        return array_values($result);
    }

    /**
     * Административная панель
     */
    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'title' => 'PayPal',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'accounts' => $this->getAccounts(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/paypal/webhook',
        ]);

        tpl::displayPlugin('/paypal/tpl/admin.html');
    }

    /**
     * Сохранение настроек
     */
    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        $accounts = [];
        if (isset($_POST['accounts']) && is_array($_POST['accounts'])) {
            foreach ($_POST['accounts'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $accounts[] = [
                    'client_id' => trim((string)($row['client_id'] ?? '')),
                    'client_secret' => trim((string)($row['client_secret'] ?? '')),
                    'mode' => strtoupper(trim((string)($row['mode'] ?? 'LIVE'))),
                    'currency' => strtoupper(trim((string)($row['currency'] ?? 'USD'))),
                ];
            }
        }

        $accounts = $this->sanitizeAccounts($accounts);
        $this->setPluginSetting('accounts', $accounts);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('paypal_settings_saved'));
    }

    /**
     * Страница оплаты для пользователей
     */
    public function payment(?int $count = null): void
    {
        if (!user::self()->isAuth()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase(234));
            }
            redirect::location('/login');
            return;
        }

        $accounts = $this->getAccounts();

        if (empty($accounts)) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase('paypal_no_accounts'));
            } else {
                echo lang::get_phrase('paypal_no_accounts');
                exit;
            }
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        $sphereCoinCost = $donateConfig->getSphereCoinCost();
        $rateCalc = static fn($r) => round($sphereCoinCost >= 1 ? $r / $sphereCoinCost : $r * $sphereCoinCost, 4);
        $USD_val = $rateCalc($donateConfig->getRatioUSD());
        $EUR_val = $rateCalc($donateConfig->getRatioEUR());
        $RUB_val = $rateCalc($donateConfig->getRatioRUB());
        $UAH_val = $rateCalc($donateConfig->getRatioUAH());
        $userCountry = strtoupper(user::self()->getCountry() ?? '');
        $mainCurrency = match(true) {
            $userCountry === 'UA' => 'UAH',
            $userCountry === 'RU' => 'RUB',
            default               => 'USD',
        };

        tpl::addVar([
            'title'        => 'PayPal',
            'accounts'     => $accounts,
            'minAmount'    => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'    => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'      => $USD_val,
            'EUR_val'      => $EUR_val,
            'RUB_val'      => $RUB_val,
            'UAH_val'      => $UAH_val,
            'mainCurrency' => $mainCurrency,
        ]);

        tpl::displayPlugin('/paypal/tpl/payment.html');
    }

    /**
     * Создание платежа
     */
    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error('Плагин выключен');
            }
            redirect::location('/main');
            return;
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        $accountIndex = filter_input(INPUT_POST, 'account_index', FILTER_VALIDATE_INT);
        $userInputAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        if ($accountIndex === false || $accountIndex === null) {
            board::error(lang::get_phrase('paypal_no_account_selected'));
        }

        if (!$userInputAmount || $userInputAmount <= 0) {
            board::error(lang::get_phrase('paypal_enter_correct_amount'));
        }

        $accounts = $this->getAccounts();
        if (!isset($accounts[$accountIndex])) {
            board::error(lang::get_phrase('paypal_account_not_found'));
        }

        $account = $accounts[$accountIndex];
        $currency = $account['currency'];
        $mode = $account['mode'];

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($userInputAmount < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('paypal_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($userInputAmount > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('paypal_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $userInputAmount,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        // Получаем токен доступа
        $accessToken = $this->getAccessToken($account['client_id'], $account['client_secret'], $mode);
        if (!$accessToken) {
            board::error(lang::get_phrase('paypal_token_error'));
        }

        // Создаем заказ
        $orderId = user::self()->getId() . '_' . time() . '_' . random_int(1000, 9999);
        $apiUrl = $mode === 'LIVE' ? self::API_URL_LIVE : self::API_URL_SANDBOX;

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'custom_id' => (string)user::self()->getId(),
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => $currency,
                    ],
                    'description' => 'Donation',
                ],
            ],
            'application_context' => [
                'return_url' => \Ofey\Logan22\component\request\url::host('/balance'),
                'cancel_url' => \Ofey\Logan22\component\request\url::host('/balance'),
            ],
        ];

        $response = $this->request($apiUrl . '/v2/checkout/orders', $orderData, $accessToken);

        if (($response['httpCode'] ?? 0) !== 201) {
            board::error(sprintf(lang::get_phrase('paypal_api_error'), ($response['httpCode'] ?? 0)));
        }

        $result = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($result)) {
            board::error(lang::get_phrase('paypal_invalid_response'));
        }

        if (($result['status'] ?? '') !== 'CREATED') {
            board::error(sprintf(lang::get_phrase('paypal_payment_error'), ($result['message'] ?? 'Unknown error')));
        }

        $paypalOrderId = $result['id'] ?? null;
        if (!$paypalOrderId) {
            board::error(lang::get_phrase('paypal_no_order_id'));
        }

        $approveUrl = $mode === 'LIVE'
            ? "https://www.paypal.com/checkoutnow?token={$paypalOrderId}"
            : "https://www.sandbox.paypal.com/checkoutnow?token={$paypalOrderId}";

        board::response('success', ['url' => $approveUrl]);
    }

    /**
     * Webhook обработчик
     */
    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            echo json_encode(['status' => false, 'message' => 'Plugin disabled']);
            return;
        }

        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input || !isset($input['resource'])) {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid payload or missing resource']);
            echo json_encode(['status' => false, 'message' => 'Invalid input']);
            return;
        }

        $resource = $input['resource'];
        $eventType = (string)($input['event_type'] ?? '');

        if (!in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
            $this->logWebhook('EVENT_IGNORED', ['event_type' => $eventType]);
            echo json_encode(['status' => true, 'message' => 'Event type not processed']);
            return;
        }

        $orderId = null;
        if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED'], true)) {
            $orderId = $resource['id'] ?? null;
        } elseif ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        }

        if (!$orderId) {
            $this->logWebhook('ORDER_ID_MISSING', ['event_type' => $eventType]);
            echo json_encode(['status' => false, 'message' => 'Missing order ID']);
            return;
        }

        $accounts = $this->getAccounts();
        if (empty($accounts)) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'No configured accounts']);
            echo json_encode(['status' => false, 'message' => 'No configured accounts']);
            return;
        }

        $orderData = null;
        $selectedAccount = null;

        foreach ($accounts as $account) {
            $accessToken = $this->getAccessToken($account['client_id'], $account['client_secret'], $account['mode']);
            if (!$accessToken) {
                continue;
            }

            $apiUrl = $account['mode'] === 'LIVE' ? self::API_URL_LIVE : self::API_URL_SANDBOX;
            $orderResponse = $this->request($apiUrl . '/v2/checkout/orders/' . $orderId, [], $accessToken, 'GET');
            if (($orderResponse['httpCode'] ?? 0) !== 200) {
                continue;
            }

            $decodedOrder = json_decode((string)($orderResponse['body'] ?? ''), true);
            if (!is_array($decodedOrder)) {
                continue;
            }

            $orderData = $decodedOrder;
            $selectedAccount = $account;
            break;
        }

        if (!$orderData || !$selectedAccount) {
            $this->logWebhook('ORDER_NOT_FOUND', ['order_id' => $orderId]);
            echo json_encode(['status' => false, 'message' => 'Order not found in configured accounts']);
            return;
        }

        $orderStatus = (string)($orderData['status'] ?? '');
        if ($orderStatus === 'APPROVED') {
            $accessToken = $this->getAccessToken($selectedAccount['client_id'], $selectedAccount['client_secret'], $selectedAccount['mode']);
            if (!$accessToken) {
                $this->logWebhook('PROCESS_ERROR', ['reason' => 'Access token error on capture', 'order_id' => $orderId]);
                echo json_encode(['status' => false, 'message' => 'Access token error']);
                return;
            }

            $apiUrl = $selectedAccount['mode'] === 'LIVE' ? self::API_URL_LIVE : self::API_URL_SANDBOX;
            $captureResponse = $this->request($apiUrl . '/v2/checkout/orders/' . $orderId . '/capture', new \stdClass(), $accessToken, 'POST');

            if (($captureResponse['httpCode'] ?? 0) !== 201) {
                $this->logWebhook('PROCESS_ERROR', ['reason' => 'Capture failed', 'order_id' => $orderId]);
                echo json_encode(['status' => false, 'message' => 'Capture failed']);
                return;
            }

            $capturedOrder = json_decode((string)($captureResponse['body'] ?? ''), true);
            if (!is_array($capturedOrder)) {
                $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid capture response', 'order_id' => $orderId]);
                echo json_encode(['status' => false, 'message' => 'Invalid capture response']);
                return;
            }

            $orderData = $capturedOrder;
            $orderStatus = (string)($orderData['status'] ?? '');
        }

        if ($orderStatus !== 'COMPLETED') {
            $this->logWebhook('PAYMENT_NOT_CONFIRMED', ['order_id' => $orderId, 'status' => $orderStatus]);
            echo json_encode(['status' => false, 'message' => 'Order status is not completed']);
            return;
        }

        $purchaseUnit = $orderData['purchase_units'][0] ?? null;
        $capture = $purchaseUnit['payments']['captures'][0] ?? null;

        $customId = $purchaseUnit['custom_id'] ?? $capture['custom_id'] ?? null;
        $amount = (float)($capture['amount']['value'] ?? 0);
        $currency = $capture['amount']['currency_code'] ?? null;
        $captureStatus = $capture['status'] ?? null;
        $captureId = $capture['id'] ?? null;

        if (!$customId || !$amount || !$currency || $captureStatus !== 'COMPLETED') {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid payment data', 'order_id' => $orderId]);
            echo json_encode(['status' => false, 'message' => 'Invalid payment data']);
            return;
        }

        $userId = (int)$customId;
        if ($userId <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $customId, 'order_id' => $orderId]);
            echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
            return;
        }

        $uuid = $captureId ?: $orderId;

        try {
            donate::control_uuid($uuid, $this->getNameClass());
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'uuid' => $uuid,
            ], $userId);
            echo json_encode(['status' => false, 'message' => 'UUID control failed']);
            return;
        }

        try {
            $convertedAmount = donate::currency($amount, $currency);
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ], $userId);
            echo json_encode(['status' => false, 'message' => 'Currency conversion failed']);
            return;
        }

        try {
            telegram::telegramNotice(user::getUserId($userId), $amount, $currency, $convertedAmount, $this->getNameClass());
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($userId)
                ->donateAdd($convertedAmount)
                ->AddHistoryDonate(amount: $convertedAmount, pay_system: $this->getNameClass(), input: $inputJSON);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'amount' => $convertedAmount,
            ], $userId);
            echo json_encode(['status' => false, 'message' => 'Failed to add funds']);
            return;
        }

        try {
            donate::addUserBonus($userId, $convertedAmount);
        } catch (\Throwable $e) {
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'order_id' => $orderId,
            'uuid' => $uuid,
            'amount' => $convertedAmount,
            'currency' => (string)$currency,
        ], $userId);

        echo json_encode(['status' => true]);
    }

    /**
     * Получение токена доступа
     */
    private function getAccessToken(string $clientId, string $clientSecret, string $mode): ?string
    {
        $apiUrl = $mode === 'LIVE' ? self::API_URL_LIVE : self::API_URL_SANDBOX;
        $tokenUrl = $apiUrl . '/v1/oauth2/token';

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => "{$clientId}:{$clientSecret}",
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $result = json_decode($response, true);
        return $result['access_token'] ?? null;
    }

    /**
     * Выполнить HTTP запрос к API
     */
    private function request(string $url, array|\stdClass $payload, string $accessToken, string $method = 'POST'): array
    {
        $ch = curl_init($url);

        $httpHeaders = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $httpHeaders,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];

        if (strtoupper($method) !== 'GET') {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }
}
