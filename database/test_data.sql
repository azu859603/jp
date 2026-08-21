-- 测试数据（会员/卖家/商品/出价/订单/流水/提现）
USE `auction`;
SET NAMES utf8mb4;

-- 会员（密码均为 123456）
INSERT INTO `user` (`mobile`, `password`, `nickname`, `invite_code`, `pid`, `is_seller`, `seller_check`, `balance`, `freeze_balance`, `total_buy`, `total_sell`, `status`, `reg_time`, `create_time`) VALUES
('13800000001', '14e1b600b1fd579f47433b88e8d85291', '张三', 'A10001', 0, 0, 0, 500.00, 0.00, 1, 0, 1, 1786880000, 1786880000),
('13800000002', '14e1b600b1fd579f47433b88e8d85291', '李四', 'A10002', 1, 0, 1, 1000.00, 100.00, 0, 1, 1, 1786880100, 1786880100),
('13800000003', '14e1b600b1fd579f47433b88e8d85291', '王五', 'A10003', 1, 1, 1, 2000.00, 0.00, 0, 2, 1, 1786880200, 1786880200),
('13800000004', '14e1b600b1fd579f47433b88e8d85291', '赵六', 'A10004', 2, 0, 0, 0.00, 0.00, 0, 0, 0, 1786880300, 1786880300);

-- 商品
INSERT INTO `goods` (`seller_id`, `category_id`, `title`, `cover`, `images`, `content`, `start_price`, `raise_price`, `reserve_price`, `deposit`, `commission_rate`, `start_time`, `end_time`, `delay_seconds`, `status`, `view_count`, `bid_count`, `final_price`, `winner_id`, `create_time`) VALUES
(3, 1, 'iPhone 15 Pro 256G 国行', '', NULL, '95新，无拆无修，全套配件。', 3000.00, 100.00, 3800.00, 200.00, 10.00, 1786800000, 1787500000, 0, 1, 25, 6, 0, 0, 1786900000),
(3, 2, '限量版潮玩手办', '', NULL, '全新未拆封。', 200.00, 20.00, 0.00, 50.00, 10.00, 1786800000, 1787000000, 0, 2, 40, 8, 480.00, 2, 1786900100),
(2, 3, '和田玉手串', '', NULL, '真品保证。', 500.00, 50.00, 800.00, 100.00, 10.00, 1786800000, 1787400000, 0, 0, 3, 0, 0, 0, 1786900200),
(3, 1, 'iPad Air 5', '', NULL, '插电无法开机，售出不退。', 1500.00, 50.00, 0.00, 100.00, 10.00, 1786800000, 1787400000, 0, 5, 10, 0, 0, 0, 1786900300);

-- 出价记录
INSERT INTO `bid_record` (`goods_id`, `user_id`, `price`, `status`, `is_winner`, `create_time`) VALUES
(1, 2, 3100.00, 0, 0, 1786910000),
(1, 4, 3200.00, 0, 0, 1786911000),
(1, 2, 3300.00, 0, 0, 1786912000),
(2, 2, 300.00, 1, 1, 1786913000),
(2, 4, 320.00, 1, 0, 1786914000),
(2, 2, 480.00, 1, 1, 1786915000);

-- 订单（商品2 成交 480 元，佣金 10% = 48 元）
INSERT INTO `order` (`order_no`, `goods_id`, `goods_title`, `goods_cover`, `seller_id`, `buyer_id`, `price`, `commission_rate`, `commission`, `seller_income`, `deposit`, `pay_status`, `pay_time`, `order_status`, `ship_name`, `ship_mobile`, `ship_address`, `create_time`) VALUES
('AU20260817120000123', 2, '限量版潮玩手办', '', 3, 2, 480.00, 10.00, 48.00, 432.00, 50.00, 1, 1786920000, 3, '张三', '13800000001', '广东省深圳市南山区科技园1号', 1786916000);

-- 余额流水
INSERT INTO `balance_log` (`user_id`, `type`, `amount`, `balance`, `remark`, `create_time`) VALUES
(1, 'recharge', 500.00, 500.00, '余额充值', 1786880000),
(2, 'recharge', 1000.00, 1000.00, '余额充值', 1786880100),
(2, 'deposit', -100.00, 900.00, '拍卖保证金', 1786913000),
(3, 'income', 432.00, 2432.00, '商品成交收入', 1786920000),
(1, 'withdraw', -200.00, 300.00, '提现申请', 1786930000);

-- 提现记录
INSERT INTO `withdraw` (`user_id`, `amount`, `fee`, `account_type`, `account`, `account_name`, `status`, `create_time`) VALUES
(1, 200.00, 1.00, 1, 'zhangsan@alipay.com', '张三', 0, 1786930000),
(3, 500.00, 2.50, 3, '6222020200112233445', '王五', 1, 1786931000);
