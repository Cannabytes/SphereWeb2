<?php
/////
namespace Ofey\Logan22\model\user;

use Ofey\Logan22\model\db\sql;

class user
{

    /**
     * @var array <userModel>
     */
    private static array $users = [];

    /**
     * @return userModel|null
     * Возвращает класс информации профиле
     */
    public static function self(): ?userModel
    {
        return self::getUserId();
    }

    public static function getUserId($userId = 0): ?userModel
    {
        if (isset(self::$users[$userId])) {
            return self::$users[$userId];
        }
        if ($userId === 0) {
            if(isset($_SESSION['id'])){
                $userId = $_SESSION['id'];
            }
        }
        if (isset(self::$users[$userId])) {
            return self::$users[$userId];
        }
        $user                 = new userModel($userId);
        self::$users[$userId] = $user;
        return $user;
    }

    public static function getUserByName($name): ?userModel
    {
        foreach (self::$users as $user) {
            if ($user->getName() == $name) {
                return $user;
            }
        }
        $userQuery = sql::getRow("SELECT * FROM `users` WHERE `name` = ? LIMIT 1", [$name]);
        if ($userQuery) {
            $user = new userModel();
            $user->setUser($userQuery);
            self::$users[$user->getId()] = $user;
            return $user;
        }
        return null;
    }

    public static function getUserByEmail($email): ?userModel
    {
        foreach (self::$users as $user) {
            if ($user->getEmail() == $email) {
                return $user;
            }
        }
        $userQuery = sql::getRow("SELECT * FROM `users` WHERE `email` = ? LIMIT 1", [$email]);
        if ($userQuery) {
            $user = new userModel();
            $user->setUser($userQuery);
            self::$users[$user->getId()] = $user;
            return $user;
        }
        return null;
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