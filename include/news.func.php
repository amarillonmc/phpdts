<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

include_once GAME_ROOT.'./include/game/titles.func.php';

function  nparse_news($start = 0, $range = 0  ){//$type = '') {
	global $week,$nowep,$db,$tablepre,$lwinfo,$plsinfo,$hplsinfo,$wthinfo,$typeinfo,$exdmginf,$newslimit,$cskills;
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
			$newsinfo .= "<span class=\"evergreen\"><B>{$month}月{$day}日(星期$week[$wday])</B></span><br>";
			$nday = $day;
		}

		//登记非功能性地点信息时合并隐藏地点 为什么会有两个news.func.php？？？
		foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
		//死法（除DN外）：道具名登记在$d上；
		if(strpos($news,'death')!==false && $news!=='death28' && isset($d)) $d = parse_info_desc($d,'m');
		//赠送道具、吃到毒补给、陷阱、改变天气、强化武器、唱歌、打开礼物盒：道具名登记在$c上；
		if((strpos($news,'senditem')!==false||strpos($news,'poison')!==false||strpos($news,'trap')!==false||strpos($news,'wth')!==false||strpos($news,'newwep')!==false||strpos($news,'song')!==false||strpos($news,'present')!==false) && isset($c)) $c = parse_info_desc($c,'m');
		//合成、使用死斗卡、使用仓库：道具名登记在$b上;
		if((strpos($news,'mix')!==false||strpos($news,'duelkey')!==false||strpos($news,'depot')===0) && isset($b)) $b = parse_info_desc($b,'m');
		
		//新PC加入战场 格式化nick
		//卧槽这可怎么搞……只能脏一把了

		//$sec='??';
		if($news == 'newgame') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">第{$a}回ACFUN大逃杀开始了</span><br>\n";
		} elseif($news == 'gameover') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">第{$a}回ACFUN大逃杀结束了</span><br>\n";
		} elseif($news == 'newpc') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}({$b})进入了大逃杀战场</span><br>\n";
		} elseif($news == 'newgm') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">管理员-{$a}({$b})华丽地乱入了战场</span><br>\n";
		} elseif($news == 'teammake') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$b}创建了队伍{$a}</span><br>\n";
		} elseif($news == 'teamjoin') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$b}加入了队伍{$a}</span><br>\n";
		} elseif($news == 'teamquit') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$b}退出了队伍{$a}</span><br>\n";
		} elseif($news == 'senditem') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}将<span class=\"yellow\">$c</span>赠送给了{$b}</span><br>\n";
		} elseif($news == 'addarea') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，增加禁区：";
			$alist = explode('_',$a);
			foreach($alist as $ar) {
				$newsinfo .= "$plsinfo[$ar] ";
			}
			$newsinfo .= "<span class=\"yellow\">【天气：{$wthinfo[$b]}】</span><br>\n";
		} elseif($news == 'hack') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}启动了hack程序，全部禁区解除！</span><br>\n";
		} elseif($news == 'hack2') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}启动了救济程序，全部禁区解除！</span><br>\n";
		} elseif($news == 'combo') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">游戏进入连斗阶段！</span><br>\n";
		} elseif($news == 'comboupdate') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">连斗判断死亡数修正为{$a}人，当前死亡数为{$b}人！</span><br>\n";
		} elseif($news == 'duel') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">游戏进入死斗阶段！</span><br>\n";
		} elseif($news == 'end0') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">游戏出现故障，意外结束</span><br>\n";
		} elseif($news == 'end1') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">参与者全部死亡！</span><br>\n";
		} elseif($news == 'end2') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">优胜者——{$a}！</span><br>\n";
		} elseif($news == 'end3') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}解除了精神锁定，游戏紧急中止</span><br>\n";
		} elseif($news == 'end4') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">无人参加，游戏自动结束</span><br>\n";
		} elseif($news == 'end5') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}引爆了核弹，毁坏了虚拟战场</span><br>\n";
		} elseif($news == 'end6') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">本局游戏被GM中止</span><br>\n";
		} elseif($news == 'revival') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}因为及时按了BOMB键而原地满血复活了！</span><br>\n";
		} elseif($news == 'aurora_revival')  {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}在奥罗拉的作用下原地复活了！</span><br>\n";
		} elseif(strpos($news,'death') === 0) {
			if($news == 'death11') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因滞留在<span class=\"red\">禁区【{$plsinfo[$c]}】</span>死亡";
			} elseif($news == 'death12') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"red\">毒发</span>死亡";
			} elseif($news == 'death13') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"red\">意外事故</span>死亡";
			} elseif($news == 'death14') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"red\">入侵禁区系统失败</span>死亡";
			} elseif($news == 'death15') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"red\">时空特使强行消除</span>";
			} elseif($news == 'death16') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"red\">由理直接拉入SSS团</span>";
			} elseif($news == 'death17') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"red\">冰雹砸死</span>";
			} elseif($news == 'death18') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"red\">烧伤发作</span>死亡";
			} elseif($news == 'death20') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>用<span class=\"red\">$nowep</span>击飞";
			} elseif($news == 'death21') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>使用<span class=\"red\">{$d}</span>殴打致死";
			} elseif($news == 'death22') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>使用<span class=\"red\">{$d}</span>斩杀";
			} elseif($news == 'death23') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>使用<span class=\"red\">{$d}</span>射杀";
			} elseif($news == 'death24') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>投掷<span class=\"red\">{$d}</span>致死";
			} elseif($news == 'death25') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>埋设<span class=\"red\">{$d}</span>伏击炸死";
			} elseif($news == 'death29') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">$c</span>发动<span class=\"red\">{$d}</span>以灵力杀死";
			} elseif($news == 'death39') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>在与<span class=\"yellow\">$c</span>的战斗中因<span class=\"red\">武器反噬</span>意外身亡";
			} elseif($news == 'death26') {
				if($c) {
					$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因食用了<span class=\"yellow\">$c</span>下毒的{$d}被毒死";
				} else {
					$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因食用了有毒的{$d}被毒死";
				}
			} elseif($news == 'death27') {
				if(($c)&&($c!=' ')){
					$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因触发了<span class=\"yellow\">$c</span>设置的陷阱{$d}被杀死";
				} else {
					$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因触发了陷阱{$d}被杀死";
				}
			} elseif($news == 'death28') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"yellow\">$d</span>意外身亡";
			} elseif($news == 'death30') {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因误触伪装成核弹按钮的蛋疼机关被炸死";
			} elseif($news == 'death31'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因L5发作自己挠破喉咙身亡！";
			} elseif($news == 'death32'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，躲藏于<span class=\"red\">$plsinfo[$c]</span>的<span class=\"yellow\">$a</span><span class=\"red\">挂机时间过长</span>，被在外等待的愤怒的玩家们私刑处死！";
			} elseif($news == 'death33'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因卷入特殊部队『天使』的实弹演习，被坠落的少女和机体“亲吻”而死";
			} elseif($news == 'death34'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因摄入过量突变药剂，身体组织崩解而死！";
			} elseif($news == 'death35'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识救♀济！";
			} elseif($news == 'death36'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识腰★斩！";
			} elseif($news == 'death37'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识断★头！";
			} elseif($news == 'death38'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因为敌意过剩，被虚拟意识救♀济！";
			} elseif($news == 'death40'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被御柱创死了！";
			} elseif($news == 'death42'){
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>活用了单人脱出程序机构，提前离开了虚拟幻境！";
			} else {
				$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>因<span class=\"red\">不明原因</span>死亡";
			}
			if($b) $dname = $typeinfo[$b].' '.$a;
			else $dname = $typeinfo[0].' '.$a;
//			if($b == 0) {
//				//$dname = $a;
//				$lwresult = $db->query("SELECT lastword FROM {$tablepre}users WHERE username = '$a'");
//				$lastword = $db->result($lwresult, 0);
//			} else {
//				//$dname = $typeinfo[$b].' '.$a;
//				$lastword = is_array($lwinfo[$b]) ? $lwinfo[$b][$a] : $lwinfo[$b];
//			}
			if(!$e){
				$newsinfo .= "<span class=\"yellow\">【{$dname} 什么都没说就死去了】</span><br>\n";
			}else{
				$newsinfo .= "<span class=\"yellow\">【{$dname}：“{$e}”】</span><br>\n";
			}
		} elseif($news == 'poison') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"purple\">{$a}食用了{$b}下毒的{$c}</span><br>\n";
		} elseif($news == 'trap') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}中了{$b}设置的陷阱{$c}</span><br>\n";
		} elseif($news == 'trapmiss') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}回避了{$b}设置的陷阱{$c}</span><br>\n";
		} elseif($news == 'trapdef') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}依靠迎击装备抵御了{$b}设置的陷阱{$c}的伤害</span><br>\n";
		} elseif($news == 'duelkey') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}使用了{$b}，启动了死斗程序！</span><br>\n";
		} elseif($news == 'corpseclear') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了凸眼鱼，{$b}具尸体被吸走了！</span><br>\n";
		} elseif($news == 'wthchange') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了{$c}，天气变成了{$wthinfo[$b]}！</span><br>\n";
		} elseif($news == 'wthfail') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了{$c}，但是恶劣的天气并未发生改变！</span><br>\n";
		} elseif($news == 'syswthchg') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">奇迹和魔法都是存在的！当前天气变成了{$wthinfo[$a]}！</span><br>\n";
		} elseif($news == 'sysaddarea') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">奇迹和魔法都是存在的！禁区提前增加了！</span><br>\n";
		} elseif($news == 'syshackchg') {
			if($a){$hackword = '全部禁区都被解除了';$class = 'lime';}
			else{$hackword = '禁区恢复了未解除状态';$class = 'yellow';}
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"{$class}\">奇迹和魔法都是存在的！{$hackword}！</span><br>\n";
		} elseif($news == 'sysgschg') {
			if($a == 20){
				$chgword = '当前游戏立即开始了！';
				$class = 'lime';
			}	elseif($a == 30){
				$chgword = '当前游戏停止激活！';
				$class = 'yellow';
			}	elseif($a == 40){
				$chgword = '当前游戏进入连斗阶段！';
				$class = 'red';
			}	elseif($a == 50){
				$chgword = '当前游戏进入死斗阶段！';
				$class = 'red';
			}	else{
				$chgword = '异常语句，请联系管理员！';
				$class = 'red';
			}
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"{$class}\">奇迹和魔法都是存在的！{$chgword}</span><br>\n";
		} elseif($news == 'newwep') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了{$b}，改造了<span class=\"yellow\">$c</span>！</span><br>\n";
		} elseif($news == 'newwep2') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了{$b}，强化了<span class=\"yellow\">$c</span>！</span><br>\n";
		} elseif($news == 'itemmix') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}合成了{$b}</span><br>\n";
		}elseif($news == 'syncmix') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}同调合成了{$b}</span><br>\n";
		}elseif($news == 'overmix') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}超量合成了{$b}</span><br>\n";
		}elseif($news == 'mixfail') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}合成游戏王卡牌失败，素材全部消失！真是大快人心啊！</span><br>\n";
		}elseif($news == 'npcmove') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，{$a}使<span class=\"yellow\">{$b}</span>的位置移动了！<br>\n";
		}elseif($news == 'song') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}</span>在<span class=\"yellow\">{$b}</span>歌唱了<span class=\"red\">{$c}</span>。<br>\n";
		}  elseif($news == 'itembuy') {
			//$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}购买了{$b}</span><br>\n";
			$newsinfo .= '';
		} elseif($news == 'damage') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"clan\">$a</span><br>\n";
		} elseif($news == 'alive') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">$a</span>被<span class=\"yellow\">神北 小毬许愿复活</span><br>\n";
		} elseif($news == 'delcp') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}的尸体被时空特使别动队销毁了</span><br>\n";
		} elseif($news == 'cstick') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}把{$b}作为武器抡了起来！哇……这可真是……</span><br>\n";
		} elseif($news == 'editpc') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}遭到了黑幕的生化改造！</span><br>\n";
		} elseif($news == 'suisidefail') {
			$newsinfo .= "<li><font style=\"background:url(img/backround4.gif) repeat-x\">{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}注射了H173，却由于RP太高进入了发狂状态！！</font></span><br>\n";
		} elseif($news == 'inf') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}的攻击致使{$b}</span>{$exdmginf[$c]}<span class=\"red\">了</span><br>\n";
		} elseif($news == 'addnpc') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}乱入战场！</span><br>\n";
		} elseif($news == 'addnpcs') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$b}名{$a}乱入战场！</span><br>\n";
		} elseif($news == 'secphase') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了挑战者之证，让3名幻影执行官加入了战场！打倒他们去获得ID卡来解除游戏吧！</span><br>\n";
		} elseif($news == 'thiphase') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}触发了对虚拟现实的救济！虚拟意识已经在■■■■活性化！</span><br>\n";
		} elseif($news == 'dfphase') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}使用了黑色碎片，让1名未知存在加入了战场！打倒她去获得ID卡来解除游戏吧！</span><br>\n";
		} elseif($news == 'dfsecphase') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}闯了大祸，打破了Dark Force的封印！</span><br>\n";
		} elseif($news == 'key1'){
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}释放了第一批被封印的NPC存在！</span><br>\n";
		} elseif($news == 'key2'){
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}释放了第二批被封印的NPC存在！</span><br>\n";
		} elseif($news == 'key3'){
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}出于未知原因，在战场上部署了更多的种火！Ψпψтμψхλδ！</span><br>\n";
		} elseif($news == 'fsmove'){
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}在【$plsinfo[$c]】移动了全部种火NPC的位置！真是不解风情啊！</span><br>\n";
		} elseif($news == 'keyuu'){
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}在【$plsinfo[$c]】向红暮和蓝凝发起了挑战！</span><br>\n";
		} elseif($news == 'evonpc') {
			if($a == 'Dark Force幼体'){
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却没料到这只是幻影……{$b}的封印已经被破坏了！</span>";
			}elseif($a == '小莱卡'){
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却发现这只是幻象……真正的{$b}受到惊动，方才加入战场！</span>";
			}else{
				$nword = "<span class=\"lime\">{$c}击杀了{$a}，却发现对方展现出了第二形态：{$b}！</span>";
			}
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，$nword<br>\n";
		} elseif($news == 'notworthit') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"lime\">{$a}做出了一个他自己可能会后悔很长一段时间的决定。</span><br>\n";
		} elseif($news == 'present') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}打开了{$b}，获得了{$c}！</span><br>\n";
		} elseif($news == 'emix_success') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}用零散的元素组合出了{$b}！</span><br>\n";
		} elseif($news == 'emix_failed') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}试图把零散的元素重新组合起来，但是失败了！哎呀呀、这可真是……</span><br>\n";
		} elseif($news == 'gpost') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"sienna\">{$a}为{$c}赞助了{$e}份{$b}！快递员正带着包裹前往【{$plsinfo[$d]}】</span><br>\n";
		} elseif($news == 'gpost_success') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"sienna\">{$a}向{$c}赞助的{$b}已成功送达！</span><br>\n";
		} elseif($news == 'gpost_failed') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"sienna\">{$a}向场内玩家赞助的{$b}竟然被人半路截走了！真是天有不测风云……</span><br>\n";
		} elseif($news == 'depot_save') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"grey\">{$a}向安全箱中存入了道具{$b}。</span><br>\n";
		} elseif($news == 'depot_load') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"grey\">{$a}从安全箱中取出了道具{$b}。</span><br>\n";
		} elseif($news == 'loot_depot') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"grey\">{$a}将{$b}生前存放在安全箱里的东西转移到了自己的名下。哇……真是世风日下，道德沦丧啊！</span><br>\n";
		} elseif($news == 'cdestroy') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"red\">{$a}把{$b}的尸体销毁了</span><br>\n";
		} elseif($news == 'csl_wthchange') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"orange\">{$a}发送了控制指令，战场的天气变成了{$wthinfo[$b]}！</span><br>\n";
		} elseif($news == 'csl_hack') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"orange\">{$a}发送了控制指令，全部禁区解除！</span><br>\n";
		} elseif($news == 'csl_addarea') {
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"orange\">{$a}发送了控制指令，下一回禁区提前到来了！</span><br>\n";
		} elseif(strpos($news,'bsk_')===0) {
			$bsk = substr($news,4);
			$bname = $cskills[$bsk]['name'];
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"clan\">{$a}对{$b}发动了技能<span class=\"yellow\">「{$bname}」</span>！</span><br>\n";
		} elseif(strpos($news,'getsk_')===0) {
			$bsk = substr($news,6);
			$bname = $cskills[$bsk]['name'];
			$newsinfo .= "<li>{$hour}时{$min}分{$sec}秒，<span class=\"yellow\">{$a}通过翻阅{$b}学会了技能<span class=\"lime\">「{$bname}」</span>！</span><br>\n";
		} else {
			$newsinfo .= "<li>$time,$news,$a,$b,$c,$d<br>\n";
		}
	}

	$newsinfo .= '</ul>';
	return $newsinfo;
		
}

?>
