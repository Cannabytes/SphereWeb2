<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class cryptocloud extends \Ofey\Logan22\model\donate\pay_abstract
{

    /****** SETTING *****/

    //Включена/отключена платежная система
    //Enabled/Disabled pay system
    protected static bool $enable = true;

    /**
     * @return bool
     */
    public static function isEnable(): bool
    {
        return self::$enable;
    }

    //Включить только для администратора
    //Enable for administrator only
    protected static bool $forAdmin = false;


    protected string $currency_default = 'USD';

    private array $allowIP = [];

    /*
     * Список IP адресов, от которых может прийти уведомление от платежной системы.
     * List of IP addresses from which notifications from the payment system can come.
     */

    public static function inputs(): array
    {
        return [
          'apiKey'    => '',
          'shopId'    => '',
          'secretKey' => '',
        ];
    }

    /****** IMPLEMENTATION *****/

    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);
        if (empty(self::getConfigValue('shopId')) or empty(self::getConfigValue('apiKey')) or empty(self::getConfigValue('secretKey'))) {
            board::error('No set token api');
        }
        filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT) ?: board::notice(false, "Введите сумму цифрой");
        $donate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();

        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }


        $order_amount = $_POST['count'] * ($donate->getRatioUSD() / $donate->getSphereCoinCost());

        $response = $this->getResponse('https://api.cryptocloud.plus/v2/invoice/create', [
          'shop_id'  => self::getConfigValue('shopId'),
          'amount'   => $order_amount,
          'order_id' => user::self()->getId() . '@' . time(),
          'currency' => $this->currency_default,
          'email'    => user::self()->getEmail(),
        ]);

        $status = $response['status'] ?? 'fail';

        if ($status <> 'success') {
            board::notice(false, $response['msg'] ?? $response['detail'] ?? 'Unknown error');
        }

        echo $response['result']['link'];
    }

    //Получение информации об оплате

    function getResponse($url, $postFields = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
          CURLOPT_URL            => $url,
          CURLOPT_RETURNTRANSFER => 1,
          CURLOPT_POST           => 1,
          CURLOPT_POSTFIELDS     => json_encode($postFields),
          CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . self::getConfigValue('apiKey'),
            'Content-Type: application/json',
          ],
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    function webhook()
    {
        file_put_contents( __DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r( $_REQUEST, true ) . PHP_EOL, FILE_APPEND );

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        if (empty(self::getConfigValue('shopId')) or empty(self::getConfigValue('apiKey')) or empty(self::getConfigValue('secretKey'))) {
            board::error('No set token api');
        }

        $jwtParts  = explode('.', $_REQUEST['token'] ?? '..');
        $signature = $jwtParts[2];

        $generatedSignature = hash_hmac('sha256', $jwtParts[0] . '.' . $jwtParts[1], self::getConfigValue('secretKey'), true);
        $generatedSignature = strtr(rtrim(base64_encode($generatedSignature), '='), '+/', '-_');

        if ( ! hash_equals($signature, $generatedSignature)) {
            header('HTTP/1.1 400 Bad Request', true, 400);
            die('Bad sign!');
        }

        $response = $this->getResponse('https://api.cryptocloud.plus/v2/invoice/merchant/info', [
          'uuids' => [
            $_REQUEST['invoice_id'] ?? '',
          ],
        ]);
        if (isset($response['detail'])) {
            die($response['detail']);
        }
        if ($response['status'] <> 'success' || $response['result'][0]['status'] <> 'paid') {
            header('HTTP/1.1 400 Bad Request', true, 400);
            die('Not paid!');
        }

        donate::control_uuid($_REQUEST['invoice_id'], get_called_class());

        $orderId = explode('@', $response['result'][0]['order_id']);
        $amount  = $response['result'][0]['amount_to_pay_usd'] ?? 0;

        $user_id = $orderId[0];

        $amount = donate::currency($amount, $this->currency_default);

        if (config::load()->notice()->isDonationCrediting()) {
            $msg = sprintf("Пользователь %s (%s) пополнил баланс на %s %s.\nДобавлено %0.1f внутренней валюты.\nСистема: %s",
                user::getUserId($user_id)->getEmail(), user::getUserId($user_id)->getName(), $_POST['amount'], $this->currency_default, $amount, get_called_class());
            telegram::sendTelegramMessage($msg);
        }

        \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$amount, $this->currency_default, get_called_class()]);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, message: null, pay_system:  get_called_class());
        donate::addUserBonus($user_id, $amount);

    }

}
