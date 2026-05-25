<?php
if(!defined('IN_GAME')) exit('Access Denied');

/**
 * RuleSet动态覆盖系统
 * 基于函数重定向的动态覆盖，不修改物理文件
 */

// 全局变量存储当前房间的RuleSet配置
global $current_ruleset_config, $ruleset_function_overrides;
$current_ruleset_config = null;
$ruleset_function_overrides = array();

/**
 * 获取当前房间的RuleSet ID
 */
function get_current_ruleset_id() {
    global $groomid, $db, $gtablepre;

    if (!isset($groomid) || !isset($db) || !isset($gtablepre)) {
        return '';
    }

    $room_id = intval($groomid);
    $result = $db->query("SELECT gruleset FROM {$gtablepre}game WHERE groomid = {$room_id}");
    if ($db->num_rows($result)) {
        $room_data = $db->fetch_array($result);
        return $room_data['gruleset'];
    }

    return '';
}

/**
 * 检查当前房间是否为全随机模式
 */
function is_all_random_mode() {
    return get_current_ruleset_id() == 'ACDTS_298SP4_AR';
}

/**
 * 初始化RuleSet覆盖系统（简化版）
 */
function init_ruleset_override() {
    global $current_ruleset_config;

    $ruleset_id = get_current_ruleset_id();
    if (!empty($ruleset_id)) {
        include_once GAME_ROOT.'./gamedata/ruleset/ruleset_config.php';
        $current_ruleset_config = get_ruleset_config($ruleset_id);
        error_log("RuleSet Override: 房间 " . $GLOBALS['groomid'] . " 使用 RuleSet: $ruleset_id");
    }
}

/**
 * 动态加载RuleSet覆盖的函数文件
 * 只在需要时加载，不修改原文件
 */
function load_ruleset_override_functions() {
    $ruleset_id = get_current_ruleset_id();

    if ($ruleset_id == 'ACDTS_298SP4_AR') {
        // 加载全随机模式的覆盖函数
        include_once GAME_ROOT.'./gamedata/ruleset/ACDTS_298SP4_AR/include/ruleset_functions.php';
        error_log("RuleSet Override: 已加载 $ruleset_id 的覆盖函数");
    }
}

/**
 * 调试函数：显示当前的RuleSet信息
 */
function debug_ruleset_override() {
    $ruleset_id = get_current_ruleset_id();
    $is_all_random = is_all_random_mode();

    echo "<pre>RuleSet Debug Info:\n";
    echo "房间ID: " . $GLOBALS['groomid'] . "\n";
    echo "RuleSet ID: " . ($ruleset_id ? $ruleset_id : 'NONE') . "\n";
    echo "全随机模式: " . ($is_all_random ? 'YES' : 'NO') . "\n";
    echo "</pre>";
}

?>
