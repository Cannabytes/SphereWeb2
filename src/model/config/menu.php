<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\model\db\sql;

class menu
{

    private array $menulist = [];

    public function get(): array
    {
        return $this->menulist;
    }

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_menu__'");
        if ($configData) {
            $setting       = json_decode($configData['setting'], true);
            if(isset($setting['menulist'])){
                $this->menulist = $setting['menulist'];
                foreach ($this->menulist AS &$value){
                    $value['phrase'] = lang::get_phrase($value['phraseId']);
                }
            }
        }
    }
}