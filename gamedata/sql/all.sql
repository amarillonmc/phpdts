-- MySQL dump 10.13  Distrib 5.5.31, for Linux (x86_64)
--
-- Host: localhost    Database: dts
-- ------------------------------------------------------
-- Server version       5.5.31-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acbra2_chat`
--

DROP TABLE IF EXISTS `acbra2_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_chat` (
  `cid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('0','1','2','3','4','5') NOT NULL DEFAULT '0',
  `time` int(10) unsigned NOT NULL DEFAULT '0',
  `send` char(24) NOT NULL DEFAULT '',
  `recv` char(15) NOT NULL DEFAULT '',
  `msg` char(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`cid`)
) ENGINE=MEMORY AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_gambling`
--

DROP TABLE IF EXISTS `acbra2_gambling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_gambling` (
  `gid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `uname` char(15) NOT NULL DEFAULT '',
  `bid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `bname` char(15) NOT NULL DEFAULT '',
  `wager` int(10) unsigned NOT NULL DEFAULT '0',
  `odds` decimal(8,4) unsigned NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_game`
--

DROP TABLE IF EXISTS `acbra2_game`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_game` (
  `gamenum` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `gamestate` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `groomid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `groomnums` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `groomownid` char(15) NOT NULL default '',
  `starttime` int(10) unsigned NOT NULL DEFAULT '0',
  `winmode` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `winner` char(15) NOT NULL DEFAULT '',
  `arealist` varchar(255) NOT NULL DEFAULT '',
  `areanum` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `areatime` int(10) unsigned NOT NULL DEFAULT '0',
  `areawarn` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `validnum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `alivenum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `deathnum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `afktime` int(10) unsigned NOT NULL DEFAULT '0',
  `optime` int(10) unsigned NOT NULL DEFAULT '0',
  `weather` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `hack` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `hdamage` int(10) unsigned NOT NULL DEFAULT '0',
  `hplayer` char(15) NOT NULL DEFAULT '',
  `combonum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `gamevars` text NOT NULL,
  `noisevars` varchar(1000) NOT NULL DEFAULT '',
  `rdown` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `bdown` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ldown` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `kdown` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`groomid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_log`
--

DROP TABLE IF EXISTS `acbra2_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_log` (
  `lid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `toid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `type` char(1) NOT NULL DEFAULT '',
  `prcsd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `time` int(10) unsigned NOT NULL DEFAULT '0',
  `log` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`lid`)
) ENGINE=MEMORY AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_mapitem`
--

DROP TABLE IF EXISTS `acbra2_mapitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_mapitem` (
  iid mediumint unsigned NOT NULL auto_increment,
  itm char(30) NOT NULL default '',
  itmk char(40) not null default '',
  itme int(10) unsigned NOT NULL DEFAULT '0',
  itms char(10) not null default '0',
  itmsk char(40) not null default '',
  pls tinyint unsigned not null default '0',
  PRIMARY KEY (`iid`)
) ENGINE=MyISAM AUTO_INCREMENT=7726 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_maptrap`
--

DROP TABLE IF EXISTS `acbra2_maptrap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_maptrap` (
  tid mediumint unsigned NOT NULL auto_increment,
  itm char(30) NOT NULL default '',
  itmk char(40) not null default '',
  itme int(10) unsigned NOT NULL DEFAULT '0',
  itms char(10) not null default '0',
  itmsk char(40) not null default '',
  pls tinyint unsigned not null default '0',
  PRIMARY KEY (`tid`)
) ENGINE=MyISAM AUTO_INCREMENT=185 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_newsinfo`
--

DROP TABLE IF EXISTS `acbra2_newsinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_newsinfo` (
  `nid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL DEFAULT '0',
  `news` char(15) NOT NULL DEFAULT '',
  `a` varchar(255) NOT NULL DEFAULT '',
  `b` varchar(255) NOT NULL DEFAULT '',
  `c` varchar(255) NOT NULL DEFAULT '',
  `d` varchar(255) NOT NULL DEFAULT '',
  `e` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`nid`)
) ENGINE=MyISAM AUTO_INCREMENT=73 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_players`
--

DROP TABLE IF EXISTS `acbra2_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_players` (
  pid smallint unsigned NOT NULL auto_increment,
  type tinyint NOT NULL default '0',
  name char(15) NOT NULL default '',
  pass char(32) NOT NULL default '',
  gd char(1) NOT NULL default 'm',
  sNo smallint unsigned NOT NULL default '0',
  icon smallint unsigned NOT NULL default '0',
  club tinyint unsigned NOT NULL default '0',
  endtime int(10) unsigned NOT NULL default '0',
  validtime int(10) unsigned NOT NULL default '0',
  deathtime int(10) unsigned NOT NULL default '0',
  cmdnum int unsigned NOT NULL default '0',
  achievement text NOT NULL default '',
  nick text NOT NULL default '',
  nicks text NOT NULL default '',
  skillpoint smallint unsigned NOT NULL default '0',
  skills smallint unsigned NOT NULL default '0',
  cdsec int(10) unsigned NOT NULL default '0',
  cdmsec smallint(3) unsigned NOT NULL default '0',
  cdtime mediumint unsigned NOT NULL default '0',
  action char(12) NOT NULL default '',
  hp mediumint unsigned NOT NULL default '0',
  mhp mediumint unsigned NOT NULL default '0',
  sp smallint unsigned NOT NULL default '0',
  msp smallint unsigned NOT NULL default '0',
  ss smallint unsigned NOT NULL default '0',
  mss smallint unsigned NOT NULL default '0',
  att smallint unsigned NOT NULL default '0',
  def smallint unsigned NOT NULL default '0',
  pls tinyint unsigned NOT NULL default '0',
  lvl tinyint unsigned NOT NULL default '0',
  `exp` smallint unsigned NOT NULL default '0',
  money mediumint unsigned NOT NULL default '0',
  rp mediumint unsigned NOT NULL default '0',
  bid smallint unsigned NOT NULL default '0',
  `inf` char(10) not null default '',
  rage tinyint unsigned NOT NULL default '0',
  pose tinyint(1) unsigned NOT NULL default '0',
  tactic tinyint(1) unsigned NOT NULL default '0',
  killnum smallint unsigned NOT NULL default '0',
  state tinyint unsigned NOT NULL default '0',
  `wp` smallint unsigned not null default '0',
  `wk` smallint unsigned not null default '0',
  `wg` smallint unsigned not null default '0',
  `wc` smallint unsigned not null default '0',
  `wd` smallint unsigned not null default '0',
  `wf` smallint unsigned not null default '0',
  `teamID` char(15) not null default '',
  `teamPass` char(15) not null default '',
  teamMate text NOT NULL default '',
  teamIcon smallint unsigned NOT NULL default '0',
  getitem text NOT NULL default '',
  itembag text NOT NULL default '',
  itmnum smallint unsigned NOT NULL default '0',
  itmnumlimit smallint unsigned NOT NULL default '0',
  wep char(30) NOT NULL default '',
  wepk char(40) not null default '',
  wepe int(10) unsigned NOT NULL DEFAULT '0',
  weps char(10) not null default '0',
  wepsk char(40) not null default '',
  wep2 char(30) NOT NULL default '',
  wep2k char(40) not null default '',
  wep2e int(10) unsigned NOT NULL DEFAULT '0',
  wep2s char(10) not null default '0',
  wep2sk char(40) not null default '',
  arb char(30) NOT NULL default '',
  arbk char(40) not null default '',
  arbe int(10) unsigned NOT NULL DEFAULT '0',
  arbs char(10) not null default '0',
  arbsk char(40) not null default '',
  arh char(30) NOT NULL default '',
  arhk char(40) not null default '',
  arhe int(10) unsigned NOT NULL DEFAULT '0',
  arhs char(10) not null default '0',
  arhsk char(40) not null default '',
  ara char(30) NOT NULL default '',
  arak char(40) not null default '',
  arae int(10) unsigned NOT NULL DEFAULT '0',
  aras char(10) not null default '0',
  arask char(40) not null default '',
  arf char(30) NOT NULL default '',
  arfk char(40) not null default '',
  arfe int(10) unsigned NOT NULL DEFAULT '0',
  arfs char(10) not null default '0',
  arfsk char(40) not null default '',
  art char(30) NOT NULL default '',
  artk char(40) not null default '',
  arte int(10) unsigned NOT NULL DEFAULT '0',
  arts char(10) not null default '0',
  artsk char(40) not null default '',
  itm0 char(30) NOT NULL default '',
  itmk0 char(40) not null default '',
  itme0 int(10) unsigned NOT NULL DEFAULT '0',
  itms0 char(10) not null default '0',
  itmsk0 char(40) not null default '',
  itm1 char(30) NOT NULL default '',
  itmk1 char(40) not null default '',
  itme1 int(10) unsigned NOT NULL DEFAULT '0',
  itms1 char(10) not null default '0',
  itmsk1 char(40) not null default '',
  itm2 char(30) NOT NULL default '',
  itmk2 char(40) not null default '',
  itme2 int(10) unsigned NOT NULL DEFAULT '0',
  itms2 char(10) not null default '0',
  itmsk2 char(40) not null default '',
  itm3 char(30) NOT NULL default '',
  itmk3 char(40) not null default '',
  itme3 int(10) unsigned NOT NULL DEFAULT '0',
  itms3 char(10) not null default '0',
  itmsk3 char(40) not null default '',
  itm4 char(30) NOT NULL default '',
  itmk4 char(40) not null default '',
  itme4 int(10) unsigned NOT NULL DEFAULT '0',
  itms4 char(10) not null default '0',
  itmsk4 char(40) not null default '',
  itm5 char(30) NOT NULL default '',
  itmk5 char(40) not null default '',
  itme5 int(10) unsigned NOT NULL DEFAULT '0',
  itms5 char(10) not null default '0',
  itmsk5 char(40) not null default '',
  itm6 char(30) NOT NULL default '',
  itmk6 char(40) not null default '',
  itme6 int(10) unsigned NOT NULL DEFAULT '0',
  itms6 char(10) not null default '0',
  itmsk6 char(40) not null default '',
  flare int(10) NOT NULL default '0',
  dcloak int(10) NOT NULL default '0',
  auraa int(10) NOT NULL default '0',
  aurab int(10) NOT NULL default '0',
  aurac int(10) NOT NULL default '0',
  aurad int(10) NOT NULL default '0',
  aurae int(10) NOT NULL default '0',
  souls int(10) NOT NULL default '0',
  debuffa int(10) NOT NULL default '0',
  debuffb int(10) NOT NULL default '0',
  debuffc int(10) NOT NULL default '0',
  vcode char(1) not null default '',
  gemstate tinyint(3) unsigned NOT NULL DEFAULT '0',
  gemname char(30) NOT NULL default '',
  gempower char(5) not null default '0',
  gemexp smallint unsigned NOT NULL default '0',
  gemlvl tinyint unsigned NOT NULL default '0',
  typls tinyint unsigned NOT NULL default '0',
  tyowner varchar(30) NOT NULL default '',
  statusa int(10) NOT NULL default '0',
  statusb int(10) NOT NULL default '0',
  statusc int(10) NOT NULL default '0',
  statusd int(10) NOT NULL default '0',
  statuse int(10) NOT NULL default '0',
  PRIMARY KEY (`pid`),
  KEY `TYPE` (`type`,`sNo`),
  KEY `NAME` (`name`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=365 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_shopitem`
--

DROP TABLE IF EXISTS `acbra2_shopitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_shopitem` (
  sid smallint unsigned NOT NULL auto_increment,
  kind tinyint unsigned NOT NULL default '0',
  num smallint unsigned NOT NULL default '0',
  price smallint unsigned NOT NULL default '0',
  area tinyint unsigned NOT NULL default '0',
  item char(30) NOT NULL default '',
  itmk char(40) not null default '',
  itme int(10) unsigned NOT NULL DEFAULT '0',
  itms char(10) not null default '0',
  itmsk char(40) not null default '',
  PRIMARY KEY (`sid`),
  KEY `KIND` (`kind`,`area`)
) ENGINE=MyISAM AUTO_INCREMENT=165 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_users`
--

DROP TABLE IF EXISTS `acbra2_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_users` (
  `uid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `username` char(15) NOT NULL DEFAULT '',
  `password` char(32) NOT NULL DEFAULT '',
  `groupid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `roomid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `lastgame` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `credits` int(10) NOT NULL DEFAULT '0',
  `credits2` mediumint(9) NOT NULL DEFAULT '0',
  `achievement` text NOT NULL,
  `achrev` text NOT NULL,
  `daily` varchar(255) NOT NULL DEFAULT '',
  `nick` text NOT NULL,
  `nicks` text NOT NULL,
  `validgames` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wingames` smallint(5) unsigned NOT NULL DEFAULT '0',
  `gender` char(1) NOT NULL DEFAULT '0',
  `icon` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `club` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `motto` char(30) NOT NULL DEFAULT '',
  `killmsg` char(30) NOT NULL DEFAULT '',
  `lastword` char(30) NOT NULL DEFAULT '',
  `u_templateid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=17725 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acbra2_winners`
--

DROP TABLE IF EXISTS `acbra2_winners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acbra2_winners` (
  `gid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nick` text NOT NULL,
  `skillpoint` smallint unsigned NOT NULL default '0',
  `name` char(15) NOT NULL DEFAULT '',
  `pass` char(32) NOT NULL DEFAULT '',
  `gd` char(1) NOT NULL DEFAULT 'm',
  `sNo` smallint(5) unsigned NOT NULL DEFAULT '0',
  `icon` tinyint(3) unsigned NOT NULL DEFAULT '0',
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
  `rage` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `pose` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `tactic` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `killnum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `killnum2` smallint(5) unsigned NOT NULL DEFAULT '0',
  `state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `wp` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wk` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wg` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wc` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wd` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wf` smallint(5) unsigned NOT NULL DEFAULT '0',
  `teamID` char(15) NOT NULL DEFAULT '',
  `teamPass` char(15) NOT NULL DEFAULT '',
  `teamMate` text NOT NULL default '',
  `teamIcon` smallint unsigned NOT NULL default '0',
  wep char(30) NOT NULL default '',
  wepk char(40) not null default '',
  wepe int(10) unsigned NOT NULL DEFAULT '0',
  weps char(10) not null default '0',
  wepsk char(40) not null default '',
  wep2 char(30) NOT NULL default '',
  wep2k char(40) not null default '',
  wep2e int(10) unsigned NOT NULL DEFAULT '0',
  wep2s char(10) not null default '0',
  wep2sk char(40) not null default '',
  arb char(30) NOT NULL default '',
  arbk char(40) not null default '',
  arbe int(10) unsigned NOT NULL DEFAULT '0',
  arbs char(10) not null default '0',
  arbsk char(40) not null default '',
  arh char(30) NOT NULL default '',
  arhk char(40) not null default '',
  arhe int(10) unsigned NOT NULL DEFAULT '0',
  arhs char(10) not null default '0',
  arhsk char(40) not null default '',
  ara char(30) NOT NULL default '',
  arak char(40) not null default '',
  arae int(10) unsigned NOT NULL DEFAULT '0',
  aras char(10) not null default '0',
  arask char(40) not null default '',
  arf char(30) NOT NULL default '',
  arfk char(40) not null default '',
  arfe int(10) unsigned NOT NULL DEFAULT '0',
  arfs char(10) not null default '0',
  arfsk char(40) not null default '',
  art char(30) NOT NULL default '',
  artk char(40) not null default '',
  arte int(10) unsigned NOT NULL DEFAULT '0',
  arts char(10) not null default '0',
  artsk char(40) not null default '',
  itm0 char(30) NOT NULL default '',
  itmk0 char(40) not null default '',
  itme0 int(10) unsigned NOT NULL DEFAULT '0',
  itms0 char(10) not null default '0',
  itmsk0 char(40) not null default '',
  itm1 char(30) NOT NULL default '',
  itmk1 char(40) not null default '',
  itme1 int(10) unsigned NOT NULL DEFAULT '0',
  itms1 char(10) not null default '0',
  itmsk1 char(40) not null default '',
  itm2 char(30) NOT NULL default '',
  itmk2 char(40) not null default '',
  itme2 int(10) unsigned NOT NULL DEFAULT '0',
  itms2 char(10) not null default '0',
  itmsk2 char(40) not null default '',
  itm3 char(30) NOT NULL default '',
  itmk3 char(40) not null default '',
  itme3 int(10) unsigned NOT NULL DEFAULT '0',
  itms3 char(10) not null default '0',
  itmsk3 char(40) not null default '',
  itm4 char(30) NOT NULL default '',
  itmk4 char(40) not null default '',
  itme4 int(10) unsigned NOT NULL DEFAULT '0',
  itms4 char(10) not null default '0',
  itmsk4 char(40) not null default '',
  itm5 char(30) NOT NULL default '',
  itmk5 char(40) not null default '',
  itme5 int(10) unsigned NOT NULL DEFAULT '0',
  itms5 char(10) not null default '0',
  itmsk5 char(40) not null default '',
  itm6 char(30) NOT NULL default '',
  itmk6 char(40) not null default '',
  itme6 int(10) unsigned NOT NULL DEFAULT '0',
  itms6 char(10) not null default '0',
  itmsk6 char(40) not null default '',
  `motto` char(30) NOT NULL DEFAULT '',
  `wmode` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `vnum` smallint(5) unsigned NOT NULL DEFAULT '0',
  `gtime` int(10) unsigned NOT NULL DEFAULT '0',
  `gstime` int(10) unsigned NOT NULL DEFAULT '0',
  `getime` int(10) unsigned NOT NULL DEFAULT '0',
  `hdmg` int(10) unsigned NOT NULL DEFAULT '0',
  `hdp` char(15) NOT NULL DEFAULT '',
  `hkill` smallint(5) unsigned NOT NULL DEFAULT '0',
  `hkp` char(15) NOT NULL DEFAULT '',
  UNIQUE KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lpuzzle_users`
--

DROP TABLE IF EXISTS `lpuzzle_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lpuzzle_users` (
  `sid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uname` text COLLATE utf8_unicode_ci NOT NULL,
  `pass` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=112 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `submission`
--

DROP TABLE IF EXISTS `submission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submission` (
  `sid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `result` int(10) unsigned NOT NULL DEFAULT '0',
  `detail` int(10) unsigned NOT NULL DEFAULT '0',
  `timeused` int(11) NOT NULL DEFAULT '0',
  `memused` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=188 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-06-24 15:25:21
 
