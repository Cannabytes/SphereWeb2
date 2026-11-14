<?php

namespace Ofey\Logan22\component\plugins\sphereLauncher2;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;
use RuntimeException;

/**
 * Sphere Launcher 2 — создание Windows-лаунчера.
 *
 * Логика повторяет модуль SphereWeb3 (app/internal/admin/launcher_builder.go):
 *  1. настройки сборки сохраняются на сайте и подставляются в форму;
 *  2. запрос уходит напрямую на сервер сборки (POST /api/compile);
 *  3. статус опрашивается через сайт (GET /api/compile/status/{id});
 *  4. готовый EXE скачивается сайтом с проверкой размера, SHA-256 и сигнатуры MZ,
 *     хранится в закрытой папке data/ и отдаётся только администратору.
 *
 * SphereWeb2 передаёт sphereWebVersion = 2: собранный лаунчер сам проверяет
 * лицензию домена.
 */
class sphereLauncher2
{
    private const BUILDER_SERVER_URL = 'http://167.235.239.166:8095';
    private const SPHERE_WEB_VERSION = 2;
    private const MAX_BODY = 8 << 20;
    private const MAX_BINARY = 256 << 20;
    private const MAX_ICON = 300 << 10;
    private const MAX_DATA_URLS = 10;
    private const MAX_HISTORY = 30;
    private const DEFAULT_TEMPLATE = 'default';
    private const TASK_ID_PATTERN = '/^[a-fA-F0-9]{32}$/';
    private const FILE_NAME_PATTERN = '/^[A-Za-z0-9._-]{1,200}\.exe$/';
    private const ICON_ID_PATTERN = '/^[a-f0-9]{64}$/';

    /** Встроенные HTML-шаблоны лаунчера (папка templates/ плагина). */
    private const TEMPLATES = [
        'default' => 'Default',
        'aurelis' => 'Aurelis',
        'dragonfall' => 'Dragonfall',
        'elysium' => 'Elysium',
        'nocturne' => 'Nocturne',
    ];

    /* ===================================================================
     * Страницы
     * =================================================================== */

    public function show(): void
    {
        validation::user_protection("admin");
        tpl::addVar("sl2_section", "overview");
        tpl::addVar("sl2_builds", array_map([$this, 'publicJob'], array_slice($this->readBuilds(), 0, 5)));
        tpl::addVar("sl2_builder_url", self::BUILDER_SERVER_URL);
        tpl::displayPlugin("/sphereLauncher2/tpl/index.html");
    }

    public function compilePage(): void
    {
        validation::user_protection("admin");
        $templates = [];
        foreach (self::TEMPLATES as $key => $name) {
            $templates[] = [
                'key' => $key,
                'name' => $name,
                'url' => $this->templatePublicUrl($key),
            ];
        }
        tpl::addVar("sl2_section", "create");
        tpl::addVar("sl2_bootstrap", [
            'settings' => $this->publicSettings($this->loadSettings()),
            'templates' => $templates,
            'builds' => array_map([$this, 'publicJob'], array_slice($this->readBuilds(), 0, 10)),
            'domain' => $this->licenseDomain($this->siteHost()),
        ]);
        tpl::displayPlugin("/sphereLauncher2/tpl/compile.html");
    }

    /**
     * Архивы клиента: создание ZIP/archive.csv и загрузка на FTP/SFTP.
     * Как и в SphereWeb3, работу выполняет запущенный на компьютере
     * администратора SphereLauncher2; страница управляет им через его
     * локальный мост ws://127.0.0.1:28791/ws. Настройки FTP/SFTP (включая
     * пароли) хранятся только у лаунчера на этом компьютере, не на сайте.
     */
    public function archivesPage(): void
    {
        validation::user_protection("admin");
        tpl::addVar("sl2_section", "archives");
        tpl::displayPlugin("/sphereLauncher2/tpl/archives.html");
    }

    public function config(): void
    {
        validation::user_protection("admin");
        tpl::addVar("sl2_section", "config");
        tpl::displayPlugin("/sphereLauncher2/tpl/config.html");
    }

    /* ===================================================================
     * API: настройки
     * =================================================================== */

    public function getSettings(): void
    {
        validation::user_protection("admin");
        $this->respond(fn() => $this->publicSettings($this->loadSettings()));
    }

    public function saveSettings(): void
    {
        validation::user_protection("admin");
        $this->respond(function () {
            $input = $this->readJsonBody();
            $defaults = $input['defaults'] ?? $input;
            $request = $this->prepareBuildRequest(is_array($defaults) ? $defaults : []);
            return $this->publicSettings($this->storeSettings($request));
        });
    }

    public function test(): void
    {
        validation::user_protection("admin");
        $this->respond(function () {
            $health = $this->builderJson('GET', '/healthz', null, 200);
            $this->builderJson('GET', '/api/compile/status/00000000000000000000000000000000', null, 404);
            return [
                'ok' => ($health['status'] ?? '') === 'ok',
                'status' => (string)($health['status'] ?? ''),
                'queueDepth' => (int)($health['queue_depth'] ?? 0),
                'buildRunning' => (bool)($health['build_running'] ?? false),
            ];
        });
    }

    /* ===================================================================
     * API: сборки
     * =================================================================== */

    public function startBuild(): void
    {
        validation::user_protection("admin");
        $this->respond(function () {
            $request = $this->prepareBuildRequest($this->readJsonBody());

            // Как в SphereWeb3: перед сборкой сохраняем введённые параметры как
            // значения по умолчанию (без бинарных файлов, кроме иконки).
            $this->storeSettings($request);

            $response = $this->builderJson('POST', '/api/compile', $request, 202);
            $taskId = (string)($response['task_id'] ?? '');
            if (!preg_match(self::TASK_ID_PATTERN, $taskId)) {
                throw new PublicError(502, 'Сервер сборки вернул некорректный идентификатор задачи');
            }

            $now = gmdate('c');
            $job = [
                'taskId' => $taskId,
                'title' => $request['title'],
                'status' => 'queued',
                'progress' => 0,
                'message' => 'В очереди на сборку',
                'error' => '',
                'fileName' => '',
                'fileSize' => 0,
                'sha256' => '',
                'relativePath' => '',
                'createdAt' => $now,
                'updatedAt' => $now,
                'finishedAt' => '',
            ];
            $this->mutateBuilds(function (array $builds) use ($job) {
                array_unshift($builds, $job);
                return $builds;
            });
            return $this->publicJob($job);
        }, 202);
    }

    public function buildStatus($taskId = ''): void
    {
        validation::user_protection("admin");
        $this->respond(fn() => $this->publicJob($this->refreshBuild((string)$taskId)));
    }

    public function listBuilds(): void
    {
        validation::user_protection("admin");
        $this->respond(fn() => ['items' => array_map([$this, 'publicJob'], $this->readBuilds())]);
    }

    public function downloadBuild($taskId = ''): void
    {
        validation::user_protection("admin");
        try {
            $job = $this->findBuild((string)$taskId);
            if ($job['status'] !== 'completed' || $job['relativePath'] === '') {
                throw new PublicError(404, 'Файл сборки ещё не готов');
            }
            $root = $this->buildRoot();
            $file = $root . '/' . $job['relativePath'];
            $realRoot = realpath($root);
            $realFile = realpath($file);
            if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR) || !is_file($realFile)) {
                throw new PublicError(404, 'Файл сборки не найден');
            }
        } catch (\Throwable $e) {
            $this->sendError($e);
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $fileName = basename($job['fileName'] ?: 'SphereLauncher.exe');
        header('Content-Type: application/vnd.microsoft.portable-executable');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($realFile));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($realFile);
        exit();
    }

    public function deleteBuild($taskId = ''): void
    {
        validation::user_protection("admin");
        $this->respond(function () use ($taskId) {
            $job = $this->findBuild((string)$taskId);
            if ($job['status'] === 'queued' || $job['status'] === 'running') {
                throw new PublicError(409, 'Нельзя удалить сборку, которая ещё выполняется');
            }
            $dir = $this->buildRoot() . '/' . $job['taskId'];
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($dir);
            }
            $this->mutateBuilds(fn(array $builds) => array_values(array_filter($builds, fn($b) => $b['taskId'] !== $job['taskId'])));
            return ['ok' => true];
        });
    }

    /* ===================================================================
     * API: библиотека иконок
     * =================================================================== */

    public function listIcons(): void
    {
        validation::user_protection("admin");
        $this->respond(function () {
            $items = [];
            $dir = $this->storageDir('icons');
            $files = glob($dir . '/*.png') ?: [];
            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            foreach ($files as $file) {
                $id = basename($file, '.png');
                if (!preg_match(self::ICON_ID_PATTERN, $id)) {
                    continue;
                }
                $items[] = ['id' => $id, 'base64' => base64_encode((string)file_get_contents($file))];
            }
            return ['items' => $items];
        });
    }

    public function saveIcon(): void
    {
        validation::user_protection("admin");
        $this->respond(function () {
            $body = $this->readJsonBody();
            $data = $this->decodeIcon((string)($body['base64'] ?? ''));
            $id = hash('sha256', $data);
            $target = $this->storageDir('icons') . '/' . $id . '.png';
            if (!is_file($target)) {
                $this->atomicWrite($target, $data);
            } else {
                @touch($target);
            }
            return ['id' => $id, 'base64' => base64_encode($data)];
        });
    }

    public function deleteIcon($id = ''): void
    {
        validation::user_protection("admin");
        $this->respond(function () use ($id) {
            $id = (string)$id;
            if (!preg_match(self::ICON_ID_PATTERN, $id)) {
                throw new PublicError(400, 'Некорректный идентификатор иконки');
            }
            $file = $this->storageDir('icons') . '/' . $id . '.png';
            if (is_file($file)) {
                @unlink($file);
            }
            return ['ok' => true];
        });
    }

    /* ===================================================================
     * Подготовка запроса на сборку (аналог prepareLauncherBuildRequest)
     * =================================================================== */

    private function defaultRequest(): array
    {
        $base = $this->siteBaseUrl();
        $host = $this->siteHost();
        return [
            'title' => $host !== '' ? $host : 'Sphere Launcher',
            'windowWidth' => 1100,
            'windowHeight' => 700,
            'minWidth' => 800,
            'minHeight' => 600,
            'maxWidth' => 1100,
            'maxHeight' => 800,
            'launcherVersion' => '1.0.0',
            'template' => 'remote',
            'remoteTemplateKey' => self::DEFAULT_TEMPLATE,
            'remoteTemplateUrl' => $this->templatePublicUrl(self::DEFAULT_TEMPLATE),
            'dataUrl' => $base . '/launcher.json',
            'dataUrls' => [],
            'accountLink' => $base . '/main',
            'registrationLink' => $base . '/signup',
            'forumLink' => '',
            'supportLink' => $base . '/ticket',
            'descriptionLink' => $base,
            'donateLink' => $base . '/donate/pay',
            'fullscreen' => false,
            'iconBase64' => '',
            'customHtmlBase64' => '',
            'customCssBase64' => '',
            'customLangJsonBase64' => '',
            'customLogoBase64' => '',
            'frameless' => true,
            'disableResize' => true,
            'CSSDragProperty' => true,
            'domain' => $this->licenseDomain($host),
            'sphereWebVersion' => self::SPHERE_WEB_VERSION,
        ];
    }

    private function prepareBuildRequest(array $input): array
    {
        $defaults = $this->defaultRequest();
        $request = [];

        foreach (['title', 'launcherVersion', 'dataUrl', 'accountLink', 'registrationLink', 'forumLink',
                     'supportLink', 'descriptionLink', 'donateLink', 'remoteTemplateUrl'] as $key) {
            $request[$key] = trim((string)($input[$key] ?? ''));
        }
        foreach (['windowWidth', 'windowHeight', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight'] as $key) {
            $request[$key] = (int)($input[$key] ?? 0);
        }
        foreach (['fullscreen', 'frameless', 'disableResize', 'CSSDragProperty'] as $key) {
            $request[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        // Шаблон всегда удалённый: встроенный (из папки плагина) или своя ссылка.
        $request['template'] = 'remote';
        $templateKey = trim((string)($input['remoteTemplateKey'] ?? ''));
        if ($templateKey !== '') {
            if (!isset(self::TEMPLATES[$templateKey])) {
                throw new PublicError(400, 'Неизвестный шаблон лаунчера');
            }
            $request['remoteTemplateKey'] = $templateKey;
            $request['remoteTemplateUrl'] = $this->templatePublicUrl($templateKey);
        } else {
            $request['remoteTemplateKey'] = '';
        }

        // Иконка (PNG до 256×256 и 300 КБ, подготавливается в браузере).
        $icon = trim((string)($input['iconBase64'] ?? ''));
        if ($icon !== '') {
            $icon = base64_encode($this->decodeIcon($icon));
        }
        $request['iconBase64'] = $icon;
        $request['customHtmlBase64'] = '';
        $request['customCssBase64'] = '';
        $request['customLangJsonBase64'] = '';
        $request['customLogoBase64'] = '';

        // Резервные источники JSON.
        $mirrors = [];
        foreach ((array)($input['dataUrls'] ?? []) as $value) {
            $value = trim((string)$value);
            if ($value === '' || $value === $request['dataUrl'] || in_array($value, $mirrors, true)) {
                continue;
            }
            $this->validateUrl('Резервный источник JSON', $value, true);
            $mirrors[] = $value;
        }
        if (count($mirrors) > self::MAX_DATA_URLS) {
            throw new PublicError(400, 'Можно указать не больше ' . self::MAX_DATA_URLS . ' резервных источников');
        }
        $request['dataUrls'] = $mirrors;

        // Домен лицензии и версия SphereWeb определяются сервером, а не формой.
        $request['domain'] = $defaults['domain'];
        $request['sphereWebVersion'] = self::SPHERE_WEB_VERSION;

        if ($request['title'] === '' || mb_strlen($request['title']) > 120) {
            throw new PublicError(400, 'Укажите название лаунчера (до 120 символов)');
        }
        if (!preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/', $request['launcherVersion']) || strlen($request['launcherVersion']) > 32) {
            throw new PublicError(400, 'Версия должна быть указана строго в формате 1.2.3');
        }
        if ($request['domain'] === '') {
            throw new PublicError(400, 'Не удалось определить домен сайта для лицензии лаунчера');
        }
        if (filter_var($request['domain'], FILTER_VALIDATE_IP) !== false || !str_contains($request['domain'], '.')) {
            throw new PublicError(400, 'Лаунчер собирается только для домена сайта: откройте админку по доменному имени, а не по IP или localhost');
        }

        $urls = [
            'Ссылка на HTML-шаблон' => [$request['remoteTemplateUrl'], true],
            'Ссылка на launcher.json' => [$request['dataUrl'], true],
            'Личный кабинет' => [$request['accountLink'], false],
            'Регистрация' => [$request['registrationLink'], false],
            'Форум' => [$request['forumLink'], false],
            'Поддержка' => [$request['supportLink'], false],
            'Описание' => [$request['descriptionLink'], false],
            'Пожертвования' => [$request['donateLink'], false],
        ];
        foreach ($urls as $name => [$value, $required]) {
            $this->validateUrl($name, $value, $required);
        }

        foreach (['windowWidth', 'windowHeight', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight'] as $key) {
            if ($request[$key] < 320 || $request[$key] > 7680) {
                throw new PublicError(400, 'Размеры окна должны быть от 320 до 7680 пикселей');
            }
        }
        if ($request['minWidth'] > $request['windowWidth'] || $request['windowWidth'] > $request['maxWidth']) {
            throw new PublicError(400, 'Ширина должна удовлетворять условию: минимальная ≤ стартовая ≤ максимальная');
        }
        if ($request['minHeight'] > $request['windowHeight'] || $request['windowHeight'] > $request['maxHeight']) {
            throw new PublicError(400, 'Высота должна удовлетворять условию: минимальная ≤ стартовая ≤ максимальная');
        }

        return $request;
    }

    private function validateUrl(string $name, string $value, bool $required): void
    {
        if ($value === '') {
            if ($required) {
                throw new PublicError(400, "Поле «{$name}» обязательно");
            }
            return;
        }
        $parts = parse_url($value);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($parts === false || empty($parts['host']) || ($scheme !== 'http' && $scheme !== 'https')) {
            throw new PublicError(400, "Поле «{$name}» должно быть полной ссылкой http:// или https://");
        }
    }

    private function decodeIcon(string $encoded): string
    {
        $encoded = trim($encoded);
        if (str_starts_with($encoded, 'data:') && ($comma = strpos($encoded, ',')) !== false) {
            $encoded = substr($encoded, $comma + 1);
        }
        $invalid = new PublicError(400, 'Иконка должна быть PNG размером до 256×256 и 300 КБ. Выберите файл заново для автоматической подготовки');
        if ($encoded === '' || strlen($encoded) > (int)ceil(self::MAX_ICON / 3) * 4 + 4) {
            throw $invalid;
        }
        $data = base64_decode($encoded, true);
        if ($data === false || $data === '' || strlen($data) > self::MAX_ICON) {
            throw $invalid;
        }
        $info = @getimagesizefromstring($data);
        if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_PNG || $info[0] > 256 || $info[1] > 256) {
            throw $invalid;
        }
        return $data;
    }

    /* ===================================================================
     * Обновление статуса и сохранение готового файла
     * =================================================================== */

    private function refreshBuild(string $taskId): array
    {
        $job = $this->findBuild($taskId);
        if ($job['status'] === 'failed' || ($job['status'] === 'completed' && $job['relativePath'] !== '')) {
            return $job;
        }

        $remote = $this->builderJson('GET', '/api/compile/status/' . $taskId, null, 200);
        $status = strtolower(trim((string)($remote['status'] ?? '')));
        if (!in_array($status, ['queued', 'running', 'completed', 'failed'], true)) {
            $status = 'running';
        }
        $now = gmdate('c');
        $job = $this->updateBuild($taskId, function (array $job) use ($remote, $status, $now) {
            $job['status'] = $status;
            $job['progress'] = max(0, min(100, (int)($remote['progress'] ?? 0)));
            $job['message'] = mb_substr(trim((string)($remote['message'] ?? '')), 0, 500);
            $job['error'] = mb_substr(trim((string)($remote['error'] ?? '')), 0, 4000);
            $job['updatedAt'] = $now;
            if (($status === 'completed' || $status === 'failed') && $job['finishedAt'] === '') {
                $job['finishedAt'] = $now;
            }
            return $job;
        });

        if ($status === 'completed' && $job['relativePath'] === '') {
            $job = $this->materializeBuild($taskId, $remote);
        }
        return $job;
    }

    private function materializeBuild(string $taskId, array $remote): array
    {
        $resultUrl = (string)($remote['result_url'] ?? '');
        $fileName = (string)($remote['file_name'] ?? '');
        $fileSize = (int)($remote['file_size'] ?? 0);
        $sha256 = strtolower((string)($remote['sha256'] ?? ''));

        if ($resultUrl === '' || $fileName === '' || $fileSize <= 0 || strlen($sha256) !== 64) {
            throw new PublicError(502, 'Сервер сборки не передал полные данные готового файла');
        }
        if (basename($fileName) !== $fileName || !preg_match(self::FILE_NAME_PATTERN, $fileName)) {
            throw new PublicError(502, 'Сервер сборки передал некорректное имя файла');
        }
        if ($fileSize > self::MAX_BINARY) {
            throw new PublicError(502, 'Готовый файл превышает допустимый размер');
        }
        $downloadUrl = $this->resolveBuilderUrl($resultUrl);

        $lock = fopen($this->storageDir() . '/download.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Не удалось заблокировать загрузку сборки');
        }
        try {
            // Пока ждали блокировку, файл мог сохранить параллельный запрос.
            $job = $this->findBuild($taskId);
            if ($job['relativePath'] !== '') {
                return $job;
            }

            $dir = $this->buildRoot() . '/' . $taskId;
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Не удалось создать папку для сборки');
            }
            $target = $dir . '/' . $fileName;
            $temporary = $target . '.part';

            @set_time_limit(600);
            $out = fopen($temporary, 'wb');
            if ($out === false) {
                throw new RuntimeException('Не удалось создать файл сборки');
            }
            $hash = hash_init('sha256');
            $written = 0;
            $ch = curl_init($downloadUrl);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($out, $hash, &$written) {
                    $written += strlen($chunk);
                    if ($written > self::MAX_BINARY) {
                        return 0;
                    }
                    hash_update($hash, $chunk);
                    return fwrite($out, $chunk);
                },
            ]);
            $ok = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            fclose($out);

            if ($ok === false || $httpCode !== 200) {
                @unlink($temporary);
                if ($written > self::MAX_BINARY) {
                    throw new PublicError(502, 'Готовый файл превышает допустимый размер');
                }
                $detail = $httpCode ? 'HTTP ' . $httpCode : $curlError;
                throw new PublicError(502, 'Не удалось скачать готовый лаунчер: ' . mb_substr($detail, 0, 300));
            }
            $checksum = hash_final($hash);
            if ($written !== $fileSize || !hash_equals($sha256, $checksum)) {
                @unlink($temporary);
                throw new PublicError(502, 'Размер или SHA-256 скачанного файла не совпадает с результатом сборки');
            }
            $fh = fopen($temporary, 'rb');
            $header = $fh ? fread($fh, 2) : '';
            if ($fh) {
                fclose($fh);
            }
            if ($header !== 'MZ') {
                @unlink($temporary);
                throw new PublicError(502, 'Скачанный файл не является Windows EXE');
            }
            if (!@rename($temporary, $target)) {
                @unlink($temporary);
                throw new RuntimeException('Не удалось сохранить файл сборки');
            }

            return $this->updateBuild($taskId, function (array $job) use ($fileName, $taskId, $written, $checksum) {
                $job['fileName'] = $fileName;
                $job['relativePath'] = $taskId . '/' . $fileName;
                $job['fileSize'] = $written;
                $job['sha256'] = $checksum;
                $job['updatedAt'] = gmdate('c');
                return $job;
            });
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function resolveBuilderUrl(string $resultUrl): string
    {
        $base = parse_url(self::BUILDER_SERVER_URL);
        if (str_starts_with($resultUrl, '/') && !str_starts_with($resultUrl, '//')) {
            return rtrim(self::BUILDER_SERVER_URL, '/') . $resultUrl;
        }
        $parts = parse_url($resultUrl);
        if ($parts === false || strcasecmp((string)($parts['scheme'] ?? ''), (string)$base['scheme']) !== 0
            || strcasecmp((string)($parts['host'] ?? ''), (string)$base['host']) !== 0
            || (int)($parts['port'] ?? 0) !== (int)($base['port'] ?? 0)) {
            throw new PublicError(502, 'Ссылка на файл ведёт за пределы сервера сборки');
        }
        return $resultUrl;
    }

    /* ===================================================================
     * Обмен с сервером сборки
     * =================================================================== */

    private function builderJson(string $method, string $endpoint, ?array $payload, int $expectedStatus): array
    {
        $ch = curl_init(rtrim(self::BUILDER_SERVER_URL, '/') . $endpoint);
        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                throw new RuntimeException('Не удалось сформировать запрос на сборку');
            }
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new PublicError(502, 'Сервер сборки недоступен: ' . mb_substr($error, 0, 300));
        }
        $data = json_decode(substr((string)$response, 0, 1 << 20), true);
        if ($code !== $expectedStatus) {
            $message = is_array($data) ? mb_substr(trim((string)($data['error'] ?? '')), 0, 500) : '';
            if ($message === 'Busy') {
                $message = 'Сервер сборки занят. Повторите попытку через несколько минут.';
            }
            throw new PublicError(502, $message !== '' ? $message : 'Сервер сборки вернул HTTP ' . $code);
        }
        if ($response !== '' && !is_array($data)) {
            throw new PublicError(502, 'Сервер сборки вернул некорректный JSON');
        }
        return is_array($data) ? $data : [];
    }

    /* ===================================================================
     * Хранилище (закрытая папка data/sphereLauncher2)
     * =================================================================== */

    private function storageDir(string $sub = ''): string
    {
        $dir = dirname(__DIR__, 4) . '/data/sphereLauncher2';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Нет прав на создание папки data/sphereLauncher2');
            }
        }
        if (!is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($dir . '/index.html', '');
        }
        if ($sub !== '') {
            $dir .= '/' . $sub;
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Нет прав на создание папки data/sphereLauncher2/' . $sub);
            }
        }
        return $dir;
    }

    private function buildRoot(): string
    {
        return $this->storageDir('builds');
    }

    private function loadSettings(): array
    {
        $defaults = $this->defaultRequest();
        $file = $this->storageDir() . '/settings.json';
        if (!is_file($file)) {
            return ['defaults' => $defaults, 'updatedAt' => ''];
        }
        $stored = json_decode((string)file_get_contents($file), true);
        if (!is_array($stored) || !is_array($stored['defaults'] ?? null)) {
            return ['defaults' => $defaults, 'updatedAt' => ''];
        }
        $merged = array_replace($defaults, array_intersect_key($stored['defaults'], $defaults));
        // Встроенный шаблон всегда указывает на текущий адрес сайта.
        if ($merged['remoteTemplateKey'] !== '' && isset(self::TEMPLATES[$merged['remoteTemplateKey']])) {
            $merged['remoteTemplateUrl'] = $this->templatePublicUrl($merged['remoteTemplateKey']);
        }
        $merged['template'] = 'remote';
        $merged['domain'] = $defaults['domain'];
        $merged['sphereWebVersion'] = self::SPHERE_WEB_VERSION;
        if (!is_array($merged['dataUrls'])) {
            $merged['dataUrls'] = [];
        }
        return ['defaults' => $merged, 'updatedAt' => (string)($stored['updatedAt'] ?? '')];
    }

    private function storeSettings(array $request): array
    {
        $settings = ['defaults' => $request, 'updatedAt' => gmdate('c')];
        foreach (['customHtmlBase64', 'customCssBase64', 'customLangJsonBase64', 'customLogoBase64'] as $key) {
            $settings['defaults'][$key] = '';
        }
        $this->atomicWrite($this->storageDir() . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $settings;
    }

    private function publicSettings(array $settings): array
    {
        $defaults = $settings['defaults'];
        unset($defaults['domain'], $defaults['customHtmlBase64'], $defaults['customCssBase64'], $defaults['customLangJsonBase64'], $defaults['customLogoBase64']);
        $defaults['dataUrls'] = array_values($defaults['dataUrls'] ?? []);
        return ['defaults' => $defaults, 'updatedAt' => $settings['updatedAt']];
    }

    private function readBuilds(): array
    {
        $file = $this->storageDir() . '/builds.json';
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    private function mutateBuilds(callable $mutator): array
    {
        $file = $this->storageDir() . '/builds.json';
        $fh = fopen($file, 'c+');
        if ($fh === false || !flock($fh, LOCK_EX)) {
            throw new RuntimeException('Не удалось открыть историю сборок');
        }
        try {
            $raw = stream_get_contents($fh);
            $builds = json_decode((string)$raw, true);
            $builds = is_array($builds) ? $builds : [];
            $builds = array_values($mutator($builds));

            // Ограничиваем историю и удаляем файлы вытесненных сборок.
            if (count($builds) > self::MAX_HISTORY) {
                foreach (array_slice($builds, self::MAX_HISTORY) as $old) {
                    if (!empty($old['taskId']) && preg_match(self::TASK_ID_PATTERN, $old['taskId'])) {
                        $dir = $this->buildRoot() . '/' . $old['taskId'];
                        foreach (glob($dir . '/*') ?: [] as $f) {
                            @unlink($f);
                        }
                        @rmdir($dir);
                    }
                }
                $builds = array_slice($builds, 0, self::MAX_HISTORY);
            }

            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($builds, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            fflush($fh);
            return $builds;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private function findBuild(string $taskId): array
    {
        if (!preg_match(self::TASK_ID_PATTERN, $taskId)) {
            throw new PublicError(400, 'Некорректный идентификатор сборки');
        }
        foreach ($this->readBuilds() as $job) {
            if (($job['taskId'] ?? '') === $taskId) {
                return $this->normalizeJob($job);
            }
        }
        throw new PublicError(404, 'Сборка не найдена');
    }

    private function updateBuild(string $taskId, callable $update): array
    {
        $result = null;
        $this->mutateBuilds(function (array $builds) use ($taskId, $update, &$result) {
            foreach ($builds as $i => $job) {
                if (($job['taskId'] ?? '') === $taskId) {
                    // Готовую или упавшую сборку не откатываем назад.
                    $job = $this->normalizeJob($job);
                    if ($job['status'] === 'failed' || ($job['status'] === 'completed' && $job['relativePath'] !== '')) {
                        $result = $job;
                        return $builds;
                    }
                    $builds[$i] = $result = $update($job);
                    return $builds;
                }
            }
            return $builds;
        });
        if ($result === null) {
            throw new PublicError(404, 'Сборка не найдена');
        }
        return $result;
    }

    private function normalizeJob(array $job): array
    {
        return array_replace([
            'taskId' => '', 'title' => '', 'status' => 'queued', 'progress' => 0, 'message' => '',
            'error' => '', 'fileName' => '', 'fileSize' => 0, 'sha256' => '', 'relativePath' => '',
            'createdAt' => '', 'updatedAt' => '', 'finishedAt' => '',
        ], $job);
    }

    private function publicJob(array $job): array
    {
        $job = $this->normalizeJob($job);
        if ($job['status'] === 'completed' && $job['relativePath'] !== '') {
            $job['downloadUrl'] = '/admin/plugin/sphereLauncher2/builds/' . $job['taskId'] . '/download';
        }
        unset($job['relativePath']);
        return $job;
    }

    private function atomicWrite(string $file, string $content): void
    {
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false || !@rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Не удалось записать файл ' . basename($file));
        }
    }

    /* ===================================================================
     * Адрес сайта и домен лицензии
     * =================================================================== */

    private function siteHost(): string
    {
        $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        return preg_replace('/:\d+$/', '', $host) ?? '';
    }

    private function siteBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        return ($https ? 'https' : 'http') . '://' . $host;
    }

    private function templatePublicUrl(string $key): string
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 4));
        $plugin = str_replace('\\', '/', __DIR__);
        $relative = ltrim(substr($plugin, strlen($root)), '/');
        $sub = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $prefix = $sub !== '' && $sub !== '.' ? '/' . $sub : '';
        return $this->siteBaseUrl() . $prefix . '/' . $relative . '/templates/' . rawurlencode($key) . '/index.html';
    }

    /**
     * Регистрируемый домен (аналог publicsuffix.EffectiveTLDPlusOne в SphereWeb3).
     */
    private function licenseDomain(string $host): string
    {
        $host = trim(strtolower($host), '.');
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $host;
        }
        $labels = explode('.', $host);
        if (count($labels) <= 2) {
            return $host;
        }
        $multiPartSuffixes = [
            'co.uk', 'org.uk', 'me.uk', 'com.ua', 'org.ua', 'net.ua', 'in.ua', 'kiev.ua', 'kyiv.ua', 'com.ru', 'net.ru', 'org.ru',
            'msk.ru', 'spb.ru', 'com.br', 'net.br', 'org.br', 'com.ar', 'com.au', 'net.au', 'com.tr', 'com.pl', 'com.es',
            'com.pt', 'com.gr', 'co.il', 'com.cn', 'com.kz', 'co.za', 'com.mx', 'co.jp', 'co.kr', 'co.in', 'com.by', 'net.by',
        ];
        $lastTwo = implode('.', array_slice($labels, -2));
        if (in_array($lastTwo, $multiPartSuffixes, true)) {
            return implode('.', array_slice($labels, -3));
        }
        return $lastTwo;
    }

    /* ===================================================================
     * JSON-ответы
     * =================================================================== */

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_BODY + 1);
        if ($raw === false || strlen($raw) > self::MAX_BODY) {
            throw new PublicError(413, 'Слишком большой запрос');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new PublicError(400, 'Некорректный JSON');
        }
        return $data;
    }

    private function respond(callable $fn, int $status = 200): void
    {
        try {
            $result = $fn();
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            http_response_code($status);
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            $this->sendError($e);
        }
        exit();
    }

    private function sendError(\Throwable $e): void
    {
        if ($e instanceof PublicError) {
            $code = $e->status;
            $message = $e->getMessage();
        } else {
            error_log('[sphereLauncher2] ' . $e->getMessage());
            $code = 500;
            $message = $e->getMessage() ?: 'Внутренняя ошибка';
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code($code);
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}

class PublicError extends RuntimeException
{
    public function __construct(public int $status, string $message)
    {
        parent::__construct($message);
    }
}
