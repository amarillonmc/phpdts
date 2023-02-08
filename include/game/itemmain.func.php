<?php

//
//	date:2012-02-02
///include/game/itemmain.func.php
//	Issue：
//	preg_replace
//	
//*/

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

include_once GAME_ROOT.'./include/game/titles.func.php';

function trap(){
	global $log,$cmd,$mode,$iteminfo,$itm0,$itmk0,$itme0,$itms0,$itmsk0,$nick;
	global $name,$now,$hp,$db,$tablepre,$bid,$lvl,$pid,$type,$tactic,$club,$skills,$rp;
	global $wepsk,$arbsk,$arhsk,$arask,$arfsk,$artsk,$achievement;
	
	$playerflag = $itmsk0 ? true : false;
	$selflag = $itmsk0 == $pid ? true : false;
	$dice=rand(0,99);
	$escrate = $club == 5 ? 25 + $lvl/3 : 8 + $lvl/3;
	$escrate = $club == 6 ? $escrate + 15 : $escrate;
	$escrate = $tactic == 4 ? $escrate + 20 : $escrate;
	$escrate = $selflag ? $escrate + 50 : $escrate; //自己设置的陷阱容易躲避
	//echo '回避率：'.$escrate.'%';
	$def_key = $wepsk.$arbsk.$arhsk.$arask.$arfsk.$artsk;
	
	if(strpos($def_key,'M') !== false){
		$minedetect = true;
		if($club == 7){//电脑社使用探雷器效率增加
			$escrate += 45;
		}else{
			$escrate += 35;
		}
	}
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	$escrate *= get_clubskill_bonus_escrate($club,$skills);
	$escrate = $escrate >= 90 ? 90 : $escrate;//最大回避率
	$escrate = $itmk0 == 'TOc' ? -1 : $escrate;//必中陷阱
	//$log .= "回避率: $escrate<br>";
	if($playerflag && !$selflag){
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk0'");
		$wdata = $db->fetch_array($result);
		$trname = $wdata['name'];$trtype = $wdata['type'];$trperfix = '<span class="yellow">'.$trname.'</span>设置的';
	}elseif($selflag){
		$trname = $name;$trtype = 0;$trperfix = '你自己设置的';
	}else{
		$trname = $trtype = $trperfix = '';
	}
		
	if($dice >= $escrate){
		$bid = $itmsk0;
		
		if($itmk0 == 'TOc'){//奇迹陷阱
			$damage = $hp;
			$goodmancard = 0;
		}elseif($itmk0 == 'TO8'){ //随机数大神的陷阱
			$damage = $hp / 8;
			$goodmancard = 0;
		}else{
			$damage = round(rand(0,$itme0/2)+($itme0/2));
			$damage = $tactic == 2 ? round($damage * 0.75) : $damage;
			
			//好人卡特别活动
			global $itm1,$itmk1,$itms1,$itm2,$itmk2,$itms2,$itm3,$itmk3,$itms3,$itm4,$itmk4,$itms4,$itm5,$itmk5,$itms5;
			$goodmancard = 0;
			for($i=1;$i<=5;$i++){
				if(${'itms'.$i} && ${'itm'.$i} == '好人卡' && ${'itmk'.$i} == 'Y'){
					$goodmancard += ${'itms'.$i};
				}
			}
		}
		if(strpos($def_key,'m') !== false && $itmk0 != 'TOc'){//迎击
			$dice = rand(0,99);
			$hitrate = $club == 7 ? 60 : 40;//电脑社使用探雷器效率增加
			if($dice > $hitrate){
				$damage = 0;
			}			
		}
		if($damage){
			$hp -= $damage; $tmp_club=$club;
		 
			if($playerflag){
				addnews($now,'trap',get_title_desc($nick).' '.$name,$trname,$itm0);
			}
			$log .= "糟糕，你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>！受到<span class=\"dmg\">$damage</span>点伤害！<br>";
			$rp = $rp / 2; //尝试修复RP踩雷可能不削半问题
			//$log .= "【DEBUG】你目前的rp为<span class=\"dmg\">$rp</span>！<br>";
			if($goodmancard){
				$gm = ceil($goodmancard*rand(80,120)/100);
				$log .= "在你身上的<span class=\"yellow\">好人卡</span>的作用下，你受到的伤害增加了<span class=\"red\">$gm</span>点！<br>";
				$hp -= $gm;
			}
			
			$trapkill=false;
			if($hp <= 0) {
				include_once GAME_ROOT.'./include/state.func.php';
				$killmsg = death('trap',$trname,$trtype,$itm0);
				$log .= "你被{$trperfix}陷阱杀死了！";
				$trapkill=true;
				//检查成就
				include_once GAME_ROOT.'./include/game/achievement.func.php';
				//check_trap_death_achievement($name,$trname,$selflag,$itm0,$itme0);
		
				if($killmsg && !$selflag){
					$log .= "<span class=\"yellow\">{$trname}对你说：“{$killmsg}”</span><br>";
				}				
				if ($tmp_club==99) $log.="<span class=\"lime\">但由于你及时按下了BOMB键，你原地满血复活了！</span><br>";
			}
			else
			{
				//检查成就
				include_once GAME_ROOT.'./include/game/achievement.func.php';
				//check_trap_survive_achievement($achievement,$selflag,$itm0,$itme0);
			}
			if($playerflag && !$selflag && $trapkill){
				$w_log = "<span class=\"red\">{$name}触发了你设置的陷阱{$itm0}并被杀死了！</span>";
				if ($tmp_club==99) $w_log.="<span class=\"lime\">但由于{$name}及时按下了BOMB键，{$name}原地满血复活了！</span>";
				$w_log.="<br>";
				logsave ( $itmsk0, $now, $w_log ,'b');
			}elseif($playerflag && !$selflag){
				$w_log = "<span class=\"yellow\">{$name}触发了你设置的陷阱{$itm0}！</span><br>";
				logsave ( $itmsk0, $now, $w_log ,'b');
			}
		}else{
			if($playerflag){
				addnews($now,'trapdef',get_title_desc($nick).' '.$name,$trname,$itm0);
				if(!$selflag){
					$w_log = "<span class=\"yellow\">{$name}触发了你设置的陷阱{$itm0}，但是没有受到任何伤害！</span><br>";
					logsave ( $itmsk0, $now, $w_log ,'b');
				}				
			}
			$log .= "糟糕，你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>！<br>不过，身上装备着的自动迎击系统启动了！<span class=\"yellow\">在迎击功能的保护下你毫发无伤。</span><br>";
			//检查成就
			include_once GAME_ROOT.'./include/game/achievement.func.php';
			//check_trap_fail_achievement($achievement,$selflag,$itm0,$itme0);
		}
		
		$itm0 = $itmk0 = $itmsk0 = '';
		$itme0 = $itms0 = 0;
		return;
	} else {
		//检查成就
		include_once GAME_ROOT.'./include/game/achievement.func.php';
		//check_trap_miss_achievement($achievement,$selflag,$itm0,$itme0);
		if($playerflag && !$selflag){
			addnews($now,'trapmiss',get_title_desc($nick).' '.$name,$trname,$itm0);
			$w_log = "<span class=\"yellow\">{$name}回避了你设置的陷阱{$itm0}！</span><br>";
			logsave ( $itmsk0, $now, $w_log ,'b');
		}
		$dice = rand(0,99);
		$fdrate = $club == 5 ? 40 + $lvl/3 : 5 + $lvl/3;
		$fdrate = $selflag ? $fdrate + 50 : $fdrate;
		include_once GAME_ROOT.'./include/game/clubskills.func.php';
		$fdrate *= get_clubskill_bonus_reuse($club,$skills);
		if($dice < $fdrate){
			if($minedetect){
				$log .= "在探雷装备的辅助下，你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>并且拆除了它。陷阱看上去还可以重复使用。<br>";
			}else{
				$log .= "你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>，不过你并没有触发它。陷阱看上去还可以重复使用。<br>";
			}				
			$itmsk0 = '';$itmk0 = str_replace('TO','TN',$itmk0);
			$mode = 'itemfind';
			return;
		}else{
			if($minedetect){
				$log .= "在探雷装备的辅助下，你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>并且拆除了它。不过陷阱好像被你搞坏了。<br>";
			}else{
				$log .= "你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>，不过你成功地回避了陷阱。<br>";
			}		
			
			$itm0 = $itmk0 = $itmsk0 = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}
	}
}

function itemfind() {
	global $mode,$log,$itm0,$itmk0,$itms0,$itmsk0;
	global $club;
	if(!$itm0||!$itmk0||!$itms0){
		$log .= '获取物品信息错误！';
		$mode = 'command';
		return;
	}
	if(strpos($itmk0,'TO')===0) {
		trap();
	}else{
		if(CURSCRIPT == 'botservice')
		{
			echo "mode=itemfind\n";
			echo "itm0=$itm0\n";
			echo "itms0=$itms0\n";
			echo "itmsk0=$itmsk0\n";
		}
		$mode = 'itemfind';
		return;
	}
}


function itemget() {
	global $log,$nosta,$mode,$itm0,$itmk0,$itme0,$itms0,$itmsk0,$cmd;
	$log .= "获得了物品<span class=\"yellow\">$itm0</span>。<br>";
	//PORT
	if(strpos($itmsk0,'^')!==false){
		$keep_flag = false;
		include_once GAME_ROOT . './include/game/itembag.func.php';
		replace_itembag($keep_flag);
		if(!$keep_flag){
			return;
		}
	}
	if(preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|M|V)/',$itmk0) && $itms0 !== $nosta){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		if($wep == $itm0 && $wepk == $itmk0 && $wepe == $itme0 && $wepsk == $itmsk0){
			$weps += $itms0;
			$log .= "与装备着的武器<span class=\"yellow\">$wep</span>合并了。";
			$itm0 = $itmk0 = $itmsk0 = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}else{
			for($i = 1;$i <= 6;$i++){
				global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
				if((${'itms'.$i})&&($itm0 == ${'itm'.$i})&&($itmk0 == ${'itmk'.$i})&&($itme0 == ${'itme'.$i})&&($itmsk0 == ${'itmsk'.$i})){
					${'itms'.$i} += $itms0;
					$log .= "与包裹里的<span class=\"yellow\">$itm0</span>合并了。";
					$itm0 = $itmk0 = $itmsk0 = '';
					$itme0 = $itms0 = 0;
					$mode = 'command';
					return;
				}
			}
		}
	} elseif(preg_match('/^H|^P/',$itmk0) && $itms0 !== $nosta){
		$sameitem = array(); $scnt=0;
		for($i = 1;$i <= 6;$i++){
			global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i};
			if(${'itms'.$i}&&($itm0 == ${'itm'.$i})&&($itme0 == ${'itme'.$i})&&(preg_match('/^(H|P)/',${'itmk'.$i}))){
				$sameitem[] = $i; $scnt++;
			}
		}
		if(isset($sameitem[0])){
			if (CURSCRIPT != 'botservice')
			{
				include template('itemmerge0');
				$cmd = ob_get_contents();
				ob_clean();
			}
			else  
			{
				echo "mode=itemmerge0\n";
				echo "itemmergechoicenum={$scnt}\n";
				for ($i=0; $i<$scnt; $i++)
					echo "itemmergechoice{$i}={$sameitem[$i]}\n";
			}

//			$cmd .= '<input type="hidden" name="mode" value="itemmain"><input type="hidden" name="command" value="itemmerge"><input type="hidden" name="merge1" value="0"><br>是否将 <span class="yellow">'.$itm0.'</span> 与以下物品合并？<br><input type="radio" name="merge2" id="itmn" value="n" checked><a onclick=sl("itmn"); href="javascript:void(0);" >不合并</a><br><br>';
//			foreach($sameitem as $n) {
//				$cmd .= '<input type="radio" name="merge2" id="itm'.$n.'" value="'.$n.'"><a onclick=sl("itm'.$n.'"); href="javascript:void(0);">'."${'itm'.$n}/${'itme'.$n}/${'itms'.$n}".'</a><br>';
//			}
			return;
		}
		
	}

	itemadd();
	return;
}


function itemdrop($item) {
	global $db,$log,$mode,$pls,$tablepre;

	if($item == 'wep'){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		$itm = & $wep;
		$itmk = & $wepk;
		$itme = & $wepe;
		$itms = & $weps;
		$itmsk = & $wepsk;
	} elseif(strpos($item,'ar') === 0) {
		$itmn = substr($item,2,1);
		global ${'ar'.$itmn},${'ar'.$itmn.'k'},${'ar'.$itmn.'e'},${'ar'.$itmn.'s'},${'ar'.$itmn.'sk'};
		$itm = & ${'ar'.$itmn};
		$itmk = & ${'ar'.$itmn.'k'};
		$itme = & ${'ar'.$itmn.'e'};
		$itms = & ${'ar'.$itmn.'s'};
		$itmsk = & ${'ar'.$itmn.'sk'};

	} elseif(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};
	}
	//PORT
	if(strpos($itmsk,'^')!==false){
		$dflag=true;
		for($i=1;$i<=6;$i++){
			global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
			if(strpos(${'itmsk'.$i},'^')!==false && ${'itms'.$i} && ${'itme'.$i}){
				$dflag=false;
				break;
			}
		}
		global $arbsk,$arbs,$arbe;
		if(strpos($arbsk,'^')!==false  && $arbs && $arbe){
			$dflag=false;
		}
		if ($dflag){
			include_once GAME_ROOT . './include/game/itembag.func.php';
			drop_itembag();
		}
	}
if(($itmk=='XX')||(($itmk=='XY'))){
		$log .= '该物品不能丢弃。<br>';
		$mode = 'command';
		return;
	}
	if(!$itms||!$itmk||$itmk=='WN'||$itmk=='DN'){
		$log .= '该物品不存在！<br>';
		$mode = 'command';
		return;
	}
	if(strpos($itmsk,'v')!==false){
		$log .= "{$itm}在地上化作点点碎片，随风消逝了。<br>";
		$log .= "你摧毁了<span class=\"red\">$itm</span>。<br>";
	}else{
//	$mapfile = GAME_ROOT."./gamedata/mapitem/{$pls}mapitem.php";
//	$itemdata = "$itm,$itmk,$itme,$itms,$itmsk,\n";
//	writeover($mapfile,$itemdata,'ab');
	$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('$itm', '$itmk', '$itme', '$itms', '$itmsk', '$pls')");
	$log .= "你丢弃了<span class=\"red\">$itm</span>。<br>";
	}
	$mode = 'command';
	if($item == 'wep'){
	$itm = '拳头';
	$itmsk = '';
	$itmk = 'WN';
	$itme = 0;
	$itms = $nosta;
	} else {
	$itm = $itmk = $itmsk = '';
	$itme = $itms = 0;
	}
	return;
}

function itemoff($item){
	global $log,$mode,$cmd,$itm0,$itmk0,$itme0,$itms0,$itmsk0;

	if($item == 'wep'){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		$itm = & $wep;
		$itmk = & $wepk;
		$itme = & $wepe;
		$itms = & $weps;
		$itmsk = & $wepsk;
	} elseif(strpos($item,'ar') === 0) {
		$itmn = substr($item,2,1);
		global ${'ar'.$itmn},${'ar'.$itmn.'k'},${'ar'.$itmn.'e'},${'ar'.$itmn.'s'},${'ar'.$itmn.'sk'};
		$itm = & ${'ar'.$itmn};
		$itmk = & ${'ar'.$itmn.'k'};
		$itme = & ${'ar'.$itmn.'e'};
		$itms = & ${'ar'.$itmn.'s'};
		$itmsk = & ${'ar'.$itmn.'sk'};
	}
	if(!$itms||!$itmk||$itmk=='WN'||$itmk=='DN'){
		$log .= '该物品不存在！<br>';
		$mode = 'command';
		return;
	}
		if(($itmk=='XX')||(($itmk=='XY'))){
		$log .= '该物品不能卸下。<br>';
		$mode = 'command';
		return;
	}
	$log .= "你卸下了装备<span class=\"yellow\">$itm</span>。<br>";

	$itm0 = $itm;
	$itmk0 = $itmk;
	$itme0 = $itme;
	$itms0 = $itms;
	$itmsk0 = $itmsk;
	
	if($item == 'wep'){
	$itm = '拳头';
	$itmsk = '';
	$itmk = 'WN';
	$itme = 0;
	$itms = $nosta;
	} else {
	$itm = $itmk = $itmsk = '';
	$itme = $itms = 0;
	}
	itemget();
	return;
}

function itemadd(){
	global $log,$mode,$cmd,$itm0,$itmk0,$itme0,$itms0,$itmsk0;
	if(!$itms0){
		$log .= '你没有捡取物品。<br>';
		$mode = 'command';
		return;
	}
	for($i = 1;$i <= 6;$i++){
		global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
		if(!${'itms'.$i}){
			$log .= "将<span class=\"yellow\">$itm0</span>放入包裹。<br>";
			${'itm'.$i} = $itm0;
			${'itmk'.$i} = $itmk0;
			${'itme'.$i} = $itme0;
			${'itms'.$i} = $itms0;
			${'itmsk'.$i} = $itmsk0;
			$itm0 = $itmk0 = $itmsk = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}
	}
	if (CURSCRIPT != 'botservice')
	{
		//$log .= '你的包裹已经满了。想要丢掉哪个物品？<br>';
		include template('itemdrop0');
		$cmd = ob_get_contents();
		ob_clean();
	}
	else  echo "mode=itemdrop0\n";
//	$cmd .= '<input type="hidden" name="mode" value="itemmain"><br><input type="radio" name="command" id="dropitm0" value="dropitm0" checked><a onclick=sl("dropitm0"); href="javascript:void(0);" >'."$itm0/$itme0/$itms0".'</a><br><br>';
//
//	for($i = 1;$i <= 6;$i++){
//		$cmd .= '<input type="radio" name="command" id="swapitm'.$i.'" value="swapitm'.$i.'"><a onclick=sl("swapitm'.$i.'"); href="javascript:void(0);" >'."${'itm'.$i}/${'itme'.$i}/${'itms'.$i}".'</a><br>';
//	}
	return;
}

function itemmerge($itn1,$itn2){
	global $log,$mode;
	
	if($itn1 == $itn2) {
		$log .= '需要选择两个物品才能进行合并！';
		$mode = 'itemmerge';
		return;
	}
	
	global $nosta,${'itm'.$itn1},${'itmk'.$itn1},${'itme'.$itn1},${'itms'.$itn1},${'itmsk'.$itn1},${'itm'.$itn2},${'itmk'.$itn2},${'itme'.$itn2},${'itms'.$itn2},${'itmsk'.$itn2};
	
	$it1 = & ${'itm'.$itn1};
	$itk1 = & ${'itmk'.$itn1};
	$ite1 = & ${'itme'.$itn1};
	$its1 = & ${'itms'.$itn1};
	$itsk1 = & ${'itmsk'.$itn1};
	$it2 = & ${'itm'.$itn2};
	$itk2 = & ${'itmk'.$itn2};
	$ite2 = & ${'itme'.$itn2};
	$its2 = & ${'itms'.$itn2};
	$itsk2 = & ${'itmsk'.$itn2};
	
	if(!$its1 || !$its2) {
		$log .= '请选择正确的物品进行合并！';
		$mode = 'itemmerge';
		return;
	}
	
	if($its1==$nosta || $its2==$nosta) {
		$log .= '耐久是无限的物品不能合并！';
		$mode = 'itemmerge';
		return;
	}

	if(($it1 == $it2)&&($ite1 == $ite2)) {
		if(($itk1==$itk2)&&($itsk1==$itsk2)&&preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|V|M)/',$itk1)) {
			$its2 += $its1;
			$it1 = $itk1 = $itsk1 = '';
			$ite1 = $its1 = 0;
			$log .= "你合并了<span class=\"yellow\">$it2</span>。";
			$mode = 'command';
			return;
		} elseif(preg_match('/^(H|P)/',$itk1)&&preg_match('/^(H|P)/',$itk2)) {
			if((strpos($itk1,'P') === 0)||(strpos($itk1,'P') === 0)){
				$p1 = substr($itk1,2);
				$p2 = substr($itk2,2);
				$k = substr($itk1,1,1);
				if($p2 < $p1){ $p2 = $p1;};
				$itk2 = "P$k$p2";
				if($itsk1 !== ''){
					$itsk2=$itsk1;
					}
			}
			$its2 += $its1;
			$it1 = $itk1 = $itsk1 = '';
			$ite1 = $its1 = 0;
			
			$log .= "你合并了 <span class=\"yellow\">$it2</span>。";
			$mode = 'command';
			return;
		} elseif($itk1!=$itk2||$itsk1!=$itsk2) {
			$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>不是同类型同属性物品，不能合并！";
			$mode = 'itemmerge';
		} else{
			$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>完全是两个东西，想合并也不可能啊……";
			$mode = 'itemmerge';
		}
	} else {
		$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>不是同名同效果物品，不能合并！";
		$mode = 'itemmerge';
	}

	if(!$itn1 || !$itn2) {
		itemadd();
	}

	//$mode = 'command';
	return;
}
$syncn=$synck=$synce=$syncs=$syncsk=Array();
function itemmix($mlist, $itemselect=-1) {
	global $log,$mode,$gamecfg,$name,$nosta,$gd,$name,$nick;
	global $itm1,$itm2,$itm3,$itm4,$itm5,$itm6,$itms1,$itms2,$itms3,$itms4,$itms5,$itms6,$itme1,$itme2,$itme3,$itme4,$itme5,$itme6,$club,$wd;
	global $itmk1,$itmk2,$itmk3,$itmk4,$itmk5,$itmk6,$itmsk1,$itmsk2,$itmsk3,$itmsk4,$itmsk5,$itmsk6;
	global $syncn,$synck,$synce,$syncs,$syncsk,$sync,$reqname,$star;
	global $cmd;
	$mlist2 = array_unique($mlist);	
	if(count($mlist) != count($mlist2)) {
		$log .= '相同道具不能进行合成！<br>';
		$mode = 'itemmix';
		return;
	}
	if(count($mlist) < 2){
		$log .= '至少需要2个道具才能进行合成！';
		$mode = 'itemmix';
		return;
	}
	$issyncro=false;
	$isntsyn=false;
	$isoverlay=false;
	$isntove=false;
	$star=0;
	$reqname='';
	$tzname='';
	$ostar=0;
	$mixitem = array();
	foreach($mlist as $val){
		if ((strlen(${'itmk'.$val})>=4)&&(strpos(${'itmsk'.$val},'J')!==false)){
				$isoverlay=true;
				break;
			}
	}
	foreach($mlist as $val){
		if(!${'itm'.$val}){
			$log .= '所选择的道具不存在！';
			$mode = 'itemmix';
			return;
		}
		$mitm = ${'itm'.$val};
		foreach(Array('/锋利的/','/电气/','/毒性/','/-改$/') as $value){
			$mitm = preg_replace($value,'',$mitm);
		}
		$mixitem[] = $mitm;
		if (strlen(${'itmk'.$val})<4){
			$isntove=true;
			if ($isoverlay==true){
				$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
				addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
		}else{
			if ($isoverlay==false){
				$ostar=substr(${'itmk'.$val},2,2);
			}
		}
		if ($isoverlay==true){
			if ((strlen(${'itmk'.$val})<4)||((substr(${'itmk'.$val},2,2)!=$ostar)&&($ostar!=0))){
				$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
				addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			$ostar=substr(${'itmk'.$val},2,2);
			continue;
		}else{
			if ((strlen(${'itmk'.$val})>=4)&&(strpos(${'itmsk'.$val},'J')!==false)){
				if (substr(${'itmk'.$val},2,2)!=$ostar){
					$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
					addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
					foreach($mlist as $val){
						${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
						${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
				}
				$isoverlay=true;
				$ostar=substr(${'itmk'.$val},2,2);
			}
		}
		if ($issyncro==true){
			if ((strlen(${'itmk'.$val})<4)&&($isntsyn==false)){
				$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
				addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			if (strpos(${'itmsk'.$val},'s')!==false){
				$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
				addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			$star+=substr(${'itmk'.$val},2,2);
			$reqname.=${'itm'.$val}.'_';
		}else{
			if (strpos(${'itmsk'.$val},'s')!==false){
				if ($isntsyn==false){
					$issyncro=true;
					$star+=substr(${'itmk'.$val},2,2);
					$tzname=${'itm'.$val};
					continue;
				}else{
					$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
					addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
					foreach($mlist as $val){
						${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
						${'itme'.$val} = ${'itms'.$val} = 0;
					}
					return;
				}
			}
			if (strlen(${'itmk'.$val})>=4){
				$star+=substr(${'itmk'.$val},2,2);
				$reqname.=${'itm'.$val}.'_';
			}else{
				$isntsyn=true;
			}
		}
	}
	//overlay
	if ($isoverlay==true){
		$file1 = config('overlay',$gamecfg);
		$olist = openfile($file1);
		$num = count($olist)-1;
		$nnum = sizeof($mixitem);
		$sync=-1;
		$syncn=$synck=$synce=$syncs=$syncsk=Array();
		for ($i=0;$i<=$num;$i++){
			$t = explode(',',$olist[$i]);
			if (($t[5]!=$ostar)||($t[6]!=$nnum)) {continue;}
			$sync++;
			$syncn[$sync]=$t[0];
			$synck[$sync]=$t[1];
			$synce[$sync]=$t[2];
			$syncs[$sync]=$t[3];
			$syncsk[$sync]=$t[4];
		}
		if ($sync==-1){
			$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
			foreach($mlist as $val){
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
			return;
		}
		if ($itemselect==-1)
		{
			$mask=0;
			foreach($mlist as $k)
				if (1<=$k && $k<=6)
					$mask|=(1<<((int)$k-1));
					
			$cmd.='<input type="hidden" id="mode" name="mode" value="itemmain">';
			$cmd.='<input type="hidden" id="command" name="command" value="itemmix">';
			$cmd.='<input type="hidden" id="mixmask" name="mixmask" value="'.$mask.'">';
			$cmd.='<input type="hidden" id="itemselect" name="itemselect" value="999">';
			$cmd.= "请选择超量结果<br><br>";
			for($i=0;$i<=$sync;$i++){
				$tn=$syncn[$i];
				$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_-1_';
				$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"".$tn."\" onclick=\"$('itemselect').value='".$i."';postCmd('gamecmd','command.php');this.disabled=true;\">";
			}
			$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"返回\" onclick=\"postCmd('gamecmd','command.php');this.disabled=true;\">";
		}
		else
		{
			$i=(int)$itemselect;
			if ($i<0 || $i>$sync)
			{
				$mode='command'; return; 
			}
			foreach($mlist as $val)
			{
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_-1_';
			include_once GAME_ROOT.'./include/game/special.func.php';
			syncro($tk);
			$mode='command';
		}
		return;
	}
	//syncro
	if (($issyncro==true)&&($isntsyn==false)){
		$sync=-1;
		$syncn=$synck=$synce=$syncs=$syncsk=Array();
		$file = config('synitem',$gamecfg);
		$slist = openfile($file);
		$num = count($slist)-1;
		for ($i=0;$i<=$num;$i++){
			$t = explode(',',$slist[$i]);
			$rnum = count($t)-8;
			$tn=$t[0];$tk=$t[1];$te=$t[2];$ts=$t[3];$tsk=$t[4];$tstar=$t[5];
			if ($star!=$tstar) {continue;}
			if (($t[6]!='-1')&&(strpos($tzname,$t[6])===false)) {continue;}
			$isok=true;
			for ($j=1;$j<=$rnum;$j++){
				if (($t[7+$j-1]!='-1')&&(strpos($reqname,$t[7+$j-1])===false)) {$isok=false;break;}
			}
			if ($isok==false) {continue;}
			$sync++;
			$syncn[$sync]=$tn;$synck[$sync]=$tk;$synce[$sync]=$te;$syncs[$sync]=$ts;$syncsk[$sync]=$tsk;
		}
		if ($sync==-1){
			$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
			foreach($mlist as $val){
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			addnews($now,'mixfail',get_title_desc($nick).' '.$name,$itm0);
			return;
		}
		if ($itemselect==-1)
		{
			$mask=0;
			foreach($mlist as $k)
				if (1<=$k && $k<=6)
					$mask|=(1<<((int)$k-1));
			
			$cmd.='<input type="hidden" id="mode" name="mode" value="itemmain">';
			$cmd.='<input type="hidden" id="command" name="command" value="itemmix">';
			$cmd.='<input type="hidden" id="mixmask" name="mixmask" value="'.$mask.'">';
			$cmd.='<input type="hidden" id="itemselect" name="itemselect" value="999">';
			$cmd.= "请选择同调结果<br><br>";
			for($i=0;$i<=$sync;$i++){
				$tn=$syncn[$i];
				$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_'.$star.'_';
				$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"".$tn."\" onclick=\"$('itemselect').value='".$i."';postCmd('gamecmd','command.php');this.disabled=true;\">";
			}
			$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"返回\" onclick=\"postCmd('gamecmd','command.php');this.disabled=true;\">";
		}
		else
		{
			$i=(int)$itemselect;
			if ($i<0 || $i>$sync)
			{
				$mode='command'; return; 
			}
			foreach($mlist as $val)
			{
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_1_';
			include_once GAME_ROOT.'./include/game/special.func.php';
			syncro($tk);
			$mode='command';
		}
		return;
	}

	include_once config('mixitem',$gamecfg);
	$mixflag = false;
	foreach($mixinfo as $minfo) {
		if(!array_diff($mixitem,$minfo['stuff']) && !array_diff($minfo['stuff'],$mixitem) && count($mixitem) == count($minfo['stuff'])){ 
			$mixflag = true;
			break;			
		}
	}

	$itmstr = '';
	foreach($mixitem as $val){
		$itmstr .= $val.' ';
	}
	$itmstr = substr($itmstr,0,-1);
		
	if(!$mixflag) {
		$log .= "<span class=\"yellow\">$itmstr</span>不能合成！<br>";
		$mode = 'itemmix';
	} else {
		foreach($mlist as $val){
			itemreduce('itm'.$val);
		}

		global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$now;

		list($itm0,$itmk0,$itme0,$itms0,$itmsk0) = $minfo['result'];
		$log .= "<span class=\"yellow\">$itmstr</span>合成了<span class=\"yellow\">{$minfo['result'][0]}</span><br>";
		//var_dump($minfo['result'][0]);
		addnews($now,'itemmix',get_title_desc($nick).' '.$name,$itm0);
		//if($club == 5) { $wd += 2; }
		//else { $wd+=1; }
		$wd+=1;
		if((strpos($itmk0,'WD') === 0)&&($club == 5)&&($itms0 !== $nosta)){ $itms0 = ceil($itms0*1.5); }
		elseif((strpos($itmk0,'H') === 0)&&($club == 16)&&($itms0 !== $nosta)){ $itms0 = ceil($itms0*2); }
		elseif(($itmk0 == 'EE' || $itmk0 == 'ER') && ($club == 7)){ $itme0 *= 5; }
		//elseif(($itm0 == '移动PC' || $itm0 == '广域生命探测器') && ($club == 7)){ $itme0 *= 3; }
		
		//检查成就
		include_once GAME_ROOT.'./include/game/achievement.func.php';
		check_mixitem_achievement($name,$itm0);
		
		itemget();
	}
	return;
}
function itemreduce($item){ //只限合成使用！！
	global $log;
	if(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};
	} else {
		return;
	}

	if(!$itms) { return; }
	if(preg_match('/^(Y|B|C|X|TN|GB|H|P|V|M)/',$itmk)){$itms--;}
	else{$itms=0;}
	if($itms <= 0) {
		$itms = 0;
		$log .= "<span class=\"red\">$itm</span>用光了。<br>";
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
	}
	return;
}

function itemmove($from,$to){
	global $log;
	if(!$from || !is_numeric($from) || !$to || !is_numeric($to) || $from < 1 || $to < 1 || $from > 6 || $to > 6){
		$log .= '错误的包裹位置参数。<br>';
		return;
	}	elseif($from == $to){
		$log .= '同一物品无法互换。<br>';
		return;
	}
	global ${'itm'.$from},${'itmk'.$from},${'itme'.$from},${'itms'.$from},${'itmsk'.$from},${'itm'.$to},${'itmk'.$to},${'itme'.$to},${'itms'.$to},${'itmsk'.$to};
	$f = & ${'itm'.$from};
	$fk = & ${'itmk'.$from};
	$fe = & ${'itme'.$from};
	$fs = & ${'itms'.$from};
	$fsk = & ${'itmsk'.$from};
	$t = & ${'itm'.$to};
	$tk = & ${'itmk'.$to};
	$te = & ${'itme'.$to};
	$ts = & ${'itms'.$to};
	$tsk = & ${'itmsk'.$to};
	if(!$fs){
		$log .= '错误的道具参数。<br>';
		return;
	}
	if(!$ts){
		$log .= "将<span class=\"yellow\">{$f}</span>移动到了<span class=\"yellow\">包裹{$to}</span>。<br>";
		$t = $f;
		$tk = $fk;
		$te = $fe;
		$ts = $fs;
		$tsk = $fsk;
		$f = $fk = $fsk = '';
		$fe = $fs = 0;
		
	}else {
		$log .= "将<span class=\"yellow\">{$f}</span>与<span class=\"yellow\">{$t}</span>互换了位置。<br>";
		$temp = $t;
		$tempk = $tk;
		$tempe = $te;
		$temps = $ts;
		$tempsk = $tsk;
		$t = $f;
		$tk = $fk;
		$te = $fe;
		$ts = $fs;
		$tsk = $fsk;
		$f = $temp;
		$fk = $tempk;
		$fe = $tempe;
		$fs = $temps;
		$fsk = $tempsk;
		
	}
	return;
}


function itembuy($item,$shop,$bnum=1) {
	global $log,$name,$mode,$now,$money,$areanum,$areaadd,$itm0,$itmk0,$itme0,$itms0,$itmsk0,$pls,$shops,$club;
	global $db,$tablepre;
	$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE sid = '$item'");
	$iteminfo = $db->fetch_array($result);
	$price = $club == 11 ? round($iteminfo['price']*0.75) : $iteminfo['price'];
	//$file = GAME_ROOT."./gamedata/shopitem/{$shop}shopitem.php";
	//$itemlist = openfile($file);
	//$iteminfo = $itemlist[$item];
	if(!$iteminfo) {
		$log .= '要购买的道具不存在！<br><br>';
		$mode = 'command';
		return;
	}

//	if(!in_array($pls,$shops)) {
//		$log .= '你所在的位置没有商店。<br>';
//		return;
//	}
	$bnum = (int)$bnum;
	//list($num,$price,$iname,$ikind,$ieff,$ista,$isk) = explode(',',$iteminfo);
	if($iteminfo['num'] <= 0) {
		$log .= '此物品已经售空！<br><br>';
		$mode = 'command';
		return;
	} elseif($bnum<=0) {
		$log .= '购买数量必须为大于0的整数。<br><br>';
		$mode = 'command';
		return;
	} elseif($bnum>$iteminfo['num']) {
		$log .= '购买数量必须小于存货数量。<br><br>';
		$mode = 'command';
		return;
	} elseif($money < $price*$bnum) {
		$log .= '你的钱不够，不能购买此物品！<br><br>';
		$mode = 'command';
		return;
	} elseif(!preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|H|V|M)/',$iteminfo['itmk'])&&$bnum>1) {
		$log .= '此物品一次只能购买一个。<br><br>';
		$mode = 'command';
		return;
	}elseif($iteminfo['area']> $areanum/$areaadd){
		$log .= '此物品尚未开放出售！<br><br>';
		$mode = 'command';
		return;
	}
//	if (strpos($ikind,'_') !== false) {
//		list($ik,$it) = explode('_',$ikind);
//		if($areanum < $it*$areaadd) {
//			$log .= '此物品尚未开放出售！<br>';
//			return;
//		}
//	} else {
//		$ik = $ikind;
//	}
	$inum = $iteminfo['num']-$bnum;
	$sid = $iteminfo['sid'];
	$db->query("UPDATE {$tablepre}shopitem SET num = '$inum' WHERE sid = '$sid'");
//	$num-=$bnum;
	$money -= $price*$bnum;
//	$itemlist[$item] = "$num,$price,$iname,$ikind,$ieff,$ista,$isk,\n";
//	writeover($file,implode('',$itemlist));
	addnews($now,'itembuy',$name,$iteminfo['item']);
	$log .= "购买成功。";
	$itm0 = $iteminfo['item'];
	$itmk0 = $iteminfo['itmk'];
	$itme0 = $iteminfo['itme'];
	$itms0 = $iteminfo['itms']*$bnum;
	$itmsk0 = $iteminfo['itmsk'];

	itemget();	
	return;
}





function getcorpse($item){
	global $db,$tablepre,$log,$mode;
	global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$money,$pls,$action,$rp,$name;
	global $club,$allow_destory_corpse,$no_destory_corpse_type,$rpup_destory_corpse;
	$corpseid = strpos($action,'corpse')===0 ? str_replace('corpse','',$action) : str_replace('pacorpse','',$action);
	if(!$corpseid || strpos($action,'corpse')===false){
		$log .= '<span class="yellow">你没有遇到尸体，或已经离开现场！</span><br>';
		$action = '';
		$mode = 'command';
		return;
	}

	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$corpseid'");
	if(!$db->num_rows($result)){
		$log .= '对方不存在！<br>';
		$action = '';
		$mode = 'command';
		return;
	}

	$edata = $db->fetch_array($result);
	
	if($edata['hp']>0) {
		$log .= '对方尚未死亡！<br>';
		$action = '';
		$mode = 'command';
		return;
	} elseif($edata['pls'] != $pls) {
		$log .= '对方跟你不在同一个地图！<br>';
		$action = '';
		$mode = 'command';
		return;
	}

	if($item == 'destory')
	{
		if(!$allow_destory_corpse || in_array($w_type,$no_destory_corpse_type))
		{
			$log.="你还想对这具可怜的尸体干什么？麻烦给死者一点基本的尊重！<br>";
			$action = '';
			$mode = 'command';
			return;
		}
		$log.="你销毁了{$edata['name']}的尸体。<br>但这一切值得吗……？<br>";
		include_once GAME_ROOT.'./include/game/dice.func.php';
		$rp += diceroll($rpup_destory_corpse);
		addnews($now,'cdestroy',$name,$edata['name']);
		destory_corpse($edata);
		$action = '';
		$mode = 'command';
		return;
	}
	elseif($item == 'element_split')
	{
		if($club != 20)
		{
			$log.="你还想对这具可怜的尸体干什么？麻烦给死者一点基本的尊重！<br>";
			$action = '';
			$mode = 'command';
			return;
		}
		include_once GAME_ROOT.'./include/game/elementmix.func.php';
		split_corpse_to_elements($edata);
		$action = '';
		$mode = 'command';
		return;
	}

	if($item == 'loot_depot')
	{
		global $name,$type;
		include_once GAME_ROOT.'./include/game/depot.func.php';
		loot_depot($name,$type,$edata['name'],$edata['type']);
		$action = '';
		$mode = 'command';
		return;
	}
	
	if($item == 'wep') {
		$itm0 = $edata['wep'];
		$itmk0 = $edata['wepk'];
		$itme0 = $edata['wepe'];
		$itms0 = $edata['weps'];
		$itmsk0 = $edata['wepsk'];
		$edata['wep'] = $edata['wepk'] = $edata['wepsk'] = '';
		$edata['wepe'] = $edata['weps'] = 0;  
	} elseif(strpos($item,'ar') === 0) {
		$itm0 = $edata[$item];
		$itmk0 = $edata[$item.'k'];
		$itme0 = $edata[$item.'e'];
		$itms0 = $edata[$item.'s'];
		$itmsk0 = $edata[$item.'sk'];
		$edata[$item] = $edata[$item.'k'] = $edata[$item.'sk'] = '';
		$edata[$item.'e'] = $edata[$item.'s'] = 0;  
	} elseif(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		$itm0 = $edata['itm'.$itmn];
		$itmk0 = $edata['itmk'.$itmn];
		$itme0 = $edata['itme'.$itmn];
		$itms0 = $edata['itms'.$itmn];
		$itmsk0 = $edata['itmsk'.$itmn];
		$edata['itm'.$itmn] = $edata['itmk'.$itmn] = $edata['itmsk'.$itmn] = '';
		$edata['itme'.$itmn] = $edata['itms'.$itmn] = 0;  
	} elseif($item == 'money') {
		$money += $edata['money'];
		$log .= '获得了金钱 <span class="yellow">'.$edata['money'].'</span>。<br>';
		$edata['money'] = 0;
		player_save($edata);
		$action = '';
		$mode = 'command';
		return;
	} else {
		$action = '';
		return;
	}

	player_save($edata);

	if(!$itms0||!$itmk0||$itmk0=='WN'||$itmk0=='DN') {
		$log .= '该物品不存在！';
	} else {
		itemget();
	}
	$action = '';
	$mode = 'command';
	return;
}

//武器损耗&消耗计算：force_imp：强制扣除武器效果；check_sk：是否在武器毁坏时重新检查属性数组$pa['ex_keys']
function weapon_loss(&$pa,$hurtvalue,$force_imp=0,$check_sk=0)
{
	global $log,$wepimprate,$nosta;

	if($hurtvalue>0 && $pa['wep_kind'] != 'N')
	{
		$wep_loss_flag = 0;
		//获取武器损耗类型
		$wep_imp = $wepimprate[$pa['wep_kind']];
		//损耗型武器
		if($wep_imp > 0 || $force_imp)
		{
			if($pa['weps'] == $nosta || $force_imp)
			{
				$pa['wepe'] = max(0,$pa['wepe']-$hurtvalue);
				if(!$pa['type']) $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的攻击力下降了{$hurtvalue}。</span><br>";
			}
			else 
			{
				$pa['weps'] = max(0,$pa['weps']-$hurtvalue);
				if(!$pa['type']) $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的耐久度下降了{$hurtvalue}。</span><br>";
			}
			if(empty($pa['weps']) || empty($pa['wepe']))
			{
				$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>使用过度，已经损坏，无法再装备了！<br>";
				$wep_loss_flag = 1;
			}
		}
		//消耗型武器
		else 
		{
			if($pa['weps'] != $nosta)
			{
				$pa['weps'] = max(0,$pa['weps']-$hurtvalue);
				if($pa['wep_kind'] == 'C' || $pa['wep_kind'] == 'D' || $pa['wep_kind'] == 'F')
				{
					if(!$pa['type']) $log .= "<span class='grey'>{$pa['nm']}用掉了{$hurtvalue}个{$pa['wep']}。</span><br>";
					if(empty($pa['weps']))
					{
						$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>用光了！<br>";
						$wep_loss_flag = 1;
					}
				} 
				elseif($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J') 
				{
					if(!$pa['type']) $log .= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的弹药数减少了{$hurtvalue}。</span><br>";
					if(empty($pa['weps']))
					{
						$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>弹药用光了！<br>";
						$pa['weps'] = $nosta;
					}
				}
			}
		}
		if($wep_loss_flag)
		{
			//剔除武器属性
			if($check_sk && !empty($pa['wepsk'])) unset_ex_from_array($pa,get_itmsk_array($pa['wepsk']));

			$pa['wep'] = '拳头'; $pa['wep_kind'] = 'N'; $pa['wepk'] = 'WN';
			$pa['wepe'] = 0; $pa['weps'] = $nosta; $pa['wepsk'] = '';
			return -1;
		}
	}
	return;
}

//扣除指定装备的耐久。check_sk：是否在武器毁坏时重新检查属性数组$pa['ex_keys']
function armor_hurt(&$pa,$which,$hurtvalue,$check_sk=0)
{
	global $log,$nosta;

	if(!empty($pa[$which.'s']))
	{
		//无限耐久的防具可以抵挡1次任意点损耗
		if ($pa[$which.'s'] == $nosta)
		{
			$pa[$which.'s'] = $hurtvalue;
		}
		//扣除耐久
		$x = min($pa[$which.'s'], $hurtvalue);
		$pa[$which.'s'] = $pa[$which.'s']-$x;
		if(!$pa['type']) $log .= "<span class=\"grey\">{$pa['nm']}的".$pa[$which]."的耐久度下降了{$x}！</span><br>";
		//耐久为0 装备损坏
		if($pa[$which.'s'] <= 0)
		{
			$log .= "{$pa['nm']}的<span class=\"red\">".$pa[$which]."</span>受损过重，无法再装备了！<br>";

			//剔除防具属性
			if($check_sk && !empty($pa[$which.'sk'])) unset_ex_from_array($pa,get_itmsk_array($pa[$which.'sk']));

			if($which == 'arb')
			{
				$pa[$which] = '内衣'; $pa[$which.'k'] = 'DN';
				$pa[$which.'e'] = 0; $pa[$which.'s'] = $nosta; $pa[$which.'sk'] = '';
			}
			else 
			{
				$pa[$which] = $pa[$which.'k'] = $pa[$which.'sk'] = '';
				$pa[$which.'e'] = $pa[$which.'s'] = 0; 
			}
			return -1;
		}
	}
	return 0;
}

//从属性数组中剔除指定属性
function unset_ex_from_array(&$pa,$exarr)
{
	if(!empty($pa['ex_keys']) && !empty($exarr))
	{
		foreach($exarr as $ex)
		{
			if(in_array($ex,$pa['ex_keys'])) unset($pa['ex_keys'][array_search($ex,$pa['ex_keys'])]);
		}
	}
	return;
}


?>
