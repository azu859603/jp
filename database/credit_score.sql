-- 会员表：店铺评分改为店铺星级（字段名不变，仅语义/注释），新增卖家信誉分
-- 信誉分初始 100；卖家付款后长期不发货，定时任务 order:remind 每次提醒扣 1 分；后台可编辑
ALTER TABLE `user`
  MODIFY COLUMN `shop_score` DECIMAL(3,2) NOT NULL DEFAULT 5.00 COMMENT '店铺星级(0-5)',
  ADD COLUMN `credit_score` INT NOT NULL DEFAULT 100 COMMENT '卖家信誉分(初始100,不发货扣1分)' AFTER `shop_score`;
