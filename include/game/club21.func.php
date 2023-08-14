<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//提取代码片段逻辑
	function item_extract_trait($which, $item_position)
	{
		include_once GAME_ROOT.'./gamedata/club21cfg.php';
		//去掉string which的最后一位
		$which = substr($which, 0, -1);

		global $log, $mode, $club, $sp, $hp, $exp;
		
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		
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
		
		if (isset($clbpara['skillpara']['c21_sacrifice']['active'])) $sacrifice_flag = $clbpara['skillpara']['c21_sacrifice']['active'];

		// 判断itmk是否以'D'或'W'开头
		if (strpos($itmk, 'D') === 0 || strpos($itmk, 'W') === 0) {
			// 给代码片段命名
			if ($which == 'itm') {
				if (mb_strpos($itm, '■') === false)
				{
					$namefrags = item_extract_namefrag_check($itm);					
					if (!empty($namefrags)) {
						// 技能「涌血」的任务判定
						if (isset($clbpara['skillpara']['c21_discovery']['frag'])) $discover_frag = $clbpara['skillpara']['c21_discovery']['frag'];
						if (mb_strpos($itm, $discover_frag) !== false)
						{
							$log .= "你成功找到了字段<span class='yellow'>「" . $discover_frag . "」</span>。<br>";
							$clbpara['skillpara']['c21_discovery']['frag'] = '暂无';
							if (empty($clbpara['skillpara']['c21_discovery']['count'])) $clbpara['skillpara']['c21_discovery']['count'] = 1;
							else
							{
								$clbpara['skillpara']['c21_discovery']['count'] += 1;
								if ($clbpara['skillpara']['c21_discovery']['count'] >= 4) $clbpara['skillpara']['c21_discovery']['rank'] = 2;
								if ($clbpara['skillpara']['c21_discovery']['count'] == 7)
								{
									$log .= '你成功克服了「斥血」的妨碍。<br>';
									$exp = $exp + 888;
								}
							}						
						}						
						$itm = "🥚" . $namefrags . '🥚的代码片段';
						$itmk = '🥚';
						$itme = '0';
						$itms = '1';
						$itmsk = '';
						$log .= '成功将物品转换为代码片段。<br>';
						return;
					}
					else {
						$log .= '该物品无法转换为代码片段。<br>';
						return;
					}
				}
				else {
					$log .= '该物品无法转换为代码片段。<br>';
					return;
				}
			} elseif ($which == 'itme') {
				$sp_cost = $itme_extract_rate * $itme;
				if ($sp < $sp_cost) {					
					if (!empty($sacrifice_flag))
					{
						$result = extract_sacrifice($sp_cost);
						if (isset($result))
						{
							if ($result == -1)
							{
								include_once GAME_ROOT . './include/state.func.php';
								death('club21_burnout');
								return;
							}
							else{
								$sp_cost = $result;
							}
						}
					}
					else
					{
						$log .= '体力不足，无法转换为代码片段。<br>';
							return;
					}
				}
				//$itm = "效果" . ${$which . $item_position} . '代码片段';
				$itm = '🥚' . $oriitm . '🥚的效果代码片段';
				$log .= '消耗体力' . $sp_cost . '点。<br>';
				$sp -= $sp_cost;
			} elseif ($which == 'itms') {
				//如果itms为∞
				if ($itms == '∞') {
					$sp_cost = $itms_infinite_extract_rate * 1;
					if ($sp < $sp_cost)
					{					
						if (!empty($sacrifice_flag))
						{
							$result = extract_sacrifice($sp_cost);
							if (isset($result))
							{
								if ($result == -1)
								{
									include_once GAME_ROOT . './include/state.func.php';
									death('club21_burnout');
									return;
								}
								else{
									$sp_cost = $result;
								}
							}
						}
						else
						{
							$log .= '体力不足，无法转换为代码片段。<br>';
								return;
						}
					}
					$itm = '🥚' . $oriitm . '🥚的耐久代码片段';
					$log .= '消耗体力' . $sp_cost . '点。<br>';
					$sp -= $sp_cost;
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
				$sp_cost = $itms_extract_rate * $itms;
				if ($sp < $sp_cost)
				{					
					if (!empty($sacrifice_flag))
					{
						$result = extract_sacrifice($sp_cost);
						if (isset($result))
						{
							if ($result == -1)
							{
								include_once GAME_ROOT . './include/state.func.php';
								death('club21_burnout');
								return;
							}
							else{
								$sp_cost = $result;
							}
						}
					}
					else
					{
						$log .= '体力不足，无法转换为代码片段。<br>';
							return;
					}
				}
				//$itm = "耐久" . ${$which . $item_position} . '代码片段';
				$itm = '🥚' . $oriitm . '🥚的耐久代码片段';
				$log .= '消耗体力' . $sp_cost . '点。<br>';
				$sp -= $sp_cost;
			} elseif ($which == 'itmsk') {
				preg_match_all('/./u', $itmsk, $matches);
				//var_dump($matches);
				//如果matches没有
				if (empty($matches[0])) {
					$log .= '该物品无法转换为代码片段。<br>';
					return;
				}
				$sp_cost = 0;
				foreach ($matches[0] as $single_itmsk) {
					if (isset($itmsk_extract_rate[$single_itmsk])) {
						$sp_cost += 1 * $itmsk_extract_rate[$single_itmsk];
					}
				}
				if ($sp < $sp_cost)
				{					
					if (!empty($sacrifice_flag))
					{
						$result = extract_sacrifice($sp_cost);
						if (isset($result))
						{
							if ($result == -1)
							{
								include_once GAME_ROOT . './include/state.func.php';
								death('club21_burnout');
								return;
							}
							else{
								$sp_cost = $result;
							}
						}
					}
					else
					{
						$log .= '体力不足，无法转换为代码片段。<br>';
							return;
					}
				}
				//$itm = "属性" . ${$which . $item_position} . '代码片段';
				$itm = '🥚' . $oriitm . '🥚的属性代码片段';
				$log .= '消耗体力' . $sp_cost . '点。<br>';
				$sp -= $sp_cost;
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
	function item_add_trait($choice1, $choice2)
	{
		include_once GAME_ROOT.'./gamedata/club21cfg.php';
		//var_dump($choice1, $choice2);
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
		//检查itmk1是否为🥚,itmk2是否为D或W开头或者是否为🥚
		if ($itmkc1 != '🥚' || (strpos($itmkc2, 'D') !== 0 && strpos($itmkc2, 'W') !== 0 && ($itmkc2 !== '🥚'))) {
			$log .= '该物品无法合并。<br>';
			return;
		}
		//让itm2属性合并itm1
		//如果都是🥚，则重新计算字段组合
		if ($itmkc1 == '🥚' && $itmkc2 == '🥚') {
			//var_dump($itmkc1, $itmkc2);
			//var_dump($itmc1, $itmc2);
			$itmn_result = item_extract_namefrag_check($itmc1.$itmc2);
			if (!empty($itmn_result))
			{
				$itmc2 = '🥚' . $itmn_result . '🥚复合代码片段';
			}
			else {
				$itmc2 = '🥚复合代码片段🥚';
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
			$itmkc2 = '🥚';
			$log .= '合并了代码片段。<br>';
			//清空itm1
			destory_single_item($pdata, $choice1);
			return;
		}
		elseif ($rage < 50 ) {
			$log .= '怒气不足，无法合并代码片段。<br>';
			return;
		}
		$rage -= 50;
		//提取字段
		$itmn_result = item_extract_namefrag_check($itmc1);
		//计算字段倍率
		$namefrag_total_rate = item_add_namefrag_check($itmn_result);
		if (!empty($itmn_result)) 
		{
			//拼接装备名，用🥚作为分界线
			if (strpos($itmc2, '🥚') !== false)
			{				
				$loc = mb_strpos($itmc2, '🥚');
				$itmn_result = $itmn_result. mb_substr($itmc2, 0, $loc);
				$itmc2 = mb_substr($itmc2, $loc + 1, null);
			}
			$len_ori_name = mb_strlen($itmc2);
			if (mb_strlen($itmn_result) > 29 - $len_ori_name) {
				$itmn_result = mb_substr($itmn_result, 0, 29 - $len_ori_name);
			}
			//删除重复字段
			$itmn_result = namefrag_unique($itmn_result);
			$itmc2 = $itmn_result. '🥚' . $itmc2;
		}
		$itmkc2 = $itmkc1 . $itmkc2;
		$itmec2 = (int)$itmec1 + (int)$itmec2;
		$itmec2 = (int)($itmec2 * $namefrag_total_rate);
		//当任意一个itms为∞
		if ($itmsc1 == '∞' || $itmsc2 == '∞') {
			$itmsc2 = '∞';
		}
		else {
			$itmsc2 = (int)$itmsc1 + (int)$itmsc2 - 1;
		}
		$itmskc2 = $itmskc1 . $itmskc2;
		$log .= '成功插入了代码片段。<br>';
		//清空itm1
		destory_single_item($pdata, $choice1);
		//去除itm2重复的属性
		$itmskc2 = implode(array_unique(str_split($itmskc2)));
		//去除itm2属性里的🥚
		$itmkc2 = str_replace('🥚', '', $itmkc2);
		return;
	}
	
	# 生成随机的字段表
	function generate_name_fragment_list()
	{	
		include GAME_ROOT.'./gamedata/club21cfg.php';
		global $gamevars;		
		$gamevars['name_fragment_list'] = array();
		//对每个等级的字段进行随机抽选
		for ($i = 0; $i < 4; $i++){
			$fragment_keys = array_rand($item_name_fragment_list[$i], $name_fragment_available_num[$i]);
			$fragments = array();
			foreach ($fragment_keys as $key) {
				$fragments[] = $item_name_fragment_list[$i][$key];
			}
			//保存到gamevars
			$gamevars['name_fragment_list'][$i] = $fragments;
		}
		save_gameinfo();
		return $gamevars['name_fragment_list'];
	}

	# 获取道具名中的字段
	function item_extract_namefrag_check($itm)
	{
		include GAME_ROOT.'./gamedata/club21cfg.php';
		global $gamevars;
		//如果未生成名称字段表，则生成
		if(empty($gamevars['name_fragment_list'])) $gamevars['name_fragment_list'] = generate_name_fragment_list($item_name_fragment_list, $name_fragment_available_num);
		//合并所有等级的字段
		$merged_fragment_list = array_merge($gamevars['name_fragment_list'][0], $gamevars['name_fragment_list'][1], $gamevars['name_fragment_list'][2], $gamevars['name_fragment_list'][3]);
		//将存在的字段加入到代码片段名中
		$namefrags = '';
		$len_itm = mb_strlen($itm);
		$pointer = 0;
		while ($pointer < $len_itm) {
			$found = false;
			foreach ($merged_fragment_list as $fragment) {
				$len_frag = mb_strlen($fragment);
				if (mb_substr($itm, $pointer, $len_frag) === $fragment) {				
					$namefrags .= $fragment;
					$pointer += $len_frag;
					$found = true;
					break;
				}
			}
			if (!$found) {
				$pointer++;
			}
		}
		return $namefrags;
	}

	# 根据道具名中的字段计算强化倍率
	function item_add_namefrag_check($itm)
	{
		include GAME_ROOT.'./gamedata/club21cfg.php';
		global $gamevars;
		//插入时，依次检测每个等级的字段，并根据字段获得强化倍率
		$namefrag_total_rate = 1;
		$len_itm = mb_strlen($itm);
		$pointer = 0;
		while ($pointer < $len_itm) {
			$found = false;		
			for ($i = 0; $i < 4; $i++){
				foreach ($gamevars['name_fragment_list'][$i] as $fragment) {
					$len_frag = mb_strlen($fragment);
					if (mb_substr($itm, $pointer, $len_frag) === $fragment) {
						$namefrag_total_rate = $namefrag_total_rate * $name_fragment_rate[$i];
						$pointer += $len_frag;
						$found = true;
						break;
					}
				}
				if ($found) break;
			}
			if (!$found) {
				$pointer++;
			}
		}		
		return $namefrag_total_rate;
	}
	
	# 删除道具名中的重复字段
	function namefrag_unique($itm)
	{
		include GAME_ROOT.'./gamedata/club21cfg.php';
		global $gamevars;
		//合并所有等级的字段
		$merged_fragment_list = array_merge($gamevars['name_fragment_list'][0], $gamevars['name_fragment_list'][1], $gamevars['name_fragment_list'][2], $gamevars['name_fragment_list'][3]);
		//将存在的字段加入到代码片段名中
		$namefrags = array();
		$len_itm = mb_strlen($itm);
		$pointer = 0;
		while ($pointer < $len_itm) {
			$found = false;
			foreach ($merged_fragment_list as $fragment) {
				$len_frag = mb_strlen($fragment);
				if (mb_substr($itm, $pointer, $len_frag) === $fragment) {				
					$namefrags[] = $fragment;
					$pointer += $len_frag;
					$found = true;
					break;
				}
			}
			if (!$found) {
				$pointer++;
			}
		}
		$frags_unique = implode(array_unique($namefrags));
		return $frags_unique;
	}
	
	# 消耗代码片段
	function consume_trait($item_position)
	{		
		global $log, $mode, $club;
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
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		
		extract($data,EXTR_REFS);

		$itme = &${'itme' . $item_position};
		$itms = &${'itms' . $item_position};
		if ($itms === '∞') $itms = 120;
		
		if(empty($clbpara['consumpt'])) $clbpara['consumpt'] = 0;
		$clbpara['consumpt'] = $clbpara['consumpt'] + $itme + $itms;
		destory_single_item($pdata, $item_position);
		$log.="你消耗了该代码片段。<br>";
		return;
	}
	
	function extract_sacrifice($sp_cost)
	{	
		global $log, $sp, $hp, $mhp;
		if ($hp > $mhp){
			$hp_reset_dice = rand(0, 99);
			$hp_difference = $hp - $mhp;
			if ($hp_reset_dice > 90){
				$hp = $mhp;
				$log .= '你体内的<span class="glitchb">数据风暴</span>疯狂卷动，重置了你的生命值！';
			}elseif($hp_reset_dice > 75){
				$hp -= round($hp_difference / 2);
				$log .= '你体内的<span class="glitchb">数据风暴</span>疯狂卷动，消耗了你更多的生命值！';
			}elseif($hp_reset_dice > 50){
				$hp -= round($hp_difference / 3);
				$log .= '你体内的<span class="glitchb">数据风暴</span>疯狂卷动，消耗了你更多的生命值！';
				
			}else{
				$hp -= round($hp_difference / 4);
				$log .= '你体内的<span class="glitchb">数据风暴</span>疯狂卷动，消耗了你更多的生命值！';
			}
		}

		if ($sp + $hp <= $sp_cost)
		{						
			$sacrifice_dice = rand(0, 99);
			$death_obbs = get_skillvars('c21_sacrifice','death_obbs');
			if ($sacrifice_dice < $death_obbs)
			{
				$log .= '你尝试着引导着你体内的<span class="glitchb">数据风暴</span>，让它变成你想要的东西……<br>但什么都没发生。<br>';
				$death_flag = -1;
				return $death_flag;
			}
			else
			{
				$hp_cost = $hp - 1;
				$hp -= $hp_cost;
				$sp_cost = $sp;
				$log .= '你一咬牙关，让<span class="glitchb">数据风暴</span>透支了你的生命，<br>因此消耗生命' . $hp_cost . '点代替了体力消耗。<br>';
			}
		}
		else
		{
			$hp_cost = $sp_cost - $sp;
			$hp -= $hp_cost;
			$sp_cost = $sp;
			$log .= '消耗生命' . $hp_cost . '点代替了体力消耗。<br>';
		}
		return $sp_cost;
	}
	
?>