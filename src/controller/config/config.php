<?php

namespace Ofey\Logan22\controller\config;

use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;

class config
{

    private static ?\Ofey\Logan22\model\config\config $config = null;

    public static function setLang($lang): void
    {
        user::self()->setLang($lang);
    }

    /**
     * @return \Ofey\Logan22\model\config\config|void|null
     */
    public static function load()
    {
        if (self::$config !== null) {
            return self::$config;
        }
        self::$config = new \Ofey\Logan22\model\config\config();
    }

}