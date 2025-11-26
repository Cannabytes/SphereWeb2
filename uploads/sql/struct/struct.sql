SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for bonus
-- ----------------------------
DROP TABLE IF EXISTS `bonus`;
CREATE TABLE `bonus`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT NULL,
  `enchant` int NULL DEFAULT 0,
  `phrase` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `issued` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of bonus
-- ----------------------------

-- ----------------------------
-- Table structure for bonus_code
-- ----------------------------
DROP TABLE IF EXISTS `bonus_code`;
CREATE TABLE `bonus_code`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT 1,
  `enchant` int NULL DEFAULT 0,
  `phrase` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `start_date_code` datetime NULL DEFAULT NULL,
  `end_date_code` datetime NULL DEFAULT NULL,
  `disposable` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of bonus_code
-- ----------------------------

-- ----------------------------
-- Table structure for bonus_pack_codes
-- ----------------------------
DROP TABLE IF EXISTS `bonus_pack_codes`;
CREATE TABLE `bonus_pack_codes`  (
  `id` int NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  `issued` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of bonus_pack_codes
-- ----------------------------

-- ----------------------------
-- Table structure for bonus_pack_item
-- ----------------------------
DROP TABLE IF EXISTS `bonus_pack_item`;
CREATE TABLE `bonus_pack_item`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT NULL,
  `enchant` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of bonus_pack_item
-- ----------------------------

-- ----------------------------
-- Table structure for chat
-- ----------------------------
DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `message` varchar(1200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `player` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `date` timestamp NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `server` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chat
-- ----------------------------

-- ----------------------------
-- Table structure for donate
-- ----------------------------
DROP TABLE IF EXISTS `donate`;
CREATE TABLE `donate`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT NULL,
  `cost` float NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  `is_pack` int NULL DEFAULT NULL,
  `pack_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of donate
-- ----------------------------

-- ----------------------------
-- Table structure for donate_history
-- ----------------------------
DROP TABLE IF EXISTS `donate_history`;
CREATE TABLE `donate_history`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `amount` int NULL DEFAULT NULL,
  `cost` int NULL DEFAULT NULL,
  `char_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  `date` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of donate_history
-- ----------------------------

-- ----------------------------
-- Table structure for donate_history_pay
-- ----------------------------
DROP TABLE IF EXISTS `donate_history_pay`;
CREATE TABLE `donate_history_pay`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `point` int NULL DEFAULT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pay_system` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `id_admin_pay` int NULL DEFAULT NULL COMMENT 'Если администратор зачислил вручную, запишим его ID',
  `date` datetime NULL DEFAULT NULL,
  `sphere` int NULL DEFAULT 0 COMMENT '1 если деньги зачислила сфера (к примеру это просто бонус)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of donate_history_pay
-- ----------------------------

-- ----------------------------
-- Table structure for donate_pack
-- ----------------------------
DROP TABLE IF EXISTS `donate_pack`;
CREATE TABLE `donate_pack`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `pack_id` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of donate_pack
-- ----------------------------

-- ----------------------------
-- Table structure for donate_uuid
-- ----------------------------
DROP TABLE IF EXISTS `donate_uuid`;
CREATE TABLE `donate_uuid`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `pay_system` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `request` varchar(12000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `date` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of donate_uuid
-- ----------------------------

-- ----------------------------
-- Table structure for github_updates
-- ----------------------------
DROP TABLE IF EXISTS `github_updates`;
CREATE TABLE `github_updates`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `sha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `url` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `message` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `date` datetime NULL DEFAULT NULL,
  `date_update` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of github_updates
-- ----------------------------

-- ----------------------------
-- Table structure for items_data
-- ----------------------------
DROP TABLE IF EXISTS `items_data`;
CREATE TABLE `items_data`  (
  `item_id` int NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `additionalname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_trade` int NULL DEFAULT NULL,
  `is_drop` int NULL DEFAULT NULL,
  `is_destruct` int NULL DEFAULT NULL,
  `crystal_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `consume_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'consume_type_normal',
  PRIMARY KEY (`item_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of items_data
-- ----------------------------

-- ----------------------------
-- Table structure for launcher
-- ----------------------------
DROP TABLE IF EXISTS `launcher`;
CREATE TABLE `launcher`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `l2app` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `args` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `phrase` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of launcher
-- ----------------------------

-- ----------------------------
-- Table structure for log_transfer_spherecoin
-- ----------------------------
DROP TABLE IF EXISTS `log_transfer_spherecoin`;
CREATE TABLE `log_transfer_spherecoin`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_sender` int NULL DEFAULT NULL,
  `user_receiving` int NULL DEFAULT NULL,
  `point` int NULL DEFAULT NULL,
  `date` timestamp NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of log_transfer_spherecoin
-- ----------------------------

-- ----------------------------
-- Table structure for logs_all
-- ----------------------------
DROP TABLE IF EXISTS `logs_all`;
CREATE TABLE `logs_all`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `time` datetime NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `phrase` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `variables` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  `request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `method` enum('post','get') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `action` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `file` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `line` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of logs_all
-- ----------------------------

-- ----------------------------
-- Table structure for notification
-- ----------------------------
DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `message` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `date` datetime NULL DEFAULT NULL,
  `read` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of notification
-- ----------------------------

-- ----------------------------
-- Table structure for page_comments
-- ----------------------------
DROP TABLE IF EXISTS `page_comments`;
CREATE TABLE `page_comments`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_id` int NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  `message` varchar(3000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `trash` int NOT NULL DEFAULT 0,
  `date_create` timestamp NOT NULL DEFAULT current_timestamp,
  `date_update` timestamp NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of page_comments
-- ----------------------------

-- ----------------------------
-- Table structure for pages
-- ----------------------------
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_news` int NULL DEFAULT 0,
  `name` varchar(140) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `comment` int NOT NULL DEFAULT 0,
  `date_create` timestamp NOT NULL DEFAULT current_timestamp,
  `date_update` timestamp NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `trash` int NOT NULL DEFAULT 0,
  `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'ru',
  `poster` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `link` varchar(1200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of pages
-- ----------------------------

-- ----------------------------
-- Table structure for player_accounts
-- ----------------------------
DROP TABLE IF EXISTS `player_accounts`;
CREATE TABLE `player_accounts`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  `characters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `date_update_characters` datetime NULL DEFAULT NULL,
  `password_hide` int NULL DEFAULT NULL,
  `date_create` datetime NULL DEFAULT NULL,
  `date_update` datetime NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `errors`;
CREATE TABLE `errors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `request` TEXT NOT NULL,
    `trace` TEXT NOT NULL,
    `user_id` INT NOT NULL,
    `date` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for referrals
-- ----------------------------
DROP TABLE IF EXISTS `referrals`;
CREATE TABLE `referrals`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `leader_id` int NULL DEFAULT NULL,
  `join_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `done_date` datetime DEFAULT NULL,
  `done` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for server_cache
-- ----------------------------
DROP TABLE IF EXISTS `server_cache`;
CREATE TABLE `server_cache`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `date_create` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of server_cache
-- ----------------------------

-- ----------------------------
-- Table structure for server_data
-- ----------------------------
DROP TABLE IF EXISTS `server_data`;
CREATE TABLE `server_data`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `val` varchar(6000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `server_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `key`(`key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of server_data
-- ----------------------------

-- ----------------------------
-- Table structure for server_description
-- ----------------------------
DROP TABLE IF EXISTS `server_description`;
CREATE TABLE `server_description`  (
  `server_id` int NOT NULL,
  `lang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `page_id` int NULL DEFAULT NULL,
  `default` int NULL DEFAULT 0,
  `date_create` timestamp NULL DEFAULT current_timestamp,
  `date_update` timestamp NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of server_description
-- ----------------------------

-- ----------------------------
-- Table structure for servers
-- ----------------------------
DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `data` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of servers
-- ----------------------------

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `setting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `serverId` int NULL DEFAULT NULL,
  `dateUpdate` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of settings
-- ----------------------------

-- ----------------------------
-- Table structure for shop_items
-- ----------------------------
DROP TABLE IF EXISTS `shop_items`;
CREATE TABLE `shop_items`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `serverId` int NULL DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `category` varchar(120) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of shop_items
-- ----------------------------

-- ----------------------------
-- Table structure for startpacks
-- ----------------------------
DROP TABLE IF EXISTS `startpacks`;
CREATE TABLE `startpacks`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `name` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cost` float NULL DEFAULT NULL,
  `items` varchar(6000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of startpacks
-- ----------------------------

-- ----------------------------
-- Table structure for statistic_online
-- ----------------------------
DROP TABLE IF EXISTS `statistic_online`;
CREATE TABLE `statistic_online`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `loginserver` int NULL DEFAULT NULL,
  `gameserver` int NULL DEFAULT NULL,
  `count_online_player` MEDIUMTEXT NULL DEFAULT NULL,
  `time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of statistic_online
-- ----------------------------

-- ----------------------------
-- Table structure for user_auth_log
-- ----------------------------
DROP TABLE IF EXISTS `user_auth_log`;
CREATE TABLE `user_auth_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `country` varchar(60) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `browser` varchar(600) DEFAULT NULL,
  `fingerprint` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `device` varchar(100) DEFAULT NULL,
  `user_agent` varchar(600) DEFAULT NULL,
  `signature` varchar(1500) NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_auth_log
-- ----------------------------

-- ----------------------------
-- Table structure for user_variables
-- ----------------------------
DROP TABLE IF EXISTS `user_variables`;
CREATE TABLE `user_variables`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `user_id` int NOT NULL,
  `var` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `val` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `date_create` datetime NULL DEFAULT current_timestamp,
  `date_update` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_variables
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
                                       `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `signature` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `access_level` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
    `donate_point` float DEFAULT '0',
    `avatar` varchar(62) COLLATE utf8mb4_unicode_ci DEFAULT 'none.jpeg',
    `avatar_background` varchar(62) COLLATE utf8mb4_unicode_ci DEFAULT 'none.jpeg',
    `timezone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `country` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `city` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `server_id` int(11) DEFAULT NULL,
    `lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `last_activity` datetime DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_last_activity` (`last_activity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of users
-- ----------------------------

-- ----------------------------
-- Table structure for users_password_forget
-- ----------------------------
DROP TABLE IF EXISTS `users_password_forget`;
CREATE TABLE `users_password_forget`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `date` datetime NULL DEFAULT current_timestamp,
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `active` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users_password_forget
-- ----------------------------

-- ----------------------------
-- Table structure for users_permission
-- ----------------------------
DROP TABLE IF EXISTS `users_permission`;
CREATE TABLE `users_permission`  (
  `user_id` int NOT NULL,
  `ban_page` int NULL DEFAULT 0,
  `ban_ticket` int NULL DEFAULT 0,
  `ban_gallery` int NULL DEFAULT 0
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users_permission
-- ----------------------------

-- ----------------------------
-- Table structure for warehouse
-- ----------------------------
DROP TABLE IF EXISTS `warehouse`;
CREATE TABLE `warehouse`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_id` int NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  `item_id` int NULL DEFAULT NULL,
  `count` int NULL DEFAULT NULL,
  `enchant` int NULL DEFAULT 0,
  `phrase` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `issued` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `last_message_id` int NULL DEFAULT NULL,
  `last_user_id` int NULL DEFAULT NULL,
  `is_closed` int NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `tickets_message`;
CREATE TABLE `tickets_message`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  `is_file` int NULL DEFAULT 0,
  `message` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `read` int NULL DEFAULT 0,
  `date` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `tickets_file`;
CREATE TABLE `tickets_file`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NULL DEFAULT NULL,
  `message_id` int NULL DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_id` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `streams`;
CREATE TABLE `streams`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL DEFAULT NULL,
  `channel` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `channel_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `data` varchar(3000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `confirmed` int NULL DEFAULT NULL,
  `is_live` int NULL DEFAULT NULL,
  `auto_check_date` datetime NULL DEFAULT NULL,
  `dateUpdate` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `dateCreate` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `items_increase`;
CREATE TABLE `items_increase` (
    `id` int NOT NULL AUTO_INCREMENT,
    `date` datetime NULL DEFAULT NULL,
    `itemId` int(11) NOT NULL,
    `data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
    `server_id` int NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `support_message`;
CREATE TABLE `support_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` mediumtext,
  `screens` varchar(1000) NOT NULL,
  `date_update` datetime DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `support_message_screen`;
CREATE TABLE `support_message_screen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(60) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `support_thread`;
CREATE TABLE `support_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL COMMENT 'создатель вопроса',
  `last_user_id` int(11) DEFAULT NULL COMMENT 'ID последнего ответа',
  `last_message_id` int(11) DEFAULT NULL COMMENT 'ID последнего сообщения',
  `private` int(11) NOT NULL DEFAULT '0',
  `is_close` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `support_thread_name`;
CREATE TABLE `support_thread_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_name` varchar(600) DEFAULT NULL,
  `moderators` varchar(1000) DEFAULT NULL,
  `thread_count` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

INSERT INTO `support_thread_name` (`id`, `thread_name`, `moderators`, `thread_count`, `weight`) VALUES
(1, 'account', NULL, 0, 0),
(2, 'Client, crash', NULL, 0, 0),
(3, 'Server problems', NULL, 0, 0),
(4, 'Payment problems', NULL, 0, 0),
(5, 'Complaints about players', NULL, 0, 0),
(6, 'other', NULL, 0, 0);

DROP TABLE IF EXISTS `support_read_topics`;
CREATE TABLE `support_read_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `read_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Создание таблицы forum_categories
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clans
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_user_activity
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_polls
CREATE TABLE `forum_polls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) NOT NULL,
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT 0,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_threads
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
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_poll_options
CREATE TABLE `forum_poll_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes_count` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `poll_id`(`poll_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_posts
CREATE TABLE `forum_posts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `reply_to_id` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `reply_to_id`(`reply_to_id` ASC) USING BTREE,
  INDEX `reply_to_id_2`(`reply_to_id` ASC) USING BTREE,
  INDEX `idx_thread_id_id`(`thread_id` ASC, `id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_attachments
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clan_members
CREATE TABLE `forum_clan_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `join_date` datetime NOT NULL DEFAULT current_timestamp(),
  `role` enum('member','leader') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `clan_user`(`clan_id` ASC, `user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clan_chat
CREATE TABLE `forum_clan_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `clan_id`(`clan_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clan_posts
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
  INDEX `user_id_idx`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clan_post_images
CREATE TABLE `forum_clan_post_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `post_id_idx`(`post_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_clan_requests
CREATE TABLE `forum_clan_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `clan_user_request`(`clan_id` ASC, `user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_moderator_log
CREATE TABLE `forum_moderator_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `moderator_id` int(11) NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` enum('thread','post','moderator','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `moderator_id`(`moderator_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_moderators
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_user_bans
CREATE TABLE `forum_user_bans` (
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_notifications
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_poll_votes
CREATE TABLE `forum_poll_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `unique_vote`(`poll_id` ASC, `user_id` ASC, `option_id` ASC) USING BTREE,
  INDEX `option_id`(`option_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_post_likes
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_thread_views
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- Создание таблицы forum_user_thread_tracks
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ЧАСТЬ 2: ДОБАВЛЕНИЕ ВНЕШНИХ КЛЮЧЕЙ
-- Добавление внешнего ключа к forum_threads
ALTER TABLE `forum_threads`
ADD CONSTRAINT `forum_threads_ibfk_1` FOREIGN KEY (`poll_id`)
REFERENCES `forum_polls` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_poll_options
ALTER TABLE `forum_poll_options`
ADD CONSTRAINT `forum_poll_options_ibfk_1` FOREIGN KEY (`poll_id`)
REFERENCES `forum_polls` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_polls
ALTER TABLE `forum_polls`
ADD CONSTRAINT `thread_poll` FOREIGN KEY (`thread_id`)
REFERENCES `forum_threads` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_clan_members
ALTER TABLE `forum_clan_members`
ADD CONSTRAINT `forum_clan_members_ibfk_1` FOREIGN KEY (`clan_id`)
REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_clan_chat
ALTER TABLE `forum_clan_chat`
ADD CONSTRAINT `forum_clan_chat_ibfk_1` FOREIGN KEY (`clan_id`)
REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешних ключей к forum_clan_posts
ALTER TABLE `forum_clan_posts`
ADD CONSTRAINT `fk_clan_posts_clan_id` FOREIGN KEY (`clan_id`)
REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
ADD CONSTRAINT `fk_clan_posts_user_id` FOREIGN KEY (`user_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_clan_post_images
ALTER TABLE `forum_clan_post_images`
ADD CONSTRAINT `fk_post_images_post_id` FOREIGN KEY (`post_id`)
REFERENCES `forum_clan_posts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешнего ключа к forum_clan_requests
ALTER TABLE `forum_clan_requests`
ADD CONSTRAINT `forum_clan_requests_ibfk_1` FOREIGN KEY (`clan_id`)
REFERENCES `forum_clans` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Добавление внешних ключей к forum_poll_votes
ALTER TABLE `forum_poll_votes`
ADD CONSTRAINT `forum_poll_votes_ibfk_1` FOREIGN KEY (`poll_id`)
REFERENCES `forum_polls` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
ADD CONSTRAINT `forum_poll_votes_ibfk_2` FOREIGN KEY (`option_id`)
REFERENCES `forum_poll_options` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
ADD CONSTRAINT `forum_poll_votes_ibfk_3` FOREIGN KEY (`user_id`)
REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

CREATE TABLE IF NOT EXISTS `sessions` (
   `session_id` VARCHAR(64) NOT NULL PRIMARY KEY,
   `user_id` INT NULL DEFAULT NULL,
   `ip_address` VARCHAR(45) NOT NULL,
   `user_agent` TEXT NOT NULL,
   `last_activity` INT UNSIGNED NOT NULL,
   `data` TEXT NOT NULL,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   `get_action_count` INT UNSIGNED NOT NULL DEFAULT 0,
   `post_action_count` INT UNSIGNED NOT NULL DEFAULT 0,
   `get_last_action_time` INT UNSIGNED NULL DEFAULT NULL,
   `post_last_action_time` INT UNSIGNED NULL DEFAULT NULL,
   `get_banned_until` INT UNSIGNED NULL DEFAULT NULL,
   `post_banned_until` INT UNSIGNED NULL DEFAULT NULL,
   INDEX `idx_last_activity` (`last_activity`),
   INDEX `idx_user_id` (`user_id`),
   INDEX `idx_get_banned_until` (`get_banned_until`),
   INDEX `idx_post_banned_until` (`post_banned_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
