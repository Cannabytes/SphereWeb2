<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\component\request\XssSecurity;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

class ForumClan {
    private int $id = 0;
    private ?int $owner_id;
    private ?string $name;
    private ?string $desc;
    private ?string $desc_full;
    private ?string $logo;
    private ?string $background_logo;
    private ?string $text_color;
    private ?int $acceptance;
    private ?string $clanNameGame;
    private bool $verification;
    public function __construct($row) {
        $this->id = $row["id"];
        $this->owner_id = $row['owner_id'];
        $this->name = $row['name'];
        $this->desc = $row['desc'];
        $this->desc_full = $row['desc_full'];
        $this->logo = $row['logo'];
        $this->background_logo = $row['background_logo'];
        $this->text_color = $row['text_color'];
        $this->acceptance = $row['acceptance'];
        $this->clanNameGame = $row['clan_name_game'];
        $this->verification = $row['verification'];
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getOwnerId(): ?int {
        return $this->owner_id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getDesc(): ?string {
        return $this->desc;
    }

    public function getLogo(): ?string {
        return $this->logo;
    }

    public function getBackgroundLogo(): ?string {
        return $this->background_logo;
    }

    public function getTextColor(): ?string {
        return $this->text_color;
    }

    public function getAcceptance(): ?int {
        return $this->acceptance;
    }

    // Setters
    public function setOwnerId(?int $owner_id): void {
        $this->owner_id = $owner_id;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function setDesc(?string $desc): void {
        $this->desc = $desc;
    }

    public function setLogo(?string $logo): void {
        $this->logo = $logo;
    }

    public function setBackgroundLogo(?string $background_logo): void {
        $this->background_logo = $background_logo;
    }

    public function setTextColor(?string $text_color): void {
        $this->text_color = $text_color;
    }

    public function setAcceptance(?int $acceptance): void {
        $this->acceptance = $acceptance;
    }

    public function getClanNameGame(): ?string
    {
        return $this->clanNameGame;
    }

    public function setClanNameGame(?string $clanNameGame): void
    {
        $this->clanNameGame = $clanNameGame;
    }

    public function isVerification(): bool
    {
        return $this->verification;
    }

    public function setVerification(bool $verification): void
    {
        $this->verification = $verification;
    }

    public function getMembersCount(): int {
        return (int)sql::getValue(
            "SELECT COUNT(*) FROM forum_clan_members WHERE clan_id = ?",
            [$this->getId()]
        );
    }

    public function getMembers()
    {
        return sql::getRows(
            "SELECT m.*, u.name, u.avatar 
         FROM forum_clan_members m 
         JOIN users u ON m.user_id = u.id 
         WHERE m.clan_id = ?
         ORDER BY m.role DESC, m.join_date ASC",
            [$this->getId()]
        );
    }


    public function getPendingRequestsCount(): int {
        return (int)sql::getValue(
            "SELECT COUNT(*) FROM forum_clan_requests 
         WHERE clan_id = ? AND status = 'pending'",
            [$this->getId()]
        );
    }

    public function getPendingRequests()
    {
        if (!$this->getOwnerId() == user::self()->getId()) {
            return [];
        }

        return sql::getRows(
            "SELECT r.*, u.name, u.avatar 
         FROM forum_clan_requests r 
         JOIN users u ON r.user_id = u.id 
         WHERE r.clan_id = ? AND r.status = 'pending'
         ORDER BY r.request_date DESC",
            [$this->getId()]
        );
    }

    public function getMessages($lastMessageId = 0): array {
        $messages = sql::getRows(
            "SELECT c.*, u.name, u.avatar,
         DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at
         FROM forum_clan_chat c
         JOIN users u ON c.user_id = u.id
         WHERE c.clan_id = ? AND c.id > ?
         ORDER BY c.id DESC
         LIMIT 25",
            [$this->getId(), $lastMessageId]
        );
        if ($messages !== null && $messages !== false && is_array($messages) && !empty($messages)) {
            foreach ($messages as &$msg) {
                $avatar = $msg['avatar'] ?? '';
                $path = parse_url($avatar, PHP_URL_PATH) ?: $avatar;
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $msg['isVideoAvatar'] = ($ext === 'webm');
            }
            unset($msg);
            return array_reverse($messages);
        }
        return [];
    }

    public function sendMessage($userId, $message): array {
        // XSS защита: очищаем сообщение от потенциально опасного содержимого
        $message = XssSecurity::clean($message);
        
        // Валидация сообщения
        $message = trim($message);
        if (empty($message)) {
            throw new \Exception('Сообщение не может быть пустым');
        }

        if (mb_strlen($message) > 1000) {
            throw new \Exception('Сообщение слишком длинное');
        }

        // Проверка на спам
        $lastMessage = sql::getRow(
            "SELECT created_at FROM forum_clan_chat 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT 1",
            [$userId]
        );

        if ($lastMessage && (time() - strtotime($lastMessage['created_at'])) < 1) {
            throw new \Exception('Слишком частая отправка сообщений');
        }

        // Сохранение сообщения
        sql::run(
            "INSERT INTO forum_clan_chat (clan_id, user_id, message) 
         VALUES (?, ?, ?)",
            [$this->getId(), $userId, $message]
        );

        $messageId = sql::lastInsertId();

        // Возвращаем данные нового сообщения
        return sql::getRow(
            "SELECT c.*, u.name, u.avatar,
            DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at
         FROM forum_clan_chat c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ?",
            [$messageId]
        );
    }



    private function checkPostRateLimit(int $userId): bool {
        try {
            // Проверяем количество сообщений за последний час
            $hourlyPosts = sql::getValue(
                "SELECT COUNT(*) 
             FROM forum_clan_posts 
             WHERE user_id = ? 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND is_deleted = 0",
                [$userId]
            );

            if ($hourlyPosts >= 30) {
                throw new \Exception('Превышен лимит сообщений (30 сообщений в час). Попробуйте позже.');
            }

            return true;
        } catch (\Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            throw $e;
        }
    }

    private function checkChatRateLimit(int $userId): bool {
        try {
            // Проверяем количество сообщений в чате за последний час
            $hourlyChatMessages = sql::getValue(
                "SELECT COUNT(*) 
             FROM forum_clan_chat 
             WHERE user_id = ? 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                [$userId]
            );

            if ($hourlyChatMessages >= 300) {
                throw new \Exception('Превышен лимит сообщений в чате (300 сообщений в час). Попробуйте позже.');
            }

            return true;
        } catch (\Exception $e) {
            error_log("Chat rate limit check error: " . $e->getMessage());
            throw $e;
        }
    }

    public function createPost($userId, $message, array $images = []): array {
        $this->checkPostRateLimit($userId);

        // XSS защита: очищаем сообщение от потенциально опасного содержимого
        $message = XssSecurity::clean($message);
        
        $message = trim($message);
        if (empty($message) && empty($images)) {
            throw new \Exception('Сообщение не может быть пустым');
        }

        if (mb_strlen($message) > 2000) {
            throw new \Exception('Сообщение слишком длинное');
        }

        $postId = 0;

        try {
            sql::transaction(function() use ($userId, $message, $images, &$postId) {
                // Создаем пост
                sql::run(
                    "INSERT INTO forum_clan_posts (clan_id, user_id, message) 
                 VALUES (?, ?, ?)",
                    [$this->getId(), $userId, $message]
                );

                $postId = sql::lastInsertId();

                // Сохраняем изображения
                if (!empty($images)) {
                    foreach ($images as $index => $image) {
                        sql::run(
                            "INSERT INTO forum_clan_post_images (post_id, image, `order`) 
                         VALUES (?, ?, ?)",
                            [$postId, $image, $index]
                        );
                    }
                }
            });

            // Получаем данные созданного поста
            $post = sql::getRow(
                "SELECT p.*, u.name, u.avatar,
                    GROUP_CONCAT(pi.image) as images
             FROM forum_clan_posts p 
             JOIN users u ON p.user_id = u.id 
             LEFT JOIN forum_clan_post_images pi ON pi.post_id = p.id
             WHERE p.id = ?
             GROUP BY p.id",
                [$postId]
            );

            if (!$post) {
                throw new \Exception('Ошибка при создании поста');
            }

            return $post;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при создании поста: ' . $e->getMessage());
        }
    }

    public function updatePost($postId, $message): bool {
        $post = $this->getPost($postId);

        if (!$post) {
            throw new \Exception('Сообщение не найдено');
        }

        if ($post['user_id'] != user::self()->getId() && $this->getOwnerId() != user::self()->getId()) {
            throw new \Exception('Нет прав на редактирование');
        }

        // XSS защита: очищаем сообщение от потенциально опасного содержимого
        $message = XssSecurity::clean($message);

        // Выполняем запрос и проверяем результат
        $stmt = sql::run(
            "UPDATE forum_clan_posts 
         SET message = ?, updated_at = CURRENT_TIMESTAMP 
         WHERE id = ? AND clan_id = ?",
            [$message, $postId, $this->getId()]
        );

        // Возвращаем true, если обновление прошло успешно
        return $stmt && $stmt->rowCount() > 0;
    }

    public function deletePost($postId): bool {
        $post = $this->getPost($postId);

        if (!$post) {
            throw new \Exception('Сообщение не найдено');
        }

        if ($post['user_id'] != user::self()->getId() && $this->getOwnerId() != user::self()->getId()) {
            throw new \Exception('Нет прав на удаление');
        }

        $stmt = sql::run(
            "UPDATE forum_clan_posts 
         SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP 
         WHERE id = ? AND clan_id = ?",
            [$postId, $this->getId()]
        );

        return $stmt && $stmt->rowCount() > 0;
    }

    private function getPost($postId): ?array {
        return sql::getRow(
            "SELECT p.*, u.name, u.avatar 
         FROM forum_clan_posts p 
         JOIN users u ON p.user_id = u.id 
         WHERE p.id = ? AND p.clan_id = ? AND p.is_deleted = 0",
            [$postId, $this->getId()]
        );
    }

    public function getClanPosts(): array {
        $posts = sql::getRows(
            "SELECT p.*, u.name, u.avatar,
        CASE 
            WHEN COUNT(pi.id) > 0 THEN 
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'image', pi.image,
                        'order', pi.order
                    )
                )
            ELSE NULL 
        END as post_images
     FROM forum_clan_posts p 
     JOIN users u ON p.user_id = u.id 
     LEFT JOIN forum_clan_post_images pi ON pi.post_id = p.id
     WHERE p.clan_id = ? AND p.is_deleted = 0 
     GROUP BY p.id
     ORDER BY p.created_at DESC",
            [$this->getId()]
        );

        if($posts){
            foreach ($posts as &$post) {
                if($post['post_images']){
                    $post['post_images'] = json_decode("[" . $post['post_images'] . "]", true);
                }
            }
        }

        return $posts;
    }

    public function getDescFull(): ?string {
        $content = $this->desc_full ?? $this->desc;
        if ($content) {
            $replacements = [
                '%username%' => user::self()->getName(),
                '%clan%' => $this->getName(),
                '%leader%' => user::getUserId($this->getOwnerId())->getName()
            ];
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }
        return null;
    }

// Добавьте сеттер
    public function setDescFull(?string $desc_full): void {
        $this->desc_full = $desc_full;
    }

// Метод для обновления описания
    public function updateDescription(string $description): bool {
        try {
            if ($this->getOwnerId() !== user::self()->getId()) {
                throw new \Exception('Нет прав на редактирование описания');
            }

            // XSS защита: очищаем описание от потенциально опасного содержимого
            $description = XssSecurity::clean($description);

            $stmt = sql::run(
                "UPDATE forum_clans SET desc_full = ? WHERE id = ? AND owner_id = ?",
                [$description, $this->getId(), user::self()->getId()]
            );

            if($stmt->rowCount()==0){
                return true;
            }
            if ($stmt->rowCount() == 1) {
                $this->setDescFull($description);
                return true;
            }

            throw new \Exception('Не удалось обновить описание клана');
        } catch (\Exception $e) {
            error_log("Clan description update error in model: " . $e->getMessage());
            throw $e;
        }
    }

    public function canModerate(int $userId): bool {
        return $this->getOwnerId() === $userId || user::self()->isAdmin();
    }

    public function deletePostAsAdmin($postId): bool {
        if (!user::self()->isAdmin()) {
            throw new \Exception('Недостаточно прав');
        }

        $stmt = sql::run(
            "UPDATE forum_clan_posts 
         SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP 
         WHERE id = ? AND clan_id = ?",
            [$postId, $this->getId()]
        );

        return $stmt && $stmt->rowCount() > 0;
    }

    public function getUserPendingRequest(int $userId) {
        return sql::getRow(
            "SELECT * FROM forum_clan_requests 
             WHERE clan_id = ? AND user_id = ? AND status = 'pending'
             LIMIT 1",
            [$this->getId(), $userId]
        );
    }

    public function hasPendingRequest(int $userId): bool {
        return (bool)sql::getValue(
            "SELECT 1 FROM forum_clan_requests 
             WHERE clan_id = ? AND user_id = ? AND status = 'pending'
             LIMIT 1",
            [$this->getId(), $userId]
        );
    }

}