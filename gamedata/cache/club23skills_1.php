<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/club23.func.php';
$club23cfg = club23_config();
$club_skillslist[23] = Array('s_hp','s_ad','f_heal','c23_trauma','c23_scrape','c23_shroud','c23_afterimage','c23_patch','c23_vacancy');

$cskills['c23_trauma'] = Array(
	'name' => '伤迹', 'tags' => Array('active'), 'input' => '剥落',
	'desc' => "将当前武器转化为<span class='purple'>剥落的记忆</span>，类别为「？？？？」，射程沿用灵力系。<br>保留效果与有限耐久，清除原属性和专用参数；无限耐久改为{$club23cfg['memory_durability']}。<br>记忆以{$club23cfg['memory_hit_rate']}%独立概率命中；命中玩家增加「武器效果×{$club23cfg['subliminal_per_effect']}」点记忆侵入（S），不造成普通伤害，对 NPC 不积累 S。<br>目标带有 S 时，每次与任意持有记忆的玩家交战，开战先掷 1d1000；结果严格小于 S 时，造成等于目标当前生命的伤害。每人每次交战判定一次，新施加的 S 从下次交战生效，复活照常结算。<br><span class='red'>拥有此称号时，常规武器对玩家和 NPC 的最终伤害只保留{$club23cfg['ordinary_damage_percent']}%。</span><br><span class='grey'>你揭开的地方，曾经有人疼过。</span>",
	'events' => Array('club23_skill'), 'log' => '',
);
$cskills['c23_scrape'] = Array(
	'name' => '旧痕', 'tags' => Array('passive'),
	'desc' => "持有剥落的记忆时，承受的武器伤害降至{$club23cfg['scrape_damage_percent']}%，包括常规固定伤害。记忆侵入的即死判定不受此减伤影响。<br><span class='grey'>最痛的那一下已经过去了。这里留着它的形状。</span>",
);
$cskills['c23_shroud'] = Array(
	'name' => '黑岚', 'tags' => Array('active'), 'input' => '领取',
	'desc' => "每成功移动或探索{$club23cfg['shroud_steps']}次，可领取一件效果{$club23cfg['memory_effect']}、耐久{$club23cfg['memory_durability']}的剥落的记忆。次数可累积，加入时获得{$club23cfg['initial_claims']}次领取机会。背包满时不消耗机会。<br>当前可用探索次数：<span class='yellow'>[^skillpara|c23_shroud-steps^]</span>。<br><span class='grey'>你没有找过它。你只是知道它在那里。</span>",
	'events' => Array('club23_skill'), 'log' => '',
	'svars' => Array('steps' => $club23cfg['shroud_steps']*$club23cfg['initial_claims']),
	'pvars' => Array('skillpara|c23_shroud-steps'),
);
$cskills['c23_afterimage'] = Array(
	'name' => '残像', 'tags' => Array('passive'),
	'desc' => "你的记忆命中玩家或空座接触生效后，{$club23cfg['afterimage_seconds']}秒内，该玩家移动、探索时的 S 消退量降至{$club23cfg['afterimage_decay_percent']}%。重复接触只刷新时间，不叠加幅度；每次仍至少消退1点。<br>平时每次成功移动或探索消退{$club23cfg['decay']}点；该次行动若再次受到记忆影响则不消退。<br><span class='grey'>你已经走出很远。那盏灯还在头顶。</span>",
);
$cskills['c23_patch'] = Array(
	'name' => '补缀', 'tags' => Array('active'), 'input' => '补缀',
	'desc' => "消耗一次黑岚领取机会，为当前记忆增加{$club23cfg['patch_durability']}耐久，或恢复最大生命的{$club23cfg['patch_heal_percent']}%（不超过生命上限）。无法完成时不消耗。<br><span class='grey'>这块记忆的颜色不太一样。暂时能用。</span>",
	'events' => Array('club23_skill'), 'log' => '',
);
$cskills['c23_vacancy'] = Array(
	'name' => '空座', 'tags' => Array('passive'),
	'desc' => '另一名玩家主动攻击并命中你，且你在交战后仍存活、仍持有记忆时，消耗1耐久向其施加一次记忆侵入，并触发残像。无需对方已有 S，不受反击射程限制。<br>每次交战最多一次；普通记忆反击已经命中时不重复施加，不额外掷即死骰。<br><span class="grey">对方的攻击穿过了你。那个空着的位置，终于有人看见。</span>',
);
