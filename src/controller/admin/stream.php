<?php

namespace Ofey\Logan22\controller\admin;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\template\tpl;

class stream
{

    static function satisfy(): void
    {
        if ( ! isset($_POST['streamId']) || empty(trim($_POST['streamId']))) {
            board::error("Не выбран канал");
        } elseif ( ! filter_var($_POST['streamId'], FILTER_VALIDATE_INT)) {
            board::error("Канал должен быть корректным URL адресом");
        }
        $streamId = $_POST['streamId'];

        $streamData = sql::getRow("SELECT * FROM `streams` WHERE `id` = ?", [$streamId]);
        if ( ! $streamData) {
            board::error("Нет данных о стриме");
        }
        sql::run("UPDATE `streams` SET `confirmed` = 1 WHERE `id` = ?", [$streamId]);
        board::success("Стрим одобрен");
    }

    static function show()
    {
        $streams = sql::getRows("SELECT * FROM `streams` ORDER BY `id` DESC ", []);
        foreach ($streams as &$stream) {
            if (empty($stream['data'])) {
                continue;
            }
            $stream['data'] = json_decode($stream['data'], true);
        }
        tpl::addVar(['streams' => $streams]);
        tpl::display("/admin/stream.html");
    }

    //Установка автопроверки
    static function setAutoCheck(): void
    {
        $streamId      = $_POST['streamId'];
        $autoCheckDate = $_POST['date'];
        sql::run("UPDATE `streams` SET `auto_check_date` = ? WHERE `id` = ?", [$autoCheckDate, $streamId]);
    }

    static function removeAutoCheck(): void
    {
        $streamId = $_POST['streamId'];
        sql::run("UPDATE `streams` SET `auto_check_date` = NULL WHERE `id` = ?", [$streamId]);
    }

    //Удаление стрима админом
    static function removeStream(): void
    {
        $streamId = $_POST['streamId'];
        sql::run("DELETE FROM `streams` WHERE `id` = ?", [$streamId]);
        board::success("Стрим удален");
    }

}