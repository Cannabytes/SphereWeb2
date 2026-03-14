<?php

namespace wata;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\ip;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\config\donateSystem;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class wata extends BasePaymentPlugin
{
    private const CREATE_LINK_URL = 'https://api.wata.pro/api/h2h/links';

    private const PUBLIC_KEY_URL = 'https://api.wata.pro/api/h2h/public-key';

    private const DEFAULT_CURRENCY = 'RUB';

    private const ALLOWED_IPS = [
    ];

    protected function isConfigured(): bool
    {
        return $this->getAccessToken() !== '';
    }

    private function getStoredAccessToken(): string
    {
        return trim((string)$this->getPluginSetting('access_token', ''));
    }

    private function getStoredCurrency(): string
    {
        return trim((string)$this->getPluginSetting('currency', ''));
    }

    private function getAccessToken(?int $serverId = null): string
    {
        $storedToken = $this->getStoredAccessToken();
        if ($storedToken !== '') {
            return $storedToken;
        }

        return trim($this->getLegacyInput('access_token', $serverId));
    }

    private function getCurrency(?int $serverId = null): string
    {
        $storedCurrency = $this->getStoredCurrency();
        if ($storedCurrency !== '') {
            return $this->sanitizeCurrency($storedCurrency, self::DEFAULT_CURRENCY);
        }

        $legacyCurrency = $this->getLegacyCurrency($serverId);
        return $this->sanitizeCurrency($legacyCurrency ?: self::DEFAULT_CURRENCY, self::DEFAULT_CURRENCY);
    }

    private function getLegacySystem(?int $serverId = null): ?donateSystem
    {
        try {
            $serverId ??= (int)(user::self()->getServerId() ?? 0);
            if ($serverId <= 0) {
                return null;
            }

            $serverModel = server::getServer($serverId);
            if ($serverModel === null) {
                return null;
            }

            return $serverModel->donate()->get($this->getNameClass());
        } catch (\Throwable) {
            return null;
        }
    }

    private function getLegacyInput(string $key, ?int $serverId = null): string
    {
        $legacySystem = $this->getLegacySystem($serverId);
        if ($legacySystem === null) {
            return '';
        }

        return trim((string)$legacySystem->getInput($key));
    }

    private function getLegacyCurrency(?int $serverId = null): ?string
    {
        $legacySystem = $this->getLegacySystem($serverId);
        if ($legacySystem === null) {
            return null;
        }

        $currency = $legacySystem->getCurrency();
        return $currency === null ? null : trim((string)$currency);
    }

    private function resolveUserServerId(int $userId): int
    {
        try {
            return (int)(user::getUserId($userId)->getServerId() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        tpl::addVar([
            'title' => 'Wata',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => $this->resolvePluginDescription('wata_gateway_description'),
            'accessToken' => $this->getStoredAccessToken(),
            'currency' => $this->getStoredCurrency(),
            'selectedCountries' => $this->sanitizeSupportedCountries($this->getPluginSetting('supported_countries', ['ru'])),
            'webhookUrl' => $this->getBaseUrl() . '/plugin/wata/webhook',
        ]);

        tpl::displayPlugin('/wata/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $accessToken = trim((string)($_POST['access_token'] ?? ''));
        $currencyRaw = trim((string)($_POST['currency'] ?? ''));
        $currency = $currencyRaw === '' ? '' : $this->sanitizeCurrency($currencyRaw, self::DEFAULT_CURRENCY);
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['ru']);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        $this->setPluginSetting('access_token', $accessToken);
        $this->setPluginSetting('currency', $currency);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('wata_settings_saved'));
    }

    public function payment(?int $count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase('wata_plugin_disabled'));
            }

            redirect::location('/main');
            return;
        }

        if (!user::self()->isAuth()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase(234));
            }

            redirect::location('/login');
            return;
        }

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('wata_not_configured'));
        }

        $donateConfig = server::getServer(user::self()->getServerId())->donate();

        $sphereCoinCost = $donateConfig->getSphereCoinCost();
        $rateCalc = static fn($ratio): float => round($sphereCoinCost >= 1 ? $ratio / $sphereCoinCost : $ratio * $sphereCoinCost, 4);
        $userCountry = strtoupper((string)(user::self()->getCountry() ?? ''));
        $mainCurrency = match (true) {
            $userCountry === 'UA' => 'UAH',
            $userCountry === 'RU' => 'RUB',
            default => 'USD',
        };

        tpl::addVar([
            'title' => 'Wata',
            'paymentCurrency' => $this->getCurrency((int)(user::self()->getServerId() ?? 0)),
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val' => $rateCalc($donateConfig->getRatioUSD()),
            'EUR_val' => $rateCalc($donateConfig->getRatioEUR()),
            'RUB_val' => $rateCalc($donateConfig->getRatioRUB()),
            'UAH_val' => $rateCalc($donateConfig->getRatioUAH()),
            'mainCurrency' => $mainCurrency,
        ]);

        tpl::displayPlugin('/wata/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase('wata_plugin_disabled'));
            }

            redirect::location('/main');
            return;
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
        if ($count === false || $count === null || $count <= 0) {
            board::error(lang::get_phrase('wata_enter_amount'));
        }

        $serverId = (int)(user::self()->getServerId() ?? 0);
        $accessToken = $this->getAccessToken($serverId);
        if ($accessToken === '') {
            board::error(lang::get_phrase('wata_not_configured_admin'));
        }

        $currency = $this->getCurrency($serverId);
        $donateConfig = server::getServer($serverId)->donate();

        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('wata_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('wata_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            (float)$count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $payload = [
            'amount' => number_format((float)$amount, 2, '.', ''),
            'currency' => $currency,
            'orderId' => (string)user::self()->getId(),
            'successRedirectUrl' => $this->getBaseUrl() . '/donate/pay',
            'failRedirectUrl' => $this->getBaseUrl() . '/donate/pay',
        ];

        $response = $this->requestJson(
            self::CREATE_LINK_URL,
            'POST',
            $payload,
            ['Authorization: Bearer ' . $accessToken]
        );

        if (($response['error'] ?? '') !== '') {
            board::error(sprintf(lang::get_phrase('wata_curl_error'), (string)$response['error']));
        }

        $result = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($result)) {
            board::error(lang::get_phrase('wata_invalid_response'));
        }

        $apiMessage = $result['error']['message'] ?? $result['message'] ?? null;
        if (($response['httpCode'] ?? 0) >= 400) {
            board::error(sprintf(lang::get_phrase('wata_api_error'), (string)($apiMessage ?? $response['httpCode'])));
        }

        if (is_string($apiMessage) && isset($result['error'])) {
            board::error($apiMessage);
        }

        $paymentUrl = $result['url'] ?? null;
        if (!is_string($paymentUrl) || trim($paymentUrl) === '') {
            board::error(lang::get_phrase('wata_no_payment_link'));
        }

        board::response('success', ['url' => $paymentUrl]);
    }

    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            echo 'disabled';
            return;
        }

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->logWebhook('INVALID_METHOD', ['method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'GET')]);
            http_response_code(405);
            echo 'method not allowed';
            return;
        }

        ip::allowIP(self::ALLOWED_IPS);

        $rawBody = (string)file_get_contents('php://input');
        if (trim($rawBody) === '') {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Empty request body']);
            http_response_code(400);
            echo 'empty body';
            return;
        }

        $signature = $this->getSignatureHeader();
        if ($signature === '') {
            $this->logWebhook('SIGNATURE_MISSING', ['reason' => 'Missing X-Signature header']);
            http_response_code(400);
            echo 'missing signature';
            return;
        }

        $publicKey = $this->getWataPublicKey();
        if ($publicKey === null) {
            $this->logWebhook('PUBLIC_KEY_FETCH_FAILED', ['reason' => 'Unable to load Wata public key']);
            http_response_code(500);
            echo 'public key error';
            return;
        }

        if (!$this->checkSign($rawBody, $signature, $publicKey)) {
            $this->logWebhook('SIGN_INVALID');
            http_response_code(400);
            echo 'wrong sign';
            return;
        }

        $request = json_decode($rawBody, true);
        if (!is_array($request)) {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Invalid JSON body']);
            http_response_code(400);
            echo 'invalid json';
            return;
        }

        $transactionId = trim((string)($request['transactionId'] ?? ''));
        if ($transactionId === '') {
            $this->logWebhook('TRANSACTION_ID_MISSING');
            http_response_code(400);
            echo 'missing transaction';
            return;
        }

        $userIdRaw = trim((string)($request['orderId'] ?? ''));
        if (!ctype_digit($userIdRaw) || (int)$userIdRaw <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $userIdRaw, 'transaction_id' => $transactionId]);
            http_response_code(400);
            echo 'invalid user';
            return;
        }

        $userId = (int)$userIdRaw;
        $paymentStatus = trim((string)($request['transactionStatus'] ?? ''));
        if ($paymentStatus !== 'Paid') {
            $this->logWebhook('PAYMENT_NOT_CONFIRMED', [
                'status' => $paymentStatus,
                'transaction_id' => $transactionId,
            ], $userId);
            echo 'no paid';
            return;
        }

        try {
            $user = user::getUserId($userId);
        } catch (\Throwable $e) {
            $this->logWebhook('INVALID_USER_ID', [
                'user_id' => $userId,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            http_response_code(400);
            echo 'invalid user';
            return;
        }

        $serverId = $this->resolveUserServerId($userId);
        $currency = $this->sanitizeCurrency(
            (string)($request['currency'] ?? $this->getCurrency($serverId)),
            $this->getCurrency($serverId)
        );
        $invoiceAmount = (float)($request['amount'] ?? 0);
        if ($invoiceAmount <= 0) {
            $this->logWebhook('AMOUNT_INVALID', [
                'amount' => $request['amount'] ?? null,
                'transaction_id' => $transactionId,
            ], $userId, $serverId);
            http_response_code(400);
            echo 'invalid amount';
            return;
        }

        try {
            donate::control_uuid($transactionId, $this->getNameClass(), $rawBody);
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ], $userId, $serverId);
            echo 'duplicate';
            return;
        }

        try {
            $donateAmount = donate::currency($invoiceAmount, $currency);
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'currency' => $currency,
                'amount' => $invoiceAmount,
            ], $userId, $serverId);
            echo 'currency error';
            return;
        }

        try {
            telegram::telegramNotice($user, $invoiceAmount, $currency, $donateAmount, $this->getNameClass());
        } catch (\Throwable) {
        }

        try {
            $user
                ->donateAdd($donateAmount)
                ->AddHistoryDonate(amount: $donateAmount, pay_system: $this->getNameClass(), input: $rawBody);
            donate::addUserBonus($userId, $donateAmount);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'currency' => $currency,
                'amount' => $donateAmount,
            ], $userId, $serverId);
            echo 'failed';
            return;
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'transaction_id' => $transactionId,
            'currency' => $currency,
            'amount' => $donateAmount,
        ], $userId, $serverId);

        echo 'YES';
    }

    private function getSignatureHeader(): string
    {
        $header = trim((string)($_SERVER['HTTP_X_SIGNATURE'] ?? ''));
        if ($header !== '') {
            return $header;
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string)$name) === 'x-signature') {
                        return trim((string)$value);
                    }
                }
            }
        }

        return '';
    }

    private function getWataPublicKey(): ?string
    {
        $response = $this->requestJson(self::PUBLIC_KEY_URL, 'GET');
        if (($response['error'] ?? '') !== '') {
            return null;
        }

        if ((int)($response['httpCode'] ?? 0) >= 400) {
            return null;
        }

        $data = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($data) || empty($data['value'])) {
            return null;
        }

        return trim((string)$data['value']);
    }

    private function checkSign(string $rawBody, string $signature, string $publicKey): bool
    {
        $signatureBytes = base64_decode($signature, true);
        if ($signatureBytes === false) {
            return false;
        }

        $publicSignature = openssl_pkey_get_public($publicKey);
        if ($publicSignature === false) {
            return false;
        }

        return openssl_verify($rawBody, $signatureBytes, $publicSignature, OPENSSL_ALGO_SHA512) === 1;
    }

    private function requestJson(string $url, string $method = 'GET', ?array $payload = null, array $headers = []): array
    {
        $ch = curl_init($url);
        $requestHeaders = ['Accept: application/json'];

        if ($payload !== null) {
            $requestHeaders[] = 'Content-Type: application/json';
        }

        foreach ($headers as $header) {
            $requestHeaders[] = $header;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($payload ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'body' => $body,
            'error' => $error,
            'httpCode' => $httpCode,
        ];
    }
}