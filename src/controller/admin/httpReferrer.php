<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\template\tpl;

class httpReferrer
{
    public static function show()
    {
        tpl::display("/admin/httpreferrer.html");
    }
}