<?php

namespace Ofey\Logan22\model\plugin;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class DynamicPluginSetting
{
    public mixed $pluginName;
    public mixed $pluginServerId;

    private array $data = [];

    // Новое поле для хранения данных, добавленных из плагина
    protected array $pluginData = [];

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function getAllData(): array
    {
        return $this->data;
    }

    public function getPluginData(): array
    {
        return $this->pluginData;
    }


    // Метод для добавления данных из плагина
    public function setPluginData(string $name, $value): void
    {
        $this->pluginData[$name] = $value;
    }

    // Получение всех данных без плагиновых параметров
    public function getFilteredData(): array
    {
        return array_diff_key($this->data, $this->pluginData);
    }

    public function save($data = null)
    {
        if($data == null){
            $data = $_POST;
        }
        $setting = $data['setting'] ?? null;
        $value   = $data['value'] ?? null;
        $type    = $data['type'] ?? 'string';
        $serverId = isset($_POST['serverId']) && $_POST['serverId'] == 0 ? 0 : user::self()->getServerId();
        // Приведение значения к нужному типу
        $value = match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => json_decode($value, true) ?: [],
            default => (string) $value,
        };
        $this->__set($setting, $value);

        // Получаем данные без плагиновых параметров
        $arr = $this->getFilteredData();

        sql::sql("DELETE FROM `settings` WHERE `key` = ? AND `serverId` = ?", [
          '__PLUGIN__' . $this->pluginName,
            $serverId,
        ]);

        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)", [
          '__PLUGIN__' . $this->pluginName,
          json_encode($arr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            $serverId,
          time::mysql(),
        ]);
    }
}
