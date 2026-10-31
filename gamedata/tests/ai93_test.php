<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','game');
error_reporting(E_ALL);

// 独立回归：真实AI、addnpc、道具及战斗函数；内存数据库，不读部署配置或运行表。
function ai93_test_assert($ok,$message)
{
	if(!$ok) { fwrite(STDERR,"FAIL: $message\n"); exit(1); }
	echo "PASS: $message\n";
}
set_error_handler(function($level,$message,$file,$line) {
	// 旧模块大量依赖未初始化的可选字段；新AI文件不容许这类Notice。
	if($level === E_NOTICE && basename($file) !== 'aievent_minion.func.php') return true;
	throw new ErrorException($message,0,$level,$file,$line);
});
function config($file,$version=1)
{
	global $test_ruleset;
	$path = GAME_ROOT.'gamedata/ruleset/'.$test_ruleset.'/cache/'.$file.'_'.$version.'.php';
	return $test_ruleset && is_file($path) ? $path : GAME_ROOT.'gamedata/cache/'.$file.'_'.$version.'.php';
}
function get_current_ruleset_id() { global $test_ruleset; return $test_ruleset; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: array()); }
function &get_itmpara($value) { $value = get_clbpara($value); return $value; }
function get_itmsk_array($value) { return is_array($value) ? $value : preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY); }
function get_safe_plslist() { global $test_safe; return $test_safe; }
function diceroll($max) { global $test_dice; return min($max,$test_dice); }
function addnews(...$args) { global $test_news; $test_news[] = $args; }
function logsave(...$args) { global $test_logs; $test_logs[] = $args; }
function save_combatinfo() {}
function init_battle_rev($pa,$pd,$active) {}
function npc_chat_rev($pa,$pd,$event) { return ''; }
function npc_changewep_rev(&$pa,&$pd,$active) {}
function addnoise(...$args) {}
function check_add_searchmemory(...$args) {}
function lost_searchmemory($key,&$data) { unset($data['clbpara']['smeo']); }
function openfile($path) { return file($path); }
function save_gameinfo() { global $gamevars,$saved_gamevars; $saved_gamevars = json_encode($gamevars); }
function create_dummy_playerdata() { return ai93_test_player(0); }
function ruleset_get_fix_damage_hook(&$pa,&$pd,$active) { global $test_damage; return is_array($test_damage) ? $test_damage[$pa['pid']] : $test_damage; }
function player_format_with_db_structure($data)
{
	$data = array_intersect_key($data,ai93_test_player(0));
	$row = array();
	foreach($data as $key=>$value) $row[$key] = is_array($value) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value;
	return $row; // 与正式格式化函数一致：不能序列化掉 EXTR_REFS 绑定的运行状态。
}
function player_save($data) {
	global $db,$test_events;
	$test_events[] = array('save',$data['pid'],$data['wep']);
	$db->players[$data['pid']] = player_format_with_db_structure($data);
}
function fetch_playerdata_by_pid($pid)
{
	global $db;
	if(!isset($db->players[$pid])) return NULL;
	$data = $db->players[$pid];
	$data['clbpara'] = get_clbpara($data['clbpara']);
	return $data;
}
class Ai93TestResult
{
	public $rows; public $offset = 0;
	function __construct($rows) { $this->rows = array_values($rows); }
}
class Ai93TestDb
{
	public $players = array(), $mapitem = array(), $maptrap = array(), $chat = array();
	public $last_id = 0;
	public $affected = 0;
	function array_insert($table,$data)
	{
		global $test_events;
		$table = str_replace('test_','',$table);
		if($table === 'chat') $test_events[] = array('chat',$data['msg']);
		$key = array('players'=>'pid','mapitem'=>'iid','maptrap'=>'tid','chat'=>'cid')[$table];
		$id = $this->$table ? max(array_keys($this->$table))+1 : 1;
		$data[$key] = $id;
		$this->{$table}[$id] = $data;
		$this->last_id = $id;
	}
	function array_update($table,$data,$where)
	{
		$table = str_replace('test_','',$table);
		preg_match('/(?:pid|iid|tid)\s*=\s*\'?(\d+)/',$where,$m);
		if(isset($this->{$table}[$m[1]])) $this->{$table}[$m[1]] = array_replace($this->{$table}[$m[1]],$data);
	}
	function query($sql)
	{
		if(preg_match("/^UPDATE test_players SET pls='(\d+)' WHERE hp<=0 AND endtime<=(\d+)$/",$sql,$m)) {
			$this->affected = 0;
			foreach($this->players as &$row) if($row['hp'] <= 0 && $row['endtime'] <= $m[2] && $row['pls'] != $m[1]) {
				$row['pls'] = intval($m[1]); $this->affected++;
			}
			unset($row); return true;
		}
		if(preg_match("/^UPDATE test_players SET pls = '?(\d+)'? WHERE type = 92 AND hp > 0$/",$sql,$m)) {
			foreach($this->players as &$row) if($row['type'] == 92 && $row['hp'] > 0) $row['pls'] = intval($m[1]);
			unset($row); return true;
		}
		if(preg_match('/^DELETE FROM test_(mapitem|maptrap) WHERE (?:iid|tid)=\'?(\d+)/',$sql,$m)) {
			unset($this->{$m[1]}[$m[2]]); return true;
		}
		if(preg_match('/^INSERT INTO test_(mapitem|maptrap) \((.*?)\) VALUES \((.*)\)$/s',$sql,$m)) {
			$keys = array_map('trim',explode(',',$m[2]));
			foreach(preg_split('/\),\s*\(/',$m[3]) as $tuple) {
				$values = str_getcsv($tuple,',',"'",'\\');
				$this->array_insert('test_'.$m[1],array_combine($keys,array_map('trim',$values)));
			}
			return true;
		}
		if(!preg_match('/^SELECT .* FROM test_(players|mapitem|maptrap|chat)(?: WHERE (.*))?$/',$sql,$m)) {
			return new Ai93TestResult(array()); // 新闻、成就与UI查询不参与状态断言
		}
		$rows = $this->{$m[1]};
		$where = preg_split('/ ORDER BY | LIMIT /',$m[2] ?? '')[0];
		$corpse = strpos($where,' AND (money>0') !== false;
		if($corpse) $where = strstr($where,' AND (money>0',true);
		foreach($rows as $id=>$row) {
			$match = true;
			foreach(explode(' AND ',$where) as $condition) {
				if($condition === '') continue;
				if(preg_match("/^(\w+) IN \((.*)\)$/",$condition,$c)) {
					$match = $match && in_array($row[$c[1]],str_getcsv($c[2],',',"'"));
				} elseif(preg_match("/^(\w+)\s*(<=|>=|<>|!=|=|>|<)\s*'?([^']*)'?$/",$condition,$c)) {
					$a = $row[$c[1]] ?? 0; $b = $c[3];
					switch($c[2]) {
						case '=': $ok = $a == $b; break;
						case '!=': case '<>': $ok = $a != $b; break;
						case '>': $ok = $a > $b; break;
						case '<': $ok = $a < $b; break;
						case '<=': $ok = $a <= $b; break;
						case '>=': $ok = $a >= $b; break;
					}
					$match = $match && $ok;
				} else throw new Exception('未覆盖的测试查询：'.$sql);
			}
			if($corpse) {
				$loot = $row['money'] > 0;
				foreach(array('wep','wep2','arb') as $s) $loot = $loot || (!empty($row[$s.'s']) && !empty($row[$s.'e']));
				foreach(array('arhs','aras','arfs','arts','itms0','itms1','itms2','itms3','itms4','itms5','itms6') as $s) $loot = $loot || !empty($row[$s]);
				$match = $match && $loot;
			}
			if(!$match) unset($rows[$id]);
		}
		if(preg_match('/ORDER BY \(pls=(\d+)\)/',$sql,$m)) uasort($rows,function($a,$b) use($m) { return ($a['pls']==$m[1]) <=> ($b['pls']==$m[1]); });
		if(preg_match('/LIMIT (\d+)$/',$sql,$m)) $rows = array_slice($rows,0,intval($m[1]));
		return new Ai93TestResult($rows);
	}
	function fetch_array($result) { return $result->rows[$result->offset++] ?? NULL; }
	function num_rows($result) { return count($result->rows); }
	function data_seek($result,$offset) { $result->offset = $offset; }
	function insert_id() { return $this->last_id; }
	function affected_rows() { return $this->affected; }
	function result($result,$index=0) { return ''; }
}
function ai93_test_player($pid)
{
	$data = array(
		'pid'=>$pid,'name'=>'测试者'.$pid,'nm'=>'测试者'.$pid,'pass'=>'test','type'=>0,'club'=>0,
		'hp'=>2000,'mhp'=>2000,'sp'=>1000,'msp'=>1000,'ss'=>30,'mss'=>30,'att'=>100,'def'=>100,'lvl'=>10,'exp'=>0,
		'rage'=>0,'rp'=>0,'pose'=>1,'tactic'=>3,'pls'=>1,'pgroup'=>0,'nick'=>0,'gd'=>'n','icon'=>0,'sNo'=>1,
		'inf'=>'','state'=>0,'action'=>'','bid'=>0,'killnum'=>0,'endtime'=>0,'deathtime'=>0,'validtime'=>1,
		'money'=>0,'skillpoint'=>20,'ip'=>'127.0.0.1','horizon'=>0,
		'wp'=>100,'wk'=>100,'wc'=>100,'wg'=>100,'wd'=>100,'wf'=>100,'teamID'=>'','teamPass'=>'',
		'wep'=>'测试武器','wepk'=>'WP','wepe'=>80,'weps'=>30,'wepsk'=>'','weppara'=>'',
		'clbpara'=>array('skill'=>array(),'BGMBrand'=>'','achvars'=>array('wthchange'=>0),'randver1'=>1),'message'=>'','achievement'=>'',
	);
	foreach(array('wep2','arb','arh','ara','arf','art') as $slot) {
		$data[$slot] = $data[$slot.'k'] = $data[$slot.'sk'] = $data[$slot.'para'] = '';
		$data[$slot.'e'] = $data[$slot.'s'] = 0;
	}
	for($i=0;$i<=6;$i++) {
		$data['itm'.$i] = $data['itmk'.$i] = $data['itmsk'.$i] = $data['itmpara'.$i] = '';
		$data['itme'.$i] = $data['itms'.$i] = 0;
	}
	return $data;
}
function ai93_test_npc($sub,$pid=100)
{
	$info = get_addnpcinfo();
	$data = array_replace(ai93_test_player($pid),get_npcinit(),$info[93],$info[93]['sub'][$sub]);
	unset($data['sub']);
	$data['type'] = 93; $data['hp'] = $data['mhp']; $data['pls'] = 1;
	foreach(array('wp','wk','wc','wg','wd','wf') as $skill) if(!$data[$skill]) $data[$skill] = $data['skill'];
	return $data;
}
function ai93_test_item(&$player,$name,$kind,$effect=1,$uses=1,$slot=1)
{
	$player['itm'.$slot] = $name; $player['itmk'.$slot] = $kind;
	$player['itme'.$slot] = $effect; $player['itms'.$slot] = $uses;
	$player['itmsk'.$slot] = $player['itmpara'.$slot] = '';
}
function ai93_test_roll($min,$max,$second_min=0,$second_max=99)
{
	for($seed=0;$seed<100000;$seed++) {
		srand($seed); $a=rand(0,99); $b=rand(0,99);
		if($a >= $min && $a <= $max && $b >= $second_min && $b <= $second_max) { srand($seed); return; }
	}
	throw new Exception('找不到测试随机种子');
}

$test_ruleset = ''; $groomid = 0; $tablepre = $gtablepre = 'test_'; $database = 'mysqli';
$test_safe = array(1,2,3); $test_dice = 1; $test_damage = 25;
$now = 1000; $starttime = 900; $gamevars = array(); $saved_gamevars = '';
$db = new Ai93TestDb(); $log = $cmd = $command = ''; $mode = 'command';
$gamecfg = 1; $gamestate = 20; $weather = 0; $hack = 0; $areawarn = 0;
$arealist = array(0,1,2,3); $areanum = 0; $areaadd = 1;
$hdamage = 9999999; $hplayer = ''; $alivenum = 10; $deathnum = 0;
include GAME_ROOT.'include/resources.func.php';
include GAME_ROOT.'include/game/revclubskills.func.php';
include GAME_ROOT.'include/game/clubslct.func.php';
include GAME_ROOT.'include/game/revcombat.func.php';
include GAME_ROOT.'include/game/item.main.php';
include_once GAME_ROOT.'include/game/item.trap.php';
include_once GAME_ROOT.'include/game/item.weather.php';
include_once GAME_ROOT.'include/game/item.npc.php';
include GAME_ROOT.'include/game/item2.func.php';
include_once GAME_ROOT.'include/system.func.php';
include config('resources');
include config('gamecfg');
include config('combatcfg');
include config('clubskills');
$baseexp = $upexp = 10000; $chase_obbs = $dfight_obbs = 0;
$pdata = ai93_test_player(1);

ai93_test_assert(ai93_enabled(),'默认大房间启用');
$groomid = 1;
ai93_test_assert(!ai93_enabled(),'普通小房间不启用');
$test_ruleset = 'YELLOWKNIFE';
ai93_test_assert(ai93_enabled(),'YELLOWKNIFE小房间启用');
$groomid = 0;
ai93_test_assert(ai93_enabled(),'YELLOWKNIFE大房间启用');
$test_ruleset = 'LAIKAADVENT';
ai93_trigger(0,$pdata);
ai93_test_assert(!ai93_enabled() && !$gamevars,'其他规则集即使在大房间也不写状态');
$test_ruleset = '';
for($i=0;$i<25;$i++) ai93_test_assert(ai93_branch(ai93_test_npc($i)) === intdiv($i,5),'识别sub '.$i);
$quest = ai93_test_npc(22); $quest['clbpara']['quest_id']='Q6';
ai93_test_assert(ai93_branch($quest) === -1,'排除任务NPC');

foreach(array('','YELLOWKNIFE') as $test_ruleset) {
	$db = new Ai93TestDb(); $gamevars = array(); $pdata = ai93_test_player(1000);
	for($b=0;$b<5;$b++) { ai93_trigger($b,$pdata); ai93_trigger($b,$pdata); }
	ai93_test_assert(count($gamevars['ai93']['pending']) === 5,'扳机队列去重 '.$test_ruleset);
	$gamevars = json_decode($saved_gamevars,true);
	aievent_minion(100,$pdata);
	$counts = array();
	$safe_arrival = true;
	foreach($db->players as $npc) {
		if($npc['type'] != 93) continue;
		$counts[$npc['name']] = ($counts[$npc['name']] ?? 0)+1;
		$safe_arrival = $safe_arrival && in_array($npc['pls'],$test_safe);
	}
	ai93_test_assert($safe_arrival,'入场安全地点');
	ai93_test_assert(array_sum($counts) === 75 && count($counts) === 25 && array_unique(array_values($counts)) === array(3),'五分支各15只 '.$test_ruleset);
	$acts = array_sum(array_map(function($npc) { return get_clbpara($npc['clbpara'])['ai_acts'] ?? 0; },$db->players));
	ai93_test_assert($acts >= 1 && $acts <= 2,'每次事件随机行动1至2只');
	$gamevars = json_decode($saved_gamevars,true);
	for($b=0;$b<5;$b++) ai93_trigger($b,$pdata);
	aievent_minion(100,$pdata);
	ai93_test_assert(count(array_filter($db->players,function($p) { return $p['type'] == 93; })) === 75 && !$gamevars['ai93']['pending'],'重新加载、另一请求不能重复整批入场');
}
$test_ruleset = ''; $gamevars = array(); $db = new Ai93TestDb();
$pdata['killnum'] = 15; $pdata['inf'] = 'ie';
aievent_minion(0,$pdata);
ai93_test_assert($gamevars['ai93']['pending'] === array(0,4) && !$db->players,'未抽中事件时先保留清兵/异常扳机');
$pdata = ai93_test_player(1); $pdata['rp']=180; $gamevars = array();
aievent_minion(0,$pdata);
ai93_test_assert($gamevars['ai93']['pending'] === array(0),'RP扳机');
$pdata = ai93_test_player(1000); $gamevars = array(); $starttime = $now-300;
for($i=0;$i<30;$i++) aievent_minion(100,$pdata);
ai93_test_assert(!$db->players,'开局未超过五分钟不滴灌');
$starttime--;
for($i=0;$i<400;$i++) aievent_minion(100,$pdata);
ai93_test_assert($gamevars['ai93']['drip_cnt'] === 8 && count(array_filter($db->players,function($p) { return $p['type'] == 93; })) === 8,'滴灌最多8只，超过五分钟后自然入场');

$gamevars = array(); $db = new Ai93TestDb(); $npc = ai93_test_npc(0);
$db->array_insert('test_mapitem',array('itm'=>'钥匙剑','itmk'=>'WK','itme'=>9,'itms'=>2,'itmsk'=>'','itmpara'=>'','pls'=>1));
$db->array_insert('test_mapitem',array('itm'=>'剧情道具','itmk'=>'Y','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>'','pls'=>1));
$db->array_insert('test_mapitem',array('itm'=>'普通防具','itmk'=>'DB','itme'=>99,'itms'=>7,'itmsk'=>'I','itmpara'=>'{"keep":1}','pls'=>1));
ai93_test_roll(0,39); ai93_act($npc,$pdata);
ai93_test_assert($npc['itm1'] === '普通防具' && $npc['itme1'] === 99 && $npc['itms1'] === 7 && $npc['itmsk1'] === 'I' && $npc['itmpara1'] === '{"keep":1}' && count($db->mapitem) === 2,'拾荒保留全部物品字段，跳过关键名称及Y类');
ai93_test_assert($npc['money'] === 580 && $npc['itms5'] === 2 && $npc['clbpara']['ai_acts'] === 1,'行动积累金钱、五系药与次数');
$npc = fetch_playerdata_by_pid(100);
ai93_test_assert($npc['money'] === 580 && $npc['itms5'] === 2 && $npc['itmpara1'] === '{"keep":1}','NPC成长与拾荒物品保存后重新读取');
$money = $npc['money']; $att = $npc['att']; $wepe = $npc['wepe'];
ai93_test_roll(40,69); ai93_act($npc,$pdata);
ai93_test_assert($npc['money'] === $money+580 && $npc['att'] === $att+15 && $npc['wepe'] === $wepe+20,'磨刀打钱额外成长');
$pdata['pls']=2; $pdata['name']="O'Brien<玩家>";
player_save(ai93_test_player(1)); $npc['pls']=2; $pdata['inf']='ie';
ai93_test_roll(70,99); ai93_act($npc,$pdata);
$chat = end($db->chat);
ai93_test_assert(strpos($chat['msg'],$pdata['name']) !== false && strpos($chat['msg'],$plsinfo[2]) !== false && strpos($chat['msg'],'生命') !== false,'报点读取当前请求的新位置和状态，文本不重复HTML转义');

$gamevars = array(); $weather = 0; $npc = ai93_test_npc(5);
ai93_trigger(1,$pdata,true);
for($i=0;$i<4;$i++) ai93_act($npc,$pdata);
ai93_test_assert($gamevars['ai93']['wth_cnt'] === 3 && in_array($weather,array(8,9,17)),'玩家改天气90秒内反手，全局最多3次');
$gamevars['ai93']['last_wth_by_player'] = 0; $gamevars['ai93']['wth_cnt']=0;
for($i=0;$i<3;$i++) { ai93_test_roll(80,99,0,4); ai93_act($npc,$pdata); }
ai93_test_assert($gamevars['ai93']['wth_cnt'] === 2,'平时主动改天气最多2次');
$gamevars['ai93']['wth_cnt']=3; $db->players = array();
for($i=10;$i<17;$i++) {
	$corpse = ai93_test_player($i); $corpse['hp']=0; $corpse['endtime']=$now-$corpseprotect;
	$corpse['money']=10; $corpse['weppara']='{"keep":1}'; player_save($corpse);
}
$protected = ai93_test_player(17); $protected['hp']=0; $protected['endtime']=$now; player_save($protected);
$remote = ai93_test_player(18); $remote['hp']=0; $remote['pls']=2; player_save($remote);
$infinite = ai93_test_player(19); $infinite['hp']=0; $infinite['weps']=$nosta; player_save($infinite);
$mhp = $npc['mhp']; $att = $npc['att'];
ai93_test_roll(0,34); ai93_act($npc,$pdata);
ai93_test_assert($npc['mhp'] === $mhp+750 && $npc['att'] === $att+125 && $db->players[10]['weppara'] === '' && $db->players[14]['money'] === 0 && $db->players[15]['money'] === 10,'凸眼鱼一次最多5具，清空装备参数和金钱');
ai93_test_roll(0,34); ai93_act($npc,$pdata);
$mhp = $npc['mhp'];
ai93_test_roll(0,34); ai93_act($npc,$pdata);
ai93_test_assert($npc['mhp'] === $mhp && $db->players[17]['wepe'] === 80 && $db->players[18]['wepe'] === 80 && $db->players[19]['weps'] === 0,'已清空尸体不重复成长，保尸及异地图尸体保留，无限耐久装备也会清空');

$gamevars = array(); $db = new Ai93TestDb();
for($i=0;$i<29;$i++) ai93_drop(1,'电子垃圾');
$npc = ai93_test_npc(10);
ai93_test_roll(0,49); ai93_act($npc,$pdata);
ai93_test_assert(count($db->mapitem) === 30 && $gamevars['ai93']['trash_cnt'] === 30,'一次生成1至2件也不会越过30上限');
ai93_test_roll(0,49); ai93_act($npc,$pdata);
ai93_test_assert(count($db->mapitem) === 30 && count(array_filter($db->mapitem,function($i) { return $i['pls']==2; })) === 1,'到上限后搬到当前玩家地图，零新增');
ai93_test_assert(!ai93_drop(1,'异常雷','TO',45,1,100,'e') && !$db->maptrap,'异常雷与垃圾共享30次额度');
$gamevars = json_decode($saved_gamevars,true);
ai93_test_assert(!ai93_drop(1,'料理','PB2',120,1,100),'重新加载不重置投放上限');

$db->players = array(); $npc = ai93_test_npc(10);
$npc['hp']=50; $ally = ai93_test_npc(0,101); $ally['hp']=25; player_save($ally);
$quest = ai93_test_npc(0,102); $quest['clbpara']['quest_id']='Q6'; $quest['hp']=30; player_save($quest);
$dead = ai93_test_npc(0,103); $dead['hp']=0; player_save($dead);
ai93_test_roll(50,99,50,79); ai93_act($npc,$pdata); // 跳过受伤自救，再抽中共鸣。
ai93_test_assert($npc['hp'] === $npc['mhp'] && $npc['def'] === 197 && $db->players[101]['hp'] === 788 && $db->players[101]['wepe'] === 65,'共鸣同时强化自己及同图高级小兵');
ai93_test_assert($db->players[102]['hp'] === 30 && $db->players[103]['hp'] === 0,'共鸣不改任务NPC、不复活死者');
ai93_test_roll(80,99); ai93_act($npc,$pdata);
ai93_test_assert($npc['pose'] === 4 && $npc['tactic'] === 3,'使魔隐藏反击姿态');

$gamevars = array(); $db = new Ai93TestDb(); $npc = ai93_test_npc(15);
ai93_test_roll(0,39); ai93_act($npc,$pdata);
ai93_test_assert(count($db->mapitem) === 2,'无垃圾时女仆生成2件');
ai93_test_roll(0,39); ai93_act($npc,$pdata);
ai93_test_assert(count($db->mapitem) === 2 && $db->mapitem[1]['pls'] === 2,'女仆搬运玩家扔掉的垃圾');
$db->array_insert('test_mapitem',array('itm'=>'补给','itmk'=>'HB','itme'=>30,'itms'=>3,'itmsk'=>'','itmpara'=>'{"keep":1}','pls'=>1));
ai93_test_roll(40,69); ai93_act($npc,$pdata);
ai93_test_assert($db->mapitem[3]['itmk'] === 'PB' && $db->mapitem[3]['itmsk'] === 100 && $db->mapitem[3]['itmpara'] === '{"keep":1}','女仆给地上补给下毒，保留参数及归属');
ai93_test_roll(40,69); ai93_act($npc,$pdata);
ai93_test_assert($db->mapitem[4]['itmk'] === 'PB2' && $db->mapitem[4]['itme'] === 120,'无补给时投放伪装料理');
ai93_test_roll(70,99); ai93_act($npc,$pdata);
$trap = reset($db->maptrap);
ai93_test_assert($trap['itmk'] === 'TO' && $trap['itme'] <= 60 && $trap['itmsk'] === 100 && isset(get_itmpara($trap['itmpara'])['ai93_inf']),'女仆异常雷低伤且有真实pid归属');

$npc = ai93_test_npc(22); $npc['hp']=20;
for($i=0;$i<5;$i++) { ai93_test_roll(50,99,0,59); ai93_act($npc,$pdata); }
ai93_test_assert($npc['att'] > 129 && $npc['def'] > 167 && $npc['wp'] > 0 && $npc['hp'] === 1 && $npc['clbpara']['ai_needles'] === 5,'扎针叠面板，最多自损到1血并记录狂躁');
ai93_test_roll(50,99,60,99); ai93_act($npc,$pdata);
ai93_test_assert($npc['pls'] === 2 && $npc['pose'] === 2,'游荡到存活玩家当前位置');

// 真实战斗入口，包括击杀与原有双方player_save路径。
foreach(array(1,0) as $active) {
	$gamevars = array(); $db = new Ai93TestDb(); $log = '';
	$p = ai93_test_player(1); $npc = ai93_test_npc(22); $test_dice = 1;
	if($active) \revcombat\rev_combat_prepare($p,$npc,1,'',0);
	else \revcombat\rev_combat_prepare($npc,$p,0,'',0);
	ai93_test_assert($npc['pls'] != 1 && count($db->maptrap) === 1 && reset($db->maptrap)['pls'] === 1,'主动/遭袭均一击脱离并在原地留雷 '.$active);
	ai93_test_assert(($active ? $p['hp'] === 2000 : $npc['hp'] === 788) && empty($p['action']),'不再反击、追击或协战 '.$active);
	ai93_test_assert($db->players[100]['pls'] === $npc['pls'] && $db->players[1]['hp'] === $p['hp'],'战斗原流程保存双方数据 '.$active);
}
$p = ai93_test_player(1); $npc = ai93_test_npc(22); $test_dice = 99; $log = '';
\revcombat\rev_combat_prepare($p,$npc,1,'',0);
ai93_test_assert($npc['pls'] != 1 && $p['hp'] === 2000 && $npc['hp'] === 788,'攻击落空也脱离，未执行命中或反击伤害');
$test_dice = 1; $p = ai93_test_player(1); $npc = ai93_test_npc(22);
$npc['wepsk']='';
ai93_test_assert(ai93_hitrun($p,$npc) && get_itmpara(end($db->maptrap)['itmpara'])['ai93_inf'] === 'e','武器耗尽后仍留下对应元素药瓶');
$p = ai93_test_player(1); $npc = ai93_test_npc(22);
$npc['hp'] = 1; $traps = count($db->maptrap);
\revcombat\rev_combat_prepare($p,$npc,1,'',0);
ai93_test_assert($npc['hp'] <= 0 && $npc['pls'] === 1 && count($db->maptrap) === $traps && $p['action'] === 'corpse','被一击击杀时不逃跑、不留雷，照常摸尸');
$p = ai93_test_player(1); $npc = ai93_test_npc(22); $gamevars['ai93']['trash_cnt']=30;
ai93_test_assert(ai93_hitrun($p,$npc) && count($db->maptrap) === $traps,'额度用完仍可脱离，不额外埋雷');
$test_safe = array(1); $p = ai93_test_player(1); $npc = ai93_test_npc(22);
ai93_test_assert(!ai93_hitrun($p,$npc) && $npc['pls'] === 1,'没有异地安全地图时正常留在战斗');
$test_safe = array(); $gamevars = array(); ai93_trigger(1,$p); aievent_minion(100,$p);
ai93_test_assert($gamevars['ai93']['pending'] === array(1),'无安全地图不丢失待入场分支');
$test_safe = array(1,2,3); $areawarn = 1; $areaadd = 1;
$arealist = array(0,1,2,3); $areanum = 0; $hack = 0;
$npc = ai93_test_npc(0); ai93_test_roll(40,69); ai93_act($npc,$p);
ai93_test_assert(in_array($npc['pls'],array(2,3)),'即将禁区的小兵移往安全地图');
$areawarn = 0;

// 真正踩雷、免疫、回收、再埋设；归属始终用数字pid，异常放itmpara。
$db = new Ai93TestDb(); $gamevars = array(); $test_dice = 99;
player_save(ai93_test_npc(15));
foreach(array('i','u','e','p','w') as $infkey) {
	$p = ai93_test_player(1); $p['ex_keys']=array('unused');
	ai93_test_item($p,'异常雷','TO',45,1,0);
	$p['itmsk0']=100; $p['itmpara0']=json_encode(array('ai93_inf'=>$infkey));
	trap($p);
	ai93_test_assert($p['hp'] < 2000 && $p['inf'] === $infkey && $p['itmpara0'] === '' && $p['itms0'] === 0,'异常雷挂对应异常并清理待拾取槽 '.$infkey);
}
$p = ai93_test_player(1); $p['clbpara']['skill']=array('fireseed4'); $p['ex_keys']=array('unused');
ai93_test_item($p,'异常雷','TO',45,1,0); $p['itmpara0']='{"ai93_inf":"e"}';
trap($p);
ai93_test_assert($p['hp'] === 2000 && $p['inf'] === '' && $p['itmpara0'] === '','免疫陷阱伤害时不附加异常');
$p = ai93_test_player(1); $p['ex_keys']=array('unused'); $test_dice=0;
ai93_test_item($p,'异常雷','TO',45,1,0); $p['itmsk0']=100; $p['itmpara0']='{"ai93_inf":"p"}';
trap($p);
ai93_test_assert($p['inf'] === '' && $p['itmk0'] === 'TN' && $p['itmpara0'] === '{"ai93_inf":"p"}','回避并回收时保留异常参数');
itemget($p); item_trap(1,$p); $trap = end($db->maptrap);
ai93_test_assert($trap['itmsk'] === 1 && get_itmpara($trap['itmpara'])['ai93_inf'] === 'p','再次设雷归属为玩家且异常仍在');

// X类通过现有拾取/丢弃入口，不会与同名道具叠加。
$p = ai93_test_player(1);
for($i=0;$i<2;$i++) { ai93_test_item($p,'电子垃圾','X',1,1,0); itemget($p); }
ai93_test_assert($p['itms1'] === 1 && $p['itms2'] === 1 && $p['itm1'] === $p['itm2'],'同名X垃圾占独立背包格');
$items = count($db->mapitem); itemdrop('itm1',$p);
ai93_test_assert(count($db->mapitem) === $items+1 && !$p['itms1'],'X垃圾可正常丢弃');

// 分发入口的基础拒绝及天气/二级菜单/呼唤器的实际处理函数。
// 完整itemuse会引入旧骰子模块的common.inc.php，需在配置过的站点做HTTP联调。
$gamevars = array(); $pdata = ai93_test_player(1); $test_dice=1;
$pdata['itms1']='0';
itemuse(1,$pdata);
ai93_test_assert(!$gamevars,'空槽不触发');
$pdata['hp']=0; ai93_test_item($pdata,'解冻药','Ci',1,1); itemuse(1,$pdata);
ai93_test_assert(!$gamevars,'死亡玩家不能使用道具触发');
$gamevars = array(); $pdata = ai93_test_player(1); $weather=17;
ai93_test_item($pdata,'天气控制器','EW',1,2);
item_weather(1,$pdata);
ai93_test_assert(!$gamevars,'不可改的天气不登记最近改天气时间');
$weather=0; item_weather(1,$pdata);
ai93_test_assert($gamevars['ai93']['pending'] === array(1) && $gamevars['ai93']['last_wth_by_player'] === $now,'天气成功操作登记扳机与时间');
$gamevars = array(); $bot = ai93_test_player(2); $weather=0; $pdata=NULL;
ai93_test_item($bot,'天气控制器','EW',1,1); item_weather(1,$bot);
ai93_test_assert($gamevars['ai93']['last_wth_by_player'] === $now,'显式传入的BOT玩家也可触发天气扳机');
$pdata = ai93_test_player(1); $gamevars = array();
ai93_test_item($pdata,'陷阱','TN',10,1); item_trap(1,$pdata);
ai93_test_assert($gamevars['ai93']['pending'] === array(3),'真实埋雷触发女仆');
$gamevars = array(); $pdata = ai93_test_player(1);
ai93_test_item($pdata,'毒药','Y',1,1);
ai93_test_item($pdata,'压缩饼干','HB',30,2,2);
extract($pdata,EXTR_REFS); $itmp=1;
poison(3);
ai93_test_assert(!$gamevars,'选错下毒目标不触发');
poison(2);
ai93_test_assert($gamevars['ai93']['pending'] === array(3) && $pdata['itmk2'] === 'PB','成功下毒触发女仆');
$gamevars = array(); $pdata = ai93_test_player(1);
ai93_test_item($pdata,'『祝福宝石』','Y',1,1);
ai93_test_item($pdata,'测试防具','DB',10,30,2); $pdata['itmsk2']='Z';
extract($pdata,EXTR_REFS); $itmp=1;
qianghua(3);
ai93_test_assert(!$gamevars,'宝石强化目标非法不触发');
qianghua(2);
ai93_test_assert($gamevars['ai93']['pending'] === array(2) && $pdata['itme2'] > 10,'成功提交强化二级菜单触发使魔');
$gamevars = array(); $pdata = ai93_test_player(1); $db = new Ai93TestDb();
player_save($pdata);
ai93_test_item($pdata,'✦【自律AI呼唤器】','Y',1,1); item_npc(1,$pdata);
ai93_test_assert(count($db->players) === 31 && $gamevars['ai93']['caller_pid'] === 1 && empty($gamevars['ai93']['unlocked'][0]),'呼唤器仍召唤30只，只记录召唤者、不占自然解锁');
player_save($pdata);
$npc = fetch_playerdata_by_pid(2);
for($i=0;$i<30;$i++) ai93_act($npc,$pdata);
ai93_test_assert(count(array_filter($db->chat,function($chat) { return strpos($chat['msg'],'测试者1') !== false; })) > 0,'行动后有甩锅召唤者的广播');

// 第二期：每例重新建立真实玩家与NPC，PID不重叠。
function ai93_test_reset()
{
	global $db,$gamevars,$saved_gamevars,$pdata,$now,$starttime,$test_safe,$test_ruleset,$groomid,$weather,$aidata,$ai93_moves,$log,$test_news,$test_events,$test_dice,$test_damage,$areawarn;
	$db = new Ai93TestDb(); $gamevars = array(); $saved_gamevars = '';
	$now = mktime(12,0,0,10,26,2026); $starttime = $now-100;
	$test_safe = array(1,2,3,11,19,32); $test_ruleset = ''; $groomid = 0;
	$weather = 0; $aidata = false; $ai93_moves = 0; $log = ''; $areawarn = 0;
	$test_dice = 1; $test_damage = 25; $pdata = ai93_test_player(1); player_save($pdata);
	$test_news = $test_events = array();
}
function ai93_test_group($branch,$num=1)
{
	$ids = array();
	for($sub=$branch*5;$sub<$branch*5+5;$sub++) $ids = array_merge($ids,addnpc(93,$sub,$num));
	ai93_group_create($ids);
	return array_map('fetch_playerdata_by_pid',$ids);
}

foreach(array('','YELLOWKNIFE') as $test_ruleset_id) {
	ai93_test_reset(); $test_ruleset = $test_ruleset_id;
	$expected = array('委员长 青鬼','粉色星辰 情欲草','迅雷使魔 依莱特');
	for($branch=0;$branch<5;$branch++) {
		$members = ai93_test_group($branch,3);
		$leaders = array_values(array_filter($members,function($n) { return ($n['clbpara']['role'] ?? '') === 'leader'; }));
		$followers = array_filter($members,function($n) { return ($n['clbpara']['role'] ?? '') === 'follower'; });
		if($branch < 3) ai93_test_assert(count($leaders) === 1 && $leaders[0]['name'] === $expected[$branch] && count($followers) === 14
			&& count(array_filter($followers,function($n) use($leaders) { return $n['clbpara']['leader_pid'] == $leaders[0]['pid']; })) === 14,'批次建群选实际领袖且全部成员关联 '.$branch.' '.$test_ruleset);
		elseif($branch === 3) ai93_test_assert(count(array_filter($members,function($n) { return $n['clbpara']['role'] === 'buddy' && empty($n['clbpara']['leader_pid']); })) === 15,'女仆全员buddy '.$test_ruleset);
		else ai93_test_assert(!array_filter($members,function($n) { return isset($n['clbpara']['grp']); }),'被检体不建血缘 '.$test_ruleset);
	}
}
ai93_test_reset();
$old_members = ai93_test_group(0); $old_leader = $old_members[0]['pid'];
foreach(array(10,30,50,70,90) as $index=>$version) {
	$before = array_keys($db->players);
	$pdata['clbpara']['randver1'] = $version;
	ai93_test_item($pdata,'✦【自律AI呼唤器】','Y',1,1); item_npc(1,$pdata);
	$members = array_map('fetch_playerdata_by_pid',array_diff(array_keys($db->players),$before));
	$leaders = array_filter($members,function($n) { return ($n['clbpara']['role'] ?? '') === 'leader'; });
	ai93_test_assert(count($members) === 30 && ($index < 3 ? count($leaders) === 1 : count($leaders) === 0),'呼唤器30只按本分支建群 '.$index);
}
ai93_test_assert(fetch_playerdata_by_pid($old_members[1]['pid'])['clbpara']['leader_pid'] === $old_leader,'呼唤器不改写此前批次的血缘');

// 遭遇：没看见、忽略、应战、逃跑、先攻、特殊及45秒冷却。
foreach(array(0,25,50,15,80,85) as $roll) {
	ai93_test_reset(); $npc = ai93_test_npc(0); player_save($npc);
	ai93_test_roll($roll,$roll,40,99); ai93_encounter($npc,$pdata);
	if($roll === 0 || $roll === 25) ai93_test_assert($npc['pls'] === 1 && !$aidata && !$db->chat,'无动作遭遇分支 '.$roll);
	if($roll === 50) ai93_test_assert($npc['pose'] === 1 && $npc['tactic'] === 2,'五鬼遭遇应战姿态');
	if($roll === 15) ai93_test_assert($npc['pls'] != 1 && $npc['pose'] === 0 && $npc['tactic'] === 4,'遭遇逃往异地安全点');
	if($roll === 80) ai93_test_assert($aidata['pid'] === 100 && $aidata['pose'] === 2 && $aidata['tactic'] === 1 && $aidata['ai93_first_strike'],'先攻快照含切换后的姿态');
	if($roll === 85) ai93_test_assert(strpos(end($db->chat)['msg'],$pdata['name']) !== false,'特殊反应公开报点');
	ai93_test_assert(fetch_playerdata_by_pid(100)['clbpara']['last_react'] === $now,'遭遇冷却保存 '.$roll);
}
ai93_test_reset(); $npc = ai93_test_npc(22);
ai93_test_roll(50,94); ai93_encounter($npc,$pdata);
$first = $aidata['pid']; $other = ai93_test_npc(23,101);
ai93_test_roll(50,94); ai93_encounter($other,$pdata);
ai93_test_assert($aidata['pid'] === $first,'同一tick最多一只先攻者');
$aidata = false; $npc = fetch_playerdata_by_pid(100); $now += 44;
ai93_test_roll(50,94); ai93_encounter($npc,$pdata);
ai93_test_assert(!$aidata,'重载后44秒内不重复反应');
$now++; ai93_test_roll(50,94); ai93_encounter($npc,$pdata);
ai93_test_assert(!empty($aidata['ai93_first_strike']),'45秒冷却到期可再次反应');
foreach(array(11,19,32) as $hospital) {
	ai93_test_reset(); $npc = ai93_test_npc(22); $npc['pls'] = $pdata['pls'] = $hospital;
	ai93_test_roll(50,94); ai93_encounter($npc,$pdata);
	ai93_test_assert(!$aidata,'医院禁止强制先攻 '.$hospital);
}
foreach(array('busy','quest','horizon','dead','other_ruleset') as $condition) {
	ai93_test_reset(); $npc = ai93_test_npc(22);
	if($condition === 'busy') $npc['bid'] = 1;
	if($condition === 'quest') $npc['clbpara']['quest_id'] = 'Q6';
	if($condition === 'horizon') $npc['horizon'] = 1;
	if($condition === 'dead') $npc['hp'] = 0;
	if($condition === 'other_ruleset') $test_ruleset = 'LAIKAADVENT';
	ai93_test_roll(50,94); ai93_encounter($npc,$pdata);
	ai93_test_assert(!$aidata && !$db->chat,'遭遇守卫 '.$condition);
}

// 受击反应：清场标记、慌乱下限及每场重置，特殊雷预算。
ai93_test_reset(); $npc = ai93_test_npc(0); $npc['action']='enemy'; $npc['bid']=1;
$pdata['action']='enemy'; $pdata['bid']=100; $pdata['coveratk_flag']=9;
ai93_test_roll(0,29); $fled = ai93_react($pdata,$npc);
ai93_test_assert($fled && $npc['pls'] != 1 && !$npc['bid'] && !$pdata['bid'] && !$pdata['action'] && !isset($pdata['coveratk_flag']),'受击逃跑中断并清理追击/协战状态');
ai93_test_reset(); $npc = ai93_test_npc(0);
ai93_test_roll(30,54); ai93_react($pdata,$npc); $att = $npc['att']; $def = $npc['def'];
ai93_test_roll(30,54); ai93_react($pdata,$npc);
ai93_test_assert($att === $npc['att'] && $def === $npc['def'] && $att < 129 && $def < 167,'慌乱每场只减一次且留在原地');
unset($npc['ai93_panic_done']); $npc['att']=81; $npc['def']=101;
ai93_test_roll(30,54); ai93_react($pdata,$npc);
ai93_test_assert($npc['att'] === 80 && $npc['def'] === 100,'慌乱攻防下限80/100');
player_save($npc); $npc = fetch_playerdata_by_pid(100);
ai93_test_assert($npc['att'] === 80 && !isset($npc['ai93_panic_done']),'慌乱数值保留，单场标记不持久化');
ai93_test_reset(); $panic_in_combat = false;
for($seed=0;$seed<100 && !$panic_in_combat;$seed++) {
	$npc = ai93_test_npc(0); $npc['ai93_panic_done']=true; $p = ai93_test_player(1);
	srand($seed); \revcombat\rev_combat_prepare($p,$npc,1,'',0);
	$panic_in_combat = $npc['att'] < 129;
}
ai93_test_assert($panic_in_combat,'真实战斗入口重置上一场慌乱标记');
foreach(array(array(0,55,1,2),array(10,35,4,3),array(15,50,1,3)) as $case) {
	ai93_test_reset(); $npc=ai93_test_npc($case[0]);
	ai93_test_roll($case[1],$case[1]); $stopped=ai93_react($pdata,$npc);
	ai93_test_assert(!$stopped && $npc['pose'] === $case[2] && $npc['tactic'] === $case[3],'受击应战继续原战斗 '.$case[0]);
}
ai93_test_reset(); $npc=ai93_test_npc(10);
ai93_test_roll(80,99); ai93_react($pdata,$npc);
ai93_test_assert(count($db->maptrap) === 1 && get_itmpara(reset($db->maptrap)['itmpara'])['ai93_inf'] === 'i','受击特殊反应留下对应元素雷');
$gamevars['ai93']['trash_cnt']=30;
ai93_test_roll(80,99); ai93_react($pdata,$npc);
ai93_test_assert(count($db->maptrap) === 1,'受击特殊反应不突破第一期30额度');

// 自救占本次行动，沿用行动成长；静养只在无人点回血。
ai93_test_reset(); $npc=ai93_test_npc(0); $npc['mhp']=1000; $npc['hp']=100;
ai93_test_roll(0,49,0,39); ai93_act($npc,$pdata);
ai93_test_assert(in_array($npc['pls'],array(11,19,32)) && $npc['hp'] == 500 && $npc['clbpara']['resting'] === 1 && $npc['money'] === 580 && $npc['itms5'] === 2,'受伤自救赴无人医院回血40%，仍有统一成长');
$npc = fetch_playerdata_by_pid(100); ai93_act($npc,$pdata);
ai93_test_assert($npc['hp'] == 700,'静养重载后每次行动恢复20%');
ai93_act($npc,$pdata); ai93_act($npc,$pdata);
ai93_test_assert($npc['hp'] === 1000 && !$npc['clbpara']['resting'],'静养回满后结束');
$npc['hp']=700; $npc['clbpara']['resting']=1; $pdata['pls']=$npc['pls'];
ai93_test_roll(40,69); ai93_act($npc,$pdata);
ai93_test_assert($npc['hp'] === 700 && !$npc['clbpara']['resting'],'驱动玩家抵达打断静养，无旧位置误判回血');
ai93_test_reset(); $test_safe=array(1,2,11); $occupant=ai93_test_player(2); $occupant['pls']=11; player_save($occupant);
$npc=ai93_test_npc(5); $npc['hp']=100;
ai93_test_roll(0,49); ai93_injury($npc,$pdata);
ai93_test_assert($npc['pls'] === 2,'医院有人时退到无人安全点');
ai93_test_reset(); $npc=ai93_test_npc(15); $npc['hp']=100;
ai93_test_item($npc,'备用饼干','HB',120,2,6); $npc['itmpara6']='{"old":1}';
ai93_test_roll(30,69); ai93_injury($npc,$pdata);
ai93_test_assert($npc['hp'] === 220 && !$npc['itms6'] && !$npc['itmpara6'] && $npc['itms5'] === 1,'受伤吃补给清完整槽位，不吃五系药');
ai93_test_reset(); $npc=ai93_test_npc(22); $npc['hp']=100;
ai93_test_roll(0,59); ai93_injury($npc,$pdata);
ai93_test_assert($npc['att'] === round(129*1.15) && $npc['def'] === round(167*1.1) && $npc['pose'] === 2 && $npc['tactic'] === 1,'被检体受伤60%狂暴');
$npc['att']=$npc['def']=4294967295; ai93_enrage($npc);
ai93_test_assert($npc['att'] === 4294967295 && $npc['def'] === 4294967295,'狂暴按现有字段容量封顶');

// 演唱作用域与内存/数据库一致性，含本次玩家刚移动但尚未保存。
foreach(array(0,1) as $song_roll) {
	ai93_test_reset(); $npc=ai93_test_npc(7); player_save($npc);
	$db->players[1]['pls']=2;
	$p2=ai93_test_player(2); player_save($p2);
	$p3=ai93_test_player(3); $p3['hp']=0; player_save($p3);
	$p4=ai93_test_player(4); $p4['pls']=2; player_save($p4);
	$ally=ai93_test_npc(10,101); player_save($ally);
	$quest=ai93_test_npc(10,102); $quest['clbpara']['quest_id']='Q6'; player_save($quest);
	$story=ai93_test_npc(10,103); $story['type']=15; player_save($story);
	$field = $song_roll ? 'att' : 'def'; $old=$npc[$field]; $pold=$pdata[$field]; $aold=$ally[$field];
	ai93_test_roll($song_roll,$song_roll); $sang=ai93_sing($npc,$pdata);
	ai93_test_assert($sang && $npc[$field] === $old+30 && $pdata[$field] === $pold+30 && $db->players[100][$field] === $npc[$field] && $db->players[1][$field] === $pdata[$field],'演唱同步歌手与刚到场玩家 '.$field);
	ai93_test_assert($db->players[2][$field] === $pold+30 && $db->players[101][$field] === $aold+30 && $db->players[3][$field] === $pold && $db->players[4][$field] === $pold && $db->players[102][$field] === $aold && $db->players[103][$field] === $aold,'演唱只影响同图存活玩家与普通93 '.$field);
	player_save($pdata); player_save($npc); $gamevars=json_decode($saved_gamevars,true);
	$npc=fetch_playerdata_by_pid(100);
	ai93_test_assert($npc[$field] === $old+30 && $gamevars['ai93']['sing_cnt'] === 1 && end($test_news)[1] === 'song','演唱保存重载不回滚且发布新闻');
	$now+=899; ai93_test_assert(!ai93_sing($npc,$pdata),'单只偶像899秒仍冷却');
	$now++; ai93_test_assert(ai93_sing($npc,$pdata),'900秒到期可演唱');
	$now+=900; ai93_sing($npc,$pdata); $now+=900; ai93_sing($npc,$pdata); $now+=900;
	ai93_test_assert(!ai93_sing($npc,$pdata) && $gamevars['ai93']['sing_cnt'] === 4,'正常演唱整局最多4次');
}
ai93_test_reset(); $npc=ai93_test_npc(7); $pdata['pls']=2;
ai93_test_assert(!ai93_sing($npc,$pdata) && !$gamevars,'玩家已离开但数据库仍同图时不算观众');
ai93_test_reset(); $npc=ai93_test_npc(7); $gamevars['ai93']=array('wth_cnt'=>3);
ai93_test_roll(80,99); ai93_act($npc,$pdata);
ai93_test_assert($gamevars['ai93']['sing_cnt'] === 1,'偶像原演出间歇实际开唱');
ai93_test_reset(); $npc=ai93_test_npc(7);
ai93_test_roll(0,29); ai93_special_reaction($npc,1,$pdata);
ai93_test_assert($gamevars['ai93']['sing_cnt'] === 1,'偶像特殊遭遇30%开唱');

// 族群共享两只迁移预算；忙碌、任务及异族成员不迁移。
ai93_test_reset(); $members=ai93_test_group(0); $leader=$members[0];
foreach($members as $member) { $member['pls']=2; player_save($member); }
$leader['pls']=1; player_save($leader);
$busy=$members[1]; $busy['pls']=2; $busy['bid']=1; player_save($busy);
ai93_group_alert($leader); ai93_group_alert($leader);
$gamevars=json_decode($saved_gamevars,true);
ai93_test_assert(count($gamevars['ai93']['alerts']) === 1,'受击增援队列按grp去重并持久化');
ai93_group_tick();
$arrived=array_filter($db->players,function($n) { return $n['type']==93 && $n['pid']!=2 && $n['pls']==1; });
ai93_test_assert(count($arrived) === 2 && $ai93_moves === 2 && $db->players[$busy['pid']]['pls'] === 2 && !$gamevars['ai93']['alerts'],'下一事件增援最多两只，跳过战斗中成员并消费队列');
$before=$db->players; ai93_group_move($leader,3,'follow');
ai93_test_assert($before === $db->players,'跟随与增援共用迁移额度');
$ai93_moves=0; ai93_group_move($leader,3,'flee',1);
ai93_test_assert($ai93_moves === 2 && count(array_filter($db->players,function($n) { return $n['type']==93 && $n['pls']==3; })) === 2,'共逃仅带同图同族的两只伙伴');
$before=$db->players; $ai93_moves=0; $leader['pls']=99; ai93_group_alert($leader); ai93_group_tick();
ai93_test_assert($before === $db->players && !$gamevars['ai93']['alerts'],'禁区目的地丢弃增援，不迁移任何人');
$leader['hp']=0; player_save($leader); $before=$db->players[$busy['pid']]['att'];
ai93_group_tick(); $after=$db->players[$busy['pid']]['att']; ai93_group_tick();
ai93_test_assert($after === round($before*1.15) && $db->players[$busy['pid']]['att'] === $after && empty(get_clbpara($db->players[$busy['pid']]['clbpara'])['leader_pid']),'领袖死亡使包括忙碌成员在内的全批次狂暴一次并散伙');
ai93_test_reset(); $members=ai93_test_group(2); $leader=$members[2];
unset($db->players[$leader['pid']]); ai93_group_tick();
ai93_test_assert(get_clbpara($db->players[$members[0]['pid']]['clbpara'])['role'] === 'buddy','领袖记录消失也会散伙');
ai93_test_reset(); $members=ai93_test_group(1); $leader=$members[2]; $leader['pls']=99; player_save($leader);
$gamevars['ai93']=array('wth_cnt'=>3);
ai93_test_roll(80,99); ai93_act($leader,$pdata);
ai93_test_assert($leader['pls'] != 99 && $ai93_moves <= 2 && count(array_filter($db->players,function($n) use($leader) { return $n['type']==93 && $n['pls']==$leader['pls']; })) >= 2,'头领自律移动会带小弟跟随');

// 疯狂星期四：窗口、概率、独立额度、冷却、全字段换装与预警顺序。
foreach(array(array(10,26,18,0),array(10,29,17,59),array(10,29,19,0)) as $timecase) {
	ai93_test_reset(); $npc=ai93_test_npc(7); $now=mktime($timecase[2],$timecase[3],0,$timecase[0],$timecase[1],2026);
	$gamevars['ai93']=array('sing_cnt'=>4);
	ai93_test_roll(0,24);
	ai93_test_assert(!ai93_sing($npc,$pdata) && $pdata['wep'] !== '鸡肉','鸡肉之歌不在窗口时不触发 '.implode('/',$timecase));
}
ai93_test_reset(); $npc=ai93_test_npc(7); player_save($npc);
$now=mktime(18,0,0,10,29,2026); $gamevars['ai93']=array('sing_cnt'=>4);
ai93_test_roll(25,25);
ai93_test_assert(!ai93_sing($npc,$pdata),'25%概率边界：掷到25不改唱');
$pdata['weppara']='{"old_weapon":1}'; $db->players[1]['pls']=2;
$p2=ai93_test_player(2); player_save($p2); $dead=ai93_test_player(3); $dead['hp']=0; player_save($dead);
$remote=ai93_test_player(4); $remote['pls']=2; player_save($remote);
$ally=ai93_test_npc(0,101); player_save($ally);
$test_events=array(); ai93_test_roll(24,24); $sang=ai93_sing($npc,$pdata);
ai93_test_assert($sang && $gamevars['ai93']['chicken_cnt'] === 1 && $gamevars['ai93']['sing_cnt'] === 4,'周四18点掷到24改唱，鸡肉额度独立于正常演唱');
ai93_test_assert($pdata['wep'] === '鸡肉' && $pdata['wepk'] === 'WC' && $pdata['wepe'] === 55 && $pdata['weps'] === 55 && $pdata['wepsk'] === 'z' && $pdata['weppara'] === '' && $db->players[1]['wepe'] === 55,'鸡肉武器完整换装并同步驱动玩家内存/数据库');
ai93_test_assert($db->players[2]['wep'] === '鸡肉' && $db->players[3]['wep'] !== '鸡肉' && $db->players[4]['wep'] !== '鸡肉' && $db->players[101]['wep'] === $ally['wep'] && $npc['wep'] !== '鸡肉','鸡肉之歌仅换同图存活玩家武器');
ai93_test_assert($test_events[0][0] === 'chat' && strpos($test_events[0][1],'永久') !== false && end($test_news)[4] === '鸡肉之歌','换装之前先发布预警，唱完发新闻');
player_save($pdata); $npc=fetch_playerdata_by_pid(100); $gamevars=json_decode($saved_gamevars,true);
ai93_test_roll(0,24); ai93_test_assert(!ai93_sing($npc,$pdata),'鸡肉之歌重载后仍受单只3600秒冷却');
$other=ai93_test_npc(8,102); $now=mktime(18,59,59,10,29,2026);
ai93_test_roll(0,24); ai93_test_assert(ai93_sing($other,$pdata),'18:59:59仍可触发第二次鸡肉之歌');
$third=ai93_test_npc(9,103); ai93_test_roll(0,24);
ai93_test_assert(!ai93_sing($third,$pdata) && $gamevars['ai93']['chicken_cnt'] === 2,'鸡肉之歌整局最多两次');

// 实际discover链路：即使玩家的先攻骰为1，专属先攻仍由NPC先打。
include_once GAME_ROOT.'include/game/search.func.php';
$forced = false;
for($seed=0;$seed<200 && !$forced;$seed++) {
	ai93_test_reset(); $gamevars['sanmadead']=1; $pdata['pass']='bot';
	$npc=ai93_test_npc(22); player_save($npc);
	$event_obbs=$item_obbs=$trap_max_obbs=0; $pls_bgm=array(); $test_dice=1;
	srand($seed); ob_start(); discover(0,$pdata); ob_end_clean();
	$forced=!empty($aidata['ai93_first_strike']);
	if($forced) ai93_test_assert($pdata['hp'] < 2000 && $db->players[100]['pls'] != 1,'实际探索中NPC强制先攻并经正常战斗保存');
}
ai93_test_assert($forced,'真实探索能抽中遭遇先攻分支');

// 每次事件的遭遇数、共逃与真实反击零伤害入口。
ai93_test_reset();
for($i=100;$i<108;$i++) player_save(ai93_test_npc(0,$i));
$busy=ai93_test_npc(0,108); $busy['bid']=1; player_save($busy);
$quest=ai93_test_npc(0,109); $quest['clbpara']['quest_id']='Q6'; player_save($quest);
srand(100); aievent_minion(100,$pdata);
$reacted=array_filter($db->players,function($n) { return isset(get_clbpara($n['clbpara'])['last_react']); });
ai93_test_assert(count($reacted) > 0 && count($reacted) <= 2 && !isset(get_clbpara($db->players[108]['clbpara'])['last_react']) && !isset(get_clbpara($db->players[109]['clbpara'])['last_react']),'实际AI事件至多两只遭遇反应，忙碌和任务NPC不参加');
ai93_test_reset(); $members=ai93_test_group(3); $npc=$members[0];
foreach($members as $member) { $member['pls']=1; player_save($member); }
$npc['pls']=1; ai93_test_roll(0,24); $fled=ai93_react($pdata,$npc); player_save($npc);
ai93_test_assert($fled && $ai93_moves === 2 && count(array_filter($db->players,function($n) use($npc) { return $n['type']==93 && $n['pls']==$npc['pls']; })) === 3,'女仆受击逃离会带走同图两位buddy');
ai93_test_reset(); $npc=ai93_test_npc(0);
for($i=101;$i<105;$i++) { $ally=ai93_test_npc(0,$i); $ally['pls']=2; player_save($ally); }
$alien=ai93_test_npc(10,105); $alien['pls']=2; player_save($alien);
$quest=ai93_test_npc(0,106); $quest['pls']=2; $quest['clbpara']['quest_id']='Q6'; player_save($quest);
$busy=ai93_test_npc(0,107); $busy['pls']=2; $busy['bid']=1; player_save($busy);
for($seed=0;$seed<100 && !$ai93_moves;$seed++) { srand($seed); ai93_special_reaction($npc,0,$pdata); }
ai93_test_assert($ai93_moves === 2 && $db->players[105]['pls'] === 2 && $db->players[106]['pls'] === 2 && $db->players[107]['pls'] === 2,'五鬼呼叫只带同分支闲散同僚，至多两只');
foreach(array('miss','counter_zero') as $battle_case) {
	$observed = false;
	for($seed=0;$seed<100 && !$observed;$seed++) {
		ai93_test_reset(); $npc=ai93_test_npc(0); $p=ai93_test_player(1);
		if($battle_case === 'miss') {
			$test_dice=99; srand($seed); \revcombat\rev_combat_prepare($p,$npc,1,'',0);
			$observed=$npc['pls'] != 1 && $npc['hp'] === 788 && $p['hp'] === 2000;
		} else {
			$test_damage=array(100=>25,1=>0); srand($seed); \revcombat\rev_combat_prepare($npc,$p,0,'',0);
			$observed=!empty($p['is_counter']) && $npc['pls'] != 1 && $npc['hp'] === 788 && $p['hp'] === 1975;
		}
	}
	ai93_test_assert($observed,'真实战斗未造成伤害时仍能触发普通小兵逃跑 '.$battle_case);
}

// 物品拆分漏迁回归：沿用本脚本的真实addnpc与内存数据库，检查实际生成、移动和消耗。
$summon_seeds = $move_seeds = array();
for($seed=0;count($summon_seeds)<7;$seed++) { srand($seed); $summon_seeds[rand(0,6)]=$seed; }
for($seed=0;count($move_seeds)<101;$seed++) { srand($seed); $move_seeds[rand(0,100)]=$seed; }
foreach(array('','YELLOWKNIFE','LAIKAADVENT','RAIDCAGEDBIRD') as $ruleset_id) {
	foreach($summon_seeds as $sub=>$seed) {
		ai93_test_reset(); $test_ruleset=$ruleset_id;
		$p = ai93_test_player(1); ai93_test_item($p,'电掣召唤仪','Z'); $p['itmpara1']='{"source":true}';
		$info=get_addnpcinfo(); srand($seed); item_npc(1,$p);
		$npc=$db->players[2];
		ai93_test_assert(count($db->players) === 2 && $npc['type'] === 89 && $npc['name'] === $info[89]['sub'][$sub]['name']
			&& ($sub != 4 || $npc['wg'] === 0),'电掣召唤对应NPC并保留零熟练度 '.$ruleset_id.' sub'.$sub);
		// MySQL读取数量为字符串，避免PHP 7把整数0与无限耐久字符串作宽松比较。
		player_save($p); $p=fetch_playerdata_by_pid(1); $p['itms1']=(string)$p['itms1']; itemuse(1,$p);
		ai93_test_assert(!$p['itms1'] && $p['itmpara1'] === '' && count($db->players) === 2,'召唤后消耗及参数清理已保存，重放不重复召唤 '.$ruleset_id.' sub'.$sub);
	}
}
foreach(array(2,$nosta) as $uses) {
	ai93_test_reset(); $p=ai93_test_player(1); ai93_test_item($p,'电掣召唤仪','Y',1,$uses);
	item_npc(1,$p);
	ai93_test_assert(count($db->players) === 2 && $p['itms1'] === ($uses == $nosta ? $nosta : 1),'召唤仪仅消耗一次，保留无限耐久 '.$uses);
}
foreach(array(0=>0,20=>0,21=>22,40=>22,41=>23,60=>23,61=>24,80=>24,81=>28,100=>28,'gather'=>7) as $roll=>$destination) {
	ai93_test_reset(); $p=ai93_test_player(1); $p['pls']=7;
	$gather=$roll === 'gather';
	ai93_test_item($p,$gather ? '✦种火聚集装置✦' : '✦种火定点移位装置✦','Y'); $p['itmpara1']='{"source":true}';
	foreach(array(10=>array(92,100),11=>array(92,100),12=>array(92,0),13=>array(89,100)) as $npc_pid=>$attrs) {
		$npc=ai93_test_player($npc_pid); $npc['type']=$attrs[0]; $npc['hp']=$attrs[1]; $npc['pls']=3; player_save($npc);
	}
	if(!$gather) srand($move_seeds[$roll]);
	item_npc(1,$p);
	ai93_test_assert($db->players[10]['pls'] === $destination && $db->players[11]['pls'] === $destination
		&& $db->players[12]['pls'] === 3 && $db->players[13]['pls'] === 3 && $p['pls'] === 7,'种火装置只移动存活种火，目的地及边界 '.$roll);
	ai93_test_assert(count($db->chat) === 2 && $test_news[0][1] === 'fsmove' && $test_news[0][4] === 7,'种火装置广播及新闻 '.$roll);
	player_save($p); $p=fetch_playerdata_by_pid(1); $p['itms1']=(string)$p['itms1']; $db->players[10]['pls']=3; itemuse(1,$p);
	ai93_test_assert(!$p['itms1'] && $p['itmpara1'] === '' && $db->players[10]['pls'] === 3 && count($db->chat) === 2,'种火装置消耗已保存，重放不再次移动 '.$roll);
}
ai93_test_reset(); $p=ai93_test_player(1); ai93_test_item($p,'✦种火定点移位装置✦','Y');
$saved_pls=$plsinfo[0]; unset($plsinfo[0]); srand($move_seeds[0]); item_npc(1,$p); $plsinfo[0]=$saved_pls;
ai93_test_assert($p['itms1'] === 1 && !$db->chat && !$test_news && strpos($log,'无法定位') !== false,'目的地不存在时保留种火装置并提示');

include_once GAME_ROOT.'include/game/item.other.php';
foreach(array('J'=>array('游戏王卡包','ygo',1,''),'s'=>array('游戏王卡包','ygo',1,''),
	'X'=>array('■DeathNote■','Y',1,''),'x'=>array('奇迹的元素','HB',65535,'z'),'Jp'=>array('游戏王卡包','ygo',1,'')) as $code=>$expected) {
	foreach(array(0,6) as $slot) {
		ai93_test_reset(); $p=ai93_test_player(1); ai93_test_item($p,'属性代码片段','🥚',40,8,$slot);
		$p['itmsk'.$slot]=$code; $p['itmpara'.$slot]='{"source":true}';
		item_other($slot,$p); player_save($p); $p=fetch_playerdata_by_pid(1);
		ai93_test_assert(array($p['itm0'],$p['itmk0'],$p['itme0'],$p['itmsk0']) === $expected && $p['itms0'] === 1
			&& $p['itmpara0'] === '' && ($slot === 0 || (!$p['itms6'] && $p['itmpara6'] === '')),'代码片段转换整件并清除旧参数，保存后产物正确 '.$code.' 槽'.$slot);
	}
}
foreach(array('v','V') as $code) {
	ai93_test_reset(); $p=ai93_test_player(1); ai93_test_item($p,'属性代码片段','🥚',40,8);
	$p['itmsk1']=$code; $p['itmpara1']='{"source":true}'; $p['wepsk']='p';
	item_other(1,$p); player_save($p); $p=fetch_playerdata_by_pid(1); $p['itms1']=(string)$p['itms1']; itemuse(1,$p);
	ai93_test_assert($p['wepsk'] === 'p'.$code && !$p['itms1'] && $p['itmpara1'] === '','诅咒/绑定代码作用于当前武器且重放不重复附加 '.$code);
}
ai93_test_reset(); $p=ai93_test_player(1); ai93_test_item($p,'属性代码片段','🥚',40,8); $p['itmsk1']='X';
ai93_test_item($p,'手中原物品','Y',5,2,0); $p['itmpara0']='{"keep":true}'; $before=$p;
item_other(1,$p);
ai93_test_assert($p === $before && strpos($log,'先处理手中的物品') !== false,'手中有物品时不覆盖、不消耗代码片段');
$p['itmsk1']='A'; $before=$p; $log=''; item_other(1,$p);
ai93_test_assert($p === $before && strpos($log,'以后再来探索') !== false,'未实现的代码属性保留原物品并提示');

// 歌簿、残响与演出沿用真实函数及同一内存数据库，覆盖保存后再提交。
include_once GAME_ROOT.'include/game/song.inc.php';
ai93_test_reset();
$pdata['art']='【Alicemagic】'; $pdata['artk']='ss'; $pdata['arts']=1; $pdata['arte']=999;
$pdata['ss']=29;
sing('Alicemagic');
ai93_test_assert($pdata['ss'] === 29 && empty($pdata['clbpara']['songbook']) && !$gamevars,'歌魂不足不记歌、不留残响');
$pdata['ss']=100;
sing('Alicemagic');
ai93_test_assert($pdata['ss'] === 70 && isset($pdata['clbpara']['songbook']['Alicemagic']) && $gamevars['songecho'][1]['power'] === 30,'歌词卡解锁按歌曲成本扣费，不使用卡片效果值');
$pdata['art']=$pdata['artk']=''; $pdata['arts']=0;
player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
sing('Alicemagic');
ai93_test_assert($pdata['ss'] === 40 && count($pdata['clbpara']['songbook']) === 1,'重载后免装备重唱，歌簿去重');
$before=$pdata; $echo_before=$gamevars;
foreach(array('Crow Song','一剪梅','不存在的歌曲',array('Alicemagic')) as $invalid) sing($invalid);
ai93_test_assert($pdata === $before && $gamevars === $echo_before,'拒绝未学、未解锁、未知及数组形式的曲目');
$pdata['art']='【Crow Song】'; $pdata['artk']='Y'; $pdata['arts']=1;
ai93_test_assert(!isset(song_list($pdata)['Crow Song']),'同名非歌词卡不能解锁');
$pdata['artk']='ss'; $pdata['arts']=0;
ai93_test_assert(!isset(song_list($pdata)['Crow Song']),'已耗尽的歌词卡不能解锁');
$pdata['arts']=1;
ai93_test_assert(isset(song_list($pdata)['Crow Song']),'歌单同时列出歌簿和当前歌词卡');

// 春日影：唱者学会重力，只有同点其他存活玩家获得生命增益。
ai93_test_reset(); $pdata['hp']=800; $pdata['ss']=59;
$pdata['art']='【春日影】'; $pdata['artk']='ss'; $pdata['arts']=1; $pdata['arte']=999;
foreach(array(2,3,4,5,6) as $id) {
	$listener=ai93_test_player($id);
	if($id === 2) { $listener['hp']=1000; $listener['clbpara']['songbook']=array('水手'=>1); }
	if($id === 4) $listener['hp']=0;
	if($id === 5) $listener['pls']=2;
	if($id === 6) $listener['type']=93;
	player_save($listener);
}
$before=$pdata; $listeners_before=$db->players;
sing('春日影');
ai93_test_assert($pdata === $before && $db->players === $listeners_before && !$gamevars && !$db->chat,'春日影不足60歌魂时不学习、不加生命、不广播');
$pdata['ss']=180; sing('春日影'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert($pdata['ss'] === 120 && $pdata['hp'] === 800 && $pdata['mhp'] === 2000
	&& in_array('gravity',$pdata['clbpara']['skill']) && isset($pdata['clbpara']['songbook']['春日影']),'歌词卡演唱春日影扣60、学歌与重力，唱者不加生命');
$listener=fetch_playerdata_by_pid(2);
ai93_test_assert($listener['hp'] === 1010 && $listener['mhp'] === 2010 && isset($listener['clbpara']['songbook']['水手'])
	&& $db->players[3]['hp'] === 2010 && $db->players[3]['mhp'] === 2010,'同点满血和受伤玩家各增加10生命及上限，重载保留原歌簿');
ai93_test_assert($db->players[4] === $listeners_before[4] && $db->players[5] === $listeners_before[5]
	&& $db->players[6] === $listeners_before[6],'春日影不影响尸体、异地玩家和NPC');
ai93_test_assert($gamevars['echo_serial'] === 1 && $gamevars['songecho'][1]['power'] === 60
	&& count($db->chat) === 1 && end($test_news)[1] === 'song' && end($test_news)[4] === '春日影','春日影只放一次普通残响，并发布歌词和歌名新闻');
$pdata['art']=$pdata['artk']=''; $pdata['arts']=0; player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
sing('春日影'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert($pdata['ss'] === 60 && count(array_keys($pdata['clbpara']['skill'],'gravity')) === 1
	&& count($pdata['clbpara']['songbook']) === 1 && $db->players[2]['hp'] === 1020 && $db->players[2]['mhp'] === 2020
	&& $gamevars['echo_serial'] === 2 && $gamevars['songecho'][1]['power'] === 90,'重载后无卡重唱照常扣费、加生命与共鸣，技能和歌簿不重复登记');
$pdata['clbpara']['setsuna_mute']=1; $before=$pdata; $listeners_before=$db->players; $echo_before=$gamevars;
sing('春日影');
ai93_test_assert($pdata === $before && $db->players === $listeners_before && $gamevars === $echo_before,'春日影失声时不触发任何增益');
unset($pdata['clbpara']['setsuna_mute']);
death('event','',0,'测试',$pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert(in_array('gravity',$pdata['clbpara']['skill']) && isset($pdata['clbpara']['songbook']['春日影']),'事件死亡保存后重力和春日影歌簿均保留');

// 真实战斗准备自动铺被动标记；倍率作用于双方且只乘一次，可以越过基础命中上限。
foreach(array(0,1,2,3) as $holders) foreach(array(1,0) as $active) {
	ai93_test_reset(); $a=ai93_test_player(1); $b=ai93_test_player(2); $a['wp']=$b['wp']=10000;
	if($holders & 1) getclubskill('gravity',$a['clbpara']);
	if($holders & 2) getclubskill('gravity',$b['clbpara']);
	\revcombat\get_attr_wepbase($a,$b,$active);
	$expected=$hitrate_max_obbs['P']*($holders ? 5/3 : 1);
	ai93_test_assert(abs(\revattr\get_hitrate_rev($a,$b,$active)-$expected) < 0.000001
		&& abs(\revattr\get_hitrate_rev($b,$a,1-$active)-$expected) < 0.000001,'双方命中倍率、无持有者不变、双持不叠乘 '.$holders.' 视角'.$active);
	$test_dice=99; \revcombat\rev_combat_prepare($a,$b,$active,'',0);
	ai93_test_assert($a['hitrate_times'] === ($holders ? 1 : 0) && $b['hp'] === ($holders ? 1975 : 2000),'真实战斗99点命中骰在重力下命中，原上限下落空 '.$holders.' 视角'.$active);
}
foreach(array('c2_intuit'=>'WK','c4_stable'=>'WG') as $skill=>$weapon) {
	ai93_test_reset(); $a=ai93_test_player(1); $b=ai93_test_player(2); $a['wepk']=$weapon;
	getclubskill($skill,$a['clbpara']); set_skillpara($skill,'lvl',6,$a['clbpara']);
	\revcombat\get_attr_wepbase($a,$b,1); $before=\revattr\get_hitrate_rev($a,$b,1);
	getclubskill('gravity',$b['clbpara']); \revcombat\get_attr_wepbase($a,$b,1);
	ai93_test_assert(abs(\revattr\get_hitrate_rev($a,$b,1)-$before*5/3) < 0.000001,'重力与既有命中技能乘法叠加 '.$skill);
}

ai93_test_reset(); $pdata['ss']=200; $pdata['clbpara']['songbook']=array('星をつなげて'=>1);
$pdata['clbpara']['star_mark_pls']=2;
$mode=''; // 无页面上下文时探测仪不绘制雷达，本例只验证回传和回响落点。
sing('星をつなげて'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
$mode='command';
ai93_test_assert($pdata['pls'] === 2 && isset($gamevars['songecho'][1]) && !isset($gamevars['songecho'][2])
	&& end($test_news)[3] === $plsinfo[1],'星标回传后残响和新闻仍留在原演唱地点');
ai93_test_reset(); $pdata['art']='【忘忧草】'; $pdata['artk']='ss'; $pdata['arts']=1; $pdata['arte']=1;
$pdata['ss']=199; sing('忘忧草');
ai93_test_assert($pdata['hp'] === 2000 && $pdata['ss'] === 199 && empty($pdata['clbpara']['songbook']),'忘忧草不足200歌魂不能演唱');
$pdata['clbpara']['songbook']=array('忘忧草'=>1); $pdata['art']='其他饰物'; $pdata['artk']='Y';
$pdata['ss']=200; sing('忘忧草'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert($pdata['hp'] === 0 && $pdata['ss'] === 0 && isset($pdata['clbpara']['songbook']['忘忧草'])
	&& $gamevars['songecho'][1]['power'] === 200 && end($test_news)[5] === '【忘忧草】','无卡重唱忘忧草留下正确死亡来源，残响与歌簿保存后仍保留');

ai93_test_reset();
$costs=song_costs();
$ten=array_slice(array_keys($costs),0,10);
$pdata['clbpara']['songbook']=array_fill_keys(array_slice($ten,0,9),1);
$pdata['art']='【'.$ten[9].'】'; $pdata['artk']='ss'; $pdata['arts']=1; $pdata['ss']=1000;
sing($ten[9]);
ai93_test_assert(count($pdata['clbpara']['songbook']) === 10 && !empty($pdata['clbpara']['showcase']) && count(song_list($pdata)) === 14,'第十首普通歌解锁四首演出');
$log=''; sing($ten[9]);
ai93_test_assert(strpos($log,'第十页') === false,'解锁提示不重复');
$pdata['clbpara']['setsuna_mute']=1; $before=$pdata;
sing('一剪梅'); ai93_test_assert($pdata === $before,'失声时不扣费也不首演');
unset($pdata['clbpara']['setsuna_mute']);
$pdata['clbpara']['kimi_no_ss']=1; $before=$pdata;
sing('一剪梅'); ai93_test_assert($pdata === $before,'歌魂槽废除时不能演出');

ai93_test_reset();
$echo=song_echo_place('Alicemagic',30,1);
ai93_test_assert($echo['expire'] === $now+300 && $echo['serial'] === 1,'普通残响持续300秒');
$gamevars=json_decode($saved_gamevars,true);
$pdata['ss']=$pdata['mss']=0; $pdata['clbpara']=json_encode($pdata['clbpara']);
move_search_events($pdata,'search'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
move_search_events($pdata,'search');
ai93_test_assert($pdata['ss'] === 3 && $pdata['mss'] === 3,'真实探索钩子领取下限3，JSON及重载后重复探索不再领取');
$other=ai93_test_player(2); $other['ss']=0;
song_echo_claim($other,1);
ai93_test_assert($other['ss'] === 3,'不同玩家可各领一次同一残响');
$other['pls']=2; $other['ss']=0; song_echo_claim($other,2);
ai93_test_assert($other['ss'] === 0,'不同地点不能领地点残响');
$e2=song_echo_place('Alicemagic',30,1);
$gamevars=json_decode($saved_gamevars,true);
$e3=song_echo_place('Alicemagic',30,1); $e4=song_echo_place('Alicemagic',30,1);
ai93_test_assert($e2['power'] === 45 && $e3['power'] === 67 && $e4['power'] === 67 && $e4['resonance'] === 2 && $e4['expire'] === $now+600,'共鸣1.5倍、2.25倍封顶，时长相应延长');
song_echo_claim($pdata,1);
ai93_test_assert($pdata['ss'] === 10,'新残响序号允许再次领取');
$cover=song_echo_place('Crow Song',900,1); song_echo_claim($pdata,1);
ai93_test_assert(count($gamevars['songecho']) === 1 && $cover['resonance'] === 0 && $pdata['ss'] === 25,'异歌覆盖同地点且不继承共鸣，单次上限15');
$world=song_echo_place('一剪梅',233,1,true); song_echo_claim($other,2);
ai93_test_assert($other['ss'] === 30 && $other['mss'] >= $other['ss'],'雪中全岛残响在远方领取30歌魂');
player_save($other); $other=fetch_playerdata_by_pid(2); song_echo_claim($other,3);
ai93_test_assert($other['ss'] === 30,'全岛残响换地点和重载均不能重复领取');
$world=song_echo_place('棺材舞',233,1,true);
ai93_test_assert($gamevars['worldecho']['song'] === '棺材舞' && empty($world['double']),'后演出覆盖全岛残响，不继承雪中翻倍');
$p=ai93_test_player(3); $p['clbpara']['kimi_no_ss']=1; $before=$p; song_echo_claim($p,1);
ai93_test_assert($p === $before,'废除歌魂槽者不领残响也不记领取');
$npc=ai93_test_npc(7); $before=$npc; song_echo_claim($npc,1);
ai93_test_assert($npc === $before,'NPC不领歌魂');
$p=ai93_test_player(4); $p['hp']=0; $before=$p; song_echo_claim($p,1);
ai93_test_assert($p === $before,'死者不领歌魂');
$now+=300; song_echo_claim($pdata,1);
ai93_test_assert(empty($gamevars['songecho']) && !isset($gamevars['worldecho']),'到期边界清理地点与全岛残响');
$fresh=song_echo_place('Crow Song',30,1);
ai93_test_assert($fresh['power'] === 30 && $fresh['serial'] > $world['serial'],'到期后不共鸣，序号不重用');

ai93_test_reset(); $npc=ai93_test_npc(7); player_save($npc);
ai93_sing($npc,$pdata);
ai93_test_assert($gamevars['songecho'][1]['power'] === 60 && end($test_news)[1] === 'song','偶像演唱留下60强度残响并保留地点新闻');
$now=mktime(18,0,0,10,29,2026); ai93_test_roll(0,24); ai93_sing($npc,$pdata);
ai93_test_assert($gamevars['songecho'][1]['song'] === '鸡肉之歌' && $gamevars['songecho'][1]['power'] === 10,'鸡肉彩蛋残响强度10');

ai93_test_reset(); $pdata['ss']=$pdata['mss']=5000; $pdata['clbpara']['showcase']=1;
sing('一剪梅'); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert($weather === 7 && !empty($pdata['clbpara']['showcase_sung']['一剪梅']) && !empty($gamevars['worldecho']['double']),'一剪梅首唱下雪并留下双倍全岛残响');
$world=$gamevars['worldecho']; $weather=1;
sing('一剪梅');
ai93_test_assert($weather === 1 && $gamevars['worldecho'] === $world && $gamevars['songecho'][1]['song'] === '一剪梅','一剪梅重唱只留地点残响，不再次改天气或世界残响');
$dead=ai93_test_player(2); $dead['hp']=0; $dead['pls']=2; $dead['endtime']=$now-$corpseprotect; player_save($dead);
$protected=ai93_test_player(3); $protected['hp']=0; $protected['pls']=2; $protected['endtime']=$now-$corpseprotect+1; player_save($protected);
$rp_before=$pdata['rp']; sing('棺材舞');
ai93_test_assert($db->players[2]['pls'] === 1 && $db->players[3]['pls'] === 2 && $pdata['rp'] === $rp_before,'棺材舞只搬保护期外尸体，无RP惩罚');
$db->players[2]['pls']=2; sing('棺材舞');
ai93_test_assert($db->players[2]['pls'] === 2,'棺材舞重唱不搬尸体');
sing('Caramelldansen'); $pdata['clbpara']['dance_step']=9; sing('Caramelldansen');
ai93_test_assert($pdata['clbpara']['dance_step'] === 9,'扭腰舞首唱获得10步，重唱不刷新');
// 当前只剩同地活人和死者；空场首演仍会落幕并给一次奖励。
sing('Moonlight Shadow');
ai93_test_assert($pdata['itm0'] === '【歌单】瑞克·拉玛尔' && $pdata['itmk0'] === 'Z' && $pdata['itmpara0'] === '' && !empty($pdata['clbpara']['showcase_done']),'四场演完在手中发正确类别歌单');
include config('audio'); include_once GAME_ROOT.'include/game/item.nachster_booster.php';
item_nachster_booster(0,$pdata); player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
ai93_test_assert(!$pdata['itms0'] && $pdata['clbpara']['BGMBrand'] === 'rixolamal' && $pdata['clbpara']['traitorRoll'] === 0
	&& $pdata['clbpara']['event_bgmbook'] === $event_bgm['rixolamaltracks'],'奖励歌单沿用原道具处理启用骰子反叛播放列表');
player_save($pdata); $pdata=fetch_playerdata_by_pid(1); ai93_test_item($pdata,'原有物品','Y',1,1,0);
sing('Moonlight Shadow');
ai93_test_assert($pdata['itm0'] === '原有物品' && !$pdata['itms1'] && !$db->mapitem,'重载重唱不覆盖手中物品、不重复发奖');
foreach(array(3,-1) as $empty_slot) {
	ai93_test_reset(); $pdata['clbpara']['showcase_sung']=array_fill_keys(song_showcase_songs(),1);
	for($i=0;$i<=6;$i++) ai93_test_item($pdata,'保留'.$i,'Y',3,2,$i);
	if($empty_slot >= 0) { $pdata['itms'.$empty_slot]=0; $pdata['itmpara'.$empty_slot]='旧参数'; }
	song_showcase_reward($pdata); player_save($pdata); $pdata=fetch_playerdata_by_pid(1); song_showcase_reward($pdata);
	if($empty_slot >= 0) ai93_test_assert($pdata['itm3'] === '【歌单】瑞克·拉玛尔' && $pdata['itmpara3'] === '' && !$db->mapitem,'手中占用时奖励进空包格，清全字段');
	else ai93_test_assert(count($db->mapitem) === 1 && reset($db->mapitem)['itmk'] === 'Z' && reset($db->mapitem)['pls'] === 1 && $pdata['itm0'] === '保留0','满手满包时歌单只落地一次');
}

// 六连射从异地活人/NPC中选一个，保留装备，不启动正常战斗计数。
foreach(array(0,93) as $target_type) {
	ai93_test_reset(); $target=ai93_test_player(2); $target['pls']=2; $target['type']=$target_type; player_save($target);
	$same=ai93_test_player(3); player_save($same);
	$dead=ai93_test_player(4); $dead['hp']=0; $dead['pls']=2; player_save($dead);
	$before=$pdata; $test_logs=array(); $hit=song_shadow_volley($pdata);
	ai93_test_assert($pdata === $before && $db->players[2]['hp'] < 2000 && $db->players[3]['hp'] === 2000 && $db->players[4]['hp'] === 0
		&& count(explode('、',$hit)) === 6 && end($test_news)[1] === 'songshot' && end($test_logs)[0] === 2,'六次射系伤害仅命中异地存活目标，新闻及受害者日志 '.$target_type);
}
ai93_test_reset(); $target=ai93_test_player(2); $target['pls']=2; $target['hp']=1; player_save($target);
$deaths_before=$deathnum; song_shadow_volley($pdata); $target=fetch_playerdata_by_pid(2);
ai93_test_assert($target['hp'] === 0 && $target['bid'] === 1 && $target['endtime'] === $now && $target['deathtime'] === $now && $pdata['killnum'] === 0 && $deathnum === $deaths_before+1,'致死枪声保存死者及来源，不增加歌手击杀数');
ai93_test_reset(); $target=ai93_test_player(2); $target['pls']=2; $target['hp']=1;
$target['clbpara']['songbook']=array('Alicemagic'=>1); $target['clbpara']['kimi_no_ss']=1; player_save($target);
song_shadow_volley($pdata); $target=fetch_playerdata_by_pid(2);
ai93_test_assert($target['hp'] === 1 && !empty($target['clbpara']['kimi_no_hp']) && isset($target['clbpara']['songbook']['Alicemagic']),'六连射保留归处免死语义和歌簿');
unset($target['clbpara']['kimi_no_ss'],$target['clbpara']['kimi_no_hp']);
death('event','',0,'测试',$target); player_save($target); $target=fetch_playerdata_by_pid(2);
ai93_test_assert(isset($target['clbpara']['songbook']['Alicemagic']),'事件死亡保存后歌簿仍在');

// 舞步走真实移动入口，只有成功移动消耗次数，探索仍可正常领取残响。
ai93_test_reset(); $pdata['clbpara']['dance_step']=10; $pdata['pass']='bot';
$gamevars['sanmadead']=1; $event_obbs=$item_obbs=$enemy_obbs=$trap_max_obbs=0; $pls_bgm=array();
$ai93cfg['event_rate']=0; $saved_areas=$arealist; $saved_areanum=$areanum;
$arealist=array_keys($plsinfo); sort($arealist); $areanum=0;
$before=$pdata; move(0,$pdata);
ai93_test_assert($pdata === $before,'移动到禁区失败不扣舞步');
for($i=10;$i>0;$i--) {
	$destination=$pdata['pls'] == 2 ? 3 : 2;
	move($destination,$pdata);
	ai93_test_assert($pdata['pls'] != $destination && $pdata['pls'] != 0 && ($pdata['clbpara']['dance_step'] ?? 0) === $i-1,'成功移动偏移并扣减舞步 '.$i);
	player_save($pdata); $pdata=fetch_playerdata_by_pid(1);
}
$destination=$pdata['pls'] == 2 ? 3 : 2; move($destination,$pdata);
ai93_test_assert($pdata['pls'] == $destination && !isset($pdata['clbpara']['dance_step']),'十步用尽后恢复正常移动');
$pdata['clbpara']['dance_step']=2; search($pdata);
ai93_test_assert($pdata['clbpara']['dance_step'] === 2,'原地探索不扣舞步');
$hidden_before=$hplsinfo; $hplsinfo[999]=array(900=>'测试甲',901=>'测试乙',902=>'测试丙');
$pdata['pls']=900; $pdata['pgroup']=999;
ai93_test_assert(song_dance_drift(901,$pdata) === 902,'隐藏地图偏移不离开当前地图组');
$hplsinfo[999]=array(900=>'测试甲',901=>'测试乙');
ai93_test_assert(song_dance_drift(901,$pdata) === 901,'唯一合法落点不强行跨组或踏入禁区');
$hplsinfo=$hidden_before; $arealist=$saved_areas; $areanum=$saved_areanum;

// 真正执行开局物品投放，只写内存数据库；不是检查资源文件中的字符串。
ai93_test_reset(); $areanum=0; rs_game(16);
$new_songs=array_slice(song_costs(),4,15,true);
$new_songs['春日影']=song_costs()['春日影'];
foreach($new_songs as $song=>$cost) {
	$cards=array_filter($db->mapitem,function($item) use($song) { return $item['itm'] === '【'.$song.'】'; });
	ai93_test_assert(count($cards) === 7 && count(array_unique(array_column($cards,'pls'))) > 1,'开局随机投放七张歌词卡 '.$song);
	$valid_cards=array_filter($cards,function($card) use($cost) { return $card['itmk'] === 'ss' && $card['itme'] == $cost && $card['itms'] == 1 && $card['itmpara'] === ''; });
	ai93_test_assert(count($valid_cards) === 7,'歌词卡完整字段 '.$song);
}

// 配置默认值与可调参数都走实际读取；其余默认行为已由上面的既有用例覆盖。
unset($ai93cfg);
ai93_test_assert(ai93_cfg('event_rate') === 20 && ai93_cfg('sing_max') === 4 && ai93_cfg('migrate_max') === 2,'频率配置按原默认值读取');
ai93_test_reset(); $ai93cfg['sing_max']=1; $ai93cfg['sing_cooldown']=0; $npc=ai93_test_npc(7);
ai93_test_assert(ai93_sing($npc,$pdata) && !ai93_sing($npc,$pdata),'调整演唱配置即影响上限');
$ai93cfg['trash_max']=0;
ai93_test_assert(!ai93_drop(1,'测试垃圾') && !$db->mapitem,'调整投放配置即影响上限');
unset($ai93cfg);

echo "All AI93, song and restored item tests passed.\n";
