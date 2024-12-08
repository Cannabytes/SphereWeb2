<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class palych extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static string $webhook = "/donate/webhook/palych";

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    private array $allowIP = [
        '138.201.53.39',
        '176.9.155.22',
        '2.58.70.63',
    ];

    public static function inputs(): array
    {
        return [
          'shop_id'    => '',
          'api_key' => '',
        ];
    }

    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);
        filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT) ?: board::notice(false, "Введите сумму цифрой");
        $donate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();
        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }
        $order_amount = $_POST['count'] * ($donate->getRatioRUB() / $donate->getSphereCoinCost());

        $data = [
            'amount' => $order_amount,
            'order_id' => '123456',
            'type' => 'normal',
            'shop_id' => self::getConfigValue('shop_id'),
            'custom' => user::self()->getId(),
            'currency_in' => 'RUB',
            'payer_email' => user::self()->getEmail(),
            'success_url' => \Ofey\Logan22\component\request\url::host("/donate/pay"),
            'fail_url' => \Ofey\Logan22\component\request\url::host("/donate/pay"),
        ];

        $ch = curl_init('https://paypalych.com/api/v1/bill/create');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . self::getConfigValue('api_key'),
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $json = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($json, true);

        if(isset($data['success']) && $data['success'] == 'true') {
            echo $data['link_page_url'];
        } else {
            echo json_encode([
                'ok' => false,
                'message' => "Error: " . $data['message'],
            ]);
        }
    }

    function webhook(): void
    {
        $input     = file_get_contents('php://input');
        $requestData = json_decode($input, true);
        file_put_contents(__DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r($requestData, true) . PHP_EOL, FILE_APPEND);

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

        if($_POST['Status'] != 'success'){
            echo "Status no success";
        }

        $invId = $_POST['InvId'] ?? ""; // Уникальный идентификатор заказа, переданный при формировании счета
        $amount = $_POST['OutSum']; //Сумма платежа
        $currencyIn = $_POST['CurrencyIn']; // Валюта, в которой оплачивался счет
        $user_id = $_POST['custom']; //Произвольное поле, переданное при формировании счета
        $signatureValue = $_POST['SignatureValue']; // Подпись

        //Проверяем подпись
        if (!$this->checkSignature($signatureValue, $amount, $invId)){
            echo 'checksum error';exit;
//            file_put_contents(__DIR__ . '/error_checksumm.log', '_REQUEST: ' . print_r($invId, true) . PHP_EOL, FILE_APPEND);
        }

        $amount   = donate::currency($amount, $currencyIn);

        \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$_POST['OutSum'], $currencyIn, get_called_class()]);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, message: null, pay_system:  get_called_class(), input: $input);
        donate::addUserBonus($user_id, $amount);
        echo 'YES';

    }

    private function checkSignature(string $signatureValue = "", string $outSum = "", string $invId = ""): bool
    {
       $hash = strtoupper(md5($outSum . ":" . $invId . ":" . self::getConfigValue('api_key')));
       if ($hash == $signatureValue){
           return true;
       }
       return false;
    }

}
