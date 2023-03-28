<?php

define('CURSCRIPT', 'dbup');

require './include/common.inc.php';

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
elseif(($udata['groupid'] <= 1)&&($cuser!==$gamefounder)) { gexit($_ERROR['no_admin'], __file__, __line__); }

$query = $db->query("SHOW TABLES LIKE '{$tablepre}vnmixitem'", 'SILENT');
//print_r($query);
if(!$db->num_rows($query))
{
	$sqldir = GAME_ROOT.'./gamedata/sql/';
	$sql = file_get_contents("{$sqldir}vnworld.sql");
	$sql = str_replace("\r", "\n", str_replace('bra_', ' '.$tablepre, $sql));
	$db->queries($sql);
	echo "Mysql Update Fish.<br>";
	$vcdir = config('queue_vnmixitem',1);
	if(file_exists($vcdir))
	{
		include $vcdir;
		foreach($carr as $key => $arr)
		{
			$vr = Array();
			$vr['class'] = $arr['class'] ?: 'item';
			$vr['istatus'] = $arr['status'];
			$vr['creator'] = $arr['name'];
			for($i=0;$i<5;$i++)
			{
				$vr['stf'.$i] = $arr['stuff'][$i] ?: '';
			}
			$vr['itm'] = $arr['result'][0] ?: '';
			$vr['itmk'] = $arr['result'][1] ?: '';
			$vr['itme'] = $arr['result'][2] ?: '';
			$vr['itms'] = $arr['result'][3] ?: '';
			$vr['itmsk'] = $arr['result'][4] ? implode('',$arr['result'][4]) : '';
			$db->array_insert("{$tablepre}vnmixitem",$vr);
		}
		echo "Old data clear.<br>";
	}
}
else 
{
	echo "No Update.<br>";
}

?>