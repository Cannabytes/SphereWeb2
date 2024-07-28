<?php

namespace Ofey\Logan22\model\phrases;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;

class phrases
{


    static function save()
    {
        $phrases = $_POST['phrases'] ?? board::error("Not phrases array");
        $phrasesArray = json_decode($phrases, true);

        $phraseFormat = [];

        foreach($phrasesArray AS $key=>$phrases){
            foreach($phrases AS $lang=>$phrase){
                $phraseFormat[$lang][$key] = $phrase;
            }
        }

        // Директория для сохранения файлов
        $directory = fileSys::get_dir('/data/languages/');
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_writable($directory)) {
            board::error("Ошибка: Директория '$directory' не доступна для записи.");
        }

        foreach ($phraseFormat as $key => $values) {
            // Название файла
            $fileName = $directory . $key . '.php';

            // Начало файла с массивом
            $data = "<?php\nreturn [\n";

            // Добавление каждой пары ключ-значение с экранированием кавычек
            foreach ($values as $subKey => $value) {
                $value = str_replace("\"", "'", $value);
                $escapedValue = addslashes($value);
                $data .= "\t'$subKey' => '{$escapedValue}',\n";
            }

            // Закрытие массива и файла
            $data .= "];\n";

            // Запись в файл
            try {
                $result = file_put_contents($fileName, $data);
                if ($result === false) {
                    throw new Exception("Не удалось записать в файл $fileName");
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                board::error("Ошибка: " . $e->getMessage());
            }
        }

        board::success("Сохранено");
    }

}