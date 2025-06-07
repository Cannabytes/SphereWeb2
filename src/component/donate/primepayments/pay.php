<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class primepayments extends \Ofey\Logan22\model\donate\pay_abstract
{

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    protected static string $name = 'PrimePayments';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

    private array $allowIP = [];

    /*
     * Список IP адресов, от которых может прити уведомление от платежной системы.
     */

    /**
     * Получить содержимое конфига используя метод self::getConfigValue('secret_1')
     *
     * @return string[]
     */
    public static function inputs(): array
    {
        return [
          'project_id' => '',
          'secret_1'   => '',
          'secret_2'   => '',
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
          'action'     => 'initPayment',
          'project'    => self::getConfigValue('project_id'),
          'sum'        => $amount,
          'currency'   => $currency,
          'innerID'    => user::self()->getId(),
          'payWay'     => '1',
          'email'      => user::self()->getEmail(),
          'returnLink' => 1,
        ];

        $data['sign'] = md5(
          self::getConfigValue(
            'secret_1'
          ) . $data['action'] . $data['project'] . $amount . $data['currency'] . $data['innerID'] . $data['email'] . $data['payWay']
        );
        $ch           = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://pay.primepayments.io/API/v2/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $answer = json_decode($server_output, true);
        if (isset($answer['status']) && $answer['status'] == 'OK') {
            echo $answer['result'];
        } else {
            echo json_encode([
              'ok'      => false,
              'message' => "Error: " . $answer['result'],
            ]);
        }
    }

    //Получение информации об оплате
    function webhook(): void
    {

        if (!(config::load()->donate()->getDonateSystems('primepayments')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        $hash = md5(
          self::getConfigValue(
            'secret_2'
          ) . $_POST['orderID'] . $_POST['payWay'] . $_POST['innerID'] . $_POST['sum'] . $_POST['webmaster_profit']
        );
        if ($hash != $_POST['sign']) {
            die('wrong sign');
        }
        $user_id = $_POST['innerID'];
        donate::control_uuid($_POST['orderID'], get_called_class());
        //Зачисление на пользовательский аккаунт средств
        $amount = donate::currency($_POST['sum'], $_POST['currency']);

        self::telegramNotice(user::getUserId($user_id), $_POST['sum'], $_POST['currency'], $amount, get_called_class());
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Primepayments", get_called_class());
        donate::addUserBonus($user_id, $amount);
        echo 'YES';
    }

}
