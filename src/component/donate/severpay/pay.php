<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class severpay extends \Ofey\Logan22\model\donate\pay_abstract
{

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = false;

    protected static string $name = 'SeverPay';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

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
    function create_link(): void
    {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        donate::isOnlyAdmin(self::class);

        if (empty(self::getConfigValue('mid')) or empty(self::getConfigValue('token'))) {
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
        $order_id = user::self()->getId() . '_' . time() . '_' . bin2hex(random_bytes(16));
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

    // The old URL remains an alias for the plugin's single webhook handler.
    function webhook(): void
    {
        $legacyMerchant = [];
        try {
            $legacySystem = config::load()->donate()->getDonateSystems('severpay');
            if ($legacySystem?->isEnable()) {
                $legacyMerchant[] = [
                    'mid' => self::getConfigValue('mid'),
                    'token' => self::getConfigValue('token'),
                    'currency' => $legacySystem->getCurrency() ?? self::getCurrency(),
                ];
            }
        } catch (\Throwable $e) {
            error_log('SeverPay legacy webhook configuration failed: ' . $e->getMessage());
        }
        require_once __DIR__ . '/../../plugins/severpay/severpay.php';
        (new \severpay\severpay())->webhook($legacyMerchant);
    }
}
