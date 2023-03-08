<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

//----------------------------------------
//              底层机制函数
//----------------------------------------

function gameerrorhandler($code, $msg, $file, $line){
	global $errorinfo;
	if(!$errorinfo){return;}
	if($code == 2){$emessage = '<b style="color:#ff0">Warning</b> ';}
	elseif($code == 4){$emessage = '<b style="color:#f00">Parse</b> ';}
	elseif($code == 8){$emessage = '<b>Notice</b> ';}
	elseif($code == 256){$emessage = '<b>User Error</b> ';}
	elseif($code == 512){$emessage = '<b>User Warning</b> ';}
	elseif($code == 1024){$emessage = '<b>User Notice</b> ';}
	else{$emessage = '<b style="color:#f00>Fatal error</b> ';}
	$emessage .= "($code): $msg in $file on line $line";
	if(isset($GLOBALS['error'])){
		$GLOBALS['error'] .= '<br>'.$emessage;
	}else{
		$GLOBALS['error'] = $emessage;
	}
	return true;
}

function gexit($message = '',$file = '', $line = 0) {
	global $charset,$title,$extrahead,$allowcsscache,$errorinfo;
	include template('error');
	exit();
}

function output($content = '') {
	//if(!$content){$content = ob_get_contents();}
	//ob_end_clean();
	//$GLOBALS['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();
	//echo $content;
	ob_end_flush();
}

//----------------------------------------
//              输入输出函数
//----------------------------------------

function gstrfilter($str) {
	if(is_array($str)) {
		foreach($str as $key => $val) {
			$str[$key] = gstrfilter($val);
		}
	} else {		
		if($GLOBALS['magic_quotes_gpc']) {
			$str = stripslashes($str);
		}
		$str = str_replace("'","",$str);//屏蔽单引号'
		$str = str_replace("\\","",$str);//屏蔽反斜杠/
		$str = htmlspecialchars($str,ENT_COMPAT);//转义html特殊字符，即"<>&
	}
	return $str;
}

function language($file, $templateid = 0, $tpldir = '') {
	$tpldir = $tpldir ? $tpldir : TPLDIR;
	$templateid = $templateid ? $templateid : TEMPLATEID;

	$languagepack = GAME_ROOT.'./'.$tpldir.'/'.$file.'.lang.php';
	if(file_exists($languagepack)) {
		return $languagepack;
	} elseif($templateid != 1 && $tpldir != './templates/default') {
		return language($file, 1, './templates/default');
	} else {
		return FALSE;
	}
}

function template($file, $templateid = 0, $tpldir = '') {
	global $tplrefresh;

	$tpldir = $tpldir ? $tpldir : TPLDIR;
	$templateid = $templateid ? $templateid : TEMPLATEID;

	$tplfile = GAME_ROOT.'./'.$tpldir.'/'.$file.'.htm';
	$objfile = GAME_ROOT.'./gamedata/templates/'.$templateid.'_'.$file.'.tpl.php';
	if(TEMPLATEID != 1 && $templateid != 1 && !file_exists($tplfile)) {
		return template($file, 1, './templates/default/');
	}
	if($tplrefresh == 1) {
		if(!file_exists($objfile) || filemtime($tplfile) > filemtime($objfile)) {
			require_once GAME_ROOT.'./include/template.func.php';
			parse_template($file, $templateid, $tpldir);
		}
	}
	return $objfile;
}

function content($file = '') {
	ob_clean();
	include template($file);
	$content = ob_get_contents();
	ob_end_clean();
	$GLOBALS['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();
	return $content;
}

function gsetcookie($var, $value, $life = 0, $prefix = 1) {
	global $tablepre, $cookiedomain, $cookiepath, $now, $_SERVER;
	setcookie(($prefix ? $tablepre : '').$var, $value,
		$life ? $now + $life : 0, $cookiepath,
		$cookiedomain, $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);
}

function clearcookies() {
	global $cookiepath, $cookiedomain, $game_uid, $game_user, $game_pw, $game_secques, $adminid, $groupid, $credits;
	dsetcookie('auth', '', -86400 * 365);

	$game_uid = $adminid = $credits = 0;
	$game_user = $game_pw = $game_secques = '';
}

function config($file = '', $cfg = 1) {
	$cfgfile = file_exists(GAME_ROOT."./gamedata/cache/{$file}_{$cfg}.php") ? GAME_ROOT."./gamedata/cache/{$file}_{$cfg}.php" : GAME_ROOT."./gamedata/cache/{$file}_1.php";
	return $cfgfile;
}

function tempfile($file = '') {
	$tempfile = file_exists(GAME_ROOT."./templates/default/{$file}.htm") ? GAME_ROOT."./templates/default/{$file}.htm" : 0;
	return $tempfile;
}

function dir_clear($dir) {
	$directory = dir($dir);
	while($entry = $directory->read()) {
		$filename = $dir.'/'.$entry;
		if(is_file($filename)) {
			unlink($filename);
		}
	}
	$directory->close();
}

//读取文件
function readover($filename,$method="rb"){
	strpos($filename,'..')!==false && exit('Forbidden');
	//$filedata=file_get_contents($filename);
	$handle=fopen($filename,$method);
	if(flock($handle,LOCK_SH)){
		$filedata='';
		while (!feof($handle)) {
   		$filedata .= fread($handle, 8192);
		}
		//$filedata.=fread($handle,filesize($filename));
		fclose($handle);
	} else {exit ('Read file error.');}
	return $filedata;
}

//写入文件
function writeover($filename,$data,$method="rb+",$iflock=1,$check=1,$chmod=1){
	$check && strpos($filename,'..')!==false && exit('Forbidden');
	touch($filename);
	$handle=fopen($filename,$method);
	if($iflock){
		if(flock($handle,LOCK_EX)){
			fwrite($handle,$data);
			if($method=="rb+") ftruncate($handle,strlen($data));
			fclose($handle); 
		} else {var_dump($filename);exit ('Write file error.');}
	} else {
		fwrite($handle,$data);
		if($method=="rb+") ftruncate($handle,strlen($data));
		fclose($handle); 
	}
	$chmod && chmod($filename,0777);
	return;
}

//打开文件，以数组形式返回
function openfile($filename){
	$filedata=readover($filename);
	$filedata=str_replace("\n","\n<:game:>",$filedata);
	$filedb=explode("<:game:>",$filedata);
	$count=count($filedb);
	if($filedb[$count-1]==''||$filedb[$count-1]=="\r"){unset($filedb[$count-1]);}
	if(empty($filedb)){$filedb[0]='';}
	return $filedb;
}

function compatible_json_encode($data){	//自动选择使用内置函数或者自定义函数，结合JSON.php可做到兼容低版本PHP
	if(version_compare(PHP_VERSION, '5.2.0', '<')) {
		require_once GAME_ROOT.'./include/JSON.php';
		$json = new Services_JSON();
		$jdata = $json->encode($data);
	} else{
		$jdata = json_encode($data);
	}
	return $jdata;	
}

//function addnews($t = '', $n = '', $a = '',$b = '', $c = '', $d = '') {
//	global $now,$db,$tablepre;
//	$t = $t ? $t : $now;
//	$newsfile = GAME_ROOT.'./gamedata/newsinfo.php';
//	$newsdata = readover($newsfile); //file_get_contents($newsfile);
//	if(is_array($a)) {
//		$news = "$t,$n,".implode('-',$a).",$b,$c,$d,\n";
//	} elseif(isset($n)) {
//		$news = "$t,$n,$a,$b,$c,$d,\n";
//	}
//	$newsdata = substr_replace($newsdata,$news,53,0);
//	writeover($newsfile,$newsdata,'wb');
//	
//	if(strpos($n,'death11') === 0  || strpos($n,'death32') === 0) {
//		$result = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
//		$lastword = $db->result($result, 0);
//		//$result = $db->query("SELECT pls FROM {$tablepre}players WHERE name = '$a' AND type = '$b'");
//		//$pls = $db->result($result, 0);
//		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$t','$a','$c','$lastword')");
//	}	elseif(strpos($n,'death15') === 0 || strpos($n,'death16') === 0) {
//		$result = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
//		$lastword = $db->result($result, 0);
//		$result = $db->query("SELECT pls FROM {$tablepre}players WHERE name = '$a' AND type = '$b'");
//		$pls = $db->result($result, 0);
//		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$t','$a','$pls','$lastword')");
//	}
//}

//----------------------------------------
//              重要游戏函数
//----------------------------------------

function addnews($t = 0, $n = '',$a='',$b='',$c = '', $d = '', $e = '') {
	global $now,$db,$tablepre;
	$t = $t ? $t : $now;
	$newsfile = GAME_ROOT.'./gamedata/newsinfo.php';
	touch($newsfile);
	if(is_array($a)){
		$a=implode('_',$a);
	}
//	$newsfile = GAME_ROOT.'./gamedata/newsinfo.php';
//	$newsdata = readover($newsfile); //file_get_contents($newsfile);
//	if(is_array($a)) {
//		$news = "$t,$n,".implode('-',$a).",$b,$c,$d,\n";
//	} elseif(isset($n)) {
//		$news = "$t,$n,$a,$b,$c,$d,\n";
//	}
//	$newsdata = substr_replace($newsdata,$news,53,0);
//	writeover($newsfile,$newsdata,'wb');
	if(strpos($n,'death11') === 0  || strpos($n,'death32') === 0) {
		$result = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
		$e = $lastword = $db->result($result, 0);
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$t','$a','$c','$lastword')");
	}	elseif(strpos($n,'death15') === 0 || strpos($n,'death16') === 0) {
		$result = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
		$e = $lastword = $db->result($result, 0);
		$result = $db->query("SELECT pls FROM {$tablepre}players WHERE name = '$a' AND type = '0'");
		$place = $db->result($result, 0);
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$t','$a','$place','$lastword')");
	}
	$db->query("INSERT INTO {$tablepre}newsinfo (`time`,`news`,`a`,`b`,`c`,`d`,`e`) VALUES ('$t','$n','$a','$b','$c','$d','$e')");
}

function logsave($pid,$time,$log = '',$type = 's'){
//	$logfile = GAME_ROOT."./gamedata/log/$pid.log";
//	$date = date("H:i:s",$time);
//	$logdata = "{$date}，{$log}<br>\n";
//	writeover($logfile,$logdata,'ab+');
	
	global $db,$tablepre;
	$ldata['toid']=$pid;
	$ldata['type']=$type;
	$ldata['prcsd']=0;
	$ldata['time']=$time;
	$ldata['log']=$log;
	//$db->query("INSERT INTO {$tablepre}log (toid,type,`time`,log) VALUES ('$pid','$type','$time','$log')");
	$db->array_insert("{$tablepre}log", $ldata);
	return;	
}

function load_gameinfo() {
	global $now,$db,$tablepre;
	global $gamenum,$gamestate,$lastupdate,$starttime,$winmode,$winner,$arealist,$areanum,$areatime,$areawarn,$validnum,$alivenum,$deathnum,$afktime,$optime,$weather,$hack,$combonum,$gamevars;
	$result = $db->query("SELECT * FROM {$tablepre}game");
	$gameinfo = $db->fetch_array($result);
	$gamenum = $gameinfo['gamenum'];
	$gamestate = $gameinfo['gamestate'];
	//$lastupdate = $gameinfo['lastupdate'];
	$starttime = $gameinfo['starttime'];
	$winmode = $gameinfo['winmode'];
	$winner = $gameinfo['winner'];
	$arealist = explode(',',$gameinfo['arealist']);
	$areanum = $gameinfo['areanum'];
	$areatime = $gameinfo['areatime'];
	$areawarn = $gameinfo['areawarn'];
	$validnum = $gameinfo['validnum'];
	$alivenum = $gameinfo['alivenum'];
	$deathnum = $gameinfo['deathnum'];
	$afktime = $gameinfo['afktime'];
	$optime = $gameinfo['optime'];
	$weather = $gameinfo['weather'];
	$hack = $gameinfo['hack'];
	$gamevars = $gameinfo['gamevars'];
	$gamevars = json_decode($gamevars,true);
	if(isset($gamevars['sanmaact']) && isset($gamevars['sanmadead'])) unset($gamevars['sanmaact']);
	$combonum = $gameinfo['combonum'];
	return;
}

function save_gameinfo() {
	global $now,$db,$tablepre;
	global $gamenum,$gamestate,$lastupdate,$starttime,$winmode,$winner,$arealist,$areanum,$areatime,$areawarn,$validnum,$alivenum,$deathnum,$afktime,$optime,$weather,$hack,$combonum,$gamevars;
	if(!isset($gamenum)||!isset($gamestate)){return;}
	if($alivenum < 0){$alivenum = 0;}
	if($deathnum < 0){$deathnum = 0;}
	if(empty($afktime)){$afktime = $now;}
	if(empty($optime)){$optime = $now;}
	$gameinfo = Array();
	$gameinfo['gamenum'] = $gamenum;
	$gameinfo['gamestate'] = $gamestate;
	//$gameinfo['lastupdate'] = $now;//注意此处
	$gameinfo['starttime'] = $starttime;	
	$gameinfo['winmode'] = $winmode;
	$gameinfo['winner'] = $winner;
	$gameinfo['arealist'] = implode(',',$arealist);
	$gameinfo['areanum'] = $areanum;
	$gameinfo['areatime'] = $areatime;
	$gameinfo['areawarn'] = $areawarn;
	$gameinfo['validnum'] = $validnum;
	$gameinfo['alivenum'] = $alivenum;
	$gameinfo['deathnum'] = $deathnum;
	$gameinfo['afktime'] = $afktime;
	$gameinfo['optime'] = $optime;
	$gameinfo['weather'] = $weather;
	//$gamevars0 = ($gamevars['sanmaact'] ? 1 : 0) + ($gamevars['sanmadead'] ? 2 : 0);
	$gameinfo['gamevars'] = json_encode($gamevars);
	$gameinfo['hack'] = $hack;
	$gameinfo['combonum'] = $combonum;
	$db->array_update("{$tablepre}game",$gameinfo,1);
	/*
	$gameinfo = "<?php\n\nif(!defined('IN_GAME')){exit('Access Denied');}\n\n\$gamenum = {$gamenum};\n\$gamestate = {$gamestate};\n\$starttime = {$starttime};\n\$winmode = {$winmode};\n\$winner = '{$winner}';\n\$arealist = array(".implode(',',$arealist).");\n\$areanum = {$areanum};\n\$areatime = {$areatime};\n\$weather = {$weather};\n\$hack = {$hack};\n\$validnum = {$validnum};\n\$alivenum = {$alivenum};\n\$deathnum = {$deathnum};\n\$afktime = {$afktime};\n\$optime = {$optime};\n\n?>";
	$dir = GAME_ROOT.'./gamedata/';
	if($fp = fopen("{$dir}gameinfo.php", 'w')) {
		if(flock($fp,LOCK_EX)) {
			fwrite($fp, $gameinfo);
		} else {
			exit("Couldn't save the game's info !");
		}
		fclose($fp);
	} else {
		gexit('Can not write to cache files, please check directory ./gamedata/ .', __file__, __line__);
	}*/
	return;
}



function save_combatinfo(){
	global $hdamage,$hplayer,$noisetime,$noisepls,$noiseid,$noiseid2,$noisemode;
	if(!$hdamage){$hdamage = 0;}
	if(!$noisetime){$noisetime = 0;}
	if(!$noisepls){$noisepls = 0;}
	if(!$noiseid){$noiseid = 0;}
	if(!$noiseid2){$noiseid2 = 0;}
	$combatinfo = "<?php\n\nif(!defined('IN_GAME')){exit('Access Denied');}\n\n\$hdamage = {$hdamage};\n\$hplayer = '{$hplayer}';\n\$noisetime = {$noisetime};\n\$noisepls = {$noisepls};\n\$noiseid = {$noiseid};\n\$noiseid2 = {$noiseid2};\n\$noisemode = '{$noisemode}';\n\n?>";
	//$combatinfo = "{$hdamage},{$hplayer},{$noisetime},{$noisepls},{$noiseid},{$noiseid2},{$noisemode},\n";
	$dir = GAME_ROOT.'./gamedata/';
	if($fp = fopen("{$dir}combatinfo.php", 'w')) {
		if(flock($fp,LOCK_EX)) {
			fwrite($fp, $combatinfo);
		} else {
			exit("Couldn't save combat info !");
		}
		fclose($fp);
	} else {
		gexit('Can not write to cache files, please check directory ./gamedata/ .', __file__, __line__);
	}
	return;
}

function getchat($last,$team='',$limit=0) {
	global $db,$tablepre,$chatlimit,$chatinfo,$plsinfo,$hplsinfo;
	$limit = $limit ? $limit : $chatlimit;
	$result = $db->query("SELECT * FROM {$tablepre}chat WHERE cid>'$last' AND (type!='1' OR (type='1' AND recv='$team')) ORDER BY cid desc LIMIT $limit");
	$chatdata = Array('lastcid' => $last, 'msg' => array());
	if(!$db->num_rows($result)){$chatdata = array('lastcid' => $last, 'msg' => '');return $chatdata;}

	//登记非功能性地点信息时合并隐藏地点
	$tplsinfo = $plsinfo;
	foreach($hplsinfo as $hgroup=>$hpls) $tplsinfo += $hpls;
	
	while($chat = $db->fetch_array($result)) {
		//if(!$chatdata['lastcid']){$chatdata['lastcid'] = $chat['cid'];}
		if($chatdata['lastcid'] < $chat['cid']){$chatdata['lastcid'] = $chat['cid'];}
		$chat['msg'] = htmlspecialchars($chat['msg']);
		$chat['msg'] = preg_replace('/\[(\w+)\]/', "<img src='img/emoticons/$1.png'>",$chat['msg']);
		if($chat['type'] == '0') {
			$msg = "【{$chatinfo[$chat['type']]}】{$chat['send']}：{$chat['msg']}".date("\(H:i:s\)",$chat['time']).'<br>';
		} elseif($chat['type'] == '1') {
			$msg = "<span class=\"clan\">【{$chatinfo[$chat['type']]}】{$chat['send']}：{$chat['msg']}".date("\(H:i:s\)",$chat['time']).'</span><br>';
		} elseif($chat['type'] == '2') {
			$msg = "<span class=\"lime\">【{$chatinfo[$chat['type']]}】{$chat['send']}：{$chat['msg']}".date("\(H:i:s\)",$chat['time']).'</span><br>';
		} elseif($chat['type'] == '3') {
			if ($chat['msg']){
				$msg = "<span class=\"red\">【{$tplsinfo[$chat['recv']]}】{$chat['send']}：{$chat['msg']} ".date("\(H:i:s\)",$chat['time']).'</span><br>';
			} else {
				$msg = "<span class=\"red\">【{$tplsinfo[$chat['recv']]}】{$chat['send']} 什么都没说就死去了 ".date("\(H:i:s\)",$chat['time']).'</span><br>';
			}
		} elseif($chat['type'] == '4') {
			$msg = "<span class=\"yellow\">【{$chatinfo[$chat['type']]}】{$chat['msg']}".date("\(H:i:s\)",$chat['time']).'</span><br>';
		} elseif($chat['type'] == '5') {
			$msg = "<span class=\"yellow\">【{$chatinfo[$chat['type']]}】{$chat['msg']}".date("\(H:i:s\)",$chat['time']).'</span><br>';
		}
		//表情
		//$msg = preg_replace('/\[(\w+)\]/', "<img src='img/emoticons/$1.png'>", $msg);
		$chatdata['msg'][$chat['cid']] = $msg;
	}
	return $chatdata;
}

//获取表情
function get_emdata()
{
	global $emdata;
	$emdir = 'img/emoticons/';
	$emfiles = glob($emdir . '*.png');
	$emdata = array();
	foreach ($emfiles as $emfile) {
		$name = basename($emfile, '.png');
		$emdata[] = "<img src='$emdir$name.png' alt='$name' onClick=\"insertEm('$name')\">";
  	}
	return $emdata;
}


function storyputchat($time,$type){
	global $db,$tablepre,$now,$syschatinfo,$gamestate,$rdown,$bdown,$ldown,$kdown;
	if(!$time){$time = $now;}
	if($type == 'areawarn'){
		if($gamestate == 20){
			$type = 'areawarn20';
		}else{
			$type = 'areawarn40';
		}
	}elseif($type == 'areaadd'){
		if($gamestate == 20){
			$type = 'areaadd20';
		}else{
			$type = 'areaadd40';
		}
	}
	$msgs = Array();
	$chat = $syschatinfo[$type];
	$list = Array('r' => 0, 'b' => 0, 'l' => 0, 'k'=> 0);
	if($rdown){$list['r'] = 1;}
	if($bdown){$list['b'] = 1;}	
	if($ldown){$list['l'] = 1;}
	if($kdown){$list['k'] = 1;}
	foreach($chat as $val){
		$judge = $val[0];
		$flag = true;
		for($i=0;$i < strlen($judge);$i+=2){
			$judge0 = substr($judge,$i,1);
			$judge1 = substr($judge,$i+1,1);
			if($list[$judge0] != $judge1){
				$flag = false;
				break;
			}
		}
		if($flag){$msgs[] = $val;}
	}
	if(!empty($msgs)){
		shuffle($msgs);
		$msgs = $msgs[0];
		$send = $msgs[1];
		$msg = $msgs[2];
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('2','$time','$send','$msg')");
	}		
	return;
}

function systemputchat($time,$type,$msg = ''){
	global $db,$tablepre,$now;
	if(!$time){$time = $now;}
	if($type == 'areaadd' || $type == 'areawarn'){
		$alist = $msg;
		$msg = '';
		global $plsinfo;
		foreach($alist as $ar) {
			$msg .= "$plsinfo[$ar] ";
		}
		if($type == 'areaadd'){
			$msg = '增加禁区：'.$msg;
		}elseif($type == 'areawarn'){
			$msg = '警告，以下区域即将成为禁区：'.$msg;
		}
	}elseif($type == 'combo'){
		$msg = '游戏进入连斗阶段！';
	}elseif($type == 'comboupdate'){
		$msg = '连斗死亡判断数修正为'.$msg.'人！';
	}elseif($type == 'duel'){
		$msg = '游戏进入死斗模式！';
	}elseif($type == 'newgame'){
		$msg = '游戏开始！';
	}elseif($type == 'gameover'){
		$msg = '游戏结束！';
	}
	$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('5','$time','','$msg')");
	return;
}

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function putmicrotime($t_s,$t_e,$file,$info)
{
	$mtime = ($t_e - $t_s)*1000;
	writeover( $file.'.txt',"$info ；执行时间：$mtime 毫秒 \n",'ab');
}

//格式化储存player表 可能也是四面的遗产
function update_db_player_structure($type=0)
{
	global $db,$tablepre,$checkstr;
	$db_player_structure = $db_player_structure_types = $tpldata = Array();
	
	$dps_need_update = 0;//判定是否需要更新玩家字段
	$dps_file = GAME_ROOT.'./gamedata/bak/db_player_structure.config.php';
	$sql_file = GAME_ROOT.'./gamedata/sql/players.sql';
	if(!file_exists($dps_file) || filemtime($sql_file) > filemtime($dps_file)){
		$dps_need_update = 1;
	}
	
	if($dps_need_update){//如果要更新，直接新建一个表，不需要依赖已有的players表
		$sql = file_get_contents($sql_file);
		$sql = str_replace("\r", "\n", str_replace(' bra_', ' '.$tablepre.'tmp_', $sql));
		$db->queries($sql);
		$result = $db->query("DESCRIBE {$tablepre}tmp_players");
		while ($sttdata = $db->fetch_array($result))
		{
			global ${$sttdata['Field']}; 
			$db_player_structure[] = $sttdata['Field'];
			$db_player_structure_types[$sttdata['Field']] = $sttdata['Type'];
			//array_push($db_player_structure,$pdata['Field']);
		}
		$dps_cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
		$dps_cont .= '$db_player_structure = ' . var_export($db_player_structure,1).";\r\n".'$db_player_structure_types = ' . var_export($db_player_structure_types,1).";\r\n?>";
		writeover($dps_file, $dps_cont);
		chmod($dps_file,0777);
		
	}else{//若不需要更新，则直接读文件就好
		include $dps_file ;
	}
	return $type ? $db_player_structure_types : $db_player_structure;
}
//返回一个只有数据库合法字段键名的pdata数组
function player_format_with_db_structure($data){
    $ndata=Array();
    $db_player_structure = update_db_player_structure();
    foreach ($db_player_structure as $key)
    {
        if(isset($data[$key]) && is_array($data[$key])) $data[$key]=json_encode($data[$key]);
		$ndata[$key]=isset($data[$key]) ? $data[$key] : '';
    }
    return $ndata;
}
function parse_info_desc($info,$type,$vars='',$short=0)
{
	global $iteminfo,$itemspkinfo,$cskills;
	global $tps_name,$tps_names,$tps_ik,$tps_isk;

	# 处理名字
	if($type == 'm')
	{
		$tinfo = !isset($tps_name[$info]) && !isset($tps_names[$info]) ? preg_replace('/锋利的|电气|毒性|\[\+.*\]|-改/', '', $info) : $info;
		if(isset($tps_name[$tinfo]) && !is_array($tps_name[$tinfo]) && isset($tps_names[$tps_name[$tinfo]]))
		{
			$ts = $tps_names[$tps_name[$tinfo]];
			$tinfo_f = isset($ts['class']) ? "class=\"{$ts['class']}\"" : '';
			$tinfo_tp = isset($ts['title']) ? "tooltip=\"{$ts['title']}\"" : '';
			return "<span {$tinfo_tp} {$tinfo_f}>{$info}</span>";
		}
		elseif(isset($tps_name[$tinfo]))
		{
			$tinfo_f = isset($tps_name[$tinfo]['class']) ? "class=\"{$tps_name[$tinfo]['class']}\"" : '';
			$tinfo_tp = isset($tps_name[$tinfo]['title']) ? "tooltip=\"{$tps_name[$tinfo]['title']}\"" : '';
			return "<span {$tinfo_tp} {$tinfo_f}>{$info}</span>";
		}
		return $info;
	}
	# 处理类别
	if($type == 'k')
	{
		foreach($iteminfo as $info_key => $info_value)
		{
			if(strpos($info,$info_key)===0) 
			{
				$v_info = $info_key;
				break;
			}
		}
		# 类别不存在样式或提示时，用大类尝试一下
		if(!isset($tps_ik[$info])) $info = $v_info;
		$info_f = isset($tps_ik[$info]['class']) ? "class=\"{$tps_ik[$info]['class']}\"" : '';
		$info_tp = isset($tps_ik[$info]['title']) ? "tooltip=\"{$tps_ik[$info]['title']}\"" : '';
		if(!isset($iteminfo[$info])) $info = $v_info;
		return "<span {$info_tp} {$info_f}>{$iteminfo[$info]}</span>";
	}
	# 处理属性
	if($type == 'sk')
	{
		$ret = '--';
		# 处理该数量以上的属性时，将属性格式变为+...+的缩写
		$short_nums = 4;
		# 技能书特殊处理
		if($vars == 'VS')
		{
			if(!empty($info) && isset($cskills[$info]))
			{
				$sk = $cskills[$info];  $sknm = $cskills[$info]['name'];
				return "<span tooltip=\"阅读后可习得技能「{$sknm}」\">知识</span>";
			}
			return "--";
		}
		# 正常处理属性
		else
		{
			# 数组化
			if(!is_array($info)) $info = get_itmsk_array($info); 
			# 计数
			$sk_max = count($info); $sk_nums = 0; 
			$sk_info = ''; $sk_tp = '';
			foreach($info as $sk)
			{
				$csk = $itemspkinfo[$sk];
				# 检查属性有没有特殊样式
				if(isset($tps_isk[$sk]['class'])) $csk = "<span class=\"".$tps_isk[$sk]['class']."\">".$csk."</span>"; 
				# 将属性加入显示队列
				$sk_info .= $csk;
				# 如果不是最后一个属性 显示一个 + 号
				if($sk_nums<$sk_max-1) $sk_info .= '+';
				# 检查属性有没有tooltip
				if(isset($tps_isk[$sk]['title']))
				{
					if($sk_max > 1)
					{
						$sk_tp .= "【{$itemspkinfo[$sk]}】".$tps_isk[$sk]['title'];
						if($sk_nums<$sk_max-1) $sk_tp .= "\r";
					}
					else 
					{
						$sk_tp = $tps_isk[$sk]['title'];
					}
				}
				$sk_nums++;
			}
			if(!empty($sk_info)) $ret = $sk_info;
			if($sk_max > $short_nums && $short) $ret = $itemspkinfo[$info[0]]."+...+".$itemspkinfo[end($info)];
			if(!empty($sk_tp)) 
			{
				$ret = "<span tooltip=\"{$sk_tp}\">{$ret}</span>";
			}
		}
		return $ret;
	}
	return $info;
}

//为显示在主界面、尸体发现界面、游戏帮助界面的道具名、道具类、道具属性添加额外描述
//传入$n=道具名/类/属性；$t='m'(使用名称数组)/'k'(类别)/'sk'(属性)；$short=1(传入的$n为数组情况下才有效，缩写属性)；$class(如果传入的$n没有匹配的样式,则应用该样式)
function parse_itm_desc($n,$t,$s=0,$c=NULL)
{
	global $iteminfo,$itemspkinfo,$cskills;
	global $tps_name,$tps_ik,$tps_isk,$tps_names;
	$span = "<span "; $p1 = "tooltip=\""; $p2 = "class=\""; $ret1 = ''; $ret2 = ''; $ret = '';
	switch($t)
	{
		//处理类别
		case $t=='k':
			if(isset($tps_ik[$n]['title'])) $ret1 = $tps_ik[$n]['title']."\"";				
			if(isset($tps_ik[$n]['class'])) $ret2 = $tps_ik[$n]['class']."\"";
			$n = $iteminfo[$n];
			break;
		//处理属性
		case $t=='sk':
			if($short && is_array($n) && count($n)>1)
			{
				$sk1 = $itemspkinfo[current($n)]; $sk2 = $itemspkinfo[end($n)]; $skn = '';
				foreach($n as $sk_value)
				{
					if(!empty($skn)) $skn .='+'.$itemspkinfo[$sk_value];
					else $skn = $itemspkinfo[$sk_value];
				}
				$ret1=$skn; $n = $sk1.'+...+'.$sk2; $ret1.= "\"";
			}
			else
			{
				if(isset($tps_isk[$n]['title'])) $ret1= $tps_isk[$n]['title']."\"";
				if(isset($tps_isk[$n]['class'])) $ret2= $tps_isk[$n]['class']."\"";
				$n = $itemspkinfo[$n];
			}
			break;
		//处理名字
		case $t=='m':
			$fn = preg_replace('/锋利的|电气|毒性|\[.*\]|-改/', '', $n);
			if(isset($tps_name[$fn]))
			{
				if(is_array($tps_name[$fn]))
				{
					if(isset($tps_name[$fn]['title'])) $ret1= $tps_name[$fn]['title']."\"";
					if(isset($tps_name[$fn]['class'])) $ret2= $tps_name[$fn]['class']."\"";
				}
				elseif(isset($tps_names[$tps_name[$fn]]))
				{	//使用可复用描述 越来越离谱了
					if(isset($tps_names[$tps_name[$fn]]['title'])) $ret1= $tps_names[$tps_name[$fn]]['title']."\"";
					if(isset($tps_names[$tps_name[$fn]]['class'])) $ret2= $tps_names[$tps_name[$fn]]['class']."\"";
				}
			}
			break;
	}
	$ret = $span;
	if(!empty($ret1)) $ret .= $p1.$ret1;
	if(isset($c))
	{
		$ret2 = $c."\"";
		$ret .= $p2.$ret2;
	}
	elseif(!empty($ret2))
	{
		$ret .= $p2.$ret2;
	}
	$ret .= ">".$n."</span>";
	return $ret;
}

//----------------------------------------
//              字符串处理
//----------------------------------------

//将sk转为数组格式 只会转换登记过的属性
function get_itmsk_array($sk_value)
{
	global $itemspkinfo;
	$ret = Array();
	$i = 0;
	while ($i < strlen($sk_value))
	{
		$sub = mb_substr($sk_value,$i,1,'utf-8'); 
		$i++;
		if(!empty($sub) && array_key_exists($sub,$itemspkinfo)) array_push($ret,$sub);
	}
	return $ret;		
}

//还原itmsk为字符串 $max_length:字符串长度上限 
function get_itmsk_strlen($sk_value,$max_length=30)
{
	global $itemspkinfo;
	$ret = ''; $sk_count = 0;
	foreach($sk_value as $sk)
	{
		if(array_key_exists($sk,$itemspkinfo))
		{
			$ret.=$sk;
			$sk_count+=strlen($sk);
		}
		if($sk_count>=$max_length) break;
	}
	return $ret;
}

//将clbpara转为数组
function get_clbpara($para)
{
	if(empty($para)) $para = Array();
	if(!is_array($para)) return json_decode($para,true);
	else return $para;
}
//获取clbpara中指定键
function get_single_clbpara($para,$key)
{
	if(!is_array($para)) $para = get_clbpara($para);
	if(isset($para[$key])) return $para[$key];
	return;
}
//删除clbpara中指定键
function del_single_clbpara($para,$key)
{
	if(!is_array($para)) $para = get_clbpara($para);
	if(isset($para[$key])) unset($para[$key]);
	return $para;
}
//修改clbpara中指定键
function set_clbpara($para,$key,$value)
{
	if(!is_array($para)) $para = get_clbpara($para);
	if(isset($para[$key])) $para[$key] = $value;
	return $para;
}

function mgzdecode($data)
{
	return gzinflate(substr($data,10,-8));
}

//数组压缩转化为纯字母数字
function gencode($para){
	return base64_encode(gzencode(json_encode($para)));
}

//gencode函数的逆运算
function gdecode($para, $assoc = false){
	$assoc = $assoc ? true : false;
	if (!$para) return array();
	else return json_decode(mgzdecode(base64_decode($para)),$assoc);
}

//mb_strlen()兼容替代函数，直接照抄的网络
if ( !function_exists('mb_strlen') ) {
	function mb_strlen ($text, $encode='UTF-8') {
		if ($encode=='UTF-8') {
			return preg_match_all('%(?:
			[\x09\x0A\x0D\x20-\x7E]           # ASCII
			| [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]       # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]       # excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
			)%xs',$text,$out);
		}else{
			return strlen($text);
		}
	}
}

//mb_substr()兼容替代函数，直接照抄的网络
if (!function_exists('mb_substr')) {
	function mb_substr($str, $start, $len = '', $encoding='UTF-8'){
		$limit = strlen($str);

		for ($s = 0; $start > 0;--$start) {// found the real start
			if ($s >= $limit)
			break;

			if ($str[$s] <= "\x7F")
			++$s;
			else {
				++$s; // skip length

				while ($str[$s] >= "\x80" && $str[$s] <= "\xBF")
				++$s;
			}
		}

		if ($len == '')
		return substr($str, $s);
		else
		for ($e = $s; $len > 0; --$len) {//found the real end
			if ($e >= $limit)
			break;

			if ($str[$e] <= "\x7F")
			++$e;
			else {
				++$e;//skip length

				while ($str[$e] >= "\x80" && $str[$e] <= "\xBF" && $e < $limit)
				++$e;
			}
		}

		return substr($str, $s, $e - $s);
	}
}
?>
