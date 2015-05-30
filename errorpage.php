<?php
	@$error = $_SERVER['REDIRECT_STATUS'];
	@$referring_url = $_SERVER['HTTP_REFERER'];
	@$requested_url = $_SERVER['REQUEST_URI'];
	@$referring_ip = $_SERVER['REMOTE_ADDR'];
	@$server_name = $_SERVER['SERVER_NAME'];
	
	if( $error == 200 ) die();
	
	$titles = array(
							401 => "错误401 - 认证失败",
							403 => "错误403 - 拒绝访问",
							404 => "错误404 - 文件未找到",
							500 => "错误500 - 服务器内部错误",
							503 => "错误503 - 服务暂时不可用",
							);
	
	if( isset($titles[$error]) ){
		$title = $titles[$error];
	}else{
		$title = "未知错误";
	}
	$img = "/img/error/$error.jpg";
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title;?></title>
</head>

<body style="text-align: center;">

<img src="<?php echo $img;?>" style="" />

</body>
</html>