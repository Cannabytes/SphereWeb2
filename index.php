<?php
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '');
if (preg_match('~\.(?:ico|png|apng|jpe?g|gif|webp|svg|avif|bmp|css|m?js|map|woff2?|ttf|otf|eot|mp4|webm|mp3|ogg|wav)$~i', $requestPath)
    && !is_file(__DIR__ . '/' . ltrim($requestPath, '/'))) {
    http_response_code(404);
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.txt');
require __DIR__ . '/vendor/autoload.php';
\Ofey\Logan22\component\error\error::init();
Ofey\Logan22\component\version\version::check_version_php();
Ofey\Logan22\component\fileSys\fileSys::set_root_dir(__DIR__);
require __DIR__ . '/src/route/route_registry.php';
