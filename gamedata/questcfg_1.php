<?php
if (!defined('IN_GAME')) {
    exit('Access Denied');
}

// QUEST系统配置 / QUEST system config
// 注意：此处名称为占位符，可在不改逻辑的情况下替换 / Placeholder names, safe to rename later

$questcfg_global = array(
    // 同时进行任务数上限 / Max active quests
    'max_active' => 1,
    // 探索时触发任务的基础概率（百分比）/ Base assign chance per search (percent)
    'assign_obbs' => 15,
    // 移动/探索累计到此阈值后才尝试弹出QUEST委托 / Move/search steps before trying a QUEST offer
    'offer_threshold' => 3,
    // 任务触发冷却（秒）/ Assign cooldown in seconds
    'assign_cooldown' => 120,
    // 拒绝QUEST后跳过的移动/探索次数 / Move/search steps skipped after rejecting a QUEST
    'reject_cooldown_steps' => 5,
);

$questcfg = array(
    // 橙色任务：强大 / Orange quests: power
    'Q1' => array(
        'title' => '橙色任务：猎杀乱入者',
        'tier' => 'orange',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 15,
            'min_lvl' => 1,
        ),
        'items' => array(
            'start' => array('q1_summon'),
            'reward' => array('q1_reward'),
        ),
        'reward' => array('money' => 200, 'exp' => 80),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest1_intruder'),
        ),
        'steps' => array(
            1 => array('desc' => '使用召唤道具引来目标'),
            2 => array('desc' => '击杀目标并完成任务'),
        ),
    ),
    'Q2' => array(
        'title' => '橙色任务：护送乱入者',
        'tier' => 'orange',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 12,
            'min_lvl' => 3,
        ),
        'items' => array(
            'start' => array('q2_summon'),
            'guard' => array('q2_guard'),
            'reward' => array('q2_reward'),
        ),
        'reward' => array('money' => 180, 'exp' => 70),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest2_protect'),
        ),
        // 达到指定禁区数即成功 / Success after reaching this ban count
        'ban_success' => 1,
        'steps' => array(
            1 => array('desc' => '召唤并保护目标'),
            2 => array('desc' => '目标存活到禁区推进后'),
        ),
    ),
    'Q3' => array(
        'title' => '橙色任务：数值考验',
        'tier' => 'orange',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 18,
            'min_lvl' => 1,
        ),
        'items' => array(
            'start' => array('q3_check'),
            'reward' => array('q3_reward'),
        ),
        'reward' => array('money' => 150, 'exp' => 60),
        'requirements' => array(
            // 可替换为别的字段 / Replace with other stats if needed
            'min_att' => 300,
        ),
        'steps' => array(
            1 => array('desc' => '使用道具检查数值'),
        ),
    ),

    // 蓝色任务：温柔 / Blue quests: gentle
    'Q4' => array(
        'title' => '蓝色任务：昔日重现',
        'tier' => 'blue',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 10,
            'min_lvl' => 1,
        ),
        'items' => array(
            'start' => array('q4_photo'),
            'flower' => array('q4_flower'),
            'reward' => array('q4_reward'),
        ),
        'reward' => array('money' => 160, 'exp' => 70),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest4_phantom'),
        ),
        // 指定地图使用 / Use in specific map (0 means any)
        'target_pls' => 0,
        'steps' => array(
            1 => array('desc' => '前往指定地点使用照片'),
            2 => array('desc' => '以花束/对话安抚幻影'),
        ),
    ),
    'Q5' => array(
        'title' => '蓝色任务：救赎之手',
        'tier' => 'blue',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 10,
            'min_lvl' => 5,
        ),
        'items' => array(
            'start' => array('q5_summon'),
            'purify' => array('q5_purifier'),
            'reward' => array('q5_reward'),
        ),
        'reward' => array('money' => 220, 'exp' => 90),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest5_subject'),
        ),
        // 触发净化的血量阈值（百分比）/ HP threshold percent to purify
        'hp_threshold' => 20,
        'steps' => array(
            1 => array('desc' => '召唤实验体并削弱'),
            2 => array('desc' => '使用净化药剂完成救赎'),
        ),
    ),

    // 绿色任务：可爱 / Green quests: cute
    'Q6' => array(
        'title' => '绿色任务：躲猫猫',
        'tier' => 'green',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 20,
            'min_lvl' => 1,
        ),
        'items' => array(
            'start' => array('q6_glasses'),
            'candy' => array('q6_candy'),
            'reward' => array('q6_reward'),
        ),
        'reward' => array('money' => 120, 'exp' => 50),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest6_hide'),
        ),
        // 需要收集的数量 / Candy count needed
        'candy_need' => 3,
        'steps' => array(
            1 => array('desc' => '用侦探眼镜定位捣蛋鬼'),
            2 => array('desc' => '收集糖果并交付'),
        ),
    ),
    'Q7' => array(
        'title' => '绿色任务：偶像握手会',
        'tier' => 'green',
        'repeatable' => 1,
        'assign' => array(
            'obbs' => 12,
            'min_ss' => 50,
        ),
        'items' => array(
            'start' => array('q7_cheerstick'),
            'reward' => array('q7_reward'),
        ),
        'reward' => array('money' => 200, 'exp' => 80),
        'npc' => array(
            'summon' => array('type' => 93, 'sub' => 'quest7_idol'),
        ),
        // 需要坚持回合 / Required turns
        'turn_need' => 3,
        // 需要的应援值 / Required cheer points
        'cheer_need' => 600,
        'steps' => array(
            1 => array('desc' => '召唤偶像并完成应援'),
        ),
    ),
);
