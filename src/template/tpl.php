<?php

namespace Ofey\Logan22\template;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Ofey\Logan22\component\account\generation;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\alert\logs;
use Ofey\Logan22\component\captcha\google;
use Ofey\Logan22\component\chronicle\client;
use Ofey\Logan22\component\chronicle\race_class;
use Ofey\Logan22\component\estate\castle;
use Ofey\Logan22\component\estate\clanhall;
use Ofey\Logan22\component\estate\fort;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\links\action;
use Ofey\Logan22\component\links\http;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\component\session\session;
use Ofey\Logan22\component\time\microtime;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\component\time\timezone;
use Ofey\Logan22\component\webserver\info\advancedWebServerInfo;
use Ofey\Logan22\controller\admin\startpack;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\stream\stream;
use Ofey\Logan22\controller\support\support;
use Ofey\Logan22\controller\ticket\ticket;
use Ofey\Logan22\model\admin\launcher;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\donate\donate;
use Ofey\Logan22\model\forum\forum;
use Ofey\Logan22\model\forum\internal;
use Ofey\Logan22\model\log\log;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\notification\notification;
use Ofey\Logan22\model\page\page;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\server\online;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\statistic\statistic as statistic_model;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\player\player_account;
use Ofey\Logan22\model\user\profile\other;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;
use Ofey\Logan22\route\Route;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class tpl
{

    private static array $allTplVars = [];

    private static string $templatePath = "/src/template/sphere/";

    private static ?bool $isAjax = null;

    private static bool $ajaxLoad = false;

    private static bool $categoryCabinet = false;

    private static bool|array $get_buffs_registry = false;

    private static ?bool $isPluginCustom = null;

    private static array $pluginNames = [];

    private static string|bool $isDebugVar = false;

    private static array $pluginsAllCustomAndComponents = [];

    public static function template_design_route(): ?array
    {
        $fileRoute = $_SERVER['DOCUMENT_ROOT'] . "/template/" . \Ofey\Logan22\controller\config\config::load()->template()->getName(
          ) . "/route.php";
        if (file_exists($fileRoute)) {
            require_once $fileRoute;
            if (isset($pages)) {
                return $pages;
            }
        }

        return null;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError|Throwable
     *
     * $contents массив, где первый параметр название переменной, а второй название блока
     */
    public static function getHTML(async $anyn)
    {
        $twig     = self::preload($anyn->get_fileTpl());
        $template = $twig->load($anyn->get_fileTpl());
        foreach ($anyn->blocks as &$a) {
            $a['html'] = $template->renderBlock($a['html'], self::$allTplVars);
        }
        board::alert($anyn->getArray());
    }


    private static function preload(): Environment
    {
        self::$ajaxLoad = false;
        if ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            self::$ajaxLoad = true;
        }
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER["SCRIPT_FILENAME"]));

        if (self::$categoryCabinet) {
            self::$templatePath = "/template/" . \Ofey\Logan22\controller\config\config::load()->template()->getName();
            self::lang_template_load(fileSys::get_dir(self::$templatePath . "/lang.php"));
        }

        $loader = new FilesystemLoader([
          fileSys::get_dir(self::$templatePath),
        ]);
        if (is_dir(fileSys::get_dir("/custom/plugins"))) {
            $loader->addPath(fileSys::get_dir("/custom/plugins"));
        }
        if (is_dir(fileSys::get_dir("/src/component/plugins"))) {
            $loader->addPath(fileSys::get_dir("/src/component/plugins"));
        }

        $arrTwigConfig = [];
        //TODO: ENABLE_CACHE_TEMPLATE
        if (false) {
            $arrTwigConfig['cache'] = fileSys::get_dir("/uploads/cache/template");
        }
        //TODO: AUTO_RELOAD
        $arrTwigConfig['auto_reload'] = true;
        //TODO: DEBUG_TEMPLATE
        $arrTwigConfig['debug'] = true;

        $twig = new Environment($loader, $arrTwigConfig);

        $twig->addExtension(new DebugExtension());
        $twig = self::generalfunc($twig);
        $twig = self::user_var_func($twig);

        //Ищем в плагинах все дополнительные функции, которые дополняют шаблоны
        $all_plugins_dir = fileSys::get_dir_files("/src/component/plugins", [
          'fetchAll' => true,
        ]);
        $twigCustomFile  = "custom_twig.php";
        foreach ($all_plugins_dir as $pluginDir) {
            $filePath = $pluginDir . '/' . $twigCustomFile;
            if (is_readable($filePath)) {
                require_once $filePath;
                $fileContent = file_get_contents($filePath);
                if (preg_match('/\bnamespace\s+([^\s;]+)/', $fileContent, $matches)) {
                    $namespace = $matches[1];
                    $className = pathinfo($filePath, PATHINFO_FILENAME);
                    $className = $namespace . "\\" . $className;
                    if (class_exists($className)) {
                        $customTwig = new $className();
                        $methods    = get_class_methods($customTwig);
                        foreach ($methods as $method) {
                            if (is_callable([$customTwig, $method]) && (new ReflectionMethod($customTwig, $method))->isPublic()) {
                                $twig->addFunction(new \Twig\TwigFunction($method, [$customTwig, $method]));
                            }
                        }
                    }
                }
            }
        }

        $all_plugins_dir = fileSys::get_dir_files("/custom/plugins", [
          'fetchAll' => true,
        ]);
        $twigCustomFile  = "custom_twig.php";
        foreach ($all_plugins_dir as $pluginDir) {
            $filePath = $pluginDir . '/' . $twigCustomFile;
            if (is_readable($filePath)) {
                require_once $filePath;
                $fileContent = file_get_contents($filePath);
                if (preg_match('/\bnamespace\s+([^\s;]+)/', $fileContent, $matches)) {
                    $namespace = $matches[1];
                    $className = pathinfo($filePath, PATHINFO_FILENAME);
                    $className = $namespace . "\\" . $className;
                    if (class_exists($className)) {
                        $customTwig = new $className();
                        $methods    = get_class_methods($customTwig);
                        foreach ($methods as $method) {
                            if (is_callable([$customTwig, $method]) && (new ReflectionMethod($customTwig, $method))->isPublic()) {
                                $twig->addFunction(new \Twig\TwigFunction($method, [$customTwig, $method]));
                            }
                        }
                    }
                }
            }
        }

        self::$allTplVars['dir']       = fileSys::localdir();
//        $self                          = url::host() . $relativePath . self::$templatePath;
        $self                          = self::$templatePath;

        self::$allTplVars['protocol']  = url::scheme();
        self::$allTplVars['path']      = $relativePath;
        self::$allTplVars['template']  = $self;
        self::$allTplVars['pointTime'] = microtime::pointTime();

        return $twig;
    }

    /**
     * Загрузка языкового пакета шаблона
     */
    public static function lang_template_load($tpl)
    {
        if ( ! is_dir(dirname($tpl))) {
            return;
        }
        if ( ! file_exists($tpl)) {
            return;
        }
        \Ofey\Logan22\controller\config\config::load()->lang()->load_template_lang_packet($tpl);
    }

    private static function generalfunc(?Environment $twig): Environment
    {
        if ($twig === null) {
            throw new \InvalidArgumentException('Twig environment cannot be null');
        }

        $twig->addFilter(new TwigFilter('html_entity_decode', 'html_entity_decode'));
        $twig->addFilter(new TwigFilter('file_exists', function ($filePath) {
            return file_exists($filePath);
        }));
        $twig->addFilter(new TwigFilter('donate_remove_show_bug', function ($text) {
            return str_replace(["<Soul Crystal Enhancement>"], ["-=Soul Crystal Enhancement=-"], $text);
        }));

        $twig->addFilter(new TwigFilter('json_decode', function ($json) {
            return json_decode($json, true);
        }));

        $twig->addFunction(new TwigFunction('template', function ($var = null) {
            return str_replace([
                "//",
                "\\",
            ], "/", (self::$templatePath . $var));
        }));

        $twig->addFunction(new TwigFunction('getUser', function ($id = null): ?userModel {
            if ($id == null) {
                $id = $_SESSION['id'] ?? null;
                if ($id == null) {
                    return new userModel($id);
                }
            }

            return user::getUserId($id);
        }));

        $twig->addFunction(new TwigFunction('getUsers', function () {
            return user::getUsers();
        }));

        $twig->addFunction(new TwigFunction('get_session', function ($key = null) {
            return session::get($key);
        }));

        $twig->addFunction(new TwigFunction('delete_session', function ($key = null) {
            session::remove($key);
        }));

        $twig->registerUndefinedFunctionCallback(function ($name) {
            if (function_exists($name)) {
                return new TwigFunction($name, function () use ($name) {
                    return call_user_func_array($name, func_get_args());
                });
            }
            throw new RuntimeException(sprintf('Function %s not found', $name));
        });

        $twig->addFunction(new TwigFunction('class_group_color', function ($access_level = "user") {
            switch ($access_level) {
                case "admin":
                    return "danger";
                case "moderator":
                    return "success";
                default:
                    return "info";
            }
        }));

        $twig->addFunction(new TwigFunction('get_plugins_include', function ($includeName) {
            if (empty(self::$pluginsAllCustomAndComponents)) {
                $pluginsAllCustom                    = self::processPluginsDir("custom/plugins/");
                $pluginsAllComponents                = self::processPluginsDir("src/component/plugins/");
                self::$pluginsAllCustomAndComponents = array_merge($pluginsAllCustom, $pluginsAllComponents);
            }
            $templates = [];
            foreach (self::$pluginsAllCustomAndComponents as $key => $plugin) {
                if (isset($plugin['INCLUDES'])) {
                    if (isset($plugin['PLUGIN_ENABLE'])) {
                        if ( ! $plugin['PLUGIN_ENABLE']) {
                            continue;
                        }
                    }
                    foreach ($plugin['INCLUDES'] as $name => $file) {
                        if ($name == $includeName) {
                            $templates[] = $file;
                        }
                    }
                }
            }

            return $templates;
        }));

        $twig->addFunction(new TwigFunction('path', function ($link = "/") {
            $link = sprintf("%s/%s", fileSys::getSubDir(), $link);

            return str_replace(['//', '\\'], '/', $link);
        }));

        $twig->addFunction(new TwigFunction('cache_timeout', function ($var = null) {
            return time::cache_timeout($var);
        }));

        $twig->addFunction(new TwigFunction('isAjaxRequest', function () {
            if (self::$isAjax === null) {
                self::$isAjax = ( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
                                                                                  $_SERVER['HTTP_X_REQUESTED_WITH']
                                                                                ) == 'xmlhttprequest');
            }

            return self::$isAjax;
        }));

        $twig->addFunction(new TwigFunction('get_captcha_version', function ($name = null) {
            if ($name == null) {
                return \Ofey\Logan22\controller\config\config::load()->captcha()->getCaptcha();
            }

            return strcasecmp(\Ofey\Logan22\controller\config\config::load()->captcha()->getCaptcha(), $name) == 0;
        }));

        $twig->addFunction(new TwigFunction('google_secret_key', function () {
            return google::get_client_key();
        }));

        $twig->addFunction(new TwigFunction('get_shop_items', function () {
            return donate::get_shop_items();
        }));

        $twig->addFunction(new TwigFunction('getShopItems', function () {
            return donate::getShopItems();
        }));

        $twig->addFunction(new TwigFunction('sumGetValue', function ($array, $methodName) {
            return donate::sumGetValue($array, $methodName);
        }));

        //TODO: Проверить, так как появились уже функции statistic_get_pvp
        $twig->addFunction(new TwigFunction('get_pvp', function ($count = 10, $server_id = 0) {
            return array_slice(statistic_model::get_pvp($server_id), 0, $count);
        }));

        $twig->addFunction(new TwigFunction('get_pk', function ($count = 10, $server_id = 0) {
            return array_slice(statistic_model::get_pk($server_id), 0, $count);
        }));

        $twig->addFunction(new TwigFunction('alias', function ($alias) {
            return Route::get_alias($alias);
        }));

        //Список языков
        $twig->addFunction(new TwigFunction('getLangList', function () {
            return \Ofey\Logan22\controller\config\config::load()->lang()->getLangList();
        }));

        $twig->addFunction(new TwigFunction('getAllowLang', function ($isAll = true) {
            return \Ofey\Logan22\controller\config\config::load()->lang()->getAllowLang($isAll);
        }));

        $twig->addFunction(new TwigFunction('isAllowLang', function ($lang) {
            return \Ofey\Logan22\controller\config\config::load()->lang()->isAllowLang($lang);
        }));

        $twig->addFunction(new TwigFunction('isAllowAllLang', function ($lang) {
            return \Ofey\Logan22\controller\config\config::load()->lang()->isAllowAllLang($lang);
        }));

        $twig->addFunction(new TwigFunction('getCountLang', function () {
            return \Ofey\Logan22\controller\config\config::load()->lang()->getCount();
        }));

        //Вывести язык который сейчас включен
        $twig->addFunction(new TwigFunction('lang_active', function ($isActive = true) {
            $langs = \Ofey\Logan22\controller\config\config::load()->lang()->getLangList();
            if ($isActive) {
                foreach ($langs as $lang) {
                    if ($lang->getIsActive()) {
                        return $lang;
                    }
                }

                return false;
            } else {
                foreach ($langs as $key => $lang) {
                    if ($lang->getIsActive()) {
                        unset($langs[$key]);
                    }
                }
            }

            return $langs;
        }));

        $twig->addFunction(new TwigFunction('title_start_page', function () {
            return \Ofey\Logan22\controller\config\config::load()->other()->getAllTitlePage();
        }));

        //TODO: description_start_page
        $twig->addFunction(new TwigFunction('description_start_page', function () {
            return 'TODO: description_start_page';
        }));

        $twig->addFunction(new TwigFunction('keywords_start_page', function () {
            return \Ofey\Logan22\controller\config\config::load()->other()->getKeywords();
        }));

        //Обрезаем слово до N значения, если оно больше, то добавляем в конец троеточие
        $twig->addFunction(new TwigFunction('truncateWord', function ($word, $length = 16) {
            if (mb_strlen($word, 'utf-8') <= $length) {
                return $word;
            } else {
                return mb_substr($word, 0, $length, 'utf-8') . '...';
            }
        }));

        $twig->addFunction(new TwigFunction('get_db_count_request', function () {
            return sql::getRequestCount();
        }));

        $twig->addFunction(new TwigFunction('get_sphere_api_count_request', function () {
            return \Ofey\Logan22\component\sphere\server::getCountRequest();
        }));

        $twig->addFunction(new TwigFunction('user_info', function ($type) {
            if (method_exists(auth::class, $type)) {
                return auth::$type();
            } else {
                throw new InvalidArgumentException(sprintf('Method "%s" does not exist in auth class.', $type));
            }
        }));

        $twig->addFunction(new TwigFunction('get_user_info', function ($user_id) {
            return auth::get_user_info($user_id);
        }));

        //Показать слово
        $twig->addFunction(new TwigFunction('get_phrase', function ($key, ...$values) {
            return lang::get_phrase($key, ...$values);
        }));

        //Аналог get_phrase
        $twig->addFunction(new TwigFunction('phrase', function ($phraseKey, ...$values) {
            return \Ofey\Logan22\controller\config\config::load()->lang()->getPhrase($phraseKey, ...$values);
        }));

        /**
         * Дебаг функция шаблона, которая отобразит содержимое объекта
         */
        $twig->addFunction(new TwigFunction("ss", function ($data) {
            // Определение базового типа данных
            $typeDescription = gettype($data);
            $output          = "<div class='modal fade' id='myModal' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>";
            $output          .= "<div class='modal-dialog modal-xl' role='document'>";
            $output          .= "<div class='modal-content'>";
            $output          .= "<div class='modal-header'>";
            $output          .= "<h5 class='modal-title' id='myModalLabel'>Тип данных: $typeDescription</h5>";
            $output          .= "<button type='button' class='close' data-dismiss='modal' aria-label='Close'>";
            $output          .= "<span aria-hidden='true'>&times;</span>";
            $output          .= "</button>";
            $output          .= "</div>"; // Закрытие modal-header
            $output          .= "<div class='modal-body'>";

            if (is_object($data)) {
                // Это объект
                $typeDescription = 'Объект класса: ' . get_class($data);
                $reflection      = new ReflectionClass($data);
                $methods         = $reflection->getMethods();
                $output          .= "<table class='table'>";
                $output          .= "<thead><tr><th>Метод</th><th>Видимость</th><th>Статичный</th><th>Возвращаемый тип</th><th>Комментарий</th></tr></thead>";
                $output          .= "<tbody>";
                foreach ($methods as $method) {
                    $returnType     = $method->getReturnType();
                    $returnTypeText = $returnType ?: 'void';
                    $docComment     = $method->getDocComment();
                    $docComment     = htmlspecialchars($docComment); // Экранирование специальных символов
                    $output         .= "<tr>";
                    $output         .= "<td>" . $method->name . "</td>";
                    $output         .= "<td>" . ($method->isPublic() ? "<span class='text-success'>public</span>" : ($method->isProtected(
                      ) ? "protected" : "<span class='text-danger'>private</span>")) . "</td>";
                    $output         .= "<td>" . ($method->isStatic() ? "да" : "нет") . "</td>";
                    $output         .= "<td>" . $returnTypeText . "</td>";
                    $output         .= "<td>" . ($docComment ?: "Нет комментария") . "</td>";
                    $output         .= "</tr>";
                }
                $output .= "</tbody></table>";
            } else {
                // Не объект (примитивные типы данных, такие как string, int и т.д.)
                $output .= "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
            }

            $output .= "</div>"; // Закрытие modal-body
            $output .= "<div class='modal-footer'>";
            $output .= "<button type='button' class='btn btn-secondary' data-dismiss='modal'>Закрыть</button>";
            $output .= "</div>"; // Закрытие modal-footer
            $output .= "</div>"; // Закрытие modal-content
            $output .= "</div>"; // Закрытие modal-dialog
            $output .= "</div>"; // Закрытие modal

            // JavaScript для автоматического открытия модального окна
            $output .= "<script type='text/javascript'>";
            $output .= "$(document).ready(function() {";
            $output .= "$('#myModal').modal('show');";
            $output .= "});";
            $output .= "</script>";

            return new Markup($output, 'UTF-8');
        }));

        $twig->addFunction(new TwigFunction("config", function () {
            return \Ofey\Logan22\controller\config\config::load();
        }));

        $twig->addFunction(new TwigFunction('get_template', function () {
            return \Ofey\Logan22\controller\config\config::load()->template()->getName();
        }));

        $twig->addFunction(new TwigFunction('format_number_fr', function ($num, $separator = ".") {
            echo number_format($num, 0, ',', $separator);
        }));

        $twig->addFunction(new TwigFunction('ProhloVremya', function ($mysqlTimeFormat, $reduce = false) {
            return statistic_model::timeHasPassed(time() - strtotime($mysqlTimeFormat), $reduce);
        }));

        //Время (в секундах) в часы. минуты, сек.
        $twig->addFunction(new TwigFunction('timeHasPassed', function ($num, $reduce = false) {
            return statistic_model::timeHasPassed($num, $reduce);
        }));

        $twig->addFunction(new TwigFunction('formatSeconds', function ($secs = 0) {
            if ( ! is_numeric($secs)) {
                return 'Некорректное значение';
            }

            $lang         = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default() == "ru" ? 0 : 1;
            $times_values = [
              ['сек.', 'sec.'],
              ['мин.', 'min.'],
              ['час.', 'h.'],
              ['д.', 'd.'],
              ['мес.', 'm.'],
              ['лет', 'y.'],
            ];
            $divisors     = [1, 60, 3600, 86400, 2592000, 31104000];
            for ($pow = count($divisors) - 1; $pow >= 0; $pow--) {
                if ($secs >= $divisors[$pow]) {
                    $time = $secs / $divisors[$pow];

                    return round($time) . ' ' . $times_values[$pow][$lang];
                }
            }

            return round($secs) . ' ' . $times_values[0][$lang];
        }));

        $twig->addFunction(new TwigFunction('get_class', function ($class_id) {
            return race_class::get_class($class_id);
        }));
        $twig->addFunction(new TwigFunction('get_class_race', function ($class_id) {
            return race_class::get_class_race($class_id);
        }));
        $twig->addFunction(new TwigFunction('key', function ($class_id) {
            echo key($class_id);
        }));
        $twig->addFunction(new TwigFunction('file_exists', function ($file) {
            return file_exists($file);
        }));

        //Обрезаем число до 10 символов (на некоторых сборках в микротайме хранится время) и выводим в формате времени
        $twig->addFunction(new TwigFunction('unitToDate', function ($var) {
            return date("H:i d.m.Y", (int)substr($var, 0, 10));
        }));

        $twig->addFunction(new TwigFunction('get_chronicles_by_protocol', function ($protocol) {
            return client::get_chronicles_by_protocol($protocol);
        }));

        $twig->addFunction(new TwigFunction('sex', function ($v) {
            return $v == 0 ? 'male' : 'female';
        }));
        $twig->addFunction(new TwigFunction('MobileDetect', function () {
            if ( ! isset($_SERVER["HTTP_USER_AGENT"])) {
                return false;
            }

            return preg_match(
              "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i"
              ,
              $_SERVER["HTTP_USER_AGENT"]
            );
        }));

        $twig->addFunction(new TwigFunction('get_youtube_id', function ($link) {
            $video_id = explode("?v=", $link);
            if ( ! isset($video_id[1])) {
                $video_id = explode("youtu.be/", $link);
            }
            if (empty($video_id[1])) {
                $video_id = explode("/v/", $link);
            }
            $video_id       = explode("&", $video_id[1]);
            $youtubeVideoID = $video_id[0];
            if ($youtubeVideoID) {
                return $youtubeVideoID;
            }

            return null;
        }));

        $twig->addFunction(new TwigFunction('getServer', function ($id = null) {
            if($id==null){
                $id = user::self()->getServerId();
            }
            return server::getServer($id);
        }));

        $twig->addFunction(new TwigFunction('getServers', function () {
            return server::getServerAll();
        }));

        $twig->addFunction(new TwigFunction('getServerAll', function () {
            return server::getServerAll();
        }));

        $twig->addFunction(new TwigFunction('getServerCount', function () {
            return server::get_count_servers();
        }));

        //Кол-во серверов
        $twig->addFunction(new TwigFunction("get_count_servers", function () {
            return server::get_count_servers();
        }));

        $twig->addFunction(new TwigFunction('get_launcher_info', function ($server_id = null) {
            return launcher::get_launcher_info($server_id);
        }));

        $twig->addFunction(new TwigFunction('getAdvancedServerInfo', function () {
            $serverInfo = new advancedWebServerInfo();
            return $serverInfo->getInfo();
        }));

        $twig->addFunction(new TwigFunction('lang_user_default', function () {
            return \Ofey\Logan22\controller\config\config::load()->lang()->getDefault();
        }));

        $twig->addFunction(new TwigFunction('strip_html_tags', function ($text) {
            return strip_tags($text);
        }));

        //Удаление сообщения тегов форума из текста
        $twig->addFunction(new TwigFunction('forum_message_clear_tag', function ($message) {
            $pattern = '/\[(.*?)\]/s';

            return preg_replace($pattern, '', $message);
        }));

        $twig->addFunction(new TwigFunction('last_forum_message', function ($last_message = 10) {
            return forum::get_last_message($last_message);
        }));

        $twig->addFunction(new TwigFunction('last_forum_thread', function ($last_thread = 10) {
            return forum::get_last_thread($last_thread);
        }));

        $twig->addFunction(new TwigFunction('get_forum_link', function ($thread) {
            return forum::get_link($thread);
        }));

        $twig->addFunction(new TwigFunction('forum_enable', function () {
            return forum::forum_enable();
        }));

        $twig->addFunction(new TwigFunction('get_avatar', function ($img = "none.jpeg", $thumb = false) {
            if ($thumb) {
                if (mb_substr($img, 0, 5) == "user_") {
                    $img = "thumb_" . $img;
                }
            }

            return (sprintf("/uploads/avatar/%s", $img));
        }));

        $twig->addFunction(new TwigFunction('get_support_thread_name', function ($thread_id){
            return support::getSection($thread_id);
        }));

        $twig->addFunction(new TwigFunction('balance_to_dollars', function ($dc = 0) {
            return $dc * (config::load()->donate()->getRatioUSD() / config::load()->donate()->getSphereCoinCost());

        }));

        $twig->addFunction(new TwigFunction('get_skill', function ($img = "none.jpeg") {
            return (sprintf("/uploads/images/skills/%s", $img));
        }));

        $twig->addFunction(new TwigFunction('get_icon', function ($img = "none.jpeg") {
            return (sprintf("/uploads/images/icon/%s", $img));
        }));

        $twig->addFunction(new TwigFunction('get_item_info', function ($item_id) {
            return client_icon::get_item_info($item_id, false, false);
        }));

        //        $twig->addFunction(new TwigFunction('donateConfig', function (): donateConfig {
        //            return donateConfig::get();
        //        }));

        //        $twig->addFunction(new TwigFunction('referralConfig', function (): referralConfig {
        //            return referralConfig::get();
        //        }));

        $twig->addFunction(new TwigFunction('get_forum_img', function ($img = "none.jpeg", $thumb = false) {
            if ($thumb) {
                if (mb_substr($img, 0, 5) == "user_") {
                    $img = "thumb_" . $img;
                }
            }

            return (sprintf("/uploads/images/forum/%s", $img));
        }));

        $twig->addFunction(new TwigFunction('get_ticket_img', function ($img = "none.jpeg", $thumb = false) {
            if ($thumb) {
                $img = "thumb_" . $img;
            }

            return (sprintf("/uploads/tickets/%s", $img));
        }));

        $twig->addFunction(new TwigFunction('forum', function () {
            return forum::get();
        }));

        $twig->addFunction(new TwigFunction('forum_user_avatar', function ($user_id = 0) {
            return forum::user_avatar($user_id);
        }));

        $twig->addFunction(new TwigFunction("forum_internal", function () {
            return internal::forum();
        }));

        $twig->addFunction(new TwigFunction('grade_img', function ($crystal_type): string {
            $grade_img = '';
            $dirGrade  = ("/uploads/images/grade");
            switch ($crystal_type) {
                case 'd':
                    $grade_img = "<img src='{$dirGrade}/d.png' style='width:20px'>";
                    break;
                case 'c':
                    $grade_img = "<img src='{$dirGrade}/c.png' style='width:20px'>";
                    break;
                case 'b':
                    $grade_img = "<img src='{$dirGrade}/b.png' style='width:20px'>";
                    break;
                case 'a':
                    $grade_img = "<img src='{$dirGrade}/a.png' style='width:20px'>";
                    break;
                case 's':
                    $grade_img = "<img src='{$dirGrade}/s.png' style='width:20px'>";
                    break;
                case 'r':
                    $grade_img = "<img src='{$dirGrade}/r.png' style='width:20px'>";
                    break;
                case 'r95':
                    $grade_img = "<img src='{$dirGrade}/r95.png' style='width:35px'>";
                    break;
                case 'r99':
                    $grade_img = "<img src='{$dirGrade}/r99.png' style='width:35px'>";
                    break;
                case 'r110':
                    $grade_img = "<img src='{$dirGrade}/r110.png' style='width:40px'>";
                    break;
            }

            return $grade_img;
        }));

        $twig->addFunction(new TwigFunction('generation_words_password', function ($count = 10): array {
            $words = [];
            for ($i = 0; $i < $count; $i++) {
                $word    = generation::word();
                $mt_rand = mt_rand(0, mt_rand(100, 999));
                $words[] = $word . $mt_rand;
            }

            return $words;
        }));

        //Сгенерировать рандомный аккаунт
        $twig->addFunction(new TwigFunction('generation_account', function () {
            return generation::word();
        }));

        //Список аккаунтов пользователя
        $twig->addFunction(new TwigFunction('show_all_account_player', function () {
            return player_account::show_all_account_player();
        }));

        $twig->addFunction(new TwigFunction('streams', function () {
            return stream::getStreams();
        }));

        $twig->addFunction(new TwigFunction('stream_get_platform' , function ($link) {
            return stream::stream_get_platform($link);
        }));

        //Deprecated 04.10.2024
        $twig->addFunction(new TwigFunction('stream_link_rev', function ($link){
           return stream::getSrc($link);
        }));

        $twig->addFunction(new TwigFunction('statistic_get_pvp', function ($server_id = 0, $limit = 0): ?array {
            if ($server_id < 0 || $limit < 0) {
                throw new InvalidArgumentException('Server ID and limit must be non-negative integers');
            }
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }
            $pvpStats = statistic_model::get_pvp($server_id);

            return $pvpStats ? ($limit > 0 ? array_slice($pvpStats, 0, $limit) : $pvpStats) : null;
        }));

        $twig->addFunction(new TwigFunction('statistic_get_pk', function ($server_id = 0, $limit = 0): ?array {
            if ($server_id < 0 || $limit < 0) {
                throw new InvalidArgumentException('Server ID and limit must be non-negative integers');
            }
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }
            $pkStats = statistic_model::get_pk($server_id);

            return $pkStats ? ($limit <= 0 ? $pkStats : array_slice($pkStats, 0, $limit)) : null;
        }));

        $twig->addFunction(new TwigFunction('statistic_players_online_time', function ($server_id = 0, $limit = 0) {
            if ($server_id < 0 || $limit < 0) {
                throw new InvalidArgumentException('Server ID and limit must be non-negative integers');
            }
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }
            $onlinePlayers = statistic_model::get_players_online_time($server_id);

            return $onlinePlayers ? ($limit <= 0 ? $onlinePlayers : array_slice($onlinePlayers, 0, $limit)) : null;
        }));

        $twig->addFunction(new TwigFunction('statistic_get_exp', function ($server_id = 0, $limit = 0): ?array {
            if ($server_id < 0 || $limit < 0) {
                throw new InvalidArgumentException('Server ID and limit must be non-negative integers');
            }
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }
            $expStats = statistic_model::get_exp($server_id);

            return $expStats ? ($limit <= 0 ? $expStats : array_slice($expStats, 0, $limit)) : null;
        }));

        $twig->addFunction(new TwigFunction('statistic_get_clans', function ($server_id = 0, $limit = 0) {
            if ($server_id < 0 || $limit < 0) {
                throw new InvalidArgumentException('Server ID and limit must be non-negative integers');
            }
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }
            $clanStats = statistic_model::get_clan($server_id);

            return $clanStats ? ($limit >= 1 ? array_slice($clanStats, 0, $limit) : $clanStats) : null;
        }));

        $twig->addFunction(new TwigFunction('statistic_get_castle', function ($server_id = 0) {
            if ($server_id == 0) {
                $server_id = user::self()->getServerId();
            }

            return statistic_model::get_castle($server_id);
        }));

        $twig->addFunction(new TwigFunction('clan_icon', function (string|array $data = null) {
            if ($data == null) {
                return null;
            }
            if (is_string($data)) {
                return "<img src='data:image/jpeg;base64,{$data}'>";
            }

            $crest = "";
            if (isset($data['alliance_crest'])) {
                $crest_base64 = $data['alliance_crest'];
                if ($data['alliance_crest'] != null) {
                    $crest = "<img src='data:image/jpeg;base64,{$crest_base64}'>";
                }
            }
            if (isset($data['clan_crest'])) {
                $crest_base64 = $data['clan_crest'];
                if ($data['clan_crest'] != null) {
                    $crest .= "<img src='data:image/jpeg;base64,{$crest_base64}'>";
                }
            }
            if ($crest != "") {
                return $crest;
            }
        }));

        $twig->addFunction(new TwigFunction('SphereApiError', function () {
            return \Ofey\Logan22\component\sphere\server::isError();
        }));

        $twig->addFunction(new TwigFunction('SphereApiCodeError', function () {
            return \Ofey\Logan22\component\sphere\server::getCodeError();
        }));

        $twig->addFunction(new TwigFunction('statusSphereServer', function () {
            \Ofey\Logan22\component\sphere\server::isOffline();
        }));

        //Список последних новостей
        $twig->addFunction(new TwigFunction('last_news', function ($last_thread = 10, $max_length = 300) {
            return page::show_news_short($max_length, $last_thread, false);
        }));

        $twig->addFunction(new TwigFunction('getPageLink', function ($news) {
            if ($news['link'] == '') {
                return "/read/{$news['id']}";
            } else {
                return $news['link'];
            }
        }));

        $twig->addFunction(new TwigFunction('show_all_pages_short', function () {
            return page::show_all_pages_short();
        }));

        $twig->addFunction(new TwigFunction('get_page', function ($id) {
            return page::getPage($id);
        }));

        $twig->addFunction(new TwigFunction('include', function ($template) use ($twig) {
            // Получаем информацию о пути файла
            $pathInfo = pathinfo($template);
            $customTemplate = $pathInfo['dirname'] . '/' .
                $pathInfo['filename'] .
                '_custom.' .
                $pathInfo['extension'];

            // Проверяем существование кастомного шаблона
            $customTemplatePath = fileSys::get_dir(self::$templatePath . '/' . $customTemplate);
            $originalTemplatePath = fileSys::get_dir(self::$templatePath . '/' . $template);

            try {
                if(file_exists($customTemplatePath)) {
                    $template = $twig->load($customTemplate);
                } else {
                    $template = $twig->load($template);
                }
                // Передаем все переменные из основного шаблона
                return $template->render(self::$allTplVars);
            } catch (Exception $e) {
                return "Error loading template: " . $e->getMessage();
            }
        }, ['is_safe' => ['html']]));


        $twig->addFunction(new TwigFunction('news_poster', function ($image, $full = false) {
            $uploadsPath = "uploads/images/news/";
            if ( ! $full) {
                $image = "thumb_" . $image;
            }
            $imagePath     = $uploadsPath . $image;
            $fullImagePath = ($imagePath);
            if ( ! file_exists(fileSys::getSubDir() . $fullImagePath)) {
                return ("/src/template/sphere/assets/images/logo_news_d.jpg");
            }

            return "/" . $fullImagePath;
        }));

        $twig->addFunction(new TwigFunction('all_phrase', function () {
            $languages = fileSys::get_dir_files("/data/languages", [
              'basename' => true,
              'sort'     => false,
              'fetchAll' => true,
            ]);

            $languages = array_map(function ($item) {
                return preg_replace('/\.php$/', '', $item);
            },
              array_filter($languages, function ($item) {
                  return str_ends_with($item, '.php');
              }));

            $combinedArray = [];
            foreach ($languages as $language) {
                $language_phrases = include fileSys::get_dir("/data/languages/" . $language . ".php");
                foreach ($language_phrases as $key => $phrase) {
                    $combinedArray[$key][$language] = $phrase;
                }
            }

            // Добавляем пустые строки для отсутствующих языковых значений
            foreach ($combinedArray as $key => $phrases) {
                foreach ($languages as $language) {
                    if ( ! array_key_exists($language, $phrases)) {
                        $combinedArray[$key][$language] = ""; // Добавляем пустую строку
                    }
                }
            }
            // Сортировка фраз в каждом языке
            foreach ($combinedArray as $key => &$phrases) {
                ksort($phrases);
            }

            return ['lang_list' => $languages, 'phrases' => $combinedArray];
        }));

        $twig->addFunction(new TwigFunction('all_phrase_custom', function () {
            $languages     = fileSys::get_dir_files("/data/languages/custom", [
              'basename' => true,
              'suffix'   => '.php',
              'sort'     => false,
              'fetchAll' => true,
            ]);
            $combinedArray = [];
            foreach ($languages as $language) {
                $language_phrases = include fileSys::get_dir("/data/languages/custom/" . $language . ".php");
                foreach ($language_phrases as $key => $phrase) {
                    $combinedArray[$key][$language] = $phrase;
                }
            }

            // Добавляем пустые строки для отсутствующих языковых значений
            foreach ($combinedArray as $key => $phrases) {
                foreach ($languages as $language) {
                    if ( ! array_key_exists($language, $phrases)) {
                        $combinedArray[$key][$language] = ""; // Добавляем пустую строку
                    }
                }
            }
            // Сортировка фраз в каждом языке
            foreach ($combinedArray as $key => &$phrases) {
                ksort($phrases);
            }

            return ['lang_list' => $languages, 'phrases' => $combinedArray];
        }));

        $twig->addFunction(new TwigFunction('phrase_array', function ($arr) {
            $userLang = \Ofey\Logan22\controller\config\config::load()->lang()->lang_user_default();
            foreach ($arr as $lang => $phrase) {
                if ($userLang == $lang) {
                    return $phrase;
                }
            }

            return "[no phrase to lang: {$userLang}]";
        }));

        $twig->addFunction(new TwigFunction('server_online_status', function () {
            $onlinePlayers = online::server_online_status();
            if (empty($onlinePlayers)) {
                return false;
            }

            return array_reverse($onlinePlayers);
        }));

        $twig->addFunction(new TwigFunction('get_default_page', function ($str, $server_id) {
            $pId = server::get_default_desc_page_id($server_id);

            return $pId;
        }));

        $twig->addFunction(new TwigFunction('http_referer', function () {
            return $_SERVER['HTTP_REFERER'] ?? null;
        }));

        $twig->addFunction(
          new TwigFunction(
            'json_decode',
            fn(string $value, ?bool $assoc = null) => json_decode($value, $assoc, 512, \JSON_THROW_ON_ERROR),
          )
        );

        $twig->addFunction(new TwigFunction('json_encode', function ($jsonTxt) {
            return json_encode($jsonTxt, 512, \JSON_THROW_ON_ERROR);
        }));

        //Возвращает сумму чисел в массиве по конкретному полю
        $twig->addFunction(new TwigFunction('array_field_sum', function (array $array, string $field) {
            return array_reduce($array, function ($sum, $players) use ($field) {
                return $sum + ($players[$field] ?? 0);
            }, 0);
        }));

        $twig->addFunction(new TwigFunction('get_clanhall', function ($id) {
            return clanhall::get($id);
        }));

        $twig->addFunction(new TwigFunction('get_fort', function ($id) {
            return fort::get($id);
        }));

        $twig->addFunction(new TwigFunction('get_castle', function ($id) {
            return castle::get($id);
        }));

        $twig->addFunction(new TwigFunction('icon', function ($fileIcon = null) {
            return client_icon::icon($fileIcon);
        }));

        //Кол-во завершенных и не завершенных рефералов
        $twig->addFunction(new TwigFunction('referral_count', function ($referrals) {
            if ( ! is_array($referrals)) {
                throw new InvalidArgumentException('Argument must be an array.');
            }

            function isReferralDone($referral)
            {
                return isset($referral['done']) && $referral['done'];
            }

            $completedCount = array_reduce($referrals, function ($count, $referral) {
                if (isReferralDone($referral)) {
                    $count++;
                }

                return $count;
            }, 0);

            $totalCount = count($referrals);
            if ($totalCount === 0) {
                return [
                  'completed' => 0,
                  'continues' => 0,
                  'made'      => 0,
                ];
            }

            return [
              'completed' => $completedCount,
              'continues' => $totalCount - $completedCount,
              'made'      => $completedCount / $totalCount * 100,
            ];
        }));

        //bool прошло ли больше N времени
        $twig->addFunction(new TwigFunction("testOfTime", function ($mysqlTime) {
            if (time() - strtotime($mysqlTime) > 3600 * 3) {
                return false;
            }

            return true;
        }));

        $twig->addFunction(new TwigFunction("redirect", function ($url) {
            header("Location: $url");
            exit();
        }));

        $twig->addFunction(new TwigFunction("templates", function () {
            return fileSys::dir_list("template");
        }));

        $twig->addFunction(new TwigFunction("timezone_list", function () {
            return timezone::all();
        }));

        $twig->addFunction(new TwigFunction("timezone", function ($time = null) {
            if ($time === null) {
                return 'Не указано время';
            }
            $timezone = auth::get_timezone();

            $date = new DateTime($time);
            $date->setTimezone(new DateTimeZone($timezone));

            return $date->format('Y-m-d H:i:s');
        }));

        $twig->addFunction(new TwigFunction('referral_link', function () {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $name   = user::self()->getName() ?: user::self()->getId();

            return $scheme . $_SERVER['HTTP_HOST'] . "/signup/" . mb_strtolower($name);
        }));

        $twig->addFunction(new TwigFunction('get_email_template', function () {
            return \Ofey\Logan22\component\mail\mail::getTemplates();
        }));

        $twig->addFunction(new TwigFunction("logsAll", function ($limit = 100) {
            return log::getLogs($limit);
        }));

        $twig->addFunction(new TwigFunction("logTypes", function () {
            return logTypes::cases();
        }));

        $twig->addFunction(new TwigFunction('HTTP_HOST', function ($fullUrl = false) {
            return http::getHost($fullUrl);
        }));

        $twig->addFunction(new TwigFunction('action', function ($name, array $params = []) {
            if ( ! empty($params)) {
                return action::get($name, ...$params);
            } else {
                return action::get($name);
            }
        }));

        $twig->addFunction(new TwigFunction("getPluginActive", function ($name = null) {
            return plugin::getPluginActive($name);
        }));

        $twig->addFunction(new TwigFunction("getPluginSetting", function ($name) {
            return plugin::getSetting($name);
        }));

        $twig->addFunction(new TwigFunction("getDirPlugin", function ($setting) {
            $ads = "/src/component/plugins/";
            if($setting['isCustom']){
                $ads = "/custom/plugins/";
            }
            return $ads . $setting['PLUGIN_DIR_NAME'];
        }));

        $twig->addFunction(new TwigFunction("get_self_notification", function () {
            return notification::get_self_notification();
        }));

        //Список плагинов, которые показываем в меню пользователю
        $twig->addFunction(new TwigFunction("show_plugins", function () {
            return self::pluginsAll();
        }));

        $twig->addFunction(new TwigFunction("startpacks", function () {
            return startpack::get();
        }));

        $twig->addFunction(new TwigFunction("get_plugin_config", function ($plugin_name, $config = "config.php") {
            $plugin_type = Route::get_plugin_type($plugin_name);
            if ($plugin_type == "component") {
                $pluginsPath = "src/component/plugins";
            } elseif ("custom") {
                $pluginsPath = "custom/plugins";
            }
            $pluginPath = "{$pluginsPath}/{$plugin_name}/{$config}";
            $plugins    = fileSys::dir_list($pluginsPath);
            if (in_array($plugin_name, $plugins)) {
                $configFile = ($pluginPath);
                if (file_exists($configFile)) {
                    return require $configFile;
                }
            }

            return false;
        }));

        return $twig;
    }

    private static function processPluginsDir($dir, $isCustom = false): array
    {
        $pluginsAll = [];
        $pluginsDir = fileSys::dir_list($dir);
        foreach ($pluginsDir as $key => $value) {
            if (in_array($value, self::$pluginNames)) {
                continue;
            }
            $settingsPath = fileSys::get_dir("$dir/$value/settings.php");
            if ( ! file_exists($settingsPath)) {
                unset($pluginsDir[$key]);
                continue;
            }
            $setting = include $settingsPath;
            if (isset($setting['PLUGIN_HIDE']) && $setting['PLUGIN_HIDE']) {
                unset($pluginsDir[$key]);
                continue;
            }
            $setting['PLUGIN_DIR_NAME'] = $value;
            if ($isCustom) {
                $setting['isCustom'] = true;
            } else {
                $setting['isCustom'] = false;
            }
            self::$pluginNames[] = $value;
            $pluginsAll[$key]    = $setting;
        }

        return $pluginsAll;
    }

    public static function pluginsAll(): array
    {
        if (empty(self::$pluginsAllCustomAndComponents)) {
            $pluginsAllCustom                    = self::processPluginsDir("custom/plugins/");
            $pluginsAllComponents                = self::processPluginsDir("src/component/plugins/");
            self::$pluginsAllCustomAndComponents = array_merge($pluginsAllCustom, $pluginsAllComponents);
        }

        return self::$pluginsAllCustomAndComponents;
    }

    private static function user_var_func(?Environment $twig = null): Environment
    {
        if ($twig === null) {
            throw new \InvalidArgumentException('Twig environment cannot be null');
        }

        $twig->addFunction(new TwigFunction('get_user_variables', function ($varName) {
            return auth::get_user_variables($varName);
        }));

        return $twig;
    }

    public static function displayDemo(string $template)
    {
        self::$categoryCabinet = true;
        if (file_exists(
          ("template/" . \Ofey\Logan22\controller\config\config::load()->template()->getName() . "/object.php")
        )) {
            $additionalVars = require (
              "template/" . \Ofey\Logan22\controller\config\config::load()->template()->getName() . "/object.php"
            );
            if (is_array($additionalVars)) {
                self::$allTplVars = array_merge(self::$allTplVars, $additionalVars);
            }
        }
        self::display($template);
    }

    static function customizeFilePath(string $filePath, bool $relativePath = false): string {
        $pathInfo = pathinfo($filePath);
        if (!isset($pathInfo['dirname'], $pathInfo['filename'], $pathInfo['extension'])) {
            return $filePath;
        }
        $customFileName = 'custom_' . $pathInfo['filename'] . '.' . $pathInfo['extension'];
        if($relativePath) {
            return ltrim(self::$templatePath . $pathInfo['dirname'], "/") . '/' . $customFileName;
        }else{
            return ltrim($pathInfo['dirname'], '/') . '/' . $customFileName;
        }
    }

public static function display($tplName) {

        //Проверка, есть ли кастомный файл вместо стандартного
        if (file_exists(self::customizeFilePath($tplName, true))) {
            $tplName = self::customizeFilePath($tplName, false);
        }

        $twig = self::preload();

        try {
            // Если загрузка идет через аякс, то возвращаем только контент, используется при переходе по ссылкам
            if (self::$ajaxLoad) {
                $template = $twig->load($tplName);
                if ($template->hasBlock("content")) {
                    $html  = $template->renderBlock("content", self::$allTplVars);
                    $title = $template->hasBlock("title") ? $template->renderBlock("title") : null;
                    board::html($html, $title);
                } else {
                    // Обработка отсутствия блока "content"
                    // Можно добавить действия по умолчанию или обработку ошибки здесь
                    // Например: board::html("Default content", "Default title");
                }
            } else {
                $template = $twig->load($tplName);
                echo $template->render(self::$allTplVars);
            }
        } catch (Exception $e) {
            $txt  = "<h4>TEMPLATE ERROR</h4>";
            $txt  .= "Message: " . $e->getMessage() . "<br>";
            $txt  .= "File: " . $e->getFile() . "<br>";
            $txt  .= "Line: " . $e->getLine() . "<br>";
            $txt  .= "Code: ";
            $file = fopen($e->getFile(), "r");
            if ($file) {
                for ($i = 1; $i < $e->getLine(); ++$i) {
                    if (fgets($file) === false) {
                        break;
                    }
                }
                $line = fgets($file);
                fclose($file);
                $txt .= htmlspecialchars($line);
            }
            echo $txt;
            logs::loggerError(preg_replace('/<h4[^>]*>|<\/h4>|<br[^>]*>/', "\n", $txt));
        }
    }

    public static function displayPlugin($tplName): void
    {
        $parts = explode('/', trim($tplName, '/')); // Убираем ведущий и завершающий слэши, затем разбиваем строку

        if (isset($parts[0]) && $parts[0] !== '') {
            $pluginDirName = $parts[0];
        } else {
            echo "Первая папка не найдена.";exit;
        }
        $plugin_type   = Route::get_plugin_type($pluginDirName);
        if ($plugin_type == "component") {
            self::addVar("template_plugin", ("/src/component/plugins/{$pluginDirName}"));
        } elseif ("custom") {
            self::addVar("template_plugin", ("/custom/plugins/{$pluginDirName}"));
        }
        $twig = self::preload($tplName);
        if (self::$ajaxLoad) {
            $template = $twig->load($tplName);
            $html     = $template->renderBlock("content", self::$allTplVars);
            $title    = $template->renderBlock("title");
            board::html($html, $title);
        } else {
            $template = $twig->load($tplName);
            echo $template->render(self::$allTplVars);
        }
    }

    /**
     * @param           $var
     * @param   string  $value
     *
     * @return void
     * Добавление переменной к выводу шаблона
     */
    public static function addVar($var, mixed $value = 'None')
    {
        if (is_array($var)) {
            self::$allTplVars = array_merge(self::$allTplVars, $var);
        } else {
            self::$allTplVars[$var] = $value;
        }
    }

    private static function pluginLoadSetting($pl_dir)
    {
        $plugins = fileSys::dir_list($pl_dir);
        foreach ($plugins as $key => $value) {
            if ( ! file_exists(fileSys::dir_list("{$pl_dir}/$value/settings.php"))) {
                unset($plugins[$key]);
            }
        }
        foreach ($plugins as $key => $value) {
            $setting = include fileSys::dir_list("{$pl_dir}/$value/settings.php");
            if (isset($setting['PLUGIN_HIDE'])) {
                if ($setting['PLUGIN_HIDE']) {
                    unset($plugins[$key]);
                    continue;
                }
            }
            if ( ! isset($setting['INCLUDES'])) {
                unset($plugins[$key]);
                continue;
            }

            return $setting;
        }

        return false;
    }

}
