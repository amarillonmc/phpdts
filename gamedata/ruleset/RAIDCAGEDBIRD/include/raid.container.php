<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * RAID容器与宝藏生成
 * RAID container and treasure generation
 */

function raid_container_rand($min, $max)
{
    $min = intval($min);
    $max = intval($max);
    if ($max < $min) $max = $min;
    return $min === $max ? $min : rand($min, $max);
}

// 从集中配置生成一件普通或特殊纯宝藏。
// Generate one normal or special pure treasure from centralized configuration.
function raid_generate_treasure_item($category = '')
{
    $cfg = raid_get_config('treasure');
    $container_cfg = raid_get_config('containers');
    if ($category !== 'normal' && $category !== 'special') {
        $roll = function_exists('diceroll') ? diceroll(99) : rand(0, 99);
        $category = $roll < intval(isset($container_cfg['special_treasure_rate']) ? $container_cfg['special_treasure_rate'] : 8)
            ? 'special' : 'normal';
    }

    $rarity = 1;
    $value = 300;
    $name = '未登记回收物';
    if ($category === 'special' && !empty($cfg['special']) && is_array($cfg['special'])) {
        $entry = $cfg['special'][array_rand($cfg['special'])];
        $name = isset($entry['name']) ? $entry['name'] : $name;
        $rarity = max(1, intval(isset($entry['rarity']) ? $entry['rarity'] : 5));
        $value = max(1, intval(isset($entry['value']) ? $entry['value'] : 9000));
    } else {
        $normal = !empty($cfg['normal']) && is_array($cfg['normal']) ? $cfg['normal'] : Array();
        $prefixes = !empty($normal['prefixes']) ? $normal['prefixes'] : Array('被遗忘的');
        $names = !empty($normal['names']) ? $normal['names'] : Array('权限芯片');
        $suffixes = !empty($normal['suffixes']) ? $normal['suffixes'] : Array('·原型');
        $name = $prefixes[array_rand($prefixes)].$names[array_rand($names)].$suffixes[array_rand($suffixes)];
        $rarity_range = !empty($normal['rarity_range']) ? $normal['rarity_range'] : Array(1, 4);
        $value_range = !empty($normal['value_range']) ? $normal['value_range'] : Array(300, 6000);
        $rarity = raid_container_rand($rarity_range[0], $rarity_range[1]);
        $value = raid_container_rand($value_range[0], $value_range[1]);
        $category = 'normal';
    }

    $para = Array(
        'raid_treasure' => Array(
            'category' => $category,
            'rarity' => $rarity,
            'value' => $value,
        ),
    );
    return Array(
        'itm' => $name,
        'itmk' => isset($cfg['item_kind']) ? $cfg['item_kind'] : 'RAIDT',
        'itme' => $rarity,
        'itms' => 1,
        'itmsk' => isset($cfg['item_special']) ? $cfg['item_special'] : 'Z',
        'itmpara' => raid_encode_itempara($para),
    );
}

// 从本轮已有掉落中复制一件普通物资；若池为空则使用安全后备物品。
// Copy an ordinary round drop; use a safe fallback when the pool is empty.
function raid_generate_ordinary_container_item()
{
    global $db, $tablepre;
    $item = Array();
    if (isset($db) && isset($tablepre)) {
        $result = $db->query("SELECT itm,itmk,itme,itms,itmsk,itmpara FROM {$tablepre}mapitem WHERE itmk<>'RAIDT' AND itmpara NOT LIKE '%raid_part%' ORDER BY RAND() LIMIT 1");
        if ($db->num_rows($result)) $item = $db->fetch_array($result);
    }
    $disabled = raid_get_config();
    if (empty($item) || (!empty($disabled['disabled_route_items']) && in_array($item['itm'], $disabled['disabled_route_items'], true))) {
        $item = Array(
            'itm' => '密封战术医疗包',
            'itmk' => 'HH',
            'itme' => 500,
            'itms' => 1,
            'itmsk' => '',
            'itmpara' => '',
        );
    }
    return $item;
}

// 按容器概率表生成一件掉落。
// Generate one drop according to the container probability table.
function raid_generate_container_drop()
{
    $cfg = raid_get_config('containers');
    $special = max(0, intval(isset($cfg['special_treasure_rate']) ? $cfg['special_treasure_rate'] : 8));
    $normal = max(0, intval(isset($cfg['normal_treasure_rate']) ? $cfg['normal_treasure_rate'] : 72));
    $ordinary = max(0, intval(isset($cfg['ordinary_item_rate']) ? $cfg['ordinary_item_rate'] : 20));
	$total = $special + $normal + $ordinary;
	if ($total <= 0) return raid_generate_ordinary_container_item();
    $roll = function_exists('diceroll') ? diceroll($total - 1) : rand(0, $total - 1);
    if ($roll < $special) return raid_generate_treasure_item('special');
    if ($roll < $special + $normal) return raid_generate_treasure_item('normal');
    return raid_generate_ordinary_container_item();
}

// 容器被玩家击破时在原地图投放战利品，并移走空壳。
// Drop loot at the killed container's map and move the empty shell off-map.
function raid_handle_container_kill(&$attacker, &$container, $active)
{
    global $db, $tablepre, $now, $log, $plsinfo;
    if (!raid_is_container($container)) return false;
    $clbpara = isset($container['clbpara']) ? $container['clbpara'] : Array();
    if (!is_array($clbpara) && function_exists('get_clbpara')) $clbpara = get_clbpara($clbpara);
    if (!empty($clbpara['raid']['opened'])) return false;

    $cfg = raid_get_config('containers');
    $range = !empty($cfg['drop_count_range']) ? $cfg['drop_count_range'] : Array(1, 3);
    $count = raid_container_rand($range[0], $range[1]);
    $pls = intval(isset($container['pls']) ? $container['pls'] : 0);
    for ($i = 0; $i < $count; $i++) {
        $drop = raid_generate_container_drop();
        $drop['pls'] = $pls;
        $db->array_insert("{$tablepre}mapitem", $drop);
    }

    raid_event_prepare_clbpara($container);
    $container['clbpara']['raid']['opened'] = 1;
    $container['action'] = '';
    $container['bid'] = 0;
    $container['pls'] = 254;
    if ($active && intval(isset($attacker['type']) ? $attacker['type'] : 1) === 0) {
        $log .= '<span class="lime">容器崩解，向周围抛出了'.$count.'件可回收物资。</span><br>';
    }
    if (function_exists('addnews')) {
        $place = isset($plsinfo[$pls]) ? $plsinfo[$pls] : $pls;
        addnews($now, 'raid_container_open', isset($attacker['name']) ? $attacker['name'] : '', $place, $count);
    }
    return true;
}

?>
