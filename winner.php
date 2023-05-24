<?php

define('CURSCRIPT', 'winner');

require './include/common.inc.php';


if(!isset($command)){$command = 'ref';}
if($command == 'info') {
	$result = $db->query("SELECT * FROM {$gtablepre}winners WHERE gid='$gnum' LIMIT 1");
	$pdata = $db->fetch_array($result);
	$pdata['gdate'] = floor($pdata['gtime']/3600).':'.floor($pdata['gtime']%3600/60).':'.($pdata['gtime']%60);
	$pdata['gsdate'] = date("m/d/Y H:i:s",$pdata['gstime']);
	$pdata['gedate'] = date("m/d/Y H:i:s",$pdata['getime']);
	extract($pdata);
	include GAME_ROOT.'./include/game.func.php';
	init_playerdata();
	init_profile();
} elseif($command == 'news') {
	//include  GAME_ROOT.'./include/news.func.php';
	$hnewsfile = GAME_ROOT."./gamedata/bak/{$gnum}_newsinfo.html";
	if(file_exists($hnewsfile)){
		$hnewsinfo = readover($hnewsfile);
	}
} else {
	if(!isset($start) || !$start){
		$result = $db->query("SELECT gid,name,nick,icon,gd,wep,wmode,teamID,teamMate,teamIcon,getime,motto,hdp,hdmg,hkp,hkill FROM {$gtablepre}winners ORDER BY gid desc LIMIT $winlimit");
	} else {
		$result = $db->query("SELECT gid,name,nick,icon,gd,wep,wmode,teamID,teamMate,teamIcon,getime,motto,hdp,hdmg,hkp,hkill FROM {$gtablepre}winners WHERE gid<='$start' ORDER BY gid desc LIMIT $winlimit");
	}
	while($wdata = $db->fetch_array($result)) {
		$wdata['date'] = date("Y-m-d",$wdata['getime']);
		$wdata['time'] = date("H:i:s",$wdata['getime']);
		if(!empty($wdata['teamMate']))
		{
			$wdata['teamID'] = '<span class="gold">【团队 - '.$wdata['teamID'].'】</span>';
			$wdata['iconImg'] = 't_'.$wdata['teamIcon'].'.gif';
			$wdata['nickinfo'] = '<span class="gold">团队胜利</span>';
			$wdata['name'] = explode("+",$wdata['teamMate']);
		}
		else 
		{
			$wdata['iconImg'] = $wdata['gd'] == 'f' ? 'f_'.$wdata['icon'].'.gif' : 'm_'.$wdata['icon'].'.gif';
			if(!empty($wdata['nick']) && !is_numeric($wdata['nick']))
			{
				$wdata['nickinfo'] = titles_get_desc($wdata['nick'],1);
			}
			else 
			{
				$wdata['nickinfo'] = (!empty($wdata['nick']) || $wdata['nick'] === '0') ? titles_get_desc($wdata['nick']) : '';
			}
		}
		$winfo[$wdata['gid']] = $wdata;
	}
	$listnum = floor($gamenum/$winlimit);

	for($i=0;$i<$listnum;$i++) {
		$snum = ($listnum-$i)*$winlimit;
		$enum = $snum-$winlimit+1;
		$listinfo .= "<input style='width: 120px;' type='button' value='{$snum} ~ {$enum} 回' onClick=\"document['list']['start'].value = '$snum'; document['list'].submit();\">";
		if(is_int(($i+1)/3)&&$i){$listinfo .= '<br>';}
	}
}

include template('winner');

?>