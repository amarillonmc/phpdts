<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_button($label,$action,$arg='',$extra='',$disabled=false) {
    return '<button type="button" data-action="'.flash_html($action).'" data-arg="'.flash_html($arg).'" data-extra="'.flash_html($extra).'"'.($disabled?' disabled':'').'>'.$label.'</button>';
}
function flash_meter($label,$value,$max,$class='') {
    return '<div class="fl-meter '.$class.'"><span>'.$label.' <b>'.(int)$value.' / '.(int)$max.'</b></span><progress max="'.max(1,(int)$max).'" value="'.max(0,(int)$value).'"></progress></div>';
}
function flash_join_panel($user,$error='') {
    $html='<main class="fl-welcome"><nav><a href="index.php">← 常磐大厅</a><span>单人轮回探险</span></nav><section class="fl-intro"><span class="fl-eyebrow">ELEVEN RETURNS. ONE WAY OUT.</span><h1>FLASH<span>闪回</span></h1><p>你忘记了来路。<br>她替你记着每一次醒来。</p><p class="fl-muted">11 次复活 · 5 片证据 · 60 分钟<br>无需抢点或连点，每次行动都由你决定。</p>';
    if ($error!=='') $html.='<p class="fl-error">'.flash_html($error).'</p>';
    if ($user && !$error && $GLOBALS['gamestate']>=20) {
        $html.='<form method="post" action="valid.php"><input type="hidden" name="flash_csrf" value="'.flash_html(flash_join_token($user)).'"><label>醒来时攥着什么？<select name="weapon">';
        foreach (array('WK'=>'短刀 · 斩','WP'=>'木棍 · 殴','WG'=>'手枪 · 射','WC'=>'飞镖 · 投','WD'=>'爆破包 · 爆','WF'=>'音叉 · 灵') as $k=>$label) $html.='<option value="'.$k.'">'.$label.'</option>';
        $html.='</select></label><button class="fl-primary" type="submit">睁开眼睛 →</button></form>';
    } elseif (!$user) $html.='<a href="index.php">返回大厅登录</a>';
    elseif (!$error) $html.='<p>门还没有打开，请在游戏开始后刷新。</p>';
    return $html.'</section><p class="fl-caption">「醒了的话，就是同伴了的说！」</p></main>';
}
function flash_inventory($p) {
    $html='<h2>随身行囊 <small>六格</small></h2>';
    foreach (array('wep'=>'武器','wep2'=>'备用','arb'=>'身体','arh'=>'头部','ara'=>'手臂','arf'=>'足部','art'=>'饰物') as $key=>$label) if (!empty($p[$key.'s'])) {
        $html.='<div class="fl-equip"><span>'.$label.'</span><strong>'.flash_html($p[$key]).'</strong><small>'.(int)$p[$key.'e'].' / '.flash_html($p[$key.'s']).'</small></div>';
    }
    $html.=flash_button('交换主副武器','swap');
    for ($i=1;$i<=6;$i++) {
        $item=flash_item_at($p,$i); $html.='<div class="fl-item"><span class="fl-slot">0'.$i.'</span>';
        if (!$item['itms']) { $html.='<span class="fl-muted">空位</span></div>'; continue; }
        $html.='<div><strong>'.flash_html($item['itm']).'</strong><small>'.flash_html($item['itmk']).' · 效果 '.(int)$item['itme'].' · '.flash_html($item['itms']).' 份/耐久 · '.flash_html($item['itmsk']).'</small><div class="fl-inline">';
        $html.=flash_button('使用','use',$i).flash_button('喂食','feed',$i).flash_button('放下','drop',$i).'</div></div></div>';
    }
    return $html;
}
function flash_loot_panel($p) {
    $html='<h2>可以带走的东西</h2><p class="fl-muted">六格装满时，也可以直接装备、使用或喂给乔乔。换下的装备会留在这里。</p>';
    foreach ($p['clbpara']['flash']['loot'] as $id=>$item) {
        $html.='<div class="fl-loot"><div><strong>'.flash_html($item['itm']).'</strong><small>'.flash_html($item['itmk']).' · 效果 '.(int)$item['itme'].' · '.flash_html($item['itms']).' 份/耐久 · '.flash_html($item['itmsk']).'</small></div><div class="fl-inline">';
        $html.=flash_button('带走','take',$id).flash_button('使用 / 装备','loot_use',$id).flash_button('喂给乔乔','loot_feed',$id).'</div></div>';
    }
    return $html.flash_button('其余留在这里','leave_loot');
}
function flash_quests_panel($p) {
    $html='<h2>搬运工的委托</h2><p class="fl-muted">委托和证据随你回溯。完成后选一份报酬；原地领取。已接任务的难度与报酬不会随回溯改变。</p>';
    foreach ($p['clbpara']['quest']['active'] as $qid=>$q) if (strpos($qid,'FL_recover_')===0) {
        $html.='<article class="fl-quest"><h3>找回 · 第 '.(int)$q['death'].' 次遗失的东西</h3>';
        if (empty($q['accepted'])) $html.='<p>约 75% 的属性与装备效值可以找回。</p>'.flash_button('委托搬运工找回','accept',$qid);
        else $html.='<p>探索 / 击杀 '.(int)$q['progress'].' / '.(int)$q['need'].' · 按第 '.(int)$q['at_revive'].' 次复活时的路况接取</p>'.flash_button('取回包裹','claim',$qid,'stats',$q['progress']<$q['need']);
        $html.='</article>';
    }
    foreach (flash_jobs() as $key=>$job) {
        $qid='FL_job_'.$key; $q=$p['clbpara']['quest']['active'][$qid]??null;
        $html.='<article class="fl-quest"><h3>'.flash_html($job['title']).'</h3><p>'.flash_html($job['text']).'</p>';
        if (!$q) $html.=flash_button('接下委托','accept',$key,'',$p['lvl']<$job['lvl'] || $p['clbpara']['flash']['deaths']<$job['rev']).'<small>等级 '.$job['lvl'].' / 复活 '.$job['rev'].' 次起</small>';
        else {
            $html.='<p>'.($q['kind']==='collect'?'提交背包中的补给':('进展 '.$q['progress'].' / '.$q['need'])).'</p><div class="fl-inline">';
            foreach (array('stats'=>'记起本领','equipment'=>'取回装备','supplies'=>'带上补给') as $choice=>$label) $html.=flash_button($label,'claim',$qid,$choice,$q['kind']!=='collect' && $q['progress']<$q['need']);
            $html.='</div>';
            $html.='<details><summary>换一种武器</summary><div class="fl-inline">';
            foreach (array('WP'=>'殴','WK'=>'斩','WG'=>'射','WC'=>'投','WD'=>'爆','WF'=>'灵') as $kind=>$name) $html.=flash_button($name.'系装备','claim',$qid,'equipment_'.$kind,$q['kind']!=='collect' && $q['progress']<$q['need']);
            $html.='</div></details>';
        }
        $html.='</article>';
    }
    return $html;
}
function flash_journal_panel($p) {
    $html='<h2>死亡报告册</h2><p class="fl-muted">关键页 '.flash_report_count($p).' / 5 · 五片证据已找到 '.flash_fragments($p).' 片。没有读全也可以走出门。</p>';
    foreach ($p['clbpara']['quest']['completed'] as $qid=>$entry) if (strpos($qid,'FL_report_')===0) $html.='<article class="fl-quest"><p>'.flash_html($entry['reason']).'</p></article>';
    foreach ($p['clbpara']['flash']['deaths_log']??array() as $n=>$entry) $html.='<details><summary>第 '.$n.' 页 · '.flash_html($GLOBALS['plsinfo'][$entry['place']]).' · '.flash_html($entry['reason']).'</summary><p>'.flash_html($entry['text']).'</p></details>';
    if (empty($p['clbpara']['flash']['deaths_log'])) $html.='<p>这一页还是空白。</p>';
    $html.='<h3>被擦掉的地方</h3><p>';
    foreach (flash_closed() as $id) $html.='<span class="fl-tag">'.flash_html($GLOBALS['plsinfo'][$id]).'</span> ';
    $html.='</p>';
    foreach ($p['clbpara']['quest']['completed'] as $qid=>$entry) if (strpos($qid,'FL_clue_')===0) $html.='<details><summary>'.flash_html($GLOBALS['plsinfo'][(int)substr($qid,8)]).' · 空白里的学生证</summary><p>'.flash_html($entry['reason']).'</p></details>';
    return $html;
}
function flash_people_panel($p) {
    $html='<h2>声音与足迹</h2><p class="fl-muted">追踪：45 SP，失败也消耗。成功时直接抵达；没有需要抢按的按钮。</p>';
    foreach (flash_friends() as $key=>$unused) {
        $n=flash_npc('friend_'.$key); if (!$n) continue;
        $html.='<article class="fl-quest"><h3>'.flash_html($n['name']).'</h3><small>'.($n['hp']>0?flash_html($GLOBALS['plsinfo'][$n['pls']]):'这一次，她没有回应').'</small><div class="fl-inline">';
        if ($n['hp']>0) {
            $html.=flash_button('追踪','track',$n['pid']);
            if ((int)$n['pls']===(int)$p['pls']) $html.=flash_button('交谈','talk',$key).flash_button('攻击这位旅人','attack_friend',$key);
        }
        $html.='</div></article>';
    }
    $html.='<h3>五片证据</h3>';
    for ($i=1;$i<=5;$i++) {
        $n=flash_npc('boss'.$i); if (!$n) continue;
        $done=isset($p['clbpara']['quest']['completed']['FL_evidence_'.$i]);
        $html.='<article class="fl-quest"><strong>'.flash_html($n['name']).'</strong><small>'.($done?'证据已留下':flash_html($GLOBALS['plsinfo'][$n['pls']]).' · HP '.(int)$n['hp'].' / '.(int)$n['mhp']).'</small>';
        if (!$done) $html.=flash_button('循迹寻找','track',$n['pid']); $html.='</article>';
    }
    $html.='<details><summary>游荡的回声 · 幻象与强敌</summary>';
    foreach (flash_npcs() as $n) if ($n['hp']>0 && in_array(flash_role($n),array('holo','elite','hazard'),true)) {
        $html.='<article class="fl-quest"><strong>'.flash_html($n['name']).'</strong><small>'.flash_html($GLOBALS['plsinfo'][$n['pls']]).(!empty($n['clbpara']['flash_npc']['flee'])?' · 正在躲避你的脚步':'').'</small>'.flash_button('追踪','track',$n['pid']).'</article>';
    }
    $html.='</details><h3>尚未走过的岔路</h3><p class="fl-muted">这些路可能需要花一条命才能读懂。你也可以绕开。</p><div class="fl-inline">';
    foreach (array('chain'=>'赶往争执的路口','drift'=>'跟上重复的脚步','kind'=>'接受门里的热茶') as $key=>$label) $html.=flash_button($label,'event',$key,'',!empty($p['clbpara']['flash']['events'][$key]));
    return $html.'</div>';
}
function flash_actions_panel($p) {
    $s=$p['clbpara']['flash'];
    $html='<h2>接下来，往哪里走？</h2><div class="fl-actions">';
    foreach (array('explore'=>array('探索','45 SP · 搜集物品'),'battle'=>array('战斗','每轮 30 SP · 最多五轮'),'sweep'=>array('扫荡','每名 30 SP · 杂兵'),'jojo'=>array('让她出击','每轮 30 SP · 最多五轮'),'return'=>array('沿旧路返回','45 SP · 上次死亡地点')) as $key=>$label) $html.=flash_button('<strong>'.$label[0].'</strong><small>'.$label[1].'</small>',$key,'','',$key==='return' && !$s['deaths']);
    $html.=flash_button('<strong>整理遗骸</strong><small>取走装备与补给</small>','corpse','','',empty($s['corpse']));
    $html.='</div><div class="fl-jojo"><b>乔乔的安排</b><div class="fl-inline">'.flash_button('一起攻击','stance','attack','',$s['stance']==='attack').flash_button('挠痒牵制','stance','suppress','',$s['stance']==='suppress').'</div></div><h3>附近的路 <small>15 SP</small></h3><div class="fl-destinations">';
    if ($p['pls']!==0) $html.=flash_button('⌂ 无月之影','move',0);
    foreach (flash_neighbours((int)$p['pls']) as $id) if ($id!==0) {
        $closed=in_array($id,flash_closed(),true);
        $html.=flash_button(flash_html($GLOBALS['plsinfo'][$id]).($closed?' · 崩坏':''),'move',$id,'',$closed && !$s['path']);
    }
    $html.='</div>';
    if ($s['jojo_missing'] && $p['pls']===0) $html.=flash_button('找回走散的乔乔 · 45 SP','recover_jojo');
    if ($s['gate']) {
        $final=flash_npc('final');
        if ($final && $final['hp']<=0) {
            $html.=flash_button('走向新的一天','escape');
            if (flash_true_available($p) && !$s['trial']) $html.=flash_button('循着另一个脚步追上去','trial');
        }
        $html.=flash_button('从门前折返 · 15 SP','retreat','','',$s['snare']>0);
    } elseif ($p['pls']===0) $html.=flash_button(flash_fragments($p)===5?'拼好证据，推开门':'试着硬闯真相门（危险）','gate');
    if ($s['snare']) $html.='<p class="fl-error">藤蔓还会束缚 '.(int)$s['snare'].' 次交战。</p>';
    return $html.'<details class="fl-rewind"><summary>这一回，已经走不动了</summary><p>主动结束本轮，消耗一次复活；第十二次死亡结束整局。</p>'.flash_button('合上这一页，回溯','rewind').'</details>';
}
function flash_panel($p,$error='') {
    if (!$p) return '<main class="fl-ended"><h1>FLASH · 闪回</h1><p class="fl-error">'.flash_html($error?:'请先进入本局。').'</p><a href="valid.php">进入游戏</a> · <a href="index.php">常磐大厅</a></main>';
    $s=$p['clbpara']['flash']; $html='<div class="fl-shell" data-csrf="'.flash_html($s['csrf']).'" data-revision="'.(int)$s['revision'].'">';
    $html.='<header class="fl-header"><a href="index.php" class="fl-brand">FLASH <small>闪回</small></a><div class="fl-chapters"><span>醒来 <b>'.min(11,$s['deaths']).'</b> / 11</span><span>证据 <b>'.flash_fragments($p).'</b> / 5</span><span>报告 <b>'.flash_report_count($p).'</b> / 5</span></div>';
    if (!$s['result']) $html.='<time data-remaining="'.max(0,$GLOBALS['gamevars']['flash']['start']+flash_cfg('seconds')-$GLOBALS['now']).'">60:00</time>';
    $html.='</header>';
    if ($error!=='') $html.='<div class="fl-error" role="alert">'.flash_html($error).'</div>';
    if ($s['result']) {
        list($title,$text)=flash_ending_copy($p);
        $html.='<main class="fl-ended"><span class="fl-eyebrow">THE PAGE TURNS.</span><h1>'.flash_html($title).'</h1><p>'.flash_html($text).'</p>';
        if ($s['result']!=='lost') foreach (flash_ending_extras($p) as $name=>$copy) $html.='<details><summary>'.flash_html($name).'</summary><p>'.flash_html($copy).'</p></details>';
        $html.='<p class="fl-muted">'.(int)$s['clicks'].' 次行动点击 · 最长一段 '.(int)$s['stage_max'].' 次 · '.(int)$s['deaths'].' 次死亡</p><a class="fl-primary" href="index.php">回到常磐大厅</a></main>';
        return $html.'</div>';
    }
    if ($s['pending']) {
        $html.='<main class="fl-revival" data-priority="scene"><span class="fl-eyebrow">TAKE SOMETHING WITH YOU.</span><h1>'.flash_html($s['scene']['title']).'</h1><p>'.flash_html($s['scene']['text']).'</p><div class="fl-choices">';
        $html.=flash_button('<b>武器</b><span>握住用惯的重量</span><small>主副武器与背包中的武器</small>','keep','weapon');
        $html.=flash_button('<b>防具</b><span>记住被保护的感觉</span><small>装备与背包中的防具、饰物</small>','keep','armor');
        $html.=flash_button('<b>属性</b><span>让身体替你记得</span><small>等级、命体上限、攻防、熟练与招式</small>','keep','stats');
        return $html.'</div><p class="fl-muted">未保留的成长可接取找回委托。证据和报告册始终留在你身上。</p></main></div>';
    }
    $j=flash_npc('jojo');
    $html.='<main class="fl-layout"><aside class="fl-left"><h2>'.flash_html($p['name']).' <small>Lv. '.(int)$p['lvl'].'</small></h2>';
    $html.=flash_meter('生命',$p['hp'],$p['mhp']).flash_meter('体力',$p['sp'],$p['msp'],'sp');
    $html.='<div class="fl-stats"><span>攻 '.(int)$p['att'].'</span><span>防 '.(int)$p['def'].'</span><span>钱 '.(int)$p['money'].'</span></div>';
    if ($p['inf']) $html.='<p class="fl-error">状态：'.flash_html(implode('、',array_map(function($k){return $GLOBALS['infinfo'][$k]??$k;},str_split($p['inf'])))).'</p>';
    if ($j && !$s['jojo_missing']) $html.='<div class="fl-companion"><b>乔乔 <small>一起走</small></b>'.flash_meter('生命',$j['hp'],$j['mhp']).'<small>攻击 '.(int)$j['att'].' · 斩熟 '.(int)$j['wk'].' · 怒气 '.(int)$j['rage'].'</small></div>';
    else $html.='<p class="fl-muted">衣角空了。回到无月之影可以找回她。</p>';
    $html.=flash_inventory($p).'</aside><section class="fl-center"><nav class="fl-tabs">';
    foreach (array('scene'=>'此刻','quests'=>'委托','people'=>'足迹','journal'=>'报告册') as $key=>$label) $html.='<button type="button" data-view="'.$key.'">'.$label.'</button>';
    $html.='</nav><div class="fl-story-scroll"><section data-flash-view="scene">';
    $html.='<div class="fl-scene '.($s['gate']?'fl-door':'').'"><span class="fl-location">'.flash_html($GLOBALS['plsinfo'][$p['pls']]).'</span></div><div class="fl-story"><span class="fl-eyebrow">'.($s['gate']?'BEYOND THE DOOR':'A PLACE TO REMEMBER').'</span><h2>'.flash_html($s['scene']['title']).'</h2><p>'.flash_html($s['scene']['text']).'</p></div>';
    if ($s['target']) {
        $enemy=fetch_playerdata_by_pid((int)$s['target']);
        if ($enemy && $enemy['hp']>0 && (int)$enemy['pls']===(int)$p['pls']) $html.='<div class="fl-enemy"><strong>'.flash_html($enemy['name']).'</strong>'.flash_meter('生命',$enemy['hp'],$enemy['mhp']).'<small>攻击 '.(int)$enemy['att'].' · 防御 '.(int)$enemy['def'].' · 武器效值 '.(int)$enemy['wepe'].'</small></div>';
    }
    if ($s['loot']) $html.='<div class="fl-found" data-priority="scene">'.flash_loot_panel($p).'</div>';
    $html.='<p class="fl-hint">'.($s['jojo_missing']?'风里没有那声熟悉的回应。':($p['sp']<45?'「先吃点东西再走的说。大人也不能饿着肚子的说！」':'「乔乔会记路，也会找脚印的说！不想打的家伙，就先绕开的说！」')).'</p>';
    if (!empty($s['last_log'])) $html.='<details class="fl-log" open><summary>刚才发生的事</summary><div>'.nl2br(flash_html($s['last_log'])).'</div></details>';
    $html.='</section><section data-flash-view="quests" hidden>'.flash_quests_panel($p).'</section><section data-flash-view="people" hidden>'.flash_people_panel($p).'</section><section data-flash-view="journal" hidden>'.flash_journal_panel($p).'</section></div></section>';
    return $html.'<aside class="fl-right">'.flash_actions_panel($p).'</aside></main></div>';
}
