<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 11.02.2023 / 7:00:42
 */

namespace Ofey\Logan22\model\referral;

use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;

class referral
{

    public static function get_user_leader($user_id): ?UserModel
    {
        $query = sql::getRow("SELECT `leader_id` FROM `referrals` WHERE `user_id` = ? AND `done` = 1 LIMIT 1", [$user_id]);
        if (!$query) {
            return null;
        }
        return user::getUserId($query['leader_id']);
    }

    /**
     * Возвращаем ID пользователя, который завлек человека
     */
    public static function has_leader($user_id)
    {
        return sql::getRow('SELECT `leader_id` FROM `referrals` WHERE user_id = ?', [$user_id]);
    }

    public static function player_list()
    {
        //Мои рефералы
        $ref_users = sql::getRows("SELECT `id`, `user_id`, `done` FROM `referrals` WHERE `leader_id` = ? and `done` = 0 ", [user::self()->getId()]);
        $users     = [];
        foreach ($ref_users as $ref) {
            $user    = user::getUserId($ref['user_id']);
            $is_requirements = false;
            $accountCharacters = $user->getAccounts();
            foreach ($accountCharacters as $characters) {
                 foreach ($characters->getCharacters() as $character) {
                     $is_requirements_level = false;
                     $is_requirements_pvp = false;
                     $is_requirements_pk = false;
                     $is_requirements_time = false;
                     //Проверяем уровень, pvp, pk и время в игре
                    if ($character->getLevel() >= config::load()->referral()->getLevel()) {
                        $is_requirements_level = true;
                    }
                    if ($character->getPvp() >= config::load()->referral()->getPvp()) {
                        $is_requirements_pvp = true;
                    }
                    if ($character->getPk() >= config::load()->referral()->getPk()) {
                        $is_requirements_pk = true;
                    }
                    if ($character->getTimeInGame() >= config::load()->referral()->getTimeGame()) {
                        $is_requirements_time = true;
                    }

                    if($is_requirements_level && $is_requirements_pvp && $is_requirements_pk && $is_requirements_time) {
                        $is_requirements = true;
                        break;
                    }
                }
                 if($is_requirements) {
                     break;
                 }
            }

            $data = [
                //Выполнены ли реферальные требования
                'is_requirements' => $is_requirements,
                'id' => $user->getId(),
                'name'   => $user->getName(),
                'avatar' => $user->getAvatar(),
                'accounts' => $user->getAccounts(),
                'done' => $ref['done'],
            ];
            $users[] = $data;
        }

        return $users;
    }

    public static function add($user_id, $leader_id): void
    {
        if (config::load()->referral()->isEnable()) {
            sql::run("INSERT INTO `referrals` (`user_id`, `leader_id`, `done`) VALUES (?, ?, ?)", [
              $user_id,
              $leader_id,
              0,
            ]);
        }
    }

    public static function done($user_id, $leader_id): \PDOException|false|\Exception|\PDOStatement|null
    {
       return sql::run("UPDATE `referrals` SET `done` = 1 WHERE `user_id` = ? AND `leader_id` = ?", [$user_id, $leader_id]);
    }

}