<?php
namespace Ofey\Logan22\controller\config;

use Exception;
use Ofey\Logan22\component\fileSys\fileSys;

class dsys
{
    private static array $classArray = [];

    static function initPaySysClass()
    {
        $all_donate_system = fileSys::get_dir_files("src/component/donate", [
            'basename' => true,
            'fetchAll' => true,
            'only_non_empty_folders' => true,
        ]);
        $key = array_search("monobank", $all_donate_system);
        if ($key !== false) {
            unset($all_donate_system[$key]);
        }

        foreach ($all_donate_system as $system) {
            $routePath = "src/component/donate/{$system}/pay.php";

            if (file_exists($routePath)) {
                include $routePath;
                try {
                    if (class_exists($system)) {
                        $classInstance = new $system();
                        self::$classArray[$system] = $classInstance;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }


    }

    static function getClone($name)
    {
        // Проверка на существование класса в массиве
        if (isset(self::$classArray[$name])) {
            return clone self::$classArray[$name]; // Возвращаем клон объекта
        }
    }
}
