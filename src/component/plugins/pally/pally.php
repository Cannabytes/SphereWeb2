<?php

namespace pally;

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

class pally extends BasePaymentPlugin
{
    private const API_CREATE_URL = 'https://pal24.pro/api/v1/bill/create';

    private const DEFAULT_CURRENCY = 'RUB';

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
            $shopId  = trim((string)($row['shop_id'] ?? ''));
            $apiKey  = trim((string)($row['api_key'] ?? ''));
            $currency = strtoupper(trim((string)($row['currency'] ?? self::DEFAULT_CURRENCY)));
            $label   = trim((string)($row['label'] ?? ''));

            if ($shopId === '' || $apiKey === '') {
                continue;
            }
            if (!preg_match('/^[A-Z0-9]{3,8}$/', $currency)) {
                $currency = self::DEFAULT_CURRENCY;
            }
            $result[] = [
                'id'       => count($result),
                'shop_id'  => $shopId,
                'api_key'  => $apiKey,
                'currency' => $currency,
                'label'    => $label,
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
            'title'            => 'Pally',
            'pluginName'       => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'gateways'         => $this->getGateways(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl'       => $this->getBaseUrl() . '/pally/webhook',
        ]);

        tpl::displayPlugin('/pally/tpl/admin.html');
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
                    'shop_id'  => trim((string)($row['shop_id'] ?? '')),
                    'api_key'  => trim((string)($row['api_key'] ?? '')),
                    'currency' => strtoupper(trim((string)($row['currency'] ?? self::DEFAULT_CURRENCY))),
                    'label'    => trim((string)($row['label'] ?? '')),
                ];
            }
        }

        $gateways = $this->sanitizeGateways($gateways);
        if (empty($gateways)) {
            board::error(lang::get_phrase('pally_fill_credentials'));
        }

        $this->setPluginSetting('gateways', $gateways);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('pally_settings_saved'));
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
            board::error(lang::get_phrase('pally_not_configured'));
        }

        $gateways = $this->getGateways();
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
            'title'         => 'Pally',
            'gateways'      => $gateways,
            'minAmount'     => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'     => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'       => $USD_val,
            'EUR_val'       => $EUR_val,
            'RUB_val'       => $RUB_val,
            'UAH_val'       => $UAH_val,
            'mainCurrency'  => $mainCurrency,
        ]);

        tpl::displayPlugin('/pally/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('pally_not_configured_admin'));
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($count === false || $count === null || $count <= 0) {
            board::error(lang::get_phrase('pally_enter_amount'));
        }

        $gatewayIndex = (int)($_POST['gateway_index'] ?? 0);
        $gateways     = $this->getGateways();

        if (!isset($gateways[$gatewayIndex])) {
            $gatewayIndex = 0;
        }

        if (empty($gateways)) {
            board::error(lang::get_phrase('pally_not_configured_admin'));
        }

        $gateway  = $gateways[$gatewayIndex];
        $currency = $gateway['currency'];
        $shopId   = $gateway['shop_id'];
        $apiKey   = $gateway['api_key'];

        if (!preg_match('/^[A-Z0-9]{3,8}$/', $currency)) {
            $currency = self::DEFAULT_CURRENCY;
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('pally_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('pally_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $orderId = $gatewayIndex . '_' . time() . random_int(100, 999);

        $payload = [
            'amount'               => $amount,
            'order_id'             => $orderId,
            'type'                 => 'normal',
            'shop_id'              => $shopId,
            'custom'               => (string)user::self()->getId(),
            'currency_in'          => $currency,
            'payer_pays_commission' => 1,
            'payer_email'          => user::self()->getEmail(),
        ];

        $response = $this->request(self::API_CREATE_URL, $payload, $apiKey);

        $httpCode = (int)($response['httpCode'] ?? 0);
        if ($httpCode !== 0 && ($httpCode < 200 || $httpCode >= 300)) {
            board::error($this->resolveApiErrorMessage($httpCode, (string)($response['body'] ?? '')));
        }

        if (($response['error'] ?? '') !== '') {
            board::error(sprintf(lang::get_phrase('pally_curl_error'), $response['error']));
        }

        $answer = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($answer)) {
            board::error(lang::get_phrase('pally_invalid_response'));
        }

        $success   = $answer['success'] ?? false;
        $isSuccess = $success === true || $success === 'true' || $success === 1 || $success === '1';
        if (!$isSuccess) {
            board::error(sprintf(lang::get_phrase('pally_api_error'), (string)($answer['message'] ?? 'Unknown error')));
        }

        $paymentUrl = (string)($answer['link_page_url'] ?? '');
        if ($paymentUrl === '') {
            board::error(lang::get_phrase('pally_no_payment_link'));
        }

        board::response('success', ['url' => $paymentUrl]);
    }

    public function webhook(): void
    {

        if (!$this->isConfigured()) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'Plugin not configured']);
            echo 'disabled';
            return;
        }

        if (strcasecmp((string)($_POST['Status'] ?? ''), 'SUCCESS') !== 0) {
            $this->logWebhook('PAYMENT_NOT_CONFIRMED', ['status' => (string)($_POST['Status'] ?? '')]);
            echo 'Status no success';
            return;
        }

        $invId         = (string)($_POST['InvId'] ?? '');
        $outSumRaw     = (string)($_POST['OutSum'] ?? '');
        $currencyIn    = (string)($_POST['CurrencyIn'] ?? self::DEFAULT_CURRENCY);
        $userIdRaw     = (string)($_POST['custom'] ?? '');
        $signatureValue = (string)($_POST['SignatureValue'] ?? '');

        if ($invId === '' || $outSumRaw === '' || $signatureValue === '' || $userIdRaw === '') {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Missing required fields']);
            echo 'wrong input';
            return;
        }

        // Resolve api_key by gateway index encoded in order_id prefix
        $apiKey = $this->resolveApiKeyFromInvId($invId);
        if ($apiKey === null) {
            $this->logWebhook('GATEWAY_NOT_FOUND', ['invoice_id' => $invId]);
            echo 'checksum error';
            return;
        }

        if (!$this->checkSignature($signatureValue, $outSumRaw, $invId, $apiKey)) {
            $this->logWebhook('SIGN_INVALID', ['invoice_id' => $invId]);
            echo 'checksum error';
            return;
        }

        $userId = (int)$userIdRaw;
        if ($userId <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $userId, 'invoice_id' => $invId]);
            echo 'wrong user';
            return;
        }

        try {
            donate::control_uuid($invId, $this->getNameClass());
            $amount = donate::currency((float)$outSumRaw, $currencyIn);

            $userObject = user::getUserId($userId);
            telegram::telegramNotice($userObject, (float)$outSumRaw, $currencyIn, $amount, $this->getNameClass());
            $userObject->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass());
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'invoice_id' => $invId,
            ], $userId);
            echo 'error';
            return;
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'invoice_id' => $invId,
            'amount' => $amount,
            'currency' => $currencyIn,
        ], $userId);

        echo 'YES';
    }

    /**
     * Map Pally API HTTP error code + response body to a human-readable message.
     * Error codes sourced from https://pally.info/reference/api (POST /api/v1/bill/create).
     */
    private function resolveApiErrorMessage(int $httpCode, string $body): string
    {
        static $errorMap = [
            'Unauthenticated'              => 'Неверный API Токен (401)',
            'api:error.invalid_amount'     => 'Неверная сумма',
            'api:error.merchant_banned'    => 'Доступ для мерчанта запрещен',
            'api:error.merchant_not_found' => 'Мерчант не найден',
            'api:error.shop_not_found'     => 'Магазин не найден',
            'api:error.shop_not_enabled'   => 'Магазин имеет не активный статус',
            'api:error.access_denied'      => 'Мерчант не имеет доступ до магазина',
            'api:error.rate-not-found'     => 'Направление недоступно',
            'api:error.general_error'      => 'Внутренняя ошибка сервиса (500)',
        ];

        $json = json_decode($body, true);

        // 422 — validation errors (Laravel-style {errors: {field: [msg]}}).
        if ($httpCode === 422) {
            if (is_array($json) && isset($json['errors']) && is_array($json['errors'])) {
                $parts = [];
                foreach ($json['errors'] as $fieldErrors) {
                    foreach ((array)$fieldErrors as $msg) {
                        $parts[] = (string)$msg;
                    }
                }
                if (!empty($parts)) {
                    return 'Pally: Ошибка валидации: ' . implode('; ', $parts);
                }
            }
            return 'Pally: Ошибка валидации входных данных';
        }

        if (is_array($json)) {
            $key = (string)($json['message'] ?? '');
            if (isset($errorMap[$key])) {
                return 'Pally: ' . $errorMap[$key];
            }
            if ($key !== '') {
                return 'Pally API error: ' . $key;
            }
        }

        if ($body !== '') {
            return 'Pally API error (HTTP ' . $httpCode . '): ' . $body;
        }

        return 'Pally API error (HTTP ' . $httpCode . ')';
    }

    /**
     * Resolve the correct api_key from the InvId.
     * New order_ids have the format: {gatewayIndex}_{timestamp}{rand}
     * Legacy order_ids (no prefix) fall back to the first gateway.
     */
    private function resolveApiKeyFromInvId(string $invId): ?string
    {
        $gateways = $this->getGateways();

        if (strpos($invId, '_') !== false) {
            $parts = explode('_', $invId, 2);
            $idx   = (int)$parts[0];
            if (isset($gateways[$idx])) {
                return $gateways[$idx]['api_key'];
            }
        }

        // Legacy / fallback — first gateway
        return isset($gateways[0]) ? $gateways[0]['api_key'] : null;
    }

    private function checkSignature(string $signatureValue, string $outSum, string $invId, string $apiKey): bool
    {
        $hash = strtoupper(md5($outSum . ':' . $invId . ':' . $apiKey));
        return hash_equals($hash, strtoupper($signatureValue));
    }

    private function request(string $url, array $data, string $apiKey): array
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $body    = curl_exec($ch);
        $error   = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'httpCode' => $httpCode,
            'body'     => $body,
            'error'    => $error,
        ];
    }
}
