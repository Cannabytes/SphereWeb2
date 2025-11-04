<?php

namespace Ofey\Logan22\model\forum;

use Ofey\Logan22\model\db\fdb;
use Ofey\Logan22\model\db\sql;


class forumStruct {

    private bool $showForumSphereMainPage;
    private bool $enabled = false;
    private string $engine = 'xenforo';
    private string $sort = 'desc' ;
    private int $elements = 20;
    private string $host = '127.0.0.1';
    private string $port = '80';
    private string $user = 'xenforo';
    private string $password = '';
    private string $name = 'xenforo';
    private string $url = "https://";

    public function __construct($json)
    {
        if(\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()){
            $this->showForumSphereMainPage = true;
            $this->url = "https://";
            $this->engine = 'xenforo';
            $this->enabled = true;
            return;
        }
        $config = json_decode($json, true);
        $this->showForumSphereMainPage = filter_var($config['showForumSphereMainPage'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $this->host = $config['host'] ?? '127.0.0.1';
        $this->port = $config['port'] ?? '3306';
        $this->user = $config['user'] ?? 'xenforo';
        $this->password = $config['password'] ?? '';
        $this->name = $config['name'] ?? 'xf_db';
        $this->enabled = filter_var($config['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $this->engine = $config['engine'] ?? 'xenforo';
        $this->sort = $config['sort'] ?? 'messages';
        $this->elements =  $config['elements'] ?: 20;
        $this->url = $config['url'] ?? "https://";
        if($this->enabled){
            if ($this->engine != 'xenforo') {
                return;
            }
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
        
    public function lastThreads(int|null $n = null)
    {
        if (\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()){
            $data = include "src/component/emulation/data/forumLastMessage.php";
            return array_slice($data, -$n);
        }

        if ($n != null) {
            $this->elements = $n;
        }

        if ($this->engine == 'sphere') {
            return $this->getSphereForumLastThreads();
        }else{
            return $this->getXenforoLastThreads();
        }
    }

    public function lastMessage(int|null $n = null)
    {
        if (\Ofey\Logan22\controller\config\config::load()->enabled()->isEnableEmulation()){
            $data = include "src/component/emulation/data/forumLastMessage.php";
            return array_slice($data, -$n);
        }
        if ($n != null) {
            $this->elements = $n;
        }

        if ($this->engine == 'sphere') {
            return $this->getSphereForumLastPosts();
        }else{
            return $this->getXenforoLastPosts();
        }
    }

    private function getSphereForumLastPosts(): array|string
    {
        if (self::isError()) {
            return self::getMessageError();
        }
        $rows = sql::getRows("SELECT
                                        p.id AS post_id,
                                        SUBSTRING(p.content, 1, 360) AS message,
                                        p.created_at AS post_date,
                                        t.title AS title,
                                        t.id AS thread_id,
                                        u.name AS username,
                                        u.id AS user_id,
                                        u.avatar AS avatar_date, 
                                        CONCAT('forums/threads/', t.id, '/posts/', p.id) AS post_url
                                    FROM
                                        forum_posts AS p
                                    JOIN
                                        forum_threads AS t ON p.thread_id = t.id
                                    JOIN
                                        users AS u ON p.user_id = u.id
                                    WHERE
                                        t.is_approved = 1 OR t.is_approved IS NULL
                                    ORDER BY
                                        p.created_at DESC
                                    LIMIT ?;", [self::getElements()]);

        foreach ($rows as &$row) {
            $row['message'] = $this->stripBBCode($row['message']);
            $row['avatar'] = $row['avatar'] ? '/uploads/avatar/' . $row['avatar'] : '/uploads/avatar/none.jpeg';
        }

        return $rows;
    }

    private function getSphereForumLastThreads(): array|string
    {
        if (self::isError()) {
            return self::getMessageError();
        }
        
        $limit = intval(self::getElements());
        $rows = sql::getRows("SELECT
                                       t.id,
                                       t.title,
                                       t.created_at,
                                       t.updated_at,
                                       t.user_id,
                                       (SELECT content FROM forum_posts WHERE id = t.first_message_id LIMIT 1) AS message,
                                       u.name AS username,
                                       u.avatar,
                                       c.name AS category_name,
                                       (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.id) AS posts_count
                                   FROM
                                       forum_threads AS t
                                   JOIN
                                       users AS u ON t.user_id = u.id
                                   JOIN
                                       forum_categories AS c ON t.category_id = c.id
                                   WHERE
                                       (c.is_moderated = 0 OR t.is_approved = 1)
                                       AND c.is_hidden = 0
                                   ORDER BY
                                       t.created_at DESC
                                   LIMIT " . $limit);

        // Обрабатываем результаты
        foreach ($rows as &$row) {
            // Очищаем сообщение от BBCode и HTML, обрезаем до 140 символов
            $row['message'] = $this->stripBBCode(mb_substr($row['message'] ?? '', 0, 140));
            
            // Формируем путь к аватару
            $row['avatar'] = $row['avatar'] ? '/uploads/avatar/' . $row['avatar'] : '/uploads/avatar/none.jpeg';
            
            $transliteratedTitle = $this->transliterateToEn($row['title']);
            $row['thread_url'] = '/forum/topic/' . $transliteratedTitle . '.' . $row['id'];
            
            // Добавляем алиасы для совместимости с XenForo структурой
            $row['thread_id'] = $row['id'];
            $row['last_post_date'] = $row['updated_at'];
        }

        return $rows;
    }
    
    /**
     * Транслитерация текста в латиницу (копия метода из sphere_forum/custom_twig.php)
     */
    private function transliterateToEn($input): string
    {
        if (is_null($input)) {
            return '';
        }

        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo',
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
            'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'і' => 's', 'ї' => '', 'є' => 'e',
            'І' => 'S', 'Ї' => '', 'Є' => 'E',
            ' ' => '-', '/' => '-', '\\' => '-', '_' => '-', ',' => '', '.' => '', '!' => '', '?' => '',
            ':' => '', ';' => '', '"' => '', "'" => '', '(' => '', ')' => '', '[' => '', ']' => '',
            '{' => '', '}' => '', '<' => '', '>' => '', '=' => '', '+' => '', '*' => '', '&' => '',
            '%' => '', '$' => '', '#' => '', '@' => '', '~' => '', '`' => '', '|' => ''
        ];

        $result = strtr($input, $translitMap);
        
        // Убираем все остальные не-ASCII символы и множественные дефисы
        $result = preg_replace('/[^A-Za-z0-9\-]/', '', $result);
        $result = preg_replace('/-+/', '-', $result);
        $result = trim($result, '-');
        
        return $result ?: 'topic';
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
        if ($text_to_search === null) {
            return '';
        }

        // Убираем BBCode-подобные теги (например [b], [/url], [img] и т.д.)
        $pattern = '|\[[\/\!]*?[^\[\]]*?\]|si';
        $text = preg_replace($pattern, '', $text_to_search);

        // Убираем HTML-теги
        $text = strip_tags($text);

        // Декодируем HTML-сущности
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Нормализуем пробелы и обрезаем
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $text;
    }

    function getAvatarUrl($userId, $avatarDate, $gravatarEmail = null, $size = 'm') {
        $defaultAvatar = '/uploads/avatar/none.jpeg';

        if (!$avatarDate) {
            return $defaultAvatar;
        }

        $pathFolder = intval($userId / 1000);
        $projectRoot = dirname(__DIR__, 3);
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];

        foreach ($extensions as $ext) {
            $filePath = $projectRoot . "/data/avatars/{$size}/{$pathFolder}/{$userId}.{$ext}";
            if (file_exists($filePath)) {
                return "/data/avatars/{$size}/{$pathFolder}/{$userId}.{$ext}?{$avatarDate}";
            }
        }

        return $defaultAvatar;
    }

    private function getXenforoLastPosts(){
        if (self::isError()) {
            return self::getMessageError();
        }
        $rows = fdb::getRows("SELECT
                                        post.post_id,
                                        SUBSTRING(post.message, 1, 360) AS message,
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
                                    LIMIT ?;
                                    ", [self::getElements()]);
        foreach($rows as &$row) {
            $row['message'] = $this->stripBBCode($row['message']);
            $row['avatar'] = $this->getAvatarUrl($row['user_id'], $row['avatar_date'], $row['gravatar']);
        }
        return $rows;
    }

    private function getXenforoLastThreads(): array|string
    {
        if (self::isError()) {
            return self::getMessageError();
        }

       $rows = fdb::getRows("SELECT
                                       thread.thread_id,
                                       thread.title AS title,
                                       post.message AS message,
                                       user.username,
                                       user.user_id,
                                       user.avatar_date,
                                       user.gravatar,
                                       thread.last_post_date,
                                       CONCAT('forums/threads/', thread.thread_id) AS thread_url
                                   FROM
                                       xf_thread AS thread
                                   JOIN
                                       xf_post AS post ON thread.first_post_id = post.post_id
                                   JOIN
                                       xf_user AS user ON thread.user_id = user.user_id
                                   WHERE
                                       thread.discussion_state = 'visible'
                                   ORDER BY
                                       thread.last_post_date DESC
                                   LIMIT ?;", [self::getElements()]);
        foreach($rows as &$row) {
            $row['avatar'] = $this->getAvatarUrl($row['user_id'], $row['avatar_date'], $row['gravatar']);
        }
        return $rows;
                                   
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