<?php
if(!defined('IN_GAME')) exit('Access Denied');

function another_ui_open_form($action,$fields=array(),$class='')
{
    $fields=array_merge(array('action'=>$action,'seq'=>$GLOBALS['another_ui_seq'],'csrf'=>$GLOBALS['another_ui_csrf']),$fields);
    $html='<form method="post" action="command.php" data-another-action class="'.another_html($class).'">';
    foreach($fields as $key=>$value) $html.='<input type="hidden" name="'.another_html($key).'" value="'.another_html($value).'">';
    return $html;
}
function another_ui_action($action,$label,$fields=array(),$class='',$disabled=false)
{
    return another_ui_open_form($action,$fields,'an-inline').'<button type="submit" class="'.another_html($class).'"'.($disabled?' disabled':'').'>'.another_html($label).'</button></form>';
}
function another_ui_time($seconds)
{
    $seconds=max(0,(int)$seconds);
    return sprintf('%02d:%02d',floor($seconds/60),$seconds%60);
}
function another_ui_preset($name)
{
    $trees=$name==='support'?array('P','C','I'):($name==='escape'?array('A','E','L'):array('S','E','A'));
    $points=array();
    foreach(another_talent_definitions() as $id=>$def) if(in_array($def['tree'],$trees,true)) $points[$id]=$def['max'];
    if($name==='support') $points['E1']=5;
    elseif($name==='escape') { $points['P1']=3; $points['P2']=1; $points['C2']=1; }
    else { $points['P1']=3; $points['P2']=1; $points['L4']=1; }
    return $points;
}
function another_ui_item_line($item)
{
    return '<strong>'.another_html($item['name']).'</strong><span class="an-count">×'.(int)$item['count'].'</span><small>'.another_html(another_item_description($item)).'</small>';
}
function another_ui_portrait($p)
{
    if($p['type']) {
        $role=isset($p['clbpara']['another']['npc_role'])?$p['clbpara']['another']['npc_role']:'grunt';
        if(!in_array($role,array('grunt','elite','hunter','poisoner','engineer','mirror'),true)) $role='grunt';
        return 'gamedata/ruleset/ANOTHER/img/'.$role.'.svg';
    }
    $gd=isset($p['gd'])&&$p['gd']==='f'?'f':'m';
    return 'img/'.$gd.'_'.max(0,(int)$p['icon']).'.gif';
}
function another_ui_join($csrf,$room,$error)
{
    $cfg=another_config(); $defs=another_talent_definitions();
    $names=array('S'=>'力量','P'=>'洞察','E'=>'耐久','C'=>'魅力','I'=>'智力','A'=>'敏捷','L'=>'运气');
    ob_start();
    echo '<section class="an-panel an-intro"><div class="an-kicker">载入目的地异常</div><h1>另一场对局，另一条归路。</h1>';
    foreach($cfg['story']['intro'] as $paragraph) echo '<p>'.another_html($paragraph).'</p>';
    echo '<div class="an-callout">普通幸存与异常终止均按队伍获胜。每个角色35点天赋；升级照常增加属性。双控的两人各有装备、金钱、REP与行动状态，后台角色仍会遇袭并反击。</div></section>';
    if($error!=='') echo '<div class="an-error" role="alert">'.another_html($error).'</div>';
    if(!empty($room['entry_closed']) || !empty($room['result'])) { echo '<section class="an-panel"><h2>本局已停止入场</h2><p>可以返回房间列表等待下一局。</p><a href="index.php">返回房间</a></section>'; return ob_get_clean(); }
    echo '<form method="post" action="valid.php" id="an-join"><input type="hidden" name="csrf" value="'.another_html($csrf).'">';
    echo '<section class="an-panel"><h2>选择操纵方式</h2><div class="an-control-choice"><label><input type="radio" name="controls" value="1" checked> 单控 · 一个角色</label><label><input type="radio" name="controls" value="2"> 双控 · 两个完整角色</label></div><p class="an-muted">入场后不能更改配点或增添角色。一个角色被淘汰后，仍可继续操纵另一人。</p></section>';
    for($slot=1;$slot<=2;$slot++) {
        $preset=another_ui_preset($slot===1?'melee':'support');
        echo '<section class="an-panel an-build" data-build="'.$slot.'"'.($slot===2?' hidden':'').'><div class="an-panel-heading"><h2>角色 '.$slot.' 的天赋</h2><strong class="an-budget" data-budget="'.$slot.'">35 / 35</strong></div>';
        echo '<div class="an-preset-row"><span>快速配点</span><button type="button" data-preset="melee" data-build-target="'.$slot.'">近战生存</button><button type="button" data-preset="support" data-build-target="'.$slot.'">射击支援</button><button type="button" data-preset="escape" data-build-target="'.$slot.'">灵动脱出</button><button type="button" data-preset="clear" data-build-target="'.$slot.'">清空</button></div>';
        echo '<p class="an-muted">每树普通天赋投入9点后可点终极。预设只是起点，可以自由调整。</p><div class="an-trees">';
        foreach($names as $tree=>$name) {
            echo '<details class="an-tree" data-tree="'.$tree.'" id="build'.$slot.$tree.'" open><summary><b>'.$tree.'</b> '.$name.' <span data-tree-budget>0 / 10</span></summary>';
            foreach($defs as $id=>$def) if($def['tree']===$tree) {
                echo '<label class="an-talent'.(!empty($def['ultimate'])?' an-ultimate':'').'"><span><strong>'.another_html($def['name']).'</strong><small>'.another_html($def['text']).'</small></span><select name="talents'.$slot.'['.$id.']" data-talent="'.$id.'" data-tree="'.$tree.'" data-ultimate="'.(!empty($def['ultimate'])?'1':'0').'" aria-label="'.another_html($def['name']).'等级">';
                for($level=0;$level<=$def['max'];$level++) echo '<option value="'.$level.'"'.((isset($preset[$id])?$preset[$id]:0)===$level?' selected':'').'>'.$level.'</option>';
                echo '</select></label>';
            }
            echo '</details>';
        }
        echo '</div><p class="an-build-error" data-build-error aria-live="polite"></p></section>';
    }
    echo '<div class="an-join-submit"><button class="an-primary" type="submit">确认构筑，进入 AN:OTHER</button><span>入场后先拿中央摊位的武器，即可交战。</span></div></form>';
    return ob_get_clean();
}
function another_ui_map($cfg,$room,$actor)
{
    $current=(int)$actor['pls']; $closed=isset($room['closed'])?$room['closed']:array();
    $neighbors=$cfg['maps'][$current]['neighbors'];
    ob_start();
    echo '<details class="an-panel an-map-panel" id="an-full-map"><summary>湾城全图 <span>18个地点 · 沿连线移动</span></summary><div class="an-map-scroll"><svg viewBox="0 0 1240 640" role="img" aria-label="湾城地图，当前位置高亮，相邻安全地点可点击移动">';
    foreach($cfg['edges'] as $edge) {
        $a=$cfg['maps'][$edge[0]]; $b=$cfg['maps'][$edge[1]];
        echo '<line x1="'.$a['x'].'" y1="'.$a['y'].'" x2="'.$b['x'].'" y2="'.$b['y'].'" class="'.(in_array($edge[0],$closed,true)||in_array($edge[1],$closed,true)?'closed':'').'"/>';
    }
    foreach($cfg['maps'] as $id=>$map) {
        $blocked=in_array($id,$closed,true); $move=empty($room['result'])&&$actor['hp']>0&&!$blocked&&in_array($id,$neighbors,true);
        echo '<g class="an-map-node'.($id===$current?' current':'').($blocked?' closed':'').($move?' available':'').'"'.($move?' data-another-move="'.$id.'" role="button" tabindex="0" aria-label="移动到'.another_html($map['name']).'"':'').'><circle cx="'.$map['x'].'" cy="'.$map['y'].'" r="16"/><text x="'.$map['x'].'" y="'.($map['y']+5).'" class="an-node-id">'.sprintf('%02d',$id).'</text><text x="'.$map['x'].'" y="'.($map['y']+34).'">'.another_html($map['name']).'</text></g>';
    }
    echo '</svg></div><p class="an-muted">灰色为禁区，青色为可移动的相邻地点。地图展示路线，不显示尚未发现的角色或物资。</p></details>';
    return ob_get_clean();
}
function another_ui_inventory($actor,$alive)
{
    $catalog=another_item_catalog(); $cfg=another_items_config();
    ob_start(); echo '<section class="an-panel" id="an-inventory"><h2>装备与背包</h2><div class="an-inventory">';
    $slots=array('wep'=>'武器','arb'=>'身体','arh'=>'头部','ara'=>'手部','arf'=>'足部','art'=>'饰物',1=>'背包 1',2=>'背包 2',3=>'背包 3',4=>'背包 4',5=>'背包 5',6=>'背包 6');
    foreach($slots as $slot=>$label) {
        $item=another_item_get($actor,$slot); $present=another_item_present($item);
        if(!$present&&!is_int($slot)&&$slot!=='wep'&&$slot!=='arb') continue;
        echo '<div class="an-item"><span class="an-slot">'.$label.'</span>';
        if(!$present) { echo '<span class="an-muted">空</span></div>'; continue; }
        echo another_ui_item_line($item);
        if($alive) {
            $id=another_item_id($item); $def=isset($catalog[$id])?$catalog[$id]:array();
            echo '<div class="an-item-actions">';
            if(is_int($slot)) {
                if(in_array($item['kind'],array('WK','WG','WC','DB','DH','DA','DF','A'),true)) echo another_ui_action('equip','装备',array('slot'=>$slot));
                if(isset($def['hp'])||isset($def['sp'])||isset($def['bundle'])||$id==='antidote') echo another_ui_action('use',isset($def['bundle'])?'打开':'使用',array('slot'=>$slot),'an-primary');
                if(isset($def['trap'])) echo another_ui_action('plant','布设',array('slot'=>$slot));
            } else echo another_ui_action('unequip','卸下',array('slot'=>$slot));
            echo '<details id="item-actions-'.another_html($slot).'" class="an-mini-details"><summary>整备／处理</summary><div class="an-item-tools">';
            if($item['kind']==='WG') { echo another_ui_action('reload','补弹（弹药）',array('target'=>$slot)); echo another_ui_action('upgrade','组装升级（枪械部件）',array('target'=>$slot)); echo another_ui_action('upgrade','高阶组装（高阶组件）',array('target'=>$slot,'material'=>'high_part')); }
            if($item['kind']==='WK') {
                echo another_ui_action('sharpen','磨刀（磨刀石）',array('target'=>$slot)); echo another_ui_action('sharpen','高阶磨刀（高阶组件）',array('target'=>$slot,'material'=>'high_part'));
                echo another_ui_open_form('socket',array('target'=>$slot)).'<label>镶嵌属性 <select name="effect"><option value="poison">施毒</option><option value="burn">灼伤</option><option value="slow">迟缓</option><option value="pierce">穿甲</option></select></label><button>镶嵌（嵌芯）</button></form>';
            }
            if(in_array($item['kind'],array('WK','WG'),true)) echo another_ui_action('repair','维修（工具包）',array('target'=>$slot));
            if(substr($item['kind'],0,1)==='D') { echo another_ui_action('armor_upgrade','强化防具（补片）',array('target'=>$slot)); echo another_ui_action('armor_upgrade','高阶强化（高阶组件）',array('target'=>$slot,'material'=>'high_part')); }
            if(isset($def['hp'])||isset($def['sp'])) foreach(array('test_food'=>array('poison_test','查毒'),'clean_food'=>array('food_antidote','食品解毒'),'poison'=>array('poison','下毒')) as $action=>$tool) {
                $toolslot=another_item_find($actor,$tool[0]); echo another_ui_action($action,$tool[1],array('slot'=>$toolslot===false?0:$toolslot,'target'=>$slot),'',$toolslot===false);
            }
            echo another_ui_open_form('drop',array('slot'=>$slot)).'<label>放下数量 <input type="number" name="qty" value="'.(int)$item['count'].'" min="1" max="'.(int)$item['count'].'"></label><button>放到地上</button></form>';
            echo '</div></details></div>';
        }
        echo '</div>';
    }
    echo '</div><p class="an-muted">换装会交换背包与装备槽。投系使用成品；射系组装，斩系磨刀／镶嵌。对干净食品使用食品解毒剂会使其有毒。</p>';
    if($alive) {
        echo '<details id="an-crafting"><summary>使用整备零件合成</summary><div class="an-craft-list">';
        foreach($cfg['recipes'] as $recipe=>$parts) {
            $text=array(); foreach($parts as $part=>$count) $text[]=$catalog[$part]['name'].'×'.$count;
            echo '<div><span>'.another_html($catalog[$recipe]['name']).'<small>'.another_html(implode('、',$text)).'</small></span>'.another_ui_action('craft','合成',array('recipe'=>$recipe)).'</div>';
        }
        echo '</div></details>';
    }
    echo '</section>'; return ob_get_clean();
}
function another_ui_shop($actor,$room,$alive)
{
    $catalog=another_item_catalog(); $itemcfg=another_items_config(); ob_start();
    echo '<details class="an-panel" id="an-shop"><summary>商店与 REP 援助 <span>普通猎鹰'.(int)$itemcfg['falcon_seconds'].'秒 · 援助气球'.(int)$itemcfg['balloon_seconds'].'秒</span></summary>';
    echo '<p class="an-muted">实体商店在旧商业街；持对讲机可远购。卫星电话呼叫的气球停在呼叫地点，能被其他玩家发现、击落和争夺。</p><div class="an-shop-list">';
    foreach($catalog as $id=>$def) if(isset($def['money'])||isset($def['rep'])) {
        $rep=isset($def['rep']); $price=$def[$rep?'rep':'money'];
        if(!$rep&&another_talent($actor,'C2')) $price=max(1,(int)floor($price*(1-another_talent_value($actor,'C2')/100)));
        $stock=isset($room['stock'][$id])?$room['stock'][$id]:(isset($def['stock'])?$def['stock']:-1);
        echo '<div class="an-shop-item"><div><strong>'.another_html($def['name']).'</strong><small>每组 '.$def['count'].' 件 · '.$price.' '.($rep?'REP':'金钱').' · '.($stock<0?'持续供应':'库存 '.$stock).'</small><small>'.another_html(another_item_description(another_item_make($id))).'</small></div>';
        echo another_ui_open_form('buy',array('id'=>$id)).'<label class="an-sr-only">购买组数</label><input name="qty" type="number" value="1" min="1" max="9999" aria-label="'.another_html($def['name']).'购买组数">';
        if($rep) echo '<input type="hidden" name="channel" value="rep"><button'.(!$alive||another_item_find($actor,'satellite')===false?' disabled':'').'>REP呼叫</button>';
        else echo '<select name="channel" aria-label="配送方式"><option value="local"'.($actor['pls']!=1?' disabled':'').'>实体商店</option><option value="radio"'.($actor['pls']!=1?' selected':'').'>猎鹰配送</option></select><button'.(!$alive||($actor['pls']!=1&&another_item_find($actor,'radio')===false)?' disabled':'').'>购买</button>';
        echo '</form></div>';
    }
    echo '</div></details>'; return ob_get_clean();
}
function another_render_body($room,$players,$owner,$active_pid,$seq,$csrf,$now,$join=false,$error='')
{
    global $starttime;
    $GLOBALS['another_ui_seq']=$seq; $GLOBALS['another_ui_csrf']=$csrf;
    $cfg=another_config(); $started=isset($room['started_at'])?(int)$room['started_at']:(isset($starttime)?(int)$starttime:$now);
    $ended=!empty($room['result']); $elapsed=max(0,($ended&&isset($room['result']['time'])?(int)$room['result']['time']:$now)-$started);
    $owned=another_current_player_ids($players,$owner); $actor=isset($players[$active_pid])?$players[$active_pid]:null;
    $alive=$actor&&$actor['hp']>0&&!$ended;
    ob_start();
    echo '<div id="another-app" data-seq="'.(int)$seq.'" data-csrf="'.another_html($csrf).'" data-server-now="'.(int)$now.'" data-ended="'.($ended?'1':'0').'" data-join="'.($join?'1':'0').'">';
    echo '<header class="an-header"><a class="an-brand" href="game.php">AN<span>:</span>OTHER</a><div class="an-header-note">湾城复制地点 <span>连接不稳定 · 出口待响应</span></div><div class="an-clock">对局 <b data-elapsed="'.$elapsed.'">'.another_ui_time($elapsed).'</b><small>'.($ended?'对局已结束':'最迟 <span data-countdown="'.($started+$cfg['deadline']).'">'.another_ui_time($cfg['deadline']-$elapsed).'</span> 后收束').'</small></div><a href="index.php" class="an-home-link">房间</a></header>';
    echo '<div id="an-network" role="status" aria-live="polite"></div>';
    if($join||!$actor) { echo another_ui_join($csrf,$room,$error); echo '</div>'; return ob_get_clean(); }
    if($error!=='') echo '<div class="an-error" role="alert">'.another_html($error).'</div>';
    if($ended) {
        $result=$room['result']; $won=in_array((int)$active_pid,$result['winners'],true); $kind=$result['kind'];
        echo '<section class="an-panel an-result"><div class="an-kicker">对局记录已封存</div><h1>'.($won?($kind==='escape'?'异常终止 · 队伍脱出':'幸存脱出 · 队伍获胜'):($result['winners']?'本局已分出胜负':'无人优胜')).'</h1>';
        $winner_names=array(); foreach($result['winners'] as $pid) if(isset($players[$pid])) $winner_names[]=another_name($players[$pid]);
        if($winner_names) echo '<p>胜队：'.another_html(implode('、',$winner_names)).'</p>';
        foreach(isset($cfg['story'][$kind])?$cfg['story'][$kind]:$cfg['story']['timeout'] as $paragraph) echo '<p>'.another_html($paragraph).'</p>';
        echo '<a class="an-link-button" href="index.php">返回房间列表</a></section>';
    }
    echo '<div class="an-layout"><aside class="an-sidebar"><section class="an-panel"><h2>操纵角色</h2><div class="an-characters">';
    foreach($owned as $pid) {
        $p=$players[$pid]; echo '<div class="an-character'.($pid===$active_pid?' current':'').'"><img src="'.another_html(another_ui_portrait($p)).'" alt=""><div><strong>'.another_html(another_name($p)).'</strong><small>'.($p['hp']>0?'HP '.$p['hp'].' / '.another_max_hp($p):'已被淘汰').'</small><small>'.another_html($cfg['maps'][$p['pls']]['name']).'</small></div>';
        if($pid!==$active_pid) echo another_ui_action('switch','切换',array('target_pid'=>$pid),'',false); else echo '<span class="an-tag">当前</span>';
        echo '</div>';
    }
    $an=$actor['clbpara']['another']; $maxhp=another_max_hp($actor); $maxsp=another_max_sp($actor); $stats=another_effective_stats($actor,$players,$now);
    echo '</div><div class="an-vitals"><label>生命 <b>'.$actor['hp'].' / '.$maxhp.'</b></label><meter min="0" max="'.$maxhp.'" value="'.$actor['hp'].'"></meter><label>体力 <b>'.$actor['sp'].' / '.$maxsp.'</b></label><meter class="an-sp" min="0" max="'.$maxsp.'" value="'.$actor['sp'].'"></meter></div>';
    echo '<div class="an-stats"><span>等级 <b>'.(int)$actor['lvl'].'</b></span><span>经验 <b>'.(int)$actor['exp'].'</b></span><span>攻击 <b>'.round($stats['attack'],1).'</b></span><span>防御 <b>'.round(($stats['base_defense']+$stats['armor'])*$stats['defense_multiplier'],1).'</b></span><span>金钱 <b>'.(int)$actor['money'].'</b></span><span>REP <b>'.number_format(isset($an['rep'])?$an['rep']:0,2,'.','').'</b></span></div>';
    if(!empty($an['digestion_until'])&&$an['digestion_until']>$now) echo '<p class="an-status">补给消化：<span data-countdown="'.(int)$an['digestion_until'].'">'.another_ui_time($an['digestion_until']-$now).'</span></p>';
    if(another_talent($actor,'EU')) echo '<p class="an-status">土的坚韧：'.(!empty($an['earth_left'])?'保护剩余 '.$an['earth_left'].' 次自身行动':(!empty($an['earth_cd'])?'冷却剩余 '.$an['earth_cd'].' 次自身行动':'已就绪')).'</p>';
    $status_names=array('poison'=>'中毒','burn'=>'灼伤','slow'=>'迟缓','weaken'=>'削弱','attack'=>'攻击增益','defense'=>'防御增益');
    if(!empty($an['statuses'])) foreach($an['statuses'] as $status=>$details) echo '<p class="an-status">'.another_html(isset($status_names[$status])?$status_names[$status]:$status).' · <span data-countdown="'.(int)$details['until'].'">'.another_ui_time($details['until']-$now).'</span></p>';
    if(!empty($an['rest'])) echo '<p class="an-status">正在休息，仍可被发现与攻击。</p>';
    echo '<div class="an-buttons">'.another_ui_action('rest','休息恢复',array(),'',!$alive||!empty($an['rest']));
    if(another_talent($actor,'LU')) echo another_ui_action('hack',!empty($an['hack_used'])?'黑入机会已用':'木之多彩 · 尝试黑入',array(),'an-warning',!$alive||!empty($an['hack_used'])||another_item_find($actor,'satellite')===false);
    echo '</div></section><details class="an-panel" id="an-my-talents"><summary>我的天赋 <span>'.another_talent_points($an['talents']).' / 35</span></summary>';
    foreach(another_talent_definitions() as $id=>$def) if(another_talent($actor,$id)>0) echo '<div class="an-talent-read"><strong>'.another_html($def['name']).' '.$an['talents'][$id].'/'.$def['max'].'</strong><small>'.another_html($def['text']).'</small></div>';
    echo '</details><section class="an-panel"><h2>队伍</h2>';
    foreach($players as $pid=>$p) if(!$p['type']&&another_talent_same_team($actor,$p)) echo '<div class="an-team-member"><strong>'.another_html(another_name($p)).'</strong><small>'.($p['hp']>0?'HP '.$p['hp'].' · '.another_html($cfg['maps'][$p['pls']]['name']):'已被淘汰').'</small></div>';
    if($alive) echo another_ui_action('leave_team','离开当前队伍',array());
    if(!empty($an['invites'])) foreach($an['invites'] as $pid=>$time) if(isset($players[$pid])&&$players[$pid]['hp']>0) echo '<p>'.another_html(another_name($players[$pid])).'邀请你加入队伍。</p>'.another_ui_action('accept_team','接受邀请',array('target_pid'=>$pid),'',!$alive);
    echo '</section></aside><main class="an-main">';
    echo '<section class="an-panel an-location"><div class="an-kicker">当前位置 · '.($cfg['maps'][$actor['pls']]['indoor']?'室内／有顶遮蔽':'室外').'</div><div class="an-panel-heading"><h1>'.another_html($cfg['maps'][$actor['pls']]['name']).'</h1>'.another_ui_action('search','搜索周围',array(),'an-primary',!$alive).'</div><p>'.another_html($cfg['maps'][$actor['pls']]['description']).'</p>';
    $weather=isset($room['weather'])?$room['weather']:0; echo '<p class="an-muted">天气：'.another_html(isset($cfg['weather_effects'][$weather])?$cfg['weather_effects'][$weather]['name']:'晴天').' · '.($cfg['maps'][$actor['pls']]['cover']<1?'此地便于隐蔽':($cfg['maps'][$actor['pls']]['cover']>1?'此地较开阔，容易发现目标':'普通能见度')).'</p>';
    if(!$ended) foreach($cfg['zones'] as $time=>$closing) if($time>$elapsed) {
        $labels=array(); foreach($closing as $id) $labels[]=$cfg['maps'][$id]['name'];
        echo '<div class="an-zone-warning">下一轮 <span data-countdown="'.($started+$time).'">'.another_ui_time($time-$elapsed).'</span> 后关闭：'.another_html(implode('、',$labels)).'</div>'; break;
    }
    echo '<div class="an-moves"><span>相邻地点</span>'; foreach($cfg['maps'][$actor['pls']]['neighbors'] as $id) echo another_ui_action('move',$cfg['maps'][$id]['name'].(in_array($id,$room['closed'],true)?' · 已封闭':''),array('to'=>$id),'',!$alive||in_array($id,$room['closed'],true)); echo '</div></section>';
    echo another_ui_map($cfg,$room,$actor);
    if(!$alive&&!$ended) echo '<div class="an-error">这个角色已被淘汰。若另一角色仍存活，可以在左侧切换继续。</div>';
    echo '<section class="an-panel"><h2>当前视野</h2><div class="an-sight">'; $seen=false;
    foreach($players as $pid=>$target) if($pid!==$active_pid&&another_can_target($actor,$target)) {
        $seen=true; echo '<article class="an-target"><img src="'.another_html(another_ui_portrait($target)).'" alt=""><div class="an-target-main"><h3>'.another_html(another_name($target)).'</h3><p>'.($target['hp']>0?'生命 '.$target['hp'].' / '.another_max_hp($target):'遗留装备').($target['hp']>0?' · '.another_html($target['wep']):'').'</p>';
        if($target['type']&&!empty($target['clbpara']['another']['description'])) echo '<small>'.another_html($target['clbpara']['another']['description']).'</small>';
        if($alive&&$target['hp']>0) {
            echo '<div class="an-buttons">'.another_ui_action('attack','攻击',array('target_pid'=>$pid),'an-danger');
            if(another_talent($actor,'SU')) echo another_ui_action('sun','日之愤怒',array('target_pid'=>$pid),'an-danger');
            if(!$target['type']) {
                if(another_talent($actor,'PU')) echo another_ui_action('moon','月标记',array('target_pid'=>$pid));
                if(another_talent($actor,'CU')) echo another_ui_action('fire','火标记',array('target_pid'=>$pid));
                if(!another_talent_same_team($actor,$target)) echo another_ui_action('invite','邀请组队',array('target_pid'=>$pid));
            }
            echo '</div>';
            if(!$target['type']) {
                echo '<details id="trade-'.$pid.'"><summary>向此人转交物资</summary>'.another_ui_open_form('give',array('target_pid'=>$pid)).'<select name="slot" aria-label="转交物品">';
                foreach(array('wep','arb','arh','ara','arf','art',1,2,3,4,5,6) as $slot) { $item=another_item_get($actor,$slot); if(another_item_present($item)) echo '<option value="'.another_html($slot).'">'.another_html($item['name']).' ×'.$item['count'].'</option>'; }
                echo '</select><button>转交整组</button></form>';
                foreach(array('give_money'=>'金钱','give_rep'=>'REP') as $action=>$label) echo another_ui_open_form($action,array('target_pid'=>$pid)).'<label>'.$label.' <input name="amount" type="number" min="'.($action==='give_rep'?'0.01':'1').'" step="'.($action==='give_rep'?'0.01':'1').'" value="1"></label><button>转交</button></form>';
                echo '</details>';
            }
        } elseif($alive&&$target['hp']<=0) {
            echo '<div class="an-loot">';
            if($target['money']>0) echo another_ui_action('loot','拾取金钱 '.$target['money'],array('target_pid'=>$pid,'slot'=>'money'));
            foreach(array('wep','arb','arh','ara','arf','art',0,1,2,3,4,5,6) as $slot) { $item=another_item_get($target,$slot); if(another_item_present($item)) echo another_ui_action('loot',$item['name'].' ×'.$item['count'],array('target_pid'=>$pid,'slot'=>$slot)); }
            echo '</div>';
        }
        echo '</div></article>';
    }
    foreach(isset($an['known_ground'])?$an['known_ground']:array() as $id=>$known) if(isset($room['ground'][$id])&&(int)$room['ground'][$id]['pls']===(int)$actor['pls']) { $seen=true; $item=$room['ground'][$id]['item']; echo '<div class="an-ground"><div>'.another_ui_item_line($item).'</div>'.another_ui_action('pickup','拾取',array('id'=>$id),'',!$alive).'</div>'; }
    foreach(isset($an['known_traps'])?$an['known_traps']:array() as $id=>$known) if(isset($room['traps'][$id])&&(int)$room['traps'][$id]['pls']===(int)$actor['pls']) { $seen=true; $trap=$room['traps'][$id]; echo '<div class="an-ground an-trap"><div><strong>'.another_html($trap['item']['name']).'</strong><small>'.(!empty($trap['gold'])?'触发后不可回避；可以事先拆除。':'未触发的陷阱；可以离开或拆除。').'</small></div>'.another_ui_action('disarm','尝试拆除',array('id'=>$id),'',!$alive).'</div>'; }
    foreach(isset($room['deliveries'])?$room['deliveries']:array() as $id=>$delivery) if($delivery['owner']===$active_pid||($delivery['kind']==='balloon'&&!empty($an['known_deliveries'][$id])&&(int)$delivery['pls']===(int)$actor['pls'])) {
        $seen=true; echo '<div class="an-ground"><div><strong>'.($delivery['kind']==='falcon'?'猎鹰配送':($delivery['owner']===$active_pid?'自己呼叫的补给气球':'可观测的补给气球')).'</strong><small>'.($delivery['kind']==='balloon'?another_html($cfg['maps'][$delivery['pls']]['name']).' · ':'').'抵达倒计时 <span data-countdown="'.(int)$delivery['due'].'">'.another_ui_time($delivery['due']-$now).'</span></small></div>';
        echo $delivery['kind']==='falcon'?another_ui_action('collect','领取',array('id'=>$id),'',!$alive||$delivery['due']>$now):another_ui_action('shoot_balloon','击落争夺',array('id'=>$id),'',!$alive||$delivery['due']<=$now||(int)$delivery['pls']!==(int)$actor['pls']); echo '</div>';
    }
    if(!$seen) echo '<p class="an-muted">暂未发现可交互目标。搜索会发现角色、物资、气球或未触发陷阱。</p>';
    echo '</div></section>';
    echo another_ui_inventory($actor,$alive).another_ui_shop($actor,$room,$alive);
    echo '<section class="an-panel"><h2>行动记录</h2><div class="an-logs" aria-live="polite">';
    $logs=isset($an['messages'])?$an['messages']:array(); foreach(array_reverse($logs) as $entry) echo '<p><time>'.gmdate('H:i:s',$entry['time']).'</time> '.another_html($entry['text']).'</p>';
    if(!$logs) echo '<p class="an-muted">本角色的行动和受袭消息会显示在这里。</p>'; echo '</div></section>';
    echo '<details class="an-panel" id="an-city-news" open><summary>城市动态与声音</summary><div class="an-logs">';
    foreach(array_reverse(isset($room['traces'])?$room['traces']:array()) as $trace) {
        if($trace['pid']===$active_pid) continue; $age=$now-$trace['time'];
        if($trace['kind']==='footstep') { if((int)$trace['pls']!==(int)$actor['pls']||$age>$cfg['discovery']['trail_lifetime']) continue; $text=$cfg['maps'][$trace['pls']]['name'].'有脚步通向'.$cfg['maps'][$trace['to']]['name']; }
        else { if($age>$cfg['discovery']['noise_lifetime']) continue; $text=$cfg['maps'][$trace['pls']]['name'].'传来'.($trace['kind']==='gunshot'?'枪声':'交战声'); }
        echo '<p><time>'.max(0,(int)$age).'秒前</time> '.another_html($text).'。</p>';
    }
    foreach(array_reverse(isset($room['news'])?$room['news']:array()) as $entry) echo '<p><time>'.gmdate('H:i:s',$entry['time']).'</time> '.another_html($entry['text']).'</p>';
    echo '</div></details></main></div><footer class="an-footer">AN:OTHER · 每次行动均以服务器结果为准。受击与查看页面不会消耗土的保护次数。</footer></div>';
    return ob_get_clean();
}
function another_render_page($body)
{
    $another_body=$body;
    include template('another',1,'gamedata/ruleset/ANOTHER/templates');
}
