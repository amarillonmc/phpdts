<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','game');
error_reporting(E_ALL);

// 真实技能、物品、成就和战斗函数；仅数据库、画面和站点外围使用桩，不读取部署配置。
function c25c_assert($ok,$message) {
	if(!$ok) { fwrite(STDERR,"FAIL: $message\n"); exit(1); }
	echo "PASS: $message\n";
}
set_error_handler(function($level,$message,$file,$line) {
	if($level === E_NOTICE && strpos(basename($file),'club25') !== 0) return true;
	throw new ErrorException($message,0,$level,$file,$line);
});
function config($file,$version=1) { return GAME_ROOT.'gamedata/cache/'.$file.'_'.$version.'.php'; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function &get_itmpara($value) { $value=get_clbpara($value); return $value; }
function get_itmsk_array($value) { return is_array($value) ? $value : preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY); }
if(!function_exists('diceroll')) {
	function diceroll($max) { global $test_dice,$dice_calls; $dice_calls[] = $max; return min($max,$test_dice); }
}
function addnews(...$args) {}
function logsave(...$args) {}
function save_combatinfo() {}
function save_gameinfo() {}
function init_battle_rev($pa,$pd,$active) {}
function npc_chat_rev($pa,$pd,$event) { return ''; }
function npc_changewep_rev(&$pa,&$pd,$active) {}
function addnoise(...$args) {}
function template($name) { return config('club25cfg'); } // 画面桩：只返回数据，不输出HTML
function check_add_searchmemory(...$args) {}
function lost_searchmemory(...$args) {}
function parse_nameinfo_desc($name,$horizon) { return $name; }
function parse_kinfo_desc($kind,$attrs) { return $kind; }
function parse_skinfo_desc($attrs,$kind) { return $attrs; }
function ruleset_get_fix_damage_hook(&$pa,&$pd,$active) { global $test_fixed_damage; return $test_fixed_damage; }
function ruleset_revive_process_hook(&$pa,&$pd,$active) {
	global $test_revival,$test_last_kill_damage;
	$test_last_kill_damage=$pa['final_damage'] ?? 0;
	if(!$test_revival) return NULL;
	$pd['hp'] = 25; $pd['state'] = 0; return 1;
}
function ruleset_move_allowed_hook($data,$place) { global $test_locked; return !in_array($place,$test_locked); }
function ruleset_ai_event_hook($rate,&$data) { global $ai_calls; $ai_calls++; return true; }
function get_current_ruleset_id() { return ''; }
function get_safe_plslist() { return Array(1,2); }
function fetch_userdata_by_username($name) { global $db; return $db->users[$name]; }
function fetch_playerdata_by_pid($pid) {
	global $db;
	if(!isset($db->players[$pid])) return NULL;
	$row = $db->players[$pid]; $row['clbpara'] = get_clbpara($row['clbpara']); return $row;
}
function player_save($data) {
	global $db;
	$row = array_intersect_key($data,c25c_player(0));
	$row['clbpara'] = json_encode($data['clbpara'],JSON_UNESCAPED_UNICODE);
	$db->players[$data['pid']] = $row;
}
class C25CombatResult {
	public $rows, $offset = 0;
	function __construct($rows) { $this->rows = array_values($rows); }
}
class C25CombatDb {
	public $players = Array(), $users = Array(), $shopitem = Array(), $chat = Array(), $mapitem = Array(), $maptrap = Array(), $affected = 0, $queries = Array();
	function query($sql) {
		$this->queries[] = $sql; $this->affected = 0;
		if(preg_match("/^SELECT \\* FROM test_(mapitem|maptrap)(?: WHERE (iid|tid)='(\\d+)')?/",$sql,$m)) {
			$rows=$this->{$m[1]};
			if(isset($m[3])) $rows=isset($rows[$m[3]]) ? Array($rows[$m[3]]) : Array();
			return new C25CombatResult($rows);
		}
		if(preg_match("/^DELETE FROM test_(mapitem|maptrap) WHERE (iid|tid)='(\\d+)'/",$sql,$m)) {
			$this->affected=isset($this->{$m[1]}[$m[3]]) ? 1 : 0;
			unset($this->{$m[1]}[$m[3]]); return true;
		}
		if(preg_match("/^SELECT \\* FROM test_players WHERE pid='(\\d+)'/",$sql,$m)) return new C25CombatResult(isset($this->players[$m[1]]) ? Array($this->players[$m[1]]) : Array());
		if(preg_match("/^SELECT \\* FROM test_players WHERE pls='(\\d+)' AND pid!='(\\d+)'/",$sql,$m)) return new C25CombatResult(array_filter($this->players,function($row) use($m){return $row['pls']==$m[1] && $row['pid']!=$m[2];}));
		if(preg_match("/^SELECT (achrev|nicksrev|\*) FROM test_users WHERE username\s*=\s*'([^']+)'/",$sql,$m)) {
			$row = $this->users[$m[2]] ?? Array('achrev'=>'','nicksrev'=>'');
			return new C25CombatResult(Array($m[1] === '*' ? $row : Array($m[1]=>$row[$m[1]])));
		}
		if(preg_match("/^UPDATE test_users SET (achrev|nicksrev)='(.*)' WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] = $m[2]; return true;
		}
		if(preg_match("/^UPDATE test_users SET (credits2?)=\\1\\+(\d+) WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] = ($this->users[$m[3]][$m[1]] ?? 0) + (int)$m[2]; return true;
		}
		if(preg_match("/^SELECT clbpara FROM test_players WHERE name='([^']+)'/",$sql,$m)) {
			return new C25CombatResult(array_filter($this->players,function($r) use($m) { return $r['name'] === $m[1]; }));
		}
		return new C25CombatResult(Array()); // 不参与断言的旧成就、新闻等查询
	}
	function array_insert($table,$data) {
		$table = substr($table,5); $id=empty($this->{$table}) ? 1 : max(array_keys($this->{$table}))+1;
		if($table==='maptrap' || $table==='mapitem') $data[$table==='maptrap'?'tid':'iid']=$id;
		$this->{$table}[$id] = $data;
	}
	function affected_rows() { return $this->affected; }
	function result($r,$index=0) { return isset($r->rows[$index]) ? reset($r->rows[$index]) : ''; }
	function fetch_array($r) { return $r->rows[$r->offset++] ?? false; }
	function num_rows($r) { return count($r->rows); }
	function data_seek($r,$offset) { $r->offset = $offset; }
	function free_result($r) {}
	function insert_id() { return count($this->mapitem); }
}
function c25c_player($pid,$club=0) {
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
	if($club) updateskill($data);
	return $data;
}
$db = new C25CombatDb(); $tablepre = $gtablepre = 'test_';
$now = 1000; $log = ''; $club = 0; $gamecfg = 1; $weather = 0;
$hdamage = 9999999; $hplayer = ''; $gamevars = Array(); $gamestate = 20;
$alivenum = 10; $deathnum = 0; $test_dice = 1; $dice_calls = Array();
$test_fixed_damage = NULL; $test_revival = false; $test_locked = Array();
include GAME_ROOT.'include/game/revclubskills.func.php';
include GAME_ROOT.'include/game/clubslct.func.php';
include GAME_ROOT.'include/game/item.club_card.php';
include GAME_ROOT.'include/game/revcombat.func.php';
include GAME_ROOT.'include/game/titles.func.php';
include GAME_ROOT.'include/game/achievement.func.php';
include GAME_ROOT.'include/game/special.func.php';
include GAME_ROOT.'include/game/item.weapon_mod.php';
include GAME_ROOT.'include/game/itemmix.func.php';
include GAME_ROOT.'include/game/search.func.php';
include GAME_ROOT.'include/game/item.weapon.php';
include GAME_ROOT.'include/game/item.recovery.php';
include config('resources'); include config('gamecfg'); include config('combatcfg');
include config('clubskills'); include config('titles'); include config('achievement'); include config('audio');
$baseexp = 10000; $chase_obbs = $dfight_obbs = 0;

$ai_calls=0;
$test_fixed_damage=100; $test_dice=1;
$a=c25c_player(1,25); $b=c25c_player(2); $b['tactic']=1;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c25c_assert($b['hp']==1970 && $a['wg']==101 && $a['exp']==1,'普通固定伤害保留30%，正常经验和武器熟练不受罚');
$a=c25c_player(1); $b=c25c_player(2); $b['tactic']=1;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c25c_assert($b['hp']==1900,'其他社团普通固定伤害保持原值');
$test_fixed_damage=NULL;
$a=c25c_player(1); $b=c25c_player(2); $a['wepsk']='p'; $b['tactic']=1;
mt_srand(31); \revcombat\rev_combat_prepare($a,$b,1,'',0); $ordinary=2000-$b['hp'];
$a=c25c_player(1,25); $b=c25c_player(2); $a['wepsk']='p'; $b['tactic']=1;
mt_srand(31); \revcombat\rev_combat_prepare($a,$b,1,'',0);
c25c_assert($b['hp']==2000-round($ordinary*0.3),'正面攻击的物理与毒属性总伤害一同削减');

function c25c_mine($id,$owner=1,$effect=200,$bonus=40) {
	return Array('tid'=>$id,'pls'=>1,'itm'=>'测试小阔剑','itmk'=>'TO','itme'=>$effect,'itms'=>1,'itmsk'=>$owner,
		'itmpara'=>json_encode(Array('c25_mine'=>Array('owner'=>$owner,'bonus'=>$bonus,'copy'=>0,'pgroup'=>0))));
}
$p=c25c_player(2); $p['pose']=0; $p['tactic']=0; $trap_min_obbs=1;
$cat=c25c_mine(1); $special=c25c_mine(2); $special['itmk']='TO8'; $special['itmpara']='';
c25c_assert(club25_pick_trap($p,Array($cat,$special),14,40)['tid']===1,'15%补足区间只选普通猫雷，不抬升比例特殊雷');
c25c_assert(club25_pick_trap($p,Array($cat,$special),15,40)===false,'接触率边界为15%，不额外叠加一轮判定');
c25c_assert(club25_pick_trap($p,Array_fill(0,20,$cat),16,40)===false,'多份猫雷不叠加每份15%概率');
$foreign=$cat; $foreign['itmpara']=json_encode(Array('c25_mine'=>Array('owner'=>1,'pgroup'=>1)));
c25c_assert(club25_pick_trap($p,Array($foreign),1,40)===false,'同编号地图的其他组猫雷不能触发');
foreach(Array('itm','itmk','itme','itms','itmsk','itmpara') as $field) $p[$field.'0']=$cat[$field];
$p['tactic']=2; mt_srand(9); $withbonus=calc_trap_damage($p); $p['itmpara0']=''; mt_srand(9); $without=calc_trap_damage($p);
c25c_assert($withbonus-$without==30,'布雷爆熟加成在防御策略减伤之前结算');

$test_dice=99; $owner=c25c_player(1,25); player_save($owner);
$p=c25c_player(2,25); $p['tactic']=0;
foreach(Array('itm','itmk','itme','itms','itmsk','itmpara') as $field) $p[$field.'0']=$cat[$field];
$expected=$p; mt_srand(12); $damage=calc_trap_damage($expected); mt_srand(12); trap($p);
$saved=fetch_playerdata_by_pid(1);
c25c_assert($p['hp']==2000-round($damage/2),'实际陷阱流程对可怜猫减半伤害');
c25c_assert($saved['wd']==102 && $saved['exp']==1 && !empty($saved['clbpara']['achvars']['c25_trap_hit']),'非致死陷阱成长与成就保存到真实制作者');

$db->maptrap=Array(1=>$cat); $db->mapitem=Array();
$a=c25c_player(1,25); $a['c25_net_input']='t1'; $b=c25c_player(2); $b['tactic']=1; $b['wepk']='WK';
$test_fixed_damage=100; $test_dice=50; $before=$a['weps'];
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert(!$db->maptrap && $b['hp']<2000,'收网从真实战斗入口消耗一份地雷并伤害对方');
c25c_assert($a['hp']==1900 && $a['weps']==$before && $a['wg']==100,'收网正常承受近距反击且不消耗普通武器耐久或加普通熟练');
c25c_assert($a['exp']==1 && $a['wd']==102,'主动收网只发一份经验与爆熟，不重复给自有机关奖励');
$a['c25_net_input']='t1'; $before_exp=$a['exp']; $before_wd=$a['wd'];
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert($a['exp']==$before_exp && $a['wd']==$before_wd,'重复收网已消费地雷不能再次取得成长');

$maker=c25c_player(3,25); player_save($maker);
$db->maptrap=Array(1=>c25c_mine(1,3,1000));
$a=c25c_player(1,25); $a['c25_net_input']='t1'; $b=c25c_player(2); $b['hp']=10; $test_dice=50;
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0); $maker=fetch_playerdata_by_pid(3);
c25c_assert($b['hp']==0 && $b['bid']==3 && $maker['killnum']==1 && $a['killnum']==0,'借用他人机关致死归原作者，双方保存不覆盖作者击杀');
c25c_assert($maker['exp']==0 && $maker['wd']==100 && $a['exp']==1 && $a['wd']==102,'借用收网不向原作者重复发行动成长');
c25c_assert(!empty($maker['clbpara']['achvars']['c25_trap_hit']),'借用机关仍为原作者登记实际玩家命中成就');

$db->maptrap=Array(1=>c25c_mine(1,1,1000)); $test_revival=true;
$a=c25c_player(1,25); $a['c25_net_input']='t1'; $b=c25c_player(2); $b['hp']=10;
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert($b['hp']==25 && $b['state']==0 && $a['hp']==2000 && $a['killnum']==0,'机关致死沿用复活结算且结束本次战斗，不重复扣血反击'); $test_revival=false;

$db->maptrap=Array(); $db->mapitem=Array(1=>Array('iid'=>1,'pls'=>1,'itm'=>'藏猫补给','itmk'=>'HB','itme'=>70,'itms'=>3,'itmsk'=>'','itmpara'=>json_encode(Array('c25_hide'=>Array('owner'=>1,'damage'=>300)))));
$a=c25c_player(1,25); $a['c25_net_input']='i1'; $b=c25c_player(2); $b['type']=1; $b['tactic']=1; $b['wepk']='WK';
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
$remaining=reset($db->mapitem);
c25c_assert($b['hp']==1770 && $remaining['itms']==2 && empty(get_itmpara($remaining['itmpara'])['c25_hide']),'诱食NPC先咬后正常回复，消耗一份并清除整堆幻影');
$a=c25c_player(1,25); $b=c25c_player(2); $original=$db->mapitem;
c25c_assert(club25_net_take($a,$b,'i1')===false && $db->mapitem===$original,'不允许对玩家强行收网投喂');

$food=Array('iid'=>1,'pls'=>1,'itm'=>'有毒补给','itmk'=>'PH','itme'=>70,'itms'=>1,'itmsk'=>1,'itmpara'=>'');
$db->mapitem=Array(1=>$food); $a=c25c_player(1,25); $a['c25_net_input']='i1'; $b=c25c_player(2); $b['type']=1; $b['arbs']=10; $b['arbsk']='q';
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert(strpos($b['inf'],'p')===false && $b['hp']==1930,'抗毒阻挡收网造成的新中毒，食物直接毒伤仍独立结算');
$db->mapitem=Array(1=>$food); $a=c25c_player(1,25); $a['c25_net_input']='i1'; $b=c25c_player(2); $b['type']=1; getclubskill('buff_shield',$b['clbpara']);
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert(strpos($b['inf'],'p')===false,'异常护盾仍能阻挡收网食物附加中毒');

$a=c25c_player(1,25); $a['action']='enemy'; $a['bid']=2; $pdata=&$a; $b=c25c_player(2); player_save($b); $db->maptrap=Array(1=>$cat); $db->mapitem=Array(); $c25_net_item=Array('t1');
\revbattle\revbattle_prepare('bskill_c25_net');
c25c_assert(count($db->maptrap)==1 && $a['hp']==2000,'恶意数组输入被实际战斗指令入口拒绝且不消耗地雷');
$c25_net_item='t999'; \revbattle\revbattle_prepare('bskill_c25_net');
c25c_assert(count($db->maptrap)==1 && $a['hp']==2000,'不存在的收网编号在开战前拒绝，不招致反击');
unset($pdata); unset($GLOBALS['pdata']);

$p=c25c_player(2,25); $p['inf']='p'; $p['hp']=1; $p['clbpara']['c25_poison_owner']=3; $maker=c25c_player(3,25); player_save($maker);
move_search_events($p,'search'); $maker=fetch_playerdata_by_pid(3);
c25c_assert($p['hp']==0 && $p['bid']==3 && $p['state']==12 && $maker['killnum']==1,'后续行动毒伤保留物品毒来源与原有毒发死法');
c25c_assert($test_last_kill_damage>=125 && $test_last_kill_damage<=135,'毒发击杀事件取得当前行动毒伤数值');
$p=c25c_player(2); $p['clbpara']['c25_poison_owner']=3; \revattr\get_inf_rev($p,'p');
c25c_assert(empty($p['clbpara']['c25_poison_owner']),'净化后由其他攻击重新中毒不继承旧物品来源');

$test_fixed_damage=NULL; $test_dice=14; $event_obbs=-1; $trap_max_obbs=40; $enemy_obbs=1000; $corpse_obbs=-1; $item_obbs=1000;
$p=c25c_player(1,25); $p['clbpara']['c25_catstep']=1; $enemy=c25c_player(2); player_save($enemy); $db->players=Array(2=>$db->players[2]); $db->maptrap=Array(1=>$cat); $db->mapitem=Array();
$pdata=&$p; $ai_calls=0; discover(100,$p,true);
c25c_assert($p['hp']==2000 && !$p['action'] && count($db->maptrap)==1 && $ai_calls===0,'受猫步保护的探索阶段跳过AI、活人和地雷');
$corpse=c25c_player(2); $corpse['hp']=0; player_save($corpse); discover(100,$p,true);
c25c_assert($p['action']==='corpse' && $p['bid']==2,'猫步仍可发现尸体');
$p['action']=''; $db->players=Array(); $db->mapitem=Array(1=>Array('iid'=>1,'pls'=>1,'itm'=>'普通补给','itmk'=>'HH','itme'=>70,'itms'=>1,'itmsk'=>'','itmpara'=>''));
discover(0,$p,true); c25c_assert($p['itm0']==='普通补给' && !$db->mapitem,'猫步仍可发现地面物品');
$p['itm0']=''; $p['itms0']=0; $p['action']=''; $p['clbpara']['c25_catstep']=1; $p['pose']=0; $p['tactic']=0;
$saved_places=$plsinfo; $plsinfo=Array(0=>'禁区',1=>'当前',2=>'可移动'); $hplsinfo=Array(); $arealist=Array(0,1,2); $areanum=0; $hack=0; $areainfo=Array('','','');
move(1,$p); c25c_assert(!empty($p['clbpara']['c25_catstep']),'失败的原地移动不消耗猫步');
$p['hp']=1; $p['sp']=0; move(2,$p); c25c_assert($p['pls']==1 && !empty($p['clbpara']['c25_catstep']),'命体不足的失败移动也保留猫步');
$p['hp']=2000; $p['sp']=1000; $p['inf']='p'; $hp_before=$p['hp']; move(2,$p);
c25c_assert($p['pls']==2 && empty($p['clbpara']['c25_catstep']) && $p['hp']<$hp_before,'成功移动只消耗一次猫步，行动中毒依然扣血');
$p['pls']=1; $p['inf']=''; $p['clbpara']['c25_catstep']=1; $db->maptrap=Array(1=>$cat); $ai_before=$ai_calls;
search($p); c25c_assert(!$db->maptrap && !empty($p['clbpara']['c25_catstep']) && $ai_calls>$ai_before,'原地搜索照常进行陷阱和AI判定，也不消费等待下次移动的猫步');
$plsinfo=$saved_places;

$groomid=0; $db->maptrap=Array(1=>$cat); $db->mapitem=Array(); $test_dice=50;
$a=c25c_player(1,25); $a['c25_net_input']='t1'; $b=c25c_player(2); $b['type']=93; $b['name']=get_addnpcinfo()[93]['sub'][20]['name']; $b['wepsk']='e';
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c25_net',0);
c25c_assert($b['hp']<2000 && $b['pls']==2 && $a['hp']==2000 && $db->players[2]['pls']==2,'非致死收网仍触发高级小兵打带跑并保存其移动位置');
unset($groomid);

echo "All club25 combat tests passed.\n";

