<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function itemuse($itmn) {
	global $mode,$log,$nosta,$pid,$name,$state,$now;
	if ( $itmn < 1 || $itmn > 5 ) {
		$log .= '此道具不存在，请重新选择。';
		$mode = 'command';
		return;
	}

	global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
	$itm = & ${'itm'.$itmn};
	$itmk = & ${'itmk'.$itmn};
	$itme = & ${'itme'.$itmn};
	$itms = & ${'itms'.$itmn};
	$itmsk = & ${'itmsk'.$itmn};

	if(($itms <= 0)&&($itms!=$nosta)) {
		$itm = $itmk = $itmsk = '';$itme = $itms = 0;
		$log .= '此道具不存在，请重新选择。<br>';
		$mode = 'command';
		return;
	}

	if(strpos($itmk, 'W') === 0){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		if((strpos($wepk, 'WN') === 0)||(!$wepe)){
			$wep = $itm;$wepk = $itmk;$wepe = $itme;$weps = $itms;$wepsk = $itmsk;
			$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
			$log .= "装备了武器 <span class=\"yellow\">$wep</span> 。<br>";
		} else {
			$itmt = $wep;$itmkt = $wepk;$itmet = $wepe;$itmst = $weps;$itmskt = $wepsk;
			$wep = $itm;$wepk = $itmk;$wepe = $itme;$weps = $itms;$wepsk = $itmsk;
			$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
			$log .= "卸下了武器 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$wep</span> 。<br>";
		}
	} elseif(strpos($itmk, 'D') === 0) {
		if(strpos($itmk, 'DB') === 0) {
			global $arb,$arbk,$arbe,$arbs,$arbsk;
			if((strpos($arbk, 'DN') === 0)||(!$arbe)) {
				$arb = $itm;$arbk = $itmk;$arbe = $itme;$arbs = $itms;$arbsk = $itmsk;
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
				$log .= "身体装备了防具 <span class=\"yellow\">$arb</span> 。<br>";
			} else {
				$itmt = $arb;$itmkt = $arbk;$itmet = $arbe;$itmst = $arbs;$itmskt = $arbsk;
				$arb = $itm;$arbk = $itmk;$arbe = $itme;$arbs = $itms;$arbsk = $itmsk;
				$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
				$log .= "身体脱下了防具 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$arb</span> 。<br>";
			}
		} elseif(strpos($itmk, 'DH') === 0) {
			global $arh,$arhk,$arhe,$arhs,$arhsk;
			if(!$arhs){
				$arh = $itm;$arhk = $itmk;$arhe = $itme;$arhs = $itms;$arhsk = $itmsk;
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
				$log .= "头部装备了防具 <span class=\"yellow\">$arh</span> 。<br>";
			} else {
				$itmt = $arh;$itmkt = $arhk;$itmet = $arhe;$itmst = $arhs;$itmskt = $arhsk;
				$arh = $itm;$arhk = $itmk;$arhe = $itme;$arhs = $itms;$arhsk = $itmsk;
				$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
				$log .= "头部卸下了防具 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$arh</span> 。<br>";
			}
		} elseif(strpos($itmk, 'DA') === 0) {
			global $ara,$arak,$arae,$aras,$arask;
			if(!$aras){
				$ara = $itm;$arak = $itmk;$arae = $itme;$aras = $itms;$arask = $itmsk;
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
				$log .= "腕部装备了防具 <span class=\"yellow\">$ara</span> 。<br>";
			} else {
				$itmt = $ara;$itmkt = $arak;$itmet = $arae;$itmst = $aras;$itmskt = $arask;
				$ara = $itm;$arak = $itmk;$arae = $itme;$aras = $itms;$arask = $itmsk;
				$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
				$log .= "腕部卸下了防具 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$ara</span> 。<br>";
			}
		} elseif(strpos($itmk, 'DF') === 0) {
			global $arf,$arfk,$arfe,$arfs,$arfsk;
			if(!$arfs){
				$arf = $itm;$arfk = $itmk;$arfe = $itme;$arfs = $itms;$arfsk = $itmsk;
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
				$log .= "足部装备了防具 <span class=\"yellow\">$arf</span> 。<br>";
			} else {
				$itmt = $arf;$itmkt = $arfk;$itmet = $arfe;$itmst = $arfs;$itmskt = $arfsk;
				$arf = $itm;$arfk = $itmk;$arfe = $itme;$arfs = $itms;$arfsk = $itmsk;
				$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
				$log .= "足部卸下了防具 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$arf</span> 。<br>";
			}
		}
	} elseif(strpos($itmk, 'A') === 0) {
		global $art,$artk,$arte,$arts,$artsk;
		if(!$arts){
			$art = $itm;$artk = $itmk;$arte = $itme;$arts = $itms;$artsk = $itmsk;
			$itm = $itmk = $itmsk = '';$itme = $itms = 0;$itmsk = '';
			$log .= "装备了饰品 <span class=\"yellow\">$art</span> 。<br>";
		} else {
			$itmt = $art;$itmkt = $artk;$itmet = $arte;$itmst = $arts;$itmskt = $artsk;
			$art = $itm;$artk = $itmk;$arte = $itme;$arts = $itms;$artsk = $itmsk;
			$itm = $itmt;$itmk = $itmkt;$itme = $itmet;$itms = $itmst;$itmsk = $itmskt;
			$log .= "卸下了饰品 <span class=\"red\">$itm</span>  ，装备了 <span class=\"yellow\">$art</span> 。<br>";
		}
	} elseif(strpos($itmk, 'HS') === 0) {
		global $sp,$msp;
		if($sp < $msp){
			$oldsp = $sp;
			$sp += $itme;
			$sp = $sp > $msp ? $msp : $sp;
			$oldsp = $sp - $oldsp;
			$log .= "你使用了 <span class=\"red\">$itm</span> ，恢复了 <span class=\"yellow\">$oldsp</span> 点体力。<br>";
			if($itms != $nosta){ 
				$itms--;  
				if($itms <= 0){
					$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
					$itm = $itmk = $itmsk = '';$itme = $itms = 0;
				}
			}
		} else {
			$log .= '你的体力不需要恢复。<br>';
		}
	} elseif(strpos($itmk, 'HH') === 0) {
		global $hp,$mhp;
		if($hp < $mhp) {
			$oldhp = $hp;
			$hp += $itme;
			$hp = $hp > $mhp ? $mhp : $hp;
			$oldhp = $hp - $oldhp;
			$log .= "你使用了 <span class=\"red\">$itm</span> ，恢复了 <span class=\"yellow\">$oldhp</span> 点生命。<br>";
			if($itms != $nosta){ 
				$itms--;  
				if($itms <= 0){
					$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
					$itm = $itmk = $itmsk = '';$itme = $itms = 0;
	}

			}
		} else {
			$log .= '你的生命不需要恢复。<br>';
		}
	} elseif(strpos($itmk, 'HB') === 0) {
		global $hp,$mhp,$sp,$msp;
		if(($hp < $mhp)||($sp < $msp)) {
			$oldsp = $sp;
			$sp += $itme;
			$sp = $sp > $msp ? $msp : $sp;
			$oldsp = $sp - $oldsp;
			$oldhp = $hp;
			$hp += $itme;
			$hp = $hp > $mhp ? $mhp : $hp;
			$oldhp = $hp - $oldhp;
			$log .= "你使用了 <span class=\"red\">$itm</span> ，恢复了 <span class=\"yellow\">$oldhp</span> 点生命和 <span class=\"yellow\">$oldhp</span> 点体力。<br>";
			if($itms != $nosta){ 
				$itms--;  
				if($itms <= 0){
					$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
					$itm = $itmk = $itmsk = '';$itme = $itms = 0;
				}
			}
		} else {
			$log .= '你的生命和体力都不需要恢复。<br>';
		}
	} elseif(strpos($itmk, 'P') === 0) {
		global $lvl,$db,$tablepre,$now,$hp,$inf,$bid;
		if(strpos($itmk, '2') === 2){ $damage = round($itme * 2); }
		elseif(strpos($itmk, '1') === 2){ $damage = round($itme * 1.5); }
		else{ $damage = round($itme); }
		if(strpos($inf, 'p') === false){ $inf .= 'p'; }
		$hp -= $damage;
		if($itmsk) {
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk'");
			$wdata = $db->fetch_array($result);
			$log .= "糟糕，<span class=\"yellow\">$itm</span> 中被 <span class=\"yellow\">{$wdata['name']}</span> 掺入了毒药！你受到了 <span class=\"dmg\">$damage</span> 点伤害！<br>";
			addnews($now,'poison',$name,$wdata['name'],$itm);
		} else {
			$log .= "糟糕，<span class=\"yellow\">$itm</span> 中被掺入了毒药！你受到了 <span class=\"dmg\">$damage</span> 点伤害！<br>";
		}
		if($hp <= 0 ) {
			if($itmsk) {
				$bid = $itmsk;
				$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk'");
				$wdata = $db->fetch_array($result);
				/*
				if($wdata['hp'] > 0){
					$expup = round(($wdata['lvl'] - $lvl)/3);
					$wdata['exp'] += $expup;
				}
				*/
				include_once GAME_ROOT.'./include/state.func.php';
				$killmsg = death('poison',$wdata['name'],$wdata['type'],$itm);
				$log .= "你被 <span class=\"red\">".$wdata['name']."</span> 毒死了！";
				$log .= "<span class=\"yellow\">{$wdata['name']} 对 你 说：“{$killmsg}”</span><br>";
			} else {
				$bid = 0;
				include_once GAME_ROOT.'./include/state.func.php';
				death('poison','',0,$itm);
				$log .= "你被毒死了！";
			}
		}
		if($itms != $nosta) { 
			$itms--;  
			if($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;
			}
		}

	} elseif(strpos($itmk, 'T') === 0){
		global $pls,$exp,$upexp,$wd;
		$mapfile = GAME_ROOT."./gamedata/mapitem/{$pls}mapitem.php";
		$itemdata = "$itm,TO,$itme,1,$pid,\n";
		writeover($mapfile,$itemdata,'ab');
		$log .= "设置了陷阱 <span class=\"red\">$itm</span> 。<br>小心，自己也很难发现。<br>";
		$exp++;$wd++;
		if($exp >= $upexp){
			include_once GAME_ROOT.'./include/state.func.php';
			lvlup($exp,$upexp);
		}
		if($itms != $nosta){ 
			$itms--;  
			if($itms <= 0){
				$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
				$itm = $itmk = $itmsk = '';$itme = $itms = 0;
			}
		}
	} elseif(strpos($itmk, 'GB') === 0) {
		global $wep,$wepk,$weps,$wepsk;
		if(strpos($wepk, 'WG') !== 0){
			$log .= '你没有装备枪械，不能使用子弹。<br>'; 
			$mode = 'command'; 
			return; 
		}
		if($wepsk == 1) { $bulletnum = 12; }
		else { $bulletnum = 6; }
		if($weps == $nosta){ $weps = 0; }
		$bullet = $bulletnum - $weps;
		if($bullet <= 0){
			$log .= "<span class=\"yellow\">$wep</span> 的弹匣是满的，不能装弹。";
			return;		
		} elseif($bullet >= $itms) {
			$bullet = $itms;
		}
		$itms -= $bullet;
		$weps += $bullet;
		$log .= "使用了<span class=\"red\">$itm</span> ，<span class=\"yellow\">$wep</span> 使用次数增加 <span class=\"yellow\">$bullet</span> 。<br>";
		if($itms <= 0){
			$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
			$itm = $itmk = $itmsk = '';$itme = $itms = 0;
		}
	} elseif(strpos($itmk, 'R') === 0) {
		if($itme > 0) {
			$log .= "使用了<span class=\"red\">$itm</span> 。<br>";
			include_once GAME_ROOT.'./include/game/item2.func.php';
			radar($itmsk);
			$itme--;
			if($itme <= 0) {
				$log .= '雷达的电力用光了，请使用电池充电。<br>';
			}
		} else {
			$itme = 0;
			$log .= '雷达没有电了，请先充电。<br>';
		}
	} elseif(strpos($itmk, 'Y') === 0) {
		if($itm == '电池') {
			//功能需要修改，改为选择道具使用YE类型道具可充电
			$flag = false;
			for($i=1;$i<=5;$i++) {
				global ${'itm'.$i},${'itme'.$i};
				if(${'itm'.$i} == '移动PC') {
					${'itme'.$i} += $itme;
					$itms--;
					$flag = true;
					$log .= "<span class=\"yellow\">${'itm'.$i}</span> 已经充电了。";
					break;
				}
			}
			if(!$flag){
				$log .= '你没有需要充电的物品。<br>';
			}
		} elseif($itm == '毒药'){
			global $cmd;
			$cmd = '<input type="hidden" name="mode" value="item"><input type="hidden" name="usemode" value="poison"><input type="hidden" name="itmp" value="'.$itmn.'">你想对什么下毒？<br><input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl("menu"); href="javascript:void(0);" >返回</a><br><br>';
			for($i = 1;$i < 6; $i++){
				global ${'itmk'.$i};
				if((strpos(${'itmk'.$i}, 'H') ===0)||(strpos(${'itmk'.$i}, 'P') ===0)){
					global ${'itm'.$i},${'itme'.$i},${'itms'.$i};
					$cmd .= '<input type="radio" name="command" id="itm'.$i.'" value="itm'.$i.'"><a onclick=sl("itm'.$i.'"); href="javascript:void(0);" >'."${'itm'.$i}/${'itme'.$i}/${'itms'.$i}".'</a><br>';
				}
			}
			return;
		} elseif($itm == '解毒剂') {
			global $inf,$infinfo;
			if(strpos($inf, 'p') !== false){
				$inf = str_replace('p', '', $inf);
				$log .= "使用了 <span class=\"red\">$itm</span> ，<span class=\"red\">".$infinfo['p']."</span> 状态解除了。<br>";
			} else {
				$log .= "使用了 <span class=\"red\">$itm</span> ，但是什么效果也没有。<br>";
			}
			$itms--;  
		} elseif(strpos($itm,'磨刀石') !== false) {
			global $wep,$wepk,$wepe,$weps,$wepsk;
			if(strpos($wepk,'K') == 1) {
				$dice = rand(0,49);	
				if($dice>=10) {
					$wepe += $itme;
					$log .= "使用了 <span class=\"yellow\">$itm</span> ，<span class=\"yellow\">$wep</span> 的攻击力变成了 <span class=\"yellow\">$wepe</span> 。<br>";
				} else {
					$wepe -= ceil($itme/2);
					if($wepe <= 0) {
						$log .= "<span class=\"red\">$itm</span> 使用失败，<span class=\"red\">$wep</span> 损坏了！<br>";
						$wep = $wepk = $wepsk = '';
						$wepe = $weps = 0;
					} else {
						$log .= "<span class=\"red\">$itm</span> 使用失败，<span class=\"red\">$wep</span> 的攻击力变成了<span class=\"red\">$wepe</span> 。<br>";
					}
				}
				$itms--;  
			} else {
				$log .= '你的武器不是斩系，不能使用磨刀石。<br>';
			}
		} elseif(preg_match("/钉$/",$itm)) {
			global $wep,$wepk,$wepe,$weps,$wepsk;
			if(preg_match("/棍棒$/",$wep) && ($wepk == 'WP')) {
				$dice = rand(0,49);	
				if($dice>=10) {
					$wepe += $itme;
					if(strpos($wep,'钉') !== 0){$wep = '钉'.$wep;}
					$log .= "使用了 <span class=\"yellow\">$itm</span> ，<span class=\"yellow\">$wep</span> 的攻击力变成了 <span class=\"yellow\">$wepe</span> 。<br>";
				} else {
					$wepe -= ceil($itme/2);
					if($wepe <= 0) {
						$log .= "<span class=\"red\">$itm</span> 使用失败，<span class=\"red\">$wep</span> 损坏了！<br>";
						$wep = $wepk = $wepsk = '';
						$wepe = $weps = 0;
					} else {
						$log .= "<span class=\"red\">$itm</span> 使用失败，<span class=\"red\">$wep</span> 的攻击力变成了<span class=\"red\">$wepe</span> 。<br>";
					}
				}
				$itms--;  
			} else {
				$log .= '你的武器不是棍棒，不能使用磨刀石。<br>';
			}
		} elseif($itm == '针线包') {
			global $arb,$arbk,$arbe,$arbs,$arbsk,$noarb;
			if(($arb == $noarb)|| !$arb) {
				$log .= '你没有装备防具，不能使用针线包。<br>';
			} else {
				$arbe += (rand(0,2) + $itme);
				$log .= "使用了 <span class=\"yellow\">$itm</span> ，<span class=\"yellow\">$arb</span> 的防御力变成了 <span class=\"yellow\">$arbe</span> 。<br>";
				$itms--;
			}
		} elseif($itm == '消音器') {
			global $wep,$wepk,$wepe,$weps,$wepsk;
			if(strpos($wepk, 'WG') !== 0){
				$log .= '你没有装备枪械，不能使用消音器。<br>'; 
			} elseif(strpos($wepsk,'S') === false) {
				$wepsk .= 'S';
				$log .= "你给 <span class=\"yellow\">$wep</span> 安装了 <span class=\"yellow\">$itm</span> 。<br>";
				$itms--;
			} else {
				$log .= "你的武器已经安装了消音器。<br>";
			}
		} elseif($itm == '移动PC') {
			include_once GAME_ROOT.'./include/game/item2.func.php';
			hack($itmn);
		} elseif($itm == '雷达用电池') {
			$flag = false;
			for($i=1;$i<=5;$i++) {
				global ${'itm'.$i},${'itme'.$i};
				if((strpos(${'itm'.$i}, '雷达') !== false)&&(strpos(${'itm'.$i}, '电池') === false)) {
					${'itme'.$i} += $itme;
					$itms--;
					$flag = true;
					$log .= "<span class=\"yellow\">${'itm'.$i}</span> 已经充电了。";
					break;
				}
			}
			if(!$flag){
				$log .= '你没有雷达。<br>';
			}
		} elseif($itm == '御神签') {
			$log .= "使用了 <span class=\"yellow\">$itm</span> 。<br>";
			include_once GAME_ROOT.'./include/game/item2.func.php';
			divining();
			$itms--;
		} elseif($itm == '凸眼鱼') {
			global $db,$tablepre,$name;
			$db->query("UPDATE {$tablepre}players SET wepe='0',weps='0',arbe='0',arbs='0',arhs='0',aras='0',arfs='0',arts='0',itms0='0',itms1='0',itms2='0',itms3='0',itms4='0',itms5='0',money='0' WHERE hp<=0");
			$cnum = $db->affected_rows();
			addnews($now,'corpseclear',$name,$cnum);
			$log .= "使用了 <span class=\"yellow\">$itm</span> 。<br>突然刮起了一阵怪风，把地上的尸体都吹走了！<br>";
			$itms--;
		} elseif($itm == '天候棒') {
			global $weather,$wthinfo,$name;
			$weather = rand(10,13);
			include_once GAME_ROOT.'./include/system.func.php';
			save_gameinfo();
			addnews($now,'wthchange',$name,$weather);
			$log .= "你转动了几下天候棒。<br>天气突然转变成了 <span class=\"red b\">$wthinfo[$weather] ！</span><br>";
			$itms--;
		} elseif($itm == '武器师安雅的奖赏') {
			global $wep,$wepk,$wepe,$weps,$wepsk,$wp,$wk,$wg,$wc,$wd;
			if(!$weps||!$wepe){
				$log .= '请先装备武器。<br>';
				return;
			}
			$dice = rand(0,99);
			$dice2 = rand(0,99);
			$skill = array('WP' => $wp,'WK' => $wk,'WG' => $wg,'WC' => $wc,'WD' => $wd);
			arsort($skill);
			$skill_keys = array_keys($skill);
			$nowsk = substr($wepk,0,2);
			$maxsk = $skill_keys[0];
			if(($skill[$nowsk] != $skill[$maxsk])&&($dice < 30)) {
				$wepk = $maxsk;
			} elseif(($weps!=$nosta)&&($dice2 < 70)) {
				$weps += ceil($wepe/2);
			} else {
				$wepe += ceil($wepe/2);
			}
			
			addnews($now,'newwep',$name,$itm,$wep);
			$itms--;
		} elseif($itm == '■DeathNote■') {
			$mode = 'deathnote';
			$log .= '你翻开了■DeathNote■<br>';
			return;
		} elseif($itm == '游戏解除钥匙') {
			global $url;
			$state = 6;
			$url = 'end.php';
			include_once GAME_ROOT.'./include/system.func.php';
			gameover($now,'end3',$name);
		} elseif($itm == '奇怪的按钮') {
			$button_dice = rand(0,10);
			if ($button_dice < 4) {
			$log .= "你按下了 <span class=\"yellow\">$itm</span> ，不过好像什么都没有发生！";
			}
			elseif ($button_dice < 8){
			global $url;
			$state = 6;
			$url = 'end.php';
			include_once GAME_ROOT.'./include/system.func.php';
			gameover($now,'end5',$name);
		}
			elseif ($button_dice < 10){
			$log .= '呃？好像什么也没发生啊？<br>咦？按钮上的标签写着什么？请勿按按钮？<br>';
			include_once GAME_ROOT.'./include/state.func.php';
			$log .= '呜哇，按钮爆炸了！<br>';
			death('button');
		}
		}elseif($itm == '装有H173的注射器') {
		  $log .= '你考虑了一会，<br>把袖子卷了起来，给自己注射了H173。<br>';
			include_once GAME_ROOT.'./include/state.func.php';
			$log .= '你失去了知觉。<br>';
			death('suiside');
		
		}else {
			$log .= " <span class=\"yellow\">$itm</span> 该如何使用呢？<br>";
		}

		if(($itms <= 0)&&($itm)) {
			$log .= "<span class=\"red\">$itm</span> 用光了。<br>";
			$itm = $itmk = $itmsk = '';$itme = $itms = 0;
		}
	} else {
		$log .= "你使用了道具 <span class=\"yellow\">$itm</span> 。<br>但是什么也没有发生。<br>";
	}
	$mode = 'command';
	return;
}



?>