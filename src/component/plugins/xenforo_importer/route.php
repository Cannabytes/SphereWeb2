<?php

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\plugins\xenforo_importer\xenforo_importer;
use Ofey\Logan22\component\plugins\xenforo_importer\system\XenForoConnection;
use Ofey\Logan22\component\plugins\xenforo_importer\system\XenForoImporter;
use Ofey\Logan22\component\plugins\xenforo_importer\system\StreamingImporter;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;

$routes = [
    // Главная страница плагина
    [
        "method" => "GET",
        "pattern" => "/admin/xenforo/importer",
        "file" => "xenforo_importer.php",
        "call" => function () {
            (new xenforo_importer())->index();
        },
    ],
    
    // Streaming импорт с real-time прогрессом
    [
        "method" => "GET",
        "pattern" => "/admin/xenforo/importer/stream-import",
        "file" => "xenforo_importer.php",
        "call" => function () {
            validation::user_protection("admin");
            
            $config = [
                'host' => $_GET['host'] ?? '',
                'port' => (int)($_GET['port'] ?? 3306),
                'database' => $_GET['database'] ?? '',
                'username' => $_GET['username'] ?? '',
                'password' => $_GET['password'] ?? '',
                'prefix' => $_GET['prefix'] ?? 'xf_',
            ];

            $options = [
                'importUsers' => isset($_GET['import_users']),
                'importCategories' => isset($_GET['import_categories']),
                'importThreads' => isset($_GET['import_threads']),
                'importPosts' => isset($_GET['import_posts']),
            ];

            $connection = new XenForoConnection($config);
            $connection->connect();

            // БЫСТРЫЙ РЕЖИМ (true) - в 10-15 раз быстрее, без загрузки изображений
            // ПОЛНЫЙ РЕЖИМ (false) - медленнее, но с загрузкой изображений сразу
            $fastMode = isset($_GET['fast_mode']) && $_GET['fast_mode'] === '1';
            
            $importer = new StreamingImporter($connection, $fastMode);
            $importer->runStreamingImport($options);
        },
    ],

    // Тест подключения к БД XenForo
    [
        "method" => "POST",
        "pattern" => "/admin/xenforo/importer/test-connection",
        "file" => "xenforo_importer.php",
        "call" => function () {
            validation::user_protection("admin");
            
            // Очистка всех буферов вывода
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            
            try {
                $config = [
                    'host' => $_POST['host'] ?? '',
                    'port' => (int)($_POST['port'] ?? 3306),
                    'database' => $_POST['database'] ?? '',
                    'username' => $_POST['username'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'prefix' => $_POST['prefix'] ?? 'xf_',
                ];

                $connection = new XenForoConnection($config);
                
                if ($connection->testConnection()) {
                    // Получаем статистику из XenForo
                    $pdo = $connection->getConnection();
                    
                    $userCount = $pdo->query("SELECT COUNT(*) as count FROM {$config['prefix']}user WHERE user_state = 'valid'")->fetch()['count'];
                    $nodeCount = $pdo->query("SELECT COUNT(*) as count FROM {$config['prefix']}node WHERE node_type_id IN ('Category', 'Forum')")->fetch()['count'];
                    $threadCount = $pdo->query("SELECT COUNT(*) as count FROM {$config['prefix']}thread WHERE discussion_state = 'visible'")->fetch()['count'];
                    $postCount = $pdo->query("SELECT COUNT(*) as count FROM {$config['prefix']}post WHERE message_state = 'visible'")->fetch()['count'];
                    
                    $connection->close();
                    
                    // Возвращаем JSON ответ
                    header('Content-Type: application/json');
                    echo json_encode([
                        'type' => 'success',
                        'message' => lang::phrase('xenforo_connection_success'),
                        'stats' => [
                            'users' => $userCount,
                            'categories' => $nodeCount,
                            'threads' => $threadCount,
                            'posts' => $postCount,
                        ]
                    ]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'type' => 'error',
                        'message' => lang::phrase('xenforo_connection_failed')
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'type' => 'error',
                    'message' => lang::phrase('xenforo_error') . ': ' . $e->getMessage()
                ]);
                exit;
            }
        },
    ],

    // Запуск импорта
    [
        "method" => "POST",
        "pattern" => "/admin/xenforo/importer/start-import",
        "file" => "xenforo_importer.php",
        "call" => function () {
            validation::user_protection("admin");
            
            // Очистка всех буферов вывода
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            
            try {
                $config = [
                    'host' => $_POST['host'] ?? '',
                    'port' => (int)($_POST['port'] ?? 3306),
                    'database' => $_POST['database'] ?? '',
                    'username' => $_POST['username'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'prefix' => $_POST['prefix'] ?? 'xf_',
                ];

                $options = [
                    'importUsers' => isset($_POST['import_users']),
                    'importCategories' => isset($_POST['import_categories']),
                    'importThreads' => isset($_POST['import_threads']),
                    'importPosts' => isset($_POST['import_posts']),
                ];

                $connection = new XenForoConnection($config);
                $connection->connect();

                $importer = new XenForoImporter($connection);
                $result = $importer->runImport($options);

                $connection->close();

                // Возвращаем JSON ответ
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => lang::phrase('xenforo_import_success'),
                    'stats' => $result['stats']
                ]);
                exit;

            } catch (Exception $e) {
                // Возвращаем JSON с ошибкой
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => lang::phrase('xenforo_error') . ': ' . $e->getMessage()
                ]);
                exit;
            }
        },
    ],
];

return $routes;
