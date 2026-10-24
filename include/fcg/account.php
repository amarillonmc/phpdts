<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/fcg/engine.php';

function fcg_sql($s) { return "X'".bin2hex((string)$s)."'"; }
function fcg_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function fcg_json($v) { return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }

function fcg_ensure_storage()
{
	global $db,$gtablepre;
	static $ready=false;
	if($ready) return;
	$result=$db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'fcgdata'");
	if(!$db->num_rows($result)) {
		$lock=roommng_acquire_create_lock();
		if($lock===false) throw new RuntimeException('暂时无法准备卡册，请稍后重试。');
		try {
			$result=$db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'fcgdata'");
			if(!$db->num_rows($result)) $db->query("ALTER TABLE {$gtablepre}users ADD fcgdata MEDIUMTEXT NULL");
		} finally { roommng_release_create_lock($lock); }
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
	$ready=true;
}

function fcg_new_account()
{
	$deck=fcg_starter_deck();
	return array('rev'=>0,'collection'=>array_count_values($deck),'deck'=>$deck,'tutorial'=>false,
		'embers'=>0,'wins'=>array(),'played'=>0,'match'=>null,'purchases'=>array(),'recent'=>array(),'last_reward'=>null);
}

function fcg_load_account($raw)
{
	if($raw===null || $raw==='') return fcg_new_account();
	$data=json_decode($raw,true);
	if(!is_array($data) || !isset($data['collection'],$data['deck'],$data['rev'])) throw new RuntimeException('卡册读取失败，请联系管理员；原有收藏已保留。');
	return $data;
}

function fcg_add_card(&$data, $id)
{
	$c=fcg_cards()[$id]; $owned=$data['collection'][$id] ?? 0;
	if(!$c['unlimited'] && $owned>=7) { $data['embers']++; return array('card'=>$id,'overflow'=>true); }
	$data['collection'][$id]=$owned+1;
	return array('card'=>$id,'overflow'=>false);
}

function fcg_pack_pool($filter)
{
	if(!is_array($filter) || array_diff(array_keys($filter),array('upgraded','rare','rare_only','color','count'))) throw new InvalidArgumentException('卡包筛选条件无效。');
	if(isset($filter['rare_only'])) $filter['rare']=$filter['rare_only'] ? true : ($filter['rare'] ?? null);
	foreach(array('upgraded','rare') as $key) if(isset($filter[$key]) && !is_bool($filter[$key])) throw new InvalidArgumentException('卡包稀有度及升级条件须为布尔值。');
	if(isset($filter['color']) && $filter['color']!=='all' && !in_array($filter['color'],fcg_colors(),true)) throw new InvalidArgumentException('卡包颜色无效。');
	if(isset($filter['count']) && $filter['count']!==1) throw new InvalidArgumentException('目前每个补充包固定包含一张卡。');
	$pool=array();
	foreach(fcg_cards() as $c) {
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

function fcg_settle(&$data, &$user)
{
	$m=&$data['match'];
	if($m && $m['result']!==null && !$m['rewarded']) {
		$m['rewarded']=true; $data['played']++;
		if($m['result']==='win') {
			$data['wins'][$m['opponent']]=($data['wins'][$m['opponent']] ?? 0)+1;
			$drop=$m['drop_pool'][random_int(0,count($m['drop_pool'])-1)];
			$data['last_reward']=$m['reward']=fcg_add_card($data,$drop);
			fcg_log($m,'种火从本局使用的牌堆里送给你「'.fcg_cards()[$drop]['name'].'」。');
		}
	}
	if(count($data['wins'])>=17 || count($data['collection'])>=150) fcg_award_title($user,9106);
}

function fcg_buy(&$data, &$user, $item, $now)
{
	global $titles_list;
	if(!$item || !$item['enabled']) throw new InvalidArgumentException('商品已经下架。');
	$id=(int)$item['item_id']; $price=(int)$item['price_credits2'];
	if($price<0 || $price>$user['credits2']) throw new InvalidArgumentException('切糕不足。');
	$today=date('Y-m-d',$now); $counts=$data['purchases'][$id] ?? array('day'=>$today,'daily'=>0,'total'=>0);
	if($counts['day']!==$today) { $counts['day']=$today; $counts['daily']=0; }
	if(($item['daily_limit'] && $counts['daily'] >= $item['daily_limit']) || ($item['total_limit'] && $counts['total'] >= $item['total_limit'])) throw new InvalidArgumentException('已达到这件商品的限购次数。');
	$payload=json_decode($item['reward_payload'],true);
	if($item['reward_type']==='fcg_pack') {
		$pool=fcg_pack_pool($payload); $data['last_reward']=fcg_add_card($data,$pool[random_int(0,count($pool)-1)]);
	} elseif($item['reward_type']==='title') {
		$title=$payload['title'] ?? null;
		if(!is_int($title) || !isset($titles_list[$title])) throw new InvalidArgumentException('商品配置的头衔不存在。');
		if(fcg_title_owned($user,$title)) throw new InvalidArgumentException('已经拥有这个头衔，无需再次购买。');
		fcg_award_title($user,$title); $data['last_reward']=array('title'=>$title);
	} else throw new InvalidArgumentException('这件商品暂不支持领取，未扣除切糕。');
	$user['credits2']-=$price; $counts['daily']++; $counts['total']++; $data['purchases'][$id]=$counts;
}

function fcg_catalog($all=false)
{
	global $db,$gtablepre;
	$result=$db->query("SELECT * FROM {$gtablepre}shop_items".($all ? '' : ' WHERE enabled=1').' ORDER BY sort_order,item_id');
	$list=array(); while($row=$db->fetch_array($result)) $list[(int)$row['item_id']]=$row;
	return $list;
}

function fcg_account_save($old, $user, $data)
{
	global $db,$gtablepre;
	// Compare the exact state read by this request; stale tabs cannot spend/reward twice.
	$uid=(int)$old['uid']; $debit=(int)$old['credits2']-(int)$user['credits2'];
	$changed_title=($old['nicksrev'] ?? '')!==($user['nicksrev'] ?? '');
	$where='uid='.$uid.' AND COALESCE(fcgdata,\'\')='.fcg_sql($old['fcgdata'] ?? '').' AND credits2>='.$debit;
	$set='fcgdata='.fcg_sql(fcg_json($data)).',credits2=credits2-'.$debit;
	if($changed_title) { $where.=' AND COALESCE(nicksrev,\'\')='.fcg_sql($old['nicksrev'] ?? ''); $set.=',nicksrev='.fcg_sql($user['nicksrev']); }
	return $db->query("UPDATE {$gtablepre}users SET $set WHERE $where",'SILENT') && $db->affected_rows()===1;
}

function fcg_action(&$data, &$user, $input, $now, $in_room)
{
	$action=$input['action'] ?? 'status';
	if($action==='status') return;
	if(!is_string($action)) throw new InvalidArgumentException('操作无效。');
	switch($action) {
		case 'tutorial': $data['tutorial']=true; break;
		case 'deck': $data['deck']=fcg_validate_deck($input['deck'] ?? null,$data['collection']); break;
		case 'start':
			if(!$in_room) throw new InvalidArgumentException('请先在房间列表创建或加入 FCG 异色房间。');
			if($data['match'] && $data['match']['result']===null) throw new InvalidArgumentException('请先完成当前牌局。');
			$id=$input['opponent'] ?? null;
			if(!is_int($id)) throw new InvalidArgumentException('请选择牌手。');
			fcg_validate_deck($data['deck'],$data['collection']);
			$data['match']=fcg_new_match($data['deck'],$id,$now); $data['last_reward']=null; break;
		case 'play': case 'discard': case 'destroy': case 'surrender':
			if(!$in_room) throw new InvalidArgumentException('已离开 FCG 房间，请重新加入后继续。');
			$m=&$data['match'];
			if(!$m || $m['result']!==null || ($input['match'] ?? '')!==$m['id']) throw new InvalidArgumentException('这场牌局已经结束或已切换，请刷新牌桌。');
			if($action==='play') fcg_play($m,0,$input['slot'] ?? null);
			elseif($action==='discard') {
				$slot=$input['slot'] ?? null;
				if(!is_int($slot) || !isset($m['sides'][0]['hand'][$slot])) throw new InvalidArgumentException('请选择一张手牌。');
				fcg_remove_hand($m['sides'][0],$slot); $m['sides'][0]['regen']--; fcg_log($m,'你弃掉一张牌，回合回血降低了 1 点。');
			} elseif($action==='destroy') { $m['sides'][0]['monster']=null; fcg_log($m,'你解散了驻场帮手。'); }
			else { $m['result']='loss'; fcg_log($m,'你向小种火认输了。'); }
			break;
		case 'buy':
			$catalog=fcg_catalog(); $id=$input['item'] ?? null;
			if(!is_int($id)) throw new InvalidArgumentException('请选择商品。');
			if(!isset($catalog[$id]) || ($input['price'] ?? null)!==(int)$catalog[$id]['price_credits2']) throw new InvalidArgumentException('商品价格已变化，请刷新商店后再购买。');
			fcg_buy($data,$user,$catalog[$id] ?? null,$now); break;
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
	global $db,$gtablepre,$cuser,$cpass;
	$action=$input['action'] ?? 'status';
	for($attempt=0;$attempt<3;$attempt++) {
		$result=$db->query("SELECT uid,username,password,credits2,nicksrev,roomid,fcgdata,groupid FROM {$gtablepre}users WHERE username=".fcg_sql($cuser));
		$old=$db->fetch_array($result);
		if(!$old || !hash_equals((string)$old['password'],(string)$cpass)) throw new InvalidArgumentException('登录状态已失效，请重新登录。');
		if((int)$old['groupid']<=0) throw new InvalidArgumentException('此账号暂时无法使用牌桌与商店。');
		$user=$old; $data=fcg_load_account($old['fcgdata']); $previous=fcg_json($data);
		$room=$db->fetch_array($db->query("SELECT gruleset FROM {$gtablepre}game WHERE groomid=".(int)$user['roomid']));
		$in_room=$room && $room['gruleset']==='FCG';
		$duplicate=false;
		if($action!=='status') {
			$token=$input['token'] ?? '';
			if(!is_string($token) || !preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$token)) throw new InvalidArgumentException('操作编号无效，请刷新页面。');
			$duplicate=in_array($token,$data['recent'],true);
			if(!$duplicate && (!isset($input['rev']) || $input['rev']!==$data['rev'])) throw new InvalidArgumentException('另一页面已更新卡册，已刷新状态，请重新操作。');
		}
		if($data['match'] && $data['match']['result']===null) fcg_advance($data['match'],$now);
		fcg_settle($data,$user);
		if(!$duplicate) {
			fcg_action($data,$user,$input,$now,$in_room);
			if($action!=='status') { $data['recent'][]=$token; $data['recent']=array_slice($data['recent'],-20); }
		}
		fcg_settle($data,$user);
		if($old['fcgdata']===null || $old['fcgdata']==='' || fcg_json($data)!==$previous || $user['nicksrev']!==$old['nicksrev']) {
			$data['rev']++;
			if(!fcg_account_save($old,$user,$data)) continue;
		}
		return fcg_public_state($data,$user,$in_room,$now);
	}
	throw new RuntimeException('卡册正在被另一请求更新，未重复扣费，请稍后再试。');
}

function fcg_public_state($data, $user, $in_room, $now)
{
	$m=$data['match'];
	if($m) {
		foreach($m['sides'] as $i=>&$s) {
			$s['deck_count']=count($s['deck']); unset($s['deck']);
			if($i===1) { $s['hand_count']=count($s['hand']); unset($s['hand'],$s['free']); }
		} unset($s);
		unset($m['drop_pool'],$m['ai_at']);
	}
	$n=json_decode($user['nicksrev'] ?? '',true);
	return array('rev'=>$data['rev'],'collection'=>(object)$data['collection'],'deck'=>$data['deck'],
		'tutorial'=>$data['tutorial'],'embers'=>$data['embers'],'wins'=>(object)$data['wins'],'played'=>$data['played'],
		'match'=>$m,'last_reward'=>$data['last_reward'],'purchases'=>(object)$data['purchases'],
		'credits'=>(int)$user['credits2'],'titles'=>$n['nicks'] ?? array(),'in_room'=>(bool)$in_room,'now'=>$now);
}
