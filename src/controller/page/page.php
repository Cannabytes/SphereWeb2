<?php

namespace Ofey\Logan22\controller\page;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\config\config;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\userlog;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\page\page as page_model;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class page
{

    public static function get_news_ajax()
    {
        $id = $_POST['news_id'];
        $content = page_model::get_news($id);
        if (!$content) {
            board::notice(false, "Not news");
        }
        board::alert(array_merge($content, ['ok' => true]));
    }

    public static function lastNews()
    {
        if (!config::getEnableNews()) error::error404("Отключено");
        $shorts = \Ofey\Logan22\model\page\page::show_all_pages_short();
        tpl::addVar([
            "shorts" => $shorts,
        ]);
        tpl::display("page/page.html");
    }
}