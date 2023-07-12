<?php


if(!defined('IN_GAME')) {
	exit('Access Denied');
}



function getword(){
	global $db,$gtablepre,$tablepre,$name,$motto,$lastword,$killmsg;
	global $udata;
	//$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$name'");
	//$userinfo = $db->fetch_array($result);
	$userinfo = $udata;
	$motto = $userinfo['motto'];
	$lastword = $userinfo['lastword'];
	$killmsg = $userinfo['killmsg'];
	
}

function chgword($nmotto,$nlastword,$nkillmsg) {
	global $db,$gtablepre,$tablepre,$name,$log;
	global $udata;
	//$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$name'");
	//$userinfo = $db->fetch_array($result);
	$userinfo = $udata;
//	foreach ( Array('<','>',';',',','\\\'','\\"') as $value ) {
//		if(strpos($nmotto,$value)!==false){
//			$nmotto = str_replace ( $value, '', $nmotto );
//		}
//		if(strpos($nlastword,$value)!==false){
//			$nlastword = str_replace ( $value, '', $nlastword );
//		}
//		if(strpos($nkillmsg,$value)!==false){
//			$nkillmsg = str_replace ( $value, '', $nkillmsg );
//		}
//	}

	
	if($nmotto != $userinfo['motto']) {
		$log .= $nmotto == '' ? '口头禅已清空。' : '口头禅变更为<span class="yellow">'.$nmotto.'</span>。<br>';
	}
	if($nlastword != $userinfo['lastword']) {
		$log .= $nlastword == '' ? '遗言已清空。' : '遗言变更为<span class="yellow">'.$nlastword.'</span>。<br>';
	}
	if($nkillmsg != $userinfo['killmsg']) {
		$log .= $nkillmsg == '' ? '杀人留言已清空。' : '杀人留言变更为<span class="yellow">'.$nkillmsg.'</span>。<br>';
	}

	$db->query("UPDATE {$gtablepre}users SET motto='$nmotto', lastword='$nlastword', killmsg='$nkillmsg' WHERE username='$name'");
	
	$mode = 'command';
	return;
}

function chgpassword($oldpswd,$newpswd,$newpswd2){
	global $db,$gtablepre,$tablepre,$name,$log;
	
	if (!$oldpswd || !$newpswd || !$newpswd2){
		$log .= '放弃了修改密码。<br />';
		$mode = 'command';
		return;
	} elseif ($newpswd !== $newpswd2) {
		$log .= '<span class="red">两次输入的新密码不一致。</span><br />';
		$mode = 'command';
		return;
	}
	
	$oldpswd = md5($oldpswd);$newpswd = md5($newpswd);
	
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$name'");
	$userinfo = $db->fetch_array($result);
	
	if($oldpswd == $userinfo['password']){
		$db->query("UPDATE {$gtablepre}users SET `password` ='$newpswd' WHERE username='$name'");
		$log .= '<span class="yellow">密码已修改！</span><br />';
		
		//include_once GAME_ROOT.'./include/global.func.php';
		
		gsetcookie('pass',$newpswd);
		$mode = 'command';
		return;
	}else{
		$log .= '<span class="red">原密码输入错误！</span><br />';
		$mode = 'command';
		return;
	}
}
function oneonone($sb,$sf){
	global $db,$gold,$mode,$now,$gtablepre,$tablepre,$log,$name,$art,$arte,$artk,$arts,$artsk;
	$mode = 'command';
	if($sb == $sf){
		$log .= "不能自我约战。<br>";
		return;
	}
	if(($artk=='XX')||($artk=='XY')){
		$log .= "不能重复约战。<br>";
		return;
	}
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE name='$sb' AND type = 0");
	$edata = $db->fetch_array($result);
	$a1=$edata['art'];
	$a2=$edata['artk'];
	$a3=$edata['pid'];
	$a4=$edata['hp'];
	if (!$a3){
		$log .= "该ID不存在！<br>";
		return;
	}
	if (!$a4){
		$log .= "不能和死人约战。<br>";
		return;
	}
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE name='$sf' AND type = 0");
	$edata = $db->fetch_array($result);
	$a1=$edata['money'];
	if ($a1<1500){
		$log .= "需要携带1500G才能约战。<br>";
		return;
	}
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$sb'");
	$edata = $db->fetch_array($result);
	$a1=$edata['ip'];
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$sf'");
	$edata = $db->fetch_array($result);
	$a2=$edata['ip'];
	if($a1 == $a2){
		//$log .= "不能自我约战。<br>";
		//return;
	}
	if(preg_match('/[,|<|>|&|;|#|"|\s|\p{C}]+/u',$sb)) { $log.='请不要尝试注入……';return; }
	$art=$sb;$artk='XY';$arte=1;$arts=1;$artsk='';
	$taunt=$sf.'喊道：“'.$sb.'，来，战♂个♂痛♂快！”';
	$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('4','$now','$name','$taunt')");
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE name='$sb' AND type = 0");
	$edata = $db->fetch_array($result);
	$a1=$edata['art'];
	$a2=$edata['artk'];
	if (($a1==$sf)&&($a2=='XY')){
		$artk='XX';
		$db->query ( "UPDATE {$tablepre}players SET artk='XX' WHERE `name` ='$sb' AND type=0 ");
		$taunt='约战成立！';
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('4','$now','$name','$taunt')");
	}
	return;
}

function adtsk(){
	global $log,$mode,$club,$wep,$wepk,$wepe,$weps,$wepsk;
	if($wepk == 'WN' || !$wepe || !$weps){
		$log .= '<span class="red">你没有装备武器，无法改造！</span><br />';
		$mode = 'command';
		return;
	}
	if (strpos($wepsk,'j')!==false){
				$log.='多重武器不能改造。<br>';
				$mode='command';
				return;
			}
	if($club == 7){//电脑社，电气改造
		$position = 0;
		foreach(Array(1,2,3,4,5,6) as $imn){
			global ${'itm'.$imn},${'itmk'.$imn},${'itme'.$imn},${'itms'.$imn},${'itmsk'.$imn};
			if(strpos(${'itmk'.$imn},'B')===0 && ${'itme'.$imn} > 0 ){
				$position = $imn;
				break;
			}
		}
		if($position){
			if(strpos($wepsk,'e')!==false){
				$log .= '<span class="red">武器已经带有电击属性，不用改造！</span><br />';
				$mode = 'command';
				return;
			}elseif(strlen($wepsk)>=5){
				$log .= '<span class="red">武器属性数目达到上限，无法改造！</span><br />';
				$mode = 'command';
				return;
			}
			
			
			${'itms'.$position}-=1;
			$itm = ${'itm'.$position};
			$log .= "<span class=\"yellow\">用{$itm}改造了{$wep}，{$wep}增加了电击属性！</span><br />";
			$wep = '电气'.$wep;
			$wepsk .= 'e';
			if(${'itms'.$position} == 0){
				$log .= "<span class=\"red\">$itm</span>用光了。<br />";
				${'itm'.$position} = ${'itmk'.$position} = ${'itmsk'.$position} = '';
				${'itme'.$position} =${'itms'.$position} =0;				
			}
			$mode = 'command';
			return;
		}else{
			$log .= '<span class="red">你没有电池，无法改造武器！</span><br />';
			$mode = 'command';
			return;
		}
	}elseif($club == 8){//带毒改造
		$position = 0;
		foreach(Array(1,2,3,4,5,6) as $imn){
			global ${'itm'.$imn},${'itmk'.$imn},${'itme'.$imn},${'itms'.$imn},${'itmsk'.$imn};
			if(${'itm'.$imn} == '毒药' && ${'itmk'.$imn} == 'Y' && ${'itme'.$imn} > 0 ){
				$position = $imn;
				break;
			}
		}
		if($position){
			if(strpos($wepsk,'p')!==false){
				$log .= '<span class="red">武器已经带毒，不用改造！</span><br />';
				$mode = 'command';
				return;
			}elseif(strlen($wepsk)>=5){
				$log .= '<span class="red">武器属性数目达到上限，无法改造！</span><br />';
				$mode = 'command';
				return;
			}
			$wepsk .= 'p';
			$log .= "<span class=\"yellow\">用毒药为{$wep}淬毒了，{$wep}增加了带毒属性！</span><br />";
			$wep = '毒性'.$wep;
			${'itms'.$position}-=1;
			$itm = ${'itm'.$position};
			if(${'itms'.$position} == 0){
				$log .= "<span class=\"red\">$itm</span>用光了。<br />";
				${'itm'.$position} = ${'itmk'.$position} = ${'itmsk'.$position} = '';
				${'itme'.$position} =${'itms'.$position} =0;				
			}
			$mode = 'command';
			return;
		}else{
			$log .= '<span class="red">你没有毒药，无法给武器淬毒！</span><br />';
			$mode = 'command';
			return;
		}
	}else{
		$log .= '<span class="red">你不懂得如何改造武器！</span><br />';
		$mode = 'command';
		return;
	}
}

function trap_adtsk($which){
	global $log,$mode,$club,${'itm'.$which},${'itmk'.$which},${'itme'.$which},${'itms'.$which};
	if(strpos(${'itmk'.$which},'T')!==0){
		$log .= '<span class="red">这个物品不是陷阱，无法改造！</span><br />';
		$mode = 'command';
		return;
	}
	if(${'itmk'.$which}=='TOc' || ${'itmk'.$which}=='TNc'){
		$log .= '<span class="red">奇迹陷阱不允许改造！</span><br />';
		$mode = 'command';
		return;
	}
	if($club == 7){//电脑社，电气改造
		if (strpos(${'itm'.$which},'电气')!==false){
			$log .= '<span class="red">陷阱已经带有电击属性，不用改造！</span><br />';
			$mode='command';
			return;
		}
		$position = 0;
		foreach(Array(1,2,3,4,5,6) as $imn){
			global ${'itm'.$imn},${'itmk'.$imn},${'itme'.$imn},${'itms'.$imn},${'itmsk'.$imn};
			if(strpos(${'itmk'.$imn},'B')===0 && ${'itme'.$imn} > 0 ){
				$position = $imn;
				break;
			}
		}
		if($position){
			${'itms'.$position}-=1;
			$itm = ${'itm'.$position}; $citm=${'itm'.$which};
			$log .= "<span class=\"yellow\">用{$itm}改造了{$citm}，{$citm}增加了电击属性！</span><br />";
			${'itm'.$which} = '电气'.${'itm'.$which};
			if(${'itms'.$position} == 0){
				$log .= "<span class=\"red\">$itm</span>用光了。<br />";
				${'itm'.$position} = ${'itmk'.$position} = ${'itmsk'.$position} = '';
				${'itme'.$position} =${'itms'.$position} =0;				
			}
			$mode = 'command';
			return;
		}else{
			$log .= '<span class="red">你没有电池，无法改造陷阱！</span><br />';
			$mode = 'command';
			return;
		}
	}elseif($club == 8){//带毒改造
		if (strpos(${'itm'.$which},'毒性')!==false){
			$log .= '<span class="red">陷阱已经带毒，不用改造！</span><br />';
			$mode='command';
			return;
		}
		$position = 0;
		foreach(Array(1,2,3,4,5,6) as $imn){
			global ${'itm'.$imn},${'itmk'.$imn},${'itme'.$imn},${'itms'.$imn},${'itmsk'.$imn};
			if(${'itm'.$imn} == '毒药' && ${'itmk'.$imn} == 'Y' && ${'itme'.$imn} > 0 ){
				$position = $imn;
				break;
			}
		}
		if($position){
			${'itms'.$position}-=1;
			$itm = ${'itm'.$position}; $citm=${'itm'.$which};
			$log .= "<span class=\"yellow\">用{$itm}改造了{$citm}，{$citm}增加了带毒属性！</span><br />";
			${'itm'.$which} = '毒性'.${'itm'.$which};
			if(${'itms'.$position} == 0){
				$log .= "<span class=\"red\">$itm</span>用光了。<br />";
				${'itm'.$position} = ${'itmk'.$position} = ${'itmsk'.$position} = '';
				${'itme'.$position} =${'itms'.$position} =0;				
			}
			$mode = 'command';
			return;
		}else{
			$log .= '<span class="red">你没有毒药，无法给武器淬毒！</span><br />';
			$mode = 'command';
			return;
		}
	}else{
		$log .= '<span class="red">你不懂得如何改造陷阱！</span><br />';
		$mode = 'command';
		return;
	}
}

function syncro($sb){
	global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$name,$nick;
	list($n,$k,$e,$s,$sk,$r)=explode('_',$sb);
	$itm0=$n;$itmk0=$k;$itme0=$e;$itms0=$s;$itmsk0=$sk;
	if ($r>0) {addnews($now,'syncmix',$name,$itm0,$nick);}
	else {addnews($now,'overmix',$name,$itm0,$nick);}
			//检查成就
			include_once GAME_ROOT.'./include/game/achievement.func.php';
			check_mixitem_achievement_rev($name,$itm0);
	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget();
	return;
}
function weaponswap(){
	global $log,$mode,$club,$wep,$wepk,$wepe,$weps,$wepsk,$gamecfg;
	if (strpos($wepsk,'j')===false){
		$log.='你的武器不能变换。<br>';
		$mode = 'command';
		return;
		}
	$oldw=$wep;
	$file = config('wepchange',$gamecfg);
	$wlist = openfile($file);
	$wnum = count($wlist)-1;
	for ($i=0;$i<=$wnum;$i++){
		list($on,$nn,$nk,$ne,$ns,$nsk) = explode(',',$wlist[$i]);
		if ($wep==$on){
			$wep=$nn;$wepk=$nk;$wepe=$ne;$weps=$ns;$wepsk=$nsk;
			$log.="<span class=\"yellow\">{$oldw}</span>变换成了<span class=\"yellow\">{$wep}</span>。<br>";
			return;
		}
	}
	$log.="<span class=\"yellow\">{$oldw}</span>由于改造或其他原因不能变换。<br>";
}
function chginf($infpos){
	global $log,$mode,$inf,$inf_sp,$inf_sp_2,$sp,$infinfo,$exdmginf,$club;
	$normalinf = Array('h','b','a','f');
	if(!$infpos){$mode = 'command';return;}
	if($infpos == 'A'){  
		if($club == 16){
			$spdown = 0;
			foreach($normalinf as $value){
				if(strpos($inf,$value)!== false){
					$spdown += $inf_sp;
				}
			}
			if(!$spdown){
				$log .= '你并没有受伤！';
				$mode = 'command';
				return;
			}elseif($sp <= $spdown){
				$log .= "包扎全部伤口需要{$spdown}点体力，先回复体力吧！";
				$mode = 'command';
				return;
			}
			$inf = str_replace('h','',$inf);
			$inf = str_replace('b','',$inf);
			$inf = str_replace('a','',$inf);
			$inf = str_replace('f','',$inf);
			$sp -= $spdown;
			$log .= "消耗<span class=\"yellow\">$spdown</span>点体力，全身伤口都包扎好了！";
			$mode = 'command';
			return;
		}else{
			$log .= '你不懂得怎样快速包扎伤口！';
			$mode = 'command';
			return;
		}
	}elseif(in_array($infpos,$normalinf) && strpos($inf,$infpos) !== false){	//普通伤口
		if($sp <= $inf_sp) {
			$log .= "包扎伤口需要{$inf_sp}点体力，先回复体力吧！";
			$mode = 'command';
			return;
		} else {
			$inf = str_replace($infpos,'',$inf);
			$sp -= $inf_sp;
			$log .= "消耗<span class=\"yellow\">$inf_sp</span>点体力，{$infinfo[$infpos]}<span class=\"red\">部</span>的伤口已经包扎好了！";
			$mode = 'command';
			return;
		}
	}elseif(strpos($inf,$infpos) !== false){  //特殊状态
		if($club == 16){
			if($sp <= $inf_sp_2) {
				$log .= "处理异常状态需要{$inf_sp_2}点体力，先回复体力吧！";
				$mode = 'command';
				return;
			} else {
				$inf = str_replace($infpos,'',$inf);
				$sp -= $inf_sp_2;
				$log .= "消耗<span class=\"yellow\">$inf_sp_2</span>点体力，{$exdmginf[$infpos]}状态已经完全治愈了！";
				$mode = 'command';
				return;
			}
		}else{
			$log .= '你不懂得怎样治疗异常状态！';
			$mode = 'command';
			return;
		}
	}else{
		$log .= '你不需要包扎这个伤口！';
		$mode = 'command';
		return;
	}
}

function chkpoison($itmn){
	global $log,$mode,$club;
	if($club != 8){
		$log .= '你不会查毒。';
		$mode = 'command';
		return;
	}

	if ( $itmn < 1 || $itmn > 6 ) {
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

	if(!$itms) {
		$log .= '此道具不存在，请重新选择。<br>';
		$mode = 'command';
		return;
	}
	
	if(strpos($itmk,'P') === 0) {
		$log .= '<span class="red">'.$itm.'有毒！</span>';
	} else {
		$log .= '<span class="yellow">'.$itm.'是安全的。</span>';
	}
	$mode = 'command';
	return;
}

function press_bomb(){
	global $log,$mode,$club,$wp,$wk,$wg,$wc,$wd,$wf,$mhp,$hp,$msp,$sp,$att,$def,$rage,$lvl;
	if($club != 99){
		$log .= '你的称号不能使用该技能。';
		$mode = 'command';
		return;
	}

	$club=17;
	$wp=ceil($wp*1.2); $wk=ceil($wk*1.2); $wg=ceil($wg*1.2); $wc=ceil($wc*1.2); $wd=ceil($wd*1.2); $wf=ceil($wf*1.2);
	$mhp=ceil($mhp*1.15); $hp=ceil($hp*1.15); $msp=ceil($msp*1.15); $sp=ceil($sp*1.15); 
	$att=ceil($att*1.2); $def=ceil($def*1.2); $rage+=$lvl*10; 
	$log.="你按下了X按钮，你突然感觉到一股力量贯通全身！"; 
	$mode = 'command';
	return;
}

//提取代码片段逻辑
function item_extract_trait($which, $item_position)
{
    include_once GAME_ROOT . './gamedata/club21cfg.php';
    //去掉string which的最后一位
    $which = substr($which, 0, -1);

    global $log, $mode, $club, $sp;
    if ($club != 21) {
        $log .= '你的称号不能使用该技能。';
        $mode = 'command';
        return;
    }

    if ($item_position < 1 || $item_position > 6) {
        $log .= '此道具不存在，请重新选择。';
        $mode = 'command';
        return;
    }
    global ${'itm' . $item_position}, ${'itmk' . $item_position}, ${'itme' . $item_position}, ${'itms' . $item_position}, ${'itmsk' . $item_position};

    $oriitm = &${'itm' . $item_position};
	$itm = &${'itm' . $item_position};
    $itmk = &${'itmk' . $item_position};
    $itme = &${'itme' . $item_position};
    $itms = &${'itms' . $item_position};
    $itmsk = &${'itmsk' . $item_position};

    $tmp_trait = ${$which . $item_position};

    // 判断itmk是否以'D'或'W'开头
    if (strpos($itmk, 'D') === 0 || strpos($itmk, 'W') === 0) {
        // 给代码片段命名
        if ($which == 'itm') {            
            preg_match_all('/(改|棍棒|\+(\\d+))/u', $itm, $matches);
            if (!empty($matches[0])) {
                $sp_cost = 0;
                foreach ($matches[0] as $match) {
                    if ($match == '改') {
                        $sp_cost += $itm_extract_rate['改'] * 1;
                    } elseif ($match == '棍棒') {
                        $sp_cost += $itm_extract_rate['棍棒'] * 1;
                    } else {
                        preg_match('/\+(\d+)/', $match, $numberMatch);
                        $number = isset($numberMatch[1]) ? (int) $numberMatch[1] : 0;
                        $sp_cost += $itm_extract_rate['+'] + $itm_extract_rate['n'] * $number;
                    }
                }
                if ($sp < $sp_cost) {
                    $log .= '体力不足，无法转换为代码片段。<br>';
                    return;
                }
                $sp -= $sp_cost;
                $log .= '消耗体力' . $sp_cost . '点。<br>';

                $itm = implode('', $matches[0]);
                $itm = (string)$itm;
                
                $itm = "🥚" . $itm . '🥚的代码片段';
                $itmk = '🥚';
                $itme = '0';
                $itms = '1';
                $itmsk = '';
                return;
            }
            else {
                $log .= '该物品无法转换为代码片段。<br>';
                return;
            }
        } elseif ($which == 'itme') {
            if ($sp < $itme_extract_rate * $itme) {
                $log .= '体力不足，无法转换为代码片段。<br>';
                return;
            }
            //$itm = "效果" . ${$which . $item_position} . '代码片段';
			$itm = '🥚' . $oriitm . '🥚的效果代码片段';
            $log .= '消耗体力' . $itme_extract_rate * $itme . '点。<br>';
            $sp -= $itme_extract_rate * $itme;
        } elseif ($which == 'itms') {
            //如果itms为∞
            if ($itms == '∞') {
                if ($sp < $itms_infinite_extract_rate* 1) {
                    $log .= '体力不足，无法转换为代码片段。<br>';
                    return;
                }
                $itm = '🥚' . $oriitm . '🥚的耐久代码片段';
                $log .= '消耗体力' . $itms_infinite_extract_rate* 1 . '点。<br>';
                $sp -= $itms_infinite_extract_rate* 1;
                $itmk = '';
                $itme = '0';
                $itms = '∞';
                $itmsk = '';
                ${$which . $item_position} = $tmp_trait;
                // 将itmk替换为代码片段的itmk
                $itmk = '🥚';
                $log .= '成功将物品转换为代码片段。<br>';
                return;
            }
            if ($sp < $itms_extract_rate * $itms) {
                $log .= '体力不足，无法转换为代码片段。<br>';
                return;
            }
            //$itm = "耐久" . ${$which . $item_position} . '代码片段';
			$itm = '🥚' . $oriitm . '🥚的耐久代码片段';
            $log .= '消耗体力' . $itms_extract_rate * $itms . '点。<br>';
            $sp -= $itms_extract_rate * $itms;
        } elseif ($which == 'itmsk') {
            preg_match_all('/./u', $itmsk, $matches);
            var_dump($matches);
            //如果matches没有
            if (empty($matches[0])) {
                $log .= '该物品无法转换为代码片段。<br>';
                return;
            }
            foreach ($matches[0] as $single_itmsk) {
                if (isset($itmsk_extract_rate[$single_itmsk])) {
                    $sum += 1 * $itmsk_extract_rate[$single_itmsk];
                }
            }
            if ($sp < $sum) {
                $log .= '体力不足，无法转换为代码片段。<br>';
                return;
            }
            //$itm = "属性" . ${$which . $item_position} . '代码片段';
			$itm = '🥚' . $oriitm . '🥚的属性代码片段';
            $log .= '消耗体力' . $sum . '点。<br>';
            $sp -= $sum;
        }
        $itmk = '';
        $itme = '0';
        $itms = '0';
        $itmsk = '';
        ${$which . $item_position} = $tmp_trait;
        $itms += 1;
        // 将itmk替换为代码片段的itmk
        $itmk = '🥚';
        $log .= '成功将物品转换为代码片段。<br>';
    } else {
        $log .= '该物品无法转换为代码片段。<br>';
    }
    return;
}

//合并代码片段逻辑
function  item_add_trait($choice1, $choice2)
{
    var_dump($choice1, $choice2);
    global $log, $mode, $club, $sp, $rage, $pdata;
    if ($club != 21) {
        $log .= '你的称号不能使用该技能。';
        $mode = 'command';
        return;
    }
    //获取choice1和choice2的itm itmk itme itms itmsk
    global ${'itm' . $choice1}, ${'itmk' . $choice1}, ${'itme' . $choice1}, ${'itms' . $choice1}, ${'itmsk' . $choice1};
    global ${'itm' . $choice2}, ${'itmk' . $choice2}, ${'itme' . $choice2}, ${'itms' . $choice2}, ${'itmsk' . $choice2};
    $itmc1 = &${'itm' . $choice1};
    $itmkc1 = &${'itmk' . $choice1};
    $itmec1 = &${'itme' . $choice1};
    $itmsc1 = &${'itms' . $choice1};
    $itmskc1 = &${'itmsk' . $choice1};
    $itmc2 = &${'itm' . $choice2};
    $itmkc2 = &${'itmk' . $choice2};
    $itmec2 = &${'itme' . $choice2};
    $itmsc2 = &${'itms' . $choice2};
    $itmskc2 = &${'itmsk' . $choice2};
    var_dump($itmc1, $itmc2);
    //检查itmk1是否为🥚,itmk2是否为D或W开头或者是否为🥚
    if ($itmkc1 != '🥚' || (strpos($itmkc2, 'D') !== 0 && strpos($itmkc2, 'W') !== 0 && ($itmkc2 !== '🥚'))) {
        $log .= '该物品无法合并。<br>';
        return;
    }
    //让itm2属性合并itm1
    //如果都是🥚，则去掉$itm的所有“代码片段”四个字，然后itm相加
    if ($itmkc1 == '🥚' && $itmkc2 == '🥚') {
        var_dump($itmkc1, $itmkc2);
        var_dump($itmc1, $itmc2);
        preg_match_all('/(改|棍棒|\+(\\d+))/u', $itmc1, $matches1);
        preg_match_all('/(改|棍棒|\+(\\d+))/u', $itmc2, $matches2);
        if (!empty($matches1[0]) || !empty($matches2[0])) {
            $itmn_result = '';
            foreach ($matches1[0] as $match) {
                $itmn_result .= $match;
            }
            foreach ($matches2[0] as $match) {
                $itmn_result .= $match;
            }
            $itmc2 = '🥚' . $itmn_result . '🥚复合代码片段';
        }
        else {
            $itmc2 = '🥚复合代码片段🥚';
        }   
        $itmkc2 = $itmkc1 . $itmkc2;
        $itmec2 = (int)$itmec1 + (int)$itmec2;
        //当任意一个itms为∞
        if ($itmsc1 == '∞' || $itmsc2 == '∞') {
            $itms2 = '∞';
        }
        else {
            $itms2 = (int)$itmsc1 + (int)$itmsc2 - 1;
        }
        $itmskc2 = $itmskc1 . $itmskc2;
        $itmkc2 = '🥚';
        //清空itm1
        destory_single_item($pdata, $choice1);
        return;
    }
    elseif ($rage < 50 ) {
        $log .= '怒气不足，无法合并代码片段。<br>';
        return;
    }
    $rage -= 50;
    //如果itm1含有棍棒或者改或者n
    preg_match_all('/(改|棍棒|\+(\\d+))/u', $itmc1, $matches);
    if (!empty($matches[0])) 
    {
        $itmn_result = '';
        //合并match
        foreach ($matches[0] as $match) {
            $itmn_result .= $match;
        }
        $itmc2 = $itmc2 . $itmn_result;
        $itmkc2 = $itmkc1 . $itmkc2;
        $itmec2 = (int)$itmec1 + (int)$itmec2;
        //当任意一个itms为∞
        if ($itmsc1 == '∞' || $itmsc2 == '∞') {
            $itmsc2 = '∞';
        }
        else {
            $itmsc2 = (int)$itmsc1 + (int)$itmsc2 - 1;
        }
        $itmskc2 = $itmskc1 . $itmskc2;
        //清空itm1
        destory_single_item($pdata, $choice1);

        $itmkc2 = str_replace('🥚', '', $itmkc2);
        return;
    }
    $itmkc2 = $itmkc1 . $itmkc2;
    $itmec2 = (int)$itmec1 + (int)$itmec2;
    //当任意一个itms为∞
    if ($itmsc1 == '∞' || $itmsc2 == '∞') {
        $itmsc2 = '∞';
    }
    else {
        $itmsc2 = (int)$itmsc1 + (int)$itmsc2 - 1;
    }
    $itmskc2 = $itmskc1 . $itmskc2;
    //清空itm1
    destory_single_item($pdata, $choice1);
    //去除itm2重复的属性
    $itmskc2 = implode(array_unique(str_split($itmskc2)));
    //去除itm2属性里的🥚
    $itmkc2 = str_replace('🥚', '', $itmkc2);
}

function shoplist($sn,$getlist=NULL) {
	global $gamecfg,$mode,$itemdata,$areanum,$areaadd,$iteminfo,$itemspkinfo,$club;
	global $db,$tablepre;
	$arean = floor($areanum / $areaadd); 
	$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE kind = '$sn' AND area <= '$arean' AND num > '0' AND price > '0' ORDER BY sid");
	$shopnum = $db->num_rows($result);
	$itemdata = Array();
	for($i=0;$i< $shopnum;$i++){
		$itemlist = $db->fetch_array($result);
		$itemdata[$i]['sid']=$itemlist['sid'];
		$itemdata[$i]['kind']=$itemlist['kind'];
		$itemdata[$i]['num']=$itemlist['num'];
		$itemdata[$i]['price']= $club == 11 ? round($itemlist['price']*0.75) : $itemlist['price'];
		$itemdata[$i]['area']=$itemlist['area'];
		$itemdata[$i]['item']=$itemlist['item'];
		$itemdata[$i]['item_words']= parse_info_desc($itemdata[$i]['item'],'m');
		$itemdata[$i]['itme']=$itemlist['itme'];
		$itemdata[$i]['itms']=$itemlist['itms'];
		//list($sid,$kind,$num,$price,$area,$item,$itmk,$itme,$itms,$itmsk)=explode(',',$itemlist);
		foreach($iteminfo as $info_key => $info_value){
			if(strpos($itemlist['itmk'],$info_key)===0){
				if(isset($getlist)) $itemdata[$i]['itmk'] = $info_value;
				//$itemdata[$i]['itmk_words'] = parse_itm_desc($info_key,'k');
				break;
			}
		}
		$itemdata[$i]['itmk_words'] = parse_info_desc($itemlist['itmk'],'k');
		$itemdata[$i]['itmsk_words'] = '';
		if($itemlist['itmsk'] && ! is_numeric($itemlist['itmsk'])){
			if(!isset($getlist))
			{
				$itemdata[$i]['itmsk_words'] = parse_info_desc($itemlist['itmsk'],'sk',$itemlist['itmk']);
				//$tmp_sk = get_itmsk_array($itemlist['itmsk']);
				//foreach($tmp_sk as $sk) $itemdata[$i]['itmsk_words'].= parse_itm_desc($sk,'sk');
			}
			else 
			{
				for ($j = 0; $j < strlen($itemlist['itmsk']); $j++) {
					$sub = substr($itemlist['itmsk'],$j,1);
					if(!empty($sub) && isset($itemspkinfo[$sub])){
						$itemdata[$i]['itmsk_words'] .= $itemspkinfo[$sub];
					}
				}
			}
		}
		//$itemdata[$i] = array('sid' => $sid, 'kind' => $kind,'num' => $num, 'price' => $price, 'area' => $area, 'item' => $item,'itmk_words' => $itmk_words,'itme' => $itme, 'itms' => $itms,'itmsk_words' => $itmsk_words);
	}

	if(isset($getlist)) return $itemdata;
	
	$mode = 'shop';

	return;

}
