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

// 公共送行不占普通 QUEST 名额。
if(function_exists('ending_routes_enabled') && ending_routes_enabled()) {
$questcfg['QD1']=array('public_sendo'=>1,'title'=>'南飞（Blasty）','desc'=>'加拿大鹅群正在迁徙，偷猎者的枪口已经瞄准了天空。保护它们平安通过。','gentle'=>'随着最后一名猎人倒下，完整的鹅群发出高亢的鸣叫，向南方的天际飞去。','rough'=>'首领倒在血泊中，但混乱交火里，鹅群已经折了大半。');
$questcfg['QD2']=array('public_sendo'=>1,'title'=>'守护之牙（Dante）','desc'=>'暴徒正成批涌向大排档。与獒犬少年并肩守到最后一刻。','gentle'=>'倒计时归零，最后一名暴徒溃逃。他收起臂盾，低沉点头：『有你在，门没有被跨过。』','rough'=>'你杀穿了所有波次，可硝烟散尽时，大排档的招牌也碎了半边。');
$questcfg['QD3']=array('public_sendo'=>1,'title'=>'不杀之武（Akane）','desc'=>'柔术是不杀之术。用捕绳让暴走的恶霸停下，而不是杀死他。','gentle'=>'捕绳绞紧关节，恶霸脱力倒地，眼中的狂暴逐渐退去。无须见血，胜负已定。','rough'=>'你没能收住力道。武力赢了，不杀的誓言碎了。');
$questcfg['QD4']=array('public_sendo'=>1,'title'=>'雪原目击（Holly）','desc'=>'戴上侦探眼镜，在风雪里揪出藏起来的枪口。','gentle'=>'光谱滤镜锁定了伪装网下的身影！精准反制，猎手彻底失去先机。','rough'=>'你让雪兔少女暴露在开阔地引蛇出洞。目标达成了，她还在发抖。');
$questcfg['QD5']=array('public_sendo'=>1,'title'=>'九尾的灯（Sandy）','desc'=>'提着引路灯穿过雾影封锁的山道，灯火一灭，迷雾就会吞掉一切。','gentle'=>'终点处灯油恰好燃尽。一路行来，烛火未灭，迷雾自散。','rough'=>'灯碎了。你凭蛮力在雾里砍出一条血路，两手沾满残渣。');
$questcfg['QD6']=array('public_sendo'=>1,'title'=>'吱吱电台（Albert）','desc'=>'带回备用电池，并为电波那头的听众留下一件真正的信物。','gentle'=>'仓鼠少年把电池装进广播机，再把你的装备挂上信号塔：『吱！通了！所有人都能听到希望了！』','rough'=>'你丢下三枚冷冰冰的电池。电台启动了，播的只有盲音。');
$questcfg['QD7']=array('public_sendo'=>1,'title'=>'鹿跃的终局（Kiko）','desc'=>'警报已经拉响，迷糊的狍子少女还在禁区里打转。带她离开。','gentle'=>'在警报化为红光的最后一秒，你拉着她冲进安全区。善德与恶德的虚影在大门后浮现，深深鞠躬。','rough'=>'禁区轰然封锁。她凭极强的运气毫发无伤地弹了出来，可告别的仪式已被硝烟冲散。');
}
