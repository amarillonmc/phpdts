<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

$title2qiegao = 50;//已有头衔再次获得时转成多少切糕。单独一项配置不想放在其他文件里，干脆就丢这
$messages_autocreatedb = 1;//自动建表功能，一个很丑陋的开关

function init_messages($mode){
	if('showdel' == $mode || 'recover' == $mode) {
		return deleted_message_load();
	}else{
		return message_load();
	}
}

//判断有没有新站内信，基本上每次载入页面都需要调用
//因为check这个词用在查收站内信上了，不要吐槽这个命名
function is_there_new_messages(){
	global $db, $gtablepre, $cuser, $new_messages, $messages_autocreatedb;
	$new_messages = 0;
	if($cuser){
		//考虑到devtools.php也得先载入common.inc.php，从而如果没有建表就会直接出错，必须在这里就做判断是否存在message表
		//而既然做了判断为什么不直接建表呢？
		//可以把$messages_autocreatedb关掉来阻止这罪恶的一切
		if(!empty($messages_autocreatedb)){
			$result = $db->query("SHOW TABLES LIKE '{$gtablepre}messages'");
			$ret = $db->fetch_array($result);
			if(empty($ret)){
				create_messages_db();
			}
		}
		
		$result = $db->query("SELECT mid FROM {$gtablepre}messages WHERE receiver='$cuser' AND rd=0");
		$new_messages = $db->num_rows($result);
	}
}

//很丑陋
function create_messages_db(){
	global $db, $gtablepre;
	$query = 
"DROP TABLE IF EXISTS bra_messages;
CREATE TABLE bra_messages (
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

DROP TABLE IF EXISTS bra_del_messages;
CREATE TABLE bra_del_messages (
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

function message_create($to, $title='', $content='', $enclosure='', $from='sys', $t=0)
{
	global $now,$db,$gtablepre;
	if(!$t) $t = $now;
	if(!$to) return;
	$ins_arr = array(
		'timestamp' => $t,
		'sender' => $from,
		'receiver' => $to,
		'title' => $title,
		'content' => $content,
		'enclosure' => $enclosure
	);
	$db->array_insert("{$gtablepre}messages", $ins_arr);
}

//虽然直接放到sys模块里了，但是某些地方需要第二次更新的话，还是需要这个
function message_check_new($username)
{
	global $db,$gtablepre;
	$result = $db->query("SELECT mid FROM {$gtablepre}messages WHERE receiver='$username' AND rd=0");
	$num = $db->num_rows($result);
	return $num;
}

function message_load($mid_only=0)
{
	global $udata,$db,$gtablepre;
	$username = $udata['username'];
	if($mid_only) $result = $db->query("SELECT mid FROM {$gtablepre}messages WHERE receiver='$username' ORDER BY timestamp DESC, mid DESC");
	else $result = $db->query("SELECT * FROM {$gtablepre}messages WHERE receiver='$username' ORDER BY timestamp DESC, mid DESC");
	$messages = array();
	while($r = $db->fetch_array($result)){
		$messages[$r['mid']] = $r;
	}
	return $messages;
}

function deleted_message_load()
{
	global $udata,$db,$gtablepre;
	$username = $udata['username'];
	$result = $db->query("SELECT * FROM {$gtablepre}del_messages WHERE receiver='$username' ORDER BY dtimestamp DESC, mid DESC");
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
	global $udata, $titles_list, $title_desc;
	
	foreach($messages as $mi => &$mv){
		$mv['hint'] = '<span class="L5 b">NEW!</span>';
		if($mv['rd']) $mv['hint'] = '';
		
		$mv['time_disp'] = date("Y年m月d日 H:i:s", $mv['timestamp']);
		if(isset($mv['dtimestamp'])) $mv['del_time_disp'] = date("Y年m月d日 H:i:s", $mv['dtimestamp']);
		$mv['encl_disp'] = '';
		if(!empty($mv['enclosure'])){
			
			if($mv['checked']) $mv['encl_hint'] = '<span class="grey b">附件已收</span>';
			else $mv['encl_hint'] = "<a class='L5 b' onclick=\"$('extracmd').name='sl$mi';$('extracmd').value='1';$('mode').value='check';postCmd('message_cmd', 'messages.php');$('extracmd').name='extracmd';$('extracmd').value='';\">附件<br>点此查收</a>";

			//切糕判定
			$getqiegao = message_get_encl_num($mv['enclosure'], 'getqiegao');
			if($getqiegao) {
				$mv['encl_disp'] .= '<div class="gold b">'.$getqiegao.'切糕</div>';
			}
			//头衔判定
			$gettitle = message_get_encl_num($mv['enclosure'], 'gettitle');
			if($gettitle && !empty($titles_list[$gettitle])) {//不存在的头衔不要发
				$nicksrev_disp = is_array($udata['nicksrev']) ? $udata['nicksrev'] : json_decode($udata['nicksrev'],true);
				$nownew = !in_array($gettitle, $nicksrev_disp['nicks']);
				$mv['encl_disp'] .= '<div>头衔：<span class="'.$title_desc[$gettitle]['class'].'">'.$titles_list[$gettitle].($nownew ? ' <span class="L5 b">NEW!</span>' : '').'</span></div>';
			}
		}
	}
	return $messages;
}

//查收站内信
function message_check($checklist, $messages)
{
	global $udata,$db,$gtablepre,$info, $titles_list, $title_desc, $title2qiegao;
	
	if(empty($udata['nicksrev'])) $udata['nicksrev'] = Array('nicks' => Array(0));
	//不知道$nicksrev在保存前究竟要不要手动转义，保险点不改原值
	$nicksrev_disp = is_array($udata['nicksrev']) ? $udata['nicksrev'] : json_decode($udata['nicksrev'],true);
	
	$getqiegaosum = $gettitleflag = 0;
	
	foreach($checklist as $cid){
		if($messages[$cid]['checked']) continue;
		if(!empty($messages[$cid]['enclosure'])){
			//获得切糕
			$getqiegao = message_get_encl_num($messages[$cid]['enclosure'], 'getqiegao');
			if($getqiegao) {
				$info[] = '获得了<span class="gold b">'.$getqiegao.'切糕</span>';
				$getqiegaosum += $getqiegao;
			}
			//获得卡片
			$gettitle = message_get_encl_num($messages[$cid]['enclosure'], 'gettitle');
			if(!empty($gettitle)) {
				$getname = $titles_list[$gettitle];
				if(!in_array($gettitle, $nicksrev_disp['nicks'])) {
					$info[] = '获得了头衔 “<span class="'.$title_desc[$gettitle]['class'].'">'.$titles_list[$gettitle].'</span>”！';
					titles_get_new($udata, $gettitle);
				}else {
					$info[] = '已有头衔 “<span class="'.$title_desc[$gettitle]['class'].'">'.$titles_list[$gettitle].'</span>”，转化为了'.$title2qiegao.'切糕！';
					$getqiegaosum += $title2qiegao;
				}
				$gettitleflag = 1;
			}
		}
	}
	
	if($getqiegaosum || $gettitle) {//头衔在titles_get_new()似乎就已经更新了，这里只更新切糕
		$n = $udata['username'];
		$c = $udata['credits2']+$getqiegaosum;
		$t = $udata['nicksrev'];
		$db->array_update("{$gtablepre}users", Array('credits2' => $c, 'nicksrev' => $t), "username='".$n."'");
	}
}

/* End of file messages.func.php */
/* Location: include/messages.func.php */