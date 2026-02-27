<?php

namespace unitpay;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class unitpay extends BasePaymentPlugin
{
    private const API_URL     = 'https://unitpay.ru/api';
    private const RESULT_URL  = '/donate';

    private const ALLOWED_CURRENCIES = ['RUB', 'USD', 'EUR', 'UAH'];

    private string $desc = 'Покупка Donate Coin';

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    protected function isConfigured(): bool
    {
        $pub = trim((string)($this->getPluginSetting('publicKey', '')));
        $sec = trim((string)($this->getPluginSetting('secretKey', '')));
        return $pub !== '' && $sec !== '';
    }

    private function getPublicKey(): string
    {
        return trim((string)($this->getPluginSetting('publicKey', '')));
    }

    private function getSecretKey(): string
    {
        return trim((string)($this->getPluginSetting('secretKey', '')));
    }

    private function getCurrency(): string
    {
        $c = strtoupper(trim((string)($this->getPluginSetting('currency', 'RUB'))));
        return in_array($c, self::ALLOWED_CURRENCIES, true) ? $c : 'RUB';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin
    // ─────────────────────────────────────────────────────────────────────────

    public function admin(): void
    {
        validation::user_protection('admin');

        $selectedCountries = $this->sanitizeSupportedCountries(
            $this->getPluginSetting('supported_countries', ['world'])
        );

        tpl::addVar([
            'title'             => 'UnitPay',
            'pluginName'        => $this->getNameClass(),
            'publicKey'         => $this->getPublicKey(),
            'secretKey'         => $this->getSecretKey(),
            'currency'          => $this->getCurrency(),
            'allowedCurrencies' => self::ALLOWED_CURRENCIES,
            'selectedCountries' => $selectedCountries,
            'showMainPage'      => $this->getPluginSetting('showMainPage', false),
            'addToMenu'         => $this->getPluginSetting('addToMenu', false),
            'shop'              => $this->getPluginSetting('shop', []),
            'webhookUrl'        => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/unitpay/webhook',
        ]);

        tpl::displayPlugin('/unitpay/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $publicKey  = trim((string)($_POST['publicKey']  ?? ''));
        $secretKey  = trim((string)($_POST['secretKey']  ?? ''));
        $currency   = strtoupper(trim((string)($_POST['currency'] ?? 'RUB')));

        if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
            $currency = 'RUB';
        }

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);
        $showMainPage = (bool)($_POST['showMainPage'] ?? false);
        $addToMenu = (bool)($_POST['addToMenu'] ?? false);
        $shopRaw = trim((string)($_POST['shop'] ?? ''));
        $shop = $shopRaw === '' ? [] : array_map('intval', array_filter(array_map('trim', explode(',', $shopRaw))));

        $this->setPluginSetting('publicKey',           $publicKey);
        $this->setPluginSetting('secretKey',           $secretKey);
        $this->setPluginSetting('currency',            $currency);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('showMainPage',        $showMainPage);
        $this->setPluginSetting('addToMenu',           $addToMenu);
        $this->setPluginSetting('shop',                $shop);

        board::success(lang::get_phrase('unitpay_settings_saved'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Payment page
    // ─────────────────────────────────────────────────────────────────────────

    public function payment(?int $count = null): void
    {
        if (!user::self()->isAuth()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase(234));
            }
            redirect::location('/login');
            return;
        }

        if (!$this->isPluginActive()) {
            redirect::location('/main');
            return;
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title'         => 'UnitPay',
            'currency'      => $this->getCurrency(),
            'minAmount'     => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'     => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/unitpay/tpl/payment.html');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create payment (AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error(lang::get_phrase('unitpay_plugin_disabled'));
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('unitpay_not_configured'));
        }

        $userInputAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        if (!$userInputAmount || $userInputAmount <= 0) {
            board::error(lang::get_phrase('unitpay_enter_correct_amount'));
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($userInputAmount < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('unitpay_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($userInputAmount > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('unitpay_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $currency  = $this->getCurrency();
        $publicKey = $this->getPublicKey();
        $secretKey = $this->getSecretKey();

        $amount  = donate::sphereCoinSmartCalc(
            $userInputAmount,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $account = (string)user::self()->getId();
        $desc    = $this->desc;

        // UnitPay signature: sha256(account{up}currency{up}desc{up}sum{up}secretKey)
        $signature = hash('sha256', $account . '{up}' . $currency . '{up}' . $desc . '{up}' . $amount . '{up}' . $secretKey);

        $params = [
            'account'           => $account,
            'currency'          => $currency,
            'desc'              => $desc,
            'sum'               => $amount,
            'paymentType'       => 'card',
            'customerEmail'     => user::self()->getEmail(),
            'cashItems'         => base64_encode(json_encode([[
                'name'  => $desc,
                'count' => 1,
                'price' => $amount,
            ]])),
            'projectId'         => $publicKey,
            'resultUrl'         => \Ofey\Logan22\component\request\url::host(self::RESULT_URL),
            'hideMenu'          => true,
            'hideOtherPSMethods' => true,
            'hideOtherMethods'  => true,
            'signature'         => $signature,
        ];

        $requestUrl = self::API_URL . '?' . http_build_query([
            'method' => 'initPayment',
            'params' => $params,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = json_decode((string)file_get_contents($requestUrl), true);

        if (!is_array($response)) {
            board::error(lang::get_phrase('unitpay_api_error'));
        }

        if (isset($response['error']['message'])) {
            board::error($response['error']['message']);
        }

        $redirectUrl = $response['result']['redirectUrl'] ?? null;
        if (!$redirectUrl) {
            board::error(lang::get_phrase('unitpay_api_error'));
        }

        board::response('success', ['url' => $redirectUrl]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Webhook (GET/POST from UnitPay)
    // ─────────────────────────────────────────────────────────────────────────

    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->sendJsonResponse(['error' => ['message' => 'Service disabled']]);
            return;
        }

        if (!$this->isConfigured()) {
            $this->sendJsonResponse(['error' => ['message' => 'Plugin not configured']]);
            return;
        }

        $secretKey = $this->getSecretKey();

        $method = $_REQUEST['method'] ?? '';
        $params = $_REQUEST['params'] ?? [];

        if (empty($method) || !is_array($params)) {
            $this->sendJsonResponse(['error' => ['message' => 'Missing required parameters']]);
            return;
        }

        $userId = $params['account']   ?? null;
        $amount = $params['orderSum']  ?? null;
        $crc    = $params['signature'] ?? '';

        if (empty($userId) || empty($amount) || empty($crc)) {
            $this->sendJsonResponse(['error' => ['message' => 'Missing required parameters']]);
            return;
        }

        // Validate signature
        $verifyParams = $params;
        unset($verifyParams['signature']);
        ksort($verifyParams);
        $sign = hash('sha256', $method . '{up}' . implode('{up}', $verifyParams) . '{up}' . $secretKey);

        if (!hash_equals($sign, $crc)) {
            $this->sendJsonResponse(['error' => ['message' => 'Wrong signature!']]);
            return;
        }

        // Non-pay methods (check, error, refund) — acknowledge and stop
        if ($method !== 'pay') {
            $this->sendJsonResponse(['result' => ['message' => 'Запрос успешно обработан']]);
            return;
        }

        // Validate user ID and amount
        if (!is_numeric($userId) || (int)$userId <= 0) {
            $this->sendJsonResponse(['error' => ['message' => 'Invalid user ID']]);
            return;
        }

        if (!is_numeric($amount) || (float)$amount <= 0) {
            $this->sendJsonResponse(['error' => ['message' => 'Invalid amount']]);
            return;
        }

        $userId = (int)$userId;
        $amountFloat = (float)$amount;

        // De-duplicate using abbreviated signature as UUID
        try {
            donate::control_uuid(substr($crc, 0, 12), $this->getNameClass());
        } catch (\Throwable $e) {
            $this->sendJsonResponse(['error' => ['message' => 'Duplicate transaction']]);
            return;
        }

        $currency = $this->getCurrency();

        try {
            $convertedAmount = donate::currency($amountFloat, $currency);
        } catch (\Throwable $e) {
            $this->sendJsonResponse(['error' => ['message' => 'Currency conversion failed']]);
            return;
        }

        try {
            telegram::telegramNotice(user::getUserId($userId), $amountFloat, $currency, $convertedAmount, $this->getNameClass());
        } catch (\Throwable $e) {
            // Telegram notification is non-critical
        }

        $user = user::getUserId($userId);
        if (!$user) {
            $this->sendJsonResponse(['error' => ['message' => 'User not found']]);
            return;
        }

        try {
            $user->donateAdd($convertedAmount)->AddHistoryDonate(
                amount: $convertedAmount,
                pay_system: $this->getNameClass()
            );
        } catch (\Throwable $e) {
            $this->sendJsonResponse(['error' => ['message' => 'Failed to add funds']]);
            return;
        }

        try {
            donate::addUserBonus($userId, $convertedAmount);
        } catch (\Throwable $e) {
            // Bonus is non-critical
        }

        $this->sendJsonResponse(['result' => ['message' => 'Запрос успешно обработан']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
