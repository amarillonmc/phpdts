<?php
if (!defined('IN_GAME')) exit('Access Denied');

error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$testcfg = array('base_exp'=>20,'combat'=>array('growth_per_level'=>0.025,'shot_hit'=>100,'slash_hit'=>100,
    'slash_variance'=>array(1,1),'wear_per_hit'=>2,'earth_actions'=>3,'earth_cooldown'=>6,'flurry_chance'=>0,
    'talent_priority'=>array('sure_hit_before_evade'=>true,'sky_before_earth'=>true)),
    'economy'=>array('rewards'=>array('player'=>array('money'=>120,'rep'=>30),'grunt'=>array('money'=>40,'rep'=>5))));
function another_config() { global $testcfg; return $testcfg; }
require_once dirname(__DIR__).'/include/another.combat.php';

$checks = 0;
function ac_check($condition,$message) { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
function ac_player($id, $talents=array(), $kind='K', $effect=140)
{
    $p = array('pid'=>$id,'name'=>'角色'.$id,'type'=>0,'teamID'=>'','lvl'=>1,'exp'=>0,'hp'=>400,'mhp'=>400,'sp'=>400,'msp'=>400,
        'att'=>100,'def'=>100,'pls'=>1,'money'=>300,'state'=>0,'wep'=>'试验武器','wepk'=>'W'.$kind,'wepe'=>$effect,'weps'=>20,'wepsk'=>'',
        'weppara'=>array('another'=>array('uid'=>'weapon-'.$id,'wear'=>0,'ammo'=>30,'effects'=>array())),
        'clbpara'=>array('another'=>array('talents'=>$talents,'rep'=>0)),'skillpoint'=>0);
    foreach (array('arb','arh','ara','arf','art') as $slot) { $p[$slot.'s']=0; $p[$slot.'e']=0; $p[$slot.'para']=array(); }
    $p['arbs']=1; $p['arbe']=20;
    return $p;
}
function ac_full($tree)
{
    $out=array(); foreach (another_talent_definitions() as $id=>$def) if ($def['tree']===$tree) $out[$id]=$def['max']; return $out;
}

$defs = another_talent_definitions();
ac_check(count($defs)===41,'完整41项天赋');
foreach (array('S','P','E','C','I','A','L') as $tree) ac_check(another_talent_points(ac_full($tree))===10,'每树9+1：'.$tree);
$error='';
ac_check(another_validate_talents(array('EU'=>1),$error)===false,'终极必须前置9点');
ac_check(another_validate_talents(array('E1'=>6),$error)===false,'拒绝超档位');
ac_check(another_validate_talents(array('E1'=>array(1)),$error)===false,'拒绝数组输入');
ac_check(another_validate_talents(array_merge(ac_full('S'),ac_full('E'),ac_full('C'),ac_full('A')),$error)===false,'拒绝超35点');
ac_check(another_validate_talents(array_merge(ac_full('S'),ac_full('E'),ac_full('A'),array('C1'=>3,'C5'=>2)),$error)!==false,'允许恰好35点');

$p=ac_player(1,array('E1'=>5,'A2'=>3));
ac_check(another_max_hp($p)===600 && another_max_sp($p)===540,'裸上限与天赋上限分开');
$p['clbpara']['another']['talents']=array('C1'=>1);
ac_check(another_add_rep($p,5)===5.25,'小额REP保留精度');
$finder=ac_player(1,array('P3'=>3,'P4'=>2,'AU'=>1)); $hidden=ac_player(2,array('I2'=>2));
ac_check(abs(another_discovery_chance($finder,40,'trap',$hidden)-52.2)<0.000001,'发现加算、隐藏乘算');
ac_check(abs(another_discovery_chance($finder,40,'npc',$hidden)-46.8)<0.000001,'NPC镜像隐藏有效，感官强化不扩展到NPC');

$players=array(1=>ac_player(1,array('C5'=>3)),2=>ac_player(2,array('C5'=>2)),3=>ac_player(3,array('C5'=>1)),4=>ac_player(4));
foreach($players as &$p) $p['teamID']='team'; unset($p);
foreach(array(1=>6,2=>8,3=>10,4=>12) as $id=>$want) ac_check(another_effective_stats($players[$id],$players,100)['attribute_bonus']===$want,'团队来源加算'.$id);
$players[2]['hp']=0;
ac_check(another_effective_stats($players[1],$players,100)['attribute_bonus']===2,'死亡来源移除');
$solo=ac_player(5,array('C5'=>3));
ac_check(another_effective_stats($solo,array(5=>$solo),100)['attribute_bonus']===0,'团队不作用自身');
$solo['clbpara']['another']['talents']['L2']=2;
$solo['clbpara']['another']['ground_place']=1;
ac_check(another_effective_stats($solo,array(5=>$solo),100)['attribute_bonus']===3,'地运读取搜索地点');
$solo['pls']=2;
ac_check(another_effective_stats($solo,array(5=>$solo),100)['attribute_bonus']===0,'离开地点地运失效');

$p=ac_player(1,array('AU'=>1,'A4'=>1)); $p['hp']=59;
ac_check(another_evasion($p,'attack')===100,'低血水猫100%');
ac_check(another_evasion($p,'trap')===50,'猫不影响陷阱');
$p['hp']=60; ac_check(another_evasion($p,'attack')===50,'15%边界不触发猫');
$p['hp']=59;
$a=ac_player(2,array(),'C',130);
ac_check(another_attack_hits($a,$p,another_combat_weapon($a),array('sure_hit_before_evade'=>true))===true,'投必中优先水猫');
ac_check(another_attack_hits($a,$p,another_combat_weapon($a),array('sure_hit_before_evade'=>false))===false,'优先关系可配置');
$a=ac_player(2,array('PU'=>1)); $messages=array();
ac_check(another_mark_player($a,$p,'moon',100,$messages),'月标记有效');
ac_check(another_attack_hits($a,$p,another_combat_weapon($a),array('sure_hit_before_evade'=>true)),'月必中');
$a['weppara']['another']['uid']='another-weapon';
ac_check(!another_attack_hits($a,$p,another_combat_weapon($a),array('sure_hit_before_evade'=>true)),'换同名武器失去月必中');

$messages=array(); $p=ac_player(1,array('EU'=>1));
$before=another_action_guard($p);
another_damage($p,1000,'attack',100,$messages);
another_finish_action($p,$before);
ac_check($p['hp']===1 && $p['clbpara']['another']['earth_left']===3,'触发整次行动不扣保护');
for($i=0;$i<10;$i++) another_damage($p,1000,'attack',100,$messages);
ac_check($p['hp']===1 && $p['clbpara']['another']['earth_left']===3,'重复被打不扣保护');
for($i=0;$i<3;$i++) { $before=another_action_guard($p); another_damage($p,9999,'poison',200,$messages); another_finish_action($p,$before); }
ac_check($p['hp']===1 && $p['clbpara']['another']['earth_left']===0 && $p['clbpara']['another']['earth_cd']===6,'保护3次完整行动后冷却6次');
for($i=0;$i<6;$i++) another_finish_action($p,another_action_guard($p));
another_damage($p,1,'attack',300,$messages);
ac_check($p['hp']===0,'重新就绪但HP1仍不能再触发');
$p=ac_player(1,array('EU'=>1)); another_damage($p,9999,'attack',100,$messages); another_damage($p,9999,'zone',100,$messages);
ac_check($p['hp']===0,'禁区穿过行动保护');

$players=array(1=>ac_player(1,array('L3'=>1)),2=>ac_player(2,array('L3'=>1,'E3'=>1)),3=>ac_player(3));
$players[1]['teamID']=$players[2]['teamID']='team';
another_apply_status($players[1],'poison',array('duration'=>20,'power'=>5,'source'=>3),$players,100,$messages);
ac_check(isset($players[2]['clbpara']['another']['statuses']['poison']) && empty($players[3]['clbpara']['another']['statuses']['poison']),'人运只分享同队');
another_tick_statuses($players[1],105,$messages); another_tick_statuses($players[2],105,$messages);
ac_check($players[1]['hp']===380 && $players[2]['hp']===381,'分享毒用接收者自己的耐受');
unset($players[1]['clbpara']['another']['statuses']['poison']);
ac_check(isset($players[2]['clbpara']['another']['statuses']['poison']),'自用解毒不远程解队友毒');
another_tick_statuses($players[2],120,$messages); $hp=$players[2]['hp']; another_tick_statuses($players[2],125,$messages);
ac_check($players[2]['hp']===$hp,'毒不重复结算过期跳伤');

$a=ac_player(1,array('S1'=>3,'S2'=>1,'S3'=>3,'S4'=>1)); $a['hp']=50;
$d=ac_player(2,array(),'G'); $d['hp']=50;
$players=array(1=>$a,2=>$d);
ac_check(another_attack_damage($a,$d,$players,100,null,true,1)===329,'同组增伤41%再乘日2，末端取整');
ac_check(another_sky_damage(ac_player(1,array('L4'=>1)),95,100)===100 && another_sky_damage(ac_player(1,array('L4'=>1)),120,100)===120,'天运补足不削超额伤害');

// 连续战斗行为：双方独立反击，日未命中仍消耗；踏雪不递归，后台反击也可追加。
$players=array(1=>ac_player(1,array('SU'=>1,'PU'=>1),'K',140),2=>ac_player(2,array('AU'=>1,'A4'=>1),'K',10));
$players[2]['hp']=59; $room=array(); $messages=array();
another_battle($players[1],$players[2],$players,$room,array('sun'=>true),100,$messages);
ac_check($players[1]['weps']===0 && $players[1]['hp']<201,'日即使落空仍消耗武器和半血');

$testcfg['combat']['flurry_chance']=100;
$players=array(1=>ac_player(1,array('A3'=>1),'K',10),2=>ac_player(2,array('A3'=>1),'K',10)); $room=array(); $messages=array();
another_battle($players[1],$players[2],$players,$room,array(),100,$messages);
ac_check($players[1]['weppara']['another']['wear']===4.0 && $players[2]['weppara']['another']['wear']===4.0,'双方各追加一次不递归');
$testcfg['combat']['flurry_chance']=0;

$players=array(1=>ac_player(1,array('AU'=>1,'A4'=>1),'G',1),2=>ac_player(2,array_merge(ac_full('S'),ac_full('P'),ac_full('C')),'K',200));
$players[1]['hp']=59; $players[2]['type']=110; $players[2]['hp']=$players[2]['mhp']=600; $players[2]['clbpara']['another']['npc_role']='mirror';
$room=array(); $messages=array(); another_battle($players[1],$players[2],$players,$room,array(),100,$messages);
ac_check($players[2]['clbpara']['another']['fire']===1 && $players[1]['hp']===0,'镜像主动月火标记，月必中穿过低血水猫');
ac_check($players[2]['weps']===0 && $players[2]['hp']===300,'镜像会使用日并支付半血和武器');
ac_check($room['traces'][0]['kind']==='gunshot' && $room['traces'][1]['kind']==='fight','同场记录不同双方声音');

$players=array(1=>ac_player(1,array('AU'=>1,'A4'=>1),'K',1),2=>ac_player(2,array(),'K',80));
$players[1]['hp']=59; $players[2]['type']=108; $players[2]['clbpara']['another']['npc_role']='poisoner';
$room=array(); $messages=array(); another_battle($players[1],$players[2],$players,$room,array(),100,$messages);
ac_check($players[1]['hp']===59 && isset($players[1]['clbpara']['another']['statuses']['poison']),'毒雾技师普通反击落空仍独立施毒');
another_tick_statuses($players[1],115,$messages);
ac_check($players[1]['hp']===0 && $players[1]['clbpara']['another']['killer']===2,'独立毒伤能杀低血回避并记录来源');

$players=array(1=>ac_player(1,array('L4'=>1),'C',114),2=>ac_player(2,array('EU'=>1),'K',1));
$players[2]['hp']=100; $room=array(); $messages=array();
another_battle($players[1],$players[2],$players,$room,array(),100,$messages);
ac_check($players[2]['hp']===1 && $players[2]['clbpara']['another']['earth_left']===3,'实际战斗中天运补足后判土保护');

$players=array(1=>ac_player(1,array('A1'=>3),'C',130),2=>ac_player(2));
$players[2]['type']=105; $players[2]['hp']=$players[2]['mhp']=180; $players[2]['def']=80; $players[2]['arbs']=0; $players[2]['clbpara']['another']['npc_role']='grunt';
$room=array(); $messages=array(); another_battle($players[1],$players[2],$players,$room,array(),100,$messages);
ac_check($players[2]['hp']===0 && $players[2]['clbpara']['another']['killer']===1,'普通小兵满专精一击，记录击杀归属');
another_combat_rewards($players[1],$players[2],$players,$room,100,$messages);
ac_check($players[1]['money']===340 && $players[1]['clbpara']['another']['rep']===5.0,'小兵奖励');
another_combat_rewards($players[1],$players[2],$players,$room,100,$messages);
ac_check($players[1]['money']===340,'重复死亡不重复奖励');

$players=array(); foreach(range(1,4) as $id) { $players[$id]=ac_player($id,array('C1'=>3,'CU'=>1)); $players[$id]['teamID']='team'; $players[$id]['clbpara']['another']['fire']=5; }
$players[5]=ac_player(5); $players[5]['hp']=0; $room=array(); $messages=array();
another_combat_rewards($players[1],$players[5],$players,$room,100,$messages);
ac_check($players[1]['clbpara']['another']['rep']===253.0,'四火击杀者220+33');
foreach(array(2,3,4) as $id) ac_check($players[$id]['clbpara']['another']['rep']===220.0,'四火每队友220');
ac_check(count($players[2]['clbpara']['another']['messages'])===4,'后台受益者有收益提示');
foreach($messages as $message) $players[1]['clbpara']['another']['messages'][]=array('time'=>100,'text'=>$message);
$own_fire_messages=array_filter($players[1]['clbpara']['another']['messages'],function($entry){return strpos($entry['text'],'使角色1获得55 REP')!==false;});
ac_check(count($own_fire_messages)===4,'击杀者每枚火收益只由结算回传一条，队友直接消息不重复发送本人');

$p=ac_player(1,array('E1'=>5,'A2'=>3)); $p['hp']=600; $p['sp']=500; $p['exp']=59; $messages=array(); srand(1234);
another_gain_exp($p,1,$messages);
ac_check($p['lvl']===2 && $p['mhp']>=408 && $p['mhp']<=410 && $p['msp']>=404 && $p['msp']<=406,'升级保留普通HP/SP随机成长');
ac_check($p['att']>=102 && $p['att']<=104 && $p['def']>=103 && $p['def']<=105,'升级保留普通攻防随机成长');
ac_check($p['hp']===another_max_hp($p) && $p['sp']<=another_max_sp($p) && $p['skillpoint']===0,'升级正确处理天赋上限且不发额外天赋');
unset($testcfg['base_exp']); $baseexp=9;
$p=ac_player(1); $p['exp']=26; another_gain_exp($p,1,$messages);
ac_check($p['lvl']===2 && $p['upexp']===45,'无覆盖时沿用实际gamecfg的baseexp9');
$testcfg['base_exp']=20;

// 每一级、每一档：以原升级平均成长检验基准击数，含专精和斩的两个浮动边界。
$tiers=array(array(150,140,130,20),array(180,170,155,45),array(210,200,180,70));
$deviations=array();
foreach(range(1,255) as $level) foreach($tiers as $tier=>$spec) foreach(array('G','K','C') as $ix=>$kind) foreach(array(false,true) as $specialized) {
    $talents=$specialized ? array(array('P1','S1','A1')[$ix]=>3) : array();
    $a=ac_player(1,$talents,$kind,$spec[$ix]); $d=ac_player(2,array(),$kind,$spec[$ix]);
    foreach(array('a','d') as $which) { ${$which}['lvl']=$level; ${$which}['mhp']=${$which}['hp']=400+9*($level-1); ${$which}['att']=100+3*($level-1); ${$which}['def']=100+4*($level-1); ${$which}['arbe']=$spec[3]; }
    foreach($kind==='K'?array(.95,1.05):array(1) as $variance) {
        $damage=another_attack_damage($a,$d,array(1=>$a,2=>$d),100,null,false,$variance);
        $hits=ceil($d['hp']/$damage);
        if($hits<3 || $hits>4) $deviations[]=array($level,$tier,$kind,$specialized,$variance,$damage,$d['hp'],$hits);
    }
}
ac_check(!$deviations,'等级成长全区间：'.json_encode(array_slice($deviations,0,5)));

restore_error_handler();
echo 'AN:OTHER combat: '.$checks." checks passed; levels 1-255 and three equipment tiers checked.\n";
