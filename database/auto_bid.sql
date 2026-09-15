-- 虚拟用户自动出价任务表（后台 / 代理后台按拍品配置，定时脚本 php think bid:auto 执行）
CREATE TABLE IF NOT EXISTS `auto_bid` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '拍品ID',
  `interval_min` int(10) UNSIGNED NOT NULL DEFAULT 30 COMMENT '出价间隔（分钟），实际在 70%~130% 内随机',
  `max_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '最高出价金额，当前价达到后不再出价',
  `stop_hours` decimal(6, 2) NOT NULL DEFAULT 1.00 COMMENT '截拍前 N 小时停止出价',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1运行中 0已停用 2已结束',
  `stop_reason` varchar(100) NOT NULL DEFAULT '' COMMENT '结束原因',
  `next_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次出价时间',
  `last_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最近出价时间',
  `bid_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已自动出价次数',
  `creator_type` varchar(10) NOT NULL DEFAULT 'admin' COMMENT '创建端 admin/agent',
  `creator_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_goods`(`goods_id`) USING BTREE,
  INDEX `idx_status_next`(`status`, `next_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '虚拟用户自动出价任务' ROW_FORMAT = DYNAMIC;
