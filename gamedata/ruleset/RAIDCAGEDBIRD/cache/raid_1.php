<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

/*
 * RAID：暴走笼中鸟集中配置
 * RAID: Caged Bird Rampage centralized configuration
 *
 * 行为逻辑由本规则集的函数层读取；资源、数值与叙事开关集中保存在这里。
 * The ruleset behavior layer reads this file; resources, balance values and story switches live here.
 */
$raid_mode_config = Array(
	'version' => 1,
	'ruleset_id' => 'RAIDCAGEDBIRD',

	// 每轮状态与动作计数 / Per-round state and action counters
	'round' => Array(
		'state_key' => 'raid',
		'round_id_key' => 'gamenum',
		'initialize_during_prepare' => true,
		'player_state_key' => 'raid',
	),
	'action' => Array(
		// 任一玩家累计10次成功移动或探索后触发；失败动作、机器人与强制传送不计。
		// Trigger after any one player totals 10 successful moves/searches; failed, bot and forced moves do not count.
		'spawn_actions_per_player' => 10,
		'counted_commands' => Array('move', 'search'),
		'count_failed_actions' => false,
		'count_bot_actions' => false,
		'count_forced_moves' => false,
		'count_hidden_warp' => false,
		// 暴走笼中鸟每累计3次全局有效动作行动一次。
		// The rampaging bird takes one turn per 3 global eligible actions.
		'boss_action_threshold' => 3,
	),

	// 暴走笼中鸟 / Rampaging Caged Bird
	'boss' => Array(
		'type' => 94,
		'sub' => 0,
		'name' => '暴走笼中鸟',
		'icon' => 201,
		'charging_icon' => 203,
		'near_invincible' => true,
		'direct_hit_state' => 61,
		'direct_hit_mechanism_kill' => true,
		'direct_hit_leave_corpse' => false,
		'direct_hit_destroy_inventory' => true,
		'map_wipe_state' => 62,
		'target_highest_rp_rate' => 70,
		'target_random_rate' => 30,
		'charge_start_rate' => 30,
		'charge_turns' => 3,
		'stop_moving_while_charging' => true,
		'spawn_then_act_immediately' => false,
		'leave_player_corpses_on_wipe' => true,
		'leave_npc_corpses_on_wipe' => true,
		'destroy_map_items_on_wipe' => true,
		'kill_players_on_wipe' => true,
		'kill_npcs_on_wipe' => true,
		'container_immune_to_wipe' => true,
		'destroy_seal_parts_on_wipe' => false,
		'retransmit_destroyed_seal_parts' => true,
		// 203/203a是同一外壳的充能态小图与立绘，函数层只需切换icon。
		// Icons 203/203a are the charging thumbnail and portrait; behavior only swaps icon values.
		'conventional_death_behavior' => 'recover',
	),

	// 地图路线：函数层以BFS向当前目标推进一条边。
	// Map routes: behavior advances one edge toward the current target with BFS.
	'route_graph' => Array(
		0 => Array(25, 26, 17),
		1 => Array(17, 3, 23),
		2 => Array(13, 16, 18, 15),
		3 => Array(1, 17, 5, 23),
		4 => Array(8, 12, 24),
		5 => Array(3, 19, 11, 29, 30),
		6 => Array(13, 18, 20, 21, 30),
		7 => Array(25, 28, 33, 18, 29, 15),
		8 => Array(4, 12, 24),
		9 => Array(13, 16, 22, 31, 32),
		10 => Array(14, 20, 22, 34),
		11 => Array(5, 24, 27, 12),
		12 => Array(4, 8, 11, 21, 27),
		13 => Array(2, 6, 9, 18),
		14 => Array(10, 20, 21, 27),
		15 => Array(2, 7, 28, 31),
		16 => Array(2, 9, 31, 32),
		17 => Array(0, 1, 3),
		18 => Array(2, 6, 7, 13, 29, 33),
		19 => Array(5, 25, 29, 33),
		20 => Array(6, 10, 14, 22),
		21 => Array(6, 12, 14, 27, 30),
		22 => Array(9, 10, 20, 34),
		23 => Array(1, 3, 24),
		24 => Array(4, 8, 11, 23),
		25 => Array(0, 7, 19, 28),
		26 => Array(0, 28),
		27 => Array(11, 12, 14, 21),
		28 => Array(7, 15, 25, 26),
		29 => Array(5, 7, 18, 19, 30, 33),
		30 => Array(5, 6, 21, 29),
		31 => Array(9, 15, 16, 32),
		32 => Array(9, 16, 31),
		33 => Array(7, 18, 19, 29),
		34 => Array(10, 22),
	),
	'safe_locations' => Array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31),

	// 容器 / Containers
	'containers' => Array(
		'type' => 93,
		'count' => 48,
		'subs' => Array(0, 1, 2, 3),
		'icon' => 202,
		'alt_icon' => 202,
		'random_safe_location' => true,
		'passive' => true,
		'respawn' => false,
		'drop_count_range' => Array(1, 3),
		'special_treasure_rate' => 8,
		'normal_treasure_rate' => 72,
		'ordinary_item_rate' => 20,
	),

	// 宝藏池 / Treasure pool
	'treasure' => Array(
		'item_kind' => 'RAIDT',
		'item_special' => 'Z',
		'normal' => Array(
			'prefixes' => Array('被遗忘的', '低温封存的', '沾血的', '加密的', '完好无损的', '不属于此处的', '执行层遗落的', '芙蓉标记的'),
			'names' => Array('权限芯片', '实验记录', '星尘结晶', '古董怀表', '折叠保险箱', '记忆载片', '稀有药剂', '军用终端', '未登记艺术品', '高密度电池'),
			'suffixes' => Array('·原型', '【未登录】', '「幸存者遗物」', '·零号样本', '【封存解除】', '·真品', '「异常回收物」'),
			'rarity_range' => Array(1, 4),
			'value_range' => Array(300, 6000),
		),
		'special' => Array(
			Array('name' => '仪水镜·观测残片', 'rarity' => 5, 'value' => 9000),
			Array('name' => '雪兔【原型机】', 'rarity' => 5, 'value' => 12000),
			Array('name' => 'Untainted Glory·封存稿', 'rarity' => 6, 'value' => 18000),
			Array('name' => '武器师安雅的奖赏·真品', 'rarity' => 6, 'value' => 22000),
			Array('name' => '柏木诗集·未刊终章', 'rarity' => 6, 'value' => 26000),
			Array('name' => 'FARGO黑箱记录', 'rarity' => 7, 'value' => 32000),
			Array('name' => '旧世界的VR核心', 'rarity' => 7, 'value' => 40000),
			Array('name' => '被删去的第六外壳记录', 'rarity' => 7, 'value' => 50000),
		),
	),

	// 非宝藏折算：沿用元素大师的价值常量，再乘惩罚倍率。
	// Non-treasure conversion: reuse Element Master value constants, then apply the penalty multiplier.
	'scoring' => Array(
		'non_treasure_penalty' => 0.3,
		'default_kind_rate' => 0.8,
		'kind_rates' => Array(
			'T' => 0.3,
			'WGK' => 1.5, 'WCF' => 1.5, 'WCP' => 1.5, 'WKF' => 1.5,
			'WKP' => 1.5, 'WFK' => 1.5, 'WDG' => 1.5, 'WDF' => 1.5,
			'WJ' => 1.2, 'WB' => 1.2,
			'HH' => 0.03, 'HS' => 0.03, 'HB' => 0.05, 'PB2' => 0.035,
			'PB' => 0.03, 'PH' => 0.02, 'PS' => 0.02,
			'C' => 10, 'ME' => 18, 'MH' => 17, 'M' => 10, 'V' => 10, 'VV' => 15,
			'GBh' => 5, 'GBr' => 0.003, 'GBi' => 0.005, 'GBe' => 0.005, 'GB' => 0.004,
		),
		'default_property_value' => 50,
		'property_values' => Array(
			'R' => 11, 'N' => 68, 'n' => 89, 'y' => 90, 'r' => 144,
			'u' => 52, 'i' => 34, 'w' => 49, 'e' => 39, 'p' => 51,
			'd' => Array('default' => 77, 'WD' => 22, 'WDG' => 33, 'WDF' => 33),
			'f' => 78, 'k' => 79,
			'P' => 14, 'K' => 14, 'C' => 14, 'G' => 14, 'F' => 14, 'D' => 28,
			'A' => 101, 'B' => 1, 'U' => 17, 'E' => 17, 'I' => 17, 'W' => 17,
			'q' => 17, 'a' => 89, 'b' => 1, 'M' => 33, 'S' => 15, 'c' => 26,
			'H' => 42, 'h' => 1997, 'j' => 11, 'J' => 22, 'o' => 66, 'z' => 77,
			'x' => 777, 'Z' => 1,
		),
		'infinite_durability_value' => 0,
		'rounding' => 'round',
		'minimum_score' => 0,
		'achievement_id' => 611,
		'achievement_value_cap' => 99999999,
		'award_account_credits' => false,
		'extraction_state' => 63,
		'corpse_score_transfer' => true,
		'corpse_score_copy' => false,
	),

	// 平息笼中鸟 / Pacify the Caged Bird
	'pve' => Array(
		'shared_progress' => true,
		'material_type_count' => 1,
		// 每轮材料从全图掉落与本规则集宝藏池中抽取。
		// Each round draws materials from whole-map drops and this ruleset's treasure pool.
		'material_sources' => Array('mapitem', 'normal_treasure', 'special_treasure'),
		'data_target' => 50000,
		'material_supply_margin' => 1.25,
		'consume_converted_items' => true,
		'hidden_group' => 3,
		'source_location' => 121,
		'source_location_name' => '代码源流',
		'warp_item_name' => '【源流折跃信标】',
		'part_count' => 3,
		'parts' => Array(
			Array('id' => 'cage_frame', 'name' => '封印装置部件【笼架】', 'itmk' => 'Y', 'itme' => 1, 'itms' => 1, 'itmsk' => 'Z'),
			Array('id' => 'lock_bolt', 'name' => '封印装置部件【锁销】', 'itmk' => 'Y', 'itme' => 1, 'itms' => 1, 'itmsk' => 'Z'),
			Array('id' => 'calibration_core', 'name' => '封印装置部件【校准核】', 'itmk' => 'Y', 'itme' => 1, 'itms' => 1, 'itmsk' => 'Z'),
		),
		'distinct_spawn_locations' => true,
		'distinct_deploy_locations' => true,
		'part_recovery_watchdog' => true,
		'retransmit_on_corpse_loss' => true,
		'retransmit_on_carrier_extraction' => true,
		'retransmit_on_forbidden_zone_cleanup' => true,
		'winning_team_snapshot' => 'current_team',
		// 第三部件部署时，仅当前仍存活且尚未撤离的同队成员共享胜利。
		// When the third part is deployed, only living, non-extracted current teammates share the victory.
		'include_dead_team_members' => false,
		'include_extracted_team_members' => false,
		'deduplicate_team_by_ip' => true,
		'victory_score_base' => 50000,
		'victory_score_multiplier' => 2.0,
		'victory_score_formula' => '(personal_score + victory_score_base) * victory_score_multiplier',
		'winmode' => 8,
	),

	// 本模式不可达的旧路线资源；函数层仍应拒绝管理员注入的同名终局物品。
	// Old-route resources unreachable in this mode; behavior must also reject admin-injected terminal items.
	'disabled_route_items' => Array(
		'✦种火钥匙', '✦钥匙碎片', '✦【自律AI呼唤器】', '✦NPC钥匙·一阶段', '✦✦NPC钥匙·二阶段',
		'挑战者之印', '挑战者之印Ⅱ', '黑色碎片', '【我想要领略真正的红杀之力】',
		'冰炎钥匙·炎', '冰炎钥匙·冰', '电掣召唤仪', '游戏解除钥匙', '破灭之诗', '黑色发卡',
		'『G.A.M.E.O.V.E.R』', '【E.S.C.A.P.E】', '奇怪的按钮', '→【单兵撤退按钮】←',
	),
	'endings' => Array(
		'allow_pvp_extraction' => true,
		'allow_pve_seal' => true,
		'allow_last_survivor' => false,
		'zero_alive_winmode' => 1,
		'zero_alive_immediate' => true,
		'allow_admin_stop' => true,
		'allow_no_participants' => true,
	),
	'states' => Array(
		'direct_hit' => 61,
		'map_wipe' => 62,
		'extraction' => 63,
	),
);

// 兼容规则逻辑层的简短变量名 / Compatibility alias used by the behavior layer.
$raidcfg = $raid_mode_config;

?>
