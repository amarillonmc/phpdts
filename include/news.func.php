<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}


function  nparse_news($start = 0, $range = 0  ){//$type = '') {
	global $week,$nowep,$db,$tablepre,$lwinfo,$plsinfo,$wthinfo,$typeinfo,$exdmginf,$newslimit;
	//$file = $file ? $file : $newsfile;	
	//$ninfo = openfile($file);
	$range = $range == 0 ? $newslimit : $range ;
	$result = $db->query("SELECT * FROM {$tablepre}newsinfo ORDER BY nid DESC LIMIT $start,$range");
	//$r = sizeof($ninfo) - 1;
//	$rnum=$db->num_rows($result);
//	if($range && ($range <= $rnum)) {
//		$nnum = $range;
//	} else{
//		$nnum = $rnum;
//	}
	$newsinfo = '<ul>';
	$nday = 0;
	//for($i = $start;$i <= $r;$i++) {
	//for($i = 0;$i < $nnum;$i++) {
	while($news0=$db->fetch_array($result)) {
		//$news0=$db->fetch_array($result);
		$time=$news0['time'];$news=$news0['news'];$a=$news0['a'];$b=$news0['b'];$c=$news0['c'];$d=$news0['d'];$e=$news0['e'];
		list($sec,$min,$hour,$day,$month,$year,$wday) = explode(',',date("s,i,H,j,n,Y,w",$time));
		if($day != $nday) {
			$newsinfo .= "<span class=\"evergreen\"><B>{$month}/{$day}($week[$wday])</B></span><br>";
			$nday = $day;
		}

		if($news == 'newgame') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">ROUND {$a} Hunger Game has Started!</span><br>\n";
		} elseif($news == 'gameover') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">ROUND {$a} Hunger Game has Ended!</span><br>\n";
		} elseif($news == 'newpc') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}({$b}) Entered the battlefield!</span><br>\n";
		} elseif($news == 'newgm') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">Admin-{$a}({$b}) Entered the Battlefield!</span><br>\n";
		} elseif($news == 'teammake') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$b} Created Team {$a}</span><br>\n";
		} elseif($news == 'teamjoin') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$b} Joined Team {$a}</span><br>\n";
		} elseif($news == 'teamquit') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$b} Exited Team {$a}</span><br>\n";
		} elseif($news == 'senditem') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} has gifted <span class=\"yellow\">$c</span> to {$b}</span><br>\n";
		} elseif($news == 'addarea') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，Add Restricted Areas：";
			$alist = explode('_',$a);
			foreach($alist as $ar) {
				$newsinfo .= "$plsinfo[$ar] ";
			}
			$newsinfo .= "<span class=\"yellow\">【天气：{$wthinfo[$b]}】</span><br>\n";
		} elseif($news == 'hack') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}Commerenced Override on Restricted Zones.</span><br>\n";
		} elseif($news == 'hack2') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}启动了救济程序，全部禁区解除！</span><br>\n";
		} elseif($news == 'combo') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> Game Lock Down!</span><br>\n";
		} elseif($news == 'comboupdate') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\"> Game Lock Down Death counter {$a}，Current Death : {$b}！</span><br>\n";
		} elseif($news == 'duel') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> Game Duel Mode Activate!</span><br>\n";
		} elseif($news == 'end0') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> Game Stopped by GM!</span><br>\n";
		} elseif($news == 'end1') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> All players were dead!</span><br>\n";
		} elseif($news == 'end2') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">Winner——{$a}！</span><br>\n";
		} elseif($news == 'end3') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a} Wins by overriding the system.</span><br>\n";
		} elseif($news == 'end4') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> No one join, Game Over.</span><br>\n";
		} elseif($news == 'end5') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a} detonated a nuke, turn the battlefield to dust.</span><br>\n";
		} elseif($news == 'end6') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\"> Round Stopped by GM.</span><br>\n";
		} elseif($news == 'end7') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"L5\">{$a}完成了他的使命</span><br>\n";
		}elseif(strpos($news,'death') === 0) {
			if($news == 'death11') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got killed staying at <span class=\"red\">Forbidden Area 【{$plsinfo[$c]}】</span>";
			} elseif($news == 'death12') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got killed by <span class=\"red\">Poison</span>";
			} elseif($news == 'death13') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got in an <span class=\"red\">Accident</span>";
			} elseif($news == 'death14') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> messed up <span class=\"red\">in an override</span>.";
			} elseif($news == 'death15') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got <span class=\"red\">cleared</span>";
			} elseif($news == 'death16') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got <span class=\"red\">cleaned.</span>";
			} elseif($news == 'death17') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got <span class=\"red\">Pelted by Hail</span>";
			} elseif($news == 'death18') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>got <span class=\"red\">Burned to a Crisp</span>死亡";
			} elseif($news == 'death20') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got smashed by <span class=\"yellow\">$c</span><span class=\"red\">$nowep</span>";
			} elseif($news == 'death21') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got smashed by <span class=\"yellow\">$c</span> with <span class=\"red\">$d</span>";
			} elseif($news == 'death22') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got diced by <span class=\"yellow\">$c</span> with <span class=\"red\">$d</span>";
			} elseif($news == 'death23') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got shot by <span class=\"yellow\">$c</span> with <span class=\"red\">$d</span>";
			} elseif($news == 'death24') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got nailed by <span class=\"yellow\">$c</span> with <span class=\"red\">$d</span>";
			} elseif($news == 'death25') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got blown by <span class=\"yellow\">$c</span> with <span class=\"red\">$d</span>";
			}	elseif($news == 'death29') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span> got killed by <span class=\"yellow\">$c</span> with Magic <span class=\"red\">$d</span>";
			} elseif($news == 'death26') {
				if($c) {
					$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>Got killed by eating poisoned food by <span class=\"yellow\">$c</span>";
				} else {
					$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>got killed by eating poisonous food.";
				}
			} elseif($news == 'death27') {
				if($c){
					$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>Got blowned up by <span class=\"yellow\">$c</span> with the trap <span class=\"red\">$d</span>";
				} else {
					$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>Got blowned up with a trap.";
				}
			} elseif($news == 'death28') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因<span class=\"yellow\">$d</span>意外身亡";
			} elseif($news == 'death30') {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因误触伪装成核弹按钮的蛋疼机关被炸死";
			} elseif($news == 'death31'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>Commited Suiside!";
			} elseif($news == 'death32'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，躲藏于<span class=\"red\">$plsinfo[$c]</span>的<span class=\"yellow\">$a</span><span class=\"red\">挂机时间过长</span>，被在外等待的愤怒的玩家们私刑处死！";
			} elseif($news == 'death33'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因卷入特殊部队『天使』的实弹演习，被坠落的少女和机体“亲吻”而死";
			} elseif($news == 'death34'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因摄入过量突变药剂，身体组织崩解而死！";
			} elseif($news == 'death35'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识救♀济！";
			} elseif($news == 'death36'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识腰★斩！";
			} elseif($news == 'death37'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识断★头！";
			} elseif($news == 'death38'){
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识救♀济！";
			} else {
				$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>因<span class=\"red\">不明原因</span>死亡";
			}
			$dname = $typeinfo[$b].' '.$a;
//			if($b == 0) {
//				//$dname = $a;
//				$lwresult = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
//				$lastword = $db->result($lwresult, 0);
//			} else {
//				//$dname = $typeinfo[$b].' '.$a;
//				$lastword = is_array($lwinfo[$b]) ? $lwinfo[$b][$a] : $lwinfo[$b];
//			}
			if(!$e){
				$newsinfo .= "<span class=\"yellow\">【{$dname} died saying nothing】</span><br>\n";
			}else{
				$newsinfo .= "<span class=\"yellow\">【{$dname}：“{$e}”】</span><br>\n";
			}
		} elseif($news == 'poison') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"purple\">{$a}eat poisoned food {$c} poisoned by {$b}</span><br>\n";
		} elseif($news == 'trap') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a} got in a trap {$c} that is set by {$b}</span><br>\n";
		} elseif($news == 'trapmiss') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a} dodged a trap {$c} that is set by {$b}</span><br>\n";
		} elseif($news == 'trapdef') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}依靠迎击装备抵御了{$b}设置的陷阱{$c}的伤害</span><br>\n";
		} elseif($news == 'duelkey') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a} Activated DUEL MODE LOCKDOWN WITH {$b}！</span><br>\n";
		} elseif($news == 'corpseclear') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Used 凸眼鱼， Blown away {$b} bodies！</span><br>\n";
		} elseif($news == 'wthchange') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Used {$c}， the weather changed to {$wthinfo[$b]}！</span><br>\n";
		} elseif($news == 'wthfail') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Used {$c}，nothing happened.</span><br>\n";
		} elseif($news == 'syswthchg') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">GM has changed weather into {$wthinfo[$a]}！</span><br>\n";
		} elseif($news == 'sysaddarea') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">GM has added more restrict areas!</span><br>\n";
		} elseif($news == 'syshackchg') {
			if($a){$hackword = 'All restrict area overridden';$class = 'lime';}
			else{$hackword = '禁区恢复了未解除状态';$class = 'yellow';}
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"{$class}\">奇迹和魔法都是存在的！{$hackword}！</span><br>\n";
		} elseif($news == 'sysgschg') {
			if($a == 20){
				$chgword = 'Game Started!';
				$class = 'lime';
			}	elseif($a == 30){
				$chgword = 'Game Locked!';
				$class = 'yellow';
			}	elseif($a == 40){
				$chgword = 'Game in Lockdown!';
				$class = 'red';
			}	elseif($a == 50){
				$chgword = 'Game in Duel Lock!';
				$class = 'red';
			}	else{
				$chgword = '异常语句，请联系管理员！';
				$class = 'red';
			}
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"{$class}\">奇迹和魔法都是存在的！{$chgword}</span><br>\n";
		} elseif($news == 'newwep') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Used {$b}， to modify <span class=\"yellow\">$c</span>！</span><br>\n";
		} elseif($news == 'newwep2') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Used{$b}， to upgrade <span class=\"yellow\">$c</span>！</span><br>\n";
		} elseif($news == 'itemmix') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a} Created {$b}</span><br>\n";
		}elseif($news == 'syncmix') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}同调合成了{$b}</span><br>\n";
		}elseif($news == 'overmix') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}超量合成了{$b}</span><br>\n";
		}elseif($news == 'mixfail') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a}合成游戏王卡牌失败，素材全部消失！真是大快人心啊！</span><br>\n";
		}elseif($news == 'song') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}</span>在<span class=\"yellow\">{$b}</span>歌唱了<span class=\"red\">{$c}</span>。<br>\n";
		}  elseif($news == 'itembuy') {
			//$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}购买了{$b}</span><br>\n";
			$newsinfo .= '';
		} elseif($news == 'damage') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"clan\">$a</span><br>\n";
		} elseif($news == 'alive') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">$a</span>被<span class=\"yellow\">神北 小毬许愿复活</span><br>\n";
		} elseif($news == 'delcp') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a}的尸体被时空特使别动队销毁了</span><br>\n";
		} elseif($news == 'editpc') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a}遭到了黑幕的生化改造！</span><br>\n";
		} elseif($news == 'suisidefail') {
			$newsinfo .= "<li><font style=\"background:url(http://dts.acfun.tv/img/backround4.gif) repeat-x\">{$hour}:{$min}:{$sec}，<span class=\"red\">{$a}注射了H173，却由于RP太高进入了发狂状态！！</font></span><br>\n";
		} elseif($news == 'inf') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"red\">{$a}的攻击致使{$b}</span>{$exdmginf[$c]}<span class=\"red\">了</span><br>\n";
		} elseif($news == 'addnpc') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a}乱入战场！</span><br>\n";
		} elseif($news == 'addnpcs') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$b}名{$a}加入战斗！</span><br>\n";
		} elseif($news == 'secphase') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}使用了挑战者之证，让3名幻影执行官加入了战场！打倒他们去获得ID卡来解除游戏吧！</span><br>\n";
		} elseif($news == 'thiphase') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}触发了对虚拟现实的救济！虚拟意识已经在■■■■活性化！</span><br>\n";
		} elseif($news == 'dfphase') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}使用了黑色碎片，让1名未知存在加入了战场！打倒她去获得ID卡来解除游戏吧！</span><br>\n";
		} elseif($news == 'dfsecphase') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}闯了大祸，打破了Dark Force的封印！</span><br>\n";
		} elseif($news == 'evonpc') {
			if($a == 'Dark Force幼体'){
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却没料到这只是幻影……{$b}的封印已经被破坏了！</span>";
			}elseif($a == '小莱卡'){
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却发现这只是幻象……真正的{$b}受到惊动，方才加入战场！</span>";
			}else{
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却发现对方展现出了第二形态：{$b}！</span>";
			}
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，$nword<br>\n";
		} elseif($news == 'notworthit') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"lime\">{$a}做出了一个他自己可能会后悔很长一段时间的决定。</span><br>\n";
		} elseif($news == 'present') {
			$newsinfo .= "<li>{$hour}:{$min}:{$sec}，<span class=\"yellow\">{$a} opened {$b}，Got {$c}！</span><br>\n";
		} else {
			$newsinfo .= "<li>$time,$news,$a,$b,$c,$d<br>\n";
		}
	}

	$newsinfo .= '</ul>';
	return $newsinfo;
		
}

?>
