<?php
if (!defined('IN_GAME')) exit('Access Denied');

// 只接回大房间的通用操作；移动、战斗、死亡仍由 FLASH 自己结算。
function flash_native_int($post,$key,$min,$max,$default=null) {
    $value=$post[$key]??$default;
    if (!is_string($value) || !ctype_digit($value) || strlen($value)>10 || (int)$value<$min || (int)$value>$max) throw new RuntimeException('请选择有效的操作参数。');
    return (int)$value;
}
function flash_native_command(&$p,$post) {
    global $db,$tablepre,$mode,$cmd,$flash_cmd_template,$flash_cmd_vars,$mix_type;
    if (!is_array($post)) throw new RuntimeException('请使用页面上的操作按钮。');
    $command=$post['command']; $request_mode=$post['mode']??'command';
    if (!is_string($request_mode)) throw new RuntimeException('操作方式无效。');
    // 旧物品/技能函数部分使用全局引用，与 command.php 的 extract(..., EXTR_REFS) 一致。
    $GLOBALS['pdata']=&$p;
    foreach ($p as $key=>&$value) $GLOBALS[$key]=&$value;
    unset($value);
    require_once GAME_ROOT.'include/game/itemmain.func.php';
    require_once GAME_ROOT.'include/game/special.func.php';
    $mode='command'; $cmd='';
    if ($command==='iteminfo') {
        if (strpos($p['arbsk'],'^')===false || !$p['arbs'] || !$p['arbe']) throw new RuntimeException('请先装备背包。');
        require_once GAME_ROOT.'include/game/itembag.func.php';
        item_info(); return;
    }
    if ($command==='itemmain') {
        $panel=$post['itemcmd']??'';
        if (!is_string($panel) || !in_array($panel,array('itemmerge','itemmix','itemdrop'),true)) throw new RuntimeException('请选择整理、合成或丢弃物品。');
        $flash_cmd_template=$panel;
        if ($panel==='itemmix' && isset($post['itemindex'])) {
            if (!is_string($post['itemindex']) || strlen($post['itemindex'])>240) throw new RuntimeException('合成索引无效。');
            $GLOBALS['itemindex']=flash_html($post['itemindex']); $GLOBALS['opendialog']='itemmix_tips';
        }
        return;
    }
    if ($command==='special') {
        $special=$post['sp_cmd']??'';
        if ($special==='sp_shop') $command='shop';
        elseif ($special==='sp_weapon') { $p['clbpara']['flash']['idle_at']=$GLOBALS['now']; weaponswap(); return; }
        else throw new RuntimeException('这里没有这项操作。');
    }
    if ($command==='shop' || preg_match('/^shop([0-9]{1,2})$/D',$command,$match) || ($request_mode==='shop' && ctype_digit($command))) {
        if (!in_array((int)$p['pls'],$GLOBALS['shops']) && check_skill_unlock('c11_ebuy',$p)) throw new RuntimeException('这里没有商店，请先到有商店的地点。');
        if ($command==='shop') { $flash_cmd_template='sp_shop'; return; }
        if (!empty($match)) {
            $shop=(int)$match[1];
            if ($shop<1 || $shop>20) throw new RuntimeException('没有这类商品。');
            shoplist($shop); $flash_cmd_template='shop'; $flash_cmd_vars=array('shop'=>$shop); return;
        }
        flash_require_empty_loot($p);
        $sid=flash_native_int($post,'command',1,2147483647);
        $shop=flash_native_int($post,'shoptype',1,20);
        $amount=flash_native_int($post,'buynum',1,1000,'1');
        $item=$db->fetch_array($db->query("SELECT * FROM {$tablepre}shopitem WHERE sid = '$sid'"));
        if (!$item || (int)$item['kind']!==$shop || $item['price']<=0) throw new RuntimeException('这件商品不存在，或不在当前货架。');
        $p['clbpara']['flash']['idle_at']=$GLOBALS['now'];
        ob_start();
        try { itembuy($sid,$shop,$amount,$p); } finally { ob_end_clean(); }
        if ($p['itms0']) { flash_loot($p,array(flash_item_at($p,0))); flash_set_item($p,0); }
        if (!$p['clbpara']['flash']['loot']) { shoplist($shop); $flash_cmd_template='shop'; $flash_cmd_vars=array('shop'=>$shop); }
        return;
    }
    if ($command==='itemmove' || $command==='itemmerge') {
        $from=flash_native_int($post,$command==='itemmove'?'from':'merge1',1,6);
        $to=flash_native_int($post,$command==='itemmove'?'to':'merge2',1,6);
        $p['clbpara']['flash']['idle_at']=$GLOBALS['now'];
        if ($command==='itemmove') itemmove($from,$to);
        else {
            if (get_itmpara($p['itmpara'.$from])!=get_itmpara($p['itmpara'.$to])) throw new RuntimeException('这两件物品的附加效果不同，不能合并。');
            itemmerge($from,$to);
        }
        $flash_cmd_template='itemmerge'; return;
    }
    if ($command==='itemmix') {
        flash_require_empty_loot($p);
        $select=isset($post['itemselect'])?flash_native_int($post,'itemselect',0,999):-1;
        if ($select===999) return;
        $list=array();
        if (isset($post['mixmask'])) {
            $mask=flash_native_int($post,'mixmask',0,63);
            for ($i=1;$i<=6;$i++) if ($mask & (1<<($i-1))) $list[]=$i;
        } else for ($i=1;$i<=6;$i++) if (isset($post['mitm'.$i]) && flash_native_int($post,'mitm'.$i,0,6)===$i) $list[]=$i;
        require_once GAME_ROOT.'include/game/itemmix.func.php';
        if ($select>=0) {
            $results=itemmix_get_result($list,$p);
            if (!isset($results[$select])) throw new RuntimeException('素材或合成结果已经改变，请重新选择。');
        }
        $p['clbpara']['flash']['idle_at']=$GLOBALS['now'];
        ob_start();
        try { itemmix_rev($list,$select,$p); } finally { ob_end_clean(); }
        if ($p['itms0']) { flash_loot($p,array(flash_item_at($p,0))); flash_set_item($p,0); }
        if (!$p['clbpara']['flash']['loot']) $flash_cmd_template='itemmix';
        return;
    }
    if (preg_match('/^inf([hbafpuiewA])$/D',$command,$match)) { $p['clbpara']['flash']['idle_at']=$GLOBALS['now']; chginf($match[1]); return; }
    if (preg_match('/^hor([0-9])$/D',$command,$match)) {
        if (!isset($GLOBALS['horizoninfo'][(int)$match[1]])) throw new RuntimeException('没有这种视界。');
        $p['horizon']=(int)$match[1]; flash_say('视界切换为'.$GLOBALS['horizoninfo'][$p['horizon']].'。'); return;
    }
    if (preg_match('/^(upgskill|swtskill)_([a-z0-9_]+)$/D',$command,$match)) {
        $skill=$match[2]; $spec=$GLOBALS['cskills'][$skill]??null;
        if (!$spec || !in_array($skill,$p['clbpara']['skill'],true)) throw new RuntimeException('你还没有学会这个技能。');
        $p['clbpara']['flash']['idle_at']=$GLOBALS['now'];
        if ($match[1]==='upgskill') upgclbskills($skill,flash_native_int($post,$command.'_nums',1,1000,'1'));
        else {
            $choice=$post[$skill.'upgpara']??null;
            if (!is_string($choice) || !isset($spec['choice']) || !in_array($choice,$spec['choice'])) throw new RuntimeException('请选择有效的技能状态。');
            switchclbskills($skill,$choice,$p);
        }
        $GLOBALS['opendialog']='skillpage'; return;
    }
    if (preg_match('/^rest[123]?$/D',$command)) throw new RuntimeException('无需进入休养状态，等待时生命和体力会自然恢复。也可以使用补给，或找看护女仆包扎。');
    throw new RuntimeException('这里没有这项操作，请返回行动菜单。');
}
