<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\user\user;

class morune extends \Ofey\Logan22\model\donate\pay_abstract
{

    //Включена/отключена платежная система
    protected static bool $enable = true;

    //Включить только для true
    protected static bool $forAdmin = false;

    private array $allowIP = [];

    public static function inputs(): array
    {
        return [
          'shop_id'    => '',
          'secret_key' => '',
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
        $order_amount = $_POST['count'] * ($donate->getRatioRUB() / $donate->getSphereCoinCost());
        $shop_id      = self::getConfigValue('shop_id');
        $email        = user::self()->getEmail();
        $secret_word  = self::getConfigValue('secret_key');
        $currency     = "RUB";
        $order_id     = uniqid();
        $sign         = md5($shop_id . ':' . $order_amount . ':' . $secret_word . ':' . $currency . ':' . $order_id);
        $params       = [
          'amount'        => $order_amount,
          'order_id'      => $order_id,
          'email'         => $email,
          'currency'      => $currency,
          'shop_id'       => $shop_id,
          "custom_fields" => ["order" => user::self()->getId()],
        ];
        $headers      = [
          'Accept: application/json',
          'Content-Type: application/json',
          'x-api-key: ' . self::getConfigValue('secret_key'),
        ];
        $ch           = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.morune.com/invoice/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_object = json_decode($response);
        if ($response_object->status === 200 && $response_object->status_check) {
            $url = $response_object->data->url;
            echo $url;
        } else {
            echo "Request failed: " . $response_object->error;
        }
    }

    function webhook(): void
    {
        $jsonTxt     = file_get_contents('php://input');
        $requestData = json_decode($jsonTxt, true);
        file_put_contents(__DIR__ . '/debug.log', '_REQUEST: ' . print_r($requestData, true) . PHP_EOL, FILE_APPEND);

//        \Ofey\Logan22\component\request\ip::allowIP($this->allowIP);

        $status = $requestData['status'] ?? null;

        $order = $requestData['custom_fields']['order'] ?? null;

        $user_id = $order !== null ? (int)$order : null;

        // Получаем сигнатуру из заголовка 'x-api-sha256-signature'
        $signature = $_SERVER['HTTP_X_API_SHA256_SIGNATURE'] ?? '';

        if ($status == "success") {
            $invoice = $this->getInvoiceInfo(
              self::getConfigValue('secret_key'),
              $requestData['invoice_id'],
              self::getConfigValue('shop_id')
            );
            file_put_contents(__DIR__ . '/debug_invoice.log', '_REQUEST: ' . print_r($invoice, true) . PHP_EOL, FILE_APPEND);
            $invoice = $invoice['data'];
            $amount   = $invoice['invoice_amount'];
            $currency = $invoice['currency'];
            $amount   = donate::currency($amount, $currency);
            \Ofey\Logan22\model\admin\userlog::add("user_donate", 545, [$amount, $currency, get_called_class()]);
            user::getUserId($user_id)->donateAdd($amount)->AddHistoryDonate($amount, "Пожертвование morune", get_called_class());
            donate::addUserBonus($user_id, $amount);
            echo 'YES';
        } else {
            echo 'Платеж не принят';
        }
    }

    function getInvoiceInfo(string $apiKey, string $invoiceId, string $shopId): array
    {
        $url     = "https://api.morune.com/invoice/info";
        $headers = [
          "accept: application/json",
          "x-api-key: {$apiKey}",
        ];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url?invoice_id={$invoiceId}&shop_id={$shopId}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

}
