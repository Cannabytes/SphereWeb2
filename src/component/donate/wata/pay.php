<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class wata extends \Ofey\Logan22\model\donate\pay_abstract
{

    protected static string $webhook = "/donate/webhook/wata";

    //Включена/отключена платежная система
    protected static bool $enable = true;

    protected static string $name = 'Wata';

    protected static array $country = ['ru'];

    //Включить только для true
    protected static bool $forAdmin = false;

    private array $allowIP = [ 
    ];

    public static function inputs(): array
    {
        return [
          'access_token'    => '',
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
        $order_amount = self::sphereCoinSmartCalc($_POST['count'], $donate->getRatioRUB(), $donate->getSphereCoinCost());
		$ch = curl_init();
		$order_amount = number_format((float)$order_amount, 2, '.', '');
		$data = [
			"amount"      => $order_amount,   
			"currency"    => "RUB",   
			"orderId" => (string) user::self()->getId(),
			"successRedirectUrl" => \Ofey\Logan22\component\request\url::host("/donate/pay"),
			"failRedirectUrl" => \Ofey\Logan22\component\request\url::host("/donate/pay"),
		];

		curl_setopt_array($ch, [
			CURLOPT_URL            => "https://api.wata.pro/api/h2h/links", 
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => [
				"Content-Type: application/json", 
				"Authorization: Bearer " . self::getConfigValue('access_token')
			],
			CURLOPT_POSTFIELDS     => json_encode($data), 
			CURLOPT_RETURNTRANSFER => true
		]);

		// Выполнение запроса и получение ответа
		$response = curl_exec($ch);

		// Проверка на ошибки запроса
		if ($response === false) {
			$error = curl_error($ch);
				board::notice(false, "cURL Error.");
		} else {
			// Декодирование JSON-ответа API
			$result = json_decode($response, true);
			if(isset($result['error'])){
				board::notice(false, $result['error']['message']);
			}
			if (json_last_error() === JSON_ERROR_NONE) {
				// Успешный разбор JSON – получаем ссылку на оплату
				$paymentUrl = $result['url'] ?? null;
				if ($paymentUrl) {
					echo $paymentUrl;
				} else {
					board::notice(false, "API response received, but 'url' not found.");
				}
			} else {
				board::notice(false, "Invalid JSON response.");
			}
		}

		// Завершение сеанса cURL
		curl_close($ch);


    }

    function webhook(): void {
		if (!(config::load()->donate()->getDonateSystems('wata')?->isEnable() ?? false)) {
			echo 'disabled';
			exit;
		}

		$input = file_get_contents("php://input");
		$this->logRequest($input);
		\Ofey\Logan22\component\request\ip::allowIP($this->allowIP);
		
		if (!$this->checkSign($input)) {
			die("Sign err");
		}
		
		$request = json_decode($input, true);
		
		if ($request['transactionStatus'] !== "Paid") {
			echo 'no paid';
			return;
		}
		
		$currency = $request['currency'];
		$userId = $request['orderId'];
		$amount = donate::currency($request['amount'], $currency);
		
		donate::control_uuid($request['transactionId'], get_called_class(), $input);
		self::telegramNotice(user::getUserId($userId), $request['amount'], $currency, $amount, get_called_class());
		
		$user = user::getUserId($userId);
		$user->donateAdd($amount)->AddHistoryDonate(amount: $amount, pay_system: get_called_class());
		donate::addUserBonus($userId, $amount);
		
		echo 'YES';
	}

	private function logRequest($input): void
	{
		file_put_contents(__DIR__ . '/input.php', '<?php // REQUEST: ' . print_r($input, true) . PHP_EOL, FILE_APPEND);
		file_put_contents(__DIR__ . '/server.php', '<?php // REQUEST: ' . print_r($_SERVER, true) . PHP_EOL, FILE_APPEND);
	}

	private function checkSign($rawBody): bool
	{
		if (empty($rawBody)) {
			$this->sendErrorResponse(400, 'Пустое тело запроса');
		}
		
		$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
		if (!$signature) {
			$this->sendErrorResponse(400, 'Не передана подпись X-Signature');
		}
		
		$publicKey = $this->getWataPublicKey();
		$publicSignature = openssl_get_publickey($publicKey);
		$signatureBytes = base64_decode($signature);
		
		return openssl_verify($rawBody, $signatureBytes, $publicSignature, OPENSSL_ALGO_SHA512) === 1;
	}

	private function getWataPublicKey(): string
	{
		$publicKeyUrl = 'https://api.wata.pro/api/h2h/public-key';
		$ch = curl_init($publicKeyUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$error = curl_error($ch);
			curl_close($ch);
			$this->sendErrorResponse(500, 'Ошибка при загрузке публичного ключа: ' . $error);
		}
		
		curl_close($ch);
		
		$publicKeyData = json_decode($response, true);
		if (!isset($publicKeyData['value'])) {
			$this->sendErrorResponse(500, 'Неверный формат ответа API для публичного ключа');
		}
		
		return $publicKeyData['value'];
	}

	private function sendErrorResponse(int $code, string $message): void
	{
		http_response_code($code);
		file_put_contents(__DIR__ . '/err.php', '<?php // err: ' . $message . PHP_EOL, FILE_APPEND);
		exit;
	}
 
}
