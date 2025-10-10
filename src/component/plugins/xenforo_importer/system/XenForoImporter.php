<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

use Exception;
use Ofey\Logan22\model\db\sql;
use PDO;

/**
 * Класс для импорта данных из XenForo
 */
class XenForoImporter
{
    private XenForoConnection $xenforoDb;
    private BBCodeParser $bbCodeParser;
    private array $importStats = [
        'users' => ['total' => 0, 'imported' => 0, 'skipped' => 0, 'updated' => 0, 'errors' => []],
        'categories' => ['total' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []],
        'threads' => ['total' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []],
        'posts' => ['total' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []],
        'images' => ['total' => 0, 'downloaded' => 0],
    ];

    private array $userMapping = []; // XenForo user_id => SphereWeb user_id
    private array $categoryMapping = []; // XenForo node_id => SphereWeb category_id
    private array $threadMapping = []; // XenForo thread_id => SphereWeb thread_id
    private array $postMapping = []; // XenForo post_id => SphereWeb post_id

    public function __construct(XenForoConnection $xenforoDb)
    {
        $this->xenforoDb = $xenforoDb;
        $this->bbCodeParser = new BBCodeParser();
    }

    /**
     * Запустить полный импорт с транзакцией
     * @param array $options Опции импорта (importUsers, importCategories, importThreads, importPosts)
     * @return array Статистика импорта
     * @throws Exception
     */
    public function runImport(array $options = []): array
    {
        $defaultOptions = [
            'importUsers' => true,
            'importCategories' => true,
            'importThreads' => true,
            'importPosts' => true,
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            // Начинаем транзакцию
            sql::run("START TRANSACTION");

            // Импорт пользователей
            if ($options['importUsers']) {
                $this->importUsers();
            }

            // Импорт категорий и форумов
            if ($options['importCategories']) {
                $this->importCategories();
            }

            // Импорт тем
            if ($options['importThreads']) {
                $this->importThreads();
            }

            // Импорт постов
            if ($options['importPosts']) {
                $this->importPosts();
            }

            // Если все успешно - коммитим
            sql::run("COMMIT");

            return [
                'success' => true,
                'stats' => $this->importStats,
            ];

        } catch (Exception $e) {
            // В случае ошибки - откатываем все изменения
            sql::run("ROLLBACK");

            throw new Exception("Ошибка импорта: " . $e->getMessage() . "\nИмпорт отменен, все изменения откатаны.");
        }
    }

    /**
     * Импорт пользователей из XenForo
     * @throws Exception
     */
    private function importUsers(): void
    {
        $pdo = $this->xenforoDb->getConnection();
        $userTable = $this->xenforoDb->getTableName('user');
        $authTable = $this->xenforoDb->getTableName('user_authenticate');

        // Получаем пользователей из XenForo
        $stmt = $pdo->query("
            SELECT u.*, ua.data as auth_data
            FROM {$userTable} u
            LEFT JOIN {$authTable} ua ON u.user_id = ua.user_id
            WHERE u.user_state = 'valid'
            ORDER BY u.user_id ASC
        ");

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->importStats['users']['total'] = count($users);

        foreach ($users as $xfUser) {
            try {
                // Проверяем, существует ли пользователь с таким email
                $existingUser = sql::getRow("SELECT id, name FROM users WHERE email = ?", [$xfUser['email']]);

                if ($existingUser) {
                    // Проверяем, начинается ли имя пользователя с паттерна user-*
                    if (preg_match('/^user-(.*)$/i', $existingUser['name'])) {
                        // Подготавливаем данные пароля с префиксом xenforo:
                        $passwordHash = $this->convertXenForoPassword($xfUser['auth_data']);
                        
                        // Обновляем имя пользователя и пароль на данные из XenForo
                        sql::run("UPDATE users SET name = ?, password = ? WHERE id = ?", [
                            $xfUser['username'],
                            $passwordHash,
                            $existingUser['id']
                        ]);
                        
                        $this->importStats['users']['updated']++;
                    } else {
                        $this->importStats['users']['skipped']++;
                    }
                    
                    $this->userMapping[$xfUser['user_id']] = $existingUser['id'];
                    continue;
                }

                // Подготавливаем данные пароля
                $passwordHash = $this->convertXenForoPassword($xfUser['auth_data']);

                // Создаем пользователя
                sql::run("
                    INSERT INTO users (
                        email, 
                        password, 
                        name, 
                        date_create, 
                        access_level,
                        ip
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $xfUser['email'],
                    $passwordHash,
                    $xfUser['username'],
                    date('Y-m-d H:i:s', $xfUser['register_date']),
                    'user',
                    $xfUser['ip'] ?? '0.0.0.0'
                ]);

                $newUserId = sql::lastInsertId();
                $this->userMapping[$xfUser['user_id']] = $newUserId;
                $this->importStats['users']['imported']++;

            } catch (Exception $e) {
                $this->importStats['users']['errors'][] = "User {$xfUser['username']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Конвертировать хэш пароля XenForo в формат для SphereWeb
     * Добавляет префикс xenforo: для последующей проверки при авторизации
     * @param string|null $authData Сериализованные данные аутентификации
     * @return string
     */
    private function convertXenForoPassword(?string $authData): string
    {
        if (empty($authData)) {
            // Генерируем случайный пароль, если данных нет
            return password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        }

        // XenForo хранит данные в сериализованном виде
        $data = @unserialize($authData);

        if ($data === false || !isset($data['hash'])) {
            // Если не удалось десериализовать, генерируем новый пароль
            return password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        }

        // Возвращаем хэш с префиксом xenforo: для идентификации при авторизации
        // Формат: xenforo:{hash}
        return 'xenforo:' . $data['hash'];
    }

    /**
     * Импорт категорий и форумов
     * @throws Exception
     */
    private function importCategories(): void
    {
        $pdo = $this->xenforoDb->getConnection();
        $nodeTable = $this->xenforoDb->getTableName('node');
        $forumTable = $this->xenforoDb->getTableName('forum');

        // Получаем ноды (категории и форумы)
        $stmt = $pdo->query("
            SELECT n.*, f.discussion_count, f.message_count
            FROM {$nodeTable} n
            LEFT JOIN {$forumTable} f ON n.node_id = f.node_id
            WHERE n.node_type_id IN ('Category', 'Forum')
            ORDER BY n.display_order ASC, n.node_id ASC
        ");

        $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->importStats['categories']['total'] = count($nodes);

        foreach ($nodes as $xfNode) {
            try {
                // Определяем parent_id (если есть)
                $parentId = null;
                if ($xfNode['parent_node_id'] && isset($this->categoryMapping[$xfNode['parent_node_id']])) {
                    $parentId = $this->categoryMapping[$xfNode['parent_node_id']];
                }

                // Создаем категорию
                sql::run("
                    INSERT INTO forum_categories (
                        parent_id,
                        name,
                        description,
                        created_at,
                        updated_at,
                        thread_count,
                        post_count,
                        view_count,
                        sort_order,
                        is_close
                    ) VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?)
                ", [
                    $parentId,
                    $xfNode['title'],
                    $xfNode['description'] ?? '',
                    $xfNode['discussion_count'] ?? 0,
                    $xfNode['message_count'] ?? 0,
                    0, // view_count
                    $xfNode['display_order'] ?? 0,
                    0 // is_close
                ]);

                $newCategoryId = sql::lastInsertId();
                $this->categoryMapping[$xfNode['node_id']] = $newCategoryId;
                $this->importStats['categories']['imported']++;

            } catch (Exception $e) {
                $this->importStats['categories']['errors'][] = "Category {$xfNode['title']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Импорт тем
     * @throws Exception
     */
    private function importThreads(): void
    {
        $pdo = $this->xenforoDb->getConnection();
        $threadTable = $this->xenforoDb->getTableName('thread');

        // Получаем темы из XenForo
        $stmt = $pdo->query("
            SELECT *
            FROM {$threadTable}
            WHERE discussion_state = 'visible'
            ORDER BY thread_id ASC
        ");

        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->importStats['threads']['total'] = count($threads);

        foreach ($threads as $xfThread) {
            try {
                // Проверяем, есть ли категория в маппинге
                if (!isset($this->categoryMapping[$xfThread['node_id']])) {
                    $this->importStats['threads']['skipped']++;
                    continue;
                }

                // Проверяем, есть ли пользователь в маппинге
                if (!isset($this->userMapping[$xfThread['user_id']])) {
                    $this->importStats['threads']['skipped']++;
                    continue;
                }

                $categoryId = $this->categoryMapping[$xfThread['node_id']];
                $userId = $this->userMapping[$xfThread['user_id']];

                // Создаем тему
                $createdAt = date('Y-m-d H:i:s', $xfThread['post_date']);
                $updatedAt = date('Y-m-d H:i:s', $xfThread['last_post_date']);
                sql::run("
                    INSERT INTO forum_threads (
                        category_id,
                        user_id,
                        title,
                        created_at,
                        updated_at,
                        views,
                        replies,
                        first_message_id,
                        is_pinned,
                        is_closed,
                        is_approved,
                        approved_by,
                        approved_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL, ?)
                ", [
                    $categoryId,
                    $userId,
                    $xfThread['title'],
                    $createdAt,
                    $updatedAt,
                    $xfThread['view_count'] ?? 0,
                    $xfThread['reply_count'] ?? 0,
                    0, // first_message_id - будет обновлен позже
                    $xfThread['sticky'] ?? 0,
                    $xfThread['discussion_open'] == 0 ? 1 : 0,
                    $createdAt
                ]);

                $newThreadId = sql::lastInsertId();
                $this->threadMapping[$xfThread['thread_id']] = $newThreadId;
                $this->importStats['threads']['imported']++;
                // Обновляем категорию: last_thread_id и updated_at
                try {
                    sql::run("UPDATE forum_categories SET last_thread_id = ?, updated_at = ? WHERE id = ?", [$newThreadId, $createdAt, $categoryId]);
                } catch (Exception $e) {
                    // ignore
                }

            } catch (Exception $e) {
                $this->importStats['threads']['errors'][] = "Thread {$xfThread['title']}: " . $e->getMessage();
            }
        }
    }

    /**
     * Импорт постов
     * @throws Exception
     */
    private function importPosts(): void
    {
        $pdo = $this->xenforoDb->getConnection();
        $postTable = $this->xenforoDb->getTableName('post');

        // Получаем посты из XenForo
        $stmt = $pdo->query("
            SELECT *
            FROM {$postTable}
            WHERE message_state = 'visible'
            ORDER BY post_id ASC
        ");

        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->importStats['posts']['total'] = count($posts);

        foreach ($posts as $xfPost) {
            try {
                // Проверяем, есть ли тема в маппинге
                if (!isset($this->threadMapping[$xfPost['thread_id']])) {
                    $this->importStats['posts']['skipped']++;
                    continue;
                }

                // Проверяем, есть ли пользователь в маппинге
                if (!isset($this->userMapping[$xfPost['user_id']])) {
                    $this->importStats['posts']['skipped']++;
                    continue;
                }

                $threadId = $this->threadMapping[$xfPost['thread_id']];
                $userId = $this->userMapping[$xfPost['user_id']];

                // Конвертируем BB-код в HTML
                $htmlContent = $this->bbCodeParser->parse($xfPost['message']);
                
                // Получаем reply_to_id из парсера (если было цитирование)
                $replyToPostId = $this->bbCodeParser->getReplyToPostId();
                
                // Преобразуем XenForo post_id в наш post_id, если есть
                $replyToId = null;
                if ($replyToPostId !== null && isset($this->postMapping[$replyToPostId])) {
                    $replyToId = $this->postMapping[$replyToPostId];
                }

                // Создаем пост
                sql::run("
                    INSERT INTO forum_posts (
                        thread_id,
                        user_id,
                        content,
                        reply_to_id,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $threadId,
                    $userId,
                    $htmlContent,
                    $replyToId,
                    date('Y-m-d H:i:s', $xfPost['post_date']),
                    date('Y-m-d H:i:s', $xfPost['post_date'])
                ]);

                $newPostId = sql::lastInsertId();
                $this->postMapping[$xfPost['post_id']] = $newPostId;

                // Обновляем first_message_id в теме, если это первый пост
                if ($xfPost['position'] == 0) {
                    sql::run("
                        UPDATE forum_threads 
                        SET first_message_id = ? 
                        WHERE id = ?
                    ", [$newPostId, $threadId]);
                }

                // Обновляем тему: last_post_id, last_reply_user_id, updated_at, replies++
                try {
                    sql::run("UPDATE forum_threads SET last_post_id = ?, last_reply_user_id = ?, updated_at = ?, replies = replies + 1 WHERE id = ?", [$newPostId, $userId, date('Y-m-d H:i:s', $xfPost['post_date']), $threadId]);
                } catch (Exception $e) {
                    // ignore
                }

                // Обновляем категорию: last_post_id, last_reply_user_id, updated_at, post_count++
                try {
                    $cat = sql::getRow("SELECT category_id FROM forum_threads WHERE id = ? LIMIT 1", [$threadId]);
                    if ($cat && isset($cat['category_id'])) {
                        $categoryId = (int)$cat['category_id'];
                        sql::run("UPDATE forum_categories SET last_post_id = ?, last_reply_user_id = ?, updated_at = ?, post_count = post_count + 1 WHERE id = ?", [$newPostId, $userId, date('Y-m-d H:i:s', $xfPost['post_date']), $categoryId]);
                    }
                } catch (Exception $e) {
                    // ignore
                }

                $this->importStats['posts']['imported']++;

            } catch (Exception $e) {
                $this->importStats['posts']['errors'][] = "Post ID {$xfPost['post_id']}: " . $e->getMessage();
            }
        }

        // Получаем статистику загруженных изображений
        $imageStats = $this->bbCodeParser->getImageStats();
        $this->importStats['images']['total'] = $imageStats['total'];
        $this->importStats['images']['downloaded'] = $imageStats['total'];

        // Обновляем счетчики в категориях
        $this->updateCategoryCounters();
    }

    /**
     * Обновить счетчики в категориях
     */
    private function updateCategoryCounters(): void
    {
        try {
            sql::run("
                UPDATE forum_categories c
                SET 
                    thread_count = (SELECT COUNT(*) FROM forum_threads WHERE category_id = c.id),
                    post_count = (SELECT COUNT(*) FROM forum_posts p 
                                  INNER JOIN forum_threads t ON p.thread_id = t.id 
                                  WHERE t.category_id = c.id)
            ");
        } catch (Exception $e) {
            // Логируем ошибку, но не прерываем импорт
            error_log("Error updating category counters: " . $e->getMessage());
        }
    }

    /**
     * Получить статистику импорта
     * @return array
     */
    public function getStats(): array
    {
        return $this->importStats;
    }
}
