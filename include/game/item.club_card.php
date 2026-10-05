<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理社团卡物品
 * 这些物品会改变玩家的社团属性
 * 
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_club_card($itmn, &$data) {
    global $log, $db, $tablepre, $now, $elements_info, $sparkle;
    extract($data, EXTR_REFS);
    
    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};
    
    if ($itmk == 'ZB') { // 社团卡
        if ($itme == 23) {
            // 主动选择的特殊称号；已有称号或本规则集未开放时保留道具。
            global $club_skillslist;
            if ($club || $hp <= 0 || $type || empty($club_skillslist[23])) {
                $log .= '只有尚未选择称号的参战者，才能打开无主的补缀匣。<br>';
                return;
            }
            changeclub(23, $data);
            $clbpara['dialogue'] = 'club23entry';
            $log .= '<span class="purple">你将那段没有来历的往事留在了自己身上。</span><br>获得称号<span class="yellow">崩坏心核</span>。<br>打开技能界面的「黑岚」，可以领取第一件剥落的记忆。<br>';
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
            ${'itmpara'.$itmn} = '';
            return;
        }
        if ($club) {
            $log .= "你已经是有身份的人了！不能再使用称号卡。<br>";
            $db->query("INSERT INTO {$tablepre}shopitem (kind,num,price,area,item,itmk,itme,itms,itmsk) VALUES ('18','1','20','0','$itm','$itmk','$itme','$itms','$itmsk')");
            $log .= "<span class='yellow'>$itm</span>像是有生命一般从你的手上脱离，飞回了商店！";
        }
        // 处理不能成为合法社团的情况
        elseif ($itme == 15) { // L5状态
            $log .= "【DEBUG】进入L5状态<br>";
            $log .= '你突然感觉到一种不可思议的力量贯通全身！<br>';
            $wp = $wk = $wg = $wc = $wd = $wf = 8010;
            $att = $def = 13337;
            changeclub(15, $data);
            addnews($now, 'suisidefail', $name, $nick);
        } elseif ($itme == 17 || $itme > 22) { // 状态机社团以及不存在的社团
            $log .= "但是什么都没有发生！";
        } elseif ($itme == 20) { // 元素大师特殊处理
            // 规则怪谈类型文案
            $log .= "你拿起<span class='yellow'>$itm</span>左右端详着……<br>
            然后，它突然就在你的眼前消失了！<br>
            在你寻思着出了什么事情之后，你的面前突然多了几条类似于规则的玩意。<br>
            【特殊程序·元素大师使用规则】<br>
            <br>
            【其之一】这世上的一切都由六种元素组成。<br>
            【其之二】每种元素都能组成一种武器或防具。<br>
            【其之三】当你捡到物品后，便可将其提炼成元素。<br>
            【其之四】此外，看起来没有用的尸体也可被提炼，不过后果自负。<br>
            【其之五】提炼时偶尔会蹦出特殊信息，最好将它们记录下来。<br>
            【其之六】提炼出的元素，可以通过「元素合成」产出各种物品。<br>
            【其之七】相对是这个世界的摄理之一，如果过于追求数字，就无法体现特殊性。<br>
            正在你读着这些规则的时候，它们也在你的眼前慢慢消失……<br>";
            $log .= "最后变成了一个<span class='sparkle'>{$sparkle}元素口袋{$sparkle}</span>！<br>";
            $log .= "在你将这个口袋收起来时，突然胸口一紧，你的眼前跳出了更多的文字：<br>
            【其之零】在D.T.S.的虚拟环境中，不存在将物品单纯地放在一起就能合成的手段。<br>
            然后，一行新的文字替代了这条规则：<br>
            【其之零】一切都是数字的假象而已。<br>
            正在你回味着这句话的时候，一切已经恢复如初。";
            // 社团变更
            changeclub(20, $data);
            // 获取初始元素与第一条配方
            $dice = rand(0, 5);
            $dice2 = rand(0, 1);
            $dice3 = rand(0, 3);
            ${'element' . $dice} += 500 + $dice;
            $clbpara['elements'] = Array();
            $clbpara['elements']['tags'] = Array($dice => Array('dom' => Array(0 => 1), 'sub' => Array(0 => 1)));
            $clbpara['elements']['info']['d']['d1'] = 1;
            // 初始化元素合成缓存文件
            include_once GAME_ROOT . './include/game/elementmix.func.php';
            emix_spawn_info();
        } elseif ($itme == 21) { // 码语行人特殊处理
            // Let's have some fun !
            $clbpara['dialogue'] = 'club21entry';
            // 社团变更
            $db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','','「Ρжжηψψρип ρип, ρжжηψψρжжρип ρип」')");
            $db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','','「ρψψρип ρип, ρип ρип ρжжηψψρжж ρδ」')");
            changeclub(21, $data);
            // And we inflict some pretty damage as entry fee.
            $hp = $hp / 3;
            $sp = 1;
        } elseif ($itme == 22) { // 偶像大师特殊处理
            //$log .= "再等等吧……<br>";
            $clbpara['dialogue'] = 'club22entry';
            changeclub(22, $data);
        } else { // 直接将社团卡的效果写入玩家club
            changeclub($itme, $data);
            $log .= "你的称号被改动了！";
        }
        // 销毁物品
        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
    }
}
