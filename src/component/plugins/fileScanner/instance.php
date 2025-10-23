<?php

// Второй файл instance.php
namespace Ofey\Logan22\component\plugins\fileScanner;

use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class instance {

    private const ALLOWED_EXTENSIONS = [
        'php', 'dist', 'scss', 'sfk', 'webm', 'fig', 'xml', 'jsx', 'zip',
        'ts', 'svelte', 'mp4', 'otf', 'mjs', 'rst', 'crt', 'npm', 'editorconfig', 'yml', 'map',
        'flow', 'js', 'html', 'htm', 'css', 'json', 'cur', 'tpl',
        'png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'svg', 'md',
        'mp3', 'hbs', 'ttf', 'eot', 'woff', 'woff2', 'sql',
        'htaccess', 'txt',
        'dockerfile', 'makefile', 'license', 'readme', 'changelog',
        'gitignore', 'npmignore', 'npmrc', 'babelrc', 'env',
        'editorconfig', 'eslintrc', 'prettierrc', 'npm', 'gitattributes',
    ];

    private const INCLUDED_PATHS = [
        './index.php',
        './update.php',
        './.htaccess',
        './.gitignore',
        './.gitattributes',
        './custom',
        './data',
        './src',
        './uploads',
        './vendor',
        './template'
    ];

    // Дополнительные пути исключения внутри включаемых папок (при необходимости)
    private const EXCLUDED_PATHS = [
        'uploads/cache',
        'uploads/images',
        'uploads/logs',
        'data/languages/custom'
    ];

    // Файлы, которые нужно полностью исключить из скана и обновления
    private const EXCLUDED_FILES = [
        './src/component/plugins/referral_links/config.php',
    ];

    public function index(): void {
        tpl::displayPlugin("/fileScanner/tpl/index.html");
    }

    public function scan(): void {
        header('Content-Type: application/json');
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1G');

        try {
            $scanner = new scannerSystem(
                self::ALLOWED_EXTENSIONS,
                self::EXCLUDED_PATHS,
                self::INCLUDED_PATHS
            );
            $scanner->setBufferSize(64 * 1024);

            $files = $scanner->scan("./");
            $totalFiles = count($files);

            $dataFiles = [
                'success' => true,
                'total' => $totalFiles,
                'files' => [],
                'scanned_paths' => self::INCLUDED_PATHS
            ];

            foreach ($files as $path => $hash) {
                // Пропускаем файлы, которые явно исключены
                if ($this->isFileExcluded($path)) {
                    continue;
                }

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
                'error' => $e->getMessage(),
                'scanned_paths' => self::INCLUDED_PATHS
            ]);
        }
    }

    public function updateFiles(): void {
        header('Content-Type: application/json');
        ini_set('max_execution_time', 300);

        try {
            $files = $_POST['files'] ?? [];
            if (empty($files)) {
                throw new \Exception('Список файлов пуст');
            }

            // Проверяем, что все файлы находятся в разрешенных путях
            $validFiles = $this->validateFilePaths($files);
            if (count($validFiles) !== count($files)) {
                $invalidFiles = array_diff($files, $validFiles);
                throw new \Exception('Обнаружены файлы вне разрешенных путей: ' . implode(', ', $invalidFiles));
            }

            $results = [];
            foreach ($validFiles as $file) {
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

    private function validateFilePaths(array $files): array {
        $validFiles = [];

        foreach ($files as $file) {
            $normalizedFile = $this->normalizePath($file);

            // Пропускаем явно исключенные файлы
            if ($this->isFileExcluded($normalizedFile)) {
                continue;
            }

            if ($this->isFileInIncludedPaths($normalizedFile)) {
                $validFiles[] = $file;
            }
        }

        return $validFiles;
    }

    /**
     * Проверяет, находится ли файл в списке исключённых файлов или в исключённых путях.
     */
    private function isFileExcluded(string $filePath): bool {
        $normalizedPath = $this->normalizePath($filePath);

        // Убираем префикс "./" для сравнения
        if (str_starts_with($normalizedPath, './')) {
            $normalizedPath = substr($normalizedPath, 2);
        }

        // Проверяем по конкретным файлам
        foreach (self::EXCLUDED_FILES as $excluded) {
            $normExcluded = $this->normalizePath($excluded);
            if (str_starts_with($normExcluded, './')) {
                $normExcluded = substr($normExcluded, 2);
            }
            if ($normalizedPath === $normExcluded) {
                return true;
            }
        }

        // Проверяем, если файл находится внутри исключённых путей
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            $normExcludedPath = $this->normalizePath($excludedPath);
            if ($normExcludedPath === '') {
                continue;
            }
            if (str_starts_with($normalizedPath, rtrim($normExcludedPath, '/') . '/')) {
                return true;
            }
            // Тоже учитываем совпадение директории
            if ($normalizedPath === $normExcludedPath) {
                return true;
            }
        }

        return false;
    }

    private function isFileInIncludedPaths(string $filePath): bool {
        $normalizedPath = $this->normalizePath($filePath);

        // Убираем префикс "./" если есть для корректного сравнения
        if (str_starts_with($normalizedPath, './')) {
            $normalizedPath = substr($normalizedPath, 2);
        }

        foreach (self::INCLUDED_PATHS as $includedPath) {
            $normalizedIncludedPath = $this->normalizePath($includedPath);

            // Убираем префикс "./" если есть
            if (str_starts_with($normalizedIncludedPath, './')) {
                $normalizedIncludedPath = substr($normalizedIncludedPath, 2);
            }

            // Точное совпадение для файлов
            if ($normalizedPath === $normalizedIncludedPath) {
                return true;
            }

            // Проверяем, находится ли файл внутри включаемой папки
            if ($normalizedIncludedPath !== '' && str_starts_with($normalizedPath, $normalizedIncludedPath . '/')) {
                return true;
            }

            // Специальная обработка для корневых файлов
            if ($normalizedIncludedPath === '' && !str_contains($normalizedPath, '/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string {
        // Нормализуем разделители
        $normalized = str_replace('\\', '/', $path);

        // Убираем завершающий слеш (кроме корня)
        $normalized = rtrim($normalized, '/');

        // Если путь пустой или только "./", возвращаем "."
        if ($normalized === '' || $normalized === '.') {
            return '.';
        }

        return $normalized;
    }

    private function downloadAndUpdateFile(string $file): array {
        $githubUrl = "https://raw.githubusercontent.com/Cannabytes/SphereWeb2/master/" . ltrim($file, '/');
        $localPath = $file;
        $backupPath = null;

        // Проверяем и создаем директорию перед скачиванием файла
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => "Не удалось создать директорию: $dir. Проверьте права доступа."
                ];
            }
        }

        // Проверяем права на запись перед скачиванием
        if (file_exists($localPath)) {
            if (!is_writable($localPath)) {
                return [
                    'success' => false,
                    'message' => "Нет прав на запись в файл: $localPath. Текущие права: " . substr(sprintf('%o', fileperms($localPath)), -4)
                ];
            }
        } elseif (!is_writable($dir)) {
            return [
                'success' => false,
                'message' => "Нет прав на запись в директорию: $dir. Текущие права: " . substr(sprintf('%o', fileperms($dir)), -4)
            ];
        }

        // Скачиваем файл
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

        // Запись файла с дополнительными проверками
        try {
            // Проверяем свободное место на диске
            $diskFreeSpace = @disk_free_space(dirname($localPath));
            if ($diskFreeSpace !== false && $diskFreeSpace < strlen($content)) {
                return [
                    'success' => false,
                    'message' => "Недостаточно места на диске для записи файла"
                ];
            }

            // Пытаемся создать резервную копию, если файл существует
            if (file_exists($localPath)) {
                $backupPath = $localPath . '.bak';
                @copy($localPath, $backupPath);
            }

            // Записываем содержимое файла
            $writeResult = @file_put_contents($localPath, $content, LOCK_EX);
            if ($writeResult === false) {
                throw new \Exception("Не удалось записать данные в файл: " . (error_get_last()['message'] ?? 'неизвестная ошибка'));
            }

            // Проверяем, что файл действительно был записан
            if (!file_exists($localPath) || filesize($localPath) !== strlen($content)) {
                throw new \Exception("Ошибка верификации записанного файла");
            }

            // Удаляем резервную копию после успешного обновления
            if ($backupPath && file_exists($backupPath)) {
                @unlink($backupPath);
            }

            return [
                'success' => true,
                'message' => 'Файл успешно обновлён'
            ];

        } catch (\Exception $e) {
            // Восстанавливаем из резервной копии при неудаче
            if ($backupPath && file_exists($backupPath)) {
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