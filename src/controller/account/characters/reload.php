<?php

namespace Ofey\Logan22\controller\account\characters;

use Ofey\Logan22\model\user\user;

class reload
{

    public static function reload(): void
    {
        user::self()->getLoadAccounts(true);
    }

}