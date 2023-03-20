--
-- 表的结构 `bra_vnmixitem`
-- 
--

DROP TABLE IF EXISTS bra_vnmixitem;
CREATE TABLE bra_vnmixitem (
  iid mediumint unsigned NOT NULL auto_increment,
  creator varchar(40) NOT NULL DEFAULT '',
  istatus int(10) unsigned NOT NULL default '0',
  class varchar(30) NOT NULL default '',
  stf0 varchar(30) NOT NULL default '',
  stf1 varchar(30) NOT NULL default '',
  stf2 varchar(30) NOT NULL default '',
  stf3 varchar(30) NOT NULL default '',
  stf4 varchar(30) NOT NULL default '',
  itm varchar(30) NOT NULL default '',
  itmk varchar(40) not null default '',
  itme int(10) unsigned NOT NULL default '0',
  itms char(10) not null default '0',
  itmsk varchar(40) not null default '',
    
  PRIMARY KEY  (iid)
) ENGINE=MyISAM;