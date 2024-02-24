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
	$max_gamenum = 0;
	$result = $db->query("SELECT gid FROM {$gtablepre}winners ORDER BY gid DESC LIMIT 1");
	if ($db->num_rows($result)) {
		 $tmp_rst=$db->fetch_array($result); 
		 $max_gamenum = $tmp_rst['gid']; 
	}
	
	//$start预处理
	if(!isset($start) || !$start){
		$start = 0;
	}else{
		$start = (int)$start;
		if($start > $max_gamenum) $start = $max_gamenum;
		elseif($start < $winlimit) $start = $winlimit;
	}
	//生成query
	//gid起始条件（翻页）
	$query_gid = $start > 0 ? "gid<='$start'" : "";
	//gmode条件（游戏模式）
	$query_gtype = '';
	// $show_gtype_arr = array();
	// for($i=0;$i<=20;$i++) {
	// 	if(!empty(${'winner_show_gtype_'.$i})) $show_gtype_arr[] = $i;
	// }
	// sort($show_gtype_arr);
	// if(!empty($show_gtype_arr)) {
	// 	$query_gtype = "gametype IN ('".implode("','",$show_gtype_arr)."')";
	// }
	//wmode条件（胜利类型）
	$query_wmode = ''; $show_wmode_arr = array();
	for($i=1;$i<=7;$i++) {
		if(!empty(${'winner_show_wmode_'.$i})) $show_wmode_arr[] = $i;
	}
	sort($show_wmode_arr);
	if(!empty($show_wmode_arr)) {
		$query_wmode = "wmode IN ('".implode("','",$show_wmode_arr)."')";
	}
	//winner条件（获胜者）
	$query_winner = '';
	if(!empty($winner_show_winner)) {
		$query_winner = "name='$winner_show_winner'";
	}
	//先不拼接gid条件（当前局数指针），为了获得所有符合查找条件的结果gid，并获取最大和最小值
	$query_where = '';
	if(!empty($query_wmode) || !empty($query_gtype)  || !empty($query_winner)) {
		$query_where .= $query_wmode;
		$query_where .= (!empty($query_where) && !empty($query_gtype) ? ' AND ' : '') . $query_gtype;
		$query_where .= (!empty($query_where) && !empty($query_winner) ? ' AND ' : '') . $query_winner;
		$query_where = 'WHERE '.$query_where;
	}
	$query_count = "SELECT gid FROM {$gtablepre}winners $query_where ORDER BY gid DESC";
	$result = $db->query($query_count);
	$result_num = $db->num_rows($result);
	
	$max_result_gamenum = $min_result_gamenum = 0;
	$winfo = array();
	$largest_mark = $larger_mark = $smaller_mark = $smallest_mark= 0;
	
	if($result_num){
		$wgidarr = Array();
		while($wgid = $db->fetch_array($result)) {
			$wgidarr[] = $wgid['gid'];
		}
		rsort($wgidarr);
		$max_result_gamenum = $wgidarr[0];
		$min_result_gamenum = $wgidarr[$result_num-1];
		
		//然后拼接含gid（当前局数指针）的WHERE条件
		$query_where = '';
		if(!empty($query_gid) || !empty($query_wmode) || !empty($query_gtype) || !empty($query_winner)) {
			$query_where .= $query_gid;
			$query_where .= (!empty($query_where) && !empty($query_wmode) ? ' AND ' : '') . $query_wmode;
			$query_where .= (!empty($query_where) && !empty($query_gtype) ? ' AND ' : '') . $query_gtype;
			$query_where .= (!empty($query_where) && !empty($query_winner) ? ' AND ' : '') . $query_winner;
			$query_where = 'WHERE '.$query_where;
		}
		$query_limit = "SELECT gid,name,nick,icon,gd,wep,wmode,teamID,teamMate,teamIcon,getime,motto,hdp,hdmg,hkp,hkill FROM {$gtablepre}winners $query_where ORDER BY gid DESC LIMIT $winlimit";
		//echo $query;
		$result = $db->query($query_limit);
		
		$download_button = [];
	
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
			//遍历./records/$wdata['gid']/下的所有gz文件
			$filelist = glob("./records/{$wdata['gid']}/**/*.gz");
			foreach ($filelist as $file) {
				//下载按钮,html
				$dirname = basename(dirname($file));
				if(empty($download_buttons[$wdata['gid']])) $download_buttons[$wdata['gid']] = "";
				$download_buttons[$wdata['gid']] .= "<br><a href=\"$file\" download=\"$dirname\">下载 $dirname</a>";
			}
		}
		//判断分页情况
		$winfo_keys=array_keys($winfo);rsort($winfo_keys);
		$max_wdata_num=$winfo_keys[0];
		$min_wdata_num = $winfo_keys[sizeof($winfo_keys)-1];
		if($result_num > $winlimit){
			if(!isset($start) || !$start) $start = $max_result_gamenum;
			
			$largest_mark = $max_result_gamenum;
			$smallest_mark = max($min_result_gamenum, $winlimit);
			if(in_array($start, $wgidarr)) {
				$start_n = array_search($start, $wgidarr);
			}else{
				$tmp_wgidarr = $wgidarr;
				$tmp_wgidarr[] = $start;
				rsort($tmp_wgidarr);
				$start_n = array_search($start, $tmp_wgidarr);
			}
			if($start < $largest_mark) {
				//上一页，需要用到所有符合条件的gid数组$wgidarr
				//注意这里larger和smaller是从gid绝对值角度而言的，从数组角度larger的下标反而小，smaller的下标反而大
				$larger_n = max(0, $start_n - $winlimit);
				$larger_mark = $wgidarr[$larger_n];
			}
			if($start > $smallest_mark) {
				$smaller_n = min($result_num-1, $start_n + $winlimit);
				$smaller_mark = $wgidarr[$smaller_n];
			}
		}
	}
}

include template('winner');

?>