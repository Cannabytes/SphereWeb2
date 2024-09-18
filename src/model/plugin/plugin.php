<?php

namespace Ofey\Logan22\model\plugin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class plugin
{

    private static ?array $plugins = null;

    static public function getPlugins(): array
    {
        if (self::$plugins == null) {
            self::loading();
        }

        return self::$plugins;
    }

    static public function loading(): void
    {
        $pluginList = [];
        $configData = sql::getRow(
          "SELECT * FROM `settings` WHERE `key` = '__PLUGIN__'"
        );
        $serverId = user::self()->getServerId();

        if ($configData) {
            $plugins = json_decode($configData['setting'], true);
            if (!empty($plugins)) {
                $pluginKeys = array_map(fn($plugin) => "'__PLUGIN__{$plugin}'", $plugins);
                $inClause = implode(',', $pluginKeys);
                $settings = sql::getRows(
                  "SELECT * FROM `settings` WHERE `key` IN ($inClause) AND serverId = ?",
                  [$serverId]
                );
                $settingsMap = [];
                foreach ($settings as $setting) {
                    $plugin = str_replace('__PLUGIN__', '', $setting['key']);
                    $settingsMap[$plugin] = json_decode($setting['setting'], true);
                }

                foreach ($plugins as $plugin) {
                    $pluginSetting = new DynamicPluginSetting();
                    $pluginSetting->pluginName = $plugin;
                    $pluginSetting->pluginServerId = $serverId;

                    // Загрузка стандартных данных плагина из tpl::pluginsAll()
                    foreach (tpl::pluginsAll() as $key => $value) {
                        if ($value['PLUGIN_DIR_NAME'] == $plugin) {
                            foreach ($value as $k => $v) {
                                $pluginSetting->setPluginData($k, $v);
                            }
                        }
                    }

                    // Загрузка пользовательских настроек плагина, если они есть
                    if (isset($settingsMap[$plugin])) {
                        foreach ($settingsMap[$plugin] as $key => $value) {
                            $pluginSetting->$key = $value;
                        }
                    }

                    // Добавление плагина в список активных плагинов
                    $pluginList[$plugin] = $pluginSetting;
                }
            }
        }

        self::$plugins = $pluginList;
    }


    static public function getPluginActive($name = null)
    {
        if ($name === null) {
            return self::getAllPlugins();
        }

        return self::$plugins[$name] ?? false;
    }

    static public function getAllPlugins()
    {
        return self::$plugins;
    }

    public static function saveSetting()
    {
        $pluginConfig = self::get($_POST['pluginName']);
        $pluginConfig->save();
    }

    public static function get(string $getNameClass): DynamicPluginSetting
    {
        if (self::$plugins[$getNameClass]) {
            return self::$plugins[$getNameClass];
        }
        $pl                 = new DynamicPluginSetting();
        $pl->pluginName     = $getNameClass;
        $pl->pluginServerId = user::self()->getServerId();

        return $pl;
    }

    public static function __save_activator_plugin(): void
    {
        $pluginName = $_POST['pluginName'] ?? null;
        $setting    = $_POST['setting'] ?? null;
        $value      = $_POST['value'] ?? null;

        if ( ! $pluginName || ! $setting) {
            return;
        }

        $serverId = user::self()->getServerId();
        if ($serverId == null) {
            board::error("У Вас нет выбранного сервера");
        }

        if ($setting === 'enablePlugin') {
            $isEnabled = filter_var($value, FILTER_VALIDATE_BOOL);

            if ($isEnabled && !in_array($pluginName, array_keys(self::$plugins))) {
                self::$plugins[$pluginName] = $pluginName;
            } elseif (!$isEnabled && isset(self::$plugins[$pluginName])) {
                unset(self::$plugins[$pluginName]);
            }

            // Получение только ключей активных плагинов
            $activePlugins = array_keys(self::$plugins);
            sql::sql("DELETE FROM `settings` WHERE `key` = ? AND `serverId` = ?", ['__PLUGIN__', $serverId]);
            sql::run(
              "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
              ['__PLUGIN__', json_encode($activePlugins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $serverId, time::mysql()]
            );
            board::success("OK");
        }

    }

    public static function getSetting(string $getNameClass)
    {
        $pluginData = self::get($getNameClass)->getPluginData();
        $customData = self::get($getNameClass)->getAllData();

        return array_merge($pluginData, $customData);
    }

}

