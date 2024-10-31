<?php

namespace Ofey\Logan22\component\plugins\itemMaster;

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
            foreach($filedata as $filename) {
                $data = require "{$files}/{$filename}";
                $items = array_merge($items, array_values($data));
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

    public function addIcon() {
        header('Content-Type: application/json');

        // Проверяем метод запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Метод не разрешен
            echo json_encode(['status' => 'error', 'message' => 'Неверный метод запроса']);
            exit;
        }

        // Проверяем, есть ли загруженные файлы
        if (empty($_FILES)) {
            http_response_code(400); // Плохой запрос
            echo json_encode(['status' => 'error', 'message' => 'Файл не загружен']);
            exit;
        }
        // Проверяем, есть ли ID предмета
        if (!isset($_POST['itemId']) || $_POST['itemId'] == "") {
            echo json_encode(['status' => 'error', 'message' => 'Не указан ID предмета']);
            exit;
        }
        $itemId = $_POST['itemId'];

        // Получаем файл из запроса
        $fieldName = key($_FILES);
        $file = $_FILES[$fieldName];

        // Проверяем на ошибки загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при загрузке файла']);
            exit;
        }

        // Проверяем тип файла
        $allowedTypes = ['image/webp', 'image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Недопустимый тип файла']);
            exit;
        }

        // Проверяем размер файла (макс 3MB)
        $maxFileSize = 1 * 1024 * 1024; // 3MB в байтах
        if ($file['size'] > $maxFileSize) {
            echo json_encode(['status' => 'error', 'message' => 'Файл слишком большой']);
            exit;
        }

        // Устанавливаем директорию для загрузки
        $uploadDir = 'uploads/images/icon/';

        // Создаем директорию, если не существует
        if (!file_exists($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Не удалось создать директорию загрузки']);
            exit;
        }

        // Путь для сохранения файла
        $destination = $uploadDir . $itemId . '.webp';

        // Если файл уже в формате WEBP, просто перемещаем его
        if ($fileType === 'image/webp') {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось сохранить загруженный файл']);
                exit;
            }
        } else {
            // Конвертируем изображение в WEBP
            switch ($fileType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($file['tmp_name']);
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Неподдерживаемый тип файла']);
                    exit;
            }

            // Сохраняем изображение в формате WEBP
            if (!imagewebp($image, $destination)) {
                echo json_encode(['status' => 'error', 'message' => 'Ошибка при сохранении изображения']);
                exit;
            }

            // Освобождаем память
            imagedestroy($image);
        }

        // Возвращаем успешный ответ с путем к файлу
        echo json_encode([
            'status' => 'success',
            'message' => 'Файл успешно загружен',
            'path' => '/uploads/images/icon/' . $itemId . '.webp'
        ]);
        exit;
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

}