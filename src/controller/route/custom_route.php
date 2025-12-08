<?php

namespace Ofey\Logan22\controller\route;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;
use ReflectionMethod;

class custom_route
{

    private const CUSTOM_ROUTES_FILE = '/data/routers_custom.php';

    private static ?array $routes = null;

    private static ?array $routesAll = null;

    private static ?array $disableList = null;

    /**
     * Отобразить все кастомные роутеры
     */
    public static function all(): void
    {
        foreach(self::$routesAll as &$route) {
            $route['enable'] = custom_route::getDisabledRoutes($route['pattern']) === false;
        }
        tpl::addVar(['routers' => self::$routesAll]);
        tpl::display("/admin/route_custom.html");
    }

    /**
     * Получить все кастомные роутеры с учетом уровня доступа
     */
    public static function getRoutes($userAccessLevel): array
    {
        //Загружаем отключенные роутеры
        self::getDisabledRoutes();
        if (self::$routes === null) {
            self::$routes = self::loadRoutesFromFile($userAccessLevel);
        }

        return self::$routes;
    }

    /**
     * Получить список отключенных кастомных роутеров
     */
    public static function getDisabledRoutes($pattern = null): null|array|bool
    {
        if ($pattern != null) {
            return in_array($pattern, self::$disableList ?? []);
        }

        if (self::$disableList != null) {
            return self::$disableList;
        }

        $rows = sql::getRow("SELECT `data` FROM `server_cache` WHERE `type` = 'route_custom_disabled'");
        if ($rows === false) {
            return self::$disableList = [];
        }
        return self::$disableList = json_decode($rows['data'], true);
    }

    /**
     * Загрузить кастомные роутеры из файла
     */
    private static function loadRoutesFromFile($userAccessLevel): array
    {
        $filePath        = fileSys::get_dir(self::CUSTOM_ROUTES_FILE);
        $routes          = file_exists($filePath) ? include $filePath : [];
        self::$routesAll = $routes;
        if ($userAccessLevel === null) {
            return $routes;
        }

        return self::filterAccessByRole($routes, $userAccessLevel);
    }

    /**
     * Фильтровать роутеры по уровню доступа
     */
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

    /**
     * Добавить новый кастомный роутер
     */
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

        // Проверка на дублирование паттерна в кастомных маршрутах
        foreach (self::$routesAll as $existingRoute) {
            if ($existingRoute['pattern'] === $pattern && $existingRoute['method'] === $method) {
                board::error("Маршрут с таким паттерном и методом уже существует");
                exit;
            }
        }

        $func = self::determineFunction($typeRoute, $namespace);

        self::$routesAll[] = self::createRoute($method, $pattern, $func, $access, $weight, $file, $comment);

        self::saveRoutesOrFail();

        // Вернуть JSON с успешным статусом и редиректом
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'type' => 'notice',
            'ok' => true,
            'message' => 'Добавлен новый кастомный маршрутизатор',
            'redirect' => '/admin/route/custom'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Проверить, существует ли уже такой паттерн (только в кастомных маршрутах блокируем дублирование)
     */
    public static function checkPatternExists(): void
    {
        $pattern = $_POST['pattern'] ?? '';
        $method  = $_POST['method'] ?? 'GET';

        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        // Проверяем в кастомных маршрутах
        $customExists = false;
        foreach (self::$routesAll as $route) {
            if ($route['pattern'] === $pattern && $route['method'] === $method) {
                $customExists = true;
                break;
            }
        }

        // Проверяем в обычных маршрутах (только для информации)
        $builtInExists = false;
        $builtInRoutes = route::getRoutes(null);
        foreach ($builtInRoutes as $route) {
            if ($route['pattern'] === $pattern && $route['method'] === $method) {
                $builtInExists = true;
                break;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'customExists' => $customExists,
            'builtInExists' => $builtInExists,
            'pattern' => $pattern,
            'method' => $method
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Отправить ошибку и выйти
     */
    private static function errorAndExit(string $message): void
    {
        board::error($message);
        exit;
    }

    /**
     * Определить функцию по типу роутера
     */
    private static function determineFunction(string $typeRoute, string $namespace): ?string
    {
        return match ($typeRoute) {
            'debug' => 'debug',
            'link' => 'link',
            'method' => $namespace,
            default => null,
        };
    }

    /**
     * Создать объект роутера
     */
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

    /**
     * Сохранить роутеры или выйти с ошибкой
     */
    private static function saveRoutesOrFail(): void
    {
        if ( ! self::saveRoutesToFile()) {
            self::errorAndExit("Не удалось записать данные кастомного роутера.");
        }
    }

    /**
     * Сохранить роутеры в файл
     */
    private static function saveRoutesToFile(): bool
    {
        $fileContent = "<?php\n\nreturn " . var_export(self::$routesAll, true) . ";\n";

        return file_put_contents(fileSys::get_dir(self::CUSTOM_ROUTES_FILE), $fileContent) !== false;
    }

    /**
     * Отредактировать существующий кастомный роутер
     */
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

        // Получаем СТАРЫЕ значения для поиска маршрута
        $oldPattern = $_POST['oldPattern'] ?? $pattern;
        $oldMethod  = $_POST['oldMethod'] ?? $method;

        if ( ! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        if ( ! str_starts_with($oldPattern, '/')) {
            $oldPattern = '/' . $oldPattern;
        }

        $func = self::determineFunction($typeRoute, $namespace);

        $found = false;
        foreach (self::$routesAll as &$route) {
            // Ищем маршрут по СТАРОМУ паттерну и методу
            if ($route['method'] === $oldMethod && $route['pattern'] === $oldPattern) {
                $route = self::createRoute($method, $pattern, $func, $access, $weight, $file, $comment, $route['enable']);
                $found = true;
                break;
            }
        }

        if ($found) {
            self::saveRoutesOrFail();
            board::success("Кастомный роутер будет обновлен через 3 секунды");
        } else {
            board::error("Кастомный роутер не найден");
        }
    }

    /**
     * Удалить кастомный роутер
     */
    public static function delete(): void
    {
        $pattern = $_POST['pattern'] ?? self::errorAndExit("Не заполнен паттерн");
        $method  = $_POST['method'] ?? 'GET';

        if ( ! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        $found = false;
        foreach (self::$routesAll as $key => &$route) {
            if ($route['method'] === $method && $route['pattern'] === $pattern) {
                unset(self::$routesAll[$key]);
                $found = true;
                break;
            }
        }

        // Переиндексировать массив
        self::$routesAll = array_values(self::$routesAll ?? []);

        if ($found) {
            self::saveRoutesOrFail();
            board::success("Кастомный роутер будет удален через 3 секунды");
        } else {
            board::error("Кастомный роутер не найден");
        }
    }

    /**
     * Включить/отключить кастомный роутер
     */
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
        sql::run("DELETE FROM `server_cache` WHERE `type` = 'route_custom_disabled'");
        sql::sql("INSERT INTO `server_cache` (`server_id`, `type`, `data`, `date_create`) VALUES (0, ?, ?, ?)", [
          "route_custom_disabled",
          $data,
          time::mysql(),
        ]);

        board::success($isRemove ? "Кастомный роутер включен" : "Кастомный роутер отключен");

    }

    /**
     * Получить файлы из директории
     */
    public static function getDirFiles(): void
    {
        // Получаем директорию из POST или используем корневую директорию сайта
        $dir = $_POST['dir'] ?? '';

        // Получаем корневую директорию сайта
        $startDir = $_SERVER['DOCUMENT_ROOT'];
        // Нормализуем слеши
        $startDir = rtrim(str_replace('\\', '/', $startDir), '/') . '/';

        // Если передан PHP файл, обрабатываем его
        if (pathinfo($dir, PATHINFO_EXTENSION) === 'php') {
            $fullPath = $startDir . ltrim($dir, '/');
            if (!file_exists($fullPath)) {
                echo json_encode(['error' => 'File not found']);
                return;
            }

            $fileContent = file_get_contents($fullPath);
            preg_match('/namespace\s+([^;]+);/', $fileContent, $namespaceMatches);
            $namespace = $namespaceMatches[1] ?? '';
            $className = '';
            $methods = [];
            $tokens = token_get_all($fileContent);
            $classFound = false;

            foreach ($tokens as $key => $token) {
                if (!$classFound && $token[0] === T_CLASS) {
                    if (isset($tokens[$key + 2]) && $tokens[$key + 2][0] === T_STRING) {
                        $className = $tokens[$key + 2][1];
                        $classFound = true;
                        continue;
                    }
                }
                if ($classFound && $token[0] === T_FUNCTION && isset($tokens[$key + 2]) && $tokens[$key + 2][0] === T_STRING) {
                    $methodName = $tokens[$key + 2][1];
                    try {
                        $reflectionMethod = new ReflectionMethod($namespace . '\\' . $className, $methodName);
                        if ($reflectionMethod->isPublic()) {
                            $methods[] = $methodName;
                        }
                    } catch (\ReflectionException $e) {
                        // Пропускаем методы, которые не удалось проанализировать
                        continue;
                    }
                }
            }

            $namespace = str_replace('Ofey\Logan22\\', '', $namespace);
            $result = [
                'namespace' => $namespace,
                'className' => $className,
                'methods' => $methods,
            ];
            echo json_encode($result);
            return;
        }

        // Обработка директории
        $searchPath = $startDir . ltrim($dir, '/');
        $files = glob("$searchPath/{,.}[!.,!..]*", GLOB_BRACE);

        if ($files === false) {
            echo json_encode(['error' => 'Unable to read directory']);
            return;
        }

        $result = [];
        foreach ($files as $path) {
            $path = str_replace('\\', '/', $path);
            $relativePath = str_replace($startDir, '', $path);

            // Пропускаем скрытые файлы и директории
            if (basename($relativePath)[0] === '.') {
                continue;
            }

            // Добавляем только директории и PHP файлы
            if (is_dir($path) || pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $result[] = $relativePath;
            }
        }

        echo json_encode($result);
    }
}
