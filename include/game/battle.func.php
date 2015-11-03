<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}



function findenemy(&$w_pdata) {
	global $log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo,$wepk,$wp,$wk,$wg,$wc,$wd,$wf,$nosta,$weps;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_sp,$w_msp,$w_rage,$w_wep,$w_wepk,$w_wepe,$w_lvl,$w_pose,$w_tactic,$w_inf;//,$itmsk0;
	
	if (CURSCRIPT == 'botservice') echo "mode=enemy_spotted\n";
	
	$battle_title = 'D I S C O V E R Y !!';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle();
	
	$log .= "You discovered enemy <span class=\"red\">$w_name</span>！<br>They have not noticed you!<br>";
	
//	$cmd .= '现在想要做什么？<br><br>';
//	$cmd .= '向对手大喊：<br><input size="30" type="text" name="message" maxlength="60"><br><br>';
//	$cmd .= '<input type="hidden" name="mode" value="combat">';
	if (CURSCRIPT !== 'botservice') 
	{
		$w1 = substr($wepk,1,1);
		$w2 = substr($wepk,2,1);
		if (($w2=='0')||($w2=='1')) {$w2='';}
		if((($w1 == 'G')||($w1=='J'))&&($weps==$nosta)){ $w1 = 'P'; }
//	$cmd .= '<input type="radio" name="command" id="'.$w1.'" value="'.$w1.'" checked><a onclick=sl("'.$w1.'"); href="javascript:void(0);">'."$attinfo[$w1] (${$skillinfo[$w1]})".'</a><br>';
//	if($w2) {
//		$cmd .= '<input type="radio" name="command" id="'.$w2.'" value="'.$w2.'"><a onclick=sl("'.$w2.'"); href="javascript:void(0);">'."$attinfo[$w2] (${$skillinfo[$w2]})".'</a><br>';
//	}
		include template('battlecmd');
		$cmd = ob_get_contents();
		ob_clean();
	}
//	$cmd .= '<input type="radio" name="command" id="back" value="back"><a onclick=sl("back"); href="javascript:void(0);" >逃跑</a><br>';

	$main = 'battle';
	
	return;
}

function findteam(&$w_pdata){
	global $log,$mode,$main,$cmd,$battle_title,$gamestate;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_sp,$w_msp,$w_rage,$w_wep,$w_wepk,$w_wepe,$w_lvl,$w_pose,$w_tactic,$w_inf;//,$itmsk0;

	if($gamestate>=40){
		$log .= '<span class="yellow">连斗阶段所有队伍取消！</span><br>';
		
		$mode = 'command';
		return;
	}
	$battle_title = 'T E A M M A T E !';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle(1);
	
	$log .= "You found your teammate <span class=\"yellow\">$w_name</span>！<br>";
	for($i = 1;$i <= 6; $i++){
		global ${'itm'.$i},${'itme'.$i},${'itms'.$i};
	}
	include template('findteam');
	$cmd = ob_get_contents();
	ob_clean();
//	$cmd .= '现在想要做什么？<br><br>';
//	$cmd .= '留言：<br><input size="30" type="text" name="message" maxlength="60"><br><br>';
//	$cmd .= '想要转让什么？<input type="hidden" name="mode" value="senditem"><br><input type="radio" name="command" id="back" value="back" checked><a onclick=sl("back"); href="javascript:void(0);" >不转让</a><br><br>';
//	for($i = 1;$i < 6; $i++){
//		global ${'itms'.$i};
//		if(${'itms'.$i}) {
//			global ${'itm'.$i},${'itmk'.$i},${'itme'.$i};
//			$cmd .= '<input type="radio" name="command" id="itm'.$i.'" value="itm'.$i.'"><a onclick=sl("itm'.$i.'"); href="javascript:void(0);" >'."${'itm'.$i}/${'itme'.$i}/${'itms'.$i}".'</a><br>';
//		}
//	}
	$main = 'battle';
	return;
}

function findcorpse(&$w_pdata){
	global $log,$mode,$main,$battle_title,$cmd,$iteminfo,$itemspkinfo;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep,$w_wepk,$w_wepe,$w_lvl,$w_pose,$w_tactic,$w_inf;//,$itmsk0;
	
	$battle_title = 'C O R P S E !!';
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle(1);
	
	if (CURSCRIPT == 'botservice')
	{
		echo "mode=corpse\n";
		foreach (Array('w_wep','w_arb','w_arh','w_ara','w_arf','w_art') as $w_value) 
			if (${$w_value.'s'})
			{
				echo "{$w_value}=".${$w_value}."\n";
				echo "{$w_value}k=".${$w_value.'k'}."\n";
				echo "{$w_value}e=".${$w_value.'e'}."\n";
				echo "{$w_value}s=".${$w_value.'s'}."\n";
				echo "{$w_value}sk=".${$w_value.'sk'}."\n";
			}
		foreach (Array('1','2','3','4','5','6') as $w_itm_id) 
			if (${'w_itms'.$w_itm_id})
			{
				echo "w_itm{$w_itm_id}=".${'w_itm'.$w_itm_id}."\n";
				echo "w_itmk{$w_itm_id}=".${'w_itmk'.$w_itm_id}."\n";
				echo "w_itme{$w_itm_id}=".${'w_itme'.$w_itm_id}."\n";
				echo "w_itms{$w_itm_id}=".${'w_itms'.$w_itm_id}."\n";
				echo "w_itmsk{$w_itm_id}=".${'w_itmsk'.$w_itm_id}."\n";
			}
	}
	else
	{	
		$main = 'battle';
		$log .= '你发现了<span class="red">'.$w_name.'</span>的尸体！<br>';
		foreach (Array('w_wepk','w_arbk','w_arhk','w_arak','w_arfk','w_artk','w_itmk0','w_itmk1','w_itmk2','w_itmk3','w_itmk4','w_itmk5','w_itmk6') as $w_k_value) {
			if(${$w_k_value}){
				foreach($iteminfo as $info_key => $info_value){
					if(strpos(${$w_k_value},$info_key)===0){
						${$w_k_value.'_words'} = $info_value;
						break;
					}
				}
			}
		}
		foreach (Array('w_wepsk','w_arbsk','w_arhsk','w_arask','w_arfsk','w_artsk','w_itmsk0','w_itmsk1','w_itmsk2','w_itmsk3','w_itmsk4','w_itmsk5','w_itmsk6') as $w_sk_value) {
			${$w_sk_value.'_words'} = '';
			if(${$w_sk_value} && ! is_numeric(${$w_sk_value})){
				
				for ($i = 0; $i < strlen($w_sk_value)-1; $i++) {
					$sub = substr(${$w_sk_value},$i,1);
					if(!empty($sub)){
						${$w_sk_value.'_words'} .= $itemspkinfo[$sub];
					}
				}
				
			}
		}
		include template('corpse');
		$cmd = ob_get_contents();
		ob_clean();
	}
	return;
}


function senditem(){
	global $db,$tablepre,$log,$mode,$main,$command,$cmd,$battle_title,$pls,$plsinfo,$message,$now,$name,$w_log,$teamID,$gamestate,$action;
	$mateid = str_replace('team','',$action);
	if(!$mateid || strpos($action,'team')===false){
		$log .= '<span class="yellow">你没有遇到队友，或已经离开现场！</span><br>';
		$action = '';
		$mode = 'command';
		return;
	}
	if($gamestate>=40){
		$log .= '<span class="yellow">连斗阶段无法赠送物品！</span><br>';
		$action = '';
		$mode = 'command';
		return;
	}
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$mateid'");
	if(!$db->num_rows($result)){
		$log .= "对方不存在！<br>";
		$action = '';
		$mode = 'command';
		return;
	}

	$edata = $db->fetch_array($result);
	if($edata['pls'] != $pls) {
		$log .= '<span class="yellow">'.$edata['name'].'</span>已经离开了<span class="yellow">'.$plsinfo[$pls].'</span>。<br>';
		$mode = 'command';
		$action = '';
		return;
	} elseif($edata['hp'] <= 0) {
		$log .= '<span class="yellow">'.$edata['name'].'</span>已经死亡，不能接受物品。<br>';
		$mode = 'command';
		$action = '';
		return;
	} elseif(!$teamID || $edata['teamID']!=$teamID){
		$log .= '<span class="yellow">'.$edata['name'].'</span>并非你的队友，不能接受物品。<br>';
		$mode = 'command';
		$action = '';
		return;
	}

	if($message){
//		foreach ( Array('<','>',';',',') as $value ) {
//			if(strpos($message,$value)!==false){
//				$message = str_replace ( $value, '', $message );
//			}
//		}
		$log .= "<span class=\"lime\">你对{$edata['name']}说：“{$message}”</span><br>";
		$w_log = "<span class=\"lime\">{$name}对你说：“{$message}”</span><br>";
		if(!$edata['type']){logsave($edata['pid'],$now,$w_log,'c');}
	}
	
	if($command != 'back'){
		$itmn = substr($command, 3);
		global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		if (!${'itms'.$itmn}) {
			$log .= '此道具不存在！';
			$action = '';
			$mode = 'command';
			return;
		}
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};

		global $w_pid,$w_name,$w_pass,$w_type,$w_endtime,$w_gd,$w_sNo,$w_icon,$w_club,$w_hp,$w_mhp,$w_sp,$w_msp,$w_att,$w_def,$w_pls,$w_lvl,$w_exp,$w_money,$w_bid,$w_inf,$w_rage,$w_pose,$w_tactic,$w_killnum,$w_state,$w_wp,$w_wk,$w_wg,$w_wc,$w_wd,$w_wf,$w_teamID,$w_teamPass,$w_wep,$w_wepk,$w_wepe,$w_weps,$w_arb,$w_arbk,$w_arbe,$w_arbs,$w_arh,$w_arhk,$w_arhe,$w_arhs,$w_ara,$w_arak,$w_arae,$w_aras,$w_arf,$w_arfk,$w_arfe,$w_arfs,$w_art,$w_artk,$w_arte,$w_arts,$w_itm0,$w_itmk0,$w_itme0,$w_itms0,$w_itm1,$w_itmk1,$w_itme1,$w_itms1,$w_itm2,$w_itmk2,$w_itme2,$w_itms2,$w_itm3,$w_itmk3,$w_itme3,$w_itms3,$w_itm4,$w_itmk4,$w_itme4,$w_itms4,$w_itm5,$w_itmk5,$w_itme5,$w_itms5,$w_itm6,$w_itmk6,$w_itme6,$w_itms6,$w_wepsk,$w_arbsk,$w_arhsk,$w_arask,$w_arfsk,$w_artsk,$w_itmsk0,$w_itmsk1,$w_itmsk2,$w_itmsk3,$w_itmsk4,$w_itmsk5,$w_itmsk6,$nick;
		extract($edata,EXTR_PREFIX_ALL,'w');


		for($i = 1;$i <= 6; $i++){
			if(!${'w_itms'.$i}) {
				${'w_itm'.$i} = $itm;
				${'w_itmk'.$i} = $itmk;
				${'w_itme'.$i} = $itme;
				${'w_itms'.$i} = $itms;
				${'w_itmsk'.$i} = $itmsk;
				$log .= "你将<span class=\"yellow\">${'w_itm'.$i}</span>送给了<span class=\"yellow\">$w_name</span>。<br>";
				$w_log = "<span class=\"yellow\">$name</span>将<span class=\"yellow\">${'w_itm'.$i}</span>送给了你。";
				if(!$w_type){logsave($w_pid,$now,$w_log,'t');}
				addnews($now,'senditem',$nick.' '.$name,$w_name,$itm);
				w_save($w_pid);
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
				$action = '';
				return;
			}
		}
		$log .= "<span class=\"yellow\">$w_name</span> 的包裹已经满了，不能赠送物品。<br>";
	}
	$action = '';
	$mode = 'command';
	return;
}

?>
