<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class aaiopay extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static bool $enable = true;

    protected static bool $forAdmin = false;

    private array $allowIP = [];

    private string $currency_default = 'UAH';

    public static function inputs(): array
    {
        return [
          'merchant_id'  => '',
          'secret_key_1' => '',
          'secret_key_2' => '',
        ];
    }

    function create_link(): void
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT) ?: board::notice(false, "Введіть суму цифрою");

        $donate = \Ofey\Logan22\model\server\server::getServer()->getDonateConfig();

        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        $amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatioRUB(), $donate->getSphereCoinCost());

        // Генерируем случайный идентификатор заказа
        $order_id = uniqid();
        // Очищаем идентификатор заказа от недопустимых символов
        $order_id = preg_replace('/[^a-zA-Z0-9:\[\]|_-]/', '', $order_id);
        // Обрезаем идентификатор заказа до максимальной длины, если нужно
        $order_id = substr($order_id, 0, 64);

        // Формируем строку для хеширования
        $sign_string = self::getConfigValue('merchant_id') . ':' . number_format(
            $amount,
            2,
            '.',
            ''
          ) . ':' . $this->currency_default . ':' . self::getConfigValue('secret_key_1') . ':' . $order_id;

        // Создаем подпись (хеш)
        $sign = hash('sha256', $sign_string);

        $params = [
          'merchant_id' => self::getConfigValue('merchant_id'),
          'amount'      => number_format($amount, 2, '.', ''),
          'order_id'    => $order_id,
          'sign'        => $sign,
          'currency'    => $this->currency_default,
          'desc'        => 'Описание вашего заказа',
          'email'       => user::self()->getEmail(),
        ];
        $url    = "https://aaio.so/merchant/pay?" . http_build_query($params);
        echo $url;
    }

    function webhook(): void
    {
        if (!(config::load()->donate()->getDonateSystems('aaiopay')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }
        file_put_contents( __DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r( $_REQUEST, true ) . PHP_EOL, FILE_APPEND );

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        $email       = $_REQUEST['email'];
        $amount      = number_format($_REQUEST['amount'], 2, '.', '');
        $merchant_id = $_REQUEST['merchant_id'];
        $order_id    = $_REQUEST['order_id'];
        $currency    = $_REQUEST['currency'];

        $sign = hash('sha256', implode(':', [$merchant_id, $amount, $currency, self::getConfigValue('secret_key_2'), $order_id]));

        if ( ! hash_equals($_REQUEST['sign'], $sign)) {
            die("wrong sign #1");
        }

        donate::control_uuid($order_id, get_called_class());
        $amount = donate::currency($amount, $currency);

        self::telegramNotice(user::getUserByEmail($email), $_REQUEST['amount'], $currency, $amount, get_called_class());
        $user = user::getUserByEmail($email);
        $user->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
        donate::addUserBonus($user->getId(), $amount);
        echo 'YES';
    }

}
