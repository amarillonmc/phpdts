<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
// File to process all dice related actions.
// Moving all logic related to $dice here
// 这样「奇迹的篝火」类头衔也好处理一点。

// So basically, replace all $dice rands and implement new dice items.
// Let's roll.

require_once './include/common.inc.php';

function diceroll($dice){
    global $rp, $nick;
    global $log;
    global $nikstatusa, $nikstatuse;
    //Add Luck based gameplay.
    global $clbpara;
    global $mhp, $msp, $att, $def, $wc, $wd, $wp, $wk, $wf, $wg;
    if(version_compare(PHP_VERSION,'7.0.0','<')){
        $result = rand(0, $dice);
    }else{
        //强壮随机数！
        $result = random_int(0, $dice);
    }

    //process 孤注一掷
    if($nikstatusa == 1){
        //set dice to max value
        $result = $dice;
        //reset
        $nikstatusa = $nikstatuse = 0;
    }elseif($nikstatusa == 2){
        //set dice to mid value
        $result = round($dice / 2);
        $nikstatusa = $nikstatuse = 0;
    }elseif($nikstatusa == 3){
        //set dice to 1
        $result = 1;
        $nikstatusa = $nikstatuse = 0;
    }

    if($clbpara['BGMBrand'] == 'rixolamal'){
        //Process some random rolls.
        //Each time a dice is cast, gain random ATK/DEF/WC/WD/WP/WK/WF/WG.
        if ($mhp + $msp + $att + $def < 1333){
            $rollRandomizer = rand(1, 3);
        }else{
            $rollRandomizer = rand(-2, 3);
        }
        if ($wc + $wd + $wf + $wp + $wk + $wg < 2088){
            $rollRandomizer2 = rand(1, 4);
        }else{
            $rollRandomizer2 = rand(-2, 2);
        }
        //Make sure you don't die on this.
        $mhp += $rollRandomizer;
        if ($mhp < 1){
            $mhp = 1;
        }
        $msp += $rollRandomizer;
        if ($msp < 1){
            $msp = 1;
        }
        $att += $rollRandomizer;
        $def += $rollRandomizer;
        $wc += $rollRandomizer2;
        $wd += $rollRandomizer2;
        $wp += $rollRandomizer2;
        $wk += $rollRandomizer2;
        $wf += $rollRandomizer2;
        $wg += $rollRandomizer2;
        $log .= "<span class=\"lime\">你对随机数大神的反叛令力量注入了你的身体！<br>";
        $clbpara['traitorRoll'] += 1;
    }

    if($clbpara['BGMBrand'] != 'rixolamal' && $clbpara['traitorRoll'] > 0){
        //This won't be possible because I'll implement checks to make sure you cannot exit Luck Battle Mode.
        //But, *if* people somehow do reset it - such as those NPC Platforms in BUFFALO, maybe.
        //Then we take away all the gained points, then some.
        $mhp -= $clbpara['traitorRoll'] * 2;
        if ($mhp < 1){
            $mhp = 1;
        }
        $msp -= $clbpara['traitorRoll'] * 2;
        if ($msp < 1){
            $msp = 1;
        }
        $att -= $clbpara['traitorRoll'] * 2;
        $def -= $clbpara['traitorRoll'] * 2;
        $wc -= $clbpara['traitorRoll'] * 3;
        $wd -= $clbpara['traitorRoll'] * 3;
        $wp -= $clbpara['traitorRoll'] * 3;
        $wk -= $clbpara['traitorRoll'] * 3;
        $wf -= $clbpara['traitorRoll'] * 3;
        $wg -= $clbpara['traitorRoll'] * 3;
        $power = $clbpara['traitorRoll'] * 26;
        $log .= "<span class=\"lime\">你已经不再反叛随机数大神！随机数大神对你很失望！<br>你从反叛中获得的<span class=\"yellow\">$power</span>点力量都被夺走了！<br>";
        $clbpara['traitorRoll'] = 0;
    }

    if($nick == 69){
        $log .= "<span class=\"lime\">你本次骰子的检定结果为：</span><span class=\"red\">$result</span>＼<span class=\"yellow\">$dice</span>！<br>";
    }
    //$log .= "【DEBUG】你本次骰子的检定结果为：<br><span class=\"red\">$result</span>＼<span class=\"yellow\">$dice</span>！<br>";
    $clbpara['diceRolled'] += 1;
    return $result;
}

?>