<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理NPC相关物品
 * 这些物品会影响NPC的生成、移动等
 *
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_npc($itmn, &$data) {
    global $log, $now, $db, $tablepre, $hack, $gamevars;
    extract($data, EXTR_REFS);

    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};

    if ($itm == '杏仁豆腐的ID卡') {
        include_once GAME_ROOT . './include/system.func.php';
        $duelstate = duel($now, $itm);
        if ($duelstate == 50) {
            $log .= "<span class=\"yellow\">你使用了{$itm}。</span><br><span class=\"evergreen\">“干得不错呢，看来咱应该专门为你清扫一下战场……”</span><br><span class=\"evergreen\">“所有的NPC都离开战场了。好好享受接下来的杀戮吧，祝你好运。”</span>——林无月<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } elseif ($duelstate == 51) {
            $log .= "你使用了<span class=\"yellow\">{$itm}</span>，不过什么反应也没有。<br><span class=\"evergreen\">“咱已经帮你准备好舞台了，请不要要求太多哦。”</span>——林无月<br>";
        } else {
            $log .= "你使用了<span class=\"yellow\">{$itm}</span>，不过什么反应也没有。<br><span class=\"evergreen\">“表演的时机还没到呢，请再忍耐一下吧。”</span>——林无月<br>";
        }
    } elseif ($itm == '挑战者之印') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '你已经呼唤了幻影执行官，现在寻找并击败他们，<br>并且搜寻他们的ID卡吧！<br>';
        addnpc(7, 0, 1);
        addnpc(7, 1, 1);
        addnpc(7, 2, 1);
        if ($clbpara['randver1'] < 64){
            $log .= '【DEBUG】你触发了测试内容！<br>新机制执行官已额外部署进战场！<br>现在寻找并击败他们，<br>并且搜寻他们的ID卡吧！<br>';
            addnpc(7, 3, 1);
            addnpc(7, 4, 1);
            addnpc(7, 5, 1);
        }
        addnews($now, 'secphase', $name, $nick);
        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
    } elseif ($itm == '挑战者之印Ⅱ') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '你已经呼唤了幻影执行官，现在寻找并击败他们，<br>并且搜寻他们的ID卡吧！<br>';
        addnpc(7, 3, 1);
        addnpc(7, 4, 1);
        addnpc(7, 5, 1);
        addnews($now, 'secphase', $name, $nick);
        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
    } elseif ($itm == '破灭之诗') {
        $rp = 0;
        $clbpara['dialogue'] = 'thiphase';
        $clbpara['console'] = 1;
        $clbpara['achvars']['thiphase'] += 1;
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '在你唱出那单一的旋律的霎那，<br>整个虚拟世界起了翻天覆地的变化……<br>';
        addnpc(4, 0, 1);
        include_once GAME_ROOT . './include/game/item2.func.php';
        $log .= '世界响应着这旋律，产生了异变……<br>';
        wthchange($itm, $itmsk, 1, $data);
        addnews($now, 'thiphase', $name, $nick);
        $hack = 1;
        $gamevars['apis'] = $gamevars['api'] = 3;
        $log .= '因为破灭之歌的作用，全部锁定被打破了！<br>';
        movehtm();
        addnews($now, 'hack2', $name, $nick);
        save_gameinfo();
        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
    } elseif ($itm == '黑色碎片') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '你已经呼唤了一个未知的存在，现在寻找并击败她，<br>并且搜寻她的游戏解除钥匙吧！<br>';
        addnews($now, 'dfphase', $name, $nick);
        addnpc(12, 0, 1);

        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
    } elseif ($itm == '✦钥匙碎片') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '嗯……？只有碎片也能用吗？<br>好像将一小部分NPC部署进了游戏内……<br>';
        //思念体 4*3
        addnpc(2, 0, 2);
        addnpc(2, 1, 2);
        addnpc(2, 2, 2);
        addnpc(2, 3, 2);
        addnpc(2, 4, 2);
        addnpc(2, 5, 2);
        addnpc(2, 6, 2);
        addnpc(2, 7, 2);
        addnews($now, 'key0', $name, $nick);
        $itms--;
        if ($itms <= 0) destory_single_item($data, $itmn, 1);
    } elseif ($itm == '✦NPC钥匙·一阶段') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '已解锁一阶段NPC！<br>似乎大量NPC已经部署至游戏内……<br>';
        //职人 1*6
        addnpc(11, 0, 1);
        addnpc(11, 1, 1);
        addnpc(11, 2, 1);
        addnpc(11, 3, 1);
        addnpc(11, 4, 1);
        addnpc(11, 5, 1);
        //妖精幻象 1*3
        addnpc(13, 0, 1);
        addnpc(13, 1, 1);
        addnpc(13, 2, 1);
        addnews($now, 'key1', $name, $nick);
        $itms--;
        if ($itms <= 0) {
            $log .= "<span class=\"red\">$itm</span>用光了。<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
    } elseif ($itm == '✦✦NPC钥匙·二阶段') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '已解锁二阶段NPC！<br>似乎凶恶NPC已经部署至游戏内……<br>';
        //杏仁豆腐 2*2
        addnpc(5, 0, 1);
        addnpc(5, 1, 1);
        addnpc(5, 0, 1);
        addnpc(5, 1, 1);
        //猴子 1*2
        addnpc(6, 0, 1);
        addnpc(6, 0, 1);
        //假蓝凝
        addnpc(9, 0, 1);
        addnews($now, 'key2', $name, $nick);
        $itms--;
        if ($itms <= 0) {
            $log .= "<span class=\"red\">$itm</span>用光了。<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
    } elseif ($itm == '✦种火钥匙') {
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '虽然不知道你究竟想干啥，<br>但总之你放出了更多的种火……<br>';
        //种火 5*10
        addnpc(92, 0, 10);
        addnpc(92, 1, 10);
        addnpc(92, 2, 10);
        addnpc(92, 3, 10);
        addnpc(92, 4, 10);
        addnews($now, 'key3', $name, $nick);
        $itms--;
        if ($itms <= 0) {
            $log .= "<span class=\"red\">$itm</span>用光了。<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
    } elseif ($itm == '✦【自律AI呼唤器】') {
        //Call in 30 type 93 NPCs, 6 each.
        //get player's 1st Yume value - different value results in different NPC.
        //There are 5 sets - K, C, G, P, D.
        include_once GAME_ROOT . './include/system.func.php';
        $log .= '你将这根权杖一般的钥匙狠狠插在了地面上，<br>很快，大批NPC就从空中降落到了战场上！<br>';
        if ($clbpara['randver1'] < 21) {
            // 1st set - WK High School Oni Girls
            addnpc(93, 0, 6);
            addnpc(93, 1, 6);
            addnpc(93, 2, 6);
            addnpc(93, 3, 6);
            addnpc(93, 4, 6);
        } elseif ($clbpara['randver1'] < 42) {
            // 2nd set - WC Idol Magical Girls
            addnpc(93, 5, 6);
            addnpc(93, 6, 6);
            addnpc(93, 7, 6);
            addnpc(93, 8, 6);
            addnpc(93, 9, 6);
        } elseif ($clbpara['randver1'] < 63) {
            // 3rd set - WG Mecha Girls
            addnpc(93, 10, 6);
            addnpc(93, 11, 6);
            addnpc(93, 12, 6);
            addnpc(93, 13, 6);
            addnpc(93, 14, 6);
        } elseif ($clbpara['randver1'] < 84) {
            // 4th set - WP Martial Arts Girls
            addnpc(93, 15, 6);
            addnpc(93, 16, 6);
            addnpc(93, 17, 6);
            addnpc(93, 18, 6);
            addnpc(93, 19, 6);
        } else {
            // 5th set - WD Explosive Girls
            addnpc(93, 20, 6);
            addnpc(93, 21, 6);
            addnpc(93, 22, 6);
            addnpc(93, 23, 6);
            addnpc(93, 24, 6);
        }
        addnews($now, 'key4', $name, $nick);
        if(ai93_enabled()) {
            $ai93 = &ai93_state();
            $ai93['caller_pid'] = intval($pid);
            save_gameinfo();
        }
        $itms--;
        if ($itms <= 0) {
            $log .= "<span class=\"red\">$itm</span>用光了。<br>";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
        } elseif ($itm == '【我想要领略真正的红杀之力】') {
            // 召唤红暮与蓝凝（从旧版逻辑迁移，恢复原有功能）
            include_once GAME_ROOT . './include/system.func.php';
            $log .= '你拿起了这个球状物体，重重地向天空抛去！<br>地图上空出现了红杀组织的龙虎徽标！<br>';
            addnpc(19, 0, 1);
            addnpc(19, 1, 1);
            // 发布新闻：需要将当前位置传入c参数以显示【地点】
            addnews($now, 'keyuu', $name, '', $pls, $nick);
            // 系统广播
            $db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【红暮】','','切，真是少见的要求，那么我会在【无月之影】等着你们的挑战！')");
            $db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【蓝凝】','','英雄就该姗姗来迟，我会和姐姐一起迎接你们！')");
            // 销毁物品
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;

    }
}
