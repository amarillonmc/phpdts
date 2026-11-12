<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);

function talent_test_assert($condition,$label) { if(!$condition) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label."\n"; }
function talent_test_reject($callback,$label) { try { $callback(); } catch(InvalidArgumentException $e) { talent_test_assert(true,$label); return; } throw new RuntimeException('FAIL: '.$label); }
function ending_routes_enabled() { return $GLOBALS['test_enabled']; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode((string)$value,true) ?: array()); }
function get_itmpara($value) { return get_clbpara($value); }
function roommng_acquire_create_lock() { return null; }
function roommng_release_create_lock($lock) {}
function player_format_with_db_structure($data) { return $data; }
function player_save($data) { $GLOBALS['db']->players[$data['pid']]=json_decode(json_encode($data),true); }
function fetch_playerdata_by_pid($pid) { return $GLOBALS['db']->players[$pid] ?? null; }
function getclubskill($skill,&$para) { $para['skill'][]=$skill; }
function erc_config($key,$default=null) { include GAME_ROOT.'gamedata/cache/dissociation_1.php'; return $dissociation[$key] ?? $default; }
function ending_routes_give(&$data,$item) {
	$item+=array('itmk'=>'Y','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>'');
	for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) { foreach($item as $key=>$value) $data[$key.$i]=$value; return; }
}
function destory_single_item(&$data,$slot) { foreach(array('itm','itmk','itmsk','itmpara') as $key) $data[$key.$slot]=''; $data['itme'.$slot]=$data['itms'.$slot]=0; }
function save_gameinfo() { $GLOBALS['saved_talent_gamevars']=json_decode(json_encode($GLOBALS['gamevars'] ?? array()),true); }
function addnews() {}

class TalentRaceResult {
	public $rows,$index=0;
	function __construct($rows) { $this->rows=array_values($rows); }
}
class TalentRaceArchiveStop extends RuntimeException {}
class TalentRaceDb {
	public $players=array(),$users=array(),$affected=0,$lastid=0,$queries=array(),$account_updates=array(),$end_calls=0;
	function query($sql,$type='') {
		$this->queries[]=$sql; $this->affected=0;
		if(strpos($sql,'SELECT gid FROM')===0) { $this->end_calls++; throw new TalentRaceArchiveStop('Stop before archive writes'); }
		if(preg_match("/UPDATE .*players SET state='5' where pid='(\d+)'/",$sql,$m)) { $this->players[$m[1]]['state']=5; return true; }
		if(strpos($sql,'RIGHT JOIN')!==false) {
			$rows=array();
			foreach($this->players as $player) {
				$user=array('username'=>null); foreach($this->users as $candidate) if($candidate['username']===$player['name']) $user=$candidate;
				$rows[]=array_merge($user,$player);
			}
			return new TalentRaceResult($rows);
		}
		if(strpos($sql,'SHOW COLUMNS')===0) return new TalentRaceResult(array(array('Field'=>'talentdata')));
		if(in_array($sql,array('START TRANSACTION','COMMIT','ROLLBACK'),true)) return true;
		if(preg_match('/^SELECT uid,username.*users WHERE uid=(\d+)/',$sql,$m)) return new TalentRaceResult(isset($this->users[$m[1]]) ? array($this->users[$m[1]]) : array());
		if(strpos($sql,'UPDATE test_users SET talentdata=')===0) {
			preg_match("/talentdata=X'([a-f0-9]*)',nicksrev=X'([a-f0-9]*)',credits2=credits2-(\d+) WHERE uid=(\d+)/",$sql,$m);
			preg_match("/COALESCE\(talentdata,''\)=X'([a-f0-9]*)'/",$sql,$base);
			$user=&$this->users[$m[4]];
			if(($user['talentdata'] ?? '')===hex2bin($base[1]) && $user['credits2'] >= $m[3]) {
				$user['talentdata']=hex2bin($m[1]); $user['nicksrev']=hex2bin($m[2]); $user['credits2']-=(int)$m[3]; $this->affected=1;
			}
			return true;
		}
		if(preg_match("/UPDATE test_users SET credits2=credits2\+(\d+) WHERE username='([^']*)'/",$sql,$m)) {
			foreach($this->users as &$user) if($user['username']===$m[2]) $user['credits2']+=(int)$m[1];
			return true;
		}
		if(strpos($sql,'players')!==false && strpos($sql,'SELECT')===0) {
			$rows=array_values($this->players);
			if(preg_match("/name=X'([a-f0-9]*)'/",$sql,$m)) $rows=array_filter($rows,function($row) use($m) { return $row['name']===hex2bin($m[1]); });
			if(strpos($sql,'race=5')!==false) $rows=array_filter($rows,function($row) { return $row['race']===5; });
			if(strpos($sql,'hp>0')!==false) $rows=array_filter($rows,function($row) { return $row['hp']>0; });
			if(strpos($sql,'pgroup=3')!==false) $rows=array_filter($rows,function($row) { return $row['pgroup']==3; });
			if(strpos($sql,'COUNT(*)')!==false) return new TalentRaceResult(array(array(count($rows))));
			return new TalentRaceResult($rows);
		}
		throw new RuntimeException('Unexpected SQL '.$sql);
	}
	function fetch_array($result) { return $result->rows[$result->index++] ?? false; }
	function num_rows($result) { return count($result->rows); }
	function result($result,$row=0,$column=0) { return array_values($result->rows[$row])[$column]; }
	function affected_rows() { return $this->affected; }
	function data_seek($result,$index) { $result->index=$index; }
	function array_insert($table,$data) { $this->lastid=max(array_merge(array(0),array_keys($this->players)))+1; $data['pid']=$this->lastid; $this->players[$this->lastid]=$data; }
	function insert_id() { return $this->lastid; }
	function multi_update($table,$updates,$key) {
		$this->account_updates=$updates;
		foreach($updates as $update) foreach($this->users as &$user) if($user[$key]===$update[$key]) $user=array_merge($user,$update);
	}
}

require GAME_ROOT.'./include/talent.func.php';
require GAME_ROOT.'./include/game/race.func.php';
require GAME_ROOT.'./include/game/wheelchair.func.php';
require GAME_ROOT.'./include/game/dissociation.func.php';
require GAME_ROOT.'./include/game/item.ending.php';
$test_enabled=true; $club_skills=1; $db=new TalentRaceDb(); $gtablepre='test_'; $tablepre='test_'; $now=1000; $nosta='∞'; $log=''; $club_skillslist=array(1=>array('test_club_skill'));

talent_test_assert(talent_cap()===80,'160 点树总成本给出 80 点投入上限');
$talent=talent_load(''); talent_sync_earned($talent,array(630=>array('l'=>1),631=>array('l'=>0),657=>array('l'=>1),1=>array('l'=>99)));
talent_test_assert($talent['earned_points']===2,'仅已完成的新路线成就给点');
talent_sync_earned($talent,array(630=>array('l'=>1),657=>array('l'=>1))); talent_test_assert($talent['earned_points']===2,'重复同步不重复发点');
$talent['bought_points']=78;
talent_test_reject(function() use(&$talent) { talent_change($talent,'learn','blasty',2); },'节点前置条件由服务端检查');
foreach(array_slice(array_keys(talent_catalog()),0,8) as $tree) for($node=0;$node<6;$node++) talent_change($talent,'learn',$tree,$node);
talent_test_assert(talent_cost($talent)===80,'允许点满半数树');
talent_test_reject(function() use(&$talent) { talent_change($talent,'learn','bishop',0); },'超过半数投入被拒绝');
$collected=$talent['unlocked_skills']; talent_change($talent,'reset');
talent_test_assert(talent_cost($talent)===0 && $talent['unlocked_skills']===$collected && in_array(2,$talent['unlocked_races'],true),'洗点返还投入并保留技能、头衔与种族');
$player=array('pid'=>1,'name'=>'tester','type'=>0,'race'=>0,'nick'=>9310,'club'=>1,'hp'=>100,'mhp'=>100,'sp'=>100,'msp'=>100,'clbpara'=>array());
talent_entry($player,array('talentdata'=>$talent),2);
talent_test_assert(isset($player['clbpara']['talent_skills']['npc_honor_fist']) && count($player['clbpara']['talent_skills'])===2,'洗点后佩戴头衔仍携带永久技能，未携带其他树技能');
talent_test_assert($player['mhp']===100 && strpos($player['arb'],'妖精羽翼·')===0 && race_fairy_bound($player)!=='','洗点后白值消失，妖精入场绑定有效属性');
talent_test_assert(!race_can_remove($player,'arb') && race_can_remove($player,'arf'),'妖精只锁定身体装备槽');
$wounded_entry=array('type'=>0,'race'=>0,'nick'=>0,'hp'=>1,'mhp'=>100,'sp'=>2,'msp'=>100,'clbpara'=>array()); talent_entry($wounded_entry,array('talentdata'=>''),0);
talent_test_assert($wounded_entry['hp']===1 && $wounded_entry['sp']===2,'无投入的天赋不会把原头衔残血入场恢复成满血');
talent_test_reject(function() use(&$player) { talent_entry($player,array('talentdata'=>''),5); },'未解锁种族无法入场');
$test_enabled=false; $old=array('type'=>0,'race'=>2,'nick'=>9310,'mhp'=>100,'hp'=>70,'msp'=>100,'sp'=>70,'clbpara'=>array()); talent_entry($old,array('talentdata'=>$talent),2);
talent_test_assert($old['race']===2 && empty($old['clbpara']) && $old['hp']===70,'关闭模式保留原规则种族与生命，不注入天赋技能'); $test_enabled=true;

$db->users[1]=array('uid'=>1,'username'=>'tester','password'=>'pw','groupid'=>1,'credits2'=>10000,'talentdata'=>'','achrev'=>'{}','nicksrev'=>'{"nicks":[0]}');
$input=array('base'=>hash('sha256',''),'talent_action'=>'buy'); talent_save_action(1,'pw',$input);
talent_test_assert($db->users[1]['credits2']===9900 && talent_load($db->users[1]['talentdata'])['bought_points']===1,'购买与扣费同一次条件更新保存');
talent_test_reject(function() use($input) { talent_save_action(1,'pw',$input); },'重复提交旧页面不会重复购买');
$input=array('base'=>hash('sha256',$db->users[1]['talentdata']),'talent_action'=>'learn','tree'=>'blasty','node'=>'0'); talent_save_action(1,'pw',$input);
talent_test_assert(in_array(9310,json_decode($db->users[1]['nicksrev'],true)['nicks'],true),'根节点把头衔写入现有头衔列表');
$db->players[1]=$player;
$reset=array('base'=>hash('sha256',$db->users[1]['talentdata']),'talent_action'=>'reset');
talent_test_reject(function() use($reset) { talent_save_action(1,'pw',$reset); },'当前房间有存活主机体时禁止洗点');
$db->players[1]['hp']=0; talent_save_action(1,'pw',$reset);
talent_test_assert(talent_cost(talent_load($db->users[1]['talentdata']))===0,'死亡后可洗点并保存');

$db->players=array(); $active=array('pid'=>1,'name'=>'tester','type'=>0,'race'=>5,'nick'=>9310,'club'=>1,'hp'=>100,'mhp'=>100,'sp'=>100,'msp'=>100,'att'=>100,'def'=>100,'wp'=>80,'wk'=>1,'wg'=>1,'wc'=>1,'wd'=>1,'wf'=>1,'skillpoint'=>50,'money'=>777,'pls'=>5,'pgroup'=>0,'state'=>0,'bid'=>0,'action'=>'','wep'=>'宝刀','wepk'=>'WK','wepe'=>999,'weps'=>'∞','clbpara'=>array('talent_skills'=>array('npc_honor_fist'=>1),'talent_bonus'=>array('mhp'=>40),'skill'=>array('npc_honor_fist')));
$live_name=&$active['name']; player_save($active); $id=race_ai_create_body($active,true);
talent_test_assert($id===2 && $active['name']==='tester' && $live_name==='tester','部署机体不会通过 EXTR_REFS 覆盖主机体名字');
talent_test_assert($db->players[2]['name']==='tester#AI1' && $db->players[2]['money']===0 && $db->players[2]['wep']==='拳头' && $db->players[2]['wp']===80,'分体独立行、不复制金钱物品、不重复发放社团白值');
talent_test_assert(race_ai_create_body($active,true)===false && count($db->players)===2,'初始分体只创建一次');
$paid=race_ai_create_body($active); talent_test_assert($paid===3 && $active['skillpoint']===30,'部署新分体消耗 20 技能点');
race_ai_switch($active,'2'); talent_test_assert($active['pid']===2 && $db->players[1]['clbpara']['ai_active_pid']===2 && $db->players[1]['clbpara']['ai_active']===0 && $db->players[2]['clbpara']['ai_active']===1,'切换激活标记与主行指针一起保存');
talent_test_assert(in_array('START TRANSACTION',$db->queries,true) && in_array('COMMIT',$db->queries,true),'机体切换使用数据库事务');
talent_test_assert(race_ai_resolve_active($db->players[1])['pid']===2,'重新加载主行可解析活动分体');
$db->players[1]['clbpara']['wheelchair']['learned']['wc_hunt']=1;
talent_test_assert(wheelchair_penalty_count($active)===1,'主机学过轮椅技能后切换旧分体不能绕过分流');
$active['clbpara']['wheelchair']['learned']['wc_route']=1;
talent_test_assert(wheelchair_penalty_count($active)===2,'尚未保存的当前分体轮椅学习也计入账户');
$pdata=$active; $before_credits=$db->users[1]['credits2'];
talent_test_assert(wheelchair_award_credits2('tester#AI1',100)===25 && $db->users[1]['credits2']===$before_credits+25,'分体成就切糕归属账户并计入尚未保存的轮椅折扣');
unset($pdata);
$db->players[3]['clbpara']['wheelchair']['learned']['wc_grow']=1;
talent_test_assert(wheelchair_credit_gain(100,'tester')===25,'重新读取账户奖励时仍合并其他机体学过的轮椅技能');
unset($db->players[3]['clbpara']['wheelchair']);
$db->players[1]['endtime']=0;
talent_test_assert(race_ai_recent_activity($db->players[1],900),'正在操纵分体时，待机主机不会被误判挂机');
talent_test_assert(race_ai_switch($active,'3')===false && $active['pid']===2,'切换冷却期间禁止再次切换');
$now+=31; $active['action']='enemy'; talent_test_assert(race_ai_switch($active,'3')===false,'战斗中禁止切换'); $active['action']='';
race_ai_enter_city($active,'true',6000); player_save($active);
foreach($db->players as $body) talent_test_assert($body['pgroup']===3 && $body['clbpara']['dissociation_deadline']===6000,'机体 '.$body['pid'].' 同时进入湾城并共享截止时间');
talent_test_assert(race_alive_accounts()===1,'三个机体只算一个存活账户');
foreach($db->players as &$body) { $body['pgroup']=0; unset($body['clbpara']['dissociation_deadline']); } unset($body);
$active=$db->players[2]; $active['race']=0; $active['clbpara']['platform']=array('active'=>1,'original'=>array('race'=>5));
$active['clbpara']['sendo']=array('done'=>array_fill_keys(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7'),1),'gentle'=>array_fill_keys(array('QD1','QD2','QD3','QD4'),1));
$gamevars=array('dissociation'=>array('phantom'=>array('stage'=>0))); dissociation_enter($active,1); player_save($active);
talent_test_assert($active['race']===5 && empty($active['clbpara']['platform']) && $db->players[1]['pgroup']===3 && $db->players[3]['pgroup']===3,'投射暂改种族的AI先恢复本体，再把所有机体带入湾城');
$alivenum=0; $active['hp']=0; $active['state']=20; race_ai_true_death($active); player_save($active);
talent_test_assert($alivenum===1 && race_ai_resolve_active($db->players[1])['pid']===1,'分体被击破恢复主机连接并保留存活账户');
$reconnected=$active; talent_test_assert(race_ai_reconnect($reconnected) && $reconnected['pid']===1,'同一次指令可以从死去分体返回主机');
race_ai_true_death($active); talent_test_assert($alivenum===1,'重复分体死亡回调不增加存活人数');
$stale_body=$db->players[3];
$master=$db->players[1]; $master['hp']=0; $master['state']=20; $alivenum=0; race_ai_true_death($master); player_save($master);
talent_test_assert($db->players[3]['hp']===0 && race_alive_accounts()===0,'主机体死亡时全部分体同时失效');
$master['deathtime']=$now; player_save($master); race_ai_sync_master($stale_body);
talent_test_assert($stale_body['hp']===0,'旧的活动分体数组不会覆盖主机死亡造成的全灭');
talent_test_assert(race_account_name('tester#AI1')==='tester','分体奖励解析到唯一账户');

$fish=array('type'=>0,'race'=>4,'clbpara'=>array());
talent_test_assert(race_move_cost($fish,100,'move')===100,'鱼人第一次移动使用基础消耗');
for($i=0;$i<5;$i++) race_action($fish,'move');
talent_test_assert(race_move_cost($fish,100,'move')===40,'鱼人连续移动最多降至四成'); race_action($fish,'combat');
talent_test_assert(race_move_cost($fish,100,'move')===100,'交战后鱼人连移重新计数');
$fish['clbpara']['talent_bonus']['rest_time']=9; talent_test_assert(talent_rest_required($fish,180)===164,'天赋静养减时使用当前投入快照');
$dragon=array('type'=>0,'race'=>3,'money'=>0,'itm1'=>'普通刀','itmk1'=>'WK','itme1'=>10,'itms1'=>2,'itmsk1'=>'','itmpara1'=>'');
talent_test_assert(race_sell($dragon,'1') && $dragon['money']===40 && $dragon['itm1']==='','龙变现完整清空槽位并收款');
talent_test_assert(!race_sell($dragon,'1') && $dragon['money']===40,'重复变现空槽不再获得金钱');
$dragon['itm1']='任务物'; $dragon['itmk1']='Q3'; $dragon['itms1']=1; talent_test_assert(!race_sell($dragon,'1'),'任务绑定物禁止变现');
$dragon['itm1']='引路灯'; $dragon['itmk1']='DA'; $dragon['itmpara1']=array('IsQuestItem'=>1,'QuestID'=>'QD5'); talent_test_assert(!race_sell($dragon,'1'),'正常装备种类的 QUEST 绑定物同样禁止变现');

require GAME_ROOT.'./include/system.func.php';
foreach($db->players as &$body) { $body['hp']=100; $body['killnum']=0; $body['lvl']=10; $body['pgroup']=0; } unset($body);
$gamenum='talent_race_test_no_records'; $gamestate=40; $validnum=1; $winner=''; $starttime=0;
if(is_dir(GAME_ROOT.'./records/'.$gamenum)) throw new RuntimeException('测试隔离目录已经存在。');
try { gameover($now); } catch(TalentRaceArchiveStop $e) {}
talent_test_assert($winner==='tester' && $winmode===2 && $db->players[1]['state']===5,'自动最后幸存按一个账户判胜并选择主机体');
try { gameover($now,'end7','tester#AI1'); } catch(TalentRaceArchiveStop $e) {}
talent_test_assert($winner==='tester#AI1' && (int)$winmode===7,'分体主动触发结局保留机体名');
$gamblingon=false; $db->users[1]+=array('credits'=>0,'wingames'=>0,'validgames'=>0); set_credits();
talent_test_assert(count($db->account_updates)===1 && isset($db->account_updates['tester']) && $db->users[1]['wingames']===1 && $db->users[1]['validgames']===1,'三个机体只给所属账户增加一次胜场与参加场数');
talent_test_assert($db->users[1]['credits']>=10000,'分体触发解离仍给所属账户发放胜者积分');

foreach(array('phantom'=>'【解离凭证·幻境】','true'=>'【解离凭证·真实】') as $route=>$itemname) {
	$proof=$db->players[2]; $proof['state']=0; $proof['pgroup']=3; $proof['clbpara']['dissociation_route']=$route; $proof['clbpara']['dissociation_deadline']=$now+4500;
	$proof['itm1']=$itemname; $proof['itmk1']='Y'; $proof['itme1']=1; $proof['itms1']=1; $proof['itmsk1']='v'; $proof['itmpara1']='';
	$gamevars=array('dissociation'=>array($route=>array('collapsed'=>0))); $before_ends=$db->end_calls;
	item_ending(1,$proof);
	talent_test_assert($proof['itms1']===1 && $proof['state']===0 && $db->end_calls===$before_ends,$route.' 守望者未全灭时凭证不消耗、不结局');
	$gamevars['dissociation'][$route]['collapsed']=1; $proof['pgroup']=0; item_ending(1,$proof);
	talent_test_assert($proof['itms1']===1 && $db->end_calls===$before_ends,$route.' 城外使用凭证被拒绝');
	$proof['pgroup']=3; $proof['clbpara']['dissociation_route']=$route==='true'?'phantom':'true'; item_ending(1,$proof);
	talent_test_assert($proof['itms1']===1 && $db->end_calls===$before_ends,$route.' 不同路线凭证不能串用');
	$proof['clbpara']['dissociation_route']=$route; $gamestate=40;
	try { item_ending(1,$proof); } catch(TalentRaceArchiveStop $e) {}
	talent_test_assert($db->end_calls===$before_ends+1 && $winner==='tester#AI1' && (int)$winmode===7 && $db->players[2]['state']===6 && !$db->players[2]['itms1'] && $saved_talent_gamevars['ending_subroute']==='jieli_'.$route && $db->players[2]['clbpara']['ending_subroute']==='jieli_'.$route,$route.' 凭证先持久化子路线、胜者状态及物品消耗，再调用真实gameover');
	item_ending(1,$proof); talent_test_assert($db->end_calls===$before_ends+1,$route.' 同一凭证重复使用不再次结算');
}

// 使用项目的真实模板编译器检查新面板；只写、清理本测试自己的编译文件。
function language($file,$id,$dir) { return GAME_ROOT.'./gamedata/cache/talent_1.php'; }
function template($file) { return GAME_ROOT.'./gamedata/templates/talent_test_'.$file.'.tpl.php'; }
require GAME_ROOT.'./include/template.func.php';
$compiled=array();
try {
	foreach(array('user_talent','race_equip','race_command','valid','user','itemfind','skillpage') as $file) {
		parse_template($file,'talent_test','templates/default'); $compiled[]=template($file);
		token_get_all(file_get_contents(template($file)),TOKEN_PARSE);
	}
	talent_test_assert(true,'7 个受影响模板经真实编译器生成合法 PHP');
	$talent_available=true; $talent_notice=''; $talent_data=$talent; $talent_base='test'; $talent_csrf='test'; $talent_used=0; $talent_limit=80; $talent_free=80; $talent_buy_price=100; $talent_reset_price=100;
	$talent_user=array('credits2'=>999); $talent_trees=talent_catalog(); $raceinfo=array(0=>'人类',1=>'兽人',2=>'妖精',3=>'龙',4=>'鱼人',5=>'ＡＩ');
	set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
	ob_start(); include template('user_talent'); $rendered=ob_get_clean();
	talent_test_assert(substr_count($rendered,'name="talent_action" value="learn"')===96,'天赋面板渲染 16 棵树的 96 个真实提交节点');
	$pdata=$dragon; $pid=1; ob_start(); include template('race_command'); $rendered=ob_get_clean();
	talent_test_assert(strpos($rendered,'race_sell')!==false,'龙面板包含实际变现指令');
	$pdata=$db->players[1]; $pdata['hp']=100; $pid=1; ob_start(); include template('race_command'); $rendered=ob_get_clean();
	talent_test_assert(strpos($rendered,'race_ai_switch')!==false && strpos($rendered,'race_ai_deploy')!==false,'ＡＩ面板包含切换与部署指令');
	restore_error_handler();
} finally { foreach($compiled as $file) if(is_file($file)) unlink($file); }
echo "Talent/race regression complete.\n";
