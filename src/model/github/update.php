<?php

namespace Ofey\Logan22\model\github;

use Ofey\Logan22\model\config\github;

class update
{

    static function update() {
        $github = new github();
        $github->update();
    }

}