-- 提现账户：银行卡增加开户行；提现记录同步快照
ALTER TABLE `pay_account` ADD COLUMN `bank_branch` varchar(100) NOT NULL DEFAULT '' COMMENT '开户行（银行卡）' AFTER `bank_name`;
ALTER TABLE `withdraw` ADD COLUMN `bank_branch` varchar(100) NOT NULL DEFAULT '' COMMENT '开户行快照' AFTER `bank_name`;
