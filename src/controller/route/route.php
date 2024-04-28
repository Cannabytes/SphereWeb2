<?php

namespace Ofey\Logan22\controller\route;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;
use ReflectionException;
use ReflectionMethod;

class route
{
    private static ?array $routes = null;
    static function getRoutes(): array
    {
        if(self::$routes!=null){
            return self::$routes;
        }
        return self::$routes = sql::getRows('SELECT * FROM `route`');
    }

    static function all(): void
    {
        $routers = self::getRoutes();
        tpl::addVar([
            'routers' => $routers,
        ]);
        tpl::display("/admin/route.html");
    }

    static function add()
    {
        $pattern = $_POST['pattern'] ?? board::error("Не заполнен паттерн");
        $typeRoute = $_POST['typeRoute'];
        $file = $_POST['file'];
        $namespace = $_POST['namespace'];
        $access = $_POST['access'] ?? ['user'];
        $method = $_POST['method'];
        $weight = $_POST['weight'] ?? 0;
        $comment = $_POST['comment'] ?? '';

        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }
        $func = null;
        switch ($typeRoute){
            case 'debug':
                $func = 'debug';
                break;
            case 'file':
                break;
            case 'method':
                $func = $namespace;
                break;
        }
        $access = json_encode($access);
        sql::run("INSERT INTO `route` ( `enable`, `method`, `pattern`, `func`, `access`, `weight`, `page`, `comment`) VALUES (1, ?, ?, ?, ?, ?, ?, ?)",
            [
                $method, $pattern, $func, $access, $weight, $file, $comment,
            ], true, false);
        board::success("Добавлен новый маршрутизатор");
    }

    static function edit()
    {
        $objectId = $_POST['objectId'] ?? board::error("Нет ID роутера");

        $pattern = $_POST['pattern'] ?? board::error("Не заполнен паттерн");
        $typeRoute = $_POST['typeRoute'];
        $file = $_POST['file'];
        $namespace = $_POST['namespace'];
        $access = $_POST['access'] ?? null;
        $method = $_POST['method'] ?? 'GET';
        $weight = $_POST['weight'] ?? 0;
        $comment = $_POST['comment'] ?? '';

        if (!str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }
        $func = null;
        switch ($typeRoute){
            case 'debug':
                $func = 'debug';
                break;
            case 'file':
                break;
            case 'method':
                $func = $namespace;
                break;
        }
        if($access){
            $access = json_encode($access);
            sql::run("UPDATE `route` SET `method` = ?, `pattern` = ?, `func` = ?, `access` = ?, `weight` = ?, `page` = ?, `comment` = ? WHERE `id` = ?",
                [
                    $method, $pattern, $func, $access, $weight, $file, $comment, $objectId
                ], true, false);
        }else{
            sql::run("UPDATE `route` SET `method` = ?, `pattern` = ?, `func` = ?, `weight` = ?, `page` = ?, `comment` = ? WHERE `id` = ?",
                [
                    $method, $pattern, $func, $weight, $file, $comment, $objectId
                ], true, false);
        }
        board::success("Роутер обновлен");
    }

    static function update_enable()
    {
        $id = $_POST['objId'];
        $isChecked = (int)$_POST['isChecked'] ?? null;
        if($isChecked===null){
            board::error("Состояние роутера не изменено");
        }
        sql::sql('UPDATE `route` SET `enable` = ? WHERE `id` = ?',[
            $isChecked, $id,
        ]);
        if($isChecked){
            board::success("Роутер отключен");
        }else{
            board::success("Роутер включен");
        }
    }

    /**
     * @throws ReflectionException
     */
    static function getDirFiles()
    {
        $dir = $_POST['dir'] ?? __DIR__;
        $startDir = fileSys::get_dir();
        $startDir = str_replace('\\', '/', $startDir);

        if (pathinfo($dir, PATHINFO_EXTENSION) === 'php') {
            $fileContent = file_get_contents($startDir . $dir);
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
                    $reflectionMethod = new ReflectionMethod($namespace . '\\' . $className, $methodName);
                    if ($reflectionMethod->isPublic()) {
                        $methods[] = $methodName;
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

        $dir = $startDir . $dir;
        $f = glob("$dir/{,.}[!.,!..]*", GLOB_BRACE);

        // Замена обратных слешей на прямые (Linux-подобные слеши)
        foreach ($f as $key => $path) {
            $f[$key] = str_replace('\\', '/', $path);
        }

        foreach ($f as $key => $path) {
            if (is_dir($path)) {
                // Это директория, оставляем как есть
            } elseif (!str_ends_with($path, '.php')) {
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