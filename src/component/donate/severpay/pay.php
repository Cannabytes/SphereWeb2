<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class severpay extends \Ofey\Logan22\model\donate\pay_abstract {

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = false;

    protected static string $name = 'SeverPay';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

    private array $allowIP = [
        '45.76.81.14',
        '207.148.69.64',
    ];

    public static function inputs(): array
    {
        return [
            'mid' => '',
            'token' => '',
        ];
    }

    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        if(empty(self::getConfigValue('mid')) OR empty(self::getConfigValue('token'))){
            board::error("SeverPay configuration is empty");
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
        
        $mid = self::getConfigValue('mid');
        $token = self::getConfigValue('token');
        $order_id = user::self()->getId() . '_' . time();
        $salt = bin2hex(random_bytes(16));

        $body = [
            'mid' => (int)$mid,
            'amount' => (float)$amount,
            'currency' => $currency,
            'order_id' => $order_id,
            'client_email' => user::self()->getEmail(),
            'client_id' => (string)user::self()->getId(),
            'salt' => $salt,
        ];

        ksort($body);
        $body['sign'] = hash_hmac("sha256", json_encode($body), $token);

        $ch = curl_init('https://severpay.io/api/merchant/payin/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            board::error("SeverPay API error: HTTP " . $httpCode);
        }

        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === true) {
            echo $result['data']['url'];
        } else {
            board::error("SeverPay error: " . ($result['msg'] ?? 'Unknown error'));
        }
    }

    //Получение информации об оплате
    function webhook(): void {
        if (!(config::load()->donate()->getDonateSystems('severpay')?->isEnable() ?? false)) {
             echo 'disabled';
            exit;
        }

        // \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        $inputJSON = file_get_contents('php://input');

        $input = json_decode($inputJSON, TRUE);

        if (!$input || !isset($input['sign'])) {
            die('Invalid input');
        }

        $token = self::getConfigValue('token');
        $input_sign = $input['sign'];
        unset($input['sign']);

        $sign = hash_hmac("sha256", json_encode($input), $token);

        if (!hash_equals($input_sign, $sign)) {
            die('Wrong sign');
        }

        if ($input['type'] !== 'payin') {
            die('Invalid type');
        }

        $data = $input['data'];
        if ($data['status'] === 'success') {
            
            $amount = $data['amount'];
            $order_id = $data['order_id'];
            $user_id = explode('_', $order_id)[0];

            try {
                donate::control_uuid($input_sign, get_called_class());
            } catch (\Throwable $e) {
                echo json_encode(['status' => false, 'msg' => 'UUID control failed']);
                return;
            }
            
            try {
                $amount = donate::currency($amount, $data['currency']);
            } catch (\Throwable $e) {
                echo json_encode(['status' => false, 'msg' => 'Currency conversion failed']);
                return;
            }

            try {
                self::telegramNotice(user::getUserId($user_id), $data['amount'], $data['currency'], $amount, get_called_class());
            } catch (\Throwable $e) {
               
            }

            try {
                user::getUserId($user_id)->donateAdd($amount)
                ->AddHistoryDonate(amount: $amount, pay_system: get_called_class(), input: $inputJSON );
            } catch (\Throwable $e) {
                echo json_encode(['status' => false, 'msg' => 'Failed to add funds']);
                return;
            }

            try {
                donate::addUserBonus($user_id, $amount);
            } catch (\Throwable $e) {
                
            }

            echo json_encode(['status' => true]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Payment not successful']);
        }
    }

}
