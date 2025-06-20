<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class pally extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static string $webhook = "/donate/webhook/pally";

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    protected static string $name = 'Pally';

    protected static array $country = ['ru', 'ua'];

    protected static string $currency_default = 'RUB';

    private array $allowIP = [
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

        $currency = config::load()->donate()->getDonateSystems(get_called_class())?->getCurrency() ?? self::getCurrency();
        $amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatio($currency), $donate->getSphereCoinCost());

        $data = [
            'amount' => $amount,
            'order_id' => (string)(time() . mt_rand(1, 999)),
            'type' => 'normal',
            'shop_id' => self::getConfigValue('shop_id'),
            'custom' => user::self()->getId(),
            'currency_in' => $currency,
            'payer_pays_commission' => 1,
            'payer_email' => user::self()->getEmail(),
        ];

        $ch = curl_init('https://pal24.pro/api/v1/bill/create');
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
        if (!(config::load()->donate()->getDonateSystems('pally')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

        if (strcasecmp($_POST['Status'], 'SUCCESS') !== 0) {
            echo "Status no success";exit;
        }

        $invId = $_POST['InvId'] ?? "";
        $amount = $_POST['OutSum'];
        $currencyIn = $_POST['CurrencyIn'];
        $user_id = $_POST['custom'];
        $signatureValue = $_POST['SignatureValue']; // Подпись

        //Проверяем подпись
        if (!$this->checkSignature($signatureValue, $amount, $invId)){
            echo 'checksum error';exit;
        }

        $amount   = donate::currency($amount, $currencyIn);
        donate::control_uuid($signatureValue, get_called_class());

        self::telegramNotice(user::getUserId($user_id), $_POST['OutSum'], $currencyIn, $amount, get_called_class());
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
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
