<?php
function weibolog($log){
	global $stateinfo,$state;
	$weibotopic = '';//'#电波大逃杀# ';
	$deathtitle = $stateinfo[$state];
	$cplog = br_replace($log);
	$cplog = cplog($cplog);
	if(mb_strlen($cplog,'utf-8') > 100){
		$logarr = explode(';br;',$cplog);
		//var_dump($logarr);
		$sllog = Array();
		do {
			$sllog[] = array_pop($logarr);
		} while(mb_strlen(implode('',$sllog),'utf-8') <= 100);
		$sllog = array_reverse($sllog);
		unset($sllog[0]);
		$cplog = implode('',$sllog);
//		$logarr = explode('<br><br>',$cplog);
//		$cplog = array_pop($logarr);
		//$cplog = cplog($sllog);
		//var_dump(mb_strlen($cplog,'utf-8'));
	}else{
		//echo $cplog;
		$cplog = str_replace(";br;", "", $cplog);
	}
	$cplog = $weibotopic.$deathtitle . '：' . $cplog . '你死了。';
	return $cplog;
}

function cplog($log){
	$cplog = preg_replace("/([\n\r]+)\t+/s", "", $log);
	$cplog = preg_replace("/\<(.+?)\>/es", "", $log);
	return $cplog;
}

function br_replace($log){
	$log = str_replace("<BR>", "<br>", $log);
	$log = str_replace("<br />", "<br>", $log);
	$log = str_replace("<BR />", "<br>", $log);
	$log = str_replace("<br>", ";br;", $log);
	return $log;
}
?> 