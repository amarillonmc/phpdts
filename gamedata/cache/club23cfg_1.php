<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 崩坏心核：初版数值。RuleSet 可用同名配置覆盖，不依赖结局路线。
return Array(
	'ordinary_damage_percent' => 10, // 常规武器最终伤害保留比例，包括固定伤害
	'scrape_damage_percent' => 50, // 持有记忆时承受的武器伤害比例；不影响即死判定
	'memory_hit_rate' => 90, // 独立命中率，不受攻防、熟练和连击属性影响
	'subliminal_per_effect' => 1, // 每点武器效果增加的 S 值
	'decay' => 10, // 成功移动/探索且本次未再次受记忆影响时降低的 S 值
	'afterimage_seconds' => 8,
	'afterimage_decay_percent' => 50,
	'shroud_steps' => 40, // 每次领取或补缀消耗的移动/探索次数
	'initial_claims' => 1,
	'memory_effect' => 80, // 黑岚生成的武器效果
	'memory_durability' => 20, // 黑岚武器耐久，也用于转化无限耐久武器
	'patch_durability' => 10,
	'patch_heal_percent' => 25,
	'distortion_threshold' => 200,
	'dialogue_threshold' => 400,
	'dialogue_chance_max' => 30, // 每次移动/探索触发对白的概率上限
);
