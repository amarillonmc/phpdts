<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * 投射平台 itmpara 配置说明 / Projection platform itmpara reference
 *
 * 平台物品本体建议使用 itmk 以“💝”开头。使用时会读取当前物品的 itmpara。
 * Platform items should normally use an itmk beginning with "💝". All behavior is read from itmpara.
 *
 * PID 模式 / PID mode:
 * array(
 *     'IsPlatformItem' => 1,
 *     'platformPlayerMode' => 'PID',
 *     'targetPID' => 123,
 *     'platformTransformTime' => 80,       // 可省略；省略时永久替换 / Optional; omit for permanent transform
 *     'isStatPersistent' => true           // 到期后保留 exp/att/def/wp/wk/wg/wc/wd/wf 的正增长 / Keep positive stat growth
 * )
 *
 * 回放模式 / Replay mode:
 * array(
 *     'IsPlatformItem' => 1,
 *     'platformPlayerMode' => 'REPLAY',
 *     'platformPlayerData' => array(
 *         'mhp' => 500,
 *         'msp' => 300,
 *         'att' => 80,
 *         'def' => 80,
 *         'wep' => '样例武器',
 *         'wepk' => 'WP',
 *         'wepe' => 100,
 *         'weps' => 100,
 *         'wepsk' => ''
 *     ),
 *     'platformTransformTime' => 80,
 *     'isStatPersistent' => false
 * )
 *
 * 兼容旧格式 / Legacy replay keys:
 * 也可以继续使用 PlatformPlayerMhp、PlatformPlayerAtt 等键。系统会把
 * PlatformPlayer + ucfirst(字段名) 的键收集为回放数据。
 *
 * 记录器物品 / Recorder item:
 * 记录器不是投射平台，但可以由 item.main.php 路由到本文件。使用后会把当前玩家
 * 的可投射数据写入一个回放模式平台，并用生成的平台覆盖记录器自身。
 * A recorder is not itself a platform. On use it captures projectable player data
 * into a replay platform and replaces the recorder item in the same slot.
 *
 * array(
 *     'IsPlatformRecorder' => 1,
 *     'GeneratedItm' => '投射平台',          // 可省略 / Optional
 *     'GeneratedItmk' => '💝',
 *     'GeneratedItme' => 1,
 *     'GeneratedItms' => 1,
 *     'platformTransformTime' => 80,
 *     'isStatPersistent' => true
 * )
 *
 * 为避免登录、存档、战斗目标和队伍判定混乱，投射不会覆盖 pid/name/pass/type/clbpara、
 * 位置、队伍、行动状态、冷却、死亡/结束时间等本体字段。显示用的投射名写入
 * $clbpara['PlatformName']。
 * To keep identity, login, combat targeting, location, team and action state safe,
 * platform projection never overwrites pid/name/pass/type/clbpara/location/team/action
 * or timing fields. The display projection name is stored in $clbpara['PlatformName'].
 */

function platform_is_recorder_item($itmpara)
{
    $itmpara = platform_to_array($itmpara);
    return !empty($itmpara['IsPlatformRecorder']) || !empty($itmpara['isPlatformRecorder'])
        || (!empty($itmpara['platformPlayerMode']) && strtoupper($itmpara['platformPlayerMode']) == 'CAPTURE');
}

function item_platform($itmn, &$data)
{
    global $log, $nosta;

    if (!isset($data)) {
        global $pdata;
        $data = &$pdata;
    }

    $itm_key = 'itm' . $itmn;
    $itmk_key = 'itmk' . $itmn;
    $itme_key = 'itme' . $itmn;
    $itms_key = 'itms' . $itmn;
    $itmsk_key = 'itmsk' . $itmn;
    $itmpara_key = 'itmpara' . $itmn;

    if (empty($data[$itm_key])) {
        $log .= '此道具不存在，请重新选择。<br>';
        return;
    }

    $itmpara = platform_to_array(isset($data[$itmpara_key]) ? $data[$itmpara_key] : array());

    if (platform_is_recorder_item($itmpara)) {
        platform_record_current_player($itmn, $data, $itmpara);
        return;
    }

    if (!empty($data['clbpara']['platform']['active'])) {
        $log .= '投射平台仍在运行中，无法叠加新的投射。<br>';
        return;
    }

    $source = platform_resolve_source_data($itmpara);
    if (empty($source['data'])) {
        $log .= '投射平台中没有有效的投射数据。<br>';
        return;
    }

    $target_name = !empty($source['name']) ? $source['name'] : '未知目标';
    $duration = platform_get_duration($itmpara);
    $is_stat_persistent = platform_bool(platform_config_value($itmpara, array('isStatPersistent', 'IsStatPersistent'), false));
    $project_data = platform_filter_projectable_data($source['data']);

    if (empty($project_data)) {
        $log .= '投射平台没有可套用的数据。<br>';
        return;
    }

    platform_consume_item($data, $itmn, $nosta);

    if ($duration > 0) {
        platform_save_state($data, $project_data, $target_name, $duration, $is_stat_persistent);
        $log .= "投射平台启动：你的本体数据已被暂存，投射稳定度阈值为<span class=\"yellow\">{$duration}</span>。<br>";
    } else {
        $log .= '投射平台启动：未设置生效时间，本次投射将永久写入当前角色数据。<br>';
    }

    platform_apply_data($data, $project_data, $target_name);
    $log .= "投射完成。当前投射对象：<span class=\"yellow\">{$target_name}</span>。<br>";
}

function platform_record_current_player($itmn, &$data, $itmpara)
{
    global $log;

    $itm_key = 'itm' . $itmn;
    $itmk_key = 'itmk' . $itmn;
    $itme_key = 'itme' . $itmn;
    $itms_key = 'itms' . $itmn;
    $itmsk_key = 'itmsk' . $itmn;
    $itmpara_key = 'itmpara' . $itmn;

    $capture_data = $data;
    $capture_data[$itm_key] = '';
    $capture_data[$itmk_key] = '';
    $capture_data[$itme_key] = 0;
    $capture_data[$itms_key] = 0;
    $capture_data[$itmsk_key] = '';
    $capture_data[$itmpara_key] = '';

    $platform_data = platform_filter_projectable_data($capture_data);
    $owner_name = isset($data['name']) ? $data['name'] : '未知玩家';
    $generated_name = platform_config_value($itmpara, array('GeneratedItm', 'generatedItm'), "【{$owner_name}】的投射平台");

    $new_para = array(
        'IsPlatformItem' => 1,
        'platformPlayerMode' => 'REPLAY',
        'platformPlayerData' => $platform_data,
        'platformSourceName' => $owner_name,
        'isStatPersistent' => platform_bool(platform_config_value($itmpara, array('isStatPersistent', 'IsStatPersistent'), false))
    );

    $duration = platform_get_duration($itmpara);
    if ($duration > 0) {
        $new_para['platformTransformTime'] = $duration;
    }

    $data[$itm_key] = $generated_name;
    $data[$itmk_key] = platform_config_value($itmpara, array('GeneratedItmk', 'generatedItmk'), '💝');
    $data[$itme_key] = platform_config_value($itmpara, array('GeneratedItme', 'generatedItme'), isset($data[$itme_key]) ? $data[$itme_key] : 1);
    $data[$itms_key] = platform_config_value($itmpara, array('GeneratedItms', 'generatedItms'), 1);
    $data[$itmsk_key] = platform_config_value($itmpara, array('GeneratedItmsk', 'generatedItmsk'), isset($data[$itmsk_key]) ? $data[$itmsk_key] : '');
    $data[$itmpara_key] = $new_para;

    $log .= "记录完成：<span class=\"yellow\">{$generated_name}</span>已经写入当前槽位。<br>";
}

function platform_tick(&$data, $act = '')
{
    global $log;

    if (!isset($data)) {
        global $pdata;
        $data = &$pdata;
    }
    if (empty($data['clbpara']) || !is_array($data['clbpara']) || empty($data['clbpara']['platform']['active'])) {
        return false;
    }

    $state = $data['clbpara']['platform'];
    if (empty($state['duration']) || empty($state['original'])) {
        platform_clear_state($data);
        return false;
    }

    $randver = !empty($data['clbpara']['randver1']) ? intval($data['clbpara']['randver1']) : 10;
    $roll_max = max(1, intval(ceil($randver / 10)));
    $increase = rand(1, $roll_max);
    $data['clbpara']['platform']['progress'] = isset($state['progress']) ? $state['progress'] + $increase : $increase;

    if ($data['clbpara']['platform']['progress'] > $state['duration']) {
        restorePlayerOriginalData($data);
        $log .= "投射稳定度耗尽，平台投射已经结束。<br>";
        return true;
    }

    return false;
}

function restorePlayerOriginalData(&$data)
{
    global $log;

    if (!isset($data)) {
        global $pdata;
        $data = &$pdata;
    }
    if (empty($data['clbpara']) || !is_array($data['clbpara']) || empty($data['clbpara']['platform']['active'])) {
        $log .= '没有找到保存的投射平台数据，无法恢复。<br>';
        return false;
    }

    $state = $data['clbpara']['platform'];
    if (empty($state['original']) || !is_array($state['original'])) {
        platform_clear_state($data);
        $log .= '投射平台恢复数据损坏，已清理投射状态。<br>';
        return false;
    }

    $restore_data = $state['original'];
    if (!empty($state['isStatPersistent'])) {
        $restore_data = platform_apply_persistent_growth($restore_data, $data, $state);
    }

    foreach ($restore_data as $field => $value) {
        $data[$field] = $value;
    }

    platform_clear_state($data);
    $log .= '投射解除：你恢复了原来的数据。<br>';
    return true;
}

function platform_resolve_source_data($itmpara)
{
    global $db, $tablepre;

    $mode = strtoupper(platform_config_value($itmpara, array('platformPlayerMode', 'PlatformPlayerMode'), 'REPLAY'));
    $data = array();
    $name = '';

    if ($mode == 'PID') {
        $target_pid = intval(platform_config_value($itmpara, array('targetPID', 'TargetPID', 'platformTargetPID'), 0));
        if ($target_pid > 0) {
            if (function_exists('fetch_playerdata_by_pid')) {
                $data = fetch_playerdata_by_pid($target_pid);
            } else {
                $result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='{$target_pid}'");
                if ($db->num_rows($result)) {
                    $data = $db->fetch_array($result);
                }
            }
        }
    } else {
        $data = platform_extract_replay_data($itmpara);
    }

    if (!empty($data['name'])) {
        $name = $data['name'];
    } else {
        $name = platform_config_value($itmpara, array('platformSourceName', 'PlatformSourceName', 'PlatformName'), '');
    }

    return array('data' => $data, 'name' => $name);
}

function platform_extract_replay_data($itmpara)
{
    $data = array();
    $direct_data = platform_config_value($itmpara, array('platformPlayerData', 'PlatformPlayerData'), array());
    if (is_array($direct_data)) {
        $data = $direct_data;
    }

    foreach (platform_projectable_fields() as $field) {
        $legacy_key = 'PlatformPlayer' . ucfirst($field);
        if (isset($itmpara[$legacy_key])) {
            $data[$field] = $itmpara[$legacy_key];
        }
    }

    return $data;
}

function extractPlatformPlayerData($itmpara)
{
    return platform_extract_replay_data(platform_to_array($itmpara));
}

function savePlayerOriginalData(&$data)
{
    if (empty($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : array());
    }
    $data['clbpara']['platform']['original'] = platform_filter_projectable_data($data);
    $data['clbpara']['platform']['active'] = true;
}

function applyPlatformDataToPlayer(&$data, $platformData)
{
    platform_apply_data($data, platform_filter_projectable_data($platformData), '');
}

function platform_save_state(&$data, $project_data, $target_name, $duration, $is_stat_persistent)
{
    if (empty($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : array());
    }

    $data['clbpara']['platform'] = array(
        'active' => true,
        'targetName' => $target_name,
        'duration' => intval($duration),
        'progress' => 0,
        'isStatPersistent' => $is_stat_persistent ? 1 : 0,
        'original' => platform_filter_projectable_data($data),
        'startStats' => platform_collect_stats($project_data)
    );
}

function platform_apply_data(&$data, $project_data, $target_name)
{
    if (empty($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : array());
    }

    foreach (platform_filter_projectable_data($project_data) as $field => $value) {
        $data[$field] = $value;
    }

    if ($target_name !== '') {
        $data['clbpara']['PlatformName'] = $target_name;
    }
}

function platform_apply_persistent_growth($restore_data, $current_data, $state)
{
    $start_stats = !empty($state['startStats']) && is_array($state['startStats']) ? $state['startStats'] : array();
    foreach (platform_persistent_stat_fields() as $field) {
        $start = isset($start_stats[$field]) ? intval($start_stats[$field]) : 0;
        $current = isset($current_data[$field]) ? intval($current_data[$field]) : 0;
        $growth = max(0, $current - $start);
        if ($growth > 0) {
            $restore_data[$field] = isset($restore_data[$field]) ? intval($restore_data[$field]) + $growth : $growth;
        }
    }
    return $restore_data;
}

function platform_collect_stats($data)
{
    $stats = array();
    foreach (platform_persistent_stat_fields() as $field) {
        $stats[$field] = isset($data[$field]) ? intval($data[$field]) : 0;
    }
    return $stats;
}

function platform_clear_state(&$data)
{
    if (empty($data['clbpara']) || !is_array($data['clbpara'])) {
        $data['clbpara'] = get_clbpara(isset($data['clbpara']) ? $data['clbpara'] : array());
    }

    unset($data['clbpara']['platform']);
    unset($data['clbpara']['PlatformName']);

    // 清理旧版键值 / Clear legacy keys from the older platform implementation.
    unset($data['clbpara']['platformTransformed']);
    unset($data['clbpara']['platformTransformTime']);
    foreach (array_keys($data['clbpara']) as $key) {
        if (strpos($key, 'ori') === 0) {
            unset($data['clbpara'][$key]);
        }
    }
}

function platform_consume_item(&$data, $itmn, $nosta)
{
    $itm_key = 'itm' . $itmn;
    $itmk_key = 'itmk' . $itmn;
    $itme_key = 'itme' . $itmn;
    $itms_key = 'itms' . $itmn;
    $itmsk_key = 'itmsk' . $itmn;
    $itmpara_key = 'itmpara' . $itmn;

    if (!isset($data[$itms_key]) || $data[$itms_key] == $nosta) {
        return;
    }

    $data[$itms_key]--;
    if ($data[$itms_key] <= 0) {
        $data[$itm_key] = '';
        $data[$itmk_key] = '';
        $data[$itme_key] = 0;
        $data[$itms_key] = 0;
        $data[$itmsk_key] = '';
        $data[$itmpara_key] = '';
    }
}

function platform_get_duration($itmpara)
{
    $duration = platform_config_value($itmpara, array('platformTransformTime', 'PlatformTransformTime'), null);
    if ($duration === null && !empty($itmpara['PlatformChargeBaseValue'])) {
        $duration = $itmpara['PlatformChargeBaseValue'];
    }
    if ($duration === null && !empty($itmpara['PlatformIsTimed'])) {
        $duration = 100;
    }
    return max(0, intval($duration));
}

function platform_filter_projectable_data($data)
{
    $filtered = array();
    if (!is_array($data)) {
        return $filtered;
    }

    foreach (platform_projectable_fields() as $field) {
        if (array_key_exists($field, $data)) {
            $filtered[$field] = $data[$field];
        }
    }
    return $filtered;
}

function platform_projectable_fields()
{
    $fields = array();
    if (function_exists('update_db_player_structure')) {
        $fields = update_db_player_structure();
    }

    if (empty($fields) || !is_array($fields)) {
        $fields = array(
            'gd', 'race', 'sNo', 'icon', 'club', 'horizon', 'hp', 'mhp', 'sp', 'msp', 'ss', 'mss',
            'att', 'def', 'lvl', 'exp', 'money', 'rp', 'inf', 'rage', 'pose', 'tactic', 'killnum',
            'wp', 'wk', 'wg', 'wc', 'wd', 'wf', 'getitem', 'itembag', 'itmnum', 'itmnumlimit',
            'wep', 'wepk', 'wepe', 'weps', 'wepsk', 'weppara', 'wep2', 'wep2k', 'wep2e', 'wep2s',
            'wep2sk', 'wep2para', 'arb', 'arbk', 'arbe', 'arbs', 'arbsk', 'arbpara',
            'arh', 'arhk', 'arhe', 'arhs', 'arhsk', 'arhpara', 'ara', 'arak', 'arae', 'aras',
            'arask', 'arapara', 'arf', 'arfk', 'arfe', 'arfs', 'arfsk', 'arfpara',
            'art', 'artk', 'arte', 'arts', 'artsk', 'artpara',
            'itm0', 'itmk0', 'itme0', 'itms0', 'itmsk0', 'itmpara0',
            'itm1', 'itmk1', 'itme1', 'itms1', 'itmsk1', 'itmpara1',
            'itm2', 'itmk2', 'itme2', 'itms2', 'itmsk2', 'itmpara2',
            'itm3', 'itmk3', 'itme3', 'itms3', 'itmsk3', 'itmpara3',
            'itm4', 'itmk4', 'itme4', 'itms4', 'itmsk4', 'itmpara4',
            'itm5', 'itmk5', 'itme5', 'itms5', 'itmsk5', 'itmpara5',
            'itm6', 'itmk6', 'itme6', 'itms6', 'itmsk6', 'itmpara6',
            'flare', 'dcloak', 'auraa', 'aurab', 'aurac', 'aurad', 'aurae', 'souls',
            'debuffa', 'debuffb', 'debuffc', 'vcode', 'statusa', 'statusb', 'statusc', 'statusd',
            'statuse', 'clbstatusa', 'clbstatusb', 'clbstatusc', 'clbstatusd', 'clbstatuse',
            'nikstatusa', 'nikstatusb', 'nikstatusc', 'nikstatusd', 'nikstatuse',
            'element0', 'element1', 'element2', 'element3', 'element4', 'element5'
        );
    }

    return array_values(array_diff($fields, platform_forbidden_fields()));
}

function platform_forbidden_fields()
{
    return array(
        'pid', 'type', 'name', 'pass', 'ip', 'endtime', 'validtime', 'deathtime', 'cmdnum',
        'nick', 'nicks', 'skillpoint', 'skills', 'cdsec', 'cdmsec', 'cdtime',
        'action', 'bid', 'pgroup', 'pls', 'state', 'teamID', 'teamPass', 'teamIcon', 'clbpara'
    );
}

function platform_persistent_stat_fields()
{
    return array('exp', 'att', 'def', 'wp', 'wk', 'wg', 'wc', 'wd', 'wf');
}

function platform_config_value($arr, $keys, $default = null)
{
    if (!is_array($arr)) {
        return $default;
    }
    foreach ($keys as $key) {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }
    }
    return $default;
}

function platform_to_array($para)
{
    $para = get_itmpara($para);
    return is_array($para) ? $para : array();
}

function platform_bool($value)
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_numeric($value)) {
        return intval($value) != 0;
    }
    if (is_string($value)) {
        return in_array(strtolower($value), array('1', 'true', 'yes', 'on'), true);
    }
    return !empty($value);
}
