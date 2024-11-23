<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\lang\lang;

class menu
{

    private array $menulist = [];

    public function __construct($setting)
    {
        if (isset($setting['menulist'])) {
            $this->menulist = $setting['menulist'];
            foreach ($this->menulist as &$value) {
                $value['phrase'] = lang::get_phrase($value['phraseId']);
            }
        }
    }

    public function get(): array
    {
        return $this->menulist;
    }
}