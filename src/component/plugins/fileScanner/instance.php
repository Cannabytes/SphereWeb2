<?php

// Второй файл instance.php
namespace Ofey\Logan22\component\plugins\fileScanner;

use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class instance {

    private const ALLOWED_EXTENSIONS = [
        'php', 'dist', 'scss', 'sfk', 'webm', 'fig', 'xml', 'jsx', 'zip',
        'ts', 'svelte', 'mp4', 'otf', 'mjs', 'rst', 'crt', 'npm',
        'gitignore', 'gitattributes', 'editorconfig', 'yml', 'map',
        'flow', 'js', 'html', 'htm', 'css', 'json', 'cur', 'tpl',
        'png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'svg', 'md',
        'mp3', 'hbs', 'ttf', 'eot', 'woff', 'woff2', 'sql',
        'htaccess', 'txt',
        'dockerfile', 'makefile', 'license', 'readme', 'changelog',
        'gitignore', 'npmignore', 'npmrc', 'babelrc', 'env',
        'editorconfig', 'eslintrc', 'prettierrc', 'npm',
    ];

    private const EXCLUDED_PATHS = [
        '/custom',
        '/uploads/cache',
        '/uploads/images',
        '/uploads/logs',
        '/data/languages/custom'
    ];

    public function index(): void {
        tpl::displayPlugin("/fileScanner/tpl/index.html");
    }

    public function scan(): void {
        header('Content-Type: application/json');

        try {
            $scanner = new scannerSystem(self::ALLOWED_EXTENSIONS, self::EXCLUDED_PATHS);
            $scanner->setBufferSize(64 * 1024);

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
                $results[] = [
                    'file' => $file,
                    'status' => $result['success'],
                    'message' => $result['message']
                ];
            }

            echo json_encode([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function downloadAndUpdateFile(string $file): array {
        $githubUrl = "https://raw.githubusercontent.com/Cannabytes/SphereWeb2/master/" . ltrim($file, '/');

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
            return [
                'success' => false,
                'message' => "Ошибка загрузки файла: $errorMessage"
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

            if (file_exists($localPath) && !is_writable($localPath)) {
                throw new \Exception("Нет прав на запись в файл: $localPath");
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
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
