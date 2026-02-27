<?php

namespace Ofey\Logan22\model\phrases;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;

class phrases
{


    static function save()
    {
        $phrases = $_POST['phrases'] ?? board::error("Not phrases array");
        $phrasesArray = json_decode($phrases, true);

        if (empty($phrasesArray)) {
            board::error("Пустой массив фраз");
        }

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

        foreach ($phraseFormat as $lang => $newPhrases) {
            $fileName = $directory . $lang . '.php';

            // Загружаем существующие фразы (если файл существует)
            $existingPhrases = [];
            if (file_exists($fileName)) {
                $existingPhrases = include $fileName;
                if (!is_array($existingPhrases)) {
                    $existingPhrases = [];
                }
            }

            // Обновляем только те ключи, которые пришли
            foreach ($newPhrases as $key => $value) {
                $existingPhrases[$key] = $value;
            }

            // Сортируем по ключам для консистентности
            ksort($existingPhrases);

            $data = "<?php\nreturn [\n";

            foreach ($existingPhrases as $subKey => $value) {
                $value = str_replace("\"", "'", $value);
                $escapedValue = addslashes($value);
                $data .= "\t'$subKey' => '{$escapedValue}',\n";
            }

            $data .= "];\n";

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

        board::success(lang::get_phrase(217));
    }

    static function saveCustom()
    {
        $phrases = $_POST['phrases'] ?? board::error("Not phrases array");
        $phrasesArray = json_decode($phrases, true);

        if (empty($phrasesArray)) {
            board::error("Пустой массив фраз");
        }

        $phraseFormat = [];

        foreach($phrasesArray AS $key=>$phrases){
            foreach($phrases AS $lang=>$phrase){
                $phraseFormat[$lang][$key] = $phrase;
            }
        }

        $directory = fileSys::get_dir('/data/languages/custom/');
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_writable($directory)) {
            board::error("Ошибка: Директория '$directory' не доступна для записи.");
        }

        foreach ($phraseFormat as $lang => $newPhrases) {
            $fileName = $directory . $lang . '.php';

            // Загружаем существующие фразы (если файл существует)
            $existingPhrases = [];
            if (file_exists($fileName)) {
                $existingPhrases = include $fileName;
                if (!is_array($existingPhrases)) {
                    $existingPhrases = [];
                }
            }

            // Обновляем только те ключи, которые пришли
            foreach ($newPhrases as $key => $value) {
                $existingPhrases[$key] = $value;
            }

            // Сортируем по ключам для консистентности
            ksort($existingPhrases);

            $data = "<?php\nreturn [\n";
            foreach ($existingPhrases as $subKey => $value) {
                $value = str_replace("\"", "'", $value);
                $escapedValue = addslashes($value);
                $data .= "\t'$subKey' => '{$escapedValue}',\n";
            }
            $data .= "];\n";
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

        board::success(lang::get_phrase(217));
    }

}