<?php

namespace Ofey\Logan22\component\plugins\fileScanner;

use FilesystemIterator;
use Ofey\Logan22\component\fileSys\fileSys;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Exception;

class scannerSystem {
    private array $result = [];
    private ?array $allowedExtensions = null;
    private array $excludedPaths = [];
    private array $includedPaths = [];
    private int $bufferSize = 32 * 1024;
    private bool $useIncludedPaths = false;

    public function __construct(array|string|null $extensions = null, array|string|null $excludePaths = null, array|string|null $includePaths = null) {
        if (is_array($extensions) && count($extensions) > 0) {
            $this->allowedExtensions = array_map(function($ext) {
                return ltrim(strtolower($ext), '.');
            }, $extensions);
        } elseif (is_string($extensions)) {
            $this->allowedExtensions = [ltrim(strtolower($extensions), '.')];
        }

        $this->setExcludedPaths($excludePaths);
        $this->setIncludedPaths($includePaths);
    }

    public function setExcludedPaths(array|string|null $paths): self {
        if ($paths === null) {
            $this->excludedPaths = [];
        } elseif (is_string($paths)) {
            $this->excludedPaths = [$this->normalizePath($paths)];
        } elseif (is_array($paths)) {
            $this->excludedPaths = array_map([$this, 'normalizePath'], $paths);
        }
        return $this;
    }

    public function setIncludedPaths(array|string|null $paths): self {
        if ($paths === null) {
            $this->includedPaths = [];
            $this->useIncludedPaths = false;
        } elseif (is_string($paths)) {
            $this->includedPaths = [$this->normalizePath($paths)];
            $this->useIncludedPaths = true;
        } elseif (is_array($paths) && count($paths) > 0) {
            $this->includedPaths = array_map([$this, 'normalizePath'], $paths);
            $this->useIncludedPaths = true;
        } else {
            $this->includedPaths = [];
            $this->useIncludedPaths = false;
        }
        return $this;
    }

    private function normalizePath(string $path): string {
        // Нормализуем разделители
        $normalized = str_replace('\\', '/', $path);

        // Убираем завершающий слеш для папок (кроме корня)
        $normalized = rtrim($normalized, '/');

        // Если путь пустой или только "./", возвращаем "."
        if ($normalized === '' || $normalized === '.') {
            return '.';
        }

        return $normalized;
    }

    /**
     * Безопасное удаление префикса "./" из пути
     */
    private function removePathPrefix(string $path): string {
        if (str_starts_with($path, './')) {
            return substr($path, 2);
        }
        return $path;
    }

    private function getFileCRC32(string $filePath): string|false {
        $size = @filesize($filePath);
        if ($size === false) {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Failed to get size of file: $filePath\n";
            file_put_contents('scanner_errors.log', $logMessage, FILE_APPEND);
            return false;
        }

        $maxTextSize = 10 * 1024 * 1024; // 10 MB

        if ($size > $maxTextSize) {
            // Для больших файлов используем hash_file, чтобы избежать загрузки в память
            $hash = @hash_file('crc32b', $filePath);
            if ($hash === false) {
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Failed to hash large file: $filePath (size: $size bytes)\n";
                file_put_contents('scanner_errors.log', $logMessage, FILE_APPEND);
                return false;
            }
            return $hash;
        }

        // Для файлов до 10 MB читаем в память и обрабатываем
        $content = @file_get_contents($filePath);
        if ($content === false) {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Failed to read file: $filePath\n";
            file_put_contents('scanner_errors.log', $logMessage, FILE_APPEND);
            return false;
        }

        $originalContent = $content;
        $originalHash = hash('crc32b', $content);

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $binaryExts = [
            // Изображения
            'jpg' => true, 'jpeg' => true, 'png' => true,
            'gif' => true, 'ico' => true, 'webp' => true,
            'apng' => true, 'avif' => true, 'tiff' => true,
            'bmp' => true, 'psd' => true, 'ai' => true,
            'eps' => true, 'raw' => true, 'cr2' => true,
            'nef' => true, 'heic' => true, 'heif' => true,
            'jxr' => true, 'hdp' => true,

            // Шрифты
            'ttf' => true, 'woff' => true, 'woff2' => true,
            'eot' => true, 'otf' => true, 'pfb' => true,
            'pfm' => true,

            // Аудио
            'mp3' => true, 'wav' => true, 'ogg' => true,
            'm4a' => true, 'aac' => true, 'wma' => true,
            'flac' => true, 'alac' => true, 'aiff' => true,
            'opus' => true, 'mid' => true, 'midi' => true,

            // Видео
            'mp4' => true, 'webm' => true, 'avi' => true,
            'mov' => true, 'wmv' => true, 'flv' => true,
            'mkv' => true, 'm4v' => true, 'mpeg' => true,
            'mpg' => true, '3gp' => true, '3g2' => true,
            'ts' => true, 'mts' => true, 'vob' => true,

            // Документы
            'pdf' => true, 'doc' => true, 'docx' => true,
            'xls' => true, 'xlsx' => true, 'ppt' => true,
            'pptx' => true, 'odt' => true, 'ods' => true,
            'odp' => true, 'pages' => true, 'numbers' => true,
            'key' => true,

            // Архивы
            'zip' => true, 'rar' => true, '7z' => true,
            'tar' => true, 'gz' => true, 'bz2' => true,
            'xz' => true, 'iso' => true, 'dmg' => true,
            'cab' => true,

            // Исполняемые и библиотеки
            'exe' => true, 'dll' => true, 'so' => true,
            'dylib' => true, 'app' => true, 'apk' => true,
            'deb' => true, 'rpm' => true,

            // Базы данных
            'db' => true, 'sqlite' => true, 'mdb' => true,
            'accdb' => true, 'fdb' => true,

            // 3D и CAD
            'obj' => true, 'stl' => true, 'fbx' => true,
            '3ds' => true, 'blend' => true, 'dwg' => true,
            'dxf' => true,

            // Adobe и дизайн
            'indd' => true, 'prproj' => true, 'aep' => true,
            'psb' => true, 'xd' => true,

            // Другие специфические форматы
            'swf' => true, 'fla' => true, 'sketch' => true,
            'fig' => true, 'xcf' => true, 'cdr' => true,
        ];

        if (!isset($binaryExts[$ext])) {
            $hasBOM = strncmp($content, "\xEF\xBB\xBF", 3) === 0;
            $content = str_replace(["\r\n", "\r"], "\n", $content);

            if ($hasBOM && strncmp($content, "\xEF\xBB\xBF", 3) !== 0) {
                $content = "\xEF\xBB\xBF" . $content;
            }
        }

        if ($content !== $originalContent) {
            return hash('crc32b', $content);
        }

        return $originalHash;
    }

    public function scan(string $directory): array {
        $directory = $this->normalizePath($directory);

        if (!is_dir($directory)) {
            throw new RuntimeException("Директория '{$directory}' не существует");
        }

        $this->result = [];

        if ($this->useIncludedPaths) {
            $this->scanIncludedPaths($directory);
        } else {
            $this->scanDirectory($directory);
        }

        return $this->result;
    }

    private function scanIncludedPaths(string $baseDirectory): void {
        foreach ($this->includedPaths as $includedPath) {
            // Более аккуратная обработка путей
            if ($baseDirectory === '.') {
                $fullPath = $this->removePathPrefix($includedPath);
            } else {
                $fullPath = rtrim($baseDirectory, '/') . '/' . $this->removePathPrefix($includedPath);
            }

            // Если это корневой путь (.), проверяем как есть
            if ($includedPath === '.') {
                $fullPath = $baseDirectory;
            }

            if (is_file($fullPath)) {
                $this->scanFile($fullPath);
            } elseif (is_dir($fullPath)) {
                $this->scanDirectory($fullPath);
            }
        }
    }

    private function scanFile(string $filePath): void {
        $file = new SplFileInfo($filePath);

        if (!$file->isFile()) {
            return;
        }

        $path = $this->normalizePath($file->getPathname());

        if ($this->isExcludedPath($path)) {
            return;
        }

        if ($this->isAllowedFile($file)) {
            $size = $file->getSize();
            if ($size > 1024 * 1024 * 1024) { // 1 GB
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Skipping large file: $path (size: $size bytes)\n";
                file_put_contents('scanner_errors.log', $logMessage, FILE_APPEND);
                return;
            }
            $this->result[$path] = $this->getFileCRC32($file->getPathname());
        }
    }

    private function scanDirectory(string $directory): void {
        try {
            $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, $flags),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                /** @var SplFileInfo $file */
                if (!$file->isFile()) {
                    continue;
                }

                $path = $this->normalizePath($file->getPathname());

                // Если используются включаемые пути, проверяем соответствие
                if ($this->useIncludedPaths && !$this->isIncludedPath($path)) {
                    continue;
                }

                if ($this->isExcludedPath($path)) {
                    continue;
                }

                if ($this->isAllowedFile($file)) {
                    $this->result[$path] = $this->getFileCRC32($file->getPathname());
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function isIncludedPath(string $path): bool {
        // Нормализуем путь для сравнения
        $normalizedPath = $this->normalizePath($path);

        // Убираем префикс "./" если есть для корректного сравнения
        $normalizedPath = $this->removePathPrefix($normalizedPath);

        foreach ($this->includedPaths as $includedPath) {
            $normalizedIncludedPath = $this->normalizePath($includedPath);

            // Убираем префикс "./" если есть
            $normalizedIncludedPath = $this->removePathPrefix($normalizedIncludedPath);

            // Точное совпадение для файлов
            if ($normalizedPath === $normalizedIncludedPath) {
                return true;
            }

            // Проверяем, находится ли файл внутри включаемой папки
            if ($normalizedIncludedPath !== '' && str_starts_with($normalizedPath, $normalizedIncludedPath . '/')) {
                return true;
            }

            // Для корневых файлов
            if ($normalizedIncludedPath === '' && !str_contains($normalizedPath, '/')) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedPath(string $path): bool {
        $normalizedPath = $this->normalizePath($path);

        // Убираем префикс "./" если есть для корректного сравнения
        $normalizedPath = $this->removePathPrefix($normalizedPath);

        foreach ($this->excludedPaths as $excludedPath) {
            $normalizedExcludedPath = $this->normalizePath($excludedPath);

            // Убираем префикс "./" если есть
            $normalizedExcludedPath = $this->removePathPrefix($normalizedExcludedPath);

            if ($normalizedPath === $normalizedExcludedPath || str_starts_with($normalizedPath, $normalizedExcludedPath . '/')) {
                return true;
            }
        }
        return false;
    }

    private function isAllowedFile(SplFileInfo $file): bool {
        if ($this->allowedExtensions === null) {
            return true;
        }

        $filename = strtolower($file->getFilename());
        $extension = strtolower($file->getExtension());

        if (empty($extension)) {
            if (in_array($filename, $this->allowedExtensions, true)) {
                return true;
            }
        }

        if (in_array($extension, $this->allowedExtensions, true)) {
            return true;
        }

        if (preg_match('/\.([^.]+\.[^.]+)$/', $filename, $matches)) {
            $doubleExtension = $matches[1];
            if (in_array($doubleExtension, $this->allowedExtensions, true)) {
                return true;
            }
        }

        foreach ($this->allowedExtensions as $allowedExt) {
            if ($filename === $allowedExt || str_ends_with($filename, '.' . $allowedExt)) {
                return true;
            }
        }

        return false;
    }

    public function setBufferSize(int $size): self {
        if ($size <= 0) {
            throw new RuntimeException("Размер буфера должен быть положительным числом");
        }
        $this->bufferSize = $size;
        return $this;
    }

    public function getResult(): array {
        return $this->result;
    }

    public function getIncludedPaths(): array {
        return $this->includedPaths;
    }

    public function isUsingIncludedPaths(): bool {
        return $this->useIncludedPaths;
    }
}