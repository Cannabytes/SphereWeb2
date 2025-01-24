<?php

namespace Ofey\Logan22\component\links;

class http
{

    static public function getHost($fullUrl = false): string
    {
        // Если не передан параметр, то возвращаем только имя хоста

        // Если передан параметр, то возвращаем полный URL
        $scheme = ( ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";

        // Получаем имя хоста
        $host = $_SERVER['HTTP_HOST'];

        // Получаем путь страницы
        $requestUri = $_SERVER['REQUEST_URI'];

        if ($fullUrl) {
            $currentUrl = $scheme . "://" . $host . $requestUri;
        } else {
            $currentUrl = $scheme . "://" . $host;
        }

        // Собираем полный URL

        return $currentUrl;
    }

}