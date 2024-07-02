<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class enot extends \Ofey\Logan22\model\donate\pay_abstract
{

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    public static function inputs(): array
    {
        return [
          'merchant_id' => '',
          'secret_1'   => '',
          'secret_2'   => '',
        ];
    }

    private $allowIP = [
        '5.187.7.207',
        '149.202.68.3 ',
        '51.210.114.114',
        '109.206.163.80',
        '23.88.5.163',
		    '31.133.209.40',
        '23.88.5.156',
    ];


    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT) ?: board::notice(false, "Введите сумму цифрой");

        $donate = \Ofey\Logan22\controller\config\config::load()->donate();

        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        $order_amount = $_POST['count'] * ($donate->getRatioRUB() / $donate->getSphereCoinCost());
        $payment_id = time();
        $sign = md5(self::getConfigValue('merchant_id') . ':' . $order_amount . ':' . self::getConfigValue('secret_1') . ':' . $payment_id);

        $params = [
            'm' => self::getConfigValue('merchant_id'),
            'oa' => $order_amount,
            'o' => $payment_id,
            's' => $sign,
            'cf[user_id]' => user::self()->getId(),
        ];
        echo "https://enot.io/pay/?" . http_build_query($params);
    }

    function transfer(): void
    {
        file_put_contents( __DIR__ . '/debug.log', '_REQUEST: ' . print_r( $_REQUEST, true ) . PHP_EOL, FILE_APPEND );

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

        $merchant = $_POST['merchant'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $payment_id = $_POST['merchant_id'] ?? 0;
        $sign = md5( $merchant . ':' . $amount . ':' . self::getConfigValue('secret_2') . ':' . $payment_id );
        $sign_2 = $_POST['sign_2'] ?? '';
        $user_id  = $_POST['custom_field']['user_id'] ?? false;
        if ( $sign != $sign_2 ) die( 'bad sign!' );

        donate::control_uuid($payment_id, get_called_class());

        $amount = donate::currency($amount, "RUB");

        user::self()->addLog(logTypes::LOG_DONATE_SUCCESS, "LOG_DONATE_SUCCESS", [
          $_POST['amount'], $_POST['currency'], $amount,
        ]);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование", get_called_class());
        donate::addUserBonus($user_id, $amount);
        echo 'YES';

    }

    //Получение информации об оплате

    /**
     * @return void
     * Проверка IP адреса
     */
    function allowIP(): void
    {
        if (!in_array($_SERVER['REMOTE_ADDR'], $this->allowIP)) {
            die("Forbidden: Your IP is not in the list of allowed");
        }
    }
}
