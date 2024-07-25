<?php

namespace Ofey\Logan22\model\forum;

use Ofey\Logan22\model\db\fdb;

class forumStruct {

    private bool $showForumSphereMainPage;
    private bool $enabled;
    private string $engine;
    private string $sort;
    private int $elements = 20;
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $name;
    private string $url;

    public function __construct($json)
    {
        $config = json_decode($json, true);
        $this->showForumSphereMainPage = filter_var($config['showForumSphereMainPage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->name = $config['name'];
        $this->enabled = filter_var($config['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $this->engine = $config['engine'];
        $this->sort = $config['sort'];
        $this->elements =  $config['elements'] ?: 20;
        $this->url = $config['url'] ?? "https://";
        if($this->enabled){
            fdb::connect($this->host, $this->port, $this->user, $this->password, $this->name);
        }
    }

    public function isShowForumSphereMainPage(): bool
    {
        return $this->showForumSphereMainPage;
    } 

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function getElements(): int
    {
        return $this->elements;
    }

    public function lastMessage()
    {
        return $this->getXenforo();
    }

    public function isError(): bool
    {
        return fdb::isError();
    }

    public function isNotError(): bool
    {
        return !fdb::isError();
    }

    public function getMessageError(): string
    {
        return fdb::getMessageError();
    }

    private function stripBBCode($text_to_search) {
        $pattern = '|[[\/\!]*?[^\[\]]*?]|si';
        $replace = '';
        return preg_replace($pattern, $replace, $text_to_search);
    }

    function getAvatarUrl($userId, $avatarDate, $gravatarEmail = null, $size = 'm') {
        $baseUrl = 'http://xenforo/data/avatars';  // Замените на базовый URL вашего форума
        $defaultAvatarUrl = 'uploads/avatar/none.jpeg'; // Замените на URL аватара по умолчанию

        if ($avatarDate) {
            // В XenForo, файлы могут быть разделены по папкам в зависимости от ID пользователя
            $path = intval($userId / 1000);  // Вычисление номера папки на основе ID пользователя
            return "{$baseUrl}/{$size}/{$path}/{$userId}.jpg?{$avatarDate}";
        } elseif ($gravatarEmail) {
            $default = urlencode($defaultAvatarUrl);
            $gravUrl = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($gravatarEmail))) . "?d=$default&s=" . ($size === 'l' ? 192 : 96);
            return $gravUrl;
        } else {
            return $defaultAvatarUrl;
        }
    }



    private function getXenforo(): array|string
    {
        if (self::isError()) {
            return self::getMessageError();
        }
        $rows = fdb::getRows("SELECT
                                        post.post_id,
                                        post.message AS message,
                                        post.post_date,
                                        thread.title AS title,
                                        thread.thread_id,
                                        user.username,
                                        user.user_id,
                                        user.avatar_date,
                                        user.gravatar,
                                        CONCAT('forums/threads/', thread.thread_id, '/posts/', post.post_id) AS post_url
                                    FROM
                                        xf_post AS post
                                    JOIN
                                        xf_thread AS thread ON post.thread_id = thread.thread_id
                                    JOIN
                                        xf_user AS user ON post.user_id = user.user_id
                                    WHERE
                                        thread.discussion_state = 'visible' AND
                                        post.message_state = 'visible'
                                    ORDER BY
                                        post.post_date DESC
                                    LIMIT ?;", [self::getElements()]);
        foreach($rows as &$row) {
            $row['message'] = $this->stripBBCode($row['message']);
            $row['avatar'] = $this->getAvatarUrl($row['user_id'], $row['avatar_date'], $row['gravatar']);
        }
        return $rows;

        //Тут по последним темам
//        return fdb::getRows("SELECT
//                                        thread.thread_id,
//                                        thread.title AS title,
//                                        post.message AS message,
//                                        user.username,
//                                        user.gravatar,
//                                        thread.last_post_date,
//                                        CONCAT('forums/threads/', thread.thread_id) AS thread_url
//                                    FROM
//                                        xf_thread AS thread
//                                    JOIN
//                                        xf_post AS post ON thread.first_post_id = post.post_id
//                                    JOIN
//                                        xf_user AS user ON thread.user_id = user.user_id
//                                    WHERE
//                                        thread.discussion_state = 'visible'
//                                    ORDER BY
//                                        thread.last_post_date DESC
//                                    LIMIT ?;", [self::getElements()]);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPort(): string
    {
        return $this->port;
    }


}