<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\cache\PaymentSystemSort;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\config\donate;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class donateGlobal
{
    public static function showPlugins(): void
    {
        validation::user_protection("admin");

        // Get all plugins with category 'paysystem' using tpl::pluginsAll()
        $allPlugins = tpl::pluginsAll();
        $paymentPlugins = [];
        
        foreach ($allPlugins as $plugin) {
            if (isset($plugin['PLUGIN_CATEGORY']) && $plugin['PLUGIN_CATEGORY'] === 'paysystem') {
                if (!isset($plugin['PLUGIN_ENABLE']) || $plugin['PLUGIN_ENABLE']) {
                    $paymentPlugins[] = $plugin;
                }
            }
        }

        // Apply saved sort order
        $paymentPlugins = PaymentSystemSort::applySortOrder($paymentPlugins);

        tpl::addVar([
            'paymentPlugins' => $paymentPlugins,
        ]);

        tpl::display('/admin/donate_plugins.html');
    }

    /**
     * Save the sort order for paysystem plugins.
     * Expects JSON body: { "order": ["plugin_dir1", "plugin_dir2", ...] }
     */
    public static function savePaysystemSort(): void
    {
        validation::user_protection("admin");

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!isset($data['order']) || !is_array($data['order'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid order data']);
            return;
        }

        // Sanitise: allow only non-empty strings
        $order = array_values(array_filter(array_map('strval', $data['order']), function ($v) {
            return $v !== '';
        }));

        $saved = PaymentSystemSort::save($order);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $saved]);
    }

    public static function show(): void
    {
        validation::user_protection("admin");

        // Get serverId from URL parameter or use global (0)
        $serverId = (int)($_GET['serverId'] ?? 0);
        
        // Get all servers for navigation
        $servers = \Ofey\Logan22\model\server\server::getServerAll() ?? [];
        
        // Get current server info
        $currentServer = null;
        if ($serverId === 0) {
            // For global settings, create a dummy server object
            $currentServer = (object)[
                'id' => 0,
                'name' => 'Global',
                'rateExp' => 1
            ];
        } else {
            $currentServer = \Ofey\Logan22\model\server\server::getServer($serverId);
        }

        $globalDonate = new donate($serverId);

        tpl::addVar([
            'globalDonate' => $globalDonate,
            'currentServerId' => $serverId,
            'currentServer' => $currentServer,
            'allServers' => $servers,
        ]);

        tpl::display('/admin/donate_global.html');
    }

    public static function save(): void
    {
        validation::user_protection("admin");

        // Get serverId from POST data
        $serverId = (int)($_POST['serverId'] ?? 0);

        $post = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if (!$post) {
            board::error("Ошибка парсинга JSON");
        }

        $data = json_decode($post, true);
        if (!isset($data['donateSystems']) || !is_array($data['donateSystems'])) {
            $data['donateSystems'] = [];
        }

        foreach ($data['donateSystems'] as $index => $system) {
            $systemData = reset($system);
            if (empty($systemData['inputs'])) {
                unset($data['donateSystems'][$index]);
            }
        }

        // Use serverId from POST
        $data['serverId'] = $serverId;
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);

        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_donate__' AND serverId = ?", [$serverId]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_donate__', ?, ?, ?)", [
            $post,
            $serverId,
            time::mysql(),
        ]);

        user::self()->addLog(logTypes::LOG_SAVE_CONFIG, 581);
        board::success("Глобальные настройки доната сохранены");
    }

    public static function getDonateSetting(): void
    {
        validation::user_protection("admin");

        // Get serverId from POST data
        $serverId = (int)($_POST['serverId'] ?? 0);

        $row = sql::getRow("SELECT `setting` FROM `settings` WHERE `key` = '__config_donate__' AND serverId = ?", [$serverId]);

        header('Content-Type: application/json; charset=utf-8');
        if ($row && !empty($row['setting'])) {
            echo $row['setting'];
            return;
        }

        echo '{}';
    }

    public static function copySettingsFromServer(): void
    {
        validation::user_protection("admin");

        $sourceServerId = (int)($_POST['sourceServerId'] ?? 0);
        $targetServerId = (int)($_POST['targetServerId'] ?? 0);

        // Get settings from source server
        $sourceRow = sql::getRow("SELECT `setting` FROM `settings` WHERE `key` = '__config_donate__' AND serverId = ?", [$sourceServerId]);

        if (!$sourceRow || empty($sourceRow['setting'])) {
            board::error("Настройки для исходного сервера не найдены");
            return;
        }

        // Copy settings to target server
        $setting = $sourceRow['setting'];
        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_donate__' AND serverId = ?", [$targetServerId]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_donate__', ?, ?, ?)", [
            $setting,
            $targetServerId,
            time::mysql(),
        ]);

        user::self()->addLog(logTypes::LOG_SAVE_CONFIG, 581);
        board::success("Настройки скопированы от сервера #" . $sourceServerId . " на сервер #" . $targetServerId);
    }
}
