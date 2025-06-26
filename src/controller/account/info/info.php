<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 26.08.2022 / 20:07:32
 */

namespace Ofey\Logan22\controller\account\info;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\player\character;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\template\tpl;

class info {

    public static function account(): void {
        tpl::display("/account/accounts.html");
    }

}