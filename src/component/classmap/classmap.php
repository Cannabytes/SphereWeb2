<?php
namespace Ofey\Logan22\component\classmap;

use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\template\tpl;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Exception;

class classmap
{
    public static function index($data = null): void
    {
        if(is_string($data)){
            $data = config::load()->{$data}();
        }

        if (is_null($data)) {
            $data = config::load();
        }

        $typeDescription = gettype($data);
        $output = "";

        if (is_string($data)) {
            // Если $data - строка, пытаемся загрузить класс
            $className = str_replace('/', '\\', $data);
            if (class_exists($className)) {
                $data = new $className();
                $typeDescription = 'Объект класса: ' . get_class($data);
            } else {
                throw new Exception("Класс $className не найден");
            }
        }

        if (is_object($data)) {
            // Это объект
            $typeDescription = 'Объект класса: ' . get_class($data);
            $reflection = new ReflectionClass($data);
            $methods = $reflection->getMethods();
            $properties = $reflection->getProperties();

            // Таблица методов
            $output .= "<table class='table'>";
            $output .= "<thead><tr><th>Метод</th><th>Видимость</th><th>Статичный</th><th>Возвращаемый тип</th><th>Комментарий</th></tr></thead>";
            $output .= "<tbody>";
            foreach ($methods as $method) {
                $returnType = $method->getReturnType();
                $returnTypeText = 'void';
                if ($returnType instanceof ReflectionNamedType) {
                    $returnTypeText = $returnType->getName();
                } elseif ($returnType instanceof ReflectionUnionType) {
                    $types = [];
                    foreach ($returnType->getTypes() as $type) {
                        if ($type instanceof ReflectionNamedType) {
                            $types[] = $type->getName();
                        }
                    }
                    $returnTypeText = implode('|', $types);
                }

                $docComment = $method->getDocComment();
                $docComment = htmlspecialchars($docComment); // Экранирование специальных символов

                $methodName = $method->name;
                if ($returnType instanceof ReflectionNamedType && class_exists($returnType->getName())) {
                    $methodLink = "<a href='/admin/classmap/{$methodName}'>$methodName</a>";
                } else {
                    $methodLink = $methodName;
                }

                $output .= "<tr>";
                $output .= "<td>$methodLink</td>";
                $output .= "<td>" . ($method->isPublic() ? "<span class='text-success'>public</span>" : ($method->isProtected()
                    ? "protected" : "<span class='text-danger'>private</span>")) . "</td>";
                $output .= "<td>" . ($method->isStatic() ? "да" : "нет") . "</td>";
                $output .= "<td>" . $returnTypeText . "</td>";
                $output .= "<td>" . ($docComment ?: "Нет комментария") . "</td>";
                $output .= "</tr>";
            }
            $output .= "</tbody></table>";

            // Таблица свойств
            $output .= "<table class='table'>";
            $output .= "<thead><tr><th>Свойство</th><th>Видимость</th><th>Значение</th><th>Комментарий</th></tr></thead>";
            $output .= "<tbody>";
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($data);
                $docComment = $property->getDocComment();
                $docComment = htmlspecialchars($docComment); // Экранирование специальных символов
                $output .= "<tr>";
                $output .= "<td>" . $property->getName() . "</td>";
                $output .= "<td>" . ($property->isPublic() ? "<span class='text-success'>public</span>" : ($property->isProtected()
                    ? "protected" : "<span class='text-danger'>private</span>")) . "</td>";
                $output .= "<td>" . htmlspecialchars(var_export($value, true)) . "</td>";
                $output .= "<td>" . ($docComment ?: "Нет комментария") . "</td>";
                $output .= "</tr>";
            }
            $output .= "</tbody></table>";

        } else {
            // Не объект (примитивные типы данных, такие как string, int и т.д.)
            $output .= "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        }

        tpl::addVar([
          "title" => "Класс " . (is_object($data) ? get_class($data) : $data),
          "typeDescription" => $typeDescription,
          "class" => $output,
        ]);
        tpl::display("admin/classmap.html");
    }
}
