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
        $data = json_decode(file_get_contents("php://input"), true);
        if(!isset($data['loginId'])){
            board::success("Не передан ID логина");
        }
        $filePath = "uploads/import_accounts_" . time() . ".json";
        \Ofey\Logan22\component\sphere\server::setShowError(true);
        $data = \Ofey\Logan22\component\sphere\server::sendFile($filePath, type::IMPORT_ACCOUNTS, [
            "loginId" => (int)$data['loginId'],
        ]);
        // Проверка, успешен ли импорт
        if (isset($data->response)) {
            if (!file_exists($filePath)) {
                http_response_code(404);
                board::alert([
                    'type'    => 'notice',
                    'ok'      => false,
                    'message' => 'Файл не найден.',
                ]);
            }
            echo json_encode(['file' => $filePath]);
            exit;
        } else {
            // Если произошла ошибка, возвращаем JSON с сообщением об ошибке
            http_response_code(500);
            board::alert([
                'type'    => 'notice',
                'ok'      => false,
                'message' => 'Произошла ошибка при импорте аккаунтов.',
            ]);
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