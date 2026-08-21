-- 站内信表（后台私信用户，与聊天 message 表区分）
CREATE TABLE IF NOT EXISTS `sys_message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '接收用户ID',
  `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送管理员ID',
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
  `content` TEXT NOT NULL COMMENT '内容',
  `is_read` TINYINT NOT NULL DEFAULT 0 COMMENT '0未读 1已读',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站内信';
