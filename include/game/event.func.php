<?php
if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function event(){
	global $mode,$log,$hp,$sp,$inf,$pls,$rage,$money;
	global $mhp,$msp,$wp,$wk,$wg,$wc,$wd,$wf;
	global $rp,$killnum,$state;
	//没有事件的地图返回默认值0，有事件的地图返回1。地图内没有事件会继续推进探索判定。
	$event = 0;
	$dice1 = rand(0,5);
	$dice2 = rand(20,40);//原为rand(5,10)
	if($pls == 0) { //无月之影
	} elseif($pls == 1) { //端点
	} elseif($pls == 2) { //现RF高校
		$log = ($log . "突然，一个戴着面具的怪人出现了！<BR>");
		if($dice1 == 2){
			$log = ($log . "“呜嘛呜——！”<br>被怪人<span class=\"red\">打中了头</span>！<BR>");
			$inf = str_replace('h','',$inf);
			$inf = ($inf . 'h');
		}elseif($dice1 == 3){
			$log = ($log . "“呜嘛呜——！”<br>被怪人打中了，<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}elseif($rp <=45){
			$log = ($log . "“呜嘛呜——！”<br>怪人给了你一个钱包！里面有<span class=\"red\">{$dice2}个1元硬币</span>！<BR>");
			$money = $money + $dice2 * 1;
			event_rp_up(15);
			//$rp = $rp + 15;
		}else{
			$log = ($log . "呼，总算逃脱了。<BR>");
		}
		$event = 1;
	} elseif($pls == 3) { //雪之镇
		if($rp <=70){
			$log = ($log . "突然，一位拿着纸袋的少女向你撞来！<BR>");
			if($dice1 == 2){
				$log = ($log . "你身体一侧，成功回避了被撞倒的厄运。<BR>你看着少女面朝下重重地摔在地上，转头走开了。<BR>");
				event_rp_up(40);
				//$rp = $rp + 40;
			}
			else{
				$log = ($log . "你回避不及，被少女撞个正着！<BR>你面朝下地重重地摔在地上。");
				$inf = str_replace('h','',$inf);
				$inf = ($inf . 'h');	
				event_rp_up(25);
				//$rp = $rp + 25;
				$log = ($log . "不过知道少女不是故意找茬后，<BR>你原谅了她，并且和她分享了雕鱼烧，你感觉全身舒畅。");
				$hp = $mhp;
				$sp = $msp;
			}
		}else{
			$log = ($log . "突然，一位少女向你撞来！<BR>");
			if($dice1 == 2){
				$log = ($log . "你身体一侧，成功回避了被撞倒的厄运。<BR>你看着少女面朝下重重地摔在地上，转头走开了。<BR>");
				event_rp_up(40);
				//$rp = $rp + 40;
			}
			else{
				$log = ($log . "你回避不及，被少女撞个正着！<BR>你面朝下地重重地摔在地上。");
				$inf = str_replace('h','',$inf);
				$inf = ($inf . 'h');	
				event_rp_up(-5);
				//$rp = $rp - 5;
			}
		}	
		$event = 1;	
	} elseif($pls == 4) { //索拉利斯
	} elseif($pls == 5) { //指挥中心
	} elseif($pls == 6) { //梦幻馆
	} elseif($pls == 7) { //清水池
		$log = ($log . "糟糕，脚下滑了一下！<BR>");
		if($dice1 <= 3){
			$dice2 += 10;
			if($sp <= $dice2){
				$dice2 = $sp-1;
			}
			$sp-=$dice2;
			$log = ($log . "你摔进了池里！<BR>从水池里爬出来<span class=\"red\">消耗了{$dice2}点体力</span>。<BR>");
		}else{
			$log = ($log . "万幸，你没跌进池中。<BR>");
		}
		$event = 1;
	} elseif($pls == 8) { //白穗神社
	} elseif($pls == 9) { //墓地
	} elseif($pls == 10) { //麦斯克林
	} elseif($pls == 11) { //央中电视台 - 现对天使用作战本部
		$log = ($log . "哇！一个大锤向你锤来！<BR>");
		if($dice1 == 2){
			$log = ($log . "大锤重重地<span class=\"red\">砸到了腿上</span>，好疼！<BR>");
			$inf = str_replace('f','',$inf);
			$inf = ($inf . 'f');
		}elseif($dice1 == 3){
			$log = ($log . "你被击飞出了窗外，<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}else{
			$log = ($log . "你勉强躲过了大锤的攻击。<BR>");
		}
		$event = 1;
	} elseif($pls == 12) { //夏之镇
		$log = ($log . "突然，天空出现一大群乌鸦！<BR>");
		if($dice1 == 2){
			$log = ($log . "被乌鸦袭击，<span class=\"red\">头部受了伤</span>！<BR>");
			$inf = str_replace('h','',$inf);
			$inf = ($inf . 'h');
		}elseif($dice1 == 3){
			$log = ($log . "被乌鸦袭击，<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}else{
			$log = ($log . "呼，总算击退了。<BR>");
		}
		$event = 1;
	} elseif($pls == 13) { //三体星
	} elseif($pls == 14) { //光坂高校
	} elseif($pls == 15) { //守矢神社
		$log = ($log . "突然有妖怪袭击你！<BR>");
		if($dice1 == 2){
			$log = ($log . "被妖怪吓着了！你惊慌中<span class=\"red\">撞伤了自己的头部</span>！<BR>");
			$inf = str_replace('h','',$inf);
			$inf = ($inf . 'h');
		}elseif($dice1 == 3){
			$log = ($log . "妖怪的弹幕使你<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}else{
			$log = ($log . "呼，所谓妖怪不过是个撑着紫伞的少女而已，没什么可害怕的。<BR>");
		}
		$event = 1;
	} elseif($pls == 16) { //常磐森林
		$log = ($log . "野生的皮卡丘从草丛中钻出来了！<BR>");
		if($dice1 == 2){
			$log = ($log . "皮卡丘使用了电击！<span class=\"red\">手臂被击伤了</span>！<BR>");
			$inf = str_replace('a','',$inf);
			$inf = ($inf . 'a');
		}elseif($dice1 == 3){
			$log = ($log . "皮卡丘使用了电光石火！<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}else{
			$log = ($log . "成功地逃跑了。<BR>");
		}
		$event = 1;
	} elseif($pls == 17) { //常磐台中学
	} elseif($pls == 18) { //秋之镇
		$log = ($log . "突然，天空出现一大群乌鸦！<BR>");
		if($dice1 == 2){
			$log = ($log . "被乌鸦袭击，<span class=\"red\">头部受了伤</span>！<BR>");
			$inf = str_replace('h','',$inf);
			$inf = ($inf . 'h');
		}elseif($dice1 == 3){
			$log = ($log . "被乌鸦袭击，<span class=\"red\">受到{$dice2}点伤害</span>！<BR>");
			$hp-=$dice2;
		}else{
			$log = ($log . "呼，总算击退了。<BR>");
		}
		$event = 1;
	} elseif($pls == 19) { //精灵中心
	} elseif($pls == 20) { //春之镇
	} elseif($pls == 21) { //圣Gradius学园
		global $gamestate;
		if($gamestate < 50){
			$log = ($log . "隶属于时空部门G的特殊部队『天使』正在实弹演习！<BR>你被卷入了弹幕中！<BR>");
			if($dice1 <= 1 ){
				$log = ($log . "在弹幕的狂风中，你有惊无险地回避着弹幕，总算擦弹成功了。<BR>");
				if($dice2 == 40 && $rp > 40){
					$log = ($log . "咦，头顶上……好像有一名少女被弹幕击中了……？<BR>“对不起、对不起！”伴随着焦急的道歉声，少女以及她乘坐的机体向你笔直坠落下来。<br>你还来不及反应，重达数十吨的机体便直接落在了你的头上。<br>");
					include_once GAME_ROOT . './include/state.func.php';
					death('gradius');
					return;
				}
				elseif($dice2 == 30){
				$log = ($log . "接下来你看见驾驶着棕色机体的少女向你飞来。<BR>“实在对不起，我们看起来没有放假的时候啊。危险躲藏在每个大意之中不是么？”<br>她扔给了你些什么东西，貌似是面额为573的『纸币』？<br>“祝你好运！”少女这么说完就飞走了。");
				$money = $money + 573;
				event_rp_up(100);
				//$rp = $rp + 100;
				}
			}
			else{
				$log = ($log . "在弹幕的狂风中，你徒劳地试图回避弹幕……<BR>擦弹什么的根本做不到啊！<BR>你被少女们打成了筛子！<BR>");
				global $infwords;
				$infcache = '';
				foreach(Array('h','b','a','f') as $value){
					$dice3=rand(0,10);
					if($dice3<=6){
						$inf = str_replace($value,'',$inf);
						$infcache .= $value;
						$log .= "<span class=\"red\">弹幕造成你{$infwords[$value]}了！</span><br />";
					}
				}
				if(empty($infcache)){
					$inf = str_replace('b','',$inf);
					$inf .= 'b';
					$log .= "<span class=\"red\">弹幕造成你胸部受伤了！</span><br />";
				} else {$inf .= $infcache;}
	//			$inf = str_replace('h','',$inf);
	//			$inf = str_replace('b','',$inf);
	//			$inf = str_replace('a','',$inf);
	//			$inf = str_replace('f','',$inf);
	//			$inf = ($inf . 'hbaf');
				if($dice2 >= 39){
					$log = ($log . "并且，少女们的弹幕击中了要害！<BR><span class=\"red\">你感觉小命差点就交代在这里了</span>。<BR>");
					$hp = 1;
				}
				elseif($dice2 >= 36){
					$log = ($log . "并且，黑洞激光造成你<span class=\"blue\">冻结</span>了！<BR>");
					$inf = str_replace('i','',$inf);
					$inf = ($inf . 'i');
				}
				elseif($dice2 >= 32){
					$log = ($log . "并且，环形激光导致你<span class=\"red\">烧伤</span>了！<BR>");
					$inf = str_replace('u','',$inf);
					$inf = ($inf . 'u');
				}
				elseif($dice2 >= 27){
					$log = ($log . "并且，精神震荡弹导致你<span class=\"yellow\">全身麻痹</span>了！<BR>");
					$inf = str_replace('e','',$inf);
					$inf = ($inf . 'e');
				}
				elseif($dice2 >= 23){
					$log = ($log . "并且，音波装备导致你<span class=\"grey\">混乱</span>了！<BR>");
					$inf = str_replace('w','',$inf);
					$inf = ($inf . 'w');
				}
				else{
					$log = ($log . "并且，干扰用强袭装备导致你<span class=\"purple\">中毒</span>了！<BR>");
					$inf = str_replace('p','',$inf);
					$inf = ($inf . 'p');
				}
				$log = ($log . "你遍体鳞伤、连滚带爬地逃走了。<BR>");
			}
		} else {
			$log = ($log . "特殊部队『天使』的少女们不知道去了哪里。<BR>");
		}
		$event = 1;
	} elseif($pls == 22) { //初始之树
	} elseif($pls == 23) { //幻想世界
	} elseif($pls == 24) { //永恒的世界
	} elseif($pls == 25) { //妖精驿站
	} elseif($pls == 26) { //键刃墓场
		global $gamestate,$db,$tablepre;
		$result = $db->query("SELECT pid,hp FROM {$tablepre}players WHERE type=4");
		if(!$db->num_rows($result)){$flag = 0;}//篝未加入战场，正常处理事件；
		else{$flag = 1;}//篝加入战场
		$dice=rand(0,10);
		if(!$flag)
		{
			if ($dice < 3){
				if ($rp < 40){
					$log = ($log . "在远方你能感觉到什么东西在……看着你。<BR>");
					event_rp_up(rand(10,25));
					//$rp = $rp + rand(10,25);
				}elseif ($rp < 500){
					$log = ($log . "在远方你能感觉到什么东西在……追踪着你。<BR>");
					event_rp_up(rand(50,100));
					//$rp = $rp + rand(50,100);
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "你觉得身后有什么东西<BR>你回头看了一眼，发现什么都没有。<BR>你稍微放松了点精神。<BR>");
					//$rp = $rp + rand(100,200);
					$spup = rand(50,100);
					//$hp = $mhp;
					$msp += $spup;
					$sp = $msp;		
					event_rp_up($spup*2);
					//$rp += $spup*2;	
				}elseif ($rp < 1000){
					$log = ($log . "不知道为什么，你觉得双腿一软……<BR>");
					$spdown = round($rp/4);
					$sp -= $spdown;
					if($sp <= 0){$sp = 1;}
					//$sp = 17;
				}elseif ($rp < 5000 && $killnum == 0){
					$log = ($log . "你感觉你听到了什么Homo开头的拉丁文单词……可能是错觉吧。<BR>");
				}elseif ($rp < 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}else{
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(rand(1,2));
					//$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}
			}elseif ($dice < 6){
				if ($rp < 40){
					$log = ($log . "在远方你能感觉到什么东西在……看着你。<BR>");
					event_rp_up(rand(50,100));
					//$rp = $rp + rand(50,100);
				}elseif ($rp < 500){
					$log = ($log . "不知道为什么，你觉得双腿一软……<BR>");
					$hpdown = round($rp/4);
					$hp -= $hpdown;
					if($hp <= 0 ){$hp = 1;}
					//$sp = $sp - 200;			
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>少女的丝带飞到你的面前，<BR>在你的脸上重重地刮了一下。<BR>");
					$inf = str_replace('h','',$inf);
					$inf = ($inf . 'h');
				}elseif ($rp < 1000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>少女的丝带飞到你的面前，<BR>在你的脸上重重地刮了一下。<BR>");
					$inf = str_replace('e','',$inf);
					$inf = ($inf . 'e');
				}elseif ($rp < 5000 && $killnum == 0){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>少女的丝带飞到你的面前，<BR>在你的头上重重地敲了一下。<BR>");
					$inf = str_replace('h','',$inf);
					$inf = str_replace('w','',$inf);
					$inf = ($inf . 'hw');
				}elseif ($rp < 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}else{
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(rand(1,2));
					//$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}elseif ($dice < 9){
				if ($rp < 40){
					$log = ($log . "你感觉有什么东西在注意着你的一举一动。<BR>");
					event_rp_up(rand(200,400));
					//$rp = $rp + rand(200,400);
				}elseif ($rp < 500){
					$log = ($log . "你在两把卡在地上的武器间隙中<BR>发现了一个装满奇怪的深色液体的保温瓶；<BR>你喝了一口，感觉体内有一种力量涌出来。<BR>");
					$mhpup = rand(25,50);
					$mhp = $mhp + $mhpup;
					$hp = $mhp;
					event_rp_up($mhpup*4);
					//$rp += $mhpup*4;
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "你百无聊赖地坐了下来看着四周。<BR>突然你发现了一个黑白两色的袋子！<BR>");
					$hp = round($mhp/10);
					if($hp <= 0){$hp = 1;}
					$sp = round($msp/10);
					if($sp <= 0){$sp = 1;}
	//				$mhp = 400;
	//				$msp = 400;
	//				$hp = 200;
	//				$sp = 200;
					$log = ($log . "但是你头一昏<BR>然后你什么都记不得了。<BR>你醒来的时候，才发现你已经七窍流血。<BR>");
					$skillupsum = 0;
					foreach(array('wp','wk','wg','wc','wd','wf') as $val){
						$up = rand(23,34);
						${$val} += $up;
						$skillupsum += $up;
					}
					$rp += $skillupsum*2;
	//				$wp = $wp + rand(75,150);
	//				$wk = $wk + rand(75,150);
	//				$wg = $wg + rand(75,150);
	//				$wc = $wc + rand(75,150);
	//				$wd = $wd + rand(75,150);
	//				$wf = $wf + rand(75,150);
				}elseif ($rp < 1000){
					$log = ($log . "你小心翼翼地在少女旁边坐下，想看看她身下的『绘卷』<BR>结果被红色的丝带正中腿部。<BR>");
	//				$hp = 200;
	//				$sp = 200;
					$hp = round($mhp/8);
					if($hp <= 0){$hp = 1;}
	//				$sp = round($msp/10);
	//				if($sp <= 0){$sp = 1;}
					$inf = str_replace('f','',$inf);
					$inf = ($inf . 'f');
					$log = ($log . "你龇牙咧嘴地逃走了。<BR>");			
				}elseif ($rp < 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}elseif ($rp > 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}else{
					$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}else{
				if ($rp < 40){
					$log = ($log . "你感觉有什么东西在你身后吹气！<BR>太可怕了，还是赶快离开为妙！<BR>");
					event_rp_up(rand(500,1000));
					//$rp = $rp + rand(500,1000);
				}elseif ($rp < 500){
					$log = ($log . "你感觉有什么东西贯穿了你的身体！<BR>太可怕了，还是赶快离开为妙！<BR>");
					$oldhp = $hp;$oldsp = $sp;
					$hp = 1;
					$sp = 1;
					event_rp_up( -round(($oldhp+$oldsp)/10));
					//$rp = $rp - round(($oldhp+$oldsp)/10);
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "你感觉有什么东西贯穿了你的身体！<BR>太可怕了，还是赶快离开为妙！<BR>");
					$skilldownsum = 0;
					foreach(array('wp','wk','wg','wc','wd','wf') as $val){
						$down = rand(1,round(${$val}/2));
						${$val} -= $down;
						$skilldownsum += $down;
					}
					event_rp_up( -round($skilldownsum/6));
					//$rp -= round($skilldownsum/6);
				}elseif ($rp < 1000){
					$log = ($log . "你突然感觉被一种无形的压力直接压在了地上，<BR>太可怕了，还是赶快离开为妙！<BR>");
					$mhp = round($mhp/2);
					if($mhp <= 37){$mhp = 37;}
					if($hp > $mhp){$hp = $mhp;}
					$msp = round($msp/2);
					if($msp <= 37){$msp = 37;}
					if($sp > $msp){$sp = $msp;}
					//$mhp = $msp = 100;
					event_rp_up( -37);
					//$rp = $rp - 37;
				}elseif ($rp < 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}elseif ($rp > 5000){
					$log = ($log . "你面前突然出现了一个黑裙白发的少女身影！是K.A.G.A.R.I！<BR>");
					death_kagari(3);
				}else{
					$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}
		}
		else
		{
			$log .= '你环顾四周，在断壁残垣间找寻着那个熟悉的身影……<br>但她似乎已经离开了。<br>';
		}
		$event = 1;
		//echo $rp;
	} elseif($pls == 27) { //花菱商厦
	} elseif($pls == 28) { //FARGO前基地
	} elseif($pls == 29) { //风祭森林
	} elseif($pls == 30) { //移动机库
	} elseif($pls == 31) { //太鼓实验室
	} elseif($pls == 32) { //SCP实验室
	} elseif($pls == 33) { //雏菊之丘
		global $gamestate,$db,$tablepre;
		$result = $db->query("SELECT pid,hp FROM {$tablepre}players WHERE type=4");
		if(!$db->num_rows($result)) $flag = 0;//篝未加入战场，正常处理事件；
		else{
			$result = $db->fetch_array($result);
			if($result['hp'] >0) $flag = 1;//篝加入战场
			else $flag = 2;//篝加入战场而且跪了
		}
		if(!$flag)
		{
			$dice=rand(0,10);
			if ($dice < 3){
				if ($rp < 40){
					$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
					event_rp_up(rand(10,25));
					//$rp = $rp + rand(10,25);
				}elseif ($rp < 500){
					$log = ($log . "少女抬头看了你一眼，貌似对你的举动很感兴趣的样子。<BR>");
					event_rp_up(rand(50,100));
					//$rp = $rp + rand(50,100);
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "少女向你扔来一个保温瓶。<BR>里面是类似于咖啡的液体；<BR>你喝了一口，感觉味道不怎么样。<BR>");
					//$rp = $rp + rand(100,200);
					$spup = rand(50,100);
					//$hp = $mhp;
					$msp += $spup;
					$sp = $msp;		
					event_rp_up($spup*2);
					//$rp += $spup*2;	
				}elseif ($rp < 1000){
					$log = ($log . "不知道为什么，你觉得双腿一软……<BR>");
					$spdown = round($rp/4);
					$sp -= $spdown;
					if($sp <= 0){$sp = 1;}
					//$sp = 17;
				}elseif ($rp < 5000 && $killnum == 0){
					$log = ($log . "看见少女离开了，你好奇地向少女身下的那幅不明『绘卷』上看去……<BR>");
					$mhp = $mhp - rand(5,10);
					if($mhp <= 37){$mhp = 37;}
					$hp = 1;
					$msp = $msp - rand(10,20);
					if($msp <= 37){$msp = 37;}
					$sp = 1;
					//$sp = 1;
					$inf = str_replace('h','',$inf);
					$inf = str_replace('b','',$inf);
					$inf = str_replace('a','',$inf);
					$inf = str_replace('f','',$inf);
					$inf = ($inf . 'hbaf');
					$log = ($log . "不能承受绘卷上所述的知识量，你浑身冒血连滚带爬地逃走了。<BR>");
				}elseif ($rp < 5000){
					death_kagari(rand(1,2));
				}else{
					death_kagari(3);
					//$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}
			}elseif ($dice < 6){
				if ($rp < 40){
					$log = ($log . "少女抬头看了你一眼，貌似对你的举动很感兴趣的样子。<BR>");
					event_rp_up(rand(50,100));
					//$rp = $rp + rand(50,100);
				}elseif ($rp < 500){
					$log = ($log . "不知道为什么，你觉得双腿一软……<BR>");
					$hpdown = round($rp/4);
					$hp -= $hpdown;
					if($hp <= 0 ){$hp = 1;}
					//$sp = $sp - 200;			
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "少女的丝带飞到你的面前，<BR>在你的脸上重重地刮了一下。<BR>");
					$inf = str_replace('h','',$inf);
					$inf = ($inf . 'h');
				}elseif ($rp < 1000){
					$log = ($log . "少女的丝带飞到你的面前，<BR>在你的脸上重重地刮了一下。<BR>");
					$inf = str_replace('e','',$inf);
					$inf = ($inf . 'e');
				}elseif ($rp < 5000 && $killnum == 0){
					$log = ($log . "少女的丝带飞到你的面前，<BR>在你的头上重重地敲了一下。<BR>");
					$inf = str_replace('h','',$inf);
					$inf = str_replace('w','',$inf);
					$inf = ($inf . 'hw');
				}elseif ($rp < 5000){
					death_kagari(rand(1,2));
				}else{
					death_kagari(3);
					//$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}elseif ($dice < 9){
				if ($rp < 40){
					$log = ($log . "少女抬头开始注意你的一举一动。<BR>");
					event_rp_up(rand(200,400));
					//$rp = $rp + rand(200,400);
				}elseif ($rp < 500){
					$log = ($log . "少女向你扔来一个保温瓶。<BR>里面是奇怪的深色液体；<BR>你喝了一口，感觉体内有一种力量涌出来。<BR>");
					$mhpup = rand(25,50);
					$mhp = $mhp + $mhpup;
					$hp = $mhp;
					event_rp_up($mhpup*4);
					//$rp += $mhpup*4;
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "你小心翼翼地在少女旁边坐下，（竟然没被她赶走！）<BR>看着她身下的『绘卷』<BR>");
					$hp = round($mhp/10);
					if($hp <= 0){$hp = 1;}
					$sp = round($msp/10);
					if($sp <= 0){$sp = 1;}
	//				$mhp = 400;
	//				$msp = 400;
	//				$hp = 200;
	//				$sp = 200;
					$log = ($log . "当你觉得你看懂了点什么的时候<BR>只见少女用惊讶的眼光盯着你。<BR>这时你才发现你已经七窍流血。<BR>");
					$skillupsum = 0;
					foreach(array('wp','wk','wg','wc','wd','wf') as $val){
						$up = rand(23,34);
						${$val} += $up;
						$skillupsum += $up;
					}
					$rp += $skillupsum*2;
	//				$wp = $wp + rand(75,150);
	//				$wk = $wk + rand(75,150);
	//				$wg = $wg + rand(75,150);
	//				$wc = $wc + rand(75,150);
	//				$wd = $wd + rand(75,150);
	//				$wf = $wf + rand(75,150);
				}elseif ($rp < 1000){
					$log = ($log . "你小心翼翼地在少女旁边坐下，想看看她身下的『绘卷』<BR>结果被红色的丝带正中腿部。<BR>");
	//				$hp = 200;
	//				$sp = 200;
					$hp = round($mhp/8);
					if($hp <= 0){$hp = 1;}
	//				$sp = round($msp/10);
	//				if($sp <= 0){$sp = 1;}
					$inf = str_replace('f','',$inf);
					$inf = ($inf . 'f');
					$log = ($log . "你龇牙咧嘴地逃走了。<BR>");			
				}elseif ($rp < 5000){
					death_kagari(1);
				}elseif ($rp > 5000){
					death_kagari(2);
				}else{
					$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}else{
				if ($rp < 40){
					$log = ($log . "少女飘了起来，并且跟在了你的后面，<BR>太可怕了，还是赶快离开为妙！<BR>");
					event_rp_up(rand(500,1000));
					//$rp = $rp + rand(500,1000);
				}elseif ($rp < 500){
					$log = ($log . "少女瞪了你一眼，你感觉你的生命力被抽干了，<BR>太可怕了，还是赶快离开为妙！<BR>");
					$oldhp = $hp;$oldsp = $sp;
					$hp = 1;
					$sp = 1;
					event_rp_up(-round(($oldhp+$oldsp)/10));
					//$rp = $rp - round(($oldhp+$oldsp)/10);
				}elseif ($rp < 1000 && $killnum == 0){
					$log = ($log . "少女瞪了你一眼，你感觉头晕目眩，<BR>太可怕了，还是赶快离开为妙！<BR>");
					$skilldownsum = 0;
					foreach(array('wp','wk','wg','wc','wd','wf') as $val){
						$down = rand(1,round(${$val}/2));
						${$val} -= $down;
						$skilldownsum += $down;
					}
					event_rp_up(round($skilldownsum/6));
					//$rp -= round($skilldownsum/6);
				}elseif ($rp < 1000){
					$log = ($log . "少女瞪了你一眼，你被一种无形的压力直接压在了地上，<BR>太可怕了，还是赶快离开为妙！<BR>");
					$mhp = round($mhp/2);
					if($mhp <= 37){$mhp = 37;}
					if($hp > $mhp){$hp = $mhp;}
					$msp = round($msp/2);
					if($msp <= 37){$msp = 37;}
					if($sp > $msp){$sp = $msp;}
					//$mhp = $msp = 100;-37rand(500,1000));
					//$rp = $rp - 37;
				}elseif ($rp < 5000){
					death_kagari(1);
				}elseif ($rp > 5000){
					death_kagari(2);
				}else{
					$log = ($log . "少女抬头看了你一眼，随后低下头去继续她的研究。<BR>");
				}		
			}
		}
		elseif($flag == 1)
		{
			$log .= '明白了少女已经是敌人的你，刻意躲避着少女的追踪。不过至少你不用担心被『绘卷』搞得七窍流血了。<BR>';
		}
		else
		{
			$log .= '在雏菊盛开的山丘上，那个熟悉的身影已消失不见。<br>目光所及之处，徒留野花随风摇曳……<br>';
		}
		$event = 1;
		//echo $rp;
	}elseif ($pls==34){//英灵殿
		global $art,$plsinfo,$gamestate,$hack,$arealist,$areanum;
		if (($art!='Untainted Glory')&&($gamestate != 50)){
			$rpls=-1;
			while ($rpls<0 || $arealist[$rpls]==34){
				if($hack){$rpls = rand(0,sizeof($plsinfo)-1);}
				else {$rpls = rand($areanum+1,sizeof($plsinfo)-1);}
			} 
			$pls=$arealist[$rpls];
			$log.="殿堂的深处传来一个声音：<span class=\"evergreen\">“你还没有进入这里的资格”。</span><br>一股未知的力量包围了你，当你反应过来的时候，发现自己正身处<span class=\"yellow\">{$plsinfo[$pls]}</span>。<br>";
			//if (CURSCRIPT !== 'botservice') $log.="<span id=\"HsUipfcGhU\"></span>";
		}
		$event = 1;
	}else {
	}

	if($hp<=0 && $state < 10){
//		global $now,$alivenum,$deathnum,$name,$state;
//		$hp = 0;
//		$state = 13;
//		addnews($now,'death13',$name,0);
//		$alivenum--;
//		$deathnum++;
//		include_once GAME_ROOT.'./include/system.func.php';
//		save_gameinfo();
		include_once GAME_ROOT . './include/state.func.php';
		death('event');
	}
	return $event;
}


function death_kagari($type){
	global $log,$hp,$inf,$gamestate;
	if($type == 1){
		$log = ($log . "从少女的身上延伸出了红色的丝带，<BR>如巨蟒般将你紧紧地捆住。<BR>");
		if ($gamestate == 50 ){
			$log = ($log . "不过，在你即将被绞碎时，上空射来的奇异光束烧毁了丝带，救了你一命。<BR>少女见状扭头离去了。<br>");
			$inf = str_replace('b','',$inf);
			$inf .= 'b';
			$hp = round($hp/100);
			if($hp <= 0){$hp = 1;}
		}else{
			include_once GAME_ROOT . './include/state.func.php';
			death('kagari1');
			return;
		}	
	}elseif($type == 2){
		$log = ($log . "从少女的身上延伸出了红色的丝带，<BR>锋利的丝带朝着你的头部飞来！<BR>");
		if ($gamestate == 50 ){
			$log = ($log . "不过，在你即将身首异处时，上空射来的奇异光束烧毁了丝带，救了你一命。<BR>少女见状扭头离去了。<br>");
			$hp = round($hp/100);
			$inf = str_replace('h','',$inf);
			$inf .= 'h';
			if($hp <= 0){$hp = 1;}
		}else{
			include_once GAME_ROOT . './include/state.func.php';
			death('kagari2');
			return;
		}		
	}elseif($type == 3){
		$log = ($log . "从少女的身上延伸出了红色的丝带，<BR>灼热的丝带朝着你高速飞来！<BR>");
		if ($gamestate == 50 ){
			$log = ($log . "不过，在喷射着岩浆的丝带即将把你融化时，上空射来的奇异光束烧毁了丝带，救了你一命。<BR>少女见状扭头离去了。<br>");
			$hp = round($hp/100);
			$inf = str_replace('u','',$inf);
			$inf .= 'u';
			if($hp <= 0){$hp = 1;}
		}else{
			include_once GAME_ROOT . './include/state.func.php';
			death('kagari3');
			return;
		}	
	}else{
		return;
	}	
}

function event_rp_up($rpup){
	global $rp,$club,$skills;
	if($club != 19 || $rpup <= 0){
		$rp += $rpup;
	}else{
		include_once GAME_ROOT.'./include/game/clubskills.func.php';
		$rpdec = 30;
		//$rpdec += get_clubskill_rp_dec($club,$skills);
		$rp += round($rpup*(100-$rpdec)/100);
	}
	return;
}
?>
