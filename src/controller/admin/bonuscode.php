<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\bonus\bonus;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class bonuscode {

    public static function create_pack(){
        validation::user_protection("admin");
        tpl::display('admin/create_bonuscode.html');
    }

    public static function show() {
        validation::user_protection("admin");
        tpl::display('admin/bonuscode.html');
    }

    public static function genereate(){
        validation::user_protection("admin");
        bonus::genereateCode();
    }


    public static function show_code(){
        validation::user_protection("admin");
        $sql = "SELECT 
        code,
        item_id,
        count,
        start_date_code,
        end_date_code,
        server_id 
    FROM
        bonus_code 
        WHERE server_id = ?";
        $codeTable = sql::getRows($sql, [user::self()->getServerId()]);

        if (!is_array($codeTable)) {
            $codeTable = [];
            error_log("Error: codeTable is not an array in bonuscode.php");
        }

        $sortedCodeTable = [];
        foreach($codeTable as $item) {
            if (!is_array($item) || !isset($item['code'])) {
                continue;
            }
            $code = $item['code'];
            if (!isset($sortedCodeTable[$code])) {
                $sortedCodeTable[$code] = [];
            }
            $sortedCodeTable[$code][] = $item;
        }

        if (!empty($sortedCodeTable)) {
            foreach($sortedCodeTable as $code => &$items) {
                foreach($items as &$item) {
                    if (isset($item['item_id'])) {
                        $data = client_icon::get_item_info($item['item_id']);
                        $item['info'] = $data;
                    }
                }
            }
        }
        tpl::addVar('codeTable', $sortedCodeTable);
        tpl::display("/admin/bonuscode_list.html");
    }


}