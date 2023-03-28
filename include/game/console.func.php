<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	include_once GAME_ROOT . './include/game/titles.func.php';
	include_once GAME_ROOT . './include/system.func.php';

	# 子面板 —— 控制模块
	$console_tips = Array
	(
		0 => "<span class='red'>※ 警告：",
		1 => "<span class='lime'>※ 反馈：",
		2 => "<span class='grey'>※ 维持该效果将占用一条信道</span>",
	);

	# 天气控制 
	function console_wthchange($w)
	{
		global $clbpara,$gamevars,$now,$log,$weather,$wthinfo,$name,$nick,$mode;
		global $console_tips;

		if(!isset($clbpara['console']))
		{
			$log.= "输入了无效的指令。<br>";
			return;
		}
		elseif(empty($gamevars['api']))
		{
			$log.= "{$console_tips[0]}可用信道不足，无法执行指令。</span><br>";
			return;
		}
		elseif(!array_key_exists($w,$wthinfo))
		{
			$log.= "{$console_tips[0]}输入了非法的天气参数，请检查你提交的指令。</span><br>";
			return;
		}
		else 
		{
			if($weather == 18 || $w == 18)
			{
				$log .= "你像往常一样提交指令后，终端那头却陷入了诡异的沉默中。<br>……这是怎么回事……死机了？<br>";
				return;
			}
			$weather = $w;
			$log .= "提交了检索指令后，你眼前的数据流开始闪烁。<br>与此同时，整处虚拟空间也开始发生变化……<br>
			{$console_tips[1]}已将天气转变为【{$wthinfo[$weather]}】</span><br>
			{$console_tips[2]}<br><br>";
			$gamevars['api'] --;
			save_gameinfo();
			addnews($now, 'csl_wthchange', get_title_desc($nick).' '.$name, $weather);
		}
		return;
	}

	# 检索道具、陷阱、NPC
	function console_searching($kind,$nm,$ntype)
	{
		global $db,$tablepre,$clbpara,$gamevars,$typeinfo,$plsinfo,$hpinfo,$log,$mode;
		global $console_tips;

		$skind = Array(0=>'itm',1=>'trap',2=>'pc');

		//过滤输入名称中的非法字符
		$nm = preg_replace('/[,\#;\p{Cc}]+|锋利的|电气|毒性|[\r\n]|-改|<|>|\"/u','',$nm);
		//过滤输入名称首尾的空格
		$nm = preg_replace('/^\s+|\s+$/m','', $nm);
		//过滤类别
		$kind = (int)$kind; $ntype = (int)$ntype;

		if(!isset($clbpara['console']))
		{
			$log.= "输入了无效的指令。<br>";
			return;
		}
		elseif(empty($gamevars['api']))
		{
			$log.= "{$console_tips[0]}可用信道不足，无法执行指令。</span><br>";
			return;
		}
		elseif(empty($nm) || ($kind == 2 && !array_key_exists($ntype,$typeinfo)))
		{
			$log.= "{$console_tips[0]}输入了非法的命名或类别参数，请检查你提交的指令。</span><br>";
			return;
		}
		elseif(!isset($skind[$kind]))
		{
			$log.= "{$console_tips[0]}输入了非法的检索类别，请检查你提交的指令。</span><br>";
			return;
		}

		if($skind[$kind] == 'pc')
		{
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$nm' AND type = '$ntype' AND hp>0 ");
			$log.="提交了检索指令后，你眼前的数据流开始闪烁……<br>片刻后，稳定下来的数据流";
			if(!$db->num_rows($result)) 
			{ 
				$log.="给出了一个令人失望的结果：<br><br>
				{$console_tips[1]}检索对象【{$typeinfo[$ntype]} {$nm}】并不存在于系统中，或是ta已经死了。</span><br><br>";
				return;
			}
			else 
			{
				$spnums = $db->num_rows($result);
				$log.="打印出了一组数据：<br><br>
				{$console_tips[1]}检索到<span class='clan'>{$spnums}</span>位符合条件的对象，如下所示：</span><br><br>";
			}
			if($db->num_rows($result) > 1)
			{
				$sparr = $spdata = Array();
				while($spdata = $db->fetch_array($result)) 
				{
					$sparr[$spdata['pls']] = isset($sparr[$spdata['pls']]) ? $sparr[$spdata['pls']]+1 : 1;
				}
				foreach($sparr as $spls => $snums)
				{
					$log .="·于<span class='yellow'>【{$plsinfo[$spls]}】</span>检索到<span class='yellow'>【{$snums}】</span>名目标对象；<br>";
				}
			}
			else 
			{
				$spdata = $db->fetch_array($result);
				$snm = $typeinfo[$spdata['type']].' '.$spdata['name']; $spls = $spdata['pls'];
				if($spdata['hp'] < $spdata['mhp']*0.5){$shp = ($spdata['hp'] < $spdata['mhp']*0.2) ? 2 : 1;} else{$shp = 0;}
				$log .="·于<span class='yellow'>【{$plsinfo[$spls]}】</span>检索到目标【{$snm}】<br>目标当前状态：【{$hpinfo[$shp]}】<br>";
			}
		}
		elseif($skind[$kind] == 'itm' || $skind[$kind] == 'trap')
		{
			$tablename = $skind[$kind] == 'itm' ? 'mapitem' : 'maptrap';
			$tipdesc = $skind[$kind] == 'itm' ? '被放置在' : '被埋设于';
			$result = $db->query("SELECT * FROM {$tablepre}{$tablename} WHERE itm = '$nm'");
			$log.="提交了检索指令后，你眼前的数据流开始闪烁……<br>片刻后，稳定下来的数据流";
			if(!$db->num_rows($result)) 
			{ 
				$log.="给出了一个令人失望的结果：<br><br>
				{$console_tips[1]}检索对象【{$nm}】并不存在于系统中。</span><br><br>";
				return;
			}
			else 
			{
				$inums = $db->num_rows($result);
				$log.="打印出了一组数据：<br><br>
				{$console_tips[1]}检索到<span class='clan'>【{$inums}】</span>份符合条件的对象，如下所示：</span><br><br>";
			}
			$sumidata = $idata = Array();
			while($idata = $db->fetch_array($result)) 
			{
				$sumidata[$idata['pls']] = isset($sumidata[$idata['pls']]) ? $sumidata[$idata['pls']]+1 : 1;
			}
			foreach($sumidata as $ipls => $inums)
			{
				$log .="
				<span class='yellow'>【{$inums}】</span>份{$nm}{$tipdesc}<span class='yellow'>【{$plsinfo[$ipls]}】</span>；<br>";
			}
		}
		$log .= "<br>{$console_tips[2]}<br><br>";
		$gamevars['api'] --;
		save_gameinfo();
		return;
	}

	# 禁区控制模块
	function console_areacontrol($kind)
	{
		global $log,$clbpara,$gamevars,$hack,$now,$name,$nick,$areatime,$areawarn;
		global $console_tips;

		$kind = (int)$kind;
		$skind = Array(0=>'hack',1=>'addarea');

		if(!isset($clbpara['console']))
		{
			$log.= "输入了无效的指令。<br>";
			return;
		}
		elseif(!isset($skind[$kind]))
		{
			$log.= "{$console_tips[0]}提交了无效的禁区控制指令，请检查你提交的指令。</span><br>";
			return;
		}
		elseif(empty($gamevars['api']) && $skind[$kind] !== 'hack')
		{
			$log.="{$console_tips[0]}可用信道不足，无法执行指令。</span><br>";
			return;
		}

		if($skind[$kind] == 'hack')
		{
			if(!$hack)
			{
				$log .= "提交指令后，你眼前的数据流开始闪烁。<br>与此同时，整处虚拟空间也开始发生变化……<br>{$console_tips[1]}已解除全部禁区</span><br>";
				$hack = 1;
				movehtm();
				storyputchat($now,'hack');
				addnews($now,'csl_hack',get_title_desc($nick).' '.$name);
				save_gameinfo();
			}
			else 
			{
				$log .= "{$console_tips[0]}当前禁区已被解除，无法重复执行指令。</span><br>";
			}
		}
		elseif($skind[$kind] == 'addarea')
		{
			if(!$areawarn)
			{
				$log .= "提交指令后，你眼前的数据流开始闪烁。<br>与此同时，整处虚拟空间也开始发生变化……<br>{$console_tips[1]}已将下回禁区到来时间调整至5秒后</span><br>{$console_tips[2]}<br><br>";
				$areatime = $now+5;
				addnews($now,'csl_addarea',get_title_desc($nick).' '.$name);
				areawarn();
				save_gameinfo();
			}
			else 
			{
				$log .= "{$console_tips[0]}新一回禁区即将到来，目前无法执行添加禁区指令。</span><br>";
			}
		}
		return;
	}

	# 别按那个按钮！
	function console_dbutton()
	{	
		global $log,$clbpara;

		if(!isset($clbpara['console']) || isset($clbpara['nobutton']))
		{
			$log.= "输入了无效的指令。<br>";
			return;
		}
		include_once GAME_ROOT . './include/game/dice.func.php';
		$button_dice = diceroll(99);
		$log .= "这么大个按钮摆在这！哪会有人能忍住不按呢？<br>你果断出手按下了按钮！<br>……<br>";
		if ($button_dice < 75) 
		{
			$log .= "但是好像什么也没有发生……？<br><br>";
		} 
		elseif($button_dice < 95) 
		{
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$log .= "<span class='yellow'>但是因为你按的太过用力，按钮直接从界面上掉了出来！</span><br>等等……这不对吧！？<br><br>";
			$itm0 = '奇怪的按钮';$itmk0 = 'Z';
			$itme0 = $itms0 = 1;$itmsk0 = '';
			$clbpara['nobutton'] = 1;
			include_once GAME_ROOT . './include/game/itemmain.func.php';
			itemget();
		} 
		else 
		{
			include_once GAME_ROOT . './include/state.func.php';
			$log .= '<span class="red">呜哇，按钮爆炸了！</span><br><br>';
			death ( 'button', '', 0, 'dangerbutton');
		}
		return;
	}
?>
