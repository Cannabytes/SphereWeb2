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

    private $api_mode = 'LIVE';

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
        $clientId  = self::getConfigValue('clientId');
        $secretKey = self::getConfigValue('secretKey');

        if (empty($clientId) || empty($secretKey)) {
            board::error('Не установлены secretKey / clientId');
        }

        // Проверка суммы
        $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT);
        if ( ! $count) {
            board::notice(false, "Введите сумму цифрой");
        }

        $donate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();
        if ($count < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($count > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальное пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        $auth_url = $this->api_mode === 'LIVE' ? "https://api-m.paypal.com/v1/oauth2/token" : "https://api-m.sandbox.paypal.com/v1/oauth2/token";

        // Расчет суммы заказа
        $order_amount = $count * ($donate->getRatioUSD() / $donate->getSphereCoinCost());

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

        if ( ! $accessToken) {
            die("Ошибка: токен доступа не получен.");
        }

        curl_close($ch);

        $url = $this->api_mode === 'LIVE' ? "https://api-m.paypal.com/v2/checkout/orders" : "https://api-m.sandbox.paypal.com/v2/checkout/orders";

        $orderRequest = json_encode([
          'intent'              => 'CAPTURE',
          'purchase_units'      => [
            [
              'custom_id'     => user::self()->getId(),
              'amount'      => [
                'value'         => $order_amount,
                'currency_code' => 'USD',
              ],
              'description' => 'Payment for donation', // Добавьте описание для заказа
            ],
          ],
          'application_context' => [
            'return_url' =>  \Ofey\Logan22\component\request\url::host("/balance"),
            'cancel_url' => \Ofey\Logan22\component\request\url::host("/balance"),
          ],
        ]);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST           => true,
          CURLOPT_POSTFIELDS     => $orderRequest,
          CURLOPT_HTTPHEADER     => [
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
            $order_id    = $responseData['id'];
            $approveLink = $this->api_mode === 'LIVE' ? "https://www.paypal.com/checkoutnow?token={$order_id}" : "https://www.sandbox.paypal.com/checkoutnow?token={$order_id}";
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
        $data = json_decode($requestBody, true);

        $clientId     = self::getConfigValue('clientId');
        $clientSecret = self::getConfigValue('secretKey');

        $orderId = $data['resource']['id'];  // Полученный ранее идентификатор заказа
        $customId = $data['resource']['purchase_units'][0]['custom_id'] ?? null;
        if ($customId) {
            file_put_contents(__DIR__ . '/custom_id.log', "Custom ID: $customId" . PHP_EOL, FILE_APPEND);
        } else {
            return;
        }

        $auth_url = $this->api_mode === 'LIVE' ? "https://api-m.paypal.com/v1/oauth2/token" : "https://api-m.sandbox.paypal.com/v1/oauth2/token";

        // Генерация токена доступа для PayPal API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Accept: application/json",
          "Accept-Language: en_US",
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$clientSecret");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $accessToken = json_decode($response)->access_token;

        $url = $this->api_mode === 'LIVE' ? "https://api-m.paypal.com/v2/checkout/orders/$orderId/capture" : "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderId/capture";

        // Захват средств
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Content-Type: application/json",
          "Authorization: Bearer $accessToken",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        file_put_contents(__DIR__ . '/data_request_result.log', print_r($result, true) . PHP_EOL, FILE_APPEND);

        if($result['status'] === 'COMPLETED') {
            // Добавление средств
            $currency = $result['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
            $amount = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
            $customId = $result['purchase_units'][0]['payments']['captures'][0]['custom_id'];

            $amount   = donate::currency($amount, $currency);
            \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$result['purchase_units'][0]['payments']['captures'][0]['amount']['value'], $currency, get_called_class()]);
            user::getUserId($customId)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование PayPal", get_called_class());
            donate::addUserBonus($customId, $amount);
        }

    }

}
