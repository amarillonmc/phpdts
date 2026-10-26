<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_button($label,$command,$args=array(),$fields=array()) {
    return '<button type="button" data-el-command="'.elevator_html($command).'" data-el-args="'.elevator_html(json_encode($args,JSON_UNESCAPED_UNICODE)).'" data-el-fields="'.elevator_html(json_encode($fields)).'">'.elevator_html($label).'</button>';
}
function elevator_native_button($label,$command,$data=array()) {
    return '<button type="button" data-el-native="'.elevator_html($command).'" data-el-args="'.elevator_html(json_encode($data)).'">'.elevator_html($label).'</button>';
}
function elevator_item_label($item) {
    global $iteminfo,$itemspkinfo,$cskills;
    $kind=$iteminfo[$item['itmk']]??$item['itmk'];
    $extra='';
    foreach (str_split($item['itmsk']??'') as $c) if (isset($itemspkinfo[$c])) $extra.=' '.$itemspkinfo[$c];
    if ($item['itmk']==='R') $extra='蓝图';
    if ($item['itmk']==='VS') $extra='技能：'.($cskills[$item['itmsk']]['name']??'未命名笔记');
    return elevator_html($item['itm']).' <small>'.elevator_html($kind).' · 效果 '.(int)$item['itme'].' · 数量/耐久 '.elevator_html($item['itms']).elevator_html($extra).'</small>';
}
function elevator_panel($p,$e,$at) {
    global $gamevars,$db,$tablepre;
    $p['clbpara']=elevator_decode($p['clbpara']);
    $s=$p['clbpara']['elevator']; $floor=$s['in']?$e['floor']:$s['floor'];
    $state=array('idle'=>'待机','doors'=>'开门','closing'=>'关门中','travel'=>'运行中','hold'=>'异象停梯','ended'=>'行程结束')[$e['state']];
    $deadline=$e['state']==='travel'?$e['travel_end']:(in_array($e['state'],array('doors','closing'),true)?$e['door_deadline']:0);
    $players=elevator_read_players(); $active=array_filter($players,'elevator_active');
    $html='<div class="el-panel" data-el-pls="'.(int)$p['pls'].'" data-el-seq="'.(int)$s['seq'].'" data-el-csrf="'.elevator_html($s['csrf']).'">';
    $html.='<nav class="el-quicknav"><a href="#elevator-panel">电梯</a><a href="#main">装备与战斗</a><a href="#cmd">操作与拾取</a><a href="#log">行动日志</a></nav>';
    $html.='<div class="el-heading"><div><span class="el-kicker">ELEVATOR / 常磐夹层旅行</span><h2>'.elevator_html(elevator_floor_label($floor)).'</h2></div><div class="el-badge">'.($s['in']?'车厢内':'楼层探索').' · '.$state.'</div></div>';
    $html.='<div class="el-stats"><span>生命 <b>'.(int)$p['hp'].' / '.(int)$p['mhp'].'</b></span><span>体力 <b>'.(int)$p['sp'].' / '.(int)$p['msp'].'</b></span><span>现金 <b>'.(int)$p['money'].'</b></span><span>复活 <b>'.(int)$s['revive'].' 次</b></span><span>同行 <b>'.count($active).' / 8</b></span><span>本局剩余 <b data-el-countdown="'.max(0,$e['start_time']+$e['limit']-$at).'">'.max(0,$e['start_time']+$e['limit']-$at).' 秒</b></span></div>';
    if (!elevator_active($p)) return $html.'<p>行程已结算。<a href="end.php">查看结果与名人堂</a></p></div>';
    $safe=$s['in'] || $floor===-8 || ($s['protected'] && (!empty($s['board_req']) || $s['protect_until']>$at));
    $html.='<div class="el-budget"><span>暴露预算：<b>'.(int)$s['budget'].' 秒</b> · '.($safe?'当前暂停':'耗尽后每秒失去'.elevator_config('erosion').'生命').'</span><meter min="0" max="'.elevator_budget($floor).'" value="'.(int)$s['budget'].'"></meter></div>';
    $html.='<div class="el-columns"><section class="el-card"><h3>共用电梯</h3><p>停靠 '.elevator_html(elevator_floor_label($e['floor'])).' · '.$state;
    if ($deadline) $html.=' · <b data-el-countdown="'.max(0,$deadline-$at).'">'.max(0,$deadline-$at).' 秒</b>';
    if ($e['route']) $html.='<br>已承诺：'.elevator_html(elevator_floor_label($e['route'][0]['floor']));
    $html.='</p>';
    if ($e['state']==='hold') $html.='<p class="el-warning">车门锁定，请响应下方异象。门外乘客仍可探索和登记之外的操作。</p>';
    elseif ($s['in']) {
        $html.='<div class="el-buttons">';
        foreach (array(1,2,3,4,5,6,7,-1,-2,-3,-4,-5,-6,-7,-8) as $f) $html.=elevator_button(($f>0?'+':'').$f,'elevator_target',array('value'=>$f));
        $html.='</div>';
        if ($e['phase']==='endless') {
            $next=$e['floor']+$e['endless_dir']; if (!$next) $next=$e['endless_dir'];
            $html.='<p>'.elevator_button('继续：'.elevator_floor_label($next),'elevator_target',array('value'=>$next)).' <input id="el-destination" type="number" aria-label="自选目的楼层" placeholder="目的楼层"> '.elevator_button('按下楼层','elevator_target',array(),array('value'=>'el-destination')).'</p>';
        }
        if ($e['state']==='doors') $html.='<p>'.elevator_button('走出电梯','elevator_leave').'</p>';
    } elseif ((int)$p['pls']===elevator_pls($floor)) {
        $html.='<p>'.elevator_button(!empty($s['board_req'])?'已登记 · 继续候乘':'呼叫并登记自动登梯','elevator_call');
        if ($e['state']==='doors' && $e['floor']===$floor) $html.=elevator_button('现在登梯','elevator_board');
        $html.='</p><p class="el-muted">只有留在本层电梯口并登记候乘，开门时才会自动登梯。离开入口会取消登记。</p>';
    } else $html.='<p>'.elevator_native_button('返回电梯口','move',array('moveto'=>elevator_pls($floor))).'</p>';
    if ($e['reqs']) {
        $labels=array(); foreach ($e['reqs'] as $r) $labels[]=elevator_floor_label($r['floor']).($r['via']==='cabin'?'〔厢内〕':'〔呼叫〕');
        $html.='<p class="el-muted">等待安排：'.elevator_html(implode(' → ',$labels)).'</p>';
    }
    $html.='</section><section class="el-card">';
    if ($s['in']) {
        $html.='<div class="el-scene el-scene-0" role="img" aria-label="亮着暖灯的电梯车厢"></div><h3>留灯的地方</h3><p>门外的楼层一直在变。车厢里的灯、保管箱和行商，仍会陪你等到下一站。</p>';
        $names=array(); foreach ($active as $other) $names[]=elevator_html($other['name']).' · '.($other['clbpara']['elevator']['in']?'同车':elevator_html(elevator_floor_label($other['clbpara']['elevator']['floor'])));
        $html.='<p class="el-muted">'.implode('<br>',$names).'</p>';
    } elseif ($floor===-8) {
        $html.='<img class="el-fireseed" src="gamedata/ruleset/ELEVATOR/img/fireseed.png" alt="大种火在灰色会客厅留着灯与空座位"><h3>灰色会客厅</h3><p>她把空椅子向你这边挪了挪。这里没有敌人，也没有侵蚀。请慢慢听。</p>';
    } else {
        $data=elevator_floor_read($floor); $index=(int)$p['pls']-elevator_pls($floor); $maps=elevator_maps();
        $map=isset($data['tpls'][$index-1])?$maps[$data['tpls'][$index-1]]:null;
        $html.='<div class="el-scene el-scene-'.($map?$map['pool']:0).'" role="img" aria-label="'.elevator_html($map?$map['name']:'电梯入口').'"></div>';
        $html.='<h3>'.elevator_html($map?$map['name']:'这一层').'</h3><p>'.elevator_html($map?$map['text']:'电梯口的光还在。选一个地点，记住返回的路。').'</p><div class="el-buttons">';
        foreach ($data['tpls'] as $i=>$tpl) {
            $loc=elevator_pls($floor,$i+1); if ($loc===(int)$p['pls']) continue;
            $html.=elevator_native_button($maps[$tpl]['name'],'move',array('moveto'=>$loc));
        }
        if ($index>0) $html.=elevator_native_button('搜索这里','search');
        $html.='</div>';
        if ($map) $html.=elevator_environment_panel($p,$data,$index);
        foreach ($data['objects'] as $o) if ($o['index']===$index) $html.=elevator_object_panel($p,$o['id']);
    }
    $html.='</section></div>';
    if ($e['event']) $html.=elevator_event_panel($p,$e,$at);
    if (!$s['in'] && $floor===-8) $html.=elevator_fireseed_panel($p);
    if ($e['phase']==='finite' && ($s['in'] || (int)$p['pls']===elevator_pls($floor)) && ($e['endless_vote'] || in_array($floor,array(3,5,7,-3,-5,-7,-8),true))) {
        $html.='<div class="el-card">';
        if (in_array($floor,array(3,5,7,-3,-5,-7,-8),true)) $html.=elevator_button('在这里脱出并结算','elevator_escape');
        if ($e['endless_vote']) {
            $v=$e['endless_vote']; $html.='<h3>无尽提案 · 还有 '.max(0,$v['deadline']-$at).' 秒</h3><p>同意者留下，反对和未回应者胜利离场。投票结束后不能普通脱出。</p>';
            $html.=elevator_button('我愿意继续','elevator_vote',array('value'=>1)).elevator_button('到这里就好','elevator_vote',array('value'=>-1));
            $html.='<p>你的选择：'.(($v['opts'][$p['pid']]??0)===1?'继续':(($v['opts'][$p['pid']]??0)===-1?'离开':'尚未回应')).'</p>';
        } elseif (in_array($floor,array(7,-7,-8),true)) $html.=elevator_button('发起无尽行程提案','elevator_vote',array('value'=>1));
        $html.='</div>';
    } elseif ($e['endless_vote']) {
        $html.='<div class="el-card"><h3>终点的无尽提案</h3><p>你可在当前地点回应。未回应者将在投票结束时胜利离场。</p>'.elevator_button('继续','elevator_vote',array('value'=>1)).elevator_button('离开','elevator_vote',array('value'=>-1)).'</div>';
    }
    $html.=elevator_recipe_panel($p,$e);
    if ($s['in'] && $e['state']!=='hold') $html.=elevator_cabin_panel($p,$e,$at,$players);
    if ($s['in']) $html.='<details id="el-honors"><summary>电梯荣誉墙</summary>'.elevator_honor_wall().'</details>';
    if ($s['pending']) $html.='<p class="el-warning">还有 '.count($s['pending']).' 件待领物品。'.elevator_button('领取下一件','elevator_claim').'</p>';
    $html.='<details id="el-notes"><summary>行程笔记与广播</summary><div class="el-columns"><div>';
    foreach (array_reverse($s['messages']??array()) as $msg) $html.='<p>'.elevator_html($msg).'</p>';
    $html.='</div><div>'; foreach (array_reverse($e['news']) as $msg) $html.='<p>'.date('H:i:s',$msg['time']).' · '.elevator_html($msg['text']).'</p>';
    $html.='</div></div></details>';
    if (!empty($s['dictionary'])) $html.='<details id="el-dictionary"><summary>种火语字母表</summary><p class="el-cipher">a b c d e f g h i j k l m n o p q r s t u v w x y z é è œ<br>'.elevator_html(elevator_cipher('a b c d e f g h i j k l m n o p q r s t u v w x y z é è œ')).'</p></details>';
    $html.='<details id="el-help"><summary>乘客须知</summary>'.elevator_help().'<p><a href="end.php">查看名人堂</a> · '.elevator_button('提前结束本局（参与奖励）','elevator_quit').'</p></details>';
    return $html.'</div>';
}
function elevator_environment_panel($p,$data,$index) {
    $s=$p['clbpara']['elevator']; $pr=$s['floors'][$s['floor']]['environment'][$index]??array();
    if (!empty($pr['done'])) return '<p class="el-muted">本处场景已查看。</p>';
    $tpl=$data['tpls'][$index-1]; $html='<div class="el-actions">';
    if ($tpl===507) $html.=elevator_button('向左','elevator_environment',array('value'=>'left')).elevator_button('向右','elevator_environment',array('value'=>'right'));
    elseif ($tpl===604) $html.=elevator_button('使用钥匙','elevator_environment',array('value'=>'unlock')).elevator_button('敲门（10秒预算）','elevator_environment',array('value'=>'knock'));
    elseif ($s['floor']>7 && !empty($pr['word'])) $html.='<p class="el-cipher">'.elevator_html(elevator_cipher($pr['word'])).'</p><input id="el-word" placeholder="输入英文" aria-label="口哨的英文">'.elevator_button('回答','elevator_environment',array(),array('value'=>'el-word'));
    else $html.=elevator_button('查看场景','elevator_environment',array('value'=>'inspect'));
    return $html.'</div>';
}
function elevator_object_panel($p,$id) {
    $o=elevator_objects()[$id]; $s=$p['clbpara']['elevator'];
    if (!empty($s['floors'][$s['floor']]['objects'][$id]['done'])) return '<p class="el-muted">'.elevator_html($o[0]).' · 已记入笔记</p>';
    $html='<div class="el-object"><h4>'.elevator_html($o[0]).'</h4><p>'.elevator_html($o[2]).'</p>';
    $options=array('cat'=>array('feed'=>'交出猫粮'),'catloot'=>array('feed'=>'交出猫粮'),'student'=>array('talk'=>'继续交谈'),
        'guard'=>array('talk'=>'出示证件'),'projection'=>array('touch'=>'触碰投影'),'scavenger'=>array('info'=>'询问情报','trade'=>'两份回血食物换武器'),
        'data'=>array('yes'=>'我是','unsure'=>'我也不确定'),'watch'=>array('listen'=>'听歌 / 检查是否唱完'));
    if (in_array($o[3],array('turret','burnt','soldier','tap','mirror','error','half'),true)) $options[$o[3]]=array('fight'=>$o[3]==='turret'?'从北侧接近炮台':'靠近挑战');
    if ($o[3]==='forest') $html.='<input id="el-answer-'.$id.'" aria-label="种火语答案" placeholder="按字母表输入种火语">'.elevator_button('吹出口哨','elevator_object',array('object'=>$id,'value'=>'answer'),array('answer'=>'el-answer-'.$id));
    else foreach ($options[$o[3]]??array('talk'=>'靠近交谈') as $choice=>$label) $html.=elevator_button($label,'elevator_object',array('object'=>$id,'value'=>$choice));
    $html.=elevator_button($o[3]==='turret'?'从南侧绕行':'离开','elevator_object',array('object'=>$id,'value'=>'leave'));
    return $html.'</div>';
}
function elevator_event_panel($p,$e,$at) {
    $v=$e['event']; $type=$v['type'];
    $html='<section class="el-card el-event"><h3>'.elevator_html(elevator_intrusion_names()[$type]).'</h3><p>'.($v['stage']==='announce'?'广播预告':'异象已进入车厢').' · <b data-el-countdown="'.max(0,$v['deadline']-$at).'">'.max(0,$v['deadline']-$at).' 秒</b></p>';
    if (!$p['clbpara']['elevator']['in']) return $html.'<p>你在门外。异象结束后车门会重新打开。</p></section>';
    $copy=array('echo'=>'2–3只回响正在逼近。你们可以分别攻击，同车乘客共享胜利奖励。',
        'watcher'=>'缝隙后有一只眼睛。观察可能得到情报；伸手会暂时模糊视线，也可能摸到物品。',
        'rule'=>'规则要求一名自愿留在本层的人，或一件唯一物品。无人响应时，执行者会进入车厢。',
        'fans'=>'应援声围住了你。摆个姿势，或者告诉它们这不是你的舞台。',
        'fragment'=>'敲碎正在凝结的碎片。每人每秒可敲击一次；失败会短暂眩晕。',
        'overflow'=>'银色潮水挟着物品涌进来。捞取、驱逐，或等它过去；忽视会拖慢下一段行程。',
        'passenger'=>'一位没有重量的乘客，想告诉你下一站的事。');
    $html.='<p>'.$copy[$type].'</p>';
    $options=array('echo'=>array(),'watcher'=>array('look'=>'观察','away'=>'移开目光','touch'=>'伸手'),
        'rule'=>array('volunteer'=>'自愿留在本层','offer'=>'献出身上的唯一物品','refuse'=>'面对执行者'),
        'fans'=>array('pose'=>'摆姿势','refuse'=>'婉拒'),'fragment'=>array('hit'=>'敲击碎片'),
        'overflow'=>array('take'=>'捞取','expel'=>'驱逐','ignore'=>'等待'),'passenger'=>array('listen'=>'听它说')); 
    if (!$v['npcs']) foreach ($options[$type] as $choice=>$label) $html.=elevator_button($label,'elevator_event',array('value'=>$choice,'event'=>$v['id']));
    if ($type==='fragment') $html.='<p>已敲击 '.$v['actions'].' / '.max(3,count($v['participants'])*2).' 次</p>';
    foreach ($v['npcs'] as $id) {
        $npc=fetch_playerdata_by_pid($id);
        if ($npc && $npc['hp']>0) $html.=elevator_button($npc['name'].' · HP '.$npc['hp'],'elevator_fight',array('value'=>$id));
    }
    return $html.'</section>';
}
function elevator_fireseed_panel($p) {
    $s=$p['clbpara']['elevator']; $step=(int)($s['riddle_step']??0); $dictionary=!empty($s['dictionary']);
    $html='<section class="el-card"><h3>旧梦的三个问题</h3>';
    if ($step>=3) return $html.'<p>三个问题已经听完。无论对错，离开的门都为你敞开。你也可以发起继续向下的无尽提案。</p></section>';
    $q=elevator_questions()[$step]; $html.='<p class="el-cipher">'.elevator_html(elevator_cipher($q[0])).'</p>';
    if ($dictionary) $html.='<p>'.elevator_html($q[0]).'</p>';
    foreach ($q[1] as $i=>$answer) $html.='<p>'.elevator_button(chr(65+$i).' · '.($dictionary?$answer:elevator_cipher($answer)),'elevator_riddle',array('value'=>$i)).'</p>';
    return $html.'<p class="el-muted">第 '.($step+1).' / 3 问。密码手册可提供英文转译；猜错仍然可以离开。</p></section>';
}
function elevator_recipe_panel($p,$e) {
    $s=$p['clbpara']['elevator']; $view=$s['view']??array(); $html='';
    if (($view['kind']??'')==='relic') {
        $slot=(int)$view['slot']; $item=elevator_slot($p,$slot);
        if ($item['itmk']==='ER' && !empty($item['itmpara']['rr_choices'])) {
            $html.='<section class="el-card"><h3>打开遗物 · 六选一</h3><p>这些选项属于这件遗物，重新打开也不会改变。</p><div class="el-goods">';
            foreach ($item['itmpara']['rr_choices'] as $i=>$choice) $html.='<div>'.elevator_item_label($choice).'<br>'.elevator_button('选择这一件','elevator_relic',array('slot'=>$slot,'token'=>elevator_slot_token($p,$slot),'value'=>$i)).'</div>';
            $html.='</div></section>';
        }
    }
    $available=array();
    foreach ($p['clbpara']['rr_learned']??array() as $key=>$v) if ($v && ($p['clbpara']['rr_learned_round']??'')===$e['round']) $available[$key]=-1;
    $buttons='';
    for ($slot=0;$slot<=6;$slot++) {
        $item=elevator_slot($p,$slot); if (empty($item['itms'])) continue;
        if ($item['itmk']==='R') $available[(int)$item['itmsk']]=$slot;
        if ($item['itmk']==='ER' || $item['itmk']==='R') $buttons.=elevator_native_button('展开：'.$item['itm'],'itm'.$slot);
    }
    if (!$available && !$buttons) return $html;
    $html.='<details id="el-recipes"'.(($view['kind']??'')==='recipes'?' open':'').'><summary>配方与遗物</summary><div class="el-buttons">'.$buttons.'</div>';
    foreach ($available as $key=>$slot) {
        $recipe=rr_get_recipe($key); if (!$recipe) continue;
        $learned=!empty($p['clbpara']['rr_learned'][$key]); if ($learned) $slot=-1;
        $html.='<div class="el-object"><h4>'.elevator_item_label($recipe['result']).'</h4><p>素材：'; $list=array();
        for ($i=1;$i<=5;$i++) if (isset($recipe['stuff'.$i])) {
            $part=$recipe['stuff'.$i]; $label=$part['name']??(array('H'=>'任意恢复品','W'=>'任意武器','D'=>'任意防具')[$part['kind'][0]]??'任意装备');
            if (isset($part['contains'])) $label.='（名称含'.$part['contains'].'）';
            $list[]=elevator_html($label).' × '.$part['count'];
        }
        $html.=implode(' + ',$list).'</p><p>'.($learned?'本局已学会':($recipe['extra']['learnable']?'首次合成后本局学会，保留蓝图':'一次性蓝图，合成会消耗')).'。近战武器、枪械与防具整件消耗；可叠加素材按份数消耗。</p>';
        $html.=elevator_button('合成','elevator_craft',array('value'=>$key,'slot'=>$slot,'token'=>$slot>=0?elevator_slot_token($p,$slot):''));
        $html.='</div>';
    }
    return $html.'</details>';
}
function elevator_cabin_panel($p,$e,$at,$players) {
    $merchant=elevator_merchant($e,$at); $html='';
    if ($merchant && $merchant['hp']>0 && empty($merchant['clbpara']['hostile'])) {
        $html.='<details id="el-shop"><summary>'.elevator_html($merchant['name']).' · 商品与情报</summary><p>每180秒换货。库存还会保留 '.max(0,$merchant['clbpara']['stock_until']-$at).' 秒。</p><div class="el-goods">';
        foreach ($merchant['clbpara']['stock'] as $i=>$row) if ($row['count']>0) {
            $price=(int)ceil($row['price']*(!empty($p['clbpara']['elevator']['discount'])?.8:1));
            $html.='<div>'.elevator_item_label($row['item']).'<p>剩余 '.$row['count'].' · '.$price.'现金</p>'.elevator_button('购买','elevator_buy',array('value'=>$i,'stock'=>$merchant['clbpara']['stock_id'])).'</div>';
        }
        $html.='</div><p>'.elevator_button('买情报 · 30','elevator_shop',array('value'=>'info')).elevator_button('小费 · 10（下件八折）','elevator_shop',array('value'=>'tip')).'</p><details id="el-shop-attack"><summary>与行商交战</summary><p class="el-warning">攻击后，他会在本局永久停止营业并反击。</p>'.elevator_button('确认攻击行商','elevator_shop',array('value'=>'attack')).'</details></details>';
        $html.='<details id="el-sell"><summary>出售整件物品</summary><p>下面的报价对应整格物品。确认出售后，整件交给行商。</p>';
        for ($slot=0;$slot<=6;$slot++) if (!empty($p['itms'.$slot])) $html.='<p>'.elevator_item_label(elevator_slot($p,$slot)).' '.elevator_button('整件出售 · '.elevator_sale_price(elevator_slot($p,$slot)).'现金','elevator_sell',array('slot'=>$slot,'token'=>elevator_slot_token($p,$slot))).'</p>';
        $html.='</details>';
    } elseif ($merchant && $merchant['hp']>0) $html.='<p>'.elevator_button('与敌对行商交战','elevator_fight',array('value'=>$merchant['pid'])).'</p>';
    $html.='<details id="el-storage"><summary>六格个人保管箱与乘客交易</summary><div class="el-goods">';
    foreach ($p['clbpara']['elevator']['box'] as $slot=>$item) $html.='<div>'.elevator_item_label($item).'<br>'.elevator_button('取回','elevator_withdraw',array('slot'=>$slot,'token'=>hash('sha256',json_encode($item,JSON_UNESCAPED_UNICODE)))).'</div>';
    $html.='</div><p>交易对象：<select id="el-buyer" aria-label="交易对象"><option value="0">选择同车乘客</option>';
    foreach ($players as $other) if ($other['pid']!=$p['pid'] && elevator_active($other) && $other['clbpara']['elevator']['in']) $html.='<option value="'.$other['pid'].'">'.elevator_html($other['name']).'</option>';
    $html.='</select> <input id="el-price" aria-label="报价现金" type="number" min="0" max="1000000" value="0"> 现金（赠送填0）</p><div class="el-goods">';
    for ($slot=0;$slot<=6;$slot++) if (!empty($p['itms'.$slot])) {
        $args=array('slot'=>$slot,'token'=>elevator_slot_token($p,$slot));
        $html.='<div>'.elevator_item_label(elevator_slot($p,$slot)).'<br>'.elevator_button('存入保管箱','elevator_store',$args).elevator_button('向所选乘客报价','elevator_offer',$args,array('target'=>'el-buyer','price'=>'el-price')).'</div>';
    }
    $html.='</div><p class="el-muted">报价保留120秒，接收人同意后才交付；离开车厢或发生入侵会退还物品。</p>';
    if (!empty($p['clbpara']['elevator']['trade'])) $html.='<p>你有一笔等待回应的报价。'.elevator_button('撤回并取回物品','elevator_cancel').'</p>';
    foreach ($players as $other) {
        $offer=$other['clbpara']['elevator']['trade']??null;
        if ($offer && $offer['target']===$p['pid'] && $offer['until']>$at) $html.='<div class="el-object">'.elevator_html($other['name']).' 报价：'.elevator_item_label($offer['item']).' · '.$offer['price'].'现金 '.elevator_button('接受报价','elevator_accept',array('seller'=>$other['pid'],'value'=>$offer['id'])).'</div>';
    }
    return $html.'</details>';
}
function elevator_help() {
    return '<p>1–8人共享一部电梯，不进行玩家间战斗。开局前10分钟可加入，每个账户每局只领一次起装。</p><p>车厢按钮优先于层外呼叫；已经承诺的目的地不会被改写。开门8秒、关门5秒，运行每层3秒。只有登记且仍站在对应电梯口的乘客才会自动登梯。</p><p>每层有150至100秒的个人暴露预算。离线期间暂停；入口初始保护60秒。返回电梯口呼叫候乘可暂停，离开入口或电梯离站后重新计时。每局所有来源合计只有一次复活，复活到电梯口并恢复30%生命。</p><p>±3、±5、±7层可脱出；−8层听完三个问题即可离开，答错也无妨。终点可提出无尽提案，120秒后愿意者留下，其余胜利离场。有限段最长60分钟，无尽段整局最长90分钟。</p><p>每层探索两个不同地点可获六选一遗物。配方、搜尸、背包和战斗使用原有操作；配方专用合成位于本面板。车厢的行商、保管箱与双方同意的交易可帮助你准备下一程。</p>';
}
function elevator_page_start($title) {
    header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.elevator_html($title).' · 常磐大逃杀</title><link rel="stylesheet" href="gamedata/ruleset/ELEVATOR/elevator.css"></head><body class="el-page"><main class="el-shell"><nav><a href="index.php">返回首页</a> <a href="game.php">当前行程</a></nav>';
}
function elevator_join_page() {
    global $gamevars,$gamestate,$now,$db,$tablepre;
    try {
        $user=elevator_authorized(); if (!elevator_lock()) throw new RuntimeException('房间繁忙，请稍后重试。');
        elevator_request_tick(); $e=$gamevars['elevator']??array();
        foreach (elevator_read_players() as $p) if ($p['name']===$user['username']) { header('Location: '.(elevator_active($p)?'game.php':'end.php')); return; }
        if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
            if (!$e || !is_string($_POST['el_join_token']??null) || !hash_equals(elevator_join_csrf($user,$e),$_POST['el_join_token'])) throw new RuntimeException('页面已过期，请刷新后加入。');
            if (!is_scalar($_POST['el_club']??null)) throw new RuntimeException('请选择起始路线。');
            elevator_join($user,(int)$_POST['el_club'],$now); header('Location: game.php'); return;
        }
    } catch (RuntimeException $ex) { $error=$ex->getMessage(); }
    elevator_page_start('ELEVATOR · 上车');
    echo '<div class="el-hero"><div class="el-scene el-scene-0"></div><span class="el-kicker">ELEVATOR</span><h1>门打开时，<br>世界换了一层。</h1><p>一部电梯，八位乘客。你可以在合适的一站离开，也可以一直往灯光照不到的地方走。</p></div>';
    if (!empty($error)) echo '<p class="el-warning">'.elevator_html($error).'</p>';
    if (!empty($e) && $gamestate>=20 && $e['phase']==='finite' && $e['state']!=='ended' && $now<$e['start_time']+600 && $e['joined']<8) {
        echo '<section class="el-card"><h2>选一条起始路线</h2><p>生命/体力400，制式武器、外套、急救包与饮料各8份、猫粮及300现金。所有路线都可以探索、合成与解谜。</p><form method="post"><input type="hidden" name="el_join_token" value="'.elevator_html(elevator_join_csrf($user,$e)).'"><div class="el-buttons">';
        foreach (array(1=>'撬棍 · 街头霸王',2=>'短刀 · 见敌必斩',3=>'飞镖 · 灌篮高手',4=>'手枪 · 狙击鹰眼',5=>'爆破包 · 拆弹专家',9=>'音叉 · 超能力者') as $id=>$label) echo '<button type="submit" name="el_club" value="'.$id.'">'.$label.'</button>';
        echo '</div></form><p>已领取起装 '.$e['joined'].' / 8 人 · 加入窗口还剩 '.max(0,$e['start_time']+600-$now).' 秒</p></section>';
    } else echo '<section class="el-card"><h2>本班已停止登梯</h2><p>请等待下一局，或从大厅进入另一班 ELEVATOR。</p></section>';
    echo '<section class="el-card"><h2>乘客须知</h2>'.elevator_help().'</section></main></body></html>';
}
function elevator_ending_page() {
    global $gamevars,$now,$db,$gtablepre;
    try { $user=elevator_authorized(); if (!elevator_lock()) throw new RuntimeException('请稍后重试。'); elevator_request_tick(); }
    catch (RuntimeException $ex) { elevator_page_start('行程记录'); echo '<p>'.elevator_html($ex->getMessage()).'</p></main></body></html>'; return; }
    elevator_page_start('行程记录');
    echo '<span class="el-kicker">ELEVATOR / JOURNAL</span><h1>门关上以后</h1>';
    $q=$db->query('SELECT * FROM '.$gtablepre.'elevator_results WHERE username='.elevator_sql($user['username']).' ORDER BY finished_at DESC LIMIT 1'); $result=$db->fetch_array($q);
    if ($result) {
        $d=elevator_decode($result['details']);
        echo '<section class="el-card"><h2>'.array('escape'=>'你走出了那扇门','dead'=>'灯光仍为下一位亮着','quit'=>'这段行程暂且到这里')[$result['result']].'</h2><p>最深到达 '.(int)$result['depth'].' 层 · '.($result['phase']==='endless'?'无尽行程':'有限行程').' · 基础奖励 '.(int)$result['reward'].' 切糕'.($result['paid']?'（已入账）':'（等待结算）').'</p><p>新达成的成就奖励另计入账户。'.elevator_html(date('Y-m-d H:i:s',$result['finished_at'])).'</p></section>';
    } else echo '<p>这里还没有你的离场记录。<a href="game.php">返回当前行程</a></p>';
    echo elevator_honor_wall();
    echo '<section class="el-card"><h2>留在行程里的名字</h2><p>电梯常客 · 帧间行者 · 温柔的见证者 · 深渊来客 · 阁楼居民</p><p>荣誉称号会在符合条件的行程结算后加入账户，可从个人设置中选用。</p></section></main></body></html>';
}
function elevator_honor_wall() {
    global $db,$gtablepre;
    $html='';
    foreach (array(1=>'向上 · 阁楼之外',-1=>'向下 · 钻探深处') as $dir=>$label) {
        $html.='<section class="el-card"><h2>'.elevator_html($label).'</h2><p class="el-muted">无尽深度优先，同深度按首次完成两处探索的用时排列。</p><table><thead><tr><th>乘客</th><th>深度</th><th>用时</th><th>到访日</th></tr></thead><tbody>';
        $q=$db->query("SELECT username,depth,depth_time,finished_at FROM {$gtablepre}elevator_results WHERE phase='endless' AND direction=$dir ORDER BY depth DESC,depth_time ASC,finished_at ASC LIMIT 20");
        while ($r=$db->fetch_array($q)) $html.='<tr><td>'.elevator_html($r['username']).'</td><td>'.(int)$r['depth'].'</td><td>'.(int)$r['depth_time'].' 秒</td><td>'.date('Y-m-d',$r['finished_at']).'</td></tr>';
        $html.='</tbody></table></section>';
    }
    return $html;
}
