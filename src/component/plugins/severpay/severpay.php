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
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class severpay
{
    private ?string $nameClass = null;

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
            'merchants' => $this->getMerchants(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/severpay/webhook',
        ]);

        tpl::displayPlugin('/severpay/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);

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
            }else{
                echo 'Не настроено ни одного мерчанта. Обратитесь к администратору.';
                exit;
            }
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title' => 'SeverPay',
            'merchants' => $merchants,
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/severpay/tpl/payment.html');
    }

    public function createPayment(): void
    {
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
        $merchants = $this->getMerchants();
        if (empty($merchants)) {
            echo json_encode(['status' => false, 'msg' => 'No merchant configured']);
            return;
        }

        ip::allowIP(self::WEBHOOK_IPS);

        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input || !isset($input['sign'])) {
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
            echo json_encode(['status' => false, 'msg' => 'Wrong sign']);
            return;
        }

        if (($input['type'] ?? '') !== 'payin') {
            echo json_encode(['status' => false, 'msg' => 'Invalid type']);
            return;
        }

        $data = $input['data'] ?? [];
        if (($data['status'] ?? '') !== 'success') {
            echo json_encode(['status' => false, 'msg' => 'Payment not successful']);
            return;
        }

        $orderId = (string)($data['order_id'] ?? '');
        $orderParts = explode('_', $orderId);
        $userId = (int)($orderParts[0] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => false, 'msg' => 'Invalid order_id']);
            return;
        }

        $currency = strtoupper((string)($data['currency'] ?? $verifiedMerchant['currency']));
        $amountInput = (float)($data['amount'] ?? 0);
        $uuid = (string)($data['id'] ?? $inputSign);

        try {
            donate::control_uuid($uuid, $this->getNameClass());
        } catch (\Throwable $e) {
            echo json_encode(['status' => false, 'msg' => 'UUID control failed']);
            return;
        }

        try {
            $amount = donate::currency($amountInput, $currency);
        } catch (\Throwable $e) {
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
            echo json_encode(['status' => false, 'msg' => 'Failed to add funds']);
            return;
        }

        try {
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
        }

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

    private function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
