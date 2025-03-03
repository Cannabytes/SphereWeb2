<?php

namespace Ofey\Logan22\component\plugins\itemMaster;

use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\template\tpl;

class itemMaster
{

    public function show($chronicle = null)
    {
        $items = [];
        if ($chronicle !== null) {
            $files = "custom/items/{$chronicle}";
            $filedata = fileSys::file_list($files);
            if($filedata){
                foreach($filedata as $filename) {
                    $data = require "{$files}/{$filename}";
                    $items = array_merge($items, array_values($data));
                }
            }
        }
        tpl::addVar([
            "items" => $items,
            "chronicle" => $chronicle,
            "chronicleList" => fileSys::dir_list("src/component/image/icon/items"),
        ]);
        tpl::displayPlugin("/itemMaster/tpl/item.html");
    }

    public function add($chronicle = null) {
        if($chronicle==null){
            redirect::location("/admin/modify/item");
        }
        tpl::addVar([
            "chronicle" => $chronicle,
        ]);
        tpl::displayPlugin("/itemMaster/tpl/add_item.html");
    }

    public function addIcon(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Метод не разрешен
            echo json_encode(['status' => 'error', 'message' => 'Неверный метод запроса']);
            exit;
        }

        if (empty($_FILES)) {
            http_response_code(400); // Плохой запрос
            echo json_encode(['status' => 'error', 'message' => 'Файл не загружен']);
            exit;
        }

        if (!isset($_POST['itemId']) || $_POST['itemId'] == "") {
            echo json_encode(['status' => 'error', 'message' => 'Не указан ID предмета']);
            exit;
        }
        $itemId = $_POST['itemId'];

        $fieldName = key($_FILES);
        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при загрузке файла']);
            exit;
        }

        $allowedTypes = ['image/webp', 'image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Недопустимый тип файла']);
            exit;
        }

        $maxFileSize = 1 * 1024 * 1024; // 1MB в байтах
        if ($file['size'] > $maxFileSize) {
            echo json_encode(['status' => 'error', 'message' => 'Файл слишком большой']);
            exit;
        }


        $uploadDir = fileSys::get_dir('uploads/images/icon/');

        // Проверяем существование директории или создаем её
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось создать директорию загрузки']);
                exit;
            }
        }

        // Проверяем права на запись в директорию
        if (!is_writable($uploadDir)) {
            echo json_encode(['status' => 'error', 'message' => 'Нет прав на запись в директорию загрузки']);
            exit;
        }

        try {
            $manager = ImageManager::gd();

            $image = $manager->read($file['tmp_name']);

            $width = $image->width();
            $height = $image->height();

            if ($width != $height) {
                $size = min($width, $height);

                $x = intval(($width - $size) / 2);
                $y = intval(($height - $size) / 2);

                $image->crop($size, $size, $x, $y);

                $width = $height = $size;
            }

            if ($width > 64 || $height > 64) {
                $image->scale(width: 64, height: 64);
            }

            $destination = $uploadDir . $itemId . '.webp';

            $destinationDir = dirname($destination);
            if (!is_dir($destinationDir) || !is_writable($destinationDir)) {
                throw new \Exception('Нет прав на запись в указанную директорию');
            }

            if (file_exists($destination) && !is_writable($destination)) {
                throw new \Exception('Невозможно перезаписать существующий файл');
            }

            if (!$image->save($destination)) {
                throw new \Exception('Ошибка при сохранении изображения');
            }

            if (!file_exists($destination)) {
                throw new \Exception('Файл не был создан после сохранения');
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Файл успешно загружен',
                'path' => '/uploads/images/icon/' . $itemId . '.webp',
                'dimensions' => [
                    'width' => $image->width(),
                    'height' => $image->height()
                ]
            ]);
            exit;
        } catch (\Exception $e) {
            error_log('Ошибка при загрузке иконки: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при обработке изображения: ' . $e->getMessage()]);
            exit;
        } catch (\Throwable $t) {
            error_log('Критическая ошибка при загрузке иконки: ' . $t->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Произошла непредвиденная ошибка при обработке изображения']);
            exit;
        }
    }


    public function addItemSave(): void {
        $itemId = filter_input(INPUT_POST, 'itemId', FILTER_VALIDATE_INT);
        $type = $_POST["type"] ?? 'etcitem';
        $grade = $_POST["grade"] ?? "";
        $itemname = $_POST["itemname"] ?? "NoItemName";
        $itemaddname = $_POST["itemaddname"] ?? "";
        $desc = $_POST["desc"] ?? "";
        $chronicle = $_POST["chronicle"] ?? "highFive";
        $is_stackable = isset($_POST["is_stackable"]);
        if ($itemId === null || $itemId === false) {
            board::error("Не указан или неверный ID предмета");
        }

        $data = [
            $itemId => [
                'id' => $itemId,
                'type' => $type,
                'name' => $itemname,
                'add_name' => $itemaddname,
                'description' => $desc,
                'icon' => "{$itemId}.webp",
                'is_stackable' => $is_stackable,
            ],
        ];

        if ($grade !== "" && $grade !== "ng" && $grade !== "none") {
            $data[$itemId]['crystal_type'] = $grade;
        }

        $exportedData = var_export($data, true);
        $txt = "<?php return {$exportedData};";

        $directory = "custom/items/{$chronicle}";
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = "{$directory}/{$itemId}.php";

        if (file_put_contents($filePath, $txt) === false) {
            board::error("Ошибка при сохранении файла {$filePath}");
        }

        board::redirect("/admin/modify/item/get/{$chronicle}");
        board::success("Предмет {$itemname} сохранен");
    }

    public function edit($chronicle, $itemId) {
        tpl::addVar([
            'chronicle' => $chronicle,
            'item_id' => $itemId
        ]);
        tpl::displayPlugin("/itemMaster/tpl/edit_item.html");
    }

    public function updateItemSave(): void
    {
        $this->addItemSave();
    }

    //Удаление объекта и изображение
//Удаление объекта и изображение
    public function delete(): void
    {
        $chronicle = filter_input(INPUT_POST, 'chronicle', FILTER_SANITIZE_SPECIAL_CHARS);
        $itemId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        // Проверка параметров
        if (empty($chronicle) || empty($itemId)) {
            board::error("Не указаны необходимые параметры для удаления предмета");
            return;
        }

        // Формирование путей к файлам
        $itemFilePath = fileSys::get_dir("custom/items/{$chronicle}/{$itemId}.php");
        $iconFilePath = fileSys::get_dir('uploads/images/icon/') . "{$itemId}.webp";

        $deleted = false;
        $errorMessage = "";

        // Удаление файла с данными предмета
        if (file_exists($itemFilePath)) {
            try {
                if (unlink($itemFilePath)) {
                    $deleted = true;
                } else {
                    $errorMessage .= "Не удалось удалить файл данных предмета. ";
                }
            } catch (\Exception $e) {
                $errorMessage .= "Ошибка при удалении файла данных предмета: " . $e->getMessage() . ". ";
            }
        }

        // Удаление файла изображения
        if (file_exists($iconFilePath)) {
            try {
                if (unlink($iconFilePath)) {
                    $deleted = true;
                } else {
                    $errorMessage .= "Не удалось удалить изображение предмета. ";
                }
            } catch (\Exception $e) {
                $errorMessage .= "Ошибка при удалении изображения предмета: " . $e->getMessage() . ". ";
            }
        }

        if ($deleted) {
            board::success("Предмет успешно удален");
        } else {
            if (empty($errorMessage)) {
                $errorMessage = "Файлы предмета не найдены";
            }
            board::error($errorMessage);
        }

        // Перенаправление на страницу со списком предметов
        redirect::location("/admin/modify/item/get/{$chronicle}");
    }

}