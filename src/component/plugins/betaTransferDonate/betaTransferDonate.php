<?php

namespace betaTransferDonate;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\user\userModel;

use ReflectionClass;

class betaTransferDonate extends BasePaymentPlugin
{
    const BASE_URL_V1 = 'https://merchant.betatransfer.io/';
    const BASE_URL_V2 = 'https://api.betatransfer.io/';

    public function __construct()
    {
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
        ]);
    }

    protected function isConfigured(): bool
    {
        $settings = plugin::getSetting($this->getNameClass());
        return !empty($settings['public_api_key']) && !empty($settings['secret_api_key']);
    }

    /**
     * Админ панель настроек
     */
    public function adminSettings(): void
    {
        validation::user_protection("admin");

        $settings = plugin::getSetting($this->getNameClass());
        $settings['supported_countries'] = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'settings' => $settings,
            'pluginDescription' => (string)($settings['PLUGIN_DESCRIPTION'] ?? $settings['description'] ?? ''),
            'pluginName' => $this->getNameClass(),
        ]);

        tpl::displayPlugin("betaTransferDonate/tpl/admin.html");
    }

    /**
     * Сохранение настроек админа
     */
    public function saveSettings(): void
    {
        validation::user_protection("admin");

        $publicKey = $_POST['public_api_key'] ?? '';
        $secretKey = $_POST['secret_api_key'] ?? '';
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? $_POST['description'] ?? ''));
        $description = $pluginDescription;
        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);

        // Payment methods configuration — accept dynamic payment[] from admin UI
        $paymentMethods = [];
        if (isset($_POST['payment']) && is_array($_POST['payment'])) {
            $generatedIndex = 0;
            foreach ($_POST['payment'] as $entry) {
                $paymentSystem = trim((string)($entry['paymentSystem'] ?? ''));
                $name = trim((string)($entry['name'] ?? ''));

                if (!empty($entry['orig_key'])) {
                    $key = trim((string)$entry['orig_key']);
                    $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
                } else {
                    if ($paymentSystem !== '') {
                        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $paymentSystem);
                        $key = strtolower($key);
                    } else {
                        $key = 'method_' . $generatedIndex;
                    }
                }
                $generatedIndex++;

                $paymentMethods[$key] = [
                    'name' => $name ?: ($paymentSystem ?: $key),
                    'paymentSystem' => $paymentSystem ?: $key,
                    'currency' => $entry['currency'] ?? 'UAH',
                    'min' => (int)($entry['min'] ?? 0),
                    'max' => (int)($entry['max'] ?? 0),
                    'icon' => $entry['icon'] ?? 'bi-credit-card',
                    'csv' => trim((string)($entry['csv'] ?? '')),
                ];
            }
        }

        $settingsData = [
            'public_api_key' => $publicKey,
            'secret_api_key' => $secretKey,
            'PLUGIN_DESCRIPTION' => $pluginDescription,
            'description' => $description,
            'supported_countries' => $supportedCountries,
            'payment_methods' => $paymentMethods,
        ];

        // Сохраняем настройки через стандартную систему плагинов
        $serverId = 0;

        \Ofey\Logan22\model\db\sql::run(
            "DELETE FROM `settings` WHERE `key` = ? AND `serverId` = ?",
            ["__PLUGIN__{$this->getNameClass()}", $serverId]
        );

        \Ofey\Logan22\model\db\sql::run(
            "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
            [
                "__PLUGIN__{$this->getNameClass()}",
                json_encode($settingsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $serverId,
                \Ofey\Logan22\component\time\time::mysql()
            ]
        );

        board::success("Настройки успешно сохранены");
    }

    /**
     * Пользовательская страница выбора способа оплаты
     */
    public function show($count = null): void
    {
        if (!user::self()->isAuth()) {
            redirect::location("/login");
        }

        $settings = plugin::getSetting($this->getNameClass());
        $paymentMethods = $settings['payment_methods'] ?? [];

        if (empty($paymentMethods)) {
            try {
                $serverId = user::self()->getServerId() ?? 0;
                $query = \Ofey\Logan22\model\db\sql::run(
                    "SELECT `setting` FROM `settings` WHERE `key` = ? AND (`serverId` = ? OR `serverId` = 0) ORDER BY `serverId` DESC LIMIT 1",
                    ["__PLUGIN__{$this->getNameClass()}", $serverId]
                );
                if ($query && $row = $query->fetch(\PDO::FETCH_ASSOC)) {
                    $raw = json_decode($row['setting'], true);
                    if (is_array($raw) && isset($raw['payment_methods']) && is_array($raw['payment_methods'])) {
                        $paymentMethods = $raw['payment_methods'];
                    }
                }
            } catch (\Throwable $e) {
            }

            if (empty($paymentMethods)) {
                try {
                    $query2 = \Ofey\Logan22\model\db\sql::run(
                        "SELECT `setting` FROM `settings` WHERE `key` = ? ORDER BY `dateUpdate` DESC LIMIT 1",
                        ["__PLUGIN__{$this->getNameClass()}"]
                    );
                    if ($query2 && $row2 = $query2->fetch(\PDO::FETCH_ASSOC)) {
                        $raw2 = json_decode($row2['setting'], true);
                        if (is_array($raw2) && isset($raw2['payment_methods']) && is_array($raw2['payment_methods'])) {
                            $paymentMethods = $raw2['payment_methods'];
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        // Расчет стоимости коинов
        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();
        $sphereCoinCost = $donate->getSphereCoinCost();

        if ($sphereCoinCost >= 1) {
            $USD_val = $donate->getRatioUSD() / $sphereCoinCost;
            $EUR_val = $donate->getRatioEUR() / $sphereCoinCost;
            $RUB_val = $donate->getRatioRUB() / $sphereCoinCost;
            $UAH_val = $donate->getRatioUAH() / $sphereCoinCost;
        } else {
            $USD_val = $donate->getRatioUSD() * $sphereCoinCost;
            $EUR_val = $donate->getRatioEUR() * $sphereCoinCost;
            $RUB_val = $donate->getRatioRUB() * $sphereCoinCost;
            $UAH_val = $donate->getRatioUAH() * $sphereCoinCost;
        }

        $userCountry = strtoupper(user::self()->getCountry() ?? '');
        if ($userCountry == 'UA') {
            $mainCurrency = 'UAH';
            $otherCurrencies = ['USD' => $USD_val, 'EUR' => $EUR_val, 'RUB' => $RUB_val];
        } elseif ($userCountry == 'RU') {
            $mainCurrency = 'RUB';
            $otherCurrencies = ['USD' => $USD_val, 'EUR' => $EUR_val, 'UAH' => $UAH_val];
        } else {
            $mainCurrency = 'USD';
            $otherCurrencies = ['EUR' => $EUR_val, 'RUB' => $RUB_val, 'UAH' => $UAH_val];
        }

        tpl::addVar([
            'paymentMethods' => $paymentMethods,
            'settings' => $settings,
            'count' => $count,
            'USD_val' => $USD_val,
            'EUR_val' => $EUR_val,
            'RUB_val' => $RUB_val,
            'UAH_val' => $UAH_val,
            'mainCurrency' => $mainCurrency,
            'otherCurrencies' => $otherCurrencies,
        ]);

        tpl::addVar([
            'rawPluginSettings' => $settings,
            'resolvedPaymentMethods' => $paymentMethods,
        ]);

        tpl::displayPlugin("betaTransferDonate/tpl/donate.html");
    }

    /**
     * Создание платежа
     */
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
            board::notice(false, lang::get_phrase(234));
        }

        $settings = plugin::getSetting($this->getNameClass());

        if (empty($settings['public_api_key']) || empty($settings['secret_api_key'])) {
            board::error("BetaTransfer API keys are not configured");
        }

        // Получаем введенную пользователем сумму
        $userAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $paymentMethod = $_POST['payment_method'] ?? '';

        if (!$userAmount || $userAmount <= 0) {
            board::error("Введите корректную сумму");
        }

        $paymentMethods = $settings['payment_methods'] ?? [];

        if (!isset($paymentMethods[$paymentMethod])) {
            board::error("Выбранный способ оплаты не найден. Метод: '$paymentMethod'. Доступные методы: " . implode(', ', array_keys($paymentMethods)));
        }

        $method = $paymentMethods[$paymentMethod];
        $currency = trim($method['currency']);

        $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
        $donate = $server->donate();
        $amount = donate::sphereCoinSmartCalc($userAmount, $donate->getRatio($currency), $donate->getSphereCoinCost());

        // Проверка лимитов на итоговую сумму
        if ($amount < $method['min']) {
            board::error("Минимальная сумма пополнения: {$method['min']} {$currency}");
        }

        if ($amount > $method['max']) {
            board::error("Максимальная сумма пополнения: {$method['max']} {$currency}");
        }

        $orderId = user::self()->getId() . '_' . time() . '_' . mt_rand(1000, 9999);

        $options = [
            'amount' => round($amount, 2),
            'currency' => $currency,
            'orderId' => $orderId,
            'paymentSystem' => $method['paymentSystem'],
            'fullCallback' => 0,
        ];

        // Генерируем подпись
        $options['sign'] = $this->generateSignV1($options, $settings['secret_api_key']);

        $queryData = [
            'token' => $settings['public_api_key'],
        ];

        $response = $this->request(
            rtrim(self::BASE_URL_V1, '/') . '/api/payment?' . http_build_query($queryData),
            $options
        );

        if ($response['code'] == 200 && isset($response['body'])) {
            $body = $response['body'];

            if (is_string($body)) {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $body = $decoded;
                }
            }

            if (is_array($body) && isset($body['status'])) {
                if ($body['status'] === 'success' && !empty($body['url'])) {
                    // Успешное создание платежа
                    board::response('success', [
                        'success' => true,
                        'url' => $body['url'],
                        'message' => 'Перенаправление на страницу оплаты...'
                    ]);
                } else {
                    // Ошибка от API
                    $errorMessage = $this->parseApiError($body);
                    board::error($errorMessage);
                }
            } else {
                board::error("Некорректный ответ от платежной системы");
            }
        } else {
            // Ошибка соединения или HTTP код не 200
            $errorMsg = "Ошибка соединения с платежной системой";

            if (!empty($response['error'])) {
                $errorMsg .= ": " . $response['error'];
            } elseif (!empty($response['body'])) {
                if (is_string($response['body'])) {
                    $errorMsg .= ": " . $response['body'];
                } elseif (is_array($response['body'])) {
                    $errorMsg .= ": " . $this->parseApiError($response['body']);
                }
            }

            board::error($errorMsg);
        }
    }

    /**
     * Парсинг ошибок от API
     */
    private function parseApiError(array $body): string
    {
        $errorMessage = 'Произошла ошибка при создании платежа';

        if (isset($body['errors']) && is_array($body['errors'])) {
            $parts = [];
            foreach ($body['errors'] as $field => $errs) {
                if (is_array($errs)) {
                    $parts[] = ucfirst($field) . ': ' . implode(', ', $errs);
                } else {
                    $parts[] = ucfirst($field) . ': ' . (string)$errs;
                }
            }
            $errorMessage = implode(' | ', $parts);
        } elseif (isset($body['error'])) {
            $errorMessage = (string)$body['error'];
        } elseif (isset($body['message'])) {
            $errorMessage = (string)$body['message'];
        }

        return $errorMessage;
    }

    /**
     * Умный расчет суммы с учетом курса и стоимости sphere coin
     * Аналогично методу из pay_abstract
     */
    private function sphereCoinSmartCalc(float $userInput, float $ratio, float $sphereCoinCost): float
    {
        if ($ratio <= 0 || $sphereCoinCost <= 0) {
            return $userInput;
        }

        // Расчет: (пользовательский ввод * курс) / стоимость sphere coin
        return ($userInput * $ratio) / $sphereCoinCost;
    }

    /**
     * Webhook для получения уведомлений об оплате
     */
    public function webhook(): void
    {
        if (!$this->isPluginActive()) {
            $this->logWebhook('DISABLED', ['reason' => 'Plugin disabled']);
            die('Plugin disabled');
        }

        if (!$this->isConfigured()) {
            $this->logWebhook('NOT_CONFIGURED', ['reason' => 'API keys are not configured']);
            die('FAIL: API keys not configured');
        }

        $settings = plugin::getSetting($this->getNameClass());

        $sign = $_POST['sign'] ?? null;
        $amount = (float)($_POST['amount'] ?? 0);
        $orderId = $_POST['orderId'] ?? null;
        $currency = $_POST['currency'] ?? "UAH";

        if (!$sign || !$amount || !$orderId) {
            $this->logWebhook('INPUT_INVALID', ['reason' => 'Missing required callback fields']);
            die('FAIL');
        }

        if (!$this->callbackSignIsValid($sign, $amount, $orderId, $settings['secret_api_key'])) {
            $this->logWebhook('SIGN_INVALID', ['order_id' => $orderId]);
            die('FAIL');
        }

        $data = explode("_", (string)$orderId);
        $userId = (int)($data[0] ?? 0);

        if ($userId <= 0) {
            $this->logWebhook('INVALID_USER_ID', ['order_id' => $orderId, 'user_id' => $userId]);
            die('FAIL');
        }

        try {
            donate::control_uuid((string)$sign, $this->getNameClass());
        } catch (\Throwable $e) {
            $this->logWebhook('UUID_CONTROL_FAILED', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
            ], $userId);
            die('FAIL');
        }

        try {
            $sphereCoins = donate::currency($amount, (string)$currency);
        } catch (\Throwable $e) {
            $this->logWebhook('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => (string)$currency,
            ], $userId);
            die('FAIL');
        }

        try {
            $userModel = user::getUserId($userId);
            $userModel->donateAdd($sphereCoins)->AddHistoryDonate(
                amount: $sphereCoins,
                pay_system: $this->getNameClass()
            );

            donate::addUserBonus($userId, $sphereCoins);
            self::telegramNotice($userModel, (float)$amount, (string)$currency, $sphereCoins, get_called_class());
        } catch (\Throwable $e) {
            $this->logWebhook('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'amount' => $sphereCoins,
            ], $userId);
            die('FAIL');
        }

        $this->logWebhook('PAYMENT_SUCCESS', [
            'order_id' => $orderId,
            'amount' => $sphereCoins,
            'currency' => (string)$currency,
        ], $userId);

        die('OK');

    }

    /**
     * @param userModel|null $user - объект пользователя
     * @param $invoice_amount - сумма платежа
     * @param $currency - валюта
     * @param $amount - кол-во внутренних валют
     * @param $paySystem - название платежной системы
     * @return void
     */
    public static function telegramNotice(null|userModel $user, $invoice_amount, $currency, $amount, $paySystem): void
    {
        if (!config::load()->notice()->isTelegramEnable()) {
            return;
        }
        if (config::load()->notice()->isDonationCrediting()) {
            if ($user == null) {
                $user = user::self();
                $user->setEmail("NoEmail");
                $user->setName("NoName");
            }

            $template = lang::get_other_phrase(config::load()->notice()->getNoticeLang(), 'notice_user_donate');
            $msg = strtr($template, [
                '{name}' => $user->getName(),
                '{email}' => $user->getEmail(),
                '{invoice_amount}' => $invoice_amount,
                '{currency}' => $currency,
                '{amount}' => $amount,
                '{paySystem}' => $paySystem,
            ]);
            telegram::sendTelegramMessage($msg, config::load()->notice()->getDonationCreditingThreadId());
        }
    }

    /**
     * HTTP запрос к API
     */
    private function request(
        string $url,
        array $data = [],
        array $headers = [],
        string $method = 'POST'
    ): array {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $CURLOPT_POST = false;

        if (strtoupper($method) == 'POST') {
            $CURLOPT_POST = true;
        }

        if ($data) {
            $CURLOPT_POST = true;

            if (strtoupper($method) == 'JSON') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        if ($CURLOPT_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        curl_close($ch);

        if ($httpCode == 200) {
            $response = json_decode($response, true);
        }

        return [
            'code' => $httpCode,
            'error' => $curlError,
            'errno' => $curlErrno,
            'body' => $response,
        ];
    }

    /**
     * Генерация подписи для запроса
     */
    private function generateSignV1(array $options, string $secretKey): string
    {
        return md5(implode("", $options) . $secretKey);
    }

    /**
     * Проверка подписи callback
     */
    private function callbackSignIsValid($sign, $amount, $orderId, $secretKey): bool
    {
        return $sign == md5($amount . $orderId . $secretKey);
    }
}
