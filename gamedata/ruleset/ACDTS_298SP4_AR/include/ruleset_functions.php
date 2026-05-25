<?php
if(!defined('IN_GAME')) exit('Access Denied');

/**
 * ACDTS298 ALL RANDOM RuleSet 钩子函数
 * 这些函数实现纯钩子接口，不包含具体的游戏逻辑
 */

/**
 * 物品获得钩子函数
 */
function ruleset_itemget_hook(&$data) {
    global $log, $nosta;

    // 检查是否为全随机模式
    if(!is_all_random_mode()) {
        return;
    }

    extract($data, EXTR_REFS);

    // 随机化itme和itms (0到原数值的2~7倍)
    if($itme0 > 0) {
        $multiplier = rand(2, 7);
        $itme0 = rand(0, $itme0 * $multiplier);
        if($itme0 == 0) $itme0 = 1; // 防止为0
    }
    if($itms0 > 0 && $itms0 !== $nosta) {
        $multiplier = rand(2, 7);
        $itms0 = rand(0, $itms0 * $multiplier);
        if($itms0 == 0) $itms0 = 1; // 防止为0
    }

    // 随机化itmsk
    include_once GAME_ROOT.'./gamedata/ruleset/ACDTS_298SP4_AR/cache/resources_1.php';
    if(isset($itemspkinfo) && is_array($itemspkinfo)) {
        $available_itmsk = array_keys($itemspkinfo);
        // 随机选择1-3个属性
        $num_attrs = rand(1, 3);
        $selected_attrs = array();
        for($i = 0; $i < $num_attrs; $i++) {
            $random_attr = $available_itmsk[array_rand($available_itmsk)];
            if(!in_array($random_attr, $selected_attrs)) {
                $selected_attrs[] = $random_attr;
            }
        }
        $itmsk0 = implode('', $selected_attrs);
    }

    $log .= "<span class=\"cyan\">【全随机模式】物品属性已随机化！</span><br>";

    // 将修改后的变量写回$data数组
    $data['itme0'] = $itme0;
    $data['itms0'] = $itms0;
    $data['itmsk0'] = $itmsk0;
}

/**
 * 合成物品钩子函数
 */
function ruleset_itemmix_hook(&$data) {
    global $log, $nosta;

    // 检查是否为全随机模式
    if(!is_all_random_mode()) {
        return;
    }

    extract($data, EXTR_REFS);

    // 合成结果本体随机化 / Randomize the actual synthesized item result
    if(function_exists('get_mixinfo')) {
        $mixinfo = get_mixinfo();
        if(!empty($mixinfo)) {
            $random_mix = $mixinfo[array_rand($mixinfo)];
            if(!empty($random_mix['result']) && is_array($random_mix['result'])) {
                $random_result = array_pad($random_mix['result'], 6, '');
                $itm0 = $random_result[0];
                $itmk0 = $random_result[1];
                $itme0 = $random_result[2];
                $itms0 = $random_result[3];
                $itmsk0 = $random_result[4];
                $itmpara0 = $random_result[5];
            }
        }
    }

    // 随机化itme和itms (0到原数值的2~7倍)
    if($itme0 > 0) {
        $multiplier = rand(2, 7);
        $itme0 = rand(0, $itme0 * $multiplier);
        if($itme0 == 0) $itme0 = 1; // 防止为0
    }
    if($itms0 > 0 && $itms0 !== $nosta) {
        $multiplier = rand(2, 7);
        $itms0 = rand(0, $itms0 * $multiplier);
        if($itms0 == 0) $itms0 = 1; // 防止为0
    }

    // 随机化itmsk
    include_once GAME_ROOT.'./gamedata/ruleset/ACDTS_298SP4_AR/cache/resources_1.php';
    if(isset($itemspkinfo) && is_array($itemspkinfo)) {
        $available_itmsk = array_keys($itemspkinfo);
        // 随机选择1-3个属性
        $num_attrs = rand(1, 3);
        $selected_attrs = array();
        for($i = 0; $i < $num_attrs; $i++) {
            $random_attr = $available_itmsk[array_rand($available_itmsk)];
            if(!in_array($random_attr, $selected_attrs)) {
                $selected_attrs[] = $random_attr;
            }
        }
        $itmsk0 = implode('', $selected_attrs);
    }

    $log .= "<span class=\"cyan\">【全随机模式】合成结果已随机化！</span><br>";

    // 将修改后的变量写回$data数组
    $data['itm0'] = $itm0;
    $data['itmk0'] = $itmk0;
    $data['itme0'] = $itme0;
    $data['itms0'] = $itms0;
    $data['itmsk0'] = $itmsk0;
    $data['itmpara0'] = isset($itmpara0) ? $itmpara0 : '';
}

/**
 * 检查地图物品刷新是否需要随机化
 */
function ruleset_should_randomize_item($imap, $iarea, $an) {
    // 在全随机模式下，所有物品都随机刷新
    return is_all_random_mode();
}

/**
 * 检查NPC刷新是否需要随机化
 */
function ruleset_should_randomize_npc($npc_pls) {
    // 在全随机模式下，所有NPC都随机刷新
    return is_all_random_mode();
}

/**
 * 检查移动落点是否需要随机化
 */
function ruleset_should_randomize_move() {
    return is_all_random_mode();
}

/**
 * 获取随机NPC位置
 */
function ruleset_get_random_npc_location($plsnum) {
    $rmap = rand(1, $plsnum-1);
    while ($rmap == 34) { // 排除地点34
        $rmap = rand(1, $plsnum-1);
    }
    return $rmap;
}

/**
 * 获取随机移动落点
 */
function ruleset_get_random_move_destination($current_pls, $plsinfo, $arealist, $areanum, $hack) {
    $safe_pls = array();
    $plsnum = sizeof($plsinfo);

    for($i = 1; $i < $plsnum; $i++) {
        if($i == $current_pls || $i == 34) continue;
        if(!$hack && array_search($i, $arealist) <= $areanum) continue;
        $safe_pls[] = $i;
    }

    if(empty($safe_pls)) {
        return $current_pls;
    }

    return $safe_pls[array_rand($safe_pls)];
}

/**
 * 随机化NPC数值
 */
function ruleset_randomize_npc_stats(&$npc) {
    if(!is_all_random_mode()) {
        return;
    }

    $numeric_fields = array(
        'mhp', 'hp', 'msp', 'sp', 'att', 'def', 'lvl', 'exp', 'skill',
        'wp', 'wk', 'wg', 'wc', 'wd', 'wf', 'money', 'rp',
        'wepe', 'weps', 'arbe', 'arbs', 'arhe', 'arhs', 'arae', 'aras',
        'arfe', 'arfs', 'arte', 'arts',
        'itme0', 'itms0', 'itme1', 'itms1', 'itme2', 'itms2', 'itme3', 'itms3',
        'itme4', 'itms4', 'itme5', 'itms5', 'itme6', 'itms6'
    );

    foreach($numeric_fields as $field) {
        if(isset($npc[$field]) && is_numeric($npc[$field])) {
            $base = max(1, intval($npc[$field]));
            $npc[$field] = rand(1, max(1, $base * rand(2, 8)));
        }
    }

    if(isset($npc['mhp'])) $npc['hp'] = $npc['mhp'];
    if(isset($npc['msp'])) $npc['sp'] = $npc['msp'];
    foreach(array('p', 'k', 'g', 'c', 'd', 'f') as $wtype) {
        $field = 'w' . $wtype;
        if(empty($npc[$field]) && isset($npc['skill'])) {
            $npc[$field] = $npc['skill'];
        }
    }
}

?>
