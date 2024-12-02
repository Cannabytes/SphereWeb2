<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\redirect;
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
        $server = \Ofey\Logan22\model\server\server::getServer($id);
        $paySet = $server->getDonateConfig()->getDonateSystems();
        foreach ($paySet as $paySystem) {
            $sortValues[$paySystem->getName()] = $paySystem->getSortValue();
        }
        usort($donateSysName, function ($a, $b) use ($sortValues) {
            $sortA = $sortValues[$a['name']] ?? PHP_INT_MAX;
            $sortB = $sortValues[$b['name']] ?? PHP_INT_MAX;
            return $sortA <=> $sortB;
        });
        foreach ($paySet as &$ps) {
            foreach ($donateSysName as $d) {
                if ($ps->getName() === $d['name']) {
                    $psInputs = $ps->getInputs();
                    $dInputs = $d['inputs'];
                    $missingKeys = array_diff_key($dInputs, $psInputs);
                    foreach($missingKeys AS $n => $v){
                        $ps->addInput($n, $v);
                    }

                }
            }
        }
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
            'only_non_empty_folders' => true,
        ]);
        $key = array_search("monobank", $all_donate_system);
        if ($key !== false) {
            unset($all_donate_system[$key]);
        }
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