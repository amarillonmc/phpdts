<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 沿用现有战斗回归的内存数据库和外围桩；不读取部署配置、不写实际对局。
function diceroll($max) {
	global $test_dice,$dice_calls,$binglai_hit_dice,$binglai_hit_checks;
	$dice_calls[] = $max;
	$caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'] ?? '';
	if($caller === 'revattr\\get_hit_time_rev') {
		$binglai_hit_checks++;
		if(!empty($binglai_hit_dice)) return min($max,array_shift($binglai_hit_dice));
	}
	return min($max,$test_dice);
}
require __DIR__.'/club25_combat_test.php';
require_once GAME_ROOT.'include/account_rewards.func.php';
include config('resources'); include config('gamecfg'); include config('combatcfg'); include config('clubskills'); include config('titles');
$baseexp=10000; $chase_obbs=$dfight_obbs=0; $test_fixed_damage=100; $test_revival=false; $test_dice=1; $gamevars=Array();
$binglai_hit_dice=Array(); $binglai_hit_checks=0; $binglai_checks=0;
set_error_handler(function($level,$message,$file,$line) {
	if($level === E_NOTICE && strpos(basename($file),'mianhuping') !== 0) return true;
	throw new ErrorException($message,0,$level,$file,$line);
});
function binglai_assert($ok,$label) {
	global $binglai_checks;
	$binglai_checks++;
	if(!$ok) throw new RuntimeException('FAIL: '.$label);
}
class BinglaiTestDb extends C25CombatDb {
	function query($sql) {
		if(preg_match("/^SELECT \\* FROM test_players WHERE pls='(\\d+)'(?: AND pgroup='(\\d+)')? AND hp>0 AND pid<>'(\\d+)' AND pid<>'(\\d+)'/",$sql,$m)) {
			$this->queries[]=$sql;
			return new C25CombatResult(array_filter($this->players,function($p) use($m) {
				return $p['pls']==$m[1] && ($m[2]==='' || $p['pgroup']==$m[2]) && $p['hp']>0 && $p['pid']!=$m[3] && $p['pid']!=$m[4];
			}));
		}
		if(preg_match("/^UPDATE test_players SET hp='(\\d+)',clbpara=X'([0-9a-f]+)' WHERE pid='(\\d+)'/",$sql,$m)) {
			$this->queries[]=$sql; $this->players[$m[3]]['hp']=(int)$m[1]; $this->players[$m[3]]['clbpara']=hex2bin($m[2]); return true;
		}
		return parent::query($sql);
	}
}
function binglai_item(&$p,$slot,$name,$count) {
	$p['itm'.$slot]=$name; $p['itms'.$slot]=$count;
	$p['itmk'.$slot]=$name==='锅'?'WP':'HB'; $p['itme'.$slot]=40;
	$p['itmsk'.$slot]='v'; $p['itmpara'.$slot]='{"test":"保留参数"}';
}
function binglai_player($bread=0,$water=0,$jam=0) {
	$p=c25c_player(1); $p['wep']='锅'; $p['wepk']='WP'; $p['wepe']=40; $p['weps']='∞';
	$p['clbpara']['skill'][]='tl_binglai';
	if($bread) binglai_item($p,1,'面包',$bread);
	if($water) binglai_item($p,2,'矿泉水',$water);
	if($jam) binglai_item($p,3,'曾经的荣光-橙黄',$jam);
	return $p;
}
// 寻找可重复的随机种子；不向生产函数加入指定配方的测试后门。
function binglai_seed($p,$name,$cost=null) {
	for($seed=0;$seed<2000;$seed++) {
		mt_srand($seed); $r=binglai_roll($p);
		if($r && $r['name']===$name && ($cost===null || $r['cost']===$cost)) { mt_srand($seed); return $seed; }
	}
	throw new RuntimeException('无法生成配方 '.$name);
}

$db=new BinglaiTestDb();
$reward=account_reward_validate(Array('titles'=>Array(9302)));
binglai_assert($reward['titles']===Array(9302) && account_reward_catalog()['titles'][9302]==='面糊饼职人','激活码邮件奖励目录接受头衔9302');
binglai_assert(strpos(titles_get_desc(9302),'面糊饼职人')!==false && strpos(titles_get_desc(9302),'饼来')!==false,'局部重读头衔配置后仍显示名称和说明');
$p=c25c_player(1,1); $p['clbpara']['test_keep']=7;
foreach(get_title_valid(9302) as $key=>$value) $p[$key]=$key==='clbpara'?array_merge(get_clbpara($p[$key]),$value):$value;
updateskill($p); player_save($p); $saved=fetch_playerdata_by_pid(1);
binglai_assert($saved['itm6']==='锅' && $saved['itmk6']==='WP' && $saved['itme6']===40 && $saved['itms6']==='∞' && $saved['itmsk6']==='' && $saved['itmpara6']==='','入场六号槽获得完整锅参数并保存');
binglai_assert(in_array('tl_binglai',$saved['clbpara']['skill']) && in_array('c1_crit',$saved['clbpara']['skill']) && $saved['clbpara']['test_keep']===7,'入场技能与社团技能、已有杂项参数并存');

$p=binglai_player(1); binglai_assert(!check_skill_unlock('tl_binglai',$p),'装备锅可以发动');
$p['wep']='大铁锅'; binglai_assert(check_skill_unlock('tl_binglai',$p)==='binglai_pot','不将仅含锅字的其他武器当成锅');
binglai_item($p,6,'锅','∞'); binglai_assert(!check_skill_unlock('tl_binglai',$p),'背包锅可以发动');
$p['itms6']=0; binglai_assert(check_skill_unlock('tl_binglai',$p)==='binglai_pot','空槽残留名称不能当成锅');
foreach(Array('面包','矿泉水','曾经的荣光-橙黄') as $food) {
	$p=binglai_player(); binglai_item($p,1,$food,1);
	binglai_assert(!check_skill_unlock('tl_binglai',$p),'食材解锁 '.$food);
	$p['itms1']=0; binglai_assert(check_skill_unlock('tl_binglai',$p)==='binglai_food','数量为零不解锁 '.$food);
}
$p=binglai_player(); binglai_item($p,0,'面包',3);
binglai_assert(check_skill_unlock('tl_binglai',$p)==='binglai_food','待拾取食材不参与');
$p=binglai_player(1); $p['type']=1; binglai_assert(check_skill_unlock('tl_binglai',$p)==='type','NPC不能发动');
$p['type']=0; $p['clbpara']['skill']=Array(); binglai_assert(check_skill_unlock('tl_binglai',$p)==='noskill','未学技能不能发动');
$p=binglai_player(1); $p['rage']=9; binglai_assert((bool)check_skill_cost('tl_binglai',$p),'怒气不足拒绝');
$p['rage']=10; binglai_assert(!check_skill_cost('tl_binglai',$p),'恰好10怒气可发动');

$p=binglai_player(3,3,3); $seen=Array();
for($seed=0;$seed<500;$seed++) { mt_srand($seed); $roll=binglai_roll($p); $seen[$roll['name']]=1; }
binglai_assert(count($seen)===12 && isset($seen['法式面包棍棒'],$seen['高级法式面包棍棒'],$seen['调味法式面包棍棒']),'持有果酱仍可抽到全部12式');
$p=binglai_player(1,1); $seen=Array();
for($seed=0;$seed<100;$seed++) { mt_srand($seed); $roll=binglai_roll($p); $seen[$roll['name']]=1; }
binglai_assert(count($seen)===3 && isset($seen['烤面包'],$seen['热水泼洒'],$seen['法式面包棍棒']),'只有面1水1时排除果酱和用量不足配方');
$p=binglai_player(99); $quantities=Array();
for($seed=0;$seed<60;$seed++) { mt_srand($seed); $r=binglai_roll($p); $quantities[$r['cost']['面包']]=1; }
ksort($quantities); binglai_assert(array_keys($quantities)===Array(1,2,3),'单材料用量1～3，上限不随存量增加');

$expected=Array(
	'烤面包'=>Array(Array('面包'=>3),1,1,false,40),
	'热水泼洒'=>Array(Array('矿泉水'=>3),1.3,1,true,0),
	'面糊饼'=>Array(Array('面包'=>2,'矿泉水'=>1),1.4,1,false,20),
	'面糊汤'=>Array(Array('面包'=>1,'矿泉水'=>2),1,1,false,60),
	'法式面包棍棒'=>Array(Array('面包'=>1,'矿泉水'=>1),1.2,2,false,0),
	'高级法式面包棍棒'=>Array(Array('面包'=>2,'矿泉水'=>2),1.4,2,false,0),
	'炸果酱'=>Array(Array('曾经的荣光-橙黄'=>1),1,1,false,30),
	'烤果酱面包'=>Array(Array('曾经的荣光-橙黄'=>1,'面包'=>3),1,1,false,70),
	'果酱汤'=>Array(Array('曾经的荣光-橙黄'=>1,'矿泉水'=>3),1.5,1,true,40),
	'果酱面糊饼'=>Array(Array('曾经的荣光-橙黄'=>1,'面包'=>2,'矿泉水'=>1),1.4,1,false,20),
	'果酱面糊汤'=>Array(Array('曾经的荣光-橙黄'=>1,'面包'=>1,'矿泉水'=>2),1,1,false,60),
	'调味法式面包棍棒'=>Array(Array('曾经的荣光-橙黄'=>1,'面包'=>1,'矿泉水'=>1),1.15,3,false,0),
);
foreach($expected as $name=>$spec) {
	$a=binglai_player(3,3,3); $b=c25c_player(2); $a['bskill']='tl_binglai'; $a['rage']=10;
	$before=binglai_inventory($a); binglai_seed($a,$name,$spec[0]);
	\revattr\get_attr_bskills($a,$b,1); $r=$a['binglai_roll'];
	binglai_assert($r['cost']===$spec[0] && abs($r['mult']-$spec[1])<0.00001 && $r['hits']===$spec[2] && $r['aoe']===$spec[3] && $r['shield_pct']==$spec[4],'配方数值 '.$name);
	$after=binglai_inventory($a);
	foreach($spec[0] as $food=>$count) binglai_assert($after[$food]===$before[$food]-$count,'实际食材消耗 '.$name.' '.$food);
	binglai_assert($a['rage']===0 && $a['wep']==='锅' && $a['weps']==='∞' && ($a['clbpara']['binglai_shield'] ?? 0)==$a['mhp']*$spec[4]/100,'每次扣10怒气，锅不消耗，盾在发动时发放 '.$name);
}

$a=binglai_player(1); binglai_item($a,4,'面包',2); $before=$a['itmpara4']; $b=c25c_player(2); $a['bskill']='tl_binglai';
binglai_seed($a,'烤面包',Array('面包'=>2)); \revattr\get_attr_bskills($a,$b,1);
binglai_assert($a['itm1']==='' && $a['itmk1']==='' && $a['itme1']===0 && $a['itms1']===0 && $a['itmsk1']==='' && $a['itmpara1']==='','跨槽消耗后完整清空耗尽槽');
binglai_assert($a['itms4']===1 && $a['itmpara4']===$before,'未耗尽槽保留所有物品参数');
$a=binglai_player(1); $a['bskill']='tl_binglai'; \revattr\get_attr_bskills($a,$b,1);
binglai_assert($a['itm1']==='' && isset($a['bskill_tl_binglai'],$a['binglai_roll']),'最后一份食材也能成功发动');
$a=binglai_player('∞'); $a['bskill']='tl_binglai'; \revattr\get_attr_bskills($a,$b,1);
binglai_assert($a['itms1']==='∞','无限耐久食材沿用无限数量约定');

$a=binglai_player(10); $a['clbpara']['binglai_shield']=3900;
binglai_seed($a,'烤面包',Array('面包'=>1)); binglai_prepare($a);
binglai_assert($a['clbpara']['binglai_shield']===4000,'临时生命可叠加且封顶发放时最大生命的200%');
$a['mhp']=1000; binglai_assert(binglai_absorb($a,500)===0 && $a['clbpara']['binglai_shield']===3500,'发放后盾为绝对值，不随最大生命变化');
binglai_assert(binglai_absorb($a,3600)===100 && $a['clbpara']['binglai_shield']===0,'先耗盾，仅将剩余伤害交给生命');

foreach(Array('法式面包棍棒'=>240,'高级法式面包棍棒'=>280,'调味法式面包棍棒'=>345) as $recipe=>$damage) {
	$a=binglai_player(3,3,3); $b=c25c_player(2); $log=''; $binglai_hit_checks=0;
	binglai_seed($a,$recipe); \revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
	binglai_assert($b['hp']===2000-$damage,'真实战斗连续攻击各段倍率、末段倍率正确 '.$recipe);
	binglai_assert($binglai_hit_checks===($recipe==='调味法式面包棍棒'?9:6),'每段独立进行一次命中判定 '.$recipe);
	foreach(Array('binglai_roll','binglai_hits','bskill_tl_binglai','bskill') as $field) binglai_assert(!isset($a[$field]),'战后清除临时字段 '.$recipe.' '.$field);
	binglai_assert(!isset($a['clbpara']['binglai_shield']),'战后清除临时生命 '.$recipe);
}
$a=binglai_player(3,3,3); $b=c25c_player(2); $log='';
$binglai_hit_dice=Array(1,99,99,99,99,99,1,99,99);
binglai_seed($a,'调味法式面包棍棒'); \revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($b['hp']===1770 && substr_count($log,'但是没有击中')===1,'中间一段落空不会吞掉末段，末段仍为1.15倍');
$a=binglai_player(1); $b=c25c_player(2); $b['tactic']=1; $log='';
$binglai_hit_dice=Array(99,99,99,1,99,99);
\revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($a['hp']===2000 && $b['hp']===2000 && strpos($log,'抵消了100点伤害')!==false,'无攻配方普攻可落空，发动时发盾仍可抵消反击');
binglai_assert(!isset($a['clbpara']['binglai_shield']) && !isset(fetch_playerdata_by_pid(1)['clbpara']['binglai_shield']),'本场结束后保存和重新读取均无残盾');

// 正常物理＋属性伤害也使用每段倍率，免伤／伤害制御仍在倍率之后生效。
$test_fixed_damage=null; $a=binglai_player(3,3,3); $b=c25c_player(2);
$a['wepsk']='p'; $a['nm']='你'; $b['nm']=$b['name'];
\revcombat\get_attr_wepbase($a,$b,1); $plain=$a; $plain_target=$b;
mt_srand(88); $normal=\revcombat\rev_attack($plain,$plain_target,1);
$a['binglai_roll']=Array('mult'=>1.4,'aoe'=>false);
mt_srand(88); $boosted=\revcombat\rev_attack($a,$b,1);
binglai_assert($boosted==round($normal*1.4),'真实普通伤害的物理及属性部分共同吃配方倍率');
$a=binglai_player(3,3,3); $b=c25c_player(2); $b['clbpara']['skill']=Array('fireseed4');
binglai_seed($a,'面糊饼'); \revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($b['hp']==2000,'正常配方倍率不越过种火IV免伤');
$test_fixed_damage=100;

$a=binglai_player(); $b=c25c_player(2); $a['clbpara']['binglai_shield']=200; $a['wep_kind']='P'; $b['wep_kind']='G';
$a['ex_keys']=$b['ex_keys']=Array();
fcg_direct_hit($b,$a,100,1,'测试反噬',false);
binglai_assert($a['hp']==2000 && $a['clbpara']['binglai_shield']==100,'种火独立攻击及反噬也先耗盾');
$a=binglai_player(); $b=c25c_player(2); $a['clbpara']['binglai_shield']=1200;
$a['final_damage']=1000; $a['ex_keys']=Array(); $a['wep_name']='锅';
\revattr\attack_finish_events($a,$b,1);
binglai_assert($a['hp']==2000 && $a['clbpara']['binglai_shield']==200,'高伤害反噬先消耗临时生命');
$observed=false;
for($seed=0;$seed<30;$seed++) {
	$a=c25c_player(2); $a['type']=19; $a['name']='红暮'; $a['wep_kind']='P';
	$b=binglai_player(); $b['clbpara']['binglai_shield']=4000; $b['wep_kind']='P';
	mt_srand($seed); \revattr\attr_extra_19_crimson($a,$b,1,'attack');
	if($b['clbpara']['binglai_shield']<4000) { $observed=$b['hp']==2000; break; }
}
binglai_assert($observed,'NPC额外攻击命中时先消耗盾');
$db=new BinglaiTestDb(); $a=binglai_player(1); $b=c25c_player(2); $b['hp']=1; $b['clbpara']['binglai_shield']=1;
\revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($b['hp']==0 && !isset($a['clbpara']['binglai_shield']) && !isset($b['clbpara']['binglai_shield']),'击杀使死亡流程转写JSON后，双方临时生命仍安全清除');
$test_dice=1;

// 全伤害只分池一次，主目标没有额外完整主攻；两人在场时均会入选。
$db=new BinglaiTestDb(); $a=binglai_player(0,1); $b=c25c_player(2); $log=''; $test_dice=1;
\revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($a['hp']==1945 && $b['hp']==1945,'110点群攻池均分55，主攻不重复扣110，实际：'.$a['hp'].'/'.$b['hp']);
$db=new BinglaiTestDb(); $a=binglai_player(0,1); $b=c25c_player(2); $test_dice=99;
\revcombat\rev_combat_prepare($a,$b,1,'bskill_tl_binglai',0);
binglai_assert($a['hp']===2000 && $b['hp']===2000 && !$db->queries,'主攻未命中不查询群攻目标、不溅射');
$test_dice=1; $a=binglai_player(0,1); $b=c25c_player(2); $a['binglai_roll']=Array('name'=>'热水泼洒');
$a['hp']=20; $b['hp']=15; $dmg=binglai_splash($a,$b,10000); $b['hp']-=$dmg;
binglai_assert($a['hp']==1 && $b['hp']==1,'自己与主目标均保底1血');
foreach(Array('fireseed3','fireseed4','def') as $guard) {
	$db=new BinglaiTestDb(); $a=binglai_player(); $b=c25c_player(2); $a['binglai_roll']=Array('name'=>'热水泼洒');
	if($guard==='def') $b['def']=10001; else $b['clbpara']['skill'][]=$guard;
	$dmg=binglai_splash($a,$b,101);
	binglai_assert($dmg==1 && $a['hp']==1949,'群攻向上取整并尊重单点保护 '.$guard);
}
$db=new BinglaiTestDb(); $a=binglai_player(); $b=c25c_player(2); $a['binglai_roll']=Array('name'=>'热水泼洒');
$outsider=c25c_player(3); $outsider['clbpara']['skill']=Array('fireseed4'); player_save($outsider);
$othergroup=c25c_player(4); $othergroup['pgroup']=1; player_save($othergroup);
$dead=c25c_player(5); $dead['hp']=0; player_save($dead);
for($seed=0;$seed<30;$seed++) {
	mt_srand($seed); binglai_splash($a,$b,101);
	if(fetch_playerdata_by_pid(3)['hp']<2000) break;
}
binglai_assert(fetch_playerdata_by_pid(3)['hp']===1999,'数据库JSON中的种火技能也只受1点伤害并保存');
binglai_assert(fetch_playerdata_by_pid(4)['hp']===2000 && fetch_playerdata_by_pid(5)['hp']===0,'不波及其他地图组或尸体');
$before=$db->queries; binglai_splash($a,$b,0); binglai_assert($before===$db->queries,'零伤害池直接结束');

$db=new BinglaiTestDb(); $a=binglai_player(); $b=c25c_player(2); $a['binglai_roll']=Array('name'=>'果酱汤');
$a['clbpara']['binglai_shield']=30; $b['clbpara']['binglai_shield']=30;
$dmg=binglai_splash($a,$b,101); $b['hp']-=$dmg;
binglai_assert($a['hp']==1979 && $b['hp']==1979 && !$a['clbpara']['binglai_shield'] && !$b['clbpara']['binglai_shield'],'群攻自己和主目标先耗30点盾，再受21点伤害');
$outsider=c25c_player(3); $outsider['clbpara']['binglai_shield']=10; player_save($outsider);
for($seed=0;$seed<30;$seed++) {
	mt_srand($seed); binglai_splash($a,$b,101);
	if(fetch_playerdata_by_pid(3)['hp']<2000) break;
}
$saved=fetch_playerdata_by_pid(3);
binglai_assert($saved['hp']==1976 && !$saved['clbpara']['binglai_shield'],'旁观者盾和生命一同保存，重新读取保持结果');
$db=new BinglaiTestDb();
for($id=3;$id<=12;$id++) player_save(c25c_player($id));
$counts=Array();
for($seed=0;$seed<30;$seed++) {
	$a=binglai_player(); $b=c25c_player(2); $a['binglai_roll']=Array('name'=>'热水泼洒'); $log='';
	mt_srand($seed); binglai_splash($a,$b,100);
	preg_match('/波及了(\d+)人/',$log,$match); $counts[(int)$match[1]]=1;
}
ksort($counts); binglai_assert(array_keys($counts)===Array(2,3,4,5),'充足目标时随机人数覆盖2～5，绝不超过5');

// 原核子仍用自身0.8／1.2／7.77系数；双方与旁观者的盾须保存到实际数据。
foreach(Array(0=>0.8,65=>1.2,128=>7.77) as $randver=>$mult) {
	$db=new BinglaiTestDb(); $a=binglai_player(); $b=c25c_player(2);
	$a['final_damage']=100; $a['weppara']=Array('isNuclearWeapon'=>1); $a['clbpara']['randver1']=$randver;
	$a['clbpara']['binglai_shield']=10; $b['clbpara']['binglai_shield']=10;
	\revattr\get_hurt_events($a,$b,1); $expected=2000-(ceil(100*$mult/2)-10);
	binglai_assert($a['hp']==$expected && $b['hp']==$expected && !$a['clbpara']['binglai_shield'] && !$b['clbpara']['binglai_shield'],'核子原伤害系数和盾吸收 '.$randver);
}

// 从真实命令入口提交、保存、重复提交，校验失败也不能偷耗食材或降级为普通攻击。
$db=new BinglaiTestDb(); $a=binglai_player(1); $a['action']='enemy'; $a['bid']=2; $pdata=&$a; $b=c25c_player(2); player_save($b); $log='';
\revbattle\findenemy_rev($b);
binglai_assert(count($battle_skills)===1 && $battle_skills[0][1]==='tl_binglai' && !$battle_skills[0][0],'原战斗按钮列表可显示饼来');
\revbattle\revbattle_prepare('bskill_tl_binglai');
binglai_assert($a['itm1']==='' && fetch_playerdata_by_pid(1)['itm1']==='' && fetch_playerdata_by_pid(2)['hp']===1900,'真实提交消耗食材、伤害和保存均完成');
$before=fetch_playerdata_by_pid(2); \revbattle\revbattle_prepare('bskill_tl_binglai');
binglai_assert(fetch_playerdata_by_pid(2)===$before,'重复提交已完成战斗不再次伤害');
$a=binglai_player(1); $a['action']='enemy'; $a['bid']=2; $a['rage']=9; $before=$a;
\revbattle\revbattle_prepare('bskill_tl_binglai');
binglai_assert($a===$before && fetch_playerdata_by_pid(2)['hp']===1900,'怒气不足的真实命令不消耗、不攻击');
$a['rage']=10; $a['wep']='别的武器'; $before=$a;
\revbattle\revbattle_prepare('bskill_tl_binglai');
binglai_assert($a===$before,'缺锅的真实命令不消耗');
$a=fetch_playerdata_by_pid(1); $a['action']='enemy'; $a['bid']=2; $before=$a;
\revbattle\revbattle_prepare('bskill_tl_binglai');
binglai_assert($a===$before,'重新加载后食材已耗尽，伪造再次发动被拒绝');
unset($pdata); unset($GLOBALS['pdata']);

echo "All mianhuping tests passed ($binglai_checks checks).\n";
