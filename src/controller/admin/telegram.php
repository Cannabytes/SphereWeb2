<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\user\user;

class telegram
{

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

    /**
     * Обрабатывает запрос на создание тем в форум-группе Telegram
     * и возвращает их ID. Если тема с таким названием уже существует,
     * возвращает её ID без повторного создания
     *
     * POST /telegram/notice/create/topics
     */
    static public function createNoticeTopics(): void
    {
        // Получаем данные из POST запроса
        $tokenAPI = $_POST['tokenApi'] ?? "";
        $chatId = $_POST['chatID'] ?? "";
        $topics = $_POST['topics'] ?? [];

        // Проверяем наличие необходимых данных
        if (empty($tokenAPI) || empty($chatId) || empty($topics)) {
            echo json_encode([
                'success' => false,
                'message' => 'Не указаны обязательные параметры (tokenApi, chatID, topics)'
            ]);
            return;
        }

        // Создаем темы и сохраняем их ID
        $createdTopics = [];
        $errors = [];

        foreach ($topics as $key => $topicName) {
            $topicResult = self::createForumTopic($tokenAPI, $chatId, $topicName);

            if ($topicResult) {
                $createdTopics[$key] = $topicResult['message_thread_id'];
            } else {
                $errors[] = "Не удалось создать тему '{$topicName}'";
            }
        }

        // Формируем и возвращаем ответ
        if (!empty($createdTopics)) {
            // Если есть успешно созданные темы
            echo json_encode([
                'success' => true,
                'message' => empty($errors) ? 'Все темы успешно созданы' : 'Некоторые темы не были созданы',
                'topics' => $createdTopics,
                'errors' => $errors
            ]);
        } else {
            // Если ни одна тема не была создана
            echo json_encode([
                'success' => false,
                'message' => 'Не удалось создать ни одной темы',
                'errors' => $errors
            ]);
        }
    }

    /**
     * Получает список тем в форум-группе
     *
     * @param string $botToken Токен бота
     * @param string|int $chatId ID чата группы
     * @return array|false Массив тем или false при ошибке
     */
    static public function getForumTopicList($botToken, $chatId): array|false
    {
        $url = "https://api.telegram.org/bot{$botToken}/getForumTopics";

        $data = [
            'chat_id' => $chatId
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return false;
        }

        $result = json_decode($response, true);

        if (isset($result['ok']) && $result['ok'] && isset($result['result']['topics'])) {
            return $result['result']['topics'];
        } else {
            error_log("Ошибка при получении списка тем: " . json_encode($result));
            return false;
        }
    }

    /**
     * Создает новую тему в форум-группе Telegram
     *
     * @param string $botToken Токен бота
     * @param string|int $chatId ID чата группы
     * @param string $name Название темы
     * @param int|null $iconColor Цвет иконки (опционально)
     * @return array|false Данные созданной темы или false при ошибке
     */
    static private function createForumTopic($botToken, $chatId, $name, $iconColor = null): array|false
    {
        $url = "https://api.telegram.org/bot{$botToken}/createForumTopic";

        $data = [
            'chat_id' => $chatId,
            'name' => $name
        ];

        if ($iconColor !== null) {
            $data['icon_color'] = $iconColor;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return false;
        }

        $result = json_decode($response, true);

        if (isset($result['ok']) && $result['ok']) {
            return $result['result'];
        } else {
            error_log("Ошибка при создании темы: " . json_encode($result));
            return false;
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