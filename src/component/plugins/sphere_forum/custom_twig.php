<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Ofey\Logan22\component\cache\cache;
use Exception;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_category;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_post;
use Ofey\Logan22\component\plugins\sphere_forum\struct\forum_thread;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumClan;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumModerator;
use Ofey\Logan22\component\plugins\sphere_forum\struct\ForumBan;
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

    private static array $postCountCache = [];

    private static ?array $forumStatisticCache = null;

    private static array $moderatorCache = [];
    private static array $moderatorPermissionCache = [];

    private const STATISTICS_CACHE_DIR = 'uploads/cache/forum/statistics';
    private const LAST_MESSAGES_CACHE_DIR = 'uploads/cache/forum/last_messages';
    private const SIDEBAR_CACHE_TTL = 60;





    public function getTopicViewData(array $userIds, array $postIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));

        $profiles = [];
        $likesByPost = [];
        $defaultSettings = [
            'showCharacters' => true,
            'showPvPPK' => true,
            'showGameTime' => true,
            'showFlagCountry' => true,
        ];
        foreach ($userIds as $userId) {
            $profiles[$userId] = [
                'pvp' => 0,
                'pk' => 0,
                'online_time' => 0,
                'characters' => [],
            ];
        }
        foreach ($postIds as $postId) {
            $likesByPost[$postId] = [];
        }

        if ($userIds !== []) {
            $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
            $userVariables = sql::getRows(
                "SELECT user_id, var, val
                 FROM user_variables
                 WHERE user_id IN ({$userPlaceholders})
                   AND var IN ('forum_post_count', 'forum_display_settings')",
                $userIds
            );

            $usersWithStoredPostCount = [];
            foreach ($userVariables as $variable) {
                $userId = (int)$variable['user_id'];
                if ($variable['var'] === 'forum_post_count') {
                    self::$postCountCache[$userId] = (int)$variable['val'];
                    $usersWithStoredPostCount[$userId] = true;
                    continue;
                }

                $settings = json_decode($variable['val'], true);
                self::$settingsCache[$userId] = array_replace(
                    $defaultSettings,
                    is_array($settings) ? $settings : []
                );
            }

            foreach ($userIds as $userId) {
                self::$settingsCache[$userId] ??= $defaultSettings;
            }

            $usersWithoutPostCount = array_values(array_filter(
                $userIds,
                static fn(int $userId): bool => !isset($usersWithStoredPostCount[$userId])
                    && !isset(self::$postCountCache[$userId])
            ));
            if ($usersWithoutPostCount !== []) {
                $countPlaceholders = implode(',', array_fill(0, count($usersWithoutPostCount), '?'));
                $postCounts = sql::getRows(
                    "SELECT user_id, COUNT(*) AS post_count
                     FROM forum_posts
                     WHERE is_approved = 1 AND user_id IN ({$countPlaceholders})
                     GROUP BY user_id",
                    $usersWithoutPostCount
                );
                foreach ($usersWithoutPostCount as $userId) {
                    self::$postCountCache[$userId] = 0;
                }
                foreach ($postCounts as $postCount) {
                    self::$postCountCache[(int)$postCount['user_id']] = (int)$postCount['post_count'];
                }
            }

            $profileRows = sql::getRows(
                "SELECT u.id AS user_id, pa.characters
                 FROM users u
                 LEFT JOIN player_accounts pa ON pa.email = u.email
                 WHERE u.id IN ({$userPlaceholders})",
                $userIds
            );
            foreach ($profileRows as $profileRow) {
                $userId = (int)$profileRow['user_id'];
                $characters = json_decode((string)($profileRow['characters'] ?? '[]'), true);
                if (!is_array($characters)) {
                    continue;
                }

                foreach ($characters as $character) {
                    if (!is_array($character)) {
                        continue;
                    }
                    $profiles[$userId]['pvp'] += (int)($character['pvp'] ?? 0);
                    $profiles[$userId]['pk'] += (int)($character['pk'] ?? 0);
                    $profiles[$userId]['online_time'] += (int)($character['time_in_game'] ?? 0);
                    $profiles[$userId]['characters'][] = [
                        'player_name' => (string)($character['player_name'] ?? ''),
                        'level' => (int)($character['level'] ?? 0),
                        'sex' => (int)($character['sex'] ?? 0),
                        'class_id' => (int)($character['class_id'] ?? 0),
                        'online' => (int)($character['online'] ?? 0),
                    ];
                }
            }
        }

        if ($postIds !== []) {
            $postPlaceholders = implode(',', array_fill(0, count($postIds), '?'));
            $likes = sql::getRows(
                "SELECT l.post_id, l.like_image, u.name AS user_name
                 FROM forum_post_likes l
                 LEFT JOIN users u ON u.id = l.user_id
                 WHERE l.post_id IN ({$postPlaceholders})
                 ORDER BY l.created_at DESC",
                $postIds
            );
            foreach ($likes as $like) {
                $likesByPost[(int)$like['post_id']][] = $like;
            }
        }

        return [
            'profiles' => $profiles,
            'likes' => $likesByPost,
        ];
    }

    public function getForumPostCount(int $userId): int {
        if (isset(self::$postCountCache[$userId])) {
            return self::$postCountCache[$userId];
        }

        $stored = sql::getRow(
            "SELECT val FROM user_variables WHERE user_id = ? AND var = 'forum_post_count'",
            [$userId]
        );

        if ($stored && isset($stored['val'])) {
            $count = (int)$stored['val'];
            self::$postCountCache[$userId] = $count;
            return $count;
        }

        $count = (int)sql::getValue(
            "SELECT COUNT(*) FROM forum_posts WHERE user_id = ? AND is_approved = 1",
            [$userId]
        );

        try {
            $user = \Ofey\Logan22\model\user\user::getUserId($userId);
            if ($user) {
                $user->addVar('forum_post_count', (string)$count);
            }
        } catch (\Exception $e) {

        }

        self::$postCountCache[$userId] = $count;
        return $count;
    }





    public function getForumUserRank(int $userId): ?array
    {

        try {
            $userObj = \Ofey\Logan22\model\user\user::getUserId($userId);
            if ($userObj && $userObj->isAdmin() && !sphere_forum::isShowAdminRank()) {
                return null;
            }
        } catch (\Exception $e) {

        }

        $postCount = $this->getForumPostCount($userId);
        $ranks = sphere_forum::getForumRanks();

        if (empty($ranks)) {
            return null;
        }


        $lang = $this->detectUserLang($userId);


        $matchedRank = null;
        foreach ($ranks as $rank) {
            if ($postCount >= (int)$rank['post_count']) {
                $matchedRank = $rank;
                break;
            }
        }

        if ($matchedRank === null) {
            return null;
        }


        $names = $matchedRank['names'] ?? [];
        $name = null;
        if (isset($names[$lang]) && !empty($names[$lang])) {
            $name = $names[$lang];
        } else {
            foreach (['ru', 'en', 'ua', 'es', 'pt', 'gr'] as $fallback) {
                if (isset($names[$fallback]) && !empty($names[$fallback])) {
                    $name = $names[$fallback];
                    break;
                }
            }
        }

        if (!$name) {
            return null;
        }

        return [
            'name' => $name,
            'icon' => $matchedRank['icon'] ?? 'bi-trophy-fill',
            'bg_class' => $matchedRank['bg_class'] ?? 'bg-warning',
            'text_class' => $matchedRank['text_class'] ?? 'text-dark',
        ];
    }




    private function detectUserLang(int $userId): string
    {
        try {
            $user = \Ofey\Logan22\model\user\user::getUserId($userId);
            if ($user) {
                $userLang = $user->getVar('lang');
                if ($userLang && !empty($userLang['val'])) {
                    return $userLang['val'];
                }
            }
        } catch (\Exception $e) {

        }
        return \Ofey\Logan22\component\lang\lang::get_phrase('0') === 'Description' ? 'en' : 'ru';
    }

    public function getLikeBuffList(): array
    {
        return self::BUFF_LIKE_LIST;
    }

    public function getStatisticForum(): array
    {
        if (self::$forumStatisticCache !== null) {
            return self::$forumStatisticCache;
        }

        $cached = cache::read(self::STATISTICS_CACHE_DIR, second: self::SIDEBAR_CACHE_TTL);
        if (is_array($cached)) {
            return self::$forumStatisticCache = $cached;
        }

        $topics = sql::getValue("SELECT COUNT(*) AS count FROM `forum_threads`;");
        $messages = sql::getValue("SELECT COUNT(*) AS count FROM `forum_posts`;");
        $statistics = [
            "topics" => $topics ?? 0,
            "messages" => $messages ?? 0
        ];
        cache::save(self::STATISTICS_CACHE_DIR, $statistics);

        return self::$forumStatisticCache = $statistics;
    }

    public function getCategoriesForum(): array {

        $forum_categories = sql::getRows(
            "SELECT * FROM `forum_categories` ORDER BY `sort_order` ASC"
        );
        $categories = [];
        foreach ($forum_categories as $category) {
            $isHidden = (bool)($category['is_hidden'] ?? false);

            if ($isHidden && !user::self()->isAdmin() && !ForumModerator::isUserModerator(user::self()->getId(), $category['id'])) {
                continue;
            }
            $categories[] = new forum_category($category);
        }


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







    public function getLastThreadsForum(int $limit = 5): array {
        $threadsQuery = sql::getRows("
        SELECT
            t.*,
            u.name as author_name,
            u.avatar as author_avatar,
            c.name as category_name,
            c.is_moderated,
            c.can_view_topics,
            t.replies as posts_count
        FROM forum_threads t
        JOIN users u ON t.user_id = u.id
        JOIN forum_categories c ON t.category_id = c.id
        WHERE
            (c.is_moderated = 0 OR t.is_approved = 1)
            AND c.is_hidden = 0
        ORDER BY t.id DESC
        LIMIT ?
    ", [$limit]);

        $threads = [];
        foreach ($threadsQuery as $thread) {

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


            $canView = true;
            if (!user::self()->isAdmin() &&
                !ForumModerator::isUserModerator(user::self()->getId(), $thread['category_id'])) {
                if (!(bool)$thread['can_view_topics']) {

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
        $messagesRows = cache::read(self::LAST_MESSAGES_CACHE_DIR, second: self::SIDEBAR_CACHE_TTL);
        if (!is_array($messagesRows)) {
            $messagesRows = sql::getRows("
            SELECT p.*,
                   t.title AS thread_title,
                   t.user_id AS thread_author_id,
                   c.id AS category_id,
                   c.can_view_topics
            FROM forum_threads t
            INNER JOIN forum_posts p ON p.id = t.last_post_id
            INNER JOIN forum_categories c ON t.category_id = c.id
            WHERE t.last_post_id IS NOT NULL
              AND (c.is_moderated = 0 OR t.is_approved = 1)
              AND c.is_hidden = 0
            ORDER BY t.last_post_id DESC
            LIMIT 5
        ");
            cache::save(self::LAST_MESSAGES_CACHE_DIR, $messagesRows);
        }

        $messages = [];
        foreach ($messagesRows as $message) {
            $post = new forum_post($message);


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


            $canView = true;
            if (!user::self()->isAdmin() &&
                !ForumModerator::isUserModerator(user::self()->getId(), $message['category_id'])) {
                if (!(bool)$message['can_view_topics']) {

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
            ' ' => '_'
        ];


        $result = strtr($input, $translitMap);


        $result = preg_replace('/[^a-zA-Z0-9_]/', '-', $result);


        $result = preg_replace('/-+/', '-', $result);
        return trim( mb_strtolower($result), '-');
    }






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







    public function isModerator(int $userId, ?int $categoryId = null): bool {
        $cacheKey = $userId . ':' . ($categoryId ?? 'all');
        if (array_key_exists($cacheKey, self::$moderatorCache)) {
            return self::$moderatorCache[$cacheKey];
        }

        if($categoryId === null){
            $moderator = sql::getRow(
                "SELECT * FROM forum_moderators WHERE user_id = ?",
                [$userId]
            );
            return self::$moderatorCache[$cacheKey] = (bool)$moderator;
        }
        $moderator = sql::getRow(
            "SELECT * FROM forum_moderators
        WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
            [$userId, $categoryId]
        );
        return self::$moderatorCache[$cacheKey] = (bool)$moderator;
    }








    public function hasModeratorPermission(int $userId, ?int $categoryId, string $permission): bool {
        $cacheKey = $userId . ':' . ($categoryId ?? 'all') . ':' . $permission;
        if (array_key_exists($cacheKey, self::$moderatorPermissionCache)) {
            return self::$moderatorPermissionCache[$cacheKey];
        }

        $moderator = sql::getRow(
            "SELECT $permission FROM forum_moderators
            WHERE user_id = ? AND (category_id IS NULL OR category_id = ?)",
            [$userId, $categoryId]
        );
        return self::$moderatorPermissionCache[$cacheKey] = (bool)($moderator[$permission] ?? false);
    }


    public function getAwaitingModerationThreads(): array {
        $userId = user::self()->getId();
        $isAdmin = user::self()->isAdmin();


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






    public function getForumUserSettings(int $userId): array {

        if (isset(self::$settingsCache[$userId])) {
            return self::$settingsCache[$userId];
        }


        $settings = sql::getRow(
            "SELECT val FROM user_variables WHERE user_id = ? AND var = 'forum_display_settings'",
            [$userId]
        );


        $userSettings = !empty($settings) ? json_decode($settings['val'], true) : [
            'showCharacters' => true,
            'showPvPPK' => true,
            'showGameTime' => true,
            'showFlagCountry' => true,
        ];


        self::$settingsCache[$userId] = $userSettings;

        return $userSettings;
    }








    public function isForumSettingEnabled(int $userId, string $setting): bool {
        $settings = $this->getForumUserSettings($userId);
        return $settings[$setting] ?? true;
    }







    public function saveForumUserSettings(int $userId, array $settings): bool {
        try {
            $user = user::getUserId($userId);
            $user->addVar('forum_display_settings', json_encode($settings));


            self::$settingsCache[$userId] = $settings;

            return true;
        } catch (Exception $e) {
            error_log("Error saving forum settings: " . $e->getMessage());
            return false;
        }
    }







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













    public function getForumLatestNotifications(int $limit = 5): array {
        if (!user::self()->isAuth()) {
            return [];
        }



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






    public function areClansEnabled(): bool
    {
        return forum::areClanEnabled();
    }






    public function getForumSettings(): array
    {
        return forum::getForumSettingsStatic();
    }






    public function arePollsEnabled(): bool
    {
        $settings = $this->getForumSettings();
        return $settings['enable_polls'] ?? true;
    }






    public function isBBCodeEnabled(): bool
    {
        $settings = $this->getForumSettings();
        return $settings['enable_bbcode'] ?? true;
    }






    public function areAttachmentsEnabled(): bool
    {
        $settings = $this->getForumSettings();
        return $settings['enable_attachments'] ?? true;
    }







    public function isUserBanned(int $userId): bool {
        $ban = ForumBan::isUserBanned($userId);
        return $ban !== null;
    }







    public function getUserBan(int $userId): ?array {
        return ForumBan::isUserBanned($userId);
    }







    public function getBanMessage(int $userId): ?string {
        $ban = ForumBan::isUserBanned($userId);
        if (!$ban) {
            return null;
        }

        $message = "Вам запрещено писать сообщения на форуме";
        if ($ban['banned_until']) {
            $message .= " до " . date('d.m.Y H:i', strtotime($ban['banned_until']));
        } else {
            $message .= " (перманентный бан)";
        }

        if ($ban['reason']) {
            $message .= ".<br><strong>Причина:</strong> " . htmlspecialchars($ban['reason']);
        }

        return $message;
    }

}
