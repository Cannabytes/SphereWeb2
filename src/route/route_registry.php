<?php

use Bramus\Router\Router;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\route\route;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

\Ofey\Logan22\component\error\error::initDefault();
session::init();

$isFileDB = false;
if (file_exists(fileSys::get_dir('/data/db.php'))) {
    $isFileDB = true;
    config::load();
    date_default_timezone_set(config::load()->other()->getTimezone());
    $userAccessLevel = user::self()->getAccessLevel();
    $route           = new Ofey\Logan22\route\Route();
    $routes          = route::getRoutes($userAccessLevel);

    foreach ($routes as $dbRoute) {
        if ( ! $dbRoute['enable']) {
            continue;
        }
        $access = $dbRoute['access'];
        $method  = $dbRoute['method'];
        $pattern = $dbRoute['pattern'];
        $func    = $dbRoute['func'];

        if ( ! $func) {
            $func = function (...$data) use ($dbRoute) {
                foreach ($data as $key => $value) {
                    tpl::addVar("get_" . $key, $value);
                }
                tpl::display($dbRoute['page']);
            };
        } elseif ($func == 'debug') {
            $func = function (...$data) {
                var_dump($_REQUEST, $data);
                exit;
            };
        } else {
            $func = 'Ofey\\Logan22\\' . $func;
        }
        $route->$method($pattern, $func);
    }
} else {
    $route = new Router();
    $route->get("/(.*)", "Ofey\Logan22\controller\install\install::rules");
    $route->post("/install/db/connect/test", "Ofey\Logan22\controller\install\install::db_connect");
    $route->post("/install", "Ofey\Logan22\controller\install\install::startInstall");
}

/**
 * @param   \Ofey\Logan22\route\Route  $route
 *
 * @return void
 */
$route->get("test", function (){
    tpl::display("colors.html");
});

/*
function IndependentRequests(\Ofey\Logan22\route\Route $route): void
{

    $route->get("auth", 'Ofey\Logan22\controller\user\auth\auth::index')->alias("auth");
    $route->post("/auth", 'Ofey\Logan22\controller\user\auth\auth::auth_request');
    $route->get("auth/logout", 'Ofey\Logan22\controller\user\auth\auth::logout');
    $route->get("auth/forget", 'Ofey\Logan22\controller\user\auth\auth::forget')->alias("forget");
    $route->post("auth/forget/send/code", 'Ofey\Logan22\controller\user\auth\auth::send_email_forget');
    $route->post("auth/forget/verification/code", 'Ofey\Logan22\controller\user\auth\auth::send_email_verification_forget');
    $route->get("/auth/forget/code/{code}", 'Ofey\Logan22\controller\user\auth\auth::open_forget_page');
    $route->post("/auth/forget/send/password", 'Ofey\Logan22\controller\user\auth\auth::send_password');
    $route->get("/registration/account", 'Ofey\Logan22\controller\registration\account::newAccount')->alias("registration_account");
    $route->get("/registration/account/server/(\d+)", 'Ofey\Logan22\controller\registration\account::newAccount');
    $route->post("/registration/account", 'Ofey\Logan22\controller\registration\account::requestNewAccount');
    $route->get("registration/user", 'Ofey\Logan22\controller\registration\user::show')->alias("registration_user");
    $route->post("registration/user", 'Ofey\Logan22\controller\registration\user::add');
    $route->get("registration/user/ref/{username}", 'Ofey\Logan22\controller\registration\user::show');
    $route->post("generation/account", function () {
        echo \Ofey\Logan22\component\account\generation::word();
    });
    $route->post("generation/password", function () {
        echo \Ofey\Logan22\component\account\generation::password(mt_rand(4, 6), special: false);
    });
    $route->post("/captcha", 'Ofey\Logan22\component\captcha\captcha::defence');
}

if ( ! file_exists(\Ofey\Logan22\component\fileSys\fileSys::get_dir('/data/db.php'))) {
    $route->get("/", "Ofey\Logan22\controller\install\install::rules");
    $route->get("/install", "Ofey\Logan22\controller\install\install::rules");
    $route->get("/install/db", "Ofey\Logan22\controller\install\install::db");
    $route->post("/install/db/connect/test", "Ofey\Logan22\controller\install\install::db_connect");
    $route->get("/install/admin", "Ofey\Logan22\controller\install\install::admin");
    $route->post("/install/admin", "Ofey\Logan22\controller\install\install::add_admin");
    $route->set404(function () {
        \Ofey\Logan22\component\redirect::location("/install");
    });
} else {
//    config::load()->
    if (false) {
        //TODO: переделать
//    if (REQUIRE_AUTHORIZATION) {
        if ( ! auth::get_is_auth()) {
            IndependentRequests($route);
            $route->get("/(.*)", 'Ofey\Logan22\controller\user\auth\auth::index')->alias("auth");
            $route->run();

            return;
        }
    }
    //Если включен режим выполнения технических работ

    if (user::self()->getAccessLevel() != "admin") {
        if (config::load()->other()->getEnableTechnicalWork()) {
            $route->get("/auth", 'Ofey\Logan22\controller\user\auth\auth::index')->alias("auth");
            $route->all('/(.*)', function () {
                echo 'Пока отключено';exit();
//                tpl::display("/technicalwork.html");
            });
            $route->run();
            return;
        }
    }

    $route->get("page", '\Ofey\Logan22\controller\page\page::lastNews');
    $route->get("page/(\d+)", "Ofey\Logan22\controller\page\page::show");
    $route->post("page/comment/add", '\Ofey\Logan22\controller\page\page::addComment');
    $route->post("page/comment/delete", '\Ofey\Logan22\controller\admin\page::deleteComment');
    $route->post("page/comment/edit", '\Ofey\Logan22\controller\admin\page::editComment');

    $route->post("ajax/get/news", '\Ofey\Logan22\controller\page\page::get_news_ajax');
    //    $route->get("main", 'Ofey\Logan22\controller\main\main::index')->alias("home")->alias("main");
    IndependentRequests($route);

    $route->get("/forum/topic/add/(\d+)", 'Ofey\Logan22\controller\forum\forum::addTopic');
    $route->get("/forum/", 'Ofey\Logan22\controller\forum\forum::forum');
    $route->get("/forum/threads/(\d+)/(\d+)", 'Ofey\Logan22\controller\forum\forum::getTopic');
    $route->get("/forum/threads/(\d+)/(\d+)/(\d+)", 'Ofey\Logan22\controller\forum\forum::getPosts');
    $route->get("/forum/threads/(\d+)", 'Ofey\Logan22\controller\forum\forum::getTopics');
    $route->post("/forum/topic/post/add", 'Ofey\Logan22\controller\forum\forum::addPost');
    $route->post("/forum/topic/add", 'Ofey\Logan22\controller\forum\forum::addTopicRequest');
    $route->post("/forum/post/like", 'Ofey\Logan22\controller\forum\forum::likePost');
    $route->post("/forum/post/like/get", 'Ofey\Logan22\controller\forum\forum::getLikePost');
    $route->post("/forum/edit/comment", 'Ofey\Logan22\controller\forum\forum::editComment');
    $route->post("/forum/delete/comment", 'Ofey\Logan22\controller\forum\forum::deleteComment');
    $route->post("/forum/delete/comment/image", 'Ofey\Logan22\controller\forum\forum::deleteCommentImage');
    $route->post("/forum/get/user/name", 'Ofey\Logan22\controller\forum\forum::getUserName');

    $route->get("/accounts", 'Ofey\Logan22\controller\account\info\info::account');
    $route->get("account/password/change/{login}/server/(\d+)", 'Ofey\Logan22\controller\account\password\change::show');
    //TODO:Реквесты
    //TODO:Пересмотреть логику смены пароля игровому аккаунту
    $route->post("account/password/change", 'Ofey\Logan22\controller\account\password\change::password');
    $route->get("account/comparison/server/(\d+)", 'Ofey\Logan22\controller\account\comparison\comparison::call');
    $route->get("account/info/{account}", 'Ofey\Logan22\controller\account\info\info::player_list');
    $route->post("account/info/change/characters/info/forbidden", 'Ofey\Logan22\model\user\player\player_account::forbiddenViewPlayerData');
    $route->get("/account/sync", 'Ofey\Logan22\controller\registration\account::sync');
    $route->post("/account/sync", 'Ofey\Logan22\controller\registration\account::sync_add');


    $route->get("/referral", 'Ofey\Logan22\controller\referral\referral::show');
    $route->post("/referral", 'Ofey\Logan22\controller\referral\referral::my_bonus');


    $route->get("user/change", '\Ofey\Logan22\controller\user\profile\change::show');
    $route->post("user/change", '\Ofey\Logan22\controller\user\profile\change::save');
    $route->post("/user/change/default/server", '\Ofey\Logan22\controller\user\default_server::change');
    //    $route->get("user/change/avatar", 'Ofey\Logan22\controller\user\profile\change::show_avatar_page');
    $route->post("user/change/avatar", 'Ofey\Logan22\controller\user\profile\change::save_avatar');
    $route->get("/user/change/avatar/self", 'Ofey\Logan22\controller\user\profile\change::set_self_avatar');
    $route->post("/user/change/avatar/self", 'Ofey\Logan22\controller\user\profile\change::set_self_avatar_load');
    $route->post("user/change/theme", 'Ofey\Logan22\controller\user\profile\change::change_theme');

    $route->post('/user/money/transfer', 'Ofey\Logan22\controller\user\profile\change::transfer_money');
    $route->post("/user/notification/read", 'Ofey\Logan22\model\notification\notification::notification_mark_read');
    $route->get("/user/notification", 'Ofey\Logan22\controller\user\profile\change::show_notification');


    //    $route->get("/bonus", '\Ofey\Logan22\controller\account\bonus\bonus::code');
    //    $route->post("/bonus/receiving", '\Ofey\Logan22\controller\account\bonus\bonus::receiving');
    //    $route->post("/bonus/inventory/update", "\Ofey\Logan22\controller\account\bonus\bonus::update_inventory");
    //    $route->post("/bonus/inventory/addplayer", "\Ofey\Logan22\controller\account\bonus\bonus::addBonusPlayer");


    $route->get("/statistic/class/{class_name}", 'Ofey\Logan22\controller\statistic\statistic::class');
    $route->get("statistic/pvp", 'Ofey\Logan22\controller\statistic\statistic::pvp');
    $route->post("statistic/pvp/ajax", 'Ofey\Logan22\controller\statistic\statistic::pvp_ajax');
    $route->get("statistic/pk", 'Ofey\Logan22\controller\statistic\statistic::pk');
    $route->post("statistic/pk/ajax", 'Ofey\Logan22\controller\statistic\statistic::pk_ajax');
    $route->get("statistic/clan", 'Ofey\Logan22\controller\statistic\statistic::clan');
    $route->post("statistic/clan/ajax", 'Ofey\Logan22\controller\statistic\statistic::clan_ajax');
    $route->get("statistic/clan/{clan_name}", 'Ofey\Logan22\controller\statistic\statistic::clan_info');
    $route->get("statistic/online/time", 'Ofey\Logan22\controller\statistic\statistic::online_time');
    $route->post("statistic/player/ajax", 'Ofey\Logan22\controller\statistic\statistic::player_ajax');
    $route->get("statistic/block", 'Ofey\Logan22\controller\statistic\statistic::block');
    $route->get("statistic/heroes", 'Ofey\Logan22\controller\statistic\statistic::heroes');
    $route->get("statistic/castle", 'Ofey\Logan22\controller\statistic\statistic::castle');
    $route->post("statistic/castle/ajax", 'Ofey\Logan22\controller\statistic\statistic::castle_ajax');
    $route->post("statistic/block/ajax", 'Ofey\Logan22\controller\statistic\statistic::block_ajax');
    $route->post("statistic/heroes/ajax", 'Ofey\Logan22\controller\statistic\statistic::heroes_ajax');
    $route->post("statistic/other/ajax", 'Ofey\Logan22\controller\statistic\statistic::other_ajax');

    $route->get("/statistic/char/{char_name}", 'Ofey\Logan22\controller\statistic\statistic::char_info');


    //Получение курса валют
    $route->post("donate/currency_exchange_info", '\Ofey\Logan22\controller\donate\pay::currency_exchange_info');
    $route->get("donate/pay", '\Ofey\Logan22\controller\donate\pay::pay')->alias('donate_pay');
    $route->get("donate", '\Ofey\Logan22\controller\donate\pay::shop')->alias('donate');
    //    $route->get("shop", '\Ofey\Logan22\controller\donate\pay::shop')->alias('donate');
    //    $route->get("donate/(.*)", '\Ofey\Logan22\controller\donate\pay::get_donate_type');

    //    $route->post("donate/transaction", 'Ofey\Logan22\controller\donate\pay::transaction');


    $route->get("gallery", 'Ofey\Logan22\controller\gallery\screenshot::show_page');
    $route->get("gallery/screenshot", 'Ofey\Logan22\controller\gallery\screenshot::show_page');
    $route->get("gallery/screenshot/my", 'Ofey\Logan22\controller\gallery\screenshot::my_page');
    $route->post("gallery/screenshot/my/remove", 'Ofey\Logan22\controller\gallery\screenshot::my_remove');
    $route->get("gallery/screenshot/add", 'Ofey\Logan22\controller\gallery\screenshot::show_add_page');
    $route->post("gallery/screenshot/load", 'Ofey\Logan22\controller\gallery\screenshot::load_screen');

    $route->post("gallery/save", 'Ofey\Logan22\controller\gallery\screenshot::save_description');


    //    $route->get("ticket", 'Ofey\Logan22\controller\ticket\ticket::all');
    //    $route->post("ticket/message", 'Ofey\Logan22\controller\ticket\ticket::message');
    //    $route->get("ticket/(\d+)", 'Ofey\Logan22\controller\ticket\ticket::get');

    $route->get("ticket/open", 'Ofey\Logan22\controller\ticket\ticket::getOpenTickets');
    $route->get("ticket/close", 'Ofey\Logan22\controller\ticket\ticket::getCloseTickets');
    $route->get("ticket/create", 'Ofey\Logan22\controller\ticket\ticket::create');
    $route->get("ticket/edit/(\d+)", 'Ofey\Logan22\controller\ticket\ticket::edit');
    $route->get("ticket/edit/(\d+)/(\d+)", 'Ofey\Logan22\controller\ticket\ticket::edit');
    $route->get("ticket/search", 'Ofey\Logan22\controller\ticket\ticket::search');
    $route->get("ticket/search/{search}", 'Ofey\Logan22\controller\ticket\ticket::search');
    $route->post("/ticket/add", 'Ofey\Logan22\controller\ticket\ticket::add');
    $route->post("/ticket/add/comment", 'Ofey\Logan22\controller\ticket\ticket::addComment');
    $route->post("ticket/close", 'Ofey\Logan22\controller\ticket\ticket::close');
    $route->post("ticket/open", 'Ofey\Logan22\controller\ticket\ticket::open');
    $route->post("ticket/remove/comment/image", 'Ofey\Logan22\controller\ticket\ticket::removeImage');
    $route->post("ticket/edit/comment", 'Ofey\Logan22\controller\ticket\ticket::editComment');
    $route->post("ticket/edit/ticket", 'Ofey\Logan22\controller\ticket\ticket::editTicket');
    $route->post("ticket/remove", 'Ofey\Logan22\controller\ticket\ticket::remove');
    $route->post("ticket/remove/comment", 'Ofey\Logan22\controller\ticket\ticket::removeComment');


    $route->post("/user/variable/set", 'Ofey\Logan22\controller\user\auth\auth::set_variable');


    $route->get("/admin", 'Ofey\Logan22\controller\admin\index::index');
    $route->get("/admin/pages", 'Ofey\Logan22\controller\admin\page::list');
    $route->get("/admin/pages/create", 'Ofey\Logan22\controller\admin\page::create');
    $route->post("/admin/pages/create", 'Ofey\Logan22\controller\admin\page::create_news');
    $route->get("/admin/pages/edit/(\d+)", 'Ofey\Logan22\controller\admin\page::edit_news');
    $route->post("/admin/pages/edit", 'Ofey\Logan22\controller\admin\page::update_news');
    $route->get("/admin/pages/trash/(\d+)", 'Ofey\Logan22\controller\admin\page::trash_send');
    $route->get("/admin/setting", 'Ofey\Logan22\controller\admin\options::server_show');
    $route->POST("/admin/setting/donate/save", 'Ofey\Logan22\controller\admin\options::saveConfigDonate');
    $route->POST("/admin/setting/referral/save", 'Ofey\Logan22\controller\admin\options::saveConfigReferral');

    $route->post("/admin/option/server/save", 'Ofey\Logan22\controller\admin\options::new_server_save');
    $route->post("/admin/option/server/update", 'Ofey\Logan22\controller\admin\options::update_server_save');
    $route->post("/admin/option/server/remove", 'Ofey\Logan22\controller\admin\options::remove_server');
    $route->post("/admin/option/server/db/connect", 'Ofey\Logan22\controller\admin\options::test_connect_db');
    $route->post("/admin/option/server/db/connect/select/name", 'Ofey\Logan22\controller\admin\options::test_connect_db_selected_name');
    //!    $route->post("/admin/options/server/client/protocol", '\Ofey\Logan22\component\chronicle\client::get_base_collection_class');

    $route->get("/admin/options/server/cache", 'Ofey\Logan22\controller\admin\options::cache_page');
    $route->post("/admin/options/server/cache", 'Ofey\Logan22\controller\admin\options::cache_save');

    $route->get("/admin/options/server/list", 'Ofey\Logan22\controller\admin\options::server_list');
    $route->get("/admin/options/server/edit/(\d+)", 'Ofey\Logan22\controller\admin\options::edit_server_show');
    $route->get("/admin/options/server/additionally/(\d+)", 'Ofey\Logan22\controller\admin\options::additionally_server_show');
    $route->post("/admin/options/server/additionally", 'Ofey\Logan22\controller\admin\options::additionally_save');
    $route->get("/admin/options/server/description/(\d+)", 'Ofey\Logan22\controller\admin\options::description_create');
    $route->post("/admin/options/server/description", 'Ofey\Logan22\controller\admin\options::description_save');
    $route->post("/admin/options/server/description/default", 'Ofey\Logan22\controller\admin\options::description_default_page_save');

    $route->get("/admin/options/server/launcher/add/(\d+)", "Ofey\Logan22\controller\admin\launcher::add");
    $route->post("/admin/options/server/launcher/add", "Ofey\Logan22\controller\admin\launcher::addNewServer");
    $route->post("/admin/options/server/launcher/remove", "Ofey\Logan22\controller\admin\launcher::removeLauncher");

    $route->get("/admin/gallery/screen", 'Ofey\Logan22\controller\admin\screen::all');
    $route->post("/admin/gallery/screen/enable", 'Ofey\Logan22\controller\admin\screen::add_enable');
    $route->post("/admin/gallery/screen/remove", 'Ofey\Logan22\controller\admin\screen::remove');
    $route->get("/admin/gallery/screen/options", 'Ofey\Logan22\controller\admin\screen::show_options');
    $route->post("/admin/gallery/screen/options/save", 'Ofey\Logan22\controller\admin\screen::options_save');
    $route->get("/admin/template", '\Ofey\Logan22\controller\admin\template::show_design');
    $route->post("/admin/template/save", '\Ofey\Logan22\controller\admin\template::save_template');
    $route->post("/admin/template/readme", '\Ofey\Logan22\controller\admin\template::get_readme');
    $route->get("/admin/template/email/forget", '\Ofey\Logan22\controller\admin\template::email_forget');
    $route->get("/admin/template/email/password", '\Ofey\Logan22\controller\admin\template::new_password');

    //    $route->get("/admin/forum", 'Ofey\Logan22\controller\admin\forum::index');
    //    $route->post("/admin/forum", 'Ofey\Logan22\controller\admin\forum::save');
    //    $route->post("/admin/forum/add/category", 'Ofey\Logan22\controller\admin\forum::addCategory');
    //    $route->post("/admin/forum/edit/category", 'Ofey\Logan22\controller\admin\forum::editCategory');
    //    $route->post("/admin/forum/remove/category", 'Ofey\Logan22\controller\admin\forum::removeCategory');
    //    $route->post("/admin/forum/add/section", 'Ofey\Logan22\controller\admin\forum::addSection');
    //    $route->post("/admin/forum/edit/section", 'Ofey\Logan22\controller\admin\forum::editSection');
    //    $route->post("/admin/forum/remove/section", 'Ofey\Logan22\controller\admin\forum::removeSection');
    //    $route->post("/admin/forum/section/close", 'Ofey\Logan22\controller\admin\forum::closeSection');
    //    $route->post("/admin/forum/topic/close", 'Ofey\Logan22\controller\admin\forum::closeTopic');
    //    $route->post("/admin/forum/topic/pin", 'Ofey\Logan22\controller\admin\forum::pinTopic');
    //    $route->post("/admin/forum/topic/move", 'Ofey\Logan22\controller\admin\forum::topicMove');

    $route->get("/admin/chat", '\Ofey\Logan22\controller\admin\chat::show');
    $route->post("/admin/chat/find/message", '\Ofey\Logan22\controller\admin\chat::find_message');
    $route->post("/admin/chat/find/player", '\Ofey\Logan22\controller\admin\chat::find_player');
    $route->get("/admin/users", "\Ofey\Logan22\controller\admin\users::showAll");
    $route->post("/admin/users/edit", "\Ofey\Logan22\controller\admin\users::edit");
    $route->get("/admin/bonuscode", "\Ofey\Logan22\controller\admin\bonuscode::show");
    $route->post("/admin/bonuscode", "\Ofey\Logan22\controller\admin\bonuscode::genereate");
    $route->get("/admin/bonuscode/show", '\Ofey\Logan22\controller\admin\bonuscode::show_code');
    $route->post("/admin/bonuscode/remove", '\Ofey\Logan22\controller\admin\bonuscode::remove');
    $route->get("/admin/bonuscode/create/pack", "\Ofey\Logan22\controller\admin\bonuscode::create_pack");

    $route->get("/admin/logs/user", '\Ofey\Logan22\controller\admin\userlog::all');
    $route->get("/admin/logs/user/sort/(.*)", '\Ofey\Logan22\controller\admin\userlog::all');
    $route->post("/admin/logs/user/update/message", '\Ofey\Logan22\controller\admin\userlog::get_new_message');

    $route->post("/chat", 'Ofey\Logan22\controller\chat\chat::show');

    $route->post('/admin/client/info', function () {
        validation::user_protection("admin");
        Ofey\Logan22\component\image\client_icon::get_item_info(json: true);
    });
    $route->get("/admin/support", 'Ofey\Logan22\controller\admin\index::support');
    $route->get("/admin/plugin", 'Ofey\Logan22\controller\admin\plugin::show');
    $route->post("/admin/users/add/donate", 'Ofey\Logan22\controller\admin\donate::add_bonus_money');



}
*/
//
//
//    $route->post('/admin/client/item/info', function () {
//        Ofey\Logan22\component\image\client_icon::get_item_info(json: true);
//    });

//    $route->get("/", 'Ofey\Logan22\controller\promo\promo::index');
//    $route->get("login", 'Ofey\Logan22\controller\user\auth\auth::index')->alias("login");


//$route->get("/admin/route", '\Ofey\Logan22\controller\route\route::all');
//$route->get("balance", '\Ofey\Logan22\controller\donate\pay::pay')->alias('donate_pay');

$route->set404(function () {
    \Ofey\Logan22\component\redirect::location("/main");
//    \Ofey\Logan22\controller\page\error::error404();
});
$route->run();
