<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
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

        tpl::addVar([
            'paymentPlugins' => $paymentPlugins,
        ]);

        tpl::display('/admin/donate_plugins.html');
    }

    public static function show(): void
    {
        validation::user_protection("admin");

        $globalDonate = new donate(0);

        tpl::addVar([
            'globalDonate' => $globalDonate,
        ]);

        tpl::display('/admin/donate_global.html');
    }

    public static function save(): void
    {
        validation::user_protection("admin");

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

        $data['serverId'] = 0;
        $post = json_encode($data, JSON_UNESCAPED_UNICODE);

        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_donate__' AND serverId = 0");
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_donate__', ?, 0, ?)", [
            $post,
            time::mysql(),
        ]);

        user::self()->addLog(logTypes::LOG_SAVE_CONFIG, 581);
        board::success("Глобальные настройки доната сохранены");
    }

    public static function getDonateSetting(): void
    {
        validation::user_protection("admin");

        $row = sql::getRow("SELECT `setting` FROM `settings` WHERE `key` = '__config_donate__' AND serverId = 0");

        header('Content-Type: application/json; charset=utf-8');
        if ($row && !empty($row['setting'])) {
            echo $row['setting'];
            return;
        }

        echo '{}';
    }
}
