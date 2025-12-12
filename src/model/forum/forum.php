<?php
/**
 * Created by Logan22
 * Github -> https://github.com/Cannabytes/SphereWeb
 * Date: 16.09.2022 / 17:51:33
 */

namespace Ofey\Logan22\model\forum;

use Exception;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\component\lang\lang;


class forum {

    private static null|bool|forumStruct $instance = null;

    public static function get()
    {
        if(\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()){
            return self::$instance = new forumStruct("{}");
        }
        if (self::$instance === null) {
            $configData = sql::getRow("SELECT * FROM `settings` WHERE `key` = '__config_forum__'");
            if(!$configData){
                return self::$instance;
            }
            self::$instance = new forumStruct($configData['setting']);
        }
        return self::$instance;
    }

    //Сохранение конфигурации
    public static function saveConfig()
    {
        $post = json_encode($_POST);
        if(!$post){
            board::error("Ошибка парсинга JSON");
        }
        sql::sql("DELETE FROM `settings` WHERE `key` = '__config_forum__' AND serverId = ? ", [
            0,
        ]);
        sql::run("INSERT INTO `settings` (`key`, `setting`, `serverId`, `dateUpdate`) VALUES ('__config_forum__', ?, ?, ?)",[
            $post,
            0,
            time::mysql(),
        ]);
        if(sql::$error){
            board::error("Ошибка сохранения в БД: " . sql::$error);
        }
        board::success(lang::get_phrase(581));
    }

    private static string $engine = '';
    private static string $url    = '';

    /**
     * Название движка форума
     *
     * @return string
     */
    public static function get_engine(): string {
        if(self::$engine == '') {
//            self::$engine = FORUM_ENGINE;
        }
        return self::$engine;
    }

    public static function get_url($link = ''): string {
        if(self::$url == '') {
            self::$url = "/";
        }
        return self::$url;
    }


    /**
     * Создание ссылки на форум
     */
    public static function get_link($forum): string {
        return match (forum::get()->getEngine()) {
            'xenforo' => self::link_xenforo($forum),
            'ipb' => self::link_ipb($forum),
            'sphere' => self::link_sphere($forum),
            default => 'No Link',
        };
    }

    private static function link_xenforo($forum): string {
        $thread_id = $forum['thread_id'] ?? '';
        $post_id = $forum['post_id'] ?? '';
        $url = forum::get()->getUrl();
        return sprintf("%s/index.php?threads/%s/#post-%s", $url, $thread_id, $post_id);
    }

    private static function link_ipb($forum): string {
        $id = $forum['id'] ?? '';
        $title = $forum['title_seo'] ?? '';
        return sprintf("%s/index.php?/topic/%s-%s/", self::get_url(), $id, $title);
    }

    private static function link_sphere($forum): string {
        $section_id = $forum['section_id'] ?? '';
        $topic_id = $forum['topic_id'] ?? '';
        $id = $forum['last_post_user_id'] ?? '';
        return sprintf("%s/threads/%s/%s#%s", "/forum", $section_id, $topic_id, $id);
    }

    public static function user_avatar($user_id): string {
        $image = match (self::get_engine()) {
            'xenforo' => sprintf("%s/data/avatars/m/0/%d.jpg", self::get_url(), $user_id),
            'ipb' => 'uploads/avatar/none.jpeg',
            default => 'No Link',
        };
        if (!file_exists($image)) {
            return 'uploads/avatar/none.jpeg';
        }
        return $image;
    }
 
}