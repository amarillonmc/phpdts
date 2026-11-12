<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'include/resources.func.php';
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
function isolation_load($id,$room,$enabled) {
	global $db,$groomid,$gtablepre,$current_ruleset_config,$ending_routes_v2,$club_skills,$gamecfg,$gamevars;
	global $plsinfo,$hplsinfo,$areainfo,$horizoninfo,$typeinfo,$dinfo;
	$db->ruleset=$id; $groomid=$room; $gtablepre='test_'; $gamecfg=1; $gamevars=array();
	$current_ruleset_config=$id==='' ? null : get_ruleset_config($id); $club_skills=$current_ruleset_config['club_skills'] ?? 2; $ending_routes_v2=$enabled;
	$plsinfo=$hplsinfo=$areainfo=$horizoninfo=$typeinfo=$dinfo=array(); include config('resources',1);
	$before=array($plsinfo,$hplsinfo,$areainfo,$horizoninfo,$typeinfo,$dinfo); ending_routes_setup();
	$expected=$id==='' && $enabled;
	isolation_assert(ending_routes_enabled()===$expected && talent_enabled()===$expected,($id ?: '标准房').' 房间 '.$room.' 使用正确的新路线/天赋开关');
	if(!$expected) isolation_assert($before===array($plsinfo,$hplsinfo,$areainfo,$horizoninfo,$typeinfo,$dinfo),'关闭时保留既有地图、NPC分类和视界');
	else isolation_assert($plsinfo[34]==='代码洪流' && count($hplsinfo[3])===10,'标准房加载十区湾城和代码洪流');
	$npcs=get_addnpcinfo(); list($quests,)=get_questcfg(); $items=get_questiteminfo(); $recipes=get_mixinfo();
	$new_npcs=array_intersect_key($npcs,array_flip(range(34,42))); $new_quests=array_intersect_key($quests,array_flip(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7')));
	$new_recipes=array_filter($recipes,function($recipe) { return in_array($recipe['result'][0] ?? '',array('【解离凭证·幻境】','【解离凭证·真实】'),true); });
	if($expected) isolation_assert(count($new_npcs)===9 && count($new_quests)===7 && isset($items['sendo_ticket'],$items['sendo_pass']) && count($new_recipes)===2,'标准房加载新NPC、七项任务、两种通票与结局合成');
	else isolation_assert(!$new_npcs && !$new_quests && !isset($items['sendo_ticket'],$items['sendo_pass']) && !$new_recipes,'旧规则实际资源加载不注入新NPC、任务、通票或结局合成');
	$player=array('name'=>'test','type'=>0,'race'=>0,'nick'=>0,'hp'=>51,'mhp'=>100,'sp'=>40,'msp'=>100,'clbpara'=>array()); $original=$player;
	if(!$expected) { talent_entry($player,array('talentdata'=>''),5); isolation_assert($player===$original,'关闭时不注入种族、数值或技能'); }
}
$db=new EndingIsolationDb();
isolation_load('',0,1); isolation_load('',3,1); isolation_load('',0,0);
foreach(array_keys($ruleset_definitions) as $id) { isolation_load($id,0,1); isolation_load($id,3,1); }
echo "Ending route resource isolation complete.\n";
