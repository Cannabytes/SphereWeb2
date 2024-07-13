<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\github\update;
use Ofey\Logan22\template\tpl;

class index {

    public static function index() {
        validation::user_protection("admin");

        $info = server::send(type::SERVER_FULL_INFO)->show()->getResponse();
        $lastCommit = server::send(type::GET_COMMIT_LAST)->show()->getResponse();
        tpl::addVar([
            "title" => lang::get_phrase("admin_panel"),
            "servers" => $info['servers'],
            "sphere_last_commit" => $lastCommit['last_commit'],
            "self_last_commit" => update::getLastCommit(),
        ]);
        tpl::display("admin/index.html");
    }

    public static function support(){
        validation::user_protection("admin");
        tpl::addVar([
            "title" => lang::get_phrase("support"),
        ]);
        tpl::display("admin/support.html");
    }

}