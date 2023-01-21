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
    $result = rand(0, $dice);

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

    if($nick =="奇迹的篝火"){
        $log .= "你本次骰子的检定结果为：<br><span class=\"red\">$result</span>＼<span class=\"yellow\">$dice</span>！<br>";
    }
    //$log .= "【DEBUG】你本次骰子的检定结果为：<br><span class=\"red\">$result</span>＼<span class=\"yellow\">$dice</span>！<br>";
    return $result;
}

?>