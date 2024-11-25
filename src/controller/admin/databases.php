<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;
use ZipArchive;

class databases
{

    public static function downloadAccounts(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['loginId'])) {
            board::success("Не передан ID логина");
            return;
        }

        \Ofey\Logan22\component\sphere\server::setShowError(true);
        $data = \Ofey\Logan22\component\sphere\server::downloadFile(type::DOWNLOAD_ACCOUNTS, [
            'loginId' => (int)$data['loginId'],
        ]);
        echo json_encode($data);
    }

    public static function deleteImportFile(): void
    {
        // Получаем данные из тела запроса
        $data = json_decode(file_get_contents("php://input"), true);

        // Проверяем, указан ли файл
        if (!isset($data['filename'])) {
            http_response_code(400); // Ошибка 400 - неверный запрос
            echo json_encode(['error' => 'Не указан путь к файлу.']);
            return;
        }

        // Формируем полный путь к файлу
        $filePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($data['filename'], '/');

        // Проверяем, является ли путь безопасным
        if (realpath($filePath) === false || !str_starts_with(realpath($filePath), getcwd())) {
            http_response_code(403); // Ошибка 403 - доступ запрещен
            echo json_encode(['error' => 'Доступ запрещен к указанному файлу.']);
            return;
        }

        // Проверяем, существует ли файл
        if (!file_exists($filePath)) {
            http_response_code(404); // Ошибка 404 - файл не найден
            echo json_encode(['error' => 'Файл не найден.']);
            return;
        }

        // Пытаемся удалить файл
        if (!unlink($filePath)) {
            http_response_code(500); // Ошибка 500 - не удалось удалить файл
            echo json_encode(['error' => 'Не удалось удалить файл.']);
        }
    }

    public static function loadAccounts(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
            $file = $_FILES['file'];
            $loginId = $_POST['loginId'];
            // Убедимся, что файл имеет правильное расширение
            $allowedExtensions = ['zip', 'json'];
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

            if (in_array($fileExtension, $allowedExtensions)) {
                // Папка для сохранения загруженных файлов
                $uploadDir = 'uploads/';
                $uploadFile = $uploadDir . basename($file['name']);

                // Если это JSON файл, архивируем его в ZIP
                if ($fileExtension == 'json') {
                    $zipFile = $uploadDir . pathinfo($file['name'], PATHINFO_FILENAME) . '.zip';

                    // Создаём новый архив
                    $zip = new ZipArchive();
                    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                        // Добавляем файл JSON в архив
                        $zip->addFile($file['tmp_name'], basename($file['name']));
                        $zip->close();

                        // Удаляем исходный JSON файл после архивации
                        unlink($file['tmp_name']);
                        $uploadFile = $zipFile;
//                        echo json_encode(['status' => 'success', 'message' => 'Файл JSON успешно загружен и заархивирован в ZIP']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Не удалось создать ZIP архив']);
                    }
                } else {
                    // Для файлов ZIP просто перемещаем их в папку
                    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
//                        echo json_encode(['status' => 'success', 'message' => 'Файл успешно загружен']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Не удалось загрузить файл']);
                    }
                }

                $data = \Ofey\Logan22\component\sphere\server::uploadFile($uploadFile, type::LOAD_ACCOUNTS, [
                    'loginId' => (int)$loginId,
                ]);
                var_dump($data);exit();

            } else {
                echo json_encode(['status' => 'error', 'message' => 'Неверный формат файла']);
            }
        }
    }


    static public function delete()
    {
        $type = $_POST['type'] ?? board::error('No set type');
        $id = $_POST['id'] ?? board::error("no id");
        $response = \Ofey\Logan22\component\sphere\server::send(type::DELETE_DATABASE, [
            'type' => $type,
            'id' => (int)$id,
        ])->show(true)->getResponse();
        board::success("Удалено");
    }

    static public function show()
    {
        $servers = \Ofey\Logan22\model\server\server::getServerAll();
        $database = \Ofey\Logan22\component\sphere\server::send(type::GET_DATABASE_LIST)->show()->getResponse();

        $defaultDB = $database['defaultDB'];
        $gameServers = $database['gameservers'];
        $loginServers = $database['loginservers'];


        foreach ($defaultDB as $db) {
            foreach ($servers as &$server) {
                if ($db['id'] == $server->getId()) {
                    foreach ($loginServers as &$loginServer) {
                        if ($loginServer['id'] == $db['loginServerID']) {
                            $loginServer['default'] = true;
                        }
                    }
                    foreach ($gameServers as &$gameserver) {
                        if ($gameserver['id'] == $db['gameServerID']) {
                            $gameserver['default'] = true;
                        }
                    }
                }
            }
        }

        tpl::addVar([
            'defaultDB' => $defaultDB,
            'gameServers' => $gameServers,
            'loginServers' => $loginServers
        ]);
        tpl::display("/admin/databases.html");
    }


    // Оценка качества соединения с БД
    public static function connectionQualityCheck(): void
    {
        $type = filter_input(INPUT_POST, 'type') ?? board::error('No set type');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? board::error("No id");
        $response = \Ofey\Logan22\component\sphere\server::send(type::CONNECTION_QUALITY_DATABASE, [
            'type' => $type,
            'id' => $id,
        ])->show(true)->getResponse();
        if ($response['success'] ?? false) {
            $response['evaluate'] = self::evaluateConnection($response['connectionTime'], $response['queryTime']);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private static function evaluateConnection($connTime, $queryTime): string
    {
        $connTime /= 1000000;
        $queryTime /= 1000000;
        return match (true) {
            $connTime < 50 && $queryTime < 50 => "Идеальное соединение",
            $connTime < 100 && $queryTime < 100 => "Отличное соединение",
            $connTime < 200 && $queryTime < 200 => "Очень хорошее соединение",
            $connTime < 500 && $queryTime < 500 => "Хорошее соединение",
            $connTime < 1000 && $queryTime < 1000 => "Удовлетворительное соединение",
            default => "Плохое соединение",
        };
    }

    public static function portQualityCheck(): void
    {
        $type = filter_input(INPUT_POST, 'type') ?? board::error('No set type');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? board::error("No id");
        $response = \Ofey\Logan22\component\sphere\server::send(type::PORT_QUALITY_DATABASE, [
            'type' => $type,
            'id' => $id,
        ])->show(true)->getResponse();
        if ($response['success'] ?? false) {
            $response['evaluate'] = self::evaluatePort($response['pingTime']);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private static function evaluatePort($pingTime): string
    {
        return match (true) {
            $pingTime < 50 => "Идеальное соединение",
            $pingTime < 100 => "Отличное соединение",
            $pingTime < 200 => "Очень хорошее соединение",
            $pingTime < 500 => "Хорошее соединение",
            $pingTime < 1000 => "Удовлетворительное соединение",
            default => "Плохое соединение",
        };
    }

}