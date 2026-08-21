CREATE TABLE IF NOT EXISTS `recharge` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '充值金额',
  `pay_type` TINYINT NOT NULL DEFAULT 1 COMMENT '1在线支付 2人工转账',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待审核 1已到账 2已拒绝',
  `refuse_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值申请表';
