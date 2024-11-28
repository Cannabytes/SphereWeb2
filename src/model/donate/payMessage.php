<?php

namespace Ofey\Logan22\model\donate;

use Ofey\Logan22\component\lang\lang;

class payMessage
{
    static function getRandomPhrase(): string
    {
        $phraseID = range(582, 680);
        $randomIndex = array_rand($phraseID);
        return lang::get_phrase($phraseID[$randomIndex]);
    }
}