<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','ending_platform_test');
function config($name,$version=1) { return GAME_ROOT.'gamedata/cache/'.$name.'_'.$version.'.php'; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode((string)$value,true) ?: array()); }
function get_itmpara($value) { return get_clbpara($value); }
require GAME_ROOT.'include/resources.func.php';
require GAME_ROOT.'include/game/revclubskills.func.php';
require GAME_ROOT.'include/game/ending_routes.func.php';
require GAME_ROOT.'include/game/severe_status.func.php';
require GAME_ROOT.'include/game/item.platform.php';
require GAME_ROOT.'gamedata/cache/resources_1.php';
require GAME_ROOT.'gamedata/cache/gamecfg_1.php';
require GAME_ROOT.'gamedata/cache/clubskills_1.php';
$current_ruleset_config=array(); $gamecfg=1; $now=1000; $log=''; $nosta=INF;
function platform_assert($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
function platform_player($char=0) {
	$p=array('pid'=>1,'name'=>'测试者','pass'=>'not-a-real-password','type'=>0,'pgroup'=>0,'pls'=>5,'horizon'=>0,'hp'=>500,'mhp'=>500,'sp'=>500,'msp'=>500,'wp'=>20,'wk'=>30,'wg'=>40,'wc'=>50,'wd'=>60,'wf'=>70,'money'=>100,'icon'=>'1','gd'=>'m','clbpara'=>array('xharmer_event'=>1,'randver1'=>$char,'randver2'=>0,'skill'=>array('npc_overrainbow','existing'),'quest'=>array('keep'=>1),'sendo'=>array('gentle'=>array('QD1'=>1))));
	foreach(range(0,6) as $i) { $p['itm'.$i]=$p['itmk'.$i]=$p['itmsk'.$i]=''; $p['itme'.$i]=$p['itms'.$i]=0; $p['itmpara'.$i]=array(); }
	return $p;
}
$p=platform_player(); ending_routes_xharmer($p,true);
platform_assert($p['itm1']==='便携式化身平台' && $p['itmpara1']['platformTransformTime']===40,'接受邀请获得40次有效行动的便携平台');
ending_routes_xharmer($p,true); platform_assert($p['itm2']==='','重复选择不会领取第二台平台');
$p['itmpara1']['platformSkills'][]='wc_continue'; $before=$p;
item_platform(1,$p);
platform_assert($p['hp']===$p['mhp'] && $p['sp']===$p['msp'] && $p['hp']>500,'实际NPC模板展开生命、体力与熟练度');
platform_assert($p['name']===$before['name'] && $p['pid']===1 && $p['pls']===5 && $p['pgroup']===0,'化身不覆盖身份、位置与账户');
platform_assert(in_array('npc_7colour',$p['clbpara']['skill'],true) && !in_array('wc_continue',$p['clbpara']['skill'],true),'平台只挂载六技能白名单');
platform_assert($p['clbpara']['quest']===$before['clbpara']['quest'] && $p['clbpara']['sendo']===$before['clbpara']['sendo'],'任务与送行进度完整保留');
platform_assert(strpos($log,'稳定度阈值')===false,'不向玩家显示精确稳定度');
for($i=0;$i<39;$i++) platform_tick($p,'search');
platform_assert(!empty($p['clbpara']['platform']['active']),'前39次有效行动保持投射');
$p=json_decode(json_encode($p),true); platform_tick($p,'move');
platform_assert(empty($p['clbpara']['platform']) && $p['hp']===500 && $p['wp']===20,'重新加载后第40次行动准确恢复原始属性');
platform_assert(in_array('npc_overrainbow',$p['clbpara']['skill'],true) && in_array('existing',$p['clbpara']['skill'],true) && !in_array('npc_7colour',$p['clbpara']['skill'],true),'只卸载本次新增技能，保留本来持有的白名单技能');
$p=platform_player(1); ending_routes_xharmer($p,true); item_platform(1,$p);
platform_assert(in_array('npc_wrelease',$p['clbpara']['skill'],true) && in_array('npc_flying',$p['clbpara']['skill'],true),'ORIHIME化身挂载解放与飞行');
$p['clbpara']['ex_lock']=1; $p['horizon']=2; restorePlayerOriginalData($p);
platform_assert($p['horizon']===2 && empty($p['clbpara']['platform']),'解除投射不会解除 EX 视界锁');
$p=platform_player(2); ending_routes_xharmer($p,true); item_platform(1,$p);
platform_assert(in_array('npc_mecstasy',$p['clbpara']['skill'],true) && in_array('npc_perfectspy',$p['clbpara']['skill'],true),'SAYA化身挂载来潮与勇谍');
$p['clbpara']['severe_inf']['nightmare']=1; $p['horizon']=1; restorePlayerOriginalData($p);
platform_assert($p['horizon']===1,'噩梦状态下解除投射不切换视界');
$p=platform_player(); ending_routes_xharmer($p,false);
platform_assert($p['itm1']==='' && !empty($p['clbpara']['xharmer_chosen']),'拒绝后不重复发放');
echo "Ending platform regression complete.\n";
