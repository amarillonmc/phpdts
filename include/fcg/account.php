<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/fcg/engine.php';
require_once GAME_ROOT.'./include/fcg/social.php';
require_once GAME_ROOT.'./include/fcg/draft.php';

function fcg_sql($s) { return "X'".bin2hex((string)$s)."'"; }
function fcg_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function fcg_json($v) { return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }

function fcg_player_avatar($icon, $gd, $ruleset_id=null)
{
	if($icon===null || $gd===null) return null;
	// 与游戏头像使用同一资源顺序，只返回站内图片路径。
	$icon=basename(str_replace('\\','/',(string)$icon)); $gd=$gd==='f'?'f':'m';
	if($ruleset_id) {
		require_once GAME_ROOT.'gamedata/ruleset/ruleset_config.php';
		$path=get_ruleset_avatar_path($ruleset_id,$gd==='f'?'female':'male',$icon);
		if($path && is_file(GAME_ROOT.$path)) return $path;
	}
	if($icon!=='' && is_file(GAME_ROOT.'img/'.$icon)) return 'img/'.$icon;
	$path='img/'.$gd.'_'.$icon.'.gif';
	return is_file(GAME_ROOT.$path)?$path:null;
}

function fcg_ensure_storage()
{
	global $db,$gtablepre;
	static $ready=false;
	if($ready) return;
	$storage_lock=roommng_acquire_create_lock();
	if($storage_lock===false) throw new RuntimeException('暂时无法准备卡册，请稍后重试。');
	try {
	$result=$db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'fcgdata'");
	if(!$db->num_rows($result)) {
		$db->query("ALTER TABLE {$gtablepre}users ADD fcgdata MEDIUMTEXT NULL");
	}
	// Collection, active match, pack counters and cut-giao balance live on the SAME account row.
	// A single conditional UPDATE can commit both debit and delivery even on existing MyISAM installs.
	$db->query("CREATE TABLE IF NOT EXISTS {$gtablepre}shop_items (
		item_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		category VARCHAR(20) NOT NULL, name VARCHAR(120) NOT NULL, description TEXT NOT NULL,
		price_credits2 INT UNSIGNED NOT NULL DEFAULT 0, reward_type VARCHAR(20) NOT NULL,
		reward_payload TEXT NOT NULL, daily_limit INT UNSIGNED NOT NULL DEFAULT 0,
		total_limit INT UNSIGNED NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0,
		enabled TINYINT NOT NULL DEFAULT 1) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	$exists=$db->query("SELECT item_id FROM {$gtablepre}shop_items LIMIT 1");
	$seed=$db->num_rows($exists) ? array() : require GAME_ROOT.'./gamedata/fcg_shop.php';
	foreach($seed as $row) {
		list($id,$category,$name,$description,$price,$type,$payload,$daily,$total)=$row;
		$db->query("INSERT IGNORE INTO {$gtablepre}shop_items (item_id,category,name,description,price_credits2,reward_type,reward_payload,daily_limit,total_limit,sort_order) VALUES (".
			(int)$id.','.fcg_sql($category).','.fcg_sql($name).','.fcg_sql($description).','.(int)$price.','.fcg_sql($type).','.fcg_sql(fcg_json($payload)).','.(int)$daily.','.(int)$total.','.(int)$id.')');
	}
	fcg_ensure_social_storage();
	$ready=true;
	} finally { roommng_release_create_lock($storage_lock); }
}

function fcg_new_account()
{
	$deck=fcg_starter_deck();
	return array('rev'=>0,'collection'=>array_count_values($deck),'deck'=>$deck,'tutorial'=>false,
		'embers'=>0,'wins'=>array(),'hard_wins'=>array(),'played'=>0,'match'=>null,'run'=>null,'purchases'=>array(),'recent'=>array(),'last_reward'=>null);
}

function fcg_load_account($raw)
{
	if($raw===null || $raw==='') return fcg_new_account();
	$data=json_decode($raw,true);
	if(!is_array($data) || !isset($data['collection'],$data['deck'],$data['rev'])) throw new RuntimeException('卡册读取失败，请联系管理员；原有收藏已保留。');
	return $data+array('run'=>null,'hard_wins'=>array(),'pvp_wins'=>0,'temp_cards'=>array(),'live_match'=>null,'live_settled'=>null,'pvp_daily'=>array());
}

function fcg_evo_load($now)
{
	global $db,$gtablepre;
	$row=$db->fetch_array($db->query("SELECT data FROM {$gtablepre}fcg_mirrors WHERE uid=0"));
	if(!$row) {
		$raw=fcg_json(fcg_evo_initial_state());
		$db->query("INSERT IGNORE INTO {$gtablepre}fcg_mirrors (uid,updated,data) VALUES (0,".(int)$now.','.fcg_sql($raw).')');
		$row=$db->fetch_array($db->query("SELECT data FROM {$gtablepre}fcg_mirrors WHERE uid=0"));
	}
	$state=$row ? json_decode($row['data'],true) : null;
	if(!is_array($state) || !isset($state['decks'],$state['gen'],$state['deck_stats'],$state['card_stats'])) throw new RuntimeException('推演构筑读取失败，原记录已保留。');
	return array('raw'=>$row['data'],'state'=>$state);
}

function fcg_add_card(&$data, $id)
{
	$c=fcg_cards()[$id]; $owned=$data['collection'][$id] ?? 0;
	if(!$c['unlimited'] && $owned>=7) { $data['embers']++; return array('card'=>$id,'overflow'=>true); }
	$data['collection'][$id]=$owned+1;
	return array('card'=>$id,'overflow'=>false,'new'=>$owned===0);
}

function fcg_pack_pool($filter)
{
	if(!is_array($filter) || array_diff(array_keys($filter),array('upgraded','rare','rare_only','color','count','series'))) throw new InvalidArgumentException('卡包筛选条件无效。');
	if(isset($filter['series']) && !in_array($filter['series'],array('all','vol1','vol2','vol3'),true)) throw new InvalidArgumentException('卡包系列无效。');
	if(isset($filter['rare_only'])) $filter['rare']=$filter['rare_only'] ? true : ($filter['rare'] ?? null);
	foreach(array('upgraded','rare') as $key) if(isset($filter[$key]) && !is_bool($filter[$key])) throw new InvalidArgumentException('卡包稀有度及升级条件须为布尔值。');
	if(isset($filter['color']) && $filter['color']!=='all' && !in_array($filter['color'],fcg_colors(),true)) throw new InvalidArgumentException('卡包颜色无效。');
	if(isset($filter['count']) && $filter['count']!==1) throw new InvalidArgumentException('目前每个补充包固定包含一张卡。');
	$pool=array();
	foreach(fcg_cards() as $c) {
		if(isset($filter['series']) && $filter['series']!=='all' && $c['series']!==$filter['series']) continue;
		if(isset($filter['upgraded']) && $c['upgraded']!==$filter['upgraded']) continue;
		if(isset($filter['rare']) && $c['rare']!==$filter['rare']) continue;
		if(isset($filter['color']) && $filter['color']!=='all' && $c['color']!==$filter['color']) continue;
		$pool[]=$c['id'];
	}
	if(!$pool) throw new InvalidArgumentException('这个卡包没有可发放的卡片。');
	return $pool;
}

function fcg_title_owned($user, $title)
{
	$n=is_array($user['nicksrev']) ? $user['nicksrev'] : json_decode($user['nicksrev'] ?? '',true);
	return in_array((int)$title,$n['nicks'] ?? array(),false);
}

function fcg_award_title(&$user, $title)
{
	$n=is_array($user['nicksrev']) ? $user['nicksrev'] : json_decode($user['nicksrev'] ?? '',true);
	if(!is_array($n)) $n=array('nicks'=>array(0));
	if(!isset($n['nicks']) || !is_array($n['nicks'])) $n['nicks']=array(0);
	if(!in_array($title,$n['nicks'],false)) $n['nicks'][]=$title;
	$user['nicksrev']=fcg_json($n);
}

function fcg_settle(&$data, &$user, $now = 0, $room_key = '', &$evolution = null)
{
	$m=&$data['match'];
	if(!empty($m['draft'])) fcg_settle_draft($data,$m);
	elseif($m && $m['result']!==null && !$m['rewarded']) {
		if($m['opponent']===21 && empty($m['pvp']) && $m['turn']>=3 && isset($m['telemetry'])) {
			if($evolution===null) $evolution=fcg_evo_load($now);
			fcg_evo_record_and_optimize($evolution['state'],$m);
		}
		$m['rewarded']=true; $data['played']++;
		if($m['result']==='win') {
			if(!empty($m['pvp'])) {
				$data['last_reward']=$m['reward']=fcg_pvp_reward($data,$user,$m['opponent_uid'],$m['drop_pool'],$now,$room_key);
			} else {
			$data['wins'][$m['opponent']]=($data['wins'][$m['opponent']] ?? 0)+1;
			if(!empty($m['hard'])) $data['hard_wins'][$m['opponent']]=($data['hard_wins'][$m['opponent']] ?? 0)+1;
			$drop=$m['drop_pool'][random_int(0,count($m['drop_pool'])-1)];
			$data['last_reward']=$m['reward']=fcg_add_card($data,$drop);
			fcg_log($m,(!empty($m['hard'])?'种火从珍藏的代表卡与认真构筑中':'种火从本局使用的牌堆里').'送给你「'.fcg_cards()[$drop]['name'].'」。');
			}
		}
	}
	if(count(array_filter(array_intersect_key($data['wins'],array_flip(range(0,16)))))>=17 || count(array_filter($data['collection']))>=150) fcg_award_title($user,9106);
}

function fcg_buy(&$data, &$user, $item, $now, $count = 1)
{
	global $titles_list;
	if(!$item || !$item['enabled']) throw new InvalidArgumentException('商品已经下架。');
	$id=(int)$item['item_id']; $price=(int)$item['price_credits2'];
	$today=date('Y-m-d',$now); $counts=$data['purchases'][$id] ?? array('day'=>$today,'daily'=>0,'total'=>0);
	if($counts['day']!==$today) { $counts['day']=$today; $counts['daily']=0; }
	$remaining=min($item['daily_limit'] ? max(0,$item['daily_limit']-$counts['daily']) : PHP_INT_MAX,$item['total_limit'] ? max(0,$item['total_limit']-$counts['total']) : PHP_INT_MAX);
	if($count==='remaining' && $price===0 && $item['reward_type']==='fcg_pack' && $item['daily_limit']>0) $count=min($remaining,1000000);
	elseif(!is_int($count) || !in_array($count,array(1,5,10),true)) throw new InvalidArgumentException('请选择单抽、五连或十连。');
	if($count<1 || $count>$remaining) throw new InvalidArgumentException('已达到这件商品的限购次数。');
	if($count>1 && $item['reward_type']!=='fcg_pack') throw new InvalidArgumentException('这件商品只支持单次领取。');
	$total_price=(int)round($price*$count*($count===10 ? 0.8 : ($count===5 ? 0.9 : 1)));
	if($price<0 || $total_price>$user['credits2']) throw new InvalidArgumentException('切糕不足。');
	$payload=json_decode($item['reward_payload'],true);
	if($item['reward_type']==='fcg_pack') {
		$pool=fcg_pack_pool($payload); $rewards=array();
		for($i=0;$i<$count;$i++) $rewards[]=fcg_add_card($data,$pool[random_int(0,count($pool)-1)]);
		$data['last_reward']=array('cards'=>$rewards,'total_cost'=>$total_price);
		if($count===1) $data['last_reward']+=$rewards[0];
	} elseif($item['reward_type']==='title') {
		$title=$payload['title'] ?? null;
		if(!is_int($title) || !isset($titles_list[$title])) throw new InvalidArgumentException('商品配置的头衔不存在。');
		if(fcg_title_owned($user,$title)) throw new InvalidArgumentException('已经拥有这个头衔，无需再次购买。');
		fcg_award_title($user,$title); $data['last_reward']=array('title'=>$title);
	} else throw new InvalidArgumentException('这件商品暂不支持领取，未扣除切糕。');
	$user['credits2']-=$total_price; $counts['daily']+=$count; $counts['total']+=$count; $data['purchases'][$id]=$counts;
}

function fcg_catalog($all=false)
{
	global $db,$gtablepre;
	$result=$db->query("SELECT * FROM {$gtablepre}shop_items".($all ? '' : ' WHERE enabled=1').' ORDER BY sort_order,item_id');
	$list=array(); while($row=$db->fetch_array($result)) $list[(int)$row['item_id']]=$row;
	return $list;
}

function fcg_account_save($old, $user, $data, $evolution = null, $now = 0)
{
	global $db,$gtablepre;
	// Compare the exact state read by this request; stale tabs cannot spend/reward twice.
	$uid=(int)$old['uid']; $debit=(int)$old['credits2']-(int)$user['credits2'];
	$changed_title=($old['nicksrev'] ?? '')!==($user['nicksrev'] ?? '');
	$where='u.uid='.$uid.' AND COALESCE(u.fcgdata,\'\')='.fcg_sql($old['fcgdata'] ?? '').' AND u.credits2>='.$debit;
	$set='u.fcgdata='.fcg_sql(fcg_json($data)).',u.credits2=u.credits2-'.$debit;
	if($changed_title) { $where.=' AND COALESCE(u.nicksrev,\'\')='.fcg_sql($old['nicksrev'] ?? ''); $set.=',u.nicksrev='.fcg_sql($user['nicksrev']); }
	$tables="{$gtablepre}users u";
	if($evolution!==null) {
		// 同一条件 UPDATE 提交已领奖标记与共享学习记录，避免账户重试重复学习（含 MyISAM）。
		$tables.=" JOIN {$gtablepre}fcg_mirrors e ON e.uid=0";
		$where.=' AND e.data='.fcg_sql($evolution['raw']);
		$set.=',e.data='.fcg_sql(fcg_json($evolution['state'])).',e.updated='.(int)$now;
	}
	return $db->query("UPDATE $tables SET $set WHERE $where",'SILENT') && $db->affected_rows()===($evolution===null?1:2);
}

function fcg_action(&$data, &$user, $input, $now, $in_room, $evo_state = null)
{
	$action=$input['action'] ?? 'status';
	if($action==='status') {
		// 接续更新前停在营地或补强页的行程，保留已取得的卡组和胜场。
		$r=&$data['run'];
		if($r && $r['stage']==='node') {
			if($r['node']['type']==='camp') fcg_draft_prepare_battle($r);
			elseif($r['node']['type']==='reinforce') {
				$r['node']=array('type'=>'victory','removed'=>0,'opts'=>$r['node']['opts']);
				$r['hp']=$r['mhp'];
			}
		}
		return;
	}
	if(!is_string($action)) throw new InvalidArgumentException('操作无效。');
	switch($action) {
		case 'tutorial': $data['tutorial']=true; break;
		case 'deck': $data['deck']=fcg_validate_deck($input['deck'] ?? null,fcg_collection($data)); $data['temp_expired']=false; break;
		case 'draft_enter': case 'draft_pick': case 'draft_node': case 'draft_battle': case 'draft_reward': case 'draft_abandon':
			fcg_draft_action($data,$user,$input,$now,$in_room); break;
		case 'start':
			if(!$in_room) throw new InvalidArgumentException('请先在房间列表创建或加入 FCG 异色房间。');
			if(!empty($data['run'])) throw new InvalidArgumentException('请先完成并领取当前远征的奖励。');
			if($data['match'] && $data['match']['result']===null) throw new InvalidArgumentException('请先完成当前牌局。');
			$id=$input['opponent'] ?? null;
			if(!is_int($id)) throw new InvalidArgumentException('请选择牌手。');
			$hard=$input['hard'] ?? false;
			if(!is_bool($hard)) throw new InvalidArgumentException('请选择有效的挑战难度。');
			fcg_validate_deck($data['deck'],fcg_collection($data)); $data['live_match']=null;
			$evo=$id===21 ? ($evo_state ?? fcg_evo_load($now)['state']) : null;
			$data['match']=fcg_new_match($data['deck'],$id,$now,$hard,$evo); $data['last_reward']=null; break;
		case 'play': case 'discard': case 'destroy': case 'surrender':
			if(!$in_room) throw new InvalidArgumentException('已离开 FCG 房间，请重新加入后继续。');
			$m=&$data['match'];
			if(!$m || $m['result']!==null || ($input['match'] ?? '')!==$m['id']) throw new InvalidArgumentException('这场牌局已经结束或已切换，请刷新牌桌。');
			if($action==='play' || $action==='discard') {
				$slot=$input['slot'] ?? null;
				if(!is_int($slot) || !isset($m['sides'][0]['hand'][$slot]) || ($input['turn'] ?? null)!==$m['turn'] || ($input['card'] ?? null)!==$m['sides'][0]['hand'][$slot]) throw new InvalidArgumentException('手牌或回合已更新，请重新选择。');
			}
			if($action==='play') fcg_play($m,0,$input['slot'] ?? null);
			elseif($action==='discard') {
				fcg_discard_slot($m,0,$slot);
			} elseif($action==='destroy') { $m['sides'][0]['monster']=null; fcg_log($m,'你解散了驻场帮手。'); }
			else { $m['result']='loss'; fcg_log($m,'你向小种火认输了。'); }
			break;
		case 'buy':
			$catalog=fcg_catalog(); $id=$input['item'] ?? null;
			if(!is_int($id)) throw new InvalidArgumentException('请选择商品。');
			if(!isset($catalog[$id]) || ($input['price'] ?? null)!==(int)$catalog[$id]['price_credits2']) throw new InvalidArgumentException('商品价格已变化，请刷新商店后再购买。');
			fcg_buy($data,$user,$catalog[$id] ?? null,$now,$input['count'] ?? 1); break;
		case 'embers':
			$kind=$input['kind'] ?? ''; $cost=$kind==='upgraded' ? 20 : ($kind==='rare' ? 50 : 0);
			if(!$cost || $data['embers']<$cost) throw new InvalidArgumentException('余烬印花不足。');
			$pool=fcg_pack_pool(array($kind=>true));
			$data['embers']-=$cost; $data['last_reward']=fcg_add_card($data,$pool[random_int(0,count($pool)-1)]); break;
		default: throw new InvalidArgumentException('未知的牌桌操作。');
	}
}

function fcg_request($input, $now)
{
	$lock=roommng_acquire_create_lock();
	if($lock===false) throw new RuntimeException('牌桌忙碌，请稍后重试。');
	try { return fcg_request_locked($input,$now); }
	finally { roommng_release_create_lock($lock); }
}

function fcg_request_locked($input, $now)
{
	global $db,$gtablepre,$cuser,$cpass;
	$action=$input['action'] ?? 'status';
	for($attempt=0;$attempt<3;$attempt++) {
		$result=$db->query("SELECT uid,username,password,credits2,nicksrev,nick,roomid,fcgdata,groupid,icon,gender FROM {$gtablepre}users WHERE username=".fcg_sql($cuser));
		$old=$db->fetch_array($result);
		if(!$old || !hash_equals((string)$old['password'],(string)$cpass)) throw new InvalidArgumentException('登录状态已失效，请重新登录。');
		if((int)$old['groupid']<=0) throw new InvalidArgumentException('此账号暂时无法使用牌桌与商店。');
		$user=$old; $data=fcg_load_account($old['fcgdata']); $previous=fcg_json($data); $evolution=null;
		$room=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}game WHERE groomid=".(int)$user['roomid']));
		$in_room=$room && $room['gruleset']==='FCG';
		$room_key=$in_room ? fcg_room_key($room) : '';
		fcg_expire_temp($data,$user,$now,$room_key);
		$duplicate=false;
		if($action!=='status') {
			$token=$input['token'] ?? '';
			if(!is_string($token) || !preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$token)) throw new InvalidArgumentException('操作编号无效，请刷新页面。');
			$duplicate=in_array($token,$data['recent'],true);
			if(!$duplicate && (!isset($input['rev']) || $input['rev']!==$data['rev'])) throw new InvalidArgumentException('另一页面已更新卡册，已刷新状态，请重新操作。');
		}
		$social=fcg_social_load($data,$user,$room,$now);
		if($data['match'] && $data['match']['result']===null) fcg_advance($data['match'],$now);
		$won=$data['match'] && empty($data['match']['draft']) && $data['match']['result']==='win' && !$data['match']['rewarded'];
		fcg_settle($data,$user,$now,$room_key,$evolution);
		if(!$duplicate) {
			if(!fcg_social_action($social,$data,$user,$input,$room,$now)) fcg_action($data,$user,$input,$now,$in_room,$evolution['state'] ?? null);
			if($action!=='status') { $data['recent'][]=$token; $data['recent']=array_slice($data['recent'],-20); }
		}
		fcg_settle($data,$user,$now,$room_key,$evolution);
		fcg_social_settle($social,$data,$user,$room,$now);
		if($old['fcgdata']===null || $old['fcgdata']==='' || fcg_json($data)!==$previous || $user['nicksrev']!==$old['nicksrev']) {
			$data['rev']++;
			if(!fcg_account_save($old,$user,$data,$evolution,$now)) continue;
		}
		fcg_social_save($social,$data,$user,$room,$now);
		if((!$duplicate && $action==='deck') || ($won && (isset($data['match']['opponent_uid']) || (fcg_opponents()[$data['match']['opponent']]['level'] ?? 0)>=4))) fcg_publish_mirror($data,$user,$room_key,$now);
		$state=fcg_public_state($data,$user,$in_room,$now);
		$state=fcg_social_public($state,$social,$data,$user,$room,$now);
		if($state['match']) {
			// 完成双方视角转换后再放头像；账号字段为 gender，局内角色才使用 gd。
			$ruleset=$room['gruleset'] ?? null;
			$portraits=array(fcg_player_avatar($user['icon'],$user['gender'],$ruleset),null);
			$opponent_uid=$state['match']['opponent_uid'] ?? null;
			if($social['match']) {
				$side=array_search((int)$user['uid'],$social['match']['uids'],true);
				$opponent_uid=$social['match']['uids'][1-$side];
			}
			if($opponent_uid) {
				$peer=$db->fetch_array($db->query("SELECT icon,gender FROM {$gtablepre}users WHERE uid=".(int)$opponent_uid));
				if($peer) $portraits[1]=fcg_player_avatar($peer['icon'],$peer['gender'],$ruleset);
			}
			$state['match']['portraits']=$portraits;
		}
		return $state;
	}
	throw new RuntimeException('卡册正在被另一请求更新，未重复扣费，请稍后再试。');
}

function fcg_public_state($data, $user, $in_room, $now)
{
	$m=$data['match'];
	if($m) {
		foreach($m['sides'] as $i=>&$s) {
			fcg_public_side($s,$i===1);
		} unset($s);
		unset($m['drop_pool'],$m['ai_at'],$m['telemetry']);
	}
	$n=json_decode($user['nicksrev'] ?? '',true);
	return array('rev'=>$data['rev'],'collection'=>(object)$data['collection'],'available'=>(object)fcg_collection($data),'temp_cards'=>(object)($data['temp_cards']['cards'] ?? array()),'deck'=>$data['deck'],
		'tutorial'=>$data['tutorial'],'embers'=>$data['embers'],'wins'=>(object)$data['wins'],'hard_wins'=>(object)($data['hard_wins'] ?? array()),'played'=>$data['played'],
		'match'=>$m,'run'=>fcg_draft_public($data),'last_reward'=>$data['last_reward'],'purchases'=>(object)$data['purchases'],
		'credits'=>(int)$user['credits2'],'titles'=>$n['nicks'] ?? array(),'in_room'=>(bool)$in_room,'now'=>$now,'pvp_wins'=>$data['pvp_wins'] ?? 0,'temp_expired'=>$data['temp_expired'] ?? false);
}

function fcg_public_side(&$s, $enemy)
{
	$s['deck_count']=count($s['deck']); unset($s['deck']);
	if($enemy) {
		$s['hand_count']=count($s['hand']); unset($s['hand'],$s['free'],$s['surge']);
		if(($s['field']['kind'] ?? '')==='trap') $s['field']=array('kind'=>'trap','hidden'=>true);
	} else {
		$s['costs']=array(); foreach($s['hand'] as $slot=>$id) $s['costs'][$slot]=fcg_card_cost($s,$slot);
	}
}
