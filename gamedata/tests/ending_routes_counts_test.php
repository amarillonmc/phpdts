<?php
if(!defined('IN_GAME')) exit('Access Denied');
define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
function ending_routes_enabled() { return $GLOBALS['counts_enabled']; }
require GAME_ROOT.'include/global.func.php';
class EndingCountsResult {
	public $rows,$index=0;
	function __construct($rows) { $this->rows=array_values($rows); }
}
class EndingCountsDb {
	public $players=array(),$saved=array();
	function query($sql) {
		if(strpos($sql,'SELECT ')!==0 || strpos($sql,'test_players')===false) throw new RuntimeException('Unexpected count query');
		$rows=$this->players;
		if(strpos($sql,'hp<=0 OR state>=10')!==false) $rows=array_filter($rows,function($row) { return $row['hp']<=0 || $row['state']>=10; });
		else {
			if(strpos($sql,'type=0')!==false) $rows=array_filter($rows,function($row) { return !$row['type']; });
			if(strpos($sql,'hp>0')!==false) $rows=array_filter($rows,function($row) { return $row['hp']>0; });
		}
		return new EndingCountsResult($rows);
	}
	function fetch_array($result) { return $result->rows[$result->index++] ?? false; }
	function num_rows($result) { return count($result->rows); }
	function array_update($table,$data,$where) { $this->saved=$data; }
}
function counts_assert($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
$db=new EndingCountsDb(); $tablepre=$gtablepre='test_'; $counts_enabled=true;
foreach(array('groomid','gamenum','gamestate','now','lastupdate','starttime','winmode','winner','areanum','areatime','areawarn','validnum','alivenum','deathnum','afktime','optime','weather','hack','combonum') as $key) $GLOBALS[$key]=0;
$gamenum=1; $gamestate=20; $now=100; $arealist=array(0,1); $gamevars=array();
$db->players=array(
	array('pid'=>1,'type'=>0,'hp'=>100,'state'=>0,'clbpara'=>'{}'),
	array('pid'=>2,'type'=>0,'hp'=>100,'state'=>0,'clbpara'=>'{"ai_master":"AI","ai_active":1}'),
	array('pid'=>3,'type'=>0,'hp'=>0,'state'=>26,'clbpara'=>'{"ai_master":"AI"}'),
	array('pid'=>4,'type'=>0,'hp'=>100,'state'=>0,'clbpara'=>'{}'),
	array('pid'=>5,'type'=>41,'hp'=>0,'state'=>20,'clbpara'=>'{}'),
);
save_gameinfo();
counts_assert($db->saved['validnum']===2 && $db->saved['alivenum']===2,'真实 save_gameinfo 按账号保存参战与存活数');
counts_assert($db->saved['deathnum']===2,'分体及 NPC 死亡仍沿用原有死亡数口径');
$validnum=99; $alivenum=99; save_gameinfo();
counts_assert($db->saved['validnum']===2 && $db->saved['alivenum']===2,'重复保存重新计算而不累计分体');
$db->players[0]['hp']=0; $db->players[0]['state']=26; save_gameinfo();
counts_assert($db->saved['validnum']===2 && $db->saved['alivenum']===1,'主机死亡后存活分体不额外增加账号数');
$db->players[0]['hp']=100; $db->players[0]['state']=0;
$counts_enabled=false; save_gameinfo();
counts_assert($db->saved['validnum']===4 && $db->saved['alivenum']===3,'关闭新路线时保留旧规则集逐行统计');
$gamestate=0; save_gameinfo();
counts_assert($db->saved['validnum']===0 && $db->saved['alivenum']===0 && $db->saved['deathnum']===0,'游戏停止时沿用清零行为');
