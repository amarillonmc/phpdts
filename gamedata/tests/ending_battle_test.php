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

echo "All ending battle integration tests passed.\n";
