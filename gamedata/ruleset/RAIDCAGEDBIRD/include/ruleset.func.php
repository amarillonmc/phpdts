<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * RAID：暴走笼中鸟规则集公共钩子入口
 * RAID: Caged Bird Rampage public RuleSet hook entry point
 */

require_once __DIR__.'/raid.core.php';
if (file_exists(__DIR__.'/raid.event.php')) require_once __DIR__.'/raid.event.php';
if (file_exists(__DIR__.'/raid.routes.php')) require_once __DIR__.'/raid.routes.php';
if (file_exists(__DIR__.'/raid.container.php')) require_once __DIR__.'/raid.container.php';

// 登记一次技能直击并返回不可被通常保护吸收的致死值。
// Mark a skill execution and return a lethal value ordinary protection cannot absorb.
function raid_mark_direct_execution(&$pa, &$pd)
{
	raid_init_player_state($pd);
	$states = raid_get_config('states');
	$death_state = intval(isset($states['direct_hit']) ? $states['direct_hit'] : 61);
	$pd['clbpara']['raid']['bird_direct_hit'] = 1;
	$pd['clbpara']['raid']['bird_direct_attacker'] = intval(isset($pa['pid']) ? $pa['pid'] : 0);
	$pd['gg_flag'] = $death_state;
	return max(1000000000, intval(isset($pd['hp']) ? $pd['hp'] : 0)
		+ intval(isset($pd['mhp']) ? $pd['mhp'] : 0) + 1);
}

// 恢复暴走外壳；供常规复活与绕过复活流程的旧死亡路径共用。
// Restore a rampaging shell for both ordinary revival and legacy death paths that bypass it.
function raid_restore_cagedbird_shell(&$data)
{
	$data['hp'] = max(1, intval(isset($data['mhp']) ? $data['mhp'] : 1));
	$data['sp'] = max(1, intval(isset($data['msp']) ? $data['msp'] : 1));
	$data['state'] = 0;
	$data['bid'] = 0;
	$data['action'] = '';
}

// 种火IV式非战斗免伤。 / Fireseed-IV-style non-combat immunity.
function ruleset_damage_immunity_hook(&$data, $source, $damage)
{
	return raid_is_boss($data) ? 0 : NULL;
}

// 旧自然死亡入口同样取消死亡并回满。 / Legacy natural-death entry also cancels death and fully restores.
function ruleset_natural_death_hook(&$data, $death, $kname = '', $ktype = 0, $annex = '')
{
	if (!raid_is_boss($data)) return NULL;
	raid_restore_cagedbird_shell($data);
	return true;
}

// 避免绕过常规复活的击杀路径先输出错误死亡报告。 / Suppress false death reports on kill paths bypassing revival.
function ruleset_pre_kill_cancel_hook(&$pa, &$pd, $active, $death)
{
	return raid_is_boss($pd) ? true : NULL;
}

// 固定伤害钩子：鸟受到的固定伤害归零；鸟正面命中玩家时登记机制杀。
// Fixed-damage hook: negate fixed damage to the bird and mark direct hits as executions.
function ruleset_get_fix_damage_hook(&$pa, &$pd, $active)
{
	if (raid_is_boss($pd)) return 0;
	if (raid_is_boss($pa) && intval(isset($pd['type']) ? $pd['type'] : 0) === 0) {
		return raid_mark_direct_execution($pa, $pd);
	}
	return NULL;
}

// 最终伤害钩子：技能持有者免疫伤害；其命中玩家时越过通常减伤执行机制杀。
// Final-damage hook: skill holders negate damage and bypass ordinary mitigation when executing players.
function ruleset_final_damage_fix_hook(&$pa, &$pd, $active, $final_damage)
{
	if (raid_is_boss($pd)) return 0;
	if (raid_is_boss($pa) && intval(isset($pd['type']) ? $pd['type'] : 0) === 0) {
		return raid_mark_direct_execution($pa, $pd);
	}
	return NULL;
}

// 复活钩子：鸟的理论死亡被取消并回满；鸟的机制杀禁止其他复活路线介入。
// Revival hook: cancel and fully heal theoretical bird deaths; executions bypass other revivals.
function ruleset_revive_process_hook(&$pa, &$pd, $active)
{
    if (raid_is_boss($pd)) {
		raid_restore_cagedbird_shell($pd);
        return 1;
    }
    $clbpara = isset($pd['clbpara']) ? $pd['clbpara'] : Array();
    if (!is_array($clbpara) && function_exists('get_clbpara')) $clbpara = get_clbpara($clbpara);
    if (raid_is_boss($pa) && !empty($clbpara['raid']['bird_direct_hit'])) return 0;
    return NULL;
}

// 在command.php读取玩家行之前开始原子指令，防止同一玩家并发请求以旧背包覆盖新结果。
// Begin the atomic command before command.php reads the player row to prevent stale self-overwrites.
function ruleset_command_request_begin_hook()
{
	return raid_acquire_command_lock();
}

// 指令开始钩子：先处理RAID专属命令，再标记由玩家明确发起的普通移动/探索。
// Command-start hook: handle RAID commands, then mark player-requested ordinary moves/searches.
function ruleset_command_prepare_hook(&$data, $command)
{
	global $log;
	if (!raid_acquire_command_lock()) {
		$log .= '<span class="red">RAID状态正忙，请重试本次指令。</span><br>';
		return false;
	}
    raid_init_player_state($data);
	unset($data['clbpara']['raid']['eligible_action'], $data['clbpara']['raid']['completed_action']);

    if (function_exists('raid_handle_route_command')) {
        $handled = raid_handle_route_command($data, $command);
        if ($handled === true) return false;
    }

	if (($command === 'move' || $command === 'search')
		&& intval(isset($data['type']) ? $data['type'] : 0) === 0
		&& (!isset($data['pass']) || $data['pass'] !== 'bot')) {
		$data['clbpara']['raid']['eligible_action'] = $command;
    }
    return true;
}

// 真实移动/探索函数成功出口调用本钩子；资格标记可排除强制传送与机器人流程。
// Successful move/search exits call this hook; the eligibility marker excludes forced warps and bots.
function ruleset_move_search_success_hook(&$data, $command, $forced_transport = false)
{
	raid_init_player_state($data);
	if ($forced_transport) return;
	$eligible = isset($data['clbpara']['raid']['eligible_action'])
		? $data['clbpara']['raid']['eligible_action'] : '';
	if ($eligible === $command) $data['clbpara']['raid']['completed_action'] = $command;
}

// 指令结束钩子：只消费真实函数成功出口写入的标记，再刷新任务面板。
// Command-end hook: consume only a marker written by a real successful function exit, then refresh the panel.
function ruleset_command_end_hook(&$data, $command)
{
	global $log;
	// 对话或冷却分支可能未进入prepare；仍须在共享部件扫描前持锁。
	// Dialog/cooldown branches may skip prepare; still lock before shared part reconciliation.
	if (!raid_command_lock_is_held() && !raid_acquire_command_lock()) {
		$log .= '<span class="red">RAID状态正忙，本次任务状态将在下次指令刷新。</span><br>';
		return;
	}
    raid_init_player_state($data);
	$completed = isset($data['clbpara']['raid']['completed_action'])
		? $data['clbpara']['raid']['completed_action'] : '';
	unset($data['clbpara']['raid']['eligible_action'], $data['clbpara']['raid']['completed_action']);
	if (($completed === 'move' || $completed === 'search') && function_exists('raid_record_valid_action')) {
		raid_record_valid_action($data, $completed);
	}

    if (function_exists('raid_reconcile_seal_parts')) raid_reconcile_seal_parts($data);
    if (function_exists('raid_sync_tasks')) raid_sync_tasks($data, false);
}

// 玩家数据落库后才释放锁，使遗体账本、积分和共享路线状态成为同一个原子指令。
// Release only after player_save so corpse ledgers, score and shared route state form one atomic command.
function ruleset_command_request_end_hook()
{
	raid_release_command_lock();
}

function ruleset_command_post_save_hook(&$data, $command)
{
	ruleset_command_request_end_hook();
}

// 每轮资源完成初始化后建立RAID共享状态。
// Establish shared RAID state after the round resources have initialized.
function ruleset_round_init_hook($mode)
{
    if (($mode & 1) && ($mode & 16) && ($mode & 32) && function_exists('raid_event_initialize_round')) {
        raid_event_initialize_round();
        if (function_exists('raid_initialize_pve_route')) raid_initialize_pve_route();
    }
}

// 将原配置中的99号随机点限制在RAID安全地点；容器因此不会刷入隐藏区或代码源流。
// Restrict location 99 randomization to RAID-safe maps so containers never enter hidden maps or Code Source.
function ruleset_should_randomize_npc($configured_pls)
{
    return intval($configured_pls) === 99;
}

function ruleset_get_random_npc_location($map_count = 0)
{
    $safe = raid_get_config('safe_locations');
    if (empty($safe)) $safe = Array(1);
    return intval($safe[array_rand($safe)]);
}

// 被动容器只允许玩家先制接触，不会在遭遇瞬间主动攻击。
// Passive containers always yield initiative and never attack on encounter.
function ruleset_force_player_initiative_hook($enemy, $player = Array())
{
	$containers = raid_get_config('containers');
	return raid_is_container($enemy) && !empty($containers['passive']) ? true : NULL;
}

// 帮助页只展示四种实际容器，并为addnpc视图补齐数量与说明。
// Show only the four real container variants in help and attach addnpc-view descriptions.
function ruleset_npc_help_filter_hook(&$npcinfo, &$npcdescription)
{
	if (!empty($npcinfo[93]['asub']) && is_array($npcinfo[93]['asub'])) {
		$npcinfo[93]['asub'] = array_slice($npcinfo[93]['asub'], 0, 4, true);
	}
	if (!empty($npcdescription[93]['sub']) && is_array($npcdescription[93]['sub'])) {
		$descriptions = $npcdescription[93]['sub'];
		$containers = raid_get_config('containers');
		$total = max(0, intval(isset($containers['count']) ? $containers['count'] : 48));
		$base = intval(floor($total / 4));
		$remainder = $total % 4;
		foreach ($descriptions as $sub_id => &$description) {
			$sub_id = intval($sub_id);
			// 初始化循环按1、2、3、0分配余数。 / Round initialization assigns remainders in 1,2,3,0 order.
			$description['count'] = $base + ($sub_id > 0 && $sub_id <= $remainder ? 1 : 0);
		}
		unset($description);
		$npcdescription[93]['sub'] = $descriptions;
		$npcdescription[93]['asub'] = $descriptions;
	}
}

// 撤离复用退出本轮所需的死亡页管线，但显示为成功结算而不是死亡。
// Extraction reuses the round-exit page pipeline while presenting a successful settlement, not death.
function ruleset_death_page_copy_hook($state)
{
	$states = raid_get_config('states');
	$extraction = isset($states['extraction']) ? intval($states['extraction']) : 63;
	if (intval($state) !== $extraction) return NULL;
	return Array('time_label' => '撤离时间', 'status' => '你已撤离。', 'button' => '撤离完成');
}

// 玩家资料初始化时刷新滑动任务面板，不把普通QUEST当作本模式路线。
// Refresh the sliding task panel during player initialization; ordinary QUEST is not a RAID route.
function ruleset_player_init_hook(&$data)
{
    raid_init_player_state($data);
	$had_lock = raid_command_lock_is_held();
	$can_reconcile = $had_lock || raid_acquire_command_lock();
	if ($can_reconcile && function_exists('raid_reconcile_seal_parts')) {
		// command在读取玩家前已持锁，其data与数据库处于同一快照。
		// command already locked before reading the player, so its data belongs to this snapshot.
		if ($had_lock) raid_reconcile_seal_parts($data);
		// 普通页面是先读玩家再进入本钩子，必须以锁内DB行为真相。
		// Ordinary pages read first and lock later; reconcile from DB rather than their stale snapshot.
		else raid_reconcile_seal_parts();
	}
	if (!$had_lock && $can_reconcile) raid_release_command_lock();
    if (function_exists('raid_sync_tasks')) raid_sync_tasks($data, false);
}

// 道具钩子：拒绝旧结局资源，并把折跃信标/封印部件交给路线模块完整处理。
// Item hook: reject old-ending resources and fully route warp beacons/seal parts to the mode module.
function ruleset_itemuse_hook(&$data, $slot, $item)
{
    global $log, $mode;
    $cfg = raid_get_config();
    if (!empty($cfg['disabled_route_items']) && in_array(isset($item['itm']) ? $item['itm'] : '', $cfg['disabled_route_items'], true)) {
        $log .= '<span class="red">这个异常中已经没有能响应它的执行层；道具没有产生效果。</span><br>';
        $mode = 'command';
        return true;
    }
    if (function_exists('raid_handle_route_itemuse')) {
        return raid_handle_route_itemuse($data, intval($slot), $item) === true;
    }
    return false;
}

// 拾取前后均运行部件防丢检查；真正的背包写入仍由原逻辑负责。
// Reconcile seal parts around pickups while leaving the actual inventory write to core logic.
function ruleset_itemget_hook(&$data)
{
    if (function_exists('raid_reconcile_seal_parts')) raid_reconcile_seal_parts($data);
    if (function_exists('raid_sync_tasks')) raid_sync_tasks($data, false);
}

// 击杀钩子：容器掉落与鸟直接机制杀的清理分别交给对应模块。
// Kill hook: delegate container drops and direct bird execution cleanup to their modules.
function ruleset_player_kill_hook(&$pa, &$pd, $active)
{
    if (raid_is_container($pd) && function_exists('raid_handle_container_kill')) raid_handle_container_kill($pa, $pd, $active);
    if (raid_is_boss($pa)) {
        $clbpara = isset($pd['clbpara']) ? $pd['clbpara'] : Array();
        if (!is_array($clbpara) && function_exists('get_clbpara')) $clbpara = get_clbpara($clbpara);
        if (!empty($clbpara['raid']['bird_direct_hit']) && function_exists('raid_handle_boss_direct_kill')) {
            raid_handle_boss_direct_kill($pa, $pd, $active);
        }
    }
}

// 仅含RAID账本的尸体仍可被探索发现。
// A corpse containing only a RAID ledger remains discoverable.
function ruleset_corpse_has_extra_loot($corpse, $finder = Array())
{
    return raid_player_has_stash($corpse);
}

// 本模式不以最后一名存活者结束；零存活仍立即按无人幸存结束。
// This mode never ends for the last survivor; zero survivors still ends immediately.
function ruleset_should_auto_gameover($alivenum, $context = '')
{
    return intval($alivenum) <= 0;
}

// 清图NPC的尸体不纳入全局deathnum。
// NPC corpses erased by the bird do not count toward global deathnum.
function ruleset_adjust_deathnum_hook($deathnum)
{
    return function_exists('raid_correct_deathnum') ? raid_correct_deathnum($deathnum) : $deathnum;
}

// PvE胜者名单严格采用部署瞬间快照，避免普通队伍结算重新扩张名单。
// Use the deployment-time PvE winner snapshot so ordinary team settlement cannot expand it.
function ruleset_get_team_winners_hook($winmode, $winner_data)
{
    global $gamevars;
    if (intval($winmode) !== 8) return NULL;
    return !empty($gamevars['raid']['winner_names']) && is_array($gamevars['raid']['winner_names'])
        ? array_values($gamevars['raid']['winner_names'])
        : Array(isset($winner_data['name']) ? $winner_data['name'] : '');
}

// RAID专属新闻格式。
// RAID-specific news formatting.
function ruleset_format_news_hook($news, $time, $a = '', $b = '', $c = '', $d = '', $e = '')
{
    $messages = Array(
        'raid_boss_spawn' => '<span class="red">异常警报：暴走笼中鸟已进入幻境，开始追踪报应点数。</span>',
        'raid_boss_move' => '<span class="yellow">暴走笼中鸟沿地图路线移动至'.raid_html($a).'。</span>',
        'raid_boss_charge' => '<span class="red">暴走笼中鸟在'.raid_html($a).'原地充能；距消灭还剩'.intval($b).'次行动。</span>',
        'raid_boss_wipe' => '<span class="red">暴走笼中鸟抹除了'.raid_html($a).'内除容器外的一切。</span>',
        'raid_extract' => '<span class="lime">'.raid_html($a).'携带'.intval($b).'点RAID积分成功撤离。</span>',
        'raid_container_open' => '<span class="yellow">'.raid_html($a).'在'.raid_html($b).'击破容器，散出了'.intval($c).'件回收物。</span>',
        'raid_pve_ready' => '<span class="lime">封印数据完成，芙蓉已向全图投送三件装置部件。</span>',
        'end8' => '<span class="lime">'.raid_html($a).'完成最后部署，暴走笼中鸟已被重新封印。</span>',
        'death61' => '<span class="red">'.raid_html($a).'与暴走笼中鸟正面遭遇，在命中瞬间连同随身物品一起被抹除。</span>',
        'death62' => '<span class="red">'.raid_html($a).'被暴走笼中鸟释放的地图消灭数据洪流吞没。</span>',
    );
    if (!isset($messages[$news])) return NULL;
    return '<li>'.date('H时i分s秒', intval($time)).'，'.$messages[$news].'<br>'."\n";
}

?>
