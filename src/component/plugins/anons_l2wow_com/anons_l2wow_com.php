<?php

namespace Ofey\Logan22\component\plugins\anons_l2wow_com;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;
use Ofey\Logan22\model\user\userModel;

class anons_l2wow_com
{
    public function __construct()
    {
        tpl::addVar('setting', plugin::getSetting("anons_l2wow_com"));
        tpl::addVar("pluginName", "anons_l2wow_com");
        tpl::addVar("pluginActive", (bool)plugin::getPluginActive("anons_l2wow_com") ?? false);
        
        // Проверяем и создаем таблицу если её нет
        $this->ensureTablesExist();
    }
    
    /**
     * Проверка и создание таблиц если их нет
     */
    private function ensureTablesExist()
    {
        if (class_exists('Ofey\Logan22\component\plugins\anons_l2wow_com\installer')) {
            if (!installer::tableExists()) {
                installer::createTables();
            }
        }
    }

    /**
     * Отображение страницы настроек
     */
    public function setting()
    {
        validation::user_protection("admin");
        
        $servers = server::getServerAll();
        $serverSettings = [];
        
        if ($servers) {
            foreach ($servers as $srv) {
                $config = $srv->getPluginSetting("anons_l2wow_com") ?? [];
                $serverSettings[$srv->getId()] = [
                    'serverId' => $srv->getId(),
                    'serverName' => $srv->getName(),
                    'webhookKey' => $config['webhookKey'] ?? '',
                    'voteLevels' => $config['voteLevels'] ?? [],
                ];
            }
        }
        
        tpl::addVar([
            'servers' => $servers ?? [],
            'serverSettings' => $serverSettings,
        ]);
        tpl::displayPlugin("anons_l2wow_com/tpl/setting.html");
    }

    /**
     * Сохранение настроек
     */
    public function save()
    {
        validation::user_protection("admin");

        // Получить JSON данные если они есть
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }

        if (empty($input['serverId'])) {
            board::error(lang::get_phrase("error_select_server"));
        }

        $serverId = (int)($input['serverId'] ?? 0);
        $webhookKey = trim($input['webhookKey'] ?? '');
        $voteLevels = $input['voteLevels'] ?? [];

        // Обработка и валидация уровней голосов
        $processedLevels = [];

        foreach ($voteLevels as $level) {
            $voteCount = (int)($level['voteCount'] ?? 0);
            
            if ($voteCount < 1) {
                continue;
            }

            $items = [];
            foreach ($level['items'] ?? [] as $item) {
                if (empty($item['itemId'])) {
                    continue;
                }

                $items[] = [
                    'itemId' => (int)$item['itemId'],
                    'count' => max(1, (int)($item['count'] ?? 1)),
                    'enchant' => (int)($item['enchant'] ?? 0),
                ];
            }

            if (!empty($items)) {
                $processedLevels[] = [
                    'voteCount' => $voteCount,
                    'items' => $items,
                ];
            }
        }

        // Сортировка уровней по количеству голосов
        usort($processedLevels, function($a, $b) {
            return $a['voteCount'] - $b['voteCount'];
        });

        // Сохранение на сервере
        $srv = server::getServer($serverId);
        if ($srv) {
            $srv->setPluginSetting("anons_l2wow_com", [
                'webhookKey' => $webhookKey,
                'voteLevels' => $processedLevels,
            ]);
        }

        board::success(lang::get_phrase(581)); // "Параметры сохранены"
    }

    /**
     * Прием уведомления о голосовании с l2wow.com
     */
    public function receiveVote($serverId)
    {
        // Проверяем, что плагин активен
        if (!plugin::getPluginActive("anons_l2wow_com")) {
            http_response_code(503);
            die(json_encode(['success' => false, 'message' => 'Plugin disabled']));
        }

        // Получаем serverId из URL
        $serverId = (int)$serverId;
        
        // Получаем данные из POST (form-data от L2WOW.COM)
        $webhookKey = $_POST['webhook_key'] ?? '';
        $email = $_POST['email'] ?? '';
        $votes = (int)($_POST['votes'] ?? 0);
        
        // Проверяем существование сервера
        $srv = server::getServer($serverId);
        if (!$srv) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Server not found']));
        }

        // Получаем конфигурацию сервера
        $config = $srv->getPluginSetting("anons_l2wow_com");
        if (empty($config)) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Server not configured']));
        }

        // Проверяем webhook_key для защиты
        $savedWebhookKey = $config['webhookKey'] ?? '';
        if (!empty($savedWebhookKey) && $savedWebhookKey !== $webhookKey) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Invalid webhook key']));
        }

        // Валидация входных данных
        if (empty($email)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Email is required']));
        }

        if ($votes < 1 || $votes > 5) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Vote count must be between 1 and 5']));
        }

        // Получаем награды для этого количества голосов
        $voteLevels = $config['voteLevels'] ?? [];
        $selectedLevel = null;
        
        foreach ($voteLevels as $level) {
            if ($level['voteCount'] == $votes) {
                $selectedLevel = $level;
                break;
            }
        }

        if ($selectedLevel === null || empty($selectedLevel['items'])) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'No rewards configured for ' . $votes . ' votes']));
        }

        // Находим пользователя по email
        $user = sql::getRow("SELECT id FROM users WHERE email = ?", [$email]);
        if (!$user) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'User not found']));
        }

        // Добавляем предметы в warehouse пользователя
        try {
            $userModel = new userModel($user['id']);
            $sentItems = [];
            
            foreach ($selectedLevel['items'] as $item) {
                $userModel->addToWarehouse(
                    $serverId,
                    $item['itemId'],
                    $item['count'],
                    $item['enchant'],
                    'l2wow_vote_reward'
                );
                
                $sentItems[] = [
                    'itemId' => $item['itemId'],
                    'count' => $item['count'],
                    'enchant' => $item['enchant'],
                ];
            }

            // Логирование успешной отправки
            sql::run(
                "INSERT INTO plugin_anons_l2wow_log (user_id, email, server_id, character_name, vote_count, items, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $user['id'],
                    $email,
                    $serverId,
                    '',
                    $votes,
                    json_encode($sentItems)
                ]
            );

            http_response_code(200);
            die(json_encode([
                'success' => true,
                'message' => 'Rewards added to warehouse successfully',
                'items' => $sentItems
            ]));

        } catch (\Exception $e) {
            error_log("L2WOW Vote error: " . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Error adding rewards: ' . $e->getMessage()
            ]));
        }
    }

}
