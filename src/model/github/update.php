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

    static function checkNewCommit(): void
    {
        try {
            $sphere = server::send(type::GET_COMMIT_FILES, [
                'last_commit' => self::getLastCommit(),
            ])->getResponse();

            if (!isset($sphere['last_commit_now']) || !isset($sphere['status']) || !isset($sphere['data'])) {
                throw new Exception("Некорректный ответ от сервера");
            }

            if ($sphere['last_commit_now'] == self::getLastCommit()) {
                board::success("Обновление не требуется");
                return;
            }

            if (!$sphere['status']) {
                set_time_limit(600);
                $last_commit_now = $sphere['last_commit_now'];
                $totalFiles = count($sphere['data']);
                $filesStatus = [];

                // Проверяем структуру данных перед обработкой
                foreach ($sphere['data'] as $file => $fileData) {
                    if (!isset($fileData['link']) || !isset($fileData['status'])) {
                        error_log("Некорректные данные для файла: " . print_r($fileData, true));
                        continue;
                    }
                }

                // Скачиваем файлы асинхронно
                $downloadedFiles = self::downloadFiles($sphere['data']);

                foreach ($downloadedFiles as $file => $content) {
                    if (!isset($sphere['data'][$file])) {
                        error_log("Отсутствуют данные для файла: " . $file);
                        continue;
                    }

                    $fileData = $sphere['data'][$file];
                    $filePath = fileSys::get_dir($file);

                    if (!isset($fileData['status'])) {
                        error_log("Отсутствует статус для файла: " . $file);
                        continue;
                    }

                    try {
                        if ($fileData['status'] == 'added' || $fileData['status'] == 'modified') {
                            self::ensureDirectoryExists($filePath);

                            if (file_put_contents($filePath, $content) === false) {
                                throw new Exception("Не удалось записать файл: " . $filePath);
                            }

                            $filesStatus[] = [
                                'file' => $file,
                                'status' => 'updated',
                            ];
                        } elseif ($fileData['status'] == 'removed') {
                            if ($file == 'data/db.php') {
                                continue;
                            }

                            if (is_dir($filePath)) {
                                self::deleteDirectory($filePath);
                            } elseif (file_exists($filePath)) {
                                if (!unlink($filePath)) {
                                    throw new Exception("Не удалось удалить файл: " . $filePath);
                                }
                            }

                            $filesStatus[] = [
                                'file' => $file,
                                'status' => 'removed',
                            ];
                        }
                    } catch (Exception $e) {
                        error_log("Ошибка при обработке файла {$file}: " . $e->getMessage());
                        continue;
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
            error_log("Ошибка обновления: " . $e->getMessage());
            board::error("Произошла ошибка во время обновления: " . $e->getMessage());
        }
    }

    private static function downloadFiles(array $fileLinks): array
    {
        $curlMulti = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($fileLinks as $file => $data) {
            if (!isset($data['link']) || empty($data['link'])) {
                error_log("Пропущен файл {$file}: отсутствует или пустой URL");
                continue;
            }

            $ch = curl_init($data['link']);

            if ($ch === false) {
                error_log("Не удалось инициализировать cURL для файла: " . $file);
                continue;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept: */*',
                ]
            ]);

            $handles[$file] = $ch;
            curl_multi_add_handle($curlMulti, $ch);
        }

        // Выполнение асинхронных запросов
        do {
            $status = curl_multi_exec($curlMulti, $active);
            if ($active) {
                curl_multi_select($curlMulti);
            }
        } while ($active && $status == CURLM_OK);

        // Обработка результатов
        foreach ($handles as $file => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode == 200) {
                $content = curl_multi_getcontent($ch);
                if ($content !== false) {
                    $results[$file] = $content;
                } else {
                    error_log("Не удалось получить содержимое для файла: " . $file);
                }
            } else {
                error_log("Ошибка загрузки файла {$file}: HTTP код {$httpCode}");
            }

            curl_multi_remove_handle($curlMulti, $ch);
            curl_close($ch);
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