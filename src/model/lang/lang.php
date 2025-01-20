<?php

namespace Ofey\Logan22\model\lang;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\cache\dir;
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
    private array $phrasesDataOther = [];

    private bool $detectBrowserLang = false;

    private array $allowLanguages = ['en', 'ru'];

    //Загрузка языкового пакета шаблона

    private string $default = 'en';

    private array $pluginCache = [];

    /**
     * Загрузка всех необходимых языковых пакетов
     */
    public function __construct($setting)
    {
        $this->getConfig($setting);
        $this->getLangList();
        //Загрузка языкового пакета
        $this->package();
    }

    /**
     * Загрузка конфигурации языка
     *
     * @return void
     */
    public function getConfig($setting): void
    {
        $this->detectBrowserLang = filter_var(
            $setting['detectBrowserLang'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $this->allowLanguages = $setting['allow'] ?? $this->allowLanguages;
        $this->default = $setting['default'] ?? $this->default;
    }

    public function isDetectBrowserLang(): bool
    {
        return $this->detectBrowserLang;
    }

    public function getLangList(): array
    {
        if ($this->langList !== null) {
            return $this->langList;
        }
        $lngs = fileSys::get_dir_files("data/languages/", [
            'basename' => true,
            'fetchAll' => true,
        ]);

        $lngs = array_map(function ($item) {
            return preg_replace('/\.php$/', '', $item);
        }, array_filter($lngs, function ($item) {
            return substr($item, -4) === '.php';
        }));

        $lang_name = $this->lang_user_default();
        $langs = [];
        foreach ($lngs as $lng) {
            $isActive = ($lng == $lang_name);
            $langs[] = new langStruct($lng, $this->name($lng), $isActive);
        }

        usort($langs, function ($a, $b) {
            return $b->getIsActive() <=> $a->getIsActive();
        });

        return $this->langList = $langs;
    }

    public function lang_user_default(): string
    {
        //Если включено определение языка браузера
        if ($this->isDetectBrowserLang() and !isset($_SESSION['lang'])) {
            $_SESSION['lang'] = mb_strtolower($this->getBrowserLanguage($this->allowLanguages, $this->default));
        } else {
            $lang_name = $_SESSION['lang'] ?? $this->default;
            $_SESSION['lang'] = mb_strtolower($lang_name);
        }
        return $_SESSION['lang'];
    }


    /**
     * Определяет предпочтительный язык браузера.
     *
     * @param array $availableLanguages Список доступных языков (например, ['en', 'ru', 'fr']).
     * @param string|null $default Язык по умолчанию, если язык не может быть определён.
     * @return string Определённый язык.
     */
    function getBrowserLanguage(array $availableLanguages, ?string $default = 'en'): string
    {
        // Проверяем, передан ли заголовок HTTP_ACCEPT_LANGUAGE.
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($acceptLanguage)) {
            return $default;
        }

        // Парсим заголовок и упорядочиваем по приоритету.
        $langs = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', $lang);
            $code = strtolower(trim($parts[0]));
            $priority = isset($parts[1]) ? (float)$parts[1] : 1.0;
            $langs[$code] = $priority;
        }

        // Сортируем языки по убыванию приоритета.
        arsort($langs, SORT_NUMERIC);

        // Ищем совпадение доступных языков с языками браузера.
        foreach ($langs as $code => $priority) {
            // Если доступен точный язык (например, "ru-RU"), возвращаем его.
            if (in_array($code, $availableLanguages, true)) {
                return $code;
            }

            // Проверяем общий язык (например, "ru" вместо "ru-RU").
            $primaryCode = explode('-', $code)[0];
            if (in_array($primaryCode, $availableLanguages, true)) {
                return $primaryCode;
            }
        }

        // Если совпадений нет, возвращаем язык по умолчанию.
        return $default;
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
        if (!empty($filename) && file_exists($filename)) {
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
        $this->isActiveLang = $lang;
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

    public function getOtherPhrase($lang = null, $phrase = null, ...$values)
    {
        if ($lang == null) {
            return "no lang {$lang}";
        }
        if ($lang == $this->isActiveLang) {
            return $this->getPhrase($phrase, ...$values);
        }
        if ($this->phrasesDataOther == []) {
            $file = fileSys::get_dir("/data/languages/{$lang}.php");
            if (file_exists($file)) {
                $this->phrasesDataOther = require $file;
                $customLangFile = fileSys::get_dir("/data/languages/custom/{$lang}.php");
                if (file_exists($customLangFile)) {
                    $customData = require $customLangFile;
                    $this->phrasesDataOther = array_replace($this->phrasesDataOther, $customData);
                }
            } else {
                return "no lang {$lang}";
            }
        }
        if (isset($this->phrasesDataOther[$phrase])) {
            $missing_values_count = max(0, substr_count($phrase, '%s') - count($values));
            $default_values = array_fill(0, $missing_values_count, '');
            $values = array_merge($values, $default_values);
            return vsprintf($this->phrasesDataOther[$phrase], $values);
        }
        return "[no phrase {$phrase}]";
    }


    public function save()
    {
        $post = json_encode($_POST);
        if (!$post) {
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

    public function getAllowLang($isAll = true): array
    {
        $langs = [];
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
        if (!array_key_exists($key, $this->phrasesData)) {
            $phrase = $this->get_phrase_plugin($key);
            if (!$phrase) {
                return "[Not phrase «{$key}»]";
            }
            $is_plugin = true;
        }
        if (!$is_plugin) {
            if (array_key_exists($key, $this->cache)) {
                return sprintf($this->cache[$key], ...$values);
            }
            $phrase = $this->phrasesData[$key];
        }

        // Проверяем, достаточно ли аргументов передано
        $missing_values_count = max(0, substr_count($phrase, '%s') - count($values));
        $default_values = array_fill(0, $missing_values_count, ''); // Заполняем массив значениями по умолчанию

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
        // Проверяем наличие кэша плагинов
        if (!empty($this->pluginCache) && array_key_exists($key, $this->pluginCache)) {
            return $this->pluginCache[$key];
        }

        // Обработка пользовательских плагинов
        $customs = fileSys::dir_list("custom/plugins");
        foreach ($customs as $custom) {
            $file = fileSys::get_dir("custom/plugins/{$custom}/lang/{$this->lang_user_default()}.php");

            // Проверяем наличие файла
            if (file_exists($file)) {
                $langArray = include $file;
                if (is_array($langArray)) {
                    $this->pluginCache = array_merge($this->pluginCache, $langArray);
                }
            }
        }

        // Обработка компонентов плагинов
        $components = fileSys::dir_list("src/component/plugins");
        foreach ($components as $component) {
            $file = fileSys::get_dir("/src/component/plugins/{$component}/lang/{$this->lang_user_default()}.php");
            // Проверяем наличие файла
            if (file_exists($file)) {
                $langArray = include $file;
                if (is_array($langArray)) {
                    $this->pluginCache = array_merge($this->pluginCache, $langArray);
                }
            }
        }
        // Проверяем кэш плагинов на наличие ключа после обновления
        if (!empty($this->pluginCache) && array_key_exists($key, $this->pluginCache)) {
            return $this->pluginCache[$key];
        }

        // Возвращаем false, если ключ не найден
        return false;
    }


    public function load_template_lang_packet($tpl)
    {
        $lang_name = $this->lang_user_default();
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