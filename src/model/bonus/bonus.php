<?php

namespace Ofey\Logan22\model\bonus;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\image\client_icon;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\log\logTypes;
use Ofey\Logan22\model\user\user;

class bonus
{

    //Создаем коды

    /**
     * @throws Exception
     */
    public static function genereateCode()
    {
        validation::user_protection("admin");

        $minCodeSymbols = 9;
        $maxCodeSymbols = 12;
        $countGenBonusCode = $_POST["count_codes"] ?? 100;
        $items = $_POST['items'];
        $prefix = trim($_POST['prefix']) ?? '';
        $autocreatecode = (bool) filter_input(INPUT_POST, 'autocreatecode', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        $disposable = (int) filter_input(INPUT_POST, 'disposable', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        if (!$autocreatecode) {
            if($prefix == '') {
                $prefix = self::generateRandomStrings($minCodeSymbols, $maxCodeSymbols);
            }
        }

        if ($items == null || count($items) == 0) {
            board::notice(false, "Не указаны предметы");
        }
        $start_date_code = isset($_POST['start_date_code']) && $_POST['start_date_code'] !== "" ? $_POST['start_date_code'] : board::notice(
            false,
            "Не указана начальная дата действия кода"
        );
        $end_date_code = isset($_POST['end_date_code']) && $_POST['end_date_code'] !== "" ? $_POST['end_date_code'] : board::notice(
            false,
            "Не указана конечная дата действия кода"
        );


        $server_id = $_POST['server'];
        $codesReg = [];
        if($autocreatecode) {
            for ($i = 0; $i < $countGenBonusCode; $i++) {
                $code = $prefix . self::generateRandomStrings($minCodeSymbols, $maxCodeSymbols);
                $codesReg[] = $code;
                foreach ($items as $item) {
                    $itemid = $item['itemId'];
                    $count = $item['count'];
                    $enchant = 0;
                    sql::run(
                        "INSERT INTO `bonus_code` (`server_id`, `code`, `item_id`, `count`, `enchant`, `phrase`, `start_date_code`, `end_date_code`, `disposable`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $server_id,
                            $code,
                            $itemid,
                            $count,
                            $enchant,
                            "code_bonus",
                            $start_date_code,
                            $end_date_code,
                            $disposable,
                        ]
                    );
                }
            }
        }else{
            sql::run("DELETE FROM `bonus_code` WHERE `server_id` = ? AND `code` = ?", [$server_id, $prefix]);
            $codesReg[] = $prefix;
            foreach ($items as $item) {
                $itemid = $item['itemId'];
                $count = $item['count'];
                $enchant = 0;
                sql::run(
                    "INSERT INTO `bonus_code` (`server_id`, `code`, `item_id`, `count`, `enchant`, `phrase`, `start_date_code`, `end_date_code`, `disposable`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $server_id,
                        $prefix,
                        $itemid,
                        $count,
                        $enchant,
                        "code_bonus",
                        $start_date_code,
                        $end_date_code,
                        $disposable,
                    ]
                );
            }
        }

        header('Content-Type: application/json');
        echo json_encode($codesReg);
    }

    public
    static function generateRandomStrings(
        $minLength,
        $maxLength
    )
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = "";
        $length = rand($minLength, $maxLength);

        for ($j = 0; $j < $length; $j++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Активирует бонусный код для текущего пользователя
     *
     * @param string $code Код бонуса для активации
     * @return void
     */
    public static function getCode(string $code): void
    {
        try {
            sql::beginTransaction();

            // Получаем бонусные коды из БД
            $bonusCodes = sql::getRows("SELECT * FROM bonus_code WHERE code = ?", [$code]);
            if (empty($bonusCodes)) {
                throw new Exception(lang::get_phrase("code_not_found"));
            }

            // Проверяем возможность использования кода
            self::validateCode($code, $bonusCodes);

            $bonusNames = "";
            $bonusNamesTxt = "";
            $codesToDelete = [];

            // Применяем каждый бонус
            foreach ($bonusCodes as $bonus) {
                // Применяем бонус и получаем информацию для отображения
                $result = self::applyBonus($bonus);

                // Добавляем данные для отображения
                $bonusNames .= $result['htmlDisplay'];
                $bonusNamesTxt .= $result['textDisplay'];

                // Логируем использование бонуса
                user::self()->addLog(
                    logTypes::LOG_BONUS_CODE,
                    'LOG_BONUS_CODE',
                    [$code, $result['name'], $bonus['count']]
                );

                // Если код одноразовый, добавляем его в список на удаление
                $isDisposable = !isset($bonus['disposable']) || (bool)$bonus['disposable'];
                if ($isDisposable) {
                    $codesToDelete[] = $bonus['id'];
                }
            }

            // Отправляем уведомление, если настроено
            self::sendNotification($bonusNamesTxt);

            // Удаляем одноразовые коды
            self::deleteDisposableCodes($codesToDelete);

            // Завершаем транзакцию
            sql::commit();

            // Выводим сообщение об успешном применении бонуса
            $message = lang::get_phrase("bonus_code_success", rtrim($bonusNames, ", "));
            board::success($message);

        } catch (Exception $e) {
            sql::rollback();
            board::notice(false, $e->getMessage());
        }
    }

    /**
     * Проверяет возможность использования бонусного кода
     *
     * @param string $code Проверяемый код
     * @param array $bonusCodes Массив бонусных кодов
     * @throws Exception Если код не может быть использован
     */
    private static function validateCode(string $code, array $bonusCodes): void
    {
        $currentTime = time();
        $currentServerId = user::self()->getServerId();
        $logs = user::self()->getLogs(logTypes::LOG_BONUS_CODE);

        foreach ($bonusCodes as $bonus) {
            // Проверка временных ограничений
            if ($currentTime < strtotime($bonus['start_date_code'])) {
                throw new Exception(lang::get_phrase("code_not_active"));
            }

            if ($currentTime > strtotime($bonus['end_date_code'])) {
                throw new Exception(lang::get_phrase("code_dead"));
            }

            // Проверка сервера
            if ($bonus['server_id'] != 0 && $bonus['server_id'] != $currentServerId) {
                throw new Exception("Этот код создан для другого сервера");
            }

            // Проверка на повторное использование многоразовых кодов
            $isDisposable = !isset($bonus['disposable']) || (bool)$bonus['disposable'];
            if (!$isDisposable) {
                foreach ($logs as $log) {
                    $variables = $log['variables'] ?? [];
                    if (!empty($variables) && $variables[0] == $code) {
                        throw new Exception("Этот код уже был Вами использован");
                    }
                }
            }
        }
    }

    /**
     * Применяет бонус к аккаунту пользователя
     *
     * @param array $bonus Данные бонуса
     * @return array Информация о примененном бонусе
     */
    private static function applyBonus(array $bonus): array
    {
        $itemId = $bonus['item_id'];
        $count = $bonus['count'];
        $enchant = $bonus['enchant'] ?? 0;
        $phrase = $bonus['phrase'];
        $serverId = ($bonus['server_id'] == 0) ? user::self()->getServerId() : $bonus['server_id'];
        $itemInfo = client_icon::get_item_info($itemId, false);

        // Для зачисления доната в ЛК
        if ($itemId == -1) {
            user::self()->donateAdd($count);
            $name = $itemInfo->getItemName();
            $htmlDisplay = $name . " ({$count}), ";
            $textDisplay = $name . " ({$count}), ";
        } else {
            // Добавление предмета на склад
            user::self()->addToWarehouse($serverId, $itemId, $count, $enchant, $phrase);
            $enchantPrefix = ($enchant > 0) ? "+{$enchant} " : "";
            $name = $enchantPrefix . $itemInfo->getAddName() . " " . $itemInfo->getItemName();
            $htmlDisplay = "<img src='" . $itemInfo->getIcon() . "' width='16' height='16'> " . $name . "({$count}), ";
            $textDisplay = $name . " ({$count}), ";
        }

        return [
            'name' => $name,
            'htmlDisplay' => $htmlDisplay,
            'textDisplay' => $textDisplay
        ];
    }

    /**
     * Отправляет уведомление о применении бонуса
     *
     * @param string $bonusNamesTxt Текстовое описание примененных бонусов
     */
    private static function sendNotification(string $bonusNamesTxt): void
    {
        $config = \Ofey\Logan22\controller\config\config::load();
        if (!$config->notice()->isUseBonusCode()) {
            return;
        }

        $template = lang::get_other_phrase(
            $config->notice()->getNoticeLang(),
            'notice_use_bonus_code'
        );

        // Удаляем запятую в конце списка бонусов
        $bonusNamesTxt = rtrim(trim($bonusNamesTxt), ',');

        $msg = strtr($template, [
            '{email}' => user::self()->getEmail(),
            '{bonusNames}' => $bonusNamesTxt,
        ]);

        telegram::sendTelegramMessage($msg);
    }

    /**
     * Удаляет одноразовые бонусные коды
     *
     * @param array $codeIds Массив ID кодов для удаления
     */
    private static function deleteDisposableCodes(array $codeIds): void
    {
        if (empty($codeIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($codeIds), '?'));
        sql::sql("DELETE FROM bonus_code WHERE id IN ({$placeholders})", $codeIds);
    }

}