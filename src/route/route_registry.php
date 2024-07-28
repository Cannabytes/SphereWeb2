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
        if(route::getDisabledRoutes($dbRoute['pattern'])) {
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
} else {
    $route = new Router();
    $route->get("/(.*)", "Ofey\Logan22\controller\install\install::rules");
    $route->post("/install/db/connect/test", "Ofey\Logan22\controller\install\install::db_connect");
    $route->post("/install", "Ofey\Logan22\controller\install\install::startInstall");
}

$route->set404(function () {
    \Ofey\Logan22\component\redirect::location("/main");
});
$route->run();
