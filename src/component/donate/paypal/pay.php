<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class paypal extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static bool $forAdmin = false;

    // Включена/отключена платежная система
    protected static bool $enable = true;

    protected static string $name = 'PayPal';

    protected static array $country = ['world'];

    private $api_mode = 'LIVE';

    protected static string $currency_default = 'USD';

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

        $currency = config::load()->donate()->getDonateSystems(get_called_class())?->getCurrency() ?? self::getCurrency();
        $amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatio($currency), $donate->getSphereCoinCost());

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
                'value'         => $amount,
                'currency_code' => $currency,
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
        if (!(config::load()->donate()->getDonateSystems('paypal')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }
        // Получаем данные из тела запроса
        $input = file_get_contents('php://input');
        file_put_contents( __DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r( $input, true ) . PHP_EOL, FILE_APPEND );

        $data = json_decode($input, true);

        $clientId     = self::getConfigValue('clientId');
        $clientSecret = self::getConfigValue('secretKey');

        $orderId = $data['resource']['id'];  // Полученный ранее идентификатор заказа
        $customId = $data['resource']['purchase_units'][0]['custom_id'] ?? null;

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

        if($result['status'] === 'COMPLETED') {
            // Добавление средств
            $currency = $result['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
            $amount_r = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
            $amount = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
            $customId = $result['purchase_units'][0]['payments']['captures'][0]['custom_id'];
            $amount   = donate::currency($amount, $currency);
            self::telegramNotice(user::getUserId($customId), $_POST['OutSum'], $currency, $amount_r, get_called_class());
            user::getUserId($customId)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class(), input: $input);
            donate::addUserBonus($customId, $amount);
        }

    }

}
