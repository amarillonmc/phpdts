<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理武器改造类物品
 * 
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_weapon_mod($itmn, &$data) {
    global $log, $nosta, $now;
    extract($data, EXTR_REFS);
    
    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};
    
    if (strpos($itm, '磨刀石') !== false) {
        if (strpos($wepk, 'K') == 1 && strpos($wepsk, 'Z') === false) {
            if (strpos($wepsk, 'j') !== false) {
                $log .= '多重武器不能改造。<br>';
                return;
            }
            $dice = rand(0, 100);
            if ($dice >= 15) {
                if ($clbpara['BGMBrand'] == 'crimson') {
                    $check = diceroll(20);
                    if ($check > 17) {
                        $log .= "<span class=\"ltcrimson\">你想到了红暮挥舞红杀铁剑的英姿，<br>手上的刀磨得更快了！<br></span>";
                        $wepe += $check;
                    }
                }
                $wepe += $club == 24 ? diceroll(max(0,(int)$itme)) : $itme;
                $log .= "使用了<span class=\"yellow\">$itm</span>，<span class=\"yellow\">$wep</span>的攻击力变成了<span class=\"yellow\">$wepe</span>。<br>";
                if (strpos($wep, '锋利的') === false) {
                    $wep = '锋利的'.$wep;
                }
            } else {
                $wepe -= ceil($itme / 2);
                if ($wepe <= 0) {
                    $log .= "<span class=\"red\">$itm</span>使用失败，<span class=\"red\">$wep</span>损坏了！<br>";
                    $wep = $wepk = $wepsk = '';
                    $wepe = $weps = 0;
                } else {
                    $log .= "<span class=\"red\">$itm</span>使用失败，<span class=\"red\">$wep</span>的攻击力变成了<span class=\"red\">$wepe</span>。<br>";
                }
            }
            
            $itms--;
        } elseif(strpos($wepsk, 'Z') !== false) {
            $log .= '咦……刀刃过于薄了，感觉稍微磨一点都会造成不可逆的损伤呢……<br>';
        } else {
            $log .= '你没装备锐器，不能使用磨刀石。<br>';
        }
    } elseif (preg_match("/钉$/", $itm) || preg_match("/钉\[/", $itm)) {
        // 码语行人，$club==21的时候不能使用钉子
        if ($club == 21) {
            $log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
            $log .= "<span class=\"glitchb\">
            “凌乱陈言省略号，<br>
            数值爆炸知多少？<br>
            玩家以外用不到，<br>
            出了问题再来找！”<br></span><br>";
            $log .= "<span class=\"yellow\">唔，看起来这个钉子对你似乎没有什么意义……</span><br>";
            return;
        } elseif ((strpos($wep, '棍棒') !== false) && ($wepk == 'WP')) {
            if (strpos($wepsk, 'j') !== false) {
                $log .= '多重武器不能改造。<br>';
                return;
            }
            $dice = rand(0, 100);
            if ($dice >= 10) {
                if ($clbpara['BGMBrand'] == 'crimson') {
                    $check = diceroll(20);
                    if ($check > 17) {
                        $log .= "<span class=\"ltcrimson\">你想到了红暮挥舞红杀铁锤的英姿，<br>手上的钉子打得更快了！<br><span>";
                        $wepe += $check;
                    }
                }
                $wepe += $club == 24 ? diceroll(max(0,(int)$itme)) : $itme;
                $log .= "使用了<span class=\"yellow\">$itm</span>，<span class=\"yellow\">$wep</span>的攻击力变成了<span class=\"yellow\">$wepe</span>。<br>";
                if (strpos($wep, '钉') === false) {
                    $wep = str_replace('棍棒', '钉棍棒', $wep);
                }
            } else {
                $wepe -= ceil($itme / 2);
                if ($wepe <= 0) {
                    $log .= "<span class=\"red\">$itm</span>使用失败，<span class=\"red\">$wep</span>损坏了！<br>";
                    $wep = $wepk = $wepsk = '';
                    $wepe = $weps = 0;
                } else {
                    $log .= "<span class=\"red\">$itm</span>使用失败，<span class=\"red\">$wep</span>的攻击力变成了<span class=\"red\">$wepe</span>。<br>";
                }
            }
            
            $itms--;
        } else {
            $log .= '你没装备棍棒，不能安装钉子。<br>';
        }
    } elseif ($itm == '针线包') {
        // 码语行人，$club==21的时候不能使用针线包
        if ($club == 21) {
            $log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
            $log .= "<span class=\"glitchb\">
            “冷汗直流小问号，<br>
            防御堆到多少好？<br>
            与其数值罩白梦，<br>
            不如让她转生了！”<br></span><br>";
            $log .= "<span class=\"yellow\">唔，看起来这个针线包对你似乎没有什么意义……</span><br>";
            return;
        } elseif (($arb == $noarb) || !$arb) {
            $log .= '你没有装备防具，不能使用针线包。<br>';
        } elseif(strpos($arbsk, '^') !== false) {
            $log .= '<span class="yellow">你不能对背包使用针线包。<br>';
        } elseif(strpos($arbsk, 'Z') !== false) {
            $log .= '<span class="yellow">该防具太单薄以至于不能使用针线包。</span><br>你感到一阵蛋疼菊紧，你的蛋疼度增加了<span class="yellow">233</span>点。<br>';
        } else {
            if ($clbpara['BGMBrand'] == 'rimefire') {
                $check = diceroll(20);
                if ($check > 17) {
                    $log .= "<span class=\"orange\">你突然脑海中浮现了一位青年彻夜优化装甲的英姿，<br>手上的针线打得更快了！<br></span>";
                    $arbe += $check;
                }
            }
            $gain = rand(0, 2) + $itme;
            $arbe += $club == 24 ? diceroll(max(0,(int)$gain)) : $gain;
            $log .= "用<span class=\"yellow\">$itm</span>给防具打了补丁，<span class=\"yellow\">$arb</span>的防御力变成了<span class=\"yellow\">$arbe</span>。<br>";
            $itms--;
        }
    } elseif ($itm == '天然呆四面的奖赏') {
        // 码语行人，$club==21的时候不能使用天然呆四面的奖赏
        if ($club == 21) {
            $log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
            $log .= "<span class=\"glitchb\">
            “无语无言点句号，<br>
            第四墙外看不到！<br>
            无法干涉即取消，<br>
            反正一个也不少！<br>”</span><br>";
            $log .= "<span class=\"yellow\">唔，看起来这个奇怪的物品对你似乎没有什么意义……</span><br>";
            return;
        }
        if (!$weps || !$wepe) {
            $log .= '请先装备武器。<br>';
            return;
        }
        if (strpos($wepsk, 'j') !== false) {
            $log .= '多重武器不能改造。<br>';
            return;
        }
        if (strpos($wepsk, 'O') !== false) {
            $log .= '进化武器不能改造。<br>';
            return;
        }
        $log .= "使用了<span class='yellow'>天然呆四面的奖赏</span>。<br>";
        $log .= "你召唤了<span class='lime'>天然呆四面</span>对你的武器进行改造！<br>";
        addnews($now, 'newwep', $name, $itm, $wep, $nick);
        $dice = rand(0, 99);
        if ($dice < 70) {
            $log .= "<span class='lime'>天然呆四面</span>把你的武器弄坏了！<br>";
            $log .= "你的武器变成了一块废铁！<br>";
            $log .= "<span class='lime'>“不小心把你的武器弄坏了，还真是对不起呢……<br>";
            $wep = "一块废铁"; $wepk = "WP"; $wepe = 1; $weps = 1; $wepsk = "";
            $log .= "那么…… 给你点补偿吧，请务必收下。”<br></span>";
            $itm = ""; $itmk = ""; $itme = 0; $itms = 0; $itmsk = "";
            $dice2 = rand(0, 99);
            $itm0 = '四面亲手制作的■DeathNote■'; $itmk0 = 'Y'; $itme0 = 1; $itms0 = 1; $itmsk0 = 'z';
            include_once GAME_ROOT . './include/game/itemmain.func.php';
            itemget($data);
        } else if ($dice < 90) {
            $log .= "<span class='lime'>天然呆四面</span>把玩了一会儿你的武器。<br>";
            $log .= "你的武器的耐久似乎稍微多了一点。<br>";
            if (strpos($wep, '-改') === false) $wep = $wep . '-改';
            $gain = ceil($wepe / 200);
            if($club != 24 || $weps !== $nosta) $weps += $club == 24 ? diceroll(max(0,(int)$gain)) : $gain;
            $itm = ""; $itmk = ""; $itme = 0; $itms = 0; $itmsk = "";
        } else {
            $log .= "<span class='lime'>天然呆四面</span>把玩了一会儿你的武器。<br>";
            $log .= "你的武器似乎稍微变强了一点。<br>";
            if (strpos($wep, '-改') === false) $wep = $wep . '-改';
            $gain = ceil($wepe / 200);
            $wepe += $club == 24 ? diceroll(max(0,(int)$gain)) : $gain;
            $itm = ""; $itmk = ""; $itme = 0; $itms = 0; $itmsk = "";
        }
    } elseif ($itm == '武器师安雅的奖赏') {
        // 码语行人，$club==21的时候不能使用武器师安雅的奖赏
        if ($club == 21) {
            $log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
            $log .= "<span class=\"glitchb\">
            “奇诡无比省略号，<br>
            奇葩捏他哪里找？<br>
            横竖都是用不上。<br>
            看我直接注释掉！”<br></span><br>";
            $log .= "<span class=\"yellow\">唔，看起来武器师安雅的奖赏对你似乎没有什么意义……</span><br>";
            return;
        } elseif (!$weps || !$wepe) {
            $log .= '请先装备武器。<br>';
            return;
        }
        if (strpos($wepsk, 'j') !== false) {
            $log .= '多重武器不能改造。<br>';
            return;
        }
        $dice = rand(0, 99);
        $dice2 = rand(0, 99);
        $skill = array('WP' => $wp, 'WK' => $wk, 'WG' => $wg, 'WC' => $wc, 'WD' => $wd, 'WF' => $wf);
        $skill_advanced = array('WJ' => $wg, 'WB' => $wc);
        arsort($skill);
        $skill_keys = array_keys($skill);
        $skill_advanced_keys = array_keys($skill_advanced);            
        $nowsk = substr($wepk, 0, 2);
        if (strlen($wepk) > 2) $subsk = 'W'.$wepk[2];
        $maxsk = $skill_keys[0];
        // 复合武器只要其中一个类别是最高就不会改系
        // 上位武器熟练超过1200不会改系，可能算加强六系称号
        if (((!in_array($nowsk, $skill_advanced_keys) && ($skill[$nowsk] != $skill[$maxsk]) && (empty($subsk) || ((!empty($subsk) && !in_array($subsk, $skill_advanced_keys) && ($skill[$subsk] != $skill[$maxsk]))))) || (in_array($nowsk, $skill_advanced_keys) && ($skill_advanced[$nowsk] < 1200))) && ($dice < 30)) {
            $wepk = substr_replace($wepk, $maxsk, 0, 2);
            $kind = "更改了{$wep}的<span class=\"yellow\">类别</span>！";
        } elseif (($weps != $nosta) && ($dice2 < 70)) {
            $gain = ceil($wepe / 2);
            $weps += $club == 24 ? diceroll(max(0,(int)$gain)) : $gain;
            $kind = "增强了{$wep}的<span class=\"yellow\">耐久</span>！";
        } else {
            $gain = ceil($wepe / 2);
            $wepe += $club == 24 ? diceroll(max(0,(int)$gain)) : $gain;
            $kind = "提高了{$wep}的<span class=\"yellow\">攻击力</span>！";
        }
        $log .= "你使用了<span class=\"yellow\">$itm</span>，{$kind}";
        addnews($now, 'newwep', $name, $itm, $wep, $nick);
        if (strpos($wep, '-改') === false) {
            $wep = $wep . '-改';
        }
        $itms--;
    } elseif ($itm == '『灵魂宝石』' || $itm == '『祝福宝石』') {
        global $cmd, $mode;
        //码语行人，$club==21的时候不能使用宝石
        if ($club == 21) {
            $log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
            $log .= "<span class=\"glitchb\">
            “纠结纠结小问号，<br>
            代码溢出怎么搞？<br>
            干脆一刀禁了它。<br>
            反正挨打不用愁！”<br></span><br>";
            $log .= "<span class=\"yellow\">唔，看起来这个宝石对你似乎没有什么意义……</span><br>";
            return;
        }
        $cmd = '<input type="hidden" name="mode" value="item"><input type="hidden" name="usemode" value="qianghua"><input type="hidden" name="itmp" value="' . $itmn . '">你想强化哪一件装备？<br><input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl("menu"); href="javascript:void(0);" >返回</a><br><br><br>';
        for ($i = 1; $i <= 6; $i++) {
            //global ${'itmsk' . $i};
            if ((strpos(${'itmsk' . $i}, 'Z') !== false) && (strpos(${'itm' . $i}, '宝石』') === false)) {
                //global ${'itm' . $i}, ${'itme' . $i}, ${'itms' . $i};
                $cmd .= '<input type="radio" name="command" id="itm' . $i . '" value="itm' . $i . '"><a onclick=sl("itm' . $i . '"); href="javascript:void(0);" >' . "{${'itm' .$i}}/{${'itme' .$i}}/{${'itms' .$i}}" . '</a><br>';
              $flag = true;
            }
        }
        $cmd .= '<br><br><input type="button" onclick="postCmd(\'gamecmd\',\'command.php\');" value="提交">';
        if (! $flag) {
            $log .='唔？你的包裹里没有可以强化的装备，是不是没有脱下来呢？DA☆ZE<br><br>';
        }else{
            $log .="宝石在你的手上发出异样的光芒，似乎有个奇怪的女声在你耳边说道<span class=\"yellow\">\"我是从天界来的凯丽\"</span>.";
        }
        // 不要设置mode，让二级菜单显示
        return;
    } elseif ($itm == '水果刀') {
        $flag = false;
        
        for($i = 1; $i <= 6; $i ++) {
            //global ${'itm' . $i}, ${'itmk' . $i},${'itms' . $i},${'itme' . $i},$wk;
            if (strpos(${'itmsk' . $i}, '🍎') !== false) {
                if($wk >= 120){
                    $log .= "练过刀就是好啊。你娴熟地削着果皮。<br><span class=\"yellow\">{${'itm'.$i}}</span>变成了<span class=\"yellow\">★残骸★</span>！<br>咦为什么会出来这种东西？算了还是不要吐槽了。<br>";
                    ${'itm' . $i} = '★残骸★';
                    ${'itme' . $i} *= rand(2,4);
                    ${'itms' . $i} *= rand(3,5);
                    ${'itmsk' . $i} = '';
                    $flag = true;
                    $wk++;
                }else{
                    $log .= "想削皮吃<span class=\"yellow\">{${'itm'.$i}}</span>，没想到削完发现只剩下一堆果皮……<br>手太笨拙了啊。<br>";
                    $brackets_arr = Array('☆☆','★★','〖〗','【】','『』','「」','✦✦','☾☽','☼☼','■■');
                    $if_brackets = 0;
                    foreach ($brackets_arr as $brackets)
                    {
                        if ((mb_substr(${'itm' . $i}, 0, 1)).(mb_substr(${'itm' . $i}, -1)) === $brackets){
                            $if_brackets = 1;
                            ${'itm' . $i} = mb_substr(${'itm' . $i}, 0, -1).'皮'.mb_substr(${'itm' . $i}, -1);
                            break;
                        }							
                    }
                    if ($if_brackets == 0) ${'itm' . $i} = ${'itm' . $i}.'皮';
                    ${'itmk' . $i} = 'TN';
                    ${'itms' . $i} *= rand(2,4);
                    ${'itmsk' . $i} = '';
                    $flag = true;
                    $wk++;
                }
                break;
            }
            if($flag == true) {break;};
        }
        if (! $flag) {
            $log .= '包裹里没有水果。<br>';
        } else {
            $dice = rand(1,5);
            if($dice==1){
                $log .= "<span class=\"red\">$itm</span>变钝了，无法再使用了。<br>";
                $itm = $itmk = $itmsk = '';
                $itme = $itms = 0;
            }
        }
    }
}
