<?php

namespace betaTransferDonate;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\user\userModel;

use ReflectionClass;

class betaTransferDonate
{
    const BASE_URL_V1 = 'https://merchant.betatransfer.io/';
    const BASE_URL_V2 = 'https://api.betatransfer.io/';

    private string|null $nameClass = null;

    public function __construct()
    {
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
        ]);
    }

    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }
        return $this->nameClass;
    }

    /**
     * Админ панель настроек
     */
    public function adminSettings(): void
    {
        validation::user_protection("admin");
        
        $settings = plugin::getSetting($this->getNameClass());
        
        tpl::addVar([
            'settings' => $settings,
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
            'payment_methods' => $paymentMethods,
        ];

        // Сохраняем настройки через стандартную систему плагинов
        $serverId = user::self()->getServerId() ?? 0;
        
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
    public function show(): void
    {
        if (!user::self()->isAuth()) {
            board::notice(false, lang::get_phrase(234));
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

        tpl::addVar([
            'paymentMethods' => $paymentMethods,
            'settings' => $settings,
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
        $currency = $method['currency'];
  
        // Проверка лимитов на итоговую сумму
        if ($userAmount < $method['min']) {
            board::error("Минимальная сумма пополнения: {$method['min']} {$currency}");
        }

        if ($userAmount > $method['max']) {
            board::error("Максимальная сумма пополнения: {$method['max']} {$currency}");
        }

        $orderId = user::self()->getId() . '_' . time() . '_' . mt_rand(1000, 9999);

        $options = [
            'amount' => round($userAmount, 2),
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
        $settings = plugin::getSetting($this->getNameClass());

        if (empty($settings['public_api_key']) || empty($settings['secret_api_key'])) {
            die('FAIL: API keys not configured');
        }

        $sign = $_POST['sign'] ?? null;
        $amount = (float)($_POST['amount'] ?? 0);
        $orderId = $_POST['orderId'] ?? null;
        $currency = $_POST['currency'] ?? "UAH";

        if ($sign && $amount && $orderId && $this->callbackSignIsValid($sign, $amount, $orderId, $settings['secret_api_key'])) {
            $data = explode("_", $orderId);
            $userId = (int)$data[0];

            donate::control_uuid($sign, $this->getNameClass());
            $sphereCoins = donate::currency($amount, $currency);

            // Добавляем донат пользователю
            user::getUserId($userId)->donateAdd($sphereCoins)->AddHistoryDonate(
                amount: $sphereCoins, 
                pay_system: $this->getNameClass()
            );

            donate::addUserBonus($userId, $sphereCoins);

            die('OK');
        }

        die('FAIL');
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
        if(!config::load()->notice()->isTelegramEnable()) {
            return;
        }
        if (config::load()->notice()->isDonationCrediting()) {
            if($user == null){
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
