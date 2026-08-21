-- 会员表新增粉丝数量字段（后台可修改）
ALTER TABLE `user`
  ADD COLUMN `fans_count` INT NOT NULL DEFAULT 0 COMMENT '粉丝数量' AFTER `shop_score`;
