<?php

namespace primepayments;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class primepayments extends BasePaymentPlugin
{
    private const API_URL = 'https://pay.primepayments.io/API/v2/';

    private const DEFAULT_CURRENCY = 'RUB';

    private const DEFAULT_PAY_WAY = '1';

    protected function isConfigured(): bool
    {
        return (string)$this->getPluginSetting('project_id', '') !== ''
            && (string)$this->getPluginSetting('secret_1', '') !== ''
            && (string)$this->getPluginSetting('secret_2', '') !== '';
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['ru']);

        tpl::addVar([
            'title' => 'PrimePayments',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'projectId' => (string)$this->getPluginSetting('project_id', ''),
            'secret1' => (string)$this->getPluginSetting('secret_1', ''),
            'secret2' => (string)$this->getPluginSetting('secret_2', ''),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/primepayments/webhook',
        ]);

        tpl::displayPlugin('/primepayments/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $projectId = trim((string)($_POST['project_id'] ?? ''));
        $secret1 = trim((string)($_POST['secret_1'] ?? ''));
        $secret2 = trim((string)($_POST['secret_2'] ?? ''));
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['ru']);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        if ($projectId === '' || $secret1 === '' || $secret2 === '') {
            board::error(lang::get_phrase('primepayments_fill_credentials'));
        }

        $this->setPluginSetting('project_id', $projectId);
        $this->setPluginSetting('secret_1', $secret1);
        $this->setPluginSetting('secret_2', $secret2);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success(lang::get_phrase('primepayments_settings_saved'));
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
            board::error(lang::get_phrase('primepayments_not_configured'));
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
            'title'        => 'PrimePayments',
            'currency'     => self::DEFAULT_CURRENCY,
            'minAmount'    => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'    => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'      => $USD_val,
            'EUR_val'      => $EUR_val,
            'RUB_val'      => $RUB_val,
            'UAH_val'      => $UAH_val,
            'mainCurrency' => $mainCurrency,
        ]);

        tpl::displayPlugin('/primepayments/tpl/payment.html');
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

        if (!$this->isConfigured()) {
            board::error(lang::get_phrase('primepayments_not_configured_admin'));
        }

        $count = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        if ($count === false || $count === null || $count <= 0) {
            board::error(lang::get_phrase('primepayments_enter_amount'));
        }

        $currency = self::DEFAULT_CURRENCY;
        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($count < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('primepayments_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($count > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('primepayments_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $count,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $data = [
            'action' => 'initPayment',
            'project' => (string)$this->getPluginSetting('project_id', ''),
            'sum' => $amount,
            'currency' => $currency,
            'innerID' => user::self()->getId(),
            'payWay' => self::DEFAULT_PAY_WAY,
            'email' => user::self()->getEmail(),
            'returnLink' => 1,
        ];

        $data['sign'] = md5(
            (string)$this->getPluginSetting('secret_1', '')
            . $data['action']
            . $data['project']
            . $amount
            . $data['currency']
            . $data['innerID']
            . $data['email']
            . $data['payWay']
        );

        $response = $this->request(self::API_URL, $data);

        if (($response['error'] ?? '') !== '') {
            board::error(sprintf(lang::get_phrase('primepayments_curl_error'), $response['error']));
        }

        $answer = json_decode((string)($response['body'] ?? ''), true);
        if (!is_array($answer)) {
            board::error(lang::get_phrase('primepayments_invalid_response'));
        }

        if (($answer['status'] ?? null) !== 'OK' || empty($answer['result'])) {
            board::error(sprintf(lang::get_phrase('primepayments_api_error'), ($answer['result'] ?? 'Unknown error')));
        }

        board::response('success', ['url' => $answer['result']]);
    }

    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            echo 'disabled';
            return;
        }

        if (!$this->isConfigured()) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'Plugin not configured']);
            echo 'disabled';
            return;
        }

        $required = ['orderID', 'payWay', 'innerID', 'sum', 'webmaster_profit', 'sign', 'currency'];
        foreach ($required as $key) {
            if (!isset($_POST[$key])) {
                $this->logWebhook('INPUT_INVALID', ['reason' => 'Missing required field', 'field' => $key]);
                echo 'wrong input';
                return;
            }
        }

        $hash = md5(
            (string)$this->getPluginSetting('secret_2', '')
            . (string)$_POST['orderID']
            . (string)$_POST['payWay']
            . (string)$_POST['innerID']
            . (string)$_POST['sum']
            . (string)$_POST['webmaster_profit']
        );

        if (!hash_equals($hash, (string)$_POST['sign'])) {
            $this->logWebhook('SIGN_INVALID', ['order_id' => (string)$_POST['orderID']]);
            echo 'wrong sign';
            return;
        }

        $userId = (int)$_POST['innerID'];
        if ($userId <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['user_id' => $userId, 'order_id' => (string)$_POST['orderID']]);
            echo 'wrong user';
            return;
        }

        try {
            donate::control_uuid((string)$_POST['orderID'], $this->getNameClass());
            $amount = donate::currency((float)$_POST['sum'], (string)$_POST['currency']);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'order_id' => (string)$_POST['orderID'],
            ], $userId);
            echo 'wrong data';
            return;
        }

        try {
            telegram::telegramNotice(
                user::getUserId($userId),
                (float)$_POST['sum'],
                (string)$_POST['currency'],
                $amount,
                $this->getNameClass()
            );
        } catch (\Throwable $e) {
        }

        try {
            user::getUserId($userId)
                ->donateAdd($amount)
                ->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass(), input: json_encode($_POST, JSON_UNESCAPED_UNICODE));
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'order_id' => (string)$_POST['orderID'],
                'amount' => $amount,
            ], $userId);
            echo 'failed';
            return;
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'order_id' => (string)$_POST['orderID'],
            'amount' => $amount,
            'currency' => (string)$_POST['currency'],
        ], $userId);

        echo 'YES';
    }

    private function request(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'body' => $body,
            'error' => $error,
        ];
    }
}
