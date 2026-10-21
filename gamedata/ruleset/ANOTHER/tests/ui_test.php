<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('GAME_ROOT')) define('GAME_ROOT', dirname(__DIR__,4).'/');
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){ throw new ErrorException($message,0,$level,$file,$line); });
require __DIR__.'/fixture.php';
foreach(array('config','talents','items','combat','core','actions','ui') as $module) require dirname(__DIR__).'/include/another.'.$module.'.php';
$checks=0;
function an_ui_check($condition,$message) { global $checks; $checks++; if(!$condition) throw new RuntimeException($message); }
function an_ui_has_action($html,$action) { return strpos($html,'name="action" value="'.$action.'"')!==false; }

an_fixture_reset();
$room=array('started_at'=>10000,'entry_closed'=>false,'next_id'=>1,'closed'=>array(),'ground'=>array(),'traps'=>array(),'deliveries'=>array(),'stock'=>array(),'news'=>array(),'traces'=>array());
foreach(array('melee','support','escape') as $preset) {
    $points=another_ui_preset($preset); $error='';
    an_ui_check(another_validate_talents($points,$error)!==false,'预设配点必须通过服务器校验：'.$preset.' '.$error);
    an_ui_check(another_talent_points($points)===35,'预设必须完整使用35点：'.$preset);
}
$join=another_render_body($room,array(),'A',0,0,'csrf-test',10000,true);
an_ui_check(substr_count($join,'data-talent=')===82,'单控和双控各自显示完整41项天赋');
an_ui_check(strpos($join,'name="controls" value="2"')!==false,'双控入场入口');
an_ui_check(strpos($join,'name="csrf" value="csrf-test"')!==false,'入场CSRF字段');
an_ui_check(strpos($join,'DIAMOND')===false&&strpos($join,'OMICRON')===false&&strpos($join,'电掣')===false,'公开入场文案不泄漏幕后身份');
$closed=$room; $closed['entry_closed']=true;
an_ui_check(strpos(another_render_body($closed,array(),'A',0,0,'token',10600,true),'id="an-join"')===false,'停止入场后隐藏入场表单');

// 所有天赋仅用于覆盖按钮分支；真实入场配点已用上面的三个35点预设校验。
$all=array(); foreach(another_talent_definitions() as $id=>$def) $all[$id]=$def['max'];
$p=an_fixture_player(1,'A',$all); $p['clbpara']['another']['display_name']='<script>alert("name")</script>';
$p['clbpara']['another']['known_players']=array(3=>10000,5=>10000,6=>10000);
$p['clbpara']['another']['known_ground']=array(1=>10000);
$p['clbpara']['another']['known_traps']=array(3=>10000);
$p['clbpara']['another']['known_deliveries']=array(5=>10000);
$p['clbpara']['another']['invites']=array(3=>10000);
$p['clbpara']['another']['messages']=array(array('time'=>10000,'text'=>'<img src=x onerror=alert(1)>'));
another_item_set($p,'wep',another_item_make('gun'));
another_item_set($p,'arb',another_item_make('armor'));
foreach(array(1=>'heal',2=>'trap_alarm',3=>'radio',4=>'satellite',5=>'poison_test',6=>'food_antidote') as $slot=>$item) another_item_set($p,$slot,another_item_make($item));
$p2=an_fixture_player(2,'A'); $p2['hp']=0; $p2['teamID']=$p['teamID'];
$enemy=an_fixture_player(3,'已发现玩家');
$hidden=an_fixture_player(4,'绝不公开的未发现玩家');
$corpse=an_fixture_player(5,'可拾取遗体'); $corpse['hp']=0; another_item_set($corpse,'wep',another_item_make('slash'));
$npc=an_fixture_player(6,'可见机械体'); $npc['type']=105; $npc['clbpara']['another']['npc_role']='grunt';
$players=array(1=>$p,2=>$p2,3=>$enemy,4=>$hidden,5=>$corpse,6=>$npc);
$known_item=another_item_make('ammo'); $known_item['name']='已发现弹药';
$hidden_item=another_item_make('ammo'); $hidden_item['name']='绝不公开的未发现物资';
$room['ground']=array(1=>array('id'=>1,'pls'=>1,'item'=>$known_item),2=>array('id'=>2,'pls'=>1,'item'=>$hidden_item));
$room['traps']=array(3=>array('id'=>3,'pls'=>1,'item'=>another_item_make('trap_alarm'),'gold'=>false));
$room['deliveries']=array(4=>array('id'=>4,'pls'=>1,'owner'=>1,'kind'=>'falcon','due'=>9999),5=>array('id'=>5,'pls'=>1,'owner'=>3,'kind'=>'balloon','due'=>10060),6=>array('id'=>6,'pls'=>1,'owner'=>4,'kind'=>'balloon','due'=>10060));
$html=another_render_body($room,$players,'A',1,7,'csrf-test',10020);
an_ui_check(strpos($html,'data-seq="7"')!==false,'页面携带最新指令序列');
an_ui_check(strpos($html,'绝不公开的未发现玩家')===false,'不泄漏未知玩家');
an_ui_check(strpos($html,'绝不公开的未发现物资')===false,'不泄漏未知物资');
an_ui_check(strpos($html,'已发现弹药')!==false&&strpos($html,'已发现玩家')!==false,'显示实际发现的目标');
an_ui_check(strpos($html,'<script>alert')===false&&strpos($html,'&lt;script&gt;')!==false,'转义角色名称');
an_ui_check(strpos($html,'<img src=x')===false&&strpos($html,'&lt;img src=x')!==false,'转义个人日志');
an_ui_check(substr_count($html,'value="shoot_balloon"')===1,'只显示已发现气球的争夺按钮');
an_ui_check(preg_match('/name="action" value="shoot_balloon"[^<]*.*?name="id" value="5"><button type="submit" class="">击落争夺<\/button>/s',$html)===1,'在途已发现气球可以立即击落');
foreach(array('switch','rest','hack','move','search','attack','sun','moon','fire','invite','accept_team','leave_team','give','give_money','give_rep','loot','pickup','disarm','collect','shoot_balloon','unequip','use','plant','reload','upgrade','repair','armor_upgrade','test_food','clean_food','drop','craft','buy') as $action) an_ui_check(an_ui_has_action($html,$action),'动作必须有可用UI入口：'.$action);
another_item_set($players[1],'wep',another_item_make('slash')); another_item_set($players[1],5,another_item_make('poison')); another_item_set($players[1],2,another_item_make('armor'));
$blade=another_render_body($room,$players,'A',1,8,'csrf-test',10020);
foreach(array('equip','sharpen','socket','poison') as $action) an_ui_check(an_ui_has_action($blade,$action),'斩系和投毒动作入口：'.$action);
$room['deliveries'][7]=array('id'=>7,'pls'=>2,'owner'=>1,'kind'=>'balloon','due'=>10060);
$remote=another_render_body($room,$players,'A',1,8,'csrf-test',10020);
an_ui_check(strpos($remote,'自己呼叫的补给气球')!==false,'本人离开后仍显示自己的气球订单');
an_ui_check(preg_match('/name="action" value="shoot_balloon"[^<]*.*?name="id" value="7"><button type="submit" class="" disabled>击落争夺<\/button>/s',$remote)===1,'远处的本人气球不能跨地图击落');
$players[1]['hp']=0;
$dead=another_render_body($room,$players,'A',1,9,'csrf-test',10020);
an_ui_check(an_ui_has_action($dead,'switch'),'死亡角色仍有双控切换入口');
an_ui_check(strpos($dead,'name="action" value="attack"')===false,'死亡角色不能提交攻击');
$room['result']=array('kind'=>'escape','winners'=>array(1,2),'time'=>10500); $starttime=999999;
$result=another_render_body($room,$players,'A',1,9,'csrf-test',11000);
an_ui_check(strpos($result,'异常终止 · 队伍脱出')!==false,'异常终止按全队显示胜利');
an_ui_check(strpos($result,'data-elapsed="500"')!==false,'结局时长不受公共starttime重置和之后刷新影响');
an_ui_check(strpos($result,'对局已结束')!==false,'结局不显示仍在运行的截止倒计时');
an_ui_check(strpos($result,'an-zone-warning')===false,'结局不再显示下一轮禁区预告');
echo 'AN:OTHER UI OK · '.$checks." checks\n";
