--
-- 表的结构 `bra_log`
-- 类型：c对话、t队友、b作战、s系统
--

DROP TABLE IF EXISTS bra_log;
CREATE TABLE bra_log (
  lid mediumint unsigned NOT NULL auto_increment,
  toid smallint unsigned NOT NULL default '0',
  type char(1) NOT NULL default '',
  prcsd tinyint(1) unsigned NOT NULL default 0,
 `time` int(10) unsigned NOT NULL default '0',
 `log` varchar(255) NOT NULL default '',

  PRIMARY KEY  (lid)
) ENGINE=HEAP;

--
-- 表的结构 `bra_chat`
-- 公聊 0，队聊 1，私聊 2 ，系统 3，公告 4，
--

DROP TABLE IF EXISTS bra_chat;
CREATE TABLE bra_chat (
  cid smallint unsigned NOT NULL auto_increment,
  type enum('0','1','2','3','4','5') NOT NULL default '0',
 `time` int(10) unsigned NOT NULL default '0',
  send char(24) NOT NULL default '',
  recv char(15) NOT NULL default '',
  msg varchar(255) NOT NULL default '',

  PRIMARY KEY  (cid)
) ENGINE=HEAP;

--
-- 表的结构 `bra_mapitem`
-- 储存地图道具的信息
--

DROP TABLE IF EXISTS bra_mapitem;
CREATE TABLE bra_mapitem (
  iid mediumint unsigned NOT NULL auto_increment,
  itm char(30) NOT NULL default '',
  itmk char(5) not null default '',
  itme mediumint unsigned NOT NULL default '0',
  itms char(5) not null default '0',
  itmsk char(5) not null default '',
  pls tinyint unsigned not null default '0',
  
  PRIMARY KEY  (iid)
) ENGINE=MyISAM;

--
-- 表的结构 `bra_maptrap`
-- 储存地图陷阱的信息
--

DROP TABLE IF EXISTS bra_maptrap;
CREATE TABLE bra_maptrap (
  tid mediumint unsigned NOT NULL auto_increment,
  itm char(30) NOT NULL default '',
  itmk char(5) not null default '',
  itme smallint unsigned NOT NULL default '0',
  itms char(5) not null default '0',
  itmsk char(5) not null default '',
  pls tinyint unsigned not null default '0',
  
  PRIMARY KEY  (tid)
) ENGINE=MyISAM;

--
-- 表的结构 `bra_newsinfo`
-- 储存进行状况的信息
--

DROP TABLE IF EXISTS bra_newsinfo;
CREATE TABLE bra_newsinfo (
  nid smallint unsigned NOT NULL auto_increment,
 `time` int(10) unsigned NOT NULL default '0',
 `news` char(15) NOT NULL default '',
 `a` varchar(255) NOT NULL default '',
 `b` varchar(255) NOT NULL default '',
 `c` varchar(255) NOT NULL default '',
 `d` varchar(255) NOT NULL default '',
 `e` varchar(255) NOT NULL default '',

  PRIMARY KEY  (nid)
) ENGINE=MyISAM;

--
-- 表的结构 `bra_gambling`
-- 储存赌局的信息
--

DROP TABLE IF EXISTS bra_gambling;
CREATE TABLE bra_gambling (
  gid smallint unsigned NOT NULL auto_increment,
  uid mediumint(8) unsigned NOT NULL default '0',
  uname char(15) NOT NULL default '',
  bid smallint unsigned NOT NULL default '0',
  bname char(15) NOT NULL default '',
  wager int unsigned NOT NULL default '0',
  odds decimal(8,4) unsigned NOT NULL default '0',
  bnid smallint unsigned NOT NULL default '0',
  PRIMARY KEY  (gid)
) ENGINE=MyISAM;