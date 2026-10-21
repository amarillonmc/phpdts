<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once __DIR__.'/../include/another.config.php';
require_once __DIR__.'/../include/another.talents.php';
require_once __DIR__.'/../include/another.combat.php';
require_once __DIR__.'/../include/another.items.php';
require_once __DIR__.'/../include/another.core.php';
require_once __DIR__.'/../include/another.actions.php';
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$flow_checks=0;
function flow_check($ok,$message) { global $flow_checks; $flow_checks++; if(!$ok) throw new Exception($message); }
function flow_player($pid) {
    $p=array('pid'=>$pid,'type'=>0,'name'=>'内部账号'.$pid,'teamID'=>$pid,'hp'=>100,'mhp'=>400,'sp'=>400,'msp'=>400,
        'att'=>100,'def'=>100,'lvl'=>1,'exp'=>0,'pls'=>1,'money'=>3000,'bid'=>0,'state'=>0,'cmdnum'=>0,'killnum'=>0,
        'clbpara'=>array('another'=>array('display_name'=>'角色'.$pid,'owner'=>'账号'.$pid,'owner_uid'=>$pid,'talents'=>array(),'rep'=>0)));
    foreach(array(0,1,2,3,4,5,6,'wep','arb','arh','ara','arf','art') as $slot) another_item_set($p,$slot,array());
    return $p;
}
function flow_action(&$room,&$players,$pid,$action,$args,$now) {
    $messages=array();
    return another_action($room,$players,$pid,$action,$args,$now,$messages,$now*1000);
}
$players=array(1=>flow_player(1),2=>flow_player(2));
$room=array('next_id'=>0,'ground'=>array(),'traps'=>array(),'deliveries'=>array(),'stock'=>array(),'closed'=>array(),'news'=>array(),'entry_closed'=>true);
$players[1]['clbpara']['another']['talents']=array('EU'=>1);
$players[1]['clbpara']['another']['earth_left']=3;
$players[1]['clbpara']['another']['earth_generation']=1;
$players[1]['clbpara']['another']['rest']=true;
flow_check(flow_action($room,$players,1,'buy',array('id'=>'heal','channel'=>'local'),100),'real action local purchase succeeds');
flow_check($players[1]['clbpara']['another']['earth_left']===2 && $players[1]['clbpara']['another']['ready_at']===100900,'actor copy retains earth countdown and action cooldown');
flow_check(empty($players[1]['clbpara']['another']['rest']) && $players[1]['cmdnum']===1,'copy does not detach rest/command counters');
$before=json_encode($players);
flow_check(!flow_action($room,$players,1,'buy',array('id'=>'heal','channel'=>'local'),100) && json_encode($players)===$before,'repeated action within cooldown has no mutation');
flow_check(flow_action($room,$players,1,'use',array('slot'=>1),102),'real healing action succeeds');
flow_check($players[1]['hp']===320 && $players[1]['clbpara']['another']['earth_left']===1,'healing progresses one protected action');
// 模拟重新从持久化读取后继续，不依赖上一个请求遗留的PHP引用。
$players=json_decode(json_encode($players),true); $room=json_decode(json_encode($room),true);
flow_check(flow_action($room,$players,1,'buy',array('id'=>'gun','channel'=>'local'),104),'purchase after reload succeeds');
flow_check($players[1]['clbpara']['another']['earth_left']===0 && $players[1]['clbpara']['another']['earth_cd']===6,'third active action starts earth cooldown');
for($i=0;$i<6;$i++) flow_check(flow_action($room,$players,1,'buy',array('id'=>'ammo','channel'=>'local'),106+$i*2),'each valid later command advances cooldown');
flow_check($players[1]['clbpara']['another']['earth_cd']===0 && another_items_count_id($players[1],'ammo')===72,'six real actions rearm earth with exact ammo purchases');

// 工具使用也经过真实动作入口；毒食放地上可被另一角色发现、拾取、食用。
$players=array(1=>flow_player(1),2=>flow_player(2));
another_item_set($players[1],1,another_item_make('heal')); another_item_set($players[1],2,another_item_make('poison'));
flow_check(flow_action($room,$players,1,'poison',array('slot'=>2,'target'=>1),200),'poison action enters through real dispatcher');
$foodslot=another_item_find($players[1],'heal');
flow_check(flow_action($room,$players,1,'drop',array('slot'=>$foodslot),202),'poisoned food can be planted as ground bait');
$gid=array_key_last($room['ground']);
$players[2]['clbpara']['another']['known_ground'][$gid]=true;
flow_check(flow_action($room,$players,2,'pickup',array('id'=>$gid),204),'other player picks up poisoned bait');
$foodslot=another_item_find($players[2],'heal');
flow_check(flow_action($room,$players,2,'use',array('slot'=>$foodslot),206),'poisoned bait consumed through real dispatcher');
flow_check($players[2]['clbpara']['another']['statuses']['poison']['source']===1,'poison source remains setter across drop/pickup');

// 装备交换和升级后的新状态继续留在players原行，另一个角色不被覆盖。
$players=array(1=>flow_player(1),2=>flow_player(2));
another_item_set($players[1],1,another_item_make('gun')); another_item_set($players[1],2,another_item_make('gun_part'));
flow_check(flow_action($room,$players,1,'equip',array('slot'=>1),300),'equip through full action pipeline');
$uid=$players[1]['weppara']['another']['uid'];
$players[1]['clbpara']['another']['moon']=array('target'=>2,'weapon'=>$uid);
flow_check(flow_action($room,$players,1,'upgrade',array('target'=>'wep'),302),'upgrade through full action pipeline');
flow_check($players[1]['wepe']===180 && $players[1]['weppara']['another']['uid']!==$uid && empty($players[1]['clbpara']['another']['moon']),'new weapon and mark clearing remain in authoritative actor row');
flow_check($players[1]['clbpara']['another']['ready_at']===302900 && $players[1]['cmdnum']===2 && $players[1]['clbpara']['another']['rep']===10.0,'post-copy counters and earned REP survive');
flow_check($players[2]['hp']===100 && $players[2]['cmdnum']===0 && $players[2]['money']===3000,'other character remains independent');

restore_error_handler();
echo 'ANOTHER item action flows: '.$flow_checks." checks passed.\n";
