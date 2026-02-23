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

// 探索时尝试分配任务 / Try to assign quest during search
function quest_try_assign(&$data)
{
	global $now, $log, $gamestate;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	// 只对玩家生效 / Players only
	if (!empty($data['type']) || $data['hp'] <= 0) return;
	// 游戏开始后才分配 / Assign only after game start
	if ($gamestate < 10) return;

	extract($data, EXTR_REFS);
	quest_init_state($clbpara);
	list($questcfg, $questcfg_global) = quest_get_config();
	if (empty($questcfg)) return;

	$max_active = !empty($questcfg_global['max_active']) ? $questcfg_global['max_active'] : 1;
	if (count($clbpara['quest']['active']) >= $max_active) return;

	if (!empty($clbpara['quest']['cooldown']['assign']) && $now < $clbpara['quest']['cooldown']['assign']) {
		return;
	}

	$assign_obbs = !empty($questcfg_global['assign_obbs']) ? $questcfg_global['assign_obbs'] : 0;
	if ($assign_obbs > 0 && rand(0, 99) >= $assign_obbs) return;

	$candidates = array();
	foreach ($questcfg as $qid => $cfg) {
		// 已在进行 / Already active
		if (!empty($clbpara['quest']['active'][$qid])) continue;
		// 非可重复且已完成 / Non-repeatable and completed
		if (empty($cfg['repeatable']) && !empty($clbpara['quest']['completed'][$qid])) continue;
		// 触发条件 / Assign conditions
		if (!empty($cfg['assign'])) {
			$acfg = $cfg['assign'];
			if (!empty($acfg['min_lvl']) && $lvl < $acfg['min_lvl']) continue;
			if (!empty($acfg['min_ss']) && $ss < $acfg['min_ss']) continue;
			if (!empty($acfg['obbs']) && rand(0, 99) >= $acfg['obbs']) continue;
		}
		$candidates[] = $qid;
	}

	if (empty($candidates)) return;
	$qid = $candidates[array_rand($candidates)];
	quest_start($qid, $data);
	$log .= '<span class="lime">你接到了新的任务。</span><br>';
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
	global $itm0, $itmk0, $itme0, $itms0, $itmsk0, $itmpara0;
	$questiteminfo = get_questiteminfo();
	if (empty($questiteminfo[$item_key])) return false;
	$item = $questiteminfo[$item_key];
	$itm0 = $item['itm'];
	$itmk0 = $item['itmk'];
	$itme0 = $item['itme'];
	$itms0 = $item['itms'];
	$itmsk0 = $item['itmsk'];
	$itmpara = $item['itmpara'];
	if (!empty($itmpara_override)) {
		$itmpara = array_merge($itmpara, $itmpara_override);
	}
	$itmpara0 = json_encode($itmpara, JSON_UNESCAPED_UNICODE);
	itemget($data);
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
		$log .= '<span class="lime">「被你发现啦！」</span><br>';
		quest_spawn_item('q6_candy', $player);
		if (!empty($player['clbpara']['quest']['active']['Q6'])) {
			$player['clbpara']['quest']['active']['Q6']['progress'] += 1;
		}
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
	if ($qid == 'Q7' && !empty($player['clbpara']['quest']['active']['Q7'])) {
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
	$owner = fetch_playerdata_by_pid($owner_pid);
	if (empty($owner['pid'])) return;
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
		quest_fail('Q6', $owner, 'hide_npc_killed');
	} elseif ($qid == 'Q7') {
		if ($killer_pid == $owner_pid) quest_complete('Q7', $owner);
	}

	player_save($owner);
}
