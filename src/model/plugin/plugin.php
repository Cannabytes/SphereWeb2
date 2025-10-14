<?php

namespace Ofey\Logan22\model\plugin;

use Exception;
use InvalidArgumentException;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use PDO;
use ReflectionClass;
use RuntimeException;

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
        $data = sql::getRows(
            "SELECT * FROM `settings` WHERE `key` = '__PLUGIN__'"
        );

        $serverId = user::self()->getServerId();
        if ($data) {
            foreach($data AS $configData){
                $pluginServerId = $configData['serverId'];
                $plugins = json_decode($configData['setting'], true);
                if (!empty($plugins)) {
                    $pluginKeys = array_map(fn($plugin) => "'__PLUGIN__{$plugin}'", $plugins);
                    $inClause = implode(',', $pluginKeys);
                    $selectDefaultSetting = "";
                    if($serverId != 0){
                        $selectDefaultSetting = " OR `serverId` = 0";
                    }
                    if($serverId == null){
                        $serverId = 0;
                    }
                    $settings = sql::getRows(
                        "SELECT * FROM `settings` WHERE `key` IN ($inClause) AND serverId = ? {$selectDefaultSetting}",
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
                        $pluginSetting->pluginServerId = $pluginServerId;

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
        $serverId = self::resolveServerIdFromRequest();
        if (isset(self::$plugins[$getNameClass])) {
            $plugin = self::$plugins[$getNameClass];
            if ($plugin->pluginServerId === null) {
                $plugin->pluginServerId = $serverId;
            }
            return $plugin;
        }
        $pl                 = new DynamicPluginSetting();
        $pl->pluginName     = $getNameClass;
        $pl->pluginServerId = $serverId;

        return $pl;
    }

    private static function resolveServerIdFromRequest(): int
    {
        $rawServerId = $_POST['serverId'] ?? null;
        if ($rawServerId === '' || $rawServerId === null) {
            $rawServerId = user::self()->getServerId();
        }
        if ($rawServerId === '' || $rawServerId === null) {
            return 0;
        }

        return (int) $rawServerId;
    }

    public static function __save_activator_plugin(): void
    {
        try {
            $pluginName = $_POST['pluginName'] ?? null;
            $setting = $_POST['setting'] ?? null;
            $value = $_POST['value'] ?? null;

            if (!$pluginName || !$setting) {
                throw new InvalidArgumentException('Отсутствуют обязательные параметры');
            }

            // Получаем serverId из POST или используем serverId пользователя
            $serverId = isset($_POST['serverId']) ? (int)$_POST['serverId'] : user::self()->getServerId();

            // Если это общий плагин - установим serverId = 0
            if (isset($_POST['serverId']) && $_POST['serverId'] == 0) {
                $serverId = 0;
            }

            if ($setting === 'enablePlugin') {
                $isEnabled = filter_var($value, FILTER_VALIDATE_BOOL);

                // Получаем текущий список активных плагинов для данного сервера
                $query = sql::run(
                    "SELECT `setting` FROM `settings` WHERE `key` = '__PLUGIN__' AND `serverId` = ?",
                    [$serverId]
                );

                $activePlugins = [];
                if ($query && $row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $activePlugins = json_decode($row['setting'], true) ?: [];
                }

                // Обновляем список активных плагинов
                if ($isEnabled && !in_array($pluginName, $activePlugins)) {
                    $activePlugins[] = $pluginName;
                } elseif (!$isEnabled) {
                    $activePlugins = array_filter($activePlugins, function($plugin) use ($pluginName) {
                        return $plugin !== $pluginName;
                    });
                }

                // Удаляем старую запись
                sql::run(
                    "DELETE FROM `settings` WHERE `key` = '__PLUGIN__' AND `serverId` = ?",
                    [$serverId]
                );

                // Вставляем обновленную запись
                $insertResult = sql::run(
                    "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
                    [
                        '__PLUGIN__',
                        json_encode(array_values($activePlugins), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        $serverId,
                        time::mysql()
                    ]
                );

                if ($insertResult === false) {
                    throw new RuntimeException('Ошибка при сохранении настроек плагина');
                }

                // Сохраняем или обновляем настройки самого плагина
                if ($isEnabled) {
                    // Получаем данные плагина
                    $pluginData = [
                        'showMainPage' => false,
                        'addToMenu' => false,
                    ];

                    // Если плагин уже настроен, получаем его текущие настройки
                    $existingQuery = sql::run(
                        "SELECT `setting` FROM `settings` WHERE `key` = ? AND `serverId` = ?",
                        ["__PLUGIN__{$pluginName}", $serverId]
                    );

                    if ($existingQuery && $row = $existingQuery->fetch(PDO::FETCH_ASSOC)) {
                        $existingSettings = json_decode($row['setting'], true) ?: [];
                        $pluginData = array_merge($pluginData, $existingSettings);
                    }

                    // Удаляем старые настройки
                    sql::run(
                        "DELETE FROM `settings` WHERE `key` = ? AND `serverId` = ?",
                        ["__PLUGIN__{$pluginName}", $serverId]
                    );

                    // Вставляем обновленные настройки
                    $insertPluginResult = sql::run(
                        "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
                        [
                            "__PLUGIN__{$pluginName}",
                            json_encode($pluginData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            $serverId,
                            time::mysql()
                        ]
                    );

                    if ($insertPluginResult === false) {
                        throw new RuntimeException('Ошибка при сохранении настроек плагина');
                    }
                }

                board::success("Настройки плагина успешно сохранены");
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public static function getSetting(string $getNameClass)
    {
        $pluginData = self::get($getNameClass)->getPluginData();
        $customData = self::get($getNameClass)->getAllData();
        return array_merge($pluginData, $customData);
    }

}

