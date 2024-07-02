<?php

namespace Ofey\Logan22\model\config;

use Ofey\Logan22\model\db\sql;

class logo
{

    private string $logo = '/src/template/sphere/assets/images/default.webp';

    private array $favicon = [16=>'/src/template/sphere/assets/images/favicon.ico'];

    public function __construct()
    {
        $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_logo__'");
        if ($configData) {
            $setting       = json_decode($configData['setting'], true);
            $this->favicon = $setting['favicon'] ?? $this->favicon;
            $this->logo    = $setting['logo'] ?? $this->logo;
        }
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function getFavicon($size = null): string
    {
        if ($size == null) {
            return $this->favicon;
        }
        $path_parts = pathinfo($this->favicon);

        return $path_parts['dirname'] . '/' . $path_parts['filename'] . $size . '.' . $path_parts['extension'];
    }

    public function favicon(){
        $icoList = '';
        foreach (array_reverse($this->favicon, true) as $size => $favicon) {
            $icoList .= "<link rel='icon' href='{$favicon}' type='image/x-icon' sizes='{$size}x{$size}'>\n";
        }
        echo $icoList;

    }

}