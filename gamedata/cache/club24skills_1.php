<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/club24.func.php';
$club24cfg = club24_config();
$club_skillslist[24] = Array('s_hp','s_ad','f_heal','c24_verdict','c24_roulette','c24_gamble','c24_reforge','c24_allin');

$cskills['c24_verdict'] = Array(
	'name'=>'裁决', 'tags'=>Array('passive'), 'maxlvl'=>3, 'cost'=>Array(1,2,3,-1), 'input'=>'升级',
	'desc'=>'主动攻击命中后公开掷D6，每次战斗行动只掷一次。1点使本次最终伤害减半；一级6点、二级起5～6点追加一次连续攻击，三级1点时怒气+10。<br>当前连续攻击点数个数：[^skillpara|c24_verdict-faces^]；1点时怒气变化：[^skillpara|c24_verdict-rageback^]。',
	'svars'=>Array('lvl'=>0,'faces'=>0,'rageback'=>0),
	'pvars'=>Array('skillpara|c24_verdict-faces','skillpara|c24_verdict-rageback'),
);
$cskills['c24_roulette'] = Array(
	'name'=>'轮盘', 'tags'=>Array('battle'), 'maxlvl'=>3, 'cost'=>Array(3,4,5,-1), 'input'=>'升级',
	'desc'=>"消耗{$club24cfg['roulette_rage']}怒气，一级D6、二级D5、三级D4，最大点数射中敌人，否则射中自己。持械伤害为武器效果的{$club24cfg['roulette_mult_low']}～{$club24cfg['roulette_mult_high']}倍之间的随机整数；徒手为基础攻击～{$club24cfg['roulette_unarmed_cap']}（基础攻击更高时固定上限）。纯粹伤害，免死和反噬照常。<br>当前弹膛减少数：[^skillpara|c24_roulette-chambers^]。",
	'bdesc'=>"消耗{$club24cfg['roulette_rage']}怒气，赌一发纯粹伤害；未射中敌人就伤害自己。弹膛减少数：[^skillpara|c24_roulette-chambers^]。",
	'svars'=>Array('lvl'=>0,'chambers'=>0), 'vars'=>Array('ragecost'=>$club24cfg['roulette_rage']),
	'pvars'=>Array('skillpara|c24_roulette-chambers'),
);
$cskills['c24_gamble'] = Array(
	'name'=>'豪赌', 'tags'=>Array('battle'), 'maxlvl'=>3, 'cost'=>Array(2,3,4,-1), 'input'=>'升级',
	'desc'=>"消耗{$club24cfg['gamble_rage']}怒气，将忽略防御的基础伤害重掷为0～基础伤害的纯粹伤害。二级起自动掷两次取高，三级消耗降至{$club24cfg['gamble_rage_lv3']}。反噬照常。<br>当前额外掷骰次数：[^skillpara|c24_gamble-rerolls^]（负值取低）；怒气减免：[^skillpara|c24_gamble-ragecut^]。",
	'bdesc'=>'基础伤害重掷为纯粹伤害；额外掷骰：[^skillpara|c24_gamble-rerolls^]，怒气减免：[^skillpara|c24_gamble-ragecut^]。',
	'svars'=>Array('lvl'=>0,'rerolls'=>0,'ragecut'=>0), 'vars'=>Array('ragecost'=>$club24cfg['gamble_rage']),
	'pvars'=>Array('skillpara|c24_gamble-rerolls','skillpara|c24_gamble-ragecut'),
);
$cskills['c24_reforge'] = Array(
	'name'=>'重铸', 'tags'=>Array('active'), 'maxlvl'=>3, 'cost'=>Array(2,3,4,-1), 'input'=>'升级',
	'desc'=>"消耗{$club24cfg['reforge_rage']}怒气，重投背包0～6格的武器、防具或饰品；保留灵魂绑定、无限耐久，剧情必需品豁免。二级起可锁定一项：E效果、D耐久、S属性（如3E）；三级消耗降至{$club24cfg['reforge_rage_lv3']}。<br>当前怒气减免：[^skillpara|c24_reforge-ragecut^]。",
	'svars'=>Array('lvl'=>0,'ragecut'=>0), 'pvars'=>Array('skillpara|c24_reforge-ragecut'),
);
$cskills['c24_allin'] = Array(
	'name'=>'梭哈', 'tags'=>Array('switch'), 'maxlvl'=>1, 'cost'=>Array(5,-1), 'input'=>'升级',
	'desc'=>"开启后，生命、攻防与本社专属技能升级的数值收益乘以{$club24cfg['allin_fluc_min']}～{$club24cfg['allin_fluc_max']}倍，另有{$club24cfg['allin_neg_pct']}%概率整条变负。等级、解锁权限和开关不变；关闭不撤销已经掷出的收益。<br>当前开关：[^skillpara|c24_allin-active^]（0关闭、1开启）。",
	'svars'=>Array('lvl'=>0,'active'=>0), 'pvars'=>Array('skillpara|c24_allin-active'),
);
foreach(Array('c24_verdict','c24_roulette','c24_gamble','c24_reforge','c24_allin') as $c24sk) {
	$cskills[$c24sk]['log'] = '技能「'.$cskills[$c24sk]['name'].'」升级成功。<br>';
	$cskills[$c24sk]['events'] = Array('club24_upgrade');
	$cskills[$c24sk]['status'] = Array('skillpara|'.$c24sk.'-lvl');
	$cskills[$c24sk]['effect'] = Array(0=>Array('skillpara|'.$c24sk.'-lvl'=>'+=::1'));
}
foreach(Array('c24_coin'=>'掷币','c24_dice'=>'掷骰') as $c24sk=>$c24name) {
	$cskills[$c24sk] = Array('name'=>$c24name,'tags'=>Array('active'),'input'=>'发动',
		'desc'=>'消耗10点怒气，'.($c24sk == 'c24_coin' ? '掷一枚硬币' : '掷一枚D20').'，向全场公开喊话。',
		'events'=>Array('club24_skill'),'log'=>'','vars'=>Array('ragecost'=>10));
}
unset($c24sk,$c24name);
