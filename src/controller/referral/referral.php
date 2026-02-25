<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 11.02.2023 / 6:55:44
 */

namespace Ofey\Logan22\controller\referral;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\lang\lang;
use Ofey\Logan22\model\referral\referral as ReferralModel;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class referral
{

    private static int $cooldown = 5;

    /**
     * Выдача предметов за рефералов
     */
    public static function bonus()
    {
        //Проверка на включенный реферальный бонус
        if (!config::load()->referral()->isEnable()) {
            board::error("Реферальная система отключена");
        }

        if (isset($_SESSION['cooldown_referral_bonus']) && (time() - $_SESSION['cooldown_referral_bonus']) < self::$cooldown) {
            $cooldown = self::$cooldown;
            board::error("Вы можете получить бонусы только один раз в {$cooldown} секунд");
        }
        $_SESSION['cooldown_referral_bonus'] = time();

        $playersList = ReferralModel::player_list();
        $hasRequirements = array_filter($playersList, function ($entry) {
            return isset($entry['is_requirements']) && $entry['is_requirements'] === true;
        });
        $countBonus = 0;
        $playerNames = [];
        $items = []; // Массив для хранения информации о предметах
        $rejectedCharacters = []; // Track rejected characters

        if (!empty($hasRequirements)) {
            $bonusDonateCoin = config::load()->referral()->getBonusAmount();

            foreach ($hasRequirements as $referral) {
                $slaveUser = user::getUserId($referral['id']);
                $leaderUser = user::self();
                
                // Get character info for duplicate checking
                $characterName = $referral['qualifying_character']->getName();
                $serverId = $referral['qualifying_account']->getServerId();

                // Check if this character has already earned a bonus
                if (ReferralModel::hasCharacterEarnedBonus($characterName, $serverId)) {
                    // Log rejection for audit trail
                    ReferralModel::logCharacterBonusRejected(
                        $leaderUser,
                        $slaveUser,
                        $characterName,
                        $serverId,
                        $referral['referral_id']
                    );
                    $rejectedCharacters[] = $characterName;
                    continue; // Skip this character, don't award bonus
                }

                //Отметим что данный реферальный квест был выполнен
                ReferralModel::done($slaveUser->getId(), $leaderUser->getId());
                
                // Log successful bonus award for character
                ReferralModel::logCharacterBonusAwarded(
                    $leaderUser,
                    $slaveUser,
                    $characterName,
                    $serverId,
                    $bonusDonateCoin,
                    $referral['referral_id']
                );
                
                $countBonus += $bonusDonateCoin;
                $playerNames[] = $referral['name'];
                $leaderUser->donateAdd($bonusDonateCoin);

                $leaderDonateItems = config::load()->referral()->getLeaderBonusItems();
                if (!empty($leaderDonateItems)) {
                    foreach ($leaderDonateItems as $item) {
                        $item_id = $item->getItemId();
                        $count = $item->getCount() ?? 1;
                        $enchant = $item->getEnchant() ?? 0;
                        $leaderUser->addToWarehouse($leaderUser->getServerId(), $item_id, $count, $enchant, "add_item_donate_bonus_referral_master");

                        // Добавляем информацию о предмете в массив для передачи во фронтенд
                        $itemInfo = [
                            'icon' => $item->getIcon(),
                            'name' => $item->getItemName(),
                            'count' => $count,
                            'enchant' => $enchant
                        ];

                        // Если есть дополнительное имя, добавляем его
                        if ($item->getAddName()) {
                            $itemInfo['addName'] = $item->getAddName();
                        }

                        $items[] = $itemInfo;
                    }
                }

                $slaveDonateItems = config::load()->referral()->getSlaveBonusItems();
                if (!empty($slaveDonateItems)) {
                    foreach ($slaveDonateItems as $item) {
                        $item_id = (int)$item->getItemId();
                        $count = (int)$item->getCount() ?? 1;
                        $enchant = (int)$item->getEnchant() ?? 0;
                        $slaveUser->addToWarehouse($leaderUser->getServerId(), $item_id, $count, $enchant, "add_item_donate_bonus_referral_slave");
                    }
                }
            }

            // Check if we rejected any characters
            if (!empty($rejectedCharacters)) {
                if (empty($playerNames)) {
                    // All characters were rejected
                    board::error("Ошибка: персонажи уже получали бонус за реферальную систему (" . implode(', ', $rejectedCharacters) . ")");
                } else {
                    // Some were successful, some were rejected
                    board::alert([
                        "warning" => true,
                        "message" => "Бонус выдан для: " . implode(', ', $playerNames) . ". 
                        Отклонено персонажей (уже получали бонус): " . implode(', ', $rejectedCharacters),
                    ]);
                    return;
                }
            }

            // Массив с именами игроков в строку, разделенной запятой
            $playerNames = implode(', ', $playerNames);
            $getProcentDonateBonus = config::load()->referral()->getProcentDonateBonus();

            board::alert([
                "items" => $items, // Передаем массив с информацией о предметах
                "success" => true,
                "message" => config::load()->lang()->getPhrase("referral_bonus_message", $countBonus, $getProcentDonateBonus, $playerNames),
            ]);
        } else {
            board::error("Вы не можете получить бонусы за рефералов, не выполнены требуемые условия");
        }
    }

    public static function show()
    {
        if (!config::load()->referral()->isEnable()) {
            redirect::location("/main");
        }

        $playersList     = \Ofey\Logan22\model\referral\referral::player_list();
        $hasRequirements = (bool)array_filter($playersList, function ($entry) {
            return isset($entry['is_requirements']) && $entry['is_requirements'] === true;
        });

        $myRefs = sql::getRows("SELECT `done` FROM `referrals` WHERE `leader_id` = ? ", [user::self()->getId()]);
        $doneOK = 0;
        foreach ($myRefs as $ref) {
            if ($ref['done'] == 1) {
                $doneOK++;
            }
        }

        tpl::addVar([
          "allCount" => count($myRefs),
          "doneCountOk" => $doneOK,
          "hasRequirements" => $hasRequirements,
          "referrals"       => $playersList,
        ]);
        tpl::display('/referral.html');
    }

}