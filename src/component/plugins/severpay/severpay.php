<?php

namespace severpay;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\donate\payMessage;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\plugin\BasePaymentPlugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use PDO;
use RuntimeException;
use Throwable;

class severpay extends BasePaymentPlugin
{
    private const API_CREATE_URL = 'https://severpay.io/api/merchant/payin/create';

    private const ALLOWED_CURRENCIES = ['RUB', 'EUR', 'BYN'];

    public function __construct()
    {
        if (!is_array($this->getPluginSetting('merchants'))) {
            $this->setPluginSetting('merchants', []);
        }
    }

    protected function isConfigured(): bool
    {
        $merchants = $this->getMerchants();
        return !empty($merchants);
    }

    private function getMerchants(): array
    {
        $rows = $this->getPluginSetting('merchants', []);
        if (!is_array($rows)) {
            return [];
        }

        return $this->sanitizeMerchants($rows);
    }

    private function sanitizeMerchants(array $rows): array
    {
        $result = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $mid = trim((string)($row['mid'] ?? ''));
            $token = trim((string)($row['token'] ?? ''));
            $currency = strtoupper(trim((string)($row['currency'] ?? 'RUB')));

            if ($mid === '' || $token === '') {
                continue;
            }

            if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
                $currency = 'RUB';
            }

            $result[] = [
                'id' => $index,
                'mid' => $mid,
                'token' => $token,
                'currency' => $currency,
            ];
        }

        return array_values($result);
    }

    public function admin(): void
    {
        validation::user_protection('admin');

        $settings = plugin::getSetting($this->getNameClass());
        $selectedCountries = $this->sanitizeSupportedCountries($settings['supported_countries'] ?? ['world']);

        tpl::addVar([
            'title' => 'SeverPay',
            'pluginName' => $this->getNameClass(),
            'pluginDescription' => $this->resolvePluginDescription('severpay_gateway_description'),
            'merchants' => $this->getMerchants(),
            'selectedCountries' => $selectedCountries,
            'webhookUrl' => $this->getBaseUrl() . '/severpay/webhook',
        ]);

        tpl::displayPlugin('/severpay/tpl/admin.html');
    }

    public function saveSettings(): void
    {
        validation::user_protection('admin');

        $supportedCountries = $this->sanitizeSupportedCountries($_POST['supported_countries'] ?? []);
        $pluginDescription = trim((string)($_POST['PLUGIN_DESCRIPTION'] ?? ''));

        $merchants = [];
        if (isset($_POST['merchants']) && is_array($_POST['merchants'])) {
            foreach ($_POST['merchants'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $merchants[] = [
                    'mid' => trim((string)($row['mid'] ?? '')),
                    'token' => trim((string)($row['token'] ?? '')),
                    'currency' => strtoupper(trim((string)($row['currency'] ?? 'RUB'))),
                ];
            }
        }

        $merchants = $this->sanitizeMerchants($merchants);
        $this->setPluginSetting('merchants', $merchants);
        $this->setPluginSetting('supported_countries', $supportedCountries);
        $this->setPluginSetting('PLUGIN_DESCRIPTION', $pluginDescription);
        $this->savePluginCustomNameFromPost();
        $this->savePluginHideNameFromPost();

        board::success(lang::get_phrase('severpay_settings_saved'));
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
        $merchants = $this->getMerchants();

        if (empty($merchants)) {
            if ($this->isAjax()) {
                board::error(lang::get_phrase('severpay_no_merchants'));
            } else {
                echo 'Не настроено ни одного мерчанта. Обратитесь к администратору.';
                exit;
            }
        }

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        $sphereCoinCost = $donateConfig->getSphereCoinCost();
        $rateCalc = static fn($r) => round($sphereCoinCost >= 1 ? $r / $sphereCoinCost : $r * $sphereCoinCost, 4);
        $USD_val = $rateCalc($donateConfig->getRatioUSD());
        $EUR_val = $rateCalc($donateConfig->getRatioEUR());
        $RUB_val = $rateCalc($donateConfig->getRatioRUB());
        $UAH_val = $rateCalc($donateConfig->getRatioUAH());
        $BYN_val = $rateCalc($donateConfig->getRatioBYN());
        $userCountry = strtoupper(user::self()->getCountry() ?? '');
        $mainCurrency = match(true) {
            $userCountry === 'UA' => 'UAH',
            $userCountry === 'RU' => 'RUB',
            $userCountry === 'BY' => 'BYN',
            default               => 'USD',
        };

        tpl::addVar([
            'title'        => 'SeverPay',
            'merchants'    => $merchants,
            'minAmount'    => $donateConfig->getMinSummaPaySphereCoin(),
            'maxAmount'    => $donateConfig->getMaxSummaPaySphereCoin(),
            'defaultAmount' => is_null($count) ? $donateConfig->getDefaultSummaPaySphereCoin() : (int)$count,
            'USD_val'      => $USD_val,
            'EUR_val'      => $EUR_val,
            'RUB_val'      => $RUB_val,
            'UAH_val'      => $UAH_val,
            'BYN_val'      => $BYN_val,
            'mainCurrency' => $mainCurrency,
        ]);

        $this->addPaymentDisplayVars('SeverPay');
        tpl::displayPlugin('/severpay/tpl/payment.html');
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

        $merchantIndex = filter_input(INPUT_POST, 'merchant_index', FILTER_VALIDATE_INT);
        $userInputAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        if ($merchantIndex === false || $merchantIndex === null) {
            board::error(lang::get_phrase('severpay_no_merchant_selected'));
        }

        if (!$userInputAmount || $userInputAmount <= 0) {
            board::error(lang::get_phrase('severpay_enter_correct_amount'));
        }

        $merchants = $this->getMerchants();
        if (!isset($merchants[$merchantIndex])) {
            board::error(lang::get_phrase('severpay_merchant_not_found'));
        }

        $merchant = $merchants[$merchantIndex];
        $currency = $merchant['currency'];

        $donateConfig = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($userInputAmount < $donateConfig->getMinSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('severpay_min_amount'), $donateConfig->getMinSummaPaySphereCoin()));
        }

        if ($userInputAmount > $donateConfig->getMaxSummaPaySphereCoin()) {
            board::error(sprintf(lang::get_phrase('severpay_max_amount'), $donateConfig->getMaxSummaPaySphereCoin()));
        }

        $amount = donate::sphereCoinSmartCalc(
            $userInputAmount,
            $donateConfig->getRatio($currency),
            $donateConfig->getSphereCoinCost()
        );

        $orderId = user::self()->getId() . '_' . time() . '_' . bin2hex(random_bytes(16));
        $salt = bin2hex(random_bytes(16));

        $payload = [
            'mid' => (int)$merchant['mid'],
            'amount' => (float)$amount,
            'currency' => $currency,
            'order_id' => $orderId,
            'client_email' => user::self()->getEmail(),
            'client_id' => (string)user::self()->getId(),
            'salt' => $salt,
        ];

        ksort($payload);
        $payload['sign'] = hash_hmac('sha256', json_encode($payload), $merchant['token']);

        $response = $this->request(self::API_CREATE_URL, $payload);

        if (($response['httpCode'] ?? 0) !== 200) {
            board::error(sprintf(lang::get_phrase('severpay_api_error'), ($response['httpCode'] ?? 0)));
        }

		$result = json_decode((string)($response['body'] ?? ''), true);

		if (!is_array($result)) {
			board::error(lang::get_phrase('severpay_invalid_response'));
			return;
		}

		if (($result['status'] ?? null) !== true) {
			$errorMsg = isset($result['msg']) && is_string($result['msg'])
				? $result['msg']
				: 'Unknown error';
			board::error($errorMsg);
		}

		$url = $result['data']['url'] ?? null;

		if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
			board::error(lang::get_phrase('severpay_no_payment_link'));
			return;
		}

		board::response('success', ['url' => $url]);
    }

    public function webhook(array $additionalMerchants = []): void
    {
        header('Content-Type: application/json');

        $legacyMerchants = $this->sanitizeMerchants($additionalMerchants);
        $pluginActive = $this->isPluginActive();
        if (!$pluginActive && $legacyMerchants === []) {
            $this->logWebhookSafely('DISABLED', ['reason' => 'Plugin disabled']);
            $this->respondWebhook(false, 'Plugin disabled', 503);
            return;
        }

        $merchants = $pluginActive
            ? array_merge($legacyMerchants, $this->getMerchants())
            : $legacyMerchants;
        if (empty($merchants)) {
            $this->logWebhookSafely('NOT_CONFIGURED', ['reason' => 'No merchant configured']);
            $this->respondWebhook(false, 'No merchant configured', 503);
            return;
        }

        $inputJSON = file_get_contents('php://input') ?: '';
        $input = json_decode($inputJSON, true);

        if (!is_array($input) || !isset($input['sign']) || !is_string($input['sign'])) {
            $this->logWebhookSafely('INPUT_INVALID', ['reason' => 'Invalid JSON or missing sign']);
            $this->respondWebhook(false, 'Invalid input', 400);
            return;
        }

        $inputSign = $input['sign'];
        unset($input['sign']);

        $verifiedMerchant = $this->verifyWebhookMerchant($input, $inputSign, $merchants);

        if ($verifiedMerchant === null) {
            $this->logWebhookSafely('SIGN_INVALID', ['reason' => 'No merchant matched signature']);
            $this->respondWebhook(false, 'Wrong sign', 400);
            return;
        }

        if (($input['type'] ?? '') === 'test') {
            $this->logWebhookSafely('TEST_SUCCESS');
            $this->respondWebhook(true);
            return;
        }

        if (($input['type'] ?? '') !== 'payin') {
            $this->logWebhookSafely('INPUT_INVALID', ['reason' => 'Invalid type']);
            $this->respondWebhook(false, 'Invalid type', 400);
            return;
        }

        $data = $input['data'] ?? null;
        if (!is_array($data)) {
            $this->logWebhookSafely('INPUT_INVALID', ['reason' => 'Missing payment data']);
            $this->respondWebhook(false, 'Invalid payment data', 400);
            return;
        }

        $status = $data['status'] ?? null;
        if (in_array($status, ['new', 'process', 'decline', 'fail'], true)) {
            $this->logWebhookSafely('PAYMENT_STATUS', [
                'payment_id' => $data['id'] ?? null,
                'status' => $status,
            ]);
            $this->respondWebhook(true);
            return;
        }
        if ($status !== 'success') {
            $this->logWebhookSafely('INPUT_INVALID', ['reason' => 'Unknown payment status']);
            $this->respondWebhook(false, 'Invalid payment status', 400);
            return;
        }

        $paymentId = $data['id'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $amountInput = $data['amount'] ?? null;
        $currency = $data['currency'] ?? null;
        if ((!is_int($paymentId) && (!is_string($paymentId) || !ctype_digit($paymentId)))
            || (int)$paymentId <= 0
            || !is_string($orderId)
            || !preg_match('/^([1-9][0-9]*)_[0-9]{10}(?:_[a-f0-9]{4,32})?$/D', $orderId, $orderParts)
            || (!is_int($amountInput) && !is_float($amountInput))
            || !is_finite((float)$amountInput)
            || $amountInput <= 0
            || !is_string($currency)
            || strtoupper($currency) !== $verifiedMerchant['currency']) {
            $this->logWebhookSafely('INPUT_INVALID', ['reason' => 'Invalid payment fields']);
            $this->respondWebhook(false, 'Invalid payment data', 400);
            return;
        }

        $userId = (int)$orderParts[1];
        $currency = strtoupper($currency);
        $uuid = (string)$paymentId;

        try {
            $amount = donate::currency((float)$amountInput, $currency);
            if (!is_finite((float)$amount) || $amount <= 0) {
                throw new RuntimeException('Invalid converted amount');
            }
        } catch (Throwable $e) {
            $this->logWebhookSafely('CURRENCY_ERROR', [
                'error' => $e->getMessage(),
                'amount' => $amountInput,
                'currency' => $currency,
            ], $userId);
            $this->respondWebhook(false, 'Currency conversion failed', 503);
            return;
        }

        try {
            $creditResult = $this->creditWebhookPayment($uuid, $orderId, $inputSign, $userId, $amount, $data);
        } catch (Throwable $e) {
            error_log('SeverPay payment ' . $uuid . ' could not be credited: ' . $e->getMessage());
            $this->logWebhookSafely('PROCESS_ERROR', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'amount' => $amount,
            ], $userId);
            $this->respondWebhook(false, 'Failed to add funds', 503);
            return;
        }

        if ($creditResult !== 'credited') {
            $event = $creditResult === 'order_duplicate' ? 'ORDER_DUPLICATE' : 'PAYMENT_DUPLICATE';
            $details = ['payment_id' => $uuid, 'order_id' => $orderId];
            $this->respondWebhook(true);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            if ($creditResult === 'order_duplicate') {
                error_log('SeverPay duplicate order needs review: ' . json_encode($details));
            }
            $this->logWebhookSafely($event, $details, $userId);
            return;
        }

        $this->respondWebhook(true);
        // The credit is committed; reporting and bonuses run after the acknowledgement.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $this->logWebhookSafely('PAYMENT_SUCCESS', [
            'uuid' => $uuid,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
        ], $userId);
        try {
            \Ofey\Logan22\component\sphere\server::send(type::DONATE_STATISTIC, [
                'paySystem' => $this->getNameClass(),
                'request' => $inputJSON,
            ]);
            user::getUserId($userId)->addLog(logTypes::LOG_DONATE_SUCCESS, 'LOG_DONATE_SUCCESS', [$amount, $this->getNameClass()]);
        } catch (Throwable $e) {
            $this->logWebhookSafely('STATISTIC_ERROR', ['error' => $e->getMessage()], $userId);
        }
        try {
            telegram::telegramNotice(user::getUserId($userId), $amountInput, $currency, $amount, $this->getNameClass());
        } catch (Throwable $e) {
            $this->logWebhookSafely('NOTICE_ERROR', ['error' => $e->getMessage()], $userId);
        }
        try {
            donate::addUserBonus($userId, $amount);
        } catch (Throwable $e) {
            $this->logWebhookSafely('BONUS_ERROR', ['error' => $e->getMessage()], $userId);
        }
    }

    private function verifyWebhookMerchant(array $input, string $inputSign, array $merchants): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/iD', $inputSign)) {
            return null;
        }
        $payload = json_encode($input);
        if ($payload === false) {
            return null;
        }
        $paymentCurrency = ($input['type'] ?? null) === 'payin' && is_array($input['data'] ?? null)
            ? strtoupper((string)($input['data']['currency'] ?? ''))
            : null;
        $matchedMerchant = null;
        foreach ($merchants as $merchant) {
            $sign = hash_hmac('sha256', $payload, (string)$merchant['token']);
            if (hash_equals($sign, $inputSign)) {
                if ($paymentCurrency === null || $paymentCurrency === $merchant['currency']) {
                    return $merchant;
                }
                $matchedMerchant ??= $merchant;
            }
        }
        return $matchedMerchant;
    }

    /** Returns credited, payment_duplicate, or order_duplicate. */
    private function creditWebhookPayment(string $uuid, string $orderId, string $inputSign, int $userId, float|int $amount, array $data): string
    {
        $db = sql::instance();
        if (!$db instanceof PDO) {
            throw new RuntimeException('Database connection unavailable');
        }

        // donate_uuid has no unique key: serialize deliveries of the same order.
        $lockName = 'severpay:' . sha1($orderId);
        $lock = $db->prepare('SELECT GET_LOCK(?, 10)');
        $lock->execute([$lockName]);
        if ((int)$lock->fetchColumn() !== 1) {
            throw new RuntimeException('Payment lock unavailable');
        }

        try {
            $db->beginTransaction();
            try {
                $account = $db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
                $account->execute([$userId]);
                if ($account->fetchColumn() === false) {
                    throw new RuntimeException('Payment user not found');
                }

                $existing = $db->prepare('SELECT id FROM donate_uuid WHERE uuid = ? AND pay_system = ? LIMIT 1');
                $existing->execute([$uuid, self::class]);
                if ($existing->fetchColumn() !== false) {
                    $db->commit();
                    return 'payment_duplicate';
                }
                // The legacy handler used the callback signature as its receipt ID.
                $existing->execute([$inputSign, 'severpay']);
                if ($existing->fetchColumn() !== false) {
                    $db->commit();
                    return 'payment_duplicate';
                }
                $existing->execute(['order:' . $orderId, self::class]);
                if ($existing->fetchColumn() !== false) {
                    $db->commit();
                    return 'order_duplicate';
                }

                $balance = $db->prepare('UPDATE users SET donate_point = donate_point + ? WHERE id = ?');
                $balance->execute([round($amount, 1), $userId]);

                $history = $db->prepare('INSERT INTO donate_history_pay (user_id, point, message, pay_system, id_admin_pay, date, sphere) VALUES (?, ?, ?, ?, NULL, NOW(), 0)');
                $history->execute([$userId, $amount, payMessage::getRandomPhrase(), $this->getNameClass()]);

                $receipt = $db->prepare('INSERT INTO donate_uuid (uuid, pay_system, ip, request, date) VALUES (?, ?, ?, ?, NOW())');
                $receipt->execute([
                    $uuid,
                    self::class,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                $receipt->execute([
                    'order:' . $orderId,
                    self::class,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                $db->commit();
                return 'credited';
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
        } finally {
            try {
                $release = $db->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (Throwable $e) {
                error_log('SeverPay payment lock release failed: ' . $e->getMessage());
            }
        }
    }

    private function respondWebhook(bool $success, string $message = '', int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($success ? ['status' => true] : ['status' => false, 'msg' => $message]);
    }

    private function logWebhookSafely(string $phrase, array $context = [], int $userId = 0): void
    {
        try {
            $this->logWebhook($phrase, $context, $userId);
        } catch (Throwable $e) {
            error_log('SeverPay webhook logging failed: ' . $e->getMessage());
        }
    }

    private function request(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            board::error(sprintf(lang::get_phrase('severpay_api_error'), 'CURL: ' . $error));
        }

        return [
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }

}
