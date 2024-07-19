<?php

namespace launcher;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;
use trash\sdb;

class launcher
{

    public function show($launcher_name = null)
    {
        $serverInfo = server::getServer(user::self()->getServerId());
        tpl::addVar("id", $serverInfo->getId());
        tpl::addVar("chronicle", $serverInfo->getChronicle());
        tpl::displayPlugin("/launcher/tpl/show.html");
    }

    public function admin()
    {
        validation::user_protection("admin");
        tpl::addVar('userLang', user::self()->getLang());
        $setting = include_once __DIR__ . "/settings.php";
        tpl::addVar('PLUGIN', $setting);

        tpl::displayPlugin("/launcher/tpl/patch_create.html");
    }

    public function add()
    {
        validation::user_protection("admin");
        $setting = include_once __DIR__ . "/settings.php";
        tpl::addVar('PLUGIN', $setting);
        tpl::displayPlugin("/launcher/tpl/add.html");
    }

    public function saveConfig()
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

    private function l2application(&$data)
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

    public function desc()
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

    public function edit($id)
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

    public function editSave()
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

        if ($server = server::get_server_info($server_id)) {
            $data['chronicle'] = $server['chronicle'];
        }

        if (empty($data['application'])) {
            board::error("Не указан путь к запуску игры. Пример: /system/l2.exe");
        }

        $this->l2application($data);

        $_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        sql::run("UPDATE `server_data` SET `val` = ? WHERE `id` = ?", [$_json, $id]);

        board::alert([
          'type'     => 'notice',
          'ok'       => true,
          'message'  => "Success",
          'redirect' => '/launcher',
        ]);
    }

    public function setServerDefault()
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

    public function removeLauncher()
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


}