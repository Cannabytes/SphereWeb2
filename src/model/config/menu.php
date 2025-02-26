<?php
/** UPDATE **/

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\component\lang\lang;

class menu
{

    private array $menulist = [];
    private bool $neonEffects = false;
    private string $menuStyle = "green";

    public function __construct($setting)
    {
        if (isset($setting['menulist'])) {
            $this->menulist = $setting['menulist'];
            foreach ($this->menulist as &$value) {
                $value['phrase'] = lang::get_phrase($value['phraseId']);
            }
        }
        if (isset($setting['neonEffects'])) {
            $this->neonEffects = filter_var($setting['neonEffects'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($setting['menuStyle'])) {
            $this->menuStyle = $setting['menuStyle'];
        }
    }

    public function get(): array
    {
        return $this->menulist;
    }

    public function isNeonEffects(): bool
    {
        return $this->neonEffects;
    }

    public function getMenuStyle(): string
    {
        return $this->menuStyle;
    }
}