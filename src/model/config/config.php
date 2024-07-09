<?php

namespace Ofey\Logan22\model\config;

use JetBrains\PhpStorm\NoReturn;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\log\log;
use Ofey\Logan22\model\log\logTypes;
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
            $post,
            0,
            time::mysql(),
          ]
        );
        user::self()->addLog(logTypes::LOG_SAVE_CONFIG, 581);
        board::success("Настройки сохранены");
    }

    /**
     * Загрузка конфигов
     */
    public function __construct()
    {
        $this->lang = new lang();
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
        if ($this->captcha == null) {
            $this->captcha = new captcha();
        }

        return $this->captcha;
    }

    /**
     * Информация о накрутки онлайна
     */
    public function onlineCheating(): onlineCheating
    {
        if ($this->onlineCheating == null) {
            $this->onlineCheating = new onlineCheating();
        }

        return $this->onlineCheating;
    }

    /**
     * Информация о настройках регистрации
     */
    public function registration(): registration
    {
        if ($this->registration == null) {
            $this->registration = new registration();
        }

        return $this->registration;
    }

    /**
     * Информация о настройках Email (для авторизации)
     */
    public function email(): email
    {
        if ($this->email == null) {
            $this->email = new email();
        }

        return $this->email;
    }

    /**
     * Информация о кешировании
     */
    public function cache(): cache
    {
        if ($this->cache == null) {
            $this->cache = new cache();
        }

        return $this->cache;
    }

    /**
     * Информация о кешировании
     */
    public function other(): other
    {
        if ($this->other == null) {
            $this->other = new other();
        }

        return $this->other;
    }

    /**
     * Информация о шаблоне
     */
    public function template(): template
    {
        if ($this->template == null) {
            $this->template = new template();
        }

        return $this->template;
    }

    /**
     * Информация о реферал конфиге
     */
    public function referral(): referral
    {
        if ($this->referral == null) {
            $this->referral = new referral();
        }

        return $this->referral;
    }

    /**
     * Информация о вкл/выкл функциях
     */
    public function enabled(): enabled
    {
        if ($this->enabled == null) {
            $this->enabled = new enabled();
        }

        return $this->enabled;
    }

    public function donate(): ?donate
    {
        if ($this->donate == null) {
            $this->donate = new donate();
        }
        return $this->donate;
    }

    public function github(): ?github
    {
        if ($this->github == null) {
            $this->github = new github();
        }
        return $this->github;
    }

    public function logo(): ?logo
    {
        if ($this->logo == null) {
            $this->logo = new logo();
        }
        return $this->logo;
    }

    public function palette(): ?palette
    {
        if ($this->palette == null) {
            $this->palette = new palette();
        }
        return $this->palette;
    }

}