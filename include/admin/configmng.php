<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

// 主从配置只显示，不允许修改（移到条件外，确保总是可用）
// 为模板提供单独的变量
$display_slave_level = isset($slave_level) ? $slave_level : '';
$display_master_server_name = isset($master_server_name) ? $master_server_name : '';
$display_master_dbhost = isset($master_dbhost) ? $master_dbhost : '';
$display_master_dbuser = isset($master_dbuser) ? $master_dbuser : '';
$display_master_dbpw = !empty($master_dbpw) ? '********' : '';
$display_master_dbname = isset($master_dbname) ? $master_dbname : '';
$display_master_tablepre = isset($master_tablepre) ? $master_tablepre : '';
$display_authkey = !empty($authkey) ? '********' : '';

if($command == 'edit') {

	$ednum = 0;
	$edfmt = Array('authkey'=>'','bbsurl'=>'','gameurl'=>'','homepage'=>'','moveut'=>'int','moveutmin'=>'int','tplrefresh'=>'b','errorinfo'=>'b');
	$edlist = Array();
	$cmd_info = '';
	foreach($edfmt as $key => $val){
		if(isset($_POST[$key])){
			${'o_'.$key} = ${$key};
			if($val == 'int'){
				${$key} = intval($_POST[$key]);
			}elseif($val == 'b'){
				intval($_POST[$key]) != 0 ? ${$key} = 1 : ${$key} = 0;
			}else{
				${$key} = astrfilter($_POST[$key]);
			}
			if(${$key} != ${'o_'.$key}){
				$ednum ++;
				if(${$key}===''){
					$cmd_info .= "$lang[$key] 已清空<br>";
				}else{
					$cmd_info .= "$lang[$key] 修改为 ${$key} <br>";
				}
				$edlist[$key] = ${$key};
			}
		}
	}
	
	$cmd_info .= "提交的修改请求数量： $ednum <br>";
	
	if($ednum){
		//$adminlog = '';
		$configfile = file_get_contents('./config.inc.php');
		foreach($edlist as $key => $val){
			if($edfmt[$key] == 'int' || $edfmt[$key] == 'b'){
				$configfile = preg_replace("/[$]{$key}\s*\=\s*-?[0-9]+;/is", "\${$key} = ${$key};", $configfile);
			}else{
				$configfile = preg_replace("/[$]{$key}\s*\=\s*[\"'].*?[\"'];/is", "\${$key} = '${$key}';", $configfile);
			}
			
			//$adminlog .= setadminlog('configmng',$key,$val);
		}
		file_put_contents('./config.inc.php',$configfile);
		//putadminlog($adminlog);
		adminlog('configmng');
		$cmd_info .= '服务参数已修改';
	}
}
$sysnow = time();
list($nowsec,$nowmin,$nowhour,$nowday,$nowmonth,$nowyear,$nowwday,$nowyday,$nowisdst) = localtime($sysnow);
$nowmonth++;
$nowyear += 1900;
$orin_time = $nowyear.$lang['year'].$nowmonth.$lang['month'].$nowday.$lang['day'].$nowhour.$lang['hour'].$nowmin.$lang['min'];
list($setsec,$setmin,$sethour,$setday,$setmonth,$setyear,$setwday,$setyday,$setisdst) = localtime($sysnow + $moveut*3600 + $moveutmin*60);
$setmonth++;
$setyear += 1900;
$set_time = $setyear.$lang['year'].$setmonth.$lang['month'].$setday.$lang['day'].$sethour.$lang['hour'].$setmin.$lang['min'];

// 转义模板属性值 / Escape values rendered in HTML attributes
$display_authkey_html = htmlspecialchars($display_authkey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$bbsurl_html = htmlspecialchars($bbsurl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$gameurl_html = htmlspecialchars($gameurl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$homepage_html = htmlspecialchars($homepage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_slave_level_html = htmlspecialchars(strval($display_slave_level), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_server_name_html = htmlspecialchars($display_master_server_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_dbhost_html = htmlspecialchars($display_master_dbhost, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_dbuser_html = htmlspecialchars($display_master_dbuser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_dbpw_html = htmlspecialchars($display_master_dbpw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_dbname_html = htmlspecialchars($display_master_dbname, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$display_master_tablepre_html = htmlspecialchars($display_master_tablepre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

include template('admin_configmng');
?>
