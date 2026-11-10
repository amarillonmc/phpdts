<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
require_once GAME_ROOT.'./include/account_rewards.func.php';

$title2qiegao = 50;//已有头衔再次获得时转成多少切糕。单独一项配置不想放在其他文件里，干脆就丢这

$messages_autocreatedb = 1;//自动建表功能，一个很丑陋的开关

//判定是邮箱页面还是垃圾箱页面，并拉取对应的站内信数据
function init_messages($mode){
	if('showdel' == $mode || 'recover' == $mode || 'del2' == $mode) {
		return deleted_message_load();
	}else{
		return message_load();
	}
}

//判断指定用户有没有新站内信，基本上每次载入页面都需要调用
//如果没有新站内信则返回0，否则返回新站内信的数目
function message_check_new($username)
{
	global $db,$gtablepre,$messages_autocreatedb;
	$num = 0;
	if($username){
		//考虑到devtools.php也得先载入common.inc.php，从而如果没有建表就会直接出错，必须在这里就做判断是否存在message表
		//而既然做了判断为什么不直接建表呢？
		//可以把$messages_autocreatedb关掉来阻止这罪恶的一切
		if(!empty($messages_autocreatedb)){
			foreach(array('messages','del_messages') as $message_table) {
				$result = $db->query("SHOW TABLES LIKE '{$gtablepre}$message_table'");
				if(!$db->num_rows($result)) { create_messages_db(); break; }
			}
		}
		$result = $db->query("SELECT mid FROM {$gtablepre}messages WHERE receiver=".account_reward_sql($username)." AND rd=0");
		$num = $db->num_rows($result);
	}
	return $num;
}

//自动建表，很丑陋
function create_messages_db(){
	global $db, $gtablepre;
	$query = 
"CREATE TABLE IF NOT EXISTS bra_messages (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `rd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `checked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `receiver` char(15) NOT NULL DEFAULT '',
  `sender` char(15) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL DEFAULT '',
  `enclosure` text NOT NULL DEFAULT '',
  PRIMARY KEY (`mid`),
  INDEX `RECEIVER` (`receiver`),
  INDEX `SENDER` (`sender`)
  
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS bra_del_messages (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `dtimestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `rd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `checked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `receiver` char(15) NOT NULL DEFAULT '',
  `sender` char(15) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL DEFAULT '',
  `enclosure` text NOT NULL DEFAULT '',
  PRIMARY KEY (`mid`),
  INDEX `RECEIVER` (`receiver`),
  INDEX `SENDER` (`sender`)
  
) ENGINE=MyISAM;";
	$db->queries(str_replace('bra_',$gtablepre,$query));
}

//创建一封新邮件
//$enclosure 兼容旧附件串及账户奖励 JSON，成功返回新邮件编号。
function message_create($to, $title='', $content='', $enclosure='', $from='sys', $t=0)
{
	global $now,$db,$gtablepre;
	if(!$t) $t = $now;
	if(!is_string($to) || $to === '') throw new InvalidArgumentException('收件账户不能为空。');
	$ins_arr = array(
		'timestamp' => (int)$t,
		'sender' => $from,
		'receiver' => $to,
		'title' => $title,
		'content' => $content,
		'enclosure' => $enclosure
	);
	$values = array_map('account_reward_sql', array_values($ins_arr));
	if(!$db->query("INSERT INTO {$gtablepre}messages (".implode(',', array_keys($ins_arr)).') VALUES ('.implode(',', $values).')', 'SILENT')) throw new RuntimeException('邮件未能发送，请稍后重试。');
	return (int)$db->insert_id();
}

//载入当前用户有关的全部邮件，如果传入$mid_only则只拉取mid字段（一般是拉取数量用）
function message_load($mid_only=0)
{
	global $udata,$db,$gtablepre;
	$username = account_reward_sql($udata['username']);
	if($mid_only) $result = $db->query("SELECT mid FROM {$gtablepre}messages WHERE receiver=$username ORDER BY timestamp DESC, mid DESC");
	else $result = $db->query("SELECT * FROM {$gtablepre}messages WHERE receiver=$username ORDER BY timestamp DESC, mid DESC");
	$messages = array();
	while($r = $db->fetch_array($result)){
		$messages[$r['mid']] = $r;
	}
	return $messages;
}

//载入当前用户相关的垃圾箱邮件
function deleted_message_load($mid_only=0)
{
	global $udata,$db,$gtablepre;
	$username = account_reward_sql($udata['username']);
	if($mid_only) $result = $db->query("SELECT mid FROM {$gtablepre}del_messages WHERE receiver=$username ORDER BY timestamp DESC, mid DESC");
	else $result = $db->query("SELECT * FROM {$gtablepre}del_messages WHERE receiver=$username ORDER BY dtimestamp DESC, mid DESC");
	$d_messages = array();
	while($r = $db->fetch_array($result)){
		$d_messages[$r['mid']] = $r;
	}
	return $d_messages;
}

//获得站内信附件中的数字，$tp为传入的前缀，会匹配并返回"tp_xxx"中的xxx数字
function message_get_encl_num($encl, $tp)
{
	preg_match('/'.$tp.'_(\d+)/s', $encl, $matches);
	if($matches && is_numeric($matches[1])) return $matches[1];
	else return 0;
}

//显示站内信前的处理
function message_disp($messages)
{
	global $udata;
	$owned = $udata['nicksrev'] ?? array();
	if(is_string($owned)) $owned = json_decode($owned, true);
	$owned = $owned['nicks'] ?? array();
	$catalog = account_reward_catalog();
	foreach($messages as $mi => &$mv){
		$mv['hint'] = '<span class="L5 b">NEW!</span>';
		if($mv['rd']) $mv['hint'] = '';
		
		$mv['time_disp'] = date("Y年m月d日 H:i:s", $mv['timestamp']);
		if(isset($mv['dtimestamp'])) $mv['del_time_disp'] = date("Y年m月d日 H:i:s", $mv['dtimestamp']);
		$mv['encl_disp'] = '';
		$mv['encl_hint'] = '';
		if(!empty($mv['enclosure'])){
			
			if($mv['checked']) $mv['encl_hint'] = '<span class="grey b">附件已收</span>';
			elseif(isset($mv['dtimestamp'])) $mv['encl_hint'] = '附件未收<br>请先恢复邮件';
			else $mv['encl_hint'] = "<a class='L5 b' onclick=\"$('message_sl$mi').checked=true;$('message_sl$mi').value='1';$('mode').value='check';postCmd('message_cmd', 'messages.php');event.stopPropagation();return false;\">附件<br>点此查收</a>";

			try {
				foreach(account_reward_describe(account_reward_decode($mv['enclosure'])) as $key=>$line) {
					$class = $key === 'credits2' ? 'gold b' : ''; $hint = '';
					if(strpos($key, 'title_') === 0) {
						$id = (int)substr($key, 6);
						$class = $catalog['title_desc'][$id]['class'] ?? '';
						if(!in_array($id, $owned)) $hint = ' <span class="L5 b">NEW!</span>';
					}
					$mv['encl_disp'] .= '<div class="'.account_reward_html($class).'">'.account_reward_html($line).$hint.'</div>';
				}
			} catch(Throwable $e) { $mv['encl_disp'] = '附件暂时无法识别，请联系管理员。'; }
		}
	}
	return $messages;
}

//查收站内信，传入的$checklist是包含站内信cid的数组
function message_check($checklist, $messages)
{
	global $udata, $db, $gtablepre, $info, $title2qiegao;
	account_boons_ensure_storage();
	$uid = (int)$udata['uid']; $count = 0;
	$checklist = array_unique(array_map('intval', $checklist));
	sort($checklist); // 一次领取多封时，较早的邮件先进入赐福队列。
	foreach($checklist as $cid) {
		$user = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=$uid"));
		if(!$user || (int)$user['groupid'] <= 0 || !hash_equals((string)$user['password'], (string)$udata['password'])) throw new InvalidArgumentException('登录状态已失效。');
		$mail = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}messages WHERE mid=$cid AND receiver=".account_reward_sql($user['username'])));
		if(!$mail || $mail['checked'] || $mail['enclosure'] === '') continue;
		try {
			$reward = account_reward_decode($mail['enclosure']);
			$nicks = get_clbpara($user['nicksrev']);
			$ach = get_clbpara($user['achrev']);
			if(!is_array($nicks) || !is_array($ach) || (isset($nicks['nicks']) && !is_array($nicks['nicks']))) throw new InvalidArgumentException('账户成就或头衔记录异常，请联系管理员。');
			if(empty($nicks['nicks'])) $nicks['nicks'] = array(0);
			$queue = account_boons_load($user['boons']);
			$extra = 0;
			foreach($reward['titles'] as $id) {
				if(in_array($id, $nicks['nicks'])) $extra += $title2qiegao;
				else $nicks['nicks'][] = $id;
			}
			foreach($reward['achievements'] as $id=>$level) {
				$ach[$id]['l'] = max($ach[$id]['l'] ?? 0, $level);
				if(!isset($ach[$id]['v'])) $ach[$id]['v'] = 0;
			}
			if(isset($reward['boon'])) $queue[] = $reward['boon'];
			$c1 = $reward['credits']; $c2 = $reward['credits2']+$extra;
			if($user['credits'] > 2147483647-$c1 || $user['credits2'] > 8388607-$c2) throw new InvalidArgumentException('余额将超过上限，请先使用部分积分或切糕再领取。');
			$set = array("{$gtablepre}users.credits={$gtablepre}users.credits+$c1", "{$gtablepre}users.credits2={$gtablepre}users.credits2+$c2", "{$gtablepre}messages.checked=1", "{$gtablepre}messages.rd=1");
			$where = '';
			foreach(array('nicksrev'=>$nicks, 'achrev'=>$ach, 'boons'=>$queue) as $key=>$value) {
				$encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
				if(strlen($encoded) > ($key === 'boons' ? 16777215 : 65535)) throw new InvalidArgumentException('账户奖励记录已满，请联系管理员。');
				$set[] = "{$gtablepre}users.$key=".account_reward_sql($encoded);
				$where .= " AND BINARY COALESCE({$gtablepre}users.$key,'')=".account_reward_sql($user[$key]);
			}
			$sql = "UPDATE {$gtablepre}users, {$gtablepre}messages SET ".implode(',', $set)." WHERE {$gtablepre}users.uid=$uid AND {$gtablepre}messages.mid=$cid AND {$gtablepre}messages.receiver={$gtablepre}users.username AND {$gtablepre}messages.checked=0$where";
			if(!$db->query($sql, 'SILENT')) throw new RuntimeException('附件未能保存，请重试。');
			if(!$db->affected_rows()) throw new InvalidArgumentException('账户或邮件已变化，请刷新后重试。');
			$count++;
			foreach(account_reward_describe($reward) as $line) $info[] = '已领取：'.account_reward_html($line);
			if($extra) $info[] = '重复头衔已转为 '.$extra.' 切糕。';
			if(isset($reward['boon'])) $info[] = '赐福已排入队列，将在传统大房间战斗入场时按领取顺序每次使用一份。';
		} catch(InvalidArgumentException $e) { $info[] = account_reward_html($e->getMessage()); }
	}
	$udata = format_userdata($db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=$uid")));
	return $count;
}

/* End of file messages.func.php */
/* Location: include/messages.func.php */
