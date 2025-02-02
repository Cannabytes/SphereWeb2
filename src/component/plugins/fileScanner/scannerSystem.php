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


    private function getFileCRC32(string $filePath): string {
        try {
            if (!file_exists($filePath)) {
                throw new RuntimeException("Файл не существует: {$filePath}");
            }
            if (!is_readable($filePath)) {
                throw new RuntimeException("Файл не доступен для чтения: {$filePath}");
            }

            // Читаем весь файл сразу
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new RuntimeException("Не удалось прочитать файл: {$filePath}");
            }

            // Для текстовых файлов выполняем нормализацию
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $textExtensions = ['php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'md', 'txt', 'svg', 'tpl'];

            if (in_array($ext, $textExtensions)) {
                // Нормализация текстового содержимого
                $content = str_replace(["\r\n", "\r"], "\n", $content);
                $content = rtrim($content);
            }

            // Вычисляем хеш напрямую через crc32
            return sprintf("%08x", crc32($content));

        } catch (Exception $e) {
            throw $e;
        }
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
