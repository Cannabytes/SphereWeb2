<?php

namespace Ofey\Logan22\component\plugins\sphere_forum\struct;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\user\user;

/**
 * Класс для работы с банами пользователей на форуме
 */
class ForumBan implements \JsonSerializable {

    private int $id;
    private int $userId;
    private int $bannedBy;
    private ?string $reason;
    private string $bannedAt;
    private ?string $bannedUntil;
    private bool $isActive;
    private ?string $unbannedAt;
    private ?int $unbannedBy;
    
    // Дополнительные поля для удобства
    private ?string $userName = null;
    private ?string $bannedByName = null;
    private ?string $unbannedByName = null;

    public function __construct(array $data) {
        $this->id = (int)$data['id'];
        $this->userId = (int)$data['user_id'];
        $this->bannedBy = (int)$data['banned_by'];
        $this->reason = $data['reason'] ?? null;
        $this->bannedAt = $data['banned_at'];
        $this->bannedUntil = $data['banned_until'] ?? null;
        $this->isActive = (bool)$data['is_active'];
        $this->unbannedAt = $data['unbanned_at'] ?? null;
        $this->unbannedBy = isset($data['unbanned_by']) ? (int)$data['unbanned_by'] : null;
        
        // Загружаем имена, если они есть в данных
        $this->userName = $data['user_name'] ?? null;
        $this->bannedByName = $data['banned_by_name'] ?? null;
        $this->unbannedByName = $data['unbanned_by_name'] ?? null;
    }

    /**
     * Проверяет наличие таблицы forum_user_bans и создает её при необходимости
     * 
     * @return void
     */
    private static function ensureTableExists(): void {
        static $tableChecked = false;
        
        if ($tableChecked) {
            return; // Таблица уже проверена в этом запросе
        }
        
        try {
            // Проверяем существование таблицы forum_user_bans
            $tableExists = sql::getRow("SHOW TABLES LIKE 'forum_user_bans'");
            
            if (!$tableExists) {
                // Создаем таблицу forum_user_bans
                sql::run("
                    CREATE TABLE IF NOT EXISTS `forum_user_bans` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `user_id` int(11) NOT NULL COMMENT 'ID забаненного пользователя',
                      `banned_by` int(11) NOT NULL COMMENT 'ID модератора/админа который забанил',
                      `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Причина бана',
                      `banned_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Дата и время бана',
                      `banned_until` timestamp NULL DEFAULT NULL COMMENT 'До какого времени бан (NULL = перманентный)',
                      `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Активен ли бан (0 = снят досрочно)',
                      `unbanned_at` timestamp NULL DEFAULT NULL COMMENT 'Когда был снят бан',
                      `unbanned_by` int(11) NULL DEFAULT NULL COMMENT 'Кто снял бан',
                      PRIMARY KEY (`id`) USING BTREE,
                      INDEX `user_id_idx`(`user_id` ASC) USING BTREE,
                      INDEX `banned_by_idx`(`banned_by` ASC) USING BTREE,
                      INDEX `is_active_idx`(`is_active` ASC) USING BTREE,
                      INDEX `banned_until_idx`(`banned_until` ASC) USING BTREE
                    ) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic
                ");
            }
            
            $tableChecked = true;
        } catch (\Exception $e) {
            // Игнорируем ошибки при проверке/создании таблицы
            // Если таблица не существует, методы вернут null или пустой массив
        }
    }

    /**
     * Проверяет, забанен ли пользователь в данный момент
     * 
     * @param int $userId ID пользователя
     * @return array|null Информация о бане или null если пользователь не забанен
     */
    public static function isUserBanned(int $userId): ?array {
        self::ensureTableExists();
        
        try {
            $ban = sql::getRow(
            "SELECT b.*, 
                    u.name as user_name,
                    moderator.name as banned_by_name
             FROM forum_user_bans b
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN users moderator ON b.banned_by = moderator.id
             WHERE b.user_id = ? 
             AND b.is_active = 1 
             AND (b.banned_until IS NULL OR b.banned_until > NOW())
             ORDER BY b.banned_at DESC
             LIMIT 1",
                [$userId]
            );

            return $ban ?: null;
        } catch (\Exception $e) {
            // Если таблица не существует или произошла ошибка, возвращаем null
            return null;
        }
    }

    /**
     * Создает новый бан для пользователя
     * 
     * @param int $userId ID пользователя для бана
     * @param int $bannedBy ID модератора/админа
     * @param string|null $reason Причина бана
     * @param string|null $bannedUntil До какого времени бан (формат: Y-m-d H:i:s)
     * @return int ID созданного бана
     */
    public static function createBan(int $userId, int $bannedBy, ?string $reason = null, ?string $bannedUntil = null): int {
        self::ensureTableExists();
        
        try {
            // Деактивируем все предыдущие активные баны
            sql::run(
                "UPDATE forum_user_bans SET is_active = 0 WHERE user_id = ? AND is_active = 1",
                [$userId]
            );

            // Создаем новый бан
            sql::run(
                "INSERT INTO forum_user_bans (user_id, banned_by, reason, banned_until, is_active) 
                 VALUES (?, ?, ?, ?, 1)",
                [$userId, $bannedBy, $reason, $bannedUntil]
            );

            return sql::lastInsertId();
        } catch (\Exception $e) {
            throw new \Exception("Не удалось создать бан: " . $e->getMessage());
        }
    }

    /**
     * Снимает бан с пользователя
     * 
     * @param int $banId ID бана
     * @param int $unbannedBy ID модератора/админа который снял бан
     * @return bool
     */
    public static function removeBan(int $banId, int $unbannedBy): bool {
        self::ensureTableExists();
        
        try {
            $result = sql::run(
            "UPDATE forum_user_bans 
             SET is_active = 0, unbanned_at = NOW(), unbanned_by = ? 
             WHERE id = ?",
                [$unbannedBy, $banId]
            );

            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Снимает все активные баны с пользователя
     * 
     * @param int $userId ID пользователя
     * @param int $unbannedBy ID модератора/админа который снял бан
     * @return bool
     */
    public static function removeUserBans(int $userId, int $unbannedBy): bool {
        self::ensureTableExists();
        
        try {
            $result = sql::run(
            "UPDATE forum_user_bans 
             SET is_active = 0, unbanned_at = NOW(), unbanned_by = ? 
             WHERE user_id = ? AND is_active = 1",
                [$unbannedBy, $userId]
            );

            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получает список всех банов
     * 
     * @param bool $onlyActive Только активные баны
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array
     */
    public static function getAllBans(bool $onlyActive = false, int $limit = 50, int $offset = 0): array {
        self::ensureTableExists();
        
        try {
            $sql = "SELECT b.*, 
                       u.name as user_name,
                       u.avatar as user_avatar,
                       moderator.name as banned_by_name,
                       unbanner.name as unbanned_by_name
                FROM forum_user_bans b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN users moderator ON b.banned_by = moderator.id
                LEFT JOIN users unbanner ON b.unbanned_by = unbanner.id";
            
            if ($onlyActive) {
                $sql .= " WHERE b.is_active = 1 AND (b.banned_until IS NULL OR b.banned_until > NOW())";
            }
            
            $sql .= " ORDER BY b.banned_at DESC LIMIT ? OFFSET ?";
            
            $bans = sql::getRows($sql, [$limit, $offset]);
            
            return array_map(fn($ban) => new self($ban), $bans);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получает историю банов пользователя
     * 
     * @param int $userId ID пользователя
     * @return array
     */
    public static function getUserBanHistory(int $userId): array {
        self::ensureTableExists();
        
        try {
            $bans = sql::getRows(
            "SELECT b.*, 
                    moderator.name as banned_by_name,
                    unbanner.name as unbanned_by_name
             FROM forum_user_bans b
             LEFT JOIN users moderator ON b.banned_by = moderator.id
             LEFT JOIN users unbanner ON b.unbanned_by = unbanner.id
             WHERE b.user_id = ?
             ORDER BY b.banned_at DESC",
                [$userId]
            );

            return array_map(fn($ban) => new self($ban), $bans);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получает бан по ID
     * 
     * @param int $banId ID бана
     * @return ForumBan|null
     */
    public static function getBanById(int $banId): ?ForumBan {
        self::ensureTableExists();
        
        try {
            $ban = sql::getRow(
            "SELECT b.*, 
                    u.name as user_name,
                    moderator.name as banned_by_name,
                    unbanner.name as unbanned_by_name
             FROM forum_user_bans b
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN users moderator ON b.banned_by = moderator.id
             LEFT JOIN users unbanner ON b.unbanned_by = unbanner.id
             WHERE b.id = ?",
                [$banId]
            );

            return $ban ? new self($ban) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Обновляет информацию о бане
     * 
     * @param int $banId ID бана
     * @param string|null $reason Новая причина
     * @param string|null $bannedUntil Новая дата окончания
     * @return bool
     */
    public static function updateBan(int $banId, ?string $reason = null, ?string $bannedUntil = null): bool {
        self::ensureTableExists();
        
        try {
            $result = sql::run(
            "UPDATE forum_user_bans 
             SET reason = ?, banned_until = ? 
             WHERE id = ?",
                [$reason, $bannedUntil, $banId]
            );

            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Автоматически снимает истекшие баны
     * Вызывается периодически (можно через крон)
     * 
     * @return int Количество снятых банов
     */
    public static function expireBans(): int {
        self::ensureTableExists();
        
        try {
            $result = sql::run(
            "UPDATE forum_user_bans 
             SET is_active = 0 
             WHERE is_active = 1 
             AND banned_until IS NOT NULL 
             AND banned_until <= NOW()"
            );

            return $result->rowCount();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Проверяет, истек ли бан
     * 
     * @return bool
     */
    public function isExpired(): bool {
        if (!$this->isActive) {
            return true;
        }
        
        if ($this->bannedUntil === null) {
            return false; // Перманентный бан
        }
        
        return strtotime($this->bannedUntil) <= time();
    }

    /**
     * Проверяет, является ли бан перманентным
     * 
     * @return bool
     */
    public function isPermanent(): bool {
        return $this->bannedUntil === null;
    }

    // Геттеры
    public function getId(): int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getBannedBy(): int {
        return $this->bannedBy;
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function getBannedAt(): string {
        return $this->bannedAt;
    }

    public function getBannedUntil(): ?string {
        return $this->bannedUntil;
    }

    public function isActive(): bool {
        return $this->isActive;
    }

    public function getUnbannedAt(): ?string {
        return $this->unbannedAt;
    }

    public function getUnbannedBy(): ?int {
        return $this->unbannedBy;
    }

    public function getUserName(): ?string {
        return $this->userName;
    }

    public function getBannedByName(): ?string {
        return $this->bannedByName;
    }

    public function getUnbannedByName(): ?string {
        return $this->unbannedByName;
    }

    /**
     * Сериализация в JSON
     * 
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'bannedBy' => $this->bannedBy,
            'bannedByName' => $this->bannedByName,
            'reason' => $this->reason,
            'bannedAt' => $this->bannedAt,
            'bannedUntil' => $this->bannedUntil,
            'isActive' => $this->isActive,
            'isPermanent' => $this->isPermanent(),
            'isExpired' => $this->isExpired(),
            'unbannedAt' => $this->unbannedAt,
            'unbannedBy' => $this->unbannedBy,
            'unbannedByName' => $this->unbannedByName,
        ];
    }
}

