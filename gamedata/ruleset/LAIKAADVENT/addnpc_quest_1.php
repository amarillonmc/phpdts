<?php
if (!defined('IN_GAME')) {
    exit('Access Denied');
}

// QUEST NPC definitions (default ruleset)
// 任务NPC定义（默认规则）
// 仅追加sub，不覆盖原有类型定义 / Append subs only, keep base type settings

$quest_anpcinfo = array(
    93 => array(
        'sub' => array(
            // Q1: intruder hunter target
            'quest1_intruder' => array(
                'name' => '乱入的影子',
                'description' => '占位NPC：猎杀目标。/ Placeholder NPC: hunt target.',
                'icon' => 'quest_npc/quest1_intruder.jpg',
                'gd' => 'm',
                'wep' => '未知武装',
                'wepk' => 'WK',
                'wepe' => 180,
                'weps' => 80,
                'mhp' => 800,
                'msp' => 300,
                'att' => 220,
                'def' => 160,
                'lvl' => 18,
                'skill' => 120,
                'money' => 200,
                'pls' => 99,
            ),
            // Q2: protected NPC
            'quest2_protect' => array(
                'name' => '脆弱的来访者',
                'description' => '占位NPC：需要保护。/ Placeholder NPC: protect target.',
                'icon' => 'quest_npc/quest2_protect.jpg',
                'gd' => 'f',
                'wep' => '临时防身品',
                'wepk' => 'WP',
                'wepe' => 60,
                'weps' => 50,
                'mhp' => 500,
                'msp' => 200,
                'att' => 120,
                'def' => 100,
                'lvl' => 10,
                'skill' => 80,
                'money' => 50,
                'pls' => 99,
            ),
            // Q4: phantom NPC (non-hostile interaction)
            'quest4_phantom' => array(
                'name' => '逝去的幻影',
                'description' => '占位NPC：非敌对幻影。/ Placeholder NPC: friendly phantom.',
                'icon' => 'quest_npc/quest4_phantom.jpg',
                'gd' => 'f',
                'wep' => '无形回忆',
                'wepk' => 'WP',
                'wepe' => 1,
                'weps' => 1,
                'mhp' => 300,
                'msp' => 200,
                'att' => 1,
                'def' => 1,
                'lvl' => 1,
                'skill' => 1,
                'money' => 0,
                'pls' => 99,
            ),
            // Q5: experimental subject (purify)
            'quest5_subject' => array(
                'name' => '暴走的实验体',
                'description' => '占位NPC：可净化目标。/ Placeholder NPC: purifiable target.',
                'icon' => 'quest_npc/quest5_subject.jpg',
                'gd' => 'm',
                'wep' => '破坏冲动',
                'wepk' => 'WG',
                'wepe' => 140,
                'weps' => 70,
                'mhp' => 1200,
                'msp' => 400,
                'att' => 260,
                'def' => 180,
                'lvl' => 20,
                'skill' => 140,
                'money' => 150,
                'pls' => 99,
            ),
            // Q6: hide-and-seek
            'quest6_hide' => array(
                'name' => '捣蛋鬼',
                'description' => '占位NPC：找到即可获得奖励。/ Placeholder NPC: found -> reward.',
                'icon' => 'quest_npc/quest6_hide.jpg',
                'gd' => 'r',
                'wep' => '无害恶作剧',
                'wepk' => 'WP',
                'wepe' => 1,
                'weps' => 1,
                'mhp' => 200,
                'msp' => 200,
                'att' => 1,
                'def' => 1,
                'lvl' => 1,
                'skill' => 1,
                'money' => 0,
                'pls' => 99,
            ),
            // Q7: idol encounter
            'quest7_idol' => array(
                'name' => '流浪偶像',
                'description' => '占位NPC：应援互动。/ Placeholder NPC: cheer interaction.',
                'icon' => 'quest_npc/quest7_idol.jpg',
                'gd' => 'f',
                'wep' => '舞台光芒',
                'wepk' => 'WC',
                'wepe' => 80,
                'weps' => 60,
                'mhp' => 700,
                'msp' => 300,
                'att' => 160,
                'def' => 120,
                'lvl' => 16,
                'skill' => 120,
                'money' => 80,
                'pls' => 99,
            ),
        ),
    ),
);
