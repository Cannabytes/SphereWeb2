<?php
/**
 * CSRF Protection Component
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 09.11.2025
 */

namespace Ofey\Logan22\component\csrf;

use Ofey\Logan22\component\alert\board;

class csrf
{
    private const TOKEN_NAME = '_csrf_token';
    private const HEADER_NAME = 'X-CSRF-Token';
    private const TOKEN_LENGTH = 32;
    
    /**
     * Генерация CSRF токена для текущей сессии
     */
    public static function generateToken(): string
    {
        if (!isset($_SESSION[self::TOKEN_NAME]) || empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Получение текущего CSRF токена
     */
    public static function getToken(): ?string
    {
        return $_SESSION[self::TOKEN_NAME] ?? null;
    }
    
    /**
     * Валидация CSRF токена
     */
    public static function validateToken(?string $token): bool
    {
        $sessionToken = self::getToken();
        
        if ($sessionToken === null || $token === null) {
            self::logFailure($sessionToken, $token, 'missing_token');
            return false;
        }
        
        $isValid = hash_equals($sessionToken, $token);
        if (!$isValid) {
            self::logFailure($sessionToken, $token, 'mismatch');
        }
        return $isValid;
    }
    
    /**
     * Проверка CSRF токена из запроса
     * Проверяет POST параметр и HTTP заголовок
     */
    public static function verify(): bool
    {
        // Получаем токен из POST или заголовка
        $token = $_POST[self::TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($token === null) {
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded) && isset($decoded[self::TOKEN_NAME])) {
                    $token = $decoded[self::TOKEN_NAME];
                }
            }
        }
        
        if ($token === null) {
            self::logFailure(self::getToken(), null, 'token_not_found');
        }
        
        return self::validateToken($token);
    }
    
    /**
     * Проверка CSRF токена из JSON данных
     */
    public static function verifyJsonToken(array $jsonData): bool
    {
        $token = $jsonData[self::TOKEN_NAME] ?? null;
        if ($token === null) {
            self::logFailure(self::getToken(), null, 'json_token_missing');
            return false;
        }

        $isValid = self::validateToken($token);
        if (!$isValid) {
            self::logFailure(self::getToken(), $token, 'json_token_invalid');
        }
        return $isValid;
    }

    /**
     * Проверка CSRF токена из JSON данных с выбросом исключения
     */
    public static function verifyJsonTokenOrFail(array $jsonData): void
    {
        if (!self::verifyJsonToken($jsonData)) {
            http_response_code(403);
            board::error('CSRF token validation failed. Please reload the page and try again.');
        }
    }

    /**
     * Проверка CSRF токена с выбросом исключения при ошибке
     */
    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            // Проверяем, это AJAX запрос или обычный
            if (self::isAjaxRequest()) {
                http_response_code(403);
                board::error('CSRF token validation failed. Please reload the page and try again.');
            } else {
                http_response_code(403);
                die('CSRF token validation failed. Please reload the page and try again.');
            }
        }
    }
    
    /**
     * Получение HTML input для формы
     */
    public static function getTokenInput(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Получение имени поля токена
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }
    
    /**
     * Получение имени заголовка
     */
    public static function getHeaderName(): string
    {
        return self::HEADER_NAME;
    }
    
    /**
     * Регенерация токена (например, после входа пользователя)
     */
    public static function regenerateToken(): string
    {
        unset($_SESSION[self::TOKEN_NAME]);
        return self::generateToken();
    }
    
    /**
     * Проверка, является ли запрос AJAX
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Получение мета-тега для HTML
     */
    public static function getMetaTag(): string
    {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    private static function logFailure(?string $sessionToken, ?string $requestToken, string $reason): void
    {
        $logDir = __DIR__ . '/../../../uploads/logs/info';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/csrf_debug.log';
        $data = [
            'time' => date('Y-m-d H:i:s'),
            'reason' => $reason,
            'session_token' => $sessionToken,
            'request_token' => $requestToken,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'post_keys' => array_keys($_POST ?? []),
            'headers' => [
                'X-CSRF-Token' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
                'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? null,
            ],
        ];
        $message = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($logFile, $message, FILE_APPEND);
    }
}

