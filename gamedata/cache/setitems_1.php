<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 套装相关配置文件

# 套装部件登记：（部位 → 装备名 → 对应套装编号）
$set_items = Array
(
	'wep' => Array
	(
		'节操炸弹' => 'jc',
		'寂寞' => 'jm',
		'幻之刃' => 'fan',
		'幻之使魔' => 'fan',
		'永恒之桶' => 'ete',
		'新华里的投入' => 'xhl',
		'新华里的震撼' => 'xhl',
		'新华里的乱舞' => 'xhl',
		'新华里的手势' => 'xhl',
		'新华里的呐喊' => 'xhl',
		'新华里的眼神' => 'xhl',
	),

	'arb' => Array
	(
		'节操' => 'jc',
		'幻之甲' => 'fan',
		'永恒之甲' => 'ete',
		'新华里的西服' => 'xhl',
	),

	'arh' => Array
	(
		'节操' => 'jc',
		'寂寞' => 'jm',
		'幻之盔' => 'fan',
		'永恒之盔' => 'ete',
		'新华里的领带' => 'xhl',
	),

	'ara' => Array
	(
		'节操' => 'jc',
		'寂寞' => 'jm',
		'幻之手镯' => 'fan',
		'永恒之手镯' => 'ete',
		'新华里的手表' => 'xhl',
	),

	'arf' => Array
	(
		'节操' => 'jc',
		'寂寞' => 'jm',
		'幻之靴' => 'fan',
		'永恒之靴' => 'ete',
		'新华里的皮鞋' => 'xhl',
	),

	'art' => Array
	(
		'节操' => 'jc',
		'新华里的增员' => 'xhl',
	),
);

# 套装登记：
$set_items_info = Array
(
	'jc' => Array
	(
		// 套装名：
		'name' => '有节操！',
		// 套装组件上下限
		'active' => Array(1,6),
		// 套装奖励：
		// 套装奖励介绍：
	),
	'jm' => Array
	(
		'name' => '是寂寞...',
		'active' => Array(1,4),
	),
	'xhl' => Array
	(
		'name' => '业务员',
		'active' => Array(1,6),
	),
	'fan' => Array
	(
		'name' => '幻想之遗',
		'active' => Array(1,5),
	),
	'ete' => Array
	(
		'name' => '永恒之物',
		'active' => Array(1,5),
	),
);


?>
