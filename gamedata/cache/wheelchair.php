<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 公共辅助技能，RuleSet 的社团技能开关仍然有效。
$wheelchair_skills = array(
	'wc_appraise' => array('鉴定','显示当前／完好战力、装备替换收益和遭遇对手的估计战力。数值是估计，不能保证获胜。'),
	'wc_prepare' => array('整备','探索前自动换装、补给、整理物品，并根据禁区预告和公开死亡消息避险。准备不足先提醒一次，再点探索仍会继续。'),
	'wc_route' => array('导航','在行动栏输入合成目标并选择配方；探索时自动寻找、购买、保留素材并逐步合成。仍需正常探索、赚钱和战斗。'),
	'wc_hunt' => array('追猎','选择小兵、NPC种类或玩家姓名，根据视野、最近遭遇和公开消息寻找目标；优势足够时使用普通攻击，久寻无果则换地点。'),
	'wc_grow' => array('精进','探索前按当前社团和武器学习适用技能；保留自愈点数、补给和合成预算，在商店购买适用的熟练书和更强装备。'),
	'wc_review' => array('复盘','保留最近十二次行动的得失与原因，提示连续探索没有进展、生命被探索消耗等问题。此技能没有任何轮椅惩罚。'),
);
$wheelchair_penalty_desc = '学习后，本局此后获得的切糕减半；除复盘外五种辅助逐项叠加，单笔向下取整。暂停或失去技能也不取消本局折扣；真解禁、真解离开放后将改走对应伪路线。';
foreach($wheelchair_skills as $wc_key => $wc_info) {
	$wc_desc = $wc_info[1].($wc_key === 'wc_review' ? '' : $wheelchair_penalty_desc);
	$cskills[$wc_key] = array('name'=>$wc_info[0], 'tags'=>array('passive','player'), 'desc'=>$wc_desc);
	$tps_name['《'.$wc_info[0].'》'] = array('class'=>'lime', 'title'=>'使用后学会「'.$wc_info[0].'」。'.$wc_desc.' 灵魂绑定；丢弃即销毁。仅携带不产生惩罚。');
}
$titles_list[9301] = '轮椅';
$title_desc[9301] = array('class'=>'lime','title'=>'入场背包放入鉴定、整备、导航、追猎、精进、复盘六本灵魂绑定技能书。原入场物品寄存安全箱，首次取出免费；面包与矿泉水强化。可以只学复盘或丢弃不需要的书。');
// 入场的物品迁移须在玩家物品初始化完成后处理，不能在这里覆盖槽位。
$title_valid[9301] = array();
unset($wc_key,$wc_info,$wc_desc);
