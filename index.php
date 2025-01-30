<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.txt');
require __DIR__ . '/vendor/autoload.php';
//\Ofey\Logan22\component\error\error::init();
Ofey\Logan22\component\version\version::check_version_php();
Ofey\Logan22\component\fileSys\fileSys::set_root_dir(__DIR__);
require __DIR__ . '/src/route/route_registry.php';
