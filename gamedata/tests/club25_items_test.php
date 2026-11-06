<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','game');
error_reporting(E_ALL);
// 使用真实物品、伤害、奖励与死亡函数；数据库与站点外围使用桩。
function c25i_assert($ok,$message) {
	if(!$ok) { fwrite(STDERR,"FAIL: $message\n"); exit(1); }
	echo "PASS: $message\n";
}
set_error_handler(function($level,$message,$file,$line) {
	if($level === E_NOTICE && basename($file) !== 'club25.items.php') return true;
	throw new ErrorException($message,0,$level,$file,$line);
});
function config($file,$version=1) { return GAME_ROOT.'gamedata/cache/'.$file.'_'.$version.'.php'; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function &get_itmpara($value) { $value=get_clbpara($value); return $value; }
function get_itmsk_array($value) { return is_array($value) ? $value : preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY); }
if(!function_exists('diceroll')) {
	function diceroll($max) { global $test_dice,$dice_calls; $dice_calls[] = $max; return min($max,$test_dice); }
}
function addnews(...$args) { $GLOBALS['test_news'][] = $args; }
function logsave(...$args) { $GLOBALS['test_notices'][] = $args; }
function save_gameinfo() {}
function npc_chat_rev($pa,$pd,$event) { return ''; }
function template($name) { return config('club24cfg'); } // 画面桩：只返回数据，不输出HTML
function check_add_searchmemory(...$args) {}
function json_encode_comp($value) { return json_encode($value,JSON_UNESCAPED_UNICODE); }
function create_dummy_playerdata($club=0) { return c25i_player(0,$club); }
function parse_nameinfo_desc($name,$horizon) { return $name; }
function parse_kinfo_desc($kind,$attrs) { return $kind; }
function parse_skinfo_desc($attrs,$kind) { return $attrs; }
function ruleset_damage_immunity_hook(&$data,$kind,$damage) { return $GLOBALS['test_immunity']; }
function ruleset_revive_process_hook(&$pa,&$pd,$active) {
	global $test_revival;
	if(!$test_revival) return NULL;
	$pd['hp'] = 25; $pd['state'] = 0; return 1;
}
function fetch_userdata_by_username($name) { global $db; return $db->users[$name]; }
function fetch_playerdata_by_pid($pid) {
	global $db;
	if(!isset($db->players[$pid])) return NULL;
	$row = $db->players[$pid]; $row['clbpara'] = get_clbpara($row['clbpara']); return $row;
}
function player_save($data) {
	global $db;
	$row = array_intersect_key($data,c25i_player(0));
	$row['clbpara'] = json_encode($data['clbpara'],JSON_UNESCAPED_UNICODE);
	$db->players[$data['pid']] = $row;
}
class C25IResult {
	public $rows, $offset = 0;
	function __construct($rows) { $this->rows = array_values($rows); }
}
class C25IDb {
	public $players = Array(), $users = Array(), $shopitem = Array(), $chat = Array(), $mapitem = Array(), $queries = Array();
	function query($sql) {
		$this->queries[] = $sql;
		if(preg_match("/^SELECT (achrev|nicksrev|\*) FROM test_users WHERE username\s*=\s*'([^']+)'/",$sql,$m)) {
			$row = $this->users[$m[2]] ?? Array('achrev'=>'','nicksrev'=>'');
			return new C25IResult(Array($m[1] === '*' ? $row : Array($m[1]=>$row[$m[1]])));
		}
		if(preg_match("/^UPDATE test_users SET (achrev|nicksrev)='(.*)' WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] = $m[2]; return true;
		}
		if(preg_match("/^UPDATE test_users SET (credits2?)=\\1\\+(\d+) WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] += (int)$m[2]; return true;
		}
		if(preg_match("/^SELECT clbpara FROM test_players WHERE name='([^']+)'/",$sql,$m)) {
			return new C25IResult(array_filter($this->players,function($r) use($m) { return $r['name'] === $m[1]; }));
		}
		if(preg_match("/^SELECT \\* FROM test_players WHERE pid='(\\d+)'/",$sql,$m)) return new C25IResult(isset($this->players[$m[1]]) ? Array($this->players[$m[1]]) : Array());
		return new C25IResult(Array()); // 不参与断言的旧成就、新闻等查询
	}
	function array_insert($table,$data) { $table = substr($table,5); $this->{$table}[] = $data; }
	function result($r,$index=0) { return isset($r->rows[$index]) ? reset($r->rows[$index]) : ''; }
	function fetch_array($r) { return $r->rows[$r->offset++] ?? false; }
	function num_rows($r) { return count($r->rows); }
	function data_seek($r,$offset) { $r->offset = $offset; }
	function free_result($r) {}
	function insert_id() { return count($this->mapitem); }
}
function c25i_player($pid,$club=0) {
	$data = Array(
		'pid'=>$pid,'name'=>'测试者'.$pid,'nm'=>'测试者'.$pid,'pass'=>'test','type'=>0,'club'=>$club,
		'hp'=>2000,'mhp'=>2000,'sp'=>1000,'msp'=>1000,'att'=>100,'def'=>100,'lvl'=>10,'exp'=>0,
		'rage'=>255,'rp'=>0,'pose'=>1,'tactic'=>4,'pls'=>1,'pgroup'=>0,'nick'=>0,'gd'=>'n','icon'=>0,'sNo'=>1,
		'inf'=>'','state'=>0,'action'=>'','bid'=>0,'killnum'=>0,'endtime'=>0,'deathtime'=>0,'money'=>10000,'skillpoint'=>100,'ip'=>'127.0.0.1',
		'wp'=>100,'wk'=>100,'wc'=>100,'wg'=>100,'wd'=>100,'wf'=>100,'teamID'=>'','teamPass'=>'','horizon'=>0,
		'wep'=>'测试武器','wepk'=>'WG','wepe'=>80,'weps'=>30,'wepsk'=>'','weppara'=>'',
		'clbpara'=>Array('skill'=>Array(),'BGMBrand'=>'','traitorRoll'=>0,'achvars'=>Array()),'message'=>'','achievement'=>'',
	);
	foreach(Array('wep2','arb','arh','ara','arf','art') as $slot) {
		$data[$slot] = $data[$slot.'k'] = $data[$slot.'sk'] = $data[$slot.'para'] = '';
		$data[$slot.'e'] = $data[$slot.'s'] = 0;
	}
	for($i=0;$i<=6;$i++) {
		$data['itm'.$i] = $data['itmk'.$i] = $data['itmsk'.$i] = $data['itmpara'.$i] = '';
		$data['itme'.$i] = $data['itms'.$i] = 0;
	}
	
	return $data;
}
function c25i_bind(&$data) {
	$GLOBALS['pdata'] = &$data;
	foreach($data as $key=>&$value) $GLOBALS[$key] = &$value;
}
function c25i_item(&$data,$slot,$name='测试装备',$kind='WK',$effect=100,$durability=20,$attrs='rv') {
	$data['itm'.$slot]=$name; $data['itmk'.$slot]=$kind; $data['itme'.$slot]=$effect;
	$data['itms'.$slot]=$durability; $data['itmsk'.$slot]=$attrs; $data['itmpara'.$slot]='{"marker":1}';
}


$db = new C25IDb(); $tablepre = $gtablepre = 'test_';
$now = 1000; $log = ''; $gamecfg = 1; $weather = 0; $gamestate = 20;
$baseexp = 10000; $gamevars = Array(); $alivenum = 10; $deathnum = 0;
$test_dice = 99; $dice_calls = Array(); $test_revival = false; $test_immunity = NULL;
$test_news = $test_notices = Array();
include GAME_ROOT.'include/game/revclubskills.func.php';
include GAME_ROOT.'include/game/clubslct.func.php';
include GAME_ROOT.'include/game/revcombat.func.php';
include GAME_ROOT.'include/game/item.weapon.php';
include GAME_ROOT.'include/game/item.recovery.php';
include GAME_ROOT.'include/game/item.poison.php';
include GAME_ROOT.'include/game/item.cure.php';
include GAME_ROOT.'include/game/itembag.func.php';
include GAME_ROOT.'include/game/item2.func.php';
include GAME_ROOT.'include/game/titles.func.php';
include_once GAME_ROOT.'include/resources.func.php';
include config('resources'); include config('gamecfg'); include config('combatcfg');
include config('clubskills'); include config('titles'); include config('achievement');
$owner = c25i_player(2,25); player_save($owner);
$hide = Array('owner'=>2,'damage'=>100,'name'=>'测试者2');
$trip = $hide + Array('mode'=>'damage');

$p = c25i_player(1); c25i_bind($p);
c25i_item($p,1,'补给','HH',70,3,'');
$p['itmpara1'] = json_encode(Array('unrelated'=>17,'c25_hide'=>$hide,'c25_trip'=>$trip));
item_recovery_health(1,$p);
c25i_assert($p['hp'] === 2000 && isset(get_itmpara($p['itmpara1'])['c25_hide']) && $p['itms1'] === 3,'满血拒绝补给不会触发或消耗幻影');
$p['hp'] = 500; item_recovery_health(1,$p);
$para = get_itmpara($p['itmpara1']);
c25i_assert($p['hp'] === 470 && $p['itms1'] === 2 && !isset($para['c25_hide']) && isset($para['c25_trip']) && $para['unrelated'] === 17,'首次食用先咬再恢复，只清幻影且保留诡雷与其他参数');
$rewarded = fetch_playerdata_by_pid(2);
c25i_assert($rewarded['wd'] > $owner['wd'] && !empty($rewarded['clbpara']['achvars']['c25_food_hit']),'有效食用伤害持久化来源成长与成就');
item_recovery_health(1,$p);
c25i_assert($p['hp'] === 540 && fetch_playerdata_by_pid(2)['wd'] === $rewarded['wd'],'同一堆再次食用不重复幻影伤害或成长');
player_save($p); $p = fetch_playerdata_by_pid(1); c25i_bind($p);
c25i_assert(!isset(get_itmpara($p['itmpara1'])['c25_hide']),'重新加载后幻影仍已清除');

$p = c25i_player(1); c25i_bind($p);
c25i_item($p,0,'礼物','Y',1,1,'');
$p['itmpara0'] = Array('c25_trip'=>$trip,'c25_hide'=>$hide,'unrelated'=>9);
club25_item_trip($p,0);
c25i_assert($p['hp'] === 2000 && isset($p['itmpara0']['c25_trip']),'仅发现itm0不会触发');
$log=''; $p['ex_keys']=Array('M'); itemfind($p);
c25i_assert(strpos($log,'细线') === false && isset($p['itmpara0']['c25_trip']) && $p['hp'] === 2000,'未实际装备探雷属性时发现诡雷不显示细线，旧战斗缓存不能代替装备');
$log=''; $p['wepsk']='M'; $before_para=$p['itmpara0']; itemfind($p);
c25i_assert(strpos($log,'细线') !== false && $p['itmpara0'] === $before_para && $p['hp'] === 2000,'武器有探雷属性时发现诡雷显示轻提示，不清标也不造成伤害');
$log=''; $p['wepsk']=''; $p['arbs']=10; $p['arbsk']='M'; itemfind($p);
c25i_assert(strpos($log,'细线') !== false && $p['itmpara0'] === $before_para && $p['hp'] === 2000,'防具上的探雷属性同样可见细线');
$p['arbs']=0; $p['arbsk']='';
for($i=1;$i<=6;$i++) c25i_item($p,$i,'占位'.$i);
ob_start(); itemadd($p); ob_end_clean();
c25i_assert($p['hp'] === 2000 && isset($p['itmpara0']['c25_trip']),'满包失败拾取不触发且不清标记');
$p['itms3'] = 0; itemadd($p);
c25i_assert($p['hp'] === 1900 && !isset($p['itmpara3']['c25_trip']) && isset($p['itmpara3']['c25_hide']) && $p['itmpara3']['unrelated'] === 9,'成功入包触发诡雷且与幻影独立');
$rewarded = fetch_playerdata_by_pid(2);
c25i_assert(empty($rewarded['clbpara']['achvars']['c25_trap_hit']),'拾取诡雷不冒充地雷成就');
club25_item_trip($p,3);
c25i_assert($p['hp'] === 1900 && fetch_playerdata_by_pid(2)['wd'] === $rewarded['wd'],'重复拾取结算不会重复伤害或成长');
itemdrop('itm3',$p);
$drop = end($db->mapitem);
c25i_assert(!isset(get_itmpara($drop['itmpara'])['c25_trip']) && isset(get_itmpara($drop['itmpara'])['c25_hide']),'丢弃转手保存已触发状态');

$p = c25i_player(1); c25i_bind($p);
c25i_item($p,0,'测试装备','WK',100,20,'');
$p['itmpara0'] = Array('c25_trip'=>$trip,'unrelated'=>3);
$p['wepsk'] = 'V'; item_weapon(0,$p);
c25i_assert($p['hp'] === 2000 && isset($p['itmpara0']['c25_trip']),'诅咒装备拒绝替换时不触发');
$p['wepsk'] = ''; item_weapon(0,$p);
c25i_assert($p['hp'] === 1900 && $p['weppara']['unrelated'] === 3 && !isset($p['weppara']['c25_trip']),'直接装备itm0触发一次并保留装备参数');

$p = c25i_player(1); c25i_bind($p);
$p['wep']='飞镖'; $p['wepk']='WC'; $p['wepe']=20; $p['weps']=2;
$p['weppara']=Array('c25_trip'=>$trip,'unrelated'=>4);
c25i_item($p,0,'飞镖','WC',20,3,''); $p['itmpara0']=$p['weppara'];
itemget($p);
c25i_assert($p['weps'] === 5 && $p['hp'] === 1900 && !isset($p['weppara']['c25_trip']) && $p['weppara']['unrelated'] === 4,'自动合并到武器后触发诡雷并保留参数');

$p = c25i_player(1); c25i_bind($p);
$p['arb']='旧背包'; $p['arbk']='DB'; $p['arbe']=2; $p['arbs']=2; $p['arbsk']='^'; $p['arbpara']=Array('old'=>1);
$itmnumlimit=2; c25i_item($p,0,'新背包','DB',3,3,'^'); $p['itmpara0']=Array('c25_trip'=>$trip,'new'=>1);
itemget($p); $oldbag=end($db->mapitem);
c25i_assert($p['arb'] === '新背包' && $p['hp'] === 1900 && !isset($p['arbpara']['c25_trip']) && $p['arbpara']['new'] === 1 && get_itmpara($oldbag['itmpara'])['old'] === 1,'自动替换背包触发诡雷，旧包掉落保留para');
$itmnumlimit=3; c25i_item($p,0,'差背包','DB',1,1,'^'); $p['itmpara0']=Array('c25_trip'=>$trip);
itemget($p); $badbag=end($db->mapitem);
c25i_assert($p['hp'] === 1900 && isset(get_itmpara($badbag['itmpara'])['c25_trip']),'拒收较差背包不触发诡雷，丢回地图时机关仍在');

$p = c25i_player(1); c25i_bind($p);
$itembag=$getitem='[]'; $itmnum=0; $itmnumlimit=10; $weight=0; $itmstkinfo=Array('HH'=>3); $itmwtinfo=Array('补给'=>1);
for($i=1;$i<=2;$i++) { c25i_item($p,$i,'补给','HH',70,2,''); $p['itmpara'.$i]=Array('c25_hide'=>$hide); }
item_encase(Array(1,2)); $stored=json_decode($itembag,true);
c25i_assert(count($stored) === 2 && $stored[0]['itms'] === 2 && $stored[1]['itms'] === 2,'扩展背包不能通过合并溢出把一次幻影拆成多次');

$p = c25i_player(1); c25i_bind($p);
c25i_item($p,0,'补给','HH',70,2,''); c25i_item($p,1,'补给','HH',70,2,'');
$p['itmpara0'] = Array('c25_hide'=>$hide); $p['itmpara1'] = '';
itemmerge(0,1);
c25i_assert($p['itms0'] === 2 && $p['itms1'] === 2,'普通补给不能吞并带机关补给');
$p['itmpara1'] = Array('c25_hide'=>$hide,'unrelated'=>1); itemmerge(0,1);
c25i_assert($p['itms0'] === 2 && $p['itms1'] === 2,'不同完整para机关物品拒绝合并');
$p['itmpara0'] = $p['itmpara1'] = Array('c25_hide'=>$hide,'c25_trip'=>$trip);
itemmerge(0,1);
c25i_assert(!$p['itms0'] && $p['itms1'] === 4 && $p['hp'] === 1900 && !isset($p['itmpara1']['c25_trip']) && isset($p['itmpara1']['c25_hide']),'成功拾取合并后仅触发诡雷一次');
c25i_assert(!club25_item_merge_allowed(Array('c25_mine'=>Array('spread'=>1)),Array('c25_mine'=>Array('spread'=>0))),'回收雷的复线状态不能被合并洗掉');

$p = c25i_player(1,25); c25i_bind($p);
club25_item_damage($p,2,100,'food','补给','c25_hide');
c25i_assert($p['hp'] === 1950,'可怜猫受到的直接机关伤害减半');
$p['club'] = 0; $test_immunity = 0; $before = fetch_playerdata_by_pid(2)['wd'];
club25_item_damage($p,2,100,'trip','礼物');
c25i_assert($p['hp'] === 1950 && fetch_playerdata_by_pid(2)['wd'] === $before,'RuleSet伤害免疫不领取伤害成长');
$test_immunity = NULL; $p['clbpara']['skill'][] = 'fireseed4';
club25_item_damage($p,2,100,'food','补给','c25_hide');
c25i_assert($p['hp'] === 1950,'幻影保留种火IV免伤');

$p = c25i_player(1); c25i_bind($p);
c25i_item($p,1,'毒雾礼物','Y',1,2,'');
$p['itmpara1'] = Array('c25_trip'=>$hide + Array('mode'=>'poison'));
club25_item_trip($p,1);
c25i_assert(strpos($p['inf'],'p') !== false && $p['clbpara']['c25_poison_owner'] === 2,'毒雾诡雷记录新中毒的来源');
$p['itmpara1'] = Array('c25_trip'=>Array('owner'=>3,'damage'=>0,'mode'=>'poison'));
club25_item_trip($p,1);
c25i_assert($p['clbpara']['c25_poison_owner'] === 2,'重复中毒不改写已有毒伤归属');
c25i_item($p,2,'解毒剂','Cp',1,1,''); item_cure(2,$p);
c25i_assert(strpos($p['inf'],'p') === false && !isset($p['clbpara']['c25_poison_owner']),'解毒清除毒伤来源');

$p = c25i_player(1,25); c25i_bind($p);
c25i_item($p,1,'毒补给','PH',70,3,'2'); $p['itmpara1'] = Array('unrelated'=>5);
item_poison(1,$p); $after_poison = fetch_playerdata_by_pid(2)['wd']; item_poison(1,$p);
c25i_assert($p['hp'] === 1930 && $p['clbpara']['c25_poison_owner'] === 2 && fetch_playerdata_by_pid(2)['wd'] === $after_poison,'传统毒补给直接伤害减半，同一堆只发一次成长');
$p['art'] = ''; $itmp=2; c25i_item($p,2,'毒药','Y',1,1,'');
poison(1);
c25i_assert(!isset($p['itmpara1']['c25_poison_done']) && $p['itmpara1']['unrelated'] === 5 && !$p['itms2'],'实际消耗新毒药才重置毒物触发奖励');
$p['inf']=''; $p['clbpara']['c25_poison_owner']=2; c25i_item($p,1,'无主毒补给','PH',70,2,''); item_poison(1,$p);
c25i_assert(strpos($p['inf'],'p') !== false && !isset($p['clbpara']['c25_poison_owner']),'新无主中毒不继承残留的旧来源');

$p = c25i_player(1); c25i_bind($p);
$p['hp'] = 30; c25i_item($p,1,'致命补给','HH',70,2,''); $p['itmpara1'] = Array('c25_hide'=>$hide);
$before = fetch_playerdata_by_pid(2);
item_recovery_health(1,$p);
$after = fetch_playerdata_by_pid(2);
c25i_assert($p['hp'] === 0 && $p['state'] === 51 && $p['bid'] === 2 && $p['itms1'] === 1,'幻影咬死时不恢复，使用次数被消耗并记录专属死法与作者');
c25i_assert($after['killnum'] === $before['killnum']+1 && $after['wd'] > $before['wd'],'致死成长与击杀数不会互相覆盖');
c25i_assert(in_array('death51',array_column($test_news,1)),'幻影致死发出death51新闻');
player_save($p); $saved = fetch_playerdata_by_pid(1);
c25i_assert($saved['state'] === 51 && !isset(get_itmpara($saved['itmpara1'])['c25_hide']),'死亡与机关清除结果保存后不会再触发');

$p = c25i_player(1); c25i_bind($p); $p['hp'] = 30; $test_revival=true;
$before = fetch_playerdata_by_pid(2)['killnum'];
c25i_item($p,1,'保命补给','HH',70,2,''); $p['itmpara1'] = Array('c25_hide'=>$hide);
item_recovery_health(1,$p);
c25i_assert($p['hp'] === 95 && $p['state'] === 0 && fetch_playerdata_by_pid(2)['killnum'] === $before,'幻影致命伤遵守RuleSet复活，复活后继续恢复且不记击杀');
$p['hp'] = 30; $p['c25_net_active'] = 1;
$actor = fetch_playerdata_by_pid(2);
club25_item_damage($p,2,100,'food','保命补给','c25_hide',$actor);
c25i_assert($p['hp'] === 25 && !empty($p['c25_item_revived']),'收网致命伤复活后标记结束本次战斗');
$test_revival=false;

$p = c25i_player(1); c25i_bind($p); $p['hp'] = 30;
$p['clbpara']['song_rolling'] = 1;
club25_item_damage($p,2,100,'trip','保命礼物');
c25i_assert($p['hp'] === 1 && empty($p['clbpara']['song_rolling']),'机关致命伤遵守既有翻滚免死');

$p = c25i_player(1); c25i_bind($p); $p['hp'] = 30; $p['skill_c19_nirvana'] = 1;
club25_item_damage($p,1,100,'trip','自己的礼物');
c25i_assert($p['hp'] === 1 && !$p['killnum'],'自伤机关也保留涅槃复活，不增加自己的击杀数');
$p = c25i_player(1); c25i_bind($p); $p['hp'] = 30; $test_revival=true;
club25_item_damage($p,0,100,'food','无主毒补给','poison');
c25i_assert($p['hp'] === 25 && !$p['killnum'],'无主机关伤害保留RuleSet复活');

echo "All club25 item tests passed.\n";

