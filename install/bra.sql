--
-- 表的结构 `bra_game`
-- 储存当前游戏信息
--

DROP TABLE IF EXISTS bra_game;
CREATE TABLE bra_game (
  `gamenum` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `gamestate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `groomid` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `groomnums` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `groomownid` char(15) NOT NULL DEFAULT '',
  `starttime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `winmode` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `winner` char(15) NOT NULL DEFAULT '',
  `arealist` varchar(255) NOT NULL DEFAULT '',
  `areanum` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `areatime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `areawarn` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `validnum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `alivenum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `deathnum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `afktime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `optime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `weather` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hack` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `hdamage` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `hplayer` char(15) NOT NULL DEFAULT '',
  `combonum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `gamevars` text NOT NULL,
  `noisevars` varchar(1000) NOT NULL DEFAULT '',

  PRIMARY KEY (`groomid`)

) TYPE=MyISAM;

--
-- 插入初始数据 `bra_game`
--

INSERT INTO bra_game (gamenum) VALUES (0);

--
-- 表的结构 `bra_users`
-- 储存用户的激活信息
--

DROP TABLE IF EXISTS bra_users;
CREATE TABLE bra_users (
  `uid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `username` char(15) NOT NULL DEFAULT '',
  `password` char(32) NOT NULL DEFAULT '',
  `groupid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `roomid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `lastgame` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `credits` int(10) NOT NULL DEFAULT '0',
  `credits2` mediumint(9) NOT NULL DEFAULT '0',
  `achievement` text NOT NULL default '',
  `achrev` text NOT NULL default '',
  `daily` varchar(255) NOT NULL DEFAULT '',
  `nick` text NOT NULL default '',
  `nicks` text NOT NULL default '',
  `nicksrev` text NOT NULL default '',
  `validgames` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wingames` smallint(5) unsigned NOT NULL DEFAULT '0',
  `gender` char(1) NOT NULL DEFAULT '0',
  `icon` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `club` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `motto` char(30) NOT NULL DEFAULT '',
  `killmsg` char(30) NOT NULL DEFAULT '',
  `lastword` char(30) NOT NULL DEFAULT '',
  `u_templateid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (uid),
  UNIQUE KEY username (username)

) TYPE=MyISAM;

--
-- 插入初始数据 `bra_users`
--


--
-- 表的结构 `bra_winners`
-- 储存每局获胜者的信息
--

DROP TABLE IF EXISTS bra_winners;
CREATE TABLE bra_winners (
  `gid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `nick` text NOT NULL,
  `skillpoint` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `name` char(15) NOT NULL DEFAULT '',
  `pass` char(32) NOT NULL DEFAULT '',
  `gd` char(1) NOT NULL DEFAULT 'm',
  `sNo` smallint(5) unsigned NOT NULL DEFAULT '0',
  `icon` varchar(255) NOT NULL DEFAULT '0',
  `club` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `endtime` int(10) unsigned NOT NULL DEFAULT '0',
  `hp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `mhp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `sp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `msp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ss` smallint unsigned NOT NULL default '0',
  `mss` smallint unsigned NOT NULL default '0',
  `att` smallint(5) unsigned NOT NULL DEFAULT '0',
  `def` smallint(5) unsigned NOT NULL DEFAULT '0',
  `pls` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `lvl` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `exp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `money` smallint(5) unsigned NOT NULL DEFAULT '0',
  `bid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `inf` char(10) NOT NULL DEFAULT '',
  `rage` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `pose` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `tactic` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `killnum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `killnum2` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `state` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `wp` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wk` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wg` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wc` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wd` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wf` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `teamID` char(15) NOT NULL DEFAULT '',
  `teamPass` char(15) NOT NULL DEFAULT '',
  `teamMate` text NOT NULL,
  `clbpara` text NOT NULL,
  `teamIcon` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `wep` char(30) NOT NULL DEFAULT '',
  `wepk` char(40) NOT NULL DEFAULT '',
  `wepe` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `weps` char(10) NOT NULL DEFAULT '0',
  `wepsk` char(40) NOT NULL DEFAULT '',
  `arb` char(30) NOT NULL DEFAULT '',
  `arbk` char(40) NOT NULL DEFAULT '',
  `arbe` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `arbs` char(10) NOT NULL DEFAULT '0',
  `arbsk` char(40) NOT NULL DEFAULT '',
  `arh` char(30) NOT NULL DEFAULT '',
  `arhk` char(40) NOT NULL DEFAULT '',
  `arhe` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `arhs` char(10) NOT NULL DEFAULT '0',
  `arhsk` char(40) NOT NULL DEFAULT '',
  `ara` char(30) NOT NULL DEFAULT '',
  `arak` char(40) NOT NULL DEFAULT '',
  `arae` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `aras` char(10) NOT NULL DEFAULT '0',
  `arask` char(40) NOT NULL DEFAULT '',
  `arf` char(30) NOT NULL DEFAULT '',
  `arfk` char(40) NOT NULL DEFAULT '',
  `arfe` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `arfs` char(10) NOT NULL DEFAULT '0',
  `arfsk` char(40) NOT NULL DEFAULT '',
  `art` char(30) NOT NULL DEFAULT '',
  `artk` char(40) NOT NULL DEFAULT '',
  `arte` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `arts` char(10) NOT NULL DEFAULT '0',
  `artsk` char(40) NOT NULL DEFAULT '',
  `itm0` char(30) NOT NULL DEFAULT '',
  `itmk0` char(40) NOT NULL DEFAULT '',
  `itme0` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms0` char(10) NOT NULL DEFAULT '0',
  `itmsk0` char(40) NOT NULL DEFAULT '',
  `itm1` char(30) NOT NULL DEFAULT '',
  `itmk1` char(40) NOT NULL DEFAULT '',
  `itme1` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms1` char(10) NOT NULL DEFAULT '0',
  `itmsk1` char(40) NOT NULL DEFAULT '',
  `itm2` char(30) NOT NULL DEFAULT '',
  `itmk2` char(40) NOT NULL DEFAULT '',
  `itme2` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms2` char(10) NOT NULL DEFAULT '0',
  `itmsk2` char(40) NOT NULL DEFAULT '',
  `itm3` char(30) NOT NULL DEFAULT '',
  `itmk3` char(40) NOT NULL DEFAULT '',
  `itme3` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms3` char(10) NOT NULL DEFAULT '0',
  `itmsk3` char(40) NOT NULL DEFAULT '',
  `itm4` char(30) NOT NULL DEFAULT '',
  `itmk4` char(40) NOT NULL DEFAULT '',
  `itme4` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms4` char(10) NOT NULL DEFAULT '0',
  `itmsk4` char(40) NOT NULL DEFAULT '',
  `itm5` char(30) NOT NULL DEFAULT '',
  `itmk5` char(40) NOT NULL DEFAULT '',
  `itme5` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms5` char(10) NOT NULL DEFAULT '0',
  `itmsk5` char(40) NOT NULL DEFAULT '',
  `itm6` char(30) NOT NULL DEFAULT '',
  `itmk6` char(40) NOT NULL DEFAULT '',
  `itme6` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms6` char(10) NOT NULL DEFAULT '0',
  `itmsk6` char(40) NOT NULL DEFAULT '',
  `motto` char(30) NOT NULL DEFAULT '',
  `wmode` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `vnum` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `gtime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gstime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `getime` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `hdmg` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `hdp` char(15) NOT NULL DEFAULT '',
  `hkill` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `hkp` char(15) NOT NULL DEFAULT '',
  
  UNIQUE KEY (gid)
) TYPE=MyISAM;

DROP TABLE IF EXISTS bra_vnmixitem;
CREATE TABLE bra_vnmixitem (
  `iid` mediumint(8) UNSIGNED NOT NULL,
  `creator` varchar(40) NOT NULL DEFAULT '',
  `istatus` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `class` varchar(30) NOT NULL DEFAULT '',
  `stf0` varchar(30) NOT NULL DEFAULT '',
  `stf1` varchar(30) NOT NULL DEFAULT '',
  `stf2` varchar(30) NOT NULL DEFAULT '',
  `stf3` varchar(30) NOT NULL DEFAULT '',
  `stf4` varchar(30) NOT NULL DEFAULT '',
  `itm` varchar(30) NOT NULL DEFAULT '',
  `itmk` varchar(40) NOT NULL DEFAULT '',
  `itme` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `itms` char(10) NOT NULL DEFAULT '0',
  `itmsk` varchar(40) NOT NULL DEFAULT '',
  
  PRIMARY KEY  (iid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;