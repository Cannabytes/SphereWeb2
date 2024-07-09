<?php

error_reporting(E_ALL); // Логировать все ошибки
ini_set('display_errors', 0); // Не показывать ошибки в браузере
ini_set('log_errors', 1); // Логировать ошибки
ini_set('error_log', 'errors.log'); // Путь к файлу логов ошибок

require __DIR__ . '/vendor/autoload.php';
Ofey\Logan22\component\version\version::check_version_php();
Ofey\Logan22\component\fileSys\fileSys::set_root_dir(__DIR__);
require __DIR__ . '/src/route/route_registry.php';
