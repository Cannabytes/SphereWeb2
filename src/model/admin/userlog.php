<?php

namespace Ofey\Logan22\model\admin;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class userlog {


    //TODO: Устарело. Использовать user->addLog
    public static function add($type, $phrase, $variable = [], mixed $request = ""){
    }

    //Для указания user_id и server_id, в основном это нужно для внешних запросов, к примеру от платежных систем
    public static function expanded($user_id, $server_id, $type, $phrase, $variable = [], mixed $request = ""){
        $time = time::mysql();
        $variable = json_encode($variable, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $request = json_encode($_POST, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $query = "INSERT INTO `logs_all` (`user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        sql::run($query, [
            $user_id,
            $time,
            $type,
            $phrase,
            $variable,
            $server_id,
            $request,
        ]);
    }

    /**
     * Логирование webhook запросов со всеми данными (SERVER, POST, GET)
     * Используется для логирования входящих данных от платежных систем и внешних сервисов
     */
    public static function logWebhookRequest($user_id, $server_id, $phrase, $variable = []): void
    {
        $time = time::mysql();
        $variable_json = json_encode($variable, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        
        // Собираем все входные данные
        $webhook_data = [
            'server' => self::sanitizeServerData($_SERVER),
            'get' => $_GET ?? [],
            'post' => self::sanitizePostData($_POST ?? []),
            'raw_body' => file_get_contents('php://input') ?: null,
        ];
        
        $request_json = json_encode($webhook_data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        
        // Получаем информацию о файле и строке откуда была вызвана функция
        $trace = debug_backtrace()[1] ?? debug_backtrace()[0];
        $file = $trace['file'] ?? '';
        $line = $trace['line'] ?? 0;
        
        $query = "INSERT INTO `logs_all` (`user_id`, `time`, `type`, `phrase`, `variables`, `server_id`, `request`, `method`, `action`, `file`, `line`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        sql::run($query, [
            $user_id,
            $time,
            'webhook',
            $phrase,
            $variable_json,
            $server_id,
            $request_json,
            $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            $_SERVER['REQUEST_URI'] ?? '',
            $file,
            $line,
        ]);
    }

    /**
     * Очищает чувствительные данные из SERVER заголовков
     */
    private static function sanitizeServerData(array $server): array
    {
        $allowed_keys = [
            'REQUEST_METHOD',
            'REQUEST_URI',
            'SERVER_NAME',
            'SERVER_PORT',
            'REMOTE_ADDR',
            'HTTP_HOST',
            'HTTP_USER_AGENT',
            'HTTP_REFERER',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'CONTENT_TYPE',
            'CONTENT_LENGTH',
            'REQUEST_TIME',
            'SCRIPT_NAME',
            'QUERY_STRING',
        ];
        
        $sanitized = [];
        foreach ($allowed_keys as $key) {
            if (isset($server[$key])) {
                $sanitized[$key] = $server[$key];
            }
        }
        return $sanitized;
    }

    /**
     * Очищает чувствительные данные из POST данных
     */
    private static function sanitizePostData(array $post): array
    {
        $sensitive_keys = ['password', 'secret', 'token', 'api_key', 'sk_', 'pk_', 'g-recaptcha-response'];
        
        $sanitized = $post;
        foreach ($sensitive_keys as $key) {
            foreach ($sanitized as $k => $v) {
                if (stripos($k, $key) !== false) {
                    $sanitized[$k] = '*_REMOVED_*';
                }
            }
        }
        return $sanitized;
    }

}