<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

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
        $data = \Ofey\Logan22\component\sphere\server::send(type::DOWNLOAD_ACCOUNTS, [
            'loginId' => (int)$data['loginId'],
        ])->show()->getResponse();
        echo json_encode($data);
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

    public static function pollProgress(): void
    {
        // Отправляем запрос на GO-приложение, передавая jobId в параметрах
        $responseData = \Ofey\Logan22\component\sphere\server::send(type::LOAD_ACCOUNTS_PROGRESS, [
        ])->show()->getResponse();

        // Возвращаем полученные данные в формате JSON
        echo json_encode($responseData);
    }

    public static function loadAccounts(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Неверный формат файла']);
            return;
        }

        $file    = $_POST['file'] ?? board::error("Not accounts");
        $loginId = $_POST['loginId'] ?? board::error("Not loginId");
        $encrypt = "sphere";

        $parsedData   = [];
        $invalidLines = [];

        // Регулярка сразу извлекает email, account и password с учетом пробелов вокруг
        $pattern = '/^\s*(?<email>[^:\s]+@[^:\s]+\.[^:\s]+)\s*:\s*(?<account>[^:]+)\s*:\s*(?<password>.+)\s*$/u';

        // Разбиваем на строки, учитывая различные переносы строки
        $lines = preg_split('/\r\n|\r|\n/', trim($file));

        foreach ($lines as $lineNumber => $line) {
            if (trim($line) === '') {
                continue; // пропускаем пустые строки
            }

            if (preg_match($pattern, $line, $matches)) {
                // Дополнительная проверка email, если нужна строгая валидация
                if (!filter_var($matches['email'], FILTER_VALIDATE_EMAIL)) {
                    $invalidLines[] = "Строка " . ($lineNumber + 1) . " - Некорректный email: {$matches['email']}";
                    continue;
                }

                $parsedData[] = [
                    'email'    => $matches['email'],
                    'login'    => $matches['account'],
                    'password' => $matches['password']
                ];
            } else {
                $invalidLines[] = "Строка " . ($lineNumber + 1) . " - Некорректный формат: {$line}";
            }
        }

        $responseData = \Ofey\Logan22\component\sphere\server::send(type::LOAD_ACCOUNTS, [
            'loginId'  => (int)$loginId,
            'encrypt'  => $encrypt,
            'accounts' => $parsedData,
        ])->show()->getResponse();

        $responseData['invalid'] = $invalidLines;

        echo json_encode($responseData);
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

    public static function updateLoginserver(): void
    {
        $loginId = filter_input(INPUT_POST, 'loginId', FILTER_VALIDATE_INT) ?? board::error("No loginId");
        $host = filter_input(INPUT_POST, 'host') ?? board::error("No host");
        $port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT) ?? board::error("No port");
        $user = filter_input(INPUT_POST, 'user') ?? board::error("No user");
        $password = filter_input(INPUT_POST, 'password') ?? board::error("No password");
        $name = filter_input(INPUT_POST, 'name') ?? board::error("No name");
        $response = \Ofey\Logan22\component\sphere\server::send(type::UPDATE_LOGINSERVER, [
            'loginId' => $loginId,
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'name' => $name
        ])->show(true)->getResponse();
        var_dump($response);
        exit;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

}