<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 幻想世界：后台可修改每局抽取数，0 表示暂停投放。池不足时不重复抽取。
$vncreation_npc_count = 3;
$vncreation_cfg = array(
	'enabled' => true,
	'item_base' => 200,
	'bag_base' => 100,
	'effect_cost' => 1,
	'durability_cost' => 2,
	'attribute_cost' => 100,
	'infinite_cost' => 2000,
	'max_count' => 100,
	'reroll_cost' => 20, // 每件装备：等级 × 此数值。
	'npc_levels' => array(
		1 => array('name'=>'见习', 'cost'=>200, 'lvl'=>10, 'mhp'=>500, 'msp'=>500, 'att'=>60, 'def'=>60, 'skill'=>80, 'money'=>100),
		2 => array('name'=>'精锐', 'cost'=>400, 'lvl'=>25, 'mhp'=>1500, 'msp'=>1000, 'att'=>150, 'def'=>150, 'skill'=>200, 'money'=>300),
		3 => array('name'=>'勇士', 'cost'=>800, 'lvl'=>50, 'mhp'=>4000, 'msp'=>2000, 'att'=>350, 'def'=>350, 'skill'=>400, 'money'=>600),
		4 => array('name'=>'英杰', 'cost'=>1600, 'lvl'=>75, 'mhp'=>8000, 'msp'=>3000, 'att'=>700, 'def'=>700, 'skill'=>700, 'money'=>1200),
		5 => array('name'=>'传说', 'cost'=>3200, 'lvl'=>100, 'mhp'=>15000, 'msp'=>5000, 'att'=>1200, 'def'=>1200, 'skill'=>1000, 'money'=>2400),
	),
	'equip_levels' => array(
		1 => array('name'=>'普通', 'cost'=>20, 'e'=>30, 's'=>30, 'basic'=>0, 'rare'=>0),
		2 => array('name'=>'优良', 'cost'=>60, 'e'=>80, 's'=>60, 'basic'=>1, 'rare'=>0),
		3 => array('name'=>'稀有', 'cost'=>150, 'e'=>200, 's'=>120, 'basic'=>1, 'rare'=>1),
		4 => array('name'=>'史诗', 'cost'=>400, 'e'=>500, 's'=>240, 'basic'=>2, 'rare'=>1),
		5 => array('name'=>'传说', 'cost'=>1000, 'e'=>1000, 's'=>500, 'basic'=>2, 'rare'=>2),
	),
	'equip_slots' => array('wep'=>'武器', 'arb'=>'身体', 'arh'=>'头部', 'ara'=>'手臂', 'arf'=>'腿部', 'art'=>'饰物'),
	'equip_kinds' => array('wep'=>array('WP','WK','WG','WJ','WC','WD','WF'), 'arb'=>array('DB'), 'arh'=>array('DH'), 'ara'=>array('DA'), 'arf'=>array('DF'), 'art'=>array('A')),
	'rolls' => array(
		'weapon' => array('basic'=>array('p','u','i','e','w'), 'rare'=>array('n','r','f','k')),
		'armor' => array('basic'=>array('P','K','G','C','D','F'), 'rare'=>array('A','a','B','b')),
	),
);
