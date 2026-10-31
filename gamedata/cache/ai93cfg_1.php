<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 仅调整生态频率，默认值保持原有行为；RuleSet 可用同名文件覆盖。
return Array(
	'event_rate'=>20, // AI事件触发率（%）
	'drip_chance'=>8, // 零散入场概率（%）
	'drip_delay'=>300, // 开局后等待秒数
	'drip_max'=>8,
	'tick_act_min'=>1,
	'tick_act_max'=>2,
	'injury_threshold'=>60, // 受伤行动生命百分比
	'injury_chance'=>50,
	'weather_chance'=>5,
	'weather_cap_recent'=>3,
	'weather_cap_normal'=>2,
	'idol_trash_chance'=>80,
	'caller_chance'=>25,
	'act_chance_five'=>40,
	'act_chance_idol'=>30,
	'act_chance_maid'=>50,
	'sing_max'=>4,
	'sing_cooldown'=>900,
	'chicken_chance'=>25,
	'chicken_cooldown'=>3600,
	'chicken_max'=>2,
	'react_cooldown'=>45,
	'migrate_max'=>2,
	'trash_max'=>30,
);
