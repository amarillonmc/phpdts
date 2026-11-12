<?php
if(!defined('IN_GAME')) exit('Access Denied');

function dissociation_active($data)
{
	return ending_routes_enabled() && (int)($data['pgroup'] ?? 0)===3 && !empty($data['clbpara']['dissociation_deadline']);
}

function dissociation_alive()
{
	global $db,$tablepre;
	if(!ending_routes_enabled()) return false;
	return (bool)$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=0 AND hp>0 AND pgroup=3"),0);
}

// 访问游戏时结算所有入城者；离线不能把已到期的投影永久留在房间。
function dissociation_tick(&$current=NULL)
{
	global $db,$tablepre,$log;
	if(!ending_routes_enabled()) return;
	$result=$db->query("SELECT pid FROM {$tablepre}players WHERE type=0 AND hp>0 AND pgroup=3 ORDER BY pid");
	while($row=$db->fetch_array($result)) {
		$is_current=$current && (int)$current['pid']===(int)$row['pid'];
		if($is_current) $person=&$current; else $person=fetch_playerdata_by_pid($row['pid']);
		if(!$person) continue;
		if($is_current && function_exists('race_ai_sync_master')) race_ai_sync_master($person);
		$oldlog=$log;
		dissociation_timeout($person);
		if(!$is_current) $log=$oldlog;
		unset($person);
	}
}

function dissociation_timeout(&$data)
{
	global $now,$log,$db,$tablepre;
	if(!dissociation_active($data) || $data['hp']<=0 || $now<(int)$data['clbpara']['dissociation_deadline']) return false;
	$log.='75 分钟的时空锚定归零。湾城的街道在噪波中剥落，你伸出指尖，已化作飞散的代码。黑暗里有人叹息：「看来……你没能走到最后。」<br>';
	if(!empty($data['clbpara']['ex_lock']) && erc_config('omicron_taunt',true) && $db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=41 AND name='OMICRON' AND hp>0 AND pgroup=3"),0)) {
		$data['clbpara']['dissociation_taunt']=1;
		$log.='「你的时间，我收下了。」黑羽掠过最后一丝光。<br>';
	}
	include_once GAME_ROOT.'./include/state.func.php';
	death('dissociation_timeout','',0,'',$data);
	return true;
}

function dissociation_enter(&$data,$itmn)
{
	global $gamevars,$now,$log;
	if(!ending_routes_enabled()) return false;
	if(dissociation_active($data)) { $log.='你已经身处湾城投影。<br>'; return true; }
	$s=$data['clbpara']['sendo'] ?? array(); $flags=$s['gentle'] ?? array();
	if(count($s['done'] ?? array())<7 || count($flags)<4) { $log.='七封送行尚未全部完成，或温柔送行尚不足四次。请到动物大排档补做。<br>'; return true; }
	$route=count($flags)===7 ? 'true' : 'phantom';
	// 天赋树的 npc_* 技能不在 wc_* 辅助技能名单内，不应触发分流。
	if($route==='true' && wheelchair_penalty_count($data)>0) {
		$route='phantom';
		$log.='【中枢协议警报】「扶手还没松，就急着摸世界的底牌？先走这边吧。」你将转入【伪·解离】路线。<br>';
	}
	if(!empty($gamevars['dissociation'][$route]['collapsed'])) { $log.='这条路线的最终守望者已全部倒下，投影正在坍缩。通票仍留在你手中。<br>'; return true; }
	if(empty($gamevars['dissociation'][$route])) dissociation_start($route);
	// 先恢复本体种族，再判断是否须将 AI 全部机体一并带入。
	if(!empty($data['clbpara']['platform']['active'])) {
		include_once GAME_ROOT.'./include/game/item.platform.php';
		restorePlayerOriginalData($data);
	}
	$deadline=$now+(int)erc_config('duration',4500);
	if(function_exists('race_ai_enter_city') && (int)$data['race']===5) race_ai_enter_city($data,$route,$deadline);
	else dissociation_strip($data,$route,$deadline);
	addnews($now,'dissociation_enter',$data['name'],$route);
	$log.='你放下了外部装备，走进湾城投影。旧红杀应急补给已送达，时空锚定开始倒数。<br>';
	save_gameinfo();
	return true;
}

function dissociation_strip(&$data,$route,$deadline)
{
	global $nosta;
	include_once GAME_ROOT.'./include/game/item.platform.php';
	if(!empty($data['clbpara']['platform']['active'])) restorePlayerOriginalData($data);
	foreach(array('wep','wep2','arb','arh','ara','arf','art') as $slot) {
		if($slot==='arb' && (int)$data['race']===2 && strpos($data['arb'],'妖精羽翼·')===0) continue;
		foreach(array('','k','e','s','sk','para') as $suffix) $data[$slot.$suffix]=in_array($suffix,array('e','s')) ? 0 : '';
	}
	for($i=0;$i<=6;$i++) foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $data[$key.$i]=in_array($key,array('itme','itms')) ? 0 : '';
	$data['pgroup']=3; $data['pls']=120; $data['bid']=0; $data['action']='';
	unset($data['clbpara']['smeo'],$data['clbpara']['enemy'],$data['clbpara']['animal_debuff']);
	$data['clbpara']['dissociation_deadline']=(int)$deadline;
	$data['clbpara']['dissociation_route']=$route;
	if($route==='true') { $data['horizon']=2; $data['clbpara']['ex_lock']=1; }
	$skills=array(); foreach(array('p','k','g','c','d','f') as $k) $skills[$k]=(int)$data['w'.$k];
	arsort($skills); $kind=key($skills);
	$names=array('p'=>'裂甲拳套','k'=>'斩钢长刀','g'=>'旧式突击步枪','c'=>'巡风飞刃','d'=>'破城炸药','f'=>'共鸣法杖');
	$data['wep']='旧红杀·'.$names[$kind]; $data['wepk']='W'.strtoupper($kind); $data['wepe']=1200; $data['weps']=($kind==='g' ? 60 : 300); $data['wepsk']='n';
	$data['weppara']=array('dissociation_growth'=>1,'growth_skill'=>$kind,'growth_value'=>$skills[$kind]);
	foreach(array('arb'=>'战术外套','arh'=>'防护头盔','ara'=>'义肢护臂','arf'=>'义肢战靴') as $slot=>$name) {
		if($slot==='arb' && (int)$data['race']===2) continue;
		$data[$slot]=$name; $data[$slot.'k']='D'.strtoupper(substr($slot,2)); $data[$slot.'e']=400; $data[$slot.'s']=150; $data[$slot.'sk']='';
	}
	ending_routes_give($data,array('itm'=>'强效急救剂','itmk'=>'HB','itme'=>5000,'itms'=>5));
	ending_routes_give($data,array('itm'=>'净灵水','itmk'=>'Y','itme'=>1,'itms'=>2));
	if($kind==='g') ending_routes_give($data,array('itm'=>'步枪弹匣','itmk'=>'GB','itme'=>1,'itms'=>120));
}

function dissociation_spawn($type,$sub,$route,$place)
{
	return addnpc($type,$sub,1,0,array('pgroup'=>3,'pls'=>$place,'horizon'=>$route==='true' ? 2 : 0,'clbpara'=>array('dissociation_route'=>$route,'er_sub'=>$sub)));
}

function dissociation_start($route)
{
	global $gamevars,$now,$db,$tablepre;
	$gamevars['dissociation'][$route]=array('stage'=>0,'stage_at'=>$now,'dead'=>array());
	if($route==='phantom') {
		$draw=range(0,6); shuffle($draw); $draw=array_slice($draw,0,4);
		$gamevars['dissociation'][$route]['animals']=$draw;
		foreach($draw as $i=>$sub) dissociation_spawn(37,$sub,$route,121+$i);
		dissociation_spawn(35,0,$route,128); dissociation_spawn(35,1,$route,128);
	} else dissociation_spawn(40,0,$route,121);
	if(empty($gamevars['dissociation_supplies'])) {
		$gamevars['dissociation_supplies']=1;
		foreach(range(120,129) as $place) foreach(array(array('湾城急救箱','HB',5000,3,''),array('凝固汽油弹','WD',2000,20,'u'),array('穿甲弩','WB',1800,100,'N'),array('净灵水','Y',1,1,'')) as $item) {
			$db->array_insert($tablepre.'mapitem',array('pls'=>$place,'itm'=>$item[0],'itmk'=>$item[1],'itme'=>$item[2],'itms'=>$item[3],'itmsk'=>$item[4],'itmpara'=>''));
		}
	}
}

function dissociation_npc_dead(&$victim,&$killer)
{
	global $gamevars,$now,$db,$tablepre;
	$route=$victim['clbpara']['dissociation_route'] ?? '';
	if(!$route || empty($gamevars['dissociation'][$route])) return;
	$s=&$gamevars['dissociation'][$route]; $pid=(int)$victim['pid'];
	if(isset($s['dead'][$pid])) return;
	$s['dead'][$pid]=1;
	$type=(int)$victim['type'];
	if($route==='phantom' && $type===35) {
		if(!$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=35 AND hp>0 AND pid<>$pid"),0)) $s['collapsed']=1;
		return;
	}
	if($route!=='true') return;
	if($type===40 || ($type===12 && $victim['name']==='Dark Force')) {
		$stage=(int)$s['stage']; $s['stage']=$stage+1;
		$elapsed=max(0,$now-$s['stage_at']);
		$target=(int)erc_config('stage_time_target',900);
		$extra=(int)floor(max(0,$target-$elapsed)*erc_config('stage_time_ratio',.5));
		if(erc_config('stage_time_direction','fast')==='slow') $extra=-(int)floor(max(0,$elapsed-$target)*erc_config('stage_time_ratio',.5));
		if($extra) {
			$rows=$db->query("SELECT pid FROM {$tablepre}players WHERE type=0 AND hp>0 AND pgroup=3 AND horizon=2");
			while($row=$db->fetch_array($rows)) {
				if((int)$row['pid']===(int)$killer['pid']) $p=&$killer;
				// 协战者可能代当前玩家击破目标；更新请求内存，避免指令末尾覆盖加时。
				elseif(isset($GLOBALS['pdata']['pid']) && (int)$row['pid']===(int)$GLOBALS['pdata']['pid']) $p=&$GLOBALS['pdata'];
				else $p=fetch_playerdata_by_pid($row['pid']);
				if(!empty($p['clbpara']['dissociation_deadline'])) $p['clbpara']['dissociation_deadline']+=$extra;
				player_save($p); unset($p);
			}
		}
		$s['stage_at']=$now;
		if($stage<2) dissociation_spawn(40,$stage+1,'true',122+$stage);
		elseif($stage===2) dissociation_spawn(12,0,'true',124);
		elseif($stage===3) {
			for($i=0;$i<8;$i++) dissociation_spawn(39,$i,'true',120+$i);
			addnews($now,'retro_jiaotou');
		}
	} elseif($type===39 && empty($s['pillars'])) {
		if(!$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=39 AND hp>0 AND pid<>$pid"),0)) {
			$s['pillars']=1; dissociation_spawn(41,0,'true',128); dissociation_spawn(41,1,'true',129); addnews($now,'dissociation_final');
		}
	} elseif($type===41) {
		if(!$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=41 AND hp>0 AND pid<>$pid"),0)) $s['collapsed']=1;
	}
}

function dissociation_item(&$data,$itmn)
{
	global $log;
	if(!ending_routes_enabled()) return false;
	$name=$data['itm'.$itmn];
	if($name==='武器强化补丁') {
		if(empty($data['weps']) || $data['wepk']==='WN') { $log.='请先装备需要强化的武器。<br>'; return true; }
		$data['wepe']+=1000;
		if(--$data['itms'.$itmn]<=0) destory_single_item($data,$itmn);
		$log.='强化补丁融入了武器，效果提高了1000点。<br>'; return true;
	}
	if($name==='【湾城通票】') return dissociation_enter($data,$itmn);
	if(in_array($name,array('净灵水','除厄御守','清醒药剂','镇静药剂'),true)) {
		// 原有 Cw 清醒药剂仍走普通解除混乱逻辑，附带清除噩梦而不重复消耗。
		if($name==='清醒药剂' && $data['itmk'.$itmn]==='Cw') { severe_cure($data,array('nightmare')); return false; }
		if($name==='清醒药剂') severe_cure($data,array('nightmare'));
		elseif($name==='镇静药剂') severe_cure($data,array('frenzy'));
		else severe_cleanse($data);
		if(--$data['itms'.$itmn]<=0) destory_single_item($data,$itmn);
		$log.='药力缓缓流过身体，困扰你的异常正在消退。<br>'; return true;
	}
	return false;
}

function dissociation_rest_bonus($data,$elapsed)
{
	global $db,$tablepre,$now;
	if(!dissociation_active($data) || (int)$data['horizon']!==2 || $elapsed<=0) return 0;
	$pid=(int)$data['pid']; $place=(int)$data['pls'];
	$peers=$db->query("SELECT endtime FROM {$tablepre}players WHERE type=0 AND hp>0 AND pid<>$pid AND pgroup=3 AND pls=$place AND horizon=2 AND state IN (1,2,3)");
	$shared=0;
	while($peer=$db->fetch_array($peers)) $shared=max($shared,min($elapsed,max(0,$now-(int)$peer['endtime'])));
	return $shared*(erc_config('ex_rest_bonus',1.5)-1);
}

function dissociation_growth(&$data)
{
	if(!ending_routes_enabled() || empty($data['weps'])) return;
	$p=get_itmpara($data['weppara']);
	if(empty($p['dissociation_growth'])) return;
	$key='w'.($p['growth_skill'] ?? 'p'); $current=(int)($data[$key] ?? 0);
	if($current>(int)$p['growth_value']) { $data['wepe']+=2*($current-(int)$p['growth_value']); $p['growth_value']=$current; $data['weppara']=$p; }
}
