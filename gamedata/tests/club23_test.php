<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
error_reporting(E_ALL);
ini_set('display_errors','1');

// 独立回归：真实技能/战斗函数，数据库、新闻及战斗画面使用桩；不读取部署配置。
function club23_test_assert($ok,$message)
{
	if(!$ok) { fwrite(STDERR,"FAIL: {$message}\n"); exit(1); }
	echo "PASS: {$message}\n";
}
function config($file,$version=1)
{
	global $test_ruleset;
	$path = GAME_ROOT.'gamedata/ruleset/'.$test_ruleset.'/cache/'.$file.'_'.$version.'.php';
	return $test_ruleset && is_file($path) ? $path : GAME_ROOT.'gamedata/cache/'.$file.'_'.$version.'.php';
}
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function get_itmpara($value) { return get_clbpara($value); }
function get_itmsk_array($value) { return is_array($value) ? $value : preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY); }
function diceroll($max) { global $test_dice; return min($max,$test_dice); }
function addnews(...$args) { global $test_news; $test_news[] = $args; }
function logsave(...$args) { global $test_logs; $test_logs[] = $args; }
function save_combatinfo() {}
function save_gameinfo() {}
function init_battle_rev($pa,$pd,$active) {}
function npc_chat_rev($pa,$pd,$event) { return ''; }
function npc_changewep_rev(&$pa,&$pd,$active) {}
function addnoise(...$args) {}
function ruleset_get_fix_damage_hook(&$pa,&$pd,$active) { global $test_fixed_damage; return $test_fixed_damage; }
function ruleset_revive_process_hook(&$pa,&$pd,$active)
{
	global $test_revival;
	if(!$test_revival) return NULL;
	$pd['hp'] = 25; $pd['state'] = 0;
	return 1;
}
class Club23TestDb
{
	public $players = Array();
	public $queries = Array();
	function query($sql) { $this->queries[] = $sql; return Array(); }
	function result($result,$index=0) { return ''; }
	function fetch_array($result) { return Array(); }
	function num_rows($result) { return 0; }
}
function player_save($data)
{
	global $db;
	$row = array_intersect_key($data,club23_test_player(0));
	$row['clbpara'] = json_encode($data['clbpara'],JSON_UNESCAPED_UNICODE);
	$db->players[$data['pid']] = $row;
}
function club23_test_player($pid,$club=0)
{
	$data = Array(
		'pid'=>$pid,'name'=>'测试者'.$pid,'nm'=>'测试者'.$pid,'pass'=>'test','type'=>0,'club'=>$club,
		'hp'=>2000,'mhp'=>2000,'sp'=>1000,'msp'=>1000,'att'=>100,'def'=>100,'lvl'=>10,'exp'=>0,
		'rage'=>0,'rp'=>0,'pose'=>1,'tactic'=>4,'pls'=>1,'pgroup'=>0,'nick'=>0,'gd'=>'n','icon'=>0,'sNo'=>1,
		'inf'=>'','state'=>0,'action'=>'','bid'=>0,'killnum'=>0,'endtime'=>0,'deathtime'=>0,'money'=>0,'skillpoint'=>20,'ip'=>'127.0.0.1',
		'wp'=>100,'wk'=>100,'wc'=>100,'wg'=>100,'wd'=>100,'wf'=>100,'teamID'=>'','teamPass'=>'',
		'wep'=>'测试武器','wepk'=>'WG','wepe'=>80,'weps'=>30,'wepsk'=>'','weppara'=>'',
		'clbpara'=>Array('skill'=>Array(),'BGMBrand'=>'','achvars'=>Array()), 'message'=>'', 'achievement'=>'',
	);
	foreach(Array('wep2','arb','arh','ara','arf','art') as $slot)
	{
		$data[$slot] = $data[$slot.'k'] = $data[$slot.'sk'] = $data[$slot.'para'] = '';
		$data[$slot.'e'] = $data[$slot.'s'] = 0;
	}
	for($i=0;$i<=6;$i++)
	{
		$data['itm'.$i] = $data['itmk'.$i] = $data['itmsk'.$i] = $data['itmpara'.$i] = '';
		$data['itme'.$i] = $data['itms'.$i] = 0;
	}
	if($club) updateskill($data);
	return $data;
}
function club23_test_memory($pid)
{
	$data = club23_test_player($pid,23);
	$data['wep'] = '剥落的记忆'; $data['wepk'] = 'WFq';
	return $data;
}

$test_ruleset = ''; $test_dice = 1; $test_fixed_damage = NULL; $test_revival = false;
$db = new Club23TestDb(); $tablepre = 'test_'; $gtablepre = 'test_';
$now = 1000; $log = ''; $club = 0; $gamecfg = 1; $weather = 0;
$hdamage = 9999999; $hplayer = ''; $gamevars = Array(); $club23_contacts = Array();
$alivenum = 10; $deathnum = 0; $test_news = $test_logs = Array();
$gamestate = 20;
include GAME_ROOT.'include/game/revclubskills.func.php';
include GAME_ROOT.'include/game/clubslct.func.php';
include GAME_ROOT.'include/game/item.club_card.php';
include GAME_ROOT.'include/game/revcombat.func.php';
include config('resources');
include config('gamecfg');
include config('combatcfg');
include config('clubskills');
$baseexp = 10000; $chase_obbs = $dfight_obbs = 0;

club23_test_assert(count(array_filter($club_skillslist[23],function($s) { return strpos($s,'c23_') === 0; })) === 6,'登记六个专属技能');
club23_test_assert(!in_array(23,valid_getclublist_t2(Array())),'普通开局称号不包含23');
$p = club23_test_player(1);
$p['itm1'] = '无主的补缀匣'; $p['itmk1'] = 'ZB'; $p['itme1'] = 23; $p['itms1'] = 1; $p['itmpara1'] = '{"old":1}';
item_club_card(1,$p);
club23_test_assert($p['club'] === 23 && count($p['clbpara']['skill']) === 9 && $p['itms1'] === 0 && $p['itmpara1'] === '','加入、六技能及通用技能、消耗物品全部参数');
$saved = $p['clbpara']; item_club_card(1,$p);
club23_test_assert($p['clbpara'] === $saved,'重复使用已消耗加入道具不重新发放机会');
$p['itm1'] = '无主的补缀匣'; $p['itmk1'] = 'ZB'; $p['itme1'] = 23; $p['itms1'] = 1;
item_club_card(1,$p);
club23_test_assert($p['itms1'] === 1 && $p['clbpara'] === $saved,'已有称号时拒绝加入并保留道具');

$p['weps'] = $nosta; $p['wepsk'] = 'rX'; $p['weppara'] = '{"old":1}';
club23_test_assert(club23_skill('c23_trauma',$p) && $p['wepk'] === 'WFq' && $p['wepe'] === 80 && $p['weps'] === $club23cfg['memory_durability'] && $p['wepsk'] === '' && $p['weppara'] === '','伤迹转化：保留效果，转换无限耐久，清除旧属性与参数');
club23_test_assert(!club23_skill('c23_trauma',$p),'不能重复转化记忆');
$outsider = club23_test_player(2);
club23_test_assert(!club23_skill('c23_shroud',$outsider),'伪造技能请求不能领取');

$p = club23_test_player(1,23);
for($i=1;$i<=6;$i++) $p['itms'.$i] = 1;
$steps = $p['clbpara']['skillpara']['c23_shroud']['steps'];
club23_test_assert(!club23_skill('c23_shroud',$p) && $p['clbpara']['skillpara']['c23_shroud']['steps'] === $steps,'背包满不扣除黑岚机会');
$p['itms3'] = 0; $p['itm0'] = '待拾取物'; $p['itms0'] = 1;
club23_test_assert(club23_skill('c23_shroud',$p) && $p['itmk3'] === 'WFq' && $p['itm0'] === '待拾取物','黑岚放入空背包，不覆盖itm0');
club23_test_assert(!club23_skill('c23_shroud',$p),'不能重复领取已花掉的机会');
player_save($p); $p = $db->players[1]; $p['clbpara'] = get_clbpara($p['clbpara']);
club23_test_assert(!club23_skill('c23_shroud',$p),'保存并重新载入仍不能重复领取');
for($i=0;$i<$club23cfg['shroud_steps'];$i++) club23_move_search($p);
club23_test_assert($p['clbpara']['skillpara']['c23_shroud']['steps'] === $club23cfg['shroud_steps'],'移动探索积累新机会');
club23_test_assert(!club23_skill('c23_patch',$p,'bad') && !club23_skill('c23_patch',$p,'health'),'非法补缀选项和满生命不消耗机会');
$p['hp'] = 1000;
club23_test_assert(club23_skill('c23_patch',$p,'health') && $p['hp'] == 1500,'补缀生命与配置一致');
$p = club23_test_memory(1); $before = $p['weps'];
club23_test_assert(club23_skill('c23_patch',$p,'weapon') && $p['weps'] == $before+$club23cfg['patch_durability'],'补缀耐久消耗同一份黑岚机会');
club23_test_assert(!club23_skill('c23_patch',$p,'weapon'),'重复补缀不增加耐久');

$a = club23_test_memory(1); $b = club23_test_player(2);
club23_contact($a,$b);
club23_test_assert($b['clbpara']['subliminal'] === 80 && $b['clbpara']['subliminal_afterimage_until'] === 1008,'接触增加S并施加残像');
$now = 1004; club23_contact($a,$b);
club23_test_assert($b['clbpara']['subliminal'] === 160 && $b['clbpara']['subliminal_afterimage_until'] === 1012,'残像刷新时长、不叠加消退倍率');
club23_move_search($b);
club23_test_assert($b['clbpara']['subliminal'] === 160,'探索中再次受记忆影响则不消退S');
unset($club23_contacts[$b['pid']]); club23_move_search($b);
club23_test_assert($b['clbpara']['subliminal'] === 155,'残像中仍可通过探索降低S');
$now = 1012; club23_move_search($b);
club23_test_assert($b['clbpara']['subliminal'] === 145 && empty($b['clbpara']['subliminal_afterimage_until']),'残像到期立即恢复正常消退');
$b['clbpara']['subliminal'] = 1; club23_move_search($b);
club23_test_assert(!isset($b['clbpara']['subliminal']),'S归零清除状态');
$npc = club23_test_player(3); $npc['type'] = 25;
club23_test_assert(!club23_contact($a,$npc) && !isset($npc['clbpara']['subliminal']),'NPC不积累S');

$b['clbpara']['subliminal'] = 1;
club23_test_assert(club23_death_damage($a,$b) === 0,'1d1000严格小于S：S=1不触发');
$b['clbpara']['subliminal'] = 1001;
$other = club23_test_memory(4);
club23_test_assert(club23_death_damage($other,$b) === $b['hp'],'换另一持有者仍可触发，S>1000必定造成当前生命伤害');
$npc['clbpara']['subliminal'] = 1001;
club23_test_assert(club23_death_damage($a,$npc) === 0,'NPC不执行记忆即死');

// 实际战斗入口：攻击、反击、连击、空座和开战判定的先后关系。
$club23cfg['memory_hit_rate'] = 100;
$a = club23_test_memory(1); $b = club23_test_player(2);
$a['wepe'] = 1001;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($b['hp'] === 2000 && $b['clbpara']['subliminal'] === 1001,'首次记忆命中只积累S，本次交战不追加即死判定');
club23_test_assert(get_clbpara($db->players[2]['clbpara'])['subliminal'] === 1001,'真实战斗结束调用双方保存并带出S');

$test_revival = true;
$other = club23_test_memory(4);
\revcombat\rev_combat_prepare($other,$b,1,'',0);
club23_test_assert($b['hp'] === 25 && $b['state'] === 0,'下次交战触发即死，仍经现有复活流程');
$test_revival = false;

$a = club23_test_memory(1); $b = club23_test_player(2); $b['clbpara']['subliminal'] = 1001;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($b['hp'] === 0 && $b['bid'] === 1 && $a['killnum'] === 1 && $a['action'] === 'corpse','真正死亡沿用击杀归属、死亡状态和尸体入口');

$a = club23_test_player(1); $b = club23_test_memory(2); $a['clbpara']['subliminal'] = 1001;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($a['hp'] === 0 && $a['bid'] === 2 && $b['killnum'] === 1,'攻击记忆持有者也会在反击前被已有S杀死，归属正确');

$a = club23_test_player(1); $b = club23_test_memory(2);
$a['wepk'] = 'WG'; $test_fixed_damage = 100;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($b['hp'] == 1950 && $a['clbpara']['subliminal'] === 80,'旧痕减免固定伤害；空座在射程外首次施加S');
club23_test_assert($b['weps'] === 29,'空座只消耗一次耐久');
$a = club23_test_memory(1); $b = club23_test_memory(2); $b['tactic'] = 3;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($a['clbpara']['subliminal'] === 80 && $b['weps'] === 29,'普通记忆反击命中时空座不重复施加');
$a = club23_test_player(1); $b = club23_test_memory(2); $a['wepsk'] = 'r'; $a['wg'] = 900;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($a['hitrate_times'] > 1 && $a['clbpara']['subliminal'] === 80,'常规连击命中多次，空座仍只触发一次');
$a = club23_test_player(1,23); $b = club23_test_player(2); $b['type'] = 25;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($b['hp'] == 1990,'称号的常规固定武器伤害对NPC也降至10%');
$a = club23_test_memory(1); $b = club23_test_player(2); $b['type'] = 25;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($b['hp'] === 2000 && empty($b['clbpara']['subliminal']),'记忆对NPC不走固定伤害，也不施加S');

$a = club23_test_player(1); $b = club23_test_memory(2); $test_dice = 99;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert(empty($a['clbpara']['subliminal']) && $b['weps'] === 30,'未命中不触发空座');
$test_dice = 1; $club23cfg['memory_hit_rate'] = 0;
$a = club23_test_memory(1); $b = club23_test_player(2); $a['weps'] = 1;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert(empty($b['clbpara']['subliminal']) && $a['wepk'] === 'WN','记忆未命中也消耗耐久，耗尽后变回空手');
$club23cfg['memory_hit_rate'] = 100;
$a = club23_test_memory(1); $b = club23_test_memory(2); $b['tactic'] = 3; $b['weps'] = 1;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($a['clbpara']['subliminal'] === 80 && $b['wepk'] === 'WN','反击用完最后一份记忆，不再额外触发空座');

// 验证普通伤害分支而非仅固定伤害分支。
$test_fixed_damage = NULL;
$a = club23_test_player(1); $b = club23_test_player(2);
mt_srand(10); \revcombat\rev_combat_prepare($a,$b,1,'',0); $ordinary = $a['final_damage'];
$a = club23_test_player(1,23); $b = club23_test_player(2);
mt_srand(10); \revcombat\rev_combat_prepare($a,$b,1,'',0);
club23_test_assert($a['final_damage'] == round($ordinary*0.1),'普通物理伤害分支也执行称号代价');

// 通用技能按钮入口与补缀选项透传。
$pdata = club23_test_memory(1); $pdata['hp'] = 1000;
$club = &$pdata['club']; $clbpara = &$pdata['clbpara']; $skillpoint = &$pdata['skillpoint'];
$c23_patch_mode = 'health'; upgclbskills('c23_patch');
club23_test_assert($pdata['hp'] == 1500 && $pdata['clbpara']['skillpara']['c23_shroud']['steps'] === 0,'revskpts技能入口正确传递补缀选项并扣除机会');
upgclbskills('c23_patch');
club23_test_assert($pdata['hp'] == 1500,'技能按钮重复提交不重复补缀');

$b = club23_test_player(2); $b['clbpara']['subliminal'] = 3000;
$club23cfg['dialogue_chance_max'] = 100; $club23_contacts = Array();
club23_move_search($b);
club23_test_assert(isset($b['clbpara']['dialogue']) && $b['clbpara']['dialogue'] === 'club23_intrusion','高S在实际探索结算中触发侵入对白');
$b['clbpara']['dialogue'] = 'quest'; $b['clbpara']['noskip_dialogue'] = 1;
club23_move_search($b);
club23_test_assert($b['clbpara']['dialogue'] === 'quest','侵入对白不会覆盖已有剧情或强制对话');

$test_ruleset = 'YELLOWKNIFE'; include config('resources'); include config('clubskills'); include config('dialogue'); include config('tooltip');
club23_test_assert($clubinfo[23] === '崩坏心核' && $iteminfo['WFq'] === '？？？？' && isset($dialogues['club23entry'],$dialogues['club23_intrusion'],$tps_ik['WFq']),'YELLOWKNIFE的称号、技能、类别、对话和提示完整');
echo "All club23 tests passed.\n";
