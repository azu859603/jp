-- MySQL dump 10.13  Distrib 5.7.43, for Linux (x86_64)
--
-- Host: localhost    Database: auction
-- ------------------------------------------------------
-- Server version	5.7.43-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_log`
--

DROP TABLE IF EXISTS `admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `action` varchar(255) NOT NULL DEFAULT '' COMMENT '操作内容',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_log`
--

LOCK TABLES `admin_log` WRITE;
/*!40000 ALTER TABLE `admin_log` DISABLE KEYS */;
INSERT INTO `admin_log` VALUES (1,1,'登录后台','127.0.0.1',1786974959),(2,1,'登录后台','127.0.0.1',1786975052),(3,1,'登录后台','127.0.0.1',1787005824),(4,1,'登录后台','127.0.0.1',1787016296),(5,1,'登录后台','127.0.0.1',1787016537),(6,1,'登录后台','127.0.0.1',1787017142),(7,1,'登录后台','127.0.0.1',1787019824),(8,1,'登录后台','127.0.0.1',1787021171),(9,1,'登录后台','127.0.0.1',1787021892),(10,1,'登录后台','127.0.0.1',1787022342),(11,1,'登录后台','127.0.0.1',1787025750),(12,1,'登录后台','127.0.0.1',1787030765),(13,1,'登录后台','127.0.0.1',1787032411),(14,1,'登录后台','127.0.0.1',1787033334),(15,1,'登录后台','127.0.0.1',1787033782),(16,1,'登录后台','127.0.0.1',1787034197),(17,1,'登录后台','127.0.0.1',1787039548),(18,1,'登录后台','127.0.0.1',1787043938),(19,1,'登录后台','127.0.0.1',1787044297),(20,1,'登录后台','127.0.0.1',1787050647),(21,1,'登录后台','127.0.0.1',1787051172),(22,1,'登录后台','127.0.0.1',1787058186),(23,1,'登录后台','127.0.0.1',1787100829),(24,1,'登录后台','127.0.0.1',1787106752),(25,1,'登录后台','127.0.0.1',1787106790),(26,1,'登录后台','127.0.0.1',1787109072),(27,1,'登录后台','127.0.0.1',1787132155),(28,1,'登录后台','127.0.0.1',1787152660),(29,1,'登录后台','127.0.0.1',1787183268),(30,1,'登录后台','127.0.0.1',1787185800),(31,1,'登录后台','127.0.0.1',1787198234),(32,1,'登录后台','103.151.172.28',1787199799),(33,1,'登录后台','171.220.186.158',1787202654),(34,1,'登录后台','182.239.114.28',1787202661),(35,1,'登录后台','182.239.114.28',1787215283),(36,1,'登录后台','23.248.176.114',1787218125);
/*!40000 ALTER TABLE `admin_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_user`
--

DROP TABLE IF EXISTS `admin_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_user`
--

LOCK TABLES `admin_user` WRITE;
/*!40000 ALTER TABLE `admin_user` DISABLE KEYS */;
INSERT INTO `admin_user` VALUES (1,'admin','14e1b600b1fd579f47433b88e8d85291','超级管理员','',1,1,'23.248.176.114',1787218125,1786973051,0);
/*!40000 ALTER TABLE `admin_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `after_sale`
--

DROP TABLE IF EXISTS `after_sale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `after_sale`
--

LOCK TABLES `after_sale` WRITE;
/*!40000 ALTER TABLE `after_sale` DISABLE KEYS */;
INSERT INTO `after_sale` VALUES (2,6,'AU20260818163929465185',26,25,31,'青铜器',306.00,'6666666666',1,'1111',1787043970,1787044044);
/*!40000 ALTER TABLE `after_sale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `balance_log`
--

DROP TABLE IF EXISTS `balance_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COMMENT='余额流水表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `balance_log`
--

LOCK TABLES `balance_log` WRITE;
/*!40000 ALTER TABLE `balance_log` DISABLE KEYS */;
INSERT INTO `balance_log` VALUES (1,1,'recharge',500.00,500.00,'余额充值',1786880000),(2,2,'recharge',1000.00,1000.00,'余额充值',1786880100),(3,2,'deposit',-100.00,900.00,'拍卖保证金',1786913000),(4,3,'income',432.00,2432.00,'商品成交收入',1786920000),(5,1,'withdraw',-200.00,300.00,'提现申请',1786930000),(6,1,'withdraw',-200.00,300.00,'提现打款：200.00元',1786974582),(7,1,'recharge',50.00,350.00,'测试调整',1786974582),(8,7,'recharge',100.00,100.00,'余额充值（模拟支付）',1786976673),(17,22,'recharge',10000.00,10000.00,'余额充值（模拟支付）',1787013421),(18,22,'deposit',-100.00,9900.00,'拍卖保证金（和田玉手串）',1787013433),(19,23,'refund',20.00,20.00,'未拍中，保证金退回（测试拍品01）',1787021926),(20,22,'refund',20.00,9920.00,'未拍中，保证金退回（测试拍品01）',1787021926),(21,21,'refund',20.00,20.00,'未拍中，保证金退回（测试拍品01）',1787021926),(22,25,'recharge',1000000.00,1000000.00,'后台调整',1787032205),(23,25,'deposit',-10000.00,990000.00,'拍卖保证金（光绪元宝铜币）',1787032225),(24,26,'recharge',555.00,555.00,'后台调整',1787040579),(25,26,'deposit',-555.00,0.00,'拍卖保证金（哈哈哈哈哈）',1787040606),(26,26,'recharge',100.00,100.00,'后台调整',1787040782),(27,26,'deposit',-100.00,0.00,'拍卖保证金（祖传玉佩）',1787040791),(28,26,'refund',100.00,100.00,'未拍中，保证金退回（祖传玉佩）',1787040905),(29,26,'recharge',1100.00,1200.00,'后台调整',1787041324),(30,26,'pay',-1100.00,100.00,'拍卖订单支付：AU20260818161505120786',1787041330),(31,25,'income',1080.00,991080.00,'拍卖成交收入：AU20260818161505120786（平台佣金 ¥120.00）',1787041330),(32,26,'deposit',-100.00,0.00,'拍卖保证金（青铜器）',1787041998),(33,26,'recharge',300.00,300.00,'后台调整',1787042548),(34,26,'pay',-206.00,94.00,'拍卖订单支付：AU20260818163929465185',1787042552),(35,25,'income',275.40,991355.40,'拍卖成交收入：AU20260818163929465185（平台佣金 ¥30.60）',1787042552),(38,26,'refund',306.00,1600.00,'售后退款：AU20260818163929465185',1787044044),(39,25,'refund',-275.40,990000.00,'售后扣回成交收入：AU20260818163929465185',1787044044),(40,20,'recharge',10000.00,10000.00,'余额充值（模拟支付）',1787059572),(41,20,'withdraw',-5000.00,5000.00,'提现打款：5000.00元',1787061704),(45,20,'withdraw',-5000.00,0.00,'提现申请冻结：5000元',1787062257),(46,20,'refund',5000.00,5000.00,'提现拒绝退回：5000.00元',1787062280),(47,20,'withdraw',-1000.00,4000.00,'提现申请冻结：1000元',1787062774),(48,27,'recharge',100000.00,100000.00,'后台调整',1787101281),(49,27,'deposit',-100.00,99900.00,'拍卖保证金（测试）',1787101292),(50,27,'deposit',-100.00,99800.00,'拍卖保证金（测试商品）',1787102102),(51,27,'refund',100.00,99900.00,'未拍中，保证金退回（测试商品）',1787102194),(52,27,'pay',-1100.00,98800.00,'拍卖订单支付：AU20260819091634015010',1787102272),(53,25,'income',1080.00,992435.40,'拍卖成交收入：AU20260819091634015010（平台佣金 ¥120.00）',1787102272),(54,27,'recharge',200.00,99000.00,'后台调整',1787106288),(55,20,'recharge',300.00,4300.00,'充值到账：300.00元',1787132168),(56,26,'refund',555.00,649.00,'未拍中，保证金退回（哈哈哈哈哈）',1787151522),(57,26,'deposit',-100.00,549.00,'拍卖保证金（eee）',1787154332),(58,27,'deposit',-100.00,98900.00,'拍卖保证金（eee）',1787154372),(59,27,'refund',100.00,99000.00,'未拍中，保证金退回（eee）',1787189719),(60,26,'refund',100.00,649.00,'未拍中，保证金退回（eee）',1787189719),(61,20,'deposit',-10.00,4290.00,'拍卖保证金（111）',1787198095),(62,20,'refund',1000.00,5290.00,'提现拒绝退回：1000.00元',1787198246),(63,20,'withdraw',-1000.00,4290.00,'提现申请冻结：1000元',1787198271),(64,20,'income',270.00,4560.00,'拍卖成交收入：AU20260820120301760758（平台佣金 ¥30.00）',1787198949),(65,30,'recharge',1000.00,1000.00,'后台调整',1787218418),(66,30,'deposit',-10.00,990.00,'拍卖保证金（111）',1787218422),(67,31,'recharge',1200.00,1200.00,'后台调整',1787218587),(68,31,'deposit',-10.00,1190.00,'拍卖保证金（111）',1787218598),(69,31,'withdraw',-1190.00,0.00,'提现申请冻结：1190元',1787218968),(70,31,'refund',1190.00,1190.00,'提现拒绝退回：1190.00元',1787218999);
/*!40000 ALTER TABLE `balance_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banner`
--

DROP TABLE IF EXISTS `banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banner`
--

LOCK TABLES `banner` WRITE;
/*!40000 ALTER TABLE `banner` DISABLE KEYS */;
INSERT INTO `banner` VALUES (1,'新品首发专场','/uploads/20260818/134043_5943.png','/?sort=new',1,1,1787016992,1787031644),(2,'限时拍卖','/uploads/20260818/134055_5122.jpeg','/?sort=end',2,1,1787016992,1787031656);
/*!40000 ALTER TABLE `banner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bid_record`
--

DROP TABLE IF EXISTS `bid_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COMMENT='出价记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bid_record`
--

LOCK TABLES `bid_record` WRITE;
/*!40000 ALTER TABLE `bid_record` DISABLE KEYS */;
INSERT INTO `bid_record` VALUES (1,1,2,3100.00,0.00,0,0,1786910000),(2,1,4,3200.00,0.00,0,0,1786911000),(3,1,2,3300.00,0.00,0,0,1786912000),(4,2,2,300.00,50.00,1,1,1786913000),(5,2,4,320.00,0.00,1,0,1786914000),(6,2,2,480.00,0.00,1,1,1786915000),(8,3,22,550.00,100.00,0,0,1787013433),(9,3,22,600.00,0.00,0,0,1787013600),(10,3,22,650.00,0.00,0,0,1787013609),(11,8,21,100.00,20.00,2,0,1787011524),(12,8,22,130.00,20.00,2,0,1787013324),(13,8,23,160.00,20.00,2,0,1787014524),(14,8,23,170.00,0.00,1,1,1787019105),(15,26,25,110000.00,10000.00,1,1,1787032225),(16,28,26,10100.00,555.00,2,0,1787040606),(17,29,26,900.00,100.00,2,0,1787040791),(18,29,26,1000.00,0.00,2,0,1787040817),(19,29,26,1100.00,0.00,2,0,1787040860),(20,29,26,1200.00,0.00,1,1,1787040863),(21,31,26,306.00,100.00,1,1,1787041998),(22,28,26,80000.00,0.00,1,1,1787042669),(23,32,27,3100.00,100.00,1,1,1787101292),(24,27,27,1100.00,100.00,2,0,1787102102),(25,27,27,1200.00,0.00,1,1,1787102106),(26,33,26,350.00,100.00,2,0,1787154332),(27,33,27,450.00,100.00,2,0,1787154372),(28,33,25,650.00,0.00,2,0,1787184100),(29,33,28,750.00,0.00,2,0,1787186046),(30,33,29,850.00,0.00,2,0,1787187239),(31,33,29,950.00,0.00,2,0,1787187242),(32,33,29,1050.00,0.00,2,0,1787187244),(33,33,29,1150.00,0.00,2,0,1787187393),(34,33,29,1250.00,0.00,1,1,1787187396),(35,36,20,1211.00,10.00,0,0,1787198095),(36,36,20,1311.00,0.00,0,0,1787198097),(37,36,20,1411.00,0.00,0,0,1787198100),(38,36,20,1511.00,0.00,0,0,1787198102),(39,36,20,1611.00,0.00,0,0,1787198104),(40,36,20,1711.00,0.00,0,0,1787198109),(41,36,20,1811.00,0.00,0,0,1787198112),(42,37,29,200.00,0.00,2,0,1787198527),(43,37,28,300.00,0.00,1,1,1787198554),(44,36,28,1911.00,0.00,0,0,1787198713),(45,36,28,2011.00,0.00,0,0,1787198723),(46,36,30,2111.00,10.00,0,0,1787218422),(47,36,31,2211.00,10.00,0,0,1787218598),(48,36,26,2311.00,0.00,0,0,1787219365),(49,36,24,2411.00,0.00,0,0,1787219389);
/*!40000 ALTER TABLE `bid_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `browse_history`
--

DROP TABLE IF EXISTS `browse_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `browse_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COMMENT='浏览足迹';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `browse_history`
--

LOCK TABLES `browse_history` WRITE;
/*!40000 ALTER TABLE `browse_history` DISABLE KEYS */;
INSERT INTO `browse_history` VALUES (23,26,29,1787040905),(25,26,30,1787041207),(29,26,31,1787042369),(34,26,28,1787045577),(48,27,32,1787101391),(51,27,28,1787102026),(55,27,27,1787102194),(66,25,28,1787108159),(72,20,34,1787108237),(74,25,34,1787108605),(75,20,28,1787132964),(77,25,33,1787152731),(78,25,30,1787152737),(82,27,33,1787154373),(84,26,33,1787154677),(90,20,33,1787184104),(92,28,33,1787186047),(109,29,33,1787187727),(118,20,36,1787198113),(122,20,37,1787198599),(127,28,36,1787198984),(134,30,36,1787218480),(136,31,27,1787218551),(139,31,36,1787219852);
/*!40000 ALTER TABLE `browse_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1显示 0隐藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'字画','','/uploads/20260818/133819_6127.png',1,1,1786973051,1787031500),(2,'杂项','','/uploads/20260818/133852_1230.png',2,1,1786973051,1787031533),(3,'玉器','','/uploads/20260818/133934_5485.png',3,1,1786973051,1787031576),(4,'瓷器','','/uploads/20260818/133958_2340.png',4,1,1786973051,1787031603);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods`
--

DROP TABLE IF EXISTS `goods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COMMENT='拍卖商品表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods`
--

LOCK TABLES `goods` WRITE;
/*!40000 ALTER TABLE `goods` DISABLE KEYS */;
INSERT INTO `goods` VALUES (2,3,2,'限量版潮玩手办','',NULL,'全新未拆封。',200.00,20.00,0.00,50.00,10.00,0.00,0,0,1786800000,1787000000,0,2,'',40,8,480.00,2,0,1786900100,0),(8,21,1,'测试拍品01','/uploads/20260818/084555_8979.png','[\"/uploads/20260818/084555_8979.png\",\"/uploads/20260818/084559_3651.png\",\"/uploads/20260818/085232_7139.png\",\"/uploads/20260818/085705_6677.png\"]','测试内容',100.00,10.00,0.00,20.00,0.00,500.00,0,0,1787014246,1787021400,0,2,'',14,4,170.00,23,3,1787014246,1787021926),(26,20,2,'光绪元宝铜币','/uploads/20260818/134447_8796.png','[\"\\/uploads\\/20260818\\/134447_8796.png\",\"\\/uploads\\/20260818\\/134456_2189.png\"]','从历史背景来看，光绪元宝是清朝光绪年间流通的货币之一，由湖北两广总督张之洞率先引进英国铸币机器铸造，之后各省纷纷仿效。这枚铜币见证了晚清时期货币制度的变革，是中国近代货币史上的重要组成部分。\n在当时，这种铜币的铸造和流通，一定程度上适应了商品经济发展的需求，促进了经济的交流与贸易的往来。它的出现，也反映了西方工业文明对中国传统货币铸造业的影响。\n如今，这枚光绪元宝铜币已成为收藏爱好者眼中的珍品。它不仅具有较高的历史价值，能让人们直观地感受到晚清时期的社会经济状况，还因其独特的艺术风格和精湛的铸造工艺，具有了一定的艺术欣赏价值。每一枚光绪元宝铜币都承载着一段历史，是研究晚清历史和文化的重要实物资料。',100000.00,10000.00,0.00,10000.00,0.00,20000.00,0,0,1787031898,1787035260,0,2,'',8,1,110000.00,25,4,1787031898,1787035278),(27,25,1,'测试商品','/uploads/20260818/142055_1705.png','[\"\\/uploads\\/20260818\\/142055_1705.png\"]','22222',1000.00,100.00,0.00,100.00,0.00,1.00,0,0,1787102091,1787102160,0,2,'',4,2,1200.00,27,8,1787034056,1787102194),(28,20,2,'哈哈哈哈哈','/uploads/20260818/150347_7607.png','[\"\\/uploads\\/20260818\\/150347_7607.png\"]','88666664',10000.00,100.00,0.00,555.00,0.00,666.00,0,0,1787036659,1787137380,0,2,'',42,2,80000.00,26,9,1787036659,1787151522),(29,25,3,'祖传玉佩','/uploads/20260818/161159_8451.png','[\"\\/uploads\\/20260818\\/161159_8451.png\"]','111',800.00,100.00,0.00,100.00,0.00,1000.00,1,1,1787040736,1787040900,0,2,'',9,4,1200.00,26,5,1787040736,1787040905),(30,25,1,'5分钟拍品','/uploads/20260818/161958_2924.png','[\"\\/uploads\\/20260818\\/161958_2924.png\"]','不知道',100.00,100.00,0.00,100.00,0.00,100.00,1,1,1787102066,1787188560,0,3,'',2,0,0.00,0,0,1787041200,1787189719),(31,25,4,'青铜器','/uploads/20260818/163213_7126.png','[\"\\/uploads\\/20260818\\/163213_7126.png\"]','本品为当代景德镇名家特制的限量版炉钧窑变釉“冠军尊”（花觚）。器形完美承袭商周青铜觚之经典尊贵典雅，大撇口、挺拔丰满，展现出包容万象的王者气度。该尊最核心的艺术成就不仅在于其端庄的政治设计，更在于其炉火纯青的窑变釉色工艺。\n尊外施浓郁尊贵的玫瑰紫、茄皮紫底釉，内壁在高温烧结中自然析出漫天繁星般的蓝色与白色流淌结晶点，形成了陶瓷界赞誉的“雪花蓝”或“星空斑”视觉效果。这种“入窑一色，出窑万彩”的炉钧窑变工艺极难控制，需精准把握窑炉内的还原气氛，每件作品的纹理皆为世间孤品。\n作为带有大师亲笔手签及“GJ 0116”独立限量的编号作品，本品属于典型的当代艺术瓷、现代大师瓷。它告别了古代瓷器的民间作坊粗制，代表了当代景德镇造币级别的极致工艺与国礼审美标准。因其极低的发行量与高规格的纪念背景，展现出不容小觑的长期艺术资产配置价值。',200.00,100.00,0.00,100.00,0.00,12.00,1,1,1787041943,1787042160,0,2,'',2,1,306.00,26,6,1787041943,1787042369),(32,25,1,'测试','/uploads/20260819/090043_6554.png','[\"\\/uploads\\/20260819\\/090043_6554.png\"]','222222222',3000.00,100.00,0.00,100.00,0.00,1.00,0,0,1787101245,1787101380,0,2,'',3,1,3100.00,27,7,1787101245,1787101387),(33,25,1,'eee','/uploads/20260819/090529_1298.png','[\"\\/uploads\\/20260819\\/090529_1298.png\"]','2222',250.00,100.00,0.00,100.00,0.00,100.00,0,0,1787101938,1787188338,0,2,'',41,9,1250.00,29,10,1787101531,1787189719),(34,20,1,'1111','/uploads/20260819/105301_8126.png','[\"\\/uploads\\/20260819\\/105301_8126.png\",\"\\/uploads\\/20260819\\/105304_7478.png\",\"\\/uploads\\/20260819\\/105307_2913.png\"]','111',1111.00,100.00,0.00,1.00,0.00,1.00,0,0,1787107997,1787125920,0,3,'',10,0,0.00,0,0,1787107997,1787131996),(35,20,1,'测试','/uploads/20260820/075115_7683.png','[\"\\/uploads\\/20260820\\/075115_7683.png\",\"\\/uploads\\/20260820\\/075123_9840.png\",\"\\/uploads\\/20260820\\/075127_6830.png\",\"\\/uploads\\/20260820\\/075132_6642.png\"]','这东西非常好',10000.00,100.00,0.00,2000.00,0.00,0.00,0,0,1787183493,1787442600,0,5,'不好看',0,0,0.00,0,0,1787183493,1787183493),(36,29,1,'111','/uploads/20260820/083632_4854.png','[\"\\/uploads\\/20260820\\/083632_4854.png\",\"\\/uploads\\/20260820\\/083635_7154.png\",\"\\/uploads\\/20260820\\/083638_6756.jpeg\",\"\\/uploads\\/20260820\\/083642_5400.png\"]','111111111',1111.00,100.00,0.00,10.00,0.00,0.00,0,0,1787186212,1787272560,0,1,'',28,13,0.00,0,0,1787186212,1787219389),(37,20,1,'测试测试','/uploads/20260820/120110_5618.png','[\"\\/uploads\\/20260820\\/120110_5618.png\",\"\\/uploads\\/20260820\\/120119_1330.png\",\"\\/uploads\\/20260820\\/120123_8667.jpeg\",\"\\/uploads\\/20260820\\/120127_9605.png\"]','1111',100.00,100.00,0.00,100.00,0.00,0.00,0,0,1787198495,1787198580,0,2,'',2,2,300.00,28,11,1787198495,1787198581);
/*!40000 ALTER TABLE `goods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_favorite`
--

DROP TABLE IF EXISTS `goods_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_goods` (`user_id`,`goods_id`),
  KEY `idx_goods` (`goods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='商品收藏';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_favorite`
--

LOCK TABLES `goods_favorite` WRITE;
/*!40000 ALTER TABLE `goods_favorite` DISABLE KEYS */;
INSERT INTO `goods_favorite` VALUES (2,25,28,1787036699),(3,20,28,1787039604),(4,26,28,1787040469),(5,26,29,1787040750),(6,27,33,1787101566),(7,20,36,1787198066),(8,28,36,1787198986),(9,30,36,1787218064);
/*!40000 ALTER TABLE `goods_favorite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COMMENT='聊天消息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message`
--

LOCK TABLES `message` WRITE;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
INSERT INTO `message` VALUES (6,25,20,28,'111',1,1787058247),(7,20,25,28,'222',1,1787058259),(8,25,20,28,'333',1,1787058277),(9,20,25,28,'3111',1,1787058451),(10,20,20,28,'111',0,1787062553),(11,27,25,33,'还111',0,1787101579),(12,25,27,33,'111',1,1787101611),(13,30,29,36,'223123',0,1787218234),(14,30,29,36,'1234143',0,1787218252);
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '新闻标题',
  `content` text NOT NULL COMMENT '新闻内容',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1显示 0隐藏',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='新闻公告表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'平台系统升级公告','尊敬的会员：\n平台将于本周六凌晨 2:00-4:00 进行系统升级维护，期间竞拍、出价功能将暂停使用，请合理安排时间，给您带来不便敬请谅解。',1,1787043366,1787043366),(2,'新用户注册送积分活动','为回馈新老用户，即日起新注册用户完成实名认证后，即可获得 100 积分奖励，积分可在平台参与竞拍抵用，快来参与吧！',1,1787046966,1787046966),(3,'拍卖规则调整通知','自本月起，竞拍加价幅度将严格按照商品设定的加价幅度整数倍递增，请各位买家出价时注意，避免因出价不符合规则导致出价失败。',1,1787048766,1787048766);
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COMMENT='拍卖订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
INSERT INTO `order` VALUES (1,'AU20260817120000123',2,'限量版潮玩手办','',3,2,480.00,10.00,48.00,432.00,50.00,1,1786920000,3,'张三','13800000001','广东省深圳市南山区科技园1号','','',0,0,'',1786916000,0),(3,'AU20260818105846817930',8,'测试拍品01','/uploads/20260818/084555_8979.png',21,23,170.00,10.00,17.00,153.00,20.00,0,0,0,'','','','','',0,0,'',1787021926,1787021926),(4,'AU20260818144118070771',26,'光绪元宝铜币','/uploads/20260818/134447_8796.png',20,25,110000.00,10.00,11000.00,99000.00,10000.00,0,0,0,'','','','','',0,0,'',1787035278,1787035278),(5,'AU20260818161505120786',29,'祖传玉佩','/uploads/20260818/161159_8451.png',25,26,1200.00,10.00,120.00,1080.00,100.00,1,1787041330,3,'老王','13666666666','北京   5-2-201','顺丰快递','888888888888',1787041479,1787041558,'',1787040905,1787044297),(6,'AU20260818163929465185',31,'青铜器','/uploads/20260818/163213_7126.png',25,26,306.00,10.00,30.60,275.40,100.00,2,1787042552,3,'老王','13666666666','北京   5-2-201','中通快递','888888888',1787042923,1787042939,'',1787042369,1787044044),(7,'AU20260819090307067318',32,'测试','/uploads/20260819/090043_6554.png',25,27,3100.00,10.00,310.00,2790.00,100.00,0,0,4,'','','','','',0,0,'',1787101387,1787101414),(8,'AU20260819091634015010',27,'测试商品','/uploads/20260818/142055_1705.png',25,27,1200.00,10.00,120.00,1080.00,100.00,1,1787102272,3,'老王','13666666666','5-2-201','中通快递','8888888888888',1787102319,1787102345,'',1787102194,1787102345),(9,'AU20260819225842137955',28,'哈哈哈哈哈','/uploads/20260818/150347_7607.png',20,26,80000.00,10.00,8000.00,72000.00,555.00,0,0,0,'','','','','',0,0,'',1787151522,1787151522),(10,'AU20260820093519694222',33,'eee','/uploads/20260819/090529_1298.png',25,29,1250.00,10.00,125.00,1125.00,0.00,0,0,0,'','','','','',0,0,'',1787189719,1787189719),(11,'AU20260820120301760758',37,'测试测试','/uploads/20260820/120110_5618.png',20,28,300.00,10.00,30.00,270.00,0.00,1,1787198949,1,'11','13666666666','1   1','','',0,0,'',1787198581,1787198949);
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_account`
--

DROP TABLE IF EXISTS `pay_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='用户提现账户绑定';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_account`
--

LOCK TABLES `pay_account` WRITE;
/*!40000 ALTER TABLE `pay_account` DISABLE KEYS */;
INSERT INTO `pay_account` VALUES (3,20,3,'老师','88888888888888888','中国银行','',1787061665,1787061665),(5,20,1,'老头','18888888888','','/uploads/20260818/221911_2808.png',1787062756,1787062756),(6,20,4,'','111111111111','trc20','',1787198176,1787198176),(7,31,1,'124','12414','','/uploads/20260820/174116_9149.png',1787218878,1787218901),(8,31,4,'','42142141','trc20','',1787218947,1787218947);
/*!40000 ALTER TABLE `pay_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recharge`
--

DROP TABLE IF EXISTS `recharge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='充值申请表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recharge`
--

LOCK TABLES `recharge` WRITE;
/*!40000 ALTER TABLE `recharge` DISABLE KEYS */;
INSERT INTO `recharge` VALUES (1,20,300.00,1,1,'',1787132168,1787132135,1787132135);
/*!40000 ALTER TABLE `recharge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_follow`
--

DROP TABLE IF EXISTS `seller_follow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_follow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `seller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_seller` (`user_id`,`seller_id`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='店铺关注';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_follow`
--

LOCK TABLES `seller_follow` WRITE;
/*!40000 ALTER TABLE `seller_follow` DISABLE KEYS */;
INSERT INTO `seller_follow` VALUES (3,26,20,1787040467),(4,26,25,1787040749),(6,25,20,1787050046),(7,27,25,1787101620),(8,20,29,1787198085),(9,28,29,1787198973),(10,30,29,1787218076);
/*!40000 ALTER TABLE `seller_follow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting`
--

LOCK TABLES `setting` WRITE;
/*!40000 ALTER TABLE `setting` DISABLE KEYS */;
INSERT INTO `setting` VALUES (1,'site_name','竞拍商城V2',1786973051,1787183600),(2,'site_logo','/uploads/20260818/135339_9199.png',1786973051,1787183600),(3,'site_url','',1786973051,1787183600),(4,'commission_rate','10',1786973051,1787183600),(5,'seller_check','1',1786973051,1787183600),(6,'invite_code','',1786973051,1787183600),(7,'withdraw_fee','1',1786973051,1787183600),(8,'service_phone','18888888888',1786973051,1787183600),(9,'service_qq','',1786973051,1787183600),(10,'auction_delay','0',1786973051,1787183600),(11,'user_protocol','用户协议',1786973051,1787183600),(12,'goods_check','1',1787022342,1787183600),(13,'service_link','',1787104733,1787183600),(14,'privacy_policy','隐私政策',1787105070,1787183600),(15,'publish_protocol','每笔成交将扣除10%佣金',1787109114,1787183600),(16,'withdraw_min','1000',1787183350,1787183600),(17,'withdraw_max','100000',1787183350,1787183600);
/*!40000 ALTER TABLE `setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_message`
--

DROP TABLE IF EXISTS `sys_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='站内信';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_message`
--

LOCK TABLES `sys_message` WRITE;
/*!40000 ALTER TABLE `sys_message` DISABLE KEYS */;
INSERT INTO `sys_message` VALUES (2,20,0,'111','2222',1,1787152677),(3,26,0,'竞拍出局通知','您出价竞拍的「eee」已出局：您的出价 ¥350.00 于 2026-08-19 23:46:12 被 ¥450.00 超过，当前最高价 ¥450.00。如需继续竞拍，请再次出价。',1,1787154372),(4,27,0,'竞拍出局通知','您出价竞拍的「eee」已出局：您的出价 ¥450.00 于 2026-08-20 08:01:40 被 ¥650.00 超过，当前最高价 ¥650.00。如需继续竞拍，请再次出价。',0,1787184100),(5,25,0,'竞拍出局通知','您出价竞拍的「eee」已出局：您的出价 ¥650.00 于 2026-08-20 08:34:06 被 ¥750.00 超过，当前最高价 ¥750.00。如需继续竞拍，请再次出价。',0,1787186046),(6,28,0,'竞拍出局通知','您出价竞拍的「eee」已出局：您的出价 ¥750.00 于 2026-08-20 08:53:59 被 ¥850.00 超过，当前最高价 ¥850.00。如需继续竞拍，请再次出价。',0,1787187239),(7,29,0,'竞拍出局通知','您出价竞拍的「测试测试」已出局：您的出价 ¥200.00 于 2026-08-20 12:02:34 被 ¥300.00 超过，当前最高价 ¥300.00。如需继续竞拍，请再次出价。',0,1787198554),(8,20,0,'竞拍出局通知','您出价竞拍的「111」已出局：您的出价 ¥1,811.00 于 2026-08-20 12:05:13 被 ¥1,911.00 超过，当前最高价 ¥1,911.00。如需继续竞拍，请再次出价。',1,1787198713),(9,30,0,'hi打手的是啊','啊苏富比撒部分',1,1787218144),(10,28,0,'竞拍出局通知','您出价竞拍的「111」已出局：您的出价 ¥2,011.00 于 2026-08-20 17:33:42 被 ¥2,111.00 超过，当前最高价 ¥2,111.00。如需继续竞拍，请再次出价。',0,1787218422),(11,30,0,'竞拍出局通知','您出价竞拍的「111」已出局：您的出价 ¥2,111.00 于 2026-08-20 17:36:38 被 ¥2,211.00 超过，当前最高价 ¥2,211.00。如需继续竞拍，请再次出价。',1,1787218598),(12,31,0,'竞拍出局通知','您出价竞拍的「111」已出局：您的出价 ¥2,211.00 于 2026-08-20 17:49:25 被 ¥2,311.00 超过，当前最高价 ¥2,311.00。如需继续竞拍，请再次出价。',0,1787219365),(13,26,0,'竞拍出局通知','您出价竞拍的「111」已出局：您的出价 ¥2,311.00 于 2026-08-20 17:49:49 被 ¥2,411.00 超过，当前最高价 ¥2,411.00。如需继续竞拍，请再次出价。',0,1787219389);
/*!40000 ALTER TABLE `sys_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COMMENT='会员表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'13800000001','14e1b600b1fd579f47433b88e8d85291','张三','','','','',0,'',0,'','','','A10001',0,0,0,0,350.00,0.00,0,0.00,1,0,1,'',1786880000,0,1786880000,0,'',NULL,5.00,0,0.00),(2,'13800000002','14e1b600b1fd579f47433b88e8d85291','李四','','','','',0,'',0,'','','','A10002',1,0,0,1,1000.00,100.00,0,0.00,0,1,1,'',1786880100,0,1786880100,0,'',NULL,5.00,0,0.00),(3,'13800000003','14e1b600b1fd579f47433b88e8d85291','王五','','','','',0,'',0,'','','','A10003',1,1,0,1,2000.00,0.00,0,0.00,0,2,1,'',1786880200,0,1786880200,0,'',NULL,5.00,0,0.00),(4,'13800000004','14e1b600b1fd579f47433b88e8d85291','赵六','','','','',0,'',0,'','','','A10004',2,0,0,0,0.00,0.00,0,0.00,0,0,0,'',1786880300,0,1786880300,0,'',NULL,5.00,0,0.00),(20,'18888888888','63ee451939ed580ef3c4b6f0109d1fd0','www','','','','',0,'',0,'','','','436B1D9E',0,1,0,1,4560.00,10.00,0,0.00,0,1,1,'127.0.0.1',1786977719,1787198059,1786977719,1787198949,'',NULL,5.00,0,0.00),(21,'19900000001','14e1b600b1fd579f47433b88e8d85291','tester','张三','11010119900307789X','/uploads/20260818/084555_8979.png','/uploads/20260818/084555_8979.png',2,'',1787019937,'','','','8AE6FEC7',0,0,0,0,20.00,0.00,0,0.00,0,0,1,'127.0.0.1',1787010652,1787063006,1787010652,1787063006,'','',5.00,0,0.00),(22,'16666666666','63ee451939ed580ef3c4b6f0109d1fd0','用户6666','jjjj','','','',0,'',0,'opoll','bbbn','','1679BABD',0,1,0,1,9920.00,80.00,0,0.00,0,0,1,'127.0.0.1',1787011794,0,1787011794,1787021926,'',NULL,5.00,0,0.00),(23,'13333333333','14e1b600b1fd579f47433b88e8d85291','用户3333','111','450521199406250830','/uploads/20260818/103926_1119.png','/uploads/20260818/103929_1964.png',2,'',1787021759,'','','','E3191CED',0,1,0,1,20.00,0.00,0,0.00,0,0,1,'127.0.0.1',1787019028,1787041545,1787019028,1787041545,'',NULL,5.00,0,0.00),(24,'14444444444','63ee451939ed580ef3c4b6f0109d1fd0','用户4444','你哈嘛','459087784678765789','/uploads/20260818/121601_9000.png','/uploads/20260818/121604_5112.png',2,'',1787106297,'','','','625AE3A4',0,0,0,0,0.00,0.00,0,0.00,0,0,1,'127.0.0.1',1787025407,0,1787025407,1787026565,'',NULL,5.00,0,0.00),(25,'15555555555','63ee451939ed580ef3c4b6f0109d1fd0','尼玛','老头','456876767676767667','/uploads/20260818/132748_2357.png','/uploads/20260818/132751_5496.png',2,'',1787030886,'优雅','','/uploads/20260818/161826_6464.png','B5470F18',0,1,0,1,992435.40,10000.00,0,0.00,0,3,1,'127.0.0.1',1787030789,1787152721,1787030789,1787152721,'嗡嗡嗡',NULL,9.99,0,40000.00),(26,'19999999999','14e1b600b1fd579f47433b88e8d85291','小老鼠','','','','',0,'',0,'','','/uploads/20260818/164734_5620.png','5FEB951A',0,0,0,0,649.00,0.00,0,0.00,2,0,1,'127.0.0.1',1787040232,1787152778,1787040232,1787189719,'',NULL,5.00,0,0.00),(27,'12222222222','14e1b600b1fd579f47433b88e8d85291','用户2222','你好','111111111111111111','/uploads/20260819/093136_5005.png','/uploads/20260819/093139_1963.png',2,'',1787103121,'','','','75066D78',25,0,0,0,99000.00,0.00,0,0.00,1,0,1,'127.0.0.1',1787101140,1787154366,1787101140,1787189719,'',NULL,5.00,0,0.00),(28,'13558888888','63ee451939ed580ef3c4b6f0109d1fd0','1111','','','','',0,'',0,'','','','D0D7ED2D',0,0,1,0,0.00,0.00,0,0.00,1,0,1,'127.0.0.1',1787185961,1787198578,1787185961,1787198578,'',NULL,5.00,0,0.00),(29,'18888888867','63ee451939ed580ef3c4b6f0109d1fd0','rrrr','','','','',0,'',0,'111','','','C00E0678',0,1,1,1,0.00,0.00,0,0.00,0,0,1,'127.0.0.1',1787186106,1787199819,1787186106,1787199819,'2222',NULL,5.00,0,0.00),(30,'18555555555','63ee451939ed580ef3c4b6f0109d1fd0','用户5555','','','','',0,'',0,'','','','84A77C6A',0,0,0,0,990.00,10.00,0,0.00,0,0,1,'23.248.176.114',1787218044,1787218654,1787218044,1787218654,'',NULL,5.00,0,0.00),(31,'18888888887','63ee451939ed580ef3c4b6f0109d1fd0','用户8887','','','','',0,'',0,'dp名称','','','36206AD8',0,1,0,1,1190.00,10.00,0,0.00,0,0,1,'23.248.176.114',1787218523,0,1787218523,1787219824,'dp名称2',NULL,5.00,0,0.00),(32,'15666666666','63ee451939ed580ef3c4b6f0109d1fd0','用户6666','','','','',0,'',0,'','','','CEBFA63B',0,1,1,1,0.00,0.00,0,0.00,0,0,1,'23.248.176.114',1787219458,0,1787219458,1787219458,'',NULL,5.00,0,0.00);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_address`
--

DROP TABLE IF EXISTS `user_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='收货地址表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_address`
--

LOCK TABLES `user_address` WRITE;
/*!40000 ALTER TABLE `user_address` DISABLE KEYS */;
INSERT INTO `user_address` VALUES (5,21,'张三','13800138000','广东省','广州市','天河区','测试路1号A座',1,1787015670,1787015711),(6,22,'www','18888888888','广西','','','11111111111',1,1787015797,1787015797),(7,23,'老黑','18888888888','浙江省','','','12-8-01',1,1787022929,1787022929),(8,26,'老王','13666666666','北京','','','5-2-201',1,1787040973,1787040973),(9,27,'老王','13666666666','','','','5-2-201',1,1787102264,1787102264),(10,28,'11','13666666666','1','','','1',1,1787198627,1787198627);
/*!40000 ALTER TABLE `user_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdraw`
--

DROP TABLE IF EXISTS `withdraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COMMENT='提现申请表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdraw`
--

LOCK TABLES `withdraw` WRITE;
/*!40000 ALTER TABLE `withdraw` DISABLE KEYS */;
INSERT INTO `withdraw` VALUES (1,1,200.00,1.00,1,'zhangsan@alipay.com','张三','','',1,'',1786974582,1786930000,0),(2,3,500.00,2.50,3,'6222020200112233445','王五','','',1,'',0,1786931000,0),(9,20,5000.00,50.00,3,'88888888888888888','老师','中国银行','',1,'',1787061704,1787061679,1787061679),(12,20,5000.00,50.00,3,'88888888888888888','老师','中国银行','',2,'8888',1787062280,1787062257,1787062257),(14,20,1000.00,10.00,1,'18888888888','老头','','/uploads/20260818/221911_2808.png',2,'1222',1787198246,1787062774,1787062774),(15,20,1000.00,10.00,4,'111111111111','','trc20','',0,'',0,1787198271,1787198271),(16,31,1190.00,11.90,4,'42142141','','trc20','',2,'测试',1787218999,1787218968,1787218968);
/*!40000 ALTER TABLE `withdraw` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'auction'
--

--
-- Dumping routines for database 'auction'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-20 11:15:50
