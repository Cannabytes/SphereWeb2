<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\config\config;

class telegram
{

    /** POST
     * /telegram/notice/test
     */
    static public function testSendNotice(): void
    {
        $tokenAPI = $_POST['tokenApi'] ?? "";
        $bot = new \Ofey\Logan22\component\telegram\telegram($tokenAPI);
        $chatId = \Ofey\Logan22\controller\admin\telegram::getChatID($tokenAPI);
        if ($chatId != "") {
            $message = "Привет, это тестовое сообщение от SphereWeb";
            if ($bot->sendMessage((string)$chatId, $message)) {
                board::success("Сообщение успешно отправлено");
            } else {
                board::error("Ошибка при отправке сообщения");
            }
        } else {
            board::error("Не определен chat_id");
        }
    }

    static public function sendTelegramMessage($message = "")
    {
        if (!config::load()->notice()->isTelegramEnable() or !config::load()->notice()->getTechnicalSupport()){
            return;
        }

        $bot = new \Ofey\Logan22\component\telegram\telegram(config::load()->notice()->getTelegramTokenApi());
        $chatId = config::load()->notice()->getTelegramChatID();
        if ($chatId != "") {
            if ($bot->sendMessage($chatId, $message)) {
            }
        }

    }

    static public function getChatID($token = ""): string|int
    {
        $url = "https://api.telegram.org/bot{$token}/getUpdates";

        // Отправляем GET-запрос к API Telegram
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return "";
        }

        curl_close($ch);

        // Декодируем JSON-ответ
        $data = json_decode($response, true);
        if (!isset($data['result']) || empty($data['result'])) {
            return "";
        }

        // Проверяем первый элемент массива обновлений
        $firstUpdate = $data['result'][0] ?? null;
        if (isset($firstUpdate['message']['chat']['id'])) {
            return (int)$firstUpdate['message']['chat']['id'];
        }

        return "";
    }

}