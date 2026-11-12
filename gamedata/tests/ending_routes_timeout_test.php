<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
function config($file,$version=1) { return GAME_ROOT.'gamedata/cache/'.$file.'_1.php'; }
function ending_routes_enabled() { return $GLOBALS['timeout_enabled']; }
function erc_config($key,$default=null) { return $GLOBALS['timeout_config'][$key] ?? $default; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode((string)$value,true) ?: array()); }
function get_itmpara($value) { return get_clbpara($value); }
function player_save($data) { $GLOBALS['db']->players[$data['pid']]=json_decode(json_encode($data),true); }
function fetch_playerdata_by_pid($id) { return $GLOBALS['db']->players[$id] ?? null; }
function save_gameinfo() { $GLOBALS['timeout_saves']++; }
function addnews() { $GLOBALS['timeout_news'][]=func_get_args(); }
function create_dummy_playerdata() { return array('pid'=>0,'type'=>0,'pls'=>0,'clbpara'=>array()); }
function timeout_assert($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
class EndingTimeoutResult { public $rows,$index=0; function __construct($rows) { $this->rows=array_values($rows); } }
class EndingTimeoutDb {
	public $players=array(),$chat=array();
	function query($sql) {
		if(strpos($sql,'SELECT lastword')===0) return new EndingTimeoutResult(array(array('lastword'=>'稍后见')));
		if(strpos($sql,'INSERT INTO test_chat')===0) { $this->chat[]=$sql; return true; }
		if(strpos($sql,'SELECT')===0 && strpos($sql,'players')!==false) {
			$rows=$this->players;
			if(preg_match('/type=(\d+)/',$sql,$match)) $rows=array_filter($rows,function($row) use($match) { return $row['type']==$match[1]; });
			if(strpos($sql,"name='OMICRON'")!==false) $rows=array_filter($rows,function($row) { return $row['name']==='OMICRON'; });
			if(strpos($sql,'hp>0')!==false) $rows=array_filter($rows,function($row) { return $row['hp']>0; });
			if(strpos($sql,'pgroup=3')!==false) $rows=array_filter($rows,function($row) { return $row['pgroup']==3; });
			if(strpos($sql,'SELECT COUNT(*)')===0) return new EndingTimeoutResult(array(array('count'=>count($rows))));
			return new EndingTimeoutResult($rows);
		}
		throw new RuntimeException('Unexpected SQL '.$sql);
	}
	function fetch_array($result) { return $result->rows[$result->index++] ?? false; }
	function result($result,$row=0,$column=0) { return array_values($result->rows[$row])[$column]; }
}
require GAME_ROOT.'include/game/race.func.php';
require GAME_ROOT.'include/game/dissociation.func.php';
require GAME_ROOT.'include/state.func.php';
function timeout_player($pid,$race,$deadline,$master='') {
	$p=array('pid'=>$pid,'name'=>$master ? $master.'#AI1' : '超时测试'.$pid,'type'=>0,'nick'=>0,'race'=>$race,'hp'=>100,'mhp'=>100,'sp'=>100,'msp'=>100,'pls'=>120,'pgroup'=>3,'state'=>0,'action'=>'','bid'=>0,'endtime'=>100,'deathtime'=>0,'clbpara'=>array('dissociation_deadline'=>$deadline));
	if($master) $p['clbpara']['ai_master']=$master;
	foreach(array('wep','wep2','arb','arh','ara','arf','art') as $slot) foreach(array('','k','e','s','sk','para') as $suffix) $p[$slot.$suffix]=in_array($suffix,array('e','s')) ? 0 : '';
	for($i=0;$i<=6;$i++) foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $p[$key.$i]=in_array($key,array('itme','itms')) ? 0 : '';
	return $p;
}
$db=new EndingTimeoutDb(); $timeout_enabled=true; $timeout_news=array(); $timeout_saves=0; $tablepre=$gtablepre='test_'; $now=1000; $alivenum=3; $deathnum=0; $log='访问者日志'; $typeinfo=array(0=>'玩家'); $nosta=65535;
$main=timeout_player(1,5,1000); $main['clbpara']['ai_active_pid']=2;
// 主机投射成其他种族，账户关系仍须保留。
$main['race']=0; $main['clbpara']['platform']=array('active'=>1,'original'=>array('race'=>5));
$current=timeout_player(2,5,2000,$main['name']); $offline=timeout_player(3,0,999); $outside=timeout_player(4,0,2000); $outside['pgroup']=0;
foreach(array($main,$current,$offline,$outside) as $p) player_save($p);
timeout_assert(race_ai_is($main) && !race_is($main,5) && count(race_ai_family($current))===2,'平台改变生理种族，不改变AI本体及分体的账户关系');
dissociation_tick($current);
timeout_assert($db->players[1]['hp']===0 && $db->players[1]['state']===62 && $db->players[3]['hp']===0 && $db->players[3]['state']===62,'其他玩家访问会经真实death结算离线入城者到期');
timeout_assert($db->players[2]['hp']===0 && $current['hp']===0 && $current['state']===62,'离线主机到期会同时终止分体，当前旧数组不能复活分体');
timeout_assert($outside['hp']===100 && $db->players[4]['hp']===100 && $alivenum===1,'城外玩家保留生命且存活账户统计正确');
timeout_assert($log==='访问者日志' && count($timeout_news)===2 && count($db->chat)===2,'离线死亡日志不混入访问者提示，级联机体不重复播报');
$before=$timeout_news; dissociation_tick($current);
timeout_assert($timeout_news===$before && $deathnum===2,'重复请求不重复处决、新闻或死亡计数');
$current=timeout_player(5,0,$now); player_save($current); $alivenum++; $log=''; dissociation_tick($current);
timeout_assert($current['hp']===0 && $db->players[5]['state']===62 && strpos($log,'时空锚定归零')!==false,'当前玩家到期仍保留消散提示并立即保存');
$future=timeout_player(6,0,$now+1); player_save($future); $timeout_enabled=false; $now+=10; dissociation_tick($future);
timeout_assert($future['hp']===100 && $db->players[6]['hp']===100,'关闭路线时全局到期扫描不生效');
$timeout_enabled=true; $timeout_config=array('omicron_taunt'=>true);
$boss=timeout_player(20,0,$now-1); $boss['type']=41; $boss['name']='OMICRON'; $boss['pls']=124; player_save($boss);
$current=timeout_player(7,0,$now); $current['clbpara']['ex_lock']=1; player_save($current); $alivenum++; $log=''; dissociation_tick($current);
timeout_assert(!empty($current['clbpara']['dissociation_taunt']) && !empty($db->players[7]['clbpara']['dissociation_taunt']) && strpos($log,'你的时间，我收下了')!==false && $db->players[20]['hp']===100,'移动后的存活OMICRON仍追加超时对白并保存演出标记，扫描不处决NPC');
$boss['hp']=0; player_save($boss); $current=timeout_player(8,0,$now); $current['clbpara']['ex_lock']=1; player_save($current); $alivenum++; $log=''; dissociation_tick($current);
timeout_assert(empty($current['clbpara']['dissociation_taunt']) && strpos($log,'你的时间，我收下了')===false,'OMICRON已死时不再追加超时嘲讽');
$boss['hp']=100; player_save($boss); $timeout_config['omicron_taunt']=false; $current=timeout_player(9,0,$now); $current['clbpara']['ex_lock']=1; player_save($current); $alivenum++; $log=''; dissociation_tick($current);
timeout_assert(empty($current['clbpara']['dissociation_taunt']),'配置关闭时不追加超时嘲讽');
echo "Ending route timeout regression complete.\n";
