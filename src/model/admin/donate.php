<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 15.09.2022 / 22:29:39
 */

namespace Ofey\Logan22\model\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\template\async;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class donate {

    /**
     * Добавление новых предметов в магазин
     */
    static function add_item() {
        $items = $_POST['items'] ?? null;
        if(!$items){
            return ;
        }
        foreach($items AS &$item){
            $count = $item['count'];
            if($count<=0){
                board::notice(false, "Не может быть меньше чем 0");
            }
            $item['objectId'] = hrtime(true) . mt_rand(1000, 9999);
            if(!item::getItem($item['itemId'])){
                board::notice(false, "ID Предмета не найдено");
            }
        }
        sql::run("INSERT INTO `shop_items` (`serverId`, `items`) VALUES (?, ?)",[
            user::self()->getServerId(),
            json_encode($items),
        ]);
        $lastId = sql::lastInsertId();
        if(!$lastId){
            board::notice(false, "Не удалось добавить предмет в магазин");
        }
        $arr = [
            "success" => true,
            "ok" => true,
            "id" => sql::lastInsertId(),
            "items" => \Ofey\Logan22\model\donate\donate::getShopItems($lastId),
        ];
        board::alert($arr);
    }

    /**
     * Удаление предмета из доната меню
     * @param $id
     */
    public static function remove_item() {
         $shopId = $_POST['shopId'] ?? board::error("No POST shopId");
         $objectId = $_POST['objectId']?? board::error("No POST objectId");
         $object = sql::getRow("SELECT * FROM `shop_items` WHERE id= ?", [
             $shopId,
         ]);
         if($object){
             $items = json_decode($object['items'], true);
             foreach ($items as $key => $item) {
                 if ($item['objectId'] == $objectId) {
                     unset($items[$key]);
                     break;
                 }
             }
             if(empty($items)){
                sql::run("DELETE FROM `sphere`.`shop_items` WHERE `id` = ?", [$shopId]);
             }else{
                 $newItemsJson = json_encode(array_values($items));
                 sql::run("UPDATE `shop_items` SET `items` = ? WHERE id = ?", [
                     $newItemsJson,
                     $shopId,
                 ]);
             }
             board::alert([ "ok" => true]);
         }
    }

}