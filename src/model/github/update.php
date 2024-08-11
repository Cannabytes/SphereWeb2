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
            } else {
                echo 'token error';
            }
        }
    }

    static function checkNewCommit(): void
    {
        try {
            $sphere = server::send(type::GET_COMMIT_FILES, [
              'last_commit' => self::getLastCommit(),
            ])->getResponse();

            if ($sphere['last_commit'] == self::getLastCommit()) {
                board::success("Обновление не требуется");

                return;
            }

            if ( ! $sphere['status']) {
                set_time_limit(600);
                $last_commit_now = $sphere['last_commit_now'];
                $totalFiles      = count($sphere['data']);
                $filesStatus           = [];
                foreach ($sphere['data'] as $data) {
                    $file     = $data['file'];
                    $status   = $data['status'];
                    $link     = $data['link'];
                    $filesStatus[]    = [
                      'file'   => $file,
                      'status' => $status,
                    ];
                    $filePath = fileSys::get_dir($file);

                    if ($status == 'added' || $status == 'modified') {
                        self::ensureDirectoryExists($filePath);

                        $curlResponse = self::getContentUsingCurl($link);
                        if ( ! $curlResponse['success']) {
                            throw new Exception("Не удалось получить контент по ссылке: " . $link);
                        }

                        $content = $curlResponse['data'];

                        $writeResult = file_put_contents($filePath, $content);
                        if ($writeResult === false) {
                            throw new Exception("Не удалось записать контент в файл: " . $filePath);
                        }

                        $writtenContent = file_get_contents($filePath);
                        if ($writtenContent === false || $writtenContent !== $content) {
                            throw new Exception("Содержимое файла не совпадает с ожидаемым: " . $filePath);
                        }
                    } elseif ($status == 'removed') {
                        if ($file == 'data/db.php') {
                            continue;
                        }
                        unlink($filePath);
                    }
                }
                self::addLastCommit($last_commit_now);
                board::alert([
                    'type'    => 'notice',
                    'ok'      => true,
                    'message' => "Обновлено " . $totalFiles . " файл(ов)",
                    'files' => ($filesStatus),
                ]);
            } else {
                board::success("Обновление не требуется");
            }
        } catch (Exception $e) {
            board::error("Произошла ошибка во время обновления: " . $e->getMessage());
        }
    }

    static function getLastCommit(): string|null
    {
        $github              = sql::getRow("SELECT * FROM `github_updates` WHERE sha != '' ORDER BY `id` DESC LIMIT 1");
        self::$shaLastCommit = $github['sha'] ?? '';

        return self::$shaLastCommit;
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