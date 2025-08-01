<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class freekassa extends \Ofey\Logan22\model\donate\pay_abstract {

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = false;

    protected static string $name = 'FreeKassa';

    protected static array $country = ['ru', 'ua', 'crypto'];

    public static function inputs(): array
    {
        return [
            'merchant_id' => '',
            'secret_key_1' => '',
            'secret_key_2' => '',
        ];
    }

    protected static string $currency_default = 'RUB';

    /*
     * Список IP адресов, от которых может прити уведомление от платежной системы.
     */
    private array $allowIP = [];

    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        if(empty(self::getConfigValue('secret_key_1')) OR empty(self::getConfigValue('secret_key_2'))){
            board::error("Freekassa token is empty");
        }
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
        $merchant_id = self::getConfigValue('merchant_id');
        $order_id = user::self()->getEmail();
        $secret_word = self::getConfigValue('secret_key_1');
        $sign = md5($merchant_id . ':' . $amount . ':' . $secret_word . ':' . $currency . ':' . $order_id);
        $params = [
            'm'         => $merchant_id,
            'oa'        => (string)$amount,
            "currency"  => $currency,
            's'         => $sign,
            'o'         => $order_id,
            'us_userid' => user::self()->getId(),
        ];
        echo "https://pay.fk.money/?" . http_build_query($params);
    }

    //Получение информации об оплате
    function webhook(): void {
        if (!(config::load()->donate()->getDonateSystems('freekassa')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        if(empty(self::getConfigValue('secret_key_1')) OR empty(self::getConfigValue('secret_key_2') )){
            board::error("Freekassa token is empty");
        }
        $user_id = $_REQUEST['us_userid'];
        $MERCHANT_ID = $_REQUEST['MERCHANT_ID'];
        $MERCHANT_ORDER_ID = $_REQUEST['MERCHANT_ORDER_ID'];

        $sign = md5($MERCHANT_ID . ':' . $_REQUEST['AMOUNT'] . ':' . self::getConfigValue('secret_key_2') . ':' . $MERCHANT_ORDER_ID);

        if($sign != $_REQUEST['SIGN']){
            die('wrong sign');
        }
        donate::control_uuid($_REQUEST['SIGN'] . "__" . mt_rand(0, 999999999), get_called_class());

        $amount = donate::currency($_REQUEST['AMOUNT'], self::getCurrency());

        self::telegramNotice(user::getUserId($user_id), $_REQUEST['AMOUNT'], self::getCurrency(), $amount, get_called_class());
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
        donate::addUserBonus($user_id, $amount);

        echo 'YES';
    }


}
