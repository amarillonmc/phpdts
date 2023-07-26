<?php

	/*User remote system settings*/
	
	//用户数据库远程存放地址（php，一般为userdb_receive.php），留空为存本地
	//开启后，本地数据库相当于缓存，实际以远端数据库为准
	$userdb_remote_storage = '';
	//用户数据远程存放签名
	$userdb_remote_storage_sign = 'local';
	//用户数据远程存放密钥
	$userdb_remote_storage_pass = '142857';
	//接收来自以下地址的用户数据读写
	//键名为地址（其实只是个签名），键值为密钥和IP，应该与发送端上面那个密钥对应。IP留空为不判断。
	$userdb_receive_list = array(
		'local' => Array('pass' => '142857', 'ip' => '127.0.0.1'),
	);

?>