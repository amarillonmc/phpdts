<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

$dir = GAME_ROOT.'./gamedata/templates/';
if(!isset($sub_cmd))
{
	$cmd_info = "即将清空路径{$dir}下的.tpl后缀文件，";
	$cmd_info .= "确认清理吗？<br>";
	$cmd_info .= "
	<form method=\"post\" name=\"admin\" onsubmit=\"admin.php\">
	<input type=\"hidden\" name=\"mode\" id=\"mode\" value=\"templates_clean\">
	<input type=\"hidden\" name=\"sub_cmd\" id=\"sub_cmd\" value=\"confirm\">
	<input type=\"submit\" style=\"width:100;height:40;\" value=\"清空\" onclick=\"$('command').value='templates_clean';\">
	</form>
	";
}
else 
{
	$tpl_file = scandir($dir);
	if(!empty($tpl_file))
	{
		$cmd_info = "开始清理缓存文件……<br>";
		foreach($tpl_file as $key => $file_name)
		{
			if(strpos($file_name,'.tpl')!==false)
			{
				$cmd_info .= "已删除文件{$file_name}<br>";
				unlink($dir.$file_name);
			}
		}
		$cmd_info .= "清理完成！<br>";
	}
}


include template('admin_menu');

?>