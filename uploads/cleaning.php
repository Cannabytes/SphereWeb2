<?php

function deleteLogFiles($directory): void
{
    // Рекурсивный итератор директорий
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'log') {
            @unlink($file->getPathname());
        }
    }
}

$path = 'src/component/donate/';

if (is_dir($path)) {
    deleteLogFiles($path);
}
