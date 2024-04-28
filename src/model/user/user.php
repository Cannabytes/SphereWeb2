<?php

namespace Ofey\Logan22\model\user;

class user
{

    private static array $users = [];

    public static function getUserId($userId = null): ?userModel
    {
        if ($userId === null) {
            $userId = $_SESSION['id'] ?? null;
            if ($userId == null) {
                return new userModel(null);
            }
        }
        if (isset(self::$users[$userId])) {
            return self::$users[$userId];
        }
        $user = new userModel($userId);
        self::$users[$userId] = $user;
        return $user;
    }

    /**
     * @return userModel|null
     * Возвращает класс информации профиле
     */
    public static function self(): ?userModel
    {
        return self::getUserId();
    }

}