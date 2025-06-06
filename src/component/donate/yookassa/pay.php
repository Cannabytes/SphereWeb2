<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class yookassa extends \Ofey\Logan22\model\donate\pay_abstract {

    private string $currency = 'RUB';

    protected static string $name = 'YooKassa';

    protected static array $country = ['ru'];

    protected static string $currency_default = 'RUB';

	private string $shopId = '';
	
	private string $secretKey = '';

    public static function inputs(): array
    {
        return [
            'shopId' => '',
            'secretKey' => '',
        ];
    }

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для администратора
    protected static bool $forAdmin = false;

    protected static array $description = [
        "ru" => "Система yookassa [Россия, Беларусь]",
        "en" => "Pay system yookassa [Россия, Беларусь]",
    ];


    /*
     * Список IP адресов, от которых может прити уведомление от платежной системы.
     */
    private array $allowIP = [
        '185.71.76.0/27',
        '185.71.77.0/27',
        '77.75.153.0/25',
		'77.75.156.11',
		'77.75.156.35',
		'77.75.154.128/25',
		'2a02:5180::/32',
    ];


    /**
     * @return void
     * Генерируем ссылку для перехода на сайт оплаты
     */
    function create_link(): void {
        user::self()->isAuth() ?: board::notice(false, lang::get_phrase(234));
        filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT) ?: board::notice(false, "Введите сумму цифрой");
        donate::isOnlyAdmin(self::class);
        if(empty($this->shopId) OR empty($this->secretKey)){
            board::error('No set token api');
        }
        $donate = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId())->donate();
        if ($_POST['count'] < $donate->getMinSummaPaySphereCoin()) {
            board::notice(false, "Минимальное пополнение: " . $donate->getMinSummaPaySphereCoin());
        }
        if ($_POST['count'] > $donate->getMaxSummaPaySphereCoin()) {
            board::notice(false, "Максимальная пополнение: " . $donate->getMaxSummaPaySphereCoin());
        }

        $currency = config::load()->donate()->getDonateSystems(get_called_class())?->getCurrency() ?? self::getCurrency();
        $amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatio($currency), $donate->getSphereCoinCost());

        $userId = auth::get_id();
		$params = [
			'metadata' => [
				'userId' => $userId
			],
			'amount' => [
				'value' => $amount,
				'currency' => $currency
			],
			'capture' => true,
			'confirmation' => [
				'type' => 'redirect',
				'return_url' => \Ofey\Logan22\component\request\url::host("/donate/pay"),
			],
		];
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://api.yookassa.ru/v3/payments',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => json_encode( $params ),
			CURLOPT_HTTPHEADER => [
				'Authorization: Basic ' . base64_encode( $this->shopId . ':' . $this->secretKey ),
				'Idempotence-Key: ' . uniqid(),
				'Content-Type: application/json'
			]
		]);
		$response = json_decode( curl_exec( $ch ), true );
		curl_close($ch);
		if ( $response['confirmation']['confirmation_url'] )
			die( $response['confirmation']['confirmation_url'] );
		board::notice( false, $response['description'] );
    }

    //Получение информации об оплате
    function webhook(): void {
        if (!(config::load()->donate()->getDonateSystems('yookassa')?->isEnable() ?? false)) {
            echo 'disabled';
            exit;
        }
        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
        if(empty($this->shopId) OR empty($this->secretKey)){
            board::error('No set token api');
        }
		$request = json_decode( file_get_contents( 'php://input' ), true );
		
		$event 		= $request['event'] 						?? '';
		$id 	    = $request['object']['id'] 			    	?? '';
		$status 	= $request['object']['status'] 				?? '';
		$amount		= $request['object']['amount']['value']		?? 0;
		$currency	= $request['object']['amount']['currency']	?? $this->currency;
		$userId 	= $request['object']['metadata']['userId']	?? -1;
		
		if ( $event <> 'payment.succeeded' || $status <> 'succeeded' ) {
			header( 'HTTP/1.1 400 Bad request', true, 400 );
			die( 'Bad request' );
		}
        donate::control_uuid($id, get_called_class());

        $amount = donate::currency($amount, $currency);
        auth::change_donate_point((int) $userId, $amount, get_called_class());
    }
}
 