<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

include_once GAME_ROOT.'./include/game/itemmain.func.php';

$mix_type = Array('normal' => '通常','sync' => '同调', 'overlay' => '超量');

// 合成功能
function itemmix_rev($mlist, $itemselect=-1, &$data=NULL) 
{
	global $log,$mode,$cmd,$main,$itemcmd;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	# 合成队列合法性检查
	if(!itemmix_place_check($mlist,$data)) return;
	# 合成结果检查
	$mix_res = itemmix_get_result($mlist,$data);
	# 尝试进行合成操作时 合成操作计数+1
	if(empty($clbpara['achvars']['immix'])) $clbpara['achvars']['immix'] = 1;
	
	$mixitemname = array();
	foreach($mlist as $val) $mixitemname[] = ${'itm'.$val};
	$itmstr = implode(' ', $mixitemname);
	$mixmask = calc_mixmask($mlist);
	//没有合成选项
	if(!$mix_res) {
		$log .= "<span class=\"yellow\">{$itmstr}</span>不能合成！<br>";
		$mode = 'itemmix'; $itemcmd = 'itemmix';
	} elseif(count($mix_res) > 1) {//合成选项2个以上
		if($itemselect >= 0) {//有选择则合成
			itemmix_proc($mlist, $mix_res[$itemselect], $itmstr, $data);
		}else{//否则显示合成选项
			$cmd.=itemmix_option_show($mix_res,$mixmask);
		}
	} else {//只有1个合成选项则直接合成
		itemmix_proc($mlist, $mix_res[0], $itmstr, $data);
		// 晶莹剔透合成成功时 -30rp
		if($club == 19)
		{
			$rpup = -30;
			include_once GAME_ROOT.'./include/state.func.php';
			rpup_rev($data,$rpup);
		}
	}
	return;
}
function itemmix_get_result($mlist,&$data=NULL)
{
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$mixitem = array();
	foreach($mlist as $val){
		$mixitem[$val] = array(
			'itm' => ${'itm'.$val},
			'itmk' => ${'itmk'.$val},
			'itme' => ${'itme'.$val},
			'itms' => ${'itms'.$val},
			'itmsk' => ${'itmsk'.$val},
		);
	}
    //常规合成
    $mixresult = itemmix_recipe_check($mixitem);
    //同调合成
    $chc_res = itemmix_sync_check($mlist);
    if($chc_res){
        foreach($chc_res as $cv) {
            foreach($cv as $v){
                $mixresult[] = $v;
            }
        }
    }
    //超量合成
   $chc_res = itemmix_overlay_check($mlist);
    if($chc_res){
        foreach($chc_res as $cv) {
            foreach($cv as $v){
                $mixresult[] = $v;
            }
        }
    }
	return $mixresult;
}
//用户界面暂存的合成素材列表
function calc_mixmask($mlist)
{
    $mask=0;
        foreach($mlist as $k)
            if ($k>=1 && $k<=6)
                $mask|=(1<<((int)$k-1));
    return $mask;
}
function itemmix_option_show($mix_res,$mixmask)
{
    global $mix_type;
    ob_start();
    include template('itemmix_result');
    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}
function itemmix_place_check($mlist,&$data=NULL)
{
	global $mode,$log,$main,$itemcmd;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	if($club == 20){
		$log .= "<span class=\"yellow\">无法使用合成功能！</span><br>";
		$mode = 'command'; $main = '';
		return false;
	}
	$main = 'itemmix_tips';
	$mlist2 = array_unique($mlist);	
	if(count($mlist) != count($mlist2)) {
		$log .= '相同道具不能进行合成！<br>';
		$mode = 'itemmix'; 	$itemcmd = 'itemmix';
		return false;
	}
	if(count($mlist) < 2){
		$log .= '至少需要2个道具才能进行合成！';
		$mode = 'itemmix'; 	$itemcmd = 'itemmix';
		return false;
	}
	foreach($mlist as $val){
		if(!$data['itm'.$val]){
			$log .= '所选择的道具'.$val.'不存在！';
			$mode = 'itemmix'; 	$itemcmd = 'itemmix';
			return false;
		}
	}
	return true;
}
//查看哪些合成公式符合要求
//$mi已改为道具数组
function itemmix_recipe_check($mixitem)
{
	$mixinfo = get_mixinfo();
	$res = array();
	if(count($mixitem) >= 2){
		$mi_names = array();
		foreach($mixitem as $i) $mi_names[] = itemmix_name_proc($i['itm']);
		sort($mi_names);
		foreach($mixinfo as $minfo){
			$ms = $minfo['stuff'];
			sort($ms);
			if(count($mi_names)==count($ms) && $mi_names == $ms) {
				$minfo['type'] = 'normal';
				$res[] = $minfo;
			}
		}
	}
	return $res;	
}

//查看是否符合同调要求
function itemmix_sync_check($mlist)
{
    if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
    //判断是否存在调整，并计算总星数
    $star = $star2 = 0;
    $tunner = $stuff = array();
    foreach($mlist as $mval){
        $stuff[] = ${'itm'.$mval};
        list($mstar, $mtunner) = itemmix_star_culc_sync($mval);
        if($mstar == 0){
            $star = 0;
            break;
        }else{
            $star += $mstar;
        }
        if($mtunner){
            $tunner[] = $mval;
        }
        /*if(in_array('^002',get_itmsk_array(${'itmsk'.$mval})))//生命肌瘤龙变星效果
        {
            $streamstar = $mstar;
        }*/
    }
    $chc_res = array();
    if($star && count($tunner) == 1){
        if(!empty($streamstar)){//生命肌瘤龙的变星实际上提供两个星数分支
            $star2 = $streamstar + count($mlist) - 1;
        }
        //然后判断是否存在对应的同调成果
        $prp_res = get_syncmixinfo();
        foreach($prp_res as $pra){
            $pstar = $pra[5];
            $preq = $pra[6];
            $preqflag = true;
            if($preq){//检查是不是有特殊需求
                $req=explode('+',$preq);
                $mname = array();
                foreach($mlist as $mi){
                    $mname[] = itemmix_name_proc(${'itm'.$mi});
                }
                //如果素材没有满足则认为无法合成
                foreach($req as $rv){
                    if('st'==$rv){//调整要求是同调
                        $tunnersk = ${'itmsk'.$tunner[0]};
                        if(!in_array('s',get_itmsk_array($tunnersk))) $preqflag = false;
                    }elseif(strpos($rv,'sm')===0){//调整以外要求是同调
                        $smnum = (int)substr($rv,2);
                        foreach($mlist as $mi){
                            if(!in_array($mi, $tunner)){
                                $misk = ${'itmsk'.$mi};
                                if(!in_array('s',get_itmsk_array($misk))) {
                                    $preqflag = false;
                                    break;
                                }
                            }
                        }
                        if(count($mlist) <= $smnum) $preqflag = false;//素材数目不足
                    }else{//其他，认为是名字要求
                        if(!in_array($rv, $mname)) $preqflag = false;
                    }
                }
            }
            if(($pstar == $star || $pstar == $star2) && $preqflag){
                if($pstar == $star2) list($star, $star2) = array($star2, $star);
                if(empty($chc_res[$star])) $chc_res[$star] = array();
                //用键名记录星数和素材数方便提示
                $chc_res[$star][] = array('stuff' => $stuff, 'list' => $mlist, 'result' => $pra, 'type' => 'sync');
            }
        }
    }
    return $chc_res;
}
function itemmix_star_culc_sync($itmn)
{
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
    $star=0;
    $tunner=false;
    if(${'itms'.$itmn}){
        $star = itemmix_get_star(${'itmk'.$itmn});
        if(strpos(${'itmsk'.$itmn},'s')!==false) $tunner = true;
    }
    return array($star, $tunner);
}
function itemmix_get_star($z){
    $star = 0;
    for ($i=0; $i<strlen($z); $i++)
        if ('0'<=$z[$i] && $z[$i]<='9')
            $star=$star*10+(int)$z[$i];
    return $star;
}
function itemmix_overlay_check($mlist)
{
    if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
    //先判断是否是同星素材2张以上
    $star = $num = 0;
    $stuff = array();
    foreach($mlist as $mval){
        $stuff[] = ${'itm'.$mval};
        $mstar = itemmix_star_culc_overlay($mval);
        if(($star && $mstar != $star) || $mstar == 0){
            $star = 0;break;
        }else{
            $star = $mstar;
            $num ++;
        }
    }
    $chc_res = array();
    if($star && $num > 1){
        //然后判断是否存在对应的超量成果
        $prp_res = get_overlaymixinfo();
        foreach($prp_res as $pra){
            $pstar = $pra[5];
            $pnum = $pra[6];
            if($star == $pstar && $num == $pnum){
                if(empty($chc_res[$star.'-'.$num])) $chc_res[$star.'-'.$num] = array();
                //用键名记录星数和素材数方便提示
                $chc_res[$star.'-'.$num][] = array('stuff' => $stuff, 'list' => $mlist, 'result' => $pra, 'type' => 'overlay');
            }
        }
    }
    return $chc_res;
}
function itemmix_star_culc_overlay($itmn)
{
    if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
    $star=0;
    if(${'itms'.$itmn}){
        $star = itemmix_get_star(${'itmk'.$itmn});
        if(!check_valid_overlay_material(${'itm'.$itmn}, ${'itmsk'.$itmn}, $star)){
            $star = 0;
        }
    }
    return $star;
}
//有效的超量素材：带有“超量素材”属性，或者是真卡（名称里有★数字，数字与星数一致，并且没有“-仮”字样）
function check_valid_overlay_material($itm, $itmsk, $star)
{
    if(strpos($itmsk,'J')!==false) return true;
    preg_match('/★(\d+)/s', $itm, $matches);
    //gwrite_var('a.txt',$matches);
    if(!empty($matches) && $star == $matches[1] && strpos($itm,'-仮')===false) return true;
    return false;
}
function itemmix_name_proc($n){
	$n = trim($n);
	$itmname_ignore = Array('/锋利的/si','/电气/si','/毒性/si','/-改/si');
	foreach(Array($itmname_ignore) as $value){
		$n = preg_replace($value,'',$n);
	}
	if(strpos($n, '小黄的')!==false) $n = preg_replace('/\[\+[0-9]+?\]/si','',$n);//小黄强化特判可以合成
	$n = str_replace('钉棍棒','棍棒',$n);
	return $n;
}
//执行合成
function itemmix_proc($mlist, $minfo, $itmstr, &$data=NULL)
{
	global $log,$main,$now,$mix_type;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	foreach($mlist as $val){
		itemreduce('itm'.$val);
	}
	$itm0 = $minfo['result'][0];
	$itmk0 = $minfo['result'][1];
	$itme0 = $minfo['result'][2];
	$itms0 = $minfo['result'][3];
	if (isset($minfo['result'][4]))
		$itmsk0 = $minfo['result'][4];
	else{
		$itmsk0 = '';
	}
	$uip['mixcls'] = !empty($minfo['class']) ? $minfo['class'] : '';
	$uip['mixtp'] = $minfo['type'];
	//合成成功
	$main = '';
	//“通常”合成当动词实在是太奇怪了
	$tpstr = $mix_type[$uip['mixtp']] == '通常' ? '' : $mix_type[$uip['mixtp']];

	$log .= "<span class=\"yellow\">$itmstr</span>{$tpstr}合成了<span class=\"yellow\">{$itm0}</span>。<br>";
	addnews($now,'itemmix',$name,$itm0,$tpstr,$nick);

	//执行合成合成成功时会触发的额外事件
	itemmix_events($data);

	//检查成就
	include_once GAME_ROOT.'./include/game/achievement.func.php';
	check_mixitem_achievement_rev($name,$itm0);

	itemget($data);
}

//合成成功时会触发的额外事件
function itemmix_events(&$data=NULL)
{
	global $log,$gamevars;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	# 合成成功时爆熟+1
	$wd+=1;

	# 全能兄贵在合成补给品时，获得数量x2
	if((strpos($itmk0,'H') === 0)&&($club == 16)&&($itms0 !== $nosta)){ $itms0 = ceil($itms0*2); }

	# 锡安合成电子仪器时，耐久x2
	if(($itmk0 == 'EE' || $itmk0 == 'ER') && ($club == 7)){ $itme0 *= 5; }

	# 合成皇家蔷薇时，获得进一步合成的线索
	if($itm0 == '「皇家蔷薇」')
	{
		if(empty($gamevars['random_mixlist']['royal_rose']))
		{
			# 可能出现的随机素材列表
			$slip_list = Array('红石榴汁','红色的发圈','粉红雏菊','红豆面包',
				'☆红楼梦精装本☆','红色方块','红宝石方块','院长红酒','冴月麟的生日蛋糕-红',
				'鲜红的生血','真-红色的发圈','『红石电路』','【烈焰红唇】','红宝石方块','红莲魔龙 ★8');
			$royal_rose_stuff = $slip_list[array_rand($slip_list)];
			$royal_rose = Array(
				'class' => 'hidden', 
				'stuff' => array('「皇家蔷薇」','龙虎旗帜',$royal_rose_stuff),
				'result' => array('「猩红蔷薇」','WK',179310,'∞','BNnrfcV'),
			);
			$gamevars['random_mixlist']['royal_rose'] = $royal_rose;
			save_gameinfo();
		}
		else 
		{
			$royal_rose = $gamevars['random_mixlist']['royal_rose'];
			$royal_rose_stuff = $royal_rose['stuff'][2];
		}
		# 混淆
		$royal_rose_stuff = preg_replace('/[^红]/u', '＊', $royal_rose_stuff);
		# 获得提示
		$log .= "然后，你收到了来自某人的私聊——<br>
		<br>
		<span class='redseed'>“……嗯嗯嗯嗯，你在搜集这个东西啊……<br>
		如果你还打算进一步合成的话，<br>
		接下来就得去找‘{$royal_rose_stuff}’了。<br>
		……你问‘＊’是什么……？<br>
		‘＊’就是连在一起被和谐了，打不出来……你也是在网上冲浪的，应该能明白吧！<br>
		嘛，总之你先对着字数找找吧！”</span><br>
		<br>
		啊……？<br>";
	}

	return;
}

?>