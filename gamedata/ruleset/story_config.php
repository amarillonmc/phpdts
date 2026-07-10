<?php

if(!defined('IN_GAME')) {
    exit('Access Denied');
}

/*
 * RuleSet系统剧情配置文件
 * 用于配置不同版本的开场和结束剧情
 */

$ruleset_stories = Array(
    'ACBRA_2009' => Array(
        'opening' => Array(
            'title' => '时光重现：ACBRA 2009',
            'content' => '
                <div class="story-content">
                    <h3>欢迎来到2009年的ACBRA世界！</h3>
                    <p>时光倒流，回到了那个最初的年代...</p>
                    <p>在这里，你将体验到最原始的大逃杀玩法：</p>
                    <ul>
                        <li>经典的武器系统和道具配置</li>
                        <li>原版的NPC和地图设置</li>
                        <li>怀旧的界面风格和音效</li>
                        <li>2009年的平衡性调整</li>
                    </ul>
                    <p>准备好重温那份最初的感动了吗？</p>
                    <p class="story-note">注意：本房间使用ACBRA 2009版本的游戏规则和资源。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '开始游戏', 'action' => 'close'),
            )
        ),
        'ending' => Array(
            'title' => '时光重现结束',
            'content' => '
                <div class="story-content">
                    <h3>2009年的冒险结束了</h3>
                    <p>感谢你体验了这段怀旧的时光重现之旅。</p>
                    <p>希望你在这个经典版本中找到了当年的感动。</p>
                    <p>时光荏苒，但经典永恒。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '返回大厅', 'action' => 'redirect', 'url' => 'index.php'),
            )
        )
    ),
    
    'ACDTS_2011' => Array(
        'opening' => Array(
            'title' => '时光重现：ACDTS 2011',
            'content' => '
                <div class="story-content">
                    <h3>穿越到2011年的ACDTS世界</h3>
                    <p>这里是ACDTS的黄金时代...</p>
                    <p>在这个版本中，你将体验到：</p>
                    <ul>
                        <li>2011年的独特系统设计</li>
                        <li>当时的特色道具和武器</li>
                        <li>经典的NPC配置</li>
                        <li>那个时代的游戏平衡</li>
                    </ul>
                    <p>让我们一起回到那个充满回忆的年代！</p>
                    <p class="story-note">注意：本房间使用ACDTS 2011版本的游戏规则和资源。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '进入游戏', 'action' => 'close'),
            )
        ),
        'ending' => Array(
            'title' => '2011年的回忆',
            'content' => '
                <div class="story-content">
                    <h3>ACDTS 2011的旅程结束</h3>
                    <p>你已经完成了这次时光重现的体验。</p>
                    <p>2011年的ACDTS承载着无数玩家的青春回忆。</p>
                    <p>希望这次旅程让你重新感受到了当年的快乐。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '返回现代', 'action' => 'redirect', 'url' => 'index.php'),
            )
        )
    ),
    
    'ACDTS_298SP4' => Array(
        'opening' => Array(
            'title' => '时光重现：ACDTS 298SP4',
            'content' => '
                <div class="story-content">
                    <h3>最后的经典：298SP4版本</h3>
                    <p>这是经典时代的最后辉煌...</p>
                    <p>298SP4版本包含了：</p>
                    <ul>
                        <li>最完善的经典系统</li>
                        <li>丰富的道具和装备</li>
                        <li>成熟的游戏机制</li>
                        <li>经典时代的巅峰体验</li>
                    </ul>
                    <p>这是告别过去，迎接未来的最后一站。</p>
                    <p class="story-note">注意：本房间使用ACDTS 298SP4版本的游戏规则和资源。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '最后一战', 'action' => 'close'),
            )
        ),
        'ending' => Array(
            'title' => '经典时代的终章',
            'content' => '
                <div class="story-content">
                    <h3>298SP4的传奇落下帷幕</h3>
                    <p>你见证了经典时代的最后辉煌。</p>
                    <p>298SP4代表着一个时代的结束，也是新时代的开始。</p>
                    <p>感谢你参与了这段珍贵的历史重现。</p>
                    <p>愿经典永远在我们心中闪耀。</p>
                </div>
            ',
            'buttons' => Array(
                Array('text' => '踏向未来', 'action' => 'redirect', 'url' => 'index.php'),
            )
        )
    )
);

// 按需加载RuleSet目录内的剧情文件，避免把模式专属文案继续堆进中央配置。
// Lazily load ruleset-local story files instead of growing the central story config.
function load_ruleset_local_story($ruleset_id) {
    global $ruleset_stories, $ruleset_story_pages;

    if (empty($ruleset_id) || !preg_match('/^[A-Za-z0-9_]+$/', $ruleset_id)) return;
    $story_file = GAME_ROOT.'./gamedata/ruleset/'.$ruleset_id.'/story_1.php';
    if (file_exists($story_file)) include_once $story_file;
}

// 获取指定RuleSet的剧情配置
function get_ruleset_story($ruleset_id, $story_type = 'opening') {
    global $ruleset_stories;

    if (!isset($ruleset_stories[$ruleset_id])) load_ruleset_local_story($ruleset_id);
    
    if (isset($ruleset_stories[$ruleset_id]) && isset($ruleset_stories[$ruleset_id][$story_type])) {
        return $ruleset_stories[$ruleset_id][$story_type];
    }
    
    return false;
}

// 检查RuleSet是否有自定义剧情
function has_ruleset_story($ruleset_id) {
    global $ruleset_stories;

    if (!isset($ruleset_stories[$ruleset_id])) load_ruleset_local_story($ruleset_id);
    
    return isset($ruleset_stories[$ruleset_id]);
}

// RuleSet分镜剧情配置，供 opening_story / ending_story 模板直接读取
// RuleSet storyboard pages used by the actual opening/ending templates
$ruleset_story_pages = Array(
    'ACBRA_2009' => Array(
        'opening' => Array(
            '<span class="evergreen b">“欢迎来到最初的会场。”</span><br><br>没有复杂的系统提示，也没有后来那些层层叠叠的机制。你站在一片朴素得近乎锋利的赛场边缘，听见老旧广播发出电流声。',
            '规则很简单：寻找、逃跑、战斗，然后活下去。<br><br>这就是2009年的ACBRA，所有后来者的原点。那些粗糙的数字和直白的道具，反而让每一次相遇都显得清晰而残酷。',
            '<span class="yellow b">■ TIME REPLAY - ACBRA 2009 ■</span><br><br>怀旧不是保护色。这里的经典会直接落在你的头上。'
        ),
        'death' => Array(
            '老旧的广播响起，你的编号被从名单里划掉。<br><br>在这个最初的规则里，失败没有太多解释。你已经理解了它的锋利。',
        ),
        'end1' => Array(
            '没有人走出这场旧日重现。<br><br>2009年的会场沉默下来，只剩下广播里断断续续的雪花声。',
        ),
        'end2_winner' => Array(
            '你成为了最后的幸存者。没有盛大的演出，没有复杂的奖赏，只有一行朴素的结果记录在终端上。<br><br><span class="evergreen b">■ GAME CLEAR - ACBRA 2009 ■</span>',
        ),
        'end2_other' => Array(
            '最后的幸存者已经诞生。<br><br>你没能走到最后，但你见证了原始规则下最直接的胜利。',
        ),
        'end3_winner' => Array(
            '你解除了锁定。旧时代的系统发出迟缓的确认音，仿佛它也没想到有人会这样结束游戏。<br><br><span class="evergreen b">■ LOCK RELEASE - ACBRA 2009 ■</span>',
        ),
        'end3_other' => Array(
            '某位挑战者解除了锁定，旧日赛场被迫关停。<br><br>这一次，你搭上了回程的末班车。',
        ),
        'end5_winner' => Array(
            '核爆的白光吞没了朴素的旧赛场。<br><br>就算是2009年的规则，也挡不住这种过于现代的离谱答案。<br><br><span class="evergreen b">■ NUCLEAR END - ACBRA 2009 ■</span>',
        ),
        'end5_other' => Array(
            '远处升起的白光抹去了所有怀旧滤镜。<br><br>你在冲击波中意识到：经典规则并不意味着温柔。',
        ),
        'end7_winner' => Array(
            '你触碰到了旧时代规则背后的裂隙。<br><br>那些本不该出现在这里的数据被逐一解离，最初的赛场露出比记忆更深的底色。<br><br><span class="evergreen b">■ DISSOCIATION - ACBRA 2009 ■</span>',
        ),
        'end7_other' => Array(
            '世界被解离的光吞没。<br><br>你没能看清发生了什么，只知道这场怀旧重现已经偏离了原本的轨道。',
        ),
    ),
    'ACDTS_2011' => Array(
        'opening' => Array(
            '<span class="evergreen b">“这里是2011年的ACDTS。”</span><br><br>会场的光影比原点时代更复杂，系统提示也变得更加野心勃勃。你能感觉到，许多后来被重写的设计正在这里第一次成形。',
            'NPC、道具、机制和玩家习惯交错在一起，像一台还在高速试运转的机器。<br><br>它不总是稳定，但每一次震动都带着那个年代特有的热量。',
            '<span class="yellow b">■ TIME REPLAY - ACDTS 2011 ■</span><br><br>请进入战场。历史会亲自检验你的适应力。'
        ),
        'death' => Array('2011年的系统记录下了你的败北。<br><br>你听见远处仍有人在战斗，像一段还没有停止编译的旧代码。'),
        'end1' => Array('没有幸存者。<br><br>这台旧机器终于停了下来，只留下过热后的微弱余响。'),
        'end2_winner' => Array('你站到了最后。<br><br>2011年的会场向你亮出胜利标记，像在承认你已经读懂了它粗粝却鲜活的规则。<br><br><span class="evergreen b">■ GAME CLEAR - ACDTS 2011 ■</span>'),
        'end2_other' => Array('最后的胜者已经产生。<br><br>你没能成为那个人，但这段2011年的重现仍然把它的答案写在了你眼前。'),
        'end3_winner' => Array('锁定被你解除，旧系统的限制层层退去。<br><br>这不是单纯的逃脱，而是对2011年规则的一次漂亮越界。<br><br><span class="evergreen b">■ LOCK RELEASE - ACDTS 2011 ■</span>'),
        'end3_other' => Array('锁定被解除。<br><br>你随着其他意识一同离开，回头时只看见2011年的幻境正在缓慢淡出。'),
        'end5_winner' => Array('你按下了不属于这个年代的终止符。<br><br>核爆的光把2011年的试验场烧成一片空白。<br><br><span class="evergreen b">■ NUCLEAR END - ACDTS 2011 ■</span>'),
        'end5_other' => Array('光芒落下时，所有系统提示都失去了意义。<br><br>你在白噪声中被送离了2011年的会场。'),
        'end7_winner' => Array('你让幻境开始解离。<br><br>2011年那些尚未定型的设计像碎片般悬浮在空中，最终化为通向更深处的门。<br><br><span class="evergreen b">■ DISSOCIATION - ACDTS 2011 ■</span>'),
        'end7_other' => Array('幻境解离了。<br><br>你只看见无数旧版本的残影互相重叠，然后一并消失。'),
    ),
    'ACDTS_298SP4' => Array(
        'opening' => Array(
            '<span class="evergreen b">“欢迎来到经典时代的最后一站。”</span><br><br>298SP4的会场比你想象中更加完整。大量系统、道具和NPC像被精心压进同一个压缩包，等待这一轮重现解封。',
            '这里已经不再是单纯的怀旧样本，而是一个时代收束前的全量备份。<br><br>它成熟、拥挤、偶尔也危险得过分。',
            '<span class="yellow b">■ TIME REPLAY - ACDTS 298SP4 ■</span><br><br>经典时代的终章已经载入。请确认你的装备，然后活下去。'
        ),
        'death' => Array('你倒在了298SP4的战场上。<br><br>它没有因为自己是经典版本就对你手下留情。'),
        'end1' => Array('无人幸存。<br><br>经典时代的终章没有主角，只有一份安静归档的战斗记录。'),
        'end2_winner' => Array('你成为最后的幸存者。<br><br>298SP4把所有喧嚣压成一枚胜利标记，交到你的手里。<br><br><span class="evergreen b">■ GAME CLEAR - ACDTS 298SP4 ■</span>'),
        'end2_other' => Array('最后的幸存者已经离场。<br><br>你没能站上终点，但你确实走过了经典时代最后的战场。'),
        'end3_winner' => Array('你解除了锁定。<br><br>系统将这次越界写入旧版本的终章，像给经典时代补上一个新的尾注。<br><br><span class="evergreen b">■ LOCK RELEASE - ACDTS 298SP4 ■</span>'),
        'end3_other' => Array('锁定解除的光覆盖了会场。<br><br>你随着人群离开，身后是298SP4逐渐关闭的系统音。'),
        'end5_winner' => Array('你用核爆为经典时代写下过于响亮的句号。<br><br>那一刻，所有资源、地图和规则都被白光同时清空。<br><br><span class="evergreen b">■ NUCLEAR END - ACDTS 298SP4 ■</span>'),
        'end5_other' => Array('核爆落下，298SP4的世界瞬间失去轮廓。<br><br>你甚至来不及分辨最后看见的是道具特效还是天空。'),
        'end7_winner' => Array('幻境开始解离。<br><br>298SP4保存的旧时代数据被一层层展开，你从终章背后看见了更深的入口。<br><br><span class="evergreen b">■ DISSOCIATION - ACDTS 298SP4 ■</span>'),
        'end7_other' => Array('世界被解离的光拆开。<br><br>那些曾经完整的经典机制化为碎片，从你身边静静流走。'),
    ),
    'ACDTS_298SP4_AR' => Array(
        'opening' => Array(
            '<span class="yellow b">“警告：随机数权限被提升至最高。”</span><br><br>你刚踏入会场，就发现地图索引、NPC配置和物品表在眼前疯狂洗牌。',
            '系统仍然声称这里是298SP4，但它说这句话时显然底气不足。<br><br>落点会乱，敌人会乱，数值会乱，合成结果也会乱。唯一可靠的规则是：不要相信任何规则。',
            '<span class="red b">■ ACDTS298 ALL RANDOM ■</span><br><br>欢迎来到超混沌模式。祝你好运，因为除此之外系统也给不了你更多东西。'
        ),
        'death' => Array('你被混沌吞没了。<br><br>也许是敌人，也许是落点，也许是某个属性离谱的物品。全随机模式拒绝解释。'),
        'end1' => Array('随机数把所有人都卷了进去。<br><br>没有幸存者，只有一地无法复现的事故现场。'),
        'end2_winner' => Array('你竟然在全随机模式里活到了最后。<br><br>系统沉默了很久，最后只好承认：这也是一种实力。<br><br><span class="evergreen b">■ CHAOS CLEAR - LAST SURVIVOR ■</span>'),
        'end2_other' => Array('某位挑战者从混沌里活了出来。<br><br>你不确定这是计划、技术，还是单纯离谱的好运。'),
        'end3_winner' => Array('你在随机数的洪流里找到了锁定解除的路径。<br><br>这听起来不可能，但记录已经写下了你的名字。<br><br><span class="evergreen b">■ CHAOS CLEAR - LOCK RELEASE ■</span>'),
        'end3_other' => Array('锁定被解除。<br><br>你随着混乱的数据流被送离，身后还在持续刷新一些完全没必要刷新的东西。'),
        'end5_winner' => Array('你启动了核爆。随机数很欣赏这种简单直接的终止方式。<br><br>于是整个世界都被同一个结果覆盖了。<br><br><span class="evergreen b">■ CHAOS CLEAR - NUCLEAR END ■</span>'),
        'end5_other' => Array('核爆落下。<br><br>在全随机模式里，这甚至显得有点秩序井然。'),
        'end7_winner' => Array('你解离了这个随机到近乎失控的幻境。<br><br>无数可能性在你面前坍缩，最后留下了一条能够回去的路。<br><br><span class="evergreen b">■ CHAOS CLEAR - DISSOCIATION ■</span>'),
        'end7_other' => Array('幻境开始解离。<br><br>你看见无数种没有发生的结局同时闪过，然后全部归零。'),
    ),
);

function get_ruleset_story_pages($ruleset_id, $story_type = 'opening', $hp = 0, $state = 0, $winmode = 0) {
    global $ruleset_story_pages;

    if (!isset($ruleset_story_pages[$ruleset_id])) load_ruleset_local_story($ruleset_id);

    if (!isset($ruleset_story_pages[$ruleset_id])) {
        return Array(
            'RuleSet剧情配置缺失。<br>Ruleset story is missing.',
        );
    }

    if ($story_type == 'opening') {
        return isset($ruleset_story_pages[$ruleset_id]['opening']) ? $ruleset_story_pages[$ruleset_id]['opening'] : Array(
            '本RuleSet没有配置开场剧情。<br>This ruleset has no opening story configured.',
        );
    }

    $ending_key = get_ruleset_ending_story_key($hp, $state, $winmode);
    if (isset($ruleset_story_pages[$ruleset_id][$ending_key])) {
        return $ruleset_story_pages[$ruleset_id][$ending_key];
    }
    if (isset($ruleset_story_pages[$ruleset_id]['end' . intval($winmode)])) {
        return $ruleset_story_pages[$ruleset_id]['end' . intval($winmode)];
    }
    return Array(
        '本RuleSet没有配置对应结局剧情。<br>This ruleset has no matching ending story configured.',
    );
}

function get_ruleset_ending_story_key($hp = 0, $state = 0, $winmode = 0) {
    if ($hp <= 0) {
        return 'death';
    }

    $winmode = intval($winmode);
    $state = intval($state);
    $winner_state = ($state == 5 || $state == 6);

    if (in_array($winmode, Array(2, 3, 5, 7))) {
        return 'end' . $winmode . ($winner_state ? '_winner' : '_other');
    }

    return 'end' . $winmode;
}

?>
