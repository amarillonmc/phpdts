<?php
if(!defined('IN_GAME')) exit('Access Denied');

# BGM相关配置文件：

# 未配置的默认播放音量（单位：百分比）
$default_volume = 25;

# 会播放BGM的地图（优先级高）
$pls_bgm = Array
(
	# 在英灵殿会播放对应曲集
	34 => Array('valhalla'),
);

# 会播放BGM的地图组（优先级低）
$parea_bgm = Array();

# 不需要额外条件即可播放BGM的曲集
$regular_bgm = Array('Skaven252');

# 曲集内包含的BGM
$bgmbook = Array
(
	'Skaven252' => Array(0,1,2),
	'valhalla' => Array(3,4),
);

# 所有bgm编号清单：
$bgmlist = Array
(
	0 => Array(
		'name' => 'Skaven252-MMC-2017-Dec-BeetleOfMan',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2017-Dec-BeetleOfMan.mp3',
		'type' => 'audio/mpeg',
	),
	1 => Array(
		'name' => 'Skaven252-MMC-2018-Nov-3864Jumps',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2018-Nov-3864Jumps.mp3',
		'type' => 'audio/mpeg',
	),
	2 => Array(
		'name' => 'Skaven252-MMC-2019-Feb-PathtoaLenientDusk',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Feb-PathtoaLenientDusk.mp3',
		'type' => 'audio/mpeg',
	),
	3 => Array(
		'name' => 'Pale Moon Rising',
		'url' => 'https://res.brdts.online/BGM/Pale%20Moon%20Rising.ogg',
		'type' => 'audio/ogg',
	),
	4 => Array(
		'name' => 'mmc-2017-jan-neubruder_mix03',
		'url' => 'https://res.brdts.online/BGM/mmc-2017-jan-neubruder_mix03.mp3',
		'type' => 'audio/mpeg',
	),
);

?>
