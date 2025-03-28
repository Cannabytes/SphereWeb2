<?php

namespace Ofey\Logan22\controller\stream;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\request\url;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\admin\telegram;
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
        $currentDate = date('Y-m-d H:i:s');
        $streams = sql::getRows("SELECT * FROM `streams` 
                             WHERE confirmed = 1 
                             AND (auto_check_date IS NULL OR auto_check_date >= '$currentDate')
                             ORDER BY `dateUpdate` DESC;");
        foreach($streams AS &$stream){
            $stream['platform'] = self::stream_get_platform($stream['channel']);
            $stream['src'] = self::getSrc($stream['channel']);
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

        $link = self::stream_get_platform($_POST['channel']);
        if($link == 'unknown'){
            board::error("Поддерживается только стримы сайтов Youtube и Twitch, Kick, Trovo");
        }

        $rows = sql::getRows("SELECT * FROM `streams` WHERE `user_id` = ?", [user::self()->getId()]);
        if ($rows) {
            foreach($rows as $row) {
                if ($row['confirmed'] == 0) {
                    board::error("Ваш стрим ещё не был одобрен, по этому нельзя добавлять новую ссылку. Ожидайте одобрение администратора.");
                }
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

        if (\Ofey\Logan22\controller\config\config::load()->notice()->isAddStream()) {
            $template = lang::get_other_phrase(\Ofey\Logan22\controller\config\config::load()->notice()->getNoticeLang(), 'notice_add_stream');
            $msg = strtr($template, [
                '{email}' => user::self()->getEmail(),
                '{link}' => url::host("/admin/stream"),
            ]);
            telegram::sendTelegramMessage($msg);
        }

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

    public static function getSrc($link)
    {
        $embedUrl = $link;

        if (str_contains($link, 'youtube.com') || str_contains($link, 'youtu.be')) {
            $videoId = '';
            if (preg_match('/youtu\.be\/([^\?\/]+)/', $link, $matches)) {
                $videoId = $matches[1];
            } elseif (preg_match('/v=([^&]+)/', $link, $matches)) {
                $videoId = $matches[1];
            } elseif (preg_match('/embed\/([^\/\?&]+)/', $link, $matches)) {
                $videoId = $matches[1];
            }
            if ($videoId !== '') {
                $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
            }
        } elseif (str_contains($link, 'twitch.tv')) {
            $parsedUrl = parse_url($link);
            $path      = trim($parsedUrl['path'], '/');
            $pathParts = explode('/', $path);
            if ($pathParts[0] === 'videos' && isset($pathParts[1])) {
                $videoId  = $pathParts[1];
                $embedUrl = 'https://player.twitch.tv/?video=' . $videoId;
            } else {
                $channelName = $pathParts[0];
                $embedUrl    = 'https://player.twitch.tv/?channel=' . $channelName . '&parent=' . $_SERVER['HTTP_HOST'];
            }
        } elseif (str_contains($link, 'kick.com')) {
            $parsedUrl = parse_url($link);
            $path      = trim($parsedUrl['path'], '/');
            if (!empty($path)) {
                $embedUrl = 'https://player.kick.com/' . $path;
            }
        }
        return $embedUrl;
    }

    public static function stream_get_platform($link)
    {
        if ($link === null || $link === '') {
            return 'unknown';
        }

        $link = (string)$link;

        if (str_contains($link, 'youtube.com') || str_contains($link, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($link, 'twitch.tv')) {
            return 'twitch';
        }
        if (str_contains($link, 'kick.com')) {
            return 'kick';
        }
        if (str_contains($link, 'trovo.live')) {
            return 'trovo';
        }
        return 'unknown';
    }

    // Удаление собственного стрима пользователем
    static function userDeleteStream(): void
    {
        $streamId = $_POST['id'] ?? null;
        if($streamId == null){
            board::error("Не выбран стрим");
        }
        $getStream = "SELECT * FROM `streams` WHERE `id` = ?";
        $streamData = sql::getRow($getStream, [$streamId]);
        if ($streamData == null) {
            board::error("Нет данных о стриме");
        }
        if(user::self()->getId() == $streamData['user_id']){
            sql::run("DELETE FROM `streams` WHERE `id` = ?", [$streamId]);
            board::success("Стрим удален");
        }else{
            board::error("Вы не можете удалить этот стрим");
        }
    }

}