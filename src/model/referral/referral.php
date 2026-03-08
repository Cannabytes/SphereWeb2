<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 11.02.2023 / 7:00:42
 */

namespace Ofey\Logan22\model\referral;

use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;

class referral
{

    //Вне зависимости завершен или нет
    public static function get_user_leader($user_id): ?UserModel
    {
        $query = sql::getRow("SELECT `leader_id` FROM `referrals` WHERE `user_id` = ? LIMIT 1", [$user_id]);
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
        $ref_users = sql::getRows("SELECT `id`, `user_id`, `done`, `join_date` FROM `referrals` WHERE `leader_id` = ? and `done` = 0 ", [user::self()->getId()]);
        $users     = [];
        foreach ($ref_users as $ref) {
            $user    = user::getUserId($ref['user_id']);
            $is_requirements = false;
            $qualifying_character = null;
            $qualifying_account = null;
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
                        $qualifying_character = $character;
                        $qualifying_account = $characters;
                        break;
                    }
                }
                 if($is_requirements) {
                     break;
                 }
            }

            // Пропускаем игровые аккаунты, которые уже получили бонус
            if ($qualifying_account && self::hasAccountEarnedBonus($qualifying_account->getAccount())) {
                continue;
            }

            $data = [
                //Выполнены ли реферальные требования
                'is_requirements' => $is_requirements,
                'id' => $user->getId(),
                'name'   => $user->getName(),
                'avatar' => $user->getAvatar(),
                'accounts' => $user->getAccounts(),
                'done' => $ref['done'],
                'join_date' => $ref['join_date'],
                'referral_id' => $ref['id'],
                'qualifying_character' => $qualifying_character,
                'qualifying_account' => $qualifying_account,
            ];
            $users[] = $data;
        }

        return $users;
    }

    public static function add($user_id, $leader_id): void
    {
        if (config::load()->referral()->isEnable()) {
            sql::run("INSERT INTO `referrals` (`user_id`, `leader_id`, `done`, `join_date`) VALUES (?, ?, ?, ?)", [
              $user_id,
              $leader_id,
              0,
              time::mysql(),
            ]);
        }
    }

    public static function done($user_id, $leader_id): \PDOException|false|\Exception|\PDOStatement|null
    {
       return sql::run("UPDATE `referrals` SET `done` = 1, `done_date` = ? WHERE `user_id` = ? AND `leader_id` = ?", [time::mysql(), $user_id, $leader_id]);
    }

    /**
     * Check if a game account has already earned a referral bonus
     * Identifies account by account name - prevents same game account from earning bonus multiple times
     * 
     * @param string $accountName - Name of the game account
     * @return bool - True if account has already earned bonus, false otherwise
     */
    public static function hasAccountEarnedBonus(string $accountName): bool
    {
        $query = "SELECT `id` FROM `logs_all` 
                  WHERE `type` = ? AND `variables` LIKE ?
                  LIMIT 1";
        
        $searchPattern = '%"game_account_name":"' . $accountName . '"%';
        $result = sql::getRow($query, [logTypes::LOG_REFERRAL_CHARACTER_BONUS_AWARDED->value, $searchPattern]);
        
        return !empty($result);
    }

    /**
     * Check if a character has already earned a referral bonus
     * Identifies character by name and server_id combination
     * 
     * @param string $characterName - Name of the character
     * @param int $serverId - Server ID where character exists
     * @return bool - True if character has already earned bonus, false otherwise
     */
    public static function hasCharacterEarnedBonus(string $characterName, int $serverId): bool
    {
        $query = "SELECT `id` FROM `logs_all` 
                  WHERE `type` = ? AND `variables` LIKE ?
                  LIMIT 1";
        
        $searchPattern = '%"character_name":"' . $characterName . '"%"server_id":' . $serverId . '%';
        $result = sql::getRow($query, [logTypes::LOG_REFERRAL_CHARACTER_BONUS_AWARDED->value, $searchPattern]);
        
        return !empty($result);
    }

    /**
     * Log a successful character bonus award
     * 
     * @param userModel $leader - The referrer (leader) user object
     * @param userModel $slave - The referred (slave) user object  
     * @param string $gameAccountName - Name of the game account that earned the bonus
     * @param string $characterName - Name of the character that earned the bonus
     * @param int $serverId - Server ID where character exists
     * @param float $bonusAmount - Amount of donate coins awarded
     * @param int $referralId - ID of the referral record
     */
    public static function logCharacterBonusAwarded(userModel $leader, userModel $slave, string $gameAccountName, string $characterName, int $serverId, float $bonusAmount, int $referralId): void
    {
        $variables = [
            'game_account_name' => $gameAccountName,
            'character_name' => $characterName,
            'server_id' => $serverId,
            'profile_username' => $slave->getName(),
            'slave_user_id' => $slave->getId(),
            'leader_user_id' => $leader->getId(),
            'leader_name' => $leader->getName(),
            'bonus_amount' => $bonusAmount,
            'referral_id' => $referralId
        ];
        
        $leader->addLog(logTypes::LOG_REFERRAL_CHARACTER_BONUS_AWARDED, 'referral_character_bonus_awarded', $variables);
    }

    /**
     * Log a rejected character bonus (already earned)
     * 
     * @param userModel $leader - The referrer (leader) user object
     * @param userModel $slave - The referred (slave) user object
     * @param string $gameAccountName - Name of the game account attempting bonus
     * @param string $characterName - Name of the character attempting bonus
     * @param int $serverId - Server ID where character exists
     * @param int $referralId - ID of the referral record
     */
    public static function logCharacterBonusRejected(userModel $leader, userModel $slave, string $gameAccountName, string $characterName, int $serverId, int $referralId): void
    {
        $variables = [
            'game_account_name' => $gameAccountName,
            'character_name' => $characterName,
            'server_id' => $serverId,
            'profile_username' => $slave->getName(),
            'slave_user_id' => $slave->getId(),
            'leader_user_id' => $leader->getId(),
            'leader_name' => $leader->getName(),
            'reason' => 'character_already_earned_bonus',
            'referral_id' => $referralId
        ];
        
        $leader->addLog(logTypes::LOG_REFERRAL_CHARACTER_BONUS_REJECTED, 'referral_character_bonus_rejected', $variables);
    }

}