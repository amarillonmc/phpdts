<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('CURSCRIPT')) define('CURSCRIPT','game');
function get_itmsk_array($v) { return str_split($v); }
// 独立脚本只跳过模板渲染；物品保存、合并与任务处理仍调用正式函数。
function template($name) { return GAME_ROOT.'gamedata/ruleset/ELEVATOR/cache/mapitem_1.php'; }
require_once GAME_ROOT.'include/game/revclubskills.func.php';
require_once GAME_ROOT.'include/game/quest.func.php';
require_once GAME_ROOT.'include/game/item.ammo.php';

function ex_floor($floor=1,$tpls=array(201,401)) {
    $data=array('floor'=>$floor,'tpls'=>$tpls,'objects'=>array(array('id'=>206,'index'=>1)),'objects_state'=>array(),'armory_word'=>'light','bias'=>0);
    elevator_quest_board($data); elevator_floor_save($floor,$data); return $data;
}
et_room(); $e=&$gamevars['elevator']; $p=et_player(); $p['pls']=elevator_pls(1,1); ex_floor();
elevator_interact($e,$p,206,'puzzle',elevator_cipher('light'),1000);
et_assert(!empty(elevator_floor_read(1)['objects_state'][206]['open']),'armory puzzle saved shared open');
$other=et_player(2); $other['pls']=$p['pls'];
et_reject(function()use(&$e,&$other){elevator_interact($e,$other,206,'pay','',1001);},'second passenger cannot loot same armory');
et_assert($other['money']===500,'shared armory refusal does not charge');
ex_floor(); $p=et_player(); $p['pls']=elevator_pls(1,1);
for ($i=0;$i<3;$i++) et_reject(function()use(&$e,&$p){elevator_interact($e,$p,206,'puzzle','wrong',1001);},'wrong password persists');
et_assert(elevator_floor_read(1)['objects_state'][206]['locked'],'three errors permanently lock all methods');
et_reject(function()use(&$e,&$p){elevator_interact($e,$p,206,'pay','',1001);},'cash cannot bypass lock');
foreach (array('cash','parts','key') as $method) {
    ex_floor(); $p=et_player(); $p['pls']=elevator_pls(1,1);
    if ($method==='parts') elevator_set_slot($p,1,array('itm'=>'无线电零件','itmk'=>'X','itme'=>1,'itms'=>1));
    if ($method==='key') elevator_set_slot($p,1,elevator_keycard());
    elevator_interact($e,$p,206,$method==='key'?'key':'pay','',1001);
    et_assert($p['money']===($method==='cash'?380:500),'armory pays selected cost '.$method);
    et_assert(!empty(elevator_floor_read(1)['objects_state'][206]['open']),'armory method opens '.$method);
}
ex_floor(); $p=et_player(); $p['pls']=elevator_pls(1,1); $p['money']=0;
et_reject(function()use(&$e,&$p){elevator_interact($e,$p,206,'pay','',1001);},'insufficient cash');
et_reject(function()use(&$e,&$p){elevator_interact($e,$p,206,'key','',1001);},'missing key');
for ($i=0;$i<60;$i++) { $loot=elevator_armory_loot(1); et_assert($loot[0]['itmk']==='GB' && $loot[0]['itms']>=20 && $loot[0]['itms']<=40,'armory ammo range'); et_assert($loot[1]['itme']>=30 && $loot[1]['itme']<=80,'armory weapon band'); }
foreach (range(1,8) as $pool) foreach (range(0,2) as $tier) {
    $items=elevator_pool_loot($pool,$tier,10); $limits=elevator_loot_config($pool,$tier)['count'];
    et_assert(count($items)>=$limits[0] && count($items)<=$limits[1],'shared loot tier count');
    foreach ($items as $item) { $id=$item['itmpara']['elevator_item']??0; et_assert(!$id || !elevator_catalog()[$id]['unique'],'loot does not duplicate unique story items'); }
}
foreach (array_keys(elevator_elite_rules()) as $rule) {
    $npc=elevator_floor_npc(-10,4,true,$rule); et_assert($npc['clbpara']['elevator_npc']['rule']===$rule && strpos($npc['name'],'旧红杀残部小队长')===0,'separate elite presets');
}

// Curse advances are per passenger, catch up on later rides and never reroll offers.
et_room(); $e=&$gamevars['elevator']; $e['phase']='endless'; $e['endless_dir']=1; $e['floor']=10;
$p=et_player(); $p['clbpara']['elevator']['in']=true; $p['clbpara']['elevator']['endless']=true; $p['pls']=9000;
$q=et_player(2); $q['clbpara']['elevator']['endless']=true; $players=array(1=>$p,2=>$q);
elevator_endless_step($e,$players,1000); $p=&$players[1];
et_assert(array_sum($p['clbpara']['elevator']['curses'])===1 && empty($players[2]['clbpara']['elevator']['curses']),'only cabin passenger receives curse');
$offer=$p['clbpara']['elevator']['endless_step']; elevator_endless_step($e,$players,1001);
et_assert($offer===$p['clbpara']['elevator']['endless_step'],'endless offer frozen');
et_reject(function()use(&$p,$e){elevator_choose_endless_skill($p,0,'old',$e);},'old skill token');
et_reject(function()use(&$p,$e,$offer){elevator_choose_endless_skill($p,99,$offer['step_id'],$e);},'bad choice');
$e['floor']=16; elevator_endless_step($e,$players,1002);
et_assert($p['clbpara']['elevator']['curse_depth']===3 && $offer===$p['clbpara']['elevator']['endless_step'],'skipped depths retain current offer and queue awards');
elevator_choose_endless_skill($p,0,$offer['step_id'],$e);
et_assert($p['clbpara']['elevator']['curse_claimed']===1 && $p['clbpara']['elevator']['endless_step']['step_id']!==$offer['step_id'],'choice grants once and reveals queued reward');
et_reject(function()use(&$p,$e,$offer){elevator_choose_endless_skill($p,0,$offer['step_id'],$e);},'replay choice');
$players[2]['clbpara']['elevator']['in']=true; elevator_endless_step($e,$players,1003);
et_assert(array_sum($players[2]['clbpara']['elevator']['curses'])===3,'late boarding catches up personal depth');
$p['clbpara']['elevator']['curses']=array('903'=>3,'904'=>3,'906'=>2,'erosion'=>0); $e['floor']=19; elevator_endless_step($e,$players,1004);
et_assert($p['clbpara']['elevator']['curses']['erosion']===1,'all max adds endless erosion');
$a=et_player(3); $b=elevator_floor_npc(1,2); $b['pid']=20; $a['pls']=$b['pls']=elevator_pls(1,1); $a['final_damage']=20;
$b['clbpara']['elevator_npc']['curses']=array('904'=>3,'906'=>2); $b['clbpara']['skill']=array(); $basehp=$b['mhp'];
et_assert(elevator_final_damage($a,$b,100)===135,'vulnerability level3 is35percent'); elevator_curse_hurt($a,$b);
et_assert($b['mhp']===$basehp-10 && strpos($b['inf'],'u')!==false,'bone reduces persistent maximum and applies abnormal');
player_save($b); et_assert(fetch_playerdata_by_pid(20)['mhp']===$basehp-10,'bone maximum survives reload');
elevator_bone_tick($b,1000,1009,true); et_assert($b['clbpara']['elevator_bone_until']===1019,'cabin pauses abnormal duration');
elevator_bone_tick($b,1009,1019,false); et_assert(strpos($b['inf'],'u')===false,'bone abnormal expires');
$a['clbpara']['elevator']['curses']=array('903'=>2); et_assert(elevator_find_rate($a,50)===40.0,'misfortune item discover multiplier');

// Real native ammo handler, poison recovery, skill lifetime, inventory and replay.
et_room(); $e=&$gamevars['elevator']; $p=et_player();
et_assert(count(elevator_skill_definitions())===30 && count(elevator_round_skill_pool())===18,'configurable30skills round subset');
$pool=elevator_round_skill_pool(); et_assert($pool===elevator_round_skill_pool(),'round pool stable');
foreach (array(723,458,710,430,755,455,570,513) as $id) elevator_grant_skill($p,'el_'.$id);
$p['wepk']='WG'; $p['wepsk']=''; $p['weps']='∞'; $p['wep']='手枪'; elevator_set_slot($p,1,elevator_item(9001,$e['round']));
item_ammo_bullets(1,$p); et_assert($p['weps']===20,'reload consumes actual ammo');
elevator_set_slot($p,1,elevator_item(9001,$e['round'])); item_ammo_bullets(1,$p); et_assert($p['weps']===24 && $p['itms1']===16,'loading doubles native magazine');
$p['weps']='∞'; $enemy=elevator_floor_npc(1,2); ruleset_battle_finish_hook($p,$enemy); et_assert($p['weps']===16 && !$p['itms1'],'after battle auto reload');
$p['weps']=23; elevator_set_slot($p,1,elevator_item(9001,$e['round'])); ruleset_battle_finish_hook($p,$enemy); et_assert($p['weps']===24 && $p['itms1']===19,'after battle tops up partially used magazine');
elevator_set_slot($p,1,array('itm'=>'废品','itmk'=>'X','itme'=>30,'itms'=>2,'itmpara'=>array('discard'=>true)));
elevator_skill_action($p,458,array('slot'=>1,'token'=>elevator_slot_token($p,1)),$e,1000); et_assert($p['itmk1']==='PB2' && !elevator_slot($p,1)['itmpara'],'chicken converts all item metadata safely');
$p['hp']=300; elevator_skill_itemuse($p,1,elevator_slot($p,1)); et_assert($p['hp']===330 && $p['itmk1']==='PB2' && $p['itms1']===1,'poison food normal heal for owner');
$p['wp']=1000; elevator_set_slot($p,2,array('itm'=>'手册','itmk'=>'VP','itme'=>60,'itms'=>1)); elevator_skill_itemuse($p,2,elevator_slot($p,2)); et_assert($p['wp']===1060,'uncapped skillbook');
$saved=elevator_slot($p,1); elevator_skill_action($p,430,array('choice'=>'store','slot'=>1,'token'=>elevator_slot_token($p,1)),$e,1000);
elevator_skill_action($p,430,array('choice'=>'take','slot'=>0,'token'=>hash('sha256',json_encode($saved,JSON_UNESCAPED_UNICODE))),$e,1000); et_assert(elevator_slot($p,1)===$saved,'portable bag keeps all fields');
et_assert(elevator_skill_revive($p,1000) && elevator_skill_revive($p,1000) && !elevator_skill_revive($p,1000),'extra revival limited twice');
elevator_grant_skill($p,'el_755'); et_assert(!elevator_skill_revive($p,1000) && $p['clbpara']['elevator']['revive']===1,'duplicate skill does not refill and C4 untouched');
elevator_skill_action($p,455,array(),$e,1000); et_reject(function()use(&$p,$e){elevator_skill_action($p,455,array(),$e,1001);},'invincibility once');
$enemy['clbpara']['skill']=array(); et_assert(elevator_final_damage($enemy,$p,100)===0,'invincibility actual damage');
$now=1011; et_assert(elevator_final_damage($enemy,$p,100)===100,'invincibility expiry'); $now=1000;

// Same floor board, independent participants, real quest helpers and rewards.
et_room(); $e=&$gamevars['elevator']; $data=ex_floor(1,array(101,201,301,401,501,601,701,801));
$p=et_player(); $q=et_player(2); player_save($p); player_save($q);
elevator_quest_action($p,'EL1_lost','accept','',$e,1000); elevator_quest_action($q,'EL1_lost','accept','',$e,1000);
foreach (array(&$p,&$q) as &$person) {
    elevator_set_slot($person,1,array('itm'=>'过期学生卡','itmk'=>'X','itme'=>1,'itms'=>2));
    elevator_quest_action($person,'EL1_lost','submit','',$e,1000); et_assert(!empty($person['clbpara']['quest']['active']['EL1_lost']['ready_to_claim']),'individual submit ready');
    $money=$person['money']; elevator_quest_action($person,'EL1_lost','claim','',$e,1000); et_assert($person['money']===$money+90,'individual pooled reward');
    et_reject(function()use(&$person,$e){elevator_quest_action($person,'EL1_lost','claim','',$e,1001);},'claim replay rejected');
}
unset($person);
elevator_quest_action($p,'EL1_purify','accept','',$e,1000); elevator_quest_action($q,'EL1_purify','accept','',$e,1000);
$p['pls']=$q['pls']=elevator_pls(1,2); player_save($p); player_save($q);
elevator_quest_action($p,'EL1_purify','contact','',$e,1000); $id=elevator_floor_read(1)['quests']['EL1_purify']['npc'];
et_reject(function()use(&$q,$e){elevator_quest_action($q,'EL1_purify','contact','',$e,1000);},'shared NPC excludes other owner');
et_assert(!elevator_can_fight($q,fetch_playerdata_by_pid($id)),'other passenger cannot attack bound NPC');
et_reject(function()use(&$p,$e){elevator_quest_action($p,'EL1_purify','use','',$e,1000);},'purification requires low hp');
$npc=fetch_playerdata_by_pid($id); $npc['hp']=1; player_save($npc);
elevator_quest_action($p,'EL1_purify','use','',$e,1000);
et_assert(elevator_floor_read(1)['quests']['EL1_recycle']['voided'],'purification voids conflict for whole floor');
elevator_quest_action($q,'EL1_purify','contact','',$e,1000);
et_assert(elevator_floor_read(1)['quests']['EL1_purify']['npc']===$id,'second owner reuses same NPC instance');
elevator_quest_action($p,'EL1_feed','accept','',$e,1000); elevator_quest_event($p,'use',array('itm'=>'猫粮罐头','itmk'=>'HH','itms'=>1)); elevator_quest_event($p,'use',array('itm'=>'自动贩卖机饮料','itmk'=>'HS','itms'=>1)); et_assert($p['clbpara']['quest']['active']['EL1_feed']['ready_to_claim'],'use counter');
elevator_quest_action($p,'EL1_cipher','accept','',$e,1000); et_reject(function()use(&$p,$e){elevator_quest_action($p,'EL1_cipher','answer','wrong',$e,1000);},'puzzle failure'); elevator_quest_action($p,'EL1_cipher','answer',elevator_cipher('life game'),$e,1000); et_assert($p['clbpara']['quest']['active']['EL1_cipher']['ready_to_claim'],'puzzle complete');
elevator_quest_action($p,'EL1_survey','accept','',$e,1000); elevator_quest_event($p,'explore'); $p['pls']=elevator_pls(1,3); elevator_quest_event($p,'explore'); et_assert($p['clbpara']['quest']['active']['EL1_survey']['ready_to_claim'],'distinct point survey');
elevator_quest_action($p,'EL1_soldiers','accept','',$e,1000);
for ($i=0;$i<2;$i++) { $victim=elevator_floor_npc(1,4); $victim['pid']=100+$i; $victim['hp']=0; quest_handle_npc_death($p,$victim); quest_handle_npc_death($p,$victim); }
et_assert($p['clbpara']['quest']['active']['EL1_soldiers']['progress']===2,'generic kill count and duplicate death guard');
et_reject(function()use(&$q,$e){elevator_quest_action($q,'EL1_escort','accept','',$e,1000);},'conflict cannot be accepted');
et_assert(strpos(elevator_quest_panel($p),'data-el-command')!==false,'QUEST board has actionable native submissions');

// 其余任务生命周期：拒绝、安抚、收集、应援、护送、死亡与楼层回收。
et_room(); $e=&$gamevars['elevator']; ex_floor(1,array(101,201,301,401,501,601,701,801)); $p=et_player(); player_save($p);
elevator_quest_action($p,'EL1_books','reject','',$e,1000);
et_reject(function()use(&$p,$e){elevator_quest_action($p,'EL1_books','accept','',$e,1000);},'rejected task has exploration cooldown');
$p['pls']=elevator_pls(1,3); for ($i=0;$i<3;$i++) ruleset_move_search_success_hook($p,'search');
elevator_quest_action($p,'EL1_books','accept','',$e,1000); et_assert(isset($p['clbpara']['quest']['active']['EL1_books']),'cooldown ends after effective searches');
elevator_quest_action($p,'EL1_comfort','accept','',$e,1000); elevator_quest_action($p,'EL1_comfort','contact','',$e,1000);
et_assert($p['clbpara']['quest']['active']['EL1_comfort']['ready_to_claim'],'comfort consumes personal flower');
elevator_quest_action($p,'EL1_collect','accept','',$e,1000); $p['pls']=elevator_pls(1,7);
elevator_quest_action($p,'EL1_collect','contact','',$e,1000);
et_reject(function()use(&$p,$e){elevator_quest_action($p,'EL1_collect','contact','',$e,1000);},'collection repeated without search rejected');
for ($i=0;$i<2;$i++) { ruleset_move_search_success_hook($p,'search'); elevator_quest_action($p,'EL1_collect','contact','',$e,1000); }
et_assert($p['clbpara']['quest']['active']['EL1_collect']['ready_to_claim'],'three interactions finish collection');
elevator_quest_action($p,'EL1_cheer','accept','',$e,1000);
// 战斗界面入口在 HTTP 联调覆盖，此处准备同样的绑定状态验证结算。
$npc=elevator_floor_npc(1,7); $npc['pid']=200; $npc['pls']=$p['pls']; $npc['clbpara']['elevator_npc']['quest']='EL1_cheer'; $npc['clbpara']['linked_player_id']=$p['pid']; player_save($npc);
$p['clbpara']['quest']['active']['EL1_cheer']['linked_npc_id']=200; $data=elevator_floor_read(1); $data['quests']['EL1_cheer']['npc']=200; $data['quests']['EL1_cheer']['owner']=$p['pid']; elevator_floor_save(1,$data);
for ($i=0;$i<3;$i++) elevator_quest_battle($p,$npc);
et_assert($p['clbpara']['quest']['active']['EL1_cheer']['ready_to_claim'] && !$p['bid'],'cheer ends after three rounds');
elevator_quest_action($p,'EL1_escort','accept','',$e,1000); $p['pls']=elevator_pls(1,4); elevator_quest_action($p,'EL1_escort','contact','',$e,1000);
$e['stops']+=2; elevator_quest_sync($p,$e); et_assert($p['clbpara']['quest']['active']['EL1_escort']['ready_to_claim'],'escort survives two stops');
et_assert(elevator_floor_read(1)['quests']['EL1_soldiers']['voided'],'escort wins shared conflict');
elevator_quest_action($p,'EL1_prospector','accept','',$e,1000); $p['pls']=elevator_pls(1,8); elevator_quest_action($p,'EL1_prospector','contact','',$e,1000);
$id=$p['clbpara']['quest']['active']['EL1_prospector']['linked_npc_id']; $npc=fetch_playerdata_by_pid($id); $npc['hp']=0; player_save($npc);
elevator_quest_sync($p,$e); et_assert(isset($p['clbpara']['quest']['failed']['EL1_prospector']),'dead escort fails');
elevator_quest_action($p,'EL1_purify','accept','',$e,1000); $p['pls']=elevator_pls(1,2); elevator_quest_action($p,'EL1_purify','contact','',$e,1000); player_save($p);
$npc=fetch_playerdata_by_pid($p['clbpara']['quest']['active']['EL1_purify']['linked_npc_id']); $npc['hp']=0; $other=et_player(2);
elevator_quest_npc_death($other,$npc); et_assert(isset(fetch_playerdata_by_pid(1)['clbpara']['quest']['failed']['EL1_purify']),'other killer fails owner through death hook');
ex_floor(10,array(101)); $p=et_player(1,10); elevator_quest_action($p,'EL10_lost','accept','',$e,1000);
unset($db->floors[10]); elevator_quest_sync($p,$e); et_assert(empty($p['clbpara']['quest']['active']['EL10_lost']) && empty($p['clbpara']['quest']['failed']['EL10_lost']),'reclaimed floor removes task history');

// 伤害类、装备词条和 NPC 专属能力使用实际函数验证。
et_room(); $e=&$gamevars['elevator']; $a=et_player(); $b=elevator_floor_npc(1,2); $b['pid']=20; $b['clbpara']['skill']=array();
foreach (array(515=>115,449=>108,456=>140,558=>130,471=>125) as $id=>$expected) {
    $a['clbpara']['skill']=array('el_'.$id); $a['wep_kind']='F'; $b['mhp']=800;
    et_assert(elevator_final_damage($a,$b,100)===$expected,'damage skill '.$id);
}
$a['clbpara']['skill']=array('el_456'); $a['is_counter']=1; et_assert(elevator_final_damage($a,$b,100)===100,'assault excludes counter'); unset($a['is_counter']);
$a['clbpara']['skill']=array('el_570'); $a['rage']=100; elevator_skill_action($a,570,array(),$e,1000);
et_assert(elevator_final_damage($a,$b,0)===0 && !empty($a['clbpara']['skillpara']['el_570']['burst']),'miss retains burst');
et_assert(elevator_final_damage($a,$b,100)===200 && elevator_final_damage($a,$b,100)===100 && $a['rage']===50,'burst spends rage once and consumes on hit');
$a['clbpara']['skill']=array(); $b['clbpara']['skill']=array('el_513'); $a['pls']=$b['pls']=elevator_pls(1,1); $b['mhp']=400;
ruleset_combat_allowed_hook($a,$b); et_assert(elevator_final_damage($a,$b,30)===0 && elevator_final_damage($a,$b,30)===20,'shield absorbs only remaining barrier');
$b['clbpara']['skill']=array('el_401'); $b['lvl']=20; et_assert(abs(elevator_skill_physical($a,$b)-.7)<.00001,'hardening level tier');
$b['clbpara']['skill']=array('el_760','el_464'); $b['wg']=1000; $a['final_damage']=10; elevator_hurt_events($a,$b,true);
$b['is_counter']=1; et_assert(abs(elevator_skill_physical($b,$a)-1.2)<.00001 && $b['wg']===1001,'hurt skill growth and counter stack');
$a['clbpara']['skill']=array('el_761','el_738'); $a['rage']=0; $a['clbpara']['skillpara']['el_738']['gear']=1;
et_assert(elevator_sp_cost($a,30)===14,'yin and low gear exploration cost'); $a['rage']=51; $b['clbpara']['skill']=array(); et_assert(elevator_final_damage($a,$b,100)===200 && elevator_final_damage($b,$a,100)===200,'yang doubles dealt and received');
$a['clbpara']['skill']=array('el_443'); $b['type']=96; $b['clbpara']['elevator_npc']['duel']=array('owner'=>1,'until'=>1030);
$other=et_player(2); $other['pls']=$a['pls']; et_assert(!elevator_can_fight($other,$b) && elevator_final_damage($a,$b,100)===120,'duel excludes other players');
$now=1031; et_assert(elevator_can_fight($other,$b),'duel expires'); $now=1000;
$a['clbpara']['skill']=array('el_552','el_437'); $a['hp']=100; $b['lvl']=5; elevator_skill_kill($a,$b);
et_assert($a['hp']===125 && !empty($a['itms1']),'kill grants resource and level healing'); elevator_skill_kill($a,$b); et_assert($a['hp']===125,'kill replay does not heal');
ex_floor(); $a['clbpara']['skill']=array('el_582'); et_assert(elevator_find_rate($a,50)===60.0,'fortune discovery'); $n=count($db->ground); elevator_grant_skill($a,'el_572'); et_assert(count($db->ground)===$n+2 && !elevator_has_skill($a,572),'harvest scatters and disappears');
$a['clbpara']['skill']=array('el_535'); $GLOBALS['elevator_skill_definitions'][535][2]['chance']=100; $cash=$a['money']; ruleset_move_search_success_hook($a,'search'); et_assert($a['money']===$cash+1,'mining only effective exploration'); unset($GLOBALS['elevator_skill_definitions']);
$e['stops']=3; $n=count($db->players); elevator_supply_delivery($e,1,1000); elevator_supply_delivery($e,1,1000);
et_assert(count($db->players)===$n+1,'delivery once per qualifying stop'); $box=fetch_playerdata_by_pid($db->id); et_assert(elevator_has_skill($box,803) && elevator_has_skill($box,804),'delivery carries iron shell and loot skill');
$leader=elevator_floor_npc(1,2); $leader['clbpara']['skill']=array('el_801'); $box['clbpara']['skill'][]='el_801'; player_save($box); $leader['clbpara']['skillpara']['el_801']=array('leader'=>true,'followers'=>array($box['pid'])); elevator_skill_kill($a,$leader);
et_assert(!fetch_playerdata_by_pid($box['pid']),'leader death withdraws follower without orphan row');
$GLOBALS['elevator_recipe_skill_rate']=100; $r=rr_generate_recipe('WP'); unset($GLOBALS['elevator_recipe_skill_rate']);
$a=et_player(); $a['weps']=10; $a['weppara']=json_encode($r['result']['itmpara']); $trait=(int)substr($r['result']['itmpara']['elevator_skill'],3);
et_assert(elevator_has_skill($a,$trait),'blueprint equipment carries round skill'); $a['weps']=0; et_assert(!elevator_has_skill($a,$trait),'broken equipment loses trait');
$plain=$a; unset($plain['clbpara']['elevator']); $plain['clbpara']['skill']=array('el_515'); et_assert(!elevator_has_skill($plain,515) && elevator_sp_cost($plain,0)===0,'ordinary mode does not enable imported effects');

// 诅咒消耗与 HTTP/BOT 刷新间隔无关，怪物掉落档位不被技能档位覆盖。
foreach (array(150,0) as $budget) {
    $p=et_player(); $p['clbpara']['elevator']['protected']=false; $p['clbpara']['elevator']['budget']=$budget; $p['clbpara']['elevator']['curses']=array('erosion'=>1); $q=$p;
    elevator_budget_tick($p,1000,1004,$e); for ($t=1000;$t<1004;$t++) elevator_budget_tick($q,$t,$t+1,$e);
    et_assert(abs($p['clbpara']['elevator']['budget']-$q['clbpara']['elevator']['budget'])<.00001 && $p['hp']===$q['hp'],'erosion cadence invariant');
    if (!$budget) et_assert($p['hp']===368,'assimilation does not scale damage after budget exhaustion');
}
foreach (array(0,1,2) as $tier) {
    $GLOBALS['elevator_loot_config'][2][$tier]['count']=array($tier+1,$tier+1);
    $npc=rr_generate_npc(20,0,0,0,2,$tier,1); $count=0; for ($i=2;$i<=6;$i++) if (!empty($npc['itms'.$i])) $count++;
    et_assert($count===$tier+1,'NPC skill tier cannot override loot tier');
}
unset($GLOBALS['elevator_loot_config']); $GLOBALS['elevator_armory_key_rate']=100;
$loot=elevator_pool_loot(2,2,10,true); et_assert(!empty($loot[0]['itmpara']['elevator_keycard']),'loot elite also carries key');
foreach (elevator_pool_loot(2,2,10) as $item) et_assert(empty($item['itmpara']['elevator_keycard']),'task rewards cannot generate enemy key'); unset($GLOBALS['elevator_armory_key_rate']);

require_once GAME_ROOT.'gamedata/cache/combatcfg_1.php';
require_once GAME_ROOT.'include/game/revattr.func.php';
require_once GAME_ROOT.'include/game/revcombat_extra.func.php';
$p=et_player(); $p['nm']='乘客'; $p['clbpara']['skill']=array('el_532'); $p['wep_kind']='K'; $p['wepk']='WK'; $p['weps']=10; $p['wepe']=30; $p['arbs']=10;
weapon_loss($p,3); armor_hurt($p,'arb',3); et_assert($p['weps']===10 && $p['arbs']===10,'native durability protection');
$p['wep_kind']='G'; $p['wepk']='WG'; weapon_loss($p,3); et_assert($p['weps']===7,'native ammunition still consumed');
$p['clbpara']['skill']=array('el_422'); $p['clbpara']['skillpara']['el_422']['melee']=true; et_assert(\revattr\get_wep_kind($p)==='P','gunbutt native category');
weapon_loss($p,2); et_assert($p['weps']===7 && $p['wepe']===28,'loaded gunbutt wears weapon effect instead of ammunition');
$p['clbpara']['skill']=array('el_803'); et_assert(\revattr\get_inf_rev($p,'p')===0 && \revattr\get_skillinf_rev($p,'inf_dizzy')===0,'iron shell blocks both native debuff paths');
$other=et_player(2); et_assert(\revcombat\attack_check_can_counter($other,$p,1)===0,'iron shell native counter gate');
$p['clbpara']['skill']=array('el_442'); $p['wep_kind']='P'; $p['base_att']=100; $p['wep_skill']=100; $other['base_def']=100;
$fixed=\revattr\get_original_dmg_rev($p,$other,1); for ($i=0;$i<10;$i++) et_assert(\revattr\get_original_dmg_rev($p,$other,1)===$fixed,'E skill stable native damage');
$p['clbpara']['skill']=array(); unset($p['clbpara']['elevator'],$other['clbpara']['elevator']);
et_assert(\revattr\get_inf_rev($p,'p')===1 && ruleset_final_damage_fix_hook($p,$other,1,100)===null,'normal status and damage gate remain unchanged');
