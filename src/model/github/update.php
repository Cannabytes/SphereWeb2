<?php

namespace Ofey\Logan22\model\github;

use DateTime;
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

    static function checkNewCommit()
    {
        if($_SESSION['update_status']) {
            if($_SESSION['time'] + 30 >= time()) {
                board::error("Уже выполняется обновление");
            }else{
                unset($_SESSION['update_status']);
                unset($_SESSION['time']);
                unset($_SESSION['total_files']);
                unset($_SESSION['processed_files']);
            }
        }

        $sphere = server::send(type::GET_COMMIT_FILES, [
          'last_commit' => self::getLastCommit(),
        ])->getResponse();

        if ($sphere['last_commit'] == self::getLastCommit()) {
            board::success("Обновление не требуется");
        }

        if ( ! $sphere['status']) {
            set_time_limit(600);
            $last_commit_now = $sphere['last_commit_now'];
            $totalFiles = count($sphere['data']);
            $_SESSION['update_status'] = true;
            $_SESSION['time'] = time();
            $_SESSION['total_files'] = $totalFiles;
            $_SESSION['processed_files'] = 0;

            foreach ($sphere['data'] as $data) {
                $file = $data['file'];
                $status = $data['status'];
                $link = $data['link'];
                $filePath = fileSys::get_dir($file);

                if ($status == 'added' || $status == 'modified') {
                    self::ensureDirectoryExists($filePath);
                    file_put_contents($filePath, file_get_contents($link));
                } elseif ($status == 'removed') {
                    if ($file == 'data/db.php') {
                        continue;
                    }
                    unlink($filePath);
                }
                $_SESSION['processed_files']++;
            }
            self::addLastCommit($last_commit_now);
            board::success("ПО обновлено");
        }
        board::success("Обновление не требуется");
    }

   private static function ensureDirectoryExists($filePath) {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }


    static function getLastCommit(): string|null
    {
        $github              = sql::getRow("SELECT * FROM `github_updates` WHERE sha != '' ORDER BY `id` DESC LIMIT 1");
        self::$shaLastCommit = $github['sha'] ?? '';

        return self::$shaLastCommit;
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
        if (sql::isError()) {
            $sql = sql::debug_query(
              "INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)",
              [
                $last_commit_now,
                "Cannabytes",
                "https://github.com/Cannabytes/SphereWeb2/commit/" . $last_commit_now,
                "Autoupdated",
                time::mysql(),
                time::mysql(),
              ]
            );
            error_log($sql);
            board::error("Ошибка записи коммита");
        }
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