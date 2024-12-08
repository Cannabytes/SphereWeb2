<?php

class updateSql
{
    private ?string $sql = "
CREATE TABLE IF NOT EXISTS `support_thread_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_name` varchar(600) DEFAULT NULL,
  `moderators` varchar(1000) DEFAULT NULL,
  `thread_count` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT = 1 DEFAULT  CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `support_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL COMMENT 'создатель вопроса',
  `last_user_id` int(11) DEFAULT NULL COMMENT 'ID последний ответ',
  `last_message_id` int(11) NOT NULL,
  `private` int(11) NOT NULL DEFAULT '0',
  `is_close` int(11) NOT NULL DEFAULT '0',
  `date_update` datetime DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `support_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` mediumtext,
  `screens` varchar(1000) NOT NULL,
  `date_update` datetime DEFAULT NULL,
  `date_create` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `support_message_screen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(60) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

    function __construct()
    {
        if ($this->sql == null or $this->sql == "") {
            return;
        }
        \Ofey\Logan22\model\db\sql::run($this->sql);
    }
}

