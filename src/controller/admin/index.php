<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\github\update;
use Ofey\Logan22\model\server\serverModel;
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
        if(isset($info['servers'])){
            $restart = false;
            foreach ($info['servers'] as $server) {
                $id = $server['id'];
                \Ofey\Logan22\model\server\server::loadStatusServer($server);
                $getServer = \Ofey\Logan22\model\server\server::isServer($id, $server);
                if ($getServer == null) {
                    $serverNew = new serverModel($server, []);
                    $serverNew->setId($id);
                    $serverNew->setName("NoName #{$id}");
                    $serverNew->setEnabled($server['enabled']);
                    $serverNew->save();
                    $restart = true;
                }else{
                    $getServer->setDisabled($server['enabled']);
                }
                unset($getServer);
            }
            if($restart){
                redirect::location("/admin");
            }
        }
        if (!$sphereAPIError) {
            tpl::addVar([
                "launcher" => $info['launcher'] ?? null,
                "license" => $info['license'] ?? null,
                "licenseActive" => $info['licenseActive'] ?? null,
                "roulette" => $info['roulette'] ?? null,
                "rouletteActive" => $info['rouletteActive'] ?? false,
                "balance" => (float)$info['balance'] ?? 0,
                "servers" => $info['servers'],
                "sphere_last_commit" => $info['last_commit'],
                "registrationLimit" => $info['registrationLimit'],
            ]);
        }

        tpl::addVar([
            "sphereAPIError" => $sphereAPIError,
            "title" => lang::get_phrase("admin_panel"),
            "self_last_commit" => update::getLastCommit(),
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