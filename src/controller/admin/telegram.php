<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\user\user;

class telegram
{

    /** POST
     * /telegram/notice/test
     */
    static public function testSendNotice(): void
    {
        $tokenAPI = $_POST['tokenApi'] ?? "";
        $chatId = $_POST['chatID'] ?? "";
        $bot = new \Ofey\Logan22\component\telegram\telegram($tokenAPI);
        if ($chatId == "") {
            $chatId = \Ofey\Logan22\controller\admin\telegram::getChatID($tokenAPI);
        }
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

    static public function testGetThread()
    {
        $tokenAPI = $_POST['tokenApi'] ?? "";

        $url = "https://api.telegram.org/bot{$tokenAPI}/getUpdates";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return "";

        $response = json_decode($response, true);
        if(end( $response['result'])['message']['message_thread_id']) {
            echo json_encode([
                'id' => end( $response['result'])['message']['message_thread_id'],
                'name' => end( $response['result'])['message']['reply_to_message']['forum_topic_created']['name'],
            ]);
            exit;
        }
        board::error("no find thread");
    }


    static public function getChatID($token = ""): string|int
    {
        $url = "https://api.telegram.org/bot{$token}/getUpdates";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return "";
        }

        curl_close($ch);

        // Декодируем JSON-ответ
        $data = json_decode($response, true);
        if (empty($data['result'])) {
            return "";
        }
        // Проверяем первый элемент массива обновлений
        $firstUpdate = $data['result'][0] ?? null;
        if (isset($firstUpdate['message']['chat']['id'])) {
            return (int)$firstUpdate['message']['chat']['id'];
        }

        return "";
    }

    static public function sendTelegramMessage($message = "", $threadId = null, $serverInfo = true): void
    {
        if (!config::load()->notice()->isTelegramEnable()) {
            return;
        }
        if($serverInfo) {
            $server = \Ofey\Logan22\model\server\server::getServer(user::self()->getServerId());
            $userInfo = \Ofey\Logan22\model\user\user::getUserId();
            $link = \Ofey\Logan22\component\request\url::host("/admin/user/info/" . $userInfo->getId());
            $message .= "\n\nServer: #{$server->getName()} | Chronicle: {$server->getChronicle()} | Rate EXP: {$server->getRateExp()} | <a href='{$link}'>UserInfo</a>";
        }

        $bot = new \Ofey\Logan22\component\telegram\telegram(config::load()->notice()->getTelegramTokenApi());
        $chatId = config::load()->notice()->getTelegramChatID();
        if ($chatId != "") {
            $bot->sendMessage($chatId, $message, $threadId);
        }

    }

}