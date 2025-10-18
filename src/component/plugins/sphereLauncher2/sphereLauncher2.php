<?php

namespace Ofey\Logan22\component\plugins\sphereLauncher2;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class sphereLauncher2
{

    public function show(): void
    {
        validation::user_protection("admin");
        tpl::displayPlugin("/sphereLauncher2/tpl/index.html");
    }

    public function compilePage()
    {
        validation::user_protection("admin");
        $go_server_ip = config::load()->sphereApi()->getIp();
        $go_server_port = config::load()->sphereApi()->getPort();
        $go_server_address = "{$go_server_ip}:{$go_server_port}";
        tpl::addVar("go_server_address", $go_server_address);
        tpl::displayPlugin("/sphereLauncher2/tpl/compile.html");
    }

    public function config()
    {
        validation::user_protection("admin");
        $go_server_ip = config::load()->sphereApi()->getIp();
        $go_server_port = config::load()->sphereApi()->getPort();
        $go_server_address = "{$go_server_ip}:{$go_server_port}";
        tpl::addVar("go_server_address", $go_server_address);
        tpl::displayPlugin("/sphereLauncher2/tpl/config.html");
    }

    public function compile(): void
    {
        validation::user_protection("admin");
        $raw_post_data = file_get_contents('php://input');
        $data_array = json_decode($raw_post_data, true);
        if ($data_array === null) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON received']);
            return;
        }

        if (!isset($data_array['customLogoBase64'])) {
            $data_array['customLogoBase64'] = '';
        }

        if (!isset($data_array['dataUrl']) || empty($data_array['dataUrl'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Not set link to launcher.json']);
            return;
        }

        if (!empty($data_array['customLogoBase64'])) {
            $decoded_logo = base64_decode($data_array['customLogoBase64']);
            if ($decoded_logo === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Некорректные данные лого (base64)']);
                return;
            }

            if (strlen($decoded_logo) > 256000) {
                http_response_code(400);
                echo json_encode(['error' => 'Размер файла лого превышает 250 КБ']);
                return;
            }
        }

        server::setTimeout(10);
        $response = server::sendCustom("/api/compile", $data_array)->getResponse();
        header('Content-Type: application/json');
        if (isset($response['error'])) {
            if ($response['error'] == 'Busy') {
                http_response_code(409); // Conflict
                echo json_encode(['error' => lang::get_phrase('sp_busy', 'Компилятор занят. Пожалуйста, подождите завершения текущей задачи.')]);
                return;
            }
        }

        echo json_encode($response);
    }

    public function status()
    {
        validation::user_protection("admin");
        $uri_parts = explode('/', $_SERVER['REQUEST_URI']);
        $task_id = end($uri_parts);

        if (empty($task_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is not specified']);
            return;
        }

        $requestPath = "/api/compile/status/" . basename($task_id);
        $response = server::sendCustomDownload($requestPath);

        header('Content-Type: application/json');

        if ($response['content'] === false || $response['http_code'] >= 400) {
            http_response_code($response['http_code'] ?: 502); // Bad Gateway
            error_log("Ошибка при запросе статуса с Go-сервера: HTTP {$response['http_code']}, cURL error: {$response['error']}, URL: {$requestPath}");
            echo json_encode(['error' => 'Не удалось получить статус с сервера сборки.']);
            return;
        }

        echo $response['content'];
        exit();
    }


    public function download()
    {
        validation::user_protection("admin");
        header('Content-Type: application/json');

        $raw_post_data = file_get_contents('php://input');
        $data = json_decode($raw_post_data, true);
        if (!isset($data['result_url']) || empty($data['result_url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'result_url не был передан.']);
            return;
        }
        $result_url = $data['result_url'];

        $go_server_ip = config::load()->sphereApi()->getIp();
        $go_server_port = config::load()->sphereApi()->getPort();
        $go_server_address = "{$go_server_ip}:{$go_server_port}";
        $source_url = "http://{$go_server_address}" . $result_url;

        $rootDir = $_SERVER['DOCUMENT_ROOT'];
        $baseFileName = 'Launcher_compile_';
        $fileExtension = '.exe';

        $i = 1;
        while (file_exists($rootDir . '/' . $baseFileName . $i . $fileExtension)) {
            $i++;
        }
        $newFileName = $baseFileName . $i . $fileExtension;
        $destination_path = $rootDir . '/' . $newFileName;

        set_time_limit(300);
        $download_result = @copy($source_url, $destination_path);

        if ($download_result) {
            echo json_encode([
                'success' => true,
                'message' => 'Файл успешно сохранен на сервере как ' . $newFileName,
                'file_path' => '/' . $newFileName
            ]);
        } else {
            http_response_code(500);
            $error_info = error_get_last();
            $error_message = 'Не удалось загрузить или сохранить файл с Go-сервера.';
            if ($error_info) {
                $error_message .= ' Детали: ' . $error_info['message'];
            }
            error_log("Ошибка при скачивании файла с {$source_url} в {$destination_path}: {$error_message}");
            echo json_encode(['error' => $error_message]);
        }
    }

}