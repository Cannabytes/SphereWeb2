<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;
use Stripe\Checkout\Session;

class stripe extends \Ofey\Logan22\model\donate\pay_abstract
{

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    private array $allowIP = [
    ];

    public static function inputs(): array
    {
        return [
            'secret_key' => '',
            'webhook_secret_key' => '',
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

        $sum = intval($donate->getSphereCoinCost() >= 1 ? ($_POST['count'] * ($donate->getRatioUSD() / $donate->getSphereCoinCost())) : ($_POST['count'] * ($donate->getRatioUSD() * $donate->getSphereCoinCost())));

        try {
            \Stripe\Stripe::setApiKey(self::getConfigValue('secret_key')); // Замените YOUR_SECRET_KEY своим секретным ключом Stripe
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $sum,
                            'product_data' => [
                                'name' => 'Donation',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'user_id' => user::self()->getId(),
                    ],
                ],
                'mode' => 'payment',
                'success_url' => \Ofey\Logan22\component\request\url::host("/balance"),
                'cancel_url' => \Ofey\Logan22\component\request\url::host("/balance"),
            ]);
            echo $session['url'];
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    //Получение информации об оплате
    function webhook(): void
    {
        if (!(config::load()->donate()->getDonateSystems('stripe')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }
        $input = file_get_contents("php://input");
        if(!$input){
            return;
        }
        file_put_contents(__DIR__ . '/debug.php', '<?php _REQUEST: ' . print_r($input, true) . PHP_EOL, FILE_APPEND);
        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        try {
            $event = \Stripe\Webhook::constructEvent($input, $_SERVER['HTTP_STRIPE_SIGNATURE'], self::getConfigValue('webhook_secret_key'));
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo 'Ошибка проверки подписи: ' . $e->getMessage();
            exit();
        }
        \Stripe\Stripe::setApiKey(self::getConfigValue('secret_key'));

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $id = $event->data->id;
                $paymentIntent = $event->data->object;
                $metadata = $paymentIntent->metadata;
                $user_id = $metadata['user_id'];
                $amount = $paymentIntent->amount / 100; // перевод в доллары, потому что приходит по умолчанию в центах
                $currency = $paymentIntent->currency;

                donate::control_uuid($id, get_called_class());
                $amount = donate::currency($amount, $currency);
                self::telegramNotice(user::getUserId($user_id), $paymentIntent->amount / 100, $currency, $amount, get_called_class());
                user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
                donate::addUserBonus($user_id, $amount);
                echo 'YES';
                break;
            default:
                echo 'No';
                break;
        }

    }

}
