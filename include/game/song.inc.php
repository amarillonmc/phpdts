 <?php
 //require_once './include/common.inc.php';

 include_once GAME_ROOT.'./include/game/titles.func.php';
 
 function sing($sn){
	global $log,$msg,$now,$pls,$name,$nick,$plsinfo,$hplsinfo,$ss,$mss,$noiseinfo,$arte;
	global $db,$tablepre;
	global $att,$def;
	global $wep,$wepk,$weps,$wepes,$wepsk;
	global $rp;
	
	//登记非功能性地点信息时合并隐藏地点
	foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;

	//$log.=$sn.'_'.$now.'_'.$pls.'_'.$name."<br>";
	$r=$arte;
	include_once GAME_ROOT.'./include/game/combat.func.php';
	
		if ($ss>=$r){
		$ss-=$r;
		$log.="消耗<span class=\"yellow\">{$r}</span>点歌魂，歌唱了<span class=\"yellow\">{$noiseinfo[$sn]}</span>。<br>";
	}else{
		$log.="需要<span class=\"yellow\">{$r}</span>歌魂才能唱这首歌！<br>";
		return;
	}
	
	if ($sn=="Alicemagic"){
		$log.="♪你說過在哭泣之後應該可以破涕而笑♪<br>
					♪我們的旅行　我不會忘♪<br>
					♪施展魔法　為了不再失去　我不會說再見♪<br>
					♪再次踏出腳步之時　將在某一天到來♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪你說過在哭泣之後應該可以破涕而笑♪')");
		
		//$result = $db->query("select * from {$tablepre}players where `pls`={$pls} and hp>0 and type=0");
		$db->query ( "UPDATE {$tablepre}players SET def=def+30 WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$def+=30;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',get_title_desc($nick).' '.$name,$plsinfo[$pls],$noiseinfo[$sn]);
		return;
		
	}elseif ($sn=="Crow Song"){
			$log.="♪从这里找一条路♪<br>
					♪找到逃离的生路♪<br>
					♪奏响激烈的摇滚♪<br>
					♪盯紧遥远的彼方♪<br>
					♪在这个连呼吸都难以为继的都市中♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪从这里找一条路♪')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$db->query ("UPDATE {$tablepre}players SET att=att+30 WHERE `pls`={$pls} AND hp>0 AND type=0");
		$att+=30;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',get_title_desc($nick).' '.$name,$plsinfo[$pls],$noiseinfo[$sn]);
		return;
	
	
	}elseif ($sn=="恋歌"){
			$log.="♪la la la la♪<br>
					♪la la la la♪<br>
					♪la la la♪<br>
					♪la la la la la♪<br>
					♪la la la ... ...♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪♪la la la la♪♪...')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$ss+=20;
		$mss=$ss;
		$rp-=50;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',get_title_desc($nick).' '.$name,$plsinfo[$pls],$noiseinfo[$sn]);
		return;
	
	
	
	}elseif ($sn=="鸡肉之歌"){
			$log.="♪翼失いながらも優しくて♪<br>
					♪今は静かに眠るこの手の中で♪<br>
					♪ありがとう　感謝の言葉♪<br>
					♪あなたは教えてくれたよ　鶏肉♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪♪la la la la♪♪...')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$db->query ( "UPDATE {$tablepre}players SET wep='鸡肉' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET wepk='wc' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET weps=wepes=55 WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET wepsk='z' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',get_title_desc($nick).' '.$name,$plsinfo[$pls],$noiseinfo[$sn]);
		return;
	}
	
//	if ($ss>=$r){
//		$ss-=$r;
//		$log.="消耗<span class=\"yellow\">{$r}</span>点歌魂，歌唱了<span class=\"yellow\">{$noiseinfo[$sn]}</span>。<br>";
//	}else{
//		$log.="需要<span class=\"yellow\">{$r}</span>歌魂才能唱这首歌！<br>";
//		return;
//	}

	
//	addnoise($sn,'__',$now,$pls,0,0,$sn);
//	addnews($now,'song',$name,$plsinfo[$pls],$noiseinfo[$sn]);
	return;
 }
 ?>