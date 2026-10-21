<?php
if(!defined('IN_GAME')) exit('Access Denied');
define('GAME_ROOT',dirname(__DIR__,4).'/');
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
require __DIR__.'/fixture.php';
require dirname(__DIR__).'/include/ruleset.func.php';
$checks=0;
function anh_check($condition,$label){global $checks;$checks++;if(!$condition)throw new RuntimeException($label);}
function anh_command($input) {
    $_SERVER['REQUEST_METHOD']='POST'; $_POST=$input;
    ob_start(); another_http_page('command'); $body=ob_get_clean();
    $result=json_decode($body,true);
    if(!is_array($result)) throw new RuntimeException('HTTP command did not return JSON: '.$body);
    return $result;
}
an_fixture_reset();
$plock=tmpfile(); flock($plock,LOCK_EX); // 不触碰部署process.lock。
$groomid=1;
$udata=array('uid'=>1,'username'=>'HTTP玩家','password'=>'local-test-only','groupid'=>1,'ip'=>'127.0.0.1','gender'=>'m','icon'=>0);
$cpass=$udata['password']; $db->tables['bra_users'][1]=$udata;
another_seed_world($now);
$room=another_read_room(); $players=another_read_players(); $old_room=$room; $old_players=$players;
another_join($udata,array('controls'=>2),$room,$players,$now);
another_save_world($room,$players,$old_room,$old_players);
$ids=another_current_player_ids($players,$udata['username']);
$csrf=another_csrf($udata);
$bad=anh_command(array('action'=>'search','seq'=>0,'csrf'=>'wrong'));
anh_check($bad['seq']===0 && strpos($bad['html'],'页面已过期')!==false,'伪造CSRF不改变序号或玩家状态');
$r=anh_command(array('action'=>'search','seq'=>0,'csrf'=>$csrf));
$after=another_read_players();
anh_check($r['seq']===1 && $after[$ids[0]]['sp']==390,'真实HTTP动作扣体力并持久化');
$repeat=anh_command(array('action'=>'search','seq'=>0,'csrf'=>$csrf));
anh_check($repeat['seq']===1 && another_read_players()[$ids[0]]['sp']==390 && strpos($repeat['html'],'重复或过期')!==false,'重复POST不能再执行');
$wrong=anh_command(array('action'=>'switch','target_pid'=>1,'seq'=>1,'csrf'=>$csrf));
anh_check(strpos($wrong['html'],'不能操纵')!==false && another_read_players()[$ids[0]]['clbpara']['another']['active_pid']===$ids[0],'不能切换到NPC或他人身体');
$r=anh_command(array('action'=>'switch','target_pid'=>$ids[1],'seq'=>2,'csrf'=>$csrf));
anh_check($r['seq']===3 && another_read_players()[$ids[0]]['clbpara']['another']['active_pid']===$ids[1],'两角色可切换并保存当前操纵对象');
$r=anh_command(array('action'=>'search','seq'=>3,'csrf'=>$csrf));
$after=another_read_players();
anh_check($r['seq']===4 && $after[$ids[0]]['sp']==390 && $after[$ids[1]]['sp']==390,'新身体使用自身行动冷却和体力');
$now+=2;
$r=anh_command(array('action'=>'move','to'=>13,'seq'=>4,'csrf'=>$csrf));
anh_check(strpos($r['html'],'只能移动到相邻')!==false && another_read_players()[$ids[1]]['pls']==1,'HTTP伪造非邻接移动被拒绝');
$r=anh_command(array('action'=>'attack','target_pid'=>array(1),'seq'=>5,'csrf'=>$csrf));
anh_check(strpos($r['html'],'指令参数无效')!==false,'数组动作参数不能进入战斗');
$r=anh_command(array('action'=>'attack','target_pid'=>1,'seq'=>6,'csrf'=>$csrf));
anh_check(strpos($r['html'],'目标已不在这里')!==false,'未知异地图目标不可攻击');
$prior=serialize($db->tables);
$_SERVER['REQUEST_METHOD']='GET'; $_POST=array(); ob_start(); another_http_page('command'); $poll=json_decode(ob_get_clean(),true);
anh_check($poll['seq']===7 && serialize($db->tables)===$prior,'查看页面不消耗动作或重复改写角色');
$gamenum++; anh_check(another_csrf($udata)!==$csrf,'下一局失效旧局指令凭证');
$cpass='not-the-user-password'; ob_start(); another_http_page('command'); $denied=ob_get_clean();
anh_check(strpos($denied,'登录已失效')!==false && http_response_code()===403,'模式入口验证真实账号会话');
fclose($plock);
echo 'AN:OTHER HTTP controller: '.$checks." checks passed.\n";
