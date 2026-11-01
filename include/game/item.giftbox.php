<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle gift box items
function item_giftbox($itmn, &$data) {
	global $log, $db, $tablepre, $now, $gamecfg, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";

	$oitm = $itm; $oitmk = $itmk;
	if ($itms != $nosta) {
		$itms--;
	}
	//if($itms <= 0) destory_single_item($data,$itmn,1);

	if(strpos($oitmk, 'ps') === 0){//银色盒子
		include_once config('randomitem',$gamecfg);
		//1st case of the new diceroll system.
		//include_once GAME_ROOT.'./include/game/dice.func.php';
		$dice = diceroll(100);
		//$dice = rand(1,100);
		if($dice <= 75){//一般物品
			$itemflag = $itmlow;
		}elseif($dice <= 95){//中级道具
			$itemflag = $itmmedium;
		}elseif($dice <= 97){//神装
			$itemflag = $itmhigh;
		}elseif($dice <= 99){//礼品盒和游戏王
			$file = config('present',$gamecfg);
			$plist = openfile($file);
			$file2 = config('box',$gamecfg);
			$plist2 = openfile($file2);
			$plist = array_merge($plist,$plist2);
			$rand = rand(0,count($plist)-1);
			list($in,$ik,$ie,$is,$isk) = explode(',',$plist[$rand]);
			$itmflag = false;
		}else{//三抽
			$itemflag = $antimeta;
		}
		if($itemflag){
			$itemflag = explode("\r\n",$itemflag);
			$rand = rand(0,count($itemflag)-1);
			list($in,$ik,$ie,$is,$isk) = explode(',',$itemflag[$rand]);
		}
	}elseif(strpos($oitmk, 'p0') === 0){//新福袋·VOL1
		// 用$clbpara['opened_pack']记录打开福袋的名称，只要有这个名称，就搞事！
		if(!empty($clbpara['opened_pack'])){
			$log.="似乎你本轮已经打开过福袋，因此不能再打开更多的福袋！<br>";
			$db->query("INSERT INTO {$tablepre}shopitem (kind,num,price,area,item,itmk,itme,itms,itmsk) VALUES ('17','1','20','0','$itm','$itmk','$itme','1','$itmsk')");
			$log.="<span class=\"yellow\">$itm</span>从你的手中飞出，向商店的方向飞去。<br>";
		} 
		if(strpos($itmk, 'p0P') === 0){
			include_once config('randomWP',$gamecfg);
		}elseif(strpos($itmk, 'p0KEY') === 0){
			include_once config('randomKEY',$gamecfg);
		}elseif(strpos($itmk, 'p0K') === 0){
			include_once config('randomWK',$gamecfg);
		}elseif(strpos($itmk, 'p0G') === 0){
			include_once config('randomWG',$gamecfg);
		}elseif(strpos($itmk, 'p0C') === 0){
			include_once config('randomWC',$gamecfg);
		}elseif(strpos($itmk, 'p0D') === 0){
			include_once config('randomWD',$gamecfg);
		}elseif(strpos($itmk, 'p0F') === 0){
			include_once config('randomWF',$gamecfg);
		}elseif(strpos($itmk, 'p0O1') === 0){
			include_once config('randomO1',$gamecfg);
		}elseif(strpos($itmk, 'p000') === 0){
			include_once config('random00',$gamecfg);
		}elseif(strpos($itmk, 'p0AV') === 0){ #TODO VTuber大福袋
			//include_once config('randomAV',$gamecfg);
			include_once config('randomO1',$gamecfg);
		}else{ #防呆
			include_once config('randomO1',$gamecfg);
		}
		//include_once GAME_ROOT.'./include/game/dice.func.php';
		$dice = diceroll(1000);
		if($dice <= 550){//一般物品
			$itemflag = $itmlow;
		}elseif($dice <= 888){//中级道具
			$itemflag = $itmmedium;
		}elseif($dice <= 995){//神装
			$itemflag = $itmhigh;
			$clbpara['achvars']['gacha_sr'] += 1;
		}else{
			$itemflag = $antimeta;
			$clbpara['achvars']['gacha_ssr'] += 1;
		}
		if($itemflag){
			$itemflag = explode("\r\n",$itemflag);
			$rand = rand(0,count($itemflag)-1);
			list($in,$ik,$ie,$is,$isk) = explode(',',$itemflag[$rand]);
			if($clbpara['opened_pack']){
				$in = '乌黑的脸'; # 给一个惩罚用物品
				$ik = 'X';
				$ie = 1;
				$is = 1;
				$isk = '';
			}
			$clbpara['opened_pack'] = $oitm; //记录打开福袋
		}
	}else{//一般礼品盒
		$file = config('present',$gamecfg);
		$plist = openfile($file);
		$rand = rand(0,count($plist)-1);
		list($in,$ik,$ie,$is,$isk) = explode(',',$plist[$rand]);
	}		
	//global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$mode;
	if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);
	$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;
	addnews($now,'present',$name,$oitm,$in,$nick);

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget($data);
}

// Handle YGO box items
function item_ygo_box($itmn, &$data) {
	global $log, $now, $gamecfg, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";
	$oitm = $itm;
	if ($itms != $nosta) {
		$itms--;
	}
	if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);

	$file1 = config('box',$gamecfg);
	$plist1 = openfile($file1);
	$rand1 = rand(0,count($plist1)-1);
	list($in,$ik,$ie,$is,$isk) = explode(',',$plist1[$rand1]);
	//global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$mode;
	$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;
	addnews($now,'present',$name,$oitm,$in,$nick);

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget($data);
}

// Handle FY box items
function item_fy_box($itmn, &$data) {
	global $log, $now, $gamecfg, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";
	$oitm = $itm;
	if ($itms != $nosta) {
		$itms--;
	}
	if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);

	$file1 = config('fy',$gamecfg);
	$plist1 = openfile($file1);
	$rand1 = rand(0,count($plist1)-1);
	list($in,$ik,$ie,$is,$isk) = explode(',',$plist1[$rand1]);
	//global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$mode;
	$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;
	addnews($now,'present',$name,$oitm,$in,$nick);

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget($data);
}

// Handle debug box items
function item_debug_box($itmn, &$data) {
	global $log, $now, $gamecfg, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";
	$oitm = $itm;
	if ($itms != $nosta) {
		$itms--;
	}
	if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);

	$file1 = config('f99',$gamecfg);
	$plist1 = openfile($file1);
	$rand1 = rand(0,count($plist1)-1);
	list($in,$ik,$ie,$is,$isk,$ipara) = explode(',',$plist1[$rand1]);
	//global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$mode;
	$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;$itmpara0=$ipara;
	addnews($now,'present',$name,$oitm,$in,$nick);

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget($data);
}
