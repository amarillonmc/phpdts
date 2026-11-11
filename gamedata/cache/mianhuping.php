<?php
if(!defined('IN_GAME')) exit('Access Denied');

$cskills['tl_binglai'] = Array(
	'name' => '饼来',
	'tags' => Array('battle','player'),
	'desc' => '装备或携带「锅」时，消耗10点怒气，从能做出的十二式配方中等概率选一道，消耗背包中的面包、矿泉水或曾经的荣光-橙黄。锅不消耗。<br>配方可强化伤害、连续攻击、发动群攻或获得临时生命。每段攻击均可被回避；群攻命中后只分摊一次伤害，随机波及同位置2～5人（含自己与对手），保底留下1点生命。<br>临时生命在发动时获得，与命中无关；先抵消伤害再扣生命，本场交战结束时清除，可叠加至发放时最大生命的200%。',
	'vars' => Array('ragecost' => 10),
	'unlock' => Array(
		'type' => '[:type:] == 0',
		'binglai_pot' => 'binglai_inventory($data)["锅"] > 0',
		'binglai_food' => 'array_sum(array_intersect_key(binglai_inventory($data), array_flip(Array("面包","矿泉水","曾经的荣光-橙黄")))) > 0',
	),
	'lockdesc' => Array(
		'type' => '只有玩家才能发动「饼来」',
		'binglai_pot' => '需要装备或携带<span class="yellow">锅</span>',
		'binglai_food' => '背包里需要有面包、矿泉水或曾经的荣光-橙黄',
	),
);

$titles_list[9302] = '面糊饼职人';
$title_desc[9302] = Array('class'=>'tlime','title'=>'入场获得「锅」（殴系武器，效果40，耐久无限）并学会战斗技「饼来」：消耗10点怒气和背包食材，随机制作一道配方发动攻击。');
$title_valid[9302] = Array(
	'itm6'=>'锅','itmk6'=>'WP','itme6'=>40,'itms6'=>'∞','itmsk6'=>'','itmpara6'=>'',
	'clbpara'=>Array('skill'=>Array('tl_binglai')),
);

// 每道可行配方只占一个抽签位置；可变用量在选中配方后另掷1～3。
$binglai_recipes = Array(
	1 => Array('name'=>'烤面包', 'cost'=>Array('面包'=>1), 'random'=>'面包', 'mult'=>1, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>10),
	2 => Array('name'=>'热水泼洒', 'cost'=>Array('矿泉水'=>1), 'random'=>'矿泉水', 'mult'=>1.1, 'hits'=>1, 'aoe'=>true, 'shield_pct'=>0),
	3 => Array('name'=>'面糊饼', 'cost'=>Array('面包'=>2,'矿泉水'=>1), 'mult'=>1.4, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>20),
	4 => Array('name'=>'面糊汤', 'cost'=>Array('面包'=>1,'矿泉水'=>2), 'mult'=>1, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>60),
	5 => Array('name'=>'法式面包棍棒', 'cost'=>Array('面包'=>1,'矿泉水'=>1), 'mult'=>1.2, 'hits'=>2, 'aoe'=>false, 'shield_pct'=>0),
	6 => Array('name'=>'高级法式面包棍棒', 'cost'=>Array('面包'=>2,'矿泉水'=>2), 'mult'=>1.4, 'hits'=>2, 'aoe'=>false, 'shield_pct'=>0),
	7 => Array('name'=>'炸果酱', 'cost'=>Array('曾经的荣光-橙黄'=>1), 'mult'=>1, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>30),
	8 => Array('name'=>'烤果酱面包', 'cost'=>Array('曾经的荣光-橙黄'=>1,'面包'=>1), 'random'=>'面包', 'mult'=>1, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>40),
	9 => Array('name'=>'果酱汤', 'cost'=>Array('曾经的荣光-橙黄'=>1,'矿泉水'=>1), 'random'=>'矿泉水', 'mult'=>1.3, 'hits'=>1, 'aoe'=>true, 'shield_pct'=>30),
	10 => Array('name'=>'果酱面糊饼', 'cost'=>Array('曾经的荣光-橙黄'=>1,'面包'=>2,'矿泉水'=>1), 'mult'=>1.4, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>20),
	11 => Array('name'=>'果酱面糊汤', 'cost'=>Array('曾经的荣光-橙黄'=>1,'面包'=>1,'矿泉水'=>2), 'mult'=>1, 'hits'=>1, 'aoe'=>false, 'shield_pct'=>60),
	12 => Array('name'=>'调味法式面包棍棒', 'cost'=>Array('曾经的荣光-橙黄'=>1,'面包'=>1,'矿泉水'=>1), 'mult'=>1.15, 'hits'=>3, 'aoe'=>false, 'shield_pct'=>0),
);
