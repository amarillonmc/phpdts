<?php
if(!defined('IN_GAME')) exit('Access Denied');
// 沿用已有真实战斗回归的数据库、角色和画面桩，执行追猎到 revbattle/revcombat 的实际调用链。
require __DIR__.'/club23_test.php';
$test_ruleset='';
include config('resources'); include config('gamecfg'); include config('combatcfg'); include config('clubskills');
require GAME_ROOT.'gamedata/cache/wheelchair.php';
require_once GAME_ROOT.'include/game/wheelchair.func.php';
require_once GAME_ROOT.'include/game/wheelchair_action.func.php';
$wc_empty_template=tempnam(sys_get_temp_dir(),'wheelchair-template-');
register_shutdown_function(function() use($wc_empty_template) { unlink($wc_empty_template); });
function template($name) { return $GLOBALS['wc_empty_template']; }
function parse_nameinfo_desc($name,...$args) { return $name; }
function parse_kinfo_desc($kind,...$args) { return $kind; }
function parse_skinfo_desc($attrs,...$args) { return $attrs; }
function check_add_searchmemory($id,$kind,$name,&$data=null) { $data['clbpara']['smeo'][]=array($id,$kind,$name); }
class WheelchairBattleDb extends Club23TestDb {
	function query($sql) {
		if(preg_match("/SELECT \\* FROM test_players WHERE pid\\s*=\\s*'(\\d+)'/",$sql,$m)) return isset($this->players[$m[1]]) ? array($this->players[$m[1]]) : array();
		return parent::query($sql);
	}
	function num_rows($result) { return count($result); }
	function fetch_array($result) { return reset($result); }
}
function fetch_playerdata_by_pid($id) {
	global $db;
	if(!isset($db->players[$id])) return null;
	$p=$db->players[$id]; $p['clbpara']=get_clbpara($p['clbpara']); $p['horizon']=0; return $p;
}
function fetch_playerdata_by_name($name) {
	global $db;
	foreach($db->players as $id=>$p) if($p['name']===$name && !$p['type']) return fetch_playerdata_by_pid($id);
	return null;
}
function wc_battle_start($hp=2000) {
	global $pdata,$db,$log,$fog,$coldtimeon,$now,$alivenum,$gamestate,$test_fixed_damage,$test_revival,$test_dice,$chase_obbs,$dfight_obbs;
	$db=new WheelchairBattleDb(); $log=''; $fog=$coldtimeon=$chase_obbs=$dfight_obbs=0;
	$now=1000; $alivenum=10; $gamestate=20; $test_fixed_damage=150; $test_revival=false; $test_dice=1;
	$pdata=club23_test_player(1); $pdata['hp']=$hp; $pdata['action']='enemy'; $pdata['bid']=2;
	getclubskill('wc_hunt',$pdata['clbpara']);
	$pdata['clbpara']['wheelchair']['hunt']=$pdata['clbpara']['wheelchair']['working_hunt']=array('kind'=>'player','target'=>'测试者2','label'=>'测试者2');
	foreach(array_keys($pdata) as $key) $GLOBALS[$key]=&$pdata[$key];
	$enemy=club23_test_player(2); $enemy['hp']=100;
	$db->players[2]=$enemy;
}
$current_ruleset_config=array('club_skills'=>2); $baseexp=10000; $can_lootdepot_type=array(0);
ob_start();
wc_battle_start(); wheelchair_after_explore($pdata);
club23_test_assert($db->players[2]['hp']===0 && $pdata['killnum']===1 && $pdata['weps']<30,'追猎实际普通攻击消耗弹药、击杀目标并保存');
$kills=$pdata['killnum']; wheelchair_battle_command($pdata);
club23_test_assert($pdata['killnum']===$kills,'目标死亡后重放追猎指令不重复记击杀');
wc_battle_start(40); wheelchair_battle_command($pdata);
club23_test_assert($pdata['action']==='' && !$pdata['bid'] && $pdata['hp']===40 && $db->players[2]['hp']===100,'生命不足时走原生撤离，不攻击目标');
wc_battle_start(40); $pdata['action']='pchase'; $test_fixed_damage=20; $chase_escape_obbs=100;
wheelchair_battle_command($pdata);
$assisted=array($pdata['hp'],$db->players[2]['hp'],$pdata['weps']);
club23_test_assert(strpos($log,'敌人在你身后紧追不舍')!==false,'被追击时逃跑仍可能失败并进入原生战斗');
wc_battle_start(40); $pdata['action']='pchase'; $test_fixed_damage=20; $chase_escape_obbs=100;
\revbattle\revbattle_prepare('back');
club23_test_assert($assisted===array($pdata['hp'],$db->players[2]['hp'],$pdata['weps']),'辅助撤离与直接点击原生撤离的结算一致');
wc_battle_start(); $db->players[2]['pls']=2; wheelchair_battle_command($pdata);
club23_test_assert($pdata['action']==='' && $db->players[2]['hp']===100,'目标已移动时原生战斗检查拒绝攻击');
wc_battle_start(); $coldtimeon=1; wheelchair_after_explore($pdata);
club23_test_assert($db->players[2]['hp']===100 && !$pdata['killnum'],'启用行动冷却时不会在同次搜索追加攻击');
wc_battle_start(); $pdata['clbpara']['wheelchair']['paused']['wc_hunt']=1;
$pdata['action']=''; $pdata['bid']=0; wheelchair_battle_command($pdata);
club23_test_assert($db->players[2]['hp']===100,'暂停技能后不会继续代战');
ob_end_clean();
echo "Wheelchair battle integration passed.\n";
