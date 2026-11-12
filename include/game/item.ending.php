<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理结局物品
 * 这些物品会触发游戏结束
 *
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_ending($itmn, &$data) {
    global $log, $url, $state, $now, $gamevars;
    extract($data, EXTR_REFS);

    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};

    if ($itm == '游戏解除钥匙') {
        if(function_exists('ending_routes_enabled') && ending_routes_enabled()) {
            $true_key = strpos($itmsk,'Zv')!==false;
            if($true_key && wheelchair_penalty_count($data)>0) {
                $true_key=false;
                $log.='【中枢协议警报】「扶手还没松，就急着摸世界的底牌？先走这边吧。」你将转入【伪·解禁】路线。<br>';
            }
            $gamevars['ending_subroute']=$true_key ? 'jiejin_Zv' : 'jiejin_v';
            $clbpara['ending_subroute']=$gamevars['ending_subroute'];
            destory_single_item($data,$itmn);
            $state = 6;
            player_save($data);
            save_gameinfo();
        }
        $state = 6;
        $url = 'end.php';
        include_once GAME_ROOT . './include/system.func.php';
        gameover($now, 'end3', $name);
    } elseif (function_exists('ending_routes_enabled') && ending_routes_enabled() && in_array($itm,array('【解离凭证·幻境】','【解离凭证·真实】'),true)) {
        $route=$itm==='【解离凭证·真实】' ? 'true' : 'phantom';
        if(!dissociation_active($data) || ($clbpara['dissociation_route'] ?? '')!==$route || dissociation_timeout($data)) {
            $log.='这份凭证尚不能在这里开启出口。<br>'; return;
        }
        if(empty($gamevars['dissociation'][$route]['collapsed'])) { $log.='最终守望者仍未全部退场，凭证尚未点亮。<br>'; return; }
        $gamevars['ending_subroute']='jieli_'.$route;
        $clbpara['ending_subroute']=$gamevars['ending_subroute'];
        destory_single_item($data,$itmn);
        $state=6; $url='end.php'; player_save($data); save_gameinfo();
        include_once GAME_ROOT.'./include/system.func.php';
        gameover($now,'end7',$name);
    } elseif ($itm == '『G.A.M.E.O.V.E.R』') {
        unset($gamevars['ending_subroute']);
        $state = 6;
        $url = 'end.php';
        include_once GAME_ROOT . './include/system.func.php';
        gameover($now, 'end7', $name);
    } elseif ($itm == '奇怪的按钮') {
        $button_dice = rand(1, 10);
        if ($button_dice < 5) {
            $log .= "你按下了<span class=\"yellow\">$itm</span>，不过好像什么都没有发生！";
            $itm = $itmk = $itmsk = '';
            $itme = $itms = 0;
        } elseif ($button_dice < 8) {
            $state = 6;
            $url = 'end.php';
            include_once GAME_ROOT . './include/system.func.php';
            gameover($now, 'end5', $name);
        } else {
            $log .= '好像什么也没发生嘛？<br>咦，按钮上的标签写着什么？"危险，勿触"……？<br>';
            include_once GAME_ROOT . './include/state.func.php';
            $log .= '呜哇，按钮爆炸了！<br>';
            death('button', '', 0, $itm);
        }
    } elseif ($itm == '【E.S.C.A.P.E】') {
        // 这实际上是个死法，但是会给成就，称号，并加积分与胜场
        include_once GAME_ROOT . './include/state.func.php';
        // 成就检查该物品本身的使用，逻辑不写在这里
        $log .= '万事俱备，只欠逃离！<br>';
        // 销毁物品
        $itm = $itmk = $itmsk = '';
        $itme = $itms = 0;
        death('s_escape', '', 0, $itm);
    }
}
