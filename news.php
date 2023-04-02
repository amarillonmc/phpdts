<?php

define('CURSCRIPT', 'news');

require './include/common.inc.php';
require './include/game.func.php';
//$t_s=getmicrotime();
//require_once GAME_ROOT.'./include/JSON.php';
require_once GAME_ROOT.'./include/news.func.php';
include_once GAME_ROOT.'./include/system.func.php';

/*
$newsfile = GAME_ROOT.'./gamedata/newsinfo.php';
$newshtm = GAME_ROOT.TPLDIR.'/newsinfo.htm';
$lnewshtm = GAME_ROOT.TPLDIR.'/lastnews.htm';

if(filemtime($newsfile) > filemtime($lnewshtm)) {
	$lnewsinfo = nparse_news(0,$newslimit);
	writeover($lnewshtm,$lnewsinfo);
}*/

$last_newsinfo = nparse_news(0,50);

if(!isset($newsmode)){$newsmode = '';}

if($newsmode == 'last') {
	echo $last_newsinfo;
	$newsdata['innerHTML']['newsinfo'] = ob_get_contents();
	if(isset($error)){$newsdata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($newsdata);
//	$json = new Services_JSON();
//	$jgamedata = $json->encode($newsdata);
	echo $jgamedata;
	ob_end_flush();
} elseif($newsmode == 'all') {
	
	/*if(filemtime($newsfile) > filemtime($newshtm)) {
		$newsinfo = nparse_news(0,65535);
		writeover($newshtm,$newsinfo);
	}*/
	$newsinfo = nparse_news(0,65535);
	echo $newsinfo;
	$newsdata['innerHTML']['newsinfo'] = ob_get_contents();
	if(isset($error)){$newsdata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($newsdata);
	//$json = new Services_JSON();
	//$jgamedata = $json->encode($newsdata);
	echo $jgamedata;
	ob_end_flush();	

} elseif($newsmode == 'chat') {
	$newsdata['innerHTML']['newsinfo'] = '';
	$chats = getchat(0,'',$chatinnews);
	$chatmsg = $chats['msg'];
	foreach($chatmsg as $val){
		$newsdata['innerHTML']['newsinfo'] .= $val;
	}	
	if(isset($error)){$newsdata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($newsdata);
//	$json = new Services_JSON();
//	$jgamedata = $json->encode($newsdata);
	echo $jgamedata;
	ob_end_flush();
} else {
	include template('news');
}
//$t_e=getmicrotime();
//putmicrotime($t_s,$t_e,'news_time');

?>	
