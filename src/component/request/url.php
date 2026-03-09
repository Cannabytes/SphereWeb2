<?php

namespace Ofey\Logan22\component\request;

class url {

    /**
     * Возвращает HTTPS для всех внешних URL сайта.
     */
    public static function scheme(): string
    {
        return 'https';
    }

    //Адрес сайта
    public static function host($addToHost = null): string {
        $scheme = self::scheme();
        $scheme = rtrim($scheme, '/:') . '://';

        $hostName = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $hostName = preg_replace('/:(80|443)$/', '', (string)$hostName);
        $host = $scheme . $hostName;
        if ($addToHost) {
            $addToHost = str_replace("\\", "/", $addToHost);
            $addToHost = ltrim($addToHost, '/');
            return rtrim($host, '/') . '/' . $addToHost;
        }
        return $host;
    }


}