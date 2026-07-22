<?php
if (!defined('IN_GAME')) {
    exit('Access Denied');
}

// QUEST items definitions (default ruleset)
// 任务物品定义（默认规则）

$questiteminfo = array(
    // Q1
    'q1_summon' => array(
        'itm' => '猎杀信标',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q1', 'QuestAction' => 'summon'),
    ),
    'q1_reward' => array(
        'itm' => '猎杀徽记',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q1', 'QuestAction' => 'reward'),
    ),

    // Q2
    'q2_summon' => array(
        'itm' => '护送信标',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q2', 'QuestAction' => 'summon'),
    ),
    'q2_guard' => array(
        'itm' => '护卫终端',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q2', 'QuestAction' => 'guard'),
    ),
    'q2_reward' => array(
        'itm' => '护送证明',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q2', 'QuestAction' => 'reward'),
    ),

    // Q3
    'q3_check' => array(
        'itm' => '数值检验器',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q3', 'QuestAction' => 'check'),
    ),
    'q3_reward' => array(
        'itm' => '检验合格章',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q3', 'QuestAction' => 'reward'),
    ),

    // Q4
    'q4_photo' => array(
        'itm' => '褪色的旧照片',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q4', 'QuestAction' => 'summon'),
    ),
    'q4_flower' => array(
        'itm' => '献上的花束',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q4', 'QuestAction' => 'comfort'),
    ),
    'q4_reward' => array(
        'itm' => '遗言碎片',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q4', 'QuestAction' => 'reward'),
    ),

    // Q5
    'q5_summon' => array(
        'itm' => '净化召唤器',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q5', 'QuestAction' => 'summon'),
    ),
    'q5_purifier' => array(
        'itm' => '净化药剂',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q5', 'QuestAction' => 'purify'),
    ),
    'q5_reward' => array(
        'itm' => '清醒的证明',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q5', 'QuestAction' => 'reward'),
    ),

    // Q6
    'q6_glasses' => array(
        'itm' => '侦探眼镜',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 3,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q6', 'QuestAction' => 'clue'),
    ),
    'q6_candy' => array(
        'itm' => '捣蛋鬼的糖果',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q6', 'QuestAction' => 'candy'),
    ),
    'q6_reward' => array(
        'itm' => '捉迷藏证明',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q6', 'QuestAction' => 'reward'),
    ),

    // Q7
    'q7_cheerstick' => array(
        'itm' => '应援棒',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q7', 'QuestAction' => 'summon'),
    ),
    'q7_reward' => array(
        'itm' => '握手会凭证',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q7', 'QuestAction' => 'reward'),
    ),
);
