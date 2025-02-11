<?php

namespace Ofey\Logan22\component\plugins\sphere_forum;

use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\plugin\plugin;
use Ofey\Logan22\template\tpl;
use ReflectionClass;

class sphere_forum
{
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
        tpl::addVar([
            'setting' => plugin::getSetting($this->getNameClass()),
            'pluginName' => $this->getNameClass(),
        ]);
    }

    public function index(): void
    {
        validation::user_protection("admin");
        $this->checkDataBase();
        tpl::displayPlugin("sphere_forum/tpl/admin/index.html");
    }

    //Проверка существования нужных таблиц
    private function checkDataBase(): void
    {
        $tables = [
            'forum_attachments' => "
CREATE TABLE `forum_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `post_id`(`post_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_categories' => "
CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NULL DEFAULT NULL,
  `name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'dark',
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `last_reply_user_id` int(11) NULL DEFAULT NULL COMMENT 'ID последнего ответившего в категории',
  `last_post_id` int(11) NULL DEFAULT NULL COMMENT 'ID последнего поста в категории',
  `last_thread_id` int(11) NULL DEFAULT NULL,
  `post_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `thread_count` int(11) NOT NULL DEFAULT 0,
  `is_close` int(11) NOT NULL,
  `icon_svg` varchar(3500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Ссылка для перехода',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Скрыть раздел',
  `can_create_topics` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Можно создавать темы',
  `can_reply_topics` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Можно отвечать в темах',
  `can_view_topics` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Можно просматривать темы',
  `is_moderated` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Требуется модерация тем',
  `sort_order` int(11) NULL DEFAULT 0,
  `can_users_delete_own_threads` tinyint(1) NOT NULL DEFAULT 0,
  `can_users_delete_own_posts` tinyint(1) NOT NULL DEFAULT 0,
  `edit_timeout_minutes` int(11) NOT NULL DEFAULT 30,
  `notify_telegram` tinyint(1) NOT NULL DEFAULT 0,
  `max_post_length` int(11) NOT NULL DEFAULT 20000,
  `thread_delete_timeout_minutes` int(11) NOT NULL DEFAULT 30,
  `hide_last_topic` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Скрывать последнюю тему раздела',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clan_chat' => "
CREATE TABLE `forum_clan_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `clan_id`(`clan_id` ASC) USING BTREE,
  CONSTRAINT `forum_clan_chat_ibfk_1` FOREIGN KEY (`clan_id`) REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clan_members' => "
CREATE TABLE `forum_clan_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `join_date` datetime NOT NULL DEFAULT current_timestamp(),
  `role` enum('member','leader') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `clan_user`(`clan_id` ASC, `user_id` ASC) USING BTREE,
  CONSTRAINT `forum_clan_members_ibfk_1` FOREIGN KEY (`clan_id`) REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clan_post_images' => "
CREATE TABLE `forum_clan_post_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `post_id_idx`(`post_id` ASC) USING BTREE,
  CONSTRAINT `fk_post_images_post_id` FOREIGN KEY (`post_id`) REFERENCES `forum_clan_posts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clan_posts' => "
CREATE TABLE `forum_clan_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `clan_id_idx`(`clan_id` ASC) USING BTREE,
  INDEX `user_id_idx`(`user_id` ASC) USING BTREE,
  CONSTRAINT `fk_clan_posts_clan_id` FOREIGN KEY (`clan_id`) REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_clan_posts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clan_requests' => "
CREATE TABLE `forum_clan_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `clan_user_request`(`clan_id` ASC, `user_id` ASC) USING BTREE,
  CONSTRAINT `forum_clan_requests_ibfk_1` FOREIGN KEY (`clan_id`) REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_clans' => "
CREATE TABLE `forum_clans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NULL DEFAULT NULL,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `desc_full` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `background_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `text_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `acceptance` int(11) NULL DEFAULT NULL COMMENT 'Принятие в клан 1 - автоматическое, 2 по согласию клан-лидера.',
  `clan_name_game` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `verification` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_moderator_log' => "
CREATE TABLE `forum_moderator_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `moderator_id` int(11) NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` enum('thread','post') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `moderator_id`(`moderator_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_moderators' => "
CREATE TABLE `forum_moderators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NULL DEFAULT NULL COMMENT 'NULL означает доступ ко всем категориям',
  `can_delete_threads` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete_posts` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_posts` tinyint(1) NOT NULL DEFAULT 0,
  `can_move_threads` tinyint(1) NOT NULL DEFAULT 0,
  `can_pin_threads` tinyint(1) NOT NULL DEFAULT 0,
  `can_close_threads` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve_threads` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL COMMENT 'ID админа, который назначил модератора',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_user_category`(`user_id` ASC, `category_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_notifications' => "
CREATE TABLE `forum_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `notification_type` enum('reply_to_thread','reply_to_post') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_notification_index`(`user_id` ASC, `is_read` ASC) USING BTREE,
  INDEX `thread_index`(`thread_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_poll_options' => "
CREATE TABLE `forum_poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `poll_id`(`poll_id` ASC) USING BTREE,
  CONSTRAINT `forum_poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `forum_polls` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_poll_votes' => "
CREATE TABLE `forum_poll_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_vote`(`poll_id` ASC, `user_id` ASC, `option_id` ASC) USING BTREE,
  INDEX `option_id`(`option_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  CONSTRAINT `forum_poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `forum_polls` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `forum_poll_votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `forum_poll_options` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `forum_poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_polls' => "
CREATE TABLE `forum_polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) NOT NULL,
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT 0,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `thread_poll`(`thread_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_post_likes' => "
CREATE TABLE `forum_post_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `like_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Путь к изображению лайка',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_post_user`(`post_id` ASC, `user_id` ASC) USING BTREE COMMENT 'Один пользователь - один лайк на пост',
  INDEX `post_id`(`post_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_posts' => "
CREATE TABLE `forum_posts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `reply_to_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `reply_to_id`(`reply_to_id` ASC) USING BTREE,
  INDEX `reply_to_id_2`(`reply_to_id` ASC) USING BTREE,
  INDEX `idx_thread_id_id`(`thread_id` ASC, `id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_thread_views' => "
CREATE TABLE `forum_thread_views` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_thread_user`(`thread_id` ASC, `user_id` ASC) USING BTREE,
  INDEX `idx_thread_ip`(`thread_id` ASC, `ip_address` ASC) USING BTREE,
  INDEX `idx_viewed_at`(`viewed_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_threads' => "
CREATE TABLE `forum_threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(11) NOT NULL DEFAULT 0 COMMENT 'Количество просмотров',
  `replies` int(11) NOT NULL DEFAULT 0 COMMENT 'Количество ответов',
  `first_message_id` int(11) NOT NULL,
  `last_reply_user_id` int(11) NULL DEFAULT NULL COMMENT 'ID последнего ответившего',
  `last_post_id` int(11) NULL DEFAULT NULL COMMENT 'ID последнего поста',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Закреплена ли тема',
  `is_closed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Закрыта ли тема',
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Флаг одобрения темы модератором',
  `approved_by` int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'ID модератора, который одобрил тему',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'Дата и время одобрения темы',
  `poll_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `poll_id`(`poll_id` ASC) USING BTREE,
  CONSTRAINT `forum_threads_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `forum_polls` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_user_activity' => "
CREATE TABLE `forum_user_activity` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `activity_type` enum('post','thread') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_action_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `actions_count` int(10) UNSIGNED NULL DEFAULT 0,
  `cooldown_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_activity`(`user_id` ASC, `activity_type` ASC) USING BTREE,
  INDEX `last_action_time`(`last_action_time` ASC) USING BTREE,
  INDEX `cooldown_until`(`cooldown_until` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;",

            'forum_user_thread_tracks' => "
CREATE TABLE `forum_user_thread_tracks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `last_read_post_id` int(10) UNSIGNED NOT NULL,
  `last_visit` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_subscribed` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Подписка на уведомления',
  `last_read_position` int(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_thread_unique`(`user_id` ASC, `thread_id` ASC) USING BTREE,
  INDEX `last_visit_index`(`last_visit` ASC) USING BTREE,
  INDEX `idx_user_thread`(`user_id` ASC, `thread_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;"
        ];

        $tableOrder = [
            'forum_categories',     // Нет внешних зависимостей
            'forum_clans',         // Нет внешних зависимостей
            'forum_threads',       // Зависит от categories
            'forum_polls',         // Зависит от threads
            'forum_poll_options',  // Зависит от polls
            'forum_posts',        // Зависит от threads
            'forum_attachments',   // Зависит от posts
            'forum_clan_members',  // Зависит от clans
            'forum_clan_chat',    // Зависит от clans
            'forum_clan_posts',   // Зависит от clans
            'forum_clan_post_images', // Зависит от clan_posts
            'forum_clan_requests', // Зависит от clans
            'forum_moderator_log', // Зависит от threads и posts
            'forum_moderators',    // Зависит от categories
            'forum_notifications', // Зависит от threads и posts
            'forum_poll_votes',    // Зависит от polls и poll_options
            'forum_post_likes',    // Зависит от posts
            'forum_thread_views',  // Зависит от threads
            'forum_user_activity', // Нет внешних зависимостей
            'forum_user_thread_tracks' // Зависит от threads и posts
        ];

        // Получаем существующие таблицы
        $tablesResult = sql::getRows("SHOW TABLES;");
        $existingTables = array_map(fn($row) => reset($row), $tablesResult);

        foreach ($tableOrder as $table) {
            if (!in_array($table, $existingTables)) {
                try {
                    sql::run($tables[$table]);
                } catch (\Exception $e) {
                    throw $e; // Перебрасываем исключение дальше
                }
            }
        }

    }

}