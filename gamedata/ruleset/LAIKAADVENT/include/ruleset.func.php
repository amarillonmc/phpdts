<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

require_once GAME_ROOT.'./gamedata/ruleset/LAIKAADVENT/laika_1.php';

/*
 * 莱卡的进击 RuleSet 行为逻辑
 * Laika Advent RuleSet behavior
 *
 * 所有持久状态均保存在玩家 clbpara['laika'] 中。
 * All persistent state is stored in clbpara['laika'].
 */

function laika_get_config($section = '')
{
    global $laika_mode_config;

    if ($section !== '' && isset($laika_mode_config[$section])) {
        return $laika_mode_config[$section];
    }
    return $laika_mode_config;
}

function laika_html($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 在整条玩家指令期间持有全局进程锁，保护团队齿轮的JSON读改写。
// Hold the global process lock for the whole player command to protect team-cog JSON read/modify/write cycles.
function laika_acquire_command_lock()
{
    global $laika_command_lock_handle;
    if (is_resource($laika_command_lock_handle)) return true;
    if (!defined('GAME_ROOT')) return false;

    $handle = @fopen(GAME_ROOT.'./gamedata/process.lock', 'ab');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return false;
    }
    $laika_command_lock_handle = $handle;

    // common.inc.php在分派指令前已释放此锁，锁内要重新读取共享游戏状态。
    // common.inc.php released this lock before dispatch, so refresh shared game state inside it.
    if (function_exists('load_gameinfo')) load_gameinfo();
    return true;
}

function laika_command_lock_is_held()
{
    global $laika_command_lock_handle;
    return is_resource($laika_command_lock_handle);
}

function laika_release_command_lock()
{
    global $laika_command_lock_handle;
    if (!is_resource($laika_command_lock_handle)) return;
    flock($laika_command_lock_handle, LOCK_UN);
    fclose($laika_command_lock_handle);
    $laika_command_lock_handle = null;
}

function laika_init_state(&$data)
{
    $cfg = laika_get_config('blessing');

    if (!isset($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : Array());
    }
    if (empty($data['clbpara']['laika']) || !is_array($data['clbpara']['laika'])) {
        $data['clbpara']['laika'] = Array();
    }

    $state = &$data['clbpara']['laika'];
    if (empty($state['version'])) $state['version'] = 1;
    if (!isset($state['actions'])) $state['actions'] = 0;
    if (!isset($state['npc_tax_count'])) $state['npc_tax_count'] = 0;
    if (empty($state['thresholds']) || !is_array($state['thresholds'])) $state['thresholds'] = Array();
    if (empty($state['tax_conditions']) || !is_array($state['tax_conditions'])) $state['tax_conditions'] = Array();
    if (!isset($state['next_blessing'])) {
        $state['next_blessing'] = rand(
            intval($cfg['first_offer_min_actions']),
            intval($cfg['first_offer_max_actions'])
        );
    }
    if (empty($state['progress_snapshot']) || !is_array($state['progress_snapshot'])) {
        $state['progress_snapshot'] = laika_collect_progress_snapshot($data);
    }
}

function laika_collect_progress_snapshot($data)
{
    $snapshot = Array();
    $fields = Array('lvl', 'exp', 'money', 'rp', 'mhp', 'msp', 'att', 'def', 'skillpoint', 'wp', 'wk', 'wg', 'wc', 'wd', 'wf');
    foreach ($fields as $field) {
        if (isset($data[$field])) $snapshot[$field] = $data[$field];
    }
    // 成就、任务和技能进度也属于击杀收益；仅保存配置的相关键。
    // Achievements, quests, and skill progress are kill rewards too; retain only configured related keys.
    if (!empty($data['clbpara']) && is_array($data['clbpara'])) {
        $tax_cfg = laika_get_config('tax');
        $snapshot['_clbpara'] = Array();
        foreach ($tax_cfg['rollback_clbpara_keys'] as $key) {
            $snapshot['_clbpara'][$key] = array_key_exists($key, $data['clbpara']) ? $data['clbpara'][$key] : null;
        }
    }
    return $snapshot;
}

function laika_restore_progress_snapshot(&$data, $snapshot)
{
    if (!is_array($snapshot)) return;
    foreach ($snapshot as $field => $value) {
        if ($field == '_clbpara') continue;
        if (array_key_exists($field, $data)) $data[$field] = $value;
    }
    if (isset($data['mhp'], $data['hp'])) $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
    if (isset($data['msp'], $data['sp'])) $data['sp'] = min(intval($data['sp']), intval($data['msp']));
    if (isset($snapshot['_clbpara']) && is_array($snapshot['_clbpara'])) {
        foreach ($snapshot['_clbpara'] as $key => $value) {
            if ($value === null) unset($data['clbpara'][$key]);
            else $data['clbpara'][$key] = $value;
        }
    }
}

function laika_has_pending_event($data)
{
    return !empty($data['clbpara']['laika']['pending']);
}

function laika_set_dialogue(&$data, $dialogue_id, $noskip = true)
{
    $data['clbpara']['dialogue'] = $dialogue_id;
    if ($noskip) $data['clbpara']['noskip_dialogue'] = 1;
}

function laika_clear_dialogue(&$data)
{
    unset($data['clbpara']['dialogue']);
    unset($data['clbpara']['noskip_dialogue']);
}

function laika_install_dynamic_dialogue(&$data)
{
    global $dialogues, $dialogue_branch, $dialogue_log;

    laika_init_state($data);
    $state = &$data['clbpara']['laika'];

    if (!empty($state['pending']['type']) && $state['pending']['type'] == 'tax') {
        $pending = $state['pending'];
        $demand = $pending['demand'];
        $dialogues['laika_tax'] = Array(
            '<span class="yellow b">【星辰之代价（Astra Pretium）】</span><br><br>白色披风的内侧掠过一片没有温度的星空。<br>莱卡并未看向你，只是在衡量刚刚发生的“前进”。',
            '“前进并非无偿。你的增量已经完成计价。”<br><br><span class="lime">'.laika_html($pending['description']).'</span>',
            '<span class="red b">应付代价：</span><br>'.laika_html($demand['description']).'<br><br>支付，进度成立。拒绝，进度归零。除此之外没有第三种答案。',
        );
        $dialogue_branch['laika_tax'] = Array('支付代价', '拒绝支付');
        $dialogue_log['laika_tax'] = '';
    }

    if (!empty($state['pending']['type']) && $state['pending']['type'] == 'blessing') {
        $pending = $state['pending'];
        $dialogues['laika_blessing'] = Array(
            '<span class="lime b">“哇呼～”</span><br><br>只有这一声感叹还残留着少女般的轻盈。紧接着，三道彼此矛盾的星光落到你面前。',
            '“请选择你所期待的最好结果。”<br><br>她没有说明结果背面的内容。',
        );
        $dialogue_branch['laika_blessing'] = Array();
        foreach ($pending['offers'] as $offer) {
            $dialogue_branch['laika_blessing'][] = laika_html($offer['choice']).'（'.laika_html($offer['buff_desc']).'）';
        }
        $dialogue_log['laika_blessing'] = '';
    }

    if (!empty($state['cog']['active'])) {
        $cog = $state['cog'];
        $team_text = !empty($cog['member_pids']) && count($cog['member_pids']) > 1
            ? '齿轮同时嵌入了当时的整支队伍。'
            : '齿轮已经嵌入你的个人规则。';
        $dialogues['laika_cog_start'] = Array(
            '<span class="red b">【宇宙公理之齿轮（Cog of Cosmic Axiom）】</span><br><br>你使用剧情物品的瞬间，一枚看不见的齿轮咬进了世界的底层。',
            '“体系已经解析。为了让这段前进成立，我会把牺牲写入它的核心。”',
            '<span class="yellow">路线：'.laika_html($cog['route_name']).'</span><br><span class="yellow">基准行动预算：'.intval($cog['budget']).'</span><br><br>'.$team_text.'<br>预先准备不会被否定，但齿轮不会因为换路、退队或再次启动而归零。',
        );
    }
}

function laika_is_effective_action($command, $data)
{
    global $mode, $sp_cmd, $moveto;

    if (!is_string($command) || $command === '') return false;
    if (strpos($command, 'dialogue_choice') === 0 || strpos($command, 'end_dialogue') === 0) return false;
    if (in_array($command, Array('menu', 'back'), true)) return false;
    if (!empty($data['clbpara']['noskip_dialogue'])) return false;

    // 追击和战斗会消耗真实回合。
    // Chases and combat consume a real turn.
    if (in_array($command, Array('chase', 'pchase', 'dfight', 'cover'), true)) return true;
    if (in_array($mode, Array('combat', 'revcombat', 'corpse', 'senditem'), true)) return true;
    if ($command == 'move' || $command == 'search') return laika_can_start_move_search($command, $data, isset($moveto) ? $moveto : 99);

    // 只统计会推进战局、消耗资源或改变进度的指令；打开菜单、整理背包和切换姿态不计数。
    // Count commands that advance the match, consume resources, or change progress; UI and loadout housekeeping do not count.
    if (in_array($command, Array(
        'fishing', 'song', 'itemget', 'itemmix', 'elementmix',
        'itemencase', 'quest_accept', 'quest_cancel', 'fireseed_recruit',
    ), true)) return true;
    if (preg_match('/^itm([0-6])$/', $command, $item_match)) {
        $slot = $item_match[1];
        return !empty($data['itm'.$slot]) && !empty($data['itms'.$slot]);
    }
    if (preg_match('/^rest[0-9]*$/', $command)) {
        global $pls, $hospitals;
        if ($command == 'rest3' && isset($hospitals) && !in_array($pls, $hospitals)) return false;
        return true;
    }
    if (strpos($command, 'actskill_') === 0) return true;

    if ($command == 'special' && !empty($sp_cmd)) {
        $effective_specials = Array(
            'sp_word', 'sp_adtsk', 'sp_trapadtskselected', 'sp_extract_trait_selected',
            'sp_add_trait_selected', 'sp_consume_trait_selected', 'sp_weapon',
            'sp_pickpocket_selected', 'sp_fireseed_deploy', 'sp_fireseed_getitem',
            'sp_fireseed_enhance',
        );
        return in_array($sp_cmd, $effective_specials, true);
    }
    if ($mode == 'sp_pbomb' && $command == 'YES') return true;
    return false;
}

function laika_can_start_move_search($command, $data, $moveto)
{
    global $plsinfo, $hplsinfo, $arealist, $areanum, $hack;
    global $movesp, $movehp, $inf_move_sp;

    $pls = isset($data['pls']) ? $data['pls'] : 0;
    $pgroup = isset($data['pgroup']) ? $data['pgroup'] : 0;
    $in_hidden_area = !isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]);

    if ($command == 'move') {
        if ($pls == $moveto) return false;
        if ($in_hidden_area) {
            if (!array_key_exists($moveto, $hplsinfo[$pgroup])) return false;
        } else {
            $plsnum = count($plsinfo);
            if (!array_key_exists($moveto, $plsinfo) || $moveto == 'main' || $moveto < 0 || $moveto >= $plsnum) return false;
            if (array_search($moveto, $arealist) <= $areanum && !$hack) return false;
        }
    } elseif (!$in_hidden_area && array_search($pls, $arealist) <= $areanum && !$hack) {
        return false;
    }

    if ($command == 'move' && !empty($data['clbpara']['skill']) && in_array('npc_flying', $data['clbpara']['skill'])) return true;
    $costsp = intval($movesp);
    if (!empty($data['clbpara']['skill'])
        && in_array('npc_wrelease', $data['clbpara']['skill'])
        && !empty($data['clbpara']['skillpara']['npc_wrelease']['active'])) {
        $costsp *= isset($data['clbpara']['skillpara']['npc_wrelease']['level']) ? intval($data['clbpara']['skillpara']['npc_wrelease']['level']) : 2;
    }
    if (!empty($data['inf'])) {
        foreach ($inf_move_sp as $injury => $cost) {
            if (strpos($data['inf'], $injury) !== false) $costsp += intval($cost);
        }
    }
    if (isset($data['club']) && intval($data['club']) == 6) {
        $level = isset($data['lvl']) ? intval($data['lvl']) : 0;
        $costsp -= $level >= 20 ? 14 : 10 + floor($level / 5);
    }

    $horizon = isset($data['horizon']) ? intval($data['horizon']) : 0;
    $primary = $horizon == 1 ? 'hp' : 'sp';
    $secondary = $horizon == 1 ? 'sp' : 'hp';
    $primary_value = isset($data[$primary]) ? intval($data[$primary]) : 0;
    $secondary_value = isset($data[$secondary]) ? intval($data[$secondary]) : 0;
    return $primary_value > $costsp || $secondary_value > round($costsp * floatval($movehp));
}

// 道具使用前钩子 / Pre-item-use hook
function ruleset_itemuse_hook(&$data, $itmn, $item_snapshot = Array())
{
    global $log;

    laika_init_state($data);
    $state = &$data['clbpara']['laika'];
    $cog_cfg = laika_get_config('cog');
    $item_name = isset($item_snapshot['itm']) ? $item_snapshot['itm'] : (isset($data['itm'.$itmn]) ? $data['itm'.$itmn] : '');

    if ($item_name == $cog_cfg['warning_item'] && empty($state['pc_warned'])) {
        $state['pc_warned'] = 1;
        laika_set_dialogue($data, 'laika_pc_warning', true);
        $log .= '<span class="grey">有一道没有感情的视线短暂扫过了移动PC。</span><br>';
    }

    if (isset($cog_cfg['trigger_items'][$item_name])) {
        laika_activate_cog($data, $item_name);
    }
}

// 物品获得前钩子：只登记候选，待物品真正进入背包后再收税。
// Pre-acquisition hook: record a candidate and levy only after it really enters the inventory.
function ruleset_itemget_hook(&$data)
{
    laika_init_state($data);
    $tax_cfg = laika_get_config('tax');
    $item_name = isset($data['itm0']) ? $data['itm0'] : '';

    if ($item_name === '' || !in_array($item_name, $tax_cfg['important_items'], true)) return;
    if (laika_has_pending_event($data)) return;

    if (empty($data['clbpara']['laika']['item_candidates']) || !is_array($data['clbpara']['laika']['item_candidates'])) {
        $data['clbpara']['laika']['item_candidates'] = Array();
    }
    $data['clbpara']['laika']['item_candidates'][] = Array(
        'name' => $item_name,
        'before_count' => laika_count_inventory_item($data, $item_name),
        'amount' => isset($data['itms0']) && is_numeric($data['itms0']) ? max(1, intval($data['itms0'])) : 1,
    );
}

// NPC真正死亡后的钩子 / Final NPC death hook
function ruleset_player_kill_hook(&$pa, &$pd, $active)
{
    global $log, $gamevars;

    if (!empty($pa['type']) || empty($pd['type'])) return;
    laika_init_state($pa);

    $tax_cfg = laika_get_config('tax');
    $pd['clbpara'] = get_clbpara(isset($pd['clbpara']) ? $pd['clbpara'] : Array());
    if (in_array(intval($pd['type']), $tax_cfg['excluded_npc_types'], true)) return;
    if (!empty($tax_cfg['exclude_quest_npcs']) && !empty($pd['clbpara']['quest_id'])) return;
    if (laika_npc_has_external_death_side_effects($pd)) return;
    if (laika_has_pending_event($pa)) return;

    $important_item = laika_find_important_item($pd);
    $important = ($important_item !== '');
    $npc_tax_count_before = intval($pa['clbpara']['laika']['npc_tax_count']);
    if (!$important) {
        $pa['clbpara']['laika']['npc_tax_count']++;
        if ($pa['clbpara']['laika']['npc_tax_count'] < intval($tax_cfg['ordinary_npc_threshold'])) return;
        $npc_tax_count_before = max(0, intval($tax_cfg['ordinary_npc_threshold']) - 1);
        $pa['clbpara']['laika']['npc_tax_count'] = 0;
    }

    $rollback = Array(
        'kind' => 'npc',
        'npc_pid' => intval($pd['pid']),
        'npc_type' => intval($pd['type']),
        'npc_name' => $pd['name'],
        'mhp' => max(1, intval($pd['mhp'])),
        'msp' => max(0, intval($pd['msp'])),
        'npc_rage' => isset($pd['rage']) ? intval($pd['rage']) : 0,
        'npc_clbpara' => $pd['clbpara'],
        'npc_inventory' => laika_collect_npc_inventory_snapshot($pd),
        'sanmadead_existed' => isset($gamevars['sanmadead']),
        'sanmadead_before' => isset($gamevars['sanmadead']) ? $gamevars['sanmadead'] : null,
        'npc_tax_count_before' => $npc_tax_count_before,
        'progress_snapshot' => $pa['clbpara']['laika']['progress_snapshot'],
    );
    $description = $important
        ? '你击倒了携带重要进度“'.$important_item.'”的'.$pd['name'].'。'
        : '你击倒了第'.intval($tax_cfg['ordinary_npc_threshold']).'名需要结算的NPC——'.$pd['name'].'。';

    if (laika_schedule_tax($pa, $important ? 'important_npc' : 'ordinary_npc', $description, $rollback, $important ? 'important' : 'ordinary')) {
        $log .= '<span class="yellow">星光在尸体上方组成了一架无形的天平。</span><br>';
    }
}

function laika_collect_npc_inventory_snapshot($npc)
{
    $snapshot = Array();
    foreach (Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art') as $slot) {
        foreach (Array('', 'k', 'e', 's', 'sk', 'para') as $suffix) {
            $field = $slot.$suffix;
            if (array_key_exists($field, $npc)) $snapshot[$field] = $npc[$field];
        }
    }
    for ($i = 1; $i <= 6; $i++) {
        foreach (Array('itm', 'itmk', 'itme', 'itms', 'itmsk', 'itmpara') as $prefix) {
            $field = $prefix.$i;
            if (array_key_exists($field, $npc)) $snapshot[$field] = $npc[$field];
        }
    }
    return $snapshot;
}

function laika_npc_has_external_death_side_effects($npc)
{
    if (!empty($npc['clbpara']['post']) || !empty($npc['clbpara']['oid']) || !empty($npc['clbpara']['zombieoid'])) return true;
    if (!empty($npc['wep2e']) && !empty($npc['wep2sk']) && in_array('z', get_itmsk_array($npc['wep2sk']))) return true;
    return false;
}

// 从读取玩家数据前开始锁住整条请求，避免两个队员以同一进度快照执行max+1。
// Lock the request before player loading so two teammates cannot apply max+1 to the same snapshot.
function ruleset_command_request_begin_hook()
{
    return laika_acquire_command_lock();
}

function ruleset_command_request_end_hook()
{
    laika_release_command_lock();
}

function ruleset_command_post_save_hook(&$data, $command)
{
    ruleset_command_request_end_hook();
}

// 指令处理前钩子 / Pre-command hook
function ruleset_command_prepare_hook(&$data, $command)
{
    global $log;

    laika_init_state($data);
    laika_install_dynamic_dialogue($data);
    laika_inherit_team_cog($data);

    // 移动/探索必须在预计齿轮扣除后仍付得起行动消耗，避免“已计数但核心动作失败”。
    // Move/search must remain affordable after projected cog attrition, avoiding a counted action that core logic then rejects.
    if (($command == 'move' || $command == 'search')
        && laika_can_start_move_search($command, $data, isset($GLOBALS['moveto']) ? $GLOBALS['moveto'] : 99)
        && !empty($data['clbpara']['laika']['cog']['active'])) {
        $projected_data = laika_project_next_cog_effects($data);
        if (!laika_can_start_move_search($command, $projected_data, isset($GLOBALS['moveto']) ? $GLOBALS['moveto'] : 99)) {
            $log .= '<span class="red">齿轮预计会让你无法支付这次行动；请先恢复资源。</span><br>';
            return false;
        }
    }

    if (!laika_is_effective_action($command, $data)) return true;

    $state = &$data['clbpara']['laika'];
    $state['actions']++;
    $state['action_in_progress'] = 1;
    $state['important_items_before'] = laika_collect_important_item_counts($data);

    if (!empty($state['cog']['active'])) {
        $new_progress = laika_advance_cog_progress($data);
        $applied_progress = isset($state['cog']['applied_progress']) ? intval($state['cog']['applied_progress']) : 0;
        $state['cog']['applied_progress'] = laika_apply_cog_range($data, $applied_progress + 1, $new_progress);

        if (intval($data['hp']) <= 0) {
            $log .= '<span class="red b">齿轮碾过了你最后能够被称为“存在”的部分。</span><br>';
            unset($state['action_in_progress']);
            include_once GAME_ROOT.'./include/state.func.php';
            death('event', '', 0, '宇宙公理之齿轮', $data);
            return false;
        }
    }

    // 齿轮侵蚀是本次行动的前置代价，NPC拒税不应将它一并退回。
    // Cog attrition is a pre-action cost and must not be undone by refusing a later NPC levy.
    $state['progress_snapshot'] = laika_collect_progress_snapshot($data);

    return true;
}

function laika_project_next_cog_effects($data)
{
    $projected = $data;
    $cfg = laika_get_config('cog');
    $cog = $data['clbpara']['laika']['cog'];
    $budget = max(1, intval($cog['budget']));
    $from = isset($cog['applied_progress']) ? intval($cog['applied_progress']) + 1 : 1;
    $to = isset($cog['progress']) ? intval($cog['progress']) + 1 : 1;
    $to = min($to, $from + $budget + 100);
    for ($step = max(1, $from); $step <= $to; $step++) {
        $stage = laika_get_cog_stage($step, $budget);
        if ($stage > 0 && !empty($cfg['effects'][$stage])) laika_apply_cog_effect($projected, $cfg['effects'][$stage]);
        if (intval($projected['hp']) <= 0) break;
    }
    return $projected;
}

// 指令处理后钩子 / Post-command hook
function ruleset_command_end_hook(&$data, $command)
{
    global $log;

    laika_init_state($data);
    $state = &$data['clbpara']['laika'];

    if (strpos($command, 'dialogue_choice laika_tax ') === 0) {
        $parts = explode(' ', $command);
        laika_resolve_tax($data, isset($parts[2]) ? intval($parts[2]) : 1);
    } elseif (strpos($command, 'dialogue_choice laika_blessing ') === 0) {
        $parts = explode(' ', $command);
        laika_resolve_blessing($data, isset($parts[2]) ? intval($parts[2]) : -1);
    }

    $did_action = !empty($state['action_in_progress']);
    unset($state['action_in_progress']);

    if ($did_action) {
        laika_queue_important_item_gains($data, isset($state['important_items_before']) ? $state['important_items_before'] : Array());
    }
    unset($state['important_items_before']);

    if ($did_action && intval($data['hp']) > 0) {
        laika_tick_blessing($data);
        laika_tick_tax_conditions($data);
    }

    if (intval($data['hp']) > 0 && !laika_has_pending_event($data) && empty($data['clbpara']['dialogue'])) {
        laika_process_item_candidate($data);
    }
    if ($did_action && intval($data['hp']) > 0 && !laika_has_pending_event($data) && empty($data['clbpara']['dialogue'])) {
        laika_check_stat_thresholds($data);
    }
    if ($did_action && intval($data['hp']) > 0 && !laika_has_pending_event($data) && empty($data['clbpara']['dialogue'])) {
        laika_maybe_offer_blessing($data);
    }

    if (intval($data['hp']) <= 0 && !empty($state['tax_condition_death'])) {
        unset($state['tax_condition_death']);
        $log .= '<span class="red">你没能承受已经签下的星辰条件。</span><br>';
        include_once GAME_ROOT.'./include/state.func.php';
        death('event', '', 0, '星辰之代价', $data);
    }

    if (!laika_has_pending_event($data)) {
        $state['progress_snapshot'] = laika_collect_progress_snapshot($data);
    }
    laika_install_dynamic_dialogue($data);
}

function laika_activate_cog(&$data, $item_name)
{
    global $log, $db, $tablepre, $now;

    laika_init_state($data);
    $cfg = laika_get_config('cog');
    if (empty($cfg['trigger_items'][$item_name])) return false;

    $state = &$data['clbpara']['laika'];
    if (!empty($state['cog']['active'])) {
        $log .= '<span class="grey">齿轮确认了新的剧情分支，但拒绝重置已经产生的牺牲。</span><br>';
        return false;
    }

    $trigger = $cfg['trigger_items'][$item_name];
    $team_key = !empty($data['teamID']) ? $data['teamID'] : '';
    $member_pids = Array(intval($data['pid']));
    $member_rows = Array();
    if ($team_key !== '' && isset($db) && isset($tablepre)) {
        $safe_team = gstrfilter($team_key);
        $result = $db->query("SELECT pid,clbpara FROM {$tablepre}players WHERE teamID='$safe_team' AND type=0");
        while ($row = $db->fetch_array($result)) {
            $member_pid = intval($row['pid']);
            if ($member_pid == intval($data['pid'])) continue;
            $member_clbpara = get_clbpara($row['clbpara']);
            // 既有齿轮不被新队伍重置；该玩家继续承受原来的路线。
            // An existing cog is never reset by joining another team; that player keeps the original route.
            if (!empty($member_clbpara['laika']['cog']['active'])) continue;
            $member_pids[] = $member_pid;
            $member_rows[] = Array('pid' => $member_pid, 'clbpara' => $member_clbpara);
        }
    }
    $member_pids = array_values(array_unique($member_pids));
    sort($member_pids, SORT_NUMERIC);
    $cog_time = isset($now) ? intval($now) : time();
    $state['cog'] = Array(
        'active' => 1,
        'cog_id' => intval($data['pid']).'-'.$cog_time.'-'.rand(1000, 9999),
        'route' => $trigger['route'],
        'route_name' => $trigger['name'],
        'budget' => intval($trigger['budget']),
        'progress' => 0,
        'applied_progress' => 0,
        'stage' => 0,
        'team_key' => $team_key,
        'member_pids' => $member_pids,
        'trigger_item' => $item_name,
    );

    if (isset($db) && isset($tablepre)) {
        foreach ($member_rows as $member) {
            $member_clbpara = $member['clbpara'];
            if (empty($member_clbpara['laika']) || !is_array($member_clbpara['laika'])) $member_clbpara['laika'] = Array();
            $member_clbpara['laika']['cog'] = $state['cog'];
            $member_clbpara['dialogue'] = 'laika_cog_start';
            $member_clbpara['noskip_dialogue'] = 1;
            $db->array_update(
                "{$tablepre}players",
                Array('clbpara' => json_encode($member_clbpara, JSON_UNESCAPED_UNICODE)),
                "pid='".$member['pid']."'"
            );
        }
    }

    laika_set_dialogue($data, 'laika_cog_start', true);
    $log .= '<span class="red b">你听见了并不存在于空气中的齿轮声。</span><br>';
    laika_install_dynamic_dialogue($data);
    return true;
}

function laika_inherit_team_cog(&$data)
{
    global $db, $tablepre;

    if (!empty($data['clbpara']['laika']['cog']['active']) || empty($data['teamID'])) return;
    if (!isset($db) || !isset($tablepre)) return;

    $safe_team = gstrfilter($data['teamID']);
    $result = $db->query("SELECT clbpara FROM {$tablepre}players WHERE teamID='$safe_team' AND type=0");
    $best = Array();
    while ($row = $db->fetch_array($result)) {
        $member_clbpara = get_clbpara($row['clbpara']);
        if (empty($member_clbpara['laika']['cog']['active'])) continue;
        $candidate = $member_clbpara['laika']['cog'];
        if (empty($candidate['member_pids']) || !in_array(intval($data['pid']), $candidate['member_pids'])) continue;
        if (empty($best) || intval($candidate['progress']) > intval($best['progress'])) $best = $candidate;
    }
    if (!empty($best)) {
        $best['applied_progress'] = 0;
        $data['clbpara']['laika']['cog'] = $best;
        laika_set_dialogue($data, 'laika_cog_start', true);
    }
}

function laika_advance_cog_progress(&$data)
{
    global $db, $tablepre;

    $cog = &$data['clbpara']['laika']['cog'];
    $current = isset($cog['progress']) ? intval($cog['progress']) : 0;
    $member_pids = !empty($cog['member_pids']) ? array_map('intval', $cog['member_pids']) : Array(intval($data['pid']));
    $member_pids = array_values(array_unique(array_filter($member_pids)));
    if (count($member_pids) <= 1 || !isset($db) || !isset($tablepre)) {
        $cog['progress'] = $current + 1;
        return $cog['progress'];
    }

    $pid_list = implode(',', $member_pids);
    $result = $db->query("SELECT pid,clbpara FROM {$tablepre}players WHERE pid IN ($pid_list) AND type=0");
    $members = Array();
    $max_progress = $current;
    while ($row = $db->fetch_array($result)) {
        $member_clbpara = get_clbpara($row['clbpara']);
        if (!empty($member_clbpara['laika']['cog']['active']) && laika_is_same_cog($member_clbpara['laika']['cog'], $cog)) {
            $max_progress = max($max_progress, intval($member_clbpara['laika']['cog']['progress']));
        }
        $members[] = Array('pid' => intval($row['pid']), 'clbpara' => $member_clbpara);
    }

    $new_progress = $max_progress + 1;
    $cog['progress'] = $new_progress;
    foreach ($members as $member) {
        if ($member['pid'] == intval($data['pid'])) continue;
        $member_clbpara = $member['clbpara'];
        if (empty($member_clbpara['laika']) || !is_array($member_clbpara['laika'])) $member_clbpara['laika'] = Array();
        if (empty($member_clbpara['laika']['cog']['active'])) {
            $member_clbpara['laika']['cog'] = $cog;
            $member_clbpara['laika']['cog']['applied_progress'] = 0;
            $member_clbpara['dialogue'] = 'laika_cog_start';
            $member_clbpara['noskip_dialogue'] = 1;
        } elseif (laika_is_same_cog($member_clbpara['laika']['cog'], $cog)) {
            $member_clbpara['laika']['cog']['progress'] = $new_progress;
        } else {
            continue;
        }
        $db->array_update(
            "{$tablepre}players",
            Array('clbpara' => json_encode($member_clbpara, JSON_UNESCAPED_UNICODE)),
            "pid='".$member['pid']."'"
        );
    }
    return $new_progress;
}

function laika_is_same_cog($left, $right)
{
    if (!empty($left['cog_id']) && !empty($right['cog_id'])) return $left['cog_id'] === $right['cog_id'];
    return !empty($left['trigger_item']) && !empty($right['trigger_item'])
        && $left['trigger_item'] === $right['trigger_item']
        && intval($left['budget']) === intval($right['budget']);
}

function laika_get_cog_stage($progress, $budget)
{
    $cfg = laika_get_config('cog');
    $percent = $budget > 0 ? ($progress * 100 / $budget) : 100;
    $stage = 0;
    foreach ($cfg['stage_percentages'] as $candidate => $required) {
        if ($percent >= $required) $stage = intval($candidate);
    }
    return $stage;
}

function laika_apply_cog_range(&$data, $from, $to)
{
    global $log;

    if ($to < $from) return intval($from) - 1;
    $cfg = laika_get_config('cog');
    $cog = &$data['clbpara']['laika']['cog'];
    $budget = max(1, intval($cog['budget']));
    $old_stage = isset($cog['stage']) ? intval($cog['stage']) : 0;

    // 防止异常共享状态导致一次请求循环过大。
    // Guard against corrupted shared progress causing an oversized loop.
    $from = max(1, intval($from));
    $to = min(intval($to), $from + $budget + 100);
    $last_step = $from - 1;
    for ($step = $from; $step <= $to; $step++) {
        $stage = laika_get_cog_stage($step, $budget);
        if ($stage <= 0 || empty($cfg['effects'][$stage])) continue;
        laika_apply_cog_effect($data, $cfg['effects'][$stage]);
        $last_step = $step;
        if (intval($data['hp']) <= 0) break;
        $cog['stage'] = $stage;
    }

    if (intval($cog['stage']) > $old_stage) {
        $log .= '<span class="red">'.laika_html($cfg['stage_text'][$cog['stage']]).'</span><br>';
    } elseif ($to % 10 == 0) {
        $log .= '<span class="grey">齿轮刻度：'.$to.' / '.$budget.'。</span><br>';
    }
    return $last_step;
}

function laika_apply_cog_effect(&$data, $effect)
{
    $mhp = max(1, intval($data['mhp']));
    $msp = max(1, intval($data['msp']));

    if (!empty($effect['hp_loss_percent'])) {
        $data['hp'] -= max(1, intval(ceil($mhp * $effect['hp_loss_percent'] / 100)));
    }
    if (!empty($effect['sp_loss_percent'])) {
        $data['sp'] = max(0, intval($data['sp']) - max(1, intval(ceil($msp * $effect['sp_loss_percent'] / 100))));
    }
    if (!empty($effect['att_decay_percent'])) {
        $data['att'] = max(1, intval($data['att']) - max(1, intval(ceil(max(1, $data['att']) * $effect['att_decay_percent'] / 100))));
    }
    if (!empty($effect['def_decay_percent'])) {
        $data['def'] = max(1, intval($data['def']) - max(1, intval(ceil(max(1, $data['def']) * $effect['def_decay_percent'] / 100))));
    }
    if (!empty($effect['max_decay_percent'])) {
        $data['mhp'] = max(1, intval($data['mhp']) - max(1, intval(ceil(max(1, $data['mhp']) * $effect['max_decay_percent'] / 100))));
        $data['msp'] = max(1, intval($data['msp']) - max(1, intval(ceil(max(1, $data['msp']) * $effect['max_decay_percent'] / 100))));
        $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
        $data['sp'] = min(intval($data['sp']), intval($data['msp']));
    }
}

function laika_process_item_candidate(&$data)
{
    if (empty($data['clbpara']['laika']['item_candidates'])) return false;
    $candidate = array_shift($data['clbpara']['laika']['item_candidates']);
    $after_count = laika_count_inventory_item($data, $candidate['name']);
    $gained = $after_count - intval($candidate['before_count']);

    if ($gained <= 0) {
        if (!empty($data['itm0']) && $data['itm0'] == $candidate['name']) {
            array_unshift($data['clbpara']['laika']['item_candidates'], $candidate);
            return false;
        }
        return false;
    }

    $rollback = Array(
        'kind' => 'item',
        'item_name' => $candidate['name'],
        'amount' => max(1, $gained),
    );
    return laika_schedule_tax(
        $data,
        'important_item',
        '你获得了重要进度“'.$candidate['name'].'”。',
        $rollback,
        'important'
    );
}

function laika_check_stat_thresholds(&$data)
{
    $cfg = laika_get_config('tax');
    $state = &$data['clbpara']['laika'];

    foreach ($cfg['stat_thresholds'] as $stat => $thresholds) {
        $stat_info = laika_get_threshold_stat($data, $stat);
        foreach ($thresholds as $threshold) {
            $key = $stat.':'.$threshold;
            if (!empty($state['thresholds'][$key])) continue;
            if ($stat_info['value'] <= $threshold) continue;

            $rollback = Array(
                'kind' => 'threshold',
                'key' => $key,
                'stat' => $stat,
                'field' => $stat_info['field'],
                'threshold' => intval($threshold),
            );
            return laika_schedule_tax(
                $data,
                'stat_threshold',
                '你的'.$cfg['stat_labels'][$stat].'越过了星辰范畴 '.$threshold.'。',
                $rollback,
                'threshold'
            );
        }
    }
    return false;
}

function laika_get_threshold_stat($data, $stat)
{
    if ($stat == 'mastery') {
        $fields = Array('wp', 'wk', 'wg', 'wc', 'wd', 'wf');
        $best_field = 'wp';
        $best = isset($data['wp']) ? intval($data['wp']) : 0;
        foreach ($fields as $field) {
            if (isset($data[$field]) && intval($data[$field]) > $best) {
                $best = intval($data[$field]);
                $best_field = $field;
            }
        }
        return Array('value' => $best, 'field' => $best_field);
    }
    if ($stat == 'weapon_power') {
        return Array('value' => isset($data['wepe']) ? intval($data['wepe']) : 0, 'field' => 'wepe');
    }
    return Array('value' => isset($data[$stat]) ? intval($data[$stat]) : 0, 'field' => $stat);
}

function laika_schedule_tax(&$data, $source, $description, $rollback, $severity)
{
    global $log;

    laika_init_state($data);
    if (laika_has_pending_event($data)) return false;

    $demand = laika_generate_tax_demand($data, $severity);
    $data['clbpara']['laika']['pending'] = Array(
        'type' => 'tax',
        'source' => $source,
        'description' => $description,
        'rollback' => $rollback,
        'demand' => $demand,
    );
    laika_set_dialogue($data, 'laika_tax', true);
    $log .= '<span class="yellow b">【星辰之代价】莱卡要求结算这次前进。</span><br>';
    laika_install_dynamic_dialogue($data);
    return true;
}

function laika_generate_tax_demand($data, $severity)
{
    $cfg = laika_get_config('tax');
    $rate = isset($cfg['severity_rates'][$severity]) ? intval($cfg['severity_rates'][$severity]) : 10;
    $available = Array('condition' => intval($cfg['demand_weights']['condition']));
    $stat_candidates = laika_get_tax_stat_candidates($data, $rate);
    $item_candidates = laika_get_tax_item_candidates($data);
    if (!empty($stat_candidates)) $available['stat'] = intval($cfg['demand_weights']['stat']);
    if (!empty($item_candidates)) $available['item'] = intval($cfg['demand_weights']['item']);

    $type = laika_weighted_pick($available);
    if ($type == 'stat') {
        $candidate = $stat_candidates[array_rand($stat_candidates)];
        return Array(
            'type' => 'stat',
            'field' => $candidate['field'],
            'name' => $candidate['name'],
            'amount' => $candidate['amount'],
            'floor' => $candidate['floor'],
            'description' => '永久交出 '.intval($candidate['amount']).' 点'.$candidate['name'].'。',
        );
    }
    if ($type == 'item') {
        $candidate = $item_candidates[array_rand($item_candidates)];
        return Array(
            'type' => 'item',
            'slot' => $candidate['slot'],
            'name' => $candidate['name'],
            'description' => '交出你身上的 '.$candidate['name'].'。',
        );
    }

    $condition_key = array_rand($cfg['conditions']);
    $condition = $cfg['conditions'][$condition_key];
    $loss_desc = Array();
    if (!empty($condition['hp_loss_percent'])) $loss_desc[] = '每次行动失去'.$condition['hp_loss_percent'].'%最大生命';
    if (!empty($condition['sp_loss_percent'])) $loss_desc[] = '每次行动失去'.$condition['sp_loss_percent'].'%最大体力';
    return Array(
        'type' => 'condition',
        'condition_key' => $condition_key,
        'condition' => $condition,
        'description' => '接受条件“'.$condition['name'].'”：接下来'.intval($condition['actions']).'次行动，'.implode('，', $loss_desc).'。',
    );
}

function laika_get_tax_stat_candidates($data, $rate)
{
    $cfg = laika_get_config('tax');
    $candidates = Array();
    foreach ($cfg['stat_pool'] as $field => $info) {
        if (!isset($data[$field]) || !is_numeric($data[$field])) continue;
        $value = intval($data[$field]);
        $floor = intval($info['floor']);
        if ($value <= $floor) continue;
        $amount = max(1, intval(ceil($value * $rate / 100)));
        $amount = min($amount, $value - $floor);
        if ($amount <= 0) continue;
        $candidates[] = Array('field' => $field, 'name' => $info['name'], 'amount' => $amount, 'floor' => $floor);
    }
    return $candidates;
}

function laika_get_tax_item_candidates($data)
{
    $cfg = laika_get_config('tax');
    $candidates = Array();
    foreach (Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art') as $slot) {
        if (empty($data[$slot]) || empty($data[$slot.'s'])) continue;
        if (in_array($data[$slot], $cfg['protected_items'], true)) continue;
        $candidates[] = Array('slot' => $slot, 'name' => $data[$slot]);
    }
    for ($i = 1; $i <= 6; $i++) {
        $slot = 'itm'.$i;
        if (empty($data[$slot]) || empty($data['itms'.$i])) continue;
        if (in_array($data[$slot], $cfg['protected_items'], true)) continue;
        $candidates[] = Array('slot' => $slot, 'name' => $data[$slot]);
    }
    return $candidates;
}

function laika_weighted_pick($weights)
{
    $total = array_sum($weights);
    if ($total <= 0) return array_key_first($weights);
    $roll = rand(1, $total);
    foreach ($weights as $key => $weight) {
        $roll -= $weight;
        if ($roll <= 0) return $key;
    }
    return array_key_first($weights);
}

function laika_resolve_tax(&$data, $choice)
{
    global $log;

    if (empty($data['clbpara']['laika']['pending']['type']) || $data['clbpara']['laika']['pending']['type'] != 'tax') return false;
    $pending = $data['clbpara']['laika']['pending'];
    $paid = false;

    if ($choice === 0) {
        $paid = laika_apply_tax_demand($data, $pending['demand']);
        if ($paid) {
            if ($pending['rollback']['kind'] == 'threshold') {
                $data['clbpara']['laika']['thresholds'][$pending['rollback']['key']] = 1;
            }
            $log .= '<span class="lime">你支付了代价。莱卡承认这次前进已经成立。</span><br>';
        } else {
            $log .= '<span class="red">指定的代价已经无法支付；拒付结算自动生效。</span><br>';
        }
    }

    if (!$paid) {
        laika_rollback_progress($data, $pending['rollback']);
        $log .= '<span class="red">你拒绝了代价。与其对应的进度被公理抹除。</span><br>';
    }

    unset($data['clbpara']['laika']['pending']);
    laika_clear_dialogue($data);
    return $paid;
}

function laika_apply_tax_demand(&$data, $demand)
{
    if ($demand['type'] == 'stat') {
        $field = $demand['field'];
        if (!isset($data[$field]) || intval($data[$field]) - intval($demand['amount']) < intval($demand['floor'])) return false;
        $data[$field] = intval($data[$field]) - intval($demand['amount']);
        if ($field == 'mhp') $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
        if ($field == 'msp') $data['sp'] = min(intval($data['sp']), intval($data['msp']));
        return true;
    }
    if ($demand['type'] == 'item') {
        $slot = $demand['slot'];
        if (empty($data[$slot]) || $data[$slot] != $demand['name']) return false;
        laika_destroy_slot($data, $slot);
        return true;
    }
    if ($demand['type'] == 'condition') {
        $condition = $demand['condition'];
        $condition['key'] = $demand['condition_key'];
        $data['clbpara']['laika']['tax_conditions'][] = $condition;
        return true;
    }
    return false;
}

function laika_rollback_progress(&$data, $rollback)
{
    global $db, $tablepre, $deathnum, $gamevars;

    if ($rollback['kind'] == 'npc') {
        if (isset($db) && isset($tablepre)) {
            $npc_update = Array(
                'hp' => intval($rollback['mhp']),
                'sp' => intval($rollback['msp']),
                'rage' => intval($rollback['npc_rage']),
                'state' => 0,
                'bid' => 0,
                'endtime' => 0,
                'deathtime' => 0,
                'action' => '',
                'clbpara' => json_encode($rollback['npc_clbpara'], JSON_UNESCAPED_UNICODE),
            );
            if (!empty($rollback['npc_inventory']) && is_array($rollback['npc_inventory'])) {
                $npc_update = array_merge($npc_update, $rollback['npc_inventory']);
            }
            $db->array_update(
                "{$tablepre}players",
                $npc_update,
                "pid='".intval($rollback['npc_pid'])."' AND type<>'0'"
            );
        }
        if (intval($rollback['npc_type']) == 15) {
            if (!empty($rollback['sanmadead_existed'])) $gamevars['sanmadead'] = $rollback['sanmadead_before'];
            else unset($gamevars['sanmadead']);
        }
        if (isset($deathnum)) $deathnum = max(0, intval($deathnum) - 1);
        if (function_exists('save_gameinfo')) save_gameinfo();
        laika_restore_progress_snapshot($data, $rollback['progress_snapshot']);
        $data['clbpara']['laika']['npc_tax_count'] = intval($rollback['npc_tax_count_before']);
        return;
    }
    if ($rollback['kind'] == 'item') {
        laika_remove_inventory_item($data, $rollback['item_name'], intval($rollback['amount']));
        return;
    }
    if ($rollback['kind'] == 'threshold') {
        $field = $rollback['field'];
        if (isset($data[$field])) $data[$field] = min(intval($data[$field]), intval($rollback['threshold']));
        if ($field == 'mhp') $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
        if ($field == 'msp') $data['sp'] = min(intval($data['sp']), intval($data['msp']));
    }
}

function laika_tick_tax_conditions(&$data)
{
    if (empty($data['clbpara']['laika']['tax_conditions'])) return;
    $remaining_conditions = Array();
    foreach ($data['clbpara']['laika']['tax_conditions'] as $condition) {
        if (!empty($condition['hp_loss_percent'])) {
            $data['hp'] -= max(1, intval(ceil(max(1, $data['mhp']) * $condition['hp_loss_percent'] / 100)));
        }
        if (!empty($condition['sp_loss_percent'])) {
            $data['sp'] = max(0, intval($data['sp']) - max(1, intval(ceil(max(1, $data['msp']) * $condition['sp_loss_percent'] / 100))));
        }
        $condition['actions'] = intval($condition['actions']) - 1;
        if ($condition['actions'] > 0) $remaining_conditions[] = $condition;
    }
    $data['clbpara']['laika']['tax_conditions'] = $remaining_conditions;
    if (intval($data['hp']) <= 0) $data['clbpara']['laika']['tax_condition_death'] = 1;
}

function laika_maybe_offer_blessing(&$data)
{
    global $log;

    $state = &$data['clbpara']['laika'];
    if (!empty($state['blessing']) || intval($state['actions']) < intval($state['next_blessing'])) return false;
    $cfg = laika_get_config('blessing');
    $pool_keys = array_keys($cfg['pool']);
    shuffle($pool_keys);
    $offer_count = min(intval($cfg['offer_count']), count($pool_keys));
    $duration = rand(intval($cfg['duration_min_actions']), intval($cfg['duration_max_actions']));
    $offers = Array();
    for ($i = 0; $i < $offer_count; $i++) {
        $offer = $cfg['pool'][$pool_keys[$i]];
        $offer['id'] = $pool_keys[$i];
        $offers[] = $offer;
    }
    $state['pending'] = Array('type' => 'blessing', 'offers' => $offers, 'duration' => $duration);
    $state['next_blessing'] = intval($state['actions']) + rand(intval($cfg['offer_min_actions']), intval($cfg['offer_max_actions']));
    laika_set_dialogue($data, 'laika_blessing', true);
    $log .= '<span class="lime b">“哇呼～请选择一个祝福吧。”</span><br>';
    laika_install_dynamic_dialogue($data);
    return true;
}

function laika_resolve_blessing(&$data, $choice)
{
    global $log;

    if (empty($data['clbpara']['laika']['pending']['type']) || $data['clbpara']['laika']['pending']['type'] != 'blessing') return false;
    $pending = $data['clbpara']['laika']['pending'];
    if (!isset($pending['offers'][$choice])) return false;
    $offer = $pending['offers'][$choice];
    $deltas = Array();

    foreach ($offer['changes'] as $field => $percent) {
        if (!isset($data[$field]) || !is_numeric($data[$field])) continue;
        $old_value = intval($data[$field]);
        $delta = intval(round($old_value * intval($percent) / 100));
        $new_value = max(1, $old_value + $delta);
        $deltas[$field] = $new_value - $old_value;
        $data[$field] = $new_value;
    }
    if (isset($data['mhp'])) $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
    if (isset($data['msp'])) $data['sp'] = min(intval($data['sp']), intval($data['msp']));

    $data['clbpara']['laika']['blessing'] = Array(
        'id' => $offer['id'],
        'name' => $offer['name'],
        'remaining' => intval($pending['duration']),
        'deltas' => $deltas,
        'buff_desc' => $offer['buff_desc'],
        'debuff_desc' => $offer['debuff_desc'],
    );
    unset($data['clbpara']['laika']['pending']);
    laika_clear_dialogue($data);
    $log .= '<span class="lime">'.laika_html($offer['buff_desc']).'。</span><br>';
    $log .= '<span class="red">悖论反面显现：'.laika_html($offer['debuff_desc']).'。</span><br>';
    return true;
}

function laika_tick_blessing(&$data)
{
    global $log;

    if (empty($data['clbpara']['laika']['blessing'])) return;
    $blessing = &$data['clbpara']['laika']['blessing'];
    $blessing['remaining'] = intval($blessing['remaining']) - 1;
    if ($blessing['remaining'] > 0) return;

    foreach ($blessing['deltas'] as $field => $delta) {
        if (!isset($data[$field])) continue;
        $data[$field] = max(1, intval($data[$field]) - intval($delta));
        if (!empty($data['clbpara']['laika']['pending']['rollback']['kind'])
            && $data['clbpara']['laika']['pending']['rollback']['kind'] == 'npc'
            && isset($data['clbpara']['laika']['pending']['rollback']['progress_snapshot'][$field])) {
            $snapshot_value = $data['clbpara']['laika']['pending']['rollback']['progress_snapshot'][$field];
            $data['clbpara']['laika']['pending']['rollback']['progress_snapshot'][$field] = max(1, intval($snapshot_value) - intval($delta));
        }
    }
    if (isset($data['mhp'])) $data['hp'] = min(intval($data['hp']), intval($data['mhp']));
    if (isset($data['msp'])) $data['sp'] = min(intval($data['sp']), intval($data['msp']));
    $log .= '<span class="grey">'.laika_html($blessing['name']).'的因果扭曲已经消散。</span><br>';
    unset($data['clbpara']['laika']['blessing']);
}

function laika_count_inventory_item($data, $item_name)
{
    $count = 0;
    for ($i = 1; $i <= 6; $i++) {
        if (!empty($data['itm'.$i]) && $data['itm'.$i] == $item_name && !empty($data['itms'.$i])) {
            $count += is_numeric($data['itms'.$i]) ? intval($data['itms'.$i]) : 1;
        }
    }
    return $count;
}

function laika_collect_important_item_counts($data)
{
    $cfg = laika_get_config('tax');
    $counts = Array();
    foreach ($cfg['important_items'] as $item_name) {
        $count = laika_count_inventory_item($data, $item_name);
        if ($count > 0) $counts[$item_name] = $count;
    }
    return $counts;
}

function laika_queue_important_item_gains(&$data, $before_counts)
{
    $cfg = laika_get_config('tax');
    if (empty($data['clbpara']['laika']['item_candidates']) || !is_array($data['clbpara']['laika']['item_candidates'])) {
        $data['clbpara']['laika']['item_candidates'] = Array();
    }

    foreach ($cfg['important_items'] as $item_name) {
        $before = isset($before_counts[$item_name]) ? intval($before_counts[$item_name]) : 0;
        $after = laika_count_inventory_item($data, $item_name);
        if ($after <= $before) continue;

        $already_queued = 0;
        foreach ($data['clbpara']['laika']['item_candidates'] as $candidate) {
            if ($candidate['name'] == $item_name) $already_queued += max(1, intval($candidate['amount']));
        }
        $gained = $after - $before;
        if ($already_queued >= $gained) continue;
        $data['clbpara']['laika']['item_candidates'][] = Array(
            'name' => $item_name,
            'before_count' => $before + $already_queued,
            'amount' => $gained - $already_queued,
        );
    }
}

function laika_remove_inventory_item(&$data, $item_name, $amount)
{
    $remaining = max(1, intval($amount));
    for ($i = 1; $i <= 6 && $remaining > 0; $i++) {
        if (empty($data['itm'.$i]) || $data['itm'.$i] != $item_name || empty($data['itms'.$i])) continue;
        $uses = is_numeric($data['itms'.$i]) ? intval($data['itms'.$i]) : 1;
        if ($uses > $remaining) {
            $data['itms'.$i] = $uses - $remaining;
            $remaining = 0;
        } else {
            $remaining -= $uses;
            laika_destroy_slot($data, 'itm'.$i);
        }
    }
}

function laika_destroy_slot(&$data, $slot)
{
    if (strpos($slot, 'itm') === 0) {
        $index = substr($slot, 3);
        $data['itm'.$index] = '';
        $data['itmk'.$index] = '';
        $data['itme'.$index] = 0;
        $data['itms'.$index] = 0;
        $data['itmsk'.$index] = '';
        if (isset($data['itmpara'.$index])) $data['itmpara'.$index] = '';
        return;
    }
    $data[$slot] = '';
    $data[$slot.'k'] = '';
    $data[$slot.'e'] = 0;
    $data[$slot.'s'] = 0;
    $data[$slot.'sk'] = '';
    if (isset($data[$slot.'para'])) $data[$slot.'para'] = '';
}

function laika_find_important_item($data)
{
    $cfg = laika_get_config('tax');
    foreach (Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art') as $slot) {
        if (!empty($data[$slot]) && in_array($data[$slot], $cfg['important_items'], true)) return $data[$slot];
    }
    for ($i = 0; $i <= 6; $i++) {
        if (!empty($data['itm'.$i]) && in_array($data['itm'.$i], $cfg['important_items'], true)) return $data['itm'.$i];
    }
    return '';
}

?>
