<?php

if(!defined('IN_GAME')) {
    exit('Access Denied');
}

// 本文件经常在函数体内被 include_once；显式绑定全局作用域，避免配置只落入调用函数的局部变量。
// This file is often include_once'd inside functions; bind globals explicitly so config remains visible.
global $ruleset_enabled, $ruleset_config;

/*
 * RuleSet系统（时光重现）配置文件
 * 用于配置旧版本游戏模式的相关设置
 */

// 系统总开关
$ruleset_enabled = true;

// RuleSet配置
$ruleset_config = Array(
    'YELLOWKNIFE' => Array(
        'name' => 'YELLOWKNIFE',
        'description' => '以当前大房间资源和规则为基准保存的剧情改造用RuleSet。后续可在此基础上独立调整资源层、NPC、物品和配置。',
        'credits_cost' => 1,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 20,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'YELLOWKNIFE',
                'ruleset_name' => 'YELLOWKNIFE',
            ),
        ),
        'override_modules' => Array(),
        'title_system' => 2,
        'club_skills' => 2,
        'avatar_config' => Array(
            'use_ruleset_avatars' => false,
            'avatar_path' => './img/',
            'male_avatars' => 0,
            'female_avatars' => 0,
            'npc_avatars' => Array(),
            'special_avatars' => Array(),
        ),
        'story_config' => Array(
            'opening_story' => '沿用当前大房间开场剧情。',
            'ending_story' => '沿用当前大房间结局剧情。',
        ),
    ),

    'LAIKAADVENT' => Array(
        'name' => '莱卡的进击 / Laika Advent',
        'description' => '以YELLOWKNIFE资源为基础，加入星辰之代价、悖论祝福与宇宙公理之齿轮的剧情压力模式。',
        'credits_cost' => 1,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 20,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'LAIKAADVENT',
                'ruleset_name' => '莱卡的进击',
                'dialogue' => 'laika_intro',
            ),
        ),
        'override_modules' => Array('laika_advent'),
        'title_system' => 2,
        'club_skills' => 2,
        'avatar_config' => Array(
            'use_ruleset_avatars' => false,
            'avatar_path' => './img/',
            'male_avatars' => 0,
            'female_avatars' => 0,
            'npc_avatars' => Array(),
            'special_avatars' => Array(),
        ),
        'story_config' => Array(
            'opening_story' => '',
            'ending_story' => '',
        ),
    ),

    'RAIDCAGEDBIRD' => Array(
        'name' => 'RAID：暴走笼中鸟',
        'description' => '异常RAID模式：搜打撤取得个人积分，或与队友收集数据并重新封印暴走笼中鸟。',
        'credits_cost' => 1,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 20,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'RAIDCAGEDBIRD',
                'ruleset_name' => 'RAID：暴走笼中鸟',
                'raid_mode' => true,
            ),
        ),
        // 规则逻辑由同名覆写模块承载。 / Ruleset behavior is provided by the matching override module.
        'override_modules' => Array('raid_caged_bird'),
        'title_system' => 2,
        'club_skills' => 2,
        'avatar_config' => Array(
            'use_ruleset_avatars' => true,
            // 不带前导“./”，避免大立绘路径替换把目录点号误写为“a./”。
            // Omit leading "./" so large-avatar substitution cannot rewrite the directory dot.
            'avatar_path' => 'gamedata/ruleset/RAIDCAGEDBIRD/img/',
            'male_avatars' => 0,
            'female_avatars' => 0,
            'npc_avatars' => Array(
                201 => 'n_201.png',
                202 => 'n_202.png',
				203 => 'n_203.png',
            ),
            'special_avatars' => Array(
                'raid_boss' => 'n_201.png',
				'raid_boss_charging' => 'n_203.png',
                'raid_container' => 'n_202.png',
				'raid_container_open' => 'n_202.png',
            ),
        ),
		// 非空标记会启用本地story_1.php；正文仍由分镜页输出。
		// Non-empty markers enable local story_1.php; storyboard pages still provide the copy.
		'story_config' => Array(
			'opening_story' => 'RAIDCAGEDBIRD',
			'ending_story' => 'RAIDCAGEDBIRD',
		),
    ),

    'ACBRA_2009' => Array(
        'name' => 'ACBRA 2009版',
        'description' => '重现2009年经典ACBRA版本的游戏体验，包含原版的平衡性设置、道具系统和NPC配置。',
        'credits_cost' => 1,  // 开启房间需要的切糕数量
        'admin_free' => true,   // 管理员是否免费
        'initial_setup' => Array(
            // 初始装备和属性设置
            'hp_limit' => 500,
            'sp_limit' => 800,
            'base_exp' => 9,
            'money' => 20,
            'initial_items' => Array(
                // 格式：位置 => Array('name' => 道具名, 'type' => 类型, 'effect' => 效果, 'durability' => 耐久, 'special' => 特殊属性)
            ),
            'initial_equipment' => Array(
                // 初始装备设置
            ),
            'clbpara_flags' => Array(
                'ruleset_version' => 'ACBRA_2009',
                'ruleset_name' => 'ACBRA 2009版',
            ),
        ),
        'override_modules' => Array(
            // 需要覆盖的游戏模块文件
            // 'module_name' => 'path_to_override_file'
        ),
        'title_system' => 0,    // 0=全部禁用, 1=只禁用头衔奖励, 2=均不禁用
        'club_skills' => 0,     // 0=全部禁用, 1=使用RuleSet下的社团技能文件, 2=不禁用
        'avatar_config' => Array(
            'use_ruleset_avatars' => true,  // 是否使用RuleSet专用头像
            'avatar_path' => './gamedata/ruleset/ACBRA_2009/img/',  // 头像文件路径
            'male_avatars' => 43,    // 男性头像数量 (m_0.gif 到 m_42.gif)
            'female_avatars' => 43,  // 女性头像数量 (f_0.gif 到 f_42.gif)
            'npc_avatars' => Array(  // NPC头像映射
                1 => 'n_1.gif',     // 董事长/红暮
                11 => 'n_11.gif',   // 真职人
                12 => 'n_12.gif',   // 其他NPC
                13 => 'n_13.gif',
                14 => 'n_14.gif',
                15 => 'n_15.gif',
                16 => 'n_16.gif',
                17 => 'n_17.gif',
                18 => 'n_18.gif',
                81 => 'n_81.gif',   // 特殊NPC
                82 => 'n_82.gif',
                83 => 'n_83.gif',
                84 => 'n_84.gif',
                85 => 'n_85.gif',
                86 => 'n_86.gif',
                87 => 'n_87.gif',
                90 => 'n_90.gif',   // 各路党派
            ),
            'special_avatars' => Array(  // 特殊头像
                'boss' => 'boss.gif',
                'army' => 'army1.gif',
                'question' => 'question.gif',
            ),
        ),
        'story_config' => Array(
            'opening_story' => '欢迎来到2009年的ACBRA世界！这里保留了最初的游戏体验...',
            'ending_story' => '游戏结束！感谢体验2009年版本的经典玩法。',
        ),
    ),
    
    'ACDTS_2011' => Array(
        'name' => 'ACDTS 2011版',
        'description' => '体验2011年ACDTS版本的独特魅力，包含当时的特色系统和平衡调整。',
        'credits_cost' => 1,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 9,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'ACDTS_2011',
                'ruleset_name' => 'ACDTS 2011版',
            ),
        ),
        'override_modules' => Array(),
        'title_system' => 0,
        'club_skills' => 0,
        'avatar_config' => Array(
            'use_ruleset_avatars' => true,
            'avatar_path' => './gamedata/ruleset/ACDTS_2011/img/',
            'male_avatars' => 21,    // 男性头像数量 (m_0.gif 到 m_20.gif)
            'female_avatars' => 21,  // 女性头像数量 (f_0.gif 到 f_20.gif)
            'npc_avatars' => Array(
                1 => 'n_1.gif',     // 董事长
                2 => 'n_2.gif',     // 全息幻象
                3 => 'n_3.gif',     // 各路党派
                4 => 'n_4.gif',     // 非作战人员
                5 => 'n_5.gif',     // 代码聚合体
                6 => 'n_6.gif',     // 黑幕
                11 => 'n_11.gif',   // 真职人
                12 => 'n_12.gif',
                13 => 'n_13.gif',
                14 => 'n_14.gif',
                21 => 'n_21.gif',   // 特殊NPC
                22 => 'n_22.gif',
                23 => 'n_23.gif',
                24 => 'n_24.gif',
                31 => 'n_31.gif',
                32 => 'n_32.gif',
                33 => 'n_33.gif',
                41 => 'n_41.gif',
                42 => 'n_42.gif',
                43 => 'n_43.gif',
                51 => 'n_51.gif',
                91 => 'n_91.gif',
                92 => 'n_92.gif',
                93 => 'n_93.gif',
                94 => 'n_94.gif',
            ),
            'special_avatars' => Array(
                'star' => 'STAR.gif',
                'question' => 'question.gif',
            ),
        ),
        'story_config' => Array(
            'opening_story' => '时光倒流至2011年，重新体验ACDTS的经典时光...',
            'ending_story' => '2011年的冒险结束了，希望你享受了这段怀旧之旅。',
        ),
    ),
    
    'ACDTS_298SP4' => Array(
        'name' => 'ACDTS 298SP4版',
        'description' => '最后的经典版本298SP4，包含了丰富的内容和完善的系统。',
        'credits_cost' => 5,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 9,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'ACDTS_298SP4',
                'ruleset_name' => 'ACDTS 298SP4版',
            ),
        ),
        'override_modules' => Array(),
        'title_system' => 0,
        'club_skills' => 0,
        'avatar_config' => Array(
            'use_ruleset_avatars' => true,
            'avatar_path' => './gamedata/ruleset/ACDTS_298SP4/img/',
            'male_avatars' => 22,    // 男性头像数量 (m_0.gif 到 m_21.gif)
            'female_avatars' => 21,  // 女性头像数量 (f_0.gif 到 f_20.gif)
            'npc_avatars' => Array(
                1 => 'n_1.gif',     // 董事长
                2 => 'n_2.gif',     // 全息幻象
                3 => 'n_3.gif',     // 各路党派
                4 => 'n_4.gif',     // 非作战人员
                5 => 'n_5.gif',     // 代码聚合体
                6 => 'n_6.gif',     // 黑幕
                7 => 'n_7.gif',     // 首席执行官
                9 => 'n_9.gif',     // 活动盔甲
                11 => 'n_11.gif',   // 真职人
                12 => 'n_12.gif',
                13 => 'n_13.gif',
                14 => 'n_14.gif',
                21 => 'n_21.gif',   // 特殊NPC
                22 => 'n_22.gif',
                23 => 'n_23.gif',
                24 => 'n_24.gif',
                31 => 'n_31.gif',
                32 => 'n_32.gif',
                33 => 'n_33.gif',
                41 => 'n_41.gif',
                42 => 'n_42.gif',
                43 => 'n_43.gif',
                51 => 'n_51.gif',
                52 => 'n_52.gif',
                61 => 'n_61.gif',
                62 => 'n_62.gif',
                63 => 'n_63.gif',
                64 => 'n_64.gif',
                65 => 'n_65.gif',
                66 => 'n_66.gif',
                81 => 'n_81.gif',
                82 => 'n_82.gif',
                83 => 'n_83.gif',
                91 => 'n_91.gif',
                92 => 'n_92.gif',
                93 => 'n_93.gif',
                94 => 'n_94.gif',
                95 => 'n_95.gif',
                96 => 'n_96.gif',
                98 => 'n_98.gif',
            ),
            'special_avatars' => Array(
                'pb' => 'PB.gif',
                'p' => 'p.gif',
                'p2' => 'p2.gif',
                'question' => 'question.gif',
            ),
        ),
        'story_config' => Array(
            'opening_story' => '欢迎来到298SP4版本！这是经典时代的最后辉煌...',
            'ending_story' => '298SP4的传奇落下帷幕，感谢你的参与！',
        ),
    ),

    'ACDTS_298SP4_AR' => Array(
        'name' => 'ACDTS298 ALL RANDOM',
        'description' => '基于ACDTS_298SP4的全随机版本：全地图物品随机刷新，全NPC随机刷新，物品属性完全随机化。体验前所未有的混沌游戏！',
        'credits_cost' => 5,
        'admin_free' => true,
        'initial_setup' => Array(
            'hp_limit' => 400,
            'sp_limit' => 400,
            'base_exp' => 9,
            'money' => 20,
            'initial_items' => Array(),
            'initial_equipment' => Array(),
            'clbpara_flags' => Array(
                'ruleset_version' => 'ACDTS_298SP4_AR',
                'ruleset_name' => 'ACDTS298 ALL RANDOM',
                'all_random_mode' => true,  // 标记为全随机模式
            ),
        ),
        'override_modules' => Array(
            // 使用函数覆盖而不是文件覆盖
        ),
        'title_system' => 0,
        'club_skills' => 0,
        'avatar_config' => Array(
            'use_ruleset_avatars' => true,
            'avatar_path' => './gamedata/ruleset/ACDTS_298SP4_AR/img/',
            'male_avatars' => 22,    // 男性头像数量 (m_0.gif 到 m_21.gif)
            'female_avatars' => 21,  // 女性头像数量 (f_0.gif 到 f_20.gif)
            'npc_avatars' => Array(
                1 => 'n_1.gif',     // 董事长
                2 => 'n_2.gif',     // 全息幻象
                3 => 'n_3.gif',     // 各路党派
                4 => 'n_4.gif',     // 非作战人员
                5 => 'n_5.gif',     // 代码聚合体
                6 => 'n_6.gif',     // 黑幕
                7 => 'n_7.gif',     // 首席执行官
                9 => 'n_9.gif',     // 活动盔甲
                11 => 'n_11.gif',   // 真职人
                12 => 'n_12.gif',
                13 => 'n_13.gif',
                14 => 'n_14.gif',
                21 => 'n_21.gif',   // 特殊NPC
                22 => 'n_22.gif',
                23 => 'n_23.gif',
                24 => 'n_24.gif',
                31 => 'n_31.gif',
                32 => 'n_32.gif',
                33 => 'n_33.gif',
                41 => 'n_41.gif',
                42 => 'n_42.gif',
                43 => 'n_43.gif',
                51 => 'n_51.gif',
                52 => 'n_52.gif',
                61 => 'n_61.gif',
                62 => 'n_62.gif',
                63 => 'n_63.gif',
                64 => 'n_64.gif',
                65 => 'n_65.gif',
                66 => 'n_66.gif',
                81 => 'n_81.gif',
                82 => 'n_82.gif',
                83 => 'n_83.gif',
                91 => 'n_91.gif',
                92 => 'n_92.gif',
                93 => 'n_93.gif',
                94 => 'n_94.gif',
                95 => 'n_95.gif',
                96 => 'n_96.gif',
                98 => 'n_98.gif',
            ),
            'special_avatars' => Array(
                'pb' => 'PB.gif',
                'p' => 'p.gif',
                'p2' => 'p2.gif',
                'question' => 'question.gif',
            ),
        ),
        'story_config' => Array(
            'opening_story' => '欢迎来到ACDTS298 ALL RANDOM！在这个混沌的世界里，一切都是随机的——物品、NPC、属性，没有什么是确定的！准备好迎接前所未有的挑战吧！',
            'ending_story' => '在这个完全随机的世界中，你竟然能够生存到最后！你已经征服了混沌本身！',
        ),
    ),
);



// 获取RuleSet配置的函数
function get_ruleset_config($ruleset_id = null) {
    // 直接在函数内部定义配置，避免全局变量作用域问题
    $local_ruleset_enabled = true;

    // 尝试使用全局配置，如果不存在则使用本地配置
    global $ruleset_config;
    if (isset($ruleset_config) && is_array($ruleset_config)) {
        $local_ruleset_config = $ruleset_config;
    } else {
        // 如果全局变量不存在，使用本地配置作为fallback
        $local_ruleset_config = Array(
            'ACBRA_2009' => Array(
                'name' => 'ACBRA 2009版',
                'description' => '重现2009年经典ACBRA版本的游戏体验，包含原版的平衡性设置、道具系统和NPC配置。',
                'credits_cost' => 100,
                'admin_free' => true,
                'avatar_config' => Array(
                    'use_ruleset_avatars' => true,
                    'avatar_path' => './gamedata/ruleset/ACBRA_2009/img/',
                    'male_avatars' => 43,
                    'female_avatars' => 43,
                    'npc_avatars' => Array(
                        1 => 'n_1.gif', 11 => 'n_11.gif', 12 => 'n_12.gif', 13 => 'n_13.gif',
                        14 => 'n_14.gif', 15 => 'n_15.gif', 16 => 'n_16.gif', 17 => 'n_17.gif',
                        18 => 'n_18.gif', 81 => 'n_81.gif', 82 => 'n_82.gif', 83 => 'n_83.gif',
                        84 => 'n_84.gif', 85 => 'n_85.gif', 86 => 'n_86.gif', 87 => 'n_87.gif',
                        90 => 'n_90.gif',
                    ),
                ),
            ),
            'ACDTS_2011' => Array(
                'name' => 'ACDTS 2011版',
                'description' => '体验2011年ACDTS版本的独特魅力，包含当时的特色系统和平衡调整。',
                'credits_cost' => 150,
                'admin_free' => true,
                'avatar_config' => Array(
                    'use_ruleset_avatars' => true,
                    'avatar_path' => './gamedata/ruleset/ACDTS_2011/img/',
                    'male_avatars' => 21,
                    'female_avatars' => 21,
                    'npc_avatars' => Array(
                        1 => 'n_1.gif', 2 => 'n_2.gif', 3 => 'n_3.gif', 4 => 'n_4.gif',
                        5 => 'n_5.gif', 6 => 'n_6.gif', 11 => 'n_11.gif', 12 => 'n_12.gif',
                        13 => 'n_13.gif', 14 => 'n_14.gif', 21 => 'n_21.gif', 22 => 'n_22.gif',
                        23 => 'n_23.gif', 24 => 'n_24.gif', 31 => 'n_31.gif', 32 => 'n_32.gif',
                        33 => 'n_33.gif', 41 => 'n_41.gif', 42 => 'n_42.gif', 43 => 'n_43.gif',
                        51 => 'n_51.gif', 91 => 'n_91.gif', 92 => 'n_92.gif', 93 => 'n_93.gif',
                        94 => 'n_94.gif',
                    ),
                ),
            ),
            'ACDTS_298SP4' => Array(
                'name' => 'ACDTS 298SP4版',
                'description' => '最后的经典版本298SP4，包含了丰富的内容和完善的系统。',
                'credits_cost' => 200,
                'admin_free' => true,
                'avatar_config' => Array(
                    'use_ruleset_avatars' => true,
                    'avatar_path' => './gamedata/ruleset/ACDTS_298SP4/img/',
                    'male_avatars' => 22,
                    'female_avatars' => 21,
                    'npc_avatars' => Array(
                        1 => 'n_1.gif', 2 => 'n_2.gif', 3 => 'n_3.gif', 4 => 'n_4.gif',
                        5 => 'n_5.gif', 6 => 'n_6.gif', 7 => 'n_7.gif', 9 => 'n_9.gif',
                        11 => 'n_11.gif', 12 => 'n_12.gif', 13 => 'n_13.gif', 14 => 'n_14.gif',
                        21 => 'n_21.gif', 22 => 'n_22.gif', 23 => 'n_23.gif', 24 => 'n_24.gif',
                        31 => 'n_31.gif', 32 => 'n_32.gif', 33 => 'n_33.gif', 41 => 'n_41.gif',
                        42 => 'n_42.gif', 43 => 'n_43.gif', 51 => 'n_51.gif', 52 => 'n_52.gif',
                        61 => 'n_61.gif', 62 => 'n_62.gif', 63 => 'n_63.gif', 64 => 'n_64.gif',
                        65 => 'n_65.gif', 66 => 'n_66.gif', 81 => 'n_81.gif', 82 => 'n_82.gif',
                        83 => 'n_83.gif', 91 => 'n_91.gif', 92 => 'n_92.gif', 93 => 'n_93.gif',
                        94 => 'n_94.gif', 95 => 'n_95.gif', 96 => 'n_96.gif', 98 => 'n_98.gif',
                    ),
                ),
            ),
            'ACDTS_298SP4_AR' => Array(
                'name' => 'ACDTS298 ALL RANDOM',
                'description' => '基于ACDTS_298SP4的全随机版本：全地图物品随机刷新，全NPC随机刷新，物品属性完全随机化。',
                'credits_cost' => 300,
                'admin_free' => true,
                'avatar_config' => Array(
                    'use_ruleset_avatars' => true,
                    'avatar_path' => './gamedata/ruleset/ACDTS_298SP4_AR/img/',
                    'male_avatars' => 22,
                    'female_avatars' => 21,
                    'npc_avatars' => Array(
                        1 => 'n_1.gif', 2 => 'n_2.gif', 3 => 'n_3.gif', 4 => 'n_4.gif',
                        5 => 'n_5.gif', 6 => 'n_6.gif', 7 => 'n_7.gif', 9 => 'n_9.gif',
                        11 => 'n_11.gif', 12 => 'n_12.gif', 13 => 'n_13.gif', 14 => 'n_14.gif',
                        21 => 'n_21.gif', 22 => 'n_22.gif', 23 => 'n_23.gif', 24 => 'n_24.gif',
                        31 => 'n_31.gif', 32 => 'n_32.gif', 33 => 'n_33.gif', 41 => 'n_41.gif',
                        42 => 'n_42.gif', 43 => 'n_43.gif', 51 => 'n_51.gif', 52 => 'n_52.gif',
                        61 => 'n_61.gif', 62 => 'n_62.gif', 63 => 'n_63.gif', 64 => 'n_64.gif',
                        65 => 'n_65.gif', 66 => 'n_66.gif', 81 => 'n_81.gif', 82 => 'n_82.gif',
                        83 => 'n_83.gif', 91 => 'n_91.gif', 92 => 'n_92.gif', 93 => 'n_93.gif',
                        94 => 'n_94.gif', 95 => 'n_95.gif', 96 => 'n_96.gif', 98 => 'n_98.gif',
                    ),
                ),
            ),
        );
    }

    if (!$local_ruleset_enabled) {
        return false;
    }

    if ($ruleset_id === null) {
        return $local_ruleset_config;
    }

    return isset($local_ruleset_config[$ruleset_id]) ? $local_ruleset_config[$ruleset_id] : false;
}

// 检查用户是否可以创建指定RuleSet房间
function can_create_ruleset_room($ruleset_id, $user_data) {
    // 使用get_ruleset_config函数获取配置，确保一致性
    $local_ruleset_enabled = true;
    $local_ruleset_config = get_ruleset_config();

    // 调试信息：记录函数内部状态
    $debug_info = array(
        'function_called' => 'can_create_ruleset_room',
        'ruleset_id' => $ruleset_id,
        'user_data_groupid' => isset($user_data['groupid']) ? $user_data['groupid'] : 'undefined',
        'user_data_credits2' => isset($user_data['credits2']) ? $user_data['credits2'] : 'undefined',
        'local_ruleset_enabled' => $local_ruleset_enabled,
        'local_config_exists' => isset($local_ruleset_config[$ruleset_id]) ? 'yes' : 'no',
        'fix_method' => 'using_local_config'
    );

    if (!$local_ruleset_enabled || !isset($local_ruleset_config[$ruleset_id])) {
        $debug_info['early_return'] = 'ruleset_disabled_or_config_missing';
        $debug_info['enabled_check'] = $local_ruleset_enabled ? 'pass' : 'fail';
        $debug_info['config_exists_check'] = isset($local_ruleset_config[$ruleset_id]) ? 'pass' : 'fail';

        // 写入调试文件
        file_put_contents(GAME_ROOT.'./doc/etc/can_create_debug_'.date('Y-m-d_H-i-s').'.txt',
            "can_create_ruleset_room调试信息:\n" . print_r($debug_info, true));

        return false;
    }

    $config = $local_ruleset_config[$ruleset_id];
    $debug_info['config_admin_free'] = $config['admin_free'];
    $debug_info['config_credits_cost'] = $config['credits_cost'];

    // 管理员免费 (修改权限要求从>=4改为>=2，允许所有管理员免费创建)
    if ($config['admin_free'] && $user_data['groupid'] >= 2) {
        $debug_info['result'] = 'admin_pass';
        $debug_info['admin_free_check'] = $config['admin_free'] ? 'pass' : 'fail';
        $debug_info['groupid_check'] = ($user_data['groupid'] >= 2) ? 'pass' : 'fail';

        // 写入调试文件
        file_put_contents(GAME_ROOT.'./doc/etc/can_create_debug_'.date('Y-m-d_H-i-s').'.txt',
            "can_create_ruleset_room调试信息:\n" . print_r($debug_info, true));

        return true;
    }

    // 检查切糕数量
    if ($user_data['credits2'] >= $config['credits_cost']) {
        $debug_info['result'] = 'credits_pass';
        $debug_info['credits_check'] = ($user_data['credits2'] >= $config['credits_cost']) ? 'pass' : 'fail';

        // 写入调试文件
        file_put_contents(GAME_ROOT.'./doc/etc/can_create_debug_'.date('Y-m-d_H-i-s').'.txt',
            "can_create_ruleset_room调试信息:\n" . print_r($debug_info, true));

        return true;
    }

    $debug_info['result'] = 'all_checks_failed';
    $debug_info['admin_free_check'] = $config['admin_free'] ? 'pass' : 'fail';
    $debug_info['groupid_check'] = ($user_data['groupid'] >= 2) ? 'pass' : 'fail';
    $debug_info['credits_check'] = ($user_data['credits2'] >= $config['credits_cost']) ? 'pass' : 'fail';

    // 写入调试文件
    file_put_contents(GAME_ROOT.'./doc/etc/can_create_debug_'.date('Y-m-d_H-i-s').'.txt',
        "can_create_ruleset_room调试信息:\n" . print_r($debug_info, true));

    return false;
}

// 获取RuleSet资源文件路径
function get_ruleset_resource_path($ruleset_id, $resource_type) {
    if (empty($ruleset_id)) {
        return false;
    }
    
    $base_path = GAME_ROOT . './gamedata/ruleset/' . $ruleset_id . '/';
    
    switch ($resource_type) {
        case 'cache':
            return $base_path . 'cache/';
        case 'img':
            return $base_path . 'img/';
        case 'include':
            return $base_path . 'include/';
        default:
            return $base_path;
    }
}

// 检查RuleSet资源文件是否存在
function ruleset_resource_exists($ruleset_id, $filename, $resource_type = 'cache') {
    $path = get_ruleset_resource_path($ruleset_id, $resource_type);
    if (!$path) return false;

    return file_exists($path . $filename);
}

// 获取RuleSet头像路径
function get_ruleset_avatar_path($ruleset_id, $avatar_type, $avatar_id = null) {
    // 直接获取配置，不依赖全局变量
    $config = get_ruleset_config($ruleset_id);

    if (!$config || !isset($config['avatar_config']) || !$config['avatar_config']['use_ruleset_avatars']) {
        return false;
    }

    $avatar_config = $config['avatar_config'];
    $base_path = $avatar_config['avatar_path'];

    switch ($avatar_type) {
        case 'male':
            if ($avatar_id !== null && $avatar_id >= 0 && $avatar_id < $avatar_config['male_avatars']) {
                return $base_path . "m_{$avatar_id}.gif";
            }
            break;

        case 'female':
            if ($avatar_id !== null && $avatar_id >= 0 && $avatar_id < $avatar_config['female_avatars']) {
                return $base_path . "f_{$avatar_id}.gif";
            }
            break;

        case 'npc':
            if ($avatar_id !== null && isset($avatar_config['npc_avatars'][$avatar_id])) {
                return $base_path . $avatar_config['npc_avatars'][$avatar_id];
            }
            break;

        case 'special':
            if ($avatar_id !== null && isset($avatar_config['special_avatars'][$avatar_id])) {
                return $base_path . $avatar_config['special_avatars'][$avatar_id];
            }
            break;
    }

    return false;
}

// 检查RuleSet是否使用自定义头像
function ruleset_uses_custom_avatars($ruleset_id) {
    $config = get_ruleset_config($ruleset_id);
    return $config && isset($config['avatar_config']) && $config['avatar_config']['use_ruleset_avatars'];
}

// 获取RuleSet头像数量限制
function get_ruleset_avatar_limits($ruleset_id) {
    $config = get_ruleset_config($ruleset_id);

    if (!$config || !isset($config['avatar_config'])) {
        return false;
    }

    return Array(
        'male' => $config['avatar_config']['male_avatars'],
        'female' => $config['avatar_config']['female_avatars'],
    );
}

?>
