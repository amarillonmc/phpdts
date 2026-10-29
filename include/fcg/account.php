<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/fcg/engine.php';
require_once GAME_ROOT.'./include/fcg/social.php';
require_once GAME_ROOT.'./include/fcg/draft.php';

const FCG_MAX_DECK_SLOTS = 10;
const FCG_STARTER_DECK_PRICE = 100;

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
	$data=array('rev'=>0,'collection'=>array_count_values($deck),'deck'=>$deck,'tutorial'=>false,
		'embers'=>0,'wins'=>array(),'hard_wins'=>array(),'played'=>0,'match'=>null,'run'=>null,'purchases'=>array(),'recent'=>array(),'last_reward'=>null);
	fcg_normalize($data);
	return $data;
}

function fcg_load_account($raw)
{
	if($raw===null || $raw==='') return fcg_new_account();
	$data=json_decode($raw,true);
	if(!is_array($data) || !isset($data['collection'],$data['deck'],$data['rev'])) throw new RuntimeException('卡册读取失败，请联系管理员；原有收藏已保留。');
	$data+=array('run'=>null,'hard_wins'=>array(),'pvp_wins'=>0,'temp_cards'=>array(),'live_match'=>null,'live_settled'=>null,'pvp_daily'=>array());
	fcg_normalize($data);
	return $data;
}

function fcg_normalize(&$data)
{
	$data+=array('turn_mode'=>'instinct','starter_claimed'=>false,'owned_starters'=>array('ORIG'),'quiz_result'=>'','deck_slots'=>1,'active_deck'=>0);
	if(!in_array($data['turn_mode'],array('instinct','think'),true)) $data['turn_mode']='instinct';
	$data['deck_slots']=max(1,min(FCG_MAX_DECK_SLOTS,(int)$data['deck_slots']));
	if(empty($data['decks']) || !is_array($data['decks'])) $data['decks']=array(array('name'=>'主战卡组','cards'=>$data['deck']));
	$data['decks']=array_slice(array_values($data['decks']),0,$data['deck_slots']);
	for($i=0;$i<$data['deck_slots'];$i++) {
		$data['decks'][$i]=($data['decks'][$i] ?? array())+array('name'=>'构筑 '.($i+1),'cards'=>array());
	}
	$data['active_deck']=max(0,min($data['deck_slots']-1,(int)$data['active_deck']));
	$active=$data['decks'][$data['active_deck']]['cards'];
	if(count($active)>=25 && count($active)<=100) $data['deck']=$active;
}

function fcg_deck_slot(&$data, $input)
{
	$slot=$input['slot'] ?? $data['active_deck'];
	if(!is_int($slot) || !isset($data['decks'][$slot])) throw new InvalidArgumentException('请选择已解锁的卡组位置。');
	$op=$input['op'] ?? 'save';
	if($op==='rename') {
		$name=$input['name'] ?? null;
		if(!is_string($name) || trim($name)==='' || mb_strlen($name,'UTF-8')>30 || preg_match('/[\x00-\x1f\x7f]/',$name)) throw new InvalidArgumentException('卡组名须为 1～30 个字符。');
		$data['decks'][$slot]['name']=trim($name); return;
	}
	if(!in_array($op,array('save','activate'),true)) throw new InvalidArgumentException('卡组操作无效。');
	$cards=fcg_validate_deck($op==='save'?($input['deck'] ?? null):$data['decks'][$slot]['cards'],fcg_collection($data));
	$data['decks'][$slot]['cards']=$cards; $data['active_deck']=$slot; $data['deck']=$cards; $data['temp_expired']=false;
}

function fcg_evaluate_quiz($answers)
{
	if(!is_array($answers) || (count($answers)!==0 && count($answers)!==5) || array_values($answers)!==$answers) throw new InvalidArgumentException('请完成五道题，或选择跳过测试。');
	$scores=array_fill_keys(fcg_colors(),0);
	foreach($answers as $color) {
		if(!is_string($color) || !isset($scores[$color])) throw new InvalidArgumentException('测试选项无效。');
		$scores[$color]++;
	}
	$max=max($scores); $picked='ORIG';
	if($max>1) {
		$candidates=array_keys($scores,$max,true);
		// Q5 命中并列色则优先；否则采用最早选择的并列色。
		$picked=in_array($answers[4],$candidates,true)?$answers[4]:array_values(array_intersect($answers,$candidates))[0];
	}
	return array('recommended'=>$picked,'is_random'=>count($answers)===5 && $max===1,'scores'=>$scores);
}

function fcg_claim_starter_deck(&$data, &$user, $key, $quiz_result = '')
{
	$decks=fcg_color_starter_decks();
	if(!is_string($key) || !isset($decks[$key]) || !is_string($quiz_result) || ($quiz_result!=='' && !isset($decks[$quiz_result]))) throw new InvalidArgumentException('请选择已有的初始牌组。');
	$owned=in_array($key,$data['owned_starters'],true);
	$price=$key!=='ORIG' && !$owned && $data['starter_claimed']?FCG_STARTER_DECK_PRICE:0;
	if($user['credits2']<$price) throw new InvalidArgumentException('切糕不足（购买其余颜色初始牌组需 100 切糕）。');
	$deck=$decks[$key];
	foreach(array_count_values($deck['cards']) as $id=>$count) {
		// 能量卡同样需要入册供构筑校验；补足所需数量，不靠重复领取刷卡。
		if($key==='ORIG' || fcg_cards()[$id]['unlimited']) $count=max(0,$count-($data['collection'][$id] ?? 0));
		elseif($owned) $count=0;
		for($i=0;$i<$count;$i++) fcg_add_card($data,$id);
	}
	if(!$owned) $data['owned_starters'][]=$key;
	if($key!=='ORIG') $data['starter_claimed']=true;
	$user['credits2']-=$price;
	if($price) $data['purchases']['starter_'.$key]=array('total'=>1,'cost'=>$price);
	if($quiz_result!=='') $data['quiz_result']=$quiz_result;
	$data['decks'][$data['active_deck']]=array('name'=>$deck['name'],'cards'=>$deck['cards']);
	$data['deck']=$deck['cards']; $data['tutorial']=true; $data['temp_expired']=false;
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
	$payload=is_array($item['reward_payload'])?$item['reward_payload']:json_decode($item['reward_payload'],true);
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
	} elseif($item['reward_type']==='custom' && ($payload['kind'] ?? '')==='deck_slot') {
		if($data['deck_slots']>=FCG_MAX_DECK_SLOTS) throw new InvalidArgumentException('卡组槽位已达上限（最多 10 个），未扣除切糕。');
		$data['deck_slots']++;
		$data['decks'][]=array('name'=>'构筑 '.$data['deck_slots'],'cards'=>array());
		$data['last_reward']=array('deck_slots'=>$data['deck_slots']);
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
		case 'deck': case 'deck_slot': fcg_deck_slot($data,$input); break;
		case 'set_options':
			$mode=$input['turn_mode'] ?? null;
			if(!in_array($mode,array('instinct','think'),true)) throw new InvalidArgumentException('请选择本能模式或思考模式。');
			$data['turn_mode']=$mode;
			if($data['match'] && $data['match']['result']===null) fcg_set_turn_mode($data['match'],0,$mode,$now);
			break;
		case 'quiz':
			$data['quiz_result']=fcg_evaluate_quiz($input['answers'] ?? null)['recommended']; break;
		case 'starter_claim':
			fcg_claim_starter_deck($data,$user,$input['key'] ?? null); break;
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
			$data['match']=fcg_new_match($data['deck'],$id,$now,$hard,$evo,$data['turn_mode']); $data['last_reward']=null; break;
		case 'play': case 'discard': case 'destroy': case 'surrender': case 'end_turn':
			if(!$in_room) throw new InvalidArgumentException('已离开 FCG 房间，请重新加入后继续。');
			$m=&$data['match'];
			if(!$m || $m['result']!==null || ($input['match'] ?? '')!==$m['id']) throw new InvalidArgumentException('这场牌局已经结束或已切换，请刷新牌桌。');
			fcg_duel_action($m,0,$input,$now);
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
		$user=$old; $data=fcg_load_account($old['fcgdata']); $previous=$old['fcgdata'] ?? ''; $evolution=null;
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
		if((!$duplicate && in_array($action,array('deck','deck_slot','starter_claim'),true)) || ($won && (isset($data['match']['opponent_uid']) || (fcg_opponents()[$data['match']['opponent']]['level'] ?? 0)>=4))) fcg_publish_mirror($data,$user,$room_key,$now);
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
		fcg_public_timers($m,0);
		foreach($m['sides'] as $i=>&$s) {
			fcg_public_side($s,$i===1);
		} unset($s);
		unset($m['drop_pool'],$m['ai_at'],$m['telemetry']);
	}
	$n=json_decode($user['nicksrev'] ?? '',true);
	return array('rev'=>$data['rev'],'collection'=>(object)$data['collection'],'available'=>(object)fcg_collection($data),'temp_cards'=>(object)($data['temp_cards']['cards'] ?? array()),'deck'=>$data['deck'],
		'turn_mode'=>$data['turn_mode'],'starter_claimed'=>$data['starter_claimed'],'owned_starters'=>$data['owned_starters'],'quiz_result'=>$data['quiz_result'],
		'deck_slots'=>$data['deck_slots'],'decks'=>$data['decks'],'active_deck'=>$data['active_deck'],
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

function fcg_color_starter_decks() {
	return array(
		'W' => array(
			'key' => 'W',
			'color' => 'W',
			'name' => '白昼铸甲·铁壁武装',
			'style_tag' => '铸甲固守 · 破防反制',
			'image' => 'img/fcg/starters/starter_w.png',
			'ruby_comment' => '「哈！我就知道你是那种出门前要把防弹插板塞满三层的人！既然怕疼，那就把护甲叠到对面连牙都崩掉好了！」',
			'snow_comment' => '「白色象征『器物、秩序与工事』。借由稳固的永久攻防锻造、协战门卫护航，再用罗盘撬空对手的防线——这是最稳健的正统派战法。」',
			'cards' => array(
				// 10张能量基底：器物锚点(#0)×4、★器物共鸣桩(#1)×2、拾荒所得(#2)×2、★满载行囊(#3)×2
				0,0,0,0, 1,1, 2,2, 3,3,
				// 7张永久锻造：生锈的水果刀(#26)×4、任意揉捏的橡皮泥(#80)×3
				26,26,26,26, 80,80,80,
				// 7张破甲与工事：盖着红章的公文批条(#144)×3、摸金校尉的罗盘(#224)×4
				144,144,144, 224,224,224,224,
				// 4张续航与协战护卫：港区车间的热茶杯(#96)×2、G.D.S 门卫保安(#184)×2
				96,96, 184,184,
				// 2张王牌：★三方拼凑的虚假皮囊(#145)×1、◆永火的护身符(#28)×1
				145, 28
			)
		),
		'R' => array(
			'key' => 'R',
			'color' => 'R',
			'name' => '绯红狂飙·疾风爆破',
			'style_tag' => '武技连段 · 穿透强袭',
			'image' => 'img/fcg/starters/starter_r.png',
			'ruby_comment' => '「这才对嘛！管对面摆什么乌龟阵，一拳把桌子掀了不就完事了？这套牌我亲自给你塞满了炸药和指虎，给我狠狠地揍！」',
			'snow_comment' => '「红色代表『战意、武技与爆破』。以指虎锁臂压制敌方攻势，让常威与校医助教穿透护甲持续成长，最后用高额直伤一锤定音。」',
			'cards' => array(
				// 10张能量基底：战意火花(#4)×4、★杀伐熔炉(#5)×2、先手突袭(#6)×2、★雷霆怒火(#7)×2
				4,4,4,4, 5,5, 6,6, 7,7,
				// 8张武技与成长穿透：钨钢指虎(#152)×4、天生神力的常威(#222)×4
				152,152,152,152, 222,222,222,222,
				// 6张穿透淬毒与成长前卫：树荫下的校医助教(#70)×3、大排档的跑堂伙计(#62)×3
				70,70,70, 62,62,62,
				// 4张夺防与直伤：游乐场的抓娃娃机(#78)×2、土制燃烧瓶(#32)×2
				78,78, 32,32,
				// 2张王牌：★擒抱锁技·千斤坠(#153)×1、◆残火的断刃(#34)×1
				153, 34
			)
		),
		'Y' => array(
			'key' => 'Y',
			'color' => 'Y',
			'name' => '黄金奇策·机关窃能',
			'style_tag' => '发条协同 · 窃能拆甲',
			'image' => 'img/fcg/starters/starter_y.png',
			'ruby_comment' => '「啧啧，满肚子坏水！一边用发条羊啃光人家的护甲，一边顺走对面兜里的能量买炸鸡——你打牌怎么比我还脏啊？」',
			'snow_comment' => '「黄色象征『情报、机关与博弈』。让发条蒸汽羊以协战不断并入走地羊，既能筑起高防产能防线，又能凭碎甲与窃能把对手玩弄于股掌之间。」',
			'cards' => array(
				// 10张能量基底：信号基站(#8)×4、★全频段监听塔(#9)×2、紧急预案(#10)×2、★神算妙计(#11)×2
				8,8,8,8, 9,9, 10,10, 11,11,
				// 8张机械协同核心：啃食装备的走地羊(#102)×4、发条蒸汽羊(#168)×4
				102,102,102,102, 168,168,168,168,
				// 7张反转净毒与窃能续航：「我只是运气好啦」(#122)×3、疯狂星期四的炸鸡桶(#220)×4
				122,122,122, 220,220,220,220,
				// 3张干扰与压制：两面皆正的幸运币(#136)×2、凭空蒸发的押运车(#126)×1
				136,136, 126,
				// 2张王牌：★无视护甲的蜂与羊(#103)×1、◆埋火的算盘(#40)×1
				103, 40
			)
		),
		'G' => array(
			'key' => 'G',
			'color' => 'G',
			'name' => '翠绿温床·剧毒生机',
			'style_tag' => '生机滋养 · 剧毒蔓生',
			'image' => 'img/fcg/starters/starter_g.png',
			'ruby_comment' => '「嘴上说着请喝热鸡汤，反手就往汤里下猛药！看着自己血条越打越满、对面每回合绿着脸掉血，你也是个狠角色呢！」',
			'snow_comment' => '「绿色代表『生态、药理与演化』。剧毒完全无视护甲，配合折翼鸟的回合演化与药农、台本的生机恢复，任何持久战都将成为你的狩猎场。」',
			'cards' => array(
				// 10张能量基底：生机脉搏(#12)×4、★不息温床(#13)×2、深呼吸(#14)×2、★战地急救(#15)×2
				12,12,12,12, 13,13, 14,14, 15,15,
				// 8张淬毒侵蚀：涂毒袖珍匕(#180)×4、特工端来的热鸡汤(#216)×2、安神助眠的草药包(#74)×2
				180,180,180,180, 216,216, 74,74,
				// 7张演化与驻场：戴脚镣的折翼鸟(#110)×3、连开十把的轮盘赌台(#134)×2、隐居城外的老药农(#196)×2
				110,110,110, 134,134, 196,196,
				// 3张生机补给：涂满荧光笔的台本(#56)×2、滚烫的秋刀鱼(#44)×1
				56,56, 44,
				// 2张王牌：★见血封喉的现形斩(#181)×1、◆篝火的扩音器(#46)×1
				181, 46
			)
		),
		'B' => array(
			'key' => 'B',
			'color' => 'B',
			'name' => '苍蓝视界·冰封秘仪',
			'style_tag' => '冻结锁场 · 视界映射',
			'image' => 'img/fcg/starters/starter_b.png',
			'ruby_comment' => '「哇哦，先把对面的帮手冻成冰雕，再端起热咖啡慢悠悠地看戏……跟斯诺一样，是一肚子冷静算计的家伙呢！」',
			'snow_comment' => '「蓝色象征『灵子、视界与解析』。用持续冻结与碎甲瓦解敌方攻势，以符盾与咖啡化解危机，最终借白光与万花筒完成逆转。」',
			'cards' => array(
				// 10张能量基底：灵子视界(#16)×4、★深层视界回路(#17)×2、灵感一闪(#18)×2、★超常顿悟(#19)×2
				16,16,16,16, 17,17, 18,18, 19,19,
				// 8张冰封与破甲：走进酒馆的试雪汉(#210)×5、刺耳的禁区警报(#50)×3
				210,210,210,210,210, 50,50,50,
				// 6张弱化与防线：匿名粉丝的花篮(#58)×2、不翼而飞的证物袋(#130)×2、月光下的萤火虫群(#98)×2
				58,58, 130,130, 98,98,
				// 4张净毒与符盾：G.D.S 女秘书的咖啡(#190)×2、朱砂护身符纸(#176)×1、全息战术沙盘(#88)×1
				190,190, 176, 88,
				// 2张王牌：★洞悉纯度与烈度的白光(#99)×1、◆焰火的万花筒(#52)×1
				99, 52
			)
		),
		'ORIG' => array(
			'key' => 'ORIG',
			'color' => 'ALL',
			'name' => '五色杂烩·小种火本能牌组',
			'style_tag' => '五色混搭 · 纯凭本能',
			'image' => 'img/fcg/starters/starter_orig.png',
			'ruby_comment' => '「……等等，你这东一榔头西一棒槌的脑回路，怎么跟底下那群靠本能乱抓牌的小豆丁一模一样？！算了算了，那还是把它们按本能搓出来的那套五色大杂烩塞给你吧！」',
			'snow_comment' => '「小种火的智力不足以理解单色共鸣，只会把五种颜色亮晶晶的牌全塞进同一个盒子里。虽然实战经常因为五色缺能而卡手，但既然这是你的选择……祝你好运（免费领单色牌组的额度依然为你保留）。」',
			'cards' => fcg_starter_deck()
		)
	);
}

function fcg_quiz_definitions() {
	return array(
		'intro' => array(
			'ruby' => '「喂喂，先别急着拎起桌上那盒牌就往外冲！那是底下那群小种火凭着本能瞎抓出来的五色大杂烩——小种火的智力根本不足以拼出什么能用的东西，我们拼的好得多！」',
			'snow' => '「在幻境牌室里，盲目混搭五种颜色只会让能量彻底卡死。回答我们五个小问题吧，让我和露比看看你的本性最契合哪种颜色与战术风格。」'
		),
		'color_lore' => array(
			'W' => '⚪ 白（器物与秩序）：锻造永久攻防、部署协战门卫、用罗盘瓦解敌甲，稳扎稳打的钢铁防线。',
			'R' => '🔴 红（战意与爆破）：指虎锁臂加攻、常威穿透成长、燃烧瓶高额直伤，崇尚进攻的疾风烈火。',
			'Y' => '🟡 黄（情报与机关）：发条羊协战产能、走地羊持续碎甲、顺走敌方能量，机关算尽的奇策博弈。',
			'G' => '🟢 绿（生机与药理）：热鸡汤与毒匕叠加无视护甲的剧毒，配合高额恢复与演化，生生不息的温床。',
			'B' => '🔵 蓝（灵子与视界）：试雪汉与禁区警报冻结碎甲，符盾咖啡净毒防守，借白光映射逆转战局。'
		),
		'questions' => array(
			array(
				'id' => 'q1',
				'title' => '第一问 · 陌生的异常牌桌',
				'prompt' => '刚推开牌室大门，桌上一枚从未见过的异色种火核心突然剧烈闪烁起来。你的第一反应是？',
				'image' => 'img/fcg/quiz/q1.png',
				'options' => array(
					array('color' => 'W', 'text' => '「先拿防爆盾挡在身前，按安全规程一步步排查。」'),
					array('color' => 'R', 'text' => '「管它是什么，趁它没炸之前先一拳砸扁！」'),
					array('color' => 'Y', 'text' => '「嘿嘿，看看能不能顺手把里面的能量抽出来换钱。」'),
					array('color' => 'G', 'text' => '「哪个亮就按哪个！说不定能孵出什么好玩的活物呢！」'),
					array('color' => 'B', 'text' => '「能吃吗？先凑过去咬一口，顺便记录它的灵子波长。」')
				)
			),
			array(
				'id' => 'q2',
				'title' => '第二问 · 龟缩不出的对手',
				'prompt' => '对面的小种火把自己缩进了十几层厚铁壳里，缩着脑袋死活不肯出来。你会怎么对付它？',
				'image' => 'img/fcg/quiz/q2.png',
				'options' => array(
					array('color' => 'W', 'text' => '「它叠我也叠，把我的护甲铸得比它更厚，再用撬棍一寸寸撬开。」'),
					array('color' => 'R', 'text' => '「直接用穿透劲力连壳带人一块儿轰飞！」'),
					array('color' => 'Y', 'text' => '「放几只发条走地羊过去，咔嚓咔嚓把它的铁壳全啃光。」'),
					array('color' => 'G', 'text' => '「往壳缝里灌一碗特制热鸡汤，毒气可不管你铁壳有多厚。」'),
					array('color' => 'B', 'text' => '「把铁壳直接冻成冰棺，等它憋不住的时候再复制它的属性反打。」')
				)
			),
			array(
				'id' => 'q3',
				'title' => '第三问 · 课间茶歇的犒赏',
				'prompt' => '打赢一局后，露比和斯诺决定在课间休息室请客。面对满桌补给，你会端走哪一样？',
				'image' => 'img/fcg/quiz/q3.png',
				'options' => array(
					array('color' => 'W', 'text' => '「港区车间刚泡好的热茶配止咳药，扎实又安心。」'),
					array('color' => 'R', 'text' => '「夜市大排档最烫嘴的爆辣铁板烧，越吃越有劲！」'),
					array('color' => 'Y', 'text' => '「当然是顺手牵羊赢来的疯狂星期四豪华炸鸡桶！」'),
					array('color' => 'G', 'text' => '「炭火烤得滋滋冒油的黄金秋刀鱼，再来一盅大补药膳。」'),
					array('color' => 'B', 'text' => '「G.D.S 女秘书手冲的冰滴黑咖啡，提神醒脑、百毒不侵。」')
				)
			),
			array(
				'id' => 'q4',
				'title' => '第四问 · 最信赖的牌桌搭档',
				'prompt' => '如果只能带一位帮手在牌桌前排替你冲锋陷阵，你最中意哪位搭档？',
				'image' => 'img/fcg/quiz/q4.png',
				'options' => array(
					array('color' => 'W', 'text' => '「随叫随到、能不断协战合体的 G.D.S 防暴门卫保安。」'),
					array('color' => 'R', 'text' => '「每回合都在变强、拳拳穿透护甲的天生神力武师。」'),
					array('color' => 'Y', 'text' => '「一边替我挨揍一边源源不断产出信号能量的发条蒸汽羊。」'),
					array('color' => 'G', 'text' => '「越挫越勇、哪怕倒下也能浴火重生的笼中折翼鸟。」'),
					array('color' => 'B', 'text' => '「一出场就把全场冻得瑟瑟发抖的酒馆试雪汉。」')
				)
			),
			array(
				'id' => 'q5',
				'title' => '第五问 · 决胜时刻的美学',
				'prompt' => '到了决定胜负的最后一个回合，你心目中最完美的赢法是什么？',
				'image' => 'img/fcg/quiz/q5.png',
				'options' => array(
					array('color' => 'W', 'text' => '「我方毫发无伤、攻防数值全面碾压，堂堂正正地平推对手。」'),
					array('color' => 'R', 'text' => '「手牌全倾、断刃出鞘，用最狂暴的连段伤害瞬间清零对手！」'),
					array('color' => 'Y', 'text' => '「算盘珠子一响，把对手的能量和退路算得干干净净，让它绝望投降。」'),
					array('color' => 'G', 'text' => '「自己满血满状态，微笑着看对手在回合结算时剧毒发作倒下。」'),
					array('color' => 'B', 'text' => '「万花筒与白光齐闪，在绝对零度的控制中优雅地收下胜利。」')
				)
			)
		)
	);
}
