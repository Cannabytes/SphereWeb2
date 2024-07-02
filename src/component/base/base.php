<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 20.08.2022 / 21:46:47
 */

namespace Ofey\Logan22\component\base;

use Error;

class base {

    /**
     * @return array
     */
    static public function sql_base_source($filename = "*.php"): array {
        $filelist = glob("src/component/base/source/{$filename}");
        $arrFilename = [];
        foreach($filelist as $file) {
            $arrFilename[] = basename($file);
        }
        return $arrFilename;
    }

    //Возвращает запрос, и его параметры
    static public function get_sql_source($class, $name) {
        if (method_exists($class, $name)) {
            return $class::$name();
        } else {
            exit("Error: Method not found in class: $class, $name");
        }
    }

    /**
     * Возращаем имя класса из файла
     *
     * @param $file
     *
     * @return false|string
     */
    static public function get_class_php($file) {
        include($file);
        $classes = get_declared_classes();
        $class = end($classes);
        return $class;
    }

    /**
     * Возращает массив с пространсом имен классов коллекции запросов
     * @return bool|array|string
     */
    static public function all_class_base_data(): array {
        $s = self::sql_base_source();
        array_walk($s, function (&$r) {
            $r = self::get_class_php("src/component/base/source/" . $r);
        });
        return $s;
    }

}