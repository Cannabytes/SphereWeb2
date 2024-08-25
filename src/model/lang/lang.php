<?php

namespace Ofey\Logan22\model\lang;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Throwable;

class lang
{

    protected array $cache = [];

    /**
     * @var langStruct[]|null
     */
    private ?array $langList = null;

    private ?string $isActiveLang = null;

    private array $phrasesData = [];

    private array $allowLanguages = [];

    //Загрузка языкового пакета шаблона

    private ?string $default = null;

    private array $pluginCache = [];

    /**
     * Загрузка всех необходимых языковых пакетов
     */
    public function __construct()
    {
        $this->getConfig();
        $this->getLangList();
        //Загрузка языкового пакета
        $this->package();
    }

    /**
     * Загрузка конфигурации языка
     *
     * @return void
     */
    public function getConfig(): void
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_lang__'");
        if ( ! $configData) {
            $configData = ['setting' => '{"allow":["ru","en"],"default":"en"}'];
        }
        $setting              = json_decode($configData['setting'], true);
        $this->allowLanguages = $setting['allow'];
        $this->default        = $setting['default'];
    }

    public function getLangList(): array
    {
        if ($this->langList !== null) {
            return $this->langList;
        }
        $lngs      = fileSys::get_dir_files("data/languages/", [
          'basename' => true,
          'fetchAll' => true,
        ]);

        $lngs = array_map(function($item) {
            return preg_replace('/\.php$/', '', $item);
        }, array_filter($lngs, function($item) {
            return substr($item, -4) === '.php';
        }));

        $lang_name = $this->lang_user_default();
        $langs     = [];
        foreach ($lngs as $lng) {
            $isActive = ($lng == $lang_name);
            $langs[]  = new langStruct($lng, $this->name($lng), $isActive);
        }

        usort($langs, function ($a, $b) {
            return $b->getIsActive() <=> $a->getIsActive();
        });

        return $this->langList = $langs;
    }

    public function lang_user_default(): string
    {
        $lang_name        = $_SESSION['lang'] ?? $this->default;
        $_SESSION['lang'] = mb_strtolower($lang_name);

        return $_SESSION['lang'];
    }

    public function name($lang = null)
    {
        if ($lang === null) {
            $lang = $this->default;
        }

        if (empty($lang)) {
            error_log("Language name is empty");

            return null;
        }
        $filename = fileSys::get_dir("/data/languages/{$lang}.php");
        if ( ! empty($filename) && file_exists($filename)) {
            $lang_array = include $filename;
            return $lang_array['lang_name'] ?? null;
        }
        error_log("File $filename not found");

        return null;
    }

    /**
     * Загрузка языкового пакета по-умолчанию
     *
     * @param $dir
     *
     * @return void
     */
    public function package(): void
    {
        $lang = $_SESSION['lang'] ?? $this->default;
        $langFile = fileSys::get_dir("/data/languages/{$lang}.php");
        $defaultLangFile = fileSys::get_dir("/data/languages/en.php");
        try {
            if (file_exists($langFile)) {
                $this->phrasesData = require $langFile;
                $customLangFile = fileSys::get_dir("/data/languages/custom/{$lang}.php");
                if (file_exists($customLangFile)) {
                    $customData = require $customLangFile;
                    $this->phrasesData = array_replace($this->phrasesData, $customData);
                }
            } else {
                $this->phrasesData = require $defaultLangFile;
            }
        } catch (Throwable $e) {
            error_log("Failed to load language file: " . $e->getMessage());
            $this->phrasesData = require $defaultLangFile;
        }
    }


    public function save()
    {
        $post = json_encode($_POST);
        if ( ! $post) {
            board::error("Ошибка парсинга JSON");
        }
        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_lang__' AND serverId = ? ", [
          0,
        ]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_lang__', ?, ?, ?)", [
          $post,
          0,
          time::mysql(),
        ]);
        board::success("Настройки сохранены");
    }

    /**
     * Проверка существующего языка из всех включенных
     *
     * @param $langName
     *
     * @return bool
     */
    public function isAllowLang($langName = null): bool
    {
        foreach ($this->getAllowLang() as $lang) {
            if ($lang->getLang() == $langName) {
                return true;
            }
        }

        return false;
    }

    public function getAllowLang($isAll = true)
    {
        $langs        = [];
        $allLanguages = $this->getLangList();
        /**
         * @var @language langStruct
         */
        foreach ($allLanguages as $language) {
            if (in_array($language->getLang(), $this->allowLanguages)) {
                if (!$isAll) {
                    if ($language->getIsActive()) {
                        continue;
                    }
                }
                $langs[] = $language;
            }
        }

        return $langs;
    }

    /**
     * Проверка существующего языка среди всех
     *
     * @param $langName
     *
     * @return bool
     */
    public function isAllowAllLang($langName = null): bool
    {
        if (in_array($langName, $this->allowLanguages)) {
            return true;
        }

        return false;
    }

    /**
     * Кол-во языков
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->allowLanguages);
    }

    /**
     * Return lang is default set
     *
     * @return string|null
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function getPhrase($key, ...$values): string
    {
        $is_plugin = false;
        if ( ! array_key_exists($key, $this->phrasesData)) {
            $phrase = $this->get_phrase_plugin($key);
            if ( ! $phrase) {
                return "[Not phrase «{$key}»]";
            }
            $is_plugin = true;
        }
        if ( ! $is_plugin) {
            if (array_key_exists($key, $this->cache)) {
                return sprintf($this->cache[$key], ...$values);
            }
            $phrase = $this->phrasesData[$key];
        }

        // Проверяем, достаточно ли аргументов передано
        $missing_values_count = max(0, substr_count($phrase, '%s') - count($values));
        $default_values       = array_fill(0, $missing_values_count, ''); // Заполняем массив значениями по умолчанию

        // Дополняем массив значений по умолчанию переданными значениями
        $values = array_merge($values, $default_values);

        $result = vsprintf($phrase, $values);
        if (empty($values)) {
            $this->cache[$key] = $result;
        }

        return $result;
    }

    public function get_phrase_plugin($key)
    {
        if ( ! empty($this->pluginCache)) {
            if (array_key_exists($key, $this->pluginCache)) {
                return $this->pluginCache[$key];
            }
        }
        $customs = fileSys::dir_list("custom/plugins");
        foreach ($customs as $custom) {
            $file = fileSys::localdir("custom/plugins/" . $custom . "/lang/" . $this->lang_user_default() . ".php");
            if (file_exists($file)) {
                $langArray         = include $file;
                $this->pluginCache = array_merge($this->pluginCache, $langArray);
            }
        }
        $components = fileSys::dir_list("src/component/plugins");
        foreach ($components as $component) {
            $file = fileSys::localdir("data/languages/" . $component . "/lang/" . $this->lang_user_default() . ".php");
            if (file_exists($file)) {
                $langArray         = include $file;
                $this->pluginCache = array_merge($this->pluginCache, $langArray);
            }
        }
        if ( ! empty($this->pluginCache)) {
            if (array_key_exists($key, $this->pluginCache)) {
                return $this->pluginCache[$key];
            }
        }

        return false;
    }

    public function load_template_lang_packet($tpl)
    {
        $lang_name   = $this->lang_user_default();
        $langs_array = require $tpl;
        if (array_key_exists($lang_name, $langs_array)) {
            $this->phrasesData = array_merge($this->phrasesData, $langs_array[$lang_name]);
        }
    }

    public function getAllowLanguages(): array
    {
        return $this->allowLanguages;
    }

}