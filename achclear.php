<?php

define('CURSCRIPT', 'achclear');

require './include/common.inc.php';

if(!$udata) { gexit($_ERROR['no_login'],__file__,__line__); }
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
elseif(($udata['groupid'] <= 1)&&($cuser!==$gamefounder)) { gexit($_ERROR['no_admin'], __file__, __line__); }

$action = !empty($_POST['action']) ? $_POST['action'] : $_GET['action'];

if(empty($action))
{
	echo '点击开始批量转换用户成就数据<br>
	（操作前，请手动备份用户表数据，以避免数据丢失）<br>
	<a href="achclear.php?action=start" style="text-decoration: none">
		<span><font color="green">[开始]</font></span>
	</a><br><br>';
}

if (isset($action) && $action=='start')
{
	# 将旧成就数据格式转为新格式

	header('Location: achclear.php');
}




?>