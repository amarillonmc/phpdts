<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理测试物品
 * 这些物品主要用于测试游戏功能
 *
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_test($itmn, &$data) {
    global $log, $db, $tablepre, $now, $pid, $event_bgm, $cmd, $mode;
    extract($data, EXTR_REFS);

    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};

    if ($itm == 'NPC战斗测试仪') {
        include_once GAME_ROOT.'./include/game/revcombat.func.php';
        $pa = fetch_playerdata_by_pid(1);
        $pd = fetch_playerdata_by_pid(2);
        \revcombat\rev_combat_prepare($pa, $pd, 1);
    } elseif ($itm == 'QUEST调试终端') {
        include_once GAME_ROOT.'./include/game/quest.func.php';
        if (!quest_debug_offer($data)) {
            $log .= '<span class="yellow">当前没有可用的QUEST。</span><br>';
            $mode = 'command';
        }
    } elseif ($itm == '显现战斗测试仪') {
        //Mod the above item, YOU'll enter fight with a player entry matching the item's $itme value.
        include_once GAME_ROOT.'./include/game/revcombat.func.php';
        $pa = fetch_playerdata_by_pid($pid);
        $pd = fetch_playerdata_by_pid($itme);
        \revcombat\rev_combat_prepare($pa, $pd, 1);
    } elseif ($itm == '战斗显现测试仪') {
        //Mod the above item, A player entry matching item's $itme value will enter a fight with YOU.
        include_once GAME_ROOT.'./include/game/revcombat.func.php';
        $pa = fetch_playerdata_by_pid($itme);
        $pd = fetch_playerdata_by_pid($pid);
        \revcombat\rev_combat_prepare($pa, $pd, 1);
    } elseif ($itm == '对话测试器') {
        // 简单对话测试
        // Regarding making dialogues with choices:
        // The user's choice will be stored in $clbpara['choice_index'] and $clbpara['choice_text'].
        // {"dialogue_id":"testingDialog","choice_index":"1","choice_text":"选项B"}
        $clbpara['dialogue'] = 'testingDialog';
        $clbpara['noskip_dialogue'] = 0;
    } elseif ($itm == '事件BGM替换器') {
        // 这是一个触发事件BGM的案例，只要输入$clbpara['event_bgmbook'] = Array('事件曲集名'); 即可将当前曲集替换为特殊事件BGM
        // 特殊事件曲集'event_bgmbook'的优先级高于地图曲集'pls_bgmbook'，前者存在时后者不会生效
        //global $clbpara,$event_bgm;
        //include_once config('audio',$gamecfg);
        $log.="【DEBUG】你目前的播放列表被替换为了{$event_bgm['test'][0]}！<br>特殊的事件曲集不会被其他曲集覆盖，除非你使用下面的道具。<br>";
        $clbpara['event_bgmbook'] = $event_bgm['test'];
    } elseif ($itm == '事件BGM还原器') {
        // 这是一个取消事件BGM的案例，只要unset($clbpara['event_bgmbook']);就可以将当前曲集替换为地图曲集或默认曲集；
        // 如果你想播放另一个事件曲集，也可以$clbpara['event_bgmbook'] = Array('另一个事件曲集名');
        //global $clbpara;
        $log.="【DEBUG】你目前的播放列表还原为了默认播放列表！<br>";
        unset($clbpara['event_bgmbook']);
    } elseif ($itm == '成就重置装置') {
        //使用会重置对应属性编号的成就进度
        include_once GAME_ROOT.'./include/game/achievement.func.php';
        reset_achievement_rev($itmsk, $name);
    } elseif ($itm == '测试用元素口袋') {
        //global $elements_info;
        $log.="【DEBUG】你不知道从哪里摸出来一大堆元素！<br>";
        foreach($elements_info as $e_key => $e_info) {
            //global ${'element'.$e_key};
            ${'element'.$e_key} += 100000;
            $log.="获得了100000份".$elements_info[$e_key]."！<br>";
        }
        //初始化元素合成缓存文件
        include_once GAME_ROOT.'./include/game/elementmix.func.php';
        emix_spawn_info();
    } elseif ($itm == '测试用元素大师社团卡') {
        //-----------------------//
        //这是一张测试用卡 冴冴可以挑一些用得上的放在使用社团卡后执行的事件里
        //global $elements_info,$sparkle;
        //未选择社团情况下才可以用社团卡
        if($club) {
            $log.="你已经是有身份的人了！不能再使用社团卡。<br>";
        } else {
            //反正是测试用的 发段怪log
            $log.="你拿起<span class='yellow'>$itm</span>左右端详着……<br>
            你将目光扫过卡片上若隐若现的纹理，突然发现这张卡内似乎别有洞天。<br>
            透过纹理，你看到一群奇装异服的小人们，围坐在一处颇具古典风格的露天广场上。<br>
            广场中央有一人，正抬手指天，慷慨陈词。<br>
            你听不到它们在说什么，但演讲者那极富感染力的动作勾起了你的好奇心，<br>
            你不由自主得沿着它指的方向望去——<br>
            <br>
            洁白如镜的天穹上，倒映出的是你的脸。<br>
            <br>
            你赶忙移开视线，但小人们已经发现了你。<br>
            从广场再到远处的平原上，数以十计、百计、千计、万计，
            一眼望不到头的小人们从你视野的尽头涌出，挤向你所在的方向。<br>
            你一时慌乱，下意识地便将手里的卡片丢了出去。<br>
            眼前亦真亦幻的怪异景象登时消失不见了。<br>
            <br>
            你低下头，发现脚下的卡片已经被烧掉了一半，<br>
            在被火焰烧灼得卷曲起的边缘处，漏出了某样东西的一角。<br>
            你捡起卡片，甩了甩，便看到一个足足有卡片五倍甚至四倍大的东西从里面掉了出来！<br>";
            $log.="<br>获得了<span class='sparkle'>{$sparkle}元素口袋{$sparkle}</span>！<br>";
            $log.="……这到底是怎么一回事呢？<br><br>";
            //社团变更
            changeclub(20, $data);
            //获取初始元素与第一条配方
            $dice = rand(0, 5);
            //global ${'element'.$dice};
            ${'element'.$dice} += 200+$dice;
            //初始化元素合成缓存文件
            include_once GAME_ROOT.'./include/game/elementmix.func.php';
            emix_spawn_info();
            //销毁道具
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
    } elseif ($itm == '测试用枫火歌者社团卡') {
        //-----------------------//
        //未选择社团情况下才可以用社团卡
        if($club) {
            $log.="你已经是有身份的人了！不能再使用社团卡。<br>";
        } else {
            $log.="<br>加入了了<span class='sparkle'>{$sparkle}枫火歌者{$sparkle}</span>！<br>";
            //社团变更
            changeclub(22, $data);
            //销毁道具
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
        //-----------------------//
    } elseif ($itm == '提示纸条A') {
        $log .= '你读着纸条上的内容：<br>"执行官其实都是幻影，那个红暮的身上应该有召唤幻影的玩意。"<br>"用那个东西然后打倒幻影的话能用游戏解除钥匙出去吧。"<br>';
    } elseif ($itm == '提示纸条B') {
        $log .= '你读着纸条上的内容：<br>"我设下的灵装被残忍地清除了啊……"<br>"不过资料没全部清除掉。<br>用那个碎片加上传奇的画笔和天然属性……"<br>"应该能重新组合出那个灵装。"<br>';
    } elseif ($itm == '提示纸条C') {
        $log .= '你读着纸条上的内容：<br>"小心！那个叫红暮的家伙很强！"<br>"不过她太依赖自己的枪了，有什么东西能阻挡那伤害的话……"<br>';
    } elseif ($itm == '提示纸条D') {
        $log .= '你读着纸条上的内容：<br>"我不知道另外那个孩子的底细。如果我是你的话，不会随便乱惹她。"<br>"但是她貌似手上拿着符文册之类的东西。"<br>"也许可以利用射程优势？！"<br>"你知道的，法师的射程都不咋样……"';
    } elseif ($itm == '提示纸条E') {
        $log .= '你读着纸条上的内容：<br>"生存并不能靠他人来喂给你知识，"<br>"有一套和元素有关的符卡的公式是没有出现在帮助里面的，用逻辑推理好好推理出正确的公式吧。"<br>"金木水火土在这里都能找到哦～"<br>';
    } elseif ($itm == '提示纸条F') {
        $log .= '你读着纸条上的内容：<br>"喂你真的是全部买下来了么……"<br>"这样的提示纸条不止这六种，其他的纸条估计被那两位撒出去了吧。"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '提示纸条G') {
        $log .= '你读着纸条上的内容：<br>"上天保佑，"<br>"请不要在让我在模拟战中被击坠了！"<br>"空羽 上。"<br>';
    } elseif ($itm == '提示纸条H') {
        $log .= '你读着纸条上的内容：<br>"在研究施设里面出了大事的SCP竟然又输出了新的样本！"<br>"按照董事长的意见就把这些家伙当作人体试验吧！"<br>署名看不清楚……<br>';
    } elseif ($itm == '提示纸条I') {
        $log .= '你读着纸条上的内容：<br>"嗯……"<br>"制作神卡所用的各种认证都可以在商店里面买到。"<br>"其实卡片真的有那么强大的力量么？"<br>';
    } elseif ($itm == '提示纸条J') {
        $log .= '你读着纸条上的内容：<br>"知道么？"<br>"果酱面包果然还是甜的好，哪怕是甜的生姜也能配制出如地雷般爆炸似的美味。"<br>"祝你好运。"<br>';
    } elseif ($itm == '提示纸条K') {
        $log .= '你读着纸条上的内容：<br>"水符？"<br>"你当然需要水，然后水看起来是什么颜色的？"<br>"找一个颜色类似的东西合成就有了吧。"<br>';
    } elseif ($itm == '提示纸条L') {
        $log .= '你读着纸条上的内容：<br>"木符？"<br>"你当然需要树叶，然后说到树叶那是什么颜色？"<br>"找一个颜色类似的东西合成就有了吧。"<br>';
    } elseif ($itm == '提示纸条M') {
        $log .= '你读着纸条上的内容：<br>"火符？"<br>"你当然需要找把火，然后说到火那是什么颜色？"<br>"找一个颜色类似的东西合成就有了吧。"<br>';
    } elseif ($itm == '提示纸条N') {
        $log .= '你读着纸条上的内容：<br>"土符？"<br>"说到土那就是石头吧，然后说到石头那是什么颜色？"<br>"找一个颜色类似的东西合成就有了吧。"<br>';
    } elseif ($itm == '提示纸条O') {
        $log .= '你读着纸条上的内容：<br>"纸条O？"<br>"你确定你看到的是O而不是0？"<br>"大概需要看看眼睛？"<br>';
    } elseif ($itm == '提示纸条P') {
        $log .= '你读着纸条上的内容：<br>"金符？这个的确很绕人……"<br>"说到金那就是炼金，然后这是21世纪了，炼制一个金色方块需要什么？"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '提示纸条Q') {
        $log .= '你读着纸条上的内容：<br>"据说在另外的空间里面；"<br>"一个吸血鬼因为无聊就在她所居住的地方洒满了大雾，"<br>"真任性。"<br>';
    } elseif ($itm == '提示纸条R') {
        $log .= '你读着纸条上的内容：<br>"知道么，"<br>"东方幻想乡这作游戏里面EXTRA的最终攻击"<br>"被老外们称作『幻月的Rape Time』，当然对象是你。"<br>';
    } elseif ($itm == '提示纸条S') {
        $log .= '你读着纸条上的内容：<br>"土水符？"<br>"哈哈哈那肯定是需要土和水啦，可能还要额外的素材吧。"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '提示纸条T') {
        $log .= '你读着纸条上的内容：<br>"我一直对虚拟现实中的某些迹象很在意……"<br>"这种未名的威压感是怎么回事？"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '提示纸条U') {
        $log .= '你读着纸条上的内容：<br>"纸条啥的……"<br>"希望这张纸条不会成为你的遗书。"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '人品探测器') {
        //global $rp;
        $log .= '你读着纸条上的内容：<br>"你的RP值为'.$rp.'。"<br>"总之祝你好运。"<br>';
    } elseif ($itm == '仪水镜') {
        //global $rp;
        $log .= '水面上映出了你自己的脸，你仔细端详着……<br>';
        if ($rp < 40) {
            $log .= '你的脸看起来十分白皙。<br>';
        } elseif ($rp < 200) {
            $log .= '你的脸看起来略微有点黑。<br>';
        } elseif ($rp < 550) {
            $log .= '你的脸上貌似笼罩着一层黑雾。<br>';
        } elseif ($rp < 1200) {
            $log .= '你的脸已经和黑炭差不多了，赶快去洗洗！<br>';
        } elseif ($rp < 5499) {
            $log .= '你印堂漆黑，看起来最近要有血光之灾！<br>';
        } elseif ($rp > 5500) {
            $log .= '水镜中已经黑的如墨一般了。<br>希望你的H173还在……<br>';
        } else {
            $log .= '你的脸从水镜中消失了。<br>';
        }
    }elseif ($itm == '风祭河水'){
        //global $rp, $wp, $wk, $wg, $wc, $wd, $wf;
        $slv_dice = rand ( 1, 20 );
            if ($slv_dice < 8) {
            $log .= "你一口干掉了<span class=\"yellow\">$itm</span>，不过好像什么都没有发生！";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } elseif ($slv_dice < 16) {
            $rp = $rp - 10*$slv_dice;
            $log .= "你感觉身体稍微轻了一点点。<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } elseif ($slv_dice < 20) {
            $rp = 0 ;
            $log .= "你头晕脑胀地躺到了地上，<br>感觉整个人都被救济了。<br>你努力着站了起来。<br>";
            $wp = $wk = $wg = $wc = $wd = $wf = 100;
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } else {
            $log .= '你头晕脑胀地躺到了地上，<br>感觉整个人都被救济了。<br>';
            include_once GAME_ROOT . './include/state.func.php';
            $log .= '然后你失去了意识。<br>';
            //$bid = 0;
            death ( 'salv', '', 0, $itm );
        }
    }elseif(strpos($itm,'RP回复设备')!==false){
        //global $rp;
        $rp = 0;
        $log .= "你使用了<span class=\"yellow\">$itm</span>。你的RP归零了。<br>";
    } elseif ($itm == 'itmpara调试开关') {
        // 切换 itmpara 调试模式
        if(isset($clbpara['SetItmparaDebug']) && $clbpara['SetItmparaDebug'] === true) {
            $clbpara['SetItmparaDebug'] = false;
            $log .= "你关闭了 itmpara 调试模式。<br>现在物品的 tooltip 中不会显示调试信息。<br>";
        } else {
            $clbpara['SetItmparaDebug'] = true;
            $log .= "你开启了 itmpara 调试模式。<br>现在物品的 tooltip 中会显示详细的调试信息。<br>";
        }
    } elseif ($itm == '对话选择测试器') {
        // 带选择的对话测试
        $clbpara['dialogue'] = 'choiceTestingDialog';
        $clbpara['noskip_dialogue'] = 1; // 设置为不可跳过的对话
    }elseif ($itm == '调制解调器'){
        if(!empty($gamevars['apis']))
        {
            $log .= '你将这件长得很像猫的东西放在了地上……目送它慢悠悠地爬走了。<br>';
            if($gamevars['api'] < $gamevars['apis'])
            {
                $gamevars['api']++;
                save_gameinfo();
                $log .= '<span class="yellow">好像有什么东西恢复了！</span><br>';
            }
            else
            {
                $log .= '<span class="yellow">但是什么也没有发生！</span><br>';
            }
            $itms--;
        }
        else
        {
            $log .= '这件长得很像猫的东西该怎么用呢？<br>';
        }
    }

}
