<?php

namespace severpay;

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

class severpay extends BasePaymentPlugin
{
    private const API_CREATE_URL = 'https://severpay.io/api/merchant/payin/create';

    private const ALLOWED_CURRENCIES = ['RUB', 'EUR', 'BYN'];

    private const WEBHOOK_IPS = [
        '45.76.81.14',
        '2001:19f0:6c01:878:5400:5ff:fe38:50d1',
        '207.148.69.64',
        '2401:c080:1400:109b:5400:5ff:fe95:20d3',
    ];

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('merchants'))) {
            $this->setPluginSetting('merchants', []);
        }
    }

    protected function isConfigured(): bool
    {
        $merchants = $this->getMerchants();
        return !empty($merchants);
    }

    private function getMerchants(): array
    {
        $rows = $this->getPluginSetting('merchants', []);
        if (!is_array($rows)) {
            return [];
        }

        return $this->sanitizeMerchants($rows);
    }

    private function sanitizeMerchants(array $rows): array
    {
        $result = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $mid = trim((string)($row['mid'] ?? ''));
            $token = trim((string)($row['token'] ?? ''));
            $currency = strtoupper(trim((string)($row['currency'] ?? 'RUB')));

            if ($mid === '' || $token === '') {
                continue;
            }

            if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
                $currency = 'RUB';
            }

            $result[] = [
                'id' => $index,
                'mid' => $mid,
                'token' => $token,
                'currency' => $currency,
            ];
        }

        return array_values($result);
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'title' => 'SeverPay',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'merchants' => $this->getMerchants(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/severpay/webhook',
        ]);

        tpl::displayPlugin('/severpay/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        $merchants = [];
        if (isset($_POST['merchants']) && is_array($_POST['merchants'])) {
            foreach ($_POST['merchants'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $merchants[] = [
                    'mid' => trim((string)($row['mid'] ?? '')),
                    'token' => trim((string)($row['token'] ?? '')),
                    'currency' => strtoupper(trim((string)($row['currency'] ?? 'RUB'))),
                ];
            }
        }

        $merchants = $this->sanitizeMerchants($merchants);
        $this->setPluginSetting('merchants', $merchants);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('severpay_settings_saved'));
    }

    public function payment(?int $count = null): void
    {
        if (!user::self()->isAuth()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase(234));
            }
            redirect::location('/login');
            return;
        }
        $merchants = $this->getMerchants();

        if (empty($merchants)) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase('severpay_no_merchants'));
            } else {
                echo 'Не настроено ни одного мерчанта. Обратитесь к администратору.';
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
            'title'        => 'SeverPay',
            'merchants'    => $merchants,
            'minAmount'    => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'    => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'      => $USD_val,
            'EUR_val'      => $EUR_val,
            'RUB_val'      => $RUB_val,
            'UAH_val'      => $UAH_val,
            'mainCurrency' => $mainCurrency,
        ]);

        tpl::displayPlugin('/severpay/tpl/payment.html');
    }

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

        $merchantIndex = filter_input(INPUT_POST, 'merchant_index', FILTER_VALIDATE_INT);
        $userInputAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        if ($merchantIndex === false || $merchantIndex === null) {
            board::error(lang::get_phrase('severpay_no_merchant_selected'));
        }

        if (!$userInputAmount || $userInputAmount <= 0) {
            board::error(lang::get_phrase('severpay_enter_correct_amount'));
        }

        $merchants = $this->getMerchants();
        if (!isset($merchants[$merchantIndex])) {
            board::error(lang::get_phrase('severpay_merchant_not_found'));
        }

        $merchant = $merchants[$merchantIndex];
        $currency = $merchant['currency'];

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($userInputAmount < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('severpay_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($userInputAmount > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('severpay_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $userInputAmount,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $orderId = user::self()->getId() . '_' . time() . '_' . random_int(1000, 9999);
        $salt = bin2hex(random_bytes(16));

        $payload = [
            'mid' => (int)$merchant['mid'],
            'amount' => (float)$amount,
            'currency' => $currency,
            'order_id' => $orderId,
            'client_email' => user::self()->getEmail(),
            'client_id' => (string)user::self()->getId(),
            'salt' => $salt,
        ];

        ksort($payload);
        $payload['sign'] = hash_hmac('sha256', json_encode($payload), $merchant['token']);

        $response = $this->request(self::API_CREATE_URL, $payload);

        if (($response['httpCode'] ?? 0) !== 200) {
            board::error(sprintf(lang::get_phrase('severpay_api_error'), ($response['httpCode'] ?? 0)));
        }

        $result = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($result)) {
            board::error(lang::get_phrase('severpay_invalid_response'));
        }

        if (($result['status'] ?? false) !== true) {
            board::error(sprintf(lang::get_phrase('severpay_payment_error'), ($result['msg'] ?? 'Unknown error')));
        }

        $url = $result['data']['url'] ?? null;
        if (!$url) {
            board::error(lang::get_phrase('severpay_no_payment_link'));
        }

        board::response('success', ['url' => $url]);
    }

    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            echo json_encode(['status' => false, 'msg' => 'Plugin disabled']);
            return;
        }

        $merchants = $this->getMerchants();
        if (empty($merchants)) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'No merchant configured']);
            echo json_encode(['status' => false, 'msg' => 'No merchant configured']);
            return;
        }

        ip::allowIP(self::WEBHOOK_IPS);

        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input || !isset($input['sign'])) {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid input or missing sign']);
            echo json_encode(['status' => false, 'msg' => 'Invalid input']);
            return;
        }

        $inputSign = (string)$input['sign'];
        unset($input['sign']);

        $verifiedMerchant = null;
        foreach ($merchants as $merchant) {
            $sign = hash_hmac('sha256', json_encode($input), (string)$merchant['token']);
            if (hash_equals($inputSign, $sign)) {
                $verifiedMerchant = $merchant;
                break;
            }
        }

        if ($verifiedMerchant === null) {
            $this->logWebhook('SIGN_INVALID', ['reason' => 'No merchant matched signature']);
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'msg' => 'Wrong sign'
            ]);
            exit;
        }

        if (($input['type'] ?? '') !== 'payin') {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid type', 'type' => (string)($input['type'] ?? '')]);
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'msg' => 'Invalid type'
            ]);
            exit;
        }

        $data = $input['data'] ?? [];
        if (($data['status'] ?? '') !== 'success') {
            $this->logWebhook('PAYMENT_NOT_CONFIRMED', ['status' => (string)($data['status'] ?? '')]);
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Payment not successful']);
            exit;
        }

        $orderId = (string)($data['order_id'] ?? '');
        $orderParts = explode('_', $orderId);
        $userId = (int)($orderParts[0] ?? 0);
        if ($userId <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['order_id' => $orderId, 'user_id' => $userId]);
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Invalid order_id']);
            return;
        }

        $currency = strtoupper((string)($data['currency'] ?? $verifiedMerchant['currency']));
        $amountInput = (float)($data['amount'] ?? 0);
        $uuid = (string)($data['id'] ?? $inputSign);

        try {
            donate::control_uuid(uuid: $uuid, pay_system_name: get_called_class(), request: $data);
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ], $userId);
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'UUID control failed']);
            return;
        }

        try {
            $amount = donate::currency($amountInput, $currency);
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount' => $amountInput,
                'currency' => $currency,
            ], $userId);
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Currency conversion failed']);
            return;
        }

        try {
            telegram::telegramNotice(user::getUserId($userId), $amountInput, $currency, $amount, $this->getNameClass());
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($userId)
                ->donateAdd($amount)
                ->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass(), input: $inputJSON);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'amount' => $amount,
            ], $userId);
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Failed to add funds']);
            return;
        }

        try {
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'uuid' => $uuid,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
        ], $userId);

        echo json_encode(['status' => true]);
    }

    private function request(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            board::error(sprintf(lang::get_phrase('severpay_api_error'), 'CURL: ' . $error));
        }

        return [
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }

}
