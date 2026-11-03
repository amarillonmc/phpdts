<?php
if(!defined('IN_GAME')) exit('Access Denied');

return Array(
	'roulette_mult_low' => 1,
	'roulette_mult_high' => 7,
	'roulette_rage' => 127,
	'roulette_unarmed_cap' => 3337,
	'gamble_rage' => 30,
	'gamble_rage_lv3' => 20,
	'reforge_rage' => 20,
	'reforge_rage_lv3' => 15,
	'shop_mult_min' => 0.01,
	'shop_mult_max' => 2.98,
	'allin_fluc_min' => 0.5,
	'allin_fluc_max' => 1.5,
	'allin_neg_pct' => 20,
	'item_effect_mult' => 2,
	'item_durability_mult' => 2,
	// 只投无需配套参数的属性；不生成直死、多重、进化、背包等结构性属性。
	'weapon_attrs' => 'cdefikLNnoprRSuwxyVz',
	'armor_attrs' => 'AaBbCDEFGHhIKMPqUWVxz',
	'accessory_attrs' => 'AaBbCDEFGHhIKMPqUWcdefikLNnpruwxyVz',
	// W 是武器，D 是防具，A 是饰品；其他类别默认不改。
	// 结局物品、入口及必需装备原样保留。未实装路线的物品不在这里虚构能力。
	'exempt_items' => Array(
		'『G.A.M.E.O.V.E.R』','游戏解除钥匙','移动PC','Untainted Glory',
		'『C.H.A.O.S』','『T.E.R.R.A』','『A.Q.U.A』','『V.E.N.T.U.S』',
		'武神之魂','琉璃血','《董子语录》','圣光啊!你有看到那个敌人吗！',
		'社员专用的ID卡','冰炎钥匙·炎','冰炎钥匙·冰','银色盒子','越狱后的银色盒子','群青多面体',
		'■速溶咖啡■','矿泉水','面包','女生校服','男生校服',
	),
);
