<?php

namespace cryptocloud;

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

class cryptocloud extends BasePaymentPlugin
{
    private const API_INVOICE_CREATE_URL = 'https://api.cryptocloud.plus/v2/invoice/create';

    private const API_INVOICE_INFO_URL = 'https://api.cryptocloud.plus/v2/invoice/merchant/info';

    private const DEFAULT_CURRENCY = 'USD';

    protected function isConfigured(): bool
    {
        return trim((string)$this->getPluginSetting('shop_id', '')) !== ''
            && trim((string)$this->getPluginSetting('api_key', '')) !== ''
            && trim((string)$this->getPluginSetting('secret_key', '')) !== '';
    }

    private function getCurrency(): string
    {
        return $this->sanitizeCurrency((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY), self::DEFAULT_CURRENCY);
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);
        $baseUrl = $this->getBaseUrl();

        tpl::addVar([
            'title' => 'CryptoCloud',
            'pluginName' => $this->getNameClass(),
            'shopId' => (string)$this->getPluginSetting('shop_id', ''),
            'apiKey' => (string)$this->getPluginSetting('api_key', ''),
            'secretKey' => (string)$this->getPluginSetting('secret_key', ''),
            'currency' => $this->getCurrency(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $baseUrl . '/cryptocloud/webhook',
            'successUrl' => $baseUrl . '/cryptocloud/payment/success',
            'failUrl' => $baseUrl . '/cryptocloud/payment/fail',
            'returnUrl' => $baseUrl . '/cryptocloud/payment/return',
        ]);

        tpl::displayPlugin('/cryptocloud/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $shopId = trim((string)($_POST['shop_id'] ?? ''));
        $apiKey = trim((string)($_POST['api_key'] ?? ''));
        $secretKey = trim((string)($_POST['secret_key'] ?? ''));
        $currency = $this->sanitizeCurrency((string)($_POST['currency'] ?? self::DEFAULT_CURRENCY));
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['world']);

        if ($shopId === '' || $apiKey === '' || $secretKey === '') {
            board::error('Заполните shop_id, api_key и secret_key');
        }

        $this->setPluginSetting('shop_id', $shopId);
        $this->setPluginSetting('api_key', $apiKey);
        $this->setPluginSetting('secret_key', $secretKey);
        $this->setPluginSetting('currency', $currency);
        $this->setPluginSetting('supported_countries', $supportedCountries);

        board::success('Настройки CryptoCloud сохранены');
    }

    public function payment(?int $count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error('Плагин CryptoCloud выключен');
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
            board::error('CryptoCloud не настроен. Обратитесь к администратору.');
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title' => 'CryptoCloud',
            'currency' => $this->getCurrency(),
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/cryptocloud/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error('Плагин CryptoCloud выключен');
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error('CryptoCloud не настроен');
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($count === false || $count === null || $count <= 0) {
            board::error('Введите сумму цифрой');
        }

        $currency = $this->getCurrency();
        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        $count = donate::sphereCoinSmartCalc($count, $donateConfig->getRatio($currency), $donateConfig->getSphereCoinCost());

        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error('Минимальное пополнение: ' . $donateConfig->getMinSummaPaySphereCoin());
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error('Максимальное пополнение: ' . $donateConfig->getMaxSummaPaySphereCoin());
        }

        $amount = donate::sphereCoinSmartCalc(
            $count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $response = $this->request(self::API_INVOICE_CREATE_URL, [
            'shop_id' => (string)$this->getPluginSetting('shop_id', ''),
            'amount' => $amount,
            'order_id' => user::self()->getId() . '@' . time(),
            'currency' => $currency,
            'email' => user::self()->getEmail(),
        ]);

        if (($response['status'] ?? 'fail') !== 'success') {
            board::error((string)($response['msg'] ?? $response['detail'] ?? $response['message'] ?? 'Unknown error'));
        }

        $url = $response['result']['link'] ?? null;
        if (!is_string($url) || trim($url) === '') {
            board::error('CryptoCloud не вернул ссылку на оплату');
        }

        board::response('success', ['url' => $url]);
    }

    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            echo 'disabled';
            return;
        }

        if (!$this->isConfigured()) {
            echo 'disabled';
            return;
        }

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed', true, 405);
            echo 'Method not allowed';
            return;
        }

        $rawBody = (string)file_get_contents('php://input');
        $jsonInput = json_decode($rawBody, true);
        if (!is_array($jsonInput)) {
            $jsonInput = [];
        }

        $token = (string)($_REQUEST['token'] ?? $jsonInput['token'] ?? '');
        if ($token === '' || !$this->verifyWebhookToken($token)) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Bad sign';
            return;
        }

        $jwtPayload = $this->decodeJwtPayload($token);
        $invoiceId = (string)($_REQUEST['invoice_id'] ?? $jsonInput['invoice_id'] ?? $jwtPayload['invoice_id'] ?? $jwtPayload['uuid'] ?? '');
        if ($invoiceId === '') {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'No invoice id';
            return;
        }

        $response = $this->request(self::API_INVOICE_INFO_URL, [
            'uuids' => [$invoiceId],
        ]);

        if (isset($response['detail'])) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo (string)$response['detail'];
            return;
        }

        $invoice = $response['result'][0] ?? null;
        if (!is_array($invoice) || ($response['status'] ?? 'fail') !== 'success' || ($invoice['status'] ?? '') !== 'paid') {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Not paid';
            return;
        }

        try {
            donate::control_uuid($invoiceId, $this->getNameClass());
        } catch (\Throwable $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'UUID control failed';
            return;
        }

        $orderIdRaw = (string)($invoice['order_id'] ?? '');
        $orderIdParts = explode('@', $orderIdRaw);
        $userId = (int)($orderIdParts[0] ?? 0);
        if ($userId <= 0) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid user';
            return;
        }

        $amountInput = (float)($invoice['amount_usd'] ?? $invoice['amount'] ?? 0);
        $currency = $this->sanitizeCurrency((string)($invoice['currency'] ?? 'USD'));

        try {
            $amount = donate::currency($amountInput, $currency);
        } catch (\Throwable $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Currency conversion failed';
            return;
        }

        try {
            telegram::telegramNotice(user::getUserId($userId), $amountInput, $currency, $amount, $this->getNameClass());
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($userId)
                ->donateAdd($amount)
                ->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass(), input: $rawBody !== '' ? $rawBody : json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
            header('HTTP/1.1 500 Internal Server Error', true, 500);
            echo 'Failed to add funds';
            return;
        }

        echo 'OK';
    }

    public function paymentReturn(): void
    {
        tpl::addVar([
            'title' => 'CryptoCloud',
            'statusType' => 'info',
            'statusTitle' => 'Платеж обрабатывается',
            'statusText' => 'Мы ожидаем подтверждение от платежной системы. Баланс будет пополнен после webhook.',
        ]);

        tpl::displayPlugin('/cryptocloud/tpl/payment_result.html');
    }

    public function paymentSuccess(): void
    {
        tpl::addVar([
            'title' => 'CryptoCloud',
            'statusType' => 'success',
            'statusTitle' => 'Оплата принята',
            'statusText' => 'Платеж успешно создан. Если баланс еще не изменился, дождитесь webhook-подтверждения.',
        ]);

        tpl::displayPlugin('/cryptocloud/tpl/payment_result.html');
    }

    public function paymentFail(): void
    {
        tpl::addVar([
            'title' => 'CryptoCloud',
            'statusType' => 'danger',
            'statusTitle' => 'Оплата не завершена',
            'statusText' => 'Платеж отменен или не был завершен. Попробуйте создать платеж снова.',
        ]);

        tpl::displayPlugin('/cryptocloud/tpl/payment_result.html');
    }

    private function request(string $url, array $payload): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . (string)$this->getPluginSetting('api_key', ''),
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            return [
                'status' => 'fail',
                'detail' => $error,
            ];
        }

        $response = json_decode((string)$body, true);
        if (!is_array($response)) {
            return [
                'status' => 'fail',
                'detail' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'body' => (string)$body,
            ];
        }

        $response['http_code'] = $httpCode;
        return $response;
    }

    private function verifyWebhookToken(string $token): bool
    {
        $jwtParts = explode('.', $token);
        if (count($jwtParts) !== 3) {
            return false;
        }

        $signingInput = $jwtParts[0] . '.' . $jwtParts[1];
        $generatedSignature = hash_hmac('sha256', $signingInput, (string)$this->getPluginSetting('secret_key', ''), true);
        $generatedSignature = strtr(rtrim(base64_encode($generatedSignature), '='), '+/', '-_');

        return hash_equals((string)$jwtParts[2], $generatedSignature);
    }

    private function decodeJwtPayload(string $token): array
    {
        $jwtParts = explode('.', $token);
        if (count($jwtParts) !== 3) {
            return [];
        }

        $payload = $jwtParts[1];
        $padding = 4 - (strlen($payload) % 4);
        if ($padding < 4) {
            $payload .= str_repeat('=', $padding);
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return [];
        }

        $json = json_decode($decoded, true);
        return is_array($json) ? $json : [];
    }
}
