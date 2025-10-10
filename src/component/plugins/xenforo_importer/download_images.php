<?php
/**
 * CLI скрипт для фоновой загрузки изображений
 * Запустить: php download_images.php
 */

require_once __DIR__ . '/../../../../../../../vendor/autoload.php';
require_once __DIR__ . '/../system/BatchImageDownloader.php';

use Ofey\Logan22\component\plugins\xenforo_importer\system\BatchImageDownloader;
use Ofey\Logan22\component\lang\lang;

echo "===========================================\n";
echo "   " . lang::phrase('xenforo_cli_background_download_title') . "\n";
echo "===========================================\n\n";

$downloader = new BatchImageDownloader();

echo lang::phrase('xenforo_cli_starting_download') . "\n\n";

$stats = $downloader->processAllImages();

echo "\n===========================================\n";
echo "   " . lang::phrase('xenforo_cli_stats_title') . "\n";
echo "===========================================\n";
echo lang::phrase('xenforo_cli_posts_processed') . " " . $stats['posts_processed'] . "\n";
echo lang::phrase('xenforo_cli_images_downloaded') . " " . $stats['images_downloaded'] . "\n";
echo lang::phrase('xenforo_cli_images_failed') . " " . $stats['images_failed'] . "\n";
echo lang::phrase('xenforo_cli_time_taken') . " " . $stats['time_taken'] . " sec\n";
echo "===========================================\n";
