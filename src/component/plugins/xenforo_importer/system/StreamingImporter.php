<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

use Exception;
use Ofey\Logan22\model\db\sql;
use PDO;

/**
 * Streaming импорт с real-time прогрессом
 * Использует Server-Sent Events для передачи прогресса
 */
class StreamingImporter
{
    private XenForoConnection $xenforoDb;
    private $bbCodeParser; // BBCodeParser или OptimizedBBCodeParser
    private int $chunkSize = 50; // Импортировать по 50 записей за раз (увеличено)
    private bool $fastMode = true; // Быстрый режим БЕЗ загрузки изображений
    
    private array $userMapping = [];
    private array $categoryMapping = [];
    private array $threadMapping = [];
    private array $postMapping = [];

    public function __construct(XenForoConnection $xenforoDb, bool $fastMode = true)
    {
        $this->xenforoDb = $xenforoDb;
        $this->fastMode = $fastMode;
        
        // Используем оптимизированный парсер в быстром режиме
        if ($fastMode) {
            $this->bbCodeParser = new OptimizedBBCodeParser(true); // skipImageDownload = true
        } else {
            $this->bbCodeParser = new BBCodeParser();
        }
        
        // Увеличиваем лимиты для длительной операции
        set_time_limit(0);
        ignore_user_abort(true);
        
        // Настройки для streaming
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // Отключаем буферизацию nginx
        
        // Отключаем буферизацию PHP
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_implicit_flush(true);
    }

    /**
     * Отправить событие клиенту
     */
    private function sendEvent(string $type, array $data): void
    {
        echo "event: {$type}\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }

    /**
     * Запустить импорт с потоковой передачей прогресса
     */
    public function runStreamingImport(array $options): void
    {
        try {
            $pdo = $this->xenforoDb->getConnection();
            
            // Отправляем событие начала
            $this->sendEvent('start', ['message' => 'Импорт начат...']);
            
            // Импорт пользователей
            if ($options['importUsers'] ?? true) {
                $this->importUsersStreaming($pdo);
            }
            
            // Импорт категорий
            if ($options['importCategories'] ?? true) {
                $this->importCategoriesStreaming($pdo);
            }
            
            // Импорт тем
            if ($options['importThreads'] ?? true) {
                $this->importThreadsStreaming($pdo);
            }
            
            // Импорт постов
            if ($options['importPosts'] ?? true) {
                $this->importPostsStreaming($pdo);
            }
            
            // Завершение
            $this->sendEvent('complete', [
                'message' => 'Импорт успешно завершен!',
                'userMapping' => count($this->userMapping),
                'categoryMapping' => count($this->categoryMapping),
                'threadMapping' => count($this->threadMapping),
                'postMapping' => count($this->postMapping),
            ]);
            
        } catch (Exception $e) {
            $this->sendEvent('error', [
                'message' => 'Ошибка импорта: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Импорт пользователей с прогрессом
     */
    private function importUsersStreaming(PDO $pdo): void
    {
        $userTable = $this->xenforoDb->getTableName('user');
        $authTable = $this->xenforoDb->getTableName('user_authenticate');
        
        // Получаем общее количество
        $totalStmt = $pdo->query("SELECT COUNT(*) as count FROM {$userTable} WHERE user_state = 'valid'");
        $total = $totalStmt->fetch()['count'];
        
        if ($total == 0) {
            $this->sendEvent('progress', [
                'stage' => 'users',
                'current' => 0,
                'total' => 0,
                'percent' => 100,
                'message' => 'Пользователи не найдены'
            ]);
            return;
        }
        
        $offset = 0;
        $imported = 0;
        $skipped = 0;
        $updated = 0;
        
        while ($offset < $total) {
            // Получаем порцию пользователей
            $stmt = $pdo->query("
                SELECT u.*, ua.data as auth_data
                FROM {$userTable} u
                LEFT JOIN {$authTable} ua ON u.user_id = ua.user_id
                WHERE u.user_state = 'valid'
                ORDER BY u.user_id ASC
                LIMIT {$this->chunkSize} OFFSET {$offset}
            ");
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $xfUser) {
                try {
                    $existingUser = sql::getRow("SELECT id, name FROM users WHERE email = ?", [$xfUser['email']]);
                    
                    if ($existingUser) {
                        if (preg_match('/^user-(.*)$/i', $existingUser['name'])) {
                            $passwordHash = $this->convertXenForoPassword($xfUser['auth_data']);
                            sql::run("UPDATE users SET name = ?, password = ? WHERE id = ?", [
                                $xfUser['username'],
                                $passwordHash,
                                $existingUser['id']
                            ]);
                            $updated++;
                        } else {
                            $skipped++;
                        }
                        $this->userMapping[$xfUser['user_id']] = $existingUser['id'];
                    } else {
                        $passwordHash = $this->convertXenForoPassword($xfUser['auth_data']);
                        sql::run("
                            INSERT INTO users (email, password, name, date_create, date_active)
                            VALUES (?, ?, ?, ?, ?)
                        ", [
                            $xfUser['email'],
                            $passwordHash,
                            $xfUser['username'],
                            $xfUser['register_date'],
                            $xfUser['last_activity']
                        ]);
                        
                        $newUserId = sql::lastInsertId();
                        $this->userMapping[$xfUser['user_id']] = $newUserId;
                        $imported++;
                    }
                } catch (Exception $e) {
                    $skipped++;
                }
            }
            
            $offset += $this->chunkSize;
            $current = min($offset, $total);
            $percent = round(($current / $total) * 100);
            
            // Отправляем прогресс
            $this->sendEvent('progress', [
                'stage' => 'users',
                'current' => $current,
                'total' => $total,
                'percent' => $percent,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'message' => "Импортировано пользователей: {$current} / {$total} ({$percent}%)"
            ]);
            
            // Небольшая задержка для разгрузки сервера
            usleep(50000); // 50ms
        }
    }

    /**
     * Импорт категорий с прогрессом
     */
    private function importCategoriesStreaming(PDO $pdo): void
    {
        $nodeTable = $this->xenforoDb->getTableName('node');
        $forumTable = $this->xenforoDb->getTableName('forum');
        
        // Получаем ВСЕ категории/форумы сразу для правильной обработки иерархии
        $stmt = $pdo->query("
            SELECT n.*, f.discussion_count, f.message_count
            FROM {$nodeTable} n
            LEFT JOIN {$forumTable} f ON n.node_id = f.node_id
            WHERE n.node_type_id IN ('Category', 'Forum')
            ORDER BY n.depth ASC, n.display_order ASC, n.node_id ASC
        ");
        
        $allNodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($allNodes);
        
        if ($total == 0) {
            $this->sendEvent('progress', [
                'stage' => 'categories',
                'current' => 0,
                'total' => 0,
                'percent' => 100,
                'message' => 'Категории не найдены'
            ]);
            return;
        }
        
        $imported = 0;
        $current = 0;
        
        // Импортируем по уровням глубины (depth) для правильной иерархии
        foreach ($allNodes as $xfNode) {
            try {
                // Определяем parent_id (если есть)
                $parentId = null;
                if ($xfNode['parent_node_id'] && isset($this->categoryMapping[$xfNode['parent_node_id']])) {
                    $parentId = $this->categoryMapping[$xfNode['parent_node_id']];
                }
                
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
                $imported++;
            } catch (Exception $e) {
                // Пропускаем дубликаты
            }
            
            $current++;
            
            // Отправляем прогресс каждые 10 категорий или в конце
            if ($current % 10 === 0 || $current === $total) {
                $percent = round(($current / $total) * 100);
                
                $this->sendEvent('progress', [
                    'stage' => 'categories',
                    'current' => $current,
                    'total' => $total,
                    'percent' => $percent,
                    'imported' => $imported,
                    'message' => "Импортировано категорий: {$current} / {$total} ({$percent}%)"
                ]);
                
                usleep(50000); // 50ms задержка для стабильности
            }
        }
    }

    /**
     * Импорт тем с прогрессом
     */
    private function importThreadsStreaming(PDO $pdo): void
    {
        $threadTable = $this->xenforoDb->getTableName('thread');
        
        $totalStmt = $pdo->query("SELECT COUNT(*) as count FROM {$threadTable} WHERE discussion_state = 'visible'");
        $total = $totalStmt->fetch()['count'];
        
        if ($total == 0) {
            $this->sendEvent('progress', [
                'stage' => 'threads',
                'current' => 0,
                'total' => 0,
                'percent' => 100,
                'message' => 'Темы не найдены'
            ]);
            return;
        }
        
        $offset = 0;
        $imported = 0;
        $skipped = 0;
        
        while ($offset < $total) {
            $stmt = $pdo->query("
                SELECT * FROM {$threadTable}
                WHERE discussion_state = 'visible'
                ORDER BY thread_id ASC
                LIMIT {$this->chunkSize} OFFSET {$offset}
            ");
            
            $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($threads as $xfThread) {
                try {
                    if (!isset($this->categoryMapping[$xfThread['node_id']]) || !isset($this->userMapping[$xfThread['user_id']])) {
                        $skipped++;
                        continue;
                    }
                    
                    $categoryId = $this->categoryMapping[$xfThread['node_id']];
                    $userId = $this->userMapping[$xfThread['user_id']];
                    
                    $createdAt = date('Y-m-d H:i:s', $xfThread['post_date']);
                    $updatedAt = date('Y-m-d H:i:s', $xfThread['last_post_date']);
                    sql::run("
                        INSERT INTO forum_threads (category_id, user_id, title, created_at, updated_at, is_approved, approved_by, approved_at)
                        VALUES (?, ?, ?, ?, ?, 1, NULL, ?)
                    ", [
                        $categoryId,
                        $userId,
                        $xfThread['title'],
                        $createdAt,
                        $updatedAt,
                        $createdAt
                    ]);
                    
                    $newThreadId = sql::lastInsertId();
                    $this->threadMapping[$xfThread['thread_id']] = $newThreadId;
                    $imported++;
                    // Обновляем категорию: last_thread_id и updated_at
                    try {
                        sql::run("UPDATE forum_categories SET last_thread_id = ?, updated_at = ? WHERE id = ?", [$newThreadId, $updatedAt, $categoryId]);
                    } catch (Exception $e) {
                        // ignore
                    }
                } catch (Exception $e) {
                    $skipped++;
                }
            }
            
            $offset += $this->chunkSize;
            $current = min($offset, $total);
            $percent = round(($current / $total) * 100);
            
            $this->sendEvent('progress', [
                'stage' => 'threads',
                'current' => $current,
                'total' => $total,
                'percent' => $percent,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => "Импортировано тем: {$current} / {$total} ({$percent}%)"
            ]);
            
            usleep(50000);
        }
    }

    /**
     * Импорт постов с прогрессом
     */
    private function importPostsStreaming(PDO $pdo): void
    {
        $postTable = $this->xenforoDb->getTableName('post');
        
        $totalStmt = $pdo->query("SELECT COUNT(*) as count FROM {$postTable} WHERE message_state = 'visible'");
        $total = $totalStmt->fetch()['count'];
        
        if ($total == 0) {
            $this->sendEvent('progress', [
                'stage' => 'posts',
                'current' => 0,
                'total' => 0,
                'percent' => 100,
                'message' => 'Посты не найдены'
            ]);
            return;
        }
        
        $offset = 0;
        $imported = 0;
        $skipped = 0;
        
        while ($offset < $total) {
            $stmt = $pdo->query("
                SELECT * FROM {$postTable}
                WHERE message_state = 'visible'
                ORDER BY post_id ASC
                LIMIT {$this->chunkSize} OFFSET {$offset}
            ");
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as $xfPost) {
                try {
                    if (!isset($this->threadMapping[$xfPost['thread_id']]) || !isset($this->userMapping[$xfPost['user_id']])) {
                        $skipped++;
                        continue;
                    }
                    
                    $threadId = $this->threadMapping[$xfPost['thread_id']];
                    $userId = $this->userMapping[$xfPost['user_id']];
                    
                    $htmlContent = $this->bbCodeParser->parse($xfPost['message']);
                    $replyToPostId = $this->bbCodeParser->getReplyToPostId();
                    
                    $replyToId = null;
                    if ($replyToPostId !== null && isset($this->postMapping[$replyToPostId])) {
                        $replyToId = $this->postMapping[$replyToPostId];
                    }
                    
                    sql::run("
                        INSERT INTO forum_posts (thread_id, user_id, content, reply_to_id, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?)
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
                    $imported++;
                    // Обновляем данные темы: last_post_id, last_reply_user_id, updated_at, replies++
                    try {
                        sql::run("UPDATE forum_threads SET last_post_id = ?, last_reply_user_id = ?, updated_at = ?, replies = replies + 1 WHERE id = ?", [$newPostId, $userId, date('Y-m-d H:i:s', $xfPost['post_date']), $threadId]);
                    } catch (Exception $e) {
                        // ignore
                    }

                    // Обновляем категорию: last_post_id, last_reply_user_id, updated_at, post_count++
                    try {
                        // Получаем category_id для этой темы
                        $cat = sql::getRow("SELECT category_id FROM forum_threads WHERE id = ? LIMIT 1", [$threadId]);
                        if ($cat && isset($cat['category_id'])) {
                            $categoryId = (int)$cat['category_id'];
                            sql::run("UPDATE forum_categories SET last_post_id = ?, last_reply_user_id = ?, updated_at = ?, post_count = post_count + 1 WHERE id = ?", [$newPostId, $userId, date('Y-m-d H:i:s', $xfPost['post_date']), $categoryId]);
                        }
                    } catch (Exception $e) {
                        // ignore
                    }
                } catch (Exception $e) {
                    $skipped++;
                }
            }
            
            $offset += $this->chunkSize;
            $current = min($offset, $total);
            $percent = round(($current / $total) * 100);
            
            $this->sendEvent('progress', [
                'stage' => 'posts',
                'current' => $current,
                'total' => $total,
                'percent' => $percent,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => "Импортировано постов: {$current} / {$total} ({$percent}%)"
            ]);
            
            usleep(50000);
        }
    }

    /**
     * Конвертировать пароль XenForo
     */
    private function convertXenForoPassword(?string $authData): string
    {
        if (empty($authData)) {
            return 'xenforo:' . bin2hex(random_bytes(32));
        }
        
        $data = @unserialize($authData);
        if ($data === false || !isset($data['hash'])) {
            return 'xenforo:' . bin2hex(random_bytes(32));
        }
        
        return 'xenforo:' . $data['hash'];
    }
}
