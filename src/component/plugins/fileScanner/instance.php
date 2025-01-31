<?php

namespace Ofey\Logan22\component\plugins\fileScanner;

use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class instance {
    public function index(): void {
        tpl::displayPlugin("/fileScanner/tpl/index.html");
    }

    public function scan(): void {

        // Установим заголовок для JSON-ответа
        header('Content-Type: application/json');

        try {

            $scanner = new scannerSystem(
                ['php', 'js', 'html', 'htm', 'css', 'json', 'cur', 'tpl', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'svg', 'md', 'mp3', 'hbs', 'ttf', 'eot', 'woff', 'woff2', 'sql', 'htaccess', 'txt'],
                ['/custom', '/uploads/cache', '/uploads/images', '/uploads/logs', '/data/languages/custom']
            );

            $files = $scanner->scan("./");
            $totalFiles = count($files);


            $dataFiles = [
                'success' => true,
                'total' => $totalFiles,
                'files' => []
            ];

            foreach ($files as $path => $hash) {
                $dataFiles['files'][] = [
                    'path' => $path,
                    'hash' => $hash,
                ];
            }
            server::setTimeout(30);
            $response = server::send(type::FILE_SCANNER, $dataFiles)->show()->getResponse();
            echo json_encode($response);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateFiles(): void {
        header('Content-Type: application/json');

        try {
            $files = $_POST['files'] ?? [];
            if (empty($files)) {
                throw new \Exception('Список файлов пуст');
            }

            $results = [];
            foreach ($files as $file) {
                $result = $this->downloadAndUpdateFile($file);

                // Добавляем отладочную информацию
                $debug = [
                    'file' => $file,
                    'api_url' => "http://167.235.239.166:443/api/file/update/" . ltrim($file, '/'),
                    'result' => $result
                ];

                $results[] = [
                    'file' => $file,
                    'status' => $result['success'],
                    'message' => $result['message'],
                    'debug' => $debug
                ];
            }

            echo json_encode([
                'success' => true,
                'results' => $results
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_PRETTY_PRINT);
        }
    }

    private function downloadAndUpdateFile(string $file): array {
        // Формируем URL для GitHub, используя raw content
        $githubUrl = "https://raw.githubusercontent.com/Cannabytes/SphereWeb2/master/" . ltrim($file, '/');

        // Создаём контекст с заголовками для GitHub
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'header' => [
                    'Accept: */*',
                    'Cache-Control: no-cache',
                ]
            ]
        ]);

        $content = @file_get_contents($githubUrl, false, $context);

        if ($content === false) {
            $error = error_get_last();
            $errorMessage = $error ? $error['message'] : 'Неизвестная ошибка';

            $responseHeaders = $http_response_header ?? [];
            $httpCode = null;
            foreach ($responseHeaders as $header) {
                if (preg_match('#HTTP/\d\.\d\s+(\d+)#', $header, $matches)) {
                    $httpCode = intval($matches[1]);
                    break;
                }
            }

            return [
                'success' => false,
                'message' => "Ошибка загрузки файла ($errorMessage). HTTP код: $httpCode",
                'url' => $githubUrl,
                'headers' => $responseHeaders
            ];
        }

        try {
            $localPath = $file;
            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    throw new \Exception("Не удалось создать директорию: $dir");
                }
            }

            // Проверяем права на запись
            if (file_exists($localPath)) {
                if (!is_writable($localPath)) {
                    throw new \Exception("Нет прав на запись в файл: $localPath");
                }
                // Создаем резервную копию перед заменой
                $backupPath = $localPath . '.bak';
                @copy($localPath, $backupPath);
            }

            $writeResult = @file_put_contents($localPath, $content);
            if ($writeResult === false) {
                throw new \Exception("Не удалось записать данные в файл");
            }

            return [
                'success' => true,
                'message' => 'Файл успешно обновлён'
            ];

        } catch (\Exception $e) {
            // Если есть резервная копия и произошла ошибка, восстанавливаем её
            if (isset($backupPath) && file_exists($backupPath)) {
                @copy($backupPath, $localPath);
                @unlink($backupPath);
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

}