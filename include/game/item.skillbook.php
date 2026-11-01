<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle skill book items
function item_skillbook($itmn, &$data) {
	global $log, $nosta, $cskills, $now;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$skill_minimum = 100;
	$skill_limit = 380;
	$log .= "你阅读了<span class=\"red\">$itm</span>。<br>";
	$dice = rand(-10, 10);
	if (strpos($itmk, 'VV') === 0) {
		//global $wp, $wk, $wg, $wc, $wd, $wf;
		$ws_sum = $wp + $wk + $wg + $wc + $wd + $wf;
		if ($ws_sum < $skill_minimum * 5) {
			$vefct = $itme;
		} elseif ($ws_sum < $skill_limit * 5) {
			$vefct = round($itme * (1 - ($ws_sum - $skill_minimum * 5) / ($skill_limit * 5 - $skill_minimum * 5)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wp += $vefct; //$itme;
		$wk += $vefct; //$itme;
		$wg += $vefct; //$itme;
		$wc += $vefct; //$itme;
		$wd += $vefct; //$itme; 
		$wf += $vefct; //$itme;
		$wsname = "全系熟练度";
	} elseif (strpos($itmk, 'VP') === 0) {
		//global $wp;
		if ($wp < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wp < $skill_limit) {
			$vefct = round($itme * (1 - ($wp - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wp += $vefct; //$itme;
		$wsname = "斗殴熟练度";
	} elseif (strpos($itmk, 'VK') === 0) {
		//global $wk;
		if ($wk < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wk < $skill_limit) {
			$vefct = round($itme * (1 - ($wk - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wk += $vefct; //$itme; 
		$wsname = "斩刺熟练度";
	} elseif (strpos($itmk, 'VG') === 0) {
		//global $wg;
		if ($wg < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wg < $skill_limit) {
			$vefct = round($itme * (1 - ($wg - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wg += $vefct; //$itme; 
		$wsname = "射击熟练度";
	} elseif (strpos($itmk, 'VC') === 0) {
		//global $wc;
		if ($wc < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wc < $skill_limit) {
			$vefct = round($itme * (1 - ($wc - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wc += $vefct; //$itme; 
		$wsname = "投掷熟练度";
	} elseif (strpos($itmk, 'VD') === 0) {
		//global $wd;
		if ($wd < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wd < $skill_limit) {
			$vefct = round($itme * (1 - ($wd - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wd += $vefct; //$itme; 
		$wsname = "引爆熟练度";
	} elseif (strpos($itmk, 'VF') === 0) {
		//global $wf;
		if ($wf < $skill_minimum) {
			$vefct = $itme;
		} elseif ($wf < $skill_limit) {
			$vefct = round($itme * (1 - ($wf - $skill_minimum) / ($skill_limit - $skill_minimum)));
		} else {
			$vefct = 0;
		}
		if ($vefct < 10) {
			if ($vefct < $dice) {
				$vefct = -$dice;
			}
		}
		$wf += $vefct; //$itme; 
		$wsname = "灵击熟练度";
	} elseif (strpos($itmk, 'VS') === 0) {
		//global $cskills,$clbpara;
		$bookpara = get_clbpara($data['itmpara'.$itmn] ?? '');
		$bookskill = $bookpara['wheelchair_skill'] ?? $itmsk;
		if(isset($bookpara['wheelchair_skill']) && (!is_string($bookskill) || !isset($GLOBALS['wheelchair_skills'][$bookskill]) || !wheelchair_supported())) {
			$log .= '当前规则不支持这本辅助技能书，书已保留。<br>';
			return;
		}
		if(!empty($bookskill) && isset($cskills[$bookskill]))
		{

			$flag = getclubskill($bookskill,$clbpara);
			if($flag)
			{
				$log.="哇！没想到这本书里竟然介绍了<span class='yellow'>「{$cskills[$bookskill]['name']}」</span>的原理！<br>获得了技能<span class='yellow'>「{$cskills[$bookskill]['name']}」</span>！<br>你心满意足地把<span class='red'>{$itm}</span>吃进了肚里。<br>";
				addnews($now,'getsk_'.$bookskill,$name,$itm,$nick);
				if(isset($bookpara['wheelchair_skill'])) wheelchair_log($cskills[$bookskill]['name'],$bookskill==='wc_review' ? '复盘不产生惩罚。' : '从现在起，本局此后切糕收益为 1/'.(1<<wheelchair_penalty_count($data)).'，单笔向下取整。');
			}
			else 
			{
				$log.="什么嘛！原来里面都是些你看过的东西了，你没有从书中学到任何新东西。<br>你一怒之下把这本破书撕了个稀巴烂！<br>";
			}
		}
		else 
		{
			$log.="但是你横看竖看，也弄不明白作者到底想表达什么！<br>你一怒之下把这本破书撕了个稀巴烂！<br>";
		}
	}
	if(isset($vefct))
	{
		if ($vefct > 0) {
			$log .= "嗯，有所收获。<br>你的{$wsname}提高了<span class=\"yellow\">$vefct</span>点！<br>";
		} elseif ($vefct == 0) {
			$log .= "对你来说书里的内容过于简单了。<br>你的熟练度没有任何提升。<br>";
		} else {
			$vefct = -$vefct;
			$log .= "对你来说书里的内容过于简单了。<br>而且由于盲目相信书上的知识，你反而被编写者的纰漏所误导了！<br>你的{$wsname}下降了<span class=\"red\">$vefct</span>点！<br>";
		}
	}
	if ($itms != $nosta) {
		$itms --;
		if ($itms <= 0) {
			$log .= "<span class=\"red\">$itm</span>用光了。<br>";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	}
}
