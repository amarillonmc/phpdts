<?php

define('CURSCRIPT', 'vnworld');

require './include/common.inc.php';
require './include/game/itemplace.func.php';
require config('vnworld',$gamecfg);

/*** 登陆检测 ***/
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }
/*** 登陆检测结束 ***/

/*** 页面判定部分 ***/

$vtips = isset($_GET['vtips']) ? $check_infos[$_GET['vtips']] : '';
if(!isset($vnmode)){$vnmode = 'none';}

global $gamefounder;
if($vnmode=='none')
{
	//读取提交过的历史记录。
	include_once GAME_ROOT.'./include/vnworld/vnmix.func.php';
	$carr =  get_queue_vnmix_list();
	if(!empty($carr))
	{
		foreach($carr as $cid => $cinfo) 
		{
			$carr[$cid] = parse_queue_vnmix_info($cinfo);
			for($i=0;$i<5;$i++)
			{
				if(empty($carr[$cid]['stf'.$i])) $carr[$cid]['stf'.$i] = "<span class='grey'>-</span>";
			}
		}
	}
	include template('vnworld');
}

?>
