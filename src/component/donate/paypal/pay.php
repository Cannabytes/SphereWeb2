<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class paypal extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static bool $forAdmin = false;

    // Включена/отключена платежная система
    protected static bool $enable = true;

    protected string $currency_default = 'USD';

    public static function isEnable(): bool
    {
        return self::$enable;
    }

    // PayPal API URL для создания платежей

    public static function inputs(): array
    {
        return [
          'clientId'  => '',
          'secretKey' => '',
          'webhookId' => '',
        ];
    }

    public function create_link(): void
    {
        // Проверка аутентификации
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        // Проверка ключей
        $clientId = self::getConfigValue('clientId');
        $secretKey = self::getConfigValue('secretKey');

        if (empty($clientId) || empty($secretKey)) {
            board::error('Не установлены secretKey / clientId');
        }

        // Проверка суммы
        $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT);
        if (!$count) {
            board::notice(false, "Введите сумму цифрой");
        }

        $donate = \Ofey\Logan22\controller\config\config::load()->donate();
        if ($count < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($count > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальное пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        // Расчет суммы заказа
        $order_amount = $count * ($donate->getRatioUSD() / $donate->getSphereCoinCost());

        // URL для получения токена (замените на live URL)
        $auth_url = "https://api-m.paypal.com/v1/oauth2/token";

        // Запрос для получения токена
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Включаем проверку SSL
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$secretKey}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            die("Ошибка cURL: " . curl_error($ch));
        }

        $json = json_decode($result);

        if (isset($json->error)) {
            die("Ошибка PayPal: " . $json->error_description);
        }

        $accessToken = $json->access_token ?? null;

        if (!$accessToken) {
            die("Ошибка: токен доступа не получен.");
        }

        curl_close($ch);

        // Создание заказа (замените URL на Live)
        $url = "https://api-m.paypal.com/v2/checkout/orders";

        $orderRequest = json_encode([
          'intent' => 'CAPTURE',
          'purchase_units' => [
            [
              'amount' => [
                'value' => $order_amount,
                'currency_code' => 'USD',
              ],
              'description' => 'Payment for donation', // Добавьте описание для заказа
            ],
          ],
          'application_context' => [
            'return_url' => "https://sphereweb.net/balance",
            'cancel_url' => "https://sphereweb.net/balance",
          ],
        ]);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => $orderRequest,
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
          ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            die("Ошибка при создании заказа: " . curl_error($curl));
        }

        $responseData = json_decode($response, true);

        curl_close($curl);

        if (isset($responseData['status']) && $responseData['status'] === 'CREATED') {
            $order_id = $responseData['id'];
            $approveLink = "https://www.paypal.com/checkoutnow?token={$order_id}";  // Live URL
            echo $approveLink;
        } else {
            die("Ошибка при создании заказа: " . json_encode($responseData));
        }
    }

    function webhook(): void
    {
        // Получаем данные из тела запроса
        $requestBody = file_get_contents('php://input');
        file_put_contents(__DIR__ . '/data_request.log', print_r($requestBody, true) . PHP_EOL, FILE_APPEND);

        // Получаем заголовки
        $headers = getallheaders();
        file_put_contents(__DIR__ . '/headers.log', print_r($headers, true) . PHP_EOL, FILE_APPEND);

        // Проверка наличия необходимых заголовков
        if (!isset($headers['Paypal-Transmission-Sig']) ||
            !isset($headers['Paypal-Transmission-Time']) ||
            !isset($headers['Paypal-Transmission-Id']) ||
            !isset($headers['Paypal-Cert-Url']) ||
            !isset($headers['Paypal-Auth-Algo'])) {
            // Логируем ошибку и возвращаем 400
            http_response_code(400);
            file_put_contents(__DIR__ . '/debug_error.log', "Missing PayPal headers" . PHP_EOL, FILE_APPEND);
            return;
        }

        // Проверка подписи вебхука через API PayPal
        $validationUrl = "https://api-m.paypal.com/v1/notifications/verify-webhook-signature"; // Live URL
        $clientId = self::getConfigValue('clientId');
        $secretKey = self::getConfigValue('secretKey');
        $webhookId = self::getConfigValue('webhookId');  // ID вашего вебхука

        // Подготавливаем данные для проверки подписи
        $verificationData = [
          'auth_algo' => $headers['Paypal-Auth-Algo'],
          'cert_url' => $headers['Paypal-Cert-Url'],
          'transmission_id' => $headers['Paypal-Transmission-Id'],
          'transmission_sig' => $headers['Paypal-Transmission-Sig'],
          'transmission_time' => $headers['Paypal-Transmission-Time'],
          'webhook_id' => $webhookId,
          'webhook_event' => json_decode($requestBody, true),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $validationUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Включаем проверку SSL
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$secretKey}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verificationData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
        ]);

        $verificationResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            // Ошибка запроса верификации
            http_response_code(500);
            file_put_contents(__DIR__ . '/debug_error.log', "Curl error: " . curl_error($ch) . PHP_EOL, FILE_APPEND);
            return;
        }

        $verificationResult = json_decode($verificationResponse, true);
        curl_close($ch);

        // Проверяем результат верификации
        if ($verificationResult['verification_status'] !== 'SUCCESS') {
            http_response_code(400);
            file_put_contents(__DIR__ . '/debug_error.log', "Invalid webhook signature" . PHP_EOL, FILE_APPEND);
            return;
        }

        // Если подпись валидна, продолжаем обработку события
        $data = json_decode($requestBody, true);
        $eventType = $data['event_type'];

        // Обрабатываем только нужные события
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $webhookData = $data['resource'];
            $status = $webhookData['status'];
            $amount = $webhookData['amount']['value'];
            $currency = $webhookData['amount']['currency_code'];
            $transactionId = $webhookData['id'];
            $userId = $webhookData['purchase_units'][0]['user_id'] ?? null;

            // Логика для успешного платежа
            if ($status === 'COMPLETED') {
                // Например, зачисление средств на счет пользователя
                // updateUserBalance($userId, $amount);
                user::getUserId($userId)->donateAdd($amount);

                http_response_code(200);  // Отправляем 200 OK для подтверждения получения вебхука
                file_put_contents(__DIR__ . '/success.log', "Payment successful for User ID: $userId, Amount: $amount $currency, Transaction ID: $transactionId" . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . '/debug_error.log', "Unexpected status: $status" . PHP_EOL, FILE_APPEND);
            }
        } else {
            // Игнорируем неинтересующие нас события
            file_put_contents(__DIR__ . '/ignored_events.log', "Ignored event: $eventType" . PHP_EOL, FILE_APPEND);
            http_response_code(200);  // Все равно отправляем 200 OK
        }
    }



}
