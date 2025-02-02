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
    private int $bufferSize = 32 * 1024;

    public function __construct(array|string|null $extensions = null, array|string|null $excludePaths = null) {
        if (is_array($extensions) && count($extensions) > 0) {
            $this->allowedExtensions = array_map(function($ext) {
                return ltrim(strtolower($ext), '.');
            }, $extensions);
        } elseif (is_string($extensions)) {
            $this->allowedExtensions = [ltrim(strtolower($extensions), '.')];
        }

        $this->setExcludedPaths($excludePaths);
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

    private function normalizePath(string $path): string {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');
        return $normalized ?: '/';
    }


    function getFileCRC32(string $filePath): string|false {
        // Открываем файл
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        // Сохраняем оригинальные данные
        $originalContent = $content;

        // ✅ Оригинальный CRC32
        $originalHash = hash('crc32b', $content);

        // Проверяем расширение файла
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
            // Проверяем наличие BOM
            $hasBOM = strncmp($content, "\xEF\xBB\xBF", 3) === 0;

            // Нормализуем переносы строк
            $content = str_replace(["\r\n", "\r"], "\n", $content);

            // Восстанавливаем BOM если он был
            if ($hasBOM && strncmp($content, "\xEF\xBB\xBF", 3) !== 0) {
                $content = "\xEF\xBB\xBF" . $content;
            }
        }

        // Если контент был изменен, возвращаем новый хеш
        if ($content !== $originalContent) {
            return hash('crc32b', $content);
        }

        // Иначе возвращаем оригинальный хеш
        return $originalHash;
    }

    public function scan(string $directory): array {
        $directory = $this->normalizePath($directory);

        if (!is_dir($directory)) {
            throw new RuntimeException("Директория '{$directory}' не существует");
        }

        $this->result = [];
        $this->scanDirectory($directory);

        return $this->result;
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

    private function isExcludedPath(string $path): bool {
        foreach ($this->excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
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
}
