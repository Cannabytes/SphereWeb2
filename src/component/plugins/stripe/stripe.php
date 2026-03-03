<?php

namespace stripe;

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
use Stripe\Checkout\Session;

class stripe extends BasePaymentPlugin
{
    private const DEFAULT_CURRENCY = 'USD';

    private const DEFAULT_PAYMENT_METHODS = [
        'card',
        'klarna',
        'billie',
        'ideal',
        'amazon_pay',
        'link',
        'mobilepay',
        'multibanco',
        'bancontact',
        'blik',
        'eps',
        'sepa_debit',
        'samsung_pay',
        'naver_pay',
        'kakao_pay',
        'payco',
        'kr_card',
        'wechat_pay',
    ];

    private function normalizePaymentMethods(mixed $methods): array
    {
        $raw = [];
        if (is_array($methods)) {
            $raw = $methods;
        } elseif (is_string($methods)) {
            $raw = preg_split('/[,;\s]+/', $methods) ?: [];
        }

        $normalized = [];
        foreach ($raw as $method) {
            if (!is_string($method)) {
                continue;
            }
            $value = strtolower(trim($method));
            if ($value === '' || !preg_match('/^[a-z0-9_]+$/', $value)) {
                continue;
            }
            $normalized[] = $value;
        }

        $normalized = array_values(array_unique($normalized));
        return empty($normalized) ? self::DEFAULT_PAYMENT_METHODS : $normalized;
    }

    private function getPaymentMethods(): array
    {
        return $this->normalizePaymentMethods($this->getPluginSetting('payment_methods', self::DEFAULT_PAYMENT_METHODS));
    }

    private function getCurrency(): string
    {
        return $this->sanitizeCurrency((string)$this->getPluginSetting('currency', self::DEFAULT_CURRENCY), self::DEFAULT_CURRENCY);
    }

    protected function isConfigured(): bool
    {
        return trim((string)$this->getPluginSetting('secret_key', '')) !== ''
            && trim((string)$this->getPluginSetting('webhook_secret_key', '')) !== '';
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'title' => 'Stripe',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => (string)$this->getPluginSetting('PLUGIN_DESCRIPTION', ''),
            'secretKey' => (string)$this->getPluginSetting('secret_key', ''),
            'publishableKey' => (string)$this->getPluginSetting('publishable_key', ''),
            'webhookSecretKey' => (string)$this->getPluginSetting('webhook_secret_key', ''),
            'currency' => $this->getCurrency(),
            'paymentMethods' => implode(', ', $this->getPaymentMethods()),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/stripe/webhook',
        ]);

        tpl::displayPlugin('/stripe/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $secretKey = trim((string)($_POST['secret_key'] ?? ''));
        $publishableKey = trim((string)($_POST['publishable_key'] ?? ''));
        $webhookSecretKey = trim((string)($_POST['webhook_secret_key'] ?? ''));
        $currency = $this->sanitizeCurrency((string)($_POST['currency'] ?? self::DEFAULT_CURRENCY));
        $paymentMethods = $this->normalizePaymentMethods($_POST['payment_methods'] ?? self::DEFAULT_PAYMENT_METHODS);
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? ['world']);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        if ($secretKey === '' || $webhookSecretKey === '') {
            board::error('Заполните secret_key и webhook_secret_key');
        }

        $this->setPluginSetting('secret_key', $secretKey);
        $this->setPluginSetting('publishable_key', $publishableKey);
        $this->setPluginSetting('webhook_secret_key', $webhookSecretKey);
        $this->setPluginSetting('currency', $currency);
        $this->setPluginSetting('payment_methods', $paymentMethods);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);

        board::success('Настройки Stripe сохранены');
    }

    public function payment(?int $count = null): void
    {
        if (!$this->isPluginActive()) {
            if ($this->isAjax()) {
                board::error('Плагин Stripe выключен');
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
            board::error('Stripe не настроен. Обратитесь к администратору.');
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
            'title'        => 'Stripe',
            'currency'     => $this->getCurrency(),
            'minAmount'    => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'    => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'      => $USD_val,
            'EUR_val'      => $EUR_val,
            'RUB_val'      => $RUB_val,
            'UAH_val'      => $UAH_val,
            'mainCurrency' => $mainCurrency,
        ]);

        tpl::displayPlugin('/stripe/tpl/payment.html');
    }

    public function createPayment(): void
    {
        if (!$this->isPluginActive()) {
            board::error('Плагин Stripe выключен');
        }

        if (!user::self()->isAuth()) {
            board::error(lang::get_phrase(234));
        }

        if (!$this->isConfigured()) {
            board::error('Stripe не настроен');
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

        if ($amount < 0.5) {
            board::error('Минимальная сумма для Stripe: 0.50 ' . $currency);
        }

        $sumCents = (int)round($amount * 100);
        $methods = $this->getPaymentMethods();

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => $methods,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => $sumCents,
                        'product_data' => [
                            'name' => 'Donate to project',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'user_id' => (string)user::self()->getId(),
                'server_id' => (string)user::self()->getServerId(),
                'input_amount' => (string)$count,
                'plugin' => $this->getNameClass(),
            ],
            'success_url' => $this->getBaseUrl() . '/balance',
            'cancel_url' => $this->getBaseUrl() . '/balance',
        ];

        if (in_array('wechat_pay', $methods, true)) {
            $payload['payment_method_options'] = [
                'wechat_pay' => [
                    'client' => 'web',
                ],
            ];
        }

        try {
            \Stripe\Stripe::setApiKey((string)$this->getPluginSetting('secret_key', ''));
            $session = Session::create($payload);
            $url = (string)($session->url ?? '');

            if ($url === '') {
                board::error('Stripe не вернул URL для оплаты');
            }

            board::response('success', ['url' => $url]);
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

        $signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        if ($signature === '') {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Missing signature';
            return;
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $rawPayload,
                $signature,
                (string)$this->getPluginSetting('webhook_secret_key', '')
            );
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid signature';
            return;
        } catch (\UnexpectedValueException $e) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid payload';
            return;
        }

        if ((string)$event->type !== 'checkout.session.completed') {
            echo 'ignored';
            return;
        }

        $session = $event->data->object ?? null;
        if ($session === null) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid session';
            return;
        }

        if ((string)($session->payment_status ?? '') !== 'paid') {
            echo 'not_paid';
            return;
        }

        $userId = (int)($session->metadata->user_id ?? 0);
        if ($userId <= 0) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid user_id';
            return;
        }

        $amountInput = ((float)($session->amount_total ?? 0)) / 100;
        $currency = strtoupper((string)($session->currency ?? $this->getCurrency()));
        if ($amountInput <= 0) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            echo 'Invalid amount';
            return;
        }

        donate::control_uuid((string)($session->id ?? null), $this->getNameClass(), $rawPayload);

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
                ->AddHistoryDonate(amount: $amount, pay_system: $this->getNameClass(), input: $rawPayload);
            donate::addUserBonus($userId, $amount);
        } catch (\Throwable $e) {
            header('HTTP/1.1 500 Internal Server Error', true, 500);
            echo 'Failed to add funds';
            return;
        }

        echo 'OK';
    }
}
