<?php

namespace Ofey\Logan22\controller\stream;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\sphere\type;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\stream\streamcheck;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\template\tpl;

class stream
{

    private static ?array $streams = null;

    public static function getStreams(): array
    {
        if (self::$streams !== null) {
            return self::$streams;
        }
        $streams = sql::getRows("SELECT * FROM `streams` WHERE confirmed = 1 AND (`data` IS NOT NULL AND `data` != '') ORDER BY `dateUpdate` DESC;");
        foreach ($streams as $i=>&$stream) {
            $stream['data'] = json_decode($stream['data'], true);
            if($stream['data']){
                if ($stream['data']['title'] == null) {
                    unset($streams[$i]);
                }
            }
        }
        self::$streams = $streams;

        return self::$streams;
    }

    //Добавление нового стрима
    public static function add()
    {
        if ( ! isset($_POST['channel']) || empty(trim($_POST['channel']))) {
            board::error("Не выбран канал");
        } elseif ( ! filter_var($_POST['channel'], FILTER_VALIDATE_URL)) {
            board::error("Канал должен быть корректным URL адресом");
        }
        $row = sql::getRow("SELECT `confirmed` FROM `streams` WHERE `user_id` = ?", [user::self()->getId()]);
        if ($row) {
            if ($row['confirmed'] == 1) {
                board::error("Вы уже добавляли стрим");
            } else {
                board::error("Ваш стрим еще не одобрен. Ожидайте одобрение администратора.");
            }
        }
        sql::run(
          "INSERT INTO `streams` (`user_id`, `channel`, `data`, `confirmed`, `dateCreate`, `dateUpdate`) VALUES (?, ?, ?, ?, ?, ?)",
          [
            user::self()->getId(),
            $_POST['channel'],
            '',
            0,
            time::mysql(),
            time::mysql(),
          ]
        );
        board::success("Стрим добавлен. Ожидайте одобрение администратора.");
    }

    //Пользователь снова запустил стрим.
    public static function startStreamAgain(): void
    {
        //Проверка что админ одобрил авто добавление стрима пользователем
        if (user::self()->getVar("auto_approval_stream")['val'] == 1) {
            streamcheck::userUpdateStream();
        }
    }

    public static function show()
    {
        // Данные моего стрима
        $my_stream = sql::getRow("SELECT * FROM `streams` WHERE `user_id` = ?", [user::self()->getId()]);
        if ($my_stream) {
            $my_stream['data'] = json_decode($my_stream['data'], true);
        }
        tpl::addVar('my_stream', $my_stream);
        tpl::display("stream.html");
    }

    // Когда открывают страницу со стримом
    public static function getUserStream($userName): void
    {
        $user = user::getUserByName($userName);
        if ( ! $user) {
            redirect::location("/stream");
        }
        $userStream = sql::getRow("SELECT * FROM `streams` WHERE `user_id` = ?", [$user->getId()]);
        if ($userStream) {
            $userStream['data'] = json_decode($userStream['data'], true);
        }
        tpl::addVar('user', $user);
        tpl::addVar('stream', $userStream);
        tpl::display("userStream.html");
    }

    //Пользователь удаляет свой стрим
    public static function userDeleteStream(): void
    {
        //Проверка что у пользователя есть стрим
        $stream = sql::getRow("SELECT `id`, `data` FROM `streams` WHERE `user_id` = ?", [user::self()->getId()]);
        if ($stream) {
            if ($stream['data'] == null) {
                board::error("Ничего не произошло");
            }
            sql::run("UPDATE `streams` SET `data` = NULL WHERE `user_id` = ?", [user::self()->getId()]);
            board::success("Стрим удален");
        }
        board::error("У Вас не было зарегистрировано трансляций");

    }

}