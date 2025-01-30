<?php

namespace Ofey\Logan22\component\plugins\fileScanner;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class scannerSystem {
    private array $result = [];
    private ?array $allowedExtensions = null;
    private array $excludedPaths = [];
    private int $bufferSize = 32 * 1024; // Такой же размер буфера как в Go версии

    public function __construct(array|string|null $extensions = null, array|string|null $excludePaths = null) {
        if (is_array($extensions) && count($extensions) > 0) {
            $this->allowedExtensions = array_map('strtolower', $extensions);
        } elseif (is_string($extensions)) {
            $this->allowedExtensions = [strtolower($extensions)];
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
        return rtrim(str_replace('\\', '/', $path), '/') ?: '/';
    }

    private function getFileCRC32(string $filePath): string {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Не удалось открыть файл: {$filePath}");
        }

        $hash = hash_init('crc32b'); // Используем crc32b для совместимости с Go

        while (!feof($handle)) {
            $buffer = fread($handle, $this->bufferSize);
            hash_update($hash, $buffer);
        }

        fclose($handle);

        // Получаем хэш и форматируем его как 8 символов в нижнем регистре
        return sprintf('%08x', hexdec(hash_final($hash)));
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
        return $this->allowedExtensions === null ||
            in_array(strtolower($file->getExtension()), $this->allowedExtensions, true);
    }
}