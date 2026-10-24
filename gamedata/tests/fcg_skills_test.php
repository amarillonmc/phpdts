<?php
if(!defined('IN_GAME')) exit('Define IN_GAME before running tests.');
// 沿用真实战斗回归的假数据库、角色与新闻桩，同时检查既有社团23没有回归。
require __DIR__.'/club23_test.php';
$test_ruleset=''; include config('resources'); include config('gamecfg'); include config('combatcfg'); include config('clubskills');
$baseexp=10000; $chase_obbs=$dfight_obbs=0; $now=1000; $test_revival=false; $test_fixed_damage=null; $test_dice=1;
$fcg_checks=0;
function fcgs_assert($ok,$label) { global $fcg_checks; ++$fcg_checks; if(!$ok) throw new RuntimeException('FCG skill: '.$label); }
function fcgs_player($skill,$id=1) { $p=club23_test_player($id); $p['clbpara']['skill'][]='tl_fcg_'.$skill; fcg_skills_initialize($p,array()); return $p; }
function fcgs_command(&$p,$command,$blocked=false) { fcg_skill_command($p,$command,array('fcg_nonce'=>(string)($p['clbpara']['fcg_nonce'] ?? 0)),$blocked); }

$p=fcgs_player('attune'); $p['hp']=500; $p['sp']=100; $p['inf']='p'; $p['clbpara']['fcg_charge']=4; $p['clbpara']['fcg_color']='G';
fcg_skills_on_action($p);
fcgs_assert($p['inf']==='' && $p['hp']===1040.0 && $p['sp']===120.0 && $p['clbpara']['fcg_charge']===0,'green cleanses before poison and restores HP/SP');
for($i=0;$i<20;$i++) fcg_skills_on_action($p);
fcgs_assert($p['clbpara']['fcg_charge']===10,'attune cap');
fcgs_command($p,'fcg_color_R',true); fcgs_assert($p['clbpara']['fcg_color']==='G','battle input refused');
fcgs_command($p,'fcg_color_R'); fcgs_assert(fcg_attuned($p,'R'),'field color switch');
$p['arbe']=100; $p['clbpara']['fcg_color']='W'; fcg_battle_begin($p);
fcgs_assert($p['fcg_battle']['shield']===30.0 && $p['clbpara']['fcg_charge']===5,'white opening shield');
$a=club23_test_player(2); fcgs_assert(fcg_defend_damage($a,$p,50)===20,'shield absorbs a damage packet');

$p=fcgs_player('thorns'); $p['hp']=1000;
fcgs_assert(fcg_defend_damage($a,$p,800)===400 && $p['fcg_reflect']===600.0 && $p['clbpara']['fcg_thorns_charges']===1,'heavy hit cut and reflection');
fcgs_assert(fcg_defend_damage($a,$p,2200)===2200 && $p['clbpara']['fcg_thorns_charges']===1,'fatal even after reduction does not spend');
for($i=0;$i<45;$i++) fcg_skills_on_action($p);
fcgs_assert($p['clbpara']['fcg_thorns_charges']===2,'45 actions replenish');

$p=fcgs_player('evade'); for($i=0;$i<30;$i++) fcg_skills_on_action($p);
fcgs_assert($p['clbpara']['fcg_evade_ready']===1,'30 actions arm evade');
fcg_battle_begin($p); $p['hp']=1000;
fcgs_assert(fcg_defend_damage($a,$p,800)===1 && $p['rage']===50 && $p['fcg_battle']['crit'],'evade changes damage, grants rage and counter crit');
$p['is_counter']=1; $multipliers=\revattr\get_final_dmg_p($p,$a,1);
fcgs_assert(in_array(2,$multipliers,true),'counter has a guaranteed critical multiplier');
fcg_battle_end($p,$a); fcgs_assert(empty($p['fcg_battle']) && empty($p['fcg_critical']),'temporary battle state cleared');

$p=fcgs_player('chain'); fcgs_assert(count($p['clbpara']['fcg_hand'])===2,'two cards on entry');
for($i=0;$i<30;$i++) fcg_skills_on_action($p);
fcgs_assert(count($p['clbpara']['fcg_hand'])===3 && $p['clbpara']['fcg_draw_steps']===0,'hand caps and pauses recharge');
$p['clbpara']['fcg_hand']=array(array('color'=>'W','quality'=>1)); $before=$p['wepe']; fcgs_command($p,'fcg_card_0');
fcgs_assert($p['wepe']==$before+ceil($before*.15) && $p['weps']==45 && !$p['clbpara']['fcg_hand'],'white field weapon buff');
$p['clbpara']['fcg_hand']=array(array('color'=>'R','quality'=>1)); fcgs_command($p,'fcg_card_0'); fcgs_assert($p['rage']==60,'red field rage');
$p['clbpara']['fcg_hand']=array(array('color'=>'G','quality'=>1)); $p['hp']=100; $p['sp']=100; $p['inf']='ph'; fcgs_command($p,'fcg_card_0');
fcgs_assert($p['hp']==1300 && $p['sp']==700 && $p['inf']==='','green field recovery');
$p['clbpara']['fcg_hand']=array(array('color'=>'B','quality'=>1)); $p['clbpara']['skill'][]='c1_stalk'; $p['clbpara']['starttimes']['c1_stalk']=$now;
fcgs_command($p,'fcg_card_0'); fcgs_assert($p['clbpara']['fcg_stealth_until']==1030,'blue stealth expiry');
$p['clbpara']['fcg_hand']=array(array('color'=>'W','quality'=>1),array('color'=>'G','quality'=>1)); fcgs_command($p,'fcg_ready');
fcg_battle_begin($p); fcg_battle_opening($p,$a,1,true); fcgs_assert(empty($p['clbpara']['fcg_hand']) && !$p['clbpara']['fcg_ready'] && $p['fcg_battle']['att']==1.25,'ordered ready hand consumed once');
$p['hp']-=200; fcg_battle_end($p,$a); fcgs_assert($p['hp']==1200,'green recovers half battle loss');

$p=fcgs_player('imprint'); $p['itm1']='测试剑'; $p['itmk1']='WK'; $p['itme1']=120; $p['itms1']=$nosta; $p['itmsk1']='r'; $p['itmpara1']='{"custom":42}';
fcgs_command($p,'fcg_copy_1');
fcgs_assert($p['itm2']==='★幻影·测试剑' && $p['itme2']===120 && $p['itms2']===25 && fcg_phantom($p['itmpara2']) && $p['itmpara2']['custom']===42,'copy keeps attributes and params, bounds durability');
fcgs_assert($p['clbpara']['fcg_imprint_uses']===1,'copy spends one permission');
fcg_skill_command($p,'fcg_copy_1',array('fcg_nonce'=>'0')); fcgs_assert($p['clbpara']['fcg_imprint_uses']===1 && empty($p['itms3']),'duplicate request cannot copy twice');
fcgs_command($p,'fcg_copy_2'); fcgs_assert($p['clbpara']['fcg_imprint_uses']===1,'cannot copy a phantom');
itemdrop('itm2',$p); fcgs_assert($p['itms2']===25,'cannot drop phantom');
fcg_record_hit($p,array('ex_attack_keys'=>array('p'),'ex_damage'=>300,'final_damage'=>500),500);
for($i=0;$i<35;$i++) fcg_skills_on_action($p); fcgs_command($p,'fcg_echo');
fcgs_assert($p['clbpara']['fcg_echo_ready']['damage']===500 && $p['clbpara']['fcg_imprint_uses']===1 && !$p['clbpara']['fcg_echo_charge'],'echo uses 35-step charge before permission');

$p=fcgs_player('mimic'); $p['hp']=0; fcgs_assert(fcg_mimic_guard($p) && $p['hp']===1 && $p['clbpara']['fcg_mimic']==='rebuilding','lethal guard');
$p['hp']=0; fcgs_assert(!fcg_mimic_guard($p),'cannot guard while rebuilding'); $p['hp']=1;
for($i=0;$i<40;$i++) fcg_skills_on_action($p); fcgs_assert($p['clbpara']['fcg_mimic']==='active','40 actions rebuild');

// 真实主战斗链：反噬击杀必须停止敌方后续攻击，并按反噬方登记击杀。
$a=club23_test_player(1); $b=fcgs_player('thorns',2); $a['hp']=500; $b['hp']=1000; $test_fixed_damage=800;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
fcgs_assert($a['hp']===0 && $b['hp']===600 && $b['killnum']===1 && $a['bid']===2,'real reflection kill attribution and no further counter');
$a=club23_test_player(1); $b=fcgs_player('mimic',2); $test_fixed_damage=4000;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
fcgs_assert($b['hp']===1 && $b['clbpara']['fcg_mimic']==='rebuilding','real combat lethal guard before death');
$saved=get_clbpara($db->players[2]['clbpara']); fcgs_assert($saved['fcg_mimic']==='rebuilding','saved rebuilding survives reload');
$test_fixed_damage=null;
// 使用真实合成配方与拾取流程，幻影素材消耗后产物必须继续绑定。
require_once GAME_ROOT.'include/game/itemmix.func.php';
$pdata=fcgs_player('imprint');
$pdata['itm1']='★幻影·汽油'; $pdata['itmk1']='Y'; $pdata['itme1']=1; $pdata['itms1']=1; $pdata['itmpara1']=array('fcg_phantom'=>1); $pdata['itmsk1']='v';
$pdata['itm2']='空瓶'; $pdata['itmk2']='Y'; $pdata['itme2']=1; $pdata['itms2']=1;
fcgs_assert(fcg_copyable('汽油','Y','',array()) && !fcg_copyable('游戏解除钥匙','Y','',array()),'ordinary recipe material allowed, ending key refused');
fcgs_assert(itemmix_name_proc($pdata['itm1'])==='汽油','phantom prefix does not break recipe matching');
$recipe=null; foreach(get_mixinfo() as $candidate) if($candidate['stuff']===array('汽油','空瓶')) {$recipe=$candidate;break;}
$recipe['type']=0; $mix_type=array(0=>'通常');
extract($pdata,EXTR_REFS);
itemmix_proc(array(1,2),$recipe,'汽油、空瓶',$pdata);
fcgs_assert($pdata['itm1']==='★幻影·☆火焰瓶☆' && fcg_phantom($pdata['itmpara1']) && strpos($pdata['itmsk1'],'v')!==false && !$pdata['itms2'],'actual mixed output inherits phantom binding and consumes materials');
$a=club23_test_player(1); $b=fcgs_player('imprint',2); fcg_battle_begin($a); fcg_battle_begin($b);
fcg_direct_hit($a,$b,200,1,'属性覆唱',true,array('ex_attack_keys'=>array('p'),'ex_damage'=>100,'final_damage'=>200));
fcgs_assert($b['clbpara']['fcg_last_hit_dmg']['elements']===array('p') && $b['clbpara']['fcg_last_hit_dmg']['element_ratio']===.5,'direct echo preserves elemental snapshot');
// 独立追击不能重复触发原攻击的固定附加伤害、体力损失或必杀代价。
$a=club23_test_player(1); $b=club23_test_player(2); fcg_battle_begin($a); fcg_battle_begin($b);
$a['bskill_c12_rage']=1; $a['bskill_c1_bjack']=1; $a['bskill_c2_butcher']=1; $a['bskill_c20_lighting_white_dmg']=900; $b['sp']=50;
fcg_direct_hit($a,$b,200,1,'种火追击');
fcgs_assert($a['hp']===2000 && $b['hp']===1800,'independent packet does not replay offensive costs or bonus damage');
$a=fcgs_player('chain',1); $b=club23_test_player(2); $b['hp']=30;
$a['clbpara']['fcg_hand']=array(array('color'=>'R','quality'=>1),array('color'=>'W','quality'=>1)); $a['clbpara']['fcg_ready']=1;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
fcgs_assert($b['hp']===0 && $a['killnum']===1 && $b['bid']===1 && empty($a['clbpara']['fcg_hand']),'opening ray kills through normal result and consumes ready hand once');
$a=fcgs_player('imprint',1); $b=club23_test_player(2); fcg_battle_begin($a); fcg_battle_begin($b);
$a['clbpara']['fcg_echo_ready']=array('damage'=>1000,'elements'=>array(),'element_ratio'=>0);
fcg_battle_opening($a,$b,1,true);
fcgs_assert($b['hp']===1400 && empty($a['clbpara']['fcg_echo_ready']),'opening echo capped at 600 and consumed');
echo "FCG skills: {$fcg_checks} checks passed.\n";
