<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * RAID PvP/PvE route, seal-part tracking and task-panel logic.
 * RAID PvP/PvE路线、封印部件追踪与任务面板逻辑。
 */

// 取得并规范本轮RAID共享状态 / Fetch and normalize the shared RAID round state
function &raid_routes_state()
{
    global $gamevars;
    if (!isset($gamevars) || !is_array($gamevars)) $gamevars = Array();
    if (empty($gamevars['raid']) || !is_array($gamevars['raid'])) $gamevars['raid'] = Array();
    $state = &$gamevars['raid'];

    if (!isset($state['progress'])) {
        $state['progress'] = isset($state['pve_progress']) ? intval($state['pve_progress']) : 0;
    }
    if (empty($state['material']) && !empty($state['pve_material']) && is_array($state['pve_material'])) {
        $state['material'] = $state['pve_material'];
    }
    if (empty($state['parts']) && !empty($state['seal_parts']) && is_array($state['seal_parts'])) {
        $state['parts'] = $state['seal_parts'];
    }
    if (empty($state['parts']) || !is_array($state['parts'])) $state['parts'] = Array();
    if (!isset($state['parts_released'])) $state['parts_released'] = !empty($state['parts']);
    if (!isset($state['victory_complete'])) $state['victory_complete'] = false;
    return $state;
}

// 同步兼容字段并持久化游戏状态 / Synchronize compatibility aliases and persist game state
function raid_routes_save_state()
{
    $state = &raid_routes_state();
    $state['pve_progress'] = intval($state['progress']);
    $state['pve_material'] = isset($state['material']) ? $state['material'] : Array();
    $state['seal_parts'] = isset($state['parts']) ? $state['parts'] : Array();
    if (function_exists('raid_event_save_state')) raid_event_save_state();
    elseif (function_exists('save_gameinfo')) save_gameinfo();
}

// 将数据库中的clbpara规范为数组 / Normalize a database clbpara value to an array
function raid_routes_decode_clbpara(&$data)
{
    if (!isset($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = function_exists('get_clbpara')
            ? get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : '')
            : raid_decode_itempara(isset($data['clbpara']) ? $data['clbpara'] : '');
    }
    if (!is_array($data['clbpara'])) $data['clbpara'] = Array();
    raid_init_player_state($data);
}

// 取得地点显示名 / Resolve a location display name
function raid_routes_location_name($pls, $pgroup = 0)
{
    global $plsinfo, $hplsinfo;
    $pls = intval($pls);
    $pgroup = intval($pgroup);
    if (isset($plsinfo[$pls])) return $plsinfo[$pls];
    if (isset($hplsinfo[$pgroup][$pls])) return $hplsinfo[$pgroup][$pls];
    foreach ((array)$hplsinfo as $locations) {
        if (isset($locations[$pls])) return $locations[$pls];
    }
    return '地点'.$pls;
}

// 判断标准地点是否仍可安全投送 / Check whether a standard location is still safe for delivery
function raid_routes_location_is_safe($pls)
{
    global $arealist, $areanum, $hack, $plsinfo;
    $pls = intval($pls);
    $pve = raid_get_config('pve');
    if ($pls === intval(isset($pve['source_location']) ? $pve['source_location'] : 121)) return true;
    if (!isset($plsinfo[$pls])) return false;
    if (!empty($hack)) return true;
    $banned = array_slice((array)$arealist, 0, max(0, intval($areanum) + 1));
    foreach ($banned as $value) if (intval($value) === $pls) return false;
    return true;
}

// 取得可用投送点并支持排除指定地点 / Get available delivery locations with exclusions
function raid_routes_safe_locations($exclude = Array())
{
    $cfg = raid_get_config();
    $locations = !empty($cfg['safe_locations']) && is_array($cfg['safe_locations'])
        ? $cfg['safe_locations'] : Array();
    $excluded = Array();
    foreach ((array)$exclude as $value) $excluded[intval($value)] = true;
    $result = Array();
    foreach ($locations as $pls) {
        $pls = intval($pls);
        if (!isset($excluded[$pls]) && raid_routes_location_is_safe($pls)) $result[] = $pls;
    }
    return array_values(array_unique($result));
}

// 从安全地点中选择一个地点 / Pick one safe location
function raid_routes_pick_location($exclude = Array())
{
    $locations = raid_routes_safe_locations($exclude);
    if (empty($locations)) {
        $pve = raid_get_config('pve');
        return isset($pve['source_location']) ? intval($pve['source_location']) : 121;
    }
    return intval($locations[array_rand($locations)]);
}

// 判断当前是否为可执行路线命令的普通状态 / Check whether ordinary route actions are currently allowed
function raid_can_use_route_command($data, $allow_corpse = false)
{
    global $mode, $action, $bid;
    if (intval(isset($data['type']) ? $data['type'] : 0) !== 0) return false;
    if (intval(isset($data['hp']) ? $data['hp'] : 0) <= 0) return false;
    if (intval(isset($data['state']) ? $data['state'] : 0) >= 10) return false;
    if (!empty($data['clbpara']['noskip_dialogue']) || !empty($data['clbpara']['dialogue'])) return false;

    if ($allow_corpse) return $mode === 'corpse' || $action === 'corpse' || $action === 'pacorpse';
    if (isset($mode) && $mode !== '' && $mode !== 'command') return false;
    if (!empty($action) || !empty($bid)) return false;
    return true;
}

// 追加路线操作日志 / Append a route-operation log message
function raid_routes_log($message, $class = 'yellow')
{
    global $log;
    $log .= '<span class="'.raid_html($class).'">'.raid_html($message).'</span><br>';
}

// 记录ID 611成就的历史最高RAID积分 / Record the lifetime-best RAID score in achievement 611
function raid_award_score_achievement($username, $score)
{
    global $db, $gtablepre;
    $username = (string)$username;
    if ($username === '') return 0;
    $scoring = raid_get_config('scoring');
    $achievement_id = isset($scoring['achievement_id']) ? intval($scoring['achievement_id']) : 611;
    $cap = isset($scoring['achievement_value_cap']) ? intval($scoring['achievement_value_cap']) : 99999999;
    $score = max(0, min($cap, intval($score)));

    include_once GAME_ROOT.'./include/game/achievement.func.php';
    $achievement_level = function_exists('check_achievement_rev')
        ? intval(check_achievement_rev($achievement_id, $username)) : 0;
    $old_score = function_exists('fetch_achievement_rev') ? intval(fetch_achievement_rev($achievement_id, $username)) : 0;
    $best_score = max($old_score, $score);
    if (function_exists('update_achievement_rev') && $best_score !== $old_score) {
        update_achievement_rev($achievement_id, $username, $best_score);
    }
    if (!$achievement_level) {
        // 奖励完全读取成就配置，路线本身不另设credits结算。
        // Rewards come exclusively from achievement config; the route adds no separate credits payout.
        $achievement = function_exists('get_achlist') ? get_achlist($achievement_id) : Array();
        if (!empty($achievement['title'][0]) && function_exists('get_title')) get_title($achievement['title'][0], $username);
        $credit1 = !empty($achievement['c1'][0]) ? intval($achievement['c1'][0]) : 0;
        $credit2 = !empty($achievement['c2'][0]) ? intval($achievement['c2'][0]) : 0;
		$username_sql = raid_mysql_string_literal($username);
		if ($credit1 && isset($db, $gtablepre)) {
			$db->query("UPDATE {$gtablepre}users SET credits=credits+{$credit1} WHERE username={$username_sql}");
		}
		if ($credit2 && isset($db, $gtablepre)) {
			$db->query("UPDATE {$gtablepre}users SET credits2=credits2+{$credit2} WHERE username={$username_sql}");
        }
        if (function_exists('done_achievement_rev')) done_achievement_rev($achievement_id, 999, $username);
    }
    return $best_score;
}

// 收纳一个完整物品并转化为个人RAID积分 / Store one whole item and convert it to personal RAID score
function raid_store_item(&$data, $slot)
{
    $slot = intval($slot);
    raid_routes_decode_clbpara($data);
    if (!raid_can_use_route_command($data) || $slot < 0 || $slot > 6) {
        raid_routes_log('现在无法收纳物品。', 'red');
        return false;
    }
    $item = raid_get_item_from_slot($data, $slot);
    if (!raid_is_item_present($item)) {
        raid_routes_log('这个物品已经不在你的物品栏中。', 'red');
        return false;
    }
    $pve = raid_get_config('pve');
    $warp_name = isset($pve['warp_item_name']) ? $pve['warp_item_name'] : '【源流折跃信标】';
    if (raid_is_protected_item($item) || $item['itm'] === $warp_name) {
        raid_routes_log('这个物品受路线机制保护，不能被收纳。', 'red');
        return false;
    }

    $score = raid_calculate_item_score($item, true);
    $entry = Array(
        'name' => (string)$item['itm'],
        'kind' => (string)$item['itmk'],
        'effect' => $item['itme'],
        'durability' => $item['itms'],
        'attributes' => (string)$item['itmsk'],
        'score' => intval($score),
        'treasure' => raid_is_treasure_item($item) ? 1 : 0,
    );
    $data['clbpara']['raid']['stored_items'][] = $entry;
    $data['clbpara']['raid']['score'] += intval($score);
    raid_clear_item_slot($data, $slot);
    raid_routes_log('已收纳「'.$entry['name'].'」，获得 '.$score.' RAID积分。', 'lime');
    raid_sync_tasks($data, false);
    return true;
}

// 清除撤离玩家的实体与全部携带物 / Clear an extracted player's body and carried inventory
function raid_routes_clear_extracted_body(&$data)
{
    global $nosta, $now;
    foreach (Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art') as $equip) {
        $data[$equip] = $equip === 'wep' ? '拳头' : '';
        $data[$equip.'k'] = $equip === 'wep' ? 'WN' : '';
        $data[$equip.'e'] = 0;
        $data[$equip.'s'] = $equip === 'wep' ? $nosta : 0;
        $data[$equip.'sk'] = '';
        if (array_key_exists($equip.'para', $data)) $data[$equip.'para'] = '';
    }
    for ($i = 0; $i <= 6; $i++) raid_clear_item_slot($data, $i);
    $states = raid_get_config('states');
    $data['hp'] = 0;
    $data['state'] = isset($states['extraction']) ? intval($states['extraction']) : 63;
    $data['pls'] = 254;
    $data['pgroup'] = 0;
    $data['money'] = 0;
    $data['bid'] = 0;
    $data['action'] = '';
    $data['endtime'] = isset($now) ? $now : time();
}

// 直接撤离并结算个人历史最高分 / Extract immediately and settle the player's lifetime best
function raid_extract_player(&$data)
{
    global $db, $tablepre, $now;
    raid_routes_decode_clbpara($data);
    if (!raid_can_use_route_command($data)) {
        raid_routes_log('战斗、对话或异常状态中不能撤离。', 'red');
        return false;
    }

    // 撤离者携带的封印部件必须重新投送 / Seal parts carried by an evacuee must be retransmitted
    $part_ids = Array();
    for ($i = 0; $i <= 6; $i++) {
        $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($data, $i));
        if ($part_id !== '') $part_ids[] = $part_id;
    }
    foreach (array_unique($part_ids) as $part_id) {
        raid_retransmit_seal_part($part_id, Array(intval($data['pls'])), $data, '持有者撤离');
    }

    $final_score = intval($data['clbpara']['raid']['score']);
    $data['clbpara']['raid']['final_score'] = $final_score;
    $data['clbpara']['raid']['extracted'] = 1;
    raid_award_score_achievement(isset($data['name']) ? $data['name'] : '', $final_score);
    raid_routes_clear_extracted_body($data);
    if (function_exists('addnews')) addnews($now, 'raid_extract', $data['name'], $final_score);
    raid_routes_log('撤离成功，本局最终获得 '.$final_score.' RAID积分。', 'lime');

    if (function_exists('player_save')) player_save($data);
    if (function_exists('save_gameinfo')) save_gameinfo();

    // 最后一名活人撤离后按无人幸存结束 / End as no survivors after the final living player extracts
    if (isset($db, $tablepre) && function_exists('gameover')) {
        $result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=0 AND hp>0");
        if (!$db->num_rows($result)) gameover($now, 'end1');
    }
    return true;
}

// 从尸体一次性接收其收纳账本 / Transfer a corpse's stash ledger exactly once
function raid_claim_corpse_ledger(&$data, $corpse_pid = 0)
{
    global $db, $tablepre, $bid;
    raid_routes_decode_clbpara($data);
    if (!raid_can_use_route_command($data, true)) {
        raid_routes_log('你现在无法读取尸体上的收纳账本。', 'red');
        return false;
    }
    $corpse_pid = intval($corpse_pid ? $corpse_pid : $bid);
    if ($corpse_pid <= 0 || $corpse_pid === intval($data['pid'])) {
        raid_routes_log('找不到可接收的收纳账本。', 'red');
        return false;
    }
    $result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='{$corpse_pid}' AND hp<=0 LIMIT 1");
    if (!$db->num_rows($result)) {
        raid_routes_log('尸体已经不在这里了。', 'red');
        return false;
    }
    $corpse = $db->fetch_array($result);
	$corpse_clbpara_raw = isset($corpse['clbpara']) ? (string)$corpse['clbpara'] : '';
    raid_routes_decode_clbpara($corpse);
    if (intval($corpse['pls']) !== intval($data['pls']) || intval($corpse['state']) === 63) {
        raid_routes_log('这具尸体上没有可读取的RAID账本。', 'red');
        return false;
    }
    $ledger = &$corpse['clbpara']['raid'];
    if (!empty($ledger['ledger_claimed']) || (empty($ledger['score']) && empty($ledger['stored_items']))) {
        raid_routes_log('这份收纳账本已经被取走了。', 'red');
        return false;
    }

    $finder_pid = intval($data['pid']);
    $finder_result = $db->query("SELECT clbpara FROM {$tablepre}players WHERE pid='{$finder_pid}' LIMIT 1");
    if (!$db->num_rows($finder_result)) {
        raid_routes_log('你的RAID账本状态已经发生变化。', 'red');
        return false;
    }
    $finder_row = $db->fetch_array($finder_result);
    $finder_clbpara_raw = isset($finder_row['clbpara']) ? (string)$finder_row['clbpara'] : '';

    $score = intval($ledger['score']);
    $items = !empty($ledger['stored_items']) && is_array($ledger['stored_items']) ? $ledger['stored_items'] : Array();
    $ledger['score'] = 0;
    $ledger['stored_items'] = Array();
    $ledger['ledger_claimed'] = 1;

    // 先构造领取者的新账本，但在双行CAS成功前不改动内存。
    // Build the finder's new ledger without mutating memory until the two-row CAS succeeds.
    $finder_clbpara_new = $data['clbpara'];
    $finder_clbpara_new['raid']['score'] += $score;
    $finder_clbpara_new['raid']['stored_items'] = array_merge($finder_clbpara_new['raid']['stored_items'], $items);

	// 在一条MySQL多表UPDATE中同时写入双方账本；MyISAM会为整条语句持有表锁。
	// Update both ledgers in one MySQL multi-table statement; MyISAM holds its table lock for the statement.
	$corpse_old_para_sql = raid_mysql_string_literal($corpse_clbpara_raw);
	$corpse_new_para_sql = raid_mysql_string_literal(raid_encode_itempara($corpse['clbpara']));
	$finder_old_para_sql = raid_mysql_string_literal($finder_clbpara_raw);
	$finder_new_para_sql = raid_mysql_string_literal(raid_encode_itempara($finder_clbpara_new));
    $finder_pls = intval($data['pls']);
	$db->query("UPDATE {$tablepre}players AS corpse ".
        "INNER JOIN {$tablepre}players AS finder ON finder.pid='{$finder_pid}' ".
        "SET corpse.clbpara={$corpse_new_para_sql}, finder.clbpara={$finder_new_para_sql} ".
        "WHERE corpse.pid='{$corpse_pid}' AND corpse.hp<=0 AND corpse.state<>63 AND corpse.pls='{$finder_pls}' ".
        "AND BINARY COALESCE(corpse.clbpara,'')={$corpse_old_para_sql} ".
        "AND finder.type=0 AND finder.hp>0 AND finder.state<10 AND finder.pls='{$finder_pls}' ".
        "AND BINARY COALESCE(finder.clbpara,'')={$finder_old_para_sql}");
	$affected_rows = intval($db->affected_rows());
	if ($affected_rows !== 2) {
		raid_routes_log('另一名玩家先一步取走了这份收纳账本。', 'red');
		return false;
	}

    $data['clbpara'] = $finder_clbpara_new;
    raid_routes_log('你接收了尸体的收纳账本，获得 '.$score.' RAID积分与 '.count($items).' 件记录。', 'lime');
    raid_sync_tasks($data, false);
    return true;
}

// 创建一个配置宝藏候选 / Build one configured treasure candidate
function raid_routes_build_treasure_candidate($special = false)
{
    if (function_exists('raid_generate_treasure_item')) {
        return raid_generate_treasure_item($special ? 'special' : 'normal');
    }
    $treasure = raid_get_config('treasure');
    $kind = isset($treasure['item_kind']) ? $treasure['item_kind'] : 'RAIDT';
    if ($special && !empty($treasure['special'])) {
        $cfg = $treasure['special'][array_rand($treasure['special'])];
        return Array(
            'itm' => $cfg['name'], 'itmk' => $kind,
            'itme' => intval($cfg['rarity']), 'itms' => 1,
            'itmsk' => isset($treasure['item_special']) ? $treasure['item_special'] : 'Z',
            'itmpara' => raid_encode_itempara(Array('raid_treasure' => Array(
                'rarity' => intval($cfg['rarity']), 'value' => intval($cfg['value']), 'special' => 1,
            ))),
        );
    }
    $normal = isset($treasure['normal']) ? $treasure['normal'] : Array();
    $prefix = !empty($normal['prefixes']) ? $normal['prefixes'][array_rand($normal['prefixes'])] : '';
    $name = !empty($normal['names']) ? $normal['names'][array_rand($normal['names'])] : '异常回收物';
    $suffix = !empty($normal['suffixes']) ? $normal['suffixes'][array_rand($normal['suffixes'])] : '';
    $rarity_range = isset($normal['rarity_range']) ? $normal['rarity_range'] : Array(1, 4);
    $value_range = isset($normal['value_range']) ? $normal['value_range'] : Array(300, 6000);
    $rarity = rand(intval($rarity_range[0]), intval($rarity_range[1]));
    $value = rand(intval($value_range[0]), intval($value_range[1]));
    return Array(
        'itm' => $prefix.$name.$suffix, 'itmk' => $kind,
        'itme' => $rarity, 'itms' => 1, 'itmsk' => '',
        'itmpara' => raid_encode_itempara(Array('raid_treasure' => Array('rarity' => $rarity, 'value' => $value))),
    );
}

// 判断物品是否是本轮指定PVE材料 / Check whether an item matches this round's PVE material
function raid_is_pve_material($item, $material = null)
{
    if ($material === null) {
        $state = &raid_routes_state();
        $material = isset($state['material']) ? $state['material'] : Array();
    }
    if (empty($material['itm']) || empty($item['itm'])) return false;
    if ((string)$item['itm'] !== (string)$material['itm']) return false;
    if (!empty($material['itmk']) && (string)$item['itmk'] !== (string)$material['itmk']) return false;
    return true;
}

// 计算材料的数据转换值，投送补量可携带独立的数据密度 / Calculate PVE data value with supply-density support
function raid_pve_item_score($item)
{
    $para = raid_decode_itempara(isset($item['itmpara']) ? $item['itmpara'] : '');
    if (!empty($para['raid_pve_supply_value'])) return max(1, intval($para['raid_pve_supply_value']));
    return raid_calculate_item_score($item, true);
}

// 开局随机指定材料，并投放足以完成任务的补量 / Select the round material and seed enough supply to finish
function raid_initialize_pve_route()
{
    global $db, $tablepre;
    $state = &raid_routes_state();
    if (!empty($state['material']['itm'])) return $state['material'];
    $pve = raid_get_config('pve');
    $target = isset($pve['data_target']) ? intval($pve['data_target']) : 50000;
    $margin = isset($pve['material_supply_margin']) ? floatval($pve['material_supply_margin']) : 1.25;
    $disabled = raid_get_config();
    $disabled_names = isset($disabled['disabled_route_items']) ? $disabled['disabled_route_items'] : Array();
    $sources = !empty($pve['material_sources']) && is_array($pve['material_sources'])
        ? $pve['material_sources'] : Array('mapitem', 'normal_treasure', 'special_treasure');
    $candidates = Array();

    if (in_array('mapitem', $sources, true) && isset($db, $tablepre)) {
        $result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE itms<>'0' AND pls<100");
        while ($item = $db->fetch_array($result)) {
            if (empty($item['itm']) || empty($item['itmk']) || strpos($item['itmk'], 'T') === 0) continue;
            if (in_array($item['itm'], $disabled_names, true) || raid_is_protected_item($item)) continue;
            $key = $item['itm'].'|'.$item['itmk'];
            if (!isset($candidates[$key])) $candidates[$key] = $item;
        }
    }
    $treasure_candidates = Array();
    if (in_array('special_treasure', $sources, true)) $treasure_candidates[] = raid_routes_build_treasure_candidate(true);
    if (in_array('normal_treasure', $sources, true)) {
        for ($i = 0; $i < 3; $i++) $treasure_candidates[] = raid_routes_build_treasure_candidate(false);
    }
    foreach ($treasure_candidates as $candidate) {
        $candidates[$candidate['itm'].'|'.$candidate['itmk']] = $candidate;
    }
    if (empty($candidates)) $candidates['fallback'] = raid_routes_build_treasure_candidate(true);
    $material = $candidates[array_rand($candidates)];
    unset($material['iid'], $material['pls']);
    $material['name'] = $material['itm'];
    $state['material'] = $material;
    $state['progress'] = 0;
    $state['parts'] = Array();
    $state['parts_released'] = false;
    $state['victory_complete'] = false;

    // 普通低价值材料以“芙蓉标记补量”提供数据密度，避免产生数千条地图记录。
    // Low-value materials receive Fu Rong supply density so thousands of map rows are unnecessary.
    if (isset($db, $tablepre)) {
        $need = max($target, intval(ceil($target * $margin)));
        $current_value = 0;
        $result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE itms<>'0'");
        while ($item = $db->fetch_array($result)) {
            if (raid_is_pve_material($item, $material)) $current_value += raid_pve_item_score($item);
        }
        $per_supply = max(500, intval(ceil($need / 24)));
        while ($current_value < $need) {
            $supply = $material;
            $para = raid_decode_itempara(isset($supply['itmpara']) ? $supply['itmpara'] : '');
            if (!raid_is_treasure_item($supply)) $para['raid_pve_supply_value'] = $per_supply;
            $supply['itmpara'] = raid_encode_itempara($para);
            $supply['pls'] = raid_routes_pick_location();
            $db->array_insert("{$tablepre}mapitem", Array(
                'itm' => $supply['itm'], 'itmk' => $supply['itmk'], 'itme' => $supply['itme'],
                'itms' => $supply['itms'], 'itmsk' => $supply['itmsk'],
                'itmpara' => $supply['itmpara'], 'pls' => $supply['pls'],
            ));
            $current_value += raid_pve_item_score($supply);
        }
    }
    raid_routes_save_state();
    return $state['material'];
}

// 在代码源流消耗一整件指定材料并增加共享进度 / Consume one whole target material at Code Source
function raid_convert_material_slot(&$data, $slot)
{
    $slot = intval($slot);
    raid_routes_decode_clbpara($data);
    $pve = raid_get_config('pve');
    $source = isset($pve['source_location']) ? intval($pve['source_location']) : 121;
    if (!raid_can_use_route_command($data) || intval($data['pls']) !== $source) {
        raid_routes_log('只有在【代码源流】且处于普通状态时才能转换材料。', 'red');
        return false;
    }
    $state = &raid_routes_state();
    if (empty($state['material'])) raid_initialize_pve_route();
    $item = raid_get_item_from_slot($data, $slot);
    if (!raid_is_item_present($item) || !raid_is_pve_material($item, $state['material'])) {
        raid_routes_log('这个物品不是芙蓉指定的本轮材料。', 'red');
        return false;
    }
    $value = raid_pve_item_score($item);
    raid_clear_item_slot($data, $slot);
    $target = isset($pve['data_target']) ? intval($pve['data_target']) : 50000;
    $state['progress'] = min($target, intval($state['progress']) + $value);
    $data['clbpara']['raid']['pve_contribution'] += $value;
    raid_routes_log('「'.$item['itm'].'」已转换为 '.$value.' 点封印数据。', 'lime');
    if (intval($state['progress']) >= $target && empty($state['parts_released'])) {
        raid_release_seal_parts();
    }
    raid_routes_save_state();
    raid_sync_tasks($data, false);
    return true;
}

// 将配置部件按ID建立索引 / Index configured seal parts by ID
function raid_configured_seal_parts()
{
    $pve = raid_get_config('pve');
    $result = Array();
    foreach ((array)(isset($pve['parts']) ? $pve['parts'] : Array()) as $index => $part) {
        $id = !empty($part['id']) ? (string)$part['id'] : 'part_'.($index + 1);
        $part['id'] = $id;
        $result[$id] = $part;
    }
    return $result;
}

// 在地图中插入一个封印部件 / Insert one seal part into the map
function raid_insert_seal_part($part_id, $pls)
{
    global $db, $tablepre;
    $parts = raid_configured_seal_parts();
    if (empty($parts[$part_id]) || !isset($db, $tablepre)) return false;
    $part = $parts[$part_id];
    $para = Array('raid_part' => Array('id' => $part_id), 'raid_protected' => 1);
    $db->array_insert("{$tablepre}mapitem", Array(
        'itm' => $part['name'], 'itmk' => isset($part['itmk']) ? $part['itmk'] : 'Y',
        'itme' => isset($part['itme']) ? $part['itme'] : 1,
        'itms' => isset($part['itms']) ? $part['itms'] : 1,
        'itmsk' => isset($part['itmsk']) ? $part['itmsk'] : 'Z',
        'itmpara' => raid_encode_itempara($para), 'pls' => intval($pls),
    ));
    return true;
}

// 达成数据目标后投送三个部件并生成互异部署点 / Release all parts with distinct pickup and deploy sites
function raid_release_seal_parts()
{
    global $now;
    $state = &raid_routes_state();
    if (!empty($state['parts_released'])) return false;
    $configured = raid_configured_seal_parts();
    if (empty($configured)) return false;
    $used_pickups = Array();
    $used_deploys = Array();
    $state['parts'] = Array();
    foreach ($configured as $part_id => $part) {
        $pickup = raid_routes_pick_location($used_pickups);
        $used_pickups[] = $pickup;
        $deploy = raid_routes_pick_location(array_merge($used_deploys, Array($pickup)));
        $used_deploys[] = $deploy;
        $state['parts'][$part_id] = Array(
            'id' => $part_id, 'name' => $part['name'],
            'pickup' => $pickup, 'deploy' => $deploy,
            'deployed' => false, 'status' => 'ground', 'map' => $pickup,
            'holder_pid' => 0, 'holder_name' => '',
        );
        raid_insert_seal_part($part_id, $pickup);
    }
    $state['parts_released'] = true;
    if (function_exists('addnews')) addnews($now, 'raid_pve_ready', count($state['parts']));
    raid_routes_save_state();
    return true;
}

// 清理某部件在地图和玩家身上的全部副本 / Remove every map/player copy of one part
function raid_remove_all_part_copies($part_id, &$current_data = null)
{
    global $db, $tablepre;
    if (isset($current_data) && is_array($current_data)) {
        for ($i = 0; $i <= 6; $i++) {
            if (raid_get_part_id_from_item(raid_get_item_from_slot($current_data, $i)) === $part_id) {
                raid_clear_item_slot($current_data, $i);
            }
        }
    }
    if (!isset($db, $tablepre)) return;
    $map_result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE itmpara LIKE '%raid_part%'");
    while ($item = $db->fetch_array($map_result)) {
        if (raid_get_part_id_from_item($item) === $part_id) {
            $db->query("DELETE FROM {$tablepre}mapitem WHERE iid='".intval($item['iid'])."'");
        }
    }
    $players = $db->query("SELECT * FROM {$tablepre}players");
    while ($player = $db->fetch_array($players)) {
        if (isset($current_data['pid']) && intval($player['pid']) === intval($current_data['pid'])) continue;
        $updates = Array();
        for ($i = 0; $i <= 6; $i++) {
            if (raid_get_part_id_from_item(raid_get_item_from_slot($player, $i)) === $part_id) {
                $updates['itm'.$i] = ''; $updates['itmk'.$i] = ''; $updates['itme'.$i] = 0;
                $updates['itms'.$i] = 0; $updates['itmsk'.$i] = ''; $updates['itmpara'.$i] = '';
            }
        }
        if (!empty($updates)) $db->array_update("{$tablepre}players", $updates, "pid='".intval($player['pid'])."'");
    }
}

// 在部件丢失、撤离或清图后重新投送 / Retransmit a part after loss, extraction or a map wipe
function raid_retransmit_seal_part($part_id, $exclude = Array(), &$current_data = null, $reason = '')
{
    $state = &raid_routes_state();
    $configured = raid_configured_seal_parts();
    if (empty($configured[$part_id])) return false;
    raid_remove_all_part_copies($part_id, $current_data);
    $other_locations = (array)$exclude;
    foreach ($state['parts'] as $id => $part_state) {
        if ($id !== $part_id && empty($part_state['deployed']) && isset($part_state['map'])) {
            $other_locations[] = intval($part_state['map']);
        }
    }
    $pickup = raid_routes_pick_location($other_locations);
    raid_insert_seal_part($part_id, $pickup);
    if (empty($state['parts'][$part_id])) {
        $state['parts'][$part_id] = Array('id' => $part_id, 'name' => $configured[$part_id]['name']);
    }
    $part = &$state['parts'][$part_id];
    $part['pickup'] = $pickup;
    $part['map'] = $pickup;
    $part['status'] = 'ground';
    $part['deployed'] = false;
    $part['holder_pid'] = 0;
    $part['holder_name'] = '';
    if ($reason !== '') raid_routes_log($part['name'].'因'.$reason.'已被重新投送至'.raid_routes_location_name($pickup).'。', 'cyan');
    return true;
}

// 从即将被清除的玩家/尸体上回收全部部件 / Recover every part from a player or corpse about to be erased
function raid_recover_seal_parts_from_player(&$player, $reason = '载体消失', $exclude_pls = -1)
{
    $part_ids = Array();
    for ($i = 0; $i <= 6; $i++) {
        $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($player, $i));
        if ($part_id !== '') $part_ids[$part_id] = true;
    }
    foreach (array_keys($part_ids) as $part_id) {
        $exclude = intval($exclude_pls) >= 0 ? Array(intval($exclude_pls)) : Array();
        raid_retransmit_seal_part($part_id, $exclude, $player, $reason);
    }
    if (!empty($part_ids)) raid_routes_save_state();
    return array_keys($part_ids);
}

// 重新扫描部件位置，修复尸体清除、撤离与禁区造成的软锁 / Reconcile part locations and recover soft locks
function raid_reconcile_seal_parts(&$current_data = null)
{
    global $db, $tablepre;
    $state = &raid_routes_state();
    if (empty($state['parts_released']) || empty($state['parts']) || !isset($db, $tablepre)) return;
    $pve = raid_get_config('pve');
    $configured = raid_configured_seal_parts();
    $found = Array();

    // 部署点若变成禁区，未部署部件必须改派地点 / Relocate undeployed targets that become forbidden
    $used_deploys = Array();
    foreach ($state['parts'] as $id => &$part) {
        if (!empty($part['deployed'])) {
            $used_deploys[] = intval($part['deploy']);
            continue;
        }
        if (empty($part['deploy']) || !raid_routes_location_is_safe($part['deploy'])) {
            $part['deploy'] = raid_routes_pick_location($used_deploys);
        }
        $used_deploys[] = intval($part['deploy']);
    }
    unset($part);

    $players = $db->query("SELECT * FROM {$tablepre}players");
    while ($player = $db->fetch_array($players)) {
        if (isset($current_data['pid']) && intval($player['pid']) === intval($current_data['pid'])) continue;
        for ($i = 0; $i <= 6; $i++) {
            $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($player, $i));
            if ($part_id === '' || isset($found[$part_id])) continue;
            $found[$part_id] = Array(
                'status' => intval($player['hp']) > 0 ? 'held' : 'corpse',
                'holder_pid' => intval($player['pid']),
                'holder_name' => !empty($player['nick']) ? $player['nick'] : $player['name'],
                'map' => intval($player['pls']), 'pgroup' => intval($player['pgroup']),
                'state' => intval($player['state']), 'hp' => intval($player['hp']),
            );
        }
    }
    if (isset($current_data) && is_array($current_data)) {
        for ($i = 0; $i <= 6; $i++) {
            $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($current_data, $i));
            if ($part_id === '') continue;
            $found[$part_id] = Array(
                'status' => intval($current_data['hp']) > 0 ? 'held' : 'corpse',
                'holder_pid' => intval($current_data['pid']),
                'holder_name' => !empty($current_data['nick']) ? $current_data['nick'] : $current_data['name'],
                'map' => intval($current_data['pls']), 'pgroup' => intval($current_data['pgroup']),
                'state' => intval($current_data['state']), 'hp' => intval($current_data['hp']),
            );
        }
    }
    $map_result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE itmpara LIKE '%raid_part%'");
    while ($item = $db->fetch_array($map_result)) {
        $part_id = raid_get_part_id_from_item($item);
        if ($part_id !== '' && !isset($found[$part_id])) {
            $found[$part_id] = Array('status' => 'ground', 'map' => intval($item['pls']), 'iid' => intval($item['iid']));
        }
    }

    foreach ($configured as $part_id => $definition) {
        if (!empty($state['parts'][$part_id]['deployed'])) continue;
        $recover = false;
        if (empty($found[$part_id])) {
            $recover = true;
        } else {
            $location = $found[$part_id];
            if ($location['status'] === 'held' || $location['status'] === 'corpse') {
                if (intval($location['state']) === 63 || intval($location['map']) === 254) $recover = true;
                if ($location['status'] === 'corpse' && !raid_routes_location_is_safe($location['map'])
                    && !empty($pve['retransmit_on_forbidden_zone_cleanup'])) $recover = true;
            } elseif (!raid_routes_location_is_safe($location['map'])
                && !empty($pve['retransmit_on_forbidden_zone_cleanup'])) {
                $recover = true;
            }
        }
        if ($recover) {
            raid_retransmit_seal_part($part_id, Array(), $current_data, '失去可达载体');
            $found[$part_id] = $state['parts'][$part_id];
        }
        $state['parts'][$part_id] = array_merge($state['parts'][$part_id], $found[$part_id]);
    }
    raid_routes_save_state();
}

// 响应暴走鸟清图：保护或摧毁并重投受影响部件 / Handle wipe-time part survival or destruction and retransmission
function raid_handle_seal_parts_on_wipe($pls, &$current_data = null)
{
    global $db, $tablepre;
    $pls = intval($pls);
    $state = &raid_routes_state();
    if (empty($state['parts_released']) || empty($state['parts']) || !isset($db, $tablepre)) return;
    $boss = raid_get_config('boss');
    $destroy = !empty($boss['destroy_seal_parts_on_wipe']);
    // 默认配置下清图不会触碰地面、尸体携带或已部署部件。
    // Under the default setting a wipe leaves ground, corpse-held and deployed parts untouched.
    if (!$destroy) return;
    $affected = Array();

    $map_result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls='{$pls}' AND itmpara LIKE '%raid_part%'");
    while ($item = $db->fetch_array($map_result)) {
        $part_id = raid_get_part_id_from_item($item);
        if ($part_id === '') continue;
        $db->query("DELETE FROM {$tablepre}mapitem WHERE iid='".intval($item['iid'])."'");
        $affected[$part_id] = true;
    }
    $players = $db->query("SELECT * FROM {$tablepre}players WHERE pls='{$pls}'");
    while ($player = $db->fetch_array($players)) {
        for ($i = 0; $i <= 6; $i++) {
            $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($player, $i));
            if ($part_id !== '') $affected[$part_id] = true;
        }
    }
    if (isset($current_data) && intval($current_data['pls']) === $pls) {
        for ($i = 0; $i <= 6; $i++) {
            $part_id = raid_get_part_id_from_item(raid_get_item_from_slot($current_data, $i));
            if ($part_id !== '') $affected[$part_id] = true;
        }
    }
    foreach ($state['parts'] as $part_id => &$part) {
        if (!empty($part['deployed']) && intval($part['deploy']) === $pls) {
            $part['deployed'] = false;
            $affected[$part_id] = true;
        }
    }
    unset($part);
    foreach (array_keys($affected) as $part_id) {
        raid_retransmit_seal_part($part_id, Array($pls), $current_data, '清图摧毁');
    }
    raid_routes_save_state();
}

// 使用封印部件；只有对应部署点会成功 / Use a seal part at its exact deployment site
function raid_use_seal_part(&$data, $slot, $item = null)
{
    raid_routes_decode_clbpara($data);
    $slot = intval($slot);
    if ($item === null) $item = raid_get_item_from_slot($data, $slot);
    $part_id = raid_get_part_id_from_item($item);
    if ($part_id === '') return false;
    if (!raid_can_use_route_command($data)) {
        raid_routes_log('战斗、对话或异常状态中不能部署封印部件。', 'red');
        return true;
    }
    $state = &raid_routes_state();
    if (empty($state['parts'][$part_id]) || !empty($state['parts'][$part_id]['deployed'])) {
        raid_clear_item_slot($data, $slot);
        raid_routes_log('这个部件已经失去部署权限。', 'red');
        return true;
    }
    $part = &$state['parts'][$part_id];
    if (intval($data['pls']) !== intval($part['deploy'])) {
        raid_routes_log($part['name'].'必须在'.raid_routes_location_name($part['deploy']).'部署。', 'red');
        return true;
    }
    raid_clear_item_slot($data, $slot);
    $part['deployed'] = true;
    $part['status'] = 'deployed';
    $part['map'] = intval($part['deploy']);
    $part['holder_pid'] = 0;
    $part['holder_name'] = '';
    raid_routes_log($part['name'].'部署完成。', 'lime');
    raid_routes_save_state();

    $all_deployed = true;
    foreach ($state['parts'] as $part_state) if (empty($part_state['deployed'])) $all_deployed = false;
    if ($all_deployed) raid_finish_pve_victory($data);
    return true;
}

// 找到背包中的指定部件 / Find a configured seal part in inventory
function raid_find_part_slot($data, $part_id)
{
    for ($i = 0; $i <= 6; $i++) {
        if (raid_get_part_id_from_item(raid_get_item_from_slot($data, $i)) === $part_id) return $i;
    }
    return -1;
}

// 第三部件部署者的当前存活、未撤离队伍取得胜利 / Award the living, non-extracted current team of the final deployer
function raid_finish_pve_victory(&$deployer)
{
    global $db, $tablepre, $now;
    $state = &raid_routes_state();
    if (!empty($state['victory_complete'])) return false;
    $state['victory_complete'] = true;
    raid_routes_decode_clbpara($deployer);
    $team_id = isset($deployer['teamID']) ? (string)$deployer['teamID'] : '';
    $candidates = Array();

    if ($team_id === '') {
        $candidates[intval($deployer['pid'])] = $deployer;
    } else {
		$team_sql = raid_mysql_string_literal($team_id);
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE type=0 AND hp>0 AND state<10 AND teamID={$team_sql} ORDER BY pid");
        while ($player = $db->fetch_array($result)) {
            raid_routes_decode_clbpara($player);
            $candidates[intval($player['pid'])] = $player;
        }
        $candidates[intval($deployer['pid'])] = $deployer;
    }

    // 部署者优先，因此同IP小号不会挤掉实际完成者 / Prioritize the deployer during same-IP deduplication
    $ordered = Array(intval($deployer['pid']) => $deployer) + $candidates;
    $seen_ips = Array();
    $winner_names = Array();
    $winner_pids = Array();
    foreach ($ordered as $pid => $player) {
        if (intval($player['hp']) <= 0 || intval($player['state']) >= 10 || intval($player['state']) === 63) continue;
        $ip = isset($player['ip']) ? trim((string)$player['ip']) : '';
        if ($ip !== '' && isset($seen_ips[$ip])) continue;
        if ($ip !== '') $seen_ips[$ip] = true;
        raid_routes_decode_clbpara($player);
        $final_score = raid_calculate_pve_reward(intval($player['clbpara']['raid']['score']));
        $player['clbpara']['raid']['final_score'] = $final_score;
        $player['clbpara']['raid']['pve_winner'] = 1;
        raid_award_score_achievement($player['name'], $final_score);
        if (intval($pid) === intval($deployer['pid'])) $deployer = $player;
        if (function_exists('player_save')) player_save($player);
        $winner_names[] = $player['name'];
        $winner_pids[] = intval($pid);
    }
    $state['winner_names'] = $winner_names;
    $state['winner_pids'] = $winner_pids;
    $state['winners'] = $winner_names;
    $state['winning_team'] = $team_id;
    $state['final_deployer'] = $deployer['name'];
    raid_routes_save_state();
    // gameover(end8) 会输出最终封印新闻 / gameover(end8) emits the final sealing news
    if (function_exists('gameover')) gameover($now, 'end8', $deployer['name']);
    return true;
}

// 使用可重复折跃信标进出代码源流 / Use the reusable beacon to enter or leave Code Source
function raid_use_warp_beacon(&$data, $slot, $item = null)
{
    raid_routes_decode_clbpara($data);
    if ($item === null) $item = raid_get_item_from_slot($data, $slot);
    $pve = raid_get_config('pve');
    $warp_name = isset($pve['warp_item_name']) ? $pve['warp_item_name'] : '【源流折跃信标】';
    if (empty($item['itm']) || $item['itm'] !== $warp_name) return false;
    $source = isset($pve['source_location']) ? intval($pve['source_location']) : 121;
    $hidden_group = isset($pve['hidden_group']) ? intval($pve['hidden_group']) : 3;

    if (intval($data['pls']) === $source && intval($data['pgroup']) === $hidden_group) {
        $return = !empty($data['clbpara']['raid']['warp_return']) ? $data['clbpara']['raid']['warp_return'] : Array();
        $return_pls = isset($return['pls']) ? intval($return['pls']) : raid_routes_pick_location();
        if (!raid_routes_location_is_safe($return_pls)) $return_pls = raid_routes_pick_location();
        $data['pls'] = $return_pls;
        $data['pgroup'] = 0;
        raid_routes_log('折跃信标将你送回'.raid_routes_location_name($return_pls).'。', 'cyan');
    } else {
        $data['clbpara']['raid']['warp_return'] = Array(
            'pls' => intval($data['pls']), 'pgroup' => intval(isset($data['pgroup']) ? $data['pgroup'] : 0),
        );
        $data['pls'] = $source;
        $data['pgroup'] = $hidden_group;
        raid_routes_log('折跃完成：你已进入【代码源流】。', 'cyan');
    }
    return true;
}

// 统一处理RAID专属物品使用 / Dispatch RAID-specific item use
function raid_handle_route_itemuse(&$data, $slot, $item)
{
    if (raid_use_warp_beacon($data, $slot, $item)) return true;
    if (raid_get_part_id_from_item($item) !== '') return raid_use_seal_part($data, $slot, $item);
    return false;
}

// 统一处理面板与尸体界面的RAID命令；null表示不是本模式命令 / Dispatch RAID commands; null means unrecognized
function raid_handle_route_command(&$data, $command)
{
    global $bid;
    if (preg_match('/^raid_store_([0-6])$/', $command, $match)) {
        raid_store_item($data, intval($match[1]));
        return true;
    }
    if ($command === 'raid_extract') {
        raid_extract_player($data);
        return true;
    }
    if (preg_match('/^raid_convert_([0-6])$/', $command, $match)) {
        raid_convert_material_slot($data, intval($match[1]));
        return true;
    }
    if ($command === 'raid_claim_corpse') {
        raid_claim_corpse_ledger($data, isset($bid) ? intval($bid) : 0);
        return true;
    }
    if (preg_match('/^raid_deploy_([a-zA-Z0-9_\-]+)$/', $command, $match)) {
        $slot = raid_find_part_slot($data, $match[1]);
        if ($slot < 0) raid_routes_log('你没有携带这个封印部件。', 'red');
        else raid_use_seal_part($data, $slot);
        return true;
    }
    return null;
}

// 整理鸟事件状态供任务面板显示 / Normalize boss event status for the task panel
function raid_panel_boss_status($data)
{
    global $db, $tablepre;
    $state = &raid_routes_state();
    $action_cfg = raid_get_config('action');
    $boss = !empty($state['boss']) && is_array($state['boss']) ? $state['boss'] : Array();
    $spawned = !empty($state['boss_spawned']) || !empty($boss['spawned']);
    $boss_pls = isset($state['boss_pls']) ? intval($state['boss_pls'])
        : (isset($boss['pls']) ? intval($boss['pls']) : -1);
    if ($spawned && $boss_pls < 0 && isset($db, $tablepre)) {
        $result = $db->query("SELECT pls FROM {$tablepre}players WHERE type=94 AND hp>0 LIMIT 1");
        if ($db->num_rows($result)) $boss_pls = intval($db->result($result, 0));
    }
    if (!$spawned) {
        $actions = intval(isset($data['clbpara']['raid']['trigger_actions']) ? $data['clbpara']['raid']['trigger_actions'] : 0);
        $spawn_threshold = max(1, intval(isset($action_cfg['spawn_actions_per_player']) ? $action_cfg['spawn_actions_per_player'] : 10));
        return Array(
            'progress' => min($spawn_threshold, $actions), 'max' => $spawn_threshold,
            'desc' => '异常尚未现身；你的有效移动/探索：'.$actions.'/'.$spawn_threshold.'。',
        );
    }
    $charging = !empty($state['boss_charging']) || !empty($boss['charging']);
    $remaining = isset($state['boss_charge_remaining']) ? intval($state['boss_charge_remaining'])
        : intval(isset($boss['charge_remaining']) ? $boss['charge_remaining'] : 0);
    $counter = isset($state['boss_action_counter']) ? intval($state['boss_action_counter'])
        : intval(isset($boss['action_counter']) ? $boss['action_counter'] : 0);
    $target = isset($state['target_name']) && $state['target_name'] !== '' ? $state['target_name']
        : (isset($state['boss_target_name']) ? $state['boss_target_name']
            : (isset($boss['target_name']) ? $boss['target_name'] : '未知目标'));
    $desc = '当前位置：'.raid_routes_location_name($boss_pls).'；追击：'.$target.'；行动计数：'.$counter.'。';
    if ($charging) $desc .= ' 正在充能，距地图消灭还有 '.$remaining.' 次鸟行动！';
    $action_threshold = max(1, intval(isset($action_cfg['boss_action_threshold']) ? $action_cfg['boss_action_threshold'] : 3));
    return Array('progress' => $counter, 'max' => $action_threshold, 'desc' => $desc);
}

// 生成任务面板所需的鸟/PvP/PvE/部件视图 / Build Bird, PvP, PvE and part task-panel entries
function raid_sync_tasks(&$data, $reconcile = true)
{
    raid_routes_decode_clbpara($data);
    if ($reconcile) raid_reconcile_seal_parts($data);
    $state = &raid_routes_state();
    $pve = raid_get_config('pve');
    $tasks = Array();

    $boss_view = raid_panel_boss_status($data);
    $tasks['raid_bird'] = Array(
        'title' => 'RAID：暴走笼中鸟', 'step_desc' => $boss_view['desc'],
        'progress' => $boss_view['progress'], 'progress_max' => $boss_view['max'], 'actions' => Array(),
    );

    $stored = Array();
    foreach ($data['clbpara']['raid']['stored_items'] as $entry) {
        $stored[] = $entry['name'].'（'.$entry['score'].'分）';
    }
    $pvp_actions = Array();
    $route_available = raid_can_use_route_command($data);
    $warp_name = isset($pve['warp_item_name']) ? $pve['warp_item_name'] : '【源流折跃信标】';
    for ($i = 0; $i <= 6; $i++) {
        $item = raid_get_item_from_slot($data, $i);
        if (!raid_is_item_present($item) || raid_is_protected_item($item) || $item['itm'] === $warp_name) continue;
        $pvp_actions['store_'.$i] = Array(
            'label' => '收纳'.$item['itm'], 'command' => 'raid_store_'.$i, 'disabled' => !$route_available,
        );
    }
    $pvp_actions['extract'] = Array(
        'label' => '立即撤离', 'command' => 'raid_extract',
        'disabled' => !$route_available,
    );
    $tasks['raid_pvp'] = Array(
        'title' => '搜打撤路线 - PvP',
        'step_desc' => '当前总积分：'.intval($data['clbpara']['raid']['score']).'；已收纳：'
            .(empty($stored) ? '无' : implode('、', $stored)),
        'progress' => intval($data['clbpara']['raid']['score']), 'progress_max' => 0,
        'actions' => $pvp_actions,
    );

    $material = isset($state['material']) ? $state['material'] : Array();
    $target = isset($pve['data_target']) ? intval($pve['data_target']) : 50000;
    $source = isset($pve['source_location']) ? intval($pve['source_location']) : 121;
    $pve_actions = Array();
    if (intval($data['pls']) === $source && intval($state['progress']) < $target) {
        for ($i = 0; $i <= 6; $i++) {
            $item = raid_get_item_from_slot($data, $i);
            if (raid_is_item_present($item) && raid_is_pve_material($item, $material)) {
                $pve_actions['convert_'.$i] = Array(
                    'label' => '转换'.$item['itm'], 'command' => 'raid_convert_'.$i, 'disabled' => !$route_available,
                );
            }
        }
    }
    $tasks['raid_pve'] = Array(
        'title' => '平息笼中鸟 - PvE',
        'step_desc' => '指定材料：'.(!empty($material['itm']) ? $material['itm'] : '正在解析').'；在【代码源流】转换。',
        'progress' => intval($state['progress']), 'progress_max' => $target,
        'actions' => $pve_actions,
    );

    if (!empty($state['parts_released'])) {
        $part_lines = Array();
        $part_actions = Array();
        foreach ($state['parts'] as $part_id => $part) {
            $deploy_name = raid_routes_location_name(isset($part['deploy']) ? $part['deploy'] : 0);
            if (!empty($part['deployed'])) {
                $where = '已在'.$deploy_name.'部署';
            } elseif (isset($part['status']) && $part['status'] === 'held') {
                $where = '由'.$part['holder_name'].'持有（'.raid_routes_location_name($part['map'], isset($part['pgroup']) ? $part['pgroup'] : 0).'）';
            } elseif (isset($part['status']) && $part['status'] === 'corpse') {
                $where = '位于'.$part['holder_name'].'的尸体（'.raid_routes_location_name($part['map'], isset($part['pgroup']) ? $part['pgroup'] : 0).'）';
            } else {
                $where = '位于'.raid_routes_location_name(isset($part['map']) ? $part['map'] : $part['pickup']);
            }
            $part_lines[] = $part['name'].'：'.$where.'；部署点：'.$deploy_name;
            $slot = raid_find_part_slot($data, $part_id);
            if ($slot >= 0) {
                $part_actions['deploy_'.$part_id] = Array(
                    'label' => '部署'.$part['name'], 'command' => 'raid_deploy_'.$part_id,
                    'disabled' => !$route_available || intval($data['pls']) !== intval($part['deploy']),
                );
            }
        }
        $deployed = 0;
        foreach ($state['parts'] as $part) if (!empty($part['deployed'])) $deployed++;
        $tasks['raid_parts'] = Array(
            'title' => '封印装置部件', 'step_desc' => implode('；', $part_lines),
            'progress' => $deployed, 'progress_max' => count($state['parts']), 'actions' => $part_actions,
        );
    }
    $data['clbpara']['raid']['tasks'] = $tasks;
    return $tasks;
}

// 兼容编排层使用的面板函数名 / Compatibility alias used by the ruleset orchestrator
function raid_sync_task_panel(&$data, $reconcile = false)
{
    return raid_sync_tasks($data, $reconcile);
}

?>
