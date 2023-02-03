<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function init_playerdata(){
	global $lvl,$baseexp,$exp,$gd,$icon,$arbe,$arhe,$arae,$arfe,$weather,$fog,$weps,$arbs,$log,$upexp,$lvlupexp,$iconImg,$iconImgB,$ardef;

	$upexp = round(($lvl*$baseexp)+(($lvl+1)*$baseexp));
	$lvlupexp = $upexp - $exp;
	$iconImg = $gd.'_'.$icon; $iconImgB = NULL;
	if(file_exists('img/'.$iconImg.'a.gif'))
	{
		$iconImgB = $iconImg.'a.gif';
	}
	else 
	{
		$iconImg = $iconImg.'.gif';
	}
	$ardef = $arbe + $arhe + $arae + $arfe;
	if(($weather == 8)||($weather == 9)||($weather == 12)) {
		$fog = true;
	}

	if(!$weps) {
		global $nowep,$nosta,$wep,$wepk,$wepsk,$wepe;
		$wep = $nowep;$wepk = 'WN';$wepsk = '';
		$wepe = 0; $weps = $nosta;
	}
	if(!$arbs) {
		global $noarb,$nosta,$arb,$arbk,$arbsk,$arbe;
		$arb = $noarb;$arbk = 'DN'; $arbsk = '';
		$arbe = 0; $arbs = $nosta;
	}
}

function init_profile(){
	global $inf,$infinfo,$hp,$mhp,$sp,$msp,$hpcolor,$spcolor,$newhpimg,$newspimg,$ardef,$arbe,$arhe,$arae,$arfe;
	global $iteminfo,$wepk,$arbk,$arhk,$arak,$arfk,$artk,$itmk0,$itmk1,$itmk2,$itmk3,$itmk4,$itmk5,$itmk6,$rp,$killnum,$karma,$def,$att;
	global $itemspkinfo,$wepsk,$arbsk,$arhsk,$arask,$arfsk,$artsk,$itmsk0,$itmsk1,$itmsk2,$itmsk3,$itmsk4,$itmsk5,$itmsk6;
	global $nospk,$wepsk_words,$arbsk_words,$arhsk_words,$arask_words,$arfsk_words,$artsk_words,$itmsk0_words,$itmsk1_words,$itmsk2_words,$itmsk3_words,$itmsk4_words,$itmsk5_words,$itmsk6_words;
	global $wepk_words,$arbk_words,$arhk_words,$arak_words,$arfk_words,$artk_words,$itmk0_words,$itmk1_words,$itmk2_words,$itmk3_words,$itmk4_words,$itmk5_words,$itmk6_words;
	global $wep,$arb,$arh,$ara,$arf,$art,$itm0,$itm1,$itm2,$itm3,$itm4,$itm5,$itm6;

	foreach (Array('wep','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $value) 
	{
		${$value} = parse_itm_desc(${$value},'m');
	}

	foreach (Array('wepk','arbk','arhk','arak','arfk','artk','itmk0','itmk1','itmk2','itmk3','itmk4','itmk5','itmk6') as $k_value) {
		if(${$k_value})
		{
			${$k_value.'_words'} = '';
			foreach($iteminfo as $info_key => $info_value)
			{
				if(strpos(${$k_value},$info_key)===0){
					${$k_value.'_words'} = parse_itm_desc($info_key,'k');
					break;
				}
			}
		} else {
			${$k_value.'_words'} = '';
		}
	}
	
	foreach (Array('wepsk','arbsk','arhsk','arask','arfsk','artsk','itmsk0','itmsk1','itmsk2','itmsk3','itmsk4','itmsk5','itmsk6') as $sk_value) {
		if(${$sk_value} && is_numeric(${$sk_value}) === false){
			${$sk_value.'_words'} = '';
			//取我数组斧来
			$tmpsk = get_itmsk_array(${$sk_value});
			if(count($tmpsk)>3)
			{
				//在装备、道具栏内的道具超过3个属性时，显示为+...+的缩写……不然属性多起来太丑了！！
				${$sk_value.'_words'} = parse_itm_desc($tmpsk,'sk',1);
			}
			else 
			{
				foreach($tmpsk as $sk)
				{
					if(!empty(${$sk_value.'_words'}))
					{
						${$sk_value.'_words'} .= "+".parse_itm_desc($sk,'sk');
					}
					else
					{
						${$sk_value.'_words'} = parse_itm_desc($sk,'sk');
					}
				}
			}
		} else {
			${$sk_value.'_words'} =$nospk;
		}
	}

	$ardef = $arbe + $arhe + $arae + $arfe;
	$karma = ($rp * $killnum - $def )+ $att;

//	$infdata = '';
//	
//	if(strpos($inf,'h') !== false || strpos($inf,'b') !== false ||strpos($inf,'a') !== false ||strpos($inf,'f') !== false){
//		//$infdata = '<span class="red b">';
//		$infimg .= '<img src="img/injured.gif" style="position:absolute;top:0;left:10;width:84;height:20">';
//		if(strpos($inf,'h') !== false){
//			$infdata .= $infinfo['h'];
//			$infimg .= '<img src="img/hurt.gif" style="position:absolute;top:0;left:121;width:37;height:37">';
//		}
//		if(strpos($inf,'a') !== false){
//			$infdata .= $infinfo['a'];
//			$infimg .= '<img src="img/hurt.gif" style="position:absolute;top:17;left:102;width:37;height:37">';
//		}
//		if(strpos($inf,'b') !== false){
//			$infdata .= $infinfo['b'];
//			$infimg .= '<img src="img/hurt.gif" style="position:absolute;top:43;left:121;width:37;height:37">';
//		}
//		if(strpos($inf,'f') !== false){
//			$infdata .= $infinfo['f'];
//			$infimg .= '<img src="img/hurt.gif" style="position:absolute;top:111;left:121;width:37;height:37">';
//		}
//	} else {
//		$infimg .= '<img src="img/injured2.gif" style="position:absolute;top:0;left:10;width:84;height:20">';
//	}
//	//$infdata .= '</span>';
//	if(strpos($inf,'p') !== false) {
//		$infdata .= $infinfo['p'];
//		//$infdata .= "<span class=\"purple b\">{$infinfo['p']}</span>";
//		$infimg .= '<img src="img/p.gif" style="position:absolute;top:20;left:4;width:98;height:20">';
//	} else {
//		$infimg .= '<img src="img/p2.gif" style="position:absolute;top:20;left:4;width:98;height:20">';
//	}
//	if(strpos($inf,'u') !== false) {
//		$infdata .= $infinfo['u'];
//		//$infdata .= "<span class=\"red b\">{$infinfo['u']}</span>";
//		$infimg .= '<img src="img/u.gif" style="position:absolute;top:40;left:11;width:81;height:20">';
//	} else {
//		$infimg .= '<img src="img/u2.gif" style="position:absolute;top:40;left:11;width:81;height:20">';
//	}
//	if(strpos($inf,'i') !== false) {
//		$infdata .= $infinfo['i'];
//		//$infdata .= "<span class=\"clan b\">{$infinfo['i']}</span>";
//		$infimg .= '<img src="img/i.gif" style="position:absolute;top:60;left:13;width:77;height:20">';
//	} else {
//		$infimg .= '<img src="img/i2.gif" style="position:absolute;top:60;left:13;width:77;height:20">';
//	}
//	if(strpos($inf,'e') !== false) {
//		$infdata .= $infinfo['e'];
//		//$infdata .= "<span class=\"yellow b\">{$infinfo['e']}</span>";
//		$infimg .= '<img src="img/e.gif" style="position:absolute;top:80;left:2;width:101;height:20">';
//	} else {
//		$infimg .= '<img src="img/e2.gif" style="position:absolute;top:80;left:2;width:101;height:20">';
//	}
//	if(strpos($inf,'w') !== false) {
//		$infdata .= $infinfo['w'];
//		$infimg .= '<img src="img/w.gif" style="position:absolute;top:100;left:3;width:100;height:20">';
//	} else {
//		$infimg .= '<img src="img/w2.gif" style="position:absolute;top:100;left:3;width:100;height:20">';
//	}


	$hpcolor = 'clan';
	if($hp <= 0 ){
		//$infimg .= '<img src="img/dead.gif" style="position:absolute;top:120;left:6;width:94;height:40">';
		$hpcolor = 'red';
	} elseif($hp <= $mhp*0.2){
		//$infimg .= '<img src="img/danger.gif" style="position:absolute;top:120;left:5;width:95;height:37">';
		$hpcolor = 'red';
	} elseif($hp <= $mhp*0.5){
		//$infimg .= '<img src="img/caution.gif" style="position:absolute;top:120;left:5;width:95;height:36">';
		$hpcolor = 'yellow';
	} elseif($inf == ''){
		//$infimg .= '<img src="img/fine.gif" style="position:absolute;top:120;left:12;width:81;height:38">';
	}
	
	if($sp <= $msp*0.2){
		$spcolor = 'grey';
	} elseif($sp <= $msp*0.5){
		$spcolor = 'yellow';
	} else {
		$spcolor = 'clan';
	}
	
	$newhppre = 5+floor(151*(1-$hp/$mhp));
	$newhpimg = '<img src="img/red2.gif" style="position:absolute; clip:rect('.$newhppre.'px,55px,160px,0px);">';
	$newsppre = 5+floor(151*(1-$sp/$msp));
	$newspimg = '<img src="img/yellow2.gif" style="position:absolute; clip:rect('.$newsppre.'px,55px,160px,0px);">';

	return;
}

function init_battle($ismeet = 0){
	global $wep,$wepk;
	global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_lvl,$w_rage,$w_hp,$w_sp,$w_mhp,$w_msp,$w_wep,$w_wepk,$w_wepe,$w_sNoinfo,$w_iconImg,$w_iconImgB,$w_hpstate,$w_spstate,$w_ragestate,$w_wepestate,$w_isdead,$hpinfo,$spinfo,$rageinfo,$wepeinfo,$fog,$typeinfo,$sexinfo,$infinfo,$w_exp,$w_upexp,$baseexp,$w_pose,$w_tactic,$w_inf,$w_infdata;
	global $n_type,$n_name,$n_gd,$n_sNo,$n_icon,$n_hp,$n_mhp,$n_sp,$n_msp,$n_rage,$n_wep,$n_wepk,$n_wepe,$n_lvl,$n_pose,$n_tactic,$n_inf;
	$w_upexp = round(($w_lvl*$baseexp)+(($w_lvl+1)*$baseexp));
	
	if (CURSCRIPT == 'botservice') 
	{
		echo "w_name=$w_name\n";
		echo "w_type=$w_type\n";
		echo "w_sNo=$w_sNo\n";
	}
	
	if($w_hp <= 0) {
		$w_hpstate = "<span class=\"red\">$hpinfo[3]</span>";
		$w_spstate = "<span class=\"red\">$spinfo[3]</span>";
		$w_ragestate = "<span class=\"red\">$rageinfo[3]</span>";
		$w_isdead = true;
		if (CURSCRIPT == 'botservice') echo "w_dead=1\n";
	} else{
		if($w_hp < $w_mhp*0.2) {
			$w_hpstate = "<span class=\"red\">$hpinfo[2]</span>";
			if (CURSCRIPT == 'botservice') echo "w_hpstate=2\n";
		} elseif($w_hp < $w_mhp*0.5) {
			$w_hpstate = "<span class=\"yellow\">$hpinfo[1]</span>";
			if (CURSCRIPT == 'botservice') echo "w_hpstate=1\n";
		} else {
			$w_hpstate = "<span class=\"clan\">$hpinfo[0]</span>";
			if (CURSCRIPT == 'botservice') echo "w_hpstate=0\n";
		}
		if($w_sp < $w_msp*0.2) {
		$w_spstate = "$spinfo[2]";
		} elseif($w_sp < $w_msp*0.5) {
		$w_spstate = "$spinfo[1]";
		} else {
		$w_spstate = "$spinfo[0]";
		}
		if($w_rage >= 100) {
		$w_ragestate = "<span class=\"red\">$rageinfo[2]</span>";
		} elseif($w_rage >= 30) {
		$w_ragestate = "<span class=\"yellow\">$rageinfo[1]</span>";
		} else {
		$w_ragestate = "$rageinfo[0]";
		}
	}

	if($n_hp <= 0)
	{
		global $n_hpstate,$n_spstate,$n_ragestate,$n_isdead;
		$n_hpstate = "<span class=\"red\">$hpinfo[3]</span>";
		$n_spstate = "<span class=\"red\">$spinfo[3]</span>";
		$n_ragestate = "<span class=\"red\">$rageinfo[3]</span>";
		$n_isdead = true;
	} elseif(isset($n_hp)) {
		global $n_hpstate,$n_spstate,$n_ragestate;
		if($n_hp < $n_mhp*0.2) {
			$n_hpstate = "<span class=\"red\">$hpinfo[2]</span>";
		} elseif($n_hp < $n_mhp*0.5) {
			$n_hpstate = "<span class=\"yellow\">$hpinfo[1]</span>";
		} else {
			$n_hpstate = "<span class=\"clan\">$hpinfo[0]</span>";
		}
		if($n_sp < $n_msp*0.2) {
			$n_spstate = "$spinfo[2]";
		} elseif($n_sp < $n_msp*0.5) {
			$n_spstate = "$spinfo[1]";
		} else {
			$n_spstate = "$spinfo[0]";
		}
		if($n_rage >= 100) {
		$n_ragestate = "<span class=\"red\">$rageinfo[2]</span>";
		} elseif($n_rage >= 30) {
			$n_ragestate = "<span class=\"yellow\">$rageinfo[1]</span>";
		} else {
			$n_ragestate = "$rageinfo[0]";
		}
	}
	
	if($w_wepe >= 400) {
		$w_wepestate = "$wepeinfo[3]";
		if (CURSCRIPT == 'botservice') echo "w_wepestate=3\n";
	} elseif($w_wepe >= 200) {
		$w_wepestate = "$wepeinfo[2]";
		if (CURSCRIPT == 'botservice') echo "w_wepestate=2\n";
	} elseif($w_wepe >= 60) {
		$w_wepestate = "$wepeinfo[1]";
		if (CURSCRIPT == 'botservice') echo "w_wepestate=1\n";
	} else {
		$w_wepestate = "$wepeinfo[0]";
		if (CURSCRIPT == 'botservice') echo "w_wepestate=0\n";
	}
	
	//在战斗界面中加载敌我双方武器tooltip
	global $wep_words,$wepk_words,$w_wep_words,$w_wepk_words;
	$wep_words = parse_itm_desc($wep,'m'); $wepk_words = parse_itm_desc($wepk,'k');
	if(!$fog||$ismeet) {
		//非雾天显示敌人武器情报
		$w_wep_words = parse_itm_desc($w_wep,'m');
		$w_wepk_words = parse_itm_desc($w_wepk,'k');
		//如果有的话 初始化第三方武器情报 
		if(isset($n_type))
		{
			global $n_wep_words,$n_wepk_words,$n_iconImg;
			$n_iconImg = $n_type ? 'n_'.$n_icon.'.gif' : $n_gd.'_'.$n_icon.'.gif';
			$n_wep_words = parse_itm_desc($n_wep,'m');
			$n_wepk_words = parse_itm_desc($n_wepk,'k');
		}
		$w_sNoinfo = "$typeinfo[$w_type]({$sexinfo[$w_gd]}{$w_sNo}号)";
	 	$w_i = $w_type > 0 ? 'n' : $w_gd;
		$w_iconImg = $w_i.'_'.$w_icon; $w_iconImgB = NULL;
		if(file_exists('img/'.$w_iconImg.'a.gif'))
		{
			$w_iconImgB = $w_iconImg.'a.gif';
		}
		else 
		{
			$w_iconImg = $w_iconImg.'.gif';
		}
		if($w_inf) {
			$w_infdata = '';
			foreach ($infinfo as $inf_ky => $inf_nm) {
				if(strpos($w_inf,$inf_ky) !== false) {
					$w_infdata .= $inf_nm;
				}
			}
			//$w_infdata = '<span class="red b">';
			/*if(strpos($w_inf,'h') !== false){
				$w_infdata .= $infinfo['h'];
			}
			if(strpos($w_inf,'a') !== false){
				$w_infdata .= $infinfo['a'];
			}
			if(strpos($w_inf,'b') !== false){
				$w_infdata .= $infinfo['b'];
			}
			if(strpos($w_inf,'f') !== false){
				$w_infdata .= $infinfo['f'];
			}*/
			//$infdata .= '</span>';
			/*if(strpos($w_inf,'p') !== false) {
				$w_infdata .= "<span class=\"purple b\">{$infinfo['p']}</span>";
			}
			if(strpos($w_inf,'u') !== false) {
				$w_infdata .= "<span class=\"yellow b\">{$infinfo['u']}</span>";
			}
			if(strpos($w_inf,'i') !== false) {
				$w_infdata .= "<span class=\"clan b\">{$infinfo['i']}</span>";
			}*/
		} else {
			$w_infdata = '';
		}
	} else {
		//雾天显示？？？
		$w_wep_words = '？？？';
		$w_wepk_words = '？？？';
		$w_sNoinfo = '？？？';
		$w_iconImg = 'question.gif';
		$w_name = '？？？';
		$w_wep = '？？？';
		$w_infdata = '？？？';
		$w_pose = -1;
		$w_tactic = -1;
		$w_lvl = '？';
		$w_hpstate = '？？？';
		$w_spstate = '？？？';
		$w_ragestate = '？？？';
		$w_wepestate = '？？？';
		$w_wepk = '';
	}
	return;
}

function init_rev_battle($ismeet = 0)
{
	global $fog,$hpinfo,$spinfo,$rageinfo,$wepeinfo,$typeinfo,$sexinfo,$infinfo;
	$ui_data = Array('hpstate','spstate','ragestate','wepestate','wepk_words','wep_words','infdata','iconImg','iconImgB','sNoinfo');
	$init_data = update_db_player_structure();
	foreach(Array('w_','s_') as $p)
	{
		foreach ($init_data as $i)
   		{
			global ${$p.$i};
    	}
		foreach($ui_data as $u)
		{
			global ${$p.$u};
		}
		//更新血量、体力、怒气显示
		if(${$p.'hp'} <= 0)
		{
			${$p.'hpstate'} = "<span class=\"red\">$hpinfo[3]</span>";
			${$p.'spstate'} = "<span class=\"red\">$spinfo[3]</span>";
			${$p.'ragestate'} = "<span class=\"red\">$rageinfo[3]</span>";
		}
		else 
		{
			if(${$p.'hp'} < ${$p.'mhp'}*0.2) {
				${$p.'hpstate'} = "<span class=\"red\">$hpinfo[2]</span>";
			} elseif(${$p.'hp'} < ${$p.'mhp'}*0.5) {
				${$p.'hpstate'} = "<span class=\"yellow\">$hpinfo[1]</span>";
			} else {
				${$p.'hpstate'} = "<span class=\"clan\">$hpinfo[0]</span>";
			}
			if(${$p.'sp'} < ${$p.'msp'}*0.2) {
				${$p.'spstate'} = "$spinfo[2]";
			} elseif(${$p.'sp'} < ${$p.'msp'}*0.5) {
				${$p.'spstate'} = "$spinfo[1]";
			} else {
				${$p.'spstate'} = "$spinfo[0]";
			}
			if(${$p.'rage'} >= 100) {
				${$p.'ragestate'} = "<span class=\"red\">$rageinfo[2]</span>";
			} elseif(${$p.'rage'} >= 30) {
				${$p.'ragestate'} = "<span class=\"yellow\">$rageinfo[1]</span>";
			} else {
				${$p.'ragestate'} = "$rageinfo[0]";
			}
		}
		//更新武器效果情报
		switch(${$p.'wepe'})
		{
			case ${$p.'wepe'}>= 400:
				${$p.'wepestate'} = "$wepeinfo[3]";
				break;
			case ${$p.'wepe'}>= 200:
				${$p.'wepestate'} = "$wepeinfo[2]";
				break;
			case ${$p.'wepe'}>= 60:
				${$p.'wepestate'} = "$wepeinfo[1]";
				break;
			default :
				${$p.'wepestate'} = "$wepeinfo[0]";
		}
		//更新武器名、武器类别情报
		${$p.'wep_words'} = parse_itm_desc(${$p.'wep'},'m');
		${$p.'wepk_words'} = parse_itm_desc(${$p.'wepk'},'k');
		//更新编号情报
		${$p.'sNoinfo'} = $typeinfo[${$p.'type'}]."(".$sexinfo[${$p.'gd'}].${$p.'sNo'}."号)";
		//更新头像情报
		$itype = ${$p.'type'} > 0 ? 'n' : ${$p.'gd'};
		$iname = $itype.'_'.${$p.'icon'}; 
		if(file_exists('img/'.$iname.'a.gif'))
		{
			${$p.'iconImgB'}= $iname.'a.gif';
		}
		else 
		{
			${$p.'iconImg'} = $iname.'.gif';
			unset(${$p.'iconImgB'});
		}
		//更新受伤状态
		if(${$p.'inf'}) 
		{
			${$p.'infdata'} = '';
			foreach ($infinfo as $inf_ky => $inf_nm) 
			{
				if(strpos(${$p.'inf'},$inf_ky) !== false) ${$p.'infdata'} .= $inf_nm;	
			}
		}
		else 
		{
			${$p.'infdata'} = '';
		}
	}
	if($fog && !$ismeet)
	{
		//雾天显示？？？
		$w_wep_words = '？？？';
		$w_wepk_words = '？？？';
		$w_sNoinfo = '？？？';
		$w_iconImg = 'question.gif';
		$w_iconImgB = '';
		$w_name = '？？？';
		$w_wep = '？？？';
		$w_infdata = '？？？';
		$w_pose = -1;
		$w_tactic = -1;
		$w_lvl = '？';
		$w_hpstate = '？？？';
		$w_spstate = '？？？';
		$w_ragestate = '？？？';
		$w_wepestate = '？？？';
		$w_wepk = '';
	}
	return;
}

function get_remaincdtime($pid){
	$psdata = get_pstate($pid);
	if($psdata){
		$cdover = $psdata['cdsec']*1000 + $psdata['cdmsec'] + $psdata['cdtime'];
		$nowmtime = floor(getmicrotime()*1000);
		$rmtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
		return floor($rmtime);
	}else{
		return 0;
	}	
}

//用于将当前玩家数据保存至数据库
function current_player_save(){
	global $db,$tablepre;
	$pdata = Array();
	$data = update_db_player_structure();
	foreach($data as $key)
	{
		global $$key;
		$pdata[$key]= $$key;
	}
	$db->array_update("{$tablepre}players",$pdata,"pid='$pid'");
	return $pdata;
}
//用于将指定player数据存回数据库
function player_save($data){
	global $db,$tablepre;
	$ndata = Array();
	if(isset($data['pid'])){
		$pid = $data['pid'];
		$ndata = player_format_with_db_structure($data);
		unset($data);
		$db->array_update("{$tablepre}players",$ndata,"pid='$pid'");
	}
	return;
}
//用于刷新当前玩家数据
function player_load($data)
{
	$ndata = player_format_with_db_structure($data);
	foreach ($ndata as $key => $value)
	{
		global $$key;
		$$key = $value;
	}
	return;
}

function w_save($id){
	global $db,$tablepre,$w_name,$w_pass,$w_type,$w_endtime,$w_deathtime,$w_gd,$w_sNo,$w_icon,$w_club,$w_hp,$w_mhp,$w_sp,$w_msp,$w_att,$w_def,$w_pls,$w_lvl,$w_exp,$w_money,$w_bid,$w_inf,$w_rage,$w_pose,$w_tactic,$w_killnum,$w_state,$w_wp,$w_wk,$w_wg,$w_wc,$w_wd,$w_wf,$w_teamID,$w_teamPass,$w_wep,$w_wepk,$w_wepe,$w_weps,$w_arb,$w_arbk,$w_arbe,$w_arbs,$w_arh,$w_arhk,$w_arhe,$w_arhs,$w_ara,$w_arak,$w_arae,$w_aras,$w_arf,$w_arfk,$w_arfe,$w_arfs,$w_art,$w_artk,$w_arte,$w_arts,$w_itm0,$w_itmk0,$w_itme0,$w_itms0,$w_itm1,$w_itmk1,$w_itme1,$w_itms1,$w_itm2,$w_itmk2,$w_itme2,$w_itms2,$w_itm3,$w_itmk3,$w_itme3,$w_itms3,$w_itm4,$w_itmk4,$w_itme4,$w_itms4,$w_itm5,$w_itmk5,$w_itme5,$w_itms5,$w_itm6,$w_itmk6,$w_itme6,$w_itms6,$w_wepsk,$w_arbsk,$w_arhsk,$w_arask,$w_arfsk,$w_artsk,$w_itmsk0,$w_itmsk1,$w_itmsk2,$w_itmsk3,$w_itmsk4,$w_itmsk5,$w_itmsk6,$w_rp,$w_action,$w_achievement,$w_skillpoint;
	
	$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',deathtime='$w_deathtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',action='$w_action',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6',rp='$w_rp',achievement='$w_achievement',skillpoint='$w_skillpoint'    WHERE pid='$id'");
	//$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6' WHERE pid='$id'");

	return ;
}

function w_save2(&$data){
	global $db,$tablepre;
	if(isset($data)){
		extract($data,EXTR_PREFIX_ALL,'w');
		$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',deathtime='$w_deathtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6',rp='$w_rp',achievement='$w_achievement',skillpoint='$w_skillpoint'  WHERE pid='$w_pid'");
		//$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6' WHERE pid='$w_pid'");
	}
	return;
}

//销毁尸体
function destory_corpse(&$edata)
{
	if($edata)
	{
		$edata['state'] = 16; $edata['hp'] = 0; $edata['money'] = 0; $edata['pls'] = 254;
		$edata['weps'] = 0;$edata['arbs'] = 0;$edata['arhs'] = 0;$edata['aras'] = 0;$edata['arfs'] = 0;$edata['arts'] = 0;
		$edata['itms0'] = 0;$edata['itms1'] = 0;$edata['itms2'] = 0;$edata['itms3'] = 0;$edata['itms4'] = 0;$edata['itms5'] = 0;$edata['itms6'] = 0;
		player_save($edata);
	}
	return;
}


?>
