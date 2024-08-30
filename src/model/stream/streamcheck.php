<?php

namespace Ofey\Logan22\model\stream;

use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\stream\streamDetect;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class streamcheck
{

    //Автоматически проверяем работает ли стрим
    static function autoCheckLiveStream(): void
    {
        $currentTime = time::mysql();
        $rows        = sql::getRows("SELECT * FROM `streams` WHERE `confirmed` = 1 AND `auto_check_date` > ?", [$currentTime]);
        foreach ($rows as $row) {
            $data       = new streamDetect($row['channel']);
            $data       = $data->get();
            $channel_id = $data->channel_id;
            $is_live    = $data->is_live;
            if ($is_live) {
                $title       = $data->title;
                $username    = $data->username;
                $avatar_url  = $data->avatar_url;
                $channel_url = $data->channel_url;
                $video_url   = $data->video_url;
                $video_id    = $data->video_id;
                $arr         = json_encode([
                  'username'    => $username,
                  'title'       => $title,
                  'avatar_url'  => $avatar_url,
                  'channel_url' => $channel_url,
                  'video_url'   => $video_url,
                  'video_id'    => $video_id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                sql::run(
                  "UPDATE `streams` SET `confirmed` = 1, `channel_id` = ?, `is_live` = ?, `data` = ? WHERE `id` = ?",
                  [$channel_id, $is_live, $arr, $row['id']]
                );
            } else {
                sql::run("UPDATE `streams` SET `is_live` = 0 WHERE `id` = ?", [$row['id']]);
            }
        }
    }

    static function userUpdateStream($userId = null): void
    {
        if($userId === null) {
            $userId = user::self()->getId();
        }



        //Проверка что у пользователя есть такой стрим
        $row = sql::getRow("SELECT * FROM `streams` WHERE user_id = ?", [$userId]);
        if ( ! $row) {
            board::error("Пользователь не имеет стрима");

            return;
        }
        if ($row['confirmed'] == 0) {
            board::error("Ваш стрим еще не одобрен. Ожидайте одобрение администратора.");
        }

        $channel    = $row['channel'];
        $data       = new streamDetect($channel);
        $data       = $data->get();
        $channel_id = $data->channel_id;
        $is_live    = $data->is_live;
        if ( ! $is_live) {
            board::error("Мы не обнаружили что ваш стрим сейчас онлайн");
        }
        if ($is_live) {
            $title       = $data->title;
            $username    = $data->username;
            $avatar_url  = $data->avatar_url;
            $channel_url = $data->channel_url;
            $video_url   = $data->video_url;
            $video_id    = $data->video_id;
            $arr         = json_encode([
              'username'    => $username,
              'title'       => $title,
              'avatar_url'  => $avatar_url,
              'channel_url' => $channel_url,
              'video_url'   => $video_url,
              'video_id'    => $video_id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            sql::run(
              "UPDATE `streams` SET `confirmed` = 1, `channel_id` = ?, `is_live` = ?, `data` = ? WHERE `id` = ?",
              [$channel_id, $is_live, $arr, $row['id']]
            );
        }
        board::success("Вы обновили данные стрима");
    }

}