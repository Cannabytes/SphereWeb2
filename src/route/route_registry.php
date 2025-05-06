<?php

use Bramus\Router\Router;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\route\route;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

if (file_exists(fileSys::get_dir('/data/db.php'))) {
    session::init();
    \Ofey\Logan22\controller\config\dsys::initPaySysClass();
    config::load();
    plugin::loading();
    $route           = new Ofey\Logan22\route\Route();
    date_default_timezone_set(config::load()->other()->getTimezone());
    //Проверка, что сайт отключен
    if (config::load()->other()->getEnableTechnicalWork() AND ! user::self()->isAdmin()) {
        $route->get("/admin", function () {
            tpl::display('sign-in.html');
        });
        $route->get("/user/change/lang/{lang}", "Ofey\Logan22\controller\config\config::setLang");
        $route->post("/auth", "Ofey\Logan22\controller\user\auth\auth::auth_request");
        $route->get("/(.*)", function (){
            tpl::display('disabled.html');
        });
    } else {
        $userAccessLevel = user::self()->getAccessLevel();
        date_default_timezone_set(user::self()->getTimezone() ?? config::load()->other()->getTimezone());
        $routes = route::getRoutes($userAccessLevel);
        foreach ($routes as $dbRoute) {
            if (route::getDisabledRoutes($dbRoute['pattern'])) {
                continue;
            }
            $access  = $dbRoute['access'];
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
    }
} else {
    $route = new Router();
    $route->get("/(.*)", "Ofey\Logan22\controller\install\install::rules");
    $route->post("/install/db/connect/test", "Ofey\Logan22\controller\install\install::db_connect");
    $route->post("/install", "Ofey\Logan22\controller\install\install::startInstall");
}
$route->set404(function () {
    http_response_code(404);
    if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        \Ofey\Logan22\component\alert\board::error("Запрос отправлен на неизвестный адрес");
    }
    if(config::load()->other()->isShow404error()){
        \Ofey\Logan22\controller\page\error::error404();
    }
    \Ofey\Logan22\component\redirect::location("/main");
});
$route->run();
