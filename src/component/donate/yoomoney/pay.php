<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class yoomoney extends \Ofey\Logan22\model\donate\pay_abstract {

    /**
     * Конфигурация
     * $receiver - Номер кошелька ЮMoney, на который нужно зачислять деньги отправителей.
     * $secret_key - секретный ключ
     */
    private $receiver = '';

    public static function inputs(): array
    {
        return [
            'shopId' => '',
            'secretKey' => '',
        ];
    }

    private $currency_default = 'RUB';
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

        $order_amount = $_POST['count'] * ($donate->getRatioRUB() / $donate->getSphereCoinCost());
        $params = [
            'receiver' => $this->receiver,
            'sum' => (string)$order_amount,
            "quickpay-form" => 'donate',
            'label' => auth::get_id(),
            'paymentType' => 'AC',
            'successURL' => \Ofey\Logan22\component\request\url::host("/donate/pay"),
        ];
        echo "https://yoomoney.ru/quickpay/confirm.xml?" . http_build_query($params);
    }

    //Получение информации об оплате
    function webhook(): void {
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
        donate::control_uuid($operation_id, get_called_class());
        \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$amount, $currency, get_called_class()]);
        self::telegramNotice(user::getUserId($user_id), $_POST['amount'], $currency, $amount, get_called_class());
        $amount = donate::currency($withdraw_amount, $currency);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
        donate::addUserBonus($user_id, $amount);
        exit();
    }
}
