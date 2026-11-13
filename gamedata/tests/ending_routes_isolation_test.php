<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'include/resources.func.php';
require GAME_ROOT.'include/game.func.php';
require GAME_ROOT.'include/ruleset_override.func.php';
require GAME_ROOT.'include/game/ending_routes.func.php';
require GAME_ROOT.'include/talent.func.php';
require GAME_ROOT.'gamedata/ruleset/ruleset_config.php';

class EndingIsolationDb {
	public $ruleset='';
	function query($sql) { return array('gruleset'=>$this->ruleset); }
	function num_rows($row) { return 1; }
	function fetch_array($row) { return $row; }
}
function isolation_assert($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
function isolation_resources() {
	global $gamecfg;
	$plsinfo=$hplsinfo=$areainfo=$horizoninfo=$typeinfo=$stateinfo=$dinfo=$lwinfo=$killmsginfo=array();
	include config('resources',$gamecfg);
	return compact('plsinfo','hplsinfo','areainfo','horizoninfo','typeinfo','stateinfo','dinfo','lwinfo','killmsginfo');
}
function isolation_load($id,$room,$enabled) {
	global $db,$groomid,$gtablepre,$current_ruleset_config,$ending_routes_v2,$club_skills,$gamecfg,$gamevars;
	global $plsinfo,$hplsinfo,$areainfo,$horizoninfo,$typeinfo,$stateinfo,$dinfo,$lwinfo,$killmsginfo,$npcchat;
	$db->ruleset=$id; $groomid=$room; $gtablepre='test_'; $gamecfg=1; $gamevars=array();
	$current_ruleset_config=$id==='' ? null : get_ruleset_config($id); $club_skills=$current_ruleset_config['club_skills'] ?? 2; $ending_routes_v2=$enabled;
	$plsinfo=$hplsinfo=$areainfo=$horizoninfo=$typeinfo=$stateinfo=$dinfo=$lwinfo=$killmsginfo=array(); include config('resources',1);
	$before=compact('plsinfo','hplsinfo','areainfo','horizoninfo','typeinfo','stateinfo','dinfo','lwinfo','killmsginfo');
	$expected=$id==='' && $enabled;
	isolation_assert(ending_routes_enabled()===$expected && talent_enabled()===$expected,($id ?: '标准房').' 房间 '.$room.' 使用正确的新路线/天赋开关');
	isolation_assert($before===isolation_resources(),'函数内独立重读 resources 后地图、NPC及死亡资源保持完整');
	if(!$expected) isolation_assert(($plsinfo[34] ?? '')!=='代码洪流' && ($hplsinfo[3][120] ?? '')!=='中央旧城区' && ($typeinfo[34] ?? '')!=='红杀教头' && ($horizoninfo[2] ?? '')!=='模式ＥＸ','关闭时不注入新地图、NPC分类和视界');
	else {
		isolation_assert($plsinfo[34]==='代码洪流' && array_keys($hplsinfo[3])===range(120,129) && $hplsinfo[4][134]==='英灵殿' && !isset($plsinfo[120]) && !isset($plsinfo[134]),'直接读取正式资源得到湾城十区与隐藏英灵殿，不混入标准地图');
		foreach($hplsinfo[3]+$hplsinfo[4] as $place=>$label) isolation_assert(!empty($areainfo[$place]),$label.'有地点说明');
		isolation_assert($horizoninfo[2]==='模式ＥＸ' && !empty($stateinfo[61]) && !empty($stateinfo[62]) && !empty($dinfo[61]) && !empty($dinfo[62]),'EX视界、两种死亡状态和死法文案已登记');
	}
	$npcs=get_addnpcinfo(); list($quests,)=get_questcfg(); $items=get_questiteminfo(); $recipes=get_mixinfo();
	$new_npcs=array_intersect_key($npcs,array_flip(range(34,42))); $new_quests=array_intersect_key($quests,array_flip(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7')));
	$new_recipes=array_filter($recipes,function($recipe) { return in_array($recipe['result'][0] ?? '',array('【解离凭证·幻境】','【解离凭证·真实】'),true); });
	if($expected) isolation_assert(count($new_npcs)===9 && count($new_quests)===7 && isset($items['sendo_ticket'],$items['sendo_pass']) && count($new_recipes)===2,'标准房加载新NPC、七项任务、两种通票与结局合成');
	else isolation_assert(!$new_npcs && !$new_quests && !isset($items['sendo_ticket'],$items['sendo_pass']) && !$new_recipes,'旧规则实际资源加载不注入新NPC、任务、通票或结局合成');
	if($expected) {
		$dialogue_npcs=$new_npcs;
		$dialogue_npcs[7]['sub']=array_slice($npcs[7]['sub'],3,3);
		$dialogue_npcs[19]=$npcs[19];
		foreach(array('猎人团首领','猎人团员') as $hunter) $dialogue_npcs[38]['sub'][]=array('name'=>$hunter);
		$listener=array('name'=>'测试玩家'); $checked=0;
		foreach($dialogue_npcs as $type=>$group) {
			isolation_assert(!empty($typeinfo[$type]) && !empty($killmsginfo[$type]),'type'.$type.'有分类名与击杀词');
			foreach($group['sub'] as $npc) {
				$npc['type']=$type;
				$words=is_array($lwinfo[$type]) ? ($lwinfo[$type][$npc['name']] ?? '') : $lwinfo[$type];
				isolation_assert($words!=='' && strpos(npc_chat_rev($npc,$listener,'death'),$words)!==false,$npc['name'].'的实际死亡喊话能读取遗言');
				$checked++;
			}
		}
		isolation_assert($checked===52,'覆盖所有新路线NPC、符灵及送行动态猎人');
	}
	$player=array('name'=>'test','type'=>0,'race'=>0,'nick'=>0,'hp'=>51,'mhp'=>100,'sp'=>40,'msp'=>100,'clbpara'=>array()); $original=$player;
	if(!$expected) { talent_entry($player,array('talentdata'=>''),5); isolation_assert($player===$original,'关闭时不注入种族、数值或技能'); }
}
$db=new EndingIsolationDb();
isolation_load('',0,1); isolation_load('',3,1); isolation_load('',0,0);
foreach(array_keys($ruleset_definitions) as $id) { isolation_load($id,0,1); isolation_load($id,3,1); }
echo "Ending route resource isolation complete.\n";
