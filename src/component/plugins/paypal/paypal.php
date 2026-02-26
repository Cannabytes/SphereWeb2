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
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class paypal
{
    private ?string $nameClass = null;

    private const API_URL_LIVE = 'https://api-m.paypal.com';
    private const API_URL_SANDBOX = 'https://api-m.sandbox.paypal.com';

    private const ALLOWED_CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CNY', 'INR', 'MXN', 'BRL', 'RUB'];

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('accounts'))) {
            $this->setPluginSetting('accounts', []);
        }
    }

    private function getNameClass(): string
    {
        if ($this->nameClass === null) {
            $this->nameClass = strtolower((new ReflectionClass($this))->getShortName());
        }

        return $this->nameClass;
    }

    private function getPluginSetting(string $key, mixed $default = null): mixed
    {
        $settings = plugin::getSetting($this->getNameClass());
        return $settings[$key] ?? $default;
    }

    private function setPluginSetting(string $key, mixed $value): void
    {
        $pluginSettings = plugin::get($this->getNameClass());
        $pluginSettings->save([
            'setting' => $key,
            'value' => $value,
            'type' => gettype($value),
            'serverId' => 0,
        ]);
    }

    private function sanitizeSupportedCountries(mixed $countries): array
    {
        if (!is_array($countries)) {
            return ['world'];
        }

        $normalized = [];
        foreach ($countries as $country) {
            if (!is_string($country)) {
                continue;
            }
            $code = strtolower(trim($country));
            if ($code === '' || !preg_match('/^[a-z0-9-]+$/', $code)) {
                continue;
            }
            $normalized[] = $code;
        }

        $normalized = array_values(array_unique($normalized));
        return empty($normalized) ? ['world'] : $normalized;
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
            'accounts' => $this->getAccounts(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/paypal/webhook',
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

        tpl::addVar([
            'title' => 'PayPal',
            'accounts' => $accounts,
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/paypal/tpl/payment.html');
    }

    /**
     * Создание платежа
     */
    public function createPayment(): void
    {
        if (!$this->getPluginSetting('enabled', false)) {
            board::error(lang::get_phrase('paypal_plugin_disabled'));
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
        if (!$this->getPluginSetting('enabled', false)) {
            echo json_encode(['status' => false, 'message' => 'Plugin disabled']);
            return;
        }

        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input || !isset($input['resource'])) {
            echo json_encode(['status' => false, 'message' => 'Invalid input']);
            return;
        }

        $resource = $input['resource'];
        $orderId = $resource['id'] ?? null;
        $customId = $resource['custom_id'] ?? $resource['purchase_units'][0]['custom_id'] ?? null;
        $eventType = $input['event_type'] ?? '';

        if (!$orderId || !$customId) {
            echo json_encode(['status' => false, 'message' => 'Missing order or customer ID']);
            return;
        }

        // Проверяем только события PAYMENT.CAPTURE.COMPLETED
        if ($eventType !== 'PAYMENT.CAPTURE.COMPLETED') {
            echo json_encode(['status' => true, 'message' => 'Event type not processed']);
            return;
        }

        // Получаем информацию о платеже и валидируем
        $captureStatus = $resource['status'] ?? null;
        $amount = (float)($resource['amount']['value'] ?? 0);
        $currency = $resource['amount']['currency_code'] ?? null;

        if ($captureStatus !== 'COMPLETED' || !$amount || !$currency) {
            echo json_encode(['status' => false, 'message' => 'Invalid payment status']);
            return;
        }

        $userId = (int)$customId;
        $uuid = $orderId;

        try {
            donate::control_uuid($uuid, $this->getNameClass());
        } catch (\Throwable $e) {
            echo json_encode(['status' => false, 'message' => 'UUID control failed']);
            return;
        }

        try {
            $convertedAmount = donate::currency($amount, $currency);
        } catch (\Throwable $e) {
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
            echo json_encode(['status' => false, 'message' => 'Failed to add funds']);
            return;
        }

        try {
            donate::addUserBonus($userId, $convertedAmount);
        } catch (\Throwable $e) {
        }

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
    private function request(string $url, array $payload, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }

    /**
     * Проверка AJAX запроса
     */
    private function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
