-- 竞拍网站数据库初始化脚本
-- MySQL 5.7 / utf8mb4

CREATE DATABASE IF NOT EXISTS `auction` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `auction`;

SET NAMES utf8mb4;

-- ----------------------------
-- 管理员表
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '密码',
  `real_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
  `role` TINYINT NOT NULL DEFAULT 1 COMMENT '1超级管理员 2普通管理员',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `last_login_ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- 管理员操作日志
-- ----------------------------
DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `action` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '操作内容',
  `ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志';

-- ----------------------------
-- 会员表
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `password` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `real_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `id_card` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '身份证号',
  `id_card_front` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证正面照',
  `id_card_back` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '身份证反面照',
  `auth_status` TINYINT NOT NULL DEFAULT 0 COMMENT '实名认证 0未认证 1审核中 2已通过 3已拒绝',
  `auth_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '认证拒绝原因',
  `auth_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '认证通过时间',
  `shop_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `company_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '企业名称',
  `license_img` TEXT NOT NULL COMMENT '营业执照/企业资料图片(逗号分隔)',
  `seller_intro` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '店铺介绍',
  `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
  `invite_code` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '我的邀请码',
  `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '邀请人ID',
  `is_seller` TINYINT NOT NULL DEFAULT 0 COMMENT '是否卖家 1是 0否',
  `seller_check` TINYINT NOT NULL DEFAULT 0 COMMENT '卖家审核 0待审核 1通过 2拒绝',
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '可用余额',
  `freeze_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '冻结余额(保证金)',
  `points` INT NOT NULL DEFAULT 0 COMMENT '积分',
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '个人佣金比例%(默认-1用系统)',
  `total_buy` INT NOT NULL DEFAULT 0 COMMENT '累计成交购买',
  `total_sell` INT NOT NULL DEFAULT 0 COMMENT '累计成交卖出',
  `shop_score` DECIMAL(3,2) NOT NULL DEFAULT 5.00 COMMENT '店铺评分',
  `fans_count` INT NOT NULL DEFAULT 0 COMMENT '粉丝数量',
  `deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '消保金',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `reg_ip` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '注册IP',
  `reg_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '注册时间',
  `last_login_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mobile` (`mobile`),
  UNIQUE KEY `uk_invite` (`invite_code`),
  KEY `idx_pid` (`pid`),
  KEY `idx_seller` (`is_seller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员表';

-- ----------------------------
-- 新闻公告
-- ----------------------------
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '新闻标题',
  `content` TEXT NOT NULL COMMENT '新闻内容',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 1显示 0隐藏',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻公告表';

-- ----------------------------
-- 收货地址
-- ----------------------------
DROP TABLE IF EXISTS `user_address`;
CREATE TABLE `user_address` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `province` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省',
  `city` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '市',
  `district` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '区县',
  `address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `is_default` TINYINT NOT NULL DEFAULT 0 COMMENT '默认地址 1是',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收货地址表';

-- ----------------------------
-- 商品分类
-- ----------------------------
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `icon` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图标',
  `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1显示 0隐藏',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表';

-- ----------------------------
-- 拍卖商品表
-- ----------------------------
DROP TABLE IF EXISTS `goods`;
CREATE TABLE `goods` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seller_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '卖家ID',
  `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商品标题',
  `cover` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `images` TEXT COMMENT '图集(JSON数组)',
  `content` TEXT COMMENT '商品详情',
  `start_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '起拍价',
  `raise_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '加价幅度',
  `reserve_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '保留价(低于不出售,0无)',
  `deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '保证金',
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例%(0用系统默认)',
  `reference_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '参考价',
  `is_featured` TINYINT NOT NULL DEFAULT 0 COMMENT '是否精选 1是',
  `is_free_shipping` TINYINT NOT NULL DEFAULT 0 COMMENT '是否包邮 1是',
  `start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开拍时间',
  `end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `delay_seconds` INT NOT NULL DEFAULT 0 COMMENT '延时秒数(结束前N秒出价自动延时)',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待审核 1拍卖中 2已成交 3流拍 4已下架 5审核拒绝',
  `refuse_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核拒绝原因',
  `view_count` INT NOT NULL DEFAULT 0 COMMENT '浏览量',
  `bid_count` INT NOT NULL DEFAULT 0 COMMENT '出价次数',
  `final_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '成交价',
  `winner_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '得标者ID',
  `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_seller` (`seller_id`),
  KEY `idx_cate` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_endtime` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='拍卖商品表';

-- ----------------------------
-- 出价记录表
-- ----------------------------
DROP TABLE IF EXISTS `bid_record`;
CREATE TABLE `bid_record` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goods_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '出价金额',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0竞拍中 1成交 2流拍退回',
  `is_winner` TINYINT NOT NULL DEFAULT 0 COMMENT '是否得标 1是',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_goods` (`goods_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='出价记录表';

-- ----------------------------
-- 商品收藏表
-- ----------------------------
DROP TABLE IF EXISTS `goods_favorite`;
CREATE TABLE `goods_favorite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `goods_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_goods` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品收藏表';

-- ----------------------------
-- 店铺关注表
-- ----------------------------
DROP TABLE IF EXISTS `seller_follow`;
CREATE TABLE `seller_follow` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `seller_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '卖家用户ID',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_seller` (`user_id`,`seller_id`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='店铺关注表';

-- ----------------------------
-- 浏览足迹表
-- ----------------------------
DROP TABLE IF EXISTS `browse_history`;
CREATE TABLE `browse_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `goods_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '商品ID',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='浏览足迹表';

-- ----------------------------
-- 售后单表
-- ----------------------------
DROP TABLE IF EXISTS `after_sale`;
CREATE TABLE `after_sale` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
  `order_no` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` INT UNSIGNED NOT NULL COMMENT '买家ID',
  `seller_id` INT UNSIGNED NOT NULL COMMENT '卖家ID',
  `goods_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
  `goods_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '商品标题',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '退款金额',
  `reason` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '售后理由',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0待处理 1已同意退款 2已驳回',
  `admin_note` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请时间',
  `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后单表';

-- ----------------------------
-- 订单表
-- ----------------------------
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `goods_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `goods_title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '商品标题',
  `goods_cover` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '商品封面',
  `seller_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '卖家ID',
  `buyer_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '买家ID',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '成交价',
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '佣金比例%',
  `commission` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '佣金金额',
  `seller_income` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '卖家实收',
  `deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '买家保证金',
  `pay_status` TINYINT NOT NULL DEFAULT 0 COMMENT '支付状态 0未支付 1已支付',
  `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `order_status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待付款 1待发货 2待收货 3已完成 4已取消',
  `ship_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `ship_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '收货电话',
  `ship_address` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '收货地址',
  `ship_company` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '快递公司',
  `ship_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '快递单号',
  `ship_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发货时间',
  `finish_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
  `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_goods` (`goods_id`),
  KEY `idx_buyer` (`buyer_id`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='拍卖订单表';

-- ----------------------------
-- 余额流水表
-- ----------------------------
DROP TABLE IF EXISTS `balance_log`;
CREATE TABLE `balance_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `type` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '类型:recharge充值 deposit保证金 pay支付 income收入 refund退回 withdraw提现 reward奖励',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动金额(正入负出)',
  `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后可用余额',
  `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='余额流水表';

-- ----------------------------
-- 提现申请表
-- ----------------------------
DROP TABLE IF EXISTS `withdraw`;
CREATE TABLE `withdraw` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额',
  `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `account_type` TINYINT NOT NULL DEFAULT 1 COMMENT '1支付宝 2微信 3银行卡',
  `account` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '收款账号',
  `account_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '收款人',
  `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待审核 1已打款 2已拒绝',
  `refuse_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `handle_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现申请表';

-- ----------------------------
-- 系统设置表
-- ----------------------------
DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` TEXT COMMENT '配置值',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

CREATE TABLE `banner` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
  `url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1显示 0隐藏',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='首页轮播图表';

-- ----------------------------
-- 初始化数据
-- ----------------------------
-- 默认管理员 admin / admin123
INSERT INTO `admin_user` (`username`, `password`, `real_name`, `role`, `status`, `create_time`) VALUES
('admin', '0c909a141f1f2c0a1cb602b0b2d7d050', '超级管理员', 1, 1, UNIX_TIMESTAMP());

-- 默认系统设置
INSERT INTO `setting` (`name`, `value`, `create_time`) VALUES
('site_name', '竞拍商城', UNIX_TIMESTAMP()),
('site_logo', '', UNIX_TIMESTAMP()),
('site_url', '', UNIX_TIMESTAMP()),
('commission_rate', '10', UNIX_TIMESTAMP()),
('seller_check', '1', UNIX_TIMESTAMP()),
('goods_check', '1', UNIX_TIMESTAMP()),
('invite_code', '8888', UNIX_TIMESTAMP()),
('withdraw_fee', '1', UNIX_TIMESTAMP()),
('service_phone', '18888888888', UNIX_TIMESTAMP()),
('service_qq', '', UNIX_TIMESTAMP()),
('auction_delay', '0', UNIX_TIMESTAMP()),
('user_protocol', '欢迎使用竞拍商城，请遵守平台规则。', UNIX_TIMESTAMP());

-- 默认商品分类
INSERT INTO `category` (`name`, `icon`, `sort`, `status`, `create_time`) VALUES
('数码家电', '', 1, 1, UNIX_TIMESTAMP()),
('潮玩手办', '', 2, 1, UNIX_TIMESTAMP()),
('文玩收藏', '', 3, 1, UNIX_TIMESTAMP()),
('奢侈品', '', 4, 1, UNIX_TIMESTAMP()),
('珠宝首饰', '', 5, 1, UNIX_TIMESTAMP()),
('日用百货', '', 6, 1, UNIX_TIMESTAMP());
