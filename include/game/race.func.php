<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/talent.func.php';

function race_rules()
{
	static $rules;
	if($rules === null) { include GAME_ROOT.'./gamedata/cache/talent_1.php'; $rules = $talent_race_rules; }
	return $rules;
}

function race_is($data,$race)
{
	return talent_enabled() && empty($data['type']) && (int)($data['race'] ?? 0)===$race;
}

// 分体的账户关系属于本体，不随平台暂时投射出的生理种族改变。
function race_ai_is($data)
{
	if(!talent_enabled() || !empty($data['type'])) return false;
	$para=get_clbpara($data['clbpara'] ?? '');
	return (int)($data['race'] ?? 0)===5 || !empty($para['ai_master']) || (!empty($para['platform']['active']) && (int)($para['platform']['original']['race'] ?? 0)===5);
}

function race_account_name($data)
{
	global $db,$tablepre;
	if(is_array($data)) { $para = get_clbpara($data['clbpara'] ?? ''); return !empty($para['ai_master']) ? $para['ai_master'] : ($data['name'] ?? ''); }
	if(!is_string($data) || strpos($data,'#AI')===false) return $data;
	$row = $db->fetch_array($db->query("SELECT name,clbpara FROM {$tablepre}players WHERE type=0 AND name=X'".bin2hex($data)."' LIMIT 1"));
	return $row ? race_account_name($row) : $data;
}

function race_ai_family($data)
{
	global $db,$tablepre;
	$owner = race_account_name($data); $family = array();
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE type=0 ORDER BY pid");
	while($row = $db->fetch_array($result)) {
		$row['clbpara'] = get_clbpara($row['clbpara']);
		if(race_ai_is($row) && ($row['name']===$owner || ($row['clbpara']['ai_master'] ?? '')===$owner)) $family[(int)$row['pid']] = $row;
	}
	return $family;
}

// 主行永远仍由 fetch_playerdata_by_name($cuser) 取得，再解析其激活指针。
function race_ai_resolve_active($master)
{
	if(!$master || !race_ai_is($master)) return $master;
	$master['clbpara'] = get_clbpara($master['clbpara']);
	$active = (int)($master['clbpara']['ai_active_pid'] ?? 0);
	if($master['hp']<=0 || !$active || $active===(int)$master['pid']) return $master;
	$body = fetch_playerdata_by_pid($active);
	if($body && $body['hp']>0 && empty($body['type']) && ($body['clbpara']['ai_master'] ?? '')===$master['name']) return $body;
	$master['clbpara']['ai_active_pid'] = (int)$master['pid']; $master['clbpara']['ai_active'] = 1;
	player_save($master);
	return $master;
}

function race_ai_create_body(&$active,$free=false)
{
	global $db,$tablepre,$now,$nosta,$log,$club_skillslist;
	if(!race_ai_is($active) || $active['hp']<=0) return false;
	$active['clbpara'] = get_clbpara($active['clbpara']);
	if(!$free && (!empty($active['action']) || !empty($active['bid']))) { $log .= '交战中不能部署分体。<br>'; return false; }
	$rules = race_rules(); $cost = $free ? 0 : $rules['ai_cost'];
	if((int)($active['skillpoint'] ?? 0)<$cost) { $log .= '部署分体需要 '.$cost.' 点技能点。<br>'; return false; }
	$family = race_ai_family($active); $owner = race_account_name($active); $master = null;
	foreach($family as $row) if($row['name']===$owner) $master=$row;
	if(!$master || $master['hp']<=0) return false;
	if($master['pid']==$active['pid']) $master = $active;
	if($free && !empty($master['clbpara']['ai_initial_body'])) return false;
	$index = max(1,(int)($master['clbpara']['ai_next'] ?? 1));
	do {
		$bodyname = $owner.'#AI'.$index++;
		$existing = $db->query("SELECT pid FROM {$tablepre}players WHERE name=X'".bin2hex($bodyname)."' AND type=0 LIMIT 1");
	} while($db->num_rows($existing));
	// 当前行可能已经 extract(EXTR_REFS)，必须断开字段引用再创建新行。
	$body = json_decode(json_encode($active,JSON_UNESCAPED_UNICODE),true);
	unset($body['pid']);
	$body['name'] = $bodyname; $body['race'] = 5; $body['type'] = 0; $body['hp'] = $body['mhp']; $body['sp'] = $body['msp'];
	$body['state']=0; $body['bid']=0; $body['action']=''; $body['deathtime']=0; $body['endtime']=$now; $body['killnum']=0; $body['money']=0; $body['skillpoint']=0;
	$body['inf']=''; $body['rage']=0;
	// 新机体不复制当前背包、金钱或临时状态，避免部署时复制奖励。
	foreach(array('wep','wep2','arb','arh','ara','arf','art') as $slot) { $body[$slot]=$body[$slot.'k']=$body[$slot.'sk']=$body[$slot.'para']=''; $body[$slot.'e']=$body[$slot.'s']=0; }
	for($i=0;$i<=6;$i++) { $body['itm'.$i]=$body['itmk'.$i]=$body['itmsk'.$i]=$body['itmpara'.$i]=''; $body['itme'.$i]=$body['itms'.$i]=0; }
	$body['wep']='拳头'; $body['wepk']='WN'; $body['weps']=$nosta;
	$body['arb']='分体外壳'; $body['arbk']='DB'; $body['arbe']=5; $body['arbs']=15;
	$body['clbpara'] = array('ai_master'=>$owner,'ai_active'=>0);
	foreach(array('talent_bonus','talent_skills','talent_tree','randver1','randver2','randver3','ex_lock','dissociation_route','dissociation_deadline') as $key) if(isset($active['clbpara'][$key])) $body['clbpara'][$key]=$active['clbpara'][$key];
	$body['clbpara']['skill'] = array_keys($active['clbpara']['talent_skills'] ?? array());
	foreach($club_skillslist[$body['club']] ?? array() as $skill) getclubskill($skill,$body['clbpara']);
	$insert = player_format_with_db_structure($body);
	$db->array_insert($tablepre.'players',$insert); $body['pid']=(int)$db->insert_id();
	if(!$body['pid']) return false;
	$active['skillpoint'] -= $cost;
	$master['clbpara']['ai_next']=$index;
	if($free) $master['clbpara']['ai_initial_body']=1;
	if(empty($master['clbpara']['ai_active_pid'])) { $master['clbpara']['ai_active_pid']=(int)$master['pid']; $master['clbpara']['ai_active']=1; }
	if($master['pid']==$active['pid']) $active['clbpara']=$master['clbpara']; else player_save($master);
	player_save($active);
	if(!$free) $log .= '新的分体已部署在你所在的地点。各机体的装备、钱财和生命互相独立。<br>';
	return $body['pid'];
}

function race_ai_switch(&$active,$target)
{
	global $db,$now,$log;
	if(!race_ai_is($active) || $active['hp']<=0 || !ctype_digit((string)$target)) return false;
	$family = race_ai_family($active); $target=(int)$target;
	if(!isset($family[$target]) || $family[$target]['hp']<=0 || $target===(int)$active['pid']) { $log .= '请选择仍然完好的另一台机体。<br>'; return false; }
	if(!empty($active['action']) || !empty($active['bid']) || !empty($family[$target]['action']) || !empty($family[$target]['bid'])) { $log .= '交战中的机体无法切换。<br>'; return false; }
	$owner=race_account_name($active); $master=null;
	foreach($family as $row) if($row['name']===$owner) $master=$row;
	if(!$master || $master['hp']<=0) return false;
	if($master['pid']==$active['pid']) $master=$active;
	$master['clbpara']=get_clbpara($master['clbpara']);
	if($now < (int)($master['clbpara']['ai_switch_until'] ?? 0)) { $log .= '机体连接尚未稳定，请稍后再切换。<br>'; return false; }
	$next=$family[$target];
	$active['clbpara']['ai_active']=0; $next['clbpara']['ai_active']=1;
	$master['clbpara']['ai_active_pid']=$target; $master['clbpara']['ai_active']=$target===(int)$master['pid'] ? 1 : 0;
	$master['clbpara']['ai_switch_until']=$now+race_rules()['ai_switch_cd'];
	if($master['pid']==$active['pid']) $active['clbpara']=$master['clbpara'];
	if($master['pid']==$target) $next['clbpara']=$master['clbpara'];
	// 请求已持有 process.lock；事务保证 InnoDB 的两个激活标记一起提交。
	$db->query('START TRANSACTION');
	try {
		player_save($active); player_save($next);
		if($master['pid']!=$active['pid'] && $master['pid']!=$target) player_save($master);
		$db->query('COMMIT');
	} catch(Throwable $e) { $db->query('ROLLBACK','SILENT'); throw $e; }
	$active=$next;
	$log .= '连接已切换。待机机体仍留在原地，并会在受到攻击时自行反击。<br>';
	return true;
}

function race_ai_true_death(&$victim)
{
	global $alivenum,$db,$tablepre,$now;
	if(!race_ai_is($victim)) return;
	$victim['clbpara']=get_clbpara($victim['clbpara']);
	$owner=race_account_name($victim); $family=race_ai_family($victim);
	if(!empty($victim['clbpara']['ai_master'])) {
		foreach($family as $master) if($master['name']===$owner && $master['hp']>0 && (int)($master['clbpara']['ai_active_pid'] ?? 0)===(int)$victim['pid']) {
			$master['clbpara']['ai_active_pid']=(int)$master['pid']; $master['clbpara']['ai_active']=1; player_save($master);
		}
	} else {
		foreach($family as $body) if($body['pid']!=$victim['pid'] && $body['hp']>0) {
			$body['hp']=0; $body['state']=$victim['state']; $body['deathtime']=$now; $body['endtime']=$now; $body['bid']=0; $body['action']=''; $body['clbpara']['ai_active']=0;
			player_save($body);
		}
		$victim['clbpara']['ai_active_pid']=(int)$victim['pid'];
	}
	$victim['clbpara']['ai_active']=0;
	// 死者可能尚未保存，排除该行后重算账户数；重复回调也不会增加人数。
	$alivenum=race_alive_accounts((int)$victim['pid']);
}

function race_ai_enter_city(&$active,$route,$deadline)
{
	if(!race_ai_is($active)) { dissociation_strip($active,$route,$deadline); return; }
	foreach(race_ai_family($active) as $body) {
		if($body['pid']==$active['pid']) continue;
		if($body['hp']<=0) continue;
		dissociation_strip($body,$route,$deadline); player_save($body);
	}
	dissociation_strip($active,$route,$deadline);
}

function race_alive_accounts($exclude_pid=0)
{
	global $db,$tablepre;
	$result=$db->query("SELECT pid,name,clbpara FROM {$tablepre}players WHERE type=0 AND hp>0"); $count=0;
	while($row=$db->fetch_array($result)) { $para=get_clbpara($row['clbpara']); if(empty($para['ai_master']) && (int)$row['pid']!==$exclude_pid) $count++; }
	return $count;
}

function race_ai_reconnect(&$data)
{
	if(!race_ai_is($data) || $data['hp']>0) return false;
	$para=get_clbpara($data['clbpara']);
	if(empty($para['ai_master'])) return false;
	foreach(race_ai_family($data) as $row) if($row['name']===$para['ai_master'] && $row['hp']>0) {
		$data=$row;
		return true;
	}
	return false;
}

// 禁区和反挂机使用直接 UPDATE，需补上与战斗死亡相同的机体归属处理。
function race_ai_world_death(&$data,$state,$time)
{
	if(!race_ai_is($data)) return;
	$data['hp']=0; $data['state']=$state; $data['deathtime']=$time; $data['endtime']=$time; $data['bid']=0; $data['action']='';
	race_ai_true_death($data);
	player_save($data);
}

function race_ai_recent_activity($data,$deadline)
{
	if(!race_ai_is($data)) return false;
	foreach(race_ai_family($data) as $body) if($body['hp']>0 && (int)$body['endtime'] >= $deadline) return true;
	return false;
}

function race_ai_sync_master(&$data)
{
	if(!race_ai_is($data)) return;
	$para=get_clbpara($data['clbpara']);
	if(empty($para['ai_master'])) return;
	foreach(race_ai_family($data) as $master) if($master['name']===$para['ai_master'] && $master['hp']<=0) {
		$data['hp']=0; $data['state']=$master['state']; $data['deathtime']=$master['deathtime']; $data['endtime']=$master['endtime']; $data['bid']=0; $data['action']=''; $data['clbpara']['ai_active']=0;
		return;
	}
}

function race_move_cost($data,$cost,$act)
{
	if(!talent_enabled()) return $cost;
	$para=get_clbpara($data['clbpara'] ?? '');
	if($act==='move') {
		$cost *= max(0.4,1-($para['talent_bonus']['move_sp'] ?? 0)/100);
		if(race_is($data,4)) { $rules=race_rules(); $cost *= max($rules['fish_move_min'],1-(int)($para['fish_moves'] ?? 0)*$rules['fish_move_step']); }
	}
	return max(1,(int)round($cost));
}

function race_escape_rate($data,$rate)
{
	return race_is($data,4) ? min(100,$rate+race_rules()['fish_escape']) : $rate;
}

function race_action(&$data,$action)
{
	if(!race_is($data,4)) return;
	$data['clbpara']=get_clbpara($data['clbpara']);
	if($action==='move') $data['clbpara']['fish_moves']=min(4,(int)($data['clbpara']['fish_moves'] ?? 0)+1);
	else unset($data['clbpara']['fish_moves']);
}

function race_fairy_bound($data)
{
	if(!race_is($data,2)) return '';
	$names=array('妖精羽翼·炎'=>'u','妖精羽翼·霜'=>'i','妖精羽翼·雷'=>'e','妖精羽翼·鸣'=>'w','妖精羽翼·毒'=>'p');
	return $names[$data['arb'] ?? ''] ?? '';
}

function race_can_remove($data,$slot)
{
	return !(race_is($data,2) && $slot==='arb');
}

function race_sell(&$data,$slot)
{
	global $log;
	if(!race_is($data,3) || !preg_match('/\A[1-6]\z/',(string)$slot)) return false;
	$slot=(int)$slot; $name=$data['itm'.$slot] ?? ''; $kind=$data['itmk'.$slot] ?? ''; $amount=$data['itms'.$slot] ?? 0;
	if(!$name || !$amount) { $log .= '这个背包位置没有可以变现的物品。<br>'; return false; }
	$para=get_itmpara($data['itmpara'.$slot] ?? '');
	if(preg_match('/^(Q|ticket)/',$kind) || !empty($para['IsQuestItem']) || !empty($para['QuestID']) || !empty($para['quest']) || !empty($para['quest_id']) || !empty($para['sendo']) || !empty($para['bound']) || strpos($name,'湾城通票')!==false || strpos($name,'请柬')!==false || strpos($name,'解离凭证')!==false || in_array($name,array('善之印','恶之印','命运之钥','维度之钥','游戏解除钥匙','冰炎钥匙·冰','冰炎钥匙·炎'),true)) { $log .= '这件物品承载着未完成的约定，不能变现。<br>'; return false; }
	$rules=race_rules(); $count=is_numeric($amount) ? max(1,min(100,(int)$amount)) : 1;
	$price=max(1,min($rules['sell_max'],(int)ceil(max(1,(float)$data['itme'.$slot])*$count*$rules['sell_factor'])));
	$data['money'] += $price;
	$data['itm'.$slot]=$data['itmk'.$slot]=$data['itmsk'.$slot]=$data['itmpara'.$slot]=''; $data['itme'.$slot]=$data['itms'.$slot]=0;
	$log .= '你将<span class="yellow">'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'</span>变现，获得 '.$price.' 元。<br>';
	return true;
}

function race_dragon_pick_item($result,$count)
{
	global $db;
	$rules=race_rules(); $items=array(); $total=0;
	for($i=0;$i<$count;$i++) {
		$db->data_seek($result,$i); $row=$db->fetch_array($result);
		$weight=preg_match('/^(W|D|A|H)/',(string)$row['itmk']) ? $rules['dragon_ordinary'] : $rules['dragon_special'];
		$total+=(int)round($weight*100); $items[]=array($total,$row);
	}
	if(!$total) return null;
	$roll=rand(1,$total);
	foreach($items as $item) if($roll<=$item[0]) return $item[1];
	return null;
}
