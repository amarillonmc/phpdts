<?php
if(!defined('IN_GAME')) exit('Access Denied');

// Shared duels cannot live in either player's account. Keep one row per match;
// game.gamevars is TEXT and is also reset by ordinary room initialization.
function fcg_ensure_social_storage()
{
	global $db,$gtablepre;
	$exists=$db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.fcg_sql($gtablepre.'fcg_mirrors'));
	$install=!$db->num_rows($exists);
	$db->query("CREATE TABLE IF NOT EXISTS {$gtablepre}fcg_mirrors (uid INT UNSIGNED PRIMARY KEY, updated INT NOT NULL, data MEDIUMTEXT NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
	$db->query("CREATE TABLE IF NOT EXISTS {$gtablepre}fcg_matches (id CHAR(24) PRIMARY KEY, roomid INT NOT NULL, roomkey VARCHAR(80) NOT NULL, p0 INT UNSIGNED NOT NULL, p1 INT UNSIGNED NOT NULL, updated INT NOT NULL, data MEDIUMTEXT NOT NULL, KEY p0 (p0), KEY p1 (p1), KEY updated (updated)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
	if($install) {
		// Rename only the original seed label; preserve any administrator customization.
		$db->query("UPDATE {$gtablepre}shop_items SET name=".fcg_sql('阿卡夏的共犯')." WHERE reward_type='title' AND reward_payload=".fcg_sql(fcg_json(array('title'=>9105))).' AND name='.fcg_sql('“共犯”'));
		$seeds=require GAME_ROOT.'./gamedata/fcg_shop.php';
		foreach($seeds as $row) if(($row[6]['series'] ?? '')==='vol2') {
			list($id,$category,$name,$description,$price,$type,$payload,$daily,$total)=$row;
			$check=$db->query("SELECT item_id FROM {$gtablepre}shop_items WHERE reward_payload=".fcg_sql(fcg_json($payload)).' LIMIT 1');
			if(!$db->num_rows($check)) $db->query("INSERT INTO {$gtablepre}shop_items (category,name,description,price_credits2,reward_type,reward_payload,daily_limit,total_limit,sort_order) VALUES (".fcg_sql($category).','.fcg_sql($name).','.fcg_sql($description).','.$price.','.fcg_sql($type).','.fcg_sql(fcg_json($payload)).",$daily,$total,$id)");
		}
	}
}

function fcg_room_key($room) { return $room['groomid'].':'.$room['gamenum'].':'.$room['starttime']; }

function fcg_collection($data)
{
	$collection=$data['collection'];
	foreach($data['temp_cards']['cards'] ?? array() as $id=>$count) $collection[$id]=min(7,($collection[$id] ?? 0)+$count);
	return $collection;
}

function fcg_expire_temp(&$data, $user, $now, $room_key)
{
	$t=$data['temp_cards'] ?? array();
	if(!$t || ($t['room']===(int)$user['roomid'] && $t['key']===$room_key && $t['day']===date('Y-m-d',$now))) return;
	$data['temp_cards']=array(); $kept=array(); $counts=array();
	foreach($data['deck'] as $id) {
		$counts[$id]=($counts[$id] ?? 0)+1;
		if($counts[$id]<=($data['collection'][$id] ?? 0)) $kept[]=$id;
	}
	$data['deck']=count($kept)>=25 ? $kept : fcg_starter_deck();
	$data['temp_expired']=true;
}

function fcg_pvp_reward(&$data, &$user, $opponent, $deck, $now, $room_key)
{
	$data['pvp_wins']=($data['pvp_wins'] ?? 0)+1;
	$today=date('Y-m-d',$now); $daily=$data['pvp_daily'] ?? array();
	if(($daily['day'] ?? '')!==$today) $daily=array('day'=>$today,'opponents'=>array());
	$reward=array('pvp'=>true,'embers'=>0,'temp_card'=>null);
	if(count($daily['opponents'])<3 && !in_array((int)$opponent,$daily['opponents'],true)) {
		$data['embers']+=2; $reward['embers']=2; $daily['opponents'][]=(int)$opponent;
	}
	$data['pvp_daily']=$daily;
	if($room_key!=='') {
		$available=fcg_collection($data); $pool=array(); $best=-1; $cards=fcg_cards();
		foreach(array_unique($deck) as $id) if(isset($cards[$id]) && ($available[$id] ?? 0)<7) {
			$value=($cards[$id]['rare']?2:0)+($cards[$id]['upgraded']?1:0);
			if($value>$best) { $pool=array(); $best=$value; }
			if($value===$best) $pool[]=$id;
		}
		if($pool) {
			$id=$pool[random_int(0,count($pool)-1)];
			if(empty($data['temp_cards'])) $data['temp_cards']=array('room'=>(int)$user['roomid'],'key'=>$room_key,'day'=>$today,'cards'=>array());
			$data['temp_cards']['cards'][$id]=($data['temp_cards']['cards'][$id] ?? 0)+1;
			$reward['temp_card']=$id; $data['temp_expired']=false;
		}
	}
	if($data['pvp_wins']>=10) fcg_award_title($user,9121);
	if($data['pvp_wins']>=50) fcg_award_title($user,9122);
	return $reward;
}

function fcg_publish_mirror($data, $user, $room_key, $now)
{
	global $db,$gtablepre;
	try { fcg_validate_deck($data['deck'],fcg_collection($data)); } catch(InvalidArgumentException $e) { return; }
	$borrowed=false; foreach(array_count_values($data['deck']) as $id=>$n) if($n>($data['collection'][$id] ?? 0)) $borrowed=true;
	$mirror=array('uid'=>(int)$user['uid'],'name'=>$user['username'],'nick'=>$user['nick'],'deck'=>$data['deck']);
	if($borrowed) $mirror['scope']=array('key'=>$room_key,'day'=>date('Y-m-d',$now));
	$db->query("REPLACE INTO {$gtablepre}fcg_mirrors (uid,updated,data) VALUES (".(int)$user['uid'].','.(int)$now.','.fcg_sql(fcg_json($mirror)).')');
	$result=$db->query("SELECT uid FROM {$gtablepre}fcg_mirrors WHERE uid>0 ORDER BY updated DESC,uid DESC LIMIT 20,1000");
	$remove=array(); while($row=$db->fetch_array($result)) $remove[]=(int)$row['uid'];
	if($remove) $db->query("DELETE FROM {$gtablepre}fcg_mirrors WHERE uid>0 AND uid IN (".implode(',',$remove).')');
}

function fcg_mirrors($uid, $now)
{
	global $db,$gtablepre;
	$result=$db->query("SELECT m.data,g.groomid,g.gamenum,g.starttime,g.gruleset FROM {$gtablepre}fcg_mirrors m JOIN {$gtablepre}users u ON u.uid=m.uid LEFT JOIN {$gtablepre}game g ON g.groomid=u.roomid WHERE m.uid>0 AND m.uid<>".(int)$uid.' AND u.groupid>0 ORDER BY m.updated DESC LIMIT 20');
	$out=array();
	while($row=$db->fetch_array($result)) {
		$m=json_decode($row['data'],true); if(!$m) continue;
		if(isset($m['scope']) && ($row['gruleset']!=='FCG' || $m['scope']['day']!==date('Y-m-d',$now) || $m['scope']['key']!==fcg_room_key($row))) continue;
		$out[$m['uid']]=$m;
	}
	return $out;
}

function fcg_live_write($m, $room, $now)
{
	global $db,$gtablepre;
	$db->query("REPLACE INTO {$gtablepre}fcg_matches (id,roomid,roomkey,p0,p1,updated,data) VALUES (".fcg_sql($m['id']).','.(int)$room['groomid'].','.fcg_sql(fcg_room_key($room)).','.(int)$m['uids'][0].','.(int)$m['uids'][1].','.(int)$now.','.fcg_sql(fcg_json($m)).')');
}

function fcg_live_advance(&$m, $now)
{
	$steps=0;
	while($m['result']===null && $m['deadline']<=$now && $steps++<1200) {
		foreach(array(0,1) as $side) if($m['deadline']-$m['seen'][$side]>15 && ($m['auto_turn'][$side] ?? 0)!==$m['turn']) {
			$m['auto_turn'][$side]=$m['turn']; fcg_ai($m,$side);
		}
		if($m['result']!==null || $m['deadline']>$now) break;
		fcg_turn($m);
	}
}

function fcg_social_load(&$data, $user, $room, $now)
{
	global $db,$gtablepre;
	$uid=(int)$user['uid']; $social=array('match'=>null,'vars'=>array(),'mirrors'=>fcg_mirrors($uid,$now));
	if(!$room || $room['gruleset']!=='FCG') { $data['live_match']=null; return $social; }
	$vars=json_decode($room['gamevars'],true);
	if(!is_array($vars)) {
		if(!empty($room['gamevars'])) throw new RuntimeException('房间记录读取失败，请联系管理员。');
		$vars=array();
	}
	$vars+=array('fcg_online'=>array(),'fcg_invites'=>array(),'fcg_reports'=>array());
	foreach($vars['fcg_online'] as $id=>$p) if($p['at']<$now-30) unset($vars['fcg_online'][$id]);
	foreach($vars['fcg_invites'] as $id=>$p) if($p['expires']<$now) unset($vars['fcg_invites'][$id]);
	$vars['fcg_online'][$uid]=array('uid'=>$uid,'name'=>$user['username'],'at'=>$now,'busy'=>!empty($data['run']) || ($data['match'] && $data['match']['result']===null));
	$social['vars']=$vars;
	$result=$db->query("SELECT data FROM {$gtablepre}fcg_matches WHERE roomkey=".fcg_sql(fcg_room_key($room))." AND (p0=$uid OR p1=$uid) ORDER BY updated DESC,id DESC LIMIT 20");
	$fallback=null;
	while($row=$db->fetch_array($result)) {
		$m=json_decode($row['data'],true);
		if($m['result']===null || empty($m['ack'][$uid])) {
			$social['match']=$m; break;
		}
		if(($data['live_match'] ?? '')===$m['id']) $fallback=$m;
	}
	if(!$social['match']) $social['match']=$fallback;
	if($social['match']) {
		$data['live_match']=$social['match']['id'];
		$m=&$social['match']; fcg_live_advance($m,$now);
		$side=array_search($uid,$m['uids'],true); $m['seen'][$side]=$now;
		if($m['result']===null) $social['vars']['fcg_online'][$uid]['busy']=true;
		fcg_live_write($m,$room,$now);
	}
	return $social;
}

function fcg_social_action(&$social, &$data, &$user, $input, $room, $now)
{
	global $db,$gtablepre;
	$action=$input['action'] ?? 'status'; $uid=(int)$user['uid']; $m=&$social['match'];
	$in_room=$room && $room['gruleset']==='FCG';
	$active=$m && $m['result']===null;
	if($action==='accept' && $m && in_array($input['token'] ?? '',$m['recent'][$uid] ?? array(),true)) { $data['match']=null; return true; }
	if($active && in_array($action,array('start','mirror','invite','accept','draft_enter'),true)) throw new InvalidArgumentException('请先完成当前切磋。');
	if($action==='draft_enter' && $m && !$active && ($data['live_settled'] ?? '')!==$m['id']) throw new InvalidArgumentException('请先刷新牌桌，收好上一场切磋的奖励，再开始远征。');
	if(!empty($data['run']) && in_array($action,array('start','mirror','invite','accept'),true)) throw new InvalidArgumentException('请先完成并领取当前远征的奖励。');
	if(in_array($action,array('invite','accept','decline','mirror','sticker'),true) && !$in_room) throw new InvalidArgumentException('请先加入 FCG 房间。');
	if(in_array($action,array('invite','accept','mirror'),true) && $data['match'] && $data['match']['result']===null) throw new InvalidArgumentException('请先完成当前牌局。');
	if(in_array($action,array('invite','accept','decline'),true)) {
		$target=$input['uid'] ?? null;
		if(!is_int($target) || $target===$uid) throw new InvalidArgumentException('请选择其他牌手。');
		if($action==='decline') {
			if(($social['vars']['fcg_invites'][$uid]['from'] ?? null)===$target) unset($social['vars']['fcg_invites'][$uid]);
			return true;
		}
		$peer=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=$target AND groupid>0 AND roomid=".(int)$user['roomid']));
		if(!$peer || ($social['vars']['fcg_online'][$target]['at'] ?? 0)<$now-30) throw new InvalidArgumentException('对方已离开牌桌。');
		$peer_data=fcg_load_account($peer['fcgdata']); fcg_expire_temp($peer_data,$peer,$now,fcg_room_key($room));
		if(!empty($peer_data['run']) || ($peer_data['match'] && $peer_data['match']['result']===null) || !empty($social['vars']['fcg_online'][$target]['busy'])) throw new InvalidArgumentException('对方正在对局或远征中。');
		fcg_validate_deck($data['deck'],fcg_collection($data)); fcg_validate_deck($peer_data['deck'],fcg_collection($peer_data));
		if($action==='invite') {
			foreach($social['vars']['fcg_invites'] as $invite) if($invite['from']===$uid) throw new InvalidArgumentException('邀请已发出，请等待回应。');
			if(isset($social['vars']['fcg_invites'][$target])) throw new InvalidArgumentException('对方有一份待处理邀请。');
			$social['vars']['fcg_invites'][$target]=array('from'=>$uid,'name'=>$user['username'],'expires'=>$now+30);
		} else {
			$invite=$social['vars']['fcg_invites'][$uid] ?? null;
			if(!$invite || $invite['from']!==$target) throw new InvalidArgumentException('邀请已过期。');
			$m=array('id'=>bin2hex(random_bytes(12)),'live'=>true,'pvp'=>true,'uids'=>array($target,$uid),'names'=>array($peer['username'],$user['username']),
				'turn'=>1,'deadline'=>$now+10,'extension'=>0,'sides'=>array(fcg_side($peer_data['deck'],100),fcg_side($data['deck'],100)),
				'decks'=>array($peer_data['deck'],$data['deck']),'seen'=>array($now,$now),'last_spell'=>null,'result'=>null,'ack'=>array(),'recent'=>array(),
				'log'=>array('两位牌手落座，同房切磋开始。'),'stickers'=>array());
			$m['recent'][$uid]=array($input['token']);
			$data['live_match']=$m['id']; $data['match']=null; $data['live_reward']=null;
			foreach($social['vars']['fcg_invites'] as $to=>$pending) if(in_array((int)$to,$m['uids'],true) || in_array($pending['from'],$m['uids'],true)) unset($social['vars']['fcg_invites'][$to]);
			foreach($m['uids'] as $id) $social['vars']['fcg_online'][$id]['busy']=true;
			fcg_live_write($m,$room,$now);
		}
		return true;
	}
	if($action==='mirror') {
		$id=$input['uid'] ?? null; $mirror=is_int($id)?($social['mirrors'][$id] ?? null):null;
		if(!$mirror) throw new InvalidArgumentException('这位残影已经离场，请刷新大厅。');
		fcg_validate_deck($data['deck'],fcg_collection($data));
		$data['match']=fcg_new_match($data['deck'],0,$now); $solo=&$data['match'];
		$solo['sides']=array(fcg_side($data['deck'],100),fcg_side($mirror['deck'],100)); $solo['drop_pool']=$mirror['deck'];
		$solo['pvp']=true; $solo['opponent_uid']=$id; $solo['names']=array($user['username'],'✦'.$mirror['name'].'的残影');
		$solo['log']=array('游荡的牌手残影展开了拓印的卡组。'); $data['live_match']=null; $m=null; $data['last_reward']=null;
		return true;
	}
	if(in_array($action,array('start','draft_enter'),true) && !$active) { $m=null; return false; }
	if($m && in_array($action,array('play','discard','destroy','surrender','sticker'),true)) {
		if(($input['match'] ?? '')!==$m['id']) throw new InvalidArgumentException('牌局已切换，请刷新牌桌。');
		if(in_array($input['token'],$m['recent'][$uid] ?? array(),true)) return true;
		if($m['result']!==null && $action!=='sticker') throw new InvalidArgumentException('这场切磋已结束。');
		$side=array_search($uid,$m['uids'],true);
		fcg_duel_action($m,$side,$input,$now);
		$m['recent'][$uid][]=$input['token']; $m['recent'][$uid]=array_slice($m['recent'][$uid],-20);
		fcg_live_write($m,$room,$now);
		return true;
	}
	if($action==='sticker') {
		if(!$data['match'] || ($input['match'] ?? '')!==$data['match']['id']) throw new InvalidArgumentException('请先坐上牌桌。');
		fcg_duel_action($data['match'],0,$input,$now); return true;
	}
	return false;
}

function fcg_duel_action(&$m, $side, $input, $now)
{
	$action=$input['action']; $s=&$m['sides'][$side];
	if($action==='play' || $action==='discard') {
		$slot=$input['slot'] ?? null;
		if(!is_int($slot) || !isset($s['hand'][$slot]) || ($input['turn'] ?? null)!==$m['turn'] || ($input['card'] ?? null)!==$s['hand'][$slot]) throw new InvalidArgumentException('手牌或回合已更新，请重新选择。');
		if($action==='play') fcg_play($m,$side,$slot);
		else { fcg_remove_hand($s,$slot); $s['regen']--; fcg_log($m,fcg_side_name($m,$side).'弃掉了一张牌。'); }
	} elseif($action==='destroy') { $s['monster']=null; fcg_log($m,fcg_side_name($m,$side).'解散了帮手。'); }
	elseif($action==='surrender') { $m['result']=$side===0?'loss':'win'; fcg_log($m,fcg_side_name($m,$side).'认输了。'); }
	elseif($action==='sticker') {
		$id=$input['sticker'] ?? null;
		if(!is_int($id) || !isset(fcg_stickers()[$id])) throw new InvalidArgumentException('请选择已有表情。');
		if(isset($m['stickers'][$side]) && $now-$m['stickers'][$side]['at']<3) throw new InvalidArgumentException('表情稍等 3 秒再发送。');
		$m['stickers'][$side]=array('id'=>$id,'at'=>$now);
	}
}

function fcg_social_settle(&$social, &$data, &$user, $room, $now)
{
	$m=&$social['match']; if(!$m || $m['result']===null || ($data['live_settled'] ?? '')===$m['id']) return;
	$side=array_search((int)$user['uid'],$m['uids'],true); $data['live_settled']=$m['id']; $data['played']++;
	$win=$m['result']===($side===0?'win':'loss');
	if($win) $data['live_reward']=fcg_pvp_reward($data,$user,$m['uids'][1-$side],$m['decks'][1-$side],$now,fcg_room_key($room));
	else $data['live_reward']=null;
	$uid=(int)$user['uid']; $online=&$social['vars']['fcg_online'][$uid];
	$streak=$data['pvp_streak'] ?? array('key'=>'','wins'=>0);
	if($streak['key']!==fcg_room_key($room)) $streak=array('key'=>fcg_room_key($room),'wins'=>0);
	$streak['wins']=$win?$streak['wins']+1:0; $data['pvp_streak']=$streak;
	if($streak['wins']>=3) {
		$social['vars']['fcg_reports'][]=array('at'=>$now,'text'=>$user['username'].' 在同房切磋中取得 '.$streak['wins'].' 连胜！');
		$social['vars']['fcg_reports']=array_slice($social['vars']['fcg_reports'],-5);
	}
	$online['busy']=false;
}

function fcg_social_save(&$social, $data, $user, $room, $now)
{
	global $db,$gtablepre;
	if(!$room || $room['gruleset']!=='FCG') return;
	$social['vars']['fcg_online'][(int)$user['uid']]['busy']=!empty($data['run']) || ($data['match'] && $data['match']['result']===null) || ($social['match'] && $social['match']['result']===null);
	if($social['match'] && ($data['live_settled'] ?? '')===$social['match']['id']) {
		$social['match']['ack'][(int)$user['uid']]=true; fcg_live_write($social['match'],$room,$now);
	}
	$db->query("UPDATE {$gtablepre}game SET gamevars=".fcg_sql(fcg_json($social['vars'])).' WHERE groomid='.(int)$room['groomid']);
	$db->query("DELETE FROM {$gtablepre}fcg_matches WHERE updated<".($now-86400));
}

function fcg_social_public($state, $social, $data, $user, $room, $now)
{
	global $db,$gtablepre;
	$state['online']=array(); $state['invite']=$social['vars']['fcg_invites'][(int)$user['uid']] ?? null;
	$state['reports']=$social['vars']['fcg_reports'] ?? array();
	if($room && $room['gruleset']==='FCG') {
		$members=$db->query("SELECT uid FROM {$gtablepre}users WHERE roomid=".(int)$user['roomid'].' AND groupid>0');
		while($row=$db->fetch_array($members)) if((int)$row['uid']!==(int)$user['uid'] && isset($social['vars']['fcg_online'][$row['uid']])) $state['online'][]=$social['vars']['fcg_online'][$row['uid']];
	}
	$mirrors=array_values($social['mirrors']);
	// Stable within a minute so polling does not move a button under the pointer.
	usort($mirrors,function($a,$b)use($now,$user){return strcmp(md5($a['uid'].':'.$user['uid'].':'.intdiv($now,60)),md5($b['uid'].':'.$user['uid'].':'.intdiv($now,60)));});
	$state['mirrors']=array(); foreach(array_slice($mirrors,0,4) as $mirror) { $mirror['count']=count($mirror['deck']); unset($mirror['deck'],$mirror['scope']); $state['mirrors'][]=$mirror; }
	if($social['match']) {
		$m=$social['match']; $side=array_search((int)$user['uid'],$m['uids'],true);
		if($side===1) { $m['sides']=array($m['sides'][1],$m['sides'][0]); $m['names']=array($m['names'][1],$m['names'][0]); $m['stickers']=array($m['stickers'][1] ?? null,$m['stickers'][0] ?? null); if($m['result'] && $m['result']!=='draw') $m['result']=$m['result']==='win'?'loss':'win'; }
		$m['reward']=$m['result']==='win'?($data['live_reward'] ?? null):null;
		foreach($m['sides'] as $i=>&$s) { $s['deck_count']=count($s['deck']); unset($s['deck']); if($i===1) { $s['hand_count']=count($s['hand']); unset($s['hand'],$s['free']); } } unset($s);
		unset($m['uids'],$m['decks'],$m['seen'],$m['recent'],$m['ack'],$m['auto_turn']); $state['match']=$m;
	}
	return $state;
}

function fcg_stickers()
{
	return array(1=>'问好',2=>'致谢',8=>'开战',9=>'踩雷警告',12=>'被打懵',16=>'困惑',22=>'欧了',24=>'死鱼眼',28=>'胜利',30=>'希望盒',33=>'覆唱',36=>'再见');
}
