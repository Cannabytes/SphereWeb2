<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class databases
{

    static public function show()
    {
        $servers = \Ofey\Logan22\model\server\server::getServerAll();
        $database = \Ofey\Logan22\component\sphere\server::send(type::GET_DATABASE_LIST)->show()->getResponse();

        $defaultDB =  $database['defaultDB'];
        $gameServers = $database['gameservers'];
        $loginServers = $database['loginservers'];


        foreach($defaultDB AS $db){
            foreach($servers AS &$server){
                if ($db['id'] == $server->getId()){
                    foreach($loginServers AS &$loginServer){
                        if ($loginServer['id'] == $db['loginServerID']){
                            $loginServer['default'] = true;
                        }
                    }
                    foreach($gameServers AS &$gameserver){
                        if ($gameserver['id'] == $db['gameServerID']){
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

    public static function importAccounts()
    {
        $filePath = "uploads/import_accounts_" . time() . ".json";
        $data = \Ofey\Logan22\component\sphere\server::sendFile($filePath, type::IMPORT_ACCOUNTS);

        // Проверка, успешен ли импорт
        if (isset($data->response)) {
            if (!file_exists($filePath)) {
                // Если файл не существует, вернем ошибку
                http_response_code(404);
                echo json_encode(['error' => 'Файл не найден.']);
                exit;
            }

            // Возвращаем имя файла в JSON-ответе для клиента
            echo json_encode(['file' => $filePath]);
            exit;
        } else {
            // Если произошла ошибка, возвращаем JSON с сообщением об ошибке
            http_response_code(500);
            echo json_encode(['error' => 'Произошла ошибка при импорте аккаунтов.']);
            exit;
        }
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





}