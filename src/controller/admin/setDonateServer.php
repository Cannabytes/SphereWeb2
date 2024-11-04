<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\template\tpl;

class setDonateServer
{

    static public function show($id = null)
    {
        if ($id==null) {
            redirect::location("/main");
        }

        $donateSysName = self::AllDonateSystem();
        $paySet = config::load()->donate()->getDonateSystems();


        $sortValues = [];
        foreach ($paySet as $paySystem) {
            $sortValues[$paySystem->getName()] = $paySystem->getSortValue();
        }
        usort($donateSysName, function ($a, $b) use ($sortValues) {
            $sortA = $sortValues[$a['name']] ?? PHP_INT_MAX;
            $sortB = $sortValues[$b['name']] ?? PHP_INT_MAX;
            return $sortA <=> $sortB;
        });

        $server = \Ofey\Logan22\model\server\server::getServer($id);

        tpl::addVar([
            'server' => $server,
            "donateSysNames" => $donateSysName,
        ]);
        tpl::display("/admin/setDonateServer.html");
    }

    private static function AllDonateSystem()
    {
        $all_donate_system = fileSys::get_dir_files("src/component/donate", [
            'basename' => true,
            'fetchAll' => true,
        ]);
        $donateSysNames = [];
        foreach ($all_donate_system as $system) {
            if (!$system::isEnable()) {
                continue;
            }
            if (method_exists($system, 'forAdmin')) {
                if ($system::forAdmin() and auth::get_access_level() != 'admin') {
                    continue;
                }
            }
            $inputs = [];
            if (method_exists($system, 'inputs')) {
                $inputs = $system::inputs();
            }
            if (method_exists($system, 'getDescription')) {
                $donateSysNames[] = [
                    'name' => basename($system),
                    'desc' => $system::getDescription(),
                    'inputs' => $inputs,
                ];
            } else {
                $donateSysNames[] = [
                    'name' => basename($system),
                    'desc' => basename($system),
                    'inputs' => $inputs,
                ];
            }
        }
        return $donateSysNames;
    }

}