<?php
if(!defined('IN_GAME')) exit('Access Denied');
function get_safe_plslist($mode=0) { return array(1,2,3); }
function player_format_with_db_structure($row) { unset($row['sub'],$row['clubskill'],$row['clubskillpara']); $row['clbpara']=json_encode(get_clbpara($row['clbpara'])); return $row; }
// 复用现有真实战斗回归的假数据库、角色工厂与外围桩；不连接部署数据库。
require __DIR__.'/club24_test.php';
$ending_routes_v2=1; $club_skills=1;
$test_dice=1; $test_revival=false; $gamevars=array(); $now=2000;
$test_fixed_damage=50000;
$a=c24_player(101); $b=c24_player(102); $b['type']=19; $b['name']='红暮'; $b['hp']=$b['mhp']=100000;
$a['wepsk']='H';
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===90000,'新路线真实战斗中的固定伤害被红暮截断为一万');
c24_assert(isset($db->players[102]) && $db->players[102]['hp']===90000,'截断后的生命实际保存');

$test_fixed_damage=100;
$a=c24_player(101); $b=c24_player(102); $b['type']=34; $b['clbpara']['skill']=array('npc_talisman');
for($i=0;$i<4;$i++) {
	\revcombat\rev_combat_prepare($a,$b,1,'',0);
	c24_assert($b['hp']===2000,'真实战斗跨请求逐层破符 '.$i);
	$b=fetch_playerdata_by_pid(102);
}
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===1900,'第五次真实交战穿过破碎符盾');

$a=c24_player(101); $b=c24_player(102); $b['type']=34; $b['clbpara']['skill']=array('npc_rook_chase');
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($a['action']==='pchase' && $a['bid']===102,'全局追击率零仍被铁城锁定追击');

$a=c24_player(101); $b=c24_player(102); $a['horizon']=$b['horizon']=2;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===1850,'EX真实固定伤害链为一百五十');

$a=c24_player(101); $a['type']=34; $a['clbpara']['skill']=array('npc_headshot'); $b=c24_player(102); $b['race']=0;
$test_fixed_damage=1800; $test_revival=true; $log='';
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===25 && $b['mhp']===2000,'头部破坏经过真实复活链且保留上限');
$test_revival=false;

$a=c24_player(101); $a['type']=34; $a['clbpara']['skill']=array('npc_headshot'); $b=c24_player(102); $b['race']=2;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===200,'妖精免疫爆头处决但仍承受高额伤害');

$test_fixed_damage=100;
$a=c24_player(101); $a['att']=200000; $a['wepe']=90000; $a['clbpara']['skill']=array('npc_rook_chase'); $b=c24_player(102); $b['type']=40; $b['clbpara']['skill']=array('npc_retro_barrier');
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($a['att']===200000 && $a['wepe']===90000 && in_array('npc_rook_chase',get_clbpara($a['clbpara'])['skill']),'时光战后恢复真实面板与原技能');
c24_assert($a['action']!=='chase' && $a['action']!=='pchase','时光锚定期间玩家追击技能暂时失效');

$test_fixed_damage=100; $test_dice=50;
$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=41; $b['clbpara']['skill']=array('npc_prefetch');
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===1900,'OMICRON预取骰失败时真实攻击可以命中');
$test_dice=1; $a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=41; $b['clbpara']['skill']=array('npc_prefetch');
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===2000 && ($b['clbpara']['prefetch_damage'] ?? 0)===100,'OMICRON预取骰成功保存被截取的实际伤害');

$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=34; $b['hp']=1050; $b['clbpara']['skill']=array('npc_stage_mine','npc_pawn_evolve');
$db->maptrap=array();
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['mhp']===32000 && !empty($b['clbpara']['pawn_evolved']) && count($db->maptrap)>0,'银锤在真实受击入口进化并布雷');

$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=39; $b['clbpara']['skill']=array('npc_sequence');
for($i=0;$i<3;$i++) { \revcombat\rev_combat_prepare($a,$b,1,'',0); $b=fetch_playerdata_by_pid(102); }
c24_assert($b['hp']===1850,'断序在真实跨请求战斗中依次承受100、50、0伤害');

$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=39; $b['clbpara']['skill']=array('npc_white_phosphorus');
$db->maptrap=array(); \revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert(count($db->maptrap)>=1 && !empty(get_itmpara($db->maptrap[0]['itmpara'])['phosphorus']),'烬锤实际受击产生带灼伤参数的高热雷');

$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=39; $b['clbpara']['skill']=array('npc_afterimage'); $b['clbpara']['afterimage']=1; $before=$a['weps'];
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===2000 && $a['weps']===$before-1,'残影使真实攻击落空且照常消耗弹药');
$b['clbpara']=get_clbpara($b['clbpara']); $b['clbpara']['afterimage']=0;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===1900,'原地未出手的瞬刃耗尽残影后，下一次真实交锋不凭空再生残影');
$b['clbpara']=get_clbpara($b['clbpara']); $b['clbpara']['afterimage']=0; $b['pls']++;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']===1900,'瞬刃实际移动地点后重新获得一层残影');

$a=c24_player(101); $a['sp']=500; $a['clbpara']['animal_debuff'][1]=5;
c24_item($a,1,'体力补给','HS',1000,1,''); item_recovery_stamina(1,$a);
c24_assert($a['sp']===800 && strpos($log,'300</span>点体力')!==false,'实际补给遵守熬战有效上限且日志显示实际恢复量');

$test_dice=99; $a=c24_player(101); $a['type']=34; $a['club']=98; $a['clbpara']['skill']=array('npc_arsenal');
c24_item($a,1,'燃烧弹','WF',100,10,'u'); c24_item($a,2,'穿甲弹','WG',100,10,'n'); $a['itmpara2']=array('payload'=>'keep');
$b=c24_player(102); $b['arb']='防火衣'; $b['arbk']='DB'; $b['arbs']=100; $b['arbsk']='U';
erc_arsenal_swap($a,$b);
c24_assert($a['wep']==='穿甲弹' && $a['wep_range']>=9 && get_itmpara($a['weppara'])['payload']==='keep','灵翼换装根据抗性选择弹种、维持射程且保留物品参数');

$a=c24_player(101); $a['sp']=800; $a['horizon']=1; $a['clbpara']['skill']=array('npc_flying','npc_shadow_step'); severe_apply($a,'lower');
$before=$a['hp']; $paid=calc_move_search_sp_cost($a,'move');
c24_assert($paid==='sp' && $a['sp']===0 && $a['hp']===$before,'下肢破坏恰八成体力可移动，灵子视界与飞行不能改扣血或打折');
$a['sp']=799; $before=$a; $paid=calc_move_search_sp_cost($a,'move');
c24_assert(!$paid && $a['sp']===799 && $a['hp']===$before['hp'],'下肢破坏不足八成体力不能借血移动');
$a['arf']='义肢战靴'; $a['arfs']=100; $before=$a['sp']; $paid=calc_move_search_sp_cost($a,'move');
c24_assert($paid==='sp' && $a['sp']===$before,'穿上义肢后原有飞行移动恢复');

$test_fixed_damage=NULL; $test_dice=1;
$a=c24_player(101); $a['wepk']='WD'; $a['clbpara']['skill']=array('npc_weak_analysis','npc_weak_break'); $a['clbpara']['analysis']=array('target'=>102,'stacks'=>4);
$b=c24_player(102); $b['hp']=$b['mhp']=100000000; $b['def']=100000;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']<100000000 && $b['base_def']>0 && $b['base_def']<=.01,'满层破绽经过真实普通物理公式，穿防且不会除零');

$a=c24_player(101); getclubskill('npc_weak_analysis',$a['clbpara']);
upgclbskills_events('active|npc_weak_analysis','npc_weak_analysis',$a);
c24_assert(!erc_has($a,'npc_weak_analysis'),'玩家通过现役开关技能入口关闭解析及承伤代价');
upgclbskills_events('active|npc_weak_analysis','npc_weak_analysis',$a);
c24_assert(erc_has($a,'npc_weak_analysis'),'玩家可重新开启解析');

class EndingBattleDb extends C24Db {
	public $nextid=9000;
	function array_insert($table,$row) {
		if($table==='test_players') { $row['pid']=++$this->nextid; $this->players[$row['pid']]=$row; }
		else parent::array_insert($table,$row);
	}
	function insert_id() { return $this->nextid; }
	function query($sql) {
		if(strpos($sql,'SELECT COUNT(*) FROM test_players WHERE type=34')===0) {
			$rows=array_filter($this->players,function($row) use($sql) {
				if((int)$row['type']!==34 || $row['hp']<=0) return false;
				if(strpos($sql,"name='白凰的符灵幻象'")!==false && $row['name']!=='白凰的符灵幻象') return false;
				if(strpos($sql,"name<>'白凰的符灵幻象'")!==false && $row['name']==='白凰的符灵幻象') return false;
				if(preg_match('/pid<>(\d+)/',$sql,$m) && (int)$row['pid']===(int)$m[1]) return false;
				return true;
			});
			return new C24Result(array(array(count($rows))));
		}
		if(strpos($sql,"DELETE FROM test_players WHERE type=34 AND name='白凰的符灵幻象'")===0) { foreach($this->players as $id=>$row) if((int)$row['type']===34 && $row['name']==='白凰的符灵幻象') unset($this->players[$id]); return true; }
		return parent::query($sql);
	}
}
$db=new EndingBattleDb(); $anpcinfo=array(); require config('ending_npc');
require GAME_ROOT.'include/game/ending_routes.func.php';
$current_ruleset_config=array(); include config('resources');
$test_fixed_damage=100; $test_dice=1;
$a=c24_player(101); $a['wepk']='WD'; $b=c24_player(102); $b['type']=34; $b['name']='白凰'; $b['clbpara']['er_sub']=6; $b['hp']=$b['mhp']=10000;
for($i=0;$i<5;$i++) \revcombat\rev_combat_prepare($a,$b,1,'',0);
$phantoms=array_filter($db->players,function($row) { return $row['name']==='白凰的符灵幻象' && $row['hp']>0; });
c24_assert(count($phantoms)===3,'白凰真实受击召唤符灵，反复交战仍最多三只');
$current_ruleset_config=array(); $gamevars=array('jiaotou_challenge'=>1); $b['hp']=0;
ending_routes_npc_dead($b,$a);
$phantoms=array_filter($db->players,function($row) { return $row['name']==='白凰的符灵幻象'; });
$finals=array_filter($db->players,function($row) { return (int)$row['type']===19; });
c24_assert(!$phantoms && count($finals)===2 && !empty($gamevars['jiaotou_final']),'白凰死亡清除附属符灵，符灵不妨碍八教头清场召唤红蓝');

$historical=get_addnpcinfo()[40]['sub'][1];
c24_assert($historical['mhp']===303303 && $historical['msp']===200 && $historical['att']===200 && $historical['def']===303303 && $historical['lvl']===1303 && $historical['skill']===100,'神奈原版面板经正式 NPC 资源读取');
$summoned=addnpc(40,1,1); $kanna=$db->players[$summoned[0]]; $kanna_para=get_clbpara($kanna['clbpara']);
c24_assert($kanna['hp']===303303 && $kanna['sp']===200 && $kanna['money']===55000 && $kanna['rage']===0 && $kanna['pose']===0 && $kanna['tactic']===0 && $kanna['gd']==='f' && $kanna['wk']===100,'神奈经真实 addnpc 保留原版体力、金钱、战术及六系熟练');
c24_assert($kanna['wep']==='翼人的羽毛' && $kanna['wepk']==='WK' && $kanna['wepe']===500 && $kanna['weps']===500 && $kanna['wepsk']==='' && $kanna['arbe']===50 && $kanna['arbs']===100 && $kanna['arhe']===25 && $kanna['arhs']===100 && $kanna['arae']===25 && $kanna['aras']===100 && $kanna['arfe']===25 && $kanna['arfs']===100 && $kanna['artk']==='AD' && $kanna['arte']===20 && $kanna['arts']===10,'神奈经真实 addnpc 保留原版全身装备和空武器属性');
c24_assert($kanna['pgroup']===3 && $kanna['horizon']===2 && $kanna_para['er_sub']===1 && in_array('npc_retro_barrier',$kanna_para['skill'],true) && $kanna['icon']==='ending_routes/type40_1.jpg' && $kanna['itm1']==='贯穿型长枪','神奈更换原版数值后保留新路线位置、时光锚定、头像与阶段掉落');
$kanna['clbpara']=$kanna_para; $target=c24_player(120); $target['pgroup']=3; $target['horizon']=2;
$test_fixed_damage=100; $test_dice=1;
\revcombat\rev_combat_prepare($kanna,$target,1,'',0);
c24_assert($kanna['lvl']===1303 && (int)$db->players[$kanna['pid']]['lvl']===1303,'神奈1303级经过真实攻击经验结算和保存后不降回255');
$capped=c24_player(121); $capped['lvl']=255; $capped['exp']=99999;
lvlup_rev($capped,$target,1);
c24_assert($capped['lvl']===255,'保留神奈原版等级不提高玩家255级上限');

// 天赋小节点：沿用真实入场、行动、恢复、攻击和尸体结算函数。
$gamevars=array(); $current_ruleset_config=array(); $ending_routes_v2=1; $club_skills=1;
$talent=talent_load(''); $talent['bought_points']=80;
foreach(talent_catalog() as $tree=>$definition) for($node=0;$node<4;$node++) talent_change($talent,'learn',$tree,$node);
$a=c24_player(201); c24_item($a,1,'面包','HH',120,15,''); c24_item($a,2,'矿泉水','HS',140,15,'');
$a['itmpara1']=array('entry_marker'=>1);
talent_entry($a,array('talentdata'=>$talent),0);
c24_assert(talent_cost($talent)===80 && talent_bonus($a,'action_sp')===3 && talent_bonus($a,'weapon_care')===30 && talent_bonus($a,'kill_sp')===9 && talent_bonus($a,'corpse_money')===20 && talent_bonus($a,'rest_recovery')===30,'16 树中段按原成本投入，各类小天赋加法叠加且不要求佩戴头衔');
c24_assert($a['itms1']===30 && $a['itms2']===30 && $a['itme1']===120 && $a['itmpara1']===array('entry_marker'=>1),'备粮叠加入场食物与饮料各15份，不改效果或参数');
player_save($a); $a=fetch_playerdata_by_pid(201);
c24_assert(talent_bonus($a,'rest_recovery')===30 && $a['itms1']===30,'补给数量和当前天赋快照保存、重读后保留');
$b=c24_player(202); talent_entry($b,array('talentdata'=>$talent),0);
c24_assert($b['itm1']==='面包' && $b['itms1']===15 && $b['itm2']==='矿泉水' && $b['itms2']===15,'特殊入场没有原补给时补普通面包与水');
$c=c24_player(203); for($i=1;$i<=6;$i++) c24_item($c,$i);
$db->mapitem=array(); talent_entry($c,array('talentdata'=>$talent),0);
c24_assert(count($db->mapitem)===2 && $c['itm1']==='测试装备' && $db->mapitem[0]['itms']===15,'特殊满包入场按已有给物规则放脚下，不覆盖其他物品');
$unlimited=c24_player(204); c24_item($unlimited,1,'面包','HH',120,$nosta,''); c24_item($unlimited,2,'矿泉水','HS',140,$nosta,'');
talent_entry($unlimited,array('talentdata'=>$talent),0);
c24_assert($unlimited['itms1']===$nosta && $unlimited['itms2']===$nosta,'无限数量补给保持无限，不转换为整数');
talent_change($talent,'reset'); $b=c24_player(202); talent_entry($b,array('talentdata'=>$talent),0);
c24_assert(talent_bonus($b,'rest_recovery')===0 && !$b['itms1'] && count($talent['unlocked_skills'])===16,'洗点后的入场不再发小节点收益，永久技能照常保留');

$movesp=15; $movehp=2; $inf_move_sp=array();
foreach(array('move','search') as $act) {
	$a=c24_player(201); $a['clbpara']['talent_bonus']=array('action_sp'=>1);
	calc_move_search_sp_cost($a,$act);
	c24_assert($a['sp']===986,'轻装单节点使实际'.$act.'从15体力降为14');
	$a=c24_player(201); $a['clbpara']['talent_bonus']=array('action_sp'=>3); calc_move_search_sp_cost($a,$act);
	c24_assert($a['sp']===988,'轻装三树叠加后实际'.$act.'消耗12体力');
}
$a=c24_player(201,6); $a['lvl']=20; $a['clbpara']['talent_bonus']=array('action_sp'=>3); calc_move_search_sp_cost($a,'search');
c24_assert($a['sp']===999,'轻装与宛如疾风叠加仍至少消耗1体力');
$a=c24_player(201); $a['clbpara']['talent_bonus']=array('action_sp'=>3); severe_apply($a,'lower'); calc_move_search_sp_cost($a,'move');
c24_assert($a['sp']===200,'轻装不能降低下肢破坏的八成固定体力支出');

$heal_time=6; $sleep_time=3; $rage_time=6;
$a=c24_player(201); $a['pose']=0; $a['clbpara']['talent_bonus']=array('rest_recovery'=>15);
c24_assert(calculate_rest_uphp(60,$a)===230.0 && calculate_rest_upsp(60,$a)===230.0 && $heal_time===6 && $sleep_time===3,'调息每时间单位恢复量增加15%，不改两个恢复周期');
$a['clbpara']['talent_bonus']['rest_recovery']=30;
c24_assert(calculate_rest_uphp(60,$a)===260.0 && calculate_rest_upsp(60,$a)===260.0,'两棵调息加法叠加为30%');
$heal_time=$sleep_time=1;
c24_assert(calculate_rest_uphp(1,$a)===26.0 && calculate_rest_upsp(1,$a)===13.0,'恢复周期已是1秒时调息仍增加实际恢复量');
c24_assert(talent_rest_required($a,180)===180,'调息不改变恶伤、诅咒的自然静养时限');
$a['hp']=100; $a['sp']=100; $a['state']=3; $a['endtime']=$now-10;
rest('rest',$a); c24_assert($a['hp']===360.0 && $a['sp']===230.0,'静养实际恢复生命、体力且使用当前投入快照');
$before_rest=array($a['hp'],$a['sp']); rest('rest',$a);
c24_assert(array($a['hp'],$a['sp'])===$before_rest,'同一时间重复刷新静养不重复恢复');
$a['hp']=$a['mhp']-1; $a['sp']=$a['msp']-1; $a['endtime']=$now-10; rest('rest',$a);
c24_assert($a['hp']===$a['mhp'] && $a['sp']===$a['msp'],'调息结算仍受生命、体力上限约束');

$test_fixed_damage=100;
foreach(array(9=>30,10=>29) as $roll=>$ammo) {
	$test_dice=$roll; $a=c24_player(201); $a['clbpara']['talent_bonus']=array('weapon_care'=>10); $b=c24_player(202); $b['type']=38;
	\revcombat\rev_combat_prepare($a,$b,1,'',0);
	c24_assert($a['weps']===$ammo,'养护真实普通攻击在骰值'.$roll.'时'.($roll===9 ? '节省' : '正常消耗').'一发弹药');
}
$test_dice=9; $a=c24_player(201); $a['clbpara']['talent_bonus']=array('weapon_care'=>30); $a['wepk']='WK'; $a['weps']=$nosta; $b=c24_player(202); $b['type']=38;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($a['wepe']<80,'无限耐久武器照常损耗攻击力，不受养护减免');
$a=c24_player(201); $a['wep_kind']='G'; $a['clbpara']['talent_bonus']=array('weapon_care'=>30); weapon_loss($a,2);
c24_assert($a['weps']===28,'直接损坏武器的效果不走普通攻击养护');

$test_dice=1; $a=c24_player(201); $a['sp']=500; $a['clbpara']['talent_bonus']=array('kill_sp'=>3); $b=c24_player(202); $b['type']=38; $b['hp']=50;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($b['hp']<=0 && $a['sp']===530,'真实击破NPC后续战恢复3%体力上限');
player_save($a); $a=fetch_playerdata_by_pid(201); $b=fetch_playerdata_by_pid(202); erc_final_death($a,$b);
c24_assert($a['sp']===530,'保存重读后重复死亡回调不重复触发续战');
$a=c24_player(201); $a['sp']=500; $a['clbpara']['talent_bonus']=array('kill_sp'=>9); $b=c24_player(202); $b['type']=38; $b['hp']=0; erc_final_death($a,$b);
c24_assert($a['sp']===590,'续战三树叠加恢复9%体力上限');
$a['sp']=990; $b=c24_player(202); $b['type']=38; $b['hp']=0; erc_final_death($a,$b);
c24_assert($a['sp']===1000,'续战不能超过体力上限');
$a['sp']=950; $a['clbpara']['animal_debuff'][1]=2; $b=c24_player(202); $b['type']=38; $b['hp']=0; erc_final_death($a,$b);
c24_assert($a['sp']===920,'续战保留熬战的有效体力上限');
$a=c24_player(201); $a['sp']=500; $a['clbpara']['talent_bonus']=array('kill_sp'=>3); $b=c24_player(202); $b['hp']=0; erc_final_death($a,$b);
c24_assert($a['sp']===500,'击破玩家不触发续战');
$a['hp']=0; $b=c24_player(202); $b['type']=38; $b['hp']=0; erc_final_death($a,$b);
c24_assert($a['sp']===500,'击杀者已经死亡时不触发续战');
$a=c24_player(201); $a['sp']=500; $a['clbpara']['talent_bonus']=array('kill_sp'=>3); $b=c24_player(202); $b['type']=38; $b['hp']=50; $test_revival=true;
\revcombat\rev_combat_prepare($a,$b,1,'',0); $test_revival=false;
c24_assert($b['hp']>0 && $a['sp']===500,'NPC复活成功不能触发续战');

$a=c24_player(201); $a['money']=0; $a['action']='corpse'; $a['bid']=202; $a['clbpara']['talent_bonus']=array('corpse_money'=>10);
$b=c24_player(202); $b['hp']=0; $b['type']=38; $b['money']=99; player_save($b); getcorpse('money',$a); player_save($a);
c24_assert($a['money']===108 && fetch_playerdata_by_pid(202)['money']===0,'盘缠在实际NPC尸体拾钱时额外获得10%，小数向下取整并清空尸体金钱');
$a=fetch_playerdata_by_pid(201); $a['action']='corpse'; $a['bid']=202; getcorpse('money',$a);
c24_assert($a['money']===108,'重读后再次搜同一尸体不重复获得盘缠');
$a['action']='corpse'; $a['bid']=202; $a['clbpara']['talent_bonus']['corpse_money']=20; $b['money']=100; player_save($b); getcorpse('money',$a);
c24_assert($a['money']===228,'两树盘缠叠加为20%');
$a['action']='corpse'; $a['bid']=202; $b['type']=0; $b['money']=100; player_save($b); getcorpse('money',$a);
c24_assert($a['money']===328,'玩家尸体只获得原有金钱');

$ending_routes_v2=0; $a=c24_player(201); $a['clbpara']['talent_bonus']=array('rest_recovery'=>30,'action_sp'=>3,'weapon_care'=>30,'corpse_money'=>20,'kill_sp'=>9); $a['pose']=0;
c24_assert(calculate_rest_uphp(1,$a)===20.0 && calculate_rest_upsp(1,$a)===10.0,'功能关闭后旧快照不改变恢复量');
calc_move_search_sp_cost($a,'search'); c24_assert($a['sp']===985,'功能关闭后旧快照不改变探索消耗');
$a['action']='corpse'; $a['bid']=202; $a['money']=0; $b['type']=38; $b['money']=100; player_save($b); getcorpse('money',$a);
c24_assert($a['money']===100,'功能关闭后旧快照不增加尸体金钱');
$a=c24_player(201); $a['clbpara']['talent_bonus']=array('weapon_care'=>30,'kill_sp'=>9); $a['sp']=500; $b=c24_player(202); $b['type']=38; $b['hp']=50;
\revcombat\rev_combat_prepare($a,$b,1,'',0);
c24_assert($a['weps']===29 && $a['sp']===500,'功能关闭后旧快照不触发养护与续战');
$ending_routes_v2=1; $a['type']=38;
c24_assert(talent_bonus($a,'weapon_care')===0 && talent_bonus($a,'kill_sp')===0,'NPC不享受玩家小天赋');

echo "All ending battle integration tests passed.\n";
