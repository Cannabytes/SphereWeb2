<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\template\tpl;

class referral
{

    static function showOption($id = null): void
    {
        //проверка существования сервера ID
        if(\Ofey\Logan22\model\server\server::isServer($id)==null){
            redirect::location("/main");
        }
        tpl::addVar([
            'id' => $id,
        ]);
        tpl::display("/admin/referral.html");
    }

}