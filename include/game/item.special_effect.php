<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理特殊效果物品
 * 这些物品会产生特殊效果，如改变玩家属性、状态等
 * 
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_special_effect($itmn, &$data) {
    global $log, $now, $db, $tablepre, $nosta;
    extract($data, EXTR_REFS);
    
    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};
    
    if ($itm == '『C.H.A.O.S』') {
        $flag = false;
        $log .= "一阵强光刺得你睁不开眼。<br>强光逐渐凝成了光球，你揉揉眼睛，发现包裹里的东西全都不翼而飞了。<br>";
        for ($i = 1; $i <= 6; $i++) {
            $itm_i = & ${'itm'.$i};
            $itmk_i = & ${'itmk'.$i};
            $itme_i = & ${'itme'.$i};
            $itms_i = & ${'itms'.$i};
            $itmsk_i = & ${'itmsk'.$i};
            # ventus
            if ($itm_i == '黑色发卡') {$flag = true;}
            $itm_i = '';
            $itmk_i = '';
            $itme_i = 0;
            $itms_i = 0;
            $itmsk_i = '';
        }
        $karma = $rp * $killnum - $def + $att;
        $f1 = false;

        # terra
        $tflag = (($ss >= 600) && ($killnum <= 15)) ? 1 : 0;
        # aqua
        $hflag = $karma <= 2000 ? 1 : 0;

        # 元素大师使用chaos时，不再需要进一步合成，但是会失去元素合成功能
        if ($club == 20) {
            $log .= "系在你腰间的口袋剧烈颤动着，下一刻，你的直觉被某物触动了。<br>
            在你的视界里，浮现出了难以描绘、似真似幻的独特“元素”：<br><br>";
            if ($tflag) $log .= "有生命的热火、有逝者的悲怆；<br>";
            if ($hflag) $log .= "有命运的尾迹、有因缘的蟠结；<br>";
            if ($flag) $log .= "有衬出影子的光、有糅在光里的影。<br>";
            $log .= "<br>然后，你的<span class='sparkle'>{$sparkle}元素口袋{$sparkle}</span>飞了出去——<br><br>";
            # 失去元素口袋
            $clbstatusa = 1;
            # 追加判定
            if ($tflag && $hflag && $flag == true) {
                # 直接获得gameover
                $itm0 = '『G.A.M.E.O.V.E.R』';
                $itmk0 = 'Y';
                $itme0 = 1;
                $itms0 = 1;
                $itmsk0 = 'zv';
                $f1 = true;
                include_once GAME_ROOT . './include/game/itemmain.func.php';
                itemget($data);
            } else {
                $log .= "但似乎还是少了些什么东西……<br>";
                # 大侠请重新来过
                $itm0 = '『S.C.R.A.P』';
                $itmk0 = 'Y';
                $itme0 = 1;
                $itms0 = 1;
                //$itmsk0='zv';
                $f1 = false;
                include_once GAME_ROOT . './include/game/itemmain.func.php';
                itemget($data);
            }
        } else {
            if ($tflag) {
                $itm0 = '『T.E.R.R.A』';
                $itmk0 = 'Y';
                $itme0 = 1;
                $itms0 = 1;
                $itmsk0 = 'z';
                include_once GAME_ROOT . './include/game/itemmain.func.php';
                itemget($data);
                $f1 = true;
            }
            if ($hflag) {
                $itm0 = '『A.Q.U.A』';
                $itmk0 = 'Y';
                $itme0 = 1;
                $itms0 = 1;
                $itmsk0 = 'x';
                include_once GAME_ROOT . './include/game/itemmain.func.php';
                itemget($data);
                $f1 = true;
            }
            if ($flag == true) {
                $itm0 = '『V.E.N.T.U.S』';
                $itmk0 = 'Y';
                $itme0 = 1;
                $itms0 = 1;
                $itmsk0 = 'Z';
                include_once GAME_ROOT . './include/game/itemmain.func.php';
                itemget($data);
                $f1 = true;
            }
        }
        if ($f1 == false) {
            $itm0 = '『S.C.R.A.P』';
            $itmk0 = 'Y';
            $itme0 = 1;
            $itms0 = 1;
            include_once GAME_ROOT . './include/game/itemmain.func.php';
            itemget($data);
        }
    } elseif ($itm == '装有H173的注射器') {
        $log .= '你考虑了一会，<br>把袖子卷了起来，给自己注射了H173。<br>';
        $deathdice = rand(0, 4096);
        $spdice = 1;
        // Shiny Charm
        if ($art == '★闪耀护符★') {
            // Reference: https://wiki.52poke.com/wiki/%E7%95%B0%E8%89%B2%E5%AF%B6%E5%8F%AF%E5%A4%A2#%E3%80%8A%E6%9C%B1%EF%BC%8F%E7%B4%AB%E3%80%8B
            //$deathdice += 2731; # 4096 - 1365
            $spdice = diceroll(1365);
        }
        if ($deathdice >= 4096 || $club == 15 || $spdice == 0) {
            $log .= '你突然感觉到一种不可思议的力量贯通全身！<br>';
            $wp = $wk = $wg = $wc = $wd = $wf = 8010;
            $att = $def = 13337;
            changeclub(15, $data);
            addnews($now, 'suisidefail', $name, $nick);
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } else {
            include_once GAME_ROOT . './include/state.func.php';
            $log .= '你失去了知觉。<br>';
            death('suiside', '', 0, $itm);
        }
    } elseif (strpos($itm, '溶剂SCP-294') === 0) {
        if ($itm == '溶剂SCP-294_PT_Poini_Kune') {
            $log .= '你考虑了一会，一扬手喝下了杯中中冒着紫色幽光的液体。<br><span class="yellow">你感到全身就像燃烧起来一样，不禁扪心自问这值得么？</span><br>';
            if ($mhp > 573) {
                $up = rand(0, $mhp + $msp);
            } else {
                $up = rand(0, 573);
            }
            
            if ($club == 17) {
                $hpdown = $spdown = round($up * 1.5);
            } elseif ($club == 12) {
                $hpdown = $up + 250;
                $spdown = $up;
                //根性兄贵加成消失
            } else {
                $hpdown = $spdown = $up;
            }
            $wp += $up; $wk += $up; $wg += $up; $wc += $up; $wd += $up; $wf += $up;
            $rp += 500;
            
            $mhp = $mhp - $hpdown;
            $msp = $msp - $spdown;                
            $log .= '你的生命上限减少了<span class="yellow">'.$hpdown.'</span>点，体力上限减少了<span class="yellow">'.$spdown.'</span>点，而你的全系熟练度提升了<span class="yellow">'.$up.'</span>点！<br>';
        } elseif ($itm == '溶剂SCP-294_PT_Arnval') {
            $log .= '你考虑了一会，一扬手喝下了杯中中冒着白色气泡的清澈液体。<br><span class="yellow">你感到全身就像燃烧起来一样，不禁扪心自问这值得么？</span><br>';
            if ($msp > 573) {
                $up = rand(0, $msp * 1.5);
            } else {
                $up = rand(0, 573);
            }
            $mhp = $mhp + $up;
            $def = $def + $up;
            $down = $club == 17 ? round($up * 1.5) : $up;
            $rp += 200;
            $msp = $msp - $down;
            $att = $att - $down;
            
            $log .= '你的体力上限和攻击力减少了<span class="yellow">'.$down.'</span>点，而你的生命上限和防御力提升了<span class="yellow">'.$up.'</span>点！<br>';
        } elseif ($itm == '溶剂SCP-294_PT_Strarf') {
            $log .= '你考虑了一会，一扬手喝下了杯中中冒着灰色气泡的清澈液体。<br><span class="yellow">你感到全身就像燃烧起来一样，不禁扪心自问这值得么？</span><br>';
            if ($mhp > 573) {
                $up = rand(0, $msp * 1.5);
            } else {
                $up = rand(0, 573);
            }
            $msp = $msp + $up;
            $att = $att + $up;
            $down = $club == 17 ? round($up * 1.5) : $up;
            $rp += 200;
            $mhp = $mhp - $down;
            $def = $def - $down;
            $log .= '你的生命上限和防御力减少了<span class="yellow">'.$down.'</span>点，而你的体力上限和攻击力提升了<span class="yellow">'.$up.'</span>点！<br>';
        } elseif ($itm == '溶剂SCP-294_PT_ErulTron') {
            $log .= '你考虑了一会，<br>一扬手喝下了杯中中冒着粉红光辉的液体。<br>你感到你整个人貌似变得更普通了点。<br>';
            $lvl = $exp = 0;
            $att = round($att * 0.8);
            $def = round($def * 0.8);
            $log .= '<span class="yellow">你的等级和经验值都归0了！但是，你的攻击力和防御力也变得更加普通了。</span><br>';
        }
        if ($att < 0) {$att = 0;}
        if ($def < 0) {$def = 0;}
        if ($hp > $mhp) {$hp = $mhp;}
        if ($sp > $msp) {$sp = $msp;}
        $deathflag = false;
        if ($mhp <= 0) {$hp = $mhp = 0; $deathflag = true;}
        if ($msp <= 0) {$sp = $msp = 0; $deathflag = true;}
        if ($deathflag) {
            $log .= '<span class="yellow">看起来你的身体无法承受药剂的能量……<br>果然这一点都不值得……<br></span>';
            include_once GAME_ROOT . './include/state.func.php';
            death('SCP', '', 0, $itm);
        } else {
            changeclub(17, $data);
            addnews($now, 'notworthit', $name, $nick);
        }
        $itms--;
        if ($itms <= 0) {
            if ($hp > 0) {$log .= "<span class=\"yellow\">{$itm}用完了。</span><br>";}
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        }
    } elseif ($itm == '世界树形图的演算报告' || $itm == '世界树形图的核心终端') {
        $rp_down = rand(120, 240);
        $rp = max(0, $rp - $rp_down);
        $log .= "你查阅了<span class=\"yellow\">{$itm}</span>，梳理了因果线的流向，心中的罪恶感减轻了！（RP降低了<span class=\"lime\">{$rp_down}</span>点）<br>";
        if ($itms != $nosta) {
            $itms--;
            if ($itms <= 0) {
                $log .= "<span class=\"yellow\">{$itm}用完了。</span><br>";
                $itm = $itmk = $itmsk = '';
                $itme = $itms = 0;
            }
        }
    }
}
