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
                "icon" => fileSys::localdir("/uploads/images/icon/NOIMAGE.webp"),
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
                "icon" => fileSys::localdir("/uploads/images/icon/NOIMAGE.webp"),
            ];
        }
    }

    public static function get_item_info_json(): string|item|array {
        $item_id = $_POST['itemID'] ?? board::error("Не передано значение ID предмета");
        return self::get_item_info($item_id, true);
    }

    public static function get_item_info($item_id = null, $json = false, $protected = true): string|item|array {
        if ($protected) {
            validation::user_protection();
        }
        if ($item_id === null) {
            $item_id = $_POST['itemID'] ?? null;
            if ($item_id === null) {
                board::notice(false, "Не передано значение ID предмета");
            }
        }
        $icon = item::getItem($item_id);
        if(!$icon){
            if ($json) {
                board::alert([
                    "ok" => false,
                    "itemId" => $item_id,
                    "name" => "The item does not exist!",
                    "icon" => fileSys::localdir("/uploads/images/icon/NOIMAGE.webp"),
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
        return file_exists(fileSys::get_dir("/uploads/images/{$object}/" . $fileIcon . ".webp")) && $fileIcon != null ? fileSys::localdir("/uploads/images/{$object}/" . $fileIcon . ".webp") : fileSys::localdir("/uploads/images/icon/NOIMAGE.webp");
    }

    public static function includeFileByRange($number, $object = "items"): string|false {
        if($object == "items"){
            $object = "items/highFive";
            if(server::getServer()){
                $set = server::getServer()->getServerData("knowledge_base");
                if($set){
                    $object = "items/" . $set->getVal()  ;
                }
            }
        }
        $range = floor(($number ) / 100) * 100;
        if ($range < 0) {
            $range = 0;
        }
        $file = "{$range}-" . ($range + 99) . ".php";

        //If custom item is found
        $custom_file = fileSys::get_dir("/custom/{$object}/{$file}");
        if(file_exists($custom_file)){
            return $custom_file;
        }
        $file = fileSys::get_dir("/src/component/image/icon/{$object}/{$file}");
        if (file_exists($file)) {
            return $file;
        } else {
            return false;
        }
    }


}