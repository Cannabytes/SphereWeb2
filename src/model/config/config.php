<?php

namespace Ofey\Logan22\model\config;

use JetBrains\PhpStorm\NoReturn;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\log\log;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;

class config
{

    private lang $lang;

    private ?captcha $captcha = null;

    private ?onlineCheating $onlineCheating = null;

    private ?registration $registration = null;

    private ?email $email = null;

    private ?cache $cache = null;

    private ?other $other = null;

    private ?template $template = null;

    private ?referral $referral = null;

    private ?enabled $enabled = null;

    private ?donate $donate = null;

    private ?github $github = null;

    private ?logo $logo = null;

    private ?palette $palette = null;

    private ?sphereApi $sphereApi = null;

    public ?menu $menu = null;

    private ?background $background = null;

    /**
     * Сохранения конфигурации
     */
    public static function save($configName = null): void
    {
        if (isset($_POST['__config_name__'])) {
            $configName = $_POST['__config_name__'];
            unset($_POST['__config_name__']);
        } elseif ($configName == null) {
            board::error("Not config name");
        } else {
            board::error("Not config name");
        }

        if($configName=='__config_other__'){
            if (!empty($_POST['isExchangeRates']) && filter_var($_POST['isExchangeRates'], FILTER_VALIDATE_BOOLEAN)) {
                $exchanger = \Ofey\Logan22\component\sphere\server::send(type::EXCHANGER)->show(true)->getResponse();
                if (!empty($exchanger['rates'])) {
                    $_POST['exchangeRates'] = $exchanger['rates'];
                }
            }
        }

        $post = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if ( ! $post) {
            board::error("Ошибка парсинга JSON");
        }
        sql::sql("DELETE FROM `settings` WHERE `key` = ? AND serverId = ? ", [
            $configName,
            0,
        ]);

        sql::run(
            "INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES (?, ?, ?, ?)",
            [
                $configName,
                html_entity_decode($post),
                0,
                time::mysql(),
            ]
        );
        user::self()->addLog(logTypes::LOG_SAVE_CONFIG, 581);
        board::success("Настройки сохранены");
    }

    public static array $settings;

    static function findConfigByKeySetting(string $searchKey ): ?array {
        foreach (self::$settings as $config) {
            if (isset($config['key']) && $config['key'] === $searchKey) {
                return $config['setting'];
            }
        }
        return null;
    }

    static function findConfigByKey(string $searchKey ): ?array {
        foreach (self::$settings as $config) {
            if (isset($config['key']) && $config['key'] === $searchKey) {
                return $config;
            }
        }
        return null;
    }

    private array $donateArr = [];
    /**
     * Загрузка конфигов
     */
    public function __construct()
    {
        self::$settings = sql::getRows("SELECT * FROM `settings`" );
        foreach(self::$settings AS &$setting){
            $setting['setting'] = json_decode($setting['setting'], true);
        }
        $this->lang = new lang(self::findConfigByKeySetting('__config_lang__'));
        // var_dump($this->lang->getPhrase(0));exit();

        $this->captcha = new captcha(self::findConfigByKeySetting('__config_captcha__'));
        $this->onlineCheating = new onlineCheating(self::findConfigByKeySetting('__config_cheating__'));
        $this->registration = new registration(self::findConfigByKeySetting('__config_registration__'));
        $this->email = new email(self::findConfigByKeySetting('__config_email__'));
        $this->cache = new cache(self::findConfigByKeySetting('__config_cache__'));
        $this->other = new other(self::findConfigByKeySetting('__config_other__'));
        $this->template = new template(self::findConfigByKeySetting('__config_template__'));
//        var_dump($this->lang()->getDefault());exit;
        //Тут нужно ещё и ID сервера
//        self::$referral = new referral(self::findConfigByKeySetting('__config_referral__'));
//        var_dump(self::findConfigByKey('__config_donate__'));exit;
//        $this->donate = new donate(self::findConfigByKey('__config_donate__'));
//        foreach(self::$settings AS $getsetting){
//            if($getsetting['key'] == '__config_donate__'){
//                $this->donateArr[$getsetting['serverId']] = new donate($getsetting['serverId']);
//            }
//        }
        $this->enabled = new enabled(self::findConfigByKeySetting('__config_enabled__'));
// DEPCRICATED        self::$github = new github(self::findConfigByKeySetting('__config_github__'));
        $this->logo = new logo(self::findConfigByKeySetting('__config_logo__'));
        $this->palette = new palette(self::findConfigByKeySetting('__config_palette__'));

        $this->sphereApi = new sphereApi(self::findConfigByKeySetting('__config_sphere_api__'));
        //  var_dump(mt_rand(1,999));exit;
        // $this->menu = new menu(self::findConfigByKeySetting('__config_menu__'), $this->lang);
        $this->background = new background(self::findConfigByKeySetting('__config_background__'));

    }

    /**
     * Информация о языках
     *
     * @return lang
     */
    public function lang(): lang
    {
        return $this->lang;
    }

    /**
     * Информация о капчи
     */
    public function captcha(): captcha
    {
        return $this->captcha;
    }

    /**
     * Информация о накрутки онлайна
     */
    public function onlineCheating(): onlineCheating
    {
        return $this->onlineCheating;
    }

    /**
     * Информация о настройках регистрации
     */
    public function registration(): registration
    {
        return $this->registration;
    }

    /**
     * Информация о настройках Email (для авторизации)
     */
    public function email(): email
    {
        return $this->email;
    }

    /**
     * Информация о кешировании
     */
    public function cache(): cache
    {
        return $this->cache;
    }

    /**
     * Информация о кешировании
     */
    public function other(): other
    {
        return $this->other;
    }

    /**
     * Информация о шаблоне
     */
    public function template(): template
    {
        return $this->template;
    }

    /**
     * Информация о реферал конфиге
     */
    public function referral(): referral
    {
        return server::getServer(user::self()->getServerId())->getRefferal();
    }

    /**
     * Информация о вкл/выкл функциях
     */
    public function enabled(): enabled
    {
        return $this->enabled;
    }

    public function donate($id = null)
    {
        return server::getServer(user::self()->getServerId())->getDonateConfig();
    }

    public function github(): ?github
    {
        return $this->github;
    }

    public function logo(): ?logo
    {
        return $this->logo;
    }

    public function palette(): ?palette
    {
        return $this->palette;
    }

    public function sphereApi(): ?sphereApi
    {
        return $this->sphereApi;
    }

    public function menu(): ?menu
    {
        return $this->menu;
    }

    public function background()
    {
        return $this->background;
    }

}