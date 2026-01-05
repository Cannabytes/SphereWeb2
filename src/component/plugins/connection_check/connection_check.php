<?php

namespace Ofey\Logan22\component\plugins\connection_check;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server as SphereServer;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\controller\config\config;

class connection_check
{
    public function show()
    {
        validation::user_protection("admin");
        tpl::displayPlugin("connection_check/tpl/index.html");
    }

    public function process()
    {
        validation::user_protection("admin");

        $host = $_POST['host'] ?? '';
        $port = $_POST['port'] ?? '3306';
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';
        $check_ports = $_POST['check_ports'] ?? '7777,2016';

        if (empty($host)) {
            $msg = lang::get_phrase('connection_check.error.no_host');
            board::error($msg);
            echo json_encode(['error' => $msg]);
            return;
        }

        $portsArray = array_map('trim', explode(',', $check_ports));

        // Limit number of ports a user can request at once (protect from abuse)
        if (count($portsArray) > 5) {
            $msg = lang::get_phrase('connection_check.error.too_many_ports');
            board::error($msg);
            echo json_encode(['error' => $msg]);
            return;
        }

        $data = [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'check_ports' => $portsArray,
        ];
        // Быстрая проверка доступности целевого хоста (DB) и Sphere API с этого сервера
        $checkTimeout = 2; // seconds
        $targetReachable = false;
        $apiReachable = false;

        // Проверяем целевой хост
        $fp = @fsockopen($host, (int)$port, $errno, $errstr, $checkTimeout);
        if ($fp) {
            $targetReachable = true;
            fclose($fp);
        }

        // Проверяем Sphere API из конфига
        try {
            $apiHost = config::load()->sphereApi()->getIp();
            $apiPort = config::load()->sphereApi()->getPort();
            $fp2 = @fsockopen($apiHost, (int)$apiPort, $errno2, $errstr2, $checkTimeout);
            if ($fp2) {
                $apiReachable = true;
                fclose($fp2);
            }
        } catch (\Throwable $e) {
            $apiReachable = false;
        }

        // Если API недоступен с этого сервера, возвращаем понятную ошибку сразу
        if (!$apiReachable) {
            $msg = lang::get_phrase('connection_check.error.api_unreachable_from_server');
            board::error($msg);
            echo json_encode(['error' => $msg, 'api_reachable' => false, 'target_reachable' => $targetReachable]);
            return;
        }

        // Устанавливаем таймаут для запросов к Sphere API
        SphereServer::setTimeout(25);

        // Отправляем POST-запрос на API и получаем структурированный ответ
        $apiResponseInstance = \Ofey\Logan22\component\sphere\server::sendCustom("/api/server/check/connection", $data);
        $apiResponse = $apiResponseInstance->getResponse();

        // Если нет ответа от API — пробуем отправить запрос напрямую (фоллбэк)
        if ($apiResponse === null || $apiResponse === false) {
            $sphereError = \Ofey\Logan22\component\sphere\server::isError();
            // Логируем первичную ошибку
            $msg = lang::get_phrase('connection_check.error.no_api_response_fallback');
            if ($sphereError) {
                $msg .= " (" . $sphereError . ")";
            }
            board::error($msg);

            // Собираем адрес Sphere API из конфига
            try {
                $link = config::load()->sphereApi()->getIp() . ':' . config::load()->sphereApi()->getPort();
            } catch (\Throwable $e) {
                echo json_encode(['error' => 'Не удалось определить адрес Sphere API']);
                return;
            }

            $fullUrl = $link . '/api/server/check/connection';
            if (!preg_match('/^https?:\/\//i', $fullUrl)) {
                $fullUrl = 'http://' . $fullUrl;
            }

            $ch = curl_init();
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $headers = [
                'Content-Type: application/json',
                'Authorization: BoberKurwa',
            ];

            // Добавим обязательные заголовки как в sendCustom
            $headers[] = "User-Id: 0";
            $server_id = \Ofey\Logan22\model\server\server::getLastServer()?->getId();
            if (isset($_SESSION['server_id'])) {
                $server_id = $_SESSION['server_id'];
            }
            $headers[] = "User-Server-Id: " . ($server_id ?? 0);
            $headers[] = "Token: " . SphereServer::getToken();

            $hostHeader = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
            $parsedHost = parse_url($hostHeader, PHP_URL_HOST) ?: $hostHeader;
            $parsedHost = preg_replace('/:\\d+$/', '', $parsedHost);
            $headers[] = "Domain: " . $parsedHost;

            $headers[] = 'Content-Length: ' . strlen($json);
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $resp = curl_exec($ch);
            $curlErr = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            // Если запрос не удался
            if ($resp === false || $resp === null) {
                $errMsg = lang::get_phrase('connection_check.error.fallback_failed') . ': ' . ($curlErr ?: lang::get_phrase('connection_check.error.empty_response'));
                board::error($errMsg);
                echo json_encode(['error' => lang::get_phrase('connection_check.error.no_api_response'), 'fallback_error' => $errMsg, 'http_code' => $info['http_code'] ?? null]);
                return;
            }

            // Вернём и распарсим сырый ответ для диагностики
            $decoded = json_decode($resp, true);
            if ($decoded === null) {
                error_log("Connection Check Plugin - Invalid JSON from API: " . $resp);
                echo json_encode(['error' => lang::get_phrase('connection_check.error.invalid_api_response'), 'raw_response' => $resp, 'http_code' => $info['http_code'] ?? null]);
                return;
            }

            // Нормализуем уведомления вида {type,message,ok:false}
            if (isset($decoded['type']) && isset($decoded['message'])) {
                echo json_encode(['error' => $decoded['message'], 'raw_response' => $resp, 'http_code' => $info['http_code'] ?? null]);
                return;
            }

            // Если в теле есть ошибка
            if (isset($decoded['error'])) {
                echo json_encode(['error' => $decoded['error'], 'raw_response' => $resp, 'http_code' => $info['http_code'] ?? null]);
                return;
            }

            // Успех — вернём ответ напрямую и добавим флаги и сырой ответ для диагностики
            if (is_array($decoded)) {
                $decoded['api_reachable'] = $apiReachable;
                $decoded['target_reachable'] = $targetReachable;
                $decoded['raw_response'] = $resp;
                $decoded['http_code'] = $info['http_code'] ?? null;
            }
            echo json_encode($decoded);
            return;
        }

        // Если API вернул уведомление в корне (например {type: "notice", ok:false, message: "..."})
        if (is_array($apiResponse) && isset($apiResponse['type']) && isset($apiResponse['message'])) {
            $msg = $apiResponse['message'];
            board::error($msg);
            echo json_encode(['error' => $msg]);
            return;
        }

        // Если sendCustom возвращает структуру с полем 'json' (обычная реализация в server::sendCustom)
        if (is_array($apiResponse) && isset($apiResponse['json']) && is_array($apiResponse['json'])) {
            $json = $apiResponse['json'];

            // Стандартная ошибка
            if (isset($json['error'])) {
                board::error($json['error']);
                echo json_encode(['error' => $json['error']]);
                return;
            }

            // Обработка уведомлений вида {"type":"notice","ok":false,"message":"..."}
            if (isset($json['type']) && isset($json['message']) && ($json['type'] === 'notice' || (isset($json['ok']) && $json['ok'] === false))) {
                board::error($json['message']);
                echo json_encode(['error' => $json['message']]);
                return;
            }

            // Всё в порядке — возвращаем тело json как результат
            // Добавим флаги доступности для информации
            if (!isset($json['api_reachable'])) {
                $json['api_reachable'] = true;
            }
            if (!isset($json['target_reachable'])) {
                // Попытаемся определить из ранее вычисленной переменной, если есть
                $json['target_reachable'] = isset($targetReachable) ? $targetReachable : null;
            }
            echo json_encode($json);
            return;
        }

        // Если API вернул плоский массив/объект с ключом 'error'
        if (is_array($apiResponse) && isset($apiResponse['error'])) {
            board::error($apiResponse['error']);
            echo json_encode(['error' => $apiResponse['error']]);
            return;
        }

        // На всякий случай: если пришёл какой-то другой валидный массив — отдадим его как результат
        if (is_array($apiResponse)) {
            echo json_encode($apiResponse);
            return;
        }

        // Невалидный ответ
        $msg = 'Неверный ответ от Sphere API';
        board::error($msg);
        echo json_encode(['error' => $msg]);
    }
}
