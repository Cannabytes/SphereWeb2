<?php

namespace paritypay;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class paritypay extends BasePaymentPlugin
{
    private const API_CREATE_URL = 'https://api.paritypay.ru/invoice/create';

    private const DEFAULT_CURRENCY = 'RUB';

    private const DEFAULT_SERVICE = 'sbp';

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('gateways'))) {
            $this->setPluginSetting('gateways', []);
        }
    }

    protected function isConfigured(): bool
    {
        return !empty($this->getGateways());
    }

    private function getGateways(): array
    {
        $rows = $this->getPluginSetting('gateways', []);
        if (!is_array($rows)) {
            return [];
        }

        return $this->sanitizeGateways($rows);
    }

    private function sanitizeGateways(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $shopId = trim((string)($row['shop_id'] ?? ''));
            $secretKey1 = trim((string)($row['secret_key_1'] ?? ''));
            $secretKey2 = trim((string)($row['secret_key_2'] ?? ''));
            $currency = $this->sanitizeCurrency((string)($row['currency'] ?? self::DEFAULT_CURRENCY), self::DEFAULT_CURRENCY);
            $label = trim((string)($row['label'] ?? ''));
            $service = strtolower(trim((string)($row['service'] ?? self::DEFAULT_SERVICE)));
            $expire = (int)($row['expire'] ?? 0);

            if ($shopId === '' || $secretKey1 === '' || $secretKey2 === '') {
                continue;
            }

            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $shopId)) {
                continue;
            }

            if (!in_array($service, ['card', 'sbp'], true)) {
                $service = '';
            }

            if ($expire < 0) {
                $expire = 0;
            }
            if ($expire > 43200) {
                $expire = 43200;
            }

            $result[] = [
                'id' => count($result),
                'shop_id' => $shopId,
                'secret_key_1' => $secretKey1,
                'secret_key_2' => $secretKey2,
                'currency' => $currency,
                'service' => $service,
                'expire' => $expire,
                'label' => $label,
            ];
        }

        return array_values($result);
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['ru', 'ua']);

        tpl::addVar([
            'title' => 'ParityPay',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'gateways' => $this->getGateways(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/paritypay/webhook',
        ]);

        tpl::displayPlugin('/paritypay/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['ru', 'ua']);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        $gateways = [];
        if (isset($_POST['gateways']) && is_array($_POST['gateways'])) {
            foreach ($_POST['gateways'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $gateways[] = [
                    'shop_id' => trim((string)($row['shop_id'] ?? '')),
                    'secret_key_1' => trim((string)($row['secret_key_1'] ?? '')),
                    'secret_key_2' => trim((string)($row['secret_key_2'] ?? '')),
                    'currency' => strtoupper(trim((string)($row['currency'] ?? self::DEFAULT_CURRENCY))),
                    'service' => strtolower(trim((string)($row['service'] ?? self::DEFAULT_SERVICE))),
                    'expire' => (int)($row['expire'] ?? 0),
                    'label' => trim((string)($row['label'] ?? '')),
                ];
            }
        }

        $gateways = $this->sanitizeGateways($gateways);
        if (empty($gateways)) {
            board::error(lang::get_phrase('paritypay_fill_credentials'));
        }

        $this->setPluginSetting('gateways', $gateways);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('paritypay_settings_saved'));
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

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('paritypay_not_configured'));
        }

        $gateways = $this->getGateways();
        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title' => 'ParityPay',
            'gateways' => $gateways,
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
        ]);

        tpl::displayPlugin('/paritypay/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('paritypay_not_configured_admin'));
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($count === false || $count === null || $count <= 0) {
            board::error(lang::get_phrase('paritypay_enter_amount'));
        }

        $gatewayIndex = (int)($_POST['gateway_index'] ?? 0);
        $gateways = $this->getGateways();
        if (!isset($gateways[$gatewayIndex])) {
            $gatewayIndex = 0;
        }

        if (empty($gateways)) {
            board::error(lang::get_phrase('paritypay_not_configured_admin'));
        }

        $gateway = $gateways[$gatewayIndex];
        $currency = $gateway['currency'];

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();
        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('paritypay_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('paritypay_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $orderId = user::self()->getId() . '.' . user::self()->getServerId() . '.' . time() . random_int(100, 999);

        $payload = [
            'shop_id' => $gateway['shop_id'],
            'amount' => round((float)$amount, 2),
            'order_id' => $orderId,
            'success_url' => $this->getBaseUrl() . '/donate/pay',
            'fail_url' => $this->getBaseUrl() . '/donate/pay',
            'callback_url' => $this->getBaseUrl() . '/paritypay/webhook',
            'custom_fields' => (string)user::self()->getId(),
            'comment' => 'Sphere topup user #' . user::self()->getId(),
        ];

        if ($gateway['service'] !== '') {
            $payload['service'] = $gateway['service'];
        }

        if ((int)$gateway['expire'] > 0) {
            $payload['expire'] = (int)$gateway['expire'];
        }

        $signature = $this->generateSignature($payload, $gateway['secret_key_1']);
        $response = $this->requestJson(self::API_CREATE_URL, $payload, $signature);

        if (($response['error'] ?? '') !== '') {
            board::error(sprintf(lang::get_phrase('paritypay_curl_error'), $response['error']));
        }

        $answer = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($answer)) {
            board::error(lang::get_phrase('paritypay_invalid_response'));
        }

        if (!empty($answer['error'])) {
            board::error(sprintf(lang::get_phrase('paritypay_api_error'), (string)$answer['error']));
        }

        $paymentUrl = trim((string)($answer['link'] ?? ''));
        if ($paymentUrl === '') {
            board::error(lang::get_phrase('paritypay_no_payment_link'));
        }

        board::response('success', ['url' => $paymentUrl]);
    }

    public function webhook(): void
    {
        http_response_code(200);

        if (!$this->isConfigured()) {
            echo 'ok';
            return;
        }

        $payloadRaw = file_get_contents('php://input');
        $payload = json_decode((string)$payloadRaw, true);
        if (!is_array($payload)) {
            echo 'ok';
            return;
        }

        $signature = trim((string)($_SERVER['HTTP_X_SIGNATURE'] ?? ''));
        if ($signature === '') {
            echo 'ok';
            return;
        }

        $shopId = trim((string)($payload['shop_id'] ?? ''));
        $gateway = $this->findGatewayByShopId($shopId);
        if ($gateway === null) {
            echo 'ok';
            return;
        }

        if (!$this->verifyWebhookSignature($payload, $signature, $gateway['secret_key_2'])) {
            echo 'ok';
            return;
        }

        $status = strtoupper(trim((string)($payload['status'] ?? '')));
        if ($status !== 'PAID') {
            echo 'ok';
            return;
        }

        $invoiceId = trim((string)($payload['id'] ?? ''));
        $orderId = trim((string)($payload['order_id'] ?? ''));
        $amountRaw = (string)($payload['amount'] ?? '0');
        $userId = $this->resolveUserId($payload, $orderId);

        if ($invoiceId === '' || $orderId === '' || $userId <= 0) {
            echo 'ok';
            return;
        }

        try {
            donate::control_uuid($invoiceId, $this->getNameClass());
            $amount = donate::currency((float)$amountRaw, $gateway['currency']);

            $userObject = user::getUserId($userId);
            telegram::telegramNotice($userObject, (float)$amountRaw, $gateway['currency'], $amount, $this->getNameClass());
            $userObject->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass());
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable) {
            echo 'ok';
            return;
        }

        echo 'ok';
    }

    private function findGatewayByShopId(string $shopId): ?array
    {
        foreach ($this->getGateways() as $gateway) {
            if (hash_equals((string)$gateway['shop_id'], $shopId)) {
                return $gateway;
            }
        }

        return null;
    }

    private function resolveUserId(array $payload, string $orderId): int
    {
        $customFields = trim((string)($payload['custom_fields'] ?? ''));
        if ($customFields !== '' && ctype_digit($customFields)) {
            return (int)$customFields;
        }

        if (preg_match('/^(\d+)\./', $orderId, $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    private function verifyWebhookSignature(array $payload, string $signature, string $secretKey2): bool
    {
        $generated = $this->generateSignature($payload, $secretKey2);
        return hash_equals(strtolower($generated), strtolower($signature));
    }

    private function generateSignature(array $params, string $secretKey): string
    {
        ksort($params);

        $values = [];
        foreach ($params as $value) {
            $values[] = $this->stringifySignatureValue($value);
        }

        return hash_hmac('sha256', implode('', $values), $secretKey);
    }

    private function stringifySignatureValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function requestJson(string $url, array $data, string $signature): array
    {
        $ch = curl_init($url);

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-SIGNATURE: ' . $signature,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'httpCode' => $httpCode,
            'body' => $body,
            'error' => $error,
        ];
    }
}
