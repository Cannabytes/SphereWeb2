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
ini_set('error_log', 'errors.txt');

use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;

class update
{

    private string $__TOKEN__ = "";
    private array $ip = [
        ['host' => 'api.sphereweb.net', 'port' => 80],
        ['host' => '167.235.239.166', 'port' => 80],
    ];
    private int $port = 80;
    private const CONFIG_KEY = '__config_other__';
    private const AUTO_UPDATE_KEY = 'autoUpdate';
    private const CONFIG_SPHERE_API = '__config_sphere_api__';
    private const SW_UPDATE_FILES = '/api/sphereweb/download/';

    public function __construct()
    {
        require "data/token.php";
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            if ($json === false) {
                return;
            }
            $data = json_decode($json, true);

            if (!isset($data['token'])) {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: /main");
                exit();
            }

            if (__TOKEN__ == $data['token']) {
                include "src/model/db/sql.php";
                if ($this->isDisabled()) {
                    http_response_code(418);
                    echo 'Disabled';
                    die();
                }
                $this->ResolveServerIP();
                $this->__TOKEN__ = __TOKEN__;
                $this->checkNewCommit();
            }
        } else {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /main");
            exit();
        }
    }

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
            if (!$settings) {
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
                return;
            }

            if (!$sphere['status']) {
                set_time_limit(600);
                $last_commit_now = $sphere['last_commit_now'];
                $toDownload = [];
                foreach ($sphere['data']["Updates"] as $data) {
                    $file = $data['file'];
                    $status = $data['status'];
                    if ($status == 'added' || $status == 'modified') {
                        $this->ensureDirectoryExists($file);
                        $toDownload[] = $file;
                    } elseif ($status == 'removed') {
                        if ($file == 'data/db.php') {
                            continue;
                        }
                        @unlink($file);
                    }
                }

                $allDownloadSuccess = false;
                if (!empty($toDownload)) {
                    $results = $this->downloadFiles($toDownload, 100);
                    $allDownloadSuccess = true;
                    foreach ($results as $fname => $res) {
                        if (!isset($res['success']) || $res['success'] !== true) {
                            error_log(sprintf('Update: failed to download %s: %s', $fname, $res['error'] ?? 'unknown'));
                            $allDownloadSuccess = false;
                        }
                    }
                }
                // Обновляем коммит только если все файлы загружены успешно
                if ($allDownloadSuccess) {
                    $this->addLastCommit($last_commit_now);
                }
            }
        } catch (Exception $e) {
            error_log("Failed to update: " . $e->getMessage());
        }
    }


    private function downloadFiles(array $files, int $concurrency = 100): array
    {

        $results = [];
        if (empty($files)) {
            return $results;
        }
        $concurrency = max(1, min(100, (int)$concurrency));
        $servers = $this->getServers();
        if (empty($servers)) {
            foreach ($files as $file) {
                $results[$file] = ['success' => false, 'error' => 'Нет доступных серверов обновления'];
            }
            return $results;
        }

        $mh = curl_multi_init();
        $handles = [];
        $queue = [];
        foreach ($files as $file) {
            $queue[] = ['file' => $file, 'serverIdx' => 0];
        }
        $activeCount = 0;
        while (!empty($queue) || $activeCount > 0) {
            while ($activeCount < $concurrency && ($task = array_shift($queue)) !== null) {
                $serverIdx = $task['serverIdx'];
                if (!isset($servers[$serverIdx])) {
                    $results[$task['file']] = $results[$task['file']] ?? ['success' => false, 'error' => 'Все серверы обновления недоступны'];
                    continue;
                }
                $server = $servers[$serverIdx];
                $remoteUrl = $this->buildServerUrl($server, self::SW_UPDATE_FILES . $task['file']);
                $localPath = $task['file'];
                $directory = dirname($localPath);
                if (!is_dir($directory)) {
                    @mkdir($directory, 0777, true);
                }

                $tmpPath = $localPath . '.tmp';
                $fp = @fopen($tmpPath, 'wb');
                if ($fp === false) {
                    $results[$task['file']] = ['success' => false, 'error' => 'Unable to open local file for writing: ' . $tmpPath];
                    continue;
                }

                $ch = curl_init($remoteUrl);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_ENCODING, '');
                $headers = [
                    'User-Agent: SphereWebUpdater/1.0',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                // prepare storage for this handle and capture headers
                $key = (int)$ch;
                $handles[$key] = [
                    'ch' => $ch,
                    'file' => $task['file'],
                    'fp' => $fp,
                    'tmp' => $tmpPath,
                    'serverIdx' => $serverIdx,
                    'headers' => '',
                ];

                // capture response headers for later validation
                curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$handles, $key) {
                    $len = strlen($header);
                    $handles[$key]['headers'] = ($handles[$key]['headers'] ?? '') . $header;
                    return $len;
                });

                curl_multi_add_handle($mh, $ch);
                $activeCount++;
            }

            do {
                $mrc = curl_multi_exec($mh, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            if ($running) {
                $select = curl_multi_select($mh, 1.0);
                if ($select === -1) {
                    usleep(100000);
                }
            }

            while ($info = curl_multi_info_read($mh)) {
                $ch = $info['handle'];
                $key = (int)$ch;
                $hmeta = $handles[$key] ?? null;
                $errNo = curl_errno($ch);
                $errMsg = $errNo ? curl_error($ch) : null;
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Сбор и парсинг заголовков ответа
                $rawHeaders = $hmeta['headers'] ?? '';
                $parsedHeaders = [];
                if ($rawHeaders !== '') {
                    $lines = preg_split("/\r\n|\n|\r/", $rawHeaders);
                    foreach ($lines as $line) {
                        if (strpos($line, ':') !== false) {
                            [$hn, $hv] = explode(':', $line, 2);
                            $parsedHeaders[strtolower(trim($hn))] = trim($hv);
                        }
                    }
                }

                // Проверка специальных заголовков от сервера — только проверяем наличие ключей
                $headerFileOk = false;
                // Инициализируем причину неудачи как null, чтобы избежать предупреждений при дальнейшем сравнении
                $failureReason = null;
                if (isset($parsedHeaders['x-file-ok']) && isset($parsedHeaders['x-served-by'])) {
                    $headerFileOk = true;
                }

                if (!$headerFileOk) {
                    $failureReason = 'Missing required headers';
                }

                if ($hmeta) {
                    @fclose($hmeta['fp']);
                    curl_multi_remove_handle($mh, $ch);
                    $activeCount--;
                    if ($errMsg) {
                        $failureReason = $errMsg;
                    } elseif ($failureReason === null && ($httpCode < 200 || $httpCode >= 300)) {
                        $failureReason = 'HTTP error: ' . $httpCode;
                    }

                    if ($failureReason !== null) {
                        @unlink($hmeta['tmp']);
                        $nextServerIdx = $hmeta['serverIdx'] + 1;
                        if (isset($servers[$nextServerIdx])) {
                            $queue[] = ['file' => $hmeta['file'], 'serverIdx' => $nextServerIdx];
                        } else {
                            $results[$hmeta['file']] = ['success' => false, 'error' => $failureReason];
                        }
                    } else {
                        if (!rename($hmeta['tmp'], $hmeta['file'])) {
                            $results[$hmeta['file']] = ['success' => false, 'error' => 'Failed to move temporary file to destination'];
                            @unlink($hmeta['tmp']);
                        } else {
                            $results[$hmeta['file']] = ['success' => true, 'data' => $hmeta['file']];
                        }
                    }

                    unset($handles[$key]);
                } else {
                    curl_multi_remove_handle($mh, $ch);
                    $activeCount--;
                }
            }
        }

        curl_multi_close($mh);
        return $results;
    }

    private function send($url, $arr = [])
    {
        $json = json_encode($arr) ?? "";
        $servers = $this->getServers();
        $lastError = 'unset';
        $headers = [
            'Content-Type: application/json',
            'Authorization: BoberKurwa',
        ];

        $headers[] = "User-Id: " . 0;
        $headers[] = "User-Email: " . "auto@update.com";
        $headers[] = "User-Server-Id: " . 0;
        $headers[] = "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '');
        $headers[] = "Token: " . $this->__TOKEN__;
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($host) || !$this->is_valid_domain(parse_url($host, PHP_URL_HOST))) {
            $host = $_SERVER['SERVER_NAME'] ?? '';
        }

        $parsedHost = parse_url($host, PHP_URL_HOST) ?: $host;
        $parsedHost = preg_replace('/:\\d+$/', '', $parsedHost);
        $headers[] = "Domain: " . $parsedHost;

        foreach ($servers as $server) {
            $fullUrl = $this->buildServerUrl($server, $url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $response = curl_exec($ch);
            if ($response !== false) {
                curl_close($ch);
                return $response;
            }
            $lastError = curl_error($ch) ?: $lastError;
            curl_close($ch);
        }

        error_log('Update: failed to send request to all servers: ' . $lastError);
        exit(1);
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

    private function normalizeServerEntry(array|string $entry): ?array
    {
        $port = $this->port;
        if (is_array($entry)) {
            $host = trim($entry['host'] ?? $entry['ip'] ?? '');
            if ($host === '') {
                return null;
            }
            if (isset($entry['port']) && $entry['port'] !== '') {
                $port = (int)$entry['port'];
            }
        } else {
            $clean = trim($entry);
            if ($clean === '') {
                return null;
            }
            $clean = preg_replace('#^https?://#i', '', $clean);
            if (strpos($clean, ':') !== false) {
                [$host, $portPart] = explode(':', $clean, 2);
                $host = trim($host);
                if ($portPart !== '') {
                    $port = (int)$portPart;
                }
            } else {
                $host = $clean;
            }
        }
        $host = rtrim($host ?? '', '/');
        if ($host === '') {
            return null;
        }
        return ['host' => $host, 'port' => $port ?: $this->port];
    }

    private function getServerKey(array $server): string
    {
        $host = strtolower($server['host'] ?? '');
        $port = isset($server['port']) ? (int)$server['port'] : $this->port;
        return $host . ':' . $port;
    }

    private function getServers(): array
    {
        $normalized = [];
        foreach ($this->ip as $entry) {
            $server = $this->normalizeServerEntry($entry);
            if (!$server) {
                continue;
            }
            $key = $this->getServerKey($server);
            if (isset($normalized[$key])) {
                continue;
            }
            $normalized[$key] = $server;
        }
        if (empty($normalized)) {
            return [];
        }
        return array_values($normalized);
    }

    private function buildServerUrl(array $server, string $path): string
    {
        $port = isset($server['port']) ? (int)$server['port'] : $this->port;
        $protocol = $port === 443 ? 'https://' : 'http://';
        $host = preg_replace('#^https?://#i', '', $server['host']);
        $host = rtrim($host, '/');
        return $protocol . $host . ':' . $port . $path;
    }

    private function prependServer(array|string $entry): void
    {
        $server = $this->normalizeServerEntry($entry);
        if (!$server) {
            return;
        }
        $key = $this->getServerKey($server);
        $updated = [];
        $updated[$key] = $server;
        foreach ($this->ip as $existing) {
            $normalized = $this->normalizeServerEntry($existing);
            if (!$normalized) {
                continue;
            }
            $existingKey = $this->getServerKey($normalized);
            if ($existingKey === $key || isset($updated[$existingKey])) {
                continue;
            }
            $updated[$existingKey] = $normalized;
        }
        $this->ip = array_values($updated);
    }

    private function getContentDownload($url): array
    {
        $results = $this->downloadFiles([$url], 1);
        return $results[$url] ?? ['success' => false, 'error' => 'Failed to download file'];
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

    private function ResolveServerIP()
    {
        $data = \Ofey\Logan22\model\db\sql::getRow(
            sprintf("SELECT setting FROM settings WHERE `key` = '%s'", self::CONFIG_SPHERE_API)
        );
        if ($data && isset($data['setting'])) {
            $settings = json_decode($data['setting'], true, 512, JSON_THROW_ON_ERROR);
            if (isset($settings['ip'])) {
                $port = isset($settings['port']) ? (int)$settings['port'] : $this->port;
                $this->prependServer(['host' => $settings['ip'], 'port' => $port]);
                $this->port = $port;
            }
        }
    }
}
new update();
