<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class monobank extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static bool $enable = true;

    // Включена/отключена платежная система

    protected static bool $forAdmin = false;

    // Включить только для администратора

    private string $currency_default = 'UAH';

    public static function inputs(): array
    {
        return [
          'token' => '',
        ];
    }

    /**
     * @return mixed
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link()
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));

        donate::isOnlyAdmin(self::class);
        if (empty(self::getConfigValue('token'))) {
            board::error("Monobank token is empty");

            return false;
        }

        $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT);
        if ( ! $count) {
            board::notice(false, "Введите сумму цифрой");

            return false;
        }

        $donate = \Ofey\Logan22\controller\config\config::load()->donate();

        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        // Если отбрасываем дробную часть
        $order_amount = $_POST['count'] * ($donate->getRatioUAH() / $donate->getSphereCoinCost());

        $scheme   = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $selfSite = $scheme . $_SERVER['HTTP_HOST'];

        $jsonData = [
          "amount"           => $order_amount * 100, // Сумма в копейках
          "ccy"              => 980, // Код валюты по ISO 4217 (980 - гривна)
          "merchantPaymInfo" => [
            "reference" => auth::get_id(),
            "comment"   => "Помощь проекту",
          ],
          "redirectUrl"      => "{$selfSite}/donate/pay",
          "webHookUrl"       => "{$selfSite}/donate/webhook/monobank",
          "validity"         => 3600, // Срок действия в секундах (1 час)
          "paymentType"      => "debit", // Тип операции
        ];
        $json     = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $url      = "https://api.monobank.ua/api/merchant/invoice/create";
        $ch       = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
          "X-Token: " . self::getConfigValue('token'),
        ]);

        $result   = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ( ! $result) {
            board::error("Ошибка при запросе к Monobank API");

            return false;
        }

        $jsonData = json_decode($result, true);

        if ($httpcode !== 200) {
            board::error("Ошибка: Неверный ответ от Monobank API. HTTP Code: $httpcode");
            echo '<pre>HTTP Code: ' . $httpcode . '</pre>';
            echo '<pre>Response: ' . print_r($jsonData, true) . '</pre>'; // Вывод всего ответа для отладки

            return false;
        }

        if (isset($jsonData['pageUrl'])) {
            echo $jsonData['pageUrl'];
            return true;
        } else {
            $errCode = $jsonData['errCode'] ?? 'UNKNOWN';
            $errText = $jsonData['errText'] ?? 'No error text provided';
            board::error("Ошибка: Неверный ответ от Monobank API. Код ошибки: $errCode. Текст ошибки: $errText");
            echo '<pre>' . print_r($jsonData, true) . '</pre>'; // Вывод всего ответа для отладки

            return false;
        }
    }

    // Получение информации об оплате
    function webhook(): void
    {
        file_put_contents( __DIR__ . '/debug.log', '_REQUEST: ' . print_r( json_decode(file_get_contents('php://input'), true), true ) . PHP_EOL, FILE_APPEND );

        if (empty($this->monobank_token)) {
            board::error("Monobank token is empty");

            return;
        }
        // Получаем данные из POST
        $postData = file_get_contents('php://input');
        $logData  = json_decode($postData, true);

        if ($logData['status'] !== 'success') {
            return;
        }

        $invoiceId = $logData['invoiceId'];
        $url       = "https://api.monobank.ua/api/merchant/invoice/status?invoiceId={$invoiceId}";
        $ch        = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "X-Token: " . self::getConfigValue('token'),
        ]);

        $result = curl_exec($ch);
        curl_close($ch);
        if ( ! $result) {
            board::error("Ошибка при запросе статуса инвойса");

            return;
        }

        $info = json_decode($result, true);
        if ($info['status'] !== 'success') {
            return;
        }

        $user_id = $info['reference'];
        $amount  = $info['amount'] / 100;

        donate::control_uuid($invoiceId, get_called_class());

        $amount = donate::currency($amount, $_POST['currency']);

        \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$_POST['sum'], $_POST['currency'], get_called_class()]);
        user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование Monobank", get_called_class());
        donate::addUserBonus($user_id, $amount);
        echo 'YES';
    }

}

