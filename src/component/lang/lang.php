<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 30.08.2022 / 20:15:10
 */

namespace Ofey\Logan22\component\lang;

use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\session\session;

class lang {
    public static function get_phrase($key, ...$values): string {
       return \Ofey\Logan22\controller\config\config::load()->lang()->getPhrase($key, ...$values);
    }
}