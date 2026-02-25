<?php

namespace pally;

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

class pally
{
    private ?string $nameClass = null;

    private const API_CREATE_URL = 'https://pal24.pro/api/v1/bill/create';

    private const DEFAULT_CURRENCY = 'RUB';

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
            return ['ru', 'ua'];
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
        return empty($normalized) ? ['ru', 'ua'] : $normalized;
    }

    private function isConfigured(): bool
    {
        return trim((string)$this->getPluginSetting('shop_id', '')) !== ''
            && trim((string)$this->getPluginSetting('api_key', '')) !== '';
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
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['ru', 'ua']);

        tpl::addVar([
            'title' => 'Pally',
            'pluginName' => $this->getNameClass(),
            'shopId' => (string)$this->getPluginSetting('shop_id', ''),
            'apiKey' => (string)$this->getPluginSetting('api_key', ''),
            'currency' => strtoupper((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY)),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/pally/webhook',
        ]);

        tpl::displayPlugin('/pally/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $shopId = trim((string)($_POST['shop_id'] ?? ''));
        $apiKey = trim((string)($_POST['api_key'] ?? ''));
        $currency = strtoupper(trim((string)($_POST['currency'] ?? self::DEFAULT_CURRENCY)));
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['ru', 'ua']);

        if ($shopId === '' || $apiKey === '') {
            board::error(lang::get_phrase('pally_fill_credentials'));
        }

        if (!preg_match('/^[A-Z0-9]{3,8}$/', $currency)) {
            $currency = self::DEFAULT_CURRENCY;
        }

        $this->setPluginSetting('shop_id', $shopId);
        $this->setPluginSetting('api_key', $apiKey);
        $this->setPluginSetting('currency', $currency);
        $this->setPluginSetting('supported_countries', $supportedCountries);

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

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        tpl::addVar([
            'title' => 'Pally',
            'currency' => strtoupper((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY)),
            'minAmount' => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount' => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
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

        $currency = strtoupper((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY));
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

        $payload = [
            'amount' => $amount,
            'order_id' => (string)(time() . random_int(100, 999)),
            'type' => 'normal',
            'shop_id' => (string)$this->getPluginSetting('shop_id', ''),
            'custom' => (string)user::self()->getId(),
            'currency_in' => $currency,
            'payer_pays_commission' => 1,
            'payer_email' => user::self()->getEmail(),
        ];

        $response = $this->request(self::API_CREATE_URL, $payload, (string)$this->getPluginSetting('api_key', ''));

        if (($response['error'] ?? '') !== '') {
            board::error(sprintf(lang::get_phrase('pally_curl_error'), $response['error']));
        }

        $answer = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($answer)) {
            board::error(lang::get_phrase('pally_invalid_response'));
        }

        $success = $answer['success'] ?? false;
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
            echo 'disabled';
            return;
        }

        if (strcasecmp((string)($_POST['Status'] ?? ''), 'SUCCESS') !== 0) {
            echo 'Status no success';
            return;
        }

        $invId = (string)($_POST['InvId'] ?? '');
        $outSumRaw = (string)($_POST['OutSum'] ?? '');
        $currencyIn = (string)($_POST['CurrencyIn'] ?? self::DEFAULT_CURRENCY);
        $userIdRaw = (string)($_POST['custom'] ?? '');
        $signatureValue = (string)($_POST['SignatureValue'] ?? '');

        if ($invId === '' || $outSumRaw === '' || $signatureValue === '' || $userIdRaw === '') {
            echo 'wrong input';
            return;
        }

        if (!$this->checkSignature($signatureValue, $outSumRaw, $invId)) {
            echo 'checksum error';
            return;
        }

        $userId = (int)$userIdRaw;
        if ($userId <= 0) {
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
            echo 'error';
            return;
        }

        echo 'YES';
    }

    private function checkSignature(string $signatureValue, string $outSum, string $invId): bool
    {
        $hash = strtoupper(md5($outSum . ':' . $invId . ':' . (string)$this->getPluginSetting('api_key', '')));
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
