<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Exception;
use Intervention\Image\ImageManager;
use Ofey\Logan22\component\alert\board;
use Ofey\Logan22\component\plugins\sphere_forum\struct\{forum_category,
    forum_post,
    forum_thread,
    ForumModerator,
    ForumUserSettings
};
use Ofey\Logan22\component\fileSys\fileSys;
use Ofey\Logan22\component\lang\lang;
use Ofey\Logan22\component\links\http;
use Ofey\Logan22\component\plugins\sphere_forum\system\AntiFlood;
use Ofey\Logan22\component\redirect;
use Ofey\Logan22\component\sphere\server;
use Ofey\Logan22\component\time\time;
use Ofey\Logan22\controller\admin\telegram;
use Ofey\Logan22\controller\config\config;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\page\page;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\model\user\user;
use Ofey\Logan22\model\user\userModel;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

/**
 * Класс форума, обеспечивающий основную функциональность форума
 */
class forum
{
    private const POSTS_PER_PAGE = 10; // Количество постов на странице

    private ?string $nameClass = null;

    private function getNameClass(): string
    {
        if ($this->nameClass == null) {
            $this->nameClass = (new ReflectionClass($this))->getShortName();
        }

        return $this->nameClass;
    }

    public function __construct()
    {
        if (!plugin::getPluginActive("sphere_forum")) {
            redirect::location("/main");
        }
        tpl::addVar("template_plugin", "sphere_forum");
    }

    /**
     * Отображает главную страницу форума
     */
    public function show(): void
    {
        tpl::displayPlugin("sphere_forum/tpl/main.html");
    }

    /**
     * Получает и отображает темы для указанной категории
     *
     * @param string $sectionName Название раздела
     * @param int $sectionId ID раздела
     * @throws Exception
     */
    public function getTopics(string $sectionName, int $sectionId): void
    {
        try {
            $category = $this->getCategoryById($sectionId);

            if ($category->getLink()) {
                redirect::location($category->getLink());
                return;
            }

            // Проверяем права на создание тем
            $canCreateTopics = user::self()->isAdmin() || $category->canCreateTopics() || user::self()->isAuth();

            $subCategories = $this->getSubCategories($sectionId);
            $threads = $this->getThreadsByCategory($sectionId);

            // Проверяем непрочитанные сообщения для каждой темы
            if (user::self()->isAuth()) {
                foreach ($threads as $thread) {
                    $thread->hasUnread = $thread->hasUnreadPosts();
                }
            }

            // Получаем родительскую категорию
            $parentCategory = null;
            if ($category->getParentId() !== null) {
                $parentCategory = $this->getCategoryById($category->getParentId());
            }
            $categoryParents = $this->getCategoryParents($sectionId);

            tpl::addVar([
                "category" => $category,
                "parentCategory" => $parentCategory,
                "sectionId" => $sectionId,
                "categoryId" => $sectionId, // Убедимся, что это значение передается
                "sectionName" => $sectionName,
                "threads" => $threads,
                "categories" => $subCategories,
                "canCreateTopics" => $canCreateTopics,
                "categoryParents" => $categoryParents,
            ]);

            tpl::displayPlugin("sphere_forum/tpl/topics_list.html");
        } catch (Exception $e) {
            error::error404($e->getMessage());
        }
    }

    /**
     * Вспомогательные private методы
     */
    private function getCategoryById(int $id): forum_category
    {
        $category = sql::getRow("SELECT * FROM `forum_categories` WHERE `id` = ? LIMIT 1", [$id]);
        if (!$category) {
            throw new Exception("Категория не найдена");
        }
        return new forum_category($category);
    }

    private function getSubCategories(int $parentId): array
    {
        $categories = sql::getRows(
            "SELECT * FROM `forum_categories` WHERE `parent_id` = ? ORDER BY `sort_order` ASC",
            [$parentId]
        );
        return array_map(fn($item) => new forum_category($item), $categories);
    }

    private function getThreadsByCategory(int $categoryId): array
    {
        $threads = sql::getRows(
            "SELECT * FROM `forum_threads` 
        WHERE `category_id` = ? 
        ORDER BY `is_pinned` DESC, `updated_at` DESC",
            [$categoryId]
        );
        return array_map(fn($thread) => new forum_thread($thread), $threads);
    }


    /**
     * Получает и отображает конкретную тему
     *
     * @param int $topicId ID темы
     * @throws Exception
     */
    public function getTopicRead(int $topicId): void
    {
        try {
            $thread = $this->getThreadById($topicId);
            if (!$thread) {
                redirect::location("/forum");
                return;
            }

            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $totalPages = $this->getTotalPages($topicId);

            if ($currentPage <= 0 || $currentPage > $totalPages) {
                $custom_twig = new custom_twig();
                $translit = $custom_twig->transliterateToEn($thread->getTitle());
                $targetPage = $currentPage <= 0 ? 1 : $totalPages;
                redirect::location("/forum/topic/{$translit}.{$topicId}?page={$targetPage}");
                return;
            }

            // Регистрируем просмотр темы
            $this->registerThreadView($topicId);

            $postBoundaries = sql::getRow(
                "SELECT MIN(id) as first_post_id, MAX(id) as last_post_id 
     FROM forum_posts 
     WHERE thread_id = ?",
                [$topicId]
            );
            if ($postBoundaries) {
                $firstPostId = $postBoundaries['first_post_id'];
                $lastPostId = $postBoundaries['last_post_id'];

                // Теперь у нас есть оба ID из одного запроса
                if ($lastPostId) {
                    ForumTracker::trackThreadView($topicId, $lastPostId);
                }
            }

            // После получения постов темы, отмечаем уведомления как прочитанные
            if (user::self()->isAuth()) {
                // Получаем ID всех уведомлений для этой темы
                $notifications = sql::getRows(
                    "SELECT id 
                FROM forum_notifications 
                WHERE user_id = ? AND thread_id = ? AND is_read = 0",
                    [user::self()->getId(), $topicId]
                );

                if (!empty($notifications)) {
                    $notificationIds = array_column($notifications, 'id');
                    ForumTracker::markNotificationsAsRead($notificationIds);
                }
            }

            $category = $this->getCategoryById($thread->getCategoryId());

            $categoryParents = $this->getCategoryParents($thread->getCategoryId());

            // Сначала проверяем статус модерации
            if ($category->isModerated() && !$thread->isApproved()) {
                // Тема на модерации может быть доступна только:
                // 1. Администраторам
                // 2. Модераторам этой категории
                // 3. Автору темы
                if (!user::self()->isAdmin() &&
                    !ForumModerator::isUserModerator(user::self()->getId(), $category->getId()) &&
                    $thread->getAuthorId() !== user::self()->getId()) {
                    throw new Exception("Тема находится на модерации");
                }
            }

            // Затем проверяем общие права на просмотр
            if (!user::self()->isAdmin() &&
                !ForumModerator::isUserModerator(user::self()->getId(), $category->getId())) {
                if (!$category->canViewTopics()) {
                    if ($thread->getAuthorId() !== user::self()->getId()) {
                        throw new Exception("У вас нет прав для просмотра чужих тем в этом разделе");
                    }
                }
            }

            $canReply = (user::self()->isAdmin() || ($category->canReplyTopics() && user::self()->isAuth()));

            $categoryName = $this->getCategoryName($thread->getCategoryId());
            $posts = $this->getPostsByThread($topicId, $currentPage);

            $isSubscribed = false;
            if (user::self()->isAuth()) {
                $isSubscribed = (bool)sql::getValue(
                    "SELECT is_subscribed 
        FROM forum_user_thread_tracks 
        WHERE user_id = ? AND thread_id = ?",
                    [user::self()->getId(), $topicId]
                );
            }

            tpl::addVar([
                "category" => $category,
                "categoryTitle" => $categoryName,
                "categoryId" => $thread->getCategoryId(),
                "posts" => $posts,
                "thread" => $thread,
                "id" => $topicId,
                "canReply" => $canReply,
                "totalPages" => $totalPages,
                "currentPage" => $currentPage,
                "categoryParents" => $categoryParents,
                "isSubscribed" => $isSubscribed, // Добавляем статус подписки
                "firstPostId" => $firstPostId,
            ]);

            tpl::displayPlugin("sphere_forum/tpl/read.html");
        } catch (Exception $e) {
            error::error404("Ошибка при чтении темы: " . $e->getMessage());
        }
    }

    private function getThreadById(int $id): ?forum_thread
    {
        $thread = sql::getRow("SELECT * FROM `forum_threads` WHERE `id` = ?", [$id]);
        return $thread ? new forum_thread($thread) : null;
    }

    private function registerThreadView(int $threadId): void
    {
        // Получаем текущего пользователя или гостя
        $userId = user::self()->isAuth() ? user::self()->getId() : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        // Проверяем, не было ли просмотра за последние 24 часа
        $existing = sql::getRow(
            "SELECT id FROM forum_thread_views 
        WHERE thread_id = ? 
        AND (
            (user_id IS NOT NULL AND user_id = ?) OR 
            (user_id IS NULL AND ip_address = ? AND user_agent = ?)
        )
        AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$threadId, $userId, $ipAddress, $userAgent]
        );

        if (!$existing) {
            // Регистрируем новый просмотр
            sql::run(
                "INSERT INTO forum_thread_views 
            (thread_id, user_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)",
                [$threadId, $userId, $ipAddress, $userAgent]
            );

            // Обновляем счетчик в таблице тем
            sql::run(
                "UPDATE forum_threads 
            SET views = views + 1 
            WHERE id = ?",
                [$threadId]
            );
        }
    }

    private function getCategoryName(int $categoryId): string
    {
        $category = sql::getRow("SELECT `name` FROM `forum_categories` WHERE `id` = ?", [$categoryId]);
        return $category['name'] ?? '';
    }

    private function getPostsByThread(int $threadId, int $page = 1): array
    {
        $offset = ($page - 1) * self::POSTS_PER_PAGE;

        // Получаем посты с учетом пагинации
        $posts = sql::getRows(
            "SELECT * FROM `forum_posts` 
        WHERE `thread_id` = ? 
        ORDER BY `id` ASC 
        LIMIT ? OFFSET ?",
            [$threadId, self::POSTS_PER_PAGE, $offset]
        );

        return array_map(fn($post) => new forum_post($post), $posts);
    }

    private function getTotalPages(int $threadId): int
    {
        $totalPosts = sql::getValue(
            "SELECT COUNT(*) FROM `forum_posts` WHERE `thread_id` = ?",
            [$threadId]
        );

        return ceil($totalPosts / self::POSTS_PER_PAGE);
    }

    /**
     * Добавляет сообщение в тему
     *
     * @throws Exception
     */
    public function addTopicMessage(): void
    {
        try {
            // Проверяем на флуд перед обработкой сообщения
            $antiFlood = new AntiFlood(AntiFlood::TYPE_POST);
            $antiFlood->checkFlood();

            $this->validateMessageInput();

            $topicId = (int)$_POST['topicId'];
            $message = $_POST['message'];
            $replyToId = isset($_POST['replyToId']) ? (int)$_POST['replyToId'] : null;

            // Проверяем существование поста, на который отвечают
            if ($replyToId) {
                $replyPost = sql::getRow(
                    "SELECT id FROM forum_posts WHERE id = ? AND thread_id = ?",
                    [$replyToId, $topicId]
                );
                if (!$replyPost) {
                    throw new Exception("Пост, на который вы пытаетесь ответить, не существует");
                }
            }

            // Получаем тему и категорию
            $thread = $this->getThreadById($topicId);
            $category = $this->getCategoryById($thread->getCategoryId());

            // Валидируем содержание сообщения
            $this->validateMessageContent($message, $category);

            if (!user::self()->isAdmin() && !$category->canReplyTopics()) {
                throw new Exception("Ответы в этом разделе запрещены");
            }
            $this->validateThreadAccess($thread);
            sql::beginTransaction();
            $response = server::sendCustom("/api/plugin/forum/add/count/message")->show()->getResponse();
            if(!$response['success']) {
                throw new Exception($response['message']);
            }
            try {
                $lastPostId = $this->createPost($topicId, $message, $replyToId);
                $this->updateThreadAfterPost($topicId, $lastPostId);
                $this->updateCategoriesAfterPost($thread->getCategoryId(), $lastPostId, $topicId);
                $antiFlood->updateActivity();
                $this->incrementThreadReplies($topicId);
                $this->incrementCategoryPostCount($thread->getCategoryId());
                ForumTracker::notifyAboutNewPost($topicId, $lastPostId, $replyToId);
                // Получаем общее количество постов в теме
                $totalPosts = sql::getValue(
                    "SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?",
                    [$topicId]
                );
                // Вычисляем номер последней страницы
                $postsPerPage = self::POSTS_PER_PAGE; // Используем константу из класса
                $lastPage = ceil($totalPosts / $postsPerPage);
                sql::commit();
                // Формируем URL для редиректа
                $thread = $this->getThreadById($topicId);
                $custom_twig = new custom_twig();
                $translit = $custom_twig->transliterateToEn($thread->getTitle());
                $redirectUrl = "/forum/topic/{$translit}.{$topicId}";
                // Добавляем номер страницы только если страниц больше одной
                if ($lastPage > 1) {
                    $redirectUrl .= "?page={$lastPage}";
                }
                board::redirect($redirectUrl);
                board::success("Сообщение добавлено");
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function validateMessageInput(): void
    {
        if (!isset($_POST['topicId']) || !isset($_POST['message'])) {
            throw new Exception("Необходимо указать тему и сообщение");
        }
    }

    /**
     * Валидация содержания сообщения
     * @param string $message Текст сообщения
     * @param forum_category $category Категория форума
     * @throws Exception
     */
    private function validateMessageContent(string $message, forum_category $category): void
    {
        // Удаляем HTML-теги для проверки реального содержания
        $plainText = strip_tags($message);

        // Очищаем от пробелов в начале и конце
        $plainText = trim($plainText);

        // Проверяем минимальную длину (например, 5 символов)
        if (mb_strlen($plainText) < 1) {
            throw new Exception("Сообщение слишком короткое. Минимальная длина - 1 символов.");
        }

        // Проверяем максимальную длину
        if (mb_strlen($plainText) > $category->getMaxPostLength()) {
            throw new Exception("Сообщение слишком длинное. Максимальная длина - {$category->getMaxPostLength()} символов.");
        }

        // Проверяем, что сообщение не состоит только из пробелов или спецсимволов
        if (preg_match('/^\s*$/', $plainText)) {
            throw new Exception("Сообщение не может быть пустым.");
        }

        // Проверяем на повторяющиеся символы (например "ааааааа" или ".........")
        if (preg_match('/^(.)\1+$/', $plainText)) {
            throw new Exception("Сообщение не может состоять из повторяющихся символов.");
        }
    }

    // Добавлены остальные методы с аналогичными улучшениями...

    private function validateThreadAccess(forum_thread $thread): void
    {
        if (!user::self()->isAdmin() && $thread->isClosed()) {
            throw new Exception("Тема закрыта");
        }
    }

    private function createPost(int $threadId, string $message, ?int $replyToId): int
    {
        sql::run(
            "INSERT INTO `forum_posts` SET `thread_id` = ?, `user_id` = ?, `content` = ?, `reply_to_id` = ?, `created_at` = ?, `updated_at` = ?",
            [$threadId, user::self()->getId(), $message, $replyToId, time::mysql(), time::mysql()]
        );
        return sql::lastInsertId();
    }

    private function updateThreadAfterPost(int $threadId, int $lastPostId): void
    {
        sql::run(
            "UPDATE `forum_threads` SET `updated_at` = ?, `last_reply_user_id` = ?, `last_post_id` = ? WHERE `id` = ?",
            [time::mysql(), user::self()->getId(), $lastPostId, $threadId]
        );
    }

    private function updateCategoriesAfterPost(int $categoryId, int $lastPostId, int $threadId): void
    {
        $this->updateCategoryPostCount($categoryId);
        $this->updateCategoryAndParents($categoryId, user::self()->getId(), $lastPostId, $threadId);
    }

    /**
     * Обновление количества сообщений в категории
     */
    private function updateCategoryPostCount(int $categoryId, bool $addIncrementTopic = false): void
    {
        $postCount = sql::getValue(
            "SELECT COUNT(*) FROM `forum_threads` WHERE `category_id` = ?",
            [$categoryId]
        );

        sql::run(
            "UPDATE `forum_categories` SET `post_count` = ? WHERE `id` = ?",
            [$postCount, $categoryId]
        );

        if ($addIncrementTopic) {
            sql::run(
                "UPDATE `forum_categories` SET `post_count` = `post_count` + 1 WHERE `id` = ?",
                [$categoryId]
            );
        }
    }

    /**
     * Рекурсивное обновление категории и её родителей
     */
    private function updateCategoryAndParents(int $categoryId, int $userId, int $lastPostId, int $lastThreadId): void
    {
        sql::run(
            "UPDATE `forum_categories` SET 
            `updated_at` = ?, 
            `last_reply_user_id` = ?, 
            `last_post_id` = ?, 
            `last_thread_id` = ? 
         WHERE `id` = ?",
            [time::mysql(), $userId, $lastPostId, $lastThreadId, $categoryId]
        );

        $parentId = sql::getValue(
            "SELECT `parent_id` FROM `forum_categories` WHERE `id` = ?",
            [$categoryId]
        );

        if ($parentId !== null) {
            $this->updateCategoryAndParents($parentId, $userId, $lastPostId, $lastThreadId);
        }
    }

    /**
     * Увеличивает счетчик ответов в теме
     */
    private function incrementThreadReplies(int $threadId): void
    {
        sql::run(
            "UPDATE `forum_threads` SET `replies` = `replies` + 1 WHERE `id` = ?",
            [$threadId]
        );
    }

    /**
     * Увеличивает счетчик постов в категории
     */
    private function incrementCategoryPostCount(int $categoryId): void
    {
        // Обновляем счетчик постов в текущей категории
        sql::run(
            "UPDATE `forum_categories` SET `post_count` = `post_count` + 1 WHERE `id` = ?",
            [$categoryId]
        );

        // Обновляем счетчики в родительских категориях
        $this->updateParentCategoryCounters($categoryId);
    }

    /**
     * Рекурсивно обновляет счетчики в родительских категориях
     */
    private function updateParentCategoryCounters(int $categoryId): void
    {
        $category = sql::getRow("SELECT parent_id FROM `forum_categories` WHERE `id` = ?", [$categoryId]);

        if ($category && $category['parent_id'] !== null) {
            $parentId = (int)$category['parent_id'];

            // Обновляем счетчики в родительской категории
            sql::run(
                "UPDATE `forum_categories` SET 
                    `post_count` = `post_count` + 1
                 WHERE `id` = ?",
                [$parentId]
            );

            // Рекурсивно обновляем счетчики для следующего уровня родителей
            $this->updateParentCategoryCounters($parentId);
        }
    }

    /**
     * Создает новую тему
     *
     * @param string $sectionName Название раздела
     * @param int $categoryId ID категории
     */
    public function createTopic(string $sectionName, int $categoryId): void
    {
        try {
            $category = $this->getCategoryById($categoryId);

            if (!$category->canCreateTopics() && !user::self()->isAdmin()) {
                throw new Exception("Создание тем запрещено в данном разделе");
            }

            if (!user::self()->isAuth()) {
                throw new Exception("Необходимо авторизоваться");
            }

            tpl::addVar([
                "categoryTitle" => $sectionName,
                "categoryId" => $categoryId,
            ]);
            tpl::displayPlugin("sphere_forum/tpl/create_topic.html");
        } catch (Exception $e) {
            error::error404($e->getMessage());
        }
    }

    public function createTopicSave(): void
    {
        try {
            $antiFlood = new AntiFlood(AntiFlood::TYPE_THREAD);
            $antiFlood->checkFlood();

            $this->validateTopicInput();

            $categoryId = (int)$_POST['categoryId'];
            $title = trim($_POST['title']);
            $message = trim($_POST['content']);

            // Валидация названия темы
            $this->validateTopicTitle($title);

            $category = $this->getCategoryById($categoryId);

            // Добавляем валидацию содержания темы
            $this->validateTopicContent($title, $message, $category);
            $this->validateMessageContent($message, $category);

            $isClose = $this->getClosedStatus();
            $attachments = isset($_POST['attachments']) ? array_map('intval', $_POST['attachments']) : [];


            // Проверяем требуется ли модерация
            $isApproved = !$category->isModerated() || user::self()->isAdmin();

            sql::beginTransaction();

            try {
                $topicId = $this->insertNewThread($categoryId, $title, $isClose, $isApproved);
                $lastPostId = $this->createInitialPost($topicId, $message);

                if (!empty($attachments)) {
                    $this->linkAttachmentsToPost($lastPostId, $attachments);
                }


                $this->updateThreadAfterCreation($topicId, $lastPostId);
                $this->updateCategoriesAfterNewTopic($categoryId, $lastPostId, $topicId);

                // Обновляем информацию об активности пользователя
                $antiFlood->updateActivity();

                $this->incrementCategoryCounters($categoryId);

                sql::commit();

                $this->cleanupUnusedAttachments();

                $pollId = null;
                if (isset($_POST['poll']) && !empty($_POST['poll'])) {
                    $pollData = $_POST['poll'];
                    $pollId = $this->createPollForThread($topicId, $pollData);
                }
                sql::run(
                    "UPDATE forum_threads SET poll_id = ? WHERE id = ?",
                    [$pollId, $topicId]
                );

                if ($category->shouldNotifyTelegram()) {
                    $this->telegramNotice($title, $topicId);
                }

                $this->redirectToNewTopic($title, $topicId, $isApproved);
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function createPollForThread(int $topicId, array $pollData): ?int {
        sql::run(
            "INSERT INTO forum_polls 
        (thread_id, question, is_multiple, expires_at) 
        VALUES (?, ?, ?, ?)",
            [
                $topicId,
                $pollData['question'],
                $pollData['isMultiple'] ? 1 : 0,
                $pollData['expiresAt'] ?? null
            ]
        );

        $pollId = sql::lastInsertId();

        foreach ($pollData['options'] as $option) {
            sql::run(
                "INSERT INTO forum_poll_options (poll_id, text) VALUES (?, ?)",
                [$pollId, $option]
            );
        }

        return $pollId;
    }

    private function validateTopicInput(): void
    {
        if (!isset($_POST['categoryId']) || !isset($_POST['title']) || !isset($_POST['content'])) {
            throw new Exception("Необходимо заполнить все обязательные поля");
        }
    }

    private function validateTopicContent(string $title, string $message, forum_category $category): void
    {
        // Проверяем заголовок
        $plainTitle = trim(strip_tags($title));
        if (mb_strlen($plainTitle) < 3) {
            throw new Exception("Название темы слишком короткое. Минимальная длина - 3 символа.");
        }

        if (mb_strlen($plainTitle) > 100) {
            throw new Exception("Название темы слишком длинное. Максимальная длина - 100 символов.");
        }

        // Проверяем содержание первого сообщения
        $this->validateMessageContent($message, $category);
    }

    private function getClosedStatus(): int
    {
        if (!user::self()->isAdmin()) {
            return 0;
        }
        return filter_var($_POST['isClose'] ?? 0, FILTER_VALIDATE_INT, [
            "options" => [
                "default" => 0,
                "min_range" => 0,
                "max_range" => 1
            ]
        ]);
    }

    private function insertNewThread(int $categoryId, string $title, int $isClose, bool $isApproved): int
    {
        sql::run(
            "INSERT INTO `forum_threads` SET 
        `category_id` = ?, 
        `user_id` = ?, 
        `title` = ?, 
        `created_at` = ?, 
        `updated_at` = ?, 
        `is_closed` = ?,
        `is_approved` = ?",
            [$categoryId, user::self()->getId(), $title, time::mysql(), time::mysql(), $isClose, $isApproved]
        );
        return sql::lastInsertId();
    }

    private function createInitialPost(int $topicId, string $message): int
    {
        return $this->createPost($topicId, $message, null);
    }

    /**
     * Связывает загруженные файлы с постом
     *
     * @param int $postId ID поста
     * @param array $attachmentIds Массив ID прикрепленных файлов
     * @throws Exception
     */
    private function linkAttachmentsToPost(int $postId, array $attachmentIds): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        $placeholders = str_repeat('?,', count($attachmentIds) - 1) . '?';
        sql::run(
            "UPDATE forum_attachments SET post_id = ? WHERE id IN ($placeholders) AND user_id = ?",
            array_merge([$postId], $attachmentIds, [user::self()->getId()])
        );
    }

    private function updateThreadAfterCreation(int $topicId, int $lastPostId): void
    {
        sql::run(
            "UPDATE `forum_threads` SET `replies` = 1, `views` = 1, `created_at` = ?, `updated_at` = ?, `last_reply_user_id` = ?, `last_post_id` = ?, `first_message_id` = ? WHERE `id` = ?",
            [time::mysql(), time::mysql(), user::self()->getId(), $lastPostId, $lastPostId, $topicId]
        );
    }

    private function updateCategoriesAfterNewTopic(int $categoryId, int $lastPostId, int $topicId): void
    {
        foreach ($this->getCategoryParents($categoryId) as $categoryParent) {
            sql::run(
                "UPDATE `forum_categories` SET 
            `updated_at` = ?, 
            `last_reply_user_id` = ?, 
            `last_post_id` = ?, 
            `last_thread_id` = ? 
            WHERE `id` = ?",
                [time::mysql(), user::self()->getId(), $lastPostId, $topicId, $categoryParent->getId()]
            );
        }
    }

    /**
     * Получение цепочки родительских категорий
     */
    private function getCategoryParents(int $categoryId): array
    {
        $parents = [];

        $getParent = function (int $id) use (&$getParent, &$parents) {
            $query = "SELECT * FROM forum_categories WHERE id = ? LIMIT 1";
            $category = sql::getRows($query, [$id]);

            if (!empty($category[0])) {
                $categoryObj = new forum_category($category[0]);
                $parents[] = $categoryObj;

                if (!empty($category[0]['parent_id'])) {
                    $getParent($category[0]['parent_id']);
                }
            }
        };

        $getParent($categoryId);
        return array_reverse($parents);
    }

    /**
     * Увеличивает счетчики категории при создании новой темы
     */
    private function incrementCategoryCounters(int $categoryId): void
    {
        // Обновляем счетчики в текущей категории
        sql::run(
            "UPDATE `forum_categories` SET 
            `thread_count` = `thread_count` + 1,
            `post_count` = `post_count` + 1,
            `updated_at` = ?
        WHERE `id` = ?",
            [time::mysql(), $categoryId]
        );

        // Обновляем счетчики во всех родительских категориях
        $this->updateParentCategoryCountersIncrement($categoryId);
    }

    /**
     * Рекурсивно обновляет счетчики в родительских категориях
     */
    private function updateParentCategoryCountersIncrement(int $categoryId): void
    {
        $category = sql::getRow("SELECT parent_id FROM `forum_categories` WHERE `id` = ?", [$categoryId]);

        if ($category && $category['parent_id'] !== null) {
            $parentId = (int)$category['parent_id'];

            // Обновляем счетчики в родительской категории
            sql::run(
                "UPDATE `forum_categories` SET 
                `thread_count` = `thread_count` + 1,
                `post_count` = `post_count` + 1,
                `updated_at` = ?
            WHERE `id` = ?",
                [time::mysql(), $parentId]
            );

            // Рекурсивно обновляем счетчики для следующего уровня родителей
            $this->updateParentCategoryCountersIncrement($parentId);
        }
    }

    /**
     * Удаляет неиспользованные прикрепленные файлы
     */
    public function cleanupUnusedAttachments(): void
    {
        // Получаем список неиспользованных файлов старше 1 дня
        $unusedAttachments = sql::getRows(
            "SELECT * FROM forum_attachments 
        WHERE post_id IS NULL 
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );

        foreach ($unusedAttachments as $attachment) {
            $filepath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/forum/' . $attachment['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Удаляем записи из базы
        sql::run(
            "DELETE FROM forum_attachments 
        WHERE post_id IS NULL 
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }

    private function redirectToNewTopic(string $title, int $topicId, $isApproved = true): void
    {
        $custom_twig = new custom_twig();
        $translit = $custom_twig->transliterateToEn($title);
        board::redirect("/forum/topic/{$translit}.{$topicId}");

        // Если требуется модерация и это не админ
        if (!$isApproved) {
            board::success("Тема создана и будет опубликована после проверки модератором");
            redirect::location("/forum");
            return;
        }
        board::success("Тема создана");
    }

    /**
     * Создает новую категорию
     *
     * @throws Exception
     */
    public function createCategory(): void
    {
        try {
            $this->validateAdminRights();
            $this->validateCategoryInput();

            $name = $_POST['name'];
            $description = $_POST['description'] ?? "";

            $this->insertCategory($name, $description);

            board::reload();
            board::success("Категория создана");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function validateAdminRights(): void
    {
        if (!user::self()->isAdmin()) {
            throw new Exception("Недостаточно прав для выполнения операции");
        }
    }

    private function validateCategoryInput(): void
    {
        if (!isset($_POST['name'])) {
            throw new Exception("Необходимо указать название категории");
        }
    }

    private function insertCategory(string $name, string $description): void
    {
        try {
            sql::run(
                "INSERT INTO `forum_categories` SET `name` = ?, `description` = ?, `parent_id` = ?, `created_at` = ?, `updated_at` = ?",
                [$name, $description, null, time::mysql(), time::mysql()]
            );
        }catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Удаление категории и всех связанных данных
     */

    public function deleteCategory(): void
    {
        try {
            $this->validateAdminRights();

            $categoryId = $_POST['categoryId'] ?? throw new Exception("Не указана категория");
            $action = $_POST['action'] ?? 'delete';
            $destinationCategoryId = $_POST['destinationCategoryId'] ?? null;

            // Инициализируем custom_twig для транслитерации
            $custom_twig = new custom_twig();

            // Получаем информацию о категории
            $category = $this->getCategoryById($categoryId);
            if (!$category) {
                throw new Exception("Категория не найдена");
            }

            sql::beginTransaction();
            try {
                if ($action === 'move' && $destinationCategoryId) {
                    // Проверяем существование целевой категории
                    $destinationCategory = $this->getCategoryById($destinationCategoryId);
                    if (!$destinationCategory) {
                        throw new Exception("Категория назначения не найдена");
                    }

                    // Сначала перемещаем темы из текущей категории
                    sql::run(
                        "UPDATE forum_threads 
                    SET category_id = ?, updated_at = ? 
                    WHERE category_id = ?",
                        [$destinationCategoryId, time::mysql(), $categoryId]
                    );

                    // Получаем прямые подкатегории текущей категории
                    $directSubcategories = sql::getRows(
                        "SELECT * FROM `forum_categories` WHERE `parent_id` = ? ORDER BY `sort_order` ASC",
                        [$categoryId]
                    );

                    foreach ($directSubcategories as $subcat) {
                        // Перемещаем каждую подкатегорию в целевую категорию
                        sql::run(
                            "UPDATE forum_categories 
                        SET parent_id = ?, updated_at = ? 
                        WHERE id = ?",
                            [$destinationCategoryId, time::mysql(), $subcat['id']]
                        );
                    }

                    // Обновляем счетчики в целевой категории
                    $this->recalculateCategoryCounters($destinationCategoryId);

                    // Обновляем информацию о последнем посте в целевой категории
                    $this->updateLastPostInfo($destinationCategoryId);

                    // Удаляем исходную категорию после перемещения всего содержимого
                    sql::run("DELETE FROM forum_categories WHERE id = ?", [$categoryId]);

                } else {
                    // Получаем все ID подкатегорий
                    $allCategoryIds = $this->getAllChildCategories($categoryId);
                    $allCategoryIds[] = $categoryId;

                    $placeholders = str_repeat('?,', count($allCategoryIds) - 1) . '?';

                    // Удаляем все посты из тем всех категорий
                    sql::run(
                        "DELETE fp FROM forum_posts fp 
                    INNER JOIN forum_threads ft ON fp.thread_id = ft.id 
                    WHERE ft.category_id IN ($placeholders)",
                        $allCategoryIds
                    );

                    // Удаляем все темы из всех категорий
                    sql::run(
                        "DELETE FROM forum_threads 
                    WHERE category_id IN ($placeholders)",
                        $allCategoryIds
                    );

                    // Удаляем все категории включая подкатегории
                    sql::run(
                        "DELETE FROM forum_categories 
                    WHERE id IN ($placeholders)",
                        $allCategoryIds
                    );
                }

                sql::commit();

                // Определяем куда делать редирект
                if ($action === 'move') {
                    board::redirect("/forum/" . $custom_twig->transliterateToEn($destinationCategory->getName()) . "." . $destinationCategoryId);
                    board::success("Содержимое категории успешно перемещено");
                } else {
                    $parentId = $category->getParentId();
                    if ($parentId) {
                        $parentCategory = $this->getCategoryById($parentId);
                        board::redirect("/forum/" . $custom_twig->transliterateToEn($parentCategory->getName()) . "." . $parentId);
                    } else {
                        board::redirect("/forum");
                    }
                    board::success("Категория и её содержимое успешно удалены");
                }
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function updateLastPostInfo(int $categoryId): void
    {
        // Получаем последний пост в категории и всех её подкатегориях
        $lastPost = sql::getRow(
            "SELECT p.id as post_id, p.thread_id, p.user_id
         FROM forum_posts p
         JOIN forum_threads t ON p.thread_id = t.id
         WHERE t.category_id = ? 
            OR t.category_id IN (
                SELECT id FROM forum_categories 
                WHERE parent_id = ?
            )
         ORDER BY p.created_at DESC
         LIMIT 1",
            [$categoryId, $categoryId]
        );

        if ($lastPost) {
            sql::run(
                "UPDATE forum_categories 
            SET last_post_id = ?,
                last_thread_id = ?,
                last_reply_user_id = ?,
                updated_at = ?
            WHERE id = ?",
                [
                    $lastPost['post_id'],
                    $lastPost['thread_id'],
                    $lastPost['user_id'],
                    time::mysql(),
                    $categoryId
                ]
            );

            // Если у категории есть родитель, обновляем и его
            $parent = sql::getRow(
                "SELECT parent_id FROM forum_categories WHERE id = ?",
                [$categoryId]
            );

            if ($parent && $parent['parent_id']) {
                $this->updateLastPostInfo($parent['parent_id']);
            }
        }
    }


    private function recalculateCategoryCounters(int $categoryId): void
    {
        // Получаем количество тем и постов для категории
        $stats = sql::getRow(
            "SELECT 
            (SELECT COUNT(*) FROM forum_threads WHERE category_id = ?) as thread_count,
            (SELECT COUNT(p.id) 
             FROM forum_posts p 
             JOIN forum_threads t ON p.thread_id = t.id 
             WHERE t.category_id = ?) as post_count",
            [$categoryId, $categoryId]
        );

        // Обновляем счетчики в категории
        sql::run(
            "UPDATE forum_categories 
         SET thread_count = ?,
             post_count = ?,
             updated_at = ?
         WHERE id = ?",
            [
                $stats['thread_count'],
                $stats['post_count'],
                time::mysql(),
                $categoryId
            ]
        );

        // Получаем родительскую категорию
        $parentCategory = sql::getRow(
            "SELECT parent_id FROM forum_categories WHERE id = ?",
            [$categoryId]
        );

        // Рекурсивно обновляем счетчики родительских категорий
        if ($parentCategory && $parentCategory['parent_id'] !== null) {
            $this->recalculateCategoryCounters($parentCategory['parent_id']);
        }
    }

    /**
     * Получает все ID дочерних категорий рекурсивно для указанной родительской категории
     *
     * @param int $parentId ID родительской категории
     * @return array Массив ID всех дочерних категорий
     */
    private function getAllChildCategories(int $parentId): array
    {
        $childCategories = [];

        $children = sql::getRows("SELECT id FROM forum_categories WHERE parent_id = ?", [$parentId]);

        foreach ($children as $child) {
            $childCategories[] = $child['id'];
            // Рекурсивно получаем ID подкатегорий для каждой дочерней категории
            $childCategories = array_merge($childCategories, $this->getAllChildCategories($child['id']));
        }

        return $childCategories;
    }

    /**
     * Отображает форму редактирования поста
     *
     * @param int $postId ID поста для редактирования
     */
    public function postEdit(int $postId, int $returnPage): void
    {
        $post = sql::getRow("SELECT * FROM `forum_posts` WHERE `id` = ?", [$postId]);
        $post = new forum_post($post);
        $thread = $this->getThreadByPostId($postId);
        tpl::addVar([
            "post" => $post,
            "postId" => $postId,
            "returnPage" => $returnPage,
            "thread" => $thread,
        ]);
        tpl::displayPlugin("sphere_forum/tpl/edit_topic.html");
    }

    private function getThreadByPostId(int $postId): ?forum_thread {
        $thread = sql::getRow(
            "SELECT t.* FROM forum_threads t 
         JOIN forum_posts p ON p.thread_id = t.id 
         WHERE p.id = ?",
            [$postId]
        );
        return $thread ? new forum_thread($thread) : null;
    }

    /**
     * Сохраняет отредактированный пост
     *
     * @throws Exception
     */
    public function postEditSave(): void
    {
        try {
            if (!isset($_POST['postId']) || !isset($_POST['content'])) {
                throw new Exception("Не указан ID поста или содержимое");
            }

            $postId = (int)$_POST['postId'];
            $message = $_POST['content'];
            $returnPage = (int)($_POST['returnPage'] ?? 1);

            $post = $this->getPostById($postId);
            if (!$post) {
                throw new Exception("Сообщение не найдено");
            }

            $thread = $this->getThreadById($post->getThreadId());
            if(!$thread) {
                throw new Exception("Тема не найдена");
            }
            $category = $this->getCategoryById($thread->getCategoryId());

            //Проверка что пост принадлежит пользователю
            if($post->getUserId() != user::self()->getId()) {
                if (ForumModerator::isUserModerator(user::self()->getId(), $thread->getCategoryId()) == false or user::self()->isAdmin()) {
                    throw new Exception("Нельзя редактировать чужие сообщения");
                }
            }

            // Проверяем длину сообщения
            if (mb_strlen(strip_tags($message)) > $category->getMaxPostLength()) {
                throw new Exception("Сообщение превышает максимально допустимую длину");
            }

            // Проверяем время редактирования
            $timePassedMinutes = (time() - strtotime($post->getCreatedAt())) / 60;
            if (!user::self()->isAdmin() &&
                $timePassedMinutes > $category->getEditTimeoutMinutes()) {
                throw new Exception("Время редактирования сообщения истекло");
            }

            sql::run(
                "UPDATE `forum_posts` SET `content` = ?, `updated_at` = ? WHERE `id` = ?",
                [$message, time::mysql(), $postId]
            );

            $custom_twig = new custom_twig();
            $translit = $custom_twig->transliterateToEn($thread->getName());

            // Опрос можно будет изменять только первому сообщению темы
            if($thread->getFirstMessageId() == $post->getId()){
                if (isset($_POST['poll']) and !empty($_POST['poll'])) {
                    $pollData = $_POST['poll'];
                    if ($thread->getPoll()) {
                        // Обновляем существующий опрос
                        $poll = $thread->getPoll();
                        if (!$poll->update($pollData)) {
                            throw new Exception("Ошибка при обновлении опроса");
                        }
                    } else {
                        // Создаем новый опрос
                        $pollId = $this->createPollForThread($thread->getId(), $pollData);
                        sql::run(
                            "UPDATE forum_threads SET poll_id = ? WHERE id = ?",
                            [$pollId, $thread->getId()]
                        );
                    }
                }
            }


            $timestamp = time();
            board::redirect("/forum/topic/{$translit}.{$thread->getId()}?page={$returnPage}&t={$timestamp}#post-{$postId}");
            board::success("Сообщение отредактировано");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }


    public function createSection(): void
    {
        try {
            $this->validateAdminRights();

            $name = $_POST['name'] ?? board::error("Не указано название");
            $description = $_POST['description'] ?? "";
            $parent = $_POST['parent'] ?? null;
            $icon = $_POST['icon'] ?? null;
            $link = $_POST['link'] ?? null;

            // Валидация и приведение типов для числовых параметров
            $canUsersDeleteOwnThreads = isset($_POST['canUsersDeleteOwnThreads']) && $_POST['canUsersDeleteOwnThreads'] === 'true' ? 1 : 0;
            $threadDeleteTimeoutMinutes = isset($_POST['threadDeleteTimeoutMinutes']) ?
                max(1, min(10080, (int)$_POST['threadDeleteTimeoutMinutes'])) : 30;
            $maxPostLength = isset($_POST['maxPostLength']) ?
                max(100, min(50000, (int)$_POST['maxPostLength'])) : 20000;
            $editTimeoutMinutes = isset($_POST['editTimeoutMinutes']) ?
                max(1, min(10080, (int)$_POST['editTimeoutMinutes'])) : 30;

            $hideLastTopic = isset($_POST['hideLastTopic']) && $_POST['hideLastTopic'] === 'true' ? 1 : 0;
            $titleColor = $_POST['titleColor'] ?? 'dark';

            $sql = "INSERT INTO `forum_categories` (
            `name`, 
            `description`, 
            `parent_id`, 
            `icon_svg`,
            `link`,
            `is_hidden`,
            `can_create_topics`,
            `can_reply_topics`,
            `can_view_topics`,
            `is_moderated`,
            `can_users_delete_own_threads`,
            `thread_delete_timeout_minutes`,
            `edit_timeout_minutes`,
            `notify_telegram`,
            `max_post_length`,
             `hide_last_topic`,
            `created_at`,
            `updated_at`,
            `title_color`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $name,
                $description,
                $parent,
                $icon,
                $link,
                $_POST['isHidden'] === 'true' ? 1 : 0,
                $_POST['canCreateTopics'] === 'true' ? 1 : 0,
                $_POST['canReplyTopics'] === 'true' ? 1 : 0,
                $_POST['canViewTopics'] === 'true' ? 1 : 0,
                $_POST['isModerated'] === 'true' ? 1 : 0,
                $canUsersDeleteOwnThreads,
                $threadDeleteTimeoutMinutes,
                $editTimeoutMinutes,
                $_POST['notifyTelegram'] === 'true' ? 1 : 0,
                $maxPostLength,
                $hideLastTopic,
                time::mysql(),
                time::mysql(),
                $titleColor,
            ];

            $result = sql::run($sql, $params);

            if ($result === false || !$result->rowCount()) {
                throw new Exception("Ошибка при создании раздела");
            }

            board::reload();
            board::success("Раздел успешно создан");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Получение значений чекбоксов для создания секции
     */
    private function getCheckboxFields(): array
    {
        return [
            $_POST['isHidden'] === 'true' ? 1 : 0,
            $_POST['canCreateTopics'] === 'true' ? 1 : 0,
            $_POST['canReplyTopics'] === 'true' ? 1 : 0,
            $_POST['canViewTopics'] === 'true' ? 1 : 0,
            $_POST['isModerated'] === 'true' ? 1 : 0,
            $_POST['notifyTelegram'] === 'true' ? 1 : 0
        ];
    }


    /**
     * Извлекает пути к изображениям из HTML-контента
     *
     * @param string $content HTML-контент
     * @return array Массив путей к изображениям для удаления
     */
    private function extractImagesToDelete(string $content): array {
        $filesToDelete = [];

        // Используем htmlspecialchars для безопасного преобразования
        $content = htmlspecialchars_decode($content);

        $dom = new \DOMDocument();

        // Добавляем обработку ошибок
        $useInternalErrors = libxml_use_internal_errors(true);

        // Добавляем базовую HTML-структуру для корректного парсинга
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $content,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        // Восстанавливаем предыдущее состояние обработки ошибок
        libxml_use_internal_errors($useInternalErrors);

        // Получаем все изображения
        $images = $dom->getElementsByTagName('img');

        // Паттерн для поиска изображений форума
        $pattern = '/\/uploads\/forum\/(\d+)(?:_thumb)?\.png/i';

        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // Проверяем, что путь начинается с /uploads/forum/
            if (str_starts_with($src, '/uploads/forum/')) {
                if (preg_match($pattern, $src, $match)) {
                    $filesToDelete[] = $match[1] . '.png';
                }
            }
        }

        // Возвращаем уникальные значения
        return array_unique($filesToDelete);
    }

    /**
     * Удаляет файлы форума и их миниатюры
     *
     * @param string $filename Имя файла
     */
    private function deleteForumFiles(string $filename): void {
        $originalPath = fileSys::get_dir('/uploads/forum/' . $filename);
        $thumbPath = fileSys::get_dir('/uploads/forum/' .
            pathinfo($filename, PATHINFO_FILENAME) . '_thumb.png');

        if (file_exists($originalPath)) {
            unlink($originalPath);
        }
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
    }

    /**
     * Удаление сообщения
     */
    public function deleteMessage(): void {
        try {
            $messageId = $_POST['messageId'] ?? board::error("Не указано сообщение");
            $post = $this->getPostById($messageId);

            if (!$post) {
                throw new Exception("Сообщение не найдено");
            }

            $thread = $this->getThreadById($post->getThreadId());
            $category = $this->getCategoryById($thread->getCategoryId());

            // Проверка прав
            if (!user::self()->isAdmin() &&
                !ForumModerator::hasPermission(user::self()->getId(), $thread->getCategoryId(), 'can_delete_posts')) {

                if ($post->getUserId() !== user::self()->getId()) {
                    throw new Exception("У вас нет прав на удаление чужих сообщений");
                }

                if (!$category->canUsersDeleteOwnPosts()) {
                    throw new Exception("В данном разделе запрещено удаление своих сообщений");
                }

                if (!$post->isEditableByTime($category->getEditTimeoutMinutes())) {
                    throw new Exception("Время на удаление сообщения истекло");
                }
            }

            // Логируем действие модератора
            if (ForumModerator::isUserModerator(user::self()->getId(), $thread->getCategoryId())) {
                ForumModerator::logAction(
                    user::self()->getId(),
                    'delete_post',
                    'post',
                    $post->getId(),
                    $_POST['reason'] ?? null
                );
            }

            sql::beginTransaction();
            try {
                // Обработка изображений в сообщении
                $filesToDelete = [];


                // Получаем файлы из БД
                $attachedFiles = sql::getRows(
                    "SELECT filename FROM forum_attachments WHERE post_id = ?",
                    [$post->getId()]
                );

                // Получаем изображения для удаления
                $filesToDelete = $this->extractImagesToDelete($post->getContent());
                $filesToDelete = array_unique($filesToDelete);
                // Добавляем прикрепленные файлы в список для удаления
                foreach ($attachedFiles as $file) {
                    $filesToDelete[] = $file['filename'];
                }
                // Удаляем физические файлы
                foreach ($filesToDelete as $filename) {
                    $this->deleteForumFiles($filename);
                }
                // Удаляем записи из БД
                if (!empty($filesToDelete)) {
                    $placeholders = str_repeat('?,', count($filesToDelete) - 1) . '?';
                    sql::run(
                        "DELETE FROM forum_attachments WHERE filename IN ($placeholders)",
                        $filesToDelete
                    );
                }

                // Удаляем лайки к посту
                sql::run("DELETE FROM forum_post_likes WHERE post_id = ?", [$messageId]);

                // Удаляем само сообщение
                sql::run("DELETE FROM forum_posts WHERE id = ?", [$messageId]);

                // Уменьшаем счетчики
                $this->decrementThreadReplies($thread->getId());
                $this->decrementCategoryPostCount($thread->getCategoryId());

                sql::commit();

                board::reload('now');
                board::success("Удалено");

            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    private function decrementThreadReplies(int $threadId): void
    {
        sql::run(
            "UPDATE forum_threads SET replies = GREATEST(replies - 1, 0) WHERE id = ?",
            [$threadId]
        );
    }

    /**
     * Уменьшает счетчик постов в категории
     */
    private function decrementCategoryPostCount(int $categoryId): void
    {
        // Уменьшаем счетчик в текущей категории
        sql::run(
            "UPDATE forum_categories SET post_count = GREATEST(post_count - 1, 0) WHERE id = ?",
            [$categoryId]
        );

        // Обновляем родительские категории
        $this->updateParentCategoryCountersDecrement($categoryId);
    }

    /**
     * Рекурсивно уменьшает счетчик постов в родительских категориях
     */
    private function updateParentCategoryCountersDecrement(int $categoryId): void
    {
        $category = sql::getRow("SELECT parent_id FROM forum_categories WHERE id = ?", [$categoryId]);

        if ($category && $category['parent_id'] !== null) {
            $parentId = (int)$category['parent_id'];

            // Уменьшаем счетчик в родительской категории
            sql::run(
                "UPDATE forum_categories SET post_count = GREATEST(post_count - 1, 0) WHERE id = ?",
                [$parentId]
            );

            // Рекурсивно обновляем родительские категории
            $this->updateParentCategoryCountersDecrement($parentId);
        }
    }

    /**
     * Переименование категории
     */
    public function renameCategory(): void
    {
        try {
            $this->validateAdminRights();

            $categoryId = $_POST['categoryId'] ?? board::error("Не указана категория");
            $newName = $_POST['newName'] ?? board::error("Не указано название");
            $newDescription = $_POST['newDescription'] ?? ""; // Добавляем получение описания
            $newIcon = $_POST['icon'] ?? null; // Добавляем получение иконки
            $titleColor = $_POST['titleColor'] ?? 'dark'; // Добавляем получение цвета

            sql::run(
                "UPDATE `forum_categories` 
             SET `name` = ?, 
                 `description` = ?,
                 `title_color` = ?,
                 `icon_svg` = ?,
                 `updated_at` = ? 
             WHERE `id` = ?",
                [$newName, $newDescription, $titleColor, $newIcon, time::mysql(), $categoryId]
            );

            board::reload();
            board::success("Изменения сохранены");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Перемещение категории
     */
    public function moveCategory(): void
    {
        try {
            $this->validateAdminRights();

            $categoryId = $_POST['categoryId'] ?? board::error("Не указана категория");
            $toMoveCategory = $_POST['toMoveCategory'] ?? board::error("Не указана целевая категория");

            sql::run(
                "UPDATE `forum_categories` SET `parent_id` = ?, `updated_at` = ? WHERE `id` = ?",
                [$toMoveCategory, time::mysql(), $categoryId]
            );

            if ($toMoveCategory == "") {
                board::redirect("/forum");
            } else {
                board::reload();
            }

            board::success("Сохранено");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Удаление темы форума
     * @throws Exception
     */
    public function deleteThread(): void
    {
        try {
            $threadId = $_POST['threadId'] ?? board::error("Не указана тема");
            $thread = $this->getThreadById($threadId);

            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            $category = $this->getCategoryById($thread->getCategoryId());

            // Проверяем права на удаление
            if (!user::self()->isAdmin() &&
                !ForumModerator::hasPermission(user::self()->getId(), $thread->getCategoryId(), 'can_delete_threads')) {

                // Проверяем, может ли пользователь удалить свою тему
                if (!($thread->getAuthorId() === user::self()->getId() &&
                    $thread->canUserDeleteOwnThread($category))) {
                    throw new Exception("Недостаточно прав для удаления темы");
                }
            }

            // Если удаляет модератор, логируем действие
            if (ForumModerator::isUserModerator(user::self()->getId(), $thread->getCategoryId())) {
                ForumModerator::logAction(
                    user::self()->getId(),
                    'delete_thread',
                    'thread',
                    $thread->getId(),
                    $_POST['reason'] ?? null
                );
            }

            sql::beginTransaction();
            try {
                // Получаем количество постов в теме перед удалением
                $postCount = sql::getValue(
                    "SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?",
                    [$threadId]
                );

                // Удаляем тему и все сообщения
                $this->deleteThreadAndRelated($thread);

                // Уменьшаем счетчики в категории
                $this->decrementCategoryCounters($thread->getCategoryId(), $postCount);

                // Обновляем информацию о последней теме в категории
                $this->updateCategoryAfterThreadDeletion($thread);

                sql::commit();

                $categoryName = $this->getCategoryName($thread->getCategoryId());
                $custom_twig = new custom_twig();
                $translitCategoryName = $custom_twig->transliterateToEn($categoryName);

                board::redirect("/forum/{$translitCategoryName}.{$thread->getCategoryId()}");
                board::success("Тема успешно удалена");
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Проверяет права на удаление темы
     *
     * @param forum_thread $thread Тема форума
     * @param forum_category $category Категория форума
     * @return array [bool, string] Возвращает массив [можно_удалить, причина_запрета]
     */
    private function canDeleteThread(forum_thread $thread, forum_category $category): array
    {
        // Администраторы могут удалять любые темы
        if (user::self()->isAdmin()) {
            return [true, ""];
        }

        // Проверяем, является ли пользователь автором темы
        if ($thread->getAuthorId() !== user::self()->getId()) {
            return [false, "Вы не являетесь автором этой темы"];
        }

        // Проверяем, разрешено ли в данной категории удаление тем пользователями
        if (!$category->canUsersDeleteOwnThreads()) {
            return [false, "В данной категории запрещено удаление тем пользователями"];
        }

        // Проверяем временное ограничение
        $createdTime = strtotime($thread->getCreatedAt());
        $currentTime = time();
        $timeoutMinutes = $category->getThreadDeleteTimeoutMinutes();
        $minutesPassed = ($currentTime - $createdTime) / 60;

        if ($minutesPassed > $timeoutMinutes) {
            $hours = floor($minutesPassed / 60);
            $minutes = $minutesPassed % 60;
            $timePassedStr = $hours > 0 ? "{$hours} ч. {$minutes} мин." : "{$minutes} мин.";
            return [false, "Время для удаления темы истекло. С момента создания прошло: {$timePassedStr}. Максимальное время на удаление: {$timeoutMinutes} мин."];
        }

        return [true, ""];
    }


    /**
     * Удаление темы и связанных данных
     */
    private function deleteThreadAndRelated(forum_thread $thread): void {

        if ($thread->getPollId()) {
            // Удаляем голоса
            sql::run(
                "DELETE FROM forum_poll_votes WHERE poll_id = ?",
                [$thread->getPollId()]
            );

            // Удаляем варианты ответов
            sql::run(
                "DELETE FROM forum_poll_options WHERE poll_id = ?",
                [$thread->getPollId()]
            );

            // Удаляем сам опрос
            sql::run(
                "DELETE FROM forum_polls WHERE id = ?",
                [$thread->getPollId()]
            );
        }

        $posts = sql::getRows(
            "SELECT content FROM forum_posts WHERE thread_id = ?",
            [$thread->getId()]
        );

        $filesToDelete = [];

        foreach ($posts as $post) {
            $useInternalErrors = libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML(mb_convert_encoding($post['content'], 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            libxml_use_internal_errors($useInternalErrors);

            $images = $dom->getElementsByTagName('img');

            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                if (strpos($src, '/uploads/forum/') === 0) {
                    if (preg_match('/\/uploads\/forum\/(\d+)(?:_thumb)?\.png/i', $src, $match)) {
                        $filesToDelete[] = $match[1] . '.png';
                    }
                }
            }
        }

        // Получаем неприкрепленные файлы
        $unattachedFiles = sql::getRows(
            "SELECT filename FROM forum_attachments 
         WHERE (post_id IS NULL AND user_id = ?) OR 
               (post_id IN (SELECT id FROM forum_posts WHERE thread_id = ?))",
            [user::self()->getId(), $thread->getId()]
        );

        foreach ($unattachedFiles as $file) {
            $filesToDelete[] = $file['filename'];
        }

        $filesToDelete = array_unique($filesToDelete);

        // Удаляем физические файлы
        foreach ($filesToDelete as $filename) {
            $originalPath = fileSys::get_dir('/uploads/forum/' . $filename);
            $thumbPath = fileSys::get_dir('/uploads/forum/' .
                pathinfo($filename, PATHINFO_FILENAME) . '_thumb.png');

            if (file_exists($originalPath)) {
                unlink($originalPath);
            }
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }

        // Удаляем записи из БД, используя полные имена файлов
        if (!empty($filesToDelete)) {
            $placeholders = str_repeat('?,', count($filesToDelete) - 1) . '?';
            sql::run(
                "DELETE FROM forum_attachments 
             WHERE filename IN ($placeholders)",
                $filesToDelete
            );
        }

        // Удаляем отслеживания тем
        sql::run("DELETE FROM forum_user_thread_tracks WHERE thread_id = ?",
            [$thread->getId()]
        );

        // Удаляем уведомления
        sql::run("DELETE FROM forum_notifications WHERE thread_id = ?",
            [$thread->getId()]
        );

        // Удаляем лайки постов
        sql::run(
            "DELETE fl FROM forum_post_likes fl 
        INNER JOIN forum_posts fp ON fl.post_id = fp.id 
        WHERE fp.thread_id = ?",
            [$thread->getId()]
        );

        // Удаляем посты темы
        sql::run("DELETE FROM forum_posts WHERE thread_id = ?",
            [$thread->getId()]
        );

        // Удаляем саму тему
        sql::run("DELETE FROM forum_threads WHERE id = ?",
            [$thread->getId()]
        );
    }

    /**
     * Уменьшает счетчики категории при удалении темы
     */
    private function decrementCategoryCounters(int $categoryId, int $postCount): void
    {
        // Уменьшаем счетчики в текущей категории
        sql::run(
            "UPDATE forum_categories SET 
        thread_count = GREATEST(thread_count - 1, 0),
        post_count = GREATEST(post_count - ?, 0)
        WHERE id = ?",
            [$postCount, $categoryId]
        );

        // Обновляем счетчики родительских категорий
        $this->updateParentCategoryCountersDecrementBulk($categoryId, $postCount);
    }

    /**
     * Рекурсивно уменьшает счетчики в родительских категориях при удалении тем или сообщений
     *
     * @param int $categoryId ID текущей категории
     * @param int $postCount Количество удаляемых постов
     * @param bool $decrementThreadCount Нужно ли уменьшать счетчик тем (по умолчанию true)
     */
    private function updateParentCategoryCountersDecrementBulk(int $categoryId, int $postCount, bool $decrementThreadCount = true): void
    {
        // Получаем информацию о категории и проверяем существование родителя
        $category = sql::getRow(
            "SELECT c.*, p.id as parent_exists 
         FROM forum_categories c 
         LEFT JOIN forum_categories p ON c.parent_id = p.id 
         WHERE c.id = ? 
         LIMIT 1",
            [$categoryId]
        );

        // Проверяем что категория существует и имеет родителя
        if ($category && $category['parent_id'] !== null && $category['parent_exists']) {
            $parentId = (int)$category['parent_id'];

            if ($decrementThreadCount) {
                // Уменьшаем оба счетчика - и постов, и тем
                sql::run(
                    "UPDATE forum_categories 
                 SET post_count = GREATEST(post_count - ?, 0),
                     thread_count = GREATEST(thread_count - 1, 0),
                     updated_at = ? 
                 WHERE id = ?",
                    [$postCount, time::mysql(), $parentId]
                );
            } else {
                // Уменьшаем только счетчик постов
                sql::run(
                    "UPDATE forum_categories 
                 SET post_count = GREATEST(post_count - ?, 0),
                     updated_at = ? 
                 WHERE id = ?",
                    [$postCount, time::mysql(), $parentId]
                );
            }

            // Получаем последний пост в категории после обновления
            $lastPost = sql::getRow(
                "SELECT p.id, p.thread_id, p.user_id
             FROM forum_posts p
             JOIN forum_threads t ON p.thread_id = t.id 
             WHERE t.category_id = ?
             ORDER BY p.created_at DESC
             LIMIT 1",
                [$parentId]
            );

            if ($lastPost) {
                // Обновляем информацию о последнем посте
                sql::run(
                    "UPDATE forum_categories 
                 SET last_post_id = ?,
                     last_thread_id = ?,
                     last_reply_user_id = ?
                 WHERE id = ?",
                    [
                        $lastPost['id'],
                        $lastPost['thread_id'],
                        $lastPost['user_id'],
                        $parentId
                    ]
                );
            } else {
                // Если постов нет, очищаем информацию
                sql::run(
                    "UPDATE forum_categories 
                 SET last_post_id = NULL,
                     last_thread_id = NULL,
                     last_reply_user_id = NULL
                 WHERE id = ?",
                    [$parentId]
                );
            }

            // Рекурсивно обновляем родительские категории выше по иерархии
            $this->updateParentCategoryCountersDecrementBulk(
                $parentId,
                $postCount,
                $decrementThreadCount
            );
        }
    }

    /**
     * Обновление категории после удаления темы
     */
    private function updateCategoryAfterThreadDeletion(forum_thread $thread): void
    {
        $category = $this->getCategoryById($thread->getCategoryId());

        if ($category->getLastThreadId() == $thread->getId()) {
            $lastThread = sql::getRow(
                "SELECT * FROM `forum_threads` WHERE `category_id` = ? ORDER BY `updated_at` DESC LIMIT 1",
                [$thread->getCategoryId()]
            );

            if ($lastThread) {
                $lastThread = new forum_thread($lastThread);
                sql::run(
                    "UPDATE `forum_categories` SET `last_thread_id` = ? WHERE `last_thread_id` = ?",
                    [$lastThread->getId(), $thread->getId()]
                );
            }
        }
    }

    /**
     * Перемещение темы в другую категорию
     */
    public function moveThread(): void
    {
        try {
            $this->validateAdminRights();

            $threadId = $_POST['threadId'] ?? board::error("Не указана тема");
            $newCategoryId = $_POST['newCategoryId'] ?? board::error("Не указана новая категория");

            // Получаем информацию о теме
            $thread = $this->getThreadById($threadId);
            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            $oldCategoryId = $thread->getCategoryId();

            sql::beginTransaction();
            try {
                // Получаем количество постов в теме
                $postCount = sql::getValue(
                    "SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?",
                    [$threadId]
                );

                // Уменьшаем счетчики в старой категории
                $this->decrementCategoryCounters($oldCategoryId, $postCount);

                // Перемещаем тему
                sql::run(
                    "UPDATE forum_threads SET category_id = ?, updated_at = ? WHERE id = ?",
                    [$newCategoryId, time::mysql(), $threadId]
                );

                // Увеличиваем счетчики в новой категории
                $this->incrementCategoryCounters($newCategoryId);

                // Обновляем информацию о последней теме в обеих категориях
                $this->updateCategoryAfterThreadMove($oldCategoryId);
                $this->updateCategoryAfterThreadMove($newCategoryId);

                sql::commit();

                // Редирект на тему
                $title = $thread->getTitle();
                $custom_twig = new custom_twig();
                $translit = $custom_twig->transliterateToEn($title);

                board::redirect("/forum/topic/{$translit}.{$threadId}");
                board::success("Тема перемещена");
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Обновление информации о последней теме в категории
     */
    private function updateCategoryAfterThreadMove(int $categoryId): void
    {
        $lastThread = sql::getRow(
            "SELECT * FROM forum_threads WHERE category_id = ? ORDER BY updated_at DESC LIMIT 1",
            [$categoryId]
        );

        if ($lastThread) {
            sql::run(
                "UPDATE forum_categories 
            SET last_thread_id = ?, 
                last_post_id = ?, 
                last_reply_user_id = ?,
                updated_at = ? 
            WHERE id = ?",
                [
                    $lastThread['id'],
                    $lastThread['last_post_id'],
                    $lastThread['last_reply_user_id'],
                    time::mysql(),
                    $categoryId
                ]
            );
        } else {
            // Если тем нет, очищаем информацию
            sql::run(
                "UPDATE forum_categories 
            SET last_thread_id = NULL, 
                last_post_id = NULL, 
                last_reply_user_id = NULL,
                updated_at = ? 
            WHERE id = ?",
                [time::mysql(), $categoryId]
            );
        }
    }

    /**
     * Переименование темы
     */
    public function renameThread(): void
    {
        try {
            $this->validateAdminRights();

            $threadId = $_POST['threadId'] ?? board::error("Не указана тема");
            $title = $_POST['title'] ?? board::error("Не указано новое название");

            if (empty(trim($title))) {
                throw new Exception("Название темы не может быть пустым");
            }

            // Получаем информацию о теме
            $thread = $this->getThreadById($threadId);
            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            sql::beginTransaction();
            try {
                // Обновляем название темы
                sql::run(
                    "UPDATE forum_threads SET title = ?, updated_at = ? WHERE id = ?",
                    [$title, time::mysql(), $threadId]
                );

                sql::commit();

                // Редирект на тему с новым названием
                $custom_twig = new custom_twig();
                $translit = $custom_twig->transliterateToEn($title);

                board::redirect("/forum/topic/{$translit}.{$threadId}");
                board::success("Тема переименована");
            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Добавление лайка к посту
     */
    /**
     * Добавление лайка к посту
     */
    public function addLike(): void
    {
        try {
            if (!user::self()->isAuth()) {
                throw new Exception("Необходимо авторизоваться");
            }

            $postId = $_POST['postId'] ?? board::error("Не указан пост");
            $likeImage = $_POST['likeImage'] ?? board::error("Не указано изображение лайка");
            $message = $_POST['message'] ?? "";

            // Получаем информацию о посте
            $post = sql::getRow("SELECT id, user_id FROM forum_posts WHERE id = ?", [$postId]);
            if (!$post) {
                throw new Exception("Пост не найден");
            }

            // Проверяем, не является ли пользователь автором поста
            if ($post['user_id'] == user::self()->getId()) {
                throw new Exception("Нельзя ставить лайк своим сообщениям");
            }

            // Какой пользователь получит бафф
            $to_user = $post['user_id'];

            sql::beginTransaction();
            try {
                // Проверяем, не ставил ли уже пользователь лайк этому посту
                $existingLike = sql::getRow(
                    "SELECT id FROM forum_post_likes WHERE post_id = ? AND user_id = ?",
                    [$postId, user::self()->getId()]
                );

                if ($existingLike) {
                    // Если лайк уже есть - обновляем его
                    sql::run(
                        "UPDATE forum_post_likes SET like_image = ? WHERE id = ?",
                        [$likeImage, $existingLike['id']]
                    );
                } else {
                    // Если лайка нет - добавляем новый
                    sql::run(
                        "INSERT INTO forum_post_likes (post_id, user_id, to_user, like_image) VALUES (?, ?, ?, ?)",
                        [$postId, user::self()->getId(), $to_user, $likeImage]
                    );
                }

                sql::commit();

                $ct = new custom_twig();
                // Получаем все лайки поста для ответа
                $likes = $ct->getPostLikes($postId);

                // Отправляем ответ в JSON формате
                echo json_encode([
                    'ok' => true,
                    'message' => 'Лайк добавлен',
                    'likes' => $likes
                ]);
                die();

            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Получение списка лайков поста
     */
    private function getPostLikes(int $postId): array
    {
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
     * Загружает изображение и возвращает информацию о нем
     *
     * @return void
     * @throws Exception
     */
    public function uploadImage(): void
    {
        try {
            if (!user::self()->isAuth()) {
                throw new Exception("Необходимо авторизоваться");
            }

            // Проверяем наличие загруженного файла
            if (!isset($_FILES['filepond'])) {
                throw new Exception("Файл не был получен");
            }

            $manager = ImageManager::gd();

            // Проверка лимита загрузок для обычных пользователей
            if (!user::self()->isAdmin()) {
                $time = time() - 600; // 10 минут
                $row = sql::getRow(
                    "SELECT count(*) AS `count` 
                FROM `forum_attachments` 
                WHERE created_at > ? AND user_id = ?",
                    [
                        date('Y-m-d H:i:s', $time),
                        user::self()->getId(),
                    ]
                );
                if ($row['count'] > 6) {
                    throw new Exception('Вы загрузили больше 6 изображений за последние 10 минут.');
                }
            }

            $files = $_FILES['filepond'];
            $fileCount = is_array($files['tmp_name']) ? count($files['tmp_name']) : 1;

            if ($fileCount > 6) {
                throw new Exception('Одновременно можно загрузить не более 6 изображений');
            }

            // Создаем директорию если её нет
            $uploadDir = fileSys::get_dir('/uploads/forum/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Обработка файла
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][0] : $files['tmp_name'];
            $error = is_array($files['error']) ? $files['error'][0] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception("Ошибка при загрузке файла");
            }

            // Читаем изображение
            $image = $manager->read($tmpName);

            // Получаем исходные размеры
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Генерируем уникальное имя
            $filename = mt_rand(1, PHP_INT_MAX) . '.png';

            // Сохраняем оригинальное изображение
            if (!$image->save($uploadDir . $filename)) {
                throw new Exception("Ошибка при сохранении изображения");
            }

            // Создаем миниатюру
            $thumbImage = $image;
            if ($originalHeight > 300) {
                $thumbImage = $image->scale(height: 300);
            }
            if ($originalWidth > 300) {
                $thumbImage = $image->scale(width: 300);
            }

            $thumb = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.png';
            if (!$thumbImage->save($uploadDir . $thumb)) {
                throw new Exception("Ошибка при сохранении миниатюры");
            }

            // Записываем информацию в базу
            sql::run(
                "INSERT INTO forum_attachments SET 
            user_id = ?,
            filename = ?,
            original_filename = ?,
            file_size = ?,
            mime_type = ?,
            created_at = ?",
                [
                    user::self()->getId(),
                    $filename,
                    $files['name'],
                    $files['size'],
                    $files['type'],
                    time::mysql()
                ]
            );

            $attachmentId = sql::lastInsertId();

            // Возвращаем результат в формате для FilePond
            echo json_encode([
                'success' => true,
                'file' => [
                    'id' => $attachmentId,
                    'url' => '/uploads/forum/' . $filename,
                    'thumbnail' => '/uploads/forum/' . $thumb,
                    'name' => $files['name']
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    // Функция одобрения темы для админов

    public function approveThread(): void
    {
        try {
            if (!user::self()->isAdmin()) {
                throw new Exception("Недостаточно прав");
            }

            $threadId = $_POST['threadId'] ?? board::error("Не указана тема");

            sql::run(
                "UPDATE forum_threads SET is_approved = 1 WHERE id = ?",
                [$threadId]
            );

            board::success("Тема одобрена");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function applyApprove(): void
    {
        try {
            if (!user::self()->isAdmin()) {
                throw new Exception("Недостаточно прав для выполнения операции");
            }

            $threadId = $_POST['threadId'] ?? throw new Exception("Не указан ID темы");

            // Получаем информацию о теме
            $thread = $this->getThreadById($threadId);
            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            // Получаем категорию для проверки настроек модерации
            $category = $this->getCategoryById($thread->getCategoryId());
            if (!$category->isModerated()) {
                throw new Exception("Для данной категории не требуется модерация");
            }

            sql::beginTransaction();
            try {
                // Подтверждаем тему
                sql::run(
                    "UPDATE forum_threads 
                SET is_approved = 1,
                    approved_by = ?,
                    approved_at = ? 
                WHERE id = ?",
                    [user::self()->getId(), time::mysql(), $threadId]
                );

                // Обновляем счетчики категории
                $this->incrementCategoryCounters($thread->getCategoryId());

                sql::commit();
                board::reload();
                board::success("Тема успешно подтверждена");

            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function updateSection(): void
    {
        try {
            $this->validateAdminRights();

            $sectionId = $_POST['sectionId'] ?? board::error("Не указан раздел");
            $name = $_POST['name'] ?? board::error("Не указано название");
            $link = $_POST['link'] ?? board::error("Не указана ссылка");
            $icon = $_POST['icon'] ?? null;

            // Проверяем корректность URL
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                board::error("Некорректный URL");
            }

            sql::run(
                "UPDATE `forum_categories` SET `name` = ?, `link` = ?, `icon_svg` = ?, `updated_at` = ? WHERE `id` = ?",
                [$name, $link, $icon, time::mysql(), $sectionId]
            );

            board::reload();
            board::success("Раздел обновлен");
        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function moveOrder(): void
    {
        try {
            $this->validateAdminRights();

            $orders = $_POST['orders'] ?? throw new Exception("Не указан порядок категорий");
            $type = $_POST['type'] ?? 'category';
            sql::beginTransaction();
            try {
                if ($type === 'category') {
                    // Обработка сортировки основных категорий
                    foreach ($orders as $categoryId => $order) {
                        sql::run(
                            "UPDATE forum_categories SET sort_order = ? WHERE id = ?",
                            [(int)$order, (int)$categoryId]
                        );
                    }
                } else {
                    // Обработка сортировки подкатегорий
                    foreach ($orders as $sectionId => $data) {
                        sql::run(
                            "UPDATE forum_categories SET 
                            sort_order = ?, 
                            parent_id = ?,
                            updated_at = ?
                        WHERE id = ?",
                            [
                                (int)$data['order'],
                                (int)$data['categoryId'],
                                time::mysql(),
                                (int)$sectionId
                            ]
                        );
                    }
                }

                sql::commit();

            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Получает информацию о посте по его ID
     *
     * @param int $postId ID поста
     * @return forum_post|null Возвращает объект поста или null, если пост не найден
     */
    private function getPostById(int $postId): ?forum_post
    {
        try {
            // Получаем данные поста из БД
            $postData = sql::getRow(
                "SELECT p.*, t.title as thread_title 
             FROM forum_posts p 
             LEFT JOIN forum_threads t ON p.thread_id = t.id 
             WHERE p.id = ?",
                [$postId]
            );

            // Если пост не найден, возвращаем null
            if (!$postData) {
                return null;
            }

            // Создаем и возвращаем объект поста
            return new forum_post($postData);

        } catch (Exception $e) {
            // Логируем ошибку
            error_log("Error getting post by ID {$postId}: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Валидация названия темы
     *
     * @param string $title Название темы
     * @throws Exception
     */
    private function validateTopicTitle(string $title): void
    {
        // Очищаем от пробелов в начале и конце
        $title = trim($title);

        // Проверяем минимальную длину
        if (mb_strlen($title) < 3) {
            throw new Exception("Название темы должно содержать минимум 3 символа");
        }

        // Проверяем максимальную длину
        if (mb_strlen($title) > 60) {
            throw new Exception("Название темы не может быть длиннее 60 символов");
        }

        // Проверяем на пустоту после удаления HTML тегов
        $plainTitle = strip_tags($title);
        if (empty($plainTitle)) {
            throw new Exception("Название темы не может быть пустым");
        }

        // Проверяем на повторяющиеся символы
        if (preg_match('/^(.)\1+$/', $plainTitle)) {
            throw new Exception("Название темы не может состоять из повторяющихся символов");
        }
    }


    public function showModeratorPanel(): void
    {
        if (!user::self()->isAdmin()) {
            redirect::location("/forum");
            return;
        }

        // Получаем данные из базы данных
        $moderatorsData = sql::getRows(
            "SELECT 
            m.*,
            u.name as user_name,
            c.name as category_name 
        FROM forum_moderators m
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN forum_categories c ON m.category_id = c.id
        ORDER BY m.created_at DESC"
        );

        // Преобразуем данные в массив объектов ForumModerator
        $moderators = array_map(function ($data) {
            $moderator = new ForumModerator();
            $moderator->setId($data['id']);
            $moderator->setUserId($data['user_id']);
            $moderator->setCategoryId($data['category_id']);
            $moderator->setName($data['user_name']);
            $moderator->setCategoryName($data['category_name']);
            $moderator->setCanDeleteThreads((bool)$data['can_delete_threads']);
            $moderator->setCanDeletePosts((bool)$data['can_delete_posts']);
            $moderator->setCanEditPosts((bool)$data['can_edit_posts']);
            $moderator->setCanMoveThreads((bool)$data['can_move_threads']);
            $moderator->setCanPinThreads((bool)$data['can_pin_threads']);
            $moderator->setCanCloseThreads((bool)$data['can_close_threads']);
            $moderator->setCanApproveThreads((bool)$data['can_approve_threads']);
            $moderator->setCreatedAt($data['created_at']);
            $moderator->setCreatedBy($data['created_by']);

            return $moderator;
        }, $moderatorsData);

        $custom = new custom_twig();
        $categories = $custom->getCategoriesForum();

        tpl::addVar([
            "moderators" => $moderators,
            "categories" => $categories
        ]);

        tpl::displayPlugin("sphere_forum/tpl/moderator_panel.html");
    }

    public function addModerator(): void
    {
        try {
            if (!user::self()->isAdmin()) {
                throw new Exception("Недостаточно прав");
            }

            $username = $_POST['username'] ?? throw new Exception("Не указан пользователь");

            // Корректно обрабатываем categoryId
            $categoryId = isset($_POST['categoryId']) && $_POST['categoryId'] !== ''
                ? (int)$_POST['categoryId']
                : null;

            $permissions = $_POST['permissions'] ?? throw new Exception("Не указаны права");

            // Получаем пользователя по имени
            $user = user::getUserByName($username);
            if (!$user) {
                throw new Exception("Пользователь не найден");
            }

            // Проверяем, не является ли пользователь уже модератором
            if (ForumModerator::isUserModerator($user->getId(), $categoryId)) {
                if ($categoryId === null) {
                    throw new Exception("Пользователь уже является глобальным модератором");
                } else {
                    throw new Exception("Пользователь уже является модератором этой категории");
                }
            }

            // Преобразуем права из JSON в булевы значения
            $permissionsData = is_string($permissions) ? json_decode($permissions, true) : $permissions;

            // Явно преобразуем строковые значения в булевы
            $parsedPermissions = [
                'can_delete_threads' => filter_var($permissionsData['canDeleteThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_delete_posts' => filter_var($permissionsData['canDeletePosts'], FILTER_VALIDATE_BOOLEAN),
                'can_edit_posts' => filter_var($permissionsData['canEditPosts'], FILTER_VALIDATE_BOOLEAN),
                'can_move_threads' => filter_var($permissionsData['canMoveThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_pin_threads' => filter_var($permissionsData['canPinThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_close_threads' => filter_var($permissionsData['canCloseThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_approve_threads' => filter_var($permissionsData['canApproveThreads'], FILTER_VALIDATE_BOOLEAN)
            ];

            sql::run(
                "INSERT INTO forum_moderators 
            (user_id, category_id, can_delete_threads, can_delete_posts, can_edit_posts, 
             can_move_threads, can_pin_threads, can_close_threads, can_approve_threads, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user->getId(),
                    $categoryId,
                    $parsedPermissions['can_delete_threads'] ? 1 : 0,
                    $parsedPermissions['can_delete_posts'] ? 1 : 0,
                    $parsedPermissions['can_edit_posts'] ? 1 : 0,
                    $parsedPermissions['can_move_threads'] ? 1 : 0,
                    $parsedPermissions['can_pin_threads'] ? 1 : 0,
                    $parsedPermissions['can_close_threads'] ? 1 : 0,
                    $parsedPermissions['can_approve_threads'] ? 1 : 0,
                    user::self()->getId()
                ]
            );

            board::success($categoryId === null
                ? "Добавлен глобальный модератор"
                : "Модератор успешно добавлен для выбранной категории"
            );

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function deleteModerator(): void
    {
        try {
            if (!user::self()->isAdmin()) {
                throw new Exception("Недостаточно прав");
            }

            $moderatorId = $_POST['moderatorId'] ?? throw new Exception("Не указан ID модератора");

            sql::run(
                "DELETE FROM forum_moderators WHERE id = ?",
                [$moderatorId]
            );

            board::success("Модератор успешно удален");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function editModerator(): void
    {
        try {
            if (!user::self()->isAdmin()) {
                throw new Exception("Недостаточно прав");
            }

            $moderatorId = $_POST['moderatorId'] ?? throw new Exception("Не указан ID модератора");

            // Корректно обрабатываем categoryId
            $categoryId = isset($_POST['categoryId']) && $_POST['categoryId'] !== ''
                ? (int)$_POST['categoryId']
                : null;

            $permissions = $_POST['permissions'] ?? throw new Exception("Не указаны права");

            // Проверяем существование модератора
            $moderator = sql::getRow(
                "SELECT * FROM forum_moderators WHERE id = ?",
                [$moderatorId]
            );

            if (!$moderator) {
                throw new Exception("Модератор не найден");
            }

            // Преобразуем права из JSON в булевы значения
            $permissionsData = is_string($permissions) ? json_decode($permissions, true) : $permissions;

            // Явно преобразуем строковые значения в булевы
            $parsedPermissions = [
                'can_delete_threads' => filter_var($permissionsData['canDeleteThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_delete_posts' => filter_var($permissionsData['canDeletePosts'], FILTER_VALIDATE_BOOLEAN),
                'can_edit_posts' => filter_var($permissionsData['canEditPosts'], FILTER_VALIDATE_BOOLEAN),
                'can_move_threads' => filter_var($permissionsData['canMoveThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_pin_threads' => filter_var($permissionsData['canPinThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_close_threads' => filter_var($permissionsData['canCloseThreads'], FILTER_VALIDATE_BOOLEAN),
                'can_approve_threads' => filter_var($permissionsData['canApproveThreads'], FILTER_VALIDATE_BOOLEAN)
            ];

            sql::run(
                "UPDATE forum_moderators SET 
                category_id = ?,
                can_delete_threads = ?,
                can_delete_posts = ?,
                can_edit_posts = ?,
                can_move_threads = ?,
                can_pin_threads = ?,
                can_close_threads = ?,
                can_approve_threads = ?
            WHERE id = ?",
                [
                    $categoryId,
                    $parsedPermissions['can_delete_threads'] ? 1 : 0,
                    $parsedPermissions['can_delete_posts'] ? 1 : 0,
                    $parsedPermissions['can_edit_posts'] ? 1 : 0,
                    $parsedPermissions['can_move_threads'] ? 1 : 0,
                    $parsedPermissions['can_pin_threads'] ? 1 : 0,
                    $parsedPermissions['can_close_threads'] ? 1 : 0,
                    $parsedPermissions['can_approve_threads'] ? 1 : 0,
                    $moderatorId
                ]
            );

            // Добавляем запись в лог модерации
            ForumModerator::logAction(
                user::self()->getId(),
                'edit_moderator',
                'moderator',
                $moderatorId,
                'Изменены права модератора'
            );

            board::success("Права модератора успешно обновлены");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function toggleThreadStatus(): void
    {
        try {
            // Получаем необходимые параметры
            $threadId = $_POST['threadId'] ?? throw new Exception("Не указан ID темы");
            $newStatus = $_POST['status'] ?? throw new Exception("Не указан новый статус");

            // Получаем информацию о теме
            $thread = $this->getThreadById($threadId);
            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            // Получаем категорию
            $category = $this->getCategoryById($thread->getCategoryId());

            // Проверяем права
            if (!user::self()->isAdmin() &&
                !ForumModerator::hasPermission(user::self()->getId(), $category->getId(), 'can_close_threads')) {
                throw new Exception("Недостаточно прав для выполнения этого действия");
            }

            // Обновляем статус темы
            sql::run(
                "UPDATE forum_threads SET is_closed = ? WHERE id = ?",
                [$newStatus === 'close' ? 1 : 0, $threadId]
            );

            // Логируем действие модератора
            if (ForumModerator::isUserModerator(user::self()->getId(), $category->getId())) {
                ForumModerator::logAction(
                    user::self()->getId(),
                    $newStatus === 'close' ? 'close_thread' : 'open_thread',
                    'thread',
                    $thread->getId()
                );
            }

            board::reload();
            board::success($newStatus === 'close' ? "Тема закрыта" : "Тема открыта");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }


    /**
     * Обновляет права категории
     */
    public function updateCategoryPermissions(): void
    {
        try {
            $this->validateAdminRights();

            // Получаем и валидируем ID категории
            $categoryId = filter_input(INPUT_POST, 'categoryId', FILTER_VALIDATE_INT);
            if (!$categoryId) {
                throw new Exception("Некорректный ID категории");
            }

            // Получаем права из POST
            $permissions = $_POST['permissions'] ?? throw new Exception("Не указаны права доступа");

            // Создаем массив с значениями по умолчанию и валидацией
            $sanitizedPermissions = [
                'isHidden' => $this->sanitizeBool($permissions['is_hidden'] ?? false),
                'canCreateTopics' => $this->sanitizeBool($permissions['can_create_topics'] ?? true),
                'canReplyTopics' => $this->sanitizeBool($permissions['can_reply_topics'] ?? true),
                'canViewTopics' => $this->sanitizeBool($permissions['can_view_topics'] ?? true),
                'isModerated' => $this->sanitizeBool($permissions['is_moderated'] ?? false),
                'canUsersDeleteOwnThreads' => $this->sanitizeBool($permissions['can_users_delete_own_threads'] ?? false),
                'threadDeleteTimeoutMinutes' => $this->sanitizeInt(
                    $permissions['thread_delete_timeout_minutes'] ?? 30,
                    1,      // минимальное значение
                    10080,  // максимальное значение (7 дней)
                    30      // значение по умолчанию
                ),
                'canUsersDeleteOwnPosts' => $this->sanitizeBool($permissions['can_users_delete_own_posts'] ?? false),
                'maxPostLength' => $this->sanitizeInt(
                    $permissions['max_post_length'] ?? 10000,
                    100,    // минимальная длина
                    50000,  // максимальная длина
                    10000   // значение по умолчанию
                ),
                'hideLastTopic' => $this->sanitizeBool($permissions['hide_last_topic'] ?? false),
                'notifyTelegram' => $this->sanitizeBool($permissions['notify_telegram'] ?? false)
            ];

            // Проверяем существование категории
            $category = sql::getRow("SELECT id FROM forum_categories WHERE id = ?", [$categoryId]);
            if (!$category) {
                throw new Exception("Категория не найдена");
            }

            sql::beginTransaction();
            try {
                // Обновляем права категории
                sql::run(
                    "UPDATE forum_categories SET 
                    is_hidden = ?,
                    can_create_topics = ?,
                    can_reply_topics = ?,
                    can_view_topics = ?,
                    is_moderated = ?,
                    can_users_delete_own_threads = ?,
                    thread_delete_timeout_minutes = ?,
                    can_users_delete_own_posts = ?,
                    max_post_length = ?,
                    hide_last_topic = ?,
                    notify_telegram = ?,
                    updated_at = ?
                WHERE id = ?",
                    [
                        $sanitizedPermissions['isHidden'],
                        $sanitizedPermissions['canCreateTopics'],
                        $sanitizedPermissions['canReplyTopics'],
                        $sanitizedPermissions['canViewTopics'],
                        $sanitizedPermissions['isModerated'],
                        $sanitizedPermissions['canUsersDeleteOwnThreads'],
                        $sanitizedPermissions['threadDeleteTimeoutMinutes'],
                        $sanitizedPermissions['canUsersDeleteOwnPosts'],
                        $sanitizedPermissions['maxPostLength'],
                        $sanitizedPermissions['hideLastTopic'],
                        $sanitizedPermissions['notifyTelegram'],
                        time::mysql(),
                        $categoryId
                    ]
                );

                sql::commit();
                board::reload();
                board::success("Права категории успешно обновлены");

            } catch (Exception $e) {
                sql::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    /**
     * Безопасное преобразование в булево значение
     */
    private function sanitizeBool($value): int
    {
        if (is_string($value)) {
            return strtolower($value) === 'true' || $value === '1' ? 1 : 0;
        }
        return $value ? 1 : 0;
    }

    /**
     * Безопасное преобразование в целое число с ограничениями
     */
    private function sanitizeInt($value, int $min, int $max, int $default): int
    {
        $value = (int)$value;
        if ($value < $min || $value > $max) {
            return $default;
        }
        return $value;
    }

    public static function telegramNotice($title, $topicId): void
    {
        if (!config::load()->notice()->isTelegramEnable()) {
            return;
        }

        $custom_twig = new custom_twig();
        $translit = $custom_twig->transliterateToEn($title);
        $link = "/forum/topic/{$translit}.{$topicId}";

        $msg = "Пользователь %s (%s) создал новую тему %s.\n<a href='%s'>Открыть ссылку</a>";
        $msg = sprintf($msg, user::self()->getEmail(), user::self()->getName(), $title, \Ofey\Logan22\component\request\url::host($link));
        telegram::sendTelegramMessage($msg);

    }

    public function toggleThreadPin(): void
    {
        try {
            if (!user::self()->isAdmin() &&
                !ForumModerator::hasPermission(user::self()->getId(), null, 'can_pin_threads')) {
                throw new Exception("Недостаточно прав");
            }

            $threadId = $_POST['threadId'] ?? board::error("Не указана тема");
            $status = $_POST['status'] ?? 'pin';

            // Получаем информацию о теме
            $thread = $this->getThreadById($threadId);
            if (!$thread) {
                throw new Exception("Тема не найдена");
            }

            // Обновляем статус закрепления
            sql::run(
                "UPDATE forum_threads SET is_pinned = ?, updated_at = ? WHERE id = ?",
                [$status === 'pin' ? 1 : 0, time::mysql(), $threadId]
            );

            // Логируем действие модератора
            if (ForumModerator::isUserModerator(user::self()->getId(), $thread->getCategoryId())) {
                ForumModerator::logAction(
                    user::self()->getId(),
                    $status === 'pin' ? 'pin_thread' : 'unpin_thread',
                    'thread',
                    $thread->getId()
                );
            }

            board::success($status === 'pin' ? "Тема закреплена" : "Тема откреплена");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }

    public function updateUserSettings(): void
    {
        try {
            if (!user::self()->isAuth()) {
                throw new Exception("Необходимо авторизоваться");
            }
            $setting = $_POST['setting'] ?? throw new Exception("Не указана настройка");
            $value = filter_var($_POST['value'], FILTER_VALIDATE_BOOLEAN);

            // Получаем текущие настройки
            $customTwig = new custom_twig();
            $currentSettings = $customTwig->getForumUserSettings(user::self()->getId());

            // Обновляем конкретную настройку
            $currentSettings[$setting] = $value;

            // Сохраняем обновленные настройки
            $customTwig->saveForumUserSettings(user::self()->getId(), $currentSettings);

            board::success("Настройки сохранены");

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }


    // В классе Forum добавим метод:
    private function hasUnreadPosts(forum_thread $thread): bool {
        if (!user::self()->isAuth()) {
            return false;
        }

        // Получаем информацию о последнем прочитанном посте
        $lastRead = sql::getRow(
            "SELECT last_read_post_id 
         FROM forum_user_thread_tracks 
         WHERE user_id = ? AND thread_id = ?",
            [user::self()->getId(), $thread->getId()]
        );

        // Если пользователь никогда не читал тему - она считается непрочитанной
        if (!$lastRead) {
            return true;
        }

        // Проверяем, есть ли посты новее последнего прочитанного
        $newerPosts = sql::getValue(
            "SELECT COUNT(*) 
         FROM forum_posts 
         WHERE thread_id = ? AND id > ?",
            [$thread->getId(), $lastRead['last_read_post_id']]
        );

        return $newerPosts > 0;
    }

    public function votePoll(): void {
        try {
            if (!user::self()->isAuth()) {
                throw new Exception("Необходимо авторизоваться");
            }

            $threadId = $_POST['threadId'] ?? throw new Exception("Не указан ID темы");
            $optionIds = $_POST['options'] ?? throw new Exception("Не выбраны варианты");

            $thread = $this->getThreadById($threadId);
            if (!$thread || !$thread->getPoll()) {
                throw new Exception("Опрос не найден");
            }

            $poll = $thread->getPoll();
            $result = $poll->vote(user::self()->getId(), $optionIds);

            if (!$result) {
                throw new Exception("Не удалось проголосовать");
            }

            board::success('Голос учтен');

        } catch (Exception $e) {
            board::error($e->getMessage());
        }
    }


}