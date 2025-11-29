<?php

namespace Ofey\Logan22\controller\sphereapi;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class sphereapi
{
    static function index()
    {
        tpl::display("/admin/sphereapi.html");
    }

    static function check(): void
    {
        config::load()->sphereApi()->setIp($_POST['ip']);
        config::load()->sphereApi()->setPort($_POST['port']);
        $lastCommit = server::send(type::GET_COMMIT_LAST)->show(false)->getResponse();
        if(isset($lastCommit['last_commit'])){
            board::success(lang::get_phrase('Connection check successful'));
        }
        board::error(lang::get_phrase('Connection check failed'));
    }

    static function save(): void
    {
        $ip = $_POST['ip'] ?? '';
        $port = $_POST['port'] ?? '';
        if (empty($ip)) {
            board::error(lang::get_phrase('IP address not specified'));
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            board::error(lang::get_phrase('Invalid IP address format'));
        }
        if (!is_numeric($port) || $port < 1 || $port > 65535) {
            board::error(lang::get_phrase('incorrect_port'));
        }
        $data = json_encode([
            "ip" => $ip,
            "port" => $port,
        ]);
        sql::run("DELETE FROM `settings` WHERE `key` = '__config_sphere_api__'");
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_sphere_api__', ?, ?, ?)", [
          $data,
          0,
          time::mysql(),
        ]);
        board::success(lang::get_phrase(217));
    }

    static function changeDomain(): void
    {
        $newDomain = $_POST['domain'] ?? '';
        if (empty($newDomain)) {
            board::error(lang::get_phrase('domain_not_specified'));
        }

        // Генерируем рандомный токен 5-10 символов
        $tokenLength = rand(5, 10);
        $verificationToken = substr(bin2hex(random_bytes(10)), 0, $tokenLength);
        
        // Создаём файл в корне проекта
        $filePath = fileSys::localdir('/changeDomain.txt', true);
        if (file_put_contents($filePath, $verificationToken) === false) {
            board::error(lang::get_phrase('failed_to_create_verification_file'));
        }

        $response = server::send(type::DOMAIN_CHANGE, [
            'domain' => $newDomain,
            'token' => $verificationToken
        ])->show(false)->getResponse();
        
        // Удаляем файл после проверки
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        if (isset($response['error'])) {
            board::error($response['error']);
        }
        
        if (isset($response['success']) && $response['success'] === true) {
            $oldDomain = $response['old_domain'] ?? '';
            $newDomain = $response['new_domain'] ?? '';
            board::success(lang::get_phrase('domain_changed_success_detailed', $oldDomain, $newDomain));
        }
        
        board::notice($response['message'] ?? lang::get_phrase('domain_change_request_sent'));
    }
}