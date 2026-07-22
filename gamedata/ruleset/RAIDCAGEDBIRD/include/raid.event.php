<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

/*
 * RAID event and boss state machine.
 * RAID 事件与首领状态机。
 *
 * 本文件只处理行动计数、追击、充能与清图。路线奖励、封印部件投送和面板
 * 由 raid.routes.php 处理，两者通过文中受 function_exists 保护的钩子协作。
 * This file owns action counting, pursuit, charging and map wipes. Route rewards,
 * seal-part delivery and panels are owned by raid.routes.php and connected through
 * guarded hooks below.
 */

/**
 * 读取兼容新旧键名的配置值。
 * Read a configuration value while accepting the early design aliases.
 */
function raid_event_cfg($section, $keys, $default = null)
{
	$cfg = raid_get_config($section);
	if (!is_array($keys)) $keys = Array($keys);
	foreach ($keys as $key) {
		if (array_key_exists($key, $cfg)) return $cfg[$key];
	}
	return $default;
}

/**
 * 为 news/chat 输出地点名，配置未载入时回退到数字编号。
 * Return a location label for reports, falling back to its numeric id.
 */
function raid_event_location_label($pls)
{
	global $plsinfo;
	$pls = intval($pls);
	return isset($plsinfo[$pls]) ? $plsinfo[$pls] : (string)$pls;
}

/**
 * 取得并补齐本轮的 RAID 事件状态。
 * Return and normalize this round's RAID event state.
 */
function &raid_event_state()
{
	global $gamevars;
	if (!is_array($gamevars)) $gamevars = Array();
	if (empty($gamevars['raid']) || !is_array($gamevars['raid'])) $gamevars['raid'] = Array();
	$state = &$gamevars['raid'];

	$defaults = Array(
		'event_initialized' => 0,
		'boss_spawned' => 0,
		'boss_pid' => 0,
		'boss_pls' => -1,
		'boss_charging' => 0,
		'boss_charge_remaining' => 0,
		'boss_action_counter' => 0,
		'boss_target_pid' => 0,
		'target_name' => '',
		'boss_turns' => 0,
		'boss_wipes' => 0,
		'npc_wipe_deaths' => 0,
		'boss' => Array(
			'pid' => 0,
			'pls' => -1,
			'charging' => 0,
			'charge_remaining' => 0,
			'target_pid' => 0,
			'target_name' => '',
		),
	);
	foreach ($defaults as $key => $value) {
		if (!isset($state[$key])) $state[$key] = $value;
	}
	if (!is_array($state['boss'])) $state['boss'] = $defaults['boss'];
	return $state;
}

/**
 * 不经过 save_gameinfo() 的重算逻辑，只持久化 gamevars。
 * Persist gamevars without invoking save_gameinfo's aggregate recount.
 */
function raid_event_save_state()
{
	global $db, $gtablepre, $groomid, $gamevars;
	if (!isset($db) || !isset($gtablepre)) return;
	$room_id = intval(isset($groomid) ? $groomid : 0);
	$json = json_encode($gamevars, JSON_UNESCAPED_UNICODE);
	$db->array_update("{$gtablepre}game", Array('gamevars' => $json), "groomid='{$room_id}'");
}

/**
 * 新回合时重置事件状态；保留路线层稍后添加的其他键。
 * Reset event-owned keys for a new round while retaining route-owned keys.
 */
function raid_event_initialize_round()
{
	$state = &raid_event_state();
	$reset = Array(
		'event_initialized' => 1,
		'boss_spawned' => 0,
		'boss_pid' => 0,
		'boss_pls' => -1,
		'boss_charging' => 0,
		'boss_charge_remaining' => 0,
		'boss_action_counter' => 0,
		'boss_target_pid' => 0,
		'target_name' => '',
		'boss_turns' => 0,
		'boss_wipes' => 0,
		'npc_wipe_deaths' => 0,
		'boss' => Array(
			'pid' => 0,
			'pls' => -1,
			'charging' => 0,
			'charge_remaining' => 0,
			'target_pid' => 0,
			'target_name' => '',
		),
	);
	foreach ($reset as $key => $value) $state[$key] = $value;
	raid_event_save_state();
	return $state;
}

/**
 * 将 clbpara 安全解码成数组。
 * Decode clbpara safely before adding RAID markers.
 */
function raid_event_prepare_clbpara(&$data)
{
	if (!isset($data['clbpara']) || !is_array($data['clbpara'])) {
		$data['clbpara'] = function_exists('get_clbpara')
			? get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : '')
			: raid_decode_itempara(isset($data['clbpara']) ? $data['clbpara'] : '');
	}
	if (!is_array($data['clbpara'])) $data['clbpara'] = Array();
	if (empty($data['clbpara']['raid']) || !is_array($data['clbpara']['raid'])) {
		$data['clbpara']['raid'] = Array();
	}
}

/**
 * 兼容资源层完成前后的首领识别方式。
 * Recognize the boss across both early and final resource layouts.
 */
function raid_event_is_boss($data)
{
	if (!is_array($data)) return false;
	// 事件侧同样以技能为主，使直击清除等整套机制可被其他外壳复用。
	// Event behavior is also skill-first so other shells can reuse the complete execution package.
	if (function_exists('raid_is_boss') && raid_is_boss($data)) return true;
	$boss_type = intval(raid_event_cfg('boss', 'type', 94));
	return intval(isset($data['type']) ? $data['type'] : -1) === $boss_type;
}

/**
 * 判定清图豁免的容器。
 * Identify containers that are immune to the wipe.
 */
function raid_event_is_container($data)
{
	if (!is_array($data)) return false;
	$cfg = raid_get_config('containers');
	$container_type = intval(isset($cfg['type']) ? $cfg['type'] : 93);
	return intval(isset($data['type']) ? $data['type'] : -1) === $container_type;
}

/**
 * 在配置的安全地点中选择一个首领刷新点。
 * Choose a non-forbidden standard spawn point for the boss.
 */
function raid_event_choose_spawn_location()
{
	$safe = raid_get_config('safe_locations');
	if (empty($safe) || !is_array($safe)) {
		global $plsinfo;
		$safe = isset($plsinfo) && is_array($plsinfo) ? array_keys($plsinfo) : Array(1);
	}
	$safe = array_values(array_unique(array_map('intval', $safe)));

	// 【代码源流】不可被首领进入。 / The Code Source is boss-proof.
	$source_pls = intval(raid_event_cfg('pve', 'source_location', 121));
	$safe = array_values(array_diff($safe, Array($source_pls)));

	// 刷新时尽量不落入当前禁区；刷新后追击不再受禁区限制。
	// Avoid current forbidden zones on spawn; pursuit itself ignores them afterwards.
	if (function_exists('get_safe_plslist')) {
		$current_safe = array_map('intval', get_safe_plslist(0));
		$intersection = array_values(array_intersect($safe, $current_safe));
		if (!empty($intersection)) $safe = $intersection;
	}
	if (empty($safe)) $safe = Array(1);
	return intval($safe[array_rand($safe)]);
}

/**
 * 创建暴走笼中鸟，但不立即行动。
 * Spawn the rampaging bird without granting an immediate turn.
 */
function raid_spawn_boss(&$current_data = null)
{
	global $db, $tablepre, $now;
	$state = &raid_event_state();
	if (!empty($state['boss_spawned'])) return intval($state['boss_pid']);

	$boss_cfg = raid_get_config('boss');
	$type = intval(isset($boss_cfg['type']) ? $boss_cfg['type'] : 94);
	$sub = intval(isset($boss_cfg['sub']) ? $boss_cfg['sub'] : 0);
	$spawn_pls = raid_event_choose_spawn_location();
	$custom = Array(
		'pls' => $spawn_pls,
		'clbpara' => Array(
			'raid' => Array(
				'role' => 'boss',
				'immune_to_wipe' => true,
				'charging' => 0,
			),
			'skill' => Array('raid_cagedbird'),
		),
	);
	$ids = function_exists('addnpc') ? addnpc($type, $sub, 1, 0, $custom) : Array();
	$pid = !empty($ids) && is_array($ids) ? intval(reset($ids)) : 0;

	// 在局部测试或异常资源缺失时，不写入虚假的 spawned 状态。
	// Do not claim a spawn succeeded when addnpc/resource initialization failed.
	if ($pid <= 0) return 0;

	$state['boss_spawned'] = 1;
	$state['boss_pid'] = $pid;
	$state['boss_pls'] = $spawn_pls;
	$state['boss_charging'] = 0;
	$state['boss_charge_remaining'] = 0;
	$state['boss_action_counter'] = 0;
	$state['boss_target_pid'] = 0;
	$state['target_name'] = '';
	$state['boss'] = Array(
		'pid' => $pid,
		'pls' => $spawn_pls,
		'charging' => 0,
		'charge_remaining' => 0,
		'target_pid' => 0,
		'target_name' => '',
	);
	raid_event_save_state();

	if (function_exists('addnews')) addnews($now, 'raid_boss_spawn', raid_event_location_label($spawn_pls));
	if (function_exists('systemputchat')) {
		systemputchat($now, 'raid_bird_spawn', '异常警报：暴走笼中鸟已进入地图。');
	}
	return $pid;
}

/**
 * 取得当前首领数据，并修复可恢复的状态脱节。
 * Fetch the live boss and repair recoverable runtime drift.
 */
function raid_event_fetch_boss()
{
	global $db, $tablepre;
	$state = &raid_event_state();
	$boss = Array();
	$pid = intval($state['boss_pid']);
	if ($pid > 0) {
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='{$pid}' LIMIT 1");
		if ($db->num_rows($result)) $boss = $db->fetch_array($result);
	}
	if (empty($boss)) {
		$type = intval(raid_event_cfg('boss', 'type', 94));
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE type='{$type}' ORDER BY pid DESC LIMIT 1");
		if ($db->num_rows($result)) $boss = $db->fetch_array($result);
	}
	if (!empty($boss)) {
		raid_event_prepare_clbpara($boss);
		$state['boss_spawned'] = 1;
		$state['boss_pid'] = intval($boss['pid']);
		$state['boss_pls'] = intval($boss['pls']);
		$state['boss']['pid'] = $state['boss_pid'];
		$state['boss']['pls'] = $state['boss_pls'];
	}
	return $boss;
}

/**
 * 取得存活玩家列表，用当前命令中未保存的引用覆盖数据库旧值。
 * Fetch living players and overlay the not-yet-saved current command data.
 */
function raid_event_fetch_living_players(&$current_data = null)
{
	global $db, $tablepre;
	$current_pid = is_array($current_data) ? intval(isset($current_data['pid']) ? $current_data['pid'] : 0) : 0;
	$players = Array();
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE hp>0 AND type=0");
	while ($row = $db->fetch_array($result)) {
		if ($current_pid > 0 && intval($row['pid']) === $current_pid) continue;
		$players[] = $row;
	}
	if ($current_pid > 0 && intval(isset($current_data['type']) ? $current_data['type'] : 0) === 0
		&& intval(isset($current_data['hp']) ? $current_data['hp'] : 0) > 0) {
		$players[] = $current_data;
	}
	return $players;
}

/**
 * 按概率开始充能。
 * Start charging according to the configured probability.
 */
function raid_event_try_start_charging(&$boss)
{
	global $now;
	$rate = intval(raid_event_cfg('boss', Array('charge_start_rate', 'charge_chance'), 30));
	$roll = function_exists('diceroll') ? diceroll(99) : rand(0, 99);
	if ($roll >= max(0, min(100, $rate))) return false;

	$turns = max(1, intval(raid_event_cfg('boss', 'charge_turns', 3)));
	$state = &raid_event_state();
	$state['boss_charging'] = 1;
	$state['boss_charge_remaining'] = $turns;
	$state['boss_pls'] = intval($boss['pls']);
	$state['boss']['charging'] = 1;
	$state['boss']['charge_remaining'] = $turns;

	raid_event_prepare_clbpara($boss);
	$boss['clbpara']['raid']['charging'] = 1;
	$boss['clbpara']['raid']['charge_remaining'] = $turns;
	$charging_icon = raid_event_cfg('boss', 'charging_icon', '201a');
	if ($charging_icon !== '') $boss['icon'] = $charging_icon;
	if (function_exists('player_save')) player_save($boss);

	if (function_exists('addnews')) addnews($now, 'raid_boss_charge', raid_event_location_label($boss['pls']), $turns);
	if (function_exists('systemputchat')) {
		systemputchat($now, 'raid_bird_charge', '紧急警报：暴走笼中鸟开始充能！');
	}
	return true;
}

/**
 * 切换首领充能标记与图标。
 * Clear charge markers and restore the normal boss icon.
 */
function raid_event_finish_charging(&$boss)
{
	$state = &raid_event_state();
	$state['boss_charging'] = 0;
	$state['boss_charge_remaining'] = 0;
	$state['boss']['charging'] = 0;
	$state['boss']['charge_remaining'] = 0;
	raid_event_prepare_clbpara($boss);
	$boss['clbpara']['raid']['charging'] = 0;
	$boss['clbpara']['raid']['charge_remaining'] = 0;
	$boss['icon'] = raid_event_cfg('boss', 'icon', 201);
	if (function_exists('player_save')) player_save($boss);
}

/**
 * 执行首领的一个回合。
 * Execute one boss turn.
 */
function raid_boss_take_turn(&$current_data = null)
{
	global $db, $tablepre, $now;
	$state = &raid_event_state();
	if (empty($state['boss_spawned'])) return false;
	$boss = raid_event_fetch_boss();
	if (empty($boss)) return false;
	$state['boss_turns'] = intval($state['boss_turns']) + 1;

	// 充能期间原地停留，最后一步引爆当前地图。
	// While charging the boss stays put; the last charge step wipes its map.
	if (!empty($state['boss_charging'])) {
		$remaining = max(0, intval($state['boss_charge_remaining']) - 1);
		$state['boss_charge_remaining'] = $remaining;
		$state['boss']['charge_remaining'] = $remaining;
		raid_event_prepare_clbpara($boss);
		$boss['clbpara']['raid']['charge_remaining'] = $remaining;
		if ($remaining > 0) {
			if (function_exists('player_save')) player_save($boss);
			if (function_exists('addnews')) addnews($now, 'raid_boss_charge', raid_event_location_label($boss['pls']), $remaining);
			raid_event_save_state();
			return 'charging';
		}

		$wipe_pls = intval($boss['pls']);
		raid_event_finish_charging($boss);
		raid_boss_map_wipe($wipe_pls, $current_data, $boss);
		$state['boss_wipes'] = intval($state['boss_wipes']) + 1;
		raid_event_save_state();
		return 'wipe';
	}

	$players = raid_event_fetch_living_players($current_data);
	$high_rate = intval(raid_event_cfg('boss', 'target_highest_rp_rate', 70));
	$target = raid_choose_boss_target($players, $high_rate);
	$graph = raid_get_config('route_graph');
	$from = intval($boss['pls']);
	$to = $from;
	$moved = false;

	if (!empty($target)) {
		$state['boss_target_pid'] = intval($target['pid']);
		$state['target_name'] = isset($target['name']) ? $target['name'] : '';
		$state['boss']['target_pid'] = $state['boss_target_pid'];
		$state['boss']['target_name'] = $state['target_name'];
		$to = raid_next_route_step($from, intval($target['pls']), $graph);
	} else {
		// 所有人都在隐藏组时，沿现有邻接边随机游走。
		// If everybody is hidden, roam across a random adjacent edge.
		$state['boss_target_pid'] = 0;
		$state['target_name'] = '';
		$state['boss']['target_pid'] = 0;
		$state['boss']['target_name'] = '';
		$neighbours = isset($graph[$from]) && is_array($graph[$from]) ? $graph[$from] : Array();
		$source_pls = intval(raid_event_cfg('pve', 'source_location', 121));
		$neighbours = array_values(array_diff(array_map('intval', $neighbours), Array($source_pls)));
		if (!empty($neighbours)) $to = intval($neighbours[array_rand($neighbours)]);
	}

	$source_pls = intval(raid_event_cfg('pve', 'source_location', 121));
	if ($to !== $from && $to !== $source_pls) {
		$boss['pls'] = $to;
		$state['boss_pls'] = $to;
		$state['boss']['pls'] = $to;
		$moved = true;
		if (function_exists('player_save')) player_save($boss);
		if (function_exists('addnews')) addnews($now, 'raid_boss_move', raid_event_location_label($to), $state['target_name']);
	}

	if ($moved) raid_event_try_start_charging($boss);
	raid_event_save_state();
	return $moved ? 'moved' : 'wait';
}

/**
 * 记录一次已由命令层确认成功的普通移动/探索。
 * Record a successful normal move/search already validated by the command layer.
 */
function raid_record_valid_action(&$data, $command = '')
{
	$action_cfg = raid_get_config('action');
	$counted = isset($action_cfg['counted_commands']) && is_array($action_cfg['counted_commands'])
		? $action_cfg['counted_commands'] : Array('move', 'search');
	if ($command !== '' && !in_array($command, $counted, true)) return false;
	if (intval(isset($data['type']) ? $data['type'] : 0) !== 0) return false;
	if (isset($data['pass']) && $data['pass'] === 'bot') return false;

	raid_init_player_state($data);
	$state = &raid_event_state();
	if (empty($state['boss_spawned'])) {
		$threshold = max(1, intval(isset($action_cfg['spawn_actions_per_player'])
			? $action_cfg['spawn_actions_per_player'] : 10));
		$data['clbpara']['raid']['trigger_actions'] = intval($data['clbpara']['raid']['trigger_actions']) + 1;
		if ($data['clbpara']['raid']['trigger_actions'] >= $threshold) {
			// 第10步只用于刷新，不计入刷新后的全局行动槽。
			// The triggering action spawns the boss but does not fill its global action meter.
			return raid_spawn_boss($data) > 0 ? 'spawned' : false;
		}
		return 'counted';
	}

	$threshold = max(1, intval(isset($action_cfg['boss_action_threshold'])
		? $action_cfg['boss_action_threshold'] : 3));
	$state['boss_action_counter'] = intval($state['boss_action_counter']) + 1;
	if ($state['boss_action_counter'] < $threshold) {
		raid_event_save_state();
		return 'counted';
	}
	$state['boss_action_counter'] = 0;
	$result = raid_boss_take_turn($data);
	raid_event_save_state();
	return $result;
}

/**
 * 检查地图物品是否为封印部件。
 * Check whether a map-item row is a seal part.
 */
function raid_event_is_seal_part_item($item)
{
	return function_exists('raid_get_part_id_from_item') && raid_get_part_id_from_item($item) !== '';
}

/**
 * 清理一个地点的地图物品与陷阱。
 * Remove ordinary map items and every trap at one location.
 */
function raid_event_clear_ground($pls, $destroy_parts = false)
{
	global $db, $tablepre;
	$pls = intval($pls);
	if ($destroy_parts) {
		$db->query("DELETE FROM {$tablepre}mapitem WHERE pls='{$pls}'");
	} else {
		$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls='{$pls}'");
		while ($item = $db->fetch_array($result)) {
			if (raid_event_is_seal_part_item($item)) continue;
			$iid = intval($item['iid']);
			$db->query("DELETE FROM {$tablepre}mapitem WHERE iid='{$iid}'");
		}
	}
	$db->query("DELETE FROM {$tablepre}maptrap WHERE pls='{$pls}'");
}

/**
 * 彻底清空尸体物品与 RAID 收纳账本。
 * Completely erase carried/corpse items and the RAID stash ledger.
 */
function raid_destroy_body(&$data, $move_off_map = true)
{
	$equips = Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art');
	foreach ($equips as $slot) {
		$data[$slot] = '';
		$data[$slot.'k'] = '';
		$data[$slot.'e'] = 0;
		$data[$slot.'s'] = 0;
		$data[$slot.'sk'] = '';
		$data[$slot.'para'] = '';
	}
	for ($i = 0; $i <= 6; $i++) raid_clear_item_slot($data, $i);
	$data['money'] = 0;
	$data['action'] = '';
	$data['bid'] = 0;

	raid_event_prepare_clbpara($data);
	$data['clbpara']['raid']['score'] = 0;
	$data['clbpara']['raid']['stored_items'] = Array();
	$data['clbpara']['raid']['stash_destroyed'] = 1;
	if ($move_off_map) $data['pls'] = 254;
}

/**
 * 在不调用通常击杀奖励/特效链的前提下处理一个清图牺牲者。
 * Resolve one wipe victim without invoking normal kill rewards or NPC side effects.
 */
function raid_event_kill_wipe_victim(&$boss, &$victim, $leave_corpse)
{
	global $now, $alivenum, $deathnum;
	$is_player = intval(isset($victim['type']) ? $victim['type'] : 0) === 0;
	raid_event_prepare_clbpara($victim);

	// 仅复用通常的遗言/news/chat 报告，避开 final_kill_events 的奖励和 NPC 特效。
	// Reuse normal last-word/news/chat reporting, but bypass reward and NPC side-effect chains.
	if (function_exists('pre_kill_events')) {
		if (empty($boss['wep_name'])) $boss['wep_name'] = isset($boss['wep']) ? $boss['wep'] : '异常过载';
		pre_kill_events($boss, $victim, 1, intval(raid_event_cfg('boss', Array('map_wipe_state'), 62)));
	} elseif (function_exists('addnews')) {
		addnews($now, 'raid_bird_wipe_kill', $victim['name'], $victim['type'], $boss['name']);
	}

	$victim['hp'] = 0;
	$victim['state'] = intval(raid_event_cfg('boss', Array('map_wipe_state'), 62));
	$victim['bid'] = intval(isset($boss['pid']) ? $boss['pid'] : 0);
	$victim['action'] = '';
	$victim['endtime'] = $now;
	$victim['deathtime'] = $now;
	$victim['clbpara']['raid']['bird_wipe'] = 1;

	if ($is_player) {
		$alivenum = max(0, intval($alivenum) - 1);
		$deathnum = intval($deathnum) + 1;
	} else {
		// 这一标记供 save_gameinfo 重算钩子扣除，确保 NPC 不推进全局死亡计数。
		// The save-game recount hook subtracts marked NPCs from global deathnum.
		$victim['clbpara']['raid']['exclude_deathnum'] = 1;
		$state = &raid_event_state();
		$state['npc_wipe_deaths'] = intval($state['npc_wipe_deaths']) + 1;
	}

	if (!$leave_corpse) {
		if (function_exists('raid_recover_seal_parts_from_player')) {
			raid_recover_seal_parts_from_player($victim, 'wipe_no_corpse', intval($victim['pls']));
		}
		raid_destroy_body($victim, true);
	}
	if (function_exists('player_save')) player_save($victim);
}

/**
 * 对历史尸体应用同一留尸配置。
 * Apply the same corpse-retention setting to older corpses on the wiped map.
 */
function raid_event_cleanup_old_corpse(&$corpse, $leave_corpse)
{
	if ($leave_corpse) return;
	if (function_exists('raid_recover_seal_parts_from_player')) {
		raid_recover_seal_parts_from_player($corpse, 'old_corpse_wipe', intval($corpse['pls']));
	}
	raid_destroy_body($corpse, true);
	if (function_exists('player_save')) player_save($corpse);
}

/**
 * 暴走笼中鸟的地图消灭行动。
 * Execute the Rampaging Caged Bird map wipe.
 */
function raid_boss_map_wipe($pls, &$current_data = null, $boss_data = null)
{
	global $db, $tablepre, $now, $alivenum, $gamestate;
	$pls = intval($pls);
	$source_pls = intval(raid_event_cfg('pve', 'source_location', 121));
	if ($pls === $source_pls) return Array('players' => 0, 'npcs' => 0);

	$boss = is_array($boss_data) ? $boss_data : raid_event_fetch_boss();
	if (empty($boss)) return Array('players' => 0, 'npcs' => 0);
	$boss_pid = intval(isset($boss['pid']) ? $boss['pid'] : 0);
	$current_pid = is_array($current_data) ? intval(isset($current_data['pid']) ? $current_data['pid'] : 0) : 0;

	$destroy_parts = (bool)raid_event_cfg('boss', Array('destroy_seal_parts_on_wipe'), false);
	if (function_exists('raid_handle_seal_parts_on_wipe')) {
		raid_handle_seal_parts_on_wipe($pls, $current_data);
	}
	raid_event_clear_ground($pls, $destroy_parts);

	$leave_players = (bool)raid_event_cfg('boss',
		Array('leave_player_corpses_on_wipe', 'leave_player_corpses'), true);
	$leave_npcs = (bool)raid_event_cfg('boss',
		Array('leave_npc_corpses_on_wipe', 'leave_npc_corpses'), true);
	$counts = Array('players' => 0, 'npcs' => 0);
	$victims = Array();
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='{$pls}'");
	while ($row = $db->fetch_array($result)) {
		$pid = intval($row['pid']);
		if ($pid === $boss_pid || ($current_pid > 0 && $pid === $current_pid)) continue;
		$victims[] = $row;
	}
	if ($current_pid > 0 && intval(isset($current_data['pls']) ? $current_data['pls'] : -1) === $pls) {
		$victims[] = &$current_data;
	}

	foreach ($victims as &$victim) {
		if (raid_event_is_boss($victim) || raid_event_is_container($victim)) continue;
		$is_player = intval(isset($victim['type']) ? $victim['type'] : 0) === 0;
		$leave = $is_player ? $leave_players : $leave_npcs;
		if (intval(isset($victim['hp']) ? $victim['hp'] : 0) <= 0) {
			raid_event_cleanup_old_corpse($victim, $leave);
			continue;
		}
		raid_event_kill_wipe_victim($boss, $victim, $leave);
		if ($is_player) $counts['players']++;
		else $counts['npcs']++;
	}
	unset($victim);
	// 尸体被清空时，路线层立即扫描并重投不再可达的部件。
	// Immediately reconcile parts made unreachable by corpse erasure.
	if (function_exists('raid_reconcile_seal_parts')) raid_reconcile_seal_parts($current_data);

	if (function_exists('addnews')) addnews($now, 'raid_boss_wipe', raid_event_location_label($pls), $counts['players'], $counts['npcs']);
	if (function_exists('systemputchat')) {
		systemputchat($now, 'raid_bird_wipe', '地图消失报告：暴走笼中鸟已清空当前区域。');
	}

	// 以数据库为准复核活人数，避免当前命令尚未 player_save 的旧值干扰。
	// Recount live players after persisting the overlaid current command data.
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp>0 AND type=0");
	$alivenum = $db->num_rows($result);
	// 同步存活/死亡汇总及NPC排除修正，不能等到下一次普通房间维护。
	// Persist alive/death aggregates and the NPC exclusion correction immediately.
	if (function_exists('save_gameinfo')) save_gameinfo();
	if ($alivenum <= 0 && intval(isset($gamestate) ? $gamestate : 0) >= 10 && function_exists('gameover')) {
		gameover($now, 'end1');
	}
	return $counts;
}

/**
 * 处理首领正面命中后的机制杀：不留尸、不留物品、不留账本。
 * Finalize a direct-hit mechanism kill: no corpse, items or stash ledger survive.
 */
function raid_handle_boss_direct_kill(&$boss, &$victim, $active = 0)
{
	global $now;
	if (!raid_event_is_boss($boss)) return false;
	raid_event_prepare_clbpara($victim);
	$victim['clbpara']['raid']['bird_direct_hit'] = 1;
	$victim['state'] = intval(raid_event_cfg('boss', Array('direct_hit_state'), 61));
	$victim['hp'] = 0;
	$victim['action'] = '';
	$victim['bid'] = intval(isset($boss['pid']) ? $boss['pid'] : 0);
	$victim['endtime'] = $now;
	$victim['deathtime'] = $now;
	if (function_exists('raid_recover_seal_parts_from_player')) {
		raid_recover_seal_parts_from_player($victim, 'bird_direct_hit', intval(isset($victim['pls']) ? $victim['pls'] : 0));
	}
	raid_destroy_body($victim, true);
	if (function_exists('raid_reconcile_seal_parts')) raid_reconcile_seal_parts($victim);
	return true;
}

/**
 * 从 save_gameinfo 重算结果中扣除被鸟清图的 NPC。
 * Subtract bird-wiped NPCs from save_gameinfo's aggregate death count.
 */
function raid_correct_deathnum($raw_deathnum = null)
{
	global $db, $tablepre, $deathnum;
	$raw = $raw_deathnum === null ? intval(isset($deathnum) ? $deathnum : 0) : intval($raw_deathnum);
	if (!isset($db) || !isset($tablepre)) return max(0, $raw);
	$excluded = 0;
	$result = $db->query("SELECT clbpara FROM {$tablepre}players WHERE type<>0 AND (hp<=0 OR state>=10)");
	while ($row = $db->fetch_array($result)) {
		$para = function_exists('get_clbpara') ? get_clbpara($row['clbpara']) : raid_decode_itempara($row['clbpara']);
		if (!empty($para['raid']['exclude_deathnum'])) $excluded++;
	}
	$corrected = max(0, $raw - $excluded);
	if ($raw_deathnum === null) $deathnum = $corrected;
	return $corrected;
}

?>
