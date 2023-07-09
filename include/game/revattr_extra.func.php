<?php
namespace revattr
{

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	
	//revattr_extra.func.php: 记录NPC特殊战斗机制...
	//Q：为什么要把每个NPC的特殊战斗机制都新建一个函数保存？
	//A：也不是每个都要这么干……这个做法主要用于存在大段log、多段判定的机制，分离出来一是方便定位这个NPC的相关机制在哪个阶段执行，二是确保原流程的可读性；


	# 真红暮特殊判定
	function attr_extra_19_crimson(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		# 真红暮作为防御方时的事件
		if($pd['type'] == 19 && $pd['name'] == '红暮' && $pa['wep_kind'] != $pd['wep_kind'] && $phase == 'defend')
		{	
			$log .= "<span class=\"red\">红暮身上的武器投射出了防护罩，轻松挡下了{$pa['nm']}的攻击！</span><br>";
			return 0;
		}

		# 真红暮作为进攻方时的事件：
		if($pa['type'] == 19 && $pa['name'] == '红暮' && $phase == 'attack')
		{
			$log .= "<span class=\"yellow\">“那么说好了，不留手咯~”<br></span>";
			//$log .= "红暮吐气扬声，向你袭来！<br>";
			
			if($pa['wep'] != '喷气式红杀重铁剑')
			{
				$event_dice=rand(1,6);
				$log .= "<span class=\"neonred\">只见红暮手上的巨大铁剑带着一条火光向你飞去。</span><br>";
				if($event_dice == 1)
				{
					get_inf_rev($pd,'w');
					get_inf_rev($pd,'u');
					$log .= "<span class=\"yellow\">你被赤红热风扫过，顿感头晕目眩，而且身上也起了火！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">不过你灵活地躲开了赤红热风！</span><br>";
				}
			}
			if($pa['wep'] != '绯红记忆')
			{
				$event_dice=rand(1,8);
				$log .= "<span class=\"neonred\">只见从红暮身边飞出来了一个红色的光球！</span><br>";
				if($event_dice==1)
				{
					$damage = min($pd['hp']-1,round($pd['mhp']*0.5)); //罪不至死
					$pd['hp'] -= $damage;
					//$log .= "<span class=\"yellow\">“虽说我不是什么超能力者，但是最高级的科技也和超能力无异了！”红暮大笑。</span><br>";
					$log .= "<span class=\"yellow\">红色的光球直击你的心脏！</span><br>";
					$log .= "这一发绯红锥心弹对你造成<span class=\"red\">$damage</span>点伤害！你感觉你半条命都没咯~<br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你大呼不妙，连忙侧身躲过了这发绯红锥心弹！</span><br>";
				}
			}
			if($pa['wep'] != '血色强袭')
			{
				$event_dice=rand(1,4);
				$log .= "<span class=\"neonred\">红暮从背后抽出一把重炮，向你扣下了扳机！</span><br>";
				if($event_dice==1)
				{
					$wdamage=rand(5,40);
					weapon_loss($pd,$wdamage,1,1);
					get_inf_rev($pd,'a');
					$log .= "<span class=\"yellow\">这一发强袭追踪弹结实地打到了你手持武器的手上，你痛的龇牙咧嘴，武器也受到了损伤！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你身形一矮，躲过了这发强袭追踪弹。</span><br>";
				}
			}
			if($pa['wep'] != '狮虎丝带')
			{
				$event_dice=rand(1,4);
				//$log .= "<span class=\"yellow\">红暮打了一个响指，从背后飞出来两条丝带！<br>“虽然这种玩意蓝凝应该用的更顺手吧……”</span><br>";
				$log .= "<span class=\"neonred\">红暮打了一个响指，从背后飞出来两条丝带！”</span><br>";
				if($event_dice==1)
				{
					$pd['sp'] = max(0,$pd['sp']-250);
					$log .= "<span class=\"yellow\">丝带将你缠绕，吸收了你的体力！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你赶快腾跃躲避，两条丝带擦身而过！</span><br>";
				}
			}
			if($pa['wep'] != '落樱巨锤')
			{
				$event_dice=rand(1,6);
				//$log .= "<span class=\"yellow\">红暮高高一跃，跳到空中！<br>“询问淑女的体重固然很不礼貌，但我自然不是什么淑女！”</span><br>";
				$log .= "<span class=\"neonred\">红暮高高一跃，跳到空中！</span><br>";
				if($event_dice==1)
				{
					$pd['hp']-=1107;
					$log .= "<span class=\"yellow\">巨大的机甲一下便将你碾压！造成了<span class=\"red\">1107</span>点伤害！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你在地上进行了一次翻滚，躲开了从天而降的机甲！</span><br>";
				}
			}
			if($pa['wep'] != '八八连流星浮游炮')
			{
				$event_dice=rand(1,6);
				//$log .= "<span class=\"yellow\">从红暮的机甲中发射出了大量的火箭弹！<br>“知道吗，量变终究会引起质变！”</span><br>";
				$log .= "<span class=\"neonred\">从红暮的机甲中发射出了大量的火箭弹！</span><br>";
				if($event_dice==1)
				{
					$log .= "<span class=\"yellow\">虽然火箭弹的精度颇低，但是大量的火箭弹还是对你的防具造成了可观的伤害！</span><br>";
					$adamage=rand(5,40);
					foreach(Array('arb','arh','ara','arf') as $ar)
					{
						if(!empty(${$ar.'s'})) armor_hurt($pd,$ar,$adamage,1);
					}
				}
				else
				{
					$log .= "<span class=\"lime\">然而飞弹的精度太低，你并没有被它们打中。</span><br>";
				}
			}
		}

		return NULL;
	}

	# 真蓝凝特殊判定
	function attr_extra_19_azure(&$pa,&$pd,$active,$phase=0)
	{
		global $db,$tablepre,$log;

		if($pd['type'] == 19 && $pd['name'] == '蓝凝')
		{
			$id = $pd['pid'];
			$dice = diceroll(100);
			$ttr="♪臻蓝之愿♪";
			$ttr2="♫钴蓝之灵♫";
			$ttr3="❀矢车菊的回忆❀";
			//$rp=18;
			//不要起这种会和玩家数据混淆的变量名啊喂！
			$rpls = $pa['pls'];
			if ($dice<5) $rpls=rand(1,33);

			$le=diceroll(200)+$pa['mhp']-100;
			if ($le>1001) $le=1001;
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr', 'TO', '$le', '1', '$id', '$rpls')");

			$le=rand(1,200)+$pa['final_damage']-100;
			if ($le>2000) $le=2000;
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr2', 'TO', '$le', '1', '$id', '$rpls')");

			$le=rand(1,$pa['hp']);
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr3', 'TO', '$le', '1', '$id', '$rpls')");
	
			$log .= "从蓝凝的身边飞出了数个光球，散布在了战场上！<br>";
		}
		return;
	}

	# 电子狐特殊判定
	function attr_extra_89_efox(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		# 进攻方(pa)为米娜
		if ($pa['type'] == 89 && $pa['name'] == '电掣部长 米娜')
		{
			$log .= "<span class=\"yellow\">米娜的双眼突然闪耀了起来！</span><br>
			<span class=\"neonblue\">“侦测到敌意实体，开始扫描~”</span><br>";
			$dice = diceroll(1024);
			//$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>/1024。</span><br>";
			if($dice<=126)//8%
			{ 
				$log .= "<span class=\"yellow\">“似乎米娜具现化了你的武器！”</span><br>
				<span class=\"neonblue\">“你的<span class=\"red\">{$pd['wep']}</span>，我就收下了！”</span><br>";
				$pa['wep'] = $pd['wep']; $pa['wepk'] = $pd['wepk']; $pa['wepsk'] = $pd['wepsk'];
				$pa['wepe'] = $pd['wepe']; $pa['weps'] = $pd['weps']; 
				get_wep_kind($pa);
			}
			elseif($dice<=635)
			{
				$dice2 = rand(1,5);
				$log .= "<span class=\"yellow\">“似乎米娜扫描了你的武器！”</span><br>
				<span class=\"neonblue\">“你的<span class=\"red\">{$pd['wep']}</span>，已扫描入<span class=\"red\">$dice2</span>号位。”<br>
				“我会妥善保管的~”</span><br>";
				$pa['itm'.$dice2] = $pd['wep']; $pa['itmk'.$dice2] = $pd['wepk']; $pa['itmsk'.$dice2] = $pd['wepsk'];
				$pa['itme'.$dice2] = $pd['wepe']; $pa['itms'.$dice2] = $pd['weps']; 
			}
			elseif($dice>=1024)  // 1/1024 几率直接抢夺玩家全部背包
			{
				$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
				$log .= "<span class=\"yellow\">“米娜将你的全身扫描了个遍！”</span><br>
				<span class=\"neonblue\">“我判定你身上的东西放到我身上可能更好一点~”<br>
				“我会妥善保管的~”</span><br>";
				for($i=1;$i<=6;$i++)
				{
					if(!empty($pd['itms'.$i]))
					{
						//复制
						$pa['itm'.$i] = $pd['itm'.$i]; $pa['itmk'.$i] = $pd['itmk'.$i]; $pa['itmsk'.$i] = $pd['itmsk'.$i];
						$pa['itme'.$i] = $pd['itme'.$i]; $pa['itms'.$i] = $pd['itms'.$i]; 
						//哎哟喂啊，真是倒霉，但这就是人生啊。
						$pd['itm'.$i] =  $pd['itmk'.$i] =  $pd['itmsk'.$i] = '';
						$pd['itme'.$i] =  $pd['itms'.$i] = 0;
					}
				}
			}
			else
			{
				$log .= "<span class=\"yellow\">不过似乎什么都没发生！</span><br>
				<span class=\"neonblue\">“扫描失败了么……”</span><br>";
			}
		}
		return;
	}

	# 书中虫特殊判定
	function attr_extra_89_bookworm(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pd['type'] == 89)
		{
			$rp_up = 0; $dmg_p = -1;
			if($pd['name'] == '高中生·白神')
			{
				if($phase == 'rp')
				{
					$log .= "<span class=\"yellow\">“你真的愿意对这个手无寸铁的高中女生下手么？”</span><br>";
					$dice = diceroll(444);
					if($dice<=200){
						$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
					}else{
						$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
					}
					$rp_up = $pa['rp'] + $dice;
				}
				elseif($phase == 'defend')
				{
					if ($pa['original_dmg'] > 400)
					{
						$log .= "<span class=\"yellow\">白神从裙底抽出了她的名为WIN MAX 2的微型电脑！<br>“哪能这样被你干打？”</span><br>";
						$log .= "<span class=\"yellow\">白神的高超黑客技术大幅度降低了你造成的伤害！</span><br>";
						$dmg_p = 0.005;
					}
				}
			}
			if ($pd['name'] == '白神·讨价还价')
			{
				if($phase == 'rp')
				{
					$dice = diceroll(1777);
					$log .= "<span class=\"yellow\">“对面似乎真的没有敌意，你还是决定要下手么？”</span><br>";
					if($dice<=200){
						$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
					}elseif($dice<=400){
						$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
					}else{
						$log .= "<span class=\"neonblue\">“罪恶感爬上了你的脊梁！”</span><br>";
					}
					$rp_up = $pa['rp'] + $dice;
				}
				elseif($phase == 'defend')
				{
					if ($pa['original_dmg'] > 400)
					{
						$log .= "<span class=\"yellow\">白神从裙底抽出了她的名为DECK的微型电脑！<br>“哪能这样被你干打？”</span><br>";
						$log .= "<span class=\"yellow\">白神的高超黑客技术大幅度降低了你造成的伤害！</span><br>";
						$dmg_p = 0.005;
					}
				}
			}
			if ($pd['name'] == '白神·接受')
			{
				if($phase == 'rp')
				{
					$dice = rand(1777,4888);
					$log .= "<span class=\"yellow\">“你对一位毫无反抗能力，并且已经表示无敌意的女高中生横下死手。”</span><br>";
					$log .= "<span class=\"neonblue\">“希望你的良心还能得以安生。”</span><br>";
					//$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
					$rp_up = $pa['rp'] + $dice;
				}
			}
			//结算rp上升事件
			if($phase == 'rp' && $rp_up > 0) rpup_rev($pa,$rp_up);
			//返回一个伤害系数
			if($phase == 'defend' && $dmg_p > 0) return $dmg_p;
		}
		return;
	}

	# 百命猫特殊判定
	function attr_extra_89_100lifecat(&$pa,&$pd,$active,$phase=0)
	{
		if ($pa['type'] == 89 && $pa['name']=='是TSEROF啦！')
		{ 
			if($pa['lvl'] < 255) $pa['lvl']++;
			if($pa['rage'] < 255) $pa['rage']++;
		}
		elseif ($pd['type'] == 89 && $pd['name']=='是TSEROF啦！')
		{
			if($pd['lvl'] < 255) $pd['lvl']++;
			if($pd['rage'] < 255) $pd['rage']++;
		}
		return;
	}

	# 笼中鸟特殊判定
	function attr_extra_89_cagedbird(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pa['type'] == 89 && $pa['name'] =='笼中鸟')
		{
			if($pa['statusa'] < 3)
			{
				$continue_flag = 0;
				//70%几率吸收玩家HP值成为自己的HP和SP值，SP值上升到一定程度时变身，变身后各种数值直接膨胀。三段变身。
				$log .= "<span class=\"yellow\">“笼中鸟含情脉脉地看着你！”</span><br>";
				$dice=diceroll(20);
				//$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>。</span><br>";
				if($dice>=14)
				{
					$log .= "<span class=\"yellow\">“你感觉你的生命被她汲取，但同时更有一种奇怪的暖洋洋的舒畅感。”</span><br>";
					//继续投d20，1~10吸收30%，11~19吸收65%，大失败直接吸到1。
					$dice2=rand(1,20);
					//$log .= "<span class=\"yellow\">【DEBUG】骰子2检定结果：<span class=\"red\">$dice2</span>。</span><br>";
					if($dice2<=10){
						$log .= "<span class=\"yellow\">“你稍微稳了稳身形，似乎问题不是很严重。”</span><br>";
						$gain = $pd['hp'] * 0.3;
					}elseif($dice2<=19){
						$log .= "<span class=\"yellow\">“你觉得头晕目眩。”</span><br>";
						$gain = $pd['hp'] * 0.65;
					}elseif($dice2>=20){
						$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
						//哎哟喂啊，真是倒霉，但这就是人生啊。
						$log .= "<span class=\"yellow\">“你整个人都倒了下去，不过想到你的生命力将要打开她的镣铐，这让你充满了决心。”</span><br>";
						$gain = $pd['hp'] - 1;
						$pd['def'] = $pd['def'] + ($gain * 0.25);
					}
					$pa['hp'] = $pa['hp'] + ($gain * 30);
					$pa['mhp']= $pa['mhp'] + ($gain * 30);
					$pa['msp'] = $pa['msp'] + ($gain * 30);
					$pd['hp'] = round($pd['hp'] - $gain);
					$pd['rp'] = round($pd['rp'] - $gain);
					$continue_flag = 1;
				}
				else
				{
					$log .= "<span class=\"yellow\">“不过什么也没有发生！”</span><br>";
				}
			}
			//处理直接变身 在$pa['statusa']加了个限定条件 不然无限变身了
			if($pa['msp']> 5003 && $pa['statusa'] == 0){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被打破了一些。”</span><br>";
				$pa['statusa'] = 1;
				$pa['mhp'] = $pa['mhp'] * 5; $pa['hp'] = $pa['hp'] * 5; $pa['wf'] = $pa['wf'] * 5; $pa['att'] = $pa['att'] * 5; $pa['def'] = $pa['def'] * 5;
			}elseif($pa['msp'] > 13377 && $pa['statusa'] == 1){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被打破了一些。”</span><br>";
				$pa['statusa'] = 2;
				$pa['mhp'] = $pa['mhp'] * 10; $pa['hp'] = $pa['hp'] * 10; $pa['wf'] = $pa['wf'] * 10; $pa['att'] = $pa['att'] * 10; $pa['def'] = $pa['def'] * 10;
			}elseif($pa['msp'] > 33777 && $pa['statusa'] == 2){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被完全打破了！”</span><br>";
				$pa['statusa'] = 3;
				$pa['mhp'] = $pa['mhp'] * 30; $pa['hp'] = $pa['hp'] * 30; $pa['wf'] = $pa['wf'] * 30; $pa['att'] = $pa['att'] * 30; $pa['def'] = $pa['def'] * 30;
				$pa['name'] = $pa['nm'] = "完全解放的鸟儿";
			}
			//成功喂养笼中鸟会跳过战斗
			if($continue_flag) return -1;
		}
		return 0;
	}

	#走地羊特殊判定
	function attr_extra_89_walksheep(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pa['type'] == 89 && $pa['name'] == '坚韧之子·拉姆')
		{
			$event_dice=diceroll(99);
			if($event_dice >=30)
			{
				$log .= "<span class=\"neonblue\">“我这双拳头……很强……很厉害……咚咚打你……”</span><br>";
				$damage=rand(5,40);
				if(!empty($pd['wepe']) && $pd['wepk']!='WN')
				{
					$log .= "攻击使得<span class=\"red\">{$pd['wep']}</span>的效果下降了<span class=\"red\">$damage</span>点！<br>";
					$loss_flag = weapon_loss($pd,$damage,1,1);
					if($loss_flag < 0)
					{
						$pa['money'] = $pa['money'] + ($damage * 120);
					}
				}
				foreach(Array('arb','arh','ara','arf') as $ar)
				{
					if(!empty($pd[$ar.'s']))
					{
						$loss_flag = armor_hurt($pd,$ar,$damage,1);
						if($loss_flag < 0)
						{
							$pa['money'] = $pa['money'] + ($damage * 60);
						}
					}
				}
				$w_money = $w_money + ($damage * 30);
				get_inf_rev($pd,'a');
				get_inf_rev($pd,'f');
				$log .= "致伤攻击使你的<span class=\"red\">腕部</span>和<span class=\"red\">足部</span>受伤了！<br>";
			}
		}
		return;
	}

	# 迷你蜂特殊判定
	function attr_extra_89_minibee(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if ($pa['type'] == 89 && $pa['name'] == '诚心使魔·阿摩尔') // 迷你蜂
		{ 
			$log .= "<span class=\"neonblue\">“这只小蜜蜂勇敢地朝你袭来！”</span><br>";
			$dice = diceroll(4);
			if($dice == 0){
				$log .= "<span class=\"yellow\">魔法蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">麻痹</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'e');
			}elseif($dice == 1){
				$log .= "<span class=\"yellow\">幻惑花粉朝你扑来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">混乱</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'w');
			}elseif($dice == 2){
				$log .= "<span class=\"yellow\">凶猛翼击朝你袭来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">炎上</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'u');
			}elseif($dice == 3){
				$log .= "<span class=\"yellow\">剧毒蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">中毒</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'p');
			}else{
				$log .= "<span class=\"yellow\">体当冲刺朝你袭来！造成了<span class=\"red\">550</span>点伤害！<br>";
				$dmg = 550;
			}
			return $dmg;
		}
		return;
	}

	function attr_ach53_check(&$pa,&$pd,$active)
	{
		if(!empty($pa['arbs']) && $pa['arb'] == '【智代专用熊装】')
		{
			// 必须连续攻击同一个对象
			if(!empty($pa['clbpara']['achvars']['ach503']) && $pa['clbpara']['achvars']['ach503']['a'] == $pd['pid'])
			{
				$pa['clbpara']['achvars']['ach503']['t'] += 1;
			}
			else 
			{
				$pa['clbpara']['achvars']['ach503']['a'] = $pd['pid'];
				$pa['clbpara']['achvars']['ach503']['t'] = 1;
			}
		}
		return;
	}
}
?>