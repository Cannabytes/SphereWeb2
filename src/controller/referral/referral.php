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

        $playersList = \Ofey\Logan22\model\referral\referral::player_list();
        $hasRequirements = array_filter($playersList, function ($entry) {
            return isset($entry['is_requirements']) && $entry['is_requirements'] === true;
        });
        $countBonus = 0;
        $playerNames = [];
        $items = []; // Массив для хранения информации о предметах

        if (!empty($hasRequirements)) {
            $bonusDonateCoin = config::load()->referral()->getBonusAmount();

            foreach ($hasRequirements as $referral) {
                $slaveUser = user::getUserId($referral['id']);

                //Отметим что данный реферальный квест был выполнен
                \Ofey\Logan22\model\referral\referral::done($slaveUser->getId(), user::self()->getId());
                $countBonus += $bonusDonateCoin;
                $playerNames[] = $referral['name'];
                user::self()->donateAdd($bonusDonateCoin);

                $leaderDonateItems = config::load()->referral()->getLeaderBonusItems();
                if (!empty($leaderDonateItems)) {
                    foreach ($leaderDonateItems as $item) {
                        $item_id = $item->getItemId();
                        $count = $item->getCount() ?? 1;
                        $enchant = $item->getEnchant() ?? 0;
                        user::self()->addToWarehouse(user::self()->getServerId(), $item_id, $count, $enchant, "add_item_donate_bonus_referral_master");

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
                        $slaveUser->addToWarehouse(user::self()->getServerId(), $item_id, $count, $enchant, "add_item_donate_bonus_referral_slave");
                    }
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
        $doneWait = 0;
        foreach ($myRefs as $ref) {
            if ($ref['done'] == 1) {
                $doneOK++;
            } else {
                $doneWait++;
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