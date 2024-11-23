<?php

namespace Ofey\Logan22\component\plugins\items_increase;

use Ofey\Logan22\template\tpl;

class items_increase
{

    public function show()
    {
        tpl::displayPlugin("/items_increase/tpl/show.html");
    }

    public function save()
    {
        // Получаем данные из тела запроса
        $jsonData = file_get_contents('php://input');

        // Преобразуем JSON в массив (если необходимо)
        $data = json_decode($jsonData, true);

        // Логируем полученные данные
        file_put_contents('increase.log', '_POST: ' . print_r($data, true) . PHP_EOL, FILE_APPEND);
    }



}