<?php
namespace Ofey\Logan22\controller\config;

use Ofey\Logan22\component\fileSys\fileSys;

class dsys
{
    private static array $classArray = [];

    static function initPaySysClass()
    {
        $all_donate_system = fileSys::get_dir_files("src/component/donate", [
            'basename' => true,
            'fetchAll' => true,
        ]);

        foreach ($all_donate_system as $system) {
            $routePath = "src/component/donate/{$system}/pay.php";
            if (file_exists($routePath)) {
                include $routePath;
                $classInstance = new $system(); // где $system - имя класса
                self::$classArray[$system] = $classInstance;
            }
        }

    }

    static function getClone($name)
    {
        // Проверка на существование класса в массиве
        if (isset(self::$classArray[$name])) {
            return clone self::$classArray[$name]; // Возвращаем клон объекта
        } else {
            throw new \Exception("Класс с именем {$name} не найден в массиве.");
        }
    }
}
