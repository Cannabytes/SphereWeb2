<?php

namespace paypal;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
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

    private const DEFAULT_CURRENCY = 'USD';

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

    private function sanitizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        if ($currency === '' || !preg_match('/^[A-Z0-9]{3,10}$/', $currency)) {
            return self::DEFAULT_CURRENCY;
        }

        return $currency;
    }

    private function getMode(): string
    {
        $mode = strtoupper(trim((string)$this->getPluginSetting('mode', 'LIVE')));
        return $mode === 'SANDBOX' ? 'SANDBOX' : 'LIVE';
    }

    private function getCurrency(): string
    {
        return $this->sanitizeCurrency((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY));
    }

    private function getBaseUrl(): string
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $scheme . '://' . $host;
    }

    private function getApiBaseUrl(): string
    {
        return $this->getMode() === 'LIVE'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getCheckoutBaseUrl(): string
    {
        return $this->getMode() === 'LIVE'
            ? 'https://www.paypal.com'
            : 'https://www.sandbox.paypal.com';
    }

    private function isPluginActive(): bool
    {
        return (bool)plugin::getPluginActive($this->getNameClass());
    }

    private function isConfigured(): bool
    {
        return trim((string)$this->getPluginSetting('client_id', '')) !== ''
            && trim((string)$this->getPluginSetting('secret_key', '')) !== '';
    }

    private function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'title' => 'PayPal',
            'pluginName' => $this->getNameClass(),
            'mode' => $this->getMode(),
            'currency' => $this->getCurrency(),
            'clientId' => (string)$this->getPluginSetting('client_id', ''),
            'secretKey' => (string)$this->getPluginSetting('secret_key', ''),
            'webhookId' => (string)$this->getPluginSetting('webhook_id', ''),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/paypal/webhook',
        ]);

        tpl::displayPlugin('/paypal/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $mode = strtoupper(trim((string)($_POST['mode'] ?? 'LIVE')));
        $mode = $mode === 'SANDBOX' ? 'SANDBOX' : 'LIVE';

        $currency = $this->sanitizeCurrency((string)($_POST['currency'] ?? self::DEFAULT_CURRENCY));
        $clientId = trim((string)($_POST['client_id'] ?? ''));
        $secretKey = trim((string)($_POST['secret_key'] ?? ''));
        $webhookId = trim((string)($_POST['webhook_id'] ?? ''));
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['world']);

        if ($clientId === '' || $secretKey === '') {
            board::error('Заполните client_id и secret_key');
        }

        $this->setPluginSetting('mode', $mode);
        $this->setPluginSetting('currency', $currency);
        $this->setPluginSetting('client_id', $clientId);
        $this->setPluginSetting('secret_key', $secretKey);
        $this->setPluginSetting('webhook_id', $webhookId);
        $this->setPluginSetting('supported_countries', $supportedCountries);

        board::success('Настройки PayPal сохранены');
    }

    public function payment(?int $count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error('Плагин PayPal выключен');
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
            board::error('PayPal не настроен. Обратитесь к администратору.');
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title' => 'PayPal',
            'currency' => $this->getCurrency(),
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/paypal/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error('Плагин PayPal выключен');
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error('PayPal не настроен');
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($count === false || $count === null || $count <= 0) {
            board::error('Введите сумму цифрой');
        }

        $currency = $this->getCurrency();
        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error('Минимальное пополнение: ' . $donateConfig->getMinSummaPaySphereCoin());
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error('Максимальное пополнение: ' . $donateConfig->getMaxSummaPaySphereCoin());
        }

        $amount = donate::sphereCoinSmartCalc(
            (float)$count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        try {
            $accessToken = $this->getAccessToken();

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'custom_id' => (string)user::self()->getId(),
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format((float)$amount, 2, '.', ''),
                    ],
                    'description' => 'Payment for donation',
                ]],
                'application_context' => [
                    'return_url' => $this->getBaseUrl() . '/balance',
                    'cancel_url' => $this->getBaseUrl() . '/balance',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = $this->request(
                $this->getApiBaseUrl() . '/v2/checkout/orders',
                'POST',
                $payload,
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ]
            );

            if (!in_array($response['httpCode'], [200, 201], true)) {
                throw new \RuntimeException('PayPal API error: HTTP ' . $response['httpCode']);
            }

            $data = $response['json'] ?? [];
            $approveUrl = null;
            foreach (($data['links'] ?? []) as $link) {
                if (($link['rel'] ?? '') === 'approve' && !empty($link['href'])) {
                    $approveUrl = (string)$link['href'];
                    break;
                }
            }

            if ($approveUrl === null && !empty($data['id'])) {
                $approveUrl = $this->getCheckoutBaseUrl() . '/checkoutnow?token=' . $data['id'];
            }

            if ($approveUrl === null) {
                throw new \RuntimeException('PayPal не вернул ссылку на оплату');
            }

            board::response('success', ['url' => $approveUrl]);
        } catch (\Throwable $e) {
            board::error($e->getMessage());
        }
    }

    public function webhook(): void
    {
        if (!$this->isPluginActive() || !$this->isConfigured()) {
            echo 'disabled';
            return;
        }

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed', true, 405);
            echo 'Method not allowed';
            return;
        }

        $rawPayload = (string)file_get_contents('php://input');
        if ($rawPayload === '') {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Empty payload';
            return;
        }

        $event = json_decode($rawPayload, true);
        if (!is_array($event)) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid payload';
            return;
        }

        $eventType = (string)($event['event_type'] ?? '');
        if (!in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED'], true)) {
            echo 'ignored';
            return;
        }

        try {
            $accessToken = $this->getAccessToken();

            $webhookId = trim((string)$this->getPluginSetting('webhook_id', ''));
            if ($webhookId !== '' && !$this->verifyWebhookSignature($event, $accessToken, $webhookId)) {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Invalid signature';
                return;
            }

            $orderId = (string)($event['resource']['id'] ?? '');
            if ($orderId === '') {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Invalid order id';
                return;
            }

            $captureResponse = $this->request(
                $this->getApiBaseUrl() . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
                'POST',
                new \stdClass(),
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ]
            );

            if (!in_array($captureResponse['httpCode'], [200, 201], true)) {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Capture failed';
                return;
            }

            $result = $captureResponse['json'] ?? [];
            if (($result['status'] ?? '') !== 'COMPLETED') {
                echo 'not_completed';
                return;
            }

            $purchaseUnit = $result['purchase_units'][0] ?? [];
            $capture = $purchaseUnit['payments']['captures'][0] ?? [];

            $captureId = (string)($capture['id'] ?? $orderId);
            $currency = strtoupper((string)($capture['amount']['currency_code'] ?? $purchaseUnit['amount']['currency_code'] ?? $this->getCurrency()));
            $amountInput = (float)($capture['amount']['value'] ?? $purchaseUnit['amount']['value'] ?? 0);
            $customId = (int)($capture['custom_id'] ?? $purchaseUnit['custom_id'] ?? 0);

            if ($customId <= 0 || $amountInput <= 0) {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Invalid payment data';
                return;
            }

            donate::control_uuid($captureId, $this->getNameClass(), $rawPayload);

            try {
                $amount = donate::currency($amountInput, $currency);
            } catch (\Throwable $e) {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Currency conversion failed';
                return;
            }

            try {
                telegram::telegramNotice(user::getUserId($customId), $amountInput, $currency, $amount, $this->getNameClass());
            } catch (\Throwable $e) {
            }

            try {
                user::getUserId($customId)
                    ->donateAdd($amount)
                    ->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass(), input: $rawPayload);
            } catch (\Throwable $e) {
                header('HTTP/1.1 400 Bad Request', true, 400);
                echo 'Failed to add funds';
                return;
            }

            try {
                donate::addUserBonus($customId, $amount);
            } catch (\Throwable $e) {
            }

            echo 'OK';
        } catch (\Throwable $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo $e->getMessage();
        }
    }

    private function getAccessToken(): string
    {
        $clientId = trim((string)$this->getPluginSetting('client_id', ''));
        $secretKey = trim((string)$this->getPluginSetting('secret_key', ''));

        if ($clientId === '' || $secretKey === '') {
            throw new \RuntimeException('PayPal credentials are empty');
        }

        $response = $this->request(
            $this->getApiBaseUrl() . '/v1/oauth2/token',
            'POST',
            'grant_type=client_credentials',
            [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            $clientId . ':' . $secretKey
        );

        if ($response['httpCode'] !== 200) {
            $description = (string)($response['json']['error_description'] ?? 'OAuth failed');
            throw new \RuntimeException('PayPal auth error: ' . $description);
        }

        $token = (string)($response['json']['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('PayPal access token not received');
        }

        return $token;
    }

    private function verifyWebhookSignature(array $event, string $accessToken, string $webhookId): bool
    {
        $transmissionId = (string)($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '');
        $transmissionTime = (string)($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '');
        $transmissionSig = (string)($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '');
        $certUrl = (string)($_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '');
        $authAlgo = (string)($_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '');

        if ($transmissionId === '' || $transmissionTime === '' || $transmissionSig === '' || $certUrl === '' || $authAlgo === '') {
            return false;
        }

        $payload = [
            'auth_algo' => $authAlgo,
            'cert_url' => $certUrl,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $transmissionSig,
            'transmission_time' => $transmissionTime,
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ];

        $response = $this->request(
            $this->getApiBaseUrl() . '/v1/notifications/verify-webhook-signature',
            'POST',
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]
        );

        if ($response['httpCode'] !== 200) {
            return false;
        }

        return strtoupper((string)($response['json']['verification_status'] ?? '')) === 'SUCCESS';
    }

    private function request(string $url, string $method, mixed $payload = null, array $headers = [], ?string $userPwd = null): array
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if ($userPwd !== null) {
            curl_setopt($ch, CURLOPT_USERPWD, $userPwd);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($payload !== null) {
            if (is_array($payload) || is_object($payload)) {
                $payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$payload);
        }

        $rawBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('PayPal CURL error: ' . $curlError);
        }

        $decoded = null;
        if (is_string($rawBody) && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
        }

        return [
            'httpCode' => $httpCode,
            'body' => $rawBody,
            'json' => is_array($decoded) ? $decoded : [],
        ];
    }
}
