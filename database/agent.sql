-- ----------------------------------------------------------------------
-- 代理后台（一期：一级代理 + 只读）
-- 执行方式：导入本文件到 jp 库即可，可重复执行前请先确认字段不存在
-- ----------------------------------------------------------------------

-- 会员表新增代理标识
ALTER TABLE `user`
  ADD COLUMN `is_agent` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否代理 1是 0否' AFTER `is_seller`,
  ADD COLUMN `agent_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成为代理时间' AFTER `is_agent`,
  ADD KEY `idx_agent` (`is_agent`);
