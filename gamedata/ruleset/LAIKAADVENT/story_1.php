<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

// 莱卡的进击：模式剧情 / Laika Advent: mode story
global $ruleset_stories, $ruleset_story_pages;

$ruleset_stories['LAIKAADVENT'] = Array(
    'opening' => Array(
        'title' => '莱卡的进击',
        'content' => '
            <div class="story-content">
                <h3>群星开始计价</h3>
                <p>一个无法作为敌人战斗的存在已经进入本局规则。</p>
                <p>前进会被征税，祝福必有反面，剧情路线则会被不断加速的齿轮追赶。</p>
                <p class="story-note">莱卡不是参战NPC。你只能在她规定的代价之中继续前进。</p>
            </div>
        ',
        'buttons' => Array(
            Array('text' => '接受公理', 'action' => 'close'),
        ),
    ),
    'ending' => Array(
        'title' => '群星停止计价',
        'content' => '
            <div class="story-content">
                <h3>本轮记录已经封存</h3>
                <p>莱卡没有庆祝，也没有惋惜。她只是确认了最后一笔代价。</p>
            </div>
        ',
        'buttons' => Array(
            Array('text' => '返回大厅', 'action' => 'redirect', 'url' => 'index.php'),
        ),
    ),
);

$ruleset_story_pages['LAIKAADVENT'] = Array(
    'opening' => Array(
        '你抬头时，天空与平常没有任何区别。<br><br>只有游戏规则的最深处多出了一条从未登记过的公理：<span class="yellow">前进必须支付代价。</span>',
        '莱卡不会被搜索，不会出现在战斗列表里，也不会被任何武器命中。<br><br>她是收费站、祝福的反面，以及剧情开始后不断加速的齿轮。',
        '<span class="red b">■ LAIKA ADVENT ■</span><br><br>群星已经开始记录。请决定你愿意为了前进失去什么。',
    ),
    'death' => Array(
        '你的行动记录在这里终止。<br><br>莱卡从未亲手攻击你；她只是让你此前选择的代价继续运转，直到你再也无法支付。',
        '<span class="grey">“本次前进未能成立。记录归档。”</span>',
    ),
    'end1' => Array(
        '最后一个生命信号也从会场里消失了。<br><br>无人能够支付通向终点的全部代价，群星于是将这场游戏记作零。',
        '<span class="grey">“不存在胜者，也就不存在尚未结清的前进。”</span>',
    ),
    'end2_winner' => Array(
        '你站到了最后。身上的账簿、祝福与齿轮一并停止，仿佛那套公理从未存在。<br><br>但你清楚记得自己为了走到这里交出了什么。',
        '<span class="evergreen b">■ LAST SURVIVOR - PRICE ACCEPTED ■</span><br><br><span class="grey">“生存也是一种前进。你的代价已经足额。”</span>',
    ),
    'end2_other' => Array(
        '最后的幸存者已经产生。<br><br>莱卡封存了所有人的账簿；其中也包括那些没有走到终点、却同样支付过代价的人。',
    ),
    'end3_winner' => Array(
        '游戏解除钥匙转动时，嵌入规则的齿轮试图完成最后一次咬合。<br><br>你抢在牺牲闭环前解除了锁定。世界承认了这条越界路径。',
        '<span class="evergreen b">■ LOCK RELEASE - AXIOM OUTRUN ■</span><br><br><span class="grey">“你完成了前进。代价不会退还。”</span>',
    ),
    'end3_other' => Array(
        '某位挑战者在齿轮合拢前解除了锁定。<br><br>整支队伍与会场一同被送离，但只有真正承担路线的人知道那阵齿轮声离自己有多近。',
    ),
    'end5_winner' => Array(
        '核爆把收费站、祝福和齿轮连同战场一起抹成白光。<br><br>这是最粗暴的答案，却同样符合公理：你支付了整个世界。',
        '<span class="evergreen b">■ NUCLEAR END - WORLD AS PAYMENT ■</span>',
    ),
    'end5_other' => Array(
        '白光覆盖会场时，所有尚未结清的星辰账目同时失去了债务人。<br><br>莱卡没有追索，因为已经没有可供前进的世界。',
    ),
    'end7_winner' => Array(
        '三种概念在你手中合拢，『G.A.M.E.O.V.E.R』开始解离整个幻境。<br><br>齿轮仍在加速，但它所嵌入的叙事框架已经先一步崩解。',
        '<span class="evergreen b">■ DISSOCIATION - AXIOM DISMANTLED ■</span><br><br><span class="grey">“你拆除了体系，而不是拒绝代价。结果有效。”</span>',
    ),
    'end7_other' => Array(
        '世界被解离的光拆开，宇宙公理之齿轮也失去了能够咬合的规则。<br><br>你随着其他意识一同离开，只在最后听见一声几乎没有感情的“哇呼～”。',
    ),
);

?>
