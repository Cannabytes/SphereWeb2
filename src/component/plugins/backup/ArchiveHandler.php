<?php

namespace backup;

class ArchiveHandler
{
    private string $archiveFormat;
    private string $archivePath;
    private $archiveResource;

    public function __construct(string $format = 'zip', string $path = '')
    {
        $this->archiveFormat = $format;
        $this->archivePath = $path;
    }

    /**
     * Get current archive format
     */
    public function getFormat(): string
    {
        return $this->archiveFormat;
    }

    /**
     * Создать новый архив
     */
    public function create(): bool
    {
        if ($this->archiveFormat === 'zip') {
            return $this->createZip();
        } elseif ($this->archiveFormat === 'gzip' || $this->archiveFormat === 'bzip2') {
            return $this->createTar();
        }
        return false;
    }

    /**
     * Создать ZIP архив
     */
    private function createZip(): bool
    {
        try {
            $zip = new \ZipArchive();
            $dir = dirname($this->archivePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $res = $zip->open($this->archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if ($res !== true) {
                return false;
            }

            $this->archiveResource = $zip;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Создать TAR архив (gzip или bzip2)
     */
    private function createTar(): bool
    {
        try {
            // Для TAR используем простую в-памяти обработку
            // так как PharData может быть недоступна на некоторых хостингах
            $this->archiveResource = [
                'files' => [],
                'format' => $this->archiveFormat,
            ];
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Добавить файл в архив
     */
    public function addFile(string $filePath, string $arcPath = ''): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        if (empty($arcPath)) {
            $arcPath = basename($filePath);
        }

        if ($this->archiveFormat === 'zip') {
            $ok = @ $this->archiveResource->addFile($filePath, $arcPath);
            return $ok === true || $ok === 1;
        } else {
            // Для TAR сохраняем метаданные
            $this->archiveResource['files'][] = [
                'path' => $filePath,
                'arcPath' => $arcPath,
            ];
            return true;
        }
    }

    /**
     * Добавить данные в архив (например, SQL содержимое)
     */
    public function addData(string $data, string $filename): bool
    {
        if ($this->archiveFormat === 'zip') {
            $ok = @ $this->archiveResource->addFromString($filename, $data);
            return $ok === true || $ok === 1;
        } else {
            // Для TAR сохраняем в виде временного файла
            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '_' . $filename;
            if (file_put_contents($tempFile, $data) === false) {
                return false;
            }
            $this->archiveResource['files'][] = [
                'path' => $tempFile,
                'arcPath' => $filename,
                'temp' => true,
            ];
            return true;
        }
    }

    /**
     * Закрыть архив и сохранить
     */
    public function close(): bool
    {
        if ($this->archiveFormat === 'zip') {
            $ok = @ $this->archiveResource->close();
            if ($ok === true || $ok === 1) {
                return true;
            }

            $err = error_get_last();
            $msg = $err['message'] ?? 'Unknown error during ZipArchive close';
            throw new \Exception('ZipArchive close failed: ' . $msg);
        } else {
            // Для TAR создаем архив вручную
            return $this->createTarArchive();
        }
    }

    /**
     * Создать TAR архив с помощью встроенных функций
     */
    private function createTarArchive(): bool
    {
        try {
            $tarPath = $this->archivePath;
            if ($this->archiveFormat === 'gzip') {
                $tarPath = str_replace('.tar.gz', '.tar', $tarPath);
            } elseif ($this->archiveFormat === 'bzip2') {
                $tarPath = str_replace('.tar.bz2', '.tar', $tarPath);
            }

            // Используем PharData если доступна
            if (class_exists('PharData')) {
                $phar = new \PharData($tarPath);

                foreach ($this->archiveResource['files'] as $file) {
                    if (!isset($file['temp'])) {
                        $phar->addFile($file['path'], $file['arcPath']);
                    }
                }

                if ($this->archiveFormat === 'gzip') {
                    $phar->compress(\Phar::GZ);
                    @unlink($tarPath);
                } elseif ($this->archiveFormat === 'bzip2') {
                    $phar->compress(\Phar::BZ2);
                    @unlink($tarPath);
                }

                return true;
            } else {
                // Fallback: создать ZIP вместо TAR
                return $this->createZipFallback();
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fallback на ZIP, если TAR недоступен
     */
    private function createZipFallback(): bool
    {
        $newPath = str_replace(['.tar.gz', '.tar.bz2'], '.zip', $this->archivePath);

        try {
            $zip = new \ZipArchive();
            $dir = dirname($newPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $res = $zip->open($newPath, \ZipArchive::CREATE);
            if ($res !== true) {
                return false;
            }

            foreach ($this->archiveResource['files'] as $file) {
                if (file_exists($file['path']) && is_readable($file['path'])) {
                    @ $zip->addFile($file['path'], $file['arcPath']);
                }
            }

            @ $zip->close();
            return @rename($newPath, $this->archivePath) || file_exists($newPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить размер архива
     */
    public function getSize(): float
    {
        if (file_exists($this->archivePath)) {
            return filesize($this->archivePath);
        }
        return 0;
    }

    /**
     * Проверить валидность архива
     */
    public function isValid(): bool
    {
        if (!file_exists($this->archivePath)) {
            return false;
        }

        if ($this->archiveFormat === 'zip') {
            try {
                $zip = new \ZipArchive();
                $result = $zip->open($this->archivePath);
                if ($result === true) {
                    $zip->close();
                    return true;
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}
