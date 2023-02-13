<?php
if(!defined('IN_GAME')) exit('Access Denied');

# BGM相关配置文件：

# 未配置的默认播放音量（单位：百分比）
$default_volume = 20;

# 会播放BGM的事件（优先级最高——会覆盖默认曲集） 
# 具体触发时用不用这个数组都无所谓，可以直接调用： $clbpara['event_bgmbook'] = Array('指定事件曲集名');
$event_bgm = Array
(
	'test' => Array('event'),
);

# 会播放BGM的地图（优先级高——会覆盖默认曲集）
$pls_bgm = Array
(
	# 在英灵殿会播放对应曲集
	34 => Array('valhalla'),
);

# 会播放BGM的地图组（优先级低）
$parea_bgm = Array();

# 不需要额外条件即可播放BGM的曲集
$regular_bgm = Array('besynthed');

# 曲集内包含的BGM
$bgmbook = Array
(
	'besynthed' => Array(0,1,2,5,6,7,8,9,10),
	'valhalla' => Array(3,4),
	'event' => Array(11,12,13),
);

# 所有bgm编号清单：
$bgmlist = Array
(
	0 => Array(
		'name' => 'Skaven252 - Beetle Of Man',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2017-Dec-BeetleOfMan.mp3',
		'type' => 'audio/mpeg',
	),
	1 => Array(
		'name' => 'Skaven252 - 3864 Jumps',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2018-Nov-3864Jumps.mp3',
		'type' => 'audio/mpeg',
	),
	2 => Array(
		'name' => 'Skaven252 - Path to a Lenient Dusk',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Feb-PathtoaLenientDusk.mp3',
		'type' => 'audio/mpeg',
	),
	3 => Array(
		'name' => '林苍月 ~ Pale Moon Rising',
		'url' => 'https://res.brdts.online/BGM/Pale%20Moon%20Rising.ogg',
		'type' => 'audio/ogg',
	),
	4 => Array(
		'name' => 'Skaven252 - Neubruder',
		'url' => 'https://res.brdts.online/BGM/mmc-2017-jan-neubruder_mix03.mp3',
		'type' => 'audio/mpeg',
	),
	5 => Array(
		'name' => 'Skaven252 - Survivor One',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-jan-SurvivorOne.mp3',
		'type' => 'audio/mpeg',
	),
	6 => Array(
		'name' => 'Skaven252 - Proton Halo',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-Mar-ProtonHalo.mp3',
		'type' => 'audio/mpeg',
	),
	7 => Array(
		'name' => 'Skaven252 - Aqua Regia',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2021-07-AquaRegia.mp3',
		'type' => 'audio/mpeg',
	),
	8 => Array(
		'name' => 'Skaven252 - RMC Nighthawk',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-08-RMCNighthawk.mp3',
		'type' => 'audio/mpeg',
	),
	9 => Array(
		'name' => 'Skaven252 - Gamma Katana',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Jun-GammaKatana.mp3',
		'type' => 'audio/mpeg',
	),
	10 => Array(
		'name' => 'Skaven252 - Sublime Geometries',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-11_SublimeGeometries.mp3',
		'type' => 'audio/mpeg',
	),
	11 => Array(
		'name' => 'Skaven252 - Oathlands',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-Feb-Oathlands.mp3',
		'type' => 'audio/mpeg',
	),
	12 => Array(
		'name' => 'ElectricMudkip - Tons of Good Deeds...',
		'url' => 'https://res.dts.gay/BGM/BW2_DecisiveBattle.mp3',
		'type' => 'audio/mpeg',
	),
	13 => Array(
		'name' => 'EternalSushi & ElectricMudkip - ... and One Sin',
		'url' => 'https://res.dts.gay/BGM/XY_DecisiveBattle.mp3',
		'type' => 'audio/mpeg',
	),
);

?>
