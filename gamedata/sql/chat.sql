--
-- 表的结构 `bra_chat`
-- 0 全员, 1 队伍, 2 剧情, 3 遗言, 4 公告, 5 系统
--

DROP TABLE IF EXISTS bra_chat;
CREATE TABLE bra_chat (
  cid smallint unsigned NOT NULL auto_increment,
  type enum('0','1','2','3','4','5') NOT NULL default '0',
 `time` int(10) unsigned NOT NULL default '0',
  send char(24) NOT NULL default '',
  recv char(15) NOT NULL default '',
  msg char(60) NOT NULL default '',

  PRIMARY KEY  (cid)
) ENGINE=HEAP;



