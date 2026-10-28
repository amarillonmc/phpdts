<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle fireworks items
function item_fireworks($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	// 这里需要从原item.func.php文件中移植烟花相关的逻辑
	// 从第1530行之后的代码
		//В ΜΑЛΨ, В ЩΑЁΨ, В ЦΨΨ ОΑЙЙ, В ТИХ ЩДТЖИΜД.
		//ХЖ ДЖХЖТ, ЖХΨ ЦЩТΑВΜДЩ ТЖΑΡ, ΜΨЩ. ЩДВХΜЦ. ΡЖХΨ.
	//Thanks Chantal for crunching those numbers - I'll make sure I find you something else to crunch on some other time...
	# This method concerns 4 of them, and one additional check:
	//$hp up, $w[X] up, $mhp up, $def up

		# Then, decide on the Rank of the Fireseed Item, this will decide its maximum value:
		$rank = 0;
		# Those items will always start with either ◆,✦,★,☾, and ☼
		if (strpos ( $itm, '◆' ) === 0){
			$rank = 1;
		}elseif (strpos ( $itm, '✦' ) === 0){
			$rank = 2;
		}elseif (strpos ( $itm, '★' ) === 0){
			$rank = 3;
		}elseif (strpos ( $itm, '☾' ) === 0){
			$rank = 4;
		}elseif (strpos ( $itm, '☼') === 0){
			$rank = 5;
		}else{
			$rank = 0;
		}

		# Special check for a invalid item (Rank = 0), Just turn it into healing.
		if($rank == 0){
			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚，你感觉焕然一新！<br>";
			$hp = $mhp;
			$sp = $msp;

			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}
		}

		# Logic for each of the 4 usages.
		elseif($itmk == '🎆H'){
			# This is healing item, it can heal beyond your $mhp based on its rank.
			if ($rank == 1){
				$gainmax = round($mhp * 0.51);
			}elseif ($rank == 2){
				$gainmax = round($mhp * 1.08);
			}elseif ($rank == 3){
				$gainmax = round($mhp * 2.33);
			}elseif ($rank == 4){
				$gainmax = round($mhp * 5.73);
			}else{
				$gainmax = '∞';
			}
			// Tracking how much HP one can overheal based on its rank.
			$clbpara['fireseedMaxHPRecover'] = $gainmax;
			if ($gainmax !== '∞'){
			// Gain HP and SP - note we don't overheal SP here.
			
			$addsp = $msp - $sp < $itme ? $msp - $sp : $itme;
			if($addsp > 0) $sp += $addsp;
			else $addsp = 0;
			// Calculating overheal HP value.
			$addhp = ($mhp + $gainmax) - $hp < $itme ? ($mhp + $gainmax) - $hp : $itme;
			if($addhp > 0) $hp += $addhp;
			else $addhp = 0;

			if ($addhp <= 0){
				$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
				但是似乎并没有回复生命！<br>
				<br>
				<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
				「你可能需要找个纯度更高的代码片段哟~」<br></span>";
			}else{

			$gainless = ($mhp + $gainmax) - $hp;

			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			治愈的代码片段为你恢复了<span class=\"yellow\">$addhp</span>点生命和<span class=\"yellow\">$addsp</span>点体力。<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你还能获得{$gainless}点临时生命哟~<br>
			但临时生命就是临时的，随时都有可能消失哟~」<br></span>";

			if($gainless < $itme){
				$log.="<br><span class=\"redseed\">这时，有另一把声音插了进来：<br>
				「看起来这个纯度的代码片段已经喂不饱你了。<br>
				赶快找下一个纯度的代码片段吧！」<br></span>";
			}

			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}
		}
			}else{
				$addsp = $msp - $sp < $itme ? $msp - $sp : $itme;
				if($addsp > 0) $sp += $addsp;
				else $addsp = 0;
				
				$addhp = $itme;
				$hp += $addhp;

				$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			治愈的代码片段为你恢复了<span class=\"yellow\">$addhp</span>点生命和<span class=\"yellow\">$addsp</span>点体力。<br>";

			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}
			}
		}elseif ($itmk == '🎆V'){
			# This is $w[X] up, it simply add to all $w[X] values.
			if ($rank == 1){
				$gainmax = 201;
			}elseif ($rank == 2){
				$gainmax = 502;
			}elseif ($rank == 3){
				$gainmax = 2003;
			}elseif ($rank == 4){
				$gainmax = 8011;
			}else{
				$gainmax = '∞';
			}
			// Tracking how much w value one can gain based on its rank.
			$clbpara['fireseedmaxProfGain'] = $gainmax;
			if ($gainmax !== '∞'){
			// Gain w value
			
			$addw = $itme;
			$clbpara['fireseedmaxProfAdd'] += $addw;
			if($clbpara['fireseedmaxProfGain'] - $clbpara['fireseedmaxProfAdd'] > 0) {
				$wp += $addw;
				$wk += $addw;
				$wg += $addw;
				$wc += $addw;
				$wd += $addw; 
				$wf += $addw;}
			else $addw = 0;

			if ($addw <= 0){
			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			但是似乎什么都没有发生！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你可能需要找个纯度更高的代码片段哟~」<br></span>";
			}else{

			$gainless = $clbpara['fireseedmaxProfGain'] - $clbpara['fireseedmaxProfAdd'];

			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			载有熟练度的代码片段让你获得了<span class=\"yellow\">$addw</span>点全系熟练度！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你还能通过这个纯度的代码获得{$gainless}点熟练度哟~」<br></span>";

			if($gainless < $itme){
				$log.="<br><span class=\"redseed\">这时，有另一把声音插了进来：<br>
				「看起来这个纯度的代码片段已经喂不饱你了。<br>
				赶快找下一个纯度的代码片段吧！」<br></span>";
			}

			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}

			}
			}else{
				$addw = $itme;
				$wp += $addw;
				$wk += $addw;
				$wg += $addw;
				$wc += $addw;
				$wd += $addw; 
				$wf += $addw;

				$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
				载有熟练度的代码片段让你获得了<span class=\"yellow\">$addw</span>点全系熟练度！<br>";

				if ($itms != $nosta) {
					$itms --;
					if ($itms <= 0) {
						$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
						$itm = $itmk = $itmsk = '';
						$itme = $itms = 0;
					}
				}
			}
		}elseif ($itmk == '🎆O'){
			# This is $mhp up item, it simply add to that value.
			if ($rank == 1){
				$gainmax = 1001;
			}elseif ($rank == 2){
				$gainmax = 3002;
			}elseif ($rank == 3){
				$gainmax = 5003;
			}elseif ($rank == 4){
				$gainmax = 8008;
			}else{
				$gainmax = '∞';
			}
			// Tracking how much $mhp value one can gain based on its rank.
			$clbpara['fireseedmaxHPGain'] = $gainmax;
			if ($gainmax !== '∞'){
			// Gain $mhp value
			
			$addmhp = $itme;
			$clbpara['fireseedmaxHPAdd'] += $addmhp;
			if($clbpara['fireseedmaxHPGain'] - $clbpara['fireseedmaxHPAdd'] > 0) $mhp += $addmhp;
			else $addmhp = 0;

			if ($addmhp <= 0){
			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			但是似乎什么都没有发生！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你可能需要找个纯度更高的代码片段哟~」<br></span>";
			}else{

			$gainless = $clbpara['fireseedmaxHPGain'] - $clbpara['fireseedmaxHPAdd'];

			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			载有生命的代码片段让你获得了<span class=\"yellow\">$addmhp</span>点生命最大值！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你还能通过这个纯度的代码获得{$gainless}点生命最大值哟~」<br></span>";

			if($gainless < $itme){
				$log.="<br><span class=\"redseed\">这时，有另一把声音插了进来：<br>
				「看起来这个纯度的代码片段已经喂不饱你了。<br>
				赶快找下一个纯度的代码片段吧！」<br></span>";

			}

			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}

			}
			}else{
				$addmhp = $itme;
				$mhp += $addmhp;

				$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
				载有生命的代码片段让你获得了<span class=\"yellow\">$addmhp</span>点生命最大值！<br>";

				if ($itms != $nosta) {
					$itms --;
					if ($itms <= 0) {
						$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
						$itm = $itmk = $itmsk = '';
						$itme = $itms = 0;
					}
				}
			}
		}elseif ($itmk == '🎆D'){
			# This is $def up item, it simply add to that value.
			if ($rank == 1){
				$gainmax = 1001;
			}elseif ($rank == 2){
				$gainmax = 3002;
			}elseif ($rank == 3){
				$gainmax = 5003;
			}elseif ($rank == 4){
				$gainmax = 8008;
			}else{
				$gainmax = '∞';
			}
			// Tracking how much $def value one can gain based on its rank.
			$clbpara['fireseedmaxDefGain'] = $gainmax;
			if ($gainmax !== '∞'){
			// Gain $def value
			
			$adddef = $itme;
			$clbpara['fireseedmaxDefAdd'] += $adddef;
			if($clbpara['fireseedmaxDefGain'] - $clbpara['fireseedmaxDefAdd'] > 0) $def += $adddef;
			else $adddef = 0;

			if ($adddef <= 0){
			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			但是似乎什么都没有发生！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你可能需要找个纯度更高的代码片段哟~」<br></span>";
			}else{

			$gainless = $clbpara['fireseedmaxDefGain'] - $clbpara['fireseedmaxDefAdd'];

			$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
			载有防御数据的代码片段让你获得了<span class=\"yellow\">$adddef</span>点基础防御力！<br>
			<br>
			<br>
			<span class=\"blueseed\">同时，你还隐约听见了一个声音：<br>
			「你还能通过这个纯度的代码获得{$gainless}点基础防御力哟~」<br></span>";

			if($gainless < $itme){
				$log.="<br><span class=\"redseed\">这时，有另一把声音插了进来：<br>
				「看起来这个纯度的代码片段已经喂不饱你了。<br>
				赶快找下一个纯度的代码片段吧！」<br></span>";
			}
			if ($itms != $nosta) {
				$itms --;
				if ($itms <= 0) {
					$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
					$itm = $itmk = $itmsk = '';
					$itme = $itms = 0;
				}
			}

			}
			}else{
				$adddef = $itme;
				$def += $adddef;

				$log.="你将<span class=\"yellow\">{$itm}</span>吞下了肚。<br>
				载有防御数据的代码片段让你获得了<span class=\"yellow\">$adddef</span>点基础防御力！<br>";

				if ($itms != $nosta) {
					$itms --;
					if ($itms <= 0) {
						$log .= "<span class=\"red\">$itm</span>的余烬崩解消失了……<br>";
						$itm = $itmk = $itmsk = '';
						$itme = $itms = 0;
					}
				}
			}
		
		}elseif($itmk == '🎆B'){
			# Fireseed Box, containing various helpful items.
			# Officially dubbed Silent Box.
			$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";

			$oitm = $itm; $oitmk = $itmk;
			if ($itms != $nosta) {
				$itms--;
			}

			include_once config('randomFS',$gamecfg);

			$dice = diceroll(1000);
			if($dice <= 420){
				$itemflag = $lesserdata;
			}elseif($dice <= 740){
				$itemflag = $item;
			}elseif($dice <= 927){
				$itemflag = $constructs;
			}elseif($dice <= 998){
				$itemflag = $material;
			}else{
				$itemflag = $sundata;
				$clbpara['achvars']['gacha_ssr'] += 1;
			}
			if($itemflag){
				$itemflag = explode("\r\n",$itemflag);
				$rand = rand(0,count($itemflag)-1);
				list($in,$ik,$ie,$is,$isk) = explode(',',$itemflag[$rand]);
			}

			if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);
			$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;
			if($itemflag = $sundata) addnews($now,'present',$name,$oitm,$in,$nick);
	
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget($data);

		}elseif($itmk == '🎆C'){
			# Weird Fireseed Box, containing interesting items.
			# Officially dubbed Weird Box.
			$log.="你打开了<span class=\"yellow\">$itm</span>。<br>";

			$oitm = $itm; $oitmk = $itmk;
			if ($itms != $nosta) {
				$itms--;
			}

			include_once config('randomFSW',$gamecfg);

			$dice = diceroll(1000);
			if($dice <= 660){
				$itemflag = $selfjoke;
			}elseif($dice <= 996){
				$itemflag = $jokeonothers;
			}else{
				$itemflag = $superjoke;
				$clbpara['achvars']['gacha_ssr'] += 1;
			}
			if($itemflag){
				$itemflag = explode("\r\n",$itemflag);
				$rand = rand(0,count($itemflag)-1);
				list($in,$ik,$ie,$is,$isk) = explode(',',$itemflag[$rand]);
			}

			if($itms <= 0 && $itms != $nosta) destory_single_item($data,$itmn,1);
			$itm0 = $in;$itmk0=$ik;$itme0=$ie;$itms0=$is;$itmsk0=$isk;
			if($itemflag = $sundata) addnews($now,'present',$name,$oitm,$in,$nick);
	
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget($data);

		}else{
			$log.="这段代码……要如何使用呢？<br>";
		}

		//Process a special check for total Ash item used, for future usage.
		$clbpara['fireseedAshUsage'] += $rank;

		//Process item decrease. - Changed to do it only after succeeding item usage.
/* 		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}
		} */
	# Special check for a poisoned fireseed item, WIP for now.	

//	}else($itmk == 'P🎆'){
//		$log.="这个<span class=\"yellow\">{$itm}</span>有毒！到底是谁干的！<br>";
		# For Maximum Funniness, we destroy this item.
//		$log .= "<span class=\"red\">$itm</span>的余烬向天上盘旋飞舞，消失了。<br>";
//		$itm = $itmk = $itmsk = '';
//		$itme = $itms = 0;}
	}

// Handle other items not covered by specific handlers
function item_other($itmn, &$data) {
	global $log;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	// 处理其他未分类的物品
	if(strpos($itmk, '🥚') === 0) {
		$code = substr($itmsk, 0, 1);
		if(in_array($code, array('J', 's', 'X', 'x'), true)) {
			if($itmn != 0 && !empty($itms0)) {
				$log .= '请先处理手中的物品，再使用代码片段。<br>';
				return;
			}
			if($code == 'J' || $code == 's') {
				$log .= '你将这个蛋捧在手里仔细端详着……<br>它突然变成了一包卡牌！<br>';
				$result = array('游戏王卡包', 'ygo', 1, 1, '');
			} elseif($code == 'X') {
				$log .= '你将这个蛋捧在手里仔细端详着……<br>它突然变成了一本黑色的小册子<br>卧槽，这不会是……<br>';
				$result = array('■DeathNote■', 'Y', 1, 1, '');
			} else {
				$log .= '你将这个蛋捧在手里仔细端详着……<br>它突然变成了一枚奇迹的元素！<br>';
				$result = array('奇迹的元素', 'HB', 65535, 1, 'z');
			}
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			destory_single_item($data, $itmn);
			list($data['itm0'], $data['itmk0'], $data['itme0'], $data['itms0'], $data['itmsk0']) = $result;
			$data['itmpara0'] = '';
		} elseif($code == 'v' || $code == 'V') {
			$log .= '你看了一眼这个蛋，就理解了它的用法。<br>你痛快地……吃掉了它？<br>';
			$log .= $code == 'v' ? '你感觉到你的武器泛起了一股诅咒的力量……<br>' : '你感觉到你的武器绑定在了你的身上……<br>';
			$wepsk .= $code;
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			destory_single_item($data, $itmn);
		} else {
			$log .= '你似乎听到了一个佻皮的女孩声音：<br><br>“这个代码片段……不妨以后再来探索吧！”<br>';
		}
		return;
	}
	$log .= "你使用了<span class=\"yellow\">$itm</span>，但是什么也没有发生。<br>";
}
