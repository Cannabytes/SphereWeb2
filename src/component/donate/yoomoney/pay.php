<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class yoomoney extends \Ofey\Logan22\model\donate\pay_abstract {

    protected static string $name = 'YooMoney';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

    public static function inputs(): array
    {
        return [
            'shopId' => '',
            'secretKey' => '',
        ];
    }

    private array $allowIP = [];

    //Включена/отключена платежная система
    protected static bool $enable = true;
    protected static bool $forAdmin = false;

    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        if(empty(self::getConfigValue('shopId')) OR empty(self::getConfigValue('secretKey'))){
            board::error("Yoomoney token is empty");
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

        $params = [
            'receiver' => self::getConfigValue('shopId'),
            'sum' => (string)$amount,
            "quickpay-form" => 'donate',
            'label' => user::self()->getId(),
            'paymentType' => 'AC',
            'successURL' => \Ofey\Logan22\component\request\url::host("/donate/pay"),
        ];
        echo "https://yoomoney.ru/quickpay/confirm.xml?" . http_build_query($params);

        $ch = curl_init('https://yoomoney.ru/quickpay/confirm.xml');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            // Обрабатываем ошибку
            echo "Ошибка при отправке: $error";
        } else {
            // Выводим ответ сервиса
            echo "Ответ YooMoney: " . $response;
        }
    }

    //Получение информации об оплате
    function webhook(): void {
        file_put_contents( __DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r( $_REQUEST, true ) . PHP_EOL, FILE_APPEND );

        if (!(config::load()->donate()->getDonateSystems('yoomoney')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }

        $notification_type = $_POST['notification_type'] ?? "";
        if($notification_type != "card-incoming"){
            exit();
        }
        $request_hash = $_POST['sha1_hash'] ?? "";
        $withdraw_amount = $_POST['withdraw_amount'] ?? 0;
        $operation_id = $_POST['operation_id']  ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $currency = $_POST['currency']  ?? 0;
        $datetime = $_POST['datetime']  ?? "";
        $sender = $_POST['sender'] ?? "";
        $codepro = $_POST['codepro'] ?? "";
        $user_id = $_POST['label'] ?? "";
        $notification_secret = self::getConfigValue('secretKey');
        $hash = sha1("{$notification_type}&{$operation_id}&{$amount}&{$currency}&{$datetime}&{$sender}&{$codepro}&{$notification_secret}&{$user_id}");
        if($hash !== $request_hash){
            exit();
        }
        $currency = "RUB";
        donate::control_uuid($operation_id, get_called_class());
        self::telegramNotice(user::getUserId($user_id), $_POST['amount'], $currency, $amount, get_called_class());
        $amount = donate::currency($withdraw_amount, $currency);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
        donate::addUserBonus($user_id, $amount);
        exit();
    }
}
