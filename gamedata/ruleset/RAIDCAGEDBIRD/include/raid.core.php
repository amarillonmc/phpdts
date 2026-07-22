<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * RAID shared helpers and pure calculations.
 * RAID 共用辅助函数与纯计算逻辑。
 */

function raid_get_config($section = '')
{
    static $config_cache = null;
    global $gamecfg;

    if ($config_cache === null) {
        $raid_mode_config = Array();
        $config_file = function_exists('config') ? config('raid', isset($gamecfg) ? $gamecfg : 1)
            : GAME_ROOT.'gamedata/ruleset/RAIDCAGEDBIRD/cache/raid_1.php';
        if (file_exists($config_file)) include $config_file;
        $config_cache = is_array($raid_mode_config) ? $raid_mode_config : Array();
    }

    if ($section === '') return $config_cache;
    return isset($config_cache[$section]) && is_array($config_cache[$section])
        ? $config_cache[$section]
        : Array();
}

function raid_html($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 指令执行阶段重新持有全局进程锁，避免两个请求同时改写RAID共享状态。
// Reacquire the global process lock while a command mutates shared RAID state.
function raid_acquire_command_lock()
{
	global $raid_command_lock_handle;
	if (is_resource($raid_command_lock_handle)) return true;
	if (!defined('GAME_ROOT')) return false;

	$handle = @fopen(GAME_ROOT.'./gamedata/process.lock', 'ab');
	if (!$handle || !flock($handle, LOCK_EX)) {
		if ($handle) fclose($handle);
		return false;
	}
	$raid_command_lock_handle = $handle;

	// common.inc.php在进入指令脚本前已经释放过这把锁，因此锁内必须重读一次共享状态。
	// common.inc.php released this lock before command dispatch, so refresh shared state inside it.
	if (function_exists('load_gameinfo')) load_gameinfo();
	return true;
}

function raid_command_lock_is_held()
{
	global $raid_command_lock_handle;
	return is_resource($raid_command_lock_handle);
}

function raid_release_command_lock()
{
	global $raid_command_lock_handle;
	if (!is_resource($raid_command_lock_handle)) return;
	flock($raid_command_lock_handle, LOCK_UN);
	fclose($raid_command_lock_handle);
	$raid_command_lock_handle = null;
}

// 将文本编码为MySQL十六进制字符串字面量，避免把账号或队名直接拼入SQL。
// Encode text as a MySQL hex string literal instead of interpolating account/team names.
function raid_mysql_string_literal($value)
{
	return "X'".bin2hex((string)$value)."'";
}

function raid_decode_itempara($value)
{
    if (is_array($value)) return $value;
    if ($value === '' || $value === null) return Array();
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : Array();
}

function raid_encode_itempara($value)
{
    if (!is_array($value)) return '';
    if (function_exists('compatible_json_encode')) return compatible_json_encode($value);
    return json_encode($value, JSON_UNESCAPED_UNICODE);
}

function raid_init_player_state(&$data)
{
    if (!isset($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = function_exists('get_clbpara') ? get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : '') : Array();
    }
    if (empty($data['clbpara']['raid']) || !is_array($data['clbpara']['raid'])) {
        $data['clbpara']['raid'] = Array();
    }
    $state = &$data['clbpara']['raid'];
    if (!isset($state['trigger_actions'])) $state['trigger_actions'] = 0;
    if (!isset($state['score'])) $state['score'] = 0;
    if (empty($state['stored_items']) || !is_array($state['stored_items'])) $state['stored_items'] = Array();
    if (!isset($state['pve_contribution'])) $state['pve_contribution'] = 0;
    if (empty($state['tasks']) || !is_array($state['tasks'])) $state['tasks'] = Array();
    return $state;
}

function raid_is_boss($data)
{
    if (!is_array($data)) return false;
    if (!empty($data['skill_raid_cagedbird'])) return true;
    $clbpara = isset($data['clbpara']) ? $data['clbpara'] : Array();
    if (!is_array($clbpara) && function_exists('get_clbpara')) $clbpara = get_clbpara($clbpara);
    if (!empty($clbpara['skill']) && in_array('raid_cagedbird', $clbpara['skill'], true)) return true;
    // 94号类型作为旧存档的兼容后备；实际机制优先由技能承载。
    // Type 94 is a legacy-save fallback; the mechanic itself is skill-driven.
	$boss = raid_get_config('boss');
	return intval(isset($data['type']) ? $data['type'] : 0)
		=== intval(isset($boss['type']) ? $boss['type'] : 94);
}

function raid_is_container($data)
{
    if (!is_array($data)) return false;
	$containers = raid_get_config('containers');
	if (intval(isset($data['type']) ? $data['type'] : 0)
		=== intval(isset($containers['type']) ? $containers['type'] : 93)) return true;
    $clbpara = raid_decode_itempara(isset($data['clbpara']) ? $data['clbpara'] : '');
    return !empty($clbpara['raid_container']);
}

function raid_is_treasure_item($item)
{
    $para = raid_decode_itempara(isset($item['itmpara']) ? $item['itmpara'] : '');
    return !empty($para['raid_treasure']) && is_array($para['raid_treasure']);
}

function raid_get_part_id_from_item($item)
{
    $para = raid_decode_itempara(isset($item['itmpara']) ? $item['itmpara'] : '');
    return !empty($para['raid_part']['id']) ? (string)$para['raid_part']['id'] : '';
}

function raid_get_item_from_slot($data, $slot)
{
    $slot = intval($slot);
    if ($slot < 0 || $slot > 6) return Array();
    return Array(
        'itm' => isset($data['itm'.$slot]) ? $data['itm'.$slot] : '',
        'itmk' => isset($data['itmk'.$slot]) ? $data['itmk'.$slot] : '',
        'itme' => isset($data['itme'.$slot]) ? $data['itme'.$slot] : 0,
        'itms' => isset($data['itms'.$slot]) ? $data['itms'.$slot] : 0,
        'itmsk' => isset($data['itmsk'.$slot]) ? $data['itmsk'.$slot] : '',
        'itmpara' => isset($data['itmpara'.$slot]) ? $data['itmpara'.$slot] : '',
    );
}

function raid_clear_item_slot(&$data, $slot)
{
    $slot = intval($slot);
    if ($slot < 0 || $slot > 6) return;
    $data['itm'.$slot] = '';
    $data['itmk'.$slot] = '';
    $data['itme'.$slot] = 0;
    $data['itms'.$slot] = 0;
    $data['itmsk'.$slot] = '';
    $data['itmpara'.$slot] = '';
}

function raid_is_item_present($item)
{
    global $nosta;
    if (empty($item['itm']) || empty($item['itmk'])) return false;
    if (!isset($item['itms'])) return false;
    return $item['itms'] === $nosta || $item['itms'] === (string)$nosta || intval($item['itms']) > 0;
}

function raid_is_protected_item($item)
{
    if (raid_get_part_id_from_item($item) !== '') return true;
    $pve = raid_get_config('pve');
    $name = isset($item['itm']) ? $item['itm'] : '';
    if (!empty($pve['warp_item_name']) && $name === $pve['warp_item_name']) return true;
    $para = raid_decode_itempara(isset($item['itmpara']) ? $item['itmpara'] : '');
    return !empty($para['raid_protected']);
}

function raid_get_item_kind_rate($kind, $rates, $default_rate)
{
    if (isset($rates[$kind])) return floatval($rates[$kind]);
    $best_length = -1;
    $best_rate = $default_rate;
    foreach ($rates as $prefix => $rate) {
        if ($prefix !== '' && strpos($kind, $prefix) === 0 && strlen($prefix) > $best_length) {
            $best_length = strlen($prefix);
            $best_rate = $rate;
        }
    }
    return floatval($best_rate);
}

function raid_calculate_item_score($item, $apply_penalty = true)
{
    global $nosta;
    $scoring = raid_get_config('scoring');
    $para = raid_decode_itempara(isset($item['itmpara']) ? $item['itmpara'] : '');

    if (!empty($para['raid_treasure']['value'])) {
        return max(0, intval($para['raid_treasure']['value']));
    }

    $kind = isset($item['itmk']) ? (string)$item['itmk'] : '';
    $effect = isset($item['itme']) ? floatval($item['itme']) : 0;
    $durability = isset($item['itms']) ? $item['itms'] : 0;
    if ($durability === $nosta || $durability === (string)$nosta) {
		$durability = isset($scoring['infinite_durability_value'])
			? floatval($scoring['infinite_durability_value']) : 0;
	}
    $durability = max(0, floatval($durability));

    $default_rate = isset($scoring['default_kind_rate']) ? floatval($scoring['default_kind_rate']) : 0.8;
    $kind_rates = !empty($scoring['kind_rates']) && is_array($scoring['kind_rates']) ? $scoring['kind_rates'] : Array();
    $kind_rate = raid_get_item_kind_rate($kind, $kind_rates, $default_rate);
    $value = $kind_rate * (($effect + $durability) / 2);

    $default_attribute = isset($scoring['default_property_value']) ? floatval($scoring['default_property_value']) : 50;
    $attribute_values = !empty($scoring['property_values']) && is_array($scoring['property_values'])
        ? $scoring['property_values'] : Array();
    $attributes = isset($item['itmsk']) && $item['itmsk'] !== ''
        ? (function_exists('get_itmsk_array') ? get_itmsk_array($item['itmsk']) : preg_split('//u', $item['itmsk'], -1, PREG_SPLIT_NO_EMPTY))
        : Array();
    foreach ($attributes as $attribute) {
        $attribute_value = isset($attribute_values[$attribute]) ? $attribute_values[$attribute] : $default_attribute;
        if (is_array($attribute_value)) {
            $attribute_value = isset($attribute_value[$kind]) ? $attribute_value[$kind]
                : (isset($attribute_value['default']) ? $attribute_value['default'] : $default_attribute);
        }
        $value += floatval($attribute_value);
    }

    if ($apply_penalty) {
        $penalty = isset($scoring['non_treasure_penalty']) ? floatval($scoring['non_treasure_penalty']) : 0.3;
        $value *= $penalty;
    }
	$rounding = isset($scoring['rounding']) ? strtolower((string)$scoring['rounding']) : 'round';
	if ($rounding === 'floor') $score = intval(floor($value));
	elseif ($rounding === 'ceil') $score = intval(ceil($value));
	else $score = intval(round($value));
	$minimum = isset($scoring['minimum_score']) ? intval($scoring['minimum_score']) : 0;
    return max($minimum, $score);
}

function raid_calculate_pve_reward($personal_score)
{
    $cfg = raid_get_config('pve');
    $reward = isset($cfg['victory_score_base']) ? intval($cfg['victory_score_base']) : 50000;
    $multiplier = isset($cfg['victory_score_multiplier']) ? floatval($cfg['victory_score_multiplier']) : 2.0;
    return max(0, intval(round((intval($personal_score) + $reward) * $multiplier)));
}

function raid_shortest_path($start, $target, $graph)
{
    $start = intval($start);
    $target = intval($target);
    if ($start === $target) return Array($start);
    if (!isset($graph[$start]) || !isset($graph[$target])) return Array();

    $queue = Array(Array($start));
    $visited = Array($start => true);
    while (!empty($queue)) {
        $path = array_shift($queue);
        $node = intval(end($path));
        $neighbours = isset($graph[$node]) && is_array($graph[$node]) ? $graph[$node] : Array();
        foreach ($neighbours as $next) {
            $next = intval($next);
            if (isset($visited[$next])) continue;
            $next_path = $path;
            $next_path[] = $next;
            if ($next === $target) return $next_path;
            $visited[$next] = true;
            $queue[] = $next_path;
        }
    }
    return Array();
}

function raid_next_route_step($start, $target, $graph)
{
    $path = raid_shortest_path($start, $target, $graph);
    return count($path) >= 2 ? intval($path[1]) : intval($start);
}

function raid_choose_boss_target($players, $high_rp_chance = 70)
{
    $visible = Array();
    foreach ($players as $player) {
        if (intval(isset($player['hp']) ? $player['hp'] : 1) <= 0) continue;
        if (intval(isset($player['pgroup']) ? $player['pgroup'] : 0) !== 0) continue;
        if (intval(isset($player['pls']) ? $player['pls'] : 0) >= 100) continue;
        $visible[] = $player;
    }
    if (empty($visible)) return Array();

    usort($visible, function ($a, $b) {
        $arp = intval(isset($a['rp']) ? $a['rp'] : 0);
        $brp = intval(isset($b['rp']) ? $b['rp'] : 0);
        if ($arp === $brp) return intval($a['pid']) - intval($b['pid']);
        return $arp > $brp ? -1 : 1;
    });

    $roll = function_exists('diceroll') ? diceroll(99) : rand(0, 99);
    if ($roll < intval($high_rp_chance)) {
        $highest = intval(isset($visible[0]['rp']) ? $visible[0]['rp'] : 0);
        $ties = Array();
        foreach ($visible as $player) {
            if (intval(isset($player['rp']) ? $player['rp'] : 0) !== $highest) break;
            $ties[] = $player;
        }
        return count($ties) === 1 ? $ties[0] : $ties[array_rand($ties)];
    }

    $minimum = null;
    foreach ($visible as $player) {
        $rp = intval(isset($player['rp']) ? $player['rp'] : 0);
        if ($minimum === null || $rp < $minimum) $minimum = $rp;
    }
    $weighted = Array();
    $total = 0;
    foreach ($visible as $player) {
        $weight = max(1, intval(isset($player['rp']) ? $player['rp'] : 0) - intval($minimum) + 1);
        $total += $weight;
        $weighted[] = Array('until' => $total, 'player' => $player);
    }
    $pick = rand(1, max(1, $total));
    foreach ($weighted as $entry) if ($pick <= $entry['until']) return $entry['player'];
    return $visible[0];
}

function raid_can_start_move_search($command, $data, $moveto = 99)
{
    global $plsinfo, $hplsinfo, $arealist, $areanum, $hack;
    global $movesp, $movehp, $inf_move_sp;

    if ($command !== 'move' && $command !== 'search') return false;
    if (intval(isset($data['type']) ? $data['type'] : 0) !== 0 || intval(isset($data['hp']) ? $data['hp'] : 0) <= 0) return false;
    $pls = intval(isset($data['pls']) ? $data['pls'] : 0);
    $pgroup = intval(isset($data['pgroup']) ? $data['pgroup'] : 0);
    $hidden = !isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]);

    if ($command === 'move') {
        $moveto = intval($moveto);
        if ($pls === $moveto) return false;
        if ($hidden) {
            if (!isset($hplsinfo[$pgroup][$moveto])) return false;
        } else {
            if (!isset($plsinfo[$moveto]) || $moveto < 0 || $moveto >= count($plsinfo)) return false;
            $area_index = array_search($moveto, $arealist, true);
            if ($area_index !== false && $area_index <= $areanum && empty($hack)) return false;
        }
    } elseif (!$hidden) {
        $area_index = array_search($pls, $arealist, true);
        if ($area_index !== false && $area_index <= $areanum && empty($hack)) return false;
    }

    $cost = isset($movesp) ? intval($movesp) : 0;
    if (!empty($data['inf']) && !empty($inf_move_sp) && is_array($inf_move_sp)) {
        foreach ($inf_move_sp as $injury => $extra) if (strpos($data['inf'], $injury) !== false) $cost += intval($extra);
    }
    $primary = intval(isset($data['horizon']) ? $data['horizon'] : 0) === 1 ? 'hp' : 'sp';
    $secondary = $primary === 'hp' ? 'sp' : 'hp';
    $move_hp_rate = isset($movehp) ? floatval($movehp) : 1;
    return intval(isset($data[$primary]) ? $data[$primary] : 0) > $cost
        || intval(isset($data[$secondary]) ? $data[$secondary] : 0) > round($cost * $move_hp_rate);
}

function raid_player_has_stash($data)
{
    $clbpara = isset($data['clbpara']) ? $data['clbpara'] : Array();
    if (!is_array($clbpara) && function_exists('get_clbpara')) $clbpara = get_clbpara($clbpara);
    return !empty($clbpara['raid']['score']) || !empty($clbpara['raid']['stored_items']);
}

?>
