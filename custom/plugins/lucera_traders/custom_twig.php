<?php

namespace Ofey\Logan22\custom\plugins\lucera_traders;

use lucera_traders\lucera_traders;

class custom_twig
{

    static public function getSelllist(){
        return ( new lucera_traders() )->getSellList();
    }

}