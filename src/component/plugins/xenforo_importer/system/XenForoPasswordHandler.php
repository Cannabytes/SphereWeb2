<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

/**
 * Класс для проверки паролей XenForo
 * Поддерживает различные алгоритмы хэширования, используемые в XenForo
 * Работает с паролями в формате: xenforo:{serialized_data}
 */
class XenForoPasswordHandler
{
    /**
     * Проверить, является ли пароль паролем XenForo
     * @param string $storedHash Сохраненный хэш из БД
     * @return bool
     */
    public static function isXenForoPassword(string $storedHash): bool
    {
        return strpos($storedHash, 'xenforo:') === 0;
    }

    /**
     * Проверить пароль XenForo
     * @param string $password Введенный пароль
     * @param string $storedHash Сохраненный хэш из БД (формат: xenforo:{data})
     * @return bool
     */
    public static function verify(string $password, string $storedHash): bool
    {
        // Проверяем, что это пароль XenForo
        if (!self::isXenForoPassword($storedHash)) {
            // Если это не XenForo пароль, используем стандартную проверку
            return password_verify($password, $storedHash);
        }

        // Убираем префикс xenforo:
        $xenforoHash = substr($storedHash, 8); // strlen('xenforo:') = 8

        // Пробуем десериализовать данные XenForo
        $data = @unserialize($xenforoHash);
        if ($data === false) {
            // Если десериализация не удалась, возможно это просто хэш без соли
            // Пробуем стандартную проверку bcrypt/Argon2
            return password_verify($password, $xenforoHash);
        }

        // Если данные десериализованы, проверяем хэш по схеме XenForo
        if (isset($data['hash']) && isset($data['salt'])) {
            return self::verifyXenForoHash($password, $data['hash'], $data['salt'], $data['scheme'] ?? 'sha256');
        }

        return false;
    }

    /**
     * Проверить хэш по схеме XenForo
     * @param string $password Введенный пароль
     * @param string $hash Хэш из БД
     * @param string $salt Соль
     * @param string $scheme Схема хэширования (sha256, sha1, md5, bcrypt)
     * @return bool
     */
    private static function verifyXenForoHash(string $password, string $hash, string $salt, string $scheme): bool
    {
        switch ($scheme) {
            case 'bcrypt':
                return password_verify($password, $hash);

            case 'sha256':
                $computed = hash('sha256', hash('sha256', $password) . $salt);
                return hash_equals($hash, $computed);

            case 'sha1':
                $computed = sha1(sha1($password) . $salt);
                return hash_equals($hash, $computed);

            case 'md5':
                $computed = md5(md5($password) . $salt);
                return hash_equals($hash, $computed);

            default:
                return false;
        }
    }

    /**
     * Пере-хэшировать пароль в современный формат (bcrypt)
     * @param string $password Пароль в открытом виде
     * @return string Новый хэш
     */
    public static function rehash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Проверить, нужно ли пере-хэшировать пароль
     * @param string $storedHash Сохраненный хэш
     * @return bool
     */
    public static function needsRehash(string $storedHash): bool
    {
        // Если это пароль XenForo - нужно обновить
        if (self::isXenForoPassword($storedHash)) {
            return true;
        }

        // Проверяем через стандартную функцию PHP
        return password_needs_rehash($storedHash, PASSWORD_BCRYPT);
    }
}
