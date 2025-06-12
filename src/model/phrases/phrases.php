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
            $fileName = $directory . $key . '.php';

            $data = "<?php\nreturn [\n";

            foreach ($values as $subKey => $value) {
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

        foreach ($phraseFormat as $key => $values) {
            $fileName = $directory . $key . '.php';
            $data = "<?php\nreturn [\n";
            foreach ($values as $subKey => $value) {
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