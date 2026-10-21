<?php
if (!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).'/');
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
require __DIR__.'/fixture.php';
require dirname(__DIR__).'/include/another.config.php';
require dirname(__DIR__).'/include/another.talents.php';
require dirname(__DIR__).'/include/another.items.php';
require dirname(__DIR__).'/include/another.combat.php';
require dirname(__DIR__).'/include/another.core.php';
require dirname(__DIR__).'/include/another.actions.php';
$checks=0;
function an_check($pass,$description){global $checks;$checks++;if(!$pass)throw new RuntimeException($description);}
function an_tree($tree){$out=array();foreach(another_talent_definitions() as $id=>$def)if($def['tree']===$tree)$out[$id]=$def['max'];return $out;}
an_fixture_reset();
$p=an_fixture_player(1,'A',an_tree('E')); $q=an_fixture_player(2,'B',an_tree('C'));
$players=array(1=>$p,2=>$q); $room=array('next_id'=>1,'ground'=>array(),'traps'=>array(),'deliveries'=>array(),'stock'=>array(),'closed'=>array(),'news'=>array(),'reinforced'=>array(900=>true,1800=>true),'entry_closed'=>false);
$messages=array();
an_check(!another_action($room,$players,1,'move',array('to'=>13),$now,$messages),'不能跨地图瞬移');
an_check(another_action($room,$players,1,'move',array('to'=>2),$now,$messages),'邻接移动成功');
an_check($players[1]['sp']===385,'移动扣体力');
an_check(!another_action($room,$players,1,'search',array(),$now,$messages),'服务端行动间隔');
$now+=2; $players[1]['hp']=100;
another_action($room,$players,1,'rest',array(),$now,$messages);
for($i=1;$i<=10;$i++) another_tick($room,$players,$now+$i,$starttime);
an_check($players[1]['hp']>=110,'每秒轮询累计休息回复，不能被取整吞掉');
$players[1]['pls']=1; $players[1]['sp']=400; $now+=15;
another_item_set($players[1],'wep',another_item_make('throw_high',99));
$players[2]['hp']=1; another_discover_player($players[1],$players[2],$now);
an_check(another_action($room,$players,1,'attack',array('target_pid'=>2),$now,$messages),'实际动作战斗');
an_check($players[2]['hp']===0 && $players[1]['killnum']===1,'击杀与奖励结算');
$money=$players[1]['money']; another_settle_deaths($room,$players,$now);
an_check($players[1]['money']===$money,'再次结算不重复发奖');
$room['entry_closed']=true; another_check_survival($room,$players,$now);
an_check($room['result']['kind']==='survival' && $room['result']['winners']===array(1),'普通幸存胜利');

an_fixture_reset();
$players=array(1=>an_fixture_player(1,'A',an_tree('E')),2=>an_fixture_player(2,'B'));
$players[1]['teamID']=$players[2]['teamID']='friends'; $players[2]['hp']=0;
$room=array('closed'=>array(),'entry_closed'=>true);
another_check_survival($room,$players,$now);
an_check($room['result']['winners']===array(1,2),'全队胜利包括已倒下队友');
$room=array('next_id'=>1,'ground'=>array(),'traps'=>array(),'deliveries'=>array(),'stock'=>array(),'closed'=>array(),'news'=>array(),'reinforced'=>array(900=>true,1800=>true));
$players[1]['hp']=1; $players[1]['clbpara']['another']['earth_left']=3; $players[1]['pls']=13;
another_tick($room,$players,$starttime+600,$starttime);
an_check($players[1]['hp']===0 && $players[1]['state']===11,'禁区能处决挂机免死');

an_fixture_reset();
$players=array(1=>an_fixture_player(1,'A',an_tree('L')),2=>an_fixture_player(2,'B',an_tree('L')));
$players[1]['teamID']=$players[2]['teamID']='duo';
another_item_add($players[1],another_item_make('satellite')); another_item_add($players[2],another_item_make('satellite'));
$room=array('closed'=>array(),'entry_closed'=>false);
mt_srand(2); another_action($room,$players,1,'hack',array(),$now,$messages);
an_check(!empty($players[1]['clbpara']['another']['hack_used']) && empty($players[2]['clbpara']['another']['hack_used']),'每角色独立一次黑入');
if(empty($room['result'])) { $now+=2; another_action($room,$players,2,'hack',array(),$now,$messages); }
an_check(!empty($room['result']) && $room['result']['winners']===array(1,2),'木成功全队脱出');

an_fixture_reset();
$players=array(1=>an_fixture_player(1,'A',an_tree('C')),2=>an_fixture_player(2,'B'),3=>an_fixture_player(3,'C',an_tree('C')));
$players[1]['teamID']=$players[3]['teamID']='together';
$players[1]['clbpara']['another']['fire']=2; $players[3]['clbpara']['another']['fire']=2;
$players[2]['hp']=0; $players[2]['bid']=1;
$room=array(); another_settle_deaths($room,$players,$now);
an_check($players[1]['clbpara']['another']['rep']==143 && $players[3]['clbpara']['another']['rep']==110,'两枚火分别结算并叠自我包装');

// 持久化重载：两个身体、装备完整para、地图物品、陷阱、配送分别保存。
an_fixture_reset();
$p=an_fixture_player(1,'A',an_tree('L')); $p['clbpara']['another']['active_pid']=2;
$q=an_fixture_player(2,'A',an_tree('C')); $q['name']='A②'; $q['teamID']=$p['teamID'];
another_item_set($q,'wep',another_item_make('gun_mid'));
$db->array_insert('bra_players',player_format_with_db_structure($p)); $db->array_insert('bra_players',player_format_with_db_structure($q));
$players=array(1=>$p,2=>$q);
$room=array('next_id'=>4,'closed'=>array(),'ground'=>array(1=>array('id'=>1,'pls'=>2,'item'=>another_item_make('heal'))),'traps'=>array(2=>array('id'=>2,'pls'=>2,'owner'=>1,'item'=>another_item_make('trap_combo'),'gold'=>true)),
    'deliveries'=>array(3=>array('id'=>3,'pls'=>2,'owner'=>1,'kind'=>'balloon','due'=>$now+20,'items'=>array(another_item_make('gun_high')))));
another_save_world($room,$players);
an_check(!isset($gamevars['another']['ground']) && !isset($gamevars['another']['deliveries']),'不把活动物资塞入gamevars');
$again=another_read_room(); $read=another_read_players();
an_check($again==$room && $read[2]['weppara']===$q['weppara'],'重载保持物品参数与配送');
an_check(another_current_player_ids($read,'A')===array(1,2),'两个身体归同一账户');
unset($again['ground'][1]); another_save_world($again,$read,$room,$read);
an_check(count($db->tables['bra_mapitem'])===1 && isset($db->tables['bra_mapitem'][3]),'地物领取只移除其行，保留配送');
$again['result']=array('kind'=>'escape','winners'=>array(1,2),'time'=>$now);
another_record_result($again,$read);
an_check(count(another_mirror_pool())===2,'胜队两个完整角色入镜像池');
another_record_result($again,$read);
an_check(count($test_ends)===1,'结局不能重复入库');

echo 'AN:OTHER round integration: '.$checks." checks passed.\n";
