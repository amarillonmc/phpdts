<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','game');
error_reporting(E_ALL);

// 单独进程验证真实diceroll，使用空的common入口，避免加载站点数据库。
// php -d short_open_tag=1 -r "define('IN_GAME',true); require 'gamedata/tests/club24_test.php';" -- dice
if(($argv[1] ?? '') === 'dice') {
	$test_dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('phpdts-c24-');
	mkdir($test_dir); mkdir($test_dir.'/include'); file_put_contents($test_dir.'/include/common.inc.php','<?php');
	$old_dir=getcwd();
	try { chdir($test_dir); require GAME_ROOT.'include/game/dice.func.php'; }
	finally { chdir($old_dir); unlink($test_dir.'/include/common.inc.php'); rmdir($test_dir.'/include'); rmdir($test_dir); }
	$club=24; $clbpara=Array('BGMBrand'=>'','traitorRoll'=>5); $log=''; $nikstatusa=$nikstatuse=0; $rp=$nick=0;
	$mhp=$msp=$att=$def=$wc=$wd=$wp=$wk=$wf=$wg=100;
	diceroll(0);
	c24_assert($mhp===100 && $att===100 && $wp===100 && $clbpara['traitorRoll']===5,'神骰叛军即使歌单被重置也免反叛惩罚');
	$club=0; diceroll(0);
	c24_assert($mhp===90 && $att===90 && $wp===85 && $clbpara['traitorRoll']===0,'其他称号仍承受原有反叛惩罚');
	$club=24; $clbpara['BGMBrand']='rixolamal'; diceroll(0);
	c24_assert($clbpara['traitorRoll']===1 && $mhp>90 && $wp>85,'入社歌单仍执行原有随机成长');
	echo "All club24 dice tests passed.\n"; exit;
}

// 真实技能、物品、成就和战斗函数；仅数据库、画面和站点外围使用桩，不读取部署配置。
function c24_assert($ok,$message) {
	if(!$ok) { fwrite(STDERR,"FAIL: $message\n"); exit(1); }
	echo "PASS: $message\n";
}
set_error_handler(function($level,$message,$file,$line) {
	if($level === E_NOTICE && basename($file) !== 'club24.func.php') return true;
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
function template($name) { return config('club24cfg'); } // 画面桩：只返回数据，不输出HTML
function check_add_searchmemory(...$args) {}
function parse_nameinfo_desc($name,$horizon) { return $name; }
function parse_kinfo_desc($kind,$attrs) { return $kind; }
function parse_skinfo_desc($attrs,$kind) { return $attrs; }
function ruleset_get_fix_damage_hook(&$pa,&$pd,$active) { global $test_fixed_damage; return $test_fixed_damage; }
function ruleset_revive_process_hook(&$pa,&$pd,$active) {
	global $test_revival;
	if(!$test_revival) return NULL;
	$pd['hp'] = 25; $pd['state'] = 0; return 1;
}
function ruleset_move_allowed_hook($data,$place) { global $test_locked; return !in_array($place,$test_locked); }
function ruleset_ai_event_hook($rate,&$data) { return true; }
function fetch_userdata_by_username($name) { global $db; return $db->users[$name]; }
function fetch_playerdata_by_pid($pid) {
	global $db;
	if(!isset($db->players[$pid])) return NULL;
	$row = $db->players[$pid]; $row['clbpara'] = get_clbpara($row['clbpara']); return $row;
}
function player_save($data) {
	global $db;
	$row = array_intersect_key($data,c24_player(0));
	$row['clbpara'] = json_encode($data['clbpara'],JSON_UNESCAPED_UNICODE);
	$db->players[$data['pid']] = $row;
}
class C24Result {
	public $rows, $offset = 0;
	function __construct($rows) { $this->rows = array_values($rows); }
}
class C24Db {
	public $players = Array(), $users = Array(), $shopitem = Array(), $chat = Array(), $mapitem = Array(), $queries = Array();
	function query($sql) {
		$this->queries[] = $sql;
		if(preg_match("/^SELECT (achrev|nicksrev|\*) FROM test_users WHERE username\s*=\s*'([^']+)'/",$sql,$m)) {
			$row = $this->users[$m[2]] ?? Array('achrev'=>'','nicksrev'=>'');
			return new C24Result(Array($m[1] === '*' ? $row : Array($m[1]=>$row[$m[1]])));
		}
		if(preg_match("/^UPDATE test_users SET (achrev|nicksrev)='(.*)' WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] = $m[2]; return true;
		}
		if(preg_match("/^UPDATE test_users SET (credits2?)=\\1\\+(\d+) WHERE username='([^']+)'/",$sql,$m)) {
			$this->users[$m[3]][$m[1]] += (int)$m[2]; return true;
		}
		if(preg_match("/^SELECT clbpara FROM test_players WHERE name='([^']+)'/",$sql,$m)) {
			return new C24Result(array_filter($this->players,function($r) use($m) { return $r['name'] === $m[1]; }));
		}
		if(preg_match("/^SELECT \* FROM test_shopitem WHERE sid = '(\d+)'/",$sql,$m)) {
			return new C24Result(isset($this->shopitem[$m[1]]) ? Array($this->shopitem[$m[1]]) : Array());
		}
		if(preg_match("/^SELECT \* FROM test_shopitem WHERE kind = '(\d+)'/",$sql,$m)) {
			return new C24Result(array_filter($this->shopitem,function($r) use($m) { return $r['kind'] == $m[1] && $r['num'] > 0; }));
		}
		if(preg_match("/^UPDATE test_shopitem SET num = '(\d+)' WHERE sid = '(\d+)'/",$sql,$m)) {
			$this->shopitem[$m[2]]['num'] = (int)$m[1]; return true;
		}
		if(preg_match('/^SELECT \* FROM test_mapitem WHERE pls IN \(([^)]+)\)/',$sql,$m)) {
			return new C24Result(array_filter($this->mapitem,function($r) use($m) { return in_array($r['pls'],explode(',',$m[1])) && $r['itms']; }));
		}
		if(preg_match("/^SELECT \* FROM test_players WHERE pid!='(\d+)' AND pgroup=(\d+) AND \\(pls='(\d+)' OR \\(type>0 AND hp>0 AND pls IN \\(([^)]+)\\)\\)\\)/",$sql,$m)) {
			return new C24Result(array_filter($this->players,function($r) use($m) {
				return $r['pid'] != $m[1] && $r['pgroup'] == $m[2] && ($r['pls'] == $m[3] || ($r['type'] > 0 && $r['hp'] > 0 && in_array($r['pls'],explode(',',$m[4]))));
			}));
		}
		if(preg_match("/^DELETE FROM test_mapitem WHERE iid\s*=\s*'?(\d+)/",$sql,$m)) { unset($this->mapitem[$m[1]]); return true; }
		return new C24Result(Array()); // 不参与断言的旧成就、新闻等查询
	}
	function array_insert($table,$data) { $table = substr($table,5); $this->{$table}[] = $data; }
	function result($r,$index=0) { return isset($r->rows[$index]) ? reset($r->rows[$index]) : ''; }
	function fetch_array($r) { return $r->rows[$r->offset++] ?? false; }
	function num_rows($r) { return count($r->rows); }
	function data_seek($r,$offset) { $r->offset = $offset; }
	function free_result($r) {}
	function insert_id() { return count($this->mapitem); }
}
function c24_player($pid,$club=0) {
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
function c24_bind(&$data) {
	$GLOBALS['pdata'] = &$data;
	foreach($data as $key=>&$value) $GLOBALS[$key] = &$value;
}
function c24_learn(&$data,$skill,$level=1) {
	c24_bind($data);
	for($i=0;$i<$level;$i++) upgclbskills($skill);
}
function c24_item(&$data,$slot,$name='测试装备',$kind='WK',$effect=100,$durability=20,$attrs='rv') {
	$data['itm'.$slot]=$name; $data['itmk'.$slot]=$kind; $data['itme'.$slot]=$effect;
	$data['itms'.$slot]=$durability; $data['itmsk'.$slot]=$attrs; $data['itmpara'.$slot]='{"marker":1}';
}

$db = new C24Db(); $tablepre = $gtablepre = 'test_';
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

c24_assert($clubinfo[24] === '神骰叛军' && count($club_skillslist[24]) === 8 && !in_array(24,valid_getclublist_t2(Array())),'仅由称号卡加入，注册八项升级技能');
c24_assert(isset($hidden_ach_type[209]) && $titles_list[123] === '神骰叛军' && $title_valid[123]['itmsk6'] === 'v','隐藏成就与绑定入场奖励');
$p = c24_player(1); c24_item($p,1,'「神骰叛军」称号卡','ZB',24,1,'v');
item_club_card(1,$p);
c24_assert(!$p['club'] && $p['itms1'] === 1,'未经确认不入社、不消耗卡');
$c24_confirm = '1'; item_club_card(1,$p);
c24_assert($p['club'] === 24 && !$p['itms1'] && $p['itmpara1'] === '' && count($p['clbpara']['skill']) === 10,'确认入社，清空整张卡并发放掷币掷骰');
c24_assert($p['clbpara']['BGMBrand'] === 'rixolamal' && $p['clbpara']['event_bgmbook'] === $event_bgm['rixolamaltracks'] && $p['clbpara']['traitorRoll'] === 0,'强制歌单与反叛计数初始化');
$before = $p; item_club_card(1,$p); changeclub(1,$p);
c24_assert($p === $before,'重复使用及转社团不能重置状态或退出');
$p = c24_player(2,1); c24_item($p,1,'「神骰叛军」称号卡','ZB',24,1,'v'); item_club_card(1,$p);
c24_assert($p['club'] === 1 && $p['itms1'] === 1,'已有称号拒绝入社并保留卡');

$p = c24_player(1,24); c24_bind($p);
c24_assert(check_skill_unlock('c24_roulette',$p) === 'c24_unlearned','零级战斗技不可发动');
foreach(Array('c24_verdict'=>6,'c24_roulette'=>12,'c24_gamble'=>9,'c24_reforge'=>9) as $sk=>$cost) {
	$before = $p['skillpoint']; c24_learn($p,$sk,3);
	c24_assert(get_skilllvl($sk,$p) == 3 && $p['skillpoint'] == $before-$cost,$sk.'实际升级与技能点消耗');
	$before = $p['skillpoint']; upgclbskills($sk); upgclbskills($sk,2);
	c24_assert($p['skillpoint'] === $before && get_skilllvl($sk,$p) == 3,$sk.'满级和伪造批量升级不再扣点');
}
c24_assert(get_skillpara('c24_verdict','faces',$p['clbpara']) === 2 && get_skillpara('c24_verdict','rageback',$p['clbpara']) === 10,'裁决升级数值');
c24_assert(get_skillpara('c24_roulette','chambers',$p['clbpara']) === 2 && get_skillragecost('c24_roulette',$p) === 127 && get_skillragecost('c24_gamble',$p) === 20 && club24_skill_rage('c24_reforge',$p) === 15,'三级弹膛及怒气消耗');
c24_item($p,3); $prior_rage = $p['rage']; $before = serialize($p);
c24_assert(!club24_skill('c24_reforge',$p,'3ED') && !club24_skill('c24_reforge',$p,'7') && serialize($p) === $before,'重铸拒绝多个锁定项、越界槽位且不扣怒');
club24_skill('c24_reforge',$p,'3E');
c24_assert($p['itme3'] === 100 && $p['itms3'] >= 1 && $p['itms3'] <= 40 && $p['rage'] === $prior_rage-15 && $p['itmpara3'] === '{"marker":1}','重铸锁效果、消耗15怒气并保留para');
c24_item($p,0,'无限装备','DA',20,$nosta,'Xv'); club24_skill('c24_reforge',$p,'0D');
c24_assert($p['itms0'] === $nosta && strpos($p['itmsk0'],'v') !== false && strpos($p['itmsk0'],'X') === false,'防具重铸保留无限耐久与绑定，排除直死');
$sig = club24_item_signature($p); club24_pickup($p,'pickup');
c24_assert(club24_item_signature($p) === $sig,'重铸待拾取物后不附送一次拾取重投');
$p['itme0'] = (string)$p['itme0']; $p['clbpara'] = json_decode(json_encode($p['clbpara']),true);
club24_pickup($p,'pickup'); c24_assert(club24_item_signature($p) === $sig,'数据库数值字符串及重新加载不导致重复重投');
foreach(Array('Untainted Glory','『G.A.M.E.O.V.E.R』','女生校服') as $name) {
	c24_item($p,2,$name,'DA'); $before = serialize($p); c24_assert(!club24_skill('c24_reforge',$p,'2') && serialize($p) === $before,$name.'剧情物不重铸');
}
$p = c24_player(1,24); c24_learn($p,'c24_reforge'); c24_item($p,1);
c24_assert(!club24_skill('c24_reforge',$p,'1E'),'一级重铸不能锁定');
$p['rage']=19; c24_assert(!club24_skill('c24_reforge',$p,'1') && $p['itme1']===100,'怒气不足不改物品');
$outsider=c24_player(2); c24_assert(!club24_skill('c24_reforge',$outsider,'1'),'其他称号伪造主动技无效');

$p=c24_player(1,24); c24_item($p,0); itemget($p);
c24_assert(!$p['itms0'] && $p['itms1']>=1 && $p['itms1']<=40 && $p['itme1']>=1 && $p['itme1']<=200 && $p['itmpara1']==='{"marker":1}','真实拾取入口重投一次并入包');
$p=c24_player(1,24); c24_bind($p); c24_item($p,0,'发现页武器','WK',100,20,'Xv'); itemfind($p); $sig=club24_item_signature($p); $rolled_effect=$p['itme0'];
item_weapon(0,$p);
c24_assert($p['wepe']===$rolled_effect && strpos($p['wepsk'],'X')===false && strpos($p['wepsk'],'v')!==false,'发现页直接装备不能绕过随机化，也不重投两次');
$p=c24_player(1,24); c24_bind($p); c24_item($p,0,'发现页补给','HH',100,$nosta,''); $p['hp']=1; $p['clbpara']['BGMBrand']='rixolamal';
itemfind($p); item_recovery_health(0,$p); $sig=club24_item_signature($p); club24_pickup($p,'pickup');
c24_assert($p['hp']===2 && club24_item_signature($p)===$sig,'直接使用补给继承歌单掷骰，剩余补给不免费重投');
$p=c24_player(1); c24_item($p,0); itemget($p);
c24_assert($p['itme1']===100 && $p['itms1']===20 && $p['itmsk1']==='rv','普通称号拾取保持原值');
$p=c24_player(1,24); c24_item($p,0,'剧情卡','ZB',24,1,'v'); itemget($p);
c24_assert($p['itme1']===24 && $p['itms1']===1,'称号卡豁免随机化');
itemdrop('itm1',$p); c24_assert(!$p['itms1'] && !$db->mapitem,'绑定ZB丢弃销毁、不落地');
$p=c24_player(1,24); c24_item($p,0); $p['itmsk0']='rXv'; itemget($p,'mix');
c24_assert($p['itmsk1']==='rXv' && $p['itme1']<=200 && $p['itms1']<=40,'合成来源只重投效果耐久，不重投属性');
$p=c24_player(1,24); c24_bind($p); c24_item($p,1,'素材一','Y',1,1,''); c24_item($p,2,'素材二','Y',1,1,'');
$mix_type=Array('normal'=>'通常'); $recipe=Array('type'=>'normal','result'=>Array('合成测试装备','DA',100,20,'Xv','{"mix":1}'));
itemmix_proc(Array(1,2),$recipe,'素材一和素材二',$p);
c24_assert($p['itm1']==='合成测试装备' && !$p['itms2'] && $p['itmsk1']==='Xv' && $p['itmpara1']==='{"mix":1}' && $p['itme1']<=200 && substr_count($log,'骰子改写了这件物品！')>=1,'真实合成流程消耗材料，产物只随机化一次');
$p=c24_player(1,24); for($i=1;$i<=6;$i++) c24_item($p,$i,'占位'.$i,'Y',1,1,'');
c24_item($p,0); ob_start(); itemget($p); ob_end_clean(); $sig=club24_item_signature($p);
player_save($p); $p=fetch_playerdata_by_pid(1); ob_start(); itemget($p); ob_end_clean();
c24_assert(club24_item_signature($p)===$sig,'满包拾取跨请求重复提交不会重投');
$p=c24_player(1,24); c24_bind($p); c24_item($p,1,'磨刀石','Y',20,1,''); $p['wepk']='WK'; $before=$p['wepe'];
mt_srand(1); item_weapon_mod(1,$p); c24_assert($p['wepe']===$before+1 && !$p['itms1'],'磨刀石强化量经diceroll且道具正常消耗');

$p=c24_player(1,24); c24_learn($p,'c24_allin');
c24_assert($p['skillpoint']===95 && club24_skill('c24_allin',$p),'梭哈花费5点后可开启');
$negative=false; $positive=false;
for($seed=1;$seed<=80;$seed++) {
	$p['att']=$p['def']=100; $p['skillpoint']=100; mt_srand($seed); upgclbskills('s_ad');
	$delta=$p['att']-100; $negative=$negative || $delta<0; $positive=$positive || $delta>0;
	$def_delta=$p['def']-100;
	c24_assert(($delta>0)===($def_delta>0) && abs($delta)>=2 && abs($delta)<=6 && abs($def_delta)>=3 && abs($def_delta)<=9 && abs(1.5*$delta-$def_delta)<=1.25,'梭哈攻防整条同倍率 seed='.$seed);
	if($negative && $positive) break;
}
c24_assert($negative && $positive,'梭哈覆盖正收益与负收益分支');
club24_skill('c24_allin',$p); $before=$p['att']; upgclbskills('s_ad');
c24_assert($p['att']===$before+4,'关闭梭哈恢复固定升级收益');
player_save($p); $p=fetch_playerdata_by_pid(1);
c24_assert(get_skilllvl('c24_allin',$p)==1 && !get_skillpara('c24_allin','active',$p['clbpara']),'技能等级和开关跨保存保持');

$p=c24_player(1,24); getclubskill('c24_coin',$p['clbpara']); getclubskill('c24_dice',$p['clbpara']); c24_bind($p);
$p['rage']=20; upgclbskills('c24_coin'); upgclbskills('c24_dice'); upgclbskills('c24_dice');
c24_assert($p['rage']===0 && count($db->chat)===2 && $db->chat[0]['type']==='0' && strpos($db->chat[1]['msg'],'D20')!==false,'原技能入口公开喊话，怒气不足不重复插入');

$db->shopitem[1]=Array('sid'=>1,'kind'=>1,'num'=>5,'price'=>101,'area'=>0,'item'=>'商店测试物','itmk'=>'Y','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>'');
$p=c24_player(1,24); c24_bind($p); $areanum=0; $areaadd=1;
itembuy(1,1,1,$p); c24_assert($db->shopitem[1]['num']===5,'未进店不能伪造乘数购入');
mt_srand(1); shoplist(1); $shown=$itemdata[0]['price']; $mult=$p['clbpara']['c24_shopmult']; $before=$p['money'];
player_save($p); $p=fetch_playerdata_by_pid(1); c24_bind($p); itembuy(1,1,1,$p);
c24_assert($p['money']===$before-$shown && $shown===(int)ceil(101*$mult) && $db->shopitem[1]['num']===4 && $db->shopitem[1]['price']===101,'商店显示/重载后扣款一致，仅库存改变');
mt_srand(2); shoplist(1); c24_assert($p['clbpara']['c24_shopmult']!==$mult,'重新进店重摇乘数');
$p2=c24_player(2,24); c24_assert(!isset($p2['clbpara']['c24_shopmult']),'其他玩家不共享价格状态');
$p2['club']=0; c24_bind($p2); shoplist(1); c24_assert($itemdata[0]['price']===101,'普通玩家价格不受影响');

$saved_places=$plsinfo; $saved_hidden=$hplsinfo;
$plsinfo=Array(0=>'禁区',1=>'当前',2=>'可移动',3=>'门控',100=>'隐藏显示');
$hplsinfo=Array(1=>Array(100=>'隐藏一',101=>'隐藏二'));
$arealist=Array(0,1,2,3); $areanum=0; $hack=0; $test_locked=Array(3);
$p=c24_player(1,24);
c24_assert(club24_places($p,true)===Array(2),'随机移动排除原地、禁区、门控和合并显示的隐藏地点');
$db->mapitem=Array(1=>Array('iid'=>1,'itm'=>'远处补给','itmk'=>'HH','itme'=>100,'itms'=>2,'itmsk'=>'','itmpara'=>'{"remote":1}','pls'=>2),2=>Array('iid'=>2,'itm'=>'隐藏补给','itmk'=>'HH','itme'=>100,'itms'=>2,'itmsk'=>'','itmpara'=>'','pls'=>100));
c24_bind($p); focus_item($p); c24_assert($p['itm0']==='远处补给' && !isset($db->mapitem[1]) && isset($db->mapitem[2]),'真实发现入口从同组远处移除物品，不跨隐藏组');
$db->mapitem=Array(); $p['itms0']=0; $db->players=Array();
$remote=c24_player(3); $remote['type']=2; $remote['pls']=2; $remote['horizon']=1; player_save($remote);
$other=c24_player(4); $other['pls']=3; player_save($other);
$cross=c24_player(5); $cross['type']=2; $cross['pgroup']=1; $cross['pls']=100; player_save($cross);
$event_obbs=-1; $trap_max_obbs=0; $enemy_obbs=1000;
discover(100,$p); c24_assert($p['action']==='enemy' && $p['bid']===3,'实际探索可以发现远处不同视界NPC，排除远处玩家和其他地图组');
$p['pgroup']=1; $p['pls']=100; c24_assert(club24_places($p,true)===Array(101),'隐藏地图组移动不串组');
$p['pgroup']=0; $p['pls']=1; $n=c24_player(3); $n['type']=93; $n['pls']=2;
c24_assert(club24_remote_npc($p,$n),'任意类型存活NPC可跨地点遭遇');
$n['type']=0; c24_assert(!club24_remote_npc($p,$n),'普通玩家不跨地点');
$n['type']=93; $n['pgroup']=1; c24_assert(!club24_remote_npc($p,$n),'NPC不跨地图组');
$n['pgroup']=0; $n['hp']=0; $n['bid']=1; $p['bid']=3; $p['action']='corpse'; player_save($n);
$p['action']='focus'; c24_assert(club24_remote_npc($p,$n,true),'保留视野后可以重新查看自己的远处NPC尸体'); $p['action']='corpse';
getcorpse('money',$p);
c24_assert($p['money']===20000 && $db->players[3]['money']===0,'跨地点击杀后的实际摸尸入口可取回战利品');
$plsinfo=$saved_places; $hplsinfo=$saved_hidden; $test_locked=Array();

// 裁决每次行动只掷一次；直接调用覆盖D6各分支，再经真实战斗入口验证追加攻击。
$p=c24_player(1,24); c24_learn($p,'c24_verdict',3); $p['rage']=0; $target=c24_player(2);
$saw_half=$saw_rapid=false;
for($seed=0;$seed<80;$seed++) {
	unset($p['c24_verdict_done'],$p['c24_rapid']); $p['rage']=0; mt_srand($seed);
	$damage=club24_verdict($p,$target,101);
	if($damage===50) { $saw_half=true; c24_assert($p['rage']===10,'裁决1点伤害减半并回复怒气'); }
	if(!empty($p['c24_rapid'])) $saw_rapid=true;
	c24_assert(club24_verdict($p,$target,101)===101,'同一行动不连锁掷骰');
	if($saw_half && $saw_rapid) break;
}
c24_assert($saw_half && $saw_rapid,'裁决正反两种结果均覆盖');
$p['is_counter']=1; unset($p['c24_verdict_done']);
c24_assert(club24_verdict($p,$target,100)===100 && empty($p['c24_verdict_done']),'反击不发动裁决');
$test_fixed_damage=100; $rapid_seen=false;
for($seed=0;$seed<40;$seed++) {
	$a=c24_player(1,24); c24_learn($a,'c24_verdict',3); $b=c24_player(2); $log=''; mt_srand($seed);
	\revcombat\rev_combat_prepare($a,$b,1,'',0);
	c24_assert(substr_count($log,'裁决的骰子滚动了')===1,'实际战斗行动只掷一次裁决');
	if($b['hp']===1800) { $rapid_seen=true; c24_assert($a['weps']===28,'裁决追加攻击沿用普通武器耐久结算'); break; }
}
c24_assert($rapid_seen,'实际战斗裁决可追加一次攻击'); $test_fixed_damage=NULL;

$hit=$self=false;
for($seed=0;$seed<80;$seed++) {
	$a=c24_player(1,24); c24_learn($a,'c24_roulette',3); $b=c24_player(2); $b['def']=999999; $b['arbs']=99; $b['arbsk']='AB';
	mt_srand($seed); \revcombat\rev_combat_prepare($a,$b,1,'bskill_c24_roulette',0);
	if($b['hp']<2000) { $hit=true; c24_assert($b['hp']>=1440 && $b['hp']<=1920 && $a['hp']===2000,'轮盘命中无视极高防御及物理减伤'); }
	else { $self=true; c24_assert($a['hp']>=1440 && $a['hp']<=1920,'轮盘失手扣自己的生命'); }
	c24_assert($a['rage']<255 && $a['weps']===29,'轮盘扣怒并保留武器消耗');
	if($hit && $self) break;
}
c24_assert($hit && $self,'真实战斗轮盘命中与自伤均覆盖');
$saved_self_seed=0;
for($seed=0;$seed<40;$seed++) {
	$a=c24_player(1,24); c24_learn($a,'c24_roulette'); getclubskill('tl_2ndchance',$a['clbpara']); $a['hp']=$a['mhp']=100;
	$a['wepe']=1000; $b=c24_player(2); $log=''; mt_srand($seed);
	\revcombat\rev_combat_prepare($a,$b,1,'bskill_c24_roulette',0);
	if(strpos($log,'子弹没有飞向敌人')!==false) { $saved_self_seed=$seed; c24_assert($a['hp']==1 && $a['state']==0,'轮盘自伤先反噬后免死，奇机保留1生命'); break; }
}
$a=c24_player(1,24); c24_learn($a,'c24_roulette'); $a['hp']=$a['mhp']=100; $a['wepe']=1000; $b=c24_player(2); $test_revival=true;
mt_srand($saved_self_seed); \revcombat\rev_combat_prepare($a,$b,1,'bskill_c24_roulette',0);
c24_assert($a['hp']==25 && $a['state']==0 && $b['killnum']==0,'轮盘致命自伤走真实复活流程，不提前登记击杀'); $test_revival=false;
$a=c24_player(1,24); c24_learn($a,'c24_roulette'); $a['hp']=$a['mhp']=100; $a['wepe']=1000; $b=c24_player(2);
mt_srand($saved_self_seed); \revcombat\rev_combat_prepare($a,$b,1,'bskill_c24_roulette',0);
c24_assert($a['hp']==0 && $a['bid']==2 && $b['killnum']==1,'轮盘自伤死亡沿用战斗死亡和击杀归属');
$a=c24_player(1,24); c24_learn($a,'c24_roulette'); $a['wep_kind']='N'; $a['att']=4000; $a['bskill_c24_roulette']=1; $b=c24_player(2);
c24_assert(club24_battle_damage($a,$b,1)===3337,'徒手基础攻击超过上限仍为3337');
$a=c24_player(1,24); c24_learn($a,'c24_gamble',2); $b=c24_player(2); $b['def']=999999; $test_dice=1; $dice_calls=Array();
\revcombat\rev_combat_prepare($a,$b,1,'bskill_c24_gamble',0);
c24_assert($a['final_damage']===1 && $b['hp']===1999 && count(array_filter($dice_calls,function($n){return $n>1000;}))>=2,'豪赌二级两次基础伤害重掷，纯伤害不吃防御');
$a=c24_player(1,24); $b=c24_player(2); $b['skill_tl_2ndchance']=1;
c24_assert(\revattr\get_final_dmg_fix($a,$b,1,3000,true)===1999,'纯伤害仍触发奇机');
$b['hp']=1; $b['skill_tl_oncemore']=1;
c24_assert(\revattr\get_final_dmg_fix($a,$b,1,3000,true)===0 && isset($b['clbpara']['tl_oncemore_used']),'纯伤害仍触发起迹并保存已用状态');
$a['final_damage']=1500; $a['ex_keys']=Array(); $a['wep_name']='测试武器'; \revattr\attack_finish_events($a,$b,1);
c24_assert($a['hp']==1000,'伤害1000以上按原有规则反噬');
$a['hp']=2000; $a['ex_keys']=Array('H'); \revattr\attack_finish_events($a,$b,1);
c24_assert($a['hp']==1900,'HP制御继续降低反噬');

$p=c24_player(10); $p['clbpara']['BGMBrand']='rixolamal';
$db->users[$p['name']]=Array('achrev'=>'','nicksrev'=>'','credits'=>0,'credits2'=>0); player_save($p);
check_club24_achievement($p); $earned=$db->users[$p['name']];
c24_assert(json_decode($earned['achrev'],true)[209]['l']===1 && in_array(123,json_decode($earned['nicksrev'],true)['nicks']) && $earned['credits']===233 && $earned['credits2']===100,'隐藏成就持久化、头衔及两类奖励正确');
$p=fetch_playerdata_by_pid(10); check_club24_achievement($p); check_end_achievement_rev($p['name'],-1,$p);
c24_assert($db->users[$p['name']]===$earned,'死亡后再结算及重新加载不会重复发奖');
$n=c24_player(11); $n['type']=93; $n['clbpara']['BGMBrand']='rixolamal'; check_club24_achievement($n);
$ordinary=c24_player(12); check_club24_achievement($ordinary);
c24_assert(!isset($db->users[$n['name']]) && !isset($db->users[$ordinary['name']]),'NPC及非歌单玩家不解锁');
$p=c24_player(13); $p['clbpara']['BGMBrand']='rixolamal'; c24_item($p,6,'「神骰叛军」称号卡','ZB',24,1,'v');
$db->users[$p['name']]=Array('achrev'=>'','nicksrev'=>'','credits'=>0,'credits2'=>0); $killer=c24_player(14);
check_death_events($killer,$p,1);
c24_assert(check_achievement_rev(209,$p['name'])===999 && !$p['itms6'],'真实死亡事件解锁隐藏成就并销毁绑定ZB');
echo "All club24 tests passed.\n";
