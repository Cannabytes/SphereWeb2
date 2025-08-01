<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 15.08.2022 / 21:03:16
 */

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\request\request;
use Ofey\Logan22\component\request\request_config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class page {

    static public function list() {
        validation::user_protection("admin");
        tpl::addVar([
            'show_news'   => \Ofey\Logan22\model\page\page::show_all_pages_short(),
            'server_list' => server::get_server_info(),
            "title" => lang::get_phrase("pages"),
        ]);
        tpl::display("admin/page/list.html");
    }

    public static function create_news() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\page::create();
    }

    public static function edit_news($id) {
        validation::user_protection("admin");
        $data = \Ofey\Logan22\model\page\page::get_news($id);
        tpl::addVar([
            'id'          => $data['id'],
            'title'       => $data['name'],
            'description' => $data['description'],
            'comment'     => $data['comment'],
            'is_news'     => $data['is_news'],
            'page_lang'   => $data['lang'],
        ]);
        tpl::display("admin/page/edit.html");
    }

    public static function update_news() {
        validation::user_protection("admin");
        \Ofey\Logan22\model\admin\page::update();
    }

    public static function trash_send() {
        validation::user_protection("admin");
        $id = $_POST['id'] ?? board::error("Нет данных страницы");
        \Ofey\Logan22\model\admin\page::trash_send($id);
    }

}