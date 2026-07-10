<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * 莱卡的进击：集中平衡配置
 * Laika Advent: centralized balance configuration
 *
 * 测试时优先调整本文件；核心事件逻辑位于 include/ruleset.func.php。
 * Tune this file first during playtests. Event logic lives in include/ruleset.func.php.
 */
$laika_mode_config = Array(
    'version' => 1,

    // 星辰之代价 / Astra Pretium
    'tax' => Array(
        // 普通NPC累计达到此数量时收税；小兵、种火和QUEST临时NPC不计数。
        // Ordinary NPC kills needed for one levy. Minions, fireseeds and QUEST NPCs are ignored.
        'ordinary_npc_threshold' => 5,
        'excluded_npc_types' => Array(90, 91, 92),
        'exclude_quest_npcs' => true,
        // 拒绝NPC税时撤销的击杀衍生状态；仅快照相关键，避免复制整个clbpara。
        // Kill-derived state reverted on NPC refusal; snapshot only relevant keys instead of duplicating all clbpara.
        'rollback_clbpara_keys' => Array('achvars', 'quest', 'skillpara'),
        'demand_weights' => Array(
            'stat' => 50,
            'item' => 25,
            'condition' => 25,
        ),
        'severity_rates' => Array(
            'ordinary' => 10,
            'important' => 15,
            'threshold' => 20,
        ),
        'stat_pool' => Array(
            'mhp' => Array('name' => '生命上限', 'floor' => 100),
            'msp' => Array('name' => '体力上限', 'floor' => 100),
            'att' => Array('name' => '基础攻击', 'floor' => 10),
            'def' => Array('name' => '基础防御', 'floor' => 10),
            'money' => Array('name' => '金钱', 'floor' => 0),
            'rp' => Array('name' => '报应', 'floor' => 0),
            'wp' => Array('name' => '殴系熟练度', 'floor' => 0),
            'wk' => Array('name' => '斩系熟练度', 'floor' => 0),
            'wg' => Array('name' => '射系熟练度', 'floor' => 0),
            'wc' => Array('name' => '投系熟练度', 'floor' => 0),
            'wd' => Array('name' => '爆系熟练度', 'floor' => 0),
            'wf' => Array('name' => '灵系熟练度', 'floor' => 0),
        ),
        // 首次越过每一档时立即收税；拒绝后退回该档数值。
        // Crossing each tier for the first time causes an immediate levy.
        'stat_thresholds' => Array(
            'lvl' => Array(15, 30, 50),
            'mhp' => Array(1000, 3000, 10000),
            'msp' => Array(1000, 3000, 10000),
            'att' => Array(500, 1500, 5000),
            'def' => Array(500, 1500, 5000),
            'money' => Array(2000, 10000, 50000),
            'mastery' => Array(500, 1500, 5000),
            'weapon_power' => Array(500, 1500, 5000),
        ),
        'stat_labels' => Array(
            'lvl' => '等级',
            'mhp' => '生命上限',
            'msp' => '体力上限',
            'att' => '基础攻击',
            'def' => '基础防御',
            'money' => '金钱',
            'mastery' => '最高武器熟练度',
            'weapon_power' => '武器效果值',
        ),
        // 不会被莱卡当作支付物夺走；拒税时，作为“相关进度”的物品仍会消失。
        // Never selected as payment. The triggering progress item is still erased on refusal.
        'protected_items' => Array(
            '移动PC', '挑战者之印', '挑战者之印Ⅱ', '黑色碎片',
            '【我想要领略真正的红杀之力】', '破灭之诗',
            '社员专用的ID卡', '冰炎钥匙·炎', '冰炎钥匙·冰', '游戏解除钥匙',
            '十字发卡', '黑色发卡', '武神之魂', '琉璃血',
            '歌词卡片【海洋】', '歌词卡片【星空】', '歌词卡片【大地】',
            '『C.H.A.O.S』', '『T.E.R.R.A』', '『A.Q.U.A』', '『V.E.N.T.U.S』', '『G.A.M.E.O.V.E.R』',
        ),
        // 获得这些道具时立即收税。
        // Acquiring any of these items causes an immediate levy.
        'important_items' => Array(
            '移动PC', '挑战者之印', '黑色碎片', '【我想要领略真正的红杀之力】',
            '破灭之诗', '社员专用的ID卡', '冰炎钥匙·炎', '冰炎钥匙·冰',
            '游戏解除钥匙', '十字发卡', '黑色发卡', '武神之魂', '琉璃血',
            '歌词卡片【海洋】', '歌词卡片【星空】', '歌词卡片【大地】',
            '『C.H.A.O.S』', '『T.E.R.R.A』', '『A.Q.U.A』', '『V.E.N.T.U.S』', '『G.A.M.E.O.V.E.R』',
        ),
        'conditions' => Array(
            'blood_tithe' => Array(
                'name' => '流血公理',
                'actions' => 12,
                'hp_loss_percent' => 4,
                'sp_loss_percent' => 0,
            ),
            'star_tithe' => Array(
                'name' => '失重公理',
                'actions' => 15,
                'hp_loss_percent' => 0,
                'sp_loss_percent' => 8,
            ),
            'double_tithe' => Array(
                'name' => '双星公理',
                'actions' => 10,
                'hp_loss_percent' => 3,
                'sp_loss_percent' => 5,
            ),
        ),
    ),

    // 哇呼~悖论祝福 / Wafuu~ Paradoxical Blessing
    'blessing' => Array(
        'first_offer_min_actions' => 10,
        'first_offer_max_actions' => 16,
        'offer_min_actions' => 15,
        'offer_max_actions' => 25,
        'duration_min_actions' => 12,
        'duration_max_actions' => 20,
        'offer_count' => 3,
        'pool' => Array(
            'astra_blade' => Array(
                'name' => '星刃祝福',
                'choice' => '让我的攻击抵达群星',
                'buff_desc' => '基础攻击提高100%',
                'debuff_desc' => '基础防御降低60%',
                'changes' => Array('att' => 100, 'def' => -60),
            ),
            'event_horizon' => Array(
                'name' => '视界祝福',
                'choice' => '让我成为不可撼动的壁垒',
                'buff_desc' => '基础防御提高120%',
                'debuff_desc' => '生命上限降低55%',
                'changes' => Array('def' => 120, 'mhp' => -55),
            ),
            'white_dwarf' => Array(
                'name' => '白矮星祝福',
                'choice' => '让生命像恒星一样炽盛',
                'buff_desc' => '生命上限提高150%',
                'debuff_desc' => '基础攻击降低65%',
                'changes' => Array('mhp' => 150, 'att' => -65),
            ),
            'blue_shift' => Array(
                'name' => '蓝移祝福',
                'choice' => '让我理解所有武器',
                'buff_desc' => '全部武器熟练度提高80%',
                'debuff_desc' => '体力上限降低70%',
                'changes' => Array('wp' => 80, 'wk' => 80, 'wg' => 80, 'wc' => 80, 'wd' => 80, 'wf' => 80, 'msp' => -70),
            ),
            'second_velocity' => Array(
                'name' => '第二宇宙速度祝福',
                'choice' => '让体力永不枯竭',
                'buff_desc' => '体力上限提高200%',
                'debuff_desc' => '生命上限降低60%',
                'changes' => Array('msp' => 200, 'mhp' => -60),
            ),
            'red_giant' => Array(
                'name' => '红巨星祝福',
                'choice' => '让攻防一同膨胀',
                'buff_desc' => '基础攻击与防御各提高70%',
                'debuff_desc' => '生命与体力上限各降低45%',
                'changes' => Array('att' => 70, 'def' => 70, 'mhp' => -45, 'msp' => -45),
            ),
        ),
    ),

    // 宇宙公理之齿轮 / Cog of Cosmic Axiom
    'cog' => Array(
        'warning_item' => '移动PC',
        'trigger_items' => Array(
            '挑战者之印' => Array('route' => 'executor', 'name' => '幻影执行官线', 'budget' => 90),
            '【我想要领略真正的红杀之力】' => Array('route' => 'redblue', 'name' => '真红蓝线', 'budget' => 120),
            '黑色碎片' => Array('route' => 'darkforce', 'name' => 'Dark Force线', 'budget' => 210),
            '破灭之诗' => Array('route' => 'dissociation', 'name' => '旧解离线', 'budget' => 160),
        ),
        // progress / budget 达到对应比例时进入新阶段。
        // A new stage starts when progress / budget reaches the configured percentage.
        'stage_percentages' => Array(1 => 1, 2 => 40, 3 => 70, 4 => 100),
        'effects' => Array(
            1 => Array('hp_loss_percent' => 0, 'sp_loss_percent' => 3, 'att_decay_percent' => 0, 'def_decay_percent' => 0, 'max_decay_percent' => 0),
            2 => Array('hp_loss_percent' => 0, 'sp_loss_percent' => 5, 'att_decay_percent' => 0, 'def_decay_percent' => 1, 'max_decay_percent' => 0),
            3 => Array('hp_loss_percent' => 3, 'sp_loss_percent' => 8, 'att_decay_percent' => 1, 'def_decay_percent' => 1, 'max_decay_percent' => 0),
            4 => Array('hp_loss_percent' => 10, 'sp_loss_percent' => 100, 'att_decay_percent' => 3, 'def_decay_percent' => 3, 'max_decay_percent' => 2),
        ),
        'stage_text' => Array(
            1 => '第一枚齿轮咬合。你的体力开始被世界抽走。',
            2 => '第二枚齿轮咬合。防御法则正在磨损。',
            3 => '第三枚齿轮咬合。生命、攻击与防御一同被牺牲。',
            4 => '宇宙公理完成闭环。继续行动将迅速烧尽你的存在。',
        ),
    ),
);

?>
