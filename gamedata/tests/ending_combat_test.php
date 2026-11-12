<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).'/');
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line) { throw new Exception($message.' at '.$file.':'.$line); });
$ending_test_enabled=true; $now=1000; $nosta=65535; $gamecfg=1; $log=''; $roll=99;
function ending_routes_enabled() { return $GLOBALS['ending_test_enabled']; }
function get_clbpara($p) { return is_array($p) ? $p : (json_decode($p,true) ?: array()); }
function get_itmpara($p) { return is_array($p) ? $p : (json_decode($p,true) ?: array()); }
function config($file,$id) { return GAME_ROOT.'gamedata/cache/'.$file.'_1.php'; }
function diceroll($max) { return min($max,$GLOBALS['roll']); }
function get_safe_plslist() { return array(1,2,3,4,5); }
function addnews() {}
function weapon_loss(&$data,$n,$force=0) { $data['weps']=max(0,$data['weps']-$n); }
function armor_hurt(&$data,$slot,$n) { $data[$slot.'s']=max(0,$data[$slot.'s']-$n); }
function player_save($data) { $GLOBALS['saved'][$data['pid']]=$data; }
function fetch_playerdata_by_pid($pid) { return $GLOBALS['saved'][$pid] ?? array(); }
class EndingCombatTestDB {
	public $items=array(),$queries=array();
	function array_insert($table,$item) { $this->items[]=$item; }
	function query($sql) { $this->queries[]=$sql; return (object)array('rows'=>array_values($GLOBALS['saved'] ?? array()),'offset'=>0); }
	function fetch_array($result) { return $result->rows[$result->offset++] ?? false; }
	function num_rows($result) { return count($result->rows); }
}
$db=new EndingCombatTestDB(); $tablepre='test_';
require GAME_ROOT.'include/game/ending_combat.func.php';
$checks=0;
function expect_ending($condition,$message) {
	$GLOBALS['checks']++;
	if(!$condition) throw new Exception($message);
}
function combat_person($type=0,$skills=array()) {
	$p=array('pid'=>$type+1,'name'=>'测试','type'=>$type,'race'=>0,'club'=>0,'hp'=>1000,'mhp'=>1000,'sp'=>1000,'msp'=>1000,'att'=>100,'def'=>100,'wp'=>100,'wk'=>100,'wc'=>100,'wg'=>100,'wd'=>100,'wf'=>100,'wb'=>100,'rage'=>100,'rp'=>0,'lvl'=>10,'pls'=>1,'pgroup'=>0,'horizon'=>0,'inf'=>'','wep'=>'长剑','wepk'=>'WK','wepe'=>100,'weps'=>100,'wepsk'=>'','weppara'=>'','clbpara'=>array('skill'=>$skills));
	foreach(array('arb','arh','ara','arf','art') as $slot) foreach(array('','k','e','s','sk','para') as $suffix) $p[$slot.$suffix]=in_array($suffix,array('e','s'),true) ? 0 : '';
	return $p;
}

$a=combat_person(); $b=combat_person(19); $b['name']='红暮'; $b['hp']=$b['mhp']=100000;
expect_ending(erc_damage($a,$b,9999999)===10000,'红暮固定伤害也须受一万上限约束');
$b['name']='蓝凝'; $b['hp']=$b['mhp']=9999998;
expect_ending(erc_damage($a,$b,99999999)===4999999,'蓝凝上限按最大生命的一半');
$ending_test_enabled=false;
expect_ending(erc_damage($a,$b,99999999)===99999999,'关闭新路线时伤害不得改变');
$ending_test_enabled=true;
$a['horizon']=$b['horizon']=2; $b['type']=0;
expect_ending(erc_damage($a,$b,100)===150,'EX最终伤害只增加一次');

$a=combat_person(); $b=combat_person(34,array('npc_talisman'));
for($i=0;$i<4;$i++) {
	erc_prepare_one($b,$a);
	expect_ending(erc_damage($a,$b,100)===0,'符盾必须跨四次交锋逐层消耗');
	$b['clbpara']=json_decode(json_encode($b['clbpara']),true);
}
erc_prepare_one($b,$a);
expect_ending(erc_damage($a,$b,100)===100,'破盾后不可通过再次进入战斗立即恢复');
$now+=120; erc_prepare_one($b,$a);
expect_ending($b['clbpara']['talisman_layers']===4,'冷却结束重新贴四层符');

$a=combat_person(); $a['att']=100000; $a['mhp']=200000; $a['wepe']=90000; $a['wepsk']='炎冰电毒音斩'; $a['clbpara']['skill']=array('c1_crit','npc_rook_chase'); $a['clbpara']['talent_skills']=array('npc_rook_chase'=>1); $a['skill_c1_crit']=1;
erc_retro_begin($a);
expect_ending($a['att']===65535 && $a['mhp']===65535 && $a['wepe']===65535,'旧时代面板截断');
expect_ending($a['wepsk']==='炎冰电毒音','旧属性限制是五个字符');
expect_ending(!erc_has($a,'npc_rook_chase') && !isset($a['skill_c1_crit']),'旧时代不能泄漏天赋及现代技能');
$a['att']+=5; erc_retro_end($a);
expect_ending($a['att']===100005 && $a['mhp']===200000 && $a['wepe']===90000 && $a['wepsk']==='炎冰电毒音斩','恢复原面板并保留本场合法属性变化');
expect_ending(erc_has($a,'npc_rook_chase'),'战后恢复头衔技能');

$a=combat_person(); $a['race']=2;
expect_ending(!severe_apply($a,'head') && !isset($a['gg_flag']),'妖精免破头且不能设即死旗');
$a['race']=5; expect_ending(!severe_apply($a,'head'),'AI免破头');
$a['race']=4; expect_ending(!severe_apply($a,'lower') && !severe_apply($a,'upper'),'鱼人免上下肢破坏');
$a['race']=0; expect_ending(severe_apply($a,'head') && $a['gg_flag']===61 && $a['mhp']===1000,'破头只登记专属死法而不清上限');
severe_revival($a); expect_ending(!isset($a['gg_flag']) && !isset($a['clbpara']['severe_head']),'复活清头破标记');

$a=combat_person(); severe_apply($a,'bleeding');
for($i=0;$i<100;$i++) severe_action($a,true);
expect_ending($a['hp']>=1 && $a['mhp']>=500,'内出血保留一命且上限最多削半');
severe_cure($a,array('bleeding'));
expect_ending($a['mhp']===1000 && !severe_has($a,'bleeding'),'清内出血恢复丢失上限');
severe_apply($a,'upper'); expect_ending(severe_can_use_item($a,'DB') && !severe_can_use_item($a,'HB'),'上肢损伤允许换装但禁药');
$a['arb']='义肢动力甲'; $a['arbs']=100;
expect_ending(severe_can_use_item($a,'HB'),'义肢身体装备豁免上肢限制');
severe_apply_curse($a,'beast'); severe_rest($a,10); expect_ending(severe_has($a,'frenzy'),'兽咒未除时不能仅靠休息退狂热');
severe_rest($a,180); expect_ending(!severe_has($a,'frenzy') && !severe_curse($a,'beast'),'足够静养先除咒再退狂热');

$a=combat_person(); $b=combat_person(41,array('npc_fate_record'));
expect_ending(erc_damage($a,$b,200)===200,'命运演算第一次攻击正常');
expect_ending(erc_damage($a,$b,200)===0,'重复属性攻击被否定');
$a['wep']='火枪'; $a['wepk']='WG';
expect_ending(erc_damage($a,$b,200)===200,'更换武器可破解命运记录');
$b=combat_person(39,array('npc_sequence'));
expect_ending(erc_damage($a,$b,200)===200 && erc_damage($a,$b,200)===100 && erc_damage($a,$b,200)===0,'序列学习依次减半归零');
$a['wep_kind']='F'; expect_ending(erc_damage($a,$b,200)===200,'切换攻击方式破解序列学习');

$b=combat_person(38); $b['clbpara']['sendo_qid']='QD3';
expect_ending(erc_damage($a,$b,950)===999 && !empty($b['clbpara']['sendo_subdued']),'恶霸首次降入15%时寸止留一血');
$b['hp']=1; expect_ending(erc_damage($a,$b,10)===10,'寸止后仍允许暴力击杀');

$a=combat_person(37,array('npc_luck_charge')); $a['hp']=0; $a['clbpara']['luck_charge']=80; $roll=0;
expect_ending(erc_revive($a) && $a['clbpara']['luck_charge']===20 && $a['hp']>0,'天运复活消耗充能');
$a['hp']=0; $roll=99; expect_ending(!erc_revive($a),'天运不可永久免死');
$a=combat_person(0,array('npc_lifecat')); $a['hp']=0;
expect_ending(erc_revive($a),'百命猫玩家第一次致死获救'); $a['hp']=0;
expect_ending(!erc_revive($a),'百命猫每局只救一次');

$a=combat_person(); $a['pid']=10; $a['arb']='盔甲'; $a['arbk']='DB'; $a['arbe']=300; $a['arbs']=100; $a['arbpara']=array('custom'=>'keep');
$b=combat_person(41,array('npc_dimension_theft')); $b['pid']=50;
for($i=0;$i<5;$i++) erc_steal($b,$a);
expect_ending(count($b['clbpara']['dimension_store'])<=3,'每人被偷上限三件');
$before=count($b['clbpara']['dimension_store']); erc_return_stolen($b,$a);
expect_ending(empty($b['clbpara']['dimension_store']),'归还清空仓库');
$found=0; for($i=1;$i<=6;$i++) if(!empty($a['itms'.$i])) $found++;
expect_ending($found===$before,'返还到原主背包且不重复');
erc_return_stolen($b,$a); $again=0; for($i=1;$i<=6;$i++) if(!empty($a['itms'.$i])) $again++;
expect_ending($again===$found,'重复死亡回调不会重复返还');

$a=combat_person(); severe_apply($a,'rupture');
for($i=0;$i<5;$i++) severe_rest($a,30);
expect_ending(severe_has($a,'rupture'),'150秒分段静养不能提前治愈');
severe_rest($a,30); expect_ending(!severe_has($a,'rupture'),'刷新产生的分段静养累计180秒能治愈');
severe_apply($a,'rupture'); severe_rest($a,100); severe_action($a); severe_rest($a,100);
expect_ending(severe_has($a,'rupture'),'有效行动打断连续静养');
$a['clbpara']['animal_spirit']=0;
expect_ending(!erc_can_action($a,false) && erc_can_action($a,true),'战意耗尽只禁止搜索，允许移动');
$a['clbpara']['betrayal_stacks']=2;
expect_ending(severe_negative_damage($a,100)===140 && severe_negative_damage($a,100)===100,'背德报应只消耗于下一次负面伤害');
$a['pgroup']=3; $a['horizon']=2; $hplsinfo[3]=array(120=>'旧城',121=>'新区');
expect_ending(erc_teleport_places($a)===array(120,121) && erc_place_name($a,121)==='新区','湾城技能传送与地名限当前组');

$a=combat_person(0,array('npc_bonearmor')); $roll=0;
expect_ending(erc_bone_immune($a) && $a['sp']===970,'玩家骨甲需要消耗体力');
$a['sp']=0; expect_ending(!erc_bone_immune($a),'玩家骨甲无体力不能永久免疫');
$b=combat_person(39,array('npc_bonearmor')); $b['sp']=0;
expect_ending(erc_bone_immune($b),'NPC骨甲保留免消耗免疫');
$a=combat_person(0,array('npc_prefetch')); $b=combat_person(34);
expect_ending(erc_active($a,$b,50)===60 && $a['sp']===980 && erc_active($a,$b,50)===50,'玩家预取仅首次先制生效且扣除体力');

$a=combat_person(0,array('npc_dimension_theft')); $a['pid']=70; $b=combat_person(); $b['pid']=71; $b['hp']=0;
erc_final_death($a,$b); expect_ending(!empty($a['itm1']) && empty($b['weps']),'玩家掠夺把实际战利品搬入空背包');
$before=$a; erc_final_death($a,$b); expect_ending($a===$before,'重复死亡回调不再掠夺或扣费');
$thief=combat_person(41,array('npc_dimension_theft')); $thief['pid']=80; $thief['pls']=129; $thief['pgroup']=3;
$enemy=combat_person(); $enemy['pid']=81; $enemy['pls']=129; $enemy['pgroup']=3;
$remote=combat_person(); $remote['pid']=82; $remote['pls']=122; $remote['pgroup']=3; $remote['clbpara']['dimension_mark']=80;
$saved=array(82=>$remote); erc_finish($thief,$enemy);
expect_ending($thief['pls']===122 && $saved[82]['pls']===129 && $enemy['pls']===129,'OMICRON与远处已标记玩家实际换位并保存');
$roll=99;
$a=combat_person(39,array('npc_lockhead')); $b=combat_person();
expect_ending(erc_damage($a,$b,400)===1000 && ($b['gg_flag'] ?? 0)===61,'弑翼爆头阈值是单次伤害达到当前生命四成');
$b=combat_person(); $b['race']=5;
expect_ending(erc_damage($a,$b,400)===400,'AI免弑翼头部处决但保留原伤害');

require GAME_ROOT.'include/system.func.php';
$club_skillslist=array(0=>array()); $baseexp=10;
$source=combat_person(12); $source['pid']=88; $source['name']='Dark Force幼体'; $source['pgroup']=3; $source['horizon']=2; $source['pls']=124; $source['clbpara']['dissociation_route']='true'; $source['clbpara']['er_sub']=0;
$saved=array(88=>$source); $evolved=evonpc(12,'Dark Force幼体',$source);
expect_ending($evolved['hp']===30000000 && $evolved['pgroup']===3 && $evolved['horizon']===2 && $evolved['pls']===124,'Dark Force真实进化保留湾城视界与地点');
expect_ending(get_clbpara($evolved['clbpara'])['dissociation_route']==='true' && strpos(end($db->queries),'WHERE pid=88')!==false,'Dark Force进化保存路线并仅更新当前实例');

$anpcinfo=array(); require config('ending_npc',1);
$cskills=array(); require config('ending_skills',1);
foreach(array(34=>9,35=>2,36=>7,37=>7,39=>8,40=>3,41=>2,42=>2) as $type=>$count) {
	expect_ending(count($anpcinfo[$type]['sub'])===$count,'NPC人数与定案一致 '.$type);
	foreach($anpcinfo[$type]['sub'] as $sub=>$npc) expect_ending(($npc['clbpara']['er_sub'] ?? -1)===$sub,'NPC子号必须入库 '.$type.'/'.$sub);
}
expect_ending($anpcinfo[34]['sub'][0]['wepk']==='WP','铁城必须拳系');
expect_ending($anpcinfo[36]['pose']===7 && $anpcinfo[36]['pgroup']===0 && $anpcinfo[37]['pgroup']===3,'动物团hub与boss为独立实体');
restore_error_handler();
echo 'ending_combat: '.$checks." checks passed\n";
