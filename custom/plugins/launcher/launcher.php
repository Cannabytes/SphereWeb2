<?php

namespace launcher;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\page\page;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class launcher
{
    public function __construct() {
        if (\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()){
            error::error404('С включенным режимом эмуляции нельзя пользоваться лаунчером');
        }
    }

    public function admin(): void
    {
        validation::user_protection("admin");
        tpl::addVar('userLang', user::self()->getLang());
        $setting = include_once __DIR__ . "/settings.php";
        tpl::addVar('PLUGIN', $setting);

        tpl::displayPlugin("/launcher/tpl/patch_create.html");
    }

    public function add(): void
    {
        validation::user_protection("admin");
        $setting = include_once __DIR__ . "/settings.php";
        tpl::addVar('PLUGIN', $setting);
        tpl::displayPlugin("/launcher/tpl/add.html");
    }

    public function saveConfig(): void
    {
        validation::user_protection("admin");

        $server_id = $_POST['server'] ?? board::error("Не выбран сервер");
        $data      = $_POST;
        if (isset($_POST['autoload'])) {
            if ($_POST['autoload'] == 'on') {
                $data['autoload'] = true;
            } else {
                $data['autoload'] = false;
            }
        } else {
            $data['autoload'] = false;
        }

        if ($server = server::getServer($server_id)) {
            $data['chronicle'] = $server->getChronicle();
        }

        if (empty($data['application'])) {
            board::error("Не указан путь к запуску игры. Пример: /system/l2.exe");
        }

        $this->l2application($data);

        $_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        sql::run("INSERT INTO `server_data` (`key`, `val`, `server_id`) VALUES (?, ?, ?)", ['sphere-launcher', $_json, $server_id]);
        $lastId = sql::lastInsertId();
        if ( ! sql::getRows("SELECT 1 FROM `server_data` WHERE `key` = ?;", ["sphere-launcher-default-server_{$server_id}"])) {
            sql::run(
              "INSERT INTO `server_data` (`key`, `val`, `server_id`) VALUES (?, ?, ?);",
              ["sphere-launcher-default-server_{$server_id}", $lastId, $server_id]
            );
        }

        board::alert([
          'type'     => 'notice',
          'ok'       => true,
          'message'  => "Success",
          'redirect' => '/launcher',
        ]);
    }

    private function l2application(&$data): void
    {
        $countL2exe = count($data['application']['l2exe']);
        $arr        = [];
        for ($i = 0; $i < $countL2exe; ++$i) {
            if (empty($data['application']['l2exe'][$i])) {
                continue;
            }
            $arr[] = [
              'l2exe'           => $data['application']['l2exe'][$i],
              'args'            => $data['application']['args'][$i],
              'background'      => $data['application']['background'][$i],
              'button_start_ru' => $data['application']['button_start_ru'][$i],
              'button_start_en' => $data['application']['button_start_en'][$i],
            ];
        }
        $data['application'] = $arr;
    }

    public function desc(): void
    {
        validation::user_protection("admin");
        $launchers = sql::getRows("SELECT * FROM `server_data` WHERE `key` = ?", ['sphere-launcher']);
        foreach ($launchers as &$data) {
            $data['data']              = json_decode($data['val'], true);
            $data['data']['isDefault'] = false;
            $serverId                  = $data['data']['server'];
            $getDefault                = sql::getRow(
              "SELECT * FROM `server_data` WHERE `key` = ?;",
              ["sphere-launcher-default-server_{$serverId}"]
            );
            if ($getDefault) {
                if ($getDefault['val'] == $data['id']) {
                    $data['data']['isDefault'] = true;
                }
            }
            unset($data['val']);
        }
        unset($data);
        $sortedLaunchers = [];
        foreach ($launchers as $data) {
            $serverId                     = $data['server_id'];
            $sortedLaunchers[$serverId][] = $data;
        }
        tpl::addVar('launchers', $sortedLaunchers);
        tpl::displayPlugin("/launcher/tpl/desc.html");
    }

    public function edit($id): void
    {
        validation::user_protection("admin");
        $launcherInfo = sql::getRow("SELECT * FROM `server_data` WHERE id = ?", [$id]);
        if ( ! $launcherInfo or $launcherInfo['key'] != 'sphere-launcher') {
            redirect::location("/admin/launcher");
        }
        $launcherInfo['data'] = json_decode($launcherInfo['val'], true);
        unset($launcherInfo['val']);
        tpl::addVar("launcherInfo", $launcherInfo);
        tpl::displayPlugin("/launcher/tpl/edit.html");
    }

    public function editSave(): void
    {
        validation::user_protection("admin");
        $data      = $_POST;
        $id        = $_POST['element_id'];
        $server_id = $data['server'] ?? board::error("Не выбран сервер");
        if (isset($_POST['autoload'])) {
            if ($_POST['autoload'] == 'on') {
                $data['autoload'] = true;
            } else {
                $data['autoload'] = false;
            }
        } else {
            $data['autoload'] = false;
        }

        if ($server = server::getServer($server_id)) {
            $data['chronicle'] = $server->getChronicle();
        }

        if (empty($data['application'])) {
            board::error("Не указан путь к запуску игры. Пример: /system/l2.exe");
        }

        $this->l2application($data);

        $notice = $_POST['notice'] ?? null;
        if ($notice) {
            $data['notice'] = $notice;
        }

        $_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        sql::run("UPDATE `server_data` SET `val` = ? WHERE `id` = ?", [$_json, $id]);

        board::alert([
          'type'     => 'notice',
          'ok'       => true,
          'message'  => "Success",
          'redirect' => '/launcher',
        ]);
    }

    public function setServerDefault(): void
    {
        validation::user_protection("admin");
        $serverId  = $_POST['serverId'] ?? board::error("ID server is not set");
        $elementId = $_POST['elementId'] ?? board::error("ID element is not set");
        $data      = sql::getRow("SELECT * FROM `server_data` WHERE `key` = ?;", ["sphere-launcher-default-server_{$serverId}"]);
        if ( ! $data) {
            sql::run(
              "INSERT INTO `server_data` (`key`, `val`, `server_id`) VALUES (?, ?, ?);",
              ["sphere-launcher-default-server_{$serverId}", $elementId, $serverId]
            );
        } else {
            sql::run("UPDATE `server_data` SET `val` = ? WHERE `key` = ?;", [$elementId, "sphere-launcher-default-server_{$serverId}"]);
        }
        board::success("Установлен лаунчер по умолчанию");
    }

    public function removeLauncher(): void
    {
        validation::user_protection("admin");
        $id        = $_POST['remove'];
        $server_id = $_POST['server_id'];
        sql::run("DELETE FROM `server_data` WHERE `id` = ?;", [$id]);
        sql::run(
          "DELETE FROM `server_data` WHERE `server_id` = ? AND `val` = ? AND `key` = ?;",
          [$server_id, $id, "sphere-launcher-default-server_{$server_id}"]
        );
        board::alert([
          'type'     => 'notice',
          'ok'       => true,
          'message'  => "Удалено",
          'redirect' => '/admin/launcher',
        ]);
    }

    public function token(): void
    {
        validation::user_protection("admin");
        tpl::displayPlugin("/launcher/tpl/token.html");
    }

    public function tokenCreate(): void
    {
        validation::user_protection("admin");
        $list   = $_POST['list'] ?? board::error("No csv file");
        $storage = $_POST['storage'] ?? board::error("No URL to patch");

        if(server::get_count_servers() == 0){
            board::error("У Вас должен быть хоть один сервер");
        }

        $launcher = \Ofey\Logan22\component\sphere\server::send(type::LAUNCHER_CREATE_TOKEN, [
          'list'   => $list,
          'storage' => $storage,
        ])->show()->getResponse();
        if(isset($launcher['token'])){
            echo json_encode($launcher, JSON_UNESCAPED_SLASHES);
        }
    }

    public function show($launcher_name = null): void
    {
        $launcher = null;
        if ($launcher_name == null) {
            $serverInfo = server::getServer(user::self()->getServerId());
            $launcher   = $serverInfo->getServerData("sphere-launcher")?->getVal();
            if ($launcher == null) {
                redirect::location("/main");
            }
            $launcher = json_decode($launcher, true);
        } else {
            foreach (server::getServerAll() as $server) {
                $launcherData = $server->getServerData("sphere-launcher")?->getVal();
                if ($launcherData == null) {
                    continue;
                }
                $launcherData = json_decode($launcherData, true);
                if ($launcherData['name'] == $launcher_name) {
                    $launcher = $launcherData;
                    break;
                }
            }
        }
        if($launcher == null){
            redirect::location("/main");
        }
        tpl::addVar('launcher', $launcher);
        tpl::addVar('application', json_encode($launcher['application'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) );
        tpl::displayPlugin("/launcher/tpl/show.html");
    }

}