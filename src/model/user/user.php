<?php

namespace Ofey\Logan22\model\user;

use Ofey\Logan22\model\db\sql;

class user
{

    private static array $users = [];

    /**
     * @return userModel|null
     * Возвращает класс информации профиле
     */
    public static function self(): ?userModel
    {
        return self::getUserId();
    }

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
        $user                 = new userModel($userId);
        self::$users[$userId] = $user;

        return $user;
    }

    /**
     * @return \Ofey\Logan22\model\user\userModel[]|null
     */
    public static function getUsers(): ?array
    {
        $users = sql::getRows("SELECT * FROM `users`");
        foreach ($users as $user) {
            self::$users[$user['id']] = new userModel(null);
            self::$users[$user['id']]->setUser($user);
        }
        return self::$users;
    }

}