<?php

namespace Ofey\Logan22\controller\config;

use Ofey\Logan22\model\config\background;
use Ofey\Logan22\model\config\cache;
use Ofey\Logan22\model\config\captcha;
use Ofey\Logan22\model\config\donate;
use Ofey\Logan22\model\config\email;
use Ofey\Logan22\model\config\enabled;
use Ofey\Logan22\model\config\github;
use Ofey\Logan22\model\config\logo;
use Ofey\Logan22\model\config\menu;
use Ofey\Logan22\model\config\onlineCheating;
use Ofey\Logan22\model\config\other;
use Ofey\Logan22\model\config\palette;
use Ofey\Logan22\model\config\referral;
use Ofey\Logan22\model\config\registration;
use Ofey\Logan22\model\config\sphereApi;
use Ofey\Logan22\model\config\template;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\user\user;

class config
{

    private static lang $lang;

    private static ?captcha $captcha = null;

    private static ?onlineCheating $onlineCheating = null;

    private static ?registration $registration = null;

    private static ?email $email = null;

    private static ?cache $cache = null;

    private static ?other $other = null;

    private static ?template $template = null;

    private static ?referral $referral = null;

    private static ?enabled $enabled = null;

    private static ?donate $donate = null;

    private static ?github $github = null;

    private static ?logo $logo = null;

    private static ?palette $palette = null;

    private static ?sphereApi $sphereApi = null;

    private static ?menu $menu = null;

    private static ?background $background = null;

    private static ?\Ofey\Logan22\model\config\config $config = null;
    private static array $settings;

    public static function setLang($lang): void
    {
        user::self()->setLang($lang);
    }

    public static function load(): \Ofey\Logan22\model\config\config
    {
        if (self::$config !== null) {
            return self::$config;
        }
        self::$config = new \Ofey\Logan22\model\config\config();
        $config_menu = self::$config::findConfigByKeySetting('__config_menu__');
        self::$config->menu = new menu($config_menu);
        return self::$config;
    }

}