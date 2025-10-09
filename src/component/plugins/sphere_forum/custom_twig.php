<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Exception;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_category;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_post;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_thread;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumClan;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumModerator;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class custom_twig
{

    private const BUFF_LIKE_LIST = [
        1085,
        1204,
        1036,
        1086,
        1388,
        1389,
        1062,
        1068,
        1043,
        1077,
        1363,
        1413,
        1461,
        "0275",
        "0274",
        "0271",
        "0272",
        "0276",
        "0277",
        "0307",
        "0310",
        "0365",
        "0268",
        "0269",
        "0349",
    ];

    private static array $settingsCache = [];

    public function getLikeBuffList(): array
    {
        return self::BUFF_LIKE_LIST;
    }

    public function getStatisticForum(): array
    {
        $topics = sql::getValue("SELECT COUNT(*) AS count FROM `forum_threads`;");
        $messages = sql::getValue("SELECT COUNT(*) AS count FROM `forum_posts`;");
        return [
            "topics" => $topics ?? 0,
            "messages" => $messages ?? 0
        ];
    }

    public function getCategoriesForum(): array {
        // Получаем категории, сортируя их по sort_order
        $forum_categories = sql::getRows(
            "SELECT * FROM `forum_categories` ORDER BY `sort_order` ASC"
        );
        $categories = [];
        foreach ($forum_categories as $category) {
            $categories[] = new forum_category($category);
        }

        // Связываем категории и подкатегории
        foreach ($categories as $category) {
            $category->loadSubcategories($categories);
        }

        foreach ($categories as $i => $category) {
            if ($category->getParentId() != null) {
                unset($categories[$i]);
            }
        }

        return $categories;
    }

    /**
     * Получает список последних созданных тем на форуме
     *
     * @param int $limit Количество тем для вывода
     * @return array Массив последних тем
     */
    public function getLastThreadsForum(int $limit = 5): array {
        $threadsQuery = sql::getRows("
        SELECT 
            t.*,
            u.name as author_name,
            u.avatar as author_avatar,
            c.name as category_name,
            c.is_moderated,
            c.can_view_topics,
            (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.id) as posts_count
        FROM forum_threads t
        JOIN users u ON t.user_id = u.id
        JOIN forum_categories c ON t.category_id = c.id
        WHERE 
            (c.is_moderated = 0 OR t.is_approved = 1)
            AND c.is_hidden = 0
        ORDER BY t.created_at DESC
        LIMIT ?
    ", [$limit]);

        $threads = [];
        foreach ($threadsQuery as $thread) {
            // Проверяем наличие непрочитанных сообщений
            $hasUnread = false;
            if (user::self()->isAuth()) {
                $lastRead = sql::getRow(
                    "SELECT last_read_post_id 
                FROM forum_user_thread_tracks 
                WHERE user_id = ? AND thread_id = ?",
                    [user::self()->getId(), $thread['id']]
                );

                if (!$lastRead) {
                    $hasUnread = true;
                } else {
                    $newerPosts = sql::getValue(
                        "SELECT EXISTS(
                        SELECT 1 FROM forum_posts 
                        WHERE thread_id = ? 
                        AND id > ? 
                        LIMIT 1
                    )",
                        [$thread['id'], $lastRead['last_read_post_id']]
                    );
                    $hasUnread = (bool)$newerPosts;
                }
            }

            // Проверяем права на просмотр темы
            $canView = true;
            if (!user::self()->isAdmin() && 
                !ForumModerator::isUserModerator(user::self()->getId(), $thread['category_id'])) {
                if (!(bool)$thread['can_view_topics']) {
                    // Если пользователь не автор темы - нет прав на просмотр
                    if ($thread['user_id'] !== user::self()->getId()) {
                        $canView = false;
                    }
                }
            }

            $threads[] = [
                'id' => (int)$thread['id'],
                'title' => $thread['title'],
                'created_at' => $thread['created_at'],
                'views' => (int)$thread['views'],
                'replies' => (int)$thread['replies'],
                'posts_count' => (int)$thread['posts_count'],
                'is_closed' => (bool)$thread['is_closed'],
                'is_pinned' => (bool)$thread['is_pinned'],
                'hasUnread' => $hasUnread,
                'canView' => $canView,
                'author' => [
                    'id' => (int)$thread['user_id'],
                ],
                'category' => [
                    'id' => (int)$thread['category_id'],
                    'name' => $thread['category_name']
                ],
                'last_reply' => [
                    'user_id' => (int)$thread['last_reply_user_id'],
                    'time' => $thread['updated_at']
                ]
            ];
        }

        return $threads;
    }

    public function getLastMessagesForum(): array {
        $messagesRows = sql::getRows("
        SELECT p.*, 
               t.title AS thread_title,
               t.user_id AS thread_author_id,
               c.id AS category_id,
               c.can_view_topics
        FROM forum_posts p 
        JOIN forum_threads t ON p.thread_id = t.id 
        JOIN forum_categories c ON t.category_id = c.id 
        WHERE p.id IN (
            SELECT MAX(fp.id) 
            FROM forum_posts fp 
            JOIN forum_threads ft ON fp.thread_id = ft.id 
            JOIN forum_categories fc ON ft.category_id = fc.id
            WHERE fc.is_hidden = 0
            GROUP BY fp.thread_id
        ) 
        AND (
            c.is_moderated = 0 
            OR t.is_approved = 1
        )
        AND c.is_hidden = 0
        ORDER BY p.id DESC 
        LIMIT 5
    ");

        $messages = [];
        foreach ($messagesRows as $message) {
            $post = new forum_post($message);

            // Добавляем проверку на непрочитанные сообщения
            if (user::self()->isAuth()) {
                $lastRead = sql::getRow(
                    "SELECT last_read_post_id 
                FROM forum_user_thread_tracks 
                WHERE user_id = ? AND thread_id = ?",
                    [user::self()->getId(), $post->getThreadId()]
                );

                $post->hasUnread = !$lastRead || $post->getId() > $lastRead['last_read_post_id'];
            } else {
                $post->hasUnread = false;
            }

            // Проверяем права на просмотр темы
            $canView = true;
            if (!user::self()->isAdmin() && 
                !ForumModerator::isUserModerator(user::self()->getId(), $message['category_id'])) {
                if (!(bool)$message['can_view_topics']) {
                    // Если пользователь не автор темы - нет прав на просмотр
                    if ($message['thread_author_id'] !== user::self()->getId()) {
                        $canView = false;
                    }
                }
            }

            $post->canView = $canView;
            $messages[] = $post;
        }
        return $messages;
    }

    public function transliterateToEn($input): string
    {
        // Если входное значение null, возвращаем пустую строку
        if (is_null($input)) {
            return '';
        }

        // Таблица соответствий русских букв и английского транслита
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
            ' ' => '_'
        ];

        // Меняем буквы и пробелы
        $result = strtr($input, $translitMap);

        // Убираем лишние символы, превращая их в дефисы
        $result = preg_replace('/[^a-zA-Z0-9_]/', '-', $result);

        // Убираем двойные дефисы (потому что зачем они тебе?)
        $result = preg_replace('/-+/', '-', $result);
        return trim( mb_strtolower($result), '-'); // Обрезаем дефисы по краям
    }

    /**
     * Получает лайки для поста для отображения в шаблоне
     * @param int $postId ID поста
     * @return array Массив с лайками
     */
    public function getPostLikes(int $postId): array {
        return sql::getRows(
            "SELECT l.*, u.name as user_name 
        FROM forum_post_likes l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE l.post_id = ? 
        ORDER BY l.created_at DESC",
            [$postId]
        );
    }

    /**
     * Проверяет, является ли пользователь модератором категории
     * @param int $userId ID пользователя
     * @param int|null $categoryId ID категории или null для всех категорий
     * @return bool
     */
    public function isModerator(int $userId, ?int $categoryId = null): bool {
        if($categoryId === null){
            $moderator = sql::getRow(
                "SELECT * FROM forum_moderators WHERE user_id = ?",
                [$userId]
            );
            return (bool)$moderator;
        }
        $moderator = sql::getRow(
            "SELECT * FROM forum_moderators 
        WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
            [$userId, $categoryId]
        );
        return (bool)$moderator;
    }

    /**
     * Проверяет наличие конкретного права у модератора
     * @param int $userId ID пользователя
     * @param int|null $categoryId ID категории
     * @param string $permission Название права
     * @return bool
     */
    public function hasModeratorPermission(int $userId, ?int $categoryId, string $permission): bool {
        $moderator = sql::getRow(
            "SELECT $permission FROM forum_moderators 
            WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
            [$userId, $categoryId]
        );
        return (bool)($moderator[$permission] ?? false);
    }


    public function getAwaitingModerationThreads(): array {
        $userId = user::self()->getId();
        $isAdmin = user::self()->isAdmin();

        // Базовый запрос с учетом иерархии категорий
        $sql = "
        SELECT 
            t.id,
            t.title,
            t.created_at,
            t.category_id,
            c.name as category_name,
            c.parent_id, 
            u.id as author_id,
            u.name as author_name
        FROM forum_threads t
        JOIN forum_categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE (c.is_moderated = 1 OR c.is_moderated = '1')
        AND (t.is_approved = 0 OR t.is_approved = '0' OR t.is_approved IS NULL)
    ";

        // Если это не админ - добавляем проверку прав модератора с учётом иерархии категорий
        if (!$isAdmin) {
            $sql .= " AND (
            EXISTS (
                SELECT 1 FROM forum_moderators m
                WHERE m.user_id = ? 
                AND (
                    m.category_id IS NULL -- глобальный модератор
                    OR m.category_id = t.category_id -- прямой модератор категории
                    OR m.category_id = c.parent_id -- модератор родительской категории
                    OR EXISTS ( -- проверка на родительские категории
                        SELECT 1 FROM forum_categories pc 
                        WHERE pc.id = m.category_id 
                        AND c.parent_id = pc.id
                    )
                )
            )
        )";
            $params = [$userId];
        } else {
            $params = [];
        }

        $sql .= " ORDER BY t.created_at DESC";

        $threads = sql::getRows($sql, $params);

        $result = [];
        foreach ($threads as $thread) {
            $result[] = [
                'id' => $thread['id'],
                'title' => $thread['title'],
                'author' => [
                    'id' => $thread['author_id'],
                    'name' => $thread['author_name']
                ],
                'created_at' => $thread['created_at'],
                'category' => [
                    'id' => $thread['category_id'],
                    'name' => $thread['category_name']
                ]
            ];
        }

        return $result;
    }

    /**
     * Возвращает настройки отображения профиля пользователя на форуме
     * @param int $userId ID пользователя, чьи настройки нужно получить
     * @return array Массив настроек
     */
    public function getForumUserSettings(int $userId): array {
        // Проверяем наличие данных в кэше
        if (isset(self::$settingsCache[$userId])) {
            return self::$settingsCache[$userId];
        }

        // Если в кэше нет, получаем из БД
        $settings = sql::getRow(
            "SELECT val FROM user_variables WHERE user_id = ? AND var = 'forum_display_settings'",
            [$userId]
        );

        // Формируем настройки
        $userSettings = !empty($settings) ? json_decode($settings['val'], true) : [
            'showCharacters' => true,
            'showPvPPK' => true,
            'showGameTime' => true,
            'showFlagCountry' => true,
        ];

        // Сохраняем в кэш
        self::$settingsCache[$userId] = $userSettings;

        return $userSettings;
    }


    /**
     * Проверяет, разрешил ли пользователь показывать определенную информацию в своем профиле
     * @param int $userId ID пользователя
     * @param string $setting Название настройки (showCharacters/showPvPPK/showGameTime)
     * @return bool
     */
    public function isForumSettingEnabled(int $userId, string $setting): bool {
        $settings = $this->getForumUserSettings($userId);
        return $settings[$setting] ?? true; // По умолчанию true, если настройка не задана
    }

    /**
     * Сохраняет настройки и обновляет кэш
     * @param int $userId ID пользователя
     * @param array $settings Массив настроек
     * @return bool
     */
    public function saveForumUserSettings(int $userId, array $settings): bool {
        try {
            $user = user::getUserId($userId);
            $user->addVar('forum_display_settings', json_encode($settings));

            // Обновляем кэш
            self::$settingsCache[$userId] = $settings;

            return true;
        } catch (Exception $e) {
            error_log("Error saving forum settings: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Получает количество непрочитанных уведомлений форума для пользователя
     *
     * @return int Количество непрочитанных уведомлений
     */
    public function getForumUnreadNotificationsCount(): int {
        if (!user::self()->isAuth()) {
            return 0;
        }

        return (int)sql::getValue(
            "SELECT COUNT(*) 
            FROM forum_notifications 
            WHERE user_id = ? AND is_read = 0",
            [user::self()->getId()]
        );
    }

    /**
     * Получает последние уведомления форума для пользователя
     *
     * @param int $limit Максимальное количество уведомлений
     * @return array Массив уведомлений
     */
    /**
     * Получает последние непрочитанные уведомления форума для пользователя
     *
     * @param int $limit Максимальное количество уведомлений
     * @return array Массив непрочитанных уведомлений
     */
    public function getForumLatestNotifications(int $limit = 5): array {
        if (!user::self()->isAuth()) {
            return [];
        }

        // Используем более эффективный запрос с подзапросом для first_post_id
        // и добавляем фильтрацию по is_read
        return sql::getRows(
            "SELECT 
            n.*,
            t.title as thread_title,
            t.category_id,
            c.name as category_name,
            u.name as from_user_name,
            u.avatar as from_user_avatar,
            p.content as post_preview,
            p.created_at as post_created_at,
            COALESCE(
                (SELECT MIN(fp.id) 
                 FROM forum_posts fp 
                 WHERE fp.thread_id = t.id), 
                0
            ) as first_post_id
        FROM forum_notifications n
        JOIN forum_threads t ON n.thread_id = t.id
        JOIN forum_categories c ON t.category_id = c.id
        JOIN users u ON n.from_user_id = u.id
        JOIN forum_posts p ON n.post_id = p.id
        WHERE n.user_id = ? 
        AND n.is_read = 0
        AND (
            c.is_hidden = 0 
            OR EXISTS (
                SELECT 1 
                FROM forum_moderators m 
                WHERE m.user_id = ? 
                AND (m.category_id IS NULL OR m.category_id = t.category_id)
            )
        )
        ORDER BY n.created_at DESC
        LIMIT ?",
            [
                user::self()->getId(),
                user::self()->getId(),
                $limit
            ]
        );
    }

    public function hasUnreadPosts(forum_thread $thread): bool {
        return ForumTracker::hasUnreadPosts($thread->getId());
    }

    private array $clans = [];
    public function getForumClanInfo(?int $clanId): false|ForumClan
    {
        if($clanId == null){
            return false;
        }
        if (isset($this->clans[$clanId])) {
            return $this->clans[$clanId];
        }
        $clan = new ForumClans();
        $clanInfo = $clan->getClanInfoById($clanId);
        $this->clans[$clanId] = $clanInfo;
        return $clanInfo;
    }

    public function getClanList(): array
    {
        $clan = new ForumClans();
        return $this->clans = $clan->getClanList();
    }


}