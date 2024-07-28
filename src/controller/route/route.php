<?php

namespace Ofey\Logan22\controller\route;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;
use ReflectionMethod;

class route
{

    private const ROUTES_FILE = '/data/routes.php';

    private static ?array $routes = null;

    private static ?array $routesAll = null;

    private static ?array $disableList = null;

    public static function all(): void
    {
        foreach(self::$routesAll as &$route) {
            $route['enable'] = route::getDisabledRoutes($route['pattern']) === false;
        }
        tpl::addVar(['routers' => self::$routesAll]);
        tpl::display("/admin/route.html");
    }

    public static function getRoutes($userAccessLevel): array
    {
        //Загружаем отключенные роутеры
        self::getDisabledRoutes();
        if (self::$routes === null) {
            self::$routes = self::loadRoutesFromFile($userAccessLevel);
        }

        return self::$routes;
    }

    public static function getDisabledRoutes($pattern = null): null|array|bool
    {
        if ($pattern != null) {
            return in_array($pattern, self::$disableList);
        }

        if (self::$disableList != null) {
            return self::$disableList;
        }

        $rows = sql::getRow("SELECT `data` FROM `server_cache` WHERE `type` = 'route_disabled'");
        if ($rows === false) {
            return self::$disableList = [];
        }
        return self::$disableList = json_decode($rows['data'], true);
    }

    private static function loadRoutesFromFile($userAccessLevel): array
    {
        $filePath        = fileSys::get_dir(self::ROUTES_FILE);
        $routes          = file_exists($filePath) ? include $filePath : [];
        self::$routesAll = $routes;
        if ($userAccessLevel === null) {
            return $routes;
        }

        return self::filterAccessByRole($routes, $userAccessLevel);
    }

    private static function filterAccessByRole(array $data, string $role): array
    {
        return array_filter($data, function ($item) use ($role) {
            // Проверка наличия 'any' в access
            if (in_array('any', $item['access'])) {
                return true;
            }

            // Проверка наличия указанной роли в access
            return in_array($role, $item['access']);
        });
    }

    public static function add(): void
    {
        $pattern   = $_POST['pattern'] ?? self::errorAndExit("Не заполнен паттерн");
        $typeRoute = $_POST['typeRoute'] ?? self::errorAndExit("Не заполнен тип маршрута");
        $file      = $_POST['file'] ?? '';
        $namespace = $_POST['namespace'] ?? '';
        $access    = $_POST['access'] ?? ['user'];
        $method    = $_POST['method'] ?? self::errorAndExit("Не заполнен метод");
        $weight    = (int)($_POST['weight'] ?? 0);
        $comment   = $_POST['comment'] ?? '';

        if ( ! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        $func = self::determineFunction($typeRoute, $namespace);

        self::$routesAll[] = self::createRoute($method, $pattern, $func, $access, $weight, $file, $comment);

        self::saveRoutesOrFail();
        board::success("Добавлен новый маршрутизатор");
    }

    private static function errorAndExit(string $message): void
    {
        board::error($message);
        exit;
    }

    private static function determineFunction(string $typeRoute, string $namespace): ?string
    {
        return match ($typeRoute) {
            'debug' => 'debug',
            'method' => $namespace,
            default => null,
        };
    }

    private static function createRoute(
      string $method,
      string $pattern,
      ?string $func,
      array $access,
      int $weight,
      string $page,
      string $comment,
      bool $enable = true
    ): array {
        $func = str_replace('\\\\', "\\", $func);

        return [
          'enable'  => $enable,
          'method'  => $method,
          'pattern' => $pattern,
          'func'    => $func,
          'access'  => $access,
          'weight'  => $weight,
          'page'    => $page,
          'comment' => $comment,
        ];
    }

    private static function saveRoutesOrFail(): void
    {
        if ( ! self::saveRoutesToFile()) {
            self::errorAndExit("Не удалось записать данные роутер.");
        }
    }

    private static function saveRoutesToFile(): bool
    {
        $fileContent = "<?php\n\nreturn " . var_export(self::$routesAll, true) . ";\n";

        return file_put_contents(fileSys::get_dir(self::ROUTES_FILE), $fileContent) !== false;
    }

    public static function edit(): void
    {
        $pattern   = $_POST['pattern'] ?? self::errorAndExit("Не заполнен паттерн");
        $typeRoute = $_POST['typeRoute'] ?? self::errorAndExit("Не заполнен тип маршрута");
        $file      = $_POST['file'] ?? '';
        $namespace = $_POST['namespace'] ?? '';
        $access    = $_POST['access'] ?? ['any'];
        $method    = $_POST['method'] ?? 'GET';
        $weight    = (int)($_POST['weight'] ?? 0);
        $comment   = $_POST['comment'] ?? '';

        if ( ! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        $func = self::determineFunction($typeRoute, $namespace);

        $found = false;
        foreach (self::$routesAll as &$route) {
            if ($route['method'] === $method && $route['pattern'] === $pattern) {
                $route = self::createRoute($method, $pattern, $func, $access, $weight, $file, $comment, $route['enable']);
                $found = true;
                break;
            }
        }

        if ($found) {
            self::saveRoutesOrFail();
            board::success("Роутер обновлен");
        } else {
            board::error("Роутер не найден");
        }
    }

    public static function update_enable(): void
    {
        $pattern   = $_POST['pattern'] ?? self::errorAndExit("Не заполнен паттерн");
        $isRemove = false;
        $isDisable = self::getDisabledRoutes($pattern);
        if ($isDisable) {
            $key = array_search($pattern, self::$disableList);
            if ($key !== false) {
                $isRemove = true;
                unset(self::$disableList[$key]);
            }
        } else {
            self::$disableList[] = $pattern;
        }

        $data = json_encode(self::$disableList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        sql::run("DELETE FROM `server_cache` WHERE `type` = 'route_disabled'");
        sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (0, ?, ?, ?)", [
          "route_disabled",
          $data,
          time::mysql(),
        ]);

        board::success($isRemove ? "Роутер включен" : "Роутер отключен");

    }

    public static function getDirFiles(): void
    {
        $dir      = $_POST['dir'] ?? __DIR__;
        $startDir = fileSys::get_dir();
        $startDir = str_replace('\\', '/', $startDir);

        if (pathinfo($dir, PATHINFO_EXTENSION) === 'php') {
            $fileContent = file_get_contents($startDir . $dir);
            preg_match('/namespace\s+([^;]+);/', $fileContent, $namespaceMatches);
            $namespace  = $namespaceMatches[1] ?? '';
            $className  = '';
            $methods    = [];
            $tokens     = token_get_all($fileContent);
            $classFound = false;
            foreach ($tokens as $key => $token) {
                if ( ! $classFound && $token[0] === T_CLASS) {
                    if (isset($tokens[$key + 2]) && $tokens[$key + 2][0] === T_STRING) {
                        $className  = $tokens[$key + 2][1];
                        $classFound = true;
                        continue;
                    }
                }
                if ($classFound && $token[0] === T_FUNCTION && isset($tokens[$key + 2]) && $tokens[$key + 2][0] === T_STRING) {
                    $methodName       = $tokens[$key + 2][1];
                    $reflectionMethod = new ReflectionMethod($namespace . '\\' . $className, $methodName);
                    if ($reflectionMethod->isPublic()) {
                        $methods[] = $methodName;
                    }
                }
            }
            $namespace = str_replace('Ofey\Logan22\\', '', $namespace);
            $result    = [
              'namespace' => $namespace,
              'className' => $className,
              'methods'   => $methods,
            ];
            echo json_encode($result);

            return;
        }

        $dir = $startDir . $dir;
        $f   = glob("$dir/{,.}[!.,!..]*", GLOB_BRACE);

        // Замена обратных слешей на прямые (Linux-подобные слеши)
        foreach ($f as $key => $path) {
            $f[$key] = str_replace('\\', '/', $path);
        }

        foreach ($f as $key => $path) {
            if (is_dir($path)) {
                // Это директория, оставляем как есть
            } elseif ( ! str_ends_with($path, '.php')) {
                // Если файл не заканчивается на .php, удаляем его из массива
                unset($f[$key]);
            }
        }

        // Удаление файлов и папок, имена которых начинаются с точки
        foreach ($f as $key => $path) {
            $basename = basename($path);
            if ($basename[0] === '.') {
                unset($f[$key]);
            } else {
                $f[$key] = str_replace([$startDir], '', $path);
                if ($basename[0] === '/') {
                    $f[$key] = ltrim($basename, '/');
                }
            }
        }

        echo json_encode($f);
    }

}
