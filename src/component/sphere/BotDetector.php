<?php

namespace Ofey\Logan22\component\sphere;

class BotDetector {

    private mixed $userAgent;
    private mixed $ip;
    private ?bool $isBot = null;

    public function __construct() {
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->isBot = null; // Изначально неизвестно
    }

    public function isBot(): ?bool
    {
        if ($this->isBot !== null) {
            return $this->isBot;
        }

        // Проверка User-Agent
        if ($this->checkUserAgent()) {
            $this->isBot = true;
            return true;
        }

        // Проверка на подозрительный User-Agent
        if ($this->isSuspiciousUserAgent()) {
            $this->isBot = true;
            return true;
        }

        // Проверка IP-адреса
        if ($this->checkBotIP()) {
            $this->isBot = true;
            return true;
        }

        // Проверка на выполнение JavaScript
        if (!$this->checkJavaScript()) {
            $this->isBot = true;
            return true;
        }

        // Проверка на ограничение скорости запросов
        if ($this->isRateLimited()) {
            $this->isBot = true;
            return true;
        }

        // Проверка на наличие реферера
        if ($this->isMissingReferer()) {
            $this->isBot = true;
            return true;
        }

        // Проверка на наличие необходимых заголовков
        if ($this->missingHeaders()) {
            $this->isBot = true;
            return true;
        }

        // Дополнительные проверки поведения могут быть добавлены здесь

        $this->isBot = false;
        return false;
    }

    private function checkUserAgent(): bool
    {
        $knownBots = array(
          'Googlebot',
          'Bingbot',
          'Slurp',
          'DuckDuckBot',
          'Baiduspider',
          'YandexBot',
          'Sogou',
          'Exabot',
          'facebot',
          'ia_archiver',
            // Добавьте дополнительные известные боты
        );

        foreach ($knownBots as $bot) {
            if (stripos($this->userAgent, $bot) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isSuspiciousUserAgent(): bool
    {
        return empty($this->userAgent) || strlen($this->userAgent) < 10;
    }

    private function checkBotIP(): bool
    {
        $hostname = gethostbyaddr($this->ip);

        if ($hostname === false || $hostname === $this->ip) {
            return false;
        }

        $botDomains = array(
          'googlebot.com',
          'crawl.yahoo.net',
          'msn.com',
          'yandex.ru',
          'bing.com',
          'facebook.com',
            // Добавьте дополнительные домены
        );

        foreach ($botDomains as $domain) {
            if (str_ends_with($hostname, $domain)) {
                // Дополнительная проверка IP через прямое DNS разрешение
                $resolvedIp = gethostbyname($hostname);
                if ($resolvedIp === $this->ip) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkJavaScript(): bool
    {
        if (!isset($_COOKIE['js_enabled'])) {
            setcookie('js_enabled', '1', time() + 3600, '/');
            return false;
        }
        return true;
    }

    private function isRateLimited(): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $maxRequests = 100; // Максимальное количество запросов
        $timeFrame = 3600;  // Временной интервал в секундах

        if (!isset($_SESSION['requests'][$this->ip])) {
            $_SESSION['requests'][$this->ip] = array();
        }

        // Удаляем старые записи
        $_SESSION['requests'][$this->ip] = array_filter($_SESSION['requests'][$this->ip], function($timestamp) use ($timeFrame) {
            return ($timestamp > (time() - $timeFrame));
        });

        // Добавляем текущий запрос
        $_SESSION['requests'][$this->ip][] = time();

        if (count($_SESSION['requests'][$this->ip]) > $maxRequests) {
            return true;
        }

        return false;
    }

    private function isMissingReferer() {
        return empty($_SERVER['HTTP_REFERER']);
    }

    private function missingHeaders() {
        return !isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) || !isset($_SERVER['HTTP_ACCEPT_ENCODING']);
    }
}
