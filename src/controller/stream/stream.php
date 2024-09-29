<?php

namespace Ofey\Logan22\controller\stream;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
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
        $currentDate = date('Y-m-d H:i:s'); // Получаем текущую дату и время
        $streams = sql::getRows("SELECT * FROM `streams` 
                             WHERE confirmed = 1 
                             AND (auto_check_date IS NULL OR auto_check_date >= '$currentDate')
                             ORDER BY `dateUpdate` DESC;");
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
            if ($row['confirmed'] == 0) {
                board::error("Ваш стрим ещё не был одобрен, по этому нельзя добавлять новую ссылку. Ожидайте одобрение администратора.");
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

    public static function show()
    {
        // Данные моего стрима
        $my_stream = sql::getRow("SELECT * FROM `streams` WHERE `user_id` = ?", [user::self()->getId()]);
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

}