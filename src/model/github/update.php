<?php

namespace Ofey\Logan22\model\github;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\config\github;
use Ofey\Logan22\model\db\sql;

class update
{

    private static string $shaLastCommit = '';
    private static string $dateLastCommit = '';

    static function update()
    {
        $github = new github();
        $github->update();
    }

    // Тестируемая функция автоматического старта обновлений
    static function autoRemoteUpdate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            if ($json === false) {
                return;
            }
            $data = json_decode($json, true);
            if (server::getToken() == $data['token']) {
                self::checkNewCommit();
            }
        }
    }

    static function checkNewCommit(): void
    {
        try {
            $sphere = server::send(type::GET_COMMIT_FILES, [
                'last_commit' => self::getLastCommit(),
            ])->getResponse();

            if ($sphere['last_commit_now'] == self::getLastCommit()) {
                board::success("Обновление не требуется");
                return;
            }

            if (!$sphere['status']) {
                set_time_limit(600);
                $last_commit_now = $sphere['last_commit_now'];
                $totalFiles = count($sphere['data']);
                $filesStatus = [];

                // Скачиваем файлы асинхронно
                $fileLinks = $sphere['data'];
                $downloadedFiles = self::downloadFiles($fileLinks);

                foreach ($downloadedFiles as $file => $content) {
                    $filePath = fileSys::get_dir($file);

                    if ($fileLinks[$file]['status'] == 'added' || $fileLinks[$file]['status'] == 'modified') {
                        self::ensureDirectoryExists($filePath);

                        if (file_put_contents($filePath, $content) === false) {
                            throw new Exception("Не удалось записать файл: " . $filePath);
                        }

                        $filesStatus[] = [
                            'file' => $file,
                            'status' => 'updated',
                        ];
                    } elseif ($fileLinks[$file]['status'] == 'removed') {
                        if ($file == 'data/db.php') {
                            continue;
                        }

                        if (is_dir($filePath)) {
                            self::deleteDirectory($filePath);
                        } else {
                            if (file_exists($filePath) && !unlink($filePath)) {
                                throw new Exception("Не удалось удалить файл: " . $filePath);
                            }
                        }

                        $filesStatus[] = [
                            'file' => $file,
                            'status' => 'removed',
                        ];
                    }
                }

                // Обновляем последний коммит
                self::addLastCommit($last_commit_now);
                board::alert([
                    'type' => 'notice',
                    'ok' => true,
                    'message' => "Обновлено " . $totalFiles . " файл(ов)",
                    'files' => $filesStatus,
                ]);
            } else {
                board::success("Обновление не требуется");
            }
        } catch (Exception $e) {
            board::error("Произошла ошибка во время обновления: " . $e->getMessage());
        }
    }

    private static function downloadFiles(array $fileLinks): array
    {
        $curlMulti = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($fileLinks as $data) {
            $url = $data['link'];  // Получаем URL для загрузки
            if (empty($url)) {
                error_log("Пустой URL для файла: " . $data['file']);
                continue; // Пропускаем файл, если URL пустой
            }

            // Логируем URL
            error_log("Загружаем файл: " . $data['file'] . " с URL: " . $url);

            $ch = curl_init($url);

            // Заголовки, чтобы запрос выглядел как браузер
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // Добавляем заголовки
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Следуем за редиректом
            curl_setopt($ch, CURLOPT_HEADER, true);  // Добавляем заголовки в вывод, чтобы их тоже можно было проверить

            $handles[$data['file']] = $ch;
            curl_multi_add_handle($curlMulti, $ch);
        }

        // Выполнение асинхронных запросов
        do {
            $status = curl_multi_exec($curlMulti, $active);
            if ($active) {
                curl_multi_select($curlMulti);
            }
        } while ($active && $status == CURLM_OK);

        // Собираем результаты
        foreach ($handles as $file => $ch) {
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            // Логируем код ответа и тип контента
            error_log("Получен ответ для файла " . $file . ": HTTP статус " . $httpCode . ", Content-Type: " . $contentType);

            if ($httpCode != 200 && $httpCode != 301 && $httpCode != 302) {
                error_log("Ошибка загрузки файла: " . $file . " (HTTP Status Code: " . $httpCode . ")");
                continue;
            }

            // Проверяем, что пришёл не HTML
            if (strpos($contentType, 'html') !== false) {
                error_log("Получен HTML вместо файла: " . $file . " (URL: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . ")");
                error_log("Содержимое полученного HTML: " . substr($response, 0, 500)); // Логируем первые 500 символов
                continue;
            }

            $results[$file] = $response;
            curl_multi_remove_handle($curlMulti, $ch);
        }

        curl_multi_close($curlMulti);
        return $results;
    }


    private static function deleteDirectory(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            throw new Exception("Путь не является директорией: " . $dirPath);
        }

        $files = array_diff(scandir($dirPath), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath); // Рекурсивный вызов для вложенной директории
            } else {
                if (!unlink($filePath)) {
                    throw new Exception("Не удалось удалить файл: " . $filePath);
                }
            }
        }

        if (!rmdir($dirPath)) {
            throw new Exception("Не удалось удалить директорию: " . $dirPath);
        }
    }

    static function getLastCommit(): string|null
    {
        $github              = sql::getRow("SELECT * FROM `github_updates` WHERE sha != '' ORDER BY `id` DESC LIMIT 1");
        self::$shaLastCommit = $github['sha'] ?? '';
        self::$dateLastCommit = $github['date'] ?? '';

        return self::$shaLastCommit;
    }

    static function getLastDateUpdateCommit(): string
    {
        return self::$dateLastCommit;
    }

    static function getCountCommit(): int
    {
        $count = sql::getRow("SELECT count(*) AS `count` FROM `github_updates`");
        if($count){
            return $count['count'];
        }
        return 0;
    }

    private static function ensureDirectoryExists($filePath)
    {
        $directory = dirname($filePath);
        if ( ! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private static function getContentUsingCurl($url): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);

            return ['success' => false, 'error' => $error_msg];
        }

        curl_close($ch);

        return ['success' => true, 'data' => $response];
    }

    static function addLastCommit($last_commit_now): void
    {
        sql::run("INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)", [
            $last_commit_now,
            "Cannabytes",
            "https://github.com/Cannabytes/SphereWeb2/commit/" . $last_commit_now,
            "Autoupdated",
            time::mysql(),
            time::mysql(),
        ]);
    }

    static function getUpdateProgress(): false|string
    {
        if ( ! isset($_SESSION['update_status'])) {
            $_SESSION['update_status'] = false;
        }
        $totalFiles     = $_SESSION['total_files'] ?? 0;
        $processedFiles = $_SESSION['processed_files'] ?? 0;

        if ($totalFiles == 0) {
            echo json_encode([
                'status'   => $_SESSION['update_status'],
                'progress' => 0,
            ]);
            exit();
        }

        $progress = ($processedFiles / $totalFiles) * 100;

        echo json_encode([
            'status'   => $_SESSION['update_status'],
            'progress' => $progress,
        ]);
        exit();
    }

}