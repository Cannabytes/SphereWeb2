<?php

namespace Ofey\Logan22\component\telegram;

class telegram
{
    private string $token;
    private string $apiUrl;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot$this->token/";
    }


    /**
     * Отправляет сообщение в указанный чат
     * @param string $chatId ID чата, куда отправляется сообщение
     * @param string $message Сообщение для отправки (может содержать HTML)
     * @param string $parseMode Режим форматирования сообщения (по умолчанию HTML)
     * @return bool Успешно ли отправлено сообщение
     */
    public function sendMessage(string $chatId, string $message, string $parseMode = 'HTML'): bool
    {
        $url = $this->apiUrl . "sendMessage";
        $postData = [
            'chat_id'    => $chatId,
            'text'       => $message,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];

        $response = $this->sendRequest($url, $postData);

        if (isset($response['ok']) && $response['ok'] === true) {
            return true;
        }

        return false;
    }


    /**
     * Получает обновления от Telegram (используется для получения chat_id)
     * @return array Массив с обновлениями
     */
    public function getUpdates(): array
    {
        $url = $this->apiUrl . "getUpdates";
        return $this->sendRequest($url);
    }

    /**
     * Отправляет запрос к Telegram API
     * @param string $url URL для запроса
     * @param array|null $postData Данные POST-запроса (если есть)
     * @return array Ответ от API в виде массива
     */
    private function sendRequest(string $url, array $postData = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "Ошибка CURL: " . curl_error($ch);
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}


