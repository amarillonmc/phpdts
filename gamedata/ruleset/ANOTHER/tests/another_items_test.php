<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once __DIR__.'/../include/another.config.php';
require_once __DIR__.'/../include/another.talents.php';
require_once __DIR__.'/../include/another.combat.php';
require_once __DIR__.'/../include/another.items.php';

set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$checks=0;
function item_check($ok,$message) { global $checks; $checks++; if(!$ok) throw new Exception($message); }
function item_player($pid=1) {
    $p=array('pid'=>$pid,'type'=>0,'name'=>'测试角色'.$pid,'teamID'=>1,'hp'=>100,'mhp'=>400,'sp'=>100,'msp'=>400,
        'att'=>100,'def'=>100,'lvl'=>1,'exp'=>0,'pls'=>1,'money'=>1000,'bid'=>0,'clbpara'=>array('another'=>array('talents'=>array(),'rep'=>0)));
    foreach(array(0,1,2,3,4,5,6,'wep','arb','arh','ara','arf','art') as $slot) another_item_set($p,$slot,array());
    return $p;
}
function item_room() { return array('next_id'=>0,'ground'=>array(),'traps'=>array(),'deliveries'=>array(),'stock'=>array()); }
function item_act(&$players,&$room,$action,$args,$time=100) {
    $messages=array();
    return another_items_action($players[1],$room,$players,$action,$args,$time,$messages);
}
function item_fill(&$player) {
    foreach(array('radio','satellite','disarm_tool','detector','binoculars','gun') as $i=>$id) another_item_set($player,$i+1,another_item_make($id));
}

$room=item_room(); $players=array(1=>item_player(),2=>item_player(2));
$players[1]['clbpara']['another']['talents']=array('E1'=>5,'S5'=>1);
another_item_set($players[1],1,another_item_make('heal',2));
item_check(item_act($players,$room,'use',array('slot'=>1)),'standard food usable');
item_check($players[1]['hp']===413,'600 max hp, quick recovery restores floor(313.5)');
item_check($players[1]['clbpara']['another']['digestion_until']===116,'quick digestion is 16 seconds');
$before=$players[1];
item_check(!item_act($players,$room,'use',array('slot'=>1),110) && $players[1]===$before,'digestion rejects repeat without consuming');
item_check($players[2]['hp']===100 && empty($players[2]['clbpara']['another']['digestion_until']),'other full character has independent recovery state');
item_check(item_act($players,$room,'use',array('slot'=>1),116),'cooldown exact boundary allows recovery');

// 食物处理、查毒、人运传播、各自解毒。
$players=array(1=>item_player(),2=>item_player(2)); $room=item_room();
$players[1]['clbpara']['another']['talents']=array('L3'=>1);
another_item_set($players[1],1,another_item_make('heal'));
another_item_set($players[1],2,another_item_make('food_antidote'));
item_check(item_act($players,$room,'clean_food',array('slot'=>2,'target'=>1)),'cleaning clean food is a valid risky operation');
$foodslot=another_item_find($players[1],'heal');
item_check(!empty(another_item_get($players[1],$foodslot)['para']['another']['poisoned']),'misused food antidote poisons clean food');
another_item_set($players[1],2,another_item_make('poison_test'));
item_check(item_act($players,$room,'test_food',array('slot'=>2,'target'=>$foodslot)),'testing succeeds');
item_check(!empty(another_item_get($players[1],$foodslot)['para']['another']['tested']),'tested flag survives in item data');
item_check(item_act($players,$room,'use',array('slot'=>$foodslot)),'poisoned food consumed');
item_check(isset($players[1]['clbpara']['another']['statuses']['poison'],$players[2]['clbpara']['another']['statuses']['poison']),'human luck shares actual poison status');
another_item_set($players[1],1,another_item_make('antidote'));
item_check(item_act($players,$room,'use',array('slot'=>1)),'self antidote works');
item_check(empty($players[1]['clbpara']['another']['statuses']['poison']) && isset($players[2]['clbpara']['another']['statuses']['poison']),'self antidote does not silently cure team');

// 一份食物变性需要空间，失败时不丢材料、不处理整叠。
$players=array(1=>item_player()); item_fill($players[1]);
another_item_set($players[1],1,another_item_make('heal',2)); another_item_set($players[1],2,another_item_make('poison',2));
$before=$players[1];
item_check(!item_act($players,$room,'poison',array('slot'=>2,'target'=>1)) && $players[1]===$before,'full inventory food split is atomic');

// 购物失败不扣钱、库存；同一次购物只扣一次库存。
$room=item_room(); $players=array(1=>item_player()); item_fill($players[1]); $before=$players[1];
item_check(!item_act($players,$room,'buy',array('id'=>'whetstone','channel'=>'local')) && $players[1]===$before && empty($room['stock']),'full inventory shop atomic');
another_item_set($players[1],6,array());
$players[1]['clbpara']['another']['talents']['C2']=1;
item_check(item_act($players,$room,'buy',array('id'=>'whetstone','channel'=>'local')),'local shop succeeds after clearing space');
item_check($players[1]['money']===964 && $room['stock']['whetstone']===39,'bargain price and finite stock exact');
$players[1]['pls']=2; $before=$players[1];
item_check(!item_act($players,$room,'buy',array('id'=>'ammo','channel'=>'local')) && $players[1]===$before,'forged local shopping away from shop rejected');

// 猎鹰抵达背包满时保留配送，腾出空间后只交付一次。
$room=item_room(); $players=array(1=>item_player()); item_fill($players[1]); $players[1]['pls']=2;
item_check(item_act($players,$room,'buy',array('id'=>'heal','channel'=>'radio')),'radio order accepted');
$did=array_key_first($room['deliveries']); $messages=array();
another_items_tick($room,$players,110,$messages);
item_check(isset($room['deliveries'][$did]) && another_item_find($players[1],'heal')===false,'full bag retains completed falcon order');
another_item_set($players[1],6,array()); another_items_tick($room,$players,111,$messages);
item_check(empty($room['deliveries']) && another_items_count_id($players[1],'heal')===1,'falcon delivered after capacity returns');
another_items_tick($room,$players,120,$messages);
item_check(another_items_count_id($players[1],'heal')===1,'delivery tick cannot duplicate grant');
item_check(!item_act($players,$room,'collect',array('id'=>$did),120),'duplicate collection rejected');

// REP气球，业运扣费前判定，落地可争夺而不能重复领。
$room=item_room(); $players=array(1=>item_player(),2=>item_player(2));
another_item_set($players[1],1,another_item_make('satellite'));
$players[1]['clbpara']['another']['rep']=40; $players[1]['clbpara']['another']['talents']['L5']=1;
item_check(item_act($players,$room,'buy',array('id'=>'emergency_box','channel'=>'rep')),'REP purchase works');
$did=array_key_first($room['deliveries']);
item_check($room['deliveries'][$did]['hide']===0.95 && $players[1]['clbpara']['another']['rep']===10.0,'prepayment low REP and exact balance');
$messages=array(); another_items_tick($room,$players,120,$messages);
item_check(empty($room['deliveries']) && count($room['ground'])===1,'balloon lands once');
$gid=array_key_first($room['ground']);
item_check(item_act($players,$room,'pickup',array('id'=>$gid),120),'owner can pick landed balloon');
item_check(!item_act($players,$room,'pickup',array('id'=>$gid),120),'same ground item cannot be reclaimed');

// 补弹/维修不发REP；枪升级更换身份，解除月；普通强化保留身份。
$room=item_room(); $players=array(1=>item_player());
$gun=another_item_make('gun'); $gun['para']['another']['wear']=35;
another_item_set($players[1],'wep',$gun); another_item_set($players[1],1,another_item_make('ammo',12));
another_item_set($players[1],2,another_item_make('repair')); another_item_set($players[1],3,another_item_make('gun_part'));
$players[1]['clbpara']['another']['moon']=array('target'=>2,'weapon'=>$gun['para']['another']['uid']);
item_check(item_act($players,$room,'reload',array('target'=>'wep')),'reload accepted');
item_check($players[1]['weppara']['another']['ammo']===24 && $players[1]['clbpara']['another']['rep']===0,'ammo consumed without REP');
item_check(item_act($players,$room,'repair',array('target'=>'wep')),'repair accepted');
item_check($players[1]['weppara']['another']['wear']===15 && isset($players[1]['clbpara']['another']['moon']),'repair preserves moon and removes 20 wear');
item_check(item_act($players,$room,'upgrade',array('target'=>'wep')),'gun assembles upgrade without failure roll');
item_check($players[1]['wepe']===180 && $players[1]['weppara']['another']['uid']!==$gun['para']['another']['uid'] && empty($players[1]['clbpara']['another']['moon']),'gun upgrade creates new weapon identity');
item_check($players[1]['clbpara']['another']['rep']===10.0,'gun upgrade pays REP once');
$before=$players[1]; item_check(!item_act($players,$room,'upgrade',array('target'=>'wep')) && $players[1]===$before,'repeat without material rejected');
another_item_set($players[1],'wep',another_item_make('throw'));
another_item_set($players[1],1,another_item_make('whetstone'));
$before=$players[1]; item_check(!item_act($players,$room,'sharpen',array('target'=>'wep')) && $players[1]===$before,'throw cannot be strengthened');

// 合成只能消耗已有零件，成品无空间时回退整项操作。
$players=array(1=>item_player()); another_item_set($players[1],1,another_item_make('scrap',4));
item_check(item_act($players,$room,'craft',array('recipe'=>'slash')),'equipment crafting succeeds');
item_check(another_items_count_id($players[1],'scrap')===0 && another_items_count_id($players[1],'slash')===1 && $players[1]['clbpara']['another']['rep']===10.0,'craft consumes exact material and pays gear REP');
item_check(!item_act($players,$room,'craft',array('recipe'=>'slash')),'duplicate craft without material rejected');

// 金陷阱跳过回避，但土仍免死；报告只给设陷者，不广播给第三人。
$room=item_room(); $players=array(1=>item_player(),2=>item_player(2),3=>item_player(3));
$players[1]['clbpara']['another']['talents']=array('IU'=>1);
another_item_set($players[1],1,another_item_make('trap_combo'));
item_check(item_act($players,$room,'plant',array('slot'=>1)),'trap plants');
$tid=array_key_first($room['traps']);
$players[2]['hp']=2; $players[2]['clbpara']['another']['talents']=array('EU'=>1,'AU'=>1,'A4'=>1);
$messages=array(); another_items_trigger_trap($players[2],$room,$players,$tid,101,$messages);
item_check($players[2]['hp']===1 && $players[2]['clbpara']['another']['earth_left']===3,'gold trigger bypasses water but earth protects');
item_check(isset($players[2]['clbpara']['another']['statuses']['slow']),'nondeath trap effect still applies under earth');
item_check(!empty($players[1]['clbpara']['another']['messages']) && empty($players[3]['clbpara']['another']['messages']),'player trap report remains private');

// 未发现的对象不能凭ID直接取走或拆解。
$room=item_room(); $players=array(1=>item_player()); $gid=another_items_ground($room,another_item_make('heal'),1);
item_check(!item_act($players,$room,'pickup',array('id'=>$gid)) && isset($room['ground'][$gid]),'unseen item ID is not pickup permission');
$room['traps'][99]=array('id'=>99,'pls'=>1,'owner'=>0,'item'=>another_item_make('trap_hazard'),'gold'=>true);
item_check(!item_act($players,$room,'disarm',array('id'=>99)) && isset($room['traps'][99]),'unseen trap ID is not disarm permission');
item_check(!item_act($players,$room,'buy',array('id'=>array('heal'))),'malformed scalar rejected');
item_check(!item_act($players,$room,'drop',array('slot'=>'missing')),'invalid drop slot returns a normal failure without warnings');

// 满回复使用角色有效上限，包含天赋放大的生命和体力，不产生上限外溢出。
$room=item_room(); $players=array(1=>item_player());
$players[1]['clbpara']['another']['talents']=array('E1'=>5,'A2'=>3);
$players[1]['hp']=1; $players[1]['sp']=1;
another_item_set($players[1],1,another_item_make('full_heal'));
item_check(item_act($players,$room,'use',array('slot'=>1)),'full recovery usable');
item_check($players[1]['hp']===600 && $players[1]['sp']===540,'full recovery reaches enlarged caps exactly');

// 多件远购不加队列惩罚；每件持久化记录大小固定且同时抵达。
$room=item_room(); $players=array(1=>item_player()); another_item_set($players[1],1,another_item_make('radio'));
item_check(item_act($players,$room,'buy',array('id'=>'gun','channel'=>'radio','qty'=>3)),'bulk remote equipment order accepted');
item_check(count($room['deliveries'])===3 && $players[1]['money']===460,'bulk equipment split records, one combined price');
$due=array(); $uids=array();
foreach($room['deliveries'] as $d) { $due[]=$d['due']; item_check(count($d['items'])===1,'one equipment per persistent delivery record'); $uids[]=$d['items'][0]['para']['another']['uid']; }
item_check(count(array_unique($due))===1 && count(array_unique($uids))===3,'same arrival time and unique weapon identities');
$messages=array(); another_items_tick($room,$players,110,$messages);
item_check(empty($room['deliveries']) && another_items_count_id($players[1],'gun')===3,'all remote equipment delivered without artificial queue');

// 气球争夺消耗真实弹药；猎鹰即使猜到ID也不可击落。
$room=item_room(); $players=array(1=>item_player());
another_item_set($players[1],'wep',another_item_make('gun'));
$room['deliveries'][10]=array('id'=>10,'kind'=>'falcon','owner'=>2,'pls'=>1,'due'=>120,'items'=>array(another_item_make('heal')),'hide'=>1);
$players[1]['clbpara']['another']['known_deliveries'][10]=true;
$before=$players[1]; item_check(!item_act($players,$room,'shoot_balloon',array('id'=>10)) && $players[1]===$before,'falcon cannot be attacked');
$room['deliveries'][11]=array('id'=>11,'kind'=>'balloon','owner'=>2,'pls'=>1,'due'=>120,'items'=>array(another_item_make('heal')),'hide'=>1);
$players[1]['clbpara']['another']['known_deliveries'][11]=true;
item_check(item_act($players,$room,'shoot_balloon',array('id'=>11)),'discovered balloon can be shot down');
item_check($players[1]['weppara']['another']['ammo']===11 && $players[1]['weppara']['another']['wear']===2.0 && !isset($room['deliveries'][11]),'balloon shot pays combat weapon cost and removes exactly one shipment');
item_check(!item_act($players,$room,'shoot_balloon',array('id'=>11)),'same balloon cannot be shot twice');

// 拆除成功从已发现陷阱回收，失败产生伤害/情报；两种结果均只结算一次。
$room=item_room(); $players=array(1=>item_player());
another_item_set($players[1],1,another_item_make('disarm_tool'));
$room['traps'][12]=array('id'=>12,'pls'=>1,'owner'=>0,'item'=>another_item_make('trap_alarm'));
$players[1]['clbpara']['another']['known_traps'][12]=true;
$success_seed=null; $failure_seed=null;
for($seed=0;$seed<100;$seed++) { mt_srand($seed); $roll=mt_rand(1,100); if($roll<=70 && $success_seed===null) $success_seed=$seed; if($roll>70 && $failure_seed===null) $failure_seed=$seed; }
mt_srand($success_seed);
item_check(item_act($players,$room,'disarm',array('id'=>12)) && !isset($room['traps'][12]) && another_items_count_id($players[1],'trap_alarm')===1,'tool recovers successfully disarmed trap');
item_check(!item_act($players,$room,'disarm',array('id'=>12)),'cannot recover same trap twice');
$room['traps'][13]=array('id'=>13,'pls'=>1,'owner'=>0,'item'=>another_item_make('trap_alarm'));
$players[1]['clbpara']['another']['known_traps'][13]=true; mt_srand($failure_seed);
item_check(item_act($players,$room,'disarm',array('id'=>13)) && $players[1]['hp']===60 && !isset($room['traps'][13]),'failed dismantling damages and consumes the triggered device');
item_check(!empty($players[1]['clbpara']['another']['messages']),'failed wild dismantle broadcasts location');

// 地图环境、发现天赋及隐藏应各作用一次。
$p=item_player(); $p['clbpara']['another']['talents']=array('AU'=>1,'P3'=>3);
item_check(abs(another_items_find_rate($p,40,'trap',0.9,0.8)-41.76)<0.00001,'discovery: relative talents, concealment and environment multiply once');

// 未检验补给说明不泄露隐藏毒性；已检验说明与库存元数据保持一致。
$food=another_item_make('heal'); $food['para']['another']['poisoned']=true;
item_check(strpos(another_item_description($food),'检出毒素')===false,'unexamined item description does not reveal poison');
$food['para']['another']['tested']=true;
item_check(strpos(another_item_description($food),'检出毒素')!==false,'tested item reveals its result');
$roundtrip=json_decode(json_encode($food),true);
another_item_set($p,1,$roundtrip);
item_check(another_item_get($p,1)===$food,'complete native item fields survive a persistence-style round trip');

// 射系只能组装升级；升级后的属性参与真实战斗，物品说明同步展示。
$players=array(1=>item_player(),2=>item_player(2)); $room=item_room();
another_item_set($players[1],'wep',another_item_make('gun'));
another_item_set($players[1],1,another_item_make('gun_part',2));
another_item_set($players[2],'arb',another_item_make('armor_high'));
$players[2]['hp']=400;
item_check(item_act($players,$room,'upgrade',array('target'=>'wep')),'first gun assembly succeeds');
$w=another_combat_weapon($players[1]); $plain=$w; $plain['effects']=array();
item_check($w['effects']['pierce']===10 && another_attack_damage($players[1],$players[2],$players,100,$w)>another_attack_damage($players[1],$players[2],$players,100,$plain),'mid assembly pierce increases actual damage against armor');
item_check(item_act($players,$room,'upgrade',array('target'=>'wep')),'second gun assembly succeeds');
$w=another_combat_weapon($players[1]);
item_check($w['effects']===array('pierce'=>20,'burn'=>10),'high assembly gains configured penetration and burn attributes');
$description=another_item_description(another_item_get($players[1],'wep'));
item_check(strpos($description,'防具穿透20%')!==false && strpos($description,'灼伤触发率10%')!==false,'upgraded gun attributes are visible with unambiguous trigger chance');
$players[1]['clbpara']['another']['moon']=array('target'=>2,'weapon'=>$w['uid']);
for($seed=0;$seed<1000;$seed++) { mt_srand($seed); if(mt_rand(1,10000)<=1000) break; }
mt_srand($seed); $messages=array();
another_strike($players[1],$players[2],$players,$room,false,100,$messages,array('sure_hit_before_evade'=>true,'sky_before_earth'=>true));
item_check(isset($players[2]['clbpara']['another']['statuses']['burn']) && $players[2]['clbpara']['another']['statuses']['burn']['source']===1,'upgraded gun burns a struck target through the real combat path');

restore_error_handler();
echo 'ANOTHER item tests: '.$checks." checks passed.\n";
