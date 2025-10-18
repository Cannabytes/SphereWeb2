<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\github\update;
use Ofey\Logan22\model\server\serverModel;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class index
{

    public static function index()
    {
        validation::user_protection("admin");
        $sphereAPIError = null;

        $info = server::send(type::SERVER_FULL_INFO)->show(false)->getResponse();
        if (isset($info['error']) or $info === null) {
            $sphereAPIError = true;
            $info['servers'] = [];
        }

        if (config::load()->enabled()->isEnableEmulation() == false) {
            if (isset($info['servers'])) {
                $restart = false;
                foreach ($info['servers'] as $server) {
                    $id = $server['id'];
                    \Ofey\Logan22\model\server\server::loadStatusServer($server);
                    $getServer = \Ofey\Logan22\model\server\server::isServer($id, $server);
                    if ($getServer == null) {
                        $name = $server['info']['name'] ?? "NoName #{$id}";
                        $serverNew = new serverModel($server, []);
                        $serverNew->setId($id);
                        $serverNew->setName($name);
                        $serverNew->setRateExp($server['info']['rateExp'] ?? 1);
                        $serverNew->setRateSp($server['info']['rateSp'] ?? 1);
                        $serverNew->setRateAdena($server['info']['rateAdena'] ?? 1);
                        $serverNew->setRateDrop($server['info']['rateDrop'] ?? 1);
                        $serverNew->setChronicle($server['info']['chronicle'] ?? "NoChronicle");
                        $serverNew->setEnabled($server['enabled']);
                        $serverNew->save();
                        $restart = true;
                    } else {
                        $getServer->setDisabled($server['enabled']);
                    }
                    unset($getServer);
                }
                if ($restart) {
                    redirect::location("/admin");
                }
            }
        }

        if (!$sphereAPIError) {
            tpl::addVar([
                "launcher" => $info['launcher'] ?? null,
                "license" => $info['license'] ?? null,
                "licenseActive" => $info['licenseActive'] ?? null,
                "pluginActive" => $info['pluginActive'] ?? null,
                "pluginActiveDate" => $info['pluginActiveDate'] ?? null,
                "balance" => (float)$info['balance'] ?? 0,
                "servers" => $info['servers'],
                "sphere_last_commit" => $info['last_commit'],
                "registrationLimit" => $info['registrationLimit'],
                "forumInfo" => $info['forumInfo'],
                "serverTime" => $info['serverTime'],
                "SSL" => $info['SSL'],
            ]);
        }


        $updateLog = "uploads/update_log.php";
        if (file_exists($updateLog)) {
            $updateLog = require 'uploads/update_log.php';
            $myLang = user::self()->getLang();
            $updateLog = array_map(function ($item) use ($myLang) {
                if (isset($item['message'][$myLang])) {
                    $item['message'] = $item['message'][$myLang];
                } else {
                    $item['message'] = null;
                }
                return $item;
            }, $updateLog);
        } else {
            $updateLog = [];
        }

        tpl::addVar([
            "sphereAPIError" => $sphereAPIError,
            "title" => lang::get_phrase("admin_panel"),
            "self_last_commit" => update::getLastCommit(),
            "getLastDateUpdateCommit" => update::getLastDateUpdateCommit(),
            "getCountCommit" => update::getCountCommit(),
            "updateLog" => $updateLog,
        ]);
        tpl::display("admin/index.html");
    }

    public static function support()
    {
        validation::user_protection("admin");
        tpl::addVar([
            "title" => lang::get_phrase("support"),
        ]);
        tpl::display("admin/support.html");
    }

}