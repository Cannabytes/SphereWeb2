<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 25.11.2022 / 1:03:29
 *
 * Псевдонимы для линков
 * {{alias('registration_account')}} всегда будет ссылаться на зарезервированный паттерн алиаса registration_account
 * $router->get("registration/account",
 * 'Ofey\Logan22\controller\registration\account::newAccount')->alias("registration_account");
 */

namespace Ofey\Logan22\route;

use Bramus\Router\Router;
use Ofey\Logan22\component\csrf\csrf;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\template\tpl;

class Route extends Router {

    private static array $pluginRegister;
    
    // Паттерны маршрутов, исключенных из CSRF проверки
    private static array $csrfExemptPatterns = [
        '/donate/(.+)/webhook',  // Webhook'и платежных систем (устаревший паттерн)
        '/donate/webhook/(.+)',  // Актуальный порядок сегментов
        '/api/(.+)',              // API endpoints (если есть внешние интеграции)
        '/response/request',      // Серверные уведомления от Sphere API
        '/admin/plugin/chests/get/all',
        '/admin/plugin/chests/update/order',
        '/admin/plugin/chests/get',
        '/admin/plugin/chests/delete',
        '/admin/plugin/registration_reward/setting/save',
    ];

    //Возваращет
    static public function get_plugin_type($pluginName) {
        foreach (self::$pluginRegister as $pluginType => $pluginArray) {
            foreach ($pluginArray as $name) {
                if ($pluginName == $name) {
                    return $pluginType;
                }
            }
        }
        return false;
    }

    function __addingPlugin() {
        $dir = fileSys::get_dir("src/component/donate/");
        $payments = fileSys::file_list($dir);
        foreach ($payments as $payment) {
            $routeFile = $dir . $payment . "/route.php";
            if (!file_exists($routeFile)) {
                continue;
            }

            $routes = [];
            include $routeFile;
            if (empty($routes) || !is_array($routes)) {
                continue;
            }

            foreach ($routes as $route) {
                if (!isset($route['pattern'], $route['call'])) {
                    continue;
                }

                if (isset($route['file'])) {
                    $handlerFile = $dir . $payment . "/" . $route['file'];
                    if (file_exists($handlerFile)) {
                        include_once $handlerFile;
                    }
                }

                $method = strtoupper($route['method'] ?? 'POST');

                // Автоматически добавляем исключения из CSRF:
                // 1) если явно указано csrf_exempt => true
                // 2) если маршрут визуально выглядит как webhook/endpoints для внешних сервисов
                if (($route['csrf_exempt'] ?? false) === true
                    || str_contains($route['pattern'], '/webhook/')
                    || str_contains($route['pattern'], '/callback/')
                ) {
                    self::addCsrfExemption($route['pattern']);
                }
                $callable = $route['call'];

                $register = match ($method) {
                    'GET' => 'get',
                    'ALL' => 'all',
                    default => 'post',
                };

                $this->$register($route['pattern'], function (...$var) use ($callable) {
                    $callable(...$var);
                });
            }
        }

        $dir = fileSys::get_dir("custom/plugins/");
        foreach($pluginCustom AS $plugin){
            self::$pluginRegister['custom'][] = $plugin;
            $data = $this->addPluginReg($dir, $plugin, $routes);
            if($data==null){
                continue;
            }
            [$route, $method] = $data;
        }
    }

    public function __construct() {
        $this->__addingPlugin();
        //Загрузка из шаблона указанных файлов
        if ($pages = tpl::template_design_route()) {
            foreach ($pages as $page => $template) {
                parent::get($page, function (...$GET) use ($template) {
                    tpl::addVar("GET", $GET);
                    tpl::displayDemo($template);
                });
            }
        }

    }

    private static array $aliases = [];
    private static string $pattern;

    public function all($pattern, $fn) {
        parent::all($pattern, $fn);
        self::$pattern = $pattern;
        return $this;
    }

    public function get($pattern, $fn) {
        parent::get($pattern, $fn);
        self::$pattern = $pattern;
        return $this;
    }
    
    public function post($pattern, $fn) {
        // Оборачиваем функцию для проверки CSRF
        $wrappedFn = function(...$args) use ($fn, $pattern) {
            // Проверяем, не находится ли маршрут в списке исключений
            if (!$this->isExemptFromCsrf($pattern)) {
                csrf::verifyOrFail();
            }
            
            // Выполняем оригинальную функцию
            if (is_string($fn)) {
                return call_user_func($fn, ...$args);
            } else {
                return $fn(...$args);
            }
        };
        
        parent::post($pattern, $wrappedFn);
        self::$pattern = $pattern;
        return $this;
    }
    
    /**
     * Проверка, исключен ли маршрут из CSRF проверки
     */
    private function isExemptFromCsrf(string $pattern): bool {
        foreach (self::$csrfExemptPatterns as $exemptPattern) {
            // Преобразуем паттерн в regex
            $regex = '#^' . str_replace(['\(', '\)', '\+'], ['(', ')', '+'], preg_quote($exemptPattern, '#')) . '$#';
            if (preg_match($regex, $pattern)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Добавить маршрут в список исключений из CSRF проверки
     */
    public static function addCsrfExemption(string $pattern): void {
        self::$csrfExemptPatterns[] = $pattern;
    }


    public function alias($alias, $pattern = null): static {
        if ($pattern == null) {
            self::add_alias($alias, '/' . self::$pattern);
        } else {
            if ($pattern[0] !== '/') {
                $pattern = '/' . $pattern;
            }
            self::add_alias($alias, $pattern);
        }
        return $this;
    }

    private function add_alias($alias, $pattern) {
        self::$aliases[] = [
            'alias' => $alias,
            'pattern' => $pattern,
        ];
    }

    public static function get_alias($alias) {
        foreach (self::$aliases as $a) {
            if ($a['alias'] == $alias) {
                return $a['pattern'];
            }
        }
        return 'No_alias';
    }

    /**
     * @param string $dir
     * @param mixed $plugin
     * @param $routes
     * @return array
     */
    private function addPluginReg(string $dir, mixed $plugin, $routes): ?array {
        if (file_exists($dir . $plugin . "/route.php")) {
            include_once $dir . $plugin . "/route.php";
            if(empty($routes)){
                return null;
            }
            foreach ($routes as $route) {
                if(isset($route['file'])){
                    if(file_exists($dir . $plugin . "/" . $route['file'])) {
                        include_once $dir . $plugin . "/" . $route['file'];
                    }
                }
                $method = "POST";
                if ($route['method'] == "GET") {
                    $method = "GET";
                }
                $this->$method($route['pattern'], function (...$var) use ($route) {
                    $route['call'](...$var);
                });
            }
            return array($route, $method);
        }
        return null;
    }
}
