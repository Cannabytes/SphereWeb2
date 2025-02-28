<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\bonus\bonus;
use Ofey\Logan22\model\user\user;

class unitpay extends \Ofey\Logan22\model\donate\pay_abstract {

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = false;

    protected static array $description = [
        "ru" => "Unitpay [Россия, Беларусь]",
        "en" => "Unitpay [Russia, Belarus]",
    ];

    private string $currency_default = 'RUB';

    public static function inputs(): array
    {
        return [
            'publicKey' => '',
            'secretKey' => '',
        ];
    }

	private string $publicKey = '';
	
	private string $secretKey = '';
	
	private string $desc = 'Консультационные услуги';

    //Включить тест режим
    private const TESTMODE = false;
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
        $order_amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatioRUB(), $donate->getSphereCoinCost());

		$account = auth::get_id();
		
		$signature = hash( 'sha256', $account . '{up}' . $this->currency_default . '{up}' . $this->desc . '{up}' . $order_amount . '{up}' . $this->secretKey );

		$params = [
			'account' => $account,
			'currency' => $this->currency_default,
			'desc' => $this->desc,
			'sum' => $order_amount,
			'paymentType' => 'card',			
			'cashItems' => base64_encode(json_encode([
				[
					'name' => $this->desc,
					'count' => 1,
					'price' => $order_amount,
				]
			])),
			'customerEmail' => auth::get_email(),			
			'projectId' => $this->publicKey,
			'resultUrl' => \Ofey\Logan22\component\request\url::host('/donate'),
			'secretKey' => self::TESTMODE ? self::$test_key : $this->secretKey,
			'signature' => $signature
		];
		
		if ( self::TESTMODE ) {
			$params['test'] = 1;
			$params['login'] = self::$test_email;
		}
		
		$requestUrl = 'https://unitpay.ru/api?' . http_build_query([
			'method' => 'initPayment',
			'params' => $params
		], null, '&', PHP_QUERY_RFC3986);
		
		$response = json_decode( file_get_contents( $requestUrl ), true );

		if ( $response['error']['message'] ) {
			board::notice( false, $response['error']['message'] );
		}

		if ($response['result']['redirectUrl']) {
			echo $response['result']['redirectUrl'];
		}
    }

    //Получение информации об оплате
    function webhook(): void {
        if (!(config::load()->donate()->getDonateSystems('unitpay')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }
        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

		$method = $_REQUEST['method'] ?? '';
		$userId = $_REQUEST['params']['account'] ?? -1;
		$amount = $_REQUEST['params']['orderSum'] ?? '';
		$crc = $_REQUEST['params']['signature'] ?? '';
		
		unset( $_REQUEST['params']['signature'] );
		ksort( $_REQUEST['params'] );
		$params = implode( '{up}', $_REQUEST['params'] );
		
		$sign = hash( 'sha256', $method . '{up}' . $params . '{up}' . $this->secretKey );
		
		header( 'Content-type: application/json' );
		if ( $crc <> $sign ) {
			die(json_encode([
				'error' => [ 'message' => 'Wrong signature!' ]
			]));
        }
		
		if ( $method <> 'pay' ) {
			die(json_encode([
				'result' => [ 'message' => "Запрос [{$method}] успешно обработан!" ]
			]));
		}

        if ( !is_numeric( $userId ) ) {
			die(json_encode([
				'error' => [ 'message' => 'Bad request!' ]
			]));
		}

        donate::control_uuid($sign . "__" . mt_rand(0, 999999999), get_called_class());

        $amount = donate::currency($amount, $this->currency_default);

        self::telegramNotice(user::getUserId($userId), $_POST['AMOUNT'], $this->currency_default, $amount, get_called_class());
        \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$_POST['AMOUNT'], $this->currency_default, get_called_class()]);
        user::getUserId($userId)->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system:  get_called_class());
        donate::addUserBonus($userId, $amount);

        echo 'YES';
    }
}
 