<?php

define('CURSCRIPT', 'fireseed_data');
require './include/common.inc.php';
include_once GAME_ROOT.'./include/game.func.php';
include_once GAME_ROOT.'./include/game/club22.func.php';

header('Content-Type: application/json; charset=utf-8');

// 返回接口错误 / Return an API error
function fireseed_data_error($message, $status_code) {
    http_response_code($status_code);
    echo json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证当前玩家身份 / Authenticate the current player
if(!is_string($cuser) || !is_string($cpass) || $cuser === '' || $cpass === '') {
    fireseed_data_error('Not logged in', 401);
}

// 获取玩家数据 / Get player data
$pdata = fetch_playerdata_by_name($cuser);
if(!$pdata) {
    fireseed_data_error('Player data not found', 404);
}

// 与 game.php 保持相同的密码同步逻辑 / Match the password synchronization used by game.php
if(!isset($pdata['pass']) || !is_string($pdata['pass']) || !hash_equals($pdata['pass'], $cpass)) {
    if(empty($udata) || !isset($udata['password']) || !is_string($udata['password'])
        || !hash_equals($udata['password'], $cpass)) {
        fireseed_data_error('Wrong password', 403);
    }
    $pid = intval($pdata['pid']);
    $password = $udata['password'];
    $db->query("UPDATE {$tablepre}players SET pass='$password' WHERE pid='$pid'");
    $pdata['pass'] = $password;
}

// clbpara 已经在 fetch_playerdata_by_name 中通过 check_player_misc_states 处理过了

// 检查是否为枫火歌者
if($pdata['club'] != 22) {
    fireseed_data_error('Not a Fireseed Singer', 403);
}

// 获取种火实时数据
$fireseed_realtime_data = array();

if(!empty($pdata['clbpara']['fireseed'])) {
    foreach($pdata['clbpara']['fireseed'] as $fs_id => $fs_data) {
        // 获取种火实时数据
        $realtime_data = getFireseedRealTimeData($fs_id);
        
        if($realtime_data) {
            // 合并管理数据和实时数据
            $fireseed_realtime_data[$fs_id] = array(
                // 管理数据（来自 clbpara）
                'level' => $fs_data['level'],
                'mode' => $fs_data['mode'],
                'horizon' => isset($fs_data['horizon']) ? $fs_data['horizon'] : 0,
                'items' => isset($fs_data['items']) ? $fs_data['items'] : array(),
                'recruited_time' => isset($fs_data['recruited_time']) ? $fs_data['recruited_time'] : 0,
                'pose' => isset($fs_data['pose']) ? $fs_data['pose'] : null,
                
                // 实时数据（来自 players 表）
                'name' => $realtime_data['name'],
                'icon' => $realtime_data['icon'],
                'hp' => $realtime_data['hp'],
                'mhp' => $realtime_data['mhp'],
                'sp' => $realtime_data['sp'],
                'msp' => $realtime_data['msp'],
                'att' => $realtime_data['att'],
                'def' => $realtime_data['def'],
                'pls' => $realtime_data['pls'],
                'wep' => $realtime_data['wep'],
                'wepk' => $realtime_data['wepk'],
                'wepe' => $realtime_data['wepe'],
                'weps' => $realtime_data['weps'],
                'wepsk' => $realtime_data['wepsk'],
                'arb' => $realtime_data['arb'],
                'arbk' => $realtime_data['arbk'],
                'arbe' => $realtime_data['arbe'],
                'arbs' => $realtime_data['arbs'],
                'arbsk' => $realtime_data['arbsk'],
                'skills' => isset($realtime_data['clbpara']['skill']) && is_array($realtime_data['clbpara']['skill']) ? $realtime_data['clbpara']['skill'] : array(),
                'alive' => true
            );
        } else {
            // 种火已死亡或被销毁，但保留基本信息用于显示
            $fireseed_realtime_data[$fs_id] = array(
                'level' => $fs_data['level'],
                'mode' => $fs_data['mode'],
                'horizon' => isset($fs_data['horizon']) ? $fs_data['horizon'] : 0,
                'items' => isset($fs_data['items']) ? $fs_data['items'] : array(),
                'recruited_time' => isset($fs_data['recruited_time']) ? $fs_data['recruited_time'] : 0,
                'pose' => isset($fs_data['pose']) ? $fs_data['pose'] : null,
                'name' => '已死亡的种火',
                'icon' => '',
                'hp' => 0,
                'mhp' => 0,
                'sp' => 0,
                'msp' => 0,
                'att' => 0,
                'def' => 0,
                'pls' => 254,
                'alive' => false
            );
        }
    }
}

// 返回 JSON 数据 / Return JSON data
echo json_encode($fireseed_realtime_data, JSON_UNESCAPED_UNICODE);

?>
