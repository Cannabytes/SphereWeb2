<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class sphere_forum
{
    private ?string $nameClass = null;

    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }

        return $this->nameClass;
    }

    public function __construct()
    {
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
            'pluginActive' =>(bool)plugin::getPluginActive($this->getNameClass()) ?? false,
        ]);
    }

    public function index(): void
    {
        validation::user_protection("admin");
        tpl::displayPlugin("sphere_forum/tpl/admin/index.html");
    }

}