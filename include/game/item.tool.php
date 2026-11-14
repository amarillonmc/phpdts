<?php

if (! defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 处理工具类物品
 * 
 * @param int $itmn 物品在物品栏中的位置
 * @param array &$data 玩家数据
 */
function item_tool($itmn, &$data) {
    global $log, $mode, $nosta, $db, $tablepre, $now, $plsinfo;
    extract($data, EXTR_REFS);
    
    $itm = & ${'itm' . $itmn};
    $itmk = & ${'itmk' . $itmn};
    $itme = & ${'itme' . $itmn};
    $itms = & ${'itms' . $itmn};
    $itmsk = & ${'itmsk' . $itmn};
    
    if ($itm == '电池') {
        // 功能需要修改，改为选择道具使用YE类型道具可充电
        $flag = false;
        for($i = 1; $i <= 6; $i++) {
            if (${'itm' . $i} == '移动PC') {
                ${'itme' . $i} += $itme;
                $itms--;
                $flag = true;
                $log .= "为<span class=\"yellow\">{${'itm'.$i}}</span>充了电。";
                break;
            }
        }
        if (!$flag) {
            $log .= '你没有需要充电的物品。<br>';
        }
    } elseif ($itm == '群青多面体') {
        $result = $db->query("SELECT pid,name,pls FROM {$tablepre}players WHERE type = 14 && hp > 0");
        $ndata = array();
        while($nd = $db->fetch_array($result)){
            $ndata[$nd['name']] = $nd;
        }
        if(!empty($ndata)){
            foreach($ndata as $key => &$val){
                $npls = $val['pls'];
                while($npls == $val['pls']){
                    $npls = rand(1,count($plsinfo)-1);
                }                
                $val['pls'] = $npls;$npls = $plsinfo[$npls];
                $log .= "<span class=\"yellow\">{$key}</span>响应道具号召，移动到了<span class=\"yellow\">{$npls}</span>。<br>";
                addnews($now,'npcmove',$name,$key,$nick);
            }
            $db->multi_update("{$tablepre}players",$ndata,'pid');
            if($itms != $nosta){$itms--;}
        }
        return;
    } elseif ($itm == '残响兵器') {
        global $cmd, $mode;
        foreach(Array('wep','arb','arh','ara','arf','art') as $val) {
            // 全局变量已在extract中处理
        }
        for($i = 1; $i <= 6; $i++) {
            // 全局变量已在extract中处理
        }

        include template('nametag');

        $cmd = ob_get_contents();
        ob_clean();
        // 不要设置mode，让二级菜单显示
        return;
    } elseif ($itm == '超臆想时空') {
        global $cmd, $mode;
        foreach(Array('wep','arb','arh','ara','arf','art') as $val) {
            // 全局变量已在extract中处理
        }
        for($i = 1; $i <= 6; $i++) {
            // 全局变量已在extract中处理
        }

        include template('supernametag');

        $cmd = ob_get_contents();
        ob_clean();
        // 不要设置mode，让二级菜单显示
        return;
    } elseif ($itm == '毒药') {
        global $cmd, $mode;
        for($i = 1; $i <= 6; $i++) {
            // 全局变量已在extract中处理
        }
        include template('poison');

        $cmd = ob_get_contents();
        ob_clean();
        // 不要设置mode，让二级菜单显示
        return;
    } elseif ($itm == '探测器电池') {
        $flag = false;
        for($i = 1; $i <= 6; $i++) {
            if (${'itmk' . $i} == 'R') {
                ${'itme' . $i} += $itme;
                if ($itms != $nosta) {
                    $itms--;
                }
                $flag = true;
                $log .= "为<span class=\"yellow\">{${'itm'.$i}}</span>充了电。";
                break;
            }
        }
        if (!$flag) {
            $log .= '你没有探测仪器。<br>';
        }
    } elseif ($itm == '御神签') {
        $log .= "使用了<span class=\"yellow\">$itm</span>。<br>";
        include_once GAME_ROOT . './include/game/item2.func.php';
        divining();
        if ($itms != $nosta) {
            $itms--;
        }
    } elseif ($itm == '凸眼鱼') {
        $tm = $now - $corpseprotect; // 尸体保护
        $db->query("UPDATE {$tablepre}players SET weps='0',wep2s='0',arbs='0',arhs='0',aras='0',arfs='0',arts='0',itms0='0',itms1='0',itms2='0',itms3='0',itms4='0',itms5='0',itms6='0',money='0' WHERE hp <= 0 AND endtime <= $tm");
        $cnum = $db->affected_rows();
        addnews($now, 'corpseclear', $name, $cnum, $nick);
        $log .= "使用了<span class=\"yellow\">$itm</span>。<br>突然刮起了一阵怪风，吹走了地上的{$cnum}具尸体！<br>";
        if ($itms != $nosta) {
            $itms--;
        }
        $isk = $cnum;
    } elseif ($itm == '鱼眼凸') {
        $tm = $now - $corpseprotect; // 尸体保护
        $db->query("UPDATE {$tablepre}players SET pls='$pls' WHERE hp <= 0 AND endtime <= $tm");
        $cnum = $db->affected_rows();
        addnews($now, 'corpsegather', $name, $cnum, $nick);
        $log .= "使用了<span class=\"yellow\">$itm</span>。<br>突然刮起了一阵怪风，将遍布全场的{$cnum}具尸体吹到了你所在的地方！<br>";
        $rp += diceroll(1024);
        $log .= "<span class=\"lime\">这过于惨无人道了！</span><br>你觉得罪恶感爬上了你的脊梁！<br>";
        if ($itms != $nosta) {
            $itms--;
        }
        $isk = $cnum;
    } elseif ($itm == '天候棒') {
        if($weather <= 13) {
            $weather = rand(10, 13);
            include_once GAME_ROOT . './include/system.func.php';
            save_gameinfo();
            addnews($now, 'wthchange', $name, $weather, $nick);
            $log .= "你转动了几下天候棒。<br>天气突然转变成了<span class=\"red\">$wthinfo[$weather]</span>！<br>";
        } else {
            addnews($now, 'wthfail', $name, $weather, $nick);
            $log .= "你转动了几下天候棒。<br>但天气并未发生改变！<br>";
        }
        if ($itms != $nosta) {
            $itms--;
        }
    } elseif ($itm == '消音器') {
        if (strpos($wepk, 'WG') !== 0) {
            $log .= '你没有装备枪械，不能使用消音器。<br>';
        } elseif (strpos($wepsk, 'S') === false) {
            $wepsk .= 'S';
            $log .= "你给<span class=\"yellow\">$wep</span>安装了<span class=\"yellow\">$itm</span>。<br>";
            if ($itms != $nosta) {
                $itms--;
            }
        } else {
            $log .= "你的武器已经安装了消音器。<br>";
        }
    } elseif ($itm == '■DeathNote■' || $itm == '四面亲手制作的■DeathNote■') {
        // 打开死亡笔记二级菜单
        // 说明：item.main.php 末尾会在 $cmd 为空时强制设置 $mode='command'
        // 因此这里必须设置 $cmd（而不是仅设置 $mode），以便二级菜单正确显示
        global $cmd;
        // 旧模板使用隐藏字段 name="item" 承载道具格位，这里与之对齐
        $item = $itmn;
        include template('deathnote');
        $cmd = ob_get_contents();
        ob_clean();
        // 不设置 $mode，交由框架根据 $cmd 渲染表单
        return;
    }
}
