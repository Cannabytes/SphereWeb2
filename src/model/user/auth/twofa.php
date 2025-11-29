<?php
/**
 * Two-Factor Authentication (2FA) Model
 * Uses Time-based One-Time Password (TOTP) algorithm
 * Compatible with Google Authenticator, Microsoft Authenticator, Authy, etc.
 */

namespace Ofey\Logan22\model\user\auth;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class twofa
{
    /**
     * Длина секретного ключа в байтах (160 бит = 20 байт рекомендуется RFC 4226)
     */
    private const SECRET_LENGTH = 20;
    
    /**
     * Период действия кода в секундах (30 секунд - стандарт)
     */
    private const TIME_PERIOD = 30;
    
    /**
     * Длина OTP кода (6 цифр - стандарт)
     */
    private const CODE_LENGTH = 6;
    
    /**
     * Допустимое отклонение времени (количество периодов)
     * 1 = допускается код предыдущего и следующего периода (±30 секунд)
     */
    private const TIME_DRIFT = 1;
    
    /**
     * Base32 алфавит для кодирования
     */
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    /**
     * Генерация случайного секретного ключа
     * 
     * @return string Base32 закодированный секретный ключ
     */
    public static function generateSecret(): string
    {
        $randomBytes = random_bytes(self::SECRET_LENGTH);
        return self::base32Encode($randomBytes);
    }
    
    /**
     * Генерация TOTP кода
     * 
     * @param string $secret Base32 закодированный секрет
     * @param int|null $timestamp Временная метка (null = текущее время)
     * @return string 6-значный код
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeCounter = floor($timestamp / self::TIME_PERIOD);
        
        $secretBytes = self::base32Decode($secret);
        
        // Преобразуем счетчик в 8-байтовую строку (big-endian)
        // Используем pack('N', ...) дважды для совместимости с 32-битными системами
        $timeBytes = pack('N', 0) . pack('N', $timeCounter);
        
        // HMAC-SHA1
        $hash = hash_hmac('sha1', $timeBytes, $secretBytes, true);
        
        // Динамическое усечение
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        
        // Получаем код нужной длины
        $otp = $binary % pow(10, self::CODE_LENGTH);
        
        return str_pad((string)$otp, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
    
    /**
     * Проверка TOTP кода
     * 
     * @param string $secret Base32 закодированный секрет
     * @param string $code Введенный пользователем код
     * @return bool
     */
    public static function verifyCode(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        
        if (strlen($code) !== self::CODE_LENGTH) {
            return false;
        }
        
        $timestamp = time();
        
        // Проверяем текущий период и соседние (для компенсации drift)
        for ($i = -self::TIME_DRIFT; $i <= self::TIME_DRIFT; $i++) {
            $checkTime = $timestamp + ($i * self::TIME_PERIOD);
            $expectedCode = self::generateCode($secret, $checkTime);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Генерация URI для QR кода (otpauth://)
     * 
     * @param string $secret Секретный ключ
     * @param string $accountName Email или имя аккаунта
     * @param string $issuer Название сервиса
     * @return string URI для otpauth
     */
    public static function getOtpauthUri(string $secret, string $accountName, string $issuer = 'SphereWeb'): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_LENGTH,
            'period' => self::TIME_PERIOD,
        ]);
        
        $accountName = rawurlencode($accountName);
        $issuer = rawurlencode($issuer);
        
        return "otpauth://totp/{$issuer}:{$accountName}?{$params}";
    }
    
    /**
     * Генерация URL для QR кода через Google Charts API
     * 
     * @param string $otpauthUri URI otpauth
     * @param int $size Размер QR кода в пикселях
     * @return string URL изображения QR кода
     */
    public static function getQRCodeUrl(string $otpauthUri, int $size = 200): string
    {
        $encodedUri = urlencode($otpauthUri);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedUri}&ecc=M";
    }
    
    /**
     * Включение 2FA для пользователя
     * 
     * @param int $userId ID пользователя
     * @param string $secret Секретный ключ
     * @param string $code Код для верификации
     * @return bool Успех операции
     */
    public static function enable(int $userId, string $secret, string $code): bool
    {
        // Проверяем код перед включением
        if (!self::verifyCode($secret, $code)) {
            return false;
        }
        
        // Сохраняем в таблицу user_variables
        self::setUserVariable($userId, 'two_fa_secret', $secret);
        self::setUserVariable($userId, 'two_fa_enabled', '1');
        
        return true;
    }
    
    /**
     * Отключение 2FA для пользователя
     * 
     * @param int $userId ID пользователя
     * @param string $code Код для подтверждения (опционально, если нужна дополнительная проверка)
     * @return bool Успех операции
     */
    public static function disable(int $userId, ?string $code = null): bool
    {
        if ($code !== null) {
            $secret = self::getSecret($userId);
            if ($secret && !self::verifyCode($secret, $code)) {
                return false;
            }
        }
        
        // Удаляем переменные из user_variables
        self::deleteUserVariable($userId, 'two_fa_secret');
        self::deleteUserVariable($userId, 'two_fa_enabled');
        
        return true;
    }
    
    /**
     * Проверка, включена ли 2FA у пользователя
     * 
     * @param int $userId ID пользователя
     * @return bool
     */
    public static function isEnabled(int $userId): bool
    {
        $result = self::getUserVariable($userId, 'two_fa_enabled');
        return $result === '1';
    }
    
    /**
     * Получение секретного ключа пользователя
     * 
     * @param int $userId ID пользователя
     * @return string|null
     */
    public static function getSecret(int $userId): ?string
    {
        return self::getUserVariable($userId, 'two_fa_secret');
    }
    
    /**
     * Получить переменную пользователя из user_variables
     * 
     * @param int $userId ID пользователя
     * @param string $var Название переменной
     * @return string|null
     */
    private static function getUserVariable(int $userId, string $var): ?string
    {
        $result = sql::getRow(
            "SELECT `val` FROM `user_variables` WHERE `user_id` = ? AND `var` = ? AND (`server_id` IS NULL OR `server_id` = 0)",
            [$userId, $var]
        );
        
        return $result['val'] ?? null;
    }
    
    /**
     * Установить переменную пользователя в user_variables
     * 
     * @param int $userId ID пользователя
     * @param string $var Название переменной
     * @param string $val Значение
     */
    private static function setUserVariable(int $userId, string $var, string $val): void
    {
        // Удаляем старое значение если есть
        sql::run(
            "DELETE FROM `user_variables` WHERE `user_id` = ? AND `var` = ? AND (`server_id` IS NULL OR `server_id` = 0)",
            [$userId, $var]
        );
        
        // Вставляем новое значение
        sql::run(
            "INSERT INTO `user_variables` (`server_id`, `user_id`, `var`, `val`) VALUES (0, ?, ?, ?)",
            [$userId, $var, $val]
        );
    }
    
    /**
     * Удалить переменную пользователя из user_variables
     * 
     * @param int $userId ID пользователя
     * @param string $var Название переменной
     */
    private static function deleteUserVariable(int $userId, string $var): void
    {
        sql::run(
            "DELETE FROM `user_variables` WHERE `user_id` = ? AND `var` = ? AND (`server_id` IS NULL OR `server_id` = 0)",
            [$userId, $var]
        );
    }
    
    /**
     * Проверка кода 2FA для пользователя
     * 
     * @param int $userId ID пользователя
     * @param string $code Код
     * @return bool
     */
    public static function verifyUserCode(int $userId, string $code): bool
    {
        $secret = self::getSecret($userId);
        
        if (!$secret) {
            return false;
        }
        
        return self::verifyCode($secret, $code);
    }
    
    /**
     * Генерация резервных кодов
     * 
     * @param int $count Количество кодов
     * @return array Массив резервных кодов
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
    
    /**
     * Кодирование в Base32
     * 
     * @param string $data Данные для кодирования
     * @return string Base32 строка
     */
    private static function base32Encode(string $data): string
    {
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        
        $result = '';
        $chunks = str_split($binary, 5);
        
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $result .= self::BASE32_ALPHABET[bindec($chunk)];
        }
        
        return $result;
    }
    
    /**
     * Декодирование из Base32
     * 
     * @param string $data Base32 строка
     * @return string Декодированные данные
     */
    private static function base32Decode(string $data): string
    {
        $data = strtoupper($data);
        $data = str_replace('=', '', $data);
        
        $binary = '';
        foreach (str_split($data) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        
        $result = '';
        $chunks = str_split($binary, 8);
        
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }
        
        return $result;
    }
}
