<?php
if(!defined('IN_GAME')) exit('Access Denied');

function sendo_cfg()
{
	list($all,)=get_questcfg();
	return array_intersect_key($all,array_flip(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7')));
}

function sendo_complete(&$data,$qid,$gentle)
{
	global $log;
	if(empty($data['clbpara']['sendo']) || !preg_match('/^QD[1-7]$/',$qid)) return;
	$s=&$data['clbpara']['sendo'];
	if(($s['reset_cycle'][$qid] ?? 0)>=($GLOBALS['gamevars']['sendo']['cycle'] ?? 1)) return;
	if(!empty($s['gentle'][$qid]) || (!$gentle && !empty($s['done'][$qid]))) return;
	$s['done'][$qid]=1;
	if($gentle) $s['gentle'][$qid]=1;
	$cfg=sendo_cfg();
	$log.='<span class="'.($gentle?'lime':'yellow').'">'.$cfg[$qid][$gentle?'gentle':'rough'].'</span><br>';
}

function sendo_spawn($sub,$qid,$place,$extra=array())
{
	global $gamevars;
	$extra['pls']=$place; $extra['pgroup']=0;
	$extra['clbpara']=array_merge($extra['clbpara'] ?? array(),array('sendo_qid'=>$qid,'sendo_cycle'=>$gamevars['sendo']['cycle'],'er_sub'=>$sub));
	return addnpc(38,$sub,1,0,$extra);
}

// 复用开局的七名成员。个人完成不撤走其他参与者仍需要的保护对象。
function sendo_sync_hubs($current=NULL,$finish=false)
{
	global $db,$tablepre,$gamevars,$now;
	$w=&$gamevars['sendo']; $needed=array(); $bait=false;
	if(!$finish) {
		$players=array();
		$res=$db->query("SELECT pid,clbpara FROM {$tablepre}players WHERE type=0 AND hp>0");
		while($row=$db->fetch_array($res)) if(!$current || (int)$row['pid']!==(int)$current['pid']) $players[]=get_clbpara($row['clbpara']);
		if($current && $current['hp']>0) $players[]=get_clbpara($current['clbpara']);
		foreach($players as $para) {
			$s=$para['sendo'] ?? array();
			if(!$s) continue;
			foreach(array('QD2','QD4','QD7') as $qid) if(empty($s['done'][$qid]) && ($s['reset_cycle'][$qid] ?? 0)<$w['cycle']) {
				$needed[$qid]=true;
				if($qid==='QD4' && !empty($s['bait'])) $bait=true;
			}
		}
	}
	$hunter_alive=false;
	if($bait) {
		$res=$db->query("SELECT clbpara FROM {$tablepre}players WHERE type=38 AND hp>0");
		while($row=$db->fetch_array($res)) {
			$p=get_clbpara($row['clbpara']);
			if(($p['sendo_qid'] ?? '')==='QD4' && ($p['sendo_cycle'] ?? 0)===$w['cycle']) $hunter_alive=true;
		}
	}
	$res=$db->query("SELECT pid FROM {$tablepre}players WHERE type=36");
	while($row=$db->fetch_array($res)) {
		$npc=fetch_playerdata_by_pid($row['pid']); $sub=(int)($npc['clbpara']['er_sub'] ?? -1); $place=34; $on_task=false;
		if($sub===1) {
			$w['dante']=(int)$npc['pid'];
			if(!empty($needed['QD2']) && empty($w['dante_lost'])) { $place=$w['points'][1]; $on_task=true; }
		} elseif($sub===3 && $hunter_alive) { $place=$w['points'][3]; $on_task=true; }
		elseif($sub===6 && !empty($needed['QD7']) && $now<$w['bye_end']) { $place=$w['bye']; $on_task=true; }
		if((int)$npc['pls']===(int)$place && $npc['hp']>0 && ($on_task || ((int)$npc['hp']===(int)$npc['mhp'] && empty($npc['state'])))) continue;
		$npc['pls']=$place; $npc['pgroup']=0; $npc['bid']=0; $npc['action']='';
		if(!$on_task) {
			$npc['hp']=$npc['mhp']; $npc['sp']=$npc['msp']; $npc['state']=0;
			$npc['pose']=7; $npc['rage']=0; $npc['inf']='';
		}
		player_save($npc);
	}
}

function sendo_start_cycle($current=NULL)
{
	global $gamevars,$now,$db,$tablepre,$arealist,$areanum,$areatime,$areaadd;
	$safe=array_values(array_diff(array_slice($arealist,$areanum+1),array(0,32,33,34)));
	if(!$safe) $safe=array(34);
	shuffle($safe); $points=array();
	for($i=0;$i<7;$i++) $points[$i]=$safe[$i%count($safe)];
	$cycle=1+(int)($gamevars['sendo']['cycle'] ?? 0);
	$warn=array_slice($arealist,$areanum+1,max(1,(int)$areaadd));
	$bye=($warn && $areatime>$now) ? $warn[array_rand($warn)] : $points[6];
	$gamevars['sendo']=array('cycle'=>$cycle,'start'=>$now,'end'=>$now+180,'threat_at'=>$now,'points'=>$points,'wave'=>0,'geese'=>array(),'leader_dead'=>0,'bye'=>$bye,'bye_end'=>$warn && $areatime>$now ? $areatime : $now+180);
	$w=&$gamevars['sendo'];
	// 清理上一周期剩余的事件实体；玩家重置不走这里，不会单独补怪。
	$db->query("DELETE FROM {$tablepre}players WHERE type=38");
	for($i=0;$i<3;$i++) $w['geese']=array_merge($w['geese'],sendo_spawn(0,'QD1',$points[0],array('name'=>'迁徙的加拿大鹅','mhp'=>3000,'clbpara'=>array('sendo_role'=>'goose'))));
	sendo_spawn(0,'QD1',$points[0],array('name'=>'猎人团首领','icon'=>'ending_routes/type38_6.jpg','mhp'=>10000,'clbpara'=>array('sendo_role'=>'leader')));
	sendo_spawn(2,'QD3',$points[2]);
	sendo_spawn(3,'QD4',$points[3],array('clbpara'=>array('sendo_hidden'=>60)));
	for($i=0;$i<3;$i++) sendo_spawn(4,'QD5',$points[4]);
	sendo_sync_hubs($current);
	foreach($safe as $place) $db->array_insert($tablepre.'mapitem',array('itm'=>'备用电池','itmk'=>'B','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>'','pls'=>$place));
	save_gameinfo();
}

function sendo_tick(&$current=NULL)
{
	global $gamevars,$now,$db,$tablepre,$log;
	if(!ending_routes_enabled() || empty($gamevars['sendo_open'])) return;
	if(empty($gamevars['sendo'])) sendo_start_cycle($current);
	$w=&$gamevars['sendo'];
	$steps=min(6,(int)floor((min($now,$w['end'])-($w['threat_at'] ?? $now))/30));
	if($steps>0) {
		$w['threat_at']+=30*$steps;
		foreach(array('QD1','QD2') as $qid) {
			if($qid==='QD2' && !empty($w['dante_lost'])) continue;
			$place=(int)$w['points'][$qid==='QD1'?0:1];
			$targets=$qid==='QD1' ? $w['geese'] : array($w['dante']);
			$enemies=$db->query("SELECT pid FROM {$tablepre}players WHERE type=38 AND hp>0 AND pls=$place");
			$pressure=0;
			while($row=$db->fetch_array($enemies)) { $enemy=fetch_playerdata_by_pid($row['pid']); if(($enemy['clbpara']['sendo_qid'] ?? '')===$qid && ($enemy['clbpara']['sendo_role'] ?? '')!=='goose') $pressure++; }
			if($pressure) foreach($targets as $target) {
				$npc=fetch_playerdata_by_pid($target); if(!$npc || $npc['hp']<=0 || (int)$npc['pls']!==$place) continue;
				$npc['hp']=max(0,$npc['hp']-$steps*$pressure*150); if(!$npc['hp']) { $npc['state']=13; if($qid==='QD1') $w['goose_lost']=1; }
				if(!$npc['hp'] && $qid==='QD2') $w['dante_lost']=1;
				player_save($npc);
			}
		}
		save_gameinfo();
	}
	// 周期结束先给本周期参与者结算，再开下一周期，不把历史无限塞进 gamevars。
	if($now >= $w['end']) {
		$rows=$db->query("SELECT pid FROM {$tablepre}players WHERE type=0 AND hp>0");
		while($row=$db->fetch_array($rows)) {
			$is_current=$current && (int)$current['pid']===(int)$row['pid'];
			if($is_current) $p=&$current; else $p=fetch_playerdata_by_pid($row['pid']);
			$oldlog=$log;
			if(!empty($p['clbpara']['sendo'])) {
				$personal=&$p['clbpara']['sendo'];
				if(($personal['guard_cycle'] ?? 0)===$w['cycle']) {
					$dante=fetch_playerdata_by_pid($w['dante']);
					$gentle=empty($personal['done']['QD2']) && empty($w['dante_lost']) && $dante && $dante['hp']>0 && (int)$dante['pls']===(int)$w['points'][1] && ($personal['guard_kills'] ?? 0)>=2 && (int)$p['pgroup']===0 && (int)$p['pls']===(int)$w['points'][1] && $w['end']-($personal['guard_start'] ?? $w['end'])>=150;
					if($gentle) sendo_complete($p,'QD2',true);
				}
				if(empty($personal['done']['QD7']) && $now>=$w['bye_end']) sendo_complete($p,'QD7',false);
			}
			if(!$is_current) { player_save($p); $log=$oldlog; }
			unset($p,$personal);
		}
		sendo_sync_hubs($current,true);
		sendo_start_cycle($current);
	}
	$wave=min(3,1+(int)floor(($now-$w['start'])/45));
	while($w['wave']<$wave) {
		$w['wave']++;
		for($i=0;$i<2;$i++) {
			sendo_spawn(0,'QD1',$w['points'][0],array('name'=>'猎人团员','icon'=>'ending_routes/type38_5.jpg','mhp'=>4500,'clbpara'=>array('sendo_role'=>'hunter')));
			sendo_spawn(1,'QD2',$w['points'][1]);
		}
		save_gameinfo();
	}
	sendo_sync_hubs($current);
}

function sendo_action(&$data,$act)
{
	global $gamevars,$now,$db,$tablepre,$log;
	if(!ending_routes_enabled() || empty($data['clbpara']['sendo'])) return;
	if($data['pgroup']!=0) { unset($data['clbpara']['sendo']['guard_start']); return; }
	sendo_tick($data);
	$w=$gamevars['sendo']; $s=&$data['clbpara']['sendo'];
	if((int)$data['pls']===(int)$w['points'][1]) {
		if(($s['guard_cycle'] ?? 0)!==$w['cycle']) { $s['guard_cycle']=$w['cycle']; $s['guard_start']=$now; $s['guard_kills']=0; }
		elseif(!isset($s['guard_start'])) $s['guard_start']=$now;
	} else unset($s['guard_start']);
	if(!empty($s['lantern_route']) && empty($s['done']['QD5'])) {
		if($data['ara']!=='引路灯' || !$data['aras']) $s['lantern_broken']=1;
		$next=(int)($s['lantern_step'] ?? 0);
		$repeat_point=$next===0 || ($s['lantern_route'][$next] ?? null)===($s['lantern_route'][$next-1] ?? null);
		if(($act==='move' || ($act==='search' && $repeat_point)) && isset($s['lantern_route'][$next]) && (int)$data['pls']===(int)$s['lantern_route'][$next]) {
			$s['lantern_step']=$next+1;
			$log.='你到达了引路灯照亮的检查点。<br>';
			if($next+1===count($s['lantern_route'])) sendo_complete($data,'QD5',empty($s['lantern_broken']));
		}
	}
	if($act==='search' && (int)$data['pls']===(int)$w['bye'] && $now<$w['bye_end'] && empty($s['done']['QD7'])) {
		$data['clbpara']['dialogue']='sendo_goodbye';
		sendo_complete($data,'QD7',true);
	}
	sendo_sync_hubs($data);
}

function sendo_npc_dead(&$victim,&$killer)
{
	global $gamevars,$db,$tablepre,$now;
	$qid=$victim['clbpara']['sendo_qid'] ?? '';
	if(!$qid || empty($gamevars['sendo']) || ($victim['clbpara']['sendo_cycle'] ?? 0)!==$gamevars['sendo']['cycle']) return;
	$w=&$gamevars['sendo']; $s=&$killer['clbpara']['sendo'];
	if($qid==='QD1') {
		$role=$victim['clbpara']['sendo_role'] ?? '';
		if($role==='goose') $w['goose_lost']=1;
		if($role==='leader') {
			$w['leader_dead']=1;
			$alive=$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=38 AND hp>0 AND pls=".(int)$w['points'][0]." AND pid<>".(int)$victim['pid']." AND name='猎人团员'"),0);
			sendo_complete($killer,'QD1',empty($w['goose_lost']) && $w['wave']>=3 && !$alive);
		}
	} elseif($qid==='QD2' && !empty($s)) {
		if(($s['guard_cycle'] ?? 0)!==$w['cycle']) { $s['guard_cycle']=$w['cycle']; $s['guard_start']=$now; $s['guard_kills']=0; }
		$s['guard_kills']++;
		if($s['guard_kills']>=6 && $now<$w['end']) sendo_complete($killer,'QD2',false);
	} elseif($qid==='QD3') sendo_complete($killer,'QD3',false);
	elseif($qid==='QD4') sendo_complete($killer,'QD4',!empty($s['located']) && $s['located']===(int)$victim['pid'] && empty($s['bait']));
	elseif($qid==='QD5') {
		$s['mist_kills']=1+(int)($s['mist_kills'] ?? 0);
		if(($killer['ara'] ?? '')!=='引路灯' || empty($killer['aras'])) $s['lantern_broken']=1;
		if($s['mist_kills']>=3 && !empty($s['lantern_broken'])) sendo_complete($killer,'QD5',false);
	}
}

function sendo_item(&$data,$itmn)
{
	global $gamevars,$log,$db,$tablepre;
	if(!ending_routes_enabled()) return false;
	$item=$data['itm'.$itmn];
	if($item==='【动物团的七封请柬】') {
		if(empty($data['clbpara']['sendo'])) $data['clbpara']['sendo']=array('done'=>array(),'gentle'=>array());
		$gamevars['sendo_open']=1; sendo_tick($data); destory_single_item($data,$itmn); save_gameinfo();
		$log.='七封请柬同时展开。代码洪流里的动物大排档正在等你。<br>'; return true;
	}
	if(!in_array($item,array('捕绳','侦探眼镜'),true)) return false;
	$tool_para=get_itmpara($data['itmpara'.$itmn] ?? '');
	if(!empty($tool_para['QuestID']) && !in_array($tool_para['QuestID'],array('QD3','QD4'),true)) return false;
	if(empty($data['clbpara']['sendo']) || $data['pgroup']!=0) { $log.='请先阅读动物团的请柬。<br>'; return true; }
	$sub=$item==='捕绳' ? 2 : 3;
	$qid=$sub===2 ? 'QD3' : 'QD4';
	if(($data['clbpara']['sendo']['reset_cycle'][$qid] ?? 0)>=($gamevars['sendo']['cycle'] ?? 1)) { $log.='这一项已登记重做，请等待下一轮公共送行。<br>'; return true; }
	$res=$db->query("SELECT pid FROM {$tablepre}players WHERE type=38 AND hp>0 AND pls=".(int)$data['pls']);
	while($row=$db->fetch_array($res)) {
		$npc=fetch_playerdata_by_pid($row['pid']);
		if(!empty($npc['clbpara']['sendo_captured'])) continue;
		if(($npc['clbpara']['er_sub'] ?? -1)!==$sub) continue;
		if($item==='捕绳') {
			$npc['hp']=1; $npc['clbpara']['sendo_captured']=1; $npc['pls']=34;
			player_save($npc); sendo_complete($data,'QD3',true);
		} else {
			$data['clbpara']['sendo']['located']=(int)$npc['pid'];
			check_add_searchmemory($npc['pid'],'enemy',$npc['name'],$data);
			$log.='侦探眼镜锁定了伪装后的猎手，目标已记入视野。<br>';
		}
		if(--$data['itms'.$itmn]<=0) destory_single_item($data,$itmn);
		return true;
	}
	$log.='这里没有可以使用它的目标。<br>'; return true;
}

function sendo_command(&$data,$command)
{
	global $log,$cmd,$mode,$gamevars,$now,$plsinfo;
	if(!ending_routes_enabled() || strpos($command,'sendo')!==0) return false;
	$mode='command';
	if($data['pgroup']!=0 || $data['pls']!=34 || empty($data['clbpara']['sendo'])) { $log.='请带着七封请柬，在代码洪流进入动物大排档。<br>'; return true; }
	sendo_tick($data); $s=&$data['clbpara']['sendo'];
	$command_task='';
	if($command==='sendo_batteries' || strpos($command,'sendo_donate_')===0) $command_task='QD6';
	elseif($command==='sendo_tool_rope') $command_task='QD3';
	elseif(in_array($command,array('sendo_tool_glasses','sendo_bait'),true)) $command_task='QD4';
	elseif($command==='sendo_tool_lantern') $command_task='QD5';
	if($command_task && ($s['reset_cycle'][$command_task] ?? 0)>=$gamevars['sendo']['cycle']) {
		$log.='这一项已登记重做，请等待下一轮公共送行；物品没有消耗。<br>';
		$command='sendo';
	}
	if(preg_match('/^sendo_reset_(QD[1-7])$/',$command,$m)) {
		if(empty($s['gentle'][$m[1]])) {
			unset($s['done'][$m[1]]); $s['reset_cycle'][$m[1]]=$gamevars['sendo']['cycle'];
			$reset_keys=array('QD2'=>array('guard_cycle','guard_start','guard_kills'),'QD4'=>array('located','bait'),'QD5'=>array('lantern_route','lantern_step','lantern_broken','mist_kills'),'QD6'=>array('batteries','donate_confirm'));
			foreach($reset_keys[$m[1]] ?? array() as $key) unset($s[$key]);
			$log.='已登记重做。下一轮公共送行开始后可重新尝试。<br>';
		}
	} elseif(preg_match('/^sendo_tool_(rope|glasses|lantern)$/',$command,$m)) {
		$kind=$m[1]; $cycle=$gamevars['sendo']['cycle'];
		if(($s['tools'][$kind] ?? 0)!==$cycle) {
			$s['tools'][$kind]=$cycle;
			$item=$kind==='rope' ? array('itm'=>'捕绳','itmk'=>'Y','itms'=>1) : ($kind==='glasses' ? array('itm'=>'侦探眼镜','itmk'=>'Y','itms'=>3) : array('itm'=>'引路灯','itmk'=>'DA','itme'=>1,'itms'=>3));
			$item['itmpara']=array('IsQuestItem'=>1,'QuestID'=>$kind==='rope'?'QD3':($kind==='glasses'?'QD4':'QD5'));
			ending_routes_give($data,$item);
			if($kind==='lantern') {
				$safe=get_safe_plslist(0);
				$points=array_values(array_diff($safe,array(0,32,33,34))); shuffle($points);
				if(count($points)<3) { $extra=array_values(array_diff($safe,$points,array(0,34))); shuffle($extra); $points=array_merge($points,$extra); }
				if(!$points) $points=array(34);
				$s['lantern_route']=array();
				for($i=0;$i<3;$i++) $s['lantern_route'][]=$points[$i%count($points)];
				$s['lantern_route'][]=34; $s['lantern_step']=0; unset($s['lantern_broken']);
				if(count(array_unique($s['lantern_route']))<4) $log.='安全地点已不多，部分检查点会重复。连续两处是同一地点时，可在原地探索逐一确认；第一处就在脚下时也可探索确认。<br>';
			}
		} else $log.='这轮送行的工具已经领过。<br>';
	} elseif($command==='sendo_bait') {
		$s['bait']=1; $log.='你请 Holly 站到开阔地作为诱饵。她勉强点了点头。<br>';
	} elseif($command==='sendo_batteries') {
		if(empty($s['done']['QD6'])) {
			$total=0; for($i=1;$i<=6;$i++) if(strpos($data['itm'.$i],'电池')!==false) $total+=max(0,(int)$data['itms'.$i]);
			if($total<3) $log.='电台还需要三枚电池。<br>';
			else {
				$left=3; for($i=1;$i<=6 && $left;$i++) if(strpos($data['itm'.$i],'电池')!==false) { $n=min($left,max(0,(int)$data['itms'.$i])); $data['itms'.$i]-=$n; $left-=$n; if(!$data['itms'.$i]) destory_single_item($data,$i); }
				$s['batteries']=1; sendo_complete($data,'QD6',false);
			}
		}
	} elseif(preg_match('/^sendo_donate_([1-6])$/',$command,$m)) {
		$i=(int)$m[1];
		if(!empty($s['batteries']) && !empty($data['itms'.$i]) && preg_match('/^[WDA]/',$data['itmk'.$i]) && empty(get_itmpara($data['itmpara'.$i])['IsQuestItem'])) {
			$s['donate_confirm']=array('slot'=>$i,'item'=>array($data['itm'.$i],$data['itmk'.$i],$data['itme'.$i],$data['itms'.$i],$data['itmsk'.$i],$data['itmpara'.$i]));
		}
	} elseif($command==='sendo_donate_confirm' && !empty($s['donate_confirm']) && empty($s['gentle']['QD6'])) {
		$c=$s['donate_confirm']; $i=$c['slot'];
		if($c['item']===array($data['itm'.$i],$data['itmk'.$i],$data['itme'.$i],$data['itms'.$i],$data['itmsk'.$i],$data['itmpara'.$i])) { destory_single_item($data,$i); sendo_complete($data,'QD6',true); }
		unset($s['donate_confirm']);
	} elseif($command==='sendo_ticket') {
		if(count($s['done'])===7 && empty($s['ticket'])) { $s['ticket']=1; ending_routes_give($data,array('itm'=>'【湾城通票】','itmk'=>'ticket')); $log.='七封送行已落幕。动物团将湾城通票交给了你。<br>'; }
	}
	sendo_sync_hubs($data);
	$sendo_view=sendo_cfg(); $sendo_state=$s; $sendo_world=$gamevars['sendo']; $pdata=$data;
	include template('quest_sendo'); $cmd=ob_get_contents(); ob_clean();
	return true;
}

// 只为页面派生追踪信息，不把公共任务复制进私人 quest.active 或持久化显示文本。
function sendo_tracking($data)
{
	global $gamevars,$now,$plsinfo;
	$s=$data['clbpara']['sendo'] ?? array(); $w=$gamevars['sendo'] ?? array();
	if(!ending_routes_enabled() || !$s || !$w) return array();
	$tasks=array();
	foreach(sendo_cfg() as $qid=>$cfg) {
		$status=!empty($s['gentle'][$qid]) ? '温柔完成' : (!empty($s['done'][$qid]) ? '普通完成' : '进行中');
		$waiting=($s['reset_cycle'][$qid] ?? 0)>=$w['cycle'];
		if($waiting) $status='下轮重做';
		$desc=$cfg['desc']; $progress='';
		$index=(int)substr($qid,2)-1;
		if($index<5) $desc.=' 本轮地点：'.($plsinfo[$w['points'][$index]] ?? '未知地点').'。';
		if($qid==='QD2') {
			$same_cycle=($s['guard_cycle'] ?? 0)===$w['cycle'] && !$waiting;
			$seconds=$same_cycle && isset($s['guard_start']) ? max(0,min($now,$w['end'])-$s['guard_start']) : 0;
			$progress='驻守 '.min(150,$seconds).'/150 秒；击破 '.($same_cycle ? (int)($s['guard_kills'] ?? 0) : 0).'/2';
			if(!empty($w['dante_lost'])) $desc.=' Dante 已退回大排档，温柔送行须等下轮。';
		} elseif($qid==='QD4') $progress=!empty($s['bait']) ? '已选择诱饵' : (!empty($s['located']) ? '已定位猎手' : '尚未定位猎手');
		elseif($qid==='QD5' && !empty($s['lantern_route'])) {
			$step=(int)($s['lantern_step'] ?? 0); $progress='护送 '.$step.'/'.count($s['lantern_route']);
			if(isset($s['lantern_route'][$step])) $desc.=' 下一检查点：'.($plsinfo[$s['lantern_route'][$step]] ?? '未知地点').'。';
			if(!empty($s['lantern_broken'])) $desc.=' 引路灯已损坏或未装备，温柔送行须重做。';
		} elseif($qid==='QD6') $progress=!empty($s['batteries']) ? '电池 3/3；'.(!empty($s['gentle'][$qid]) ? '已捐装备' : '尚未捐装备') : '电池 0/3';
		elseif($qid==='QD7') $desc.=' 告别地点：'.($plsinfo[$w['bye']] ?? '未知地点').'；剩余 '.max(0,$w['bye_end']-$now).' 秒，请前往探索。';
		$tasks[$qid]=array('title'=>$cfg['title'],'status'=>$status,'step_desc'=>$desc,'progress'=>$progress);
	}
	return $tasks;
}
