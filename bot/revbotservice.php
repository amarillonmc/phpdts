<?php

require './include/common.inc.php';
require GAME_ROOT . './include/game.func.php';
require config('combatcfg', $gamecfg);

$botname = '测试用脚本狗';
$botcds = 0.5; //每0.5秒行动一次

$bdata = fetch_playerdata_by_name($botname);

if (!$bdata) {
	echo "bot_not_in_game=1\n";
	exit();
}

if ($gamestate == 0) {
	echo "game_ended=1\n";
	exit();
}

# 初始化bot上次行动时间
if (empty($bdata['clbpara']['action_flag']['lastact']))
	$bdata['clbpara']['action_flag']['lastact'] = $now;
# 初始化bot可行动次数
$action_t = ($now - $bdata['clbpara']['action_flag']['lastact']) / $botcds;
# 初始化bot逻辑判断
while ($action_t > 0 && $bdata['hp'] > 0) {
	player_save($bdata);
	$bdata = fetch_playerdata_by_name($bdata['name']);
	echo "bot开始行动...<br>";
	$bdata['clbpara']['action_flag']['lastact'] = $now;
	$action_t--;
	unset($bmoveto);

	# 判定bot是否需要移动：
	# bot优先移动到有声音的位置
	if ($noisepls && $bdata['pls'] != $noisepls) {
		$bmoveto = $noisepls;
	}
	# 否则检查是否在同一个位置探索超过20次，超过20次则移动到下个位置
	elseif ($bdata['pls'] == 0 || $bdata['clbpara']['action_flag']['sctimes'] > 20 || !empty($bdata['clbpara']['action_flag']['needmove'])) {
		$bmoveto = rand(1, 31);
	}

	# 待补完：直接调用move()
	if (isset($bmoveto)) {
		$bdata['pls'] = $bmoveto;
		unset($bdata['clbpara']['action_flag']['sctimes']);
		unset($bdata['clbpara']['action_flag']['needmove']);
	}

	# 判断是否需要执行探索行为
	$bsearch = 1;

	# 待补完：直接调用search()
	if ($bsearch) {
		echo "bot开始探索...<br>";
		# bot触发事件（暂不可用）
		$bot_event_obbs = -1;
		$event_dice = rand(0, 99);
		if ($event_dice < $event_obbs) {
			include_once GAME_ROOT . './include/game/event.func.php';
			$event_flag = event();
			//触发了事件，中止探索推进
			if ($event_flag)
				goto action_end_flag;
		}

		# bot踩雷（暂不可用）
		$bot_trap_obbs = -1;
		$trap_dice = diceroll(99);
		// 计算陷阱“发现率”
		if ($trap_dice < $bot_trap_obbs) {
			$trapresult = $db->query("SELECT * FROM {$tablepre}maptrap WHERE pls = {$bdata['pls']} ORDER BY itmk DESC");
			$trpnum = $db->num_rows($trapresult);
			//看地图上有没有陷阱	
			if ($trpnum) {
				include_once GAME_ROOT . './include/game/itemmain.func.php';
				$fstrp = $db->fetch_array($trapresult);
				//奇迹雷
				$xtrpflag = $fstrp['itmk'] == 'TOc' ? true : false;
				//计算 或不计算陷阱“触发率”：
				$real_trap_obbs = $xtrpflag ? 100 : calc_real_trap_obbs($bdata, $trpnum);
				//echo "realtrapobbs = {$real_trap_obbs}<br>";
				if ($trap_dice < $real_trap_obbs) {
					if (!$xtrpflag) {
						$itemno = rand(0, $trpnum - 1);
						$db->data_seek($trapresult, $itemno);
						$fstrp = $db->fetch_array($trapresult);
					}
					$bdata['itm0'] = $fstrp['itm'];
					$bdata['itmk0'] = $fstrp['itmk'];
					$bdata['itme0'] = $fstrp['itme'];
					$bdata['itms0'] = $fstrp['itms'];
					$bdata['itmsk0'] = $fstrp['itmsk'];
					$tid = $fstrp['tid'];
					$db->query("DELETE FROM {$tablepre}maptrap WHERE tid='$tid'");
					//itemfind();
					goto action_end_flag;
				}
			}
		}

		# bot遇敌
		$bot_schmode_obbs = 75;
		include_once GAME_ROOT . './include/game/attr.func.php';
		$mode_dice = rand(0, 99);
		if ($mode_dice < $bot_schmode_obbs) {
			echo "bot进入遇敌判定...<br>";
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls={$bdata['pls']} AND pid!={$bdata['pid']}");
			if (!$db->num_rows($result)) {
				echo 'bot发现<span class="yellow">周围一个人都没有</span>，准备离开。<br>';
				$bdata['clbpara']['action_flag']['needmove'] = 1;
				goto action_end_flag;
			}

			$enemynum = $db->num_rows($result);
			$enemyarray = range(0, $enemynum - 1);
			shuffle($enemyarray);

			# 计算bot遇敌率 待整合
			//$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
			$b_find_r = 75;
			global $enemy_obbs, $corpse_obbs, $corpseprotect;
			$find_obbs = $enemy_obbs + $b_find_r;

			# 计算bot是否遇敌
			$meetman_flag = 0;
			include_once GAME_ROOT . './include/game/revattr.func.php';
			foreach ($enemyarray as $enum) {
				$db->data_seek($result, $enum);
				$edata = $db->fetch_array($result);
				if (!$edata['type'] || $gamestate < 50) {
					if ($edata['hp'] <= 0) {
						//直接略过无效尸体
						if ($gamestate >= 40 || ($edata['endtime'] > ($now - $corpseprotect)))
							continue;
						$ret = false;
						foreach (array('money', 'arhs', 'aras', 'arfs', 'arts', 'itms1', 'itms2', 'itms3', 'itms4', 'itms5', 'itms6') as $chkval) {
							if ($edata[$chkval]) {
								$ret = true;
								break;
							}
						}
						if (!$ret)
							continue;
						//计算尸体发现率
						$corpse_dice = rand(0, 99);
						if ($corpse_dice < $corpse_obbs) {
							$meetman_flag = 1;
							break;
						}
					} else {
						//直接略过决斗者
						global $artk;
						if ((!$edata['type']) && ($artk == 'XX') && (($edata['artk'] != 'XX') || ($edata['art'] != $name)) && ($gamestate < 50))
							continue;
						if (($artk != 'XX') && ($edata['artk'] == 'XX') && ($gamestate < 50))
							continue;
						//计算活人发现率
						$hide_r = get_hide_r_rev($bdata, $edata);
						$enemy_dice = diceroll(99);
						//echo "hide_r = {$hide_r} | find_obbs = {$find_obbs} | dice = {$enemy_dice}";
						$meetman_flag = $enemy_dice < ($find_obbs - $hide_r) ? 1 : -1;
						break;
					}
				}
			}
			if ($meetman_flag > 0) {
				echo "bot已遇敌！<br>";
				if ($edata['hp'] > 0) {
					# bot发现发现队友或中立单位（暂无效果）
					/*if($teamID&&(!$fog)&&($gamestate<40)&&($teamID == $edata['teamID']))
					{
					$bid = $edata['pid'];
					$action = 'team'.$edata['pid'];
					include_once GAME_ROOT.'./include/game/battle.func.php';
					findteam($edata);
					return;
					} 
					//发现中立NPC或友军 TODO：把这里条件判断挪到一个函数里
					elseif(isset($edata['clbpara']['post']) && $edata['clbpara']['post'] == $pid)
					{
					$bid = $edata['pid'];
					$action = 'neut'.$edata['pid'];
					include_once GAME_ROOT.'./include/game/revbattle.func.php';
					findneut($edata,1);
					return;
					}*/
					# bot 发现敌人
					include_once GAME_ROOT . './include/game/revbattle.func.php';
					include_once GAME_ROOT . './include/game/revcombat.func.php';
					//刷新敌人时效性状态
					if (!empty($edata['clbpara']['lasttimes']))
						$edata = check_skilllasttimes($edata);
					//计算先攻概率
					$active_r = get_active_r_rev($bdata, $edata);
					$active_dice = diceroll(99);
					//进入战斗
					//先制
					if ($active_dice < $active_r) {
						$action = 'enemy' . $edata['pid'];
						//findenemy_rev($edata);
						rev_combat_prepare($bdata, $edata, 1);
					}
					//挨打
					else {
						rev_combat_prepare($edata, $bdata, 0);
					}
					echo $log;
					//战斗后刷新bdata数据
					$bdata = fetch_playerdata_by_name($bdata['name']);
				} else {
					# bot发现尸体（暂无操作）
					echo "bot发现了{$edata['name']}的尸体。<br>";
					//$bdata['action'] = 'corpse'.$edata['pid'];
					//include_once GAME_ROOT.'./include/game/battle.func.php';
					//findcorpse($edata);
				}
			} elseif ($meetman_flag < 0) {
				echo "bot没有发现敌人，因为敌人隐藏起来了。<br>";
			} else {
				echo 'bot发现<span class="yellow">周围一个人都没有</span>，准备移动到下一张地图。<br>';
				$bdata['clbpara']['action_flag']['needmove'] = 1;
			}
			goto action_end_flag;
		} else {
			# bot发现道具判定（暂无）
			echo "bot发现了道具...<br>";
			//echo "进入道具判定<br>";
			/*$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
			$find_obbs = $item_obbs + $find_r;
			$item_dice = rand(0,99);
			if($item_dice < $find_obbs) {
			$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls = '$pls'");
			$itemnum = $db->num_rows($result);
			if($itemnum <= 0){
			$log .= '<span class="yellow">周围找不到任何物品。</span><br>';
			$mode = 'command';
			return;
			}
			$itemno = rand(0,$itemnum-1);
			$db->data_seek($result,$itemno);
			$mi=$db->fetch_array($result);
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$itm0=$mi['itm'];
			$itmk0=$mi['itmk'];
			$itme0=$mi['itme'];
			$itms0=$mi['itms'];
			$itmsk0=$mi['itmsk'];
			$iid=$mi['iid'];
			$db->query("DELETE FROM {$tablepre}mapitem WHERE iid='$iid'");
			
			if($itms0){
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemfind();
			return;
			} else {
			$log .= "但是什么都没有发现。可能是因为道具有天然呆属性。<br>";
			}
			} else {
			$log .= "但是什么都没有发现。<br>";
			}*/
		}
		goto action_end_flag;
	}
	action_end_flag:
	player_save($bdata);
	$bdata = fetch_playerdata_by_name($bdata['name']);
}
player_save($bdata);
$bdata = fetch_playerdata_by_name($bdata['name']);

?>