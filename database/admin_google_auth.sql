-- 后台登录谷歌验证器（TOTP 二次验证）
-- 1) 管理员表增加密钥字段（空表示未绑定）
ALTER TABLE `admin_user` ADD COLUMN `google_secret` varchar(32) NOT NULL DEFAULT '' COMMENT '谷歌验证器密钥（Base32，空=未绑定）' AFTER `password`;
-- 2) 开关：1 开启后台登录需谷歌验证码，0 关闭
INSERT INTO `setting`(`name`,`value`,`create_time`,`update_time`) VALUES ('admin_google_auth','0',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `name`=`name`;
