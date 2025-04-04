<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 20.09.2022 / 11:01:02
 */

namespace Ofey\Logan22\component\image;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\item\item;
use Ofey\Logan22\model\server\server;
use Ofey\Logan22\model\user\auth\auth;
use Ofey\Logan22\model\user\user;

class client_icon {
    private static array $arrItems = [];

    /**
     * Возращает true если предмет стыкуемый
     * @return bool
     * @throws Exception
     */
    public static function is_stack($item_id): bool {
        $type = self::get_item_info($item_id);
        if ($type->getIsStackable()) {
            return true;
        }
        return false;
    }

    public static function get_skill_info($skill_id = 0, $protected = true) {
        if ($protected) {
            validation::user_protection();
        }
        if ($skill_id === 0) {
            board::notice(false, "Не передано значение ID предмета");
        }
        $file = self::includeFileByRange($skill_id, "skills");
        if (!$file) {
            return [
                "skill_id" => $skill_id,
                "name" => "NoSkillName",
                "icon" => ("/uploads/images/icon/NOIMAGE.webp"),
            ];
        }
        $itemArr = require $file;
        if (isset($itemArr[$skill_id])) {
            $item = $itemArr[$skill_id];
            $item['icon'] = self::icon($item['icon'], "skills");
            self::$arrItems = $itemArr;
            return $item;
        } else {
            return [
                "skill_id" => $skill_id,
                "name" => "NoSkillName",
                "icon" => ("/uploads/images/icon/NOIMAGE.webp"),
            ];
        }
    }

    public static function get_item_info_json(): string|item|array {
        $item_id = $_POST['itemID'] ?? board::error("Не передано значение ID предмета");
        return self::get_item_info($item_id, true);
    }

    public static function get_item_info($item_id = null, $json = false, $protected = true, $chronicle = null): string|item|array {
        if ($protected) {
            validation::user_protection();
        }
        if ($item_id === null) {
            $item_id = $_POST['itemID'] ?? null;
            if ($item_id === null) {
                board::notice(false, "Не передано значение ID предмета");
            }
        }
        if ($chronicle === null) {
            $chronicle = server::getServer(user::self()->getServerId())->getKnowledgeBase();
        }
        $icon = item::getItem($item_id, $chronicle);

        if(!$icon){
            if ($json) {
                board::alert([
                    "ok" => false,
                    "itemId" => $item_id,
                    "name" => "The item does not exist!",
                    "icon" => ("/uploads/images/icon/NOIMAGE.webp"),
                ]);
            } else {
                return false;
            }
        }
        if ($json) {
            board::alert(["ok" => true, "item" => $icon->toArray()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $icon;
    }

    public static function icon($fileIcon = null, $object = "icon") {
        if ($fileIcon != null && pathinfo($fileIcon, PATHINFO_EXTENSION) === 'webp') {
            $fileIcon = pathinfo($fileIcon, PATHINFO_FILENAME);
        }
        return file_exists(fileSys::get_dir("/uploads/images/{$object}/" . $fileIcon . ".webp")) && $fileIcon != null ? ("/uploads/images/{$object}/" . $fileIcon . ".webp") : ("/uploads/images/icon/NOIMAGE.webp");
    }

    public static function includeFileByRange($itemId, $object = "items", $dbVersion = null): string|false {
        if($object == "items"){
            $object = "items/highFive";
            if($dbVersion==null){
                if(server::getServer()){
                    $itemdb = server::getServer()->getKnowledgeBase();
                    if($itemdb){
                        $object = "items/" . $itemdb;
                    }
                }
            }else{
                $object = "items/" . $dbVersion;
            }
        }
        $range = floor(($itemId ) / 100) * 100;
        if ($range < 0) {
            $range = 0;
        }

        //If custom item is found
        $custom_file = fileSys::get_dir("/custom/{$object}/{$itemId}.php");
        if(file_exists($custom_file)){
            return $custom_file;
        }

        $file = "{$range}-" . ($range + 99) . ".php";
        $file = fileSys::get_dir("/src/component/image/icon/{$object}/{$file}");
        if (file_exists($file)) {
            return $file;
        } else {
            return false;
        }
    }


}