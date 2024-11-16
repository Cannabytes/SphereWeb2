<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\github\update;
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
            foreach ($info['servers'] as $server) {
                $id = $server['id'];
                \Ofey\Logan22\model\server\server::loadStatusServer($server);
                $getServer = \Ofey\Logan22\model\server\server::getServer($id, $server);
                if ($getServer == null) {
                    $data = [
                        "id" => $id,
                        "name" => "NoName",
                        "rateExp" => 1,
                        "rateSp" => 1,
                        "rateAdena" => 1,
                        "rateDrop" => 1,
                        "rateSpoil" => 1,
                        "chronicle" => "NoSetChronicle",
                        "source" => "",
                        "disabled" => $server['disabled'],
                        "request_count" => $server['request_count'],
                        "count_errors" => $server['count_errors'],
                        "enabled" => $server['enabled'],
                    ];
                    sql::run("INSERT INTO `servers` (`id`, `data`) VALUES (?, ?)", [$id, json_encode($data)]);
                }else{
                    $getServer->setDisabled($server['enabled']);
                }
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