<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle trap items
function item_trap($itmn, &$data) {
	global $log, $mode, $nosta, $db, $tablepre, $upexp;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	if(!check_skill_unlock('c13_master', $data))
	{
		$log .= "你老脸一红，只觉得自己是被鬼迷了心窍，怎么会起了这种卑劣的念头！<br>羞愤之下，你一口把<span class='yellow'>{$itm}</span>吞进了肚子。<br>";
		$itms = 0;
		destory_single_item($data, $itmn, 1);
		$mode = 'command';
		return;
	}

	$trapk = str_replace('TN', 'TO', $itmk);

	if($clbpara['BGMBrand'] == 'rixolamal'){
		$trapk = str_replace('TO', 'TOr', $itmk);
		$log .= "你对随机数大神的反叛让随机数大神将<span class=\"red\">$itm</span>变成了一个随机造成伤害的地雷！<br>";
	}

	// 回收的AI异常雷保留异常，归属改为本次设置者；其他陷阱沿用原行为。
	$trappara = get_itmpara($data['itmpara'.$itmn]);
	if(ai93_enabled() && isset($trappara['ai93_inf']) && in_array($trappara['ai93_inf'],array('i','u','e','p','w'))) {
		$db->array_insert($tablepre.'maptrap',array('itm'=>$itm,'itmk'=>$trapk,'itme'=>$itme,'itms'=>1,'itmsk'=>$pid,'pls'=>$pls,'itmpara'=>json_encode($trappara,JSON_UNESCAPED_UNICODE)));
	} else {
		$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$itm', '$trapk', '$itme', '1', '$pid', '$pls')");
	}
	ai93_trigger(3,$data);
	$log .= "设置了陷阱<span class=\"red\">$itm</span>。<br>小心，自己也很难发现。<br>";
	
	if($club == 5){$exp += 2;$wd+=2;}
	else{$exp++;$wd++;}
	
	if ($exp >= $upexp) {
		include_once GAME_ROOT . './include/state.func.php';
		lvlup_rev($data, $data, 1);
	}
	if ($itms != $nosta) {
		$itms --;
		if ($itms <= 0) {
			$log .= "<span class=\"red\">$itm</span>用光了。<br>";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	}
}

// Handle trap detector items
function item_trap_detector($itmn, &$data) {
	global $log, $nosta, $db, $tablepre;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$trapresult = $db->query("SELECT * FROM {$tablepre}maptrap WHERE pls = '$pls' AND itme>='$itme'");
	$trpnum = $db->num_rows($trapresult);
	if ($itms != $nosta) {
		$itms--;
		if ($itms <= 0) {
			$log .= "<span class=\"red\">$itm</span>用光了。<br>";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	}
	if ($trpnum>0){
		$itemno = rand(0,$trpnum-1);
		$db->data_seek($trapresult,$itemno);
		$mi=$db->fetch_array($trapresult);
		$deld = $mi['itm'];
		$delp = $mi['tid'];
		$db->query("DELETE FROM {$tablepre}maptrap WHERE tid='$delp'");
		$log.="远方传来一阵爆炸声，伟大的<span class=\"yellow\">{$itm}</span>用生命和鲜血扫除了<span class=\"yellow\">{$deld}</span>。<br><span class=\"red\">实在是大快人心啊！</span><br>";
	}else{
		$log.="你使用了<span class=\"yellow\">{$itm}</span>，但是没有发现陷阱。<br>";
	}
}
