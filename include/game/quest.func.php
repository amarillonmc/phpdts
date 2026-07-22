<?php
if (!defined('IN_GAME')) {
	exit('Access Denied');
}

// QUEST system core helpers / QUEST系统核心函数

include_once GAME_ROOT.'./include/resources.func.php';
include_once GAME_ROOT.'./include/game/itemmain.func.php';
include_once GAME_ROOT.'./include/system.func.php';

// 初始化任务容器 / Initialize quest container
function quest_init_state(&$clbpara)
{
	if (!is_array($clbpara)) {
		$clbpara = get_clbpara($clbpara);
	}
	if (empty($clbpara['quest']) || !is_array($clbpara['quest'])) {
		$clbpara['quest'] = array();
	}
	if (empty($clbpara['quest']['active'])) $clbpara['quest']['active'] = array();
	if (empty($clbpara['quest']['completed'])) $clbpara['quest']['completed'] = array();
	if (empty($clbpara['quest']['failed'])) $clbpara['quest']['failed'] = array();
	if (empty($clbpara['quest']['cooldown'])) $clbpara['quest']['cooldown'] = array();
}

// 获取任务配置 / Fetch quest config
function quest_get_config()
{
	return get_questcfg();
}

// 获取任务分配配置值 / Get quest assignment config value
function quest_get_global_value($questcfg_global, $key, $default)
{
	return isset($questcfg_global[$key]) ? intval($questcfg_global[$key]) : $default;
}

// 检查任务是否可接取 / Check whether a quest can be accepted
function quest_is_available($qid, $cfg, &$data, $check_requirements = true, $check_obbs = true)
{
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);

	if (!empty($clbpara['quest']['active'][$qid])) return false;
	if (empty($cfg['repeatable']) && !empty($clbpara['quest']['completed'][$qid])) return false;

	if ($check_requirements && !empty($cfg['assign'])) {
		$acfg = $cfg['assign'];
		if (!empty($acfg['min_lvl']) && $lvl < $acfg['min_lvl']) return false;
		if (!empty($acfg['min_ss']) && $ss < $acfg['min_ss']) return false;
		if ($check_obbs && !empty($acfg['obbs']) && rand(0, 99) >= $acfg['obbs']) return false;
	}

	return true;
}

// 获取可接任务候选 / Get available quest candidates
function quest_get_available_candidates(&$data, $check_requirements = true, $check_obbs = true)
{
	$candidates = array();
	list($questcfg,) = quest_get_config();
	if (empty($questcfg)) return $candidates;

	foreach ($questcfg as $qid => $cfg) {
		if (quest_is_available($qid, $cfg, $data, $check_requirements, $check_obbs)) {
			$candidates[$qid] = array(
				'id' => $qid,
				'title' => !empty($cfg['title']) ? $cfg['title'] : $qid,
				'tier' => !empty($cfg['tier']) ? $cfg['tier'] : 'normal',
				'desc' => !empty($cfg['steps'][1]['desc']) ? $cfg['steps'][1]['desc'] : '',
			);
		}
	}

	return $candidates;
}

// 设置任务接取邀请 / Set quest offer prompt
function quest_set_offer($candidates, &$data, $source = 'wander')
{
	global $now, $mode, $log;
	if (empty($candidates)) return false;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);

	$qids = array_keys($candidates);
	$qid = $source == 'debug' ? '' : $qids[array_rand($qids)];
	$clbpara['quest']['pending'] = array(
		'source' => $source,
		'qid' => $qid,
		'candidates' => $source == 'debug' ? $candidates : array($qid => $candidates[$qid]),
		'time' => $now,
	);
	$mode = 'quest';
	if ($source == 'debug') {
		$log .= '<span class="yellow">QUEST调试终端列出了可用任务。</span><br>';
	} else {
		$log .= '<span class="yellow">你发现了一份新的QUEST委托。</span><br>';
	}
	return true;
}

// 接受任务邀请 / Accept quest offer
function quest_accept_offer($qid, &$data)
{
	global $log;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);

	if (empty($clbpara['quest']['pending'])) {
		$log .= '<span class="yellow">没有待处理的QUEST委托。</span><br>';
		return false;
	}
	$pending = $clbpara['quest']['pending'];
	if (empty($qid) && !empty($pending['qid'])) $qid = $pending['qid'];
	if (empty($qid) || empty($pending['candidates'][$qid])) {
		$log .= '<span class="yellow">请选择有效的QUEST。</span><br>';
		return false;
	}

	list($questcfg, $questcfg_global) = quest_get_config();
	$max_active = quest_get_global_value($questcfg_global, 'max_active', 1);
	if (count($clbpara['quest']['active']) >= $max_active) {
		$log .= '<span class="yellow">你已经有正在进行的QUEST了。</span><br>';
		unset($clbpara['quest']['pending']);
		return false;
	}
	if (empty($questcfg[$qid]) || !quest_is_available($qid, $questcfg[$qid], $data, false, false)) {
		$log .= '<span class="yellow">这份QUEST现在无法接取。</span><br>';
		unset($clbpara['quest']['pending']);
		return false;
	}

	unset($clbpara['quest']['pending']);
	$clbpara['quest']['cooldown']['assign_steps'] = 0;
	$clbpara['quest']['cooldown']['reject_steps'] = 0;
	quest_start($qid, $data);
	return true;
}

// 拒绝任务邀请 / Reject quest offer
function quest_reject_offer(&$data, $apply_cooldown = true)
{
	global $now, $log;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list(, $questcfg_global) = quest_get_config();

	unset($clbpara['quest']['pending']);
	$clbpara['quest']['cooldown']['assign_steps'] = 0;
	if ($apply_cooldown) {
		$reject_steps = quest_get_global_value($questcfg_global, 'reject_cooldown_steps', 5);
		$reject_time = quest_get_global_value($questcfg_global, 'reject_cooldown', 0);
		$clbpara['quest']['cooldown']['reject_steps'] = $reject_steps;
		if ($reject_time > 0) $clbpara['quest']['cooldown']['assign'] = $now + $reject_time;
		$log .= '<span class="yellow">你拒绝了这份QUEST委托。</span><br>';
	} else {
		$log .= '<span class="yellow">你关闭了QUEST选择。</span><br>';
	}
}

// 调试入口：列出全部可用任务 / Debug entry: list all available quests
function quest_debug_offer(&$data)
{
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list(, $questcfg_global) = quest_get_config();
	$max_active = quest_get_global_value($questcfg_global, 'max_active', 1);
	if (count($clbpara['quest']['active']) >= $max_active) return false;

	$candidates = quest_get_available_candidates($data, false, false);
	return quest_set_offer($candidates, $data, 'debug');
}

// 探索/移动时尝试分配任务 / Try to assign quest during search or movement
function quest_try_assign(&$data)
{
	global $now, $log, $gamestate, $mode, $cmd;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	// 只对玩家生效 / Players only
	if (!empty($data['type']) || $data['hp'] <= 0) return;
	if (!empty($data['pass']) && $data['pass'] == 'bot') return;
	// 游戏开始后才分配 / Assign only after game start
	if ($gamestate < 10) return;
	if (!empty($cmd) || (!empty($mode) && $mode != 'command')) return;

	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($questcfg)) return;

	$max_active = !empty($questcfg_global['max_active']) ? $questcfg_global['max_active'] : 1;
	if (count($clbpara['quest']['active']) >= $max_active) return;

	if (!empty($clbpara['quest']['cooldown']['assign']) && $now < $clbpara['quest']['cooldown']['assign']) {
		return;
	}

	if (!empty($clbpara['quest']['pending'])) return;
	if (!empty($itms0)) return;

	if (!empty($clbpara['quest']['cooldown']['reject_steps'])) {
		$clbpara['quest']['cooldown']['reject_steps']--;
		return;
	}

	$assign_threshold = quest_get_global_value($questcfg_global, 'offer_threshold', 3);
	if ($assign_threshold > 1) {
		if (empty($clbpara['quest']['cooldown']['assign_steps'])) $clbpara['quest']['cooldown']['assign_steps'] = 0;
		$clbpara['quest']['cooldown']['assign_steps']++;
		if ($clbpara['quest']['cooldown']['assign_steps'] < $assign_threshold) return;
		$clbpara['quest']['cooldown']['assign_steps'] = 0;
	}

	$assign_obbs = quest_get_global_value($questcfg_global, 'assign_obbs', 0);
	if ($assign_obbs > 0 && rand(0, 99) >= $assign_obbs) return;

	$candidates = quest_get_available_candidates($data, true, true);
	if (empty($candidates)) return;
	quest_set_offer($candidates, $data, 'wander');
}

// 任务周期性检查 / Quest periodic tick
function quest_tick(&$data)
{
	global $areanum;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($questcfg)) return;

	// Q2 成功条件：禁区推进且NPC仍存活
	if (!empty($clbpara['quest']['active']['Q2'])) {
		$qstate = &$clbpara['quest']['active']['Q2'];
		if (empty($qstate['ready_to_claim'])) {
			$ban_success = !empty($questcfg['Q2']['ban_success']) ? $questcfg['Q2']['ban_success'] : 1;
			if ($areanum >= $ban_success && !empty($qstate['linked_npc_id'])) {
				if (quest_is_npc_alive($qstate['linked_npc_id'])) {
					$qstate['ready_to_claim'] = 1;
					$qstate['step'] = 2;
					$qstate['step_desc'] = '目标存活，返回交付任务';
				}
			}
		}
	}
}

// 启动任务 / Start quest
function quest_start($qid, &$data)
{
	global $now, $log;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($questcfg[$qid])) return;
	$cfg = $questcfg[$qid];

	$step_desc = !empty($cfg['steps'][1]['desc']) ? $cfg['steps'][1]['desc'] : '';
	$progress_max = 0;
	if (!empty($cfg['candy_need'])) $progress_max = $cfg['candy_need'];
	if (!empty($cfg['cheer_need'])) $progress_max = $cfg['cheer_need'];
	$clbpara['quest']['active'][$qid] = array(
		'id' => $qid,
		'title' => !empty($cfg['title']) ? $cfg['title'] : $qid,
		'tier' => !empty($cfg['tier']) ? $cfg['tier'] : 'normal',
		'step' => 1,
		'step_desc' => $step_desc,
		'progress' => 0,
		'progress_max' => $progress_max,
		'linked_npc_id' => 0,
		'start_time' => $now,
	);

	// 任务分配冷却 / Assign cooldown
	if (!empty($questcfg_global['assign_cooldown'])) {
		$clbpara['quest']['cooldown']['assign'] = $now + $questcfg_global['assign_cooldown'];
	}

	// 发放初始道具 / Give start items
	if (!empty($cfg['items']['start'])) {
		foreach ($cfg['items']['start'] as $item_key) {
			quest_spawn_item($item_key, $data);
		}
	}

	$log .= '<span class="yellow">任务：'.$clbpara['quest']['active'][$qid]['title'].' 已开始。</span><br>';
}

// 生成任务道具 / Spawn quest item
function quest_spawn_item($item_key, &$data, $itmpara_override = array())
{
	global $db, $tablepre, $log;
	$questiteminfo = get_questiteminfo();
	if (empty($questiteminfo[$item_key])) return false;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	$item = $questiteminfo[$item_key];
	$itmpara = $item['itmpara'];
	if (!empty($itmpara_override)) {
		$itmpara = array_merge($itmpara, $itmpara_override);
	}
	$itmpara = json_encode($itmpara, JSON_UNESCAPED_UNICODE);

	// QUEST道具优先进入拾取栏，避免背包满时任务无法继续 / Put QUEST items into pickup slot first.
	if (empty($data['itms0'])) {
		$data['itm0'] = $item['itm'];
		$data['itmk0'] = $item['itmk'];
		$data['itme0'] = $item['itme'];
		$data['itms0'] = $item['itms'];
		$data['itmsk0'] = $item['itmsk'];
		$data['itmpara0'] = $itmpara;
		$log .= '获得了QUEST道具<span class="yellow">'.$item['itm'].'</span>。<br>';
		return true;
	}

	// 拾取栏被占用时放到当前位置，防止覆盖丢失 / If pickup slot is occupied, drop it nearby.
	$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk, itmpara, pls) VALUES ('{$item['itm']}', '{$item['itmk']}', '{$item['itme']}', '{$item['itms']}', '{$item['itmsk']}', '{$itmpara}', '{$data['pls']}')");
	$iid = $db->insert_id();
	if (function_exists('check_add_searchmemory')) {
		check_add_searchmemory($iid, 'itm', $item['itm'], $data);
	}
	$log .= 'QUEST道具<span class="yellow">'.$item['itm'].'</span>出现在了附近。<br>';
	return true;
}

// 召唤任务NPC / Summon quest NPC
function quest_spawn_npc($qid, &$data, $purpose = 'summon', $extra_clbpara = array(), $override = array())
{
	global $now;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($questcfg[$qid]['npc']['summon'])) return 0;
	$npc_cfg = $questcfg[$qid]['npc']['summon'];

	$npc_clbpara = array(
		'quest_id' => $qid,
		'linked_player_id' => $pid,
		'quest_purpose' => $purpose,
	);
	if (!empty($extra_clbpara)) {
		$npc_clbpara = array_merge($npc_clbpara, $extra_clbpara);
	}

	$anpcdata = array('clbpara' => $npc_clbpara);
	if (!empty($override)) {
		foreach ($override as $okey => $oval) {
			$anpcdata[$okey] = $oval;
		}
	}

	$ids = addnpc($npc_cfg['type'], $npc_cfg['sub'], 1, $now, $anpcdata);
	return !empty($ids[0]) ? $ids[0] : 0;
}

// 任务完成 / Complete quest
function quest_complete($qid, &$data, $reason = '')
{
	global $now, $log;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($clbpara['quest']['active'][$qid])) return;
	$cfg = !empty($questcfg[$qid]) ? $questcfg[$qid] : array();

	$clbpara['quest']['completed'][$qid] = array('time' => $now, 'reason' => $reason);
	unset($clbpara['quest']['active'][$qid]);

	// 奖励道具 / Reward items
	// Q2 使用专用凭证提交，避免重复发放 / Q2 uses a turn-in token, skip auto reward items
	if ($qid != 'Q2' && !empty($cfg['items']['reward'])) {
		foreach ($cfg['items']['reward'] as $item_key) {
			quest_spawn_item($item_key, $data);
		}
	}

	// 奖励数值 / Reward stats
	if (!empty($cfg['reward']['money'])) $money += $cfg['reward']['money'];
	if (!empty($cfg['reward']['exp'])) $exp += $cfg['reward']['exp'];

	$log .= '<span class="lime">任务完成。</span><br>';
}

// 任务失败 / Fail quest
function quest_fail($qid, &$data, $reason = '')
{
	global $now, $log;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	if (empty($clbpara['quest']['active'][$qid])) return;
	$clbpara['quest']['failed'][$qid] = array('time' => $now, 'reason' => $reason);
	unset($clbpara['quest']['active'][$qid]);
	$log .= '<span class="red">任务失败。</span><br>';
}

// 检查NPC存活 / Check NPC alive
function quest_is_npc_alive($npc_id)
{
	global $db, $tablepre;
	if (empty($npc_id)) return false;
	$result = $db->query("SELECT hp FROM {$tablepre}players WHERE pid = '$npc_id'");
	if (!$db->num_rows($result)) return false;
	$ndata = $db->fetch_array($result);
	return !empty($ndata['hp']);
}

// 寻找背包中的任务道具 / Find quest item in pack
function quest_find_item_slot($data, $qid, $action)
{
	for ($i = 1; $i <= 6; $i++) {
		if (empty($data['itms'.$i])) continue;
		$para = get_itmpara($data['itmpara'.$i]);
		if (!empty($para['IsQuestItem']) && $para['QuestID'] == $qid && $para['QuestAction'] == $action) {
			return $i;
		}
	}
	return 0;
}

// 检查目标是否为当前玩家的指定QUEST NPC / Check linked QUEST NPC
function quest_is_linked_npc($qid, &$data, &$edata)
{
	if (empty($edata['clbpara'])) return false;
	$edata['clbpara'] = get_clbpara($edata['clbpara']);
	if (empty($edata['clbpara']['quest_id']) || empty($edata['clbpara']['linked_player_id'])) return false;
	return $edata['clbpara']['quest_id'] == $qid && $edata['clbpara']['linked_player_id'] == $data['pid'];
}

// 获取Q7应援战斗显示数据 / Get Q7 cheer battle display data
function quest_get_q7_battle_state(&$data, &$edata)
{
	if (!quest_is_linked_npc('Q7', $data, $edata)) return array();
	$data['clbpara'] = get_clbpara($data['clbpara']);
	if (empty($data['clbpara']['quest']['active']['Q7'])) return array();

	list($questcfg,) = quest_get_config();
	$turn_need = !empty($questcfg['Q7']['turn_need']) ? $questcfg['Q7']['turn_need'] : 3;
	$cheer_need = !empty($questcfg['Q7']['cheer_need']) ? $questcfg['Q7']['cheer_need'] : 600;
	$qstate = $data['clbpara']['quest']['active']['Q7'];
	$cheer_points = !empty($qstate['cheer_points']) ? $qstate['cheer_points'] : 0;
	$cheer_turns = !empty($qstate['cheer_turns']) ? $qstate['cheer_turns'] : 0;
	$cheer_gain = max(1, ceil($cheer_need / $turn_need));

	return array(
		'progress' => $cheer_points,
		'progress_max' => $cheer_need,
		'turns' => $cheer_turns,
		'turn_need' => $turn_need,
		'turns_left' => max(0, $turn_need - $cheer_turns),
		'cheer_gain' => $cheer_gain,
	);
}

// Q7应援行动 / Q7 cheer action
function quest_handle_q7_cheer($command, &$data, &$edata)
{
	global $log, $mode, $db, $tablepre;
	if ($command != 'quest_cheer') return 0;
	if (!quest_is_linked_npc('Q7', $data, $edata)) return 0;

	$data['clbpara'] = get_clbpara($data['clbpara']);
	quest_init_state($data['clbpara']);
	if (empty($data['clbpara']['quest']['active']['Q7'])) {
		$log .= '<span class="yellow">你没有正在进行的握手会任务。</span><br>';
		$data['action'] = ''; $data['bid'] = 0;
		$mode = 'command';
		return 2;
	}

	list($questcfg,) = quest_get_config();
	$turn_need = !empty($questcfg['Q7']['turn_need']) ? $questcfg['Q7']['turn_need'] : 3;
	$cheer_need = !empty($questcfg['Q7']['cheer_need']) ? $questcfg['Q7']['cheer_need'] : 600;
	$cheer_gain = max(1, ceil($cheer_need / $turn_need));
	$qstate = &$data['clbpara']['quest']['active']['Q7'];
	if (empty($qstate['cheer_points'])) $qstate['cheer_points'] = 0;
	if (empty($qstate['cheer_turns'])) $qstate['cheer_turns'] = 0;

	$qstate['cheer_points'] += $cheer_gain;
	$qstate['cheer_turns'] += 1;
	$qstate['progress'] = $qstate['cheer_points'];
	$qstate['progress_max'] = $cheer_need;
	$qstate['step_desc'] = '完成应援挑战';
	$log .= '<span class="lime">你向偶像送出了热烈应援！</span><br>';

	if ($qstate['cheer_points'] >= $cheer_need || $qstate['cheer_turns'] >= $turn_need) {
		$log .= '<span class="lime">应援成功，握手会圆满结束！</span><br>';
		quest_complete('Q7', $data, 'cheer_success');
		$db->query("DELETE FROM {$tablepre}players WHERE pid='{$edata['pid']}'");
		$data['action'] = ''; $data['bid'] = 0;
		$mode = 'command';
		return 2;
	}

	$mode = 'revcombat';
	return 1;
}

// QUEST战斗前事件 / QUEST combat prepare events
function quest_combat_prepare_events(&$pa, &$pd, $active)
{
	global $log, $db, $tablepre;
	// 确定玩家与NPC / Identify player and NPC
	if (!$pa['type'] && !empty($pd['type'])) {
		$player = &$pa;
		$npc = &$pd;
	} elseif (!$pd['type'] && !empty($pa['type'])) {
		$player = &$pd;
		$npc = &$pa;
	} else {
		return 1;
	}

	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	if (empty($npc['clbpara']['quest_id']) || empty($npc['clbpara']['linked_player_id'])) return 1;
	if ($npc['clbpara']['linked_player_id'] != $player['pid']) return 1;

	$player['clbpara'] = get_clbpara($player['clbpara']);
	quest_init_state($player['clbpara']);
	$qid = $npc['clbpara']['quest_id'];

	// Q4: 花束安抚
	if ($qid == 'Q4') {
		$slot = quest_find_item_slot($player, 'Q4', 'comfort');
		if ($slot) {
			$log .= '<span class="yellow">你将花束递给了幻影。</span><br>';
			$log .= '<span class="lime">幻影留下遗言后消散了。</span><br>';
			$player['itms'.$slot]--;
			if ($player['itms'.$slot] <= 0) {
				$player['itm'.$slot] = '';
				$player['itmk'.$slot] = '';
				$player['itmsk'.$slot] = '';
				$player['itmpara'.$slot] = '';
				$player['itme'.$slot] = 0;
				$player['itms'.$slot] = 0;
			}
			quest_complete('Q4', $player);
			// 直接移除NPC，避免进入战斗 / Remove NPC to avoid combat
			$db->query("DELETE FROM {$tablepre}players WHERE pid='{$npc['pid']}'");
			return -1;
		}
	}

	// Q6: 捣蛋鬼直接对话
	if ($qid == 'Q6') {
		list($questcfg,) = quest_get_config();
		$need = !empty($questcfg['Q6']['candy_need']) ? intval($questcfg['Q6']['candy_need']) : 3;
		$need = max(1, $need);
		if (empty($player['clbpara']['quest']['active']['Q6'])) {
			$log .= '<span class="yellow">捣蛋鬼做了个鬼脸，然后溜走了。</span><br>';
			$db->query("DELETE FROM {$tablepre}players WHERE pid='{$npc['pid']}'");
			return -1;
		}
		$qstate = &$player['clbpara']['quest']['active']['Q6'];
		$linked_npc = !empty($qstate['linked_npc_id']) ? intval($qstate['linked_npc_id']) : 0;
		$progress = isset($qstate['progress']) ? intval($qstate['progress']) : 0;

		// 只有当前任务绑定的NPC能够产出糖果，旧NPC与重复请求直接清理。
		// Only the NPC currently linked to the active quest can award candy.
		if ($linked_npc !== intval($npc['pid']) || $progress >= $need) {
			$log .= '<span class="yellow">捣蛋鬼做了个鬼脸，然后溜走了。</span><br>';
			$db->query("DELETE FROM {$tablepre}players WHERE pid='{$npc['pid']}'");
			return -1;
		}

		$log .= '<span class="lime">「被你发现啦！」</span><br>';
		quest_spawn_item('q6_candy', $player);
		$qstate['progress'] = min($need, $progress + 1);
		$qstate['linked_npc_id'] = 0;
		unset($qstate['target_pls']);
		$qstate['step'] = 2;
		$qstate['step_desc'] = '已收集'.$qstate['progress'].'/'.$need.'颗糖果';
		// 直接移除NPC，避免进入战斗 / Remove NPC to avoid combat
		$db->query("DELETE FROM {$tablepre}players WHERE pid='{$npc['pid']}'");
		return -1;
	}

	return 1;
}

// QUEST战斗结果事件 / QUEST attack result events
function quest_attack_result_events(&$pa, &$pd, $active)
{
	global $log;
	if (!$pa['type'] && !empty($pd['type'])) {
		$player = &$pa;
		$npc = &$pd;
	} elseif (!$pd['type'] && !empty($pa['type'])) {
		$player = &$pd;
		$npc = &$pa;
	} else {
		return 1;
	}
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	if (empty($npc['clbpara']['quest_id']) || $npc['clbpara']['linked_player_id'] != $player['pid']) return 1;
	$qid = $npc['clbpara']['quest_id'];

	// Q5: 低血量净化
	if ($qid == 'Q5') {
		list($questcfg,) = quest_get_config();
		$threshold = !empty($questcfg['Q5']['hp_threshold']) ? $questcfg['Q5']['hp_threshold'] : 20;
		$slot = quest_find_item_slot($player, 'Q5', 'purify');
		if ($slot && $npc['hp'] > 0 && $npc['hp'] <= round($npc['mhp'] * $threshold / 100)) {
			$log .= '<span class="yellow">你对实验体使用了净化药剂！</span><br>';
			$player['itms'.$slot]--;
			if ($player['itms'.$slot] <= 0) {
				$player['itm'.$slot] = '';
				$player['itmk'.$slot] = '';
				$player['itmsk'.$slot] = '';
				$player['itmpara'.$slot] = '';
				$player['itme'.$slot] = 0;
				$player['itms'.$slot] = 0;
			}
			quest_complete('Q5', $player);
			// 直接移除NPC，视作撤退 / Remove NPC as retreat
			$npc['quest_retreat'] = 1;
			$npc['action'] = '';
			$npc['clbpara']['quest_retreat'] = 1;
			$npc['hp'] = 1;
			$npc['sp'] = max($npc['sp'], 1);
			return -1;
		}
	}

	// Q7: 应援值累计
	// 只在玩家作为攻击者时计数 / Only count when player is the attacker
	if ($qid == 'Q7' && !empty($player['clbpara']['quest']['active']['Q7']) && $player['pid'] == $pa['pid']) {
		list($questcfg,) = quest_get_config();
		$turn_need = !empty($questcfg['Q7']['turn_need']) ? $questcfg['Q7']['turn_need'] : 3;
		$cheer_need = !empty($questcfg['Q7']['cheer_need']) ? $questcfg['Q7']['cheer_need'] : 600;
		$qstate = &$player['clbpara']['quest']['active']['Q7'];
		if (empty($qstate['cheer_points'])) $qstate['cheer_points'] = 0;
		if (empty($qstate['cheer_turns'])) $qstate['cheer_turns'] = 0;
		$qstate['cheer_points'] += !empty($player['final_damage']) ? $player['final_damage'] : 0;
		$qstate['cheer_turns'] += 1;
		$qstate['progress'] = $qstate['cheer_points'];
		$qstate['progress_max'] = $cheer_need;
		if ($qstate['cheer_points'] >= $cheer_need || $qstate['cheer_turns'] >= $turn_need) {
			$log .= '<span class="lime">应援成功，握手会圆满结束！</span><br>';
			quest_complete('Q7', $player);
			$npc['quest_retreat'] = 1;
			$npc['action'] = '';
			$npc['hp'] = 1;
			return -1;
		}
	}

	return 1;
}

// NPC死亡时的QUEST处理 / QUEST handling on NPC death
function quest_handle_npc_death(&$pa, &$pd)
{
	if (empty($pd['type'])) return;
	$pd['clbpara'] = get_clbpara($pd['clbpara']);
	if (empty($pd['clbpara']['quest_id']) || empty($pd['clbpara']['linked_player_id'])) return;
	$qid = $pd['clbpara']['quest_id'];
	$owner_pid = $pd['clbpara']['linked_player_id'];
	$owner_is_combatant = (!empty($pa['pid']) && $pa['pid'] == $owner_pid);
	if ($owner_is_combatant) {
		$owner = &$pa;
	} else {
		$owner = fetch_playerdata_by_pid($owner_pid);
		if (empty($owner['pid'])) return;
	}
	$owner['clbpara'] = get_clbpara($owner['clbpara']);
	quest_init_state($owner['clbpara']);
	if (empty($owner['clbpara']['quest']['active'][$qid])) return;

	// 判定击杀者 / Determine killer
	$killer_pid = !empty($pa['pid']) ? $pa['pid'] : 0;

	if ($qid == 'Q1') {
		if ($killer_pid == $owner_pid) quest_complete('Q1', $owner);
		else quest_fail('Q1', $owner, 'target_killed_by_other');
	} elseif ($qid == 'Q2') {
		$owner['clbpara']['quest']['active']['Q2']['killer_pid'] = $killer_pid;
		quest_fail('Q2', $owner, 'protected_npc_dead');
	} elseif ($qid == 'Q5') {
		quest_fail('Q5', $owner, 'purify_target_killed');
	} elseif ($qid == 'Q6') {
		// 旧的躲猫猫NPC不应让新一轮Q6失败。
		// A stale hide-and-seek NPC must not fail a newer Q6 target.
		$current_npc = !empty($owner['clbpara']['quest']['active']['Q6']['linked_npc_id'])
			? intval($owner['clbpara']['quest']['active']['Q6']['linked_npc_id']) : 0;
		if ($current_npc === intval($pd['pid'])) {
			quest_fail('Q6', $owner, 'hide_npc_killed');
		}
	} elseif ($qid == 'Q7') {
		if ($killer_pid == $owner_pid) quest_complete('Q7', $owner);
		else quest_fail('Q7', $owner, 'idol_killed_by_other');
	}

	if (!$owner_is_combatant) player_save($owner);
}
