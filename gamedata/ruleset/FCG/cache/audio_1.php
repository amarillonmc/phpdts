<?php
if(!defined('IN_GAME')) exit('Access Denied');

/**
 * FCG（种火集换牌）专属 BGM 映射架子（复用 audio_1.php 标准结构）
 *
 * 使用说明：
 * 实际音频文件放到 ./audio/ 目录下后，将下方对应条目的 'url' 填入文件名（如 'fcg_lobby.mp3'）即可自动生效。
 * 当 'url' 为空字符串 '' 时，前端播放器静默待机，不发起无效音频请求。
 */

// 场景常驻 BGM 队列（对齐 $bgm_Query 结构：id => array(url, name, loop)）
$bgm_Query = array(
	// 1: 牌室大厅 / 卡册构筑 / 商店
	1 => array(
		'id' => 'fcg_lobby',
		'url' => '',          // 待填入，例如: 'fcg_lobby.mp3'
		'name' => '牌室微光（大厅）',
		'type' => 'audio/mp3',
		'loop' => 1
	),
	// 2: 露比与斯诺的课间 / 心理测试
	2 => array(
		'id' => 'fcg_classroom',
		'url' => '',          // 待填入，例如: 'fcg_classroom.mp3'
		'name' => '露比与斯诺的课间',
		'type' => 'audio/mp3',
		'loop' => 1
	),
	// 3: 常规对战（小种火陪练 / 普通层）
	3 => array(
		'id' => 'fcg_battle_normal',
		'url' => '',          // 待填入，例如: 'fcg_battle_normal.mp3'
		'name' => '火花交锋（常规对战）',
		'type' => 'audio/mp3',
		'loop' => 1
	),
	// 4: 强敌/认真模式/残影/真人约战
	4 => array(
		'id' => 'fcg_battle_boss',
		'url' => '',          // 待填入，例如: 'fcg_battle_boss.mp3'
		'name' => '炽焰决胜（强敌对战）',
		'type' => 'audio/mp3',
		'loop' => 1
	),
	// 5: 轮抽远征地图
	5 => array(
		'id' => 'fcg_expedition',
		'url' => '',          // 待填入，例如: 'fcg_expedition.mp3'
		'name' => '灰烬远征',
		'type' => 'audio/mp3',
		'loop' => 1
	)
);

// 强制循环曲目编号（对齐 $Force_Loop）
$Force_Loop = array(1, 2, 3, 4, 5);

// 特殊事件/特定对手覆盖 BGM（对齐 $event_bgm_list）
$event_bgm_list = array(
	'opponent_20' => '', // #20 林无月专曲（预留）
	'victory' => '',     // 胜利结算短曲（预留）
	'defeat' => ''       // 落败结算短曲（预留）
);
?>
