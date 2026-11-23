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
        'itm' => '「X」牌金属活性诱导膏',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
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
    'q2_token' => array(
        'itm' => '护送证明',
        'itmk' => 'YQ',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array('IsQuestItem' => 1, 'QuestID' => 'Q2', 'QuestAction' => 'reward'),
    ),
    'q2_reward' => array(
        'itm' => '「X」牌超韧记忆金属内衬',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
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
        'itm' => '「X」印恶魔角铆钉组',
        'itmk' => 'Y',
        'itme' => 220,
        'itms' => 2,
        'itmsk' => '',
        'itmpara' => array(),
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
        'itm' => '心尾烙印·契约回火钳',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
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
        'itm' => '「X」牌超韧记忆金属内衬',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
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
        'itm' => '「X」式重装手足拘束具',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
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
        'itm' => '漫展淘来的「非法电弧谐振器」',
        'itmk' => 'Y',
        'itme' => 1,
        'itms' => 1,
        'itmsk' => '',
        'itmpara' => array(),
    ),
);

if(function_exists('ending_routes_enabled') && ending_routes_enabled()) {
	$questiteminfo['sendo_ticket']=array('itm'=>'【动物团的七封请柬】','itmk'=>'ticket','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>array('QuestID'=>'QD'));
	$questiteminfo['sendo_pass']=array('itm'=>'【湾城通票】','itmk'=>'ticket','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>array('QuestID'=>'QD'));
}
