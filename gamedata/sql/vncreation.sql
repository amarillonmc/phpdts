-- 跨局保留投稿、装备随机结果及审核状态，不放入会重建的 players/mapitem 表。
CREATE TABLE IF NOT EXISTS bra_vncreation (
  id int unsigned NOT NULL AUTO_INCREMENT,
  uid mediumint unsigned NOT NULL,
  creator char(15) NOT NULL,
  kind varchar(4) NOT NULL,
  status tinyint unsigned NOT NULL DEFAULT '0',
  content text NOT NULL,
  spent int unsigned NOT NULL DEFAULT '0',
  note varchar(255) NOT NULL DEFAULT '',
  updated int unsigned NOT NULL,
  PRIMARY KEY (id),
  KEY pool (kind,status),
  KEY owner (uid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
