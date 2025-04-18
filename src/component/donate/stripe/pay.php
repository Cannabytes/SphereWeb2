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

        // Вычисляем стоимость в долларах с сохранением дробной части
        $sumUSD = $donate->getSphereCoinCost() >= 1
            ? ($_POST['count'] * ($donate->getRatioUSD() / $donate->getSphereCoinCost()))
            : ($_POST['count'] * ($donate->getRatioUSD() * $donate->getSphereCoinCost()));

        // Проверка на минимальную сумму (50 центов)
        if ($sumUSD < 0.5) {
            board::notice(false, "Минимальная сумма для Stripe: $0.50");
        }

        // Конвертация в центы для Stripe и явное преобразование в целое число
        $sumCents = (int)round($sumUSD * 100);

        try {
            \Stripe\Stripe::setApiKey(self::getConfigValue('secret_key')); // Замените YOUR_SECRET_KEY своим секретным ключом Stripe
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $sumCents,
                            'product_data' => [
                                'name' => 'Donation',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'user_id' => user::self()->getId(),
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
            case 'checkout.session.completed':
                $session = $event->data->object;
                $user_id = $session->metadata->user_id ?? null;

                if (!$user_id) {
                    // Если нет user_id в метаданных сессии, попробуем получить его из payment_intent
                    if ($session->payment_intent) {
                        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                        $user_id = $paymentIntent->metadata->user_id ?? null;
                    }
                }

                if (!$user_id) {
                    http_response_code(400);
                    echo 'Не найден user_id в метаданных';
                    exit();
                }

                $amount = $session->amount_total / 100; // перевод в доллары, потому что приходит по умолчанию в центах
                $currency = $session->currency;

                donate::control_uuid($session->id, get_called_class());
                $amount = donate::currency($amount, $currency);
                self::telegramNotice(user::getUserId($user_id), $amount, $currency, $amount, get_called_class());
                user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: get_called_class());
                donate::addUserBonus($user_id, $amount);
                echo 'YES';
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $user_id = $paymentIntent->metadata->user_id ?? null;

                if (!$user_id) {
                    http_response_code(400);
                    echo 'Не найден user_id в метаданных payment_intent';
                    exit();
                }

                $amount = $paymentIntent->amount / 100; // перевод в доллары, потому что приходит по умолчанию в центах
                $currency = $paymentIntent->currency;

                donate::control_uuid($paymentIntent->id, get_called_class());
                $amount = donate::currency($amount, $currency);
                self::telegramNotice(user::getUserId($user_id), $amount, $currency, $amount, get_called_class());
                user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: get_called_class());
                donate::addUserBonus($user_id, $amount);
                echo 'YES';
                break;

            default:
                echo 'No';
                break;
        }
    }
}