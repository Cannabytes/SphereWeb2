<?php

namespace Ofey\Logan22\component\links;

use Ofey\Logan22\component\request\url;

class http
{

    static public function getHost($fullUrl = false): string
    {
        if ($fullUrl) {
            return url::host($_SERVER['REQUEST_URI'] ?? '');
        }

        return url::host();
    }

}