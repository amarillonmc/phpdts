<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

# bot参数一览
/*

0.['clbpara']['botphase'] : bot阶段 用于判定bot总的行动方针
1.['clbpara']['botact'] ：包含bot具体的行为参数
2.['clbpara']['botstf']：记录bot获取过的素材
3.['clbpara']['botmix']：记录bot合成过的道具
*/

# bot在不同阶段的行动目标
$bot_action_phase = Array
(
	# 0.探索阶段：刚刚入场，目标是寻找合成素材
	0 => Array(
		'pose' => 3,
		'tactic' => 2,
	),
	# 1.偷反阶段：攒够合成素材，开始刷兵，会主动移动到有声音或有兵死掉的位置
	1 => Array(
		'pose' => 4,
		'tactic' => 3,
	),
	# 2.强袭阶段：武器效果到达阈值，开始合成广域主动猎杀场上玩家目标
	2 => Array(
		'pose' => 2, //小开不算开
		'tactic' => 2,
	),
	# 3.躲避阶段：场上存在其他更强的玩家，进入躲避阶段，不停移动躲避敌人，并把身上的钱全部拿来买雷
	3 => Array(
		'pose' => 4, 
		'tactic' => 4,
	),
);

# bot在阶段0寻找素材时要前往的地图
$bot_moveto_phase = Array
(
	1 => 30,
	2 => 21,
	3 => 16,
	4 => 21,
);

# bot会拾取的道具名白名单（注意：商店购买也算在内）
$bot_can_get_itemlist = Array
(
	'治疗针','体力回复药','凸眼鱼','针线包','驱云弹','钉','磨刀石','沉默磨刀石','黑磨刀石','『祝福宝石』','『灵魂宝石』',
	'暗鸦之羽',
);

$bot_stfid = Array
(
	0 => Array
	(
		'增幅设备' => 'r0',
		'生命探测器' => 'r1',
		'广域生命探测器' => 'r2',
		'手机' => 'p1',
		'笔记本电脑' => 'p2',
		'移动PC' => 'p3',
	),
	1 => Array
	(
		'原型武器P' => 'c11',
		'实验装甲A' => 'c12',
		'小棍棒' => 'c13',
		'冰沙' => 'c14',
		'御神签' => 'c15',
		'《哲♂学》' => 'c16',
		'☆金属拳套☆	' => 'c17',
		'★RPG-7★' => 'c19',
	),
	2 => Array
	(
		'『风魔激光刃』' => 'c21',
		'『祝福宝石』' => 'c22',
	),
	3 => Array
	(
		'《小黄的草帽》' => 'c31',
		'《小黄的钓鱼竿》' => 'c32',
		'《小黄的行军靴》' => 'c33',
		'《小黄的收服特训》' => 'c34',
		'《小黄的常磐之力》' => 'c35',
	),
	4 => Array
	(
		'『连射激光』' => 'c41',
		'『高性能子机』' => 'c42',
	),
);

$bot_mixid = Array
(
	'『T-LINK念动冲拳』' => 'm11',
	'Azurewrath' => 'm21',
	'《小黄的精灵球》' => 'm31',
	'《小黄的超级球》' => 'm32',
	'《小黄的大师球》' => 'm33',
);

$bot_player_list = Array
(
	'type' => 18,
	'bid' => 0,
	'inf' => '',
	'rage' => 0,
	'pose'=> 2,
	'tactic' => 3,
	'killnum' => 0,
	'rp' => 0,
	'mhp' => 400,
	'msp' => 8888,
	'att' => 120,
	'def' => 120,
	'lvl' => 0,
	'skill' => 40,
	'money' => 50,
	'art' => '◆焰火',
	'artk' => 'A',
	'arte' => 1,
	'arts' => 1,
	'artsk' => 'H',
	'itm1' => '治疗针',
	'itmk1' => 'HH',
	'itme1' => 100,
	'itms1' => 60,
	'itm2' => '体力回复药',
	'itmk2' => 'HS',
	'itme2' => 100,
	'itms2' => 60,
	'itm5' => '暗鸦之羽',
	'itmk5' => 'HS',
	'itme5' => 100,
	'itms5' => '∞',
	'itmsk5' => 'v',
	'itm6' => '银白盒子',
	'itmk6' => 'ps',
	'itme6' => 1,
	'itms6' => 1,
	'sub' => array
	(
		0 => array
		(
			'name' => '雷文·Ｋ',
			'nick' => '参展者',
			'icon' => 128,
			'gd' => 'f',
			'club' => 2,
			'pls' => 21,
			'wep' => '『寻星勇者』',
			'wepk' => 'WK',
			'wepe' => 75,
			'weps' => 45,
			'wepsk' => 'd',
			'arb' => '男生校服',
			'arbk' => 'DB',
			'arbe' => 15,
			'arbs' => 5,
		),
		1 => array
		(
			'name' => '雷文·Ｇ',
			'nick' => '参展者',
			'icon' => 128,
			'gd' => 'f',
			'club' => 4,
			'pls' => 21,
			'wep' => '『单向火箭炮』',
			'wepk' => 'WG',
			'wepe' => 90,
			'weps' => 188,
			'wepsk' => 'do',
			'arb' => '女生校服',
			'arbk' => 'DB',
			'arbe' => 15,
			'arbs' => 5,
		),
		2 => array
		(
			'name' => '雷文·Ｃ',
			'nick' => '参展者',
			'icon' => 128,
			'gd' => 'f',
			'club' => 3,
			'pls' => 16,
			'wep' => '冰冻青蛙',
			'wepk' => 'WC',
			'wepe' => 90,
			'weps' => 198,
			'wepsk' => 'i',
			'arb' => '女生校服',
			'arbk' => 'DB',
			'arbe' => 15,
			'arbs' => 5,
		),
		3 => array
		(
			'name' => '雷文·Ｐ',
			'nick' => '参展者',
			'icon' => 128,
			'gd' => 'f',
			'club' => 1,
			'pls' => 30,
			'wep' => '电击鞭',
			'wepk' => 'WP',
			'wepe' => 64,
			'weps' => 32,
			'wepsk' => 'e',
			'arb' => '男生校服',
			'arbk' => 'DB',
			'arbe' => 15,
			'arbs' => 5,
		),
	),
);

?>
