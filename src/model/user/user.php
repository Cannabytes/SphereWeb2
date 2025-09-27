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
            if (isset($_SESSION['id'])) {
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


    private static bool $isLoaded = false;

    /**
     * @return \Ofey\Logan22\model\user\userModel[]|null
     */
    public static function getUsers(): array
    {
        if (self::$isLoaded) {
            return self::$users;
        }

        $rows = sql::getRows("SELECT * FROM users");

        foreach ($rows as $row) {
            $user = new userModel();
            $user->setUser($row);
            self::$users[$row['id']] = $user;
        }

        // Предзагружаем fingerprints для всех пользователей одной выборкой, чтобы исключить N+1
        if (!empty(self::$users)) {
            $ids = array_keys(self::$users);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $fingerRows = sql::getRows(
                "SELECT DISTINCT user_id, fingerprint FROM user_auth_log WHERE user_id IN ($placeholders)",
                $ids
            );
            $map = [];
            foreach ($fingerRows as $f) {
                $uid = (int)$f['user_id'];
                if (!isset($map[$uid])) {
                    $map[$uid] = [];
                }
                if ($f['fingerprint']) {
                    $map[$uid][] = $f['fingerprint'];
                }
            }
            foreach ($map as $uid => $fps) {
                if (isset(self::$users[$uid])) {
                    self::$users[$uid]->setFingerprints($fps);
                }
            }
        }

        self::$isLoaded = true;

        return self::$users;
    }
}
