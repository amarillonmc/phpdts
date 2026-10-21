<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).DIRECTORY_SEPARATOR);
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line) { throw new ErrorException($message,0,$level,$file,$line); });
require __DIR__.'/fixture.php';
foreach (array('config','talents','items','combat','core','actions') as $module) require dirname(__DIR__).'/include/another.'.$module.'.php';

$session_checks=0;
function ans_check($ok,$message) { global $session_checks; $session_checks++; if(!$ok) throw new RuntimeException($message); }
function ans_user($uid,$name) {
    global $db;
    $user=array('uid'=>$uid,'username'=>$name,'password'=>'fixture-only','ip'=>'127.0.0.1','gender'=>'f','icon'=>1,'lastgame'=>0);
    $db->array_insert('bra_users',$user); return $user;
}
function ans_talents($trees) {
    $talents=array();
    foreach(another_talent_definitions() as $id=>$def) if(in_array($def['tree'],$trees,true)) $talents[$id]=$def['max'];
    return $talents+array('C1'=>3,'C2'=>1,'C3'=>1);
}
function ans_action(&$room,&$players,$pid,$action,$args,$at) {
    $messages=array(); return another_action($room,$players,$pid,$action,$args,$at,$messages,$at*1000);
}
function ans_seed() {
    global $starttime;
    an_fixture_reset(); another_seed_world($starttime);
    return array(another_read_room(),another_read_players());
}

// 完整播种走真实数据库写入／重读，不以手工配置数量代替实际产物。
list($room,$players)=ans_seed();
ans_check(count($room['ground'])===444 && count($db->tables['bra_mapitem'])===444,'完整播种得到444件地图物资');
ans_check(count($players)===45 && count(array_filter($players,function($p){return $p['type']>0;}))===45,'完整播种只生成45个NPC');
ans_check(count($room['traps'])===5 && count($db->tables['bra_maptrap'])===5,'野生陷阱独立持久化');
ans_check(!isset($gamevars['another']['ground'],$gamevars['another']['traps'],$gamevars['another']['deliveries']),'活动地图内容不进入gamevars');
$single=ans_user(101,'单控测试'); $dual=ans_user(202,'双控测试'); $third=ans_user(303,'队友测试'); $invalid=ans_user(404,'无效入场');
$first_build=ans_talents(array('S','P','E')); $second_build=ans_talents(array('I','A','L'));
ans_check(another_talent_points($first_build)===35 && another_talent_points($second_build)===35,'两套完整构筑各有35点');
$old_room=$room; $old_players=$players;
another_join($single,array('controls'=>1,'talents1'=>$first_build),$room,$players,$starttime+1);
$single_ids=another_current_player_ids($players,$single['username']); $a=$single_ids[0];
ans_check(count($single_ids)===1 && $players[$a]['clbpara']['another']['talents']===$first_build,'单控真实入场保留35点');
ans_check($players[$a]['hp']===600 && $players[$a]['mhp']===400 && $players[$a]['club']===0,'天赋放大有效HP而不改裸值或恢复社团');
ans_check(count($players[$a]['clbpara']['another']['known_ground'])===14,'入场即看见中央12件中级与2件基础抢夺武器');
another_join($dual,array('controls'=>2,'talents1'=>$first_build,'talents2'=>$second_build),$room,$players,$starttime+2);
$dual_ids=another_current_player_ids($players,$dual['username']); list($b,$b2)=$dual_ids;
ans_check(count($dual_ids)===2 && $players[$b]['clbpara']['another']['talents']===$first_build && $players[$b2]['clbpara']['another']['talents']===$second_build,'双控各自保留独立35点构筑');
ans_check($players[$b]['sp']===400 && $players[$b2]['sp']===540 && $players[$b]['teamID']===$players[$b2]['teamID'],'双身体各自属性并以同队入场');
ans_check($players[$b]['name']===$dual['username'] && strpos($players[$b2]['name'],'AN2:202:')===0 && $players[$b]['clbpara']['another']['active_pid']===$b,'主账号与副身体身份及初始操纵者正确');
$before=serialize(array($room,$players,$db->tables));
another_join($dual,array('controls'=>2,'talents1'=>$first_build,'talents2'=>$second_build),$room,$players,$starttime+3);
ans_check(serialize(array($room,$players,$db->tables))===$before,'同账户重复入场不重复身体物资或新闻');
$bad_build=$first_build+array('I4'=>1); $rejected=false;
try { another_join($invalid,array('controls'=>2,'talents1'=>$first_build,'talents2'=>$bad_build),$room,$players,$starttime+4); }
catch(RuntimeException $e) { $rejected=true; }
ans_check($rejected && !another_current_player_ids($players,$invalid['username']) && serialize(array($room,$players,$db->tables))===$before,'第二角色超35点时整次入场拒绝，不先插入第一个身体');
$bad_build=array('SU'=>1); $rejected=false;
try { another_join($invalid,array('controls'=>1,'talents1'=>$bad_build),$room,$players,$starttime+4); }
catch(RuntimeException $e) { $rejected=true; }
ans_check($rejected && !another_current_player_ids($players,$invalid['username']),'缺少九点前置不能直接入场取得终极');
another_save_world($room,$players,$old_room,$old_players);
load_gameinfo(); $room=another_read_room(); $players=another_read_players();
ans_check(another_current_player_ids($players,$dual['username'])===$dual_ids && $players[$b2]['clbpara']['another']['talents']===$second_build,'完整保存重读仍保有双控构筑与归属');
ans_check($db->tables['bra_users'][101]['lastgame']===$gamenum && $db->tables['bra_users'][202]['lastgame']===$gamenum && $db->tables['bra_users'][404]['lastgame']===0,'仅成功入场记录账号lastgame');

// 管理员单独重置地图，只更新物资，不再次加入NPC，不触碰玩家背包和构筑。
$kept_players=$players; $old_ids=array_keys($room['ground']); $old_room=$room;
$first_ground=array_key_first($room['ground']); unset($room['ground'][$first_ground]); $room['stock']['whetstone']=0;
another_save_world($room,$players,$old_room,$players);
another_seed_world($starttime+10,16);
$room=another_read_room(); $players=another_read_players();
ans_check(count($room['ground'])===444 && count($db->tables['bra_mapitem'])===444 && empty($room['stock']),'mode16恢复地图数量及商店库存，删除旧地图行');
ans_check($players===$kept_players && count($players)===48,'mode16不重复45个NPC，也不重置三个玩家身体');
ans_check(min(array_keys($room['ground']))>max($old_ids) && count($room['traps'])===5,'重置物资采用新的唯一编号，陷阱仍为五个');

// 用真实邀请／入队／离队动作，验证原队长离队不会把其他账号一并搬走。
another_join($third,array('controls'=>1,'talents1'=>$second_build),$room,$players,$starttime+11);
$c=another_current_player_ids($players,$third['username'])[0];
another_discover_player($players[$b],$players[$a],$starttime+12);
another_discover_player($players[$b],$players[$c],$starttime+12);
ans_check(ans_action($room,$players,$b,'invite',array('target_pid'=>$a),$starttime+12) && ans_action($room,$players,$a,'accept_team',array('target_pid'=>$b),$starttime+13),'第一名队友通过实际邀请入队');
ans_check(ans_action($room,$players,$b,'invite',array('target_pid'=>$c),$starttime+14) && ans_action($room,$players,$c,'accept_team',array('target_pid'=>$b),$starttime+15),'第二名队友通过实际邀请入队');
$old_team=$players[$b]['teamID'];
ans_check($players[$a]['teamID']===$old_team && $players[$b2]['teamID']===$old_team && $players[$c]['teamID']===$old_team,'四个身体同队');
ans_check(ans_action($room,$players,$b,'leave_team',array(),$starttime+16),'原队长可主动离队');
ans_check($players[$b]['teamID']!==$old_team && $players[$b]['teamID']===$players[$b2]['teamID'],'离队两身体同进新队，不能复用原队号');
ans_check($players[$a]['teamID']===$old_team && $players[$c]['teamID']===$old_team,'其他账号的队友仍留在原队');
another_item_set($players[$b],'wep',another_item_make('gun_mid'));
$weapon_uid=$players[$b]['weppara']['another']['uid'];
$players[$b]['clbpara']['another']['moon']=array('target'=>$c,'weapon'=>$weapon_uid);
ans_check(ans_action($room,$players,$b,'give',array('target_pid'=>$a,'slot'=>'wep'),$starttime+18),'已发现的异队玩家也可以接收自愿转交');
$given=another_item_get($players[$a],another_item_find($players[$a],'gun_mid'));
ans_check(!another_item_present(another_item_get($players[$b],'wep')) && empty($players[$b]['clbpara']['another']['moon']) && $given['para']['another']['uid']===$weapon_uid,'转交当前武器后清除月标记，并保留物品身份');
$old_room=another_read_room(); $old_players=another_read_players(); another_save_world($room,$players,$old_room,$old_players);
$players=another_read_players();
ans_check(empty($players[$b]['clbpara']['another']['moon']) && $players[$b2]['teamID']===$players[$b]['teamID'] && $players[$a]['teamID']===$old_team,'离队和转交状态保存后仍然成立');

// 长时间无人请求：按禁区原定边界先结算幸存，不能在4000秒一次补判全灭。
list($room,$players)=ans_seed();
$one=ans_user(1,'中央幸存'); $two=ans_user(2,'内城对手'); $three=ans_user(3,'外城对手');
foreach(array($one,$two,$three) as $user) another_join($user,array('controls'=>1,'talents1'=>$first_build),$room,$players,$starttime+1);
$survivor=another_current_player_ids($players,$one['username'])[0];
$inner=another_current_player_ids($players,$two['username'])[0]; $outer=another_current_player_ids($players,$three['username'])[0];
$players[$inner]['pls']=2; $players[$outer]['pls']=13;
another_tick($room,$players,$starttime+4000,$starttime);
ans_check($room['result']['kind']==='survival' && $room['result']['winners']===array($survivor),'无人请求到4000秒仍按3600秒已产生的幸存队结算');
ans_check($room['result']['time']===$starttime+3600 && $room['last_tick']===$starttime+3600 && $room['result']['time']<=$starttime+3900,'结算时间是实际边界而非迟到请求时间');
ans_check($players[$outer]['hp']===0 && $players[$outer]['deathtime']===$starttime+600 && $players[$inner]['hp']===0 && $players[$inner]['deathtime']===$starttime+3600,'不同禁区按各自关闭时刻淘汰');
ans_check($players[$survivor]['hp']>0 && !in_array(1,$room['closed'],true),'已幸存玩家不补吃65分钟中央禁区');
$old_room=another_read_room(); $old_players=another_read_players(); another_save_world($room,$players,$old_room,$old_players); load_gameinfo();
$room=another_read_room(); $players=another_read_players(); $ended=serialize(array($room,$players));
another_tick($room,$players,$starttime+8000,$starttime);
ans_check(serialize(array($room,$players))===$ended,'结局保存重读后再迟到请求不改变胜负');

// 两个敌对土终极角色一直不行动：保护不随时间消耗，最终中央禁区仍处决。
list($room,$players)=ans_seed();
foreach(array(ans_user(1,'土守甲'),ans_user(2,'土守乙')) as $user) another_join($user,array('controls'=>1,'talents1'=>$first_build),$room,$players,$starttime+1);
$guard_a=another_current_player_ids($players,'土守甲')[0]; $guard_b=another_current_player_ids($players,'土守乙')[0];
$messages=array(); foreach(array($guard_a,$guard_b) as $pid) another_damage($players[$pid],10000,'attack',$starttime+2,$messages);
ans_check($players[$guard_a]['hp']===1 && $players[$guard_b]['hp']===1 && $players[$guard_a]['clbpara']['another']['earth_left']===3,'两个敌对角色都实际触发土保护');
another_tick($room,$players,$starttime+3899,$starttime);
ans_check(empty($room['result']) && $players[$guard_a]['hp']===1 && $players[$guard_b]['hp']===1 && $players[$guard_b]['clbpara']['another']['earth_left']===3,'长期不动不会消耗保护或提前判胜');
another_tick($room,$players,$starttime+4000,$starttime);
ans_check($room['result']['kind']==='timeout' && !$room['result']['winners'] && $room['result']['time']===$starttime+3900,'最终65分钟无胜者收束');
ans_check($players[$guard_a]['hp']===0 && $players[$guard_b]['hp']===0 && $players[$guard_a]['state']===11 && $players[$guard_b]['state']===11,'中央禁区同时处决两名土保护角色');
ans_check($players[$guard_a]['deathtime']===$starttime+3900 && $players[$guard_b]['deathtime']===$starttime+3900,'迟到请求不推迟实际禁区死亡时刻');

// 胜队实际入池后，于下一局抽取三种构筑；装备是NPC专用制式，不复制冠军库存。
an_fixture_reset();
$builds=array($first_build,$second_build,ans_talents(array('P','E','L'))); $players=array(); $old_weapon_uids=array();
foreach($builds as $index=>$build) {
    $pid=$index+1; $p=an_fixture_player($pid,'往届冠军'.$pid,$build); $p['teamID']='往届胜队';
    $p['lvl']=10+$index; $p['mhp']=450+$index*30; $p['msp']=420+$index*20; $p['att']=130+$index*10; $p['def']=120+$index*10;
    $p['hp']=another_max_hp($p); $p['sp']=another_max_sp($p);
    $weapon=another_item_make('gun_high'); $weapon['name']='玩家私藏的终局枪'; $weapon['effect']=999;
    $old_weapon_uids[$p['name']]=$weapon['para']['another']['uid']; another_item_set($p,'wep',$weapon);
    $armor=another_item_make('null_armor'); $armor['name']='玩家私藏的终局护甲'; $armor['effect']=999; another_item_set($p,'arb',$armor);
    $players[$pid]=$p; $db->array_insert('bra_players',player_format_with_db_structure($p));
}
$room=array('ground'=>array(),'traps'=>array(),'deliveries'=>array(),'result'=>array('kind'=>'survival','winners'=>array(1,2,3),'time'=>$starttime+3600));
another_record_result($room,$players); $snapshots=another_mirror_pool();
ans_check(count($snapshots)===3 && !isset($snapshots[0]['wep'],$snapshots[0]['arb']),'完整胜队入池只记录构筑与基础成长，不记录装备');
// 模拟下一局既有初始化清空活动表；保留上一局winners战绩。
$db->tables['bra_players']=$db->tables['bra_mapitem']=$db->tables['bra_maptrap']=array();
$gamevars=array(); $starttime+=5000; $gamenum=2; $gamestate=20;
another_seed_world($starttime); $room=another_read_room(); $players=another_read_players(); $old_room=$room; $old_players=$players;
another_spawn_mirrors($room,$players,$starttime+1199,$starttime);
ans_check(count($players)===45 && empty($room['mirrors_spawned']),'镜像不会提前到场');
$room['closed']=array(13,17,18,14,15,16);
another_spawn_mirrors($room,$players,$starttime+1200,$starttime);
$mirrors=array_filter($players,function($p){return !empty($p['clbpara']['another']['mirror']);});
ans_check(count($mirrors)===3 && count($players)===48 && count($db->tables['bra_players'])===48,'镜像时刻实际生成并持久化三名独立NPC');
$slash_mirror=0;
foreach($mirrors as $pid=>$mirror) {
    $snapshot=null; foreach($snapshots as $candidate) if(another_name($mirror)===$candidate['name'].'的镜像') $snapshot=$candidate;
    ans_check($snapshot!==null && $mirror['clbpara']['another']['talents']===$snapshot['talents'],'镜像保留抽中的完整天赋构筑');
    $same=true; foreach(array('lvl','mhp','msp','att','def') as $field) if($mirror[$field]!==$snapshot[$field]) $same=false;
    ans_check($same && $mirror['hp']===another_max_hp($mirror) && $mirror['sp']===another_max_sp($mirror),'镜像保留成长裸值并按自身天赋生成有效满血满体');
    $expected=another_talent($mirror,'SU')?'WK':(another_talent($mirror,'A1')>another_talent($mirror,'P1')?'WC':'WG');
    if($expected==='WK') $slash_mirror=$pid;
    ans_check($mirror['wepk']===$expected && $mirror['wepe']<999 && strpos($mirror['wep'],'镜像')!==false && $mirror['weppara']['another']['uid']!==$old_weapon_uids[$snapshot['name']],'镜像按SU优先斩/投专精优先投/其余射分配新制式武器');
    ans_check($mirror['arb']==='镜像制式护甲' && $mirror['arbe']===another_item_catalog()['armor_mid']['effect'] && empty($mirror['arbpara']['another']['nullify']),'镜像制式护甲不照抄冠军终局护甲');
    ans_check(!in_array($mirror['pls'],$room['closed'],true),'镜像只生成在仍安全的地点');
}
ans_check(count($room['traps'])===8,'含金终极的镜像实际布设三枚专属陷阱');
$before=serialize(array($room,$players,$db->tables));
another_spawn_mirrors($room,$players,$starttime+1300,$starttime);
ans_check(serialize(array($room,$players,$db->tables))===$before,'重复轮询不再抽取或复制镜像陷阱');
another_save_world($room,$players,$old_room,$old_players); load_gameinfo(); $room=another_read_room(); $players=another_read_players();
ans_check($players[$slash_mirror]['wepk']==='WK' && !empty($players[$slash_mirror]['clbpara']['another']['talents']['SU']),'保存重读后斩镜像仍持有可供日消耗的武器');
$target=an_fixture_player(1000,'镜像对战目标'); $target['pls']=$players[$slash_mirror]['pls'];
$target['hp']=$target['mhp']=10000; $target['att']=1; $players[1000]=$target; $players[$slash_mirror]['hp']=50; $messages=array();
ans_check(another_mirror_uses_sun($players[$slash_mirror],$players[1000],$players,$starttime+1301),'低血镜像能够判断发动日之愤怒');
ans_check(another_battle($players[1000],$players[$slash_mirror],$players,$room,array(),$starttime+1301,$messages),'实际遭遇可执行镜像反击');
ans_check($players[$slash_mirror]['hp']===25 && !another_item_present(another_item_get($players[$slash_mirror],'wep')) && $players[1000]['hp']<10000,'镜像真实发动日：支付半血与制式刀，并命中目标');

restore_error_handler();
echo 'AN:OTHER session lifecycle: '.$session_checks." checks passed.\n";
