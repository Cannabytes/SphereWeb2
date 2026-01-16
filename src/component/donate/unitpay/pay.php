<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class unitpay extends \Ofey\Logan22\model\donate\pay_abstract {

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = true;

    protected static string $name = 'Unitpay';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

    public static function inputs(): array
    {
        return [
            'publicKey' => '',
            'secretKey' => '',
        ];
    }

    private string $publicKey = '';

    private string $secretKey = '';

    private string $desc = 'Покупка Donate Coin';

    //Включить тест режим
    private const TESTMODE = true;
    private static string $test_key = '';
    private static $test_email = 'example@gmail.com';


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

        $account = user::self()->getId();

        $signature = hash( 'sha256', $account . '{up}' . $currency . '{up}' . $this->desc . '{up}' . $amount . '{up}' . self::getConfigValue('secretKey') );

        $params = [
            'account' => $account,
            'currency' => $currency,
            'desc' => $this->desc,
            'sum' => $amount,
            'paymentType' => 'card',
            'cashItems' => base64_encode(json_encode([
                [
                    'name' => $this->desc,
                    'count' => 1,
                    'price' => $amount,
                ]
            ])),
            'customerEmail' => user::self()->getEmail(),
            'projectId' => self::getConfigValue('publicKey'),
            'resultUrl' => \Ofey\Logan22\component\request\url::host('/donate'),
            'secretKey' => self::TESTMODE ? self::$test_key : self::getConfigValue('secretKey'),
            'signature' => $signature,
            'hideMenu' => true,
            'hideOtherPSMethods' => true,
            'hideOtherMethods' => true,
        ];

        if ( self::TESTMODE ) {
            $params['test'] = 1;
            $params['login'] = self::$test_email;
        }

        $requestUrl = 'https://unitpay.ru/api?' . http_build_query([
                'method' => 'initPayment',
                'params' => $params
            ], numeric_prefix: '', arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);

        $response = json_decode( file_get_contents( $requestUrl ), true );

        if (isset($response['error']['message'])) {
            board::notice( false, $response['error']['message'] );
        }

        if ($response['result']['redirectUrl']) {
            echo $response['result']['redirectUrl'];
        }
    }

    //Получение информации об оплате
    function webhook(): void
    {
        try {
            if (!(config::load()->donate()->getDonateSystems('unitpay')?->isEnable() ?? false)) {
                $this->sendJsonResponse(['error' => ['message' => 'Service disabled']]);
                return;
            }

            \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

            $method = $_REQUEST['method'] ?? '';
            $userId = $_REQUEST['params']['account'] ?? null;
            $amount = $_REQUEST['params']['orderSum'] ?? null;
            $crc = $_REQUEST['params']['signature'] ?? '';
            // Валидация входных данных
            if (empty($method) || empty($userId) || empty($amount) || empty($crc)) {
                $this->sendJsonResponse(['error' => ['message' => 'Missing required parameters']]);
                return;
            }

            // Валидация подписи
            unset($_REQUEST['params']['signature']);
            ksort($_REQUEST['params']);
            $params = implode('{up}', $_REQUEST['params']);
            $sign = hash('sha256', $method . '{up}' . $params . '{up}' . self::getConfigValue('secretKey'));

            if ($crc !== $sign) {
                $this->sendJsonResponse(['error' => ['message' => 'Wrong signature!']]);
                return;
            }

            if ($method !== 'pay') {
                $this->sendJsonResponse(['result' => ['message' => "Запрос успешно обработан"]]);
                return;
            }

            // Валидация user ID
            if (!is_numeric($userId) || $userId <= 0) {
                $this->sendJsonResponse(['error' => ['message' => 'Invalid user ID']]);
                return;
            }

            // Валидация суммы
            if (!is_numeric($amount) || $amount <= 0) {
                $this->sendJsonResponse(['error' => ['message' => 'Invalid amount']]);
                return;
            }

            donate::control_uuid(substr($crc, 0, 8), get_called_class());

            // Безопасное преобразование суммы
            $convertedAmount = donate::currency((float)$amount, self::getCurrency());

            self::telegramNotice(
                user::getUserId($userId),
                $_REQUEST['params']['orderSum'],
                self::getCurrency(),
                $convertedAmount,
                get_called_class()
            );

            $user = user::getUserId($userId);
            if (!$user) {
                $this->sendJsonResponse(['error' => ['message' => 'User not found']]);
                return;
            }

            $user->donateAdd($convertedAmount)->AddHistoryDonate(
                amount: $convertedAmount,
                pay_system: get_called_class()
            );
            donate::addUserBonus($userId, $convertedAmount);

            $this->sendJsonResponse(['result' => ['message' => "Запрос успешно обработан"]]);

        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            $this->sendJsonResponse(['error' => ['message' => 'Internal server error']]);
        }
    }

    private function sendJsonResponse(array $data): void
    {
        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }
}
 