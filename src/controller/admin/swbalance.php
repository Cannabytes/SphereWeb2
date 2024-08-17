<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\template\tpl;

class swbalance
{

    static public function get()
    {
        $sphereAPIError = null;

        $info = server::send(type::SERVER_FULL_INFO)->show(false)->getResponse();
        if(isset($info['error']) OR $info===null){
            $sphereAPIError = true;
            $info['servers'] = [];
        }

        if(!$sphereAPIError){
            tpl::addVar([
              "services" => $info['services'] ?? null,
              "launcher" => $info['launcher'] ?? null,
              "balance" => (float)$info['balance'] ?? 0,
              "servers" => $info['servers'],
              "sphere_last_commit" => $info['last_commit'],
            ]);
        }

        tpl::display("/admin/balance.html");
    }


    public static function saveService(): void
    {
        $stream = filter_var($_POST['serviceStream'], FILTER_VALIDATE_BOOL);
        $roulette = filter_var($_POST['serviceRoulette'], FILTER_VALIDATE_BOOL);
        $data = server::send(type::SAVE_SERVICE, [
          'stream' => $stream,
          'roulette' => $roulette,
        ])->show()->getResponse();
        if(isset($data['success'])){
            if($data['success']){
                board::success('Сохранено');
            }else{
                board::error('Случилась непредвиденная проблема');
            }
        }
    }


}