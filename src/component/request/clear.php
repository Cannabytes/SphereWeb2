<?php

namespace Ofey\Logan22\component\request;

class clear
{
    static public function cleanSQLQuery(string $query): string {
        $query = preg_replace('/\s+/', ' ', $query);
        $query = preg_replace('/\s*([(),])\s*/', '$1', $query);
        return trim($query);
    }

}