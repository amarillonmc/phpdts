<?php

define('CURSCRIPT', 'user');

require './include/common.inc.php';
require './include/user.func.php';

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }

if(!isset($mode)){
	$mode = 'show';
}

if($mode == 'edit') {
	$gamedata=Array();$gamedata['innerHTML']['info'] = '';
	if($opass && $npass && $rnpass){
		$pass_right = true;
		$pass_check = pass_check($npass,$rnpass);
		if($pass_check!='pass_ok'){
			$gamedata['innerHTML']['info'] .= $_ERROR[$pass_check].'<br />';
			$pass_right = false;
		}
		$opass = md5($opass);
		$npass = md5($npass);
		if($opass != $udata['password']){
			$gamedata['innerHTML']['info'] .= $_ERROR['wrong_pw'].'<br />';
			$pass_right = false;
		}
		if($pass_right){
			gsetcookie('pass',$npass);
			$passqry = "`password` ='$npass',";
			$gamedata['innerHTML']['info'] .= $_INFO['pass_success'].'<br />';
		}else{
			$passqry = '';
			$gamedata['innerHTML']['info'] .= $_INFO['pass_failure'].'<br />';
		}
	}else{
		$passqry = '';
		$gamedata['innerHTML']['info'] .= $_INFO['pass_failure'].'<br />';
	}
	$credits = $udata['credits'];$credits2 = $udata['credits2'];
	if($exchg12||$exchg21){
		//if(!is_numeric($exchg12)||$exchg12<0){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure'];}
		if(!is_numeric($exchg12)||!is_numeric($exchg21)||$exchg12<0||$exchg21<0){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure'];}
		elseif($exchg12 && $exchg21){$gamedata['innerHTML']['info'] .= $_INFO['credits_conflicts'];}
		else{
			if($exchg12){
				$exchg12 = ceil($exchg12);
				if($exchg12>$udata['credits']){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure2'];}
				elseif($exchg12 % 100){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure3'];}
				elseif($exchg12 > $credits/5){$gamedata['innerHTML']['info'] .= '不允许一次转换超过20%的积分！';}
				else{
					$credits -= $exchg12;
					$credits2 += $exchg12/100;
					$gamedata['innerHTML']['info'] .= $_INFO['credits_success'];
				}
			}elseif($exchg21){
				$exchg21 = ceil($exchg21);
				if($exchg21 > $credits2){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure2'];}
				else{
					$credits2 -= $exchg21;
					$credits += $exchg21*75;
					$gamedata['innerHTML']['info'] .= $_INFO['credits_success'];
				}
			}
		}
	}
	if ($icon>$iconlimit) $icon=0;
	$db->query("UPDATE {$tablepre}users SET gender='$gender', icon='$icon',{$passqry}motto='$motto',  killmsg='$killmsg', lastword='$lastword', credits='$credits', credits2='$credits2' ,nick='$nick' WHERE username='$cuser'");
	if($db->affected_rows()){
		$gamedata['innerHTML']['info'] .= $_INFO['data_success'];
	}else{
		$gamedata['innerHTML']['info'] .= $_INFO['data_failure'];
	}
	$gamedata['innerHTML']['credits'] = $credits;$gamedata['innerHTML']['credits2'] = $credits2;
	$gamedata['value']['opass'] = $gamedata['value']['npass'] = $gamedata['value']['rnpass'] = '';$gamedata['value']['exchg12'] = $gamedata['value']['exchg21'] = 0;
	if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();
	
} else {
	//$ustate = 'edit';
	extract($udata);
	$iconarray = get_iconlist($icon);
	$select_icon = $icon;
	//这里假定player表里有usertitle字段而且储存方式是这样蛋疼的。具体程序虚子你写。
	$utlist = get_utitlelist();//然后去接收用户传来的$
	include template('user');
}

?> 