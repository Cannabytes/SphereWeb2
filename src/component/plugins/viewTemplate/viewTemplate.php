<?php

namespace Ofey\Logan22\component\plugins\viewTemplate;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class viewTemplate
{

    public function show($template)
    {
        validation::user_protection("admin");
        \Ofey\Logan22\controller\config\config::load()->template()->setName($template);
        tpl::displayDemo("index.html");
    }

}