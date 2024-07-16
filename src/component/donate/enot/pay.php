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
          'shop_id' => '',
          'secret_key'   => '',
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

        $shop_id = self::getConfigValue('shop_id');
        $email = user::self()->getEmail();
        $secret_word = self::getConfigValue('secret_key');
        $currency = "RUB";
        $order_id = uniqid();
        $sign = md5($shop_id . ':' . $order_amount . ':' . $secret_word . ':' . $currency . ':' . $order_id);

        // Подготовка данных для отправки запроса
        $params = [
          'amount' => $order_amount,
          'order_id' => $order_id,
          'email' => $email,
          'currency' => $currency,
          'shop_id' => $shop_id,
          's' => $sign,
          "custom_fields" => ["order" => user::self()->getId()],
        ];

        // Формируем заголовки для запроса
        $headers = [
          'Accept: application/json',
          'Content-Type: application/json',
          'x-api-key: ' . self::getConfigValue('secret_key')
        ];

        // Инициализируем CURL-сессию
        $ch = curl_init();

        // Устанавливаем опции CURL
        curl_setopt($ch, CURLOPT_URL, 'https://api.enot.io/invoice/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Выполняем запрос и получаем ответ
        $response = curl_exec($ch);

        // Закрываем CURL-сессию
        curl_close($ch);

        $response_object = json_decode($response);

        // Проверяем успешность запроса
        if ($response_object->status === 200 && $response_object->status_check) {
            // Если запрос успешен, вытаскиваем URL из объекта
            $url = $response_object->data->url;

            // Выводим URL
            echo $url;
        } else {
            // Если запрос не успешен, выводим соответствующее сообщение или обрабатываем ошибку
            echo "Request failed: " . $response_object->error;
        }

    }

    function transfer(): void
    {
        $requestData = json_decode(file_get_contents('php://input'), true);
        file_put_contents( __DIR__ . '/debug.log', '_REQUEST: ' . print_r( $requestData, true ) . PHP_EOL, FILE_APPEND );

        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
//
//        // Использование данных из тела запроса
        $status = $requestData['status'] ?? null;
        $amount = $requestData['amount'] ?? null;
        $currency = $requestData['currency'] ?? "RUB";
        $secret_word = self::getConfigValue('secret_key');
//
        $order = $requestData['custom_fields']['order'] ?? null;
//
//        // Преобразование значения $order в целочисленное значение (int)
        $user_id = $order !== null ? (int) $order : null;
//
//        // Получение подписи из заголовка запроса
        $signature = $_SERVER['HTTP_X_API_SHA256_SIGNATURE'];
        if(!self::checkSignature($requestData, $signature, $secret_word)) {
            echo 'SIGNATURE ERROR';
            exit;
        }

        $logFile = fopen('log.txt', 'a');
//
//        // Записываем сигнатуру в файл логов
        fwrite($logFile, 'Signature: ' . $signature . "\n");
//
        // Закрываем файл логов
        fclose($logFile);
        // Проверка подписи и условие статуса
        if ($signature !== null && $status === "success") {
            $amount = donate::currency($amount, $currency);
            \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$_POST['sum'], $_POST['currency'], get_called_class()]);
            user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование Enot", get_called_class());
            donate::addUserBonus($user_id, $amount);
            echo 'YES';
        }
    }

    static function checkSignature(string $hookJson, string $headerSignature, string $secretKey): bool
    {
        $hookArr = json_decode($hookJson, true);
        ksort($hookArr);
        $sortedHookJson = json_encode($hookArr, JSON_UNESCAPED_UNICODE);
        $calculatedSignature = hash_hmac('sha256', $sortedHookJson, $secretKey);
        return hash_equals($headerSignature, $calculatedSignature);
    }

}
