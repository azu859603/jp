
-- ---------------------------------------------------------------
-- 性能索引补充（2026-09-17）：后台筛选 / 报表 / 钱包类型筛选 / 自动出价列表
-- ---------------------------------------------------------------
ALTER TABLE `user` ADD KEY `idx_virtual_status` (`is_virtual`,`status`), ADD KEY `idx_auth_status` (`auth_status`), ADD KEY `idx_seller_check` (`seller_check`), ADD KEY `idx_pid_reg` (`pid`,`reg_time`), ADD KEY `idx_reg_time` (`reg_time`);
ALTER TABLE `balance_log` ADD KEY `idx_user_type_id` (`user_id`,`type`,`id`);
ALTER TABLE `auto_bid` ADD KEY `idx_status_id` (`status`,`id`);
