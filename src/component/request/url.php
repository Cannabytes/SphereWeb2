<?php

namespace Ofey\Logan22\component\request;

class url {

    /**
     * Определяет, является ли текущее соединение защищенным (HTTPS)
     *
     * Функция проверяет все возможные заголовки и параметры сервера,
     * которые могут указывать на HTTP соединение, включая работу за прокси.
     * По умолчанию возвращает 'https' для повышенной безопасности.
     *
     * @return string 'https' или 'http' в зависимости от типа соединения
     */
    public static function scheme(): string
    {
        // Значение по умолчанию - теперь используем https
        $scheme = 'https';

        // Массив проверок для определения HTTP (обратная логика)
        $httpChecks = [
            // Проверка SERVER_PROTOCOL
            isset($_SERVER['SERVER_PROTOCOL']) &&
            strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, 5)) === 'https',

            // Проверка порта
            isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443,

            // Явная проверка флага HTTPS
            isset($_SERVER['HTTPS']) &&
            (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] === '1'),

            // Проверка заголовка X-Forwarded-Proto от прокси
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https',

            // Проверка заголовка X-Forwarded-SSL от прокси
            isset($_SERVER['HTTP_X_FORWARDED_SSL']) &&
            strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on',

            // Проверка заголовка X-Forwarded-Scheme
            isset($_SERVER['HTTP_X_FORWARDED_SCHEME']) &&
            strtolower($_SERVER['HTTP_X_FORWARDED_SCHEME']) === 'https',

            // Проверка Front-End-Https от Microsoft ISA/IIS
            isset($_SERVER['HTTP_FRONT_END_HTTPS']) &&
            strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on',

            // Проверка X-ARR-SSL от Azure
            isset($_SERVER['HTTP_X_ARR_SSL']),

            // Проверка CF-Visitor от CloudFlare
            isset($_SERVER['HTTP_CF_VISITOR']) &&
            strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false,

            // Проверка HTTP_X_FORWARDED_PORT
            isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443
        ];

        // Поскольку HTTPS теперь по умолчанию,
        // проверяем наличие явных признаков того, что соединение НЕ является HTTPS
        $forceHttp = false;

        // Явные признаки HTTP соединения
        if (
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 80) &&
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'off') &&
            (!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'http')
        ) {
            $forceHttp = true;
        }

        // Если обнаружены явные признаки HTTP, меняем на http
        if ($forceHttp) {
            $scheme = 'http';
        }

        return $scheme;
    }

    //Адрес сайта
    public static function host($addToHost = null): string {
        $scheme = self::scheme();
        $scheme = rtrim($scheme, '/:') . '://';

        $host = $scheme . $_SERVER['SERVER_NAME'];
        if ($addToHost) {
            $addToHost = str_replace("\\", "/", $addToHost);
            $addToHost = ltrim($addToHost, '/');
            return rtrim($host, '/') . '/' . $addToHost;
        }
        return $host;
    }


}