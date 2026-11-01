<?php
if(!defined('IN_GAME')) exit('Access Denied');
// 沿用现有战斗回归的角色、数据库和骰子桩，实际执行重铸、攻击与反击判定。
require __DIR__.'/club23_test.php';
$test_ruleset=''; include config('resources'); include config('gamecfg'); include config('combatcfg'); include config('clubskills');
$baseexp=10000; $chase_obbs=$dfight_obbs=0; $test_revival=false; $test_fixed_damage=null; $test_dice=1;

$p=club23_test_player(1); $p['nick']=98; $p['rp']=600; $p['wepsk']='v'; $p['weppara']=array('keep'=>1);
check_swordmaster_rp($p);
club23_test_assert($p['rp']===0 && $p['wepk']==='WK' && $p['wepe']==380 && $p['weps']==180,'剑圣非战斗重铸保留原有效果和耐久加成');
club23_test_assert(!isset($p['wep_kind']) && $p['wepsk']==='v' && $p['weppara']===array('keep'=>1),'非战斗重铸不额外初始化战斗参数，保留属性与物品参数');
player_save($p); $p=$db->players[1]; $before=$p;
check_swordmaster_rp($p);
club23_test_assert($p===$before,'重铸保存再加载后不重复消费RP或增强武器');

foreach(array('title','rp','unarmed','broken') as $case) {
	$p=club23_test_player(1); $p['nick']=98; $p['rp']=600;
	if($case==='title') $p['nick']=0;
	if($case==='rp') $p['rp']=599;
	if($case==='unarmed') $p['wepk']='WN';
	if($case==='broken') $p['weps']=0;
	$before=$p; check_swordmaster_rp($p);
	club23_test_assert($p===$before,'未满足重铸条件不改变角色：'.$case);
}

// 书中虫在命中判定前增加RP，使599点RP的剑圣当场将枪重铸为斩系。
foreach(array(false,true) as $drain) {
	$a=club23_test_player(1); $a['nick']=98; $a['rp']=599; $a['wg']=10; $a['wk']=400; $a['weps']=100;
	$b=club23_test_player(2); $b['type']=89; $b['name']='高中生·白神'; $b['wepk']='WK'; $b['tactic']=1;
	if($drain) $b['wepsk']='+';
	$log=''; \revcombat\rev_combat_prepare($a,$b,1,'',0);
	club23_test_assert($a['rp']===0 && $a['wepk']==='WK' && $a['wep_kind']==='K','攻击前重铸立即切换实际攻击方式');
	club23_test_assert($a['wep_skill']==($drain ? 20 : 400) && $a['wep_range']===$rangeinfo['K'],'重铸更新斩熟和射程，并保留已经生效的技能抽取');
	club23_test_assert($a['weps']==249 && strpos($log,'耐久度下降了1')!==false && strpos($log,'弹药数减少')===false,'当次攻击按斩系损耗耐久，不再扣弹药');
	club23_test_assert(!empty($b['is_counter']) && $a['hp']<2000,'对手按重铸后的斩系射程实际完成反击');
	club23_test_assert($db->players[1]['wepk']==='WK' && $db->players[1]['rp']===0,'实际战斗保存重铸结果和RP消耗');
}

// 已积满RP但刚装备武器的兜底路径：单次打击结束后重铸，后续打击立即使用斩系。
$a=club23_test_player(1); $a['nick']=98; $a['rp']=600; $a['wk']=400;
$b=club23_test_player(2); $b['wepk']='WK'; $b['tactic']=1;
\revcombat\get_attr_wepbase($a,$b,1); \revcombat\rev_attack($a,$b,1);
$uses=$a['weps']; $log=''; \revcombat\rev_attack($a,$b,1);
club23_test_assert($a['wep_kind']==='K' && $a['wep_range']===$rangeinfo['K'] && $a['weps']==$uses-1 && strpos($log,'弹药数减少')===false,'打击结束时的重铸也更新后续攻击和耐久计算');

// 空枪原先按枪托攻击；重铸时必须清掉这一标记，同时保留无限耐久。
$a=club23_test_player(1); $a['nick']=98; $a['rp']=600; $a['weps']=$nosta; $a['wk']=400;
$b=club23_test_player(2); \revcombat\get_attr_wepbase($a,$b,1);
club23_test_assert(isset($a['is_wpg']),'空枪重铸测试确实从枪托状态开始');
check_swordmaster_rp($a);
club23_test_assert(!isset($a['is_wpg']) && $a['wep_kind']==='K' && $a['wep_skill']===400 && $a['weps']===$nosta,'空枪重铸清除枪托标记并保留无限耐久');

echo "Swordmaster regression tests passed.\n";
