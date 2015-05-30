<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}



function findenemy(&$w_pdata) {
	global $log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo,$wepk,$wp,$wk,$wg,$wc,$wd,$nosta,$weps;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep;
	$battle_title = '发现敌人';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle();

	$log .= "你发现了敌人 <span class=\"red\">$w_name</span> ！<br>对方好像完全没有注意到你！<br>";

	$cmd .= ' 现在想要做什么？<br><br>';
	$cmd .= '向对手大喊：<br><input size="30" type="text" name="message" maxlength="60"><br><br>';
	$cmd .= '<input type="hidden" name="mode" value="combat"><input type="hidden" name="wid" value="'.$w_pid.'">';

	$w1 = substr($wepk,1,1);
	$w2 = substr($wepk,2,1);
	if(($w1 == 'G')&&($weps==$nosta)){ $w1 = 'P'; }
	$cmd .= '<input type="radio" name="command" id="'.$w1.'" value="'.$w1.'" checked><a onclick=sl("'.$w1.'"); href="javascript:void(0);">'."$attinfo[$w1] (${$skillinfo[$w1]})".'</a><br>';
	if($w2) {
		$cmd .= '<input type="radio" name="command" id="'.$w2.'" value="'.$w2.'"><a onclick=sl("'.$w2.'"); href="javascript:void(0);">'."$attinfo[$w2] (${$skillinfo[$w2]})".'</a><br>';
	}

	$cmd .= '<input type="radio" name="command" id="back" value="back"><a onclick=sl("back"); href="javascript:void(0);" >逃跑</a><br>';

	$main = 'battle';
	return;
}

function findteam(&$w_pdata){
	global $log,$mode,$main,$cmd,$battle_title;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep;
	$battle_title = '发现队友';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle(1);

	$log .= "你发现了队友 <span class=\"yellow\">$w_name</span> ！<br>";
	
	$cmd .= ' 现在想要做什么？<br><br>';
	$cmd .= '留言：<br><input size="30" type="text" name="message" maxlength="60"><br><br>';
	$cmd .= '想要转让什么？<input type="hidden" name="mode" value="senditem"><input type="hidden" name="wid" value="'.$w_pid.'"><br><input type="radio" name="command" id="back" value="back" checked><a onclick=sl("back"); href="javascript:void(0);" >不转让</a><br><br>';
	for($i = 1;$i < 6; $i++){
		global ${'itms'.$i};
		if(${'itms'.$i}) {
			global ${'itm'.$i},${'itmk'.$i},${'itme'.$i};
			$cmd .= '<input type="radio" name="command" id="itm'.$i.'" value="itm'.$i.'"><a onclick=sl("itm'.$i.'"); href="javascript:void(0);" >'."${'itm'.$i}/${'itme'.$i}/${'itms'.$i}".'</a><br>';
		}
	}
	$main = 'battle';
	return;
}

function findcorpse(&$w_pdata){
	global $log,$mode,$main,$battle_title,$cmd,$bid;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep;
	$battle_title = '发现尸体';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle(1);
	$bid = $w_pid;
	$main = 'battle';
	$log .= '你发现了 <span class="red">'.$w_name.'</span> 的尸体！<br>';

	include template('corpse');
	$cmd = ob_get_contents();
	ob_clean();
	return;
}


function senditem(){
	global $tablepre,$log,$mode,$main,$command,$cmd,$battle_title,$pls,$wid,$plsinfo,$message,$db,$now,$name,$w_log;


	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$wid'");
	if(!$db->num_rows($result)){
		$log .= "对方不存在！<br>";
		$mode = 'command';
		return;
	}

	$edata = $db->fetch_array($result);
	if($edata['pls'] != $pls) {
		$log .= "<span class=\"yellow\">".$edata['name']."</span> 已经离开了 <span class=\"yellow\">$plsinfo[$pls]</span> 。<br>";
		$mode = 'command';
		return;
	} elseif($edata['hp'] <= 0) {
		$log .= "<span class=\"yellow\">".$edata['name']."</span> 已经死亡，不能接受物品。<br>";
		$mode = 'command';
		return;
	}

	if($message){
		$log .= "<span class=\"lime\">你对 ".$edata['name']." 说：$message</span><br>";
		$w_log = "<span class=\"lime\">$name 对你说：$message</span>";
		if(!$edata['type']){logsave($edata['pid'],$now,$w_log);}
	}
	
	if($command != 'back'){
		$itmn = substr($command, 3);
		global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		if (!${'itms'.$itmn}) {
			$log .= '此道具不存在！';
			$mode = 'command';
			return;
		}
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};

		global $w_pid,$w_name,$w_pass,$w_type,$w_endtime,$w_gd,$w_sNo,$w_icon,$w_club,$w_hp,$w_mhp,$w_sp,$w_msp,$w_att,$w_def,$w_pls,$w_lvl,$w_exp,$w_money,$w_bid,$w_inf,$w_rage,$w_pose,$w_tactic,$w_killnum,$w_state,$w_wp,$w_wk,$w_wg,$w_wc,$w_wd,$w_teamID,$w_teamPass,$w_wep,$w_wepk,$w_wepe,$w_weps,$w_arb,$w_arbk,$w_arbe,$w_arbs,$w_arh,$w_arhk,$w_arhe,$w_arhs,$w_ara,$w_arak,$w_arae,$w_aras,$w_arf,$w_arfk,$w_arfe,$w_arfs,$w_art,$w_artk,$w_arte,$w_arts,$w_itm0,$w_itmk0,$w_itme0,$w_itms0,$w_itm1,$w_itmk1,$w_itme1,$w_itms1,$w_itm2,$w_itmk2,$w_itme2,$w_itms2,$w_itm3,$w_itmk3,$w_itme3,$w_itms3,$w_itm4,$w_itmk4,$w_itme4,$w_itms4,$w_itm5,$w_itmk5,$w_itme5,$w_itms5,$w_wepsk,$w_arbsk,$w_arhsk,$w_arask,$w_arfsk,$w_artsk,$w_itmsk0,$w_itmsk1,$w_itmsk2,$w_itmsk3,$w_itmsk4,$w_itmsk5;
		extract($edata,EXTR_PREFIX_ALL,'w');


		for($i = 1;$i < 6; $i++){
			if(!${'w_itms'.$i}) {
				${'w_itm'.$i} = $itm;
				${'w_itmk'.$i} = $itmk;
				${'w_itme'.$i} = $itme;
				${'w_itms'.$i} = $itms;
				${'w_itmsk'.$i} = $itmsk;
				$log .= "你将 <span class=\"yellow\">${'w_itm'.$i}</span> 送给了 <span class=\"yellow\">$w_name</span> 。<br>";
				$w_log = "<span class=\"yellow\">$name</span> 将 <span class=\"yellow\">${'w_itm'.$i}</span> 送给了你。";
				if(!$w_type){logsave($w_pid,$now,$w_log);}
				addnews($now,'senditem',$name,$w_name,$itm);
				w_save($w_pid);
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
				return;
			}
		}
		$log .= "<span class=\"yellow\">$w_name</span> 的包裹已经满了，不能赠送物品。<br>";
	}
	$mode = 'command';
	return;
}



?>