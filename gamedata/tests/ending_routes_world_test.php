<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
function world_assert($value,$message) { if(!$value) throw new RuntimeException('FAIL: '.$message); echo 'PASS: '.$message."\n"; }
function ending_routes_enabled() { return $GLOBALS['world_enabled']; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode((string)$value,true) ?: array()); }
function get_itmpara($value) { return get_clbpara($value); }
function get_questcfg() { include GAME_ROOT.'./gamedata/questcfg_1.php'; return array($questcfg,$questcfg_global); }
function save_gameinfo() { $GLOBALS['saved_gamevars']=json_decode(json_encode($GLOBALS['gamevars']),true); }
function player_save($data) { $copy=json_decode(json_encode($data),true); $copy['clbpara']=json_encode($copy['clbpara']); $GLOBALS['db']->players[$copy['pid']]=$copy; }
function fetch_playerdata_by_pid($id) { $data=$GLOBALS['db']->players[$id] ?? null; if($data) $data['clbpara']=get_clbpara($data['clbpara']); return $data; }
function destory_single_item(&$data,$slot) { foreach(array('itm','itmk','itmsk','itmpara') as $field) $data[$field.$slot]=''; $data['itme'.$slot]=$data['itms'.$slot]=0; }
function check_add_searchmemory($pid,$kind,$name,&$data) { $data['clbpara']['smeo'][]=array($pid,$kind,$name); }
function get_safe_plslist($mode=0) { return array_values(array_slice($GLOBALS['arealist'],$GLOBALS['areanum']+1)); }
function wheelchair_penalty_count($data) { return (int)($data['clbpara']['test_wheelchair'] ?? 0); }
function template($name) { return GAME_ROOT.'./gamedata/cache/dissociation_1.php'; }
function erc_config($key,$default=null) { include GAME_ROOT.'./gamedata/cache/dissociation_1.php'; return $GLOBALS['world_config'][$key] ?? $dissociation[$key] ?? $default; }
function addnews() { $GLOBALS['world_news'][]=func_get_args(); }
function gameover() { throw new RuntimeException('BOSS 死亡不能自动触发新解离结局。'); }
function ending_routes_give(&$data,$item) {
	$item+=array('itmk'=>'Y','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>'');
	for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) { foreach($item as $field=>$value) $data[$field.$i]=$value; return; }
	$GLOBALS['dropped_items'][]=$item;
}
function world_player($id=1) {
	$row=array('pid'=>$id,'name'=>'玩家'.$id,'type'=>0,'race'=>0,'hp'=>1000,'mhp'=>1000,'sp'=>1000,'msp'=>1000,'money'=>321,'lvl'=>30,'att'=>400,'def'=>400,'pls'=>34,'pgroup'=>0,'horizon'=>0,'wp'=>200,'wk'=>0,'wc'=>0,'wd'=>0,'wg'=>0,'wf'=>0,'clbpara'=>array('skill'=>array('test_skill')),'wep'=>'旧武器','wepk'=>'WK','wepe'=>900,'weps'=>100,'wepsk'=>'u','weppara'=>array('tag'=>1),'wep2'=>'藏起来的副武器','wep2k'=>'WF','wep2e'=>99999,'wep2s'=>999,'wep2sk'=>'r','wep2para'=>array('tag'=>2),'ara'=>'','aras'=>0);
	for($i=0;$i<=6;$i++) destory_single_item($row,$i);
	return $row;
}
function addnpc($type,$sub,$number=1,$mode=0,$extra=array()) {
	$GLOBALS['spawn_calls'][]=array($type,$sub,$extra); $ids=array();
	for($i=0;$i<$number;$i++) {
		$row=world_player(++$GLOBALS['db']->nextid); $row=array_merge($row,array('type'=>$type,'name'=>'NPC'.$type.'_'.$sub,'mhp'=>10000,'clbpara'=>array('er_sub'=>$sub)),$extra);
		$row['hp']=$row['mhp']; $row['clbpara']=array_merge(array('er_sub'=>$sub),$row['clbpara']); player_save($row); $ids[]=$row['pid'];
	}
	return $ids;
}
class WorldResult { public $rows,$index=0; function __construct($rows) { $this->rows=array_values($rows); } }
class WorldDb {
	public $players=array(),$mapitem=array(),$nextid=100;
	function query($sql) {
		if(preg_match('/^DELETE FROM .*players WHERE type=(\d+)/',$sql,$m)) { foreach($this->players as $id=>$row) if($row['type']==$m[1]) unset($this->players[$id]); return true; }
		if(strpos($sql,'SELECT')===0 && strpos($sql,'players')!==false) {
			$rows=array_values($this->players);
			preg_match_all('/\b(type|hp|pls|pgroup|horizon|pid)\s*(<>|>=|<=|=|>|<)\s*\x27?(\d+)\x27?/',$sql,$conditions,PREG_SET_ORDER);
			foreach($conditions as $condition) $rows=array_filter($rows,function($row) use($condition) { $a=(int)($row[$condition[1]] ?? 0); $b=(int)$condition[3]; switch($condition[2]) { case '=':return $a===$b; case '<>':return $a!==$b; case '>':return $a>$b; case '<':return $a<$b; case '>=':return $a>=$b; case '<=':return $a<=$b; } });
			if(preg_match("/name='([^']*)'/",$sql,$m)) $rows=array_filter($rows,function($row) use($m) { return $row['name']===$m[1]; });
			if(strpos($sql,'COUNT(*)')!==false) return new WorldResult(array(array(count($rows))));
			return new WorldResult($rows);
		}
		throw new RuntimeException('Unexpected SQL '.$sql);
	}
	function fetch_array($result) { return $result->rows[$result->index++] ?? false; }
	function result($result,$row=0,$column=0) { return array_values($result->rows[$row])[$column]; }
	function num_rows($result) { return count($result->rows); }
	function array_insert($table,$data) { $this->mapitem[]=$data; }
}
function world_command(&$data,$command) { ob_start(); sendo_command($data,$command); ob_end_clean(); }
function world_give(&$data,$slot,$name,$kind='Y',$amount=1) { $data['itm'.$slot]=$name; $data['itmk'.$slot]=$kind; $data['itme'.$slot]=1; $data['itms'.$slot]=$amount; $data['itmsk'.$slot]=''; $data['itmpara'.$slot]=''; }
function world_kill($id,&$killer) { $victim=fetch_playerdata_by_pid($id); $victim['hp']=0; player_save($victim); sendo_npc_dead($victim,$killer); return $victim; }

require GAME_ROOT.'./include/game/quest_sendo.func.php';
require GAME_ROOT.'./include/game/dissociation.func.php';
$db=new WorldDb(); $tablepre='test_'; $world_enabled=true; $world_config=array(); $gamevars=array(); $spawn_calls=array(); $world_news=array(); $dropped_items=array(); $now=1000; $nosta='∞'; $log=''; $cmd=''; $mode='command'; $arealist=range(0,34); $areanum=0; $areaadd=3; $areatime=2000;
$p=world_player(); player_save($p); $world_enabled=false; $gamevars['sendo_open']=1; sendo_tick($p); world_assert(!$spawn_calls,'关闭新路线时不生成公共事件'); $world_enabled=true;
world_give($p,1,'【动物团的七封请柬】','ticket'); sendo_item($p,1); player_save($p);
world_assert(isset($p['clbpara']['sendo']['done']) && empty($p['itm1']),'请柬激活七项公共任务并消耗物品');
$initial=count($spawn_calls); $second=world_player(2); world_give($second,1,'【动物团的七封请柬】','ticket'); sendo_item($second,1); player_save($second); sendo_tick($p);
world_assert(count($spawn_calls)===$initial && $gamevars['sendo']['cycle']===1,'第二人入场及重复调度不复制公共事件NPC');
$legacy=world_player(9); world_give($legacy,1,'侦探眼镜','YQ',3); $legacy['itmpara1']=array('QuestID'=>'Q6','QuestAction'=>'clue');
world_assert(sendo_item($legacy,1)===false && $legacy['itms1']===3,'旧Q6侦探眼镜继续交给原任务处理，不被送行工具拦截');
$w=$gamevars['sendo']; $goose=fetch_playerdata_by_pid($w['geese'][0]); $before=$goose['hp']; $now+=30; sendo_tick($p);
world_assert(fetch_playerdata_by_pid($w['geese'][0])['hp']<$before,'未清除猎人时鹅群实际承受公共压力');
$p['clbpara']['sendo']['done']['QD4']=1; $p['clbpara']['sendo']['bait']=1; world_command($p,'sendo_reset_QD4');
world_assert(count($spawn_calls)===$initial && empty($p['clbpara']['sendo']['bait']) && empty($p['clbpara']['sendo']['done']['QD4']),'个人重置清局部进度且不补刷公共怪');

$mist_ids=array(); foreach($db->players as $id=>$row) if($row['type']===38 && (get_clbpara($row['clbpara'])['sendo_qid'] ?? '')==='QD5') $mist_ids[]=$id;
foreach($mist_ids as $id) world_kill($id,$p);
world_assert(!empty($p['clbpara']['sendo']['done']['QD5']) && empty($p['clbpara']['sendo']['gentle']['QD5']),'未领灯直接击破三只雾影可完成暴力捷径');

$bully=null; foreach($db->players as $row) if($row['type']===38 && (get_clbpara($row['clbpara'])['sendo_qid'] ?? '')==='QD3') $bully=$row;
$p['pls']=$bully['pls']; world_give($p,1,'捕绳'); sendo_item($p,1);
$captured=fetch_playerdata_by_pid($bully['pid']); world_assert($captured['hp']===1 && !empty($captured['clbpara']['sendo_captured']) && !empty($p['clbpara']['sendo']['gentle']['QD3']),'捕绳留一血生擒并记录温柔完成');
$second['pls']=34; world_give($second,1,'捕绳'); sendo_item($second,1);
world_assert(empty($second['clbpara']['sendo']['done']['QD3']) && $second['itms1']===1,'其他玩家不能重复生擒已捕获的同一恶霸');

$p['pls']=34; world_give($p,2,'备用电池','B',3); world_command($p,'sendo_batteries');
world_assert(empty($p['itm2']) && !empty($p['clbpara']['sendo']['done']['QD6']) && empty($p['clbpara']['sendo']['gentle']['QD6']),'三枚电池完成普通电台任务');
world_give($p,2,'自己的护甲','DB'); world_command($p,'sendo_donate_2');
world_assert($p['itm2']==='自己的护甲' && !empty($p['clbpara']['sendo']['donate_confirm']),'捐装预览不消耗装备');
$p['itme2']=9; world_command($p,'sendo_donate_confirm'); world_assert($p['itm2']==='自己的护甲' && empty($p['clbpara']['sendo']['gentle']['QD6']),'确认期间装备改变则拒绝消耗');
world_command($p,'sendo_donate_2'); world_command($p,'sendo_donate_confirm');
world_assert(empty($p['itm2']) && !empty($p['clbpara']['sendo']['gentle']['QD6']),'确认后交付同一件自有装备并温柔完成');
world_give($p,2,'另一件护甲','DB'); world_command($p,'sendo_donate_confirm'); world_assert($p['itm2']==='另一件护甲','重复捐装确认不吞下一件装备');
$second['pls']=34; world_give($second,2,'电池','B',3); world_command($second,'sendo_batteries'); world_give($second,2,'自己的护甲','DB'); world_command($second,'sendo_donate_2'); world_command($second,'sendo_reset_QD6');
world_command($second,'sendo_donate_confirm'); world_assert($second['itm2']==='自己的护甲' && empty($second['clbpara']['sendo']['batteries']),'重置清除确认与电池进度，等待周期期间不吞捐装');
world_give($second,3,'备用电池','B',3); world_command($second,'sendo_batteries'); world_assert($second['itms3']===3,'等待下周期期间不提前吞电池');

$p['clbpara']['sendo']['done']=array_fill_keys(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7'),1); world_command($p,'sendo_ticket');
$ticket_count=count($dropped_items); for($i=1;$i<=6;$i++) if($p['itm'.$i]==='【湾城通票】') $ticket_count++;
world_command($p,'sendo_ticket'); $after_count=count($dropped_items); for($i=1;$i<=6;$i++) if($p['itm'.$i]==='【湾城通票】') $after_count++;
world_assert($ticket_count===1 && $after_count===1,'完成七项后只发一张通票，重复指令不重复发奖');
player_save($p); $reload=fetch_playerdata_by_pid($p['pid']); world_assert($reload['clbpara']['sendo']===$p['clbpara']['sendo'],'个人送行与领票标记重新读取后保持');

$traveler=world_player(3); $traveler['clbpara']['sendo']=array('done'=>array_fill_keys(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7'),1),'gentle'=>array('QD1'=>1,'QD2'=>1,'QD3'=>1)); world_give($traveler,1,'【湾城通票】','ticket');
dissociation_enter($traveler,1); world_assert($traveler['pgroup']===0 && $traveler['wep']==='旧武器' && $traveler['itm1']==='【湾城通票】','温柔次数不足时不吞票、不扒装备');
$traveler['clbpara']['sendo']['gentle']['QD4']=1; $gamevars['dissociation']['phantom']=array('collapsed'=>1); dissociation_enter($traveler,1);
world_assert($traveler['pgroup']===0 && $traveler['itm1']==='【湾城通票】','路线已坍缩时后来者保留通票与装备');
unset($gamevars['dissociation']); dissociation_enter($traveler,1);
world_assert($traveler['pgroup']===3 && $traveler['pls']===120 && $traveler['clbpara']['dissociation_deadline']===$now+4500,'四次温柔入场同步切换地图组、地点与独立75分钟期限');
world_assert($traveler['wep2']==='' && $traveler['wep2para']==='' && $traveler['money']===321 && $traveler['att']===400 && in_array('test_skill',$traveler['clbpara']['skill'],true),'扒光包含副武器及para，保留金钱裸属性与技能');
world_assert(count($gamevars['dissociation']['phantom']['animals'])===4 && count(array_unique($gamevars['dissociation']['phantom']['animals']))===4,'动物团每局七抽四固定且不重复');
$draw=$gamevars['dissociation']['phantom']['animals']; $count=count($spawn_calls); $other=world_player(4); $other['clbpara']['sendo']=$traveler['clbpara']['sendo']; world_give($other,1,'【湾城通票】','ticket'); dissociation_enter($other,1);
world_assert($draw===$gamevars['dissociation']['phantom']['animals'] && count($spawn_calls)===$count,'其他玩家进入同路线不重抽、不补刷公共BOSS');
$traveler['wp']+=20; $power=$traveler['wepe']; dissociation_growth($traveler); world_assert($traveler['wepe']===$power+40,'应急成长武器随对应熟练增长');

$fairy=world_player(5); $fairy['race']=2; $fairy['arb']='妖精羽翼·炎'; $fairy['arbk']='DB'; $fairy['arbe']=50; $fairy['arbs']='∞'; $fairy['arbsk']=''; $fairy['arbpara']=''; dissociation_strip($fairy,'true',$now+4500);
world_assert($fairy['arb']==='妖精羽翼·炎' && $fairy['horizon']===2 && !empty($fairy['clbpara']['ex_lock']),'真路线强制并锁定EX，扒光保留妖精种族羽翼');
$true_player=world_player(6); $true_player['clbpara']['sendo']=array('done'=>array_fill_keys(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7'),1),'gentle'=>array_fill_keys(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7'),1)); $true_player['clbpara']['test_wheelchair']=1; world_give($true_player,1,'【湾城通票】','ticket'); dissociation_enter($true_player,1);
world_assert($true_player['clbpara']['dissociation_route']==='phantom' && empty($true_player['clbpara']['ex_lock']),'轮椅玩家即使七项温柔也转入伪路线');

$hero=world_player(7); $hero['clbpara']['sendo']=$true_player['clbpara']['sendo']; world_give($hero,1,'【湾城通票】','ticket'); dissociation_enter($hero,1); player_save($hero);
$world_config=array('stage_time_target'=>100,'stage_time_ratio'=>0.5,'stage_time_direction'=>'fast');
$extra_ids=array(); foreach($db->players as $row) if($row['type']===40) $extra_ids[]=$row['pid'];
world_assert(count($extra_ids)===1 && $gamevars['dissociation']['true']['stage']===0,'真路线阶段一最初只生成第一位Extra');
$extra=fetch_playerdata_by_pid($extra_ids[0]); $extra['hp']=0; player_save($extra); $now+=60; $deadline=$hero['clbpara']['dissociation_deadline']; dissociation_npc_dead($extra,$hero);
world_assert($gamevars['dissociation']['true']['stage']===1 && $hero['clbpara']['dissociation_deadline']===$deadline+20,'速杀按配置目标、比例给入城者加时');
$spawns=count($spawn_calls); dissociation_npc_dead($extra,$hero); world_assert(count($spawn_calls)===$spawns && $hero['clbpara']['dissociation_deadline']===$deadline+20,'同一Extra重复死亡回调不重复刷怪或加时');
for($stage=1;$stage<=2;$stage++) {
	$target=null; foreach($db->players as $row) if($row['type']===40 && $row['hp']>0) $target=fetch_playerdata_by_pid($row['pid']);
	$target['hp']=0; player_save($target); $now+=120; dissociation_npc_dead($target,$hero);
}
$dark=null; foreach($db->players as $row) if($row['type']===12 && $row['hp']>0) $dark=fetch_playerdata_by_pid($row['pid']);
world_assert($dark!==null && $gamevars['dissociation']['true']['stage']===3,'前三位Extra依次击破后才生成Dark Force');
$dark['name']='Dark Force'; $dark['hp']=0; player_save($dark); dissociation_npc_dead($dark,$hero);
$retro_ids=array(); foreach($db->players as $row) if($row['type']===39 && $row['hp']>0) $retro_ids[]=$row['pid'];
world_assert(count($retro_ids)===8,'Dark Force真身击破后一次生成八位旧教头');
foreach($retro_ids as $i=>$id) { $target=fetch_playerdata_by_pid($id); $target['hp']=0; player_save($target); dissociation_npc_dead($target,$hero); if($i<7) world_assert(empty($gamevars['dissociation']['true']['pillars']),'旧教头未全灭时不提前生成终局BOSS'); }
$pillar_ids=array(); foreach($db->players as $row) if($row['type']===41 && $row['hp']>0) $pillar_ids[]=$row['pid'];
world_assert(count($pillar_ids)===2,'八位旧教头全灭后生成两位终局BOSS');
foreach($pillar_ids as $id) { $target=fetch_playerdata_by_pid($id); $target['hp']=0; player_save($target); dissociation_npc_dead($target,$hero); }
world_assert(!empty($gamevars['dissociation']['true']['collapsed']),'两柱全灭仅关闭后来者入口，结局仍需使用凭证');
$reloaded_world=json_decode(json_encode($gamevars),true); $gamevars=$reloaded_world; $spawns=count($spawn_calls); dissociation_npc_dead($target,$hero);
world_assert(count($spawn_calls)===$spawns,'房间状态重载后重复回调也不重新生成终局BOSS');

// 守护失败不等于暴力捷径；只有提前击破六位袭击者才可跳过驻守。
foreach(array('few_kills','left','dante_dead','success','early_six') as $case) {
	$db=new WorldDb(); $gamevars=array('sendo_open'=>1); $now=3000;
	$guard=world_player(20); $guard['clbpara']['sendo']=array('done'=>array(),'gentle'=>array()); player_save($guard);
	sendo_tick($guard); $world=$gamevars['sendo']; $guard['pls']=$world['points'][1];
	$guard['clbpara']['sendo']+=array('guard_cycle'=>$world['cycle'],'guard_start'=>$now,'guard_kills'=>$case==='few_kills'?1:2);
	if($case==='early_six') {
		$guard['clbpara']['sendo']['guard_kills']=0; $now+=90; sendo_tick($guard);
		$attackers=array(); foreach($db->players as $id=>$row) if($row['type']===38 && (get_clbpara($row['clbpara'])['sendo_qid'] ?? '')==='QD2') $attackers[]=$id;
		foreach($attackers as $id) world_kill($id,$guard);
		world_assert(!empty($guard['clbpara']['sendo']['done']['QD2']) && empty($guard['clbpara']['sendo']['gentle']['QD2']),'提前击破完整六人波次仍可完成暴力捷径');
		continue;
	}
	if($case==='left') $guard['pls']=34;
	if($case==='dante_dead') { $dante=fetch_playerdata_by_pid($world['dante']); $dante['hp']=0; player_save($dante); }
	player_save($guard); $now=$world['end']; sendo_tick($guard); player_save($guard);
	$guard=fetch_playerdata_by_pid($guard['pid']);
	if($case==='success') world_assert(!empty($guard['clbpara']['sendo']['gentle']['QD2']),'驻守满期、至少两杀且Dante存活才温柔完成');
	else world_assert(empty($guard['clbpara']['sendo']['done']['QD2']),'守护失败保持未完成并可下周期重试：'.$case);
}

foreach(array(20=>true,40=>false) as $return_after=>$should_complete) {
	$db=new WorldDb(); $gamevars=array('sendo_open'=>1); $now=4000; $arealist=range(0,34); $areatime=5000;
	$guard=world_player(21); $guard['clbpara']['sendo']=array('done'=>array(),'gentle'=>array()); player_save($guard); sendo_tick($guard);
	$world=$gamevars['sendo']; $guard['pls']=$world['points'][1]; sendo_action($guard,'move'); $guard['clbpara']['sendo']['guard_kills']=2;
	$now=4010; $guard['pls']=34; sendo_action($guard,'move'); world_assert(!isset($guard['clbpara']['sendo']['guard_start']),'离开驻守点中断本次连续计时');
	$now=4000+$return_after; $guard['pls']=$world['points'][1]; sendo_action($guard,'move');
	world_assert($guard['clbpara']['sendo']['guard_start']===$now && $guard['clbpara']['sendo']['guard_kills']===2,'同周期返回重新开始驻守，保留本周期个人击破');
	player_save($guard); $now=4300; sendo_tick($guard);
	world_assert(!empty($guard['clbpara']['sendo']['gentle']['QD2'])===$should_complete,$should_complete ? '返回后在原周期结束前满150秒可温柔完成' : '迟来的请求不会把周期结束后的时间补算驻守');
}

// 每次仍发三个安全检查点及终点；地点不足时按原地探索确认重复段。
foreach(array(array(34),array(7,34),array(7,8,34),array(7,8,9,34),array(7,32,33,34)) as $safe) {
	$db=new WorldDb(); $gamevars=array('sendo_open'=>1); $now=6000; $arealist=array_merge(array(0),$safe); $areanum=0; $areatime=7000;
	$runner=world_player(22); $runner['clbpara']['sendo']=array('done'=>array(),'gentle'=>array()); player_save($runner);
	world_command($runner,'sendo_tool_lantern'); $route=$runner['clbpara']['sendo']['lantern_route'];
	world_assert(count($route)===4 && $route[3]===34 && !array_diff($route,$safe),'安全候选 '.count($safe).' 处仍生成三个合法检查点和终点');
	$runner['ara']='引路灯'; $runner['aras']=3;
	foreach($route as $step=>$point) {
		$action=$runner['pls']===$point ? 'search' : 'move'; $runner['pls']=$point; sendo_action($runner,$action);
		if($step<3) world_assert(empty($runner['clbpara']['sendo']['done']['QD5']),'护送途中不提前完成：候选'.count($safe).'，检查点'.($step+1));
	}
	world_assert(!empty($runner['clbpara']['sendo']['gentle']['QD5']),'少量安全点及连续重复检查点均可实际走完护送');
}
$runner['clbpara']['sendo']=array('done'=>array(),'gentle'=>array(),'lantern_route'=>array(7,34),'lantern_step'=>0); $runner['pls']=7; sendo_action($runner,'move'); $runner['pls']=34; sendo_action($runner,'move');
world_assert(!empty($runner['clbpara']['sendo']['gentle']['QD5']),'已保存的旧短路线按其实际终点完成，不永久卡在第四步');

// 公共清场换周期后，旧工具仍可作用于新实体；下一轮可领取新灯刷新已封闭路线。
$db=new WorldDb(); $gamevars=array('sendo_open'=>1); $now=8000; $arealist=range(0,34); $areatime=9000;
$runner=world_player(23); $runner['clbpara']['sendo']=array('done'=>array(),'gentle'=>array()); player_save($runner);
world_command($runner,'sendo_tool_rope'); world_command($runner,'sendo_tool_glasses'); world_command($runner,'sendo_tool_lantern');
$old_ids=array_keys(array_filter($db->players,function($p) { return in_array($p['type'],array(36,38),true); }));
$old_cycle=$gamevars['sendo']['cycle']; $arealist=array(0,7,8,34); $now=$gamevars['sendo']['end']; sendo_tick($runner); player_save($runner);
$new_ids=array_keys(array_filter($db->players,function($p) { return in_array($p['type'],array(36,38),true); }));
world_assert($gamevars['sendo']['cycle']===$old_cycle+1 && !array_intersect($old_ids,$new_ids) && count($new_ids)===count($old_ids),'公共周期清理旧实体并一次性补齐新周期全部事件');
$count=count($spawn_calls); sendo_tick($runner); world_assert(count($spawn_calls)===$count,'同一新周期重复调度不再次补刷公共NPC');
foreach($db->players as $id=>$row) {
	$para=get_clbpara($row['clbpara']);
	if($row['type']===38 && ($para['sendo_qid'] ?? '')==='QD3') $new_bully=$id;
	if($row['type']===38 && ($para['sendo_qid'] ?? '')==='QD4') $new_hunter=$id;
}
$runner['pls']=$db->players[$new_bully]['pls']; sendo_item($runner,1);
$runner['pls']=$db->players[$new_hunter]['pls']; sendo_item($runner,2);
world_assert(!empty($runner['clbpara']['sendo']['gentle']['QD3']) && $runner['clbpara']['sendo']['located']===$new_hunter && $runner['itms2']===2,'上一轮捕绳与眼镜可正常捕获、定位本轮新NPC');
$runner['pls']=34; world_command($runner,'sendo_tool_lantern');
world_assert($runner['clbpara']['sendo']['tools']['lantern']===$gamevars['sendo']['cycle'] && !array_diff($runner['clbpara']['sendo']['lantern_route'],array(7,8,34)),'下周期重领灯会改用当前安全地点，旧路线封闭不会锁死任务');

echo "Ending route world regression complete.\n";
