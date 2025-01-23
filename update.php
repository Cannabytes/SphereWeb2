<?php
/**
 * Файл для автоматического обновления ПО SphereWeb
 * Скрипт не нужно запускать, или что либо делать, скрипт сработает если будут обновления.
 *
 * File for automatic update SphereWeb
 * You don't need to run the script or do anything, the script will work if there are updates.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

use Ofey\Logan22\model\db\sql;

class update
{

    private string $__TOKEN__ = "";

    public function __construct()
    {
        require "data/token.php";
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            if ($json === false) {
                return;
            }
            $data = json_decode($json, true);
            if (__TOKEN__ == $data['token']) {

                //Проверка на явное отключение
                include "src/model/db/sql.php";
                if($this->isDisabled()){
                    http_response_code(418);
                    echo 'Disabled';
                    die();
                }

                $this->__TOKEN__ = __TOKEN__;
                $this->checkNewCommit();

                include "uploads/update_sql.php";
                new updateSql();
                include "uploads/cleaning.php";
            }
        } else {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /main");
            exit();
        }
    }

    private const CONFIG_KEY = '__config_other__';
    private const AUTO_UPDATE_KEY = 'autoUpdate';
    /**
     * Проверяет, отключена ли функция автоматического обновления
     *
     * @throws JsonException При ошибке декодирования JSON
     * @return bool true если автообновление отключено, false в противном случае
     */
    private function isDisabled(): bool
    {
        try {
            $data = \Ofey\Logan22\model\db\sql::getRow(
                sprintf("SELECT setting FROM settings WHERE `key` = '%s'", self::CONFIG_KEY)
            );
            if (!$data || !isset($data['setting'])) {
                return false;
            }
            $settings = json_decode($data['setting'], true, 512, JSON_THROW_ON_ERROR);
            if(!$settings){
                return false;
            }
            return isset($settings[self::AUTO_UPDATE_KEY]) && !filter_var($settings[self::AUTO_UPDATE_KEY], FILTER_VALIDATE_BOOLEAN);
        } catch (JsonException $e) {
            return false;
        }
    }

    private function checkNewCommit(): void
    {
        try {
            $sphere = $this->send("/api/github/commit/files", [
                'last_commit' => $this->getLastCommit(),
            ]);
            $sphere = json_decode($sphere, true);
            if ($sphere['status']) {
                echo 'Обновление не требуется';
                return;
            }

            if (!$sphere['status']) {
                set_time_limit(600);
                $last_commit_now = $sphere['last_commit_now'];
                foreach ($sphere['data'] as $data) {
                    $file = $data['file'];
                    $status = $data['status'];
                    $link = $data['link'];

                    if ($status == 'added' || $status == 'modified') {
                        $this->ensureDirectoryExists($file);

                        $curlResponse = self::getContentUsingCurl($link);
                        if (!$curlResponse['success']) {
                            return;
                        }

                        $content = $curlResponse['data'];

                        $writeResult = file_put_contents($file, $content);
                        if ($writeResult === false) {
                            return;
                        }
                        $writtenContent = file_get_contents($file);
                        if ($writtenContent === false || $writtenContent !== $content) {
                            return;
                        }
                    } elseif ($status == 'removed') {
                        if ($file == 'data/db.php') {
                            continue;
                        }
                        unlink($file);
                    }
                }
                $this->addLastCommit($last_commit_now);
                echo 'Обновление успешно завершено';
            }
        } catch (Exception $e) {
            error_log("Failed to update: " . $e->getMessage());
        }
    }

    private function send($url, $arr = [])
    {
        $link = "api.sphereweb.net";
        $json = json_encode($arr) ?? "";
        $url = $link . $url;
        $ch = curl_init();
        $headers = [
            'Content-Type: application/json',
            'Authorization: BoberKurwa',
        ];

        $headers[] = "User-Id: " . 0;
        $headers[] = "User-Email: " . "auto@update.com";
        $headers[] = "User-Server-Id: " . 0;
        $headers[] = "IP: " . $_SERVER['REMOTE_ADDR'];

        $headers[] = "Token: " . $this->__TOKEN__;
        $host = $_SERVER['HTTP_HOST'];
        if (empty($host) || !$this->is_valid_domain(parse_url($host, PHP_URL_HOST))) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
        $parsedHost = preg_replace('/:\d+$/', '', $parsedHost);
        $headers[] = "Domain: " . $parsedHost;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true); // Указываем, что это POST запрос
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json); // Передаем JSON данные
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Возвращаем результат в переменную
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $response = curl_exec($ch);
        if ($response === false) {
            exit(1);
        }
        curl_close($ch);

        return $response;
    }

    private function is_valid_domain($domain): bool
    {
        return (bool)filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    private function getLastCommit()
    {
        $github = sql::getRow("SELECT * FROM `github_updates` WHERE sha != '' ORDER BY `id` DESC LIMIT 1");

        return $github['sha'] ?? '';
    }

    private function ensureDirectoryExists($filePath)
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
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

    private function addLastCommit($last_commit_now): void
    {
        sql::run("INSERT INTO `github_updates` (`sha`, `author`, `url`, `message`, `date`, `date_update`) VALUES (?, ?, ?, ?, ?, ?)", [
            $last_commit_now,
            "Cannabytes",
            "https://github.com/Cannabytes/SphereWeb2/commit/" . $last_commit_now,
            "Autoupdated",
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ]);
    }

}

new update();
