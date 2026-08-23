/*
Navicat MySQL Data Transfer

Source Server         : 本地数据库
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : auction

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2026-08-23 10:59:43
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `admin_log`
-- ----------------------------
DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `action` varchar(255) NOT NULL DEFAULT '' COMMENT '操作内容',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志';

-- ----------------------------
-- Records of admin_log
-- ----------------------------
INSERT INTO `admin_log` VALUES ('1', '1', '登录后台', '127.0.0.1', '1786974959');
INSERT INTO `admin_log` VALUES ('2', '1', '登录后台', '127.0.0.1', '1786975052');
INSERT INTO `admin_log` VALUES ('3', '1', '登录后台', '127.0.0.1', '1787005824');
INSERT INTO `admin_log` VALUES ('4', '1', '登录后台', '127.0.0.1', '1787016296');
INSERT INTO `admin_log` VALUES ('5', '1', '登录后台', '127.0.0.1', '1787016537');
INSERT INTO `admin_log` VALUES ('6', '1', '登录后台', '127.0.0.1', '1787017142');
INSERT INTO `admin_log` VALUES ('7', '1', '登录后台', '127.0.0.1', '1787019824');
INSERT INTO `admin_log` VALUES ('8', '1', '登录后台', '127.0.0.1', '1787021171');
INSERT INTO `admin_log` VALUES ('9', '1', '登录后台', '127.0.0.1', '1787021892');
INSERT INTO `admin_log` VALUES ('10', '1', '登录后台', '127.0.0.1', '1787022342');
INSERT INTO `admin_log` VALUES ('11', '1', '登录后台', '127.0.0.1', '1787025750');
INSERT INTO `admin_log` VALUES ('12', '1', '登录后台', '127.0.0.1', '1787030765');
INSERT INTO `admin_log` VALUES ('13', '1', '登录后台', '127.0.0.1', '1787032411');
INSERT INTO `admin_log` VALUES ('14', '1', '登录后台', '127.0.0.1', '1787033334');
INSERT INTO `admin_log` VALUES ('15', '1', '登录后台', '127.0.0.1', '1787033782');
INSERT INTO `admin_log` VALUES ('16', '1', '登录后台', '127.0.0.1', '1787034197');
INSERT INTO `admin_log` VALUES ('17', '1', '登录后台', '127.0.0.1', '1787039548');
INSERT INTO `admin_log` VALUES ('18', '1', '登录后台', '127.0.0.1', '1787043938');
INSERT INTO `admin_log` VALUES ('19', '1', '登录后台', '127.0.0.1', '1787044297');
INSERT INTO `admin_log` VALUES ('20', '1', '登录后台', '127.0.0.1', '1787050647');
INSERT INTO `admin_log` VALUES ('21', '1', '登录后台', '127.0.0.1', '1787051172');
INSERT INTO `admin_log` VALUES ('22', '1', '登录后台', '127.0.0.1', '1787058186');
INSERT INTO `admin_log` VALUES ('23', '1', '登录后台', '127.0.0.1', '1787100829');
INSERT INTO `admin_log` VALUES ('24', '1', '登录后台', '127.0.0.1', '1787106752');
INSERT INTO `admin_log` VALUES ('25', '1', '登录后台', '127.0.0.1', '1787106790');
INSERT INTO `admin_log` VALUES ('26', '1', '登录后台', '127.0.0.1', '1787109072');
INSERT INTO `admin_log` VALUES ('27', '1', '登录后台', '127.0.0.1', '1787132155');
INSERT INTO `admin_log` VALUES ('28', '1', '登录后台', '127.0.0.1', '1787152660');
INSERT INTO `admin_log` VALUES ('29', '1', '登录后台', '127.0.0.1', '1787183268');
INSERT INTO `admin_log` VALUES ('30', '1', '登录后台', '127.0.0.1', '1787185800');
INSERT INTO `admin_log` VALUES ('31', '1', '登录后台', '127.0.0.1', '1787198234');
INSERT INTO `admin_log` VALUES ('32', '1', '登录后台', '127.0.0.1', '1787400682');
INSERT INTO `admin_log` VALUES ('33', '1', '登录后台', '127.0.0.1', '1787405639');
INSERT INTO `admin_log` VALUES ('34', '1', '登录后台', '127.0.0.1', '1787446523');
INSERT INTO `admin_log` VALUES ('35', '1', '登录后台', '127.0.0.1', '1787452704');

-- ----------------------------
-- Table structure for `admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '密码',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `role` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1超级管理员 2普通管理员',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- Records of admin_user
-- ----------------------------
INSERT INTO `admin_user` VALUES ('1', 'admin', '14e1b600b1fd579f47433b88e8d85291', '超级管理员', '', '1', '1', '127.0.0.1', '1787452704', '1786973051', '0');

-- ----------------------------
-- Table structure for `after_sale`
-- ----------------------------
DROP TABLE IF EXISTS `after_sale`;
CREATE TABLE `after_sale` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `order_no` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(10) unsigned NOT NULL COMMENT '买家ID',
  `seller_id` int(10) unsigned NOT NULL COMMENT '卖家ID',
  `goods_id` int(10) unsigned NOT NULL COMMENT '商品ID',
  `goods_title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品标题',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '售后理由',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0待处理 1已同意退款 2已驳回',
  `admin_note` varchar(500) NOT NULL DEFAULT '' COMMENT '处理备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请时间',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='售后单';

-- ----------------------------
-- Records of after_sale
-- ----------------------------
INSERT INTO `after_sale` VALUES ('2', '6', 'AU20260818163929465185', '26', '25', '31', '青铜器', '306.00', '6666666666', '1', '1111', '1787043970', '1787044044');

-- ----------------------------
-- Table structure for `balance_log`
-- ----------------------------
DROP TABLE IF EXISTS `balance_log`;
CREATE TABLE `balance_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '类型:recharge充值 deposit保证金 pay支付 income收入 refund退回 withdraw提现 reward奖励',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动金额(正入负出)',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动后可用余额',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COMMENT='余额流水表';

-- ----------------------------
-- Records of balance_log
-- ----------------------------
INSERT INTO `balance_log` VALUES ('1', '1', 'recharge', '500.00', '500.00', '余额充值', '1786880000');
INSERT INTO `balance_log` VALUES ('2', '2', 'recharge', '1000.00', '1000.00', '余额充值', '1786880100');
INSERT INTO `balance_log` VALUES ('3', '2', 'deposit', '-100.00', '900.00', '拍卖保证金', '1786913000');
INSERT INTO `balance_log` VALUES ('4', '3', 'income', '432.00', '2432.00', '商品成交收入', '1786920000');
INSERT INTO `balance_log` VALUES ('5', '1', 'withdraw', '-200.00', '300.00', '提现申请', '1786930000');
INSERT INTO `balance_log` VALUES ('6', '1', 'withdraw', '-200.00', '300.00', '提现打款：200.00元', '1786974582');
INSERT INTO `balance_log` VALUES ('7', '1', 'recharge', '50.00', '350.00', '测试调整', '1786974582');
INSERT INTO `balance_log` VALUES ('8', '7', 'recharge', '100.00', '100.00', '余额充值（模拟支付）', '1786976673');
INSERT INTO `balance_log` VALUES ('17', '22', 'recharge', '10000.00', '10000.00', '余额充值（模拟支付）', '1787013421');
INSERT INTO `balance_log` VALUES ('18', '22', 'deposit', '-100.00', '9900.00', '拍卖保证金（和田玉手串）', '1787013433');
INSERT INTO `balance_log` VALUES ('19', '23', 'refund', '20.00', '20.00', '未拍中，保证金退回（测试拍品01）', '1787021926');
INSERT INTO `balance_log` VALUES ('20', '22', 'refund', '20.00', '9920.00', '未拍中，保证金退回（测试拍品01）', '1787021926');
INSERT INTO `balance_log` VALUES ('21', '21', 'refund', '20.00', '20.00', '未拍中，保证金退回（测试拍品01）', '1787021926');
INSERT INTO `balance_log` VALUES ('22', '25', 'recharge', '1000000.00', '1000000.00', '后台调整', '1787032205');
INSERT INTO `balance_log` VALUES ('23', '25', 'deposit', '-10000.00', '990000.00', '拍卖保证金（光绪元宝铜币）', '1787032225');
INSERT INTO `balance_log` VALUES ('24', '26', 'recharge', '555.00', '555.00', '后台调整', '1787040579');
INSERT INTO `balance_log` VALUES ('25', '26', 'deposit', '-555.00', '0.00', '拍卖保证金（哈哈哈哈哈）', '1787040606');
INSERT INTO `balance_log` VALUES ('26', '26', 'recharge', '100.00', '100.00', '后台调整', '1787040782');
INSERT INTO `balance_log` VALUES ('27', '26', 'deposit', '-100.00', '0.00', '拍卖保证金（祖传玉佩）', '1787040791');
INSERT INTO `balance_log` VALUES ('28', '26', 'refund', '100.00', '100.00', '未拍中，保证金退回（祖传玉佩）', '1787040905');
INSERT INTO `balance_log` VALUES ('29', '26', 'recharge', '1100.00', '1200.00', '后台调整', '1787041324');
INSERT INTO `balance_log` VALUES ('30', '26', 'pay', '-1100.00', '100.00', '拍卖订单支付：AU20260818161505120786', '1787041330');
INSERT INTO `balance_log` VALUES ('31', '25', 'income', '1080.00', '991080.00', '拍卖成交收入：AU20260818161505120786（平台佣金 ¥120.00）', '1787041330');
INSERT INTO `balance_log` VALUES ('32', '26', 'deposit', '-100.00', '0.00', '拍卖保证金（青铜器）', '1787041998');
INSERT INTO `balance_log` VALUES ('33', '26', 'recharge', '300.00', '300.00', '后台调整', '1787042548');
INSERT INTO `balance_log` VALUES ('34', '26', 'pay', '-206.00', '94.00', '拍卖订单支付：AU20260818163929465185', '1787042552');
INSERT INTO `balance_log` VALUES ('35', '25', 'income', '275.40', '991355.40', '拍卖成交收入：AU20260818163929465185（平台佣金 ¥30.60）', '1787042552');
INSERT INTO `balance_log` VALUES ('38', '26', 'refund', '306.00', '1600.00', '售后退款：AU20260818163929465185', '1787044044');
INSERT INTO `balance_log` VALUES ('39', '25', 'refund', '-275.40', '990000.00', '售后扣回成交收入：AU20260818163929465185', '1787044044');
INSERT INTO `balance_log` VALUES ('40', '20', 'recharge', '10000.00', '10000.00', '余额充值（模拟支付）', '1787059572');
INSERT INTO `balance_log` VALUES ('41', '20', 'withdraw', '-5000.00', '5000.00', '提现打款：5000.00元', '1787061704');
INSERT INTO `balance_log` VALUES ('45', '20', 'withdraw', '-5000.00', '0.00', '提现申请冻结：5000元', '1787062257');
INSERT INTO `balance_log` VALUES ('46', '20', 'refund', '5000.00', '5000.00', '提现拒绝退回：5000.00元', '1787062280');
INSERT INTO `balance_log` VALUES ('47', '20', 'withdraw', '-1000.00', '4000.00', '提现申请冻结：1000元', '1787062774');
INSERT INTO `balance_log` VALUES ('48', '27', 'recharge', '100000.00', '100000.00', '后台调整', '1787101281');
INSERT INTO `balance_log` VALUES ('49', '27', 'deposit', '-100.00', '99900.00', '拍卖保证金（测试）', '1787101292');
INSERT INTO `balance_log` VALUES ('50', '27', 'deposit', '-100.00', '99800.00', '拍卖保证金（测试商品）', '1787102102');
INSERT INTO `balance_log` VALUES ('51', '27', 'refund', '100.00', '99900.00', '未拍中，保证金退回（测试商品）', '1787102194');
INSERT INTO `balance_log` VALUES ('52', '27', 'pay', '-1100.00', '98800.00', '拍卖订单支付：AU20260819091634015010', '1787102272');
INSERT INTO `balance_log` VALUES ('53', '25', 'income', '1080.00', '992435.40', '拍卖成交收入：AU20260819091634015010（平台佣金 ¥120.00）', '1787102272');
INSERT INTO `balance_log` VALUES ('54', '27', 'recharge', '200.00', '99000.00', '后台调整', '1787106288');
INSERT INTO `balance_log` VALUES ('55', '20', 'recharge', '300.00', '4300.00', '充值到账：300.00元', '1787132168');
INSERT INTO `balance_log` VALUES ('56', '26', 'refund', '555.00', '649.00', '未拍中，保证金退回（哈哈哈哈哈）', '1787151522');
INSERT INTO `balance_log` VALUES ('57', '26', 'deposit', '-100.00', '549.00', '拍卖保证金（eee）', '1787154332');
INSERT INTO `balance_log` VALUES ('58', '27', 'deposit', '-100.00', '98900.00', '拍卖保证金（eee）', '1787154372');
INSERT INTO `balance_log` VALUES ('59', '27', 'refund', '100.00', '99000.00', '未拍中，保证金退回（eee）', '1787189719');
INSERT INTO `balance_log` VALUES ('60', '26', 'refund', '100.00', '649.00', '未拍中，保证金退回（eee）', '1787189719');
INSERT INTO `balance_log` VALUES ('61', '20', 'deposit', '-10.00', '4290.00', '拍卖保证金（111）', '1787198095');
INSERT INTO `balance_log` VALUES ('62', '20', 'refund', '1000.00', '5290.00', '提现拒绝退回：1000.00元', '1787198246');
INSERT INTO `balance_log` VALUES ('63', '20', 'withdraw', '-1000.00', '4290.00', '提现申请冻结：1000元', '1787198271');
INSERT INTO `balance_log` VALUES ('64', '20', 'income', '270.00', '4560.00', '拍卖成交收入：AU20260820120301760758（平台佣金 ¥30.00）', '1787198949');
INSERT INTO `balance_log` VALUES ('65', '20', 'refund', '10.00', '4570.00', '未拍中，保证金退回（111）', '1787400406');
INSERT INTO `balance_log` VALUES ('66', '30', 'recharge', '100000.00', '100000.00', '充值到账：100000.00元', '1787452709');
INSERT INTO `balance_log` VALUES ('67', '30', 'deposit', '-111.00', '99889.00', '拍卖保证金（111）', '1787452783');

-- ----------------------------
-- Table structure for `banner`
-- ----------------------------
DROP TABLE IF EXISTS `banner`;
CREATE TABLE `banner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1显示 0隐藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='首页轮播图表';

-- ----------------------------
-- Records of banner
-- ----------------------------
INSERT INTO `banner` VALUES ('1', '新品首发专场', '/uploads/20260818/134043_5943.png', '/?sort=new', '1', '1', '1787016992', '1787031644');
INSERT INTO `banner` VALUES ('2', '限时拍卖', '/uploads/20260818/134055_5122.jpeg', '/?sort=end', '2', '1', '1787016992', '1787031656');

-- ----------------------------
-- Table structure for `bid_record`
-- ----------------------------
DROP TABLE IF EXISTS `bid_record`;
CREATE TABLE `bid_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '出价金额',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本次出价冻结保证金',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0竞拍中 1成交 2流拍退回',
  `is_winner` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否得标 1是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_goods` (`goods_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COMMENT='出价记录表';

-- ----------------------------
-- Records of bid_record
-- ----------------------------
INSERT INTO `bid_record` VALUES ('1', '1', '2', '3100.00', '0.00', '0', '0', '1786910000');
INSERT INTO `bid_record` VALUES ('2', '1', '4', '3200.00', '0.00', '0', '0', '1786911000');
INSERT INTO `bid_record` VALUES ('3', '1', '2', '3300.00', '0.00', '0', '0', '1786912000');
INSERT INTO `bid_record` VALUES ('4', '2', '2', '300.00', '50.00', '1', '1', '1786913000');
INSERT INTO `bid_record` VALUES ('5', '2', '4', '320.00', '0.00', '1', '0', '1786914000');
INSERT INTO `bid_record` VALUES ('6', '2', '2', '480.00', '0.00', '1', '1', '1786915000');
INSERT INTO `bid_record` VALUES ('8', '3', '22', '550.00', '100.00', '0', '0', '1787013433');
INSERT INTO `bid_record` VALUES ('9', '3', '22', '600.00', '0.00', '0', '0', '1787013600');
INSERT INTO `bid_record` VALUES ('10', '3', '22', '650.00', '0.00', '0', '0', '1787013609');
INSERT INTO `bid_record` VALUES ('11', '8', '21', '100.00', '20.00', '2', '0', '1787011524');
INSERT INTO `bid_record` VALUES ('12', '8', '22', '130.00', '20.00', '2', '0', '1787013324');
INSERT INTO `bid_record` VALUES ('13', '8', '23', '160.00', '20.00', '2', '0', '1787014524');
INSERT INTO `bid_record` VALUES ('14', '8', '23', '170.00', '0.00', '1', '1', '1787019105');
INSERT INTO `bid_record` VALUES ('15', '26', '25', '110000.00', '10000.00', '1', '1', '1787032225');
INSERT INTO `bid_record` VALUES ('16', '28', '26', '10100.00', '555.00', '2', '0', '1787040606');
INSERT INTO `bid_record` VALUES ('17', '29', '26', '900.00', '100.00', '2', '0', '1787040791');
INSERT INTO `bid_record` VALUES ('18', '29', '26', '1000.00', '0.00', '2', '0', '1787040817');
INSERT INTO `bid_record` VALUES ('19', '29', '26', '1100.00', '0.00', '2', '0', '1787040860');
INSERT INTO `bid_record` VALUES ('20', '29', '26', '1200.00', '0.00', '1', '1', '1787040863');
INSERT INTO `bid_record` VALUES ('21', '31', '26', '306.00', '100.00', '1', '1', '1787041998');
INSERT INTO `bid_record` VALUES ('22', '28', '26', '80000.00', '0.00', '1', '1', '1787042669');
INSERT INTO `bid_record` VALUES ('23', '32', '27', '3100.00', '100.00', '1', '1', '1787101292');
INSERT INTO `bid_record` VALUES ('24', '27', '27', '1100.00', '100.00', '2', '0', '1787102102');
INSERT INTO `bid_record` VALUES ('25', '27', '27', '1200.00', '0.00', '1', '1', '1787102106');
INSERT INTO `bid_record` VALUES ('26', '33', '26', '350.00', '100.00', '2', '0', '1787154332');
INSERT INTO `bid_record` VALUES ('27', '33', '27', '450.00', '100.00', '2', '0', '1787154372');
INSERT INTO `bid_record` VALUES ('28', '33', '25', '650.00', '0.00', '2', '0', '1787184100');
INSERT INTO `bid_record` VALUES ('29', '33', '28', '750.00', '0.00', '2', '0', '1787186046');
INSERT INTO `bid_record` VALUES ('30', '33', '29', '850.00', '0.00', '2', '0', '1787187239');
INSERT INTO `bid_record` VALUES ('31', '33', '29', '950.00', '0.00', '2', '0', '1787187242');
INSERT INTO `bid_record` VALUES ('32', '33', '29', '1050.00', '0.00', '2', '0', '1787187244');
INSERT INTO `bid_record` VALUES ('33', '33', '29', '1150.00', '0.00', '2', '0', '1787187393');
INSERT INTO `bid_record` VALUES ('34', '33', '29', '1250.00', '0.00', '1', '1', '1787187396');
INSERT INTO `bid_record` VALUES ('35', '36', '20', '1211.00', '10.00', '2', '0', '1787198095');
INSERT INTO `bid_record` VALUES ('36', '36', '20', '1311.00', '0.00', '2', '0', '1787198097');
INSERT INTO `bid_record` VALUES ('37', '36', '20', '1411.00', '0.00', '2', '0', '1787198100');
INSERT INTO `bid_record` VALUES ('38', '36', '20', '1511.00', '0.00', '2', '0', '1787198102');
INSERT INTO `bid_record` VALUES ('39', '36', '20', '1611.00', '0.00', '2', '0', '1787198104');
INSERT INTO `bid_record` VALUES ('40', '36', '20', '1711.00', '0.00', '2', '0', '1787198109');
INSERT INTO `bid_record` VALUES ('41', '36', '20', '1811.00', '0.00', '2', '0', '1787198112');
INSERT INTO `bid_record` VALUES ('42', '37', '29', '200.00', '0.00', '2', '0', '1787198527');
INSERT INTO `bid_record` VALUES ('43', '37', '28', '300.00', '0.00', '1', '1', '1787198554');
INSERT INTO `bid_record` VALUES ('44', '36', '28', '1911.00', '0.00', '2', '0', '1787198713');
INSERT INTO `bid_record` VALUES ('45', '36', '28', '2011.00', '0.00', '1', '1', '1787198723');
INSERT INTO `bid_record` VALUES ('46', '38', '28', '211.00', '0.00', '0', '0', '1787400710');
INSERT INTO `bid_record` VALUES ('47', '38', '30', '311.00', '111.00', '0', '0', '1787452783');

-- ----------------------------
-- Table structure for `browse_history`
-- ----------------------------
DROP TABLE IF EXISTS `browse_history`;
CREATE TABLE `browse_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COMMENT='浏览足迹';

-- ----------------------------
-- Records of browse_history
-- ----------------------------
INSERT INTO `browse_history` VALUES ('23', '26', '29', '1787040905');
INSERT INTO `browse_history` VALUES ('25', '26', '30', '1787041207');
INSERT INTO `browse_history` VALUES ('29', '26', '31', '1787042369');
INSERT INTO `browse_history` VALUES ('34', '26', '28', '1787045577');
INSERT INTO `browse_history` VALUES ('48', '27', '32', '1787101391');
INSERT INTO `browse_history` VALUES ('51', '27', '28', '1787102026');
INSERT INTO `browse_history` VALUES ('55', '27', '27', '1787102194');
INSERT INTO `browse_history` VALUES ('66', '25', '28', '1787108159');
INSERT INTO `browse_history` VALUES ('72', '20', '34', '1787108237');
INSERT INTO `browse_history` VALUES ('74', '25', '34', '1787108605');
INSERT INTO `browse_history` VALUES ('75', '20', '28', '1787132964');
INSERT INTO `browse_history` VALUES ('77', '25', '33', '1787152731');
INSERT INTO `browse_history` VALUES ('78', '25', '30', '1787152737');
INSERT INTO `browse_history` VALUES ('82', '27', '33', '1787154373');
INSERT INTO `browse_history` VALUES ('84', '26', '33', '1787154677');
INSERT INTO `browse_history` VALUES ('90', '20', '33', '1787184104');
INSERT INTO `browse_history` VALUES ('92', '28', '33', '1787186047');
INSERT INTO `browse_history` VALUES ('109', '29', '33', '1787187727');
INSERT INTO `browse_history` VALUES ('118', '20', '36', '1787198113');
INSERT INTO `browse_history` VALUES ('122', '20', '37', '1787198599');
INSERT INTO `browse_history` VALUES ('127', '28', '36', '1787198984');
INSERT INTO `browse_history` VALUES ('149', '20', '38', '1787452584');
INSERT INTO `browse_history` VALUES ('153', '30', '38', '1787452994');
INSERT INTO `browse_history` VALUES ('156', '31', '38', '1787453913');
INSERT INTO `browse_history` VALUES ('157', '31', '39', '1787453916');

-- ----------------------------
-- Table structure for `category`
-- ----------------------------
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `name_tw` varchar(255) NOT NULL DEFAULT '',
  `name_en` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1显示 0隐藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表';

-- ----------------------------
-- Records of category
-- ----------------------------
INSERT INTO `category` VALUES ('1', '字画', '字画1', 'calligraphy', '', '/uploads/20260818/133819_6127.png', '1', '1', '1786973051', '1787452829');
INSERT INTO `category` VALUES ('2', '杂项', '杂项1', 'Miscellaneous', '', '/uploads/20260818/133852_1230.png', '2', '1', '1786973051', '1787452853');
INSERT INTO `category` VALUES ('3', '玉器', '玉器1', 'Jade', '', '/uploads/20260818/133934_5485.png', '3', '1', '1786973051', '1787452871');
INSERT INTO `category` VALUES ('4', '瓷器', '瓷器1', 'porcelain', '', '/uploads/20260818/133958_2340.png', '4', '1', '1786973051', '1787452898');

-- ----------------------------
-- Table structure for `goods`
-- ----------------------------
DROP TABLE IF EXISTS `goods`;
CREATE TABLE `goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '卖家ID',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '商品标题',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `images` text COMMENT '图集(JSON数组)',
  `content` text COMMENT '商品详情',
  `start_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '起拍价',
  `raise_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '加价幅度',
  `reserve_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保留价(低于不出售,0无)',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保证金',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '佣金比例%(0用系统默认)',
  `reference_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '参考价',
  `is_featured` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否精选 1是',
  `is_free_shipping` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否包邮 1是',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开拍时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `delay_seconds` int(11) NOT NULL DEFAULT '0' COMMENT '延时秒数(结束前N秒出价自动延时)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核 1拍卖中 2已成交 3流拍 4已下架 5审核拒绝',
  `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '审核拒绝原因',
  `view_count` int(11) NOT NULL DEFAULT '0' COMMENT '浏览量',
  `bid_count` int(11) NOT NULL DEFAULT '0' COMMENT '出价次数',
  `final_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '成交价',
  `winner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '得标者ID',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_seller` (`seller_id`),
  KEY `idx_cate` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_endtime` (`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COMMENT='拍卖商品表';

-- ----------------------------
-- Records of goods
-- ----------------------------
INSERT INTO `goods` VALUES ('2', '3', '2', '限量版潮玩手办', '', null, '全新未拆封。', '200.00', '20.00', '0.00', '50.00', '10.00', '0.00', '0', '0', '1786800000', '1787000000', '0', '2', '', '40', '8', '480.00', '2', '0', '1786900100', '0');
INSERT INTO `goods` VALUES ('8', '21', '1', '测试拍品01', '/uploads/20260818/084555_8979.png', '[\"/uploads/20260818/084555_8979.png\",\"/uploads/20260818/084559_3651.png\",\"/uploads/20260818/085232_7139.png\",\"/uploads/20260818/085705_6677.png\"]', '测试内容', '100.00', '10.00', '0.00', '20.00', '0.00', '500.00', '0', '0', '1787014246', '1787021400', '0', '2', '', '14', '4', '170.00', '23', '3', '1787014246', '1787021926');
INSERT INTO `goods` VALUES ('26', '20', '2', '光绪元宝铜币', '/uploads/20260818/134447_8796.png', '[\"\\/uploads\\/20260818\\/134447_8796.png\",\"\\/uploads\\/20260818\\/134456_2189.png\"]', '从历史背景来看，光绪元宝是清朝光绪年间流通的货币之一，由湖北两广总督张之洞率先引进英国铸币机器铸造，之后各省纷纷仿效。这枚铜币见证了晚清时期货币制度的变革，是中国近代货币史上的重要组成部分。\n在当时，这种铜币的铸造和流通，一定程度上适应了商品经济发展的需求，促进了经济的交流与贸易的往来。它的出现，也反映了西方工业文明对中国传统货币铸造业的影响。\n如今，这枚光绪元宝铜币已成为收藏爱好者眼中的珍品。它不仅具有较高的历史价值，能让人们直观地感受到晚清时期的社会经济状况，还因其独特的艺术风格和精湛的铸造工艺，具有了一定的艺术欣赏价值。每一枚光绪元宝铜币都承载着一段历史，是研究晚清历史和文化的重要实物资料。', '100000.00', '10000.00', '0.00', '10000.00', '0.00', '20000.00', '0', '0', '1787031898', '1787035260', '0', '2', '', '8', '1', '110000.00', '25', '4', '1787031898', '1787035278');
INSERT INTO `goods` VALUES ('27', '25', '1', '测试商品', '/uploads/20260818/142055_1705.png', '[\"\\/uploads\\/20260818\\/142055_1705.png\"]', '22222', '1000.00', '100.00', '0.00', '100.00', '0.00', '1.00', '0', '0', '1787102091', '1787102160', '0', '2', '', '4', '2', '1200.00', '27', '8', '1787034056', '1787102194');
INSERT INTO `goods` VALUES ('28', '20', '2', '哈哈哈哈哈', '/uploads/20260818/150347_7607.png', '[\"\\/uploads\\/20260818\\/150347_7607.png\"]', '88666664', '10000.00', '100.00', '0.00', '555.00', '0.00', '666.00', '0', '0', '1787036659', '1787137380', '0', '2', '', '42', '2', '80000.00', '26', '9', '1787036659', '1787151522');
INSERT INTO `goods` VALUES ('29', '25', '3', '祖传玉佩', '/uploads/20260818/161159_8451.png', '[\"\\/uploads\\/20260818\\/161159_8451.png\"]', '111', '800.00', '100.00', '0.00', '100.00', '0.00', '1000.00', '1', '1', '1787040736', '1787040900', '0', '2', '', '9', '4', '1200.00', '26', '5', '1787040736', '1787040905');
INSERT INTO `goods` VALUES ('30', '25', '1', '5分钟拍品', '/uploads/20260818/161958_2924.png', '[\"\\/uploads\\/20260818\\/161958_2924.png\"]', '不知道', '100.00', '100.00', '0.00', '100.00', '0.00', '100.00', '1', '1', '1787102066', '1787188560', '0', '3', '', '2', '0', '0.00', '0', '0', '1787041200', '1787189719');
INSERT INTO `goods` VALUES ('31', '25', '4', '青铜器', '/uploads/20260818/163213_7126.png', '[\"\\/uploads\\/20260818\\/163213_7126.png\"]', '本品为当代景德镇名家特制的限量版炉钧窑变釉“冠军尊”（花觚）。器形完美承袭商周青铜觚之经典尊贵典雅，大撇口、挺拔丰满，展现出包容万象的王者气度。该尊最核心的艺术成就不仅在于其端庄的政治设计，更在于其炉火纯青的窑变釉色工艺。\n尊外施浓郁尊贵的玫瑰紫、茄皮紫底釉，内壁在高温烧结中自然析出漫天繁星般的蓝色与白色流淌结晶点，形成了陶瓷界赞誉的“雪花蓝”或“星空斑”视觉效果。这种“入窑一色，出窑万彩”的炉钧窑变工艺极难控制，需精准把握窑炉内的还原气氛，每件作品的纹理皆为世间孤品。\n作为带有大师亲笔手签及“GJ 0116”独立限量的编号作品，本品属于典型的当代艺术瓷、现代大师瓷。它告别了古代瓷器的民间作坊粗制，代表了当代景德镇造币级别的极致工艺与国礼审美标准。因其极低的发行量与高规格的纪念背景，展现出不容小觑的长期艺术资产配置价值。', '200.00', '100.00', '0.00', '100.00', '0.00', '12.00', '1', '1', '1787041943', '1787042160', '0', '2', '', '2', '1', '306.00', '26', '6', '1787041943', '1787042369');
INSERT INTO `goods` VALUES ('32', '25', '1', '测试', '/uploads/20260819/090043_6554.png', '[\"\\/uploads\\/20260819\\/090043_6554.png\"]', '222222222', '3000.00', '100.00', '0.00', '100.00', '0.00', '1.00', '0', '0', '1787101245', '1787101380', '0', '2', '', '3', '1', '3100.00', '27', '7', '1787101245', '1787101387');
INSERT INTO `goods` VALUES ('33', '25', '1', 'eee', '/uploads/20260819/090529_1298.png', '[\"\\/uploads\\/20260819\\/090529_1298.png\"]', '2222', '250.00', '100.00', '0.00', '100.00', '0.00', '100.00', '0', '0', '1787101938', '1787188338', '0', '2', '', '41', '9', '1250.00', '29', '10', '1787101531', '1787189719');
INSERT INTO `goods` VALUES ('34', '20', '1', '1111', '/uploads/20260819/105301_8126.png', '[\"\\/uploads\\/20260819\\/105301_8126.png\",\"\\/uploads\\/20260819\\/105304_7478.png\",\"\\/uploads\\/20260819\\/105307_2913.png\"]', '111', '1111.00', '100.00', '0.00', '1.00', '0.00', '1.00', '0', '0', '1787107997', '1787125920', '0', '3', '', '10', '0', '0.00', '0', '0', '1787107997', '1787131996');
INSERT INTO `goods` VALUES ('35', '20', '1', '测试', '/uploads/20260820/075115_7683.png', '[\"\\/uploads\\/20260820\\/075115_7683.png\",\"\\/uploads\\/20260820\\/075123_9840.png\",\"\\/uploads\\/20260820\\/075127_6830.png\",\"\\/uploads\\/20260820\\/075132_6642.png\"]', '这东西非常好', '10000.00', '100.00', '0.00', '2000.00', '0.00', '0.00', '0', '0', '1787183493', '1787442600', '0', '5', '不好看', '0', '0', '0.00', '0', '0', '1787183493', '1787183493');
INSERT INTO `goods` VALUES ('36', '29', '1', '111', '/uploads/20260820/083632_4854.png', '[\"\\/uploads\\/20260820\\/083632_4854.png\",\"\\/uploads\\/20260820\\/083635_7154.png\",\"\\/uploads\\/20260820\\/083638_6756.jpeg\",\"\\/uploads\\/20260820\\/083642_5400.png\"]', '111111111', '1111.00', '100.00', '0.00', '10.00', '0.00', '0.00', '0', '0', '1787186212', '1787272560', '0', '2', '', '16', '9', '2011.00', '28', '12', '1787186212', '1787400406');
INSERT INTO `goods` VALUES ('37', '20', '1', '测试测试', '/uploads/20260820/120110_5618.png', '[\"\\/uploads\\/20260820\\/120110_5618.png\",\"\\/uploads\\/20260820\\/120119_1330.png\",\"\\/uploads\\/20260820\\/120123_8667.jpeg\",\"\\/uploads\\/20260820\\/120127_9605.png\"]', '1111', '100.00', '100.00', '0.00', '100.00', '0.00', '0.00', '0', '0', '1787198495', '1787198580', '0', '2', '', '2', '2', '300.00', '28', '11', '1787198495', '1787198581');
INSERT INTO `goods` VALUES ('38', '20', '1', '111', '/uploads/20260822/201035_9791.png', '[\"\\/uploads\\/20260822\\/201035_9791.png\",\"\\/uploads\\/20260822\\/201041_7132.png\",\"\\/uploads\\/20260822\\/201053_5944.png\",\"\\/uploads\\/20260822\\/201057_4346.png\"]', '111', '111.00', '100.00', '0.00', '111.00', '0.00', '0.00', '0', '0', '1787400659', '1787487000', '0', '1', '', '38', '2', '0.00', '0', '0', '1787400659', '1787452783');
INSERT INTO `goods` VALUES ('39', '31', '1', '111', '/uploads/20260823/105545_6539.png', '[\"\\/uploads\\/20260823\\/105545_6539.png\",\"\\/uploads\\/20260823\\/105551_8271.png\",\"\\/uploads\\/20260823\\/105556_8902.png\",\"\\/uploads\\/20260823\\/105614_9672.png\"]', '222', '111.00', '100.00', '0.00', '1111.00', '0.00', '0.00', '0', '0', '1787453778', '1787712900', '0', '1', '', '1', '0', '0.00', '0', '0', '1787453778', '1787453778');

-- ----------------------------
-- Table structure for `goods_favorite`
-- ----------------------------
DROP TABLE IF EXISTS `goods_favorite`;
CREATE TABLE `goods_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_goods` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COMMENT='商品收藏';

-- ----------------------------
-- Records of goods_favorite
-- ----------------------------
INSERT INTO `goods_favorite` VALUES ('2', '25', '28', '1787036699');
INSERT INTO `goods_favorite` VALUES ('3', '20', '28', '1787039604');
INSERT INTO `goods_favorite` VALUES ('4', '26', '28', '1787040469');
INSERT INTO `goods_favorite` VALUES ('5', '26', '29', '1787040750');
INSERT INTO `goods_favorite` VALUES ('6', '27', '33', '1787101566');
INSERT INTO `goods_favorite` VALUES ('7', '20', '36', '1787198066');
INSERT INTO `goods_favorite` VALUES ('8', '28', '36', '1787198986');
INSERT INTO `goods_favorite` VALUES ('9', '30', '38', '1787452791');
INSERT INTO `goods_favorite` VALUES ('11', '31', '39', '1787453924');

-- ----------------------------
-- Table structure for `message`
-- ----------------------------
DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) NOT NULL COMMENT '发送者',
  `to_uid` int(11) NOT NULL COMMENT '接收者',
  `goods_id` int(11) DEFAULT NULL COMMENT '关联商品',
  `content` text NOT NULL COMMENT '消息内容',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未读 1已读',
  `create_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_from_uid` (`from_uid`),
  KEY `idx_to_uid` (`to_uid`),
  KEY `idx_goods_id` (`goods_id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='聊天消息';

-- ----------------------------
-- Records of message
-- ----------------------------
INSERT INTO `message` VALUES ('6', '25', '20', '28', '111', '1', '1787058247');
INSERT INTO `message` VALUES ('7', '20', '25', '28', '222', '1', '1787058259');
INSERT INTO `message` VALUES ('8', '25', '20', '28', '333', '1', '1787058277');
INSERT INTO `message` VALUES ('9', '20', '25', '28', '3111', '1', '1787058451');
INSERT INTO `message` VALUES ('10', '20', '20', '28', '111', '0', '1787062553');
INSERT INTO `message` VALUES ('11', '27', '25', '33', '还111', '0', '1787101579');
INSERT INTO `message` VALUES ('12', '25', '27', '33', '111', '1', '1787101611');
INSERT INTO `message` VALUES ('13', '30', '20', '38', '111', '0', '1787452780');

-- ----------------------------
-- Table structure for `news`
-- ----------------------------
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '新闻标题',
  `title_tw` varchar(100) NOT NULL DEFAULT '',
  `title_en` varchar(100) NOT NULL DEFAULT '',
  `content` text NOT NULL COMMENT '新闻内容',
  `content_en` text,
  `content_tw` text,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='新闻公告表';

-- ----------------------------
-- Records of news
-- ----------------------------
INSERT INTO `news` VALUES ('1', '平台系统升级公告', '', '', '尊敬的会员：\n平台将于本周六凌晨 2:00-4:00 进行系统升级维护，期间竞拍、出价功能将暂停使用，请合理安排时间，给您带来不便敬请谅解。', null, null, '1', '1787043366', '1787043366');
INSERT INTO `news` VALUES ('2', '新用户注册送积分活动', '', '', '为回馈新老用户，即日起新注册用户完成实名认证后，即可获得 100 积分奖励，积分可在平台参与竞拍抵用，快来参与吧！', null, null, '1', '1787046966', '1787046966');
INSERT INTO `news` VALUES ('3', '拍卖规则调整通知', '測試新聞（繁體）', 'Test News (EN)', '自本月起，竞拍加价幅度将严格按照商品设定的加价幅度整数倍递增，请各位买家出价时注意，避免因出价不符合规则导致出价失败。', 'This is the English content: Welcome to the auction mall.', '這是繁體內容：歡迎來到拍賣商城。', '1', '1787048766', '1787048766');

-- ----------------------------
-- Table structure for `order`
-- ----------------------------
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_title` varchar(100) NOT NULL DEFAULT '' COMMENT '商品标题',
  `goods_cover` varchar(255) NOT NULL DEFAULT '' COMMENT '商品封面',
  `seller_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '卖家ID',
  `buyer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '买家ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '成交价',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '佣金比例%',
  `commission` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金金额',
  `seller_income` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '卖家实收',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '买家保证金',
  `pay_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付状态 0未支付 1已支付',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `order_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待付款 1待发货 2待收货 3已完成 4已取消',
  `ship_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `ship_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '收货电话',
  `ship_address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货地址',
  `ship_company` varchar(50) NOT NULL DEFAULT '' COMMENT '快递公司',
  `ship_no` varchar(50) NOT NULL DEFAULT '' COMMENT '快递单号',
  `ship_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发货时间',
  `finish_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_goods` (`goods_id`),
  KEY `idx_buyer` (`buyer_id`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='拍卖订单表';

-- ----------------------------
-- Records of order
-- ----------------------------
INSERT INTO `order` VALUES ('1', 'AU20260817120000123', '2', '限量版潮玩手办', '', '3', '2', '480.00', '10.00', '48.00', '432.00', '50.00', '1', '1786920000', '3', '张三', '13800000001', '广东省深圳市南山区科技园1号', '', '', '0', '0', '', '1786916000', '0');
INSERT INTO `order` VALUES ('3', 'AU20260818105846817930', '8', '测试拍品01', '/uploads/20260818/084555_8979.png', '21', '23', '170.00', '10.00', '17.00', '153.00', '20.00', '0', '0', '0', '', '', '', '', '', '0', '0', '', '1787021926', '1787021926');
INSERT INTO `order` VALUES ('4', 'AU20260818144118070771', '26', '光绪元宝铜币', '/uploads/20260818/134447_8796.png', '20', '25', '110000.00', '10.00', '11000.00', '99000.00', '10000.00', '0', '0', '0', '', '', '', '', '', '0', '0', '', '1787035278', '1787035278');
INSERT INTO `order` VALUES ('5', 'AU20260818161505120786', '29', '祖传玉佩', '/uploads/20260818/161159_8451.png', '25', '26', '1200.00', '10.00', '120.00', '1080.00', '100.00', '1', '1787041330', '3', '老王', '13666666666', '北京   5-2-201', '顺丰快递', '888888888888', '1787041479', '1787041558', '', '1787040905', '1787044297');
INSERT INTO `order` VALUES ('6', 'AU20260818163929465185', '31', '青铜器', '/uploads/20260818/163213_7126.png', '25', '26', '306.00', '10.00', '30.60', '275.40', '100.00', '2', '1787042552', '3', '老王', '13666666666', '北京   5-2-201', '中通快递', '888888888', '1787042923', '1787042939', '', '1787042369', '1787044044');
INSERT INTO `order` VALUES ('7', 'AU20260819090307067318', '32', '测试', '/uploads/20260819/090043_6554.png', '25', '27', '3100.00', '10.00', '310.00', '2790.00', '100.00', '0', '0', '4', '', '', '', '', '', '0', '0', '', '1787101387', '1787101414');
INSERT INTO `order` VALUES ('8', 'AU20260819091634015010', '27', '测试商品', '/uploads/20260818/142055_1705.png', '25', '27', '1200.00', '10.00', '120.00', '1080.00', '100.00', '1', '1787102272', '3', '老王', '13666666666', '5-2-201', '中通快递', '8888888888888', '1787102319', '1787102345', '', '1787102194', '1787102345');
INSERT INTO `order` VALUES ('9', 'AU20260819225842137955', '28', '哈哈哈哈哈', '/uploads/20260818/150347_7607.png', '20', '26', '80000.00', '10.00', '8000.00', '72000.00', '555.00', '0', '0', '0', '', '', '', '', '', '0', '0', '', '1787151522', '1787151522');
INSERT INTO `order` VALUES ('10', 'AU20260820093519694222', '33', 'eee', '/uploads/20260819/090529_1298.png', '25', '29', '1250.00', '10.00', '125.00', '1125.00', '0.00', '0', '0', '0', '', '', '', '', '', '0', '0', '', '1787189719', '1787189719');
INSERT INTO `order` VALUES ('11', 'AU20260820120301760758', '37', '测试测试', '/uploads/20260820/120110_5618.png', '20', '28', '300.00', '10.00', '30.00', '270.00', '0.00', '1', '1787198949', '1', '11', '13666666666', '1   1', '', '', '0', '0', '', '1787198581', '1787198949');
INSERT INTO `order` VALUES ('12', 'AU20260822200646527985', '36', '111', '/uploads/20260820/083632_4854.png', '29', '28', '2011.00', '10.00', '201.10', '1809.90', '0.00', '0', '0', '0', '', '', '', '', '', '0', '0', '', '1787400406', '1787400406');

-- ----------------------------
-- Table structure for `pay_account`
-- ----------------------------
DROP TABLE IF EXISTS `pay_account`;
CREATE TABLE `pay_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1支付宝 2微信 3银行卡',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `account` varchar(100) NOT NULL DEFAULT '' COMMENT '账号',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称(银行卡)',
  `qr_code` varchar(255) NOT NULL DEFAULT '' COMMENT '收款码图片(支付宝/微信)',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_type` (`user_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='用户提现账户绑定';

-- ----------------------------
-- Records of pay_account
-- ----------------------------
INSERT INTO `pay_account` VALUES ('3', '20', '3', '老师', '88888888888888888', '中国银行', '', '1787061665', '1787061665');
INSERT INTO `pay_account` VALUES ('5', '20', '1', '老头', '18888888888', '', '/uploads/20260818/221911_2808.png', '1787062756', '1787062756');
INSERT INTO `pay_account` VALUES ('6', '20', '4', '', '111111111111', 'trc20', '', '1787198176', '1787198176');

-- ----------------------------
-- Table structure for `recharge`
-- ----------------------------
DROP TABLE IF EXISTS `recharge`;
CREATE TABLE `recharge` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `pay_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1在线支付 2人工转账',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核 1已到账 2已拒绝',
  `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='充值申请表';

-- ----------------------------
-- Records of recharge
-- ----------------------------
INSERT INTO `recharge` VALUES ('1', '20', '300.00', '1', '1', '', '1787132168', '1787132135', '1787132135');
INSERT INTO `recharge` VALUES ('2', '30', '100000.00', '1', '1', '', '1787452709', '1787452689', '1787452689');

-- ----------------------------
-- Table structure for `seller_follow`
-- ----------------------------
DROP TABLE IF EXISTS `seller_follow`;
CREATE TABLE `seller_follow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `seller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_seller` (`user_id`,`seller_id`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='店铺关注';

-- ----------------------------
-- Records of seller_follow
-- ----------------------------
INSERT INTO `seller_follow` VALUES ('3', '26', '20', '1787040467');
INSERT INTO `seller_follow` VALUES ('4', '26', '25', '1787040749');
INSERT INTO `seller_follow` VALUES ('6', '25', '20', '1787050046');
INSERT INTO `seller_follow` VALUES ('7', '27', '25', '1787101620');
INSERT INTO `seller_follow` VALUES ('8', '20', '29', '1787198085');
INSERT INTO `seller_follow` VALUES ('9', '28', '29', '1787198973');
INSERT INTO `seller_follow` VALUES ('12', '30', '20', '1787452999');

-- ----------------------------
-- Table structure for `setting`
-- ----------------------------
DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- ----------------------------
-- Records of setting
-- ----------------------------
INSERT INTO `setting` VALUES ('1', 'site_name', '竞拍商城V2', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('2', 'site_logo', '/uploads/20260818/135339_9199.png', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('3', 'site_url', '', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('4', 'commission_rate', '10', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('5', 'seller_check', '1', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('6', 'invite_code', '', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('7', 'withdraw_fee', '1', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('8', 'service_phone', '18888888888', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('9', 'service_qq', '', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('10', 'auction_delay', '0', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('11', 'user_protocol', '用户协议', '1786973051', '1787183600');
INSERT INTO `setting` VALUES ('12', 'goods_check', '1', '1787022342', '1787183600');
INSERT INTO `setting` VALUES ('13', 'service_link', '', '1787104733', '1787183600');
INSERT INTO `setting` VALUES ('14', 'privacy_policy', '隐私政策', '1787105070', '1787183600');
INSERT INTO `setting` VALUES ('15', 'publish_protocol', '每笔成交将扣除10%佣金', '1787109114', '1787183600');
INSERT INTO `setting` VALUES ('16', 'withdraw_min', '1000', '1787183350', '1787183600');
INSERT INTO `setting` VALUES ('17', 'withdraw_max', '100000', '1787183350', '1787183600');
INSERT INTO `setting` VALUES ('18', 'user_protocol_tw', '使用者協議（繁體測試）', '1787453698', '0');
INSERT INTO `setting` VALUES ('19', 'user_protocol_en', 'User Agreement (EN test)', '1787453698', '0');
INSERT INTO `setting` VALUES ('20', 'privacy_policy_tw', '隱私政策（繁體測試）', '1787453698', '0');
INSERT INTO `setting` VALUES ('21', 'privacy_policy_en', 'Privacy Policy (EN test)', '1787453698', '0');
INSERT INTO `setting` VALUES ('22', 'publish_protocol_tw', '發佈協議（繁體測試）', '1787453698', '0');
INSERT INTO `setting` VALUES ('23', 'publish_protocol_en', 'Publishing Agreement (EN test)', '1787453698', '0');

-- ----------------------------
-- Table structure for `sys_message`
-- ----------------------------
DROP TABLE IF EXISTS `sys_message`;
CREATE TABLE `sys_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收用户ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送管理员ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `is_read` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0未读 1已读',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='站内信';

-- ----------------------------
-- Records of sys_message
-- ----------------------------
INSERT INTO `sys_message` VALUES ('2', '20', '0', '111', '2222', '1', '1787152677');
INSERT INTO `sys_message` VALUES ('3', '26', '0', '竞拍出局通知', '您出价竞拍的「eee」已出局：您的出价 ¥350.00 于 2026-08-19 23:46:12 被 ¥450.00 超过，当前最高价 ¥450.00。如需继续竞拍，请再次出价。', '1', '1787154372');
INSERT INTO `sys_message` VALUES ('4', '27', '0', '竞拍出局通知', '您出价竞拍的「eee」已出局：您的出价 ¥450.00 于 2026-08-20 08:01:40 被 ¥650.00 超过，当前最高价 ¥650.00。如需继续竞拍，请再次出价。', '0', '1787184100');
INSERT INTO `sys_message` VALUES ('5', '25', '0', '竞拍出局通知', '您出价竞拍的「eee」已出局：您的出价 ¥650.00 于 2026-08-20 08:34:06 被 ¥750.00 超过，当前最高价 ¥750.00。如需继续竞拍，请再次出价。', '0', '1787186046');
INSERT INTO `sys_message` VALUES ('6', '28', '0', '竞拍出局通知', '您出价竞拍的「eee」已出局：您的出价 ¥750.00 于 2026-08-20 08:53:59 被 ¥850.00 超过，当前最高价 ¥850.00。如需继续竞拍，请再次出价。', '0', '1787187239');
INSERT INTO `sys_message` VALUES ('7', '29', '0', '竞拍出局通知', '您出价竞拍的「测试测试」已出局：您的出价 ¥200.00 于 2026-08-20 12:02:34 被 ¥300.00 超过，当前最高价 ¥300.00。如需继续竞拍，请再次出价。', '0', '1787198554');
INSERT INTO `sys_message` VALUES ('8', '20', '0', '竞拍出局通知', '您出价竞拍的「111」已出局：您的出价 ¥1,811.00 于 2026-08-20 12:05:13 被 ¥1,911.00 超过，当前最高价 ¥1,911.00。如需继续竞拍，请再次出价。', '1', '1787198713');
INSERT INTO `sys_message` VALUES ('9', '28', '0', '竞拍出局通知', '您出价竞拍的「111」已出局：您的出价 ¥211.00 于 2026-08-23 10:39:43 被 ¥311.00 超过，当前最高价 ¥311.00。如需继续竞拍，请再次出价。', '0', '1787452783');

-- ----------------------------
-- Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `id_card` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号',
  `id_card_front` varchar(255) NOT NULL DEFAULT '' COMMENT '身份证正面照',
  `id_card_back` varchar(255) NOT NULL DEFAULT '' COMMENT '身份证反面照',
  `auth_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '实名认证 0未认证 1审核中 2已通过 3已拒绝',
  `auth_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '认证拒绝原因',
  `auth_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '认证通过时间',
  `shop_name` varchar(50) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `seller_intro` varchar(500) NOT NULL DEFAULT '' COMMENT '店铺介绍',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `invite_code` varchar(20) NOT NULL DEFAULT '' COMMENT '我的邀请码',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `is_seller` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否卖家 1是 0否',
  `is_virtual` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0普通会员 1虚拟会员',
  `seller_check` tinyint(4) NOT NULL DEFAULT '0' COMMENT '卖家审核 0待审核 1通过 2拒绝',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '可用余额',
  `freeze_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冻结余额(保证金)',
  `points` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '个人佣金比例%(默认-1用系统)',
  `total_buy` int(11) NOT NULL DEFAULT '0' COMMENT '累计成交购买',
  `total_sell` int(11) NOT NULL DEFAULT '0' COMMENT '累计成交卖出',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `reg_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '注册IP',
  `reg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `company_name` varchar(100) NOT NULL DEFAULT '' COMMENT '企业名称',
  `license_img` text CHARACTER SET utf8,
  `shop_score` decimal(3,2) NOT NULL DEFAULT '5.00' COMMENT '店铺评分',
  `fans_count` int(11) NOT NULL DEFAULT '0' COMMENT '粉丝数量',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '消保金',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mobile` (`mobile`),
  UNIQUE KEY `uk_invite` (`invite_code`),
  KEY `idx_pid` (`pid`),
  KEY `idx_seller` (`is_seller`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COMMENT='会员表';

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', '13800000001', '14e1b600b1fd579f47433b88e8d85291', '张三', '', '', '', '', '0', '', '0', '', '', '', 'A10001', '0', '0', '0', '0', '350.00', '0.00', '0', '0.00', '1', '0', '1', '', '1786880000', '0', '1786880000', '0', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('2', '13800000002', '14e1b600b1fd579f47433b88e8d85291', '李四', '', '', '', '', '0', '', '0', '', '', '', 'A10002', '1', '0', '0', '1', '1000.00', '100.00', '0', '0.00', '0', '1', '1', '', '1786880100', '0', '1786880100', '0', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('3', '13800000003', '14e1b600b1fd579f47433b88e8d85291', '王五', '', '', '', '', '0', '', '0', '', '', '', 'A10003', '1', '1', '0', '1', '2000.00', '0.00', '0', '0.00', '0', '2', '1', '', '1786880200', '0', '1786880200', '0', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('4', '13800000004', '14e1b600b1fd579f47433b88e8d85291', '赵六', '', '', '', '', '0', '', '0', '', '', '', 'A10004', '2', '0', '0', '0', '0.00', '0.00', '0', '0.00', '0', '0', '0', '', '1786880300', '0', '1786880300', '0', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('20', '18888888888', '63ee451939ed580ef3c4b6f0109d1fd0', 'www', '', '', '', '', '0', '', '0', '', '', '', '436B1D9E', '0', '1', '0', '1', '4570.00', '0.00', '0', '0.00', '0', '1', '1', '127.0.0.1', '1786977719', '1787453710', '1786977719', '1787453710', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('21', '19900000001', '14e1b600b1fd579f47433b88e8d85291', 'tester', '张三', '11010119900307789X', '/uploads/20260818/084555_8979.png', '/uploads/20260818/084555_8979.png', '2', '', '1787019937', '', '', '', '8AE6FEC7', '0', '0', '0', '0', '20.00', '0.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787010652', '1787063006', '1787010652', '1787063006', '', '', '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('22', '16666666666', '63ee451939ed580ef3c4b6f0109d1fd0', '用户6666', 'jjjj', '', '', '', '0', '', '0', 'opoll', 'bbbn', '', '1679BABD', '0', '1', '0', '1', '9920.00', '80.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787011794', '0', '1787011794', '1787021926', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('23', '13333333333', '14e1b600b1fd579f47433b88e8d85291', '用户3333', '111', '450521199406250830', '/uploads/20260818/103926_1119.png', '/uploads/20260818/103929_1964.png', '2', '', '1787021759', '', '', '', 'E3191CED', '0', '1', '0', '1', '20.00', '0.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787019028', '1787041545', '1787019028', '1787041545', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('24', '14444444444', '63ee451939ed580ef3c4b6f0109d1fd0', '用户4444', '你哈嘛', '459087784678765789', '/uploads/20260818/121601_9000.png', '/uploads/20260818/121604_5112.png', '2', '', '1787106297', '', '', '', '625AE3A4', '0', '0', '0', '0', '0.00', '0.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787025407', '0', '1787025407', '1787026565', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('25', '15555555555', '63ee451939ed580ef3c4b6f0109d1fd0', '尼玛', '老头', '456876767676767667', '/uploads/20260818/132748_2357.png', '/uploads/20260818/132751_5496.png', '2', '', '1787030886', '优雅', '', '/uploads/20260818/161826_6464.png', 'B5470F18', '0', '1', '0', '1', '992435.40', '10000.00', '0', '0.00', '0', '3', '1', '127.0.0.1', '1787030789', '1787152721', '1787030789', '1787152721', '嗡嗡嗡', null, '9.99', '0', '40000.00');
INSERT INTO `user` VALUES ('26', '19999999999', '14e1b600b1fd579f47433b88e8d85291', '小老鼠', '', '', '', '', '0', '', '0', '', '', '/uploads/20260818/164734_5620.png', '5FEB951A', '0', '0', '0', '0', '649.00', '0.00', '0', '0.00', '2', '0', '1', '127.0.0.1', '1787040232', '1787152778', '1787040232', '1787189719', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('27', '12222222222', '14e1b600b1fd579f47433b88e8d85291', '用户2222', '你好', '111111111111111111', '/uploads/20260819/093136_5005.png', '/uploads/20260819/093139_1963.png', '2', '', '1787103121', '', '', '', '75066D78', '25', '0', '0', '0', '99000.00', '0.00', '0', '0.00', '1', '0', '1', '127.0.0.1', '1787101140', '1787154366', '1787101140', '1787189719', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('28', '13558888888', '63ee451939ed580ef3c4b6f0109d1fd0', '1111', '', '', '', '', '0', '', '0', '', '', '', 'D0D7ED2D', '0', '0', '1', '0', '0.00', '0.00', '0', '0.00', '1', '0', '1', '127.0.0.1', '1787185961', '1787198578', '1787185961', '1787198578', '', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('29', '18888888867', '63ee451939ed580ef3c4b6f0109d1fd0', 'rrrr', '', '', '', '', '0', '', '0', '111', '', '', 'C00E0678', '0', '1', '1', '1', '0.00', '0.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787186106', '1787186169', '1787186106', '1787187086', '2222', null, '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('30', '12222222224', '63ee451939ed580ef3c4b6f0109d1fd0', '用户2224', '神经病1', '450789999876787678', '/uploads/20260823/104344_6100.png', '/uploads/20260823/104347_7258.png', '2', '', '1787453044', '测试店', '1111', '', '8EE8217D', '0', '0', '0', '0', '99889.00', '111.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787452679', '0', '1787452679', '1787453111', '我也是醉', '/uploads/20260823/104509_6938.png', '5.00', '0', '0.00');
INSERT INTO `user` VALUES ('31', '13333333332', '63ee451939ed580ef3c4b6f0109d1fd0', '用户3332', '总裁', '111111111111111111', '/uploads/20260823/105228_2747.png', '/uploads/20260823/105231_2630.png', '2', '', '1787453615', '11111', '2222', '', 'CBB11A06', '0', '1', '0', '1', '0.00', '0.00', '0', '0.00', '0', '0', '1', '127.0.0.1', '1787453512', '0', '1787453512', '1787453713', '1111', '/uploads/20260823/105511_2083.png', '5.00', '0', '0.00');

-- ----------------------------
-- Table structure for `user_address`
-- ----------------------------
DROP TABLE IF EXISTS `user_address`;
CREATE TABLE `user_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '市',
  `district` varchar(50) NOT NULL DEFAULT '' COMMENT '区县',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '默认地址 1是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='收货地址表';

-- ----------------------------
-- Records of user_address
-- ----------------------------
INSERT INTO `user_address` VALUES ('5', '21', '张三', '13800138000', '广东省', '广州市', '天河区', '测试路1号A座', '1', '1787015670', '1787015711');
INSERT INTO `user_address` VALUES ('6', '22', 'www', '18888888888', '广西', '', '', '11111111111', '1', '1787015797', '1787015797');
INSERT INTO `user_address` VALUES ('7', '23', '老黑', '18888888888', '浙江省', '', '', '12-8-01', '1', '1787022929', '1787022929');
INSERT INTO `user_address` VALUES ('8', '26', '老王', '13666666666', '北京', '', '', '5-2-201', '1', '1787040973', '1787040973');
INSERT INTO `user_address` VALUES ('9', '27', '老王', '13666666666', '', '', '', '5-2-201', '1', '1787102264', '1787102264');
INSERT INTO `user_address` VALUES ('10', '28', '11', '13666666666', '1', '', '', '1', '1', '1787198627', '1787198627');
INSERT INTO `user_address` VALUES ('11', '1', '测试', '18888888888', '广东省', '广州市', '天河区', '测试路1号', '1', '1787450418', '1787450418');

-- ----------------------------
-- Table structure for `withdraw`
-- ----------------------------
DROP TABLE IF EXISTS `withdraw`;
CREATE TABLE `withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `account_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1支付宝 2微信 3银行卡',
  `account` varchar(100) NOT NULL DEFAULT '' COMMENT '收款账号',
  `account_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收款人',
  `bank_name` varchar(50) NOT NULL DEFAULT '' COMMENT '银行名称',
  `qr_code` varchar(255) NOT NULL DEFAULT '' COMMENT '收款码图片',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核 1已打款 2已拒绝',
  `refuse_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COMMENT='提现申请表';

-- ----------------------------
-- Records of withdraw
-- ----------------------------
INSERT INTO `withdraw` VALUES ('1', '1', '200.00', '1.00', '1', 'zhangsan@alipay.com', '张三', '', '', '1', '', '1786974582', '1786930000', '0');
INSERT INTO `withdraw` VALUES ('2', '3', '500.00', '2.50', '3', '6222020200112233445', '王五', '', '', '1', '', '0', '1786931000', '0');
INSERT INTO `withdraw` VALUES ('9', '20', '5000.00', '50.00', '3', '88888888888888888', '老师', '中国银行', '', '1', '', '1787061704', '1787061679', '1787061679');
INSERT INTO `withdraw` VALUES ('12', '20', '5000.00', '50.00', '3', '88888888888888888', '老师', '中国银行', '', '2', '8888', '1787062280', '1787062257', '1787062257');
INSERT INTO `withdraw` VALUES ('14', '20', '1000.00', '10.00', '1', '18888888888', '老头', '', '/uploads/20260818/221911_2808.png', '2', '1222', '1787198246', '1787062774', '1787062774');
INSERT INTO `withdraw` VALUES ('15', '20', '1000.00', '10.00', '4', '111111111111', '', 'trc20', '', '0', '', '0', '1787198271', '1787198271');
