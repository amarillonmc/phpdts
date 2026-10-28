<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_button($label,$action,$arg='',$extra='',$disabled=false) {
    return '<button type="button" class="cmdbutton" data-action="'.flash_html($action).'" data-arg="'.flash_html($arg).'" data-extra="'.flash_html($extra).'"'.($disabled?' disabled':'').'>'.$label.'</button> ';
}
function flash_join_panel($user,$error='') {
    $html='<p>你在无月之影醒来，身边的猫耳少女始终没有放开你的衣角。</p><p>11 次复活 · 5 片证据 · 60 分钟</p>';
    if ($error!=='') $html.='<p class="yellow">'.flash_html($error).'</p>';
    if ($user && !$error && $GLOBALS['gamestate']>=20) {
        $html.='<form method="post" action="valid.php"><input type="hidden" name="flash_csrf" value="'.flash_html(flash_join_token($user)).'"><label>醒来时攥着什么？ <select name="weapon">';
        foreach (array('WK'=>'短刀 · 斩','WP'=>'木棍 · 殴','WG'=>'手枪 · 射','WC'=>'飞镖 · 投','WD'=>'爆破包 · 爆','WF'=>'音叉 · 灵') as $k=>$label) $html.='<option value="'.$k.'">'.$label.'</option>';
        $html.='</select></label> <button class="cmdbutton" type="submit">睁开眼睛</button></form>';
    }
    return $html.'<p><a href="index.php">返回大厅</a></p>';
}
function flash_inventory($p) {
    $html='<details id="flash-bag"><summary>使用物品 / 放下</summary>';
    for ($i=1;$i<=6;$i++) {
        $item=flash_item_at($p,$i); if (!$item['itms']) continue;
        $html.='<p><span class="yellow">'.flash_html($item['itm']).'</span> / '.(int)$item['itme'].' / '.flash_html($item['itms']).'<br>';
        $html.=flash_button('使用','use',$i).flash_button('放下','drop',$i).'</p>';
    }
    return $html.flash_button('交换主副武器','swap').'</details>';
}
function flash_loot_panel($p) {
    $html='<p class="yellow">可以带走的东西</p>';
    foreach ($p['clbpara']['flash']['loot'] as $id=>$item) {
        $html.='<p>'.flash_html($item['itm']).' / '.flash_html($item['itmk']).' / '.(int)$item['itme'].' / '.flash_html($item['itms']).'<br>';
        $html.=flash_button('带走','take',$id).flash_button('使用 / 装备','loot_use',$id).'</p>';
    }
    return $html.flash_button('其余留在这里','leave_loot').'<hr>';
}
function flash_quests_panel($p) {
    $s=$p['clbpara']['flash']; $disabled=!flash_ready($p);
    $html='<p>证据 '.flash_fragments($p).' / 5 · 关键报告 '.flash_report_count($p).' / 5</p><p>委托与证据跨回溯保留；接取时锁定难度和报酬。</p>';
    foreach ($p['clbpara']['quest']['active'] as $qid=>$q) if (strpos($qid,'FL_recover_')===0) {
        $html.='<div class="progress-item"><div class="progress-label">找回 · 第 '.(int)$q['death'].' 次遗失的东西</div>';
        if (empty($q['accepted'])) $html.='<div class="quest-item">可找回约 75% 的损失。</div>'.flash_button('接下找回委托','accept',$qid,'',$disabled);
        else $html.='<div class="quest-item">探索 / 击杀 '.(int)$q['progress'].' / '.(int)$q['need'].' · 按复活 '.(int)$q['at_revive'].' 次时接取</div>'.flash_button('取回包裹','claim',$qid,'stats',$disabled || $q['progress']<$q['need']);
        $html.='</div>';
    }
    foreach (flash_jobs() as $key=>$job) {
        $qid='FL_job_'.$key; $q=$p['clbpara']['quest']['active'][$qid]??null;
        $html.='<div class="progress-item"><div class="progress-label">'.flash_html($job['title']).'</div><div class="quest-item">'.flash_html($job['text']).'</div>';
        if (!$q) $html.=flash_button('接下委托','accept',$key,'',$disabled || $p['lvl']<$job['lvl'] || $s['deaths']<$job['rev']).'<small>等级 '.$job['lvl'].' / 复活 '.$job['rev'].' 次起</small>';
        else {
            $html.='<p>'.($q['kind']==='collect'?'提交背包中的两份补给':('进展 '.$q['progress'].' / '.$q['need'])).'</p>';
            $blocked=$disabled || ($q['kind']!=='collect' && $q['progress']<$q['need']);
            foreach (array('stats'=>'记起本领','equipment'=>'取回装备','supplies'=>'带上补给') as $choice=>$label) $html.=flash_button($label,'claim',$qid,$choice,$blocked);
            $html.='<details id="flash-reward-'.$key.'"><summary>换一种武器</summary>';
            foreach (array('WP'=>'殴','WK'=>'斩','WG'=>'射','WC'=>'投','WD'=>'爆','WF'=>'灵') as $kind=>$name) $html.=flash_button($name.'系装备','claim',$qid,'equipment_'.$kind,$blocked);
            $html.='</details>';
        }
        $html.='</div>';
    }
    $html.='<details><summary>已留下的证据</summary>';
    for ($i=1;$i<=5;$i++) if (isset($p['clbpara']['quest']['completed']['FL_evidence_'.$i])) $html.='<p>证据 '.$i.' · '.flash_html($p['clbpara']['quest']['completed']['FL_evidence_'.$i]['reason']).'</p>';
    return $html.'</details>';
}
function flash_journal_panel($p) {
    $html='<span class="yellow">死亡报告册</span><br>关键页 '.flash_report_count($p).' / 5 · 证据 '.flash_fragments($p).' / 5<br>';
    foreach ($p['clbpara']['quest']['completed'] as $qid=>$entry) if (strpos($qid,'FL_report_')===0) $html.='<p>'.flash_html($entry['reason']).'</p>';
    foreach ($p['clbpara']['flash']['deaths_log']??array() as $n=>$entry) $html.='<details><summary>第 '.$n.' 页 · '.flash_html($GLOBALS['plsinfo'][$entry['place']]).' · '.flash_html($entry['reason']).'</summary><p>'.flash_html($entry['text']).'</p></details>';
    foreach ($p['clbpara']['quest']['completed'] as $qid=>$entry) if (strpos($qid,'FL_clue_')===0) $html.='<details><summary>'.flash_html($GLOBALS['plsinfo'][(int)substr($qid,8)]).' · 空白里的学生证</summary><p>'.flash_html($entry['reason']).'</p></details>';
    $html.='<p>被擦掉的地方：';
    foreach (flash_closed() as $id) $html.=flash_html($GLOBALS['plsinfo'][$id]).' ';
    return $html.'</p>';
}
function flash_people_panel($p) {
    $html='<details id="flash-people"><summary>声音与足迹 · 追踪 45 SP</summary>';
    foreach (flash_friends() as $key=>$unused) {
        $n=flash_npc('friend_'.$key); if (!$n || $n['hp']<=0) continue;
        $html.='<p><span class="yellow">'.flash_html($n['name']).'</span> · '.flash_html($GLOBALS['plsinfo'][$n['pls']]).'<br>';
        $html.=flash_button('追踪','track',$n['pid']);
        if ((int)$n['pls']===(int)$p['pls']) $html.=flash_button('交谈','talk',$key).flash_button('攻击这位旅人','attack_friend',$key);
        $html.='</p>';
    }
    $html.='<hr>';
    for ($i=1;$i<=5;$i++) {
        $n=flash_npc('boss'.$i); if (!$n) continue;
        $done=isset($p['clbpara']['quest']['completed']['FL_evidence_'.$i]);
        $html.='<p>'.flash_html($n['name']).' · '.($done?'证据已留下':flash_html($GLOBALS['plsinfo'][$n['pls']]).' · HP '.(int)$n['hp'].' / '.(int)$n['mhp']).'<br>';
        if (!$done) $html.=flash_button('循迹寻找','track',$n['pid']); $html.='</p>';
    }
    $html.='<details id="flash-echoes"><summary>游荡的回声 · 幻象与强敌</summary>';
    foreach (flash_npcs() as $n) if ($n['hp']>0 && in_array(flash_role($n),array('holo','elite','hazard'),true)) $html.='<p>'.flash_html($n['name']).' · '.flash_html($GLOBALS['plsinfo'][$n['pls']]).'<br>'.flash_button('追踪','track',$n['pid']).'</p>';
    return $html.'</details></details>';
}
function flash_panel($p) {
    $s=$p['clbpara']['flash'];
    $html='<div id="flash-state" data-revision="'.(int)$s['revision'].'"><input type="hidden" name="flash_csrf" value="'.flash_html($s['csrf']).'"><input type="hidden" name="flash_rev" value="'.(int)$s['revision'].'"><input type="hidden" id="mode" name="mode" value="command"><input type="hidden" id="command" name="command" value="menu">';
    $html.='<p>FLASH · 复活 '.min(11,$s['deaths']).' / 11 · 证据 '.flash_fragments($p).' / 5';
    if (!$s['result']) $html.='<br>剩余 <span id="flash-clock" data-remaining="'.max(0,$GLOBALS['gamevars']['flash']['start']+flash_cfg('seconds')-$GLOBALS['now']).'"></span>';
    $html.='</p><template id="flash-quest-source">'.flash_quests_panel($p).'</template>';
    if ($s['result']) return $html.'<a href="index.php">返回常磐大厅</a></div>';
    if ($s['pending']) {
        if ($p['hp']<=0) return $html.flash_button('继续复活','revive').'</div>';
        $html.='<p>这一次，要带回什么？</p>';
        foreach (array('weapon'=>'武器','armor'=>'防具','stats'=>'属性') as $key=>$label) $html.=flash_button($label,'keep',$key);
        return $html.'<p>未保留的成长可接取找回委托。证据和报告册始终留下。</p></div>';
    }
    $html.='<button type="button" class="cmdbutton" data-flash-quest-open>任务</button> '.flash_button('报告册','journal').flash_button('回想刚才的事','recall').'<hr>';
    if ($s['loot']) $html.=flash_loot_panel($p);
    foreach (array('explore'=>'探索 45 SP','battle'=>'战斗 30 SP/轮','sweep'=>'扫荡 30 SP/人','return'=>'沿旧路返回 45 SP') as $key=>$label) $html.=flash_button($label,$key,'','',$key==='return' && !$s['deaths']);
    $html.=flash_button('整理遗骸','corpse','','',empty($s['corpse'])).'<br>';
    if ($s['jojo_missing'] && $p['pls']===0) $html.='<p>'.flash_button('找回走散的乔乔 45 SP','recover_jojo').'</p>';
    $html.='<p>附近的路 · 15 SP<br>';
    if ($p['pls']!==0) $html.=flash_button('无月之影','move',0);
    foreach (flash_neighbours((int)$p['pls']) as $id) if ($id!==0) {
        $closed=in_array($id,flash_closed(),true);
        $html.=flash_button(flash_html($GLOBALS['plsinfo'][$id]).($closed?'（崩坏）':''),'move',$id,'',$closed && !$s['path']);
    }
    $html.='</p>';
    if ($s['gate']) {
        $final=flash_npc('final');
        if ($final && $final['hp']<=0) {
            $html.=flash_button('走向新的一天','escape');
            if (flash_true_available($p) && !$s['trial']) $html.=flash_button('循着另一个脚步追上去','trial');
        }
        $html.=flash_button('从门前折返 15 SP','retreat','','',$s['snare']>0);
    } elseif ($p['pls']===0) $html.=flash_button(flash_fragments($p)===5?'拼好证据，推开门':'试着硬闯真相门（危险）','gate');
    if ($s['snare']) $html.='<p class="yellow">藤蔓还会束缚 '.(int)$s['snare'].' 次交战。</p>';
    $html.='<hr>'.flash_inventory($p).flash_people_panel($p).'<details id="flash-events"><summary>尚未走过的岔路</summary><p>这些路可能需要花一条命才能读懂，也可以绕开。</p>';
    foreach (array('chain'=>'赶往争执的路口','drift'=>'跟上重复的脚步','kind'=>'接受门里的热茶') as $key=>$label) $html.=flash_button($label,'event',$key,'',!empty($s['events'][$key]));
    return $html.'</details><details id="flash-rewind"><summary>这一回，已经走不动了</summary><p>主动结束本轮，消耗一次复活。</p>'.flash_button('合上这一页，回溯','rewind').'</details></div>';
}
function ruleset_death_page_copy_hook($state) {
    $p=$GLOBALS['pdata']; $s=$p['clbpara']['flash'];
    return array('status'=>$s['result']?'这段旅程已经结束。':'你死了。停留在这里，直到你选择继续。','controls'=>flash_panel($p));
}
function flash_companion($p) {
    $s=$p['clbpara']['flash']; $j=flash_npc('jojo');
    if (!flash_ready($p) || $p['hp']<=0 || $s['jojo_missing'] || !$j || $j['hp']<=0 || (int)$j['pls']!==(int)$p['pls']) return '';
    $html='<div id="flash-jojo" class="flash-jojo"><button type="button" id="flash-jojo-toggle" class="flash-jojo__avatar" aria-label="乔乔：点击交谈，拖动移动" aria-expanded="false" aria-controls="flash-jojo-bubble"><img src="gamedata/ruleset/FLASH/img/jojo-chibi.png" alt="乔乔" draggable="false"></button>';
    $html.='<div id="flash-jojo-bubble" class="flash-jojo__bubble" role="region" aria-label="乔乔的指令" hidden><button type="button" class="flash-jojo__close" aria-label="收起乔乔的气泡">×</button><strong>乔乔</strong><p>HP '.(int)$j['hp'].' / '.(int)$j['mhp'].' · 怒气 '.(int)$j['rage'].'<br>攻击 '.(int)$j['att'].' · 防御 '.(int)$j['def'].' · 斩熟 '.(int)$j['wk'].'</p>';
    $html.=flash_button('让她出击','jojo').flash_button('一起攻击','stance','attack','',$s['stance']==='attack').flash_button('挠痒牵制','stance','suppress','',$s['stance']==='suppress');
    $html.='<hr><strong>喂给乔乔</strong><div class="flash-jojo__food">'; $has_food=false;
    for ($i=1;$i<=6;$i++) if ($p['itms'.$i] && preg_match('/^(W|D|A|H[HSB])/',$p['itmk'.$i])) {
        $has_food=true; $html.=flash_button(flash_html($p['itm'.$i]).'（'.$p['itms'.$i].'）','feed',$i);
    }
    foreach ($s['loot'] as $id=>$item) if (preg_match('/^(W|D|A|H[HSB])/',$item['itmk'])) {
        $has_food=true; $html.=flash_button('眼前的 '.flash_html($item['itm']),'loot_feed',$id);
    }
    if (!$has_food) $html.='<p>没有可喂食的补给或装备。</p>';
    return $html.'</div></div></div>';
}
function flash_game_view($p,$ajax=false) {
    // 使用原生状态、战斗、聊天和右侧 log/cmd 模板，不再另建整页界面。
    $GLOBALS['pdata']=&$p;
    if (empty($p['clbpara']['valid_bgmbook'])) $p['clbpara']['valid_bgmbook']=$GLOBALS['regular_bgm']??array();
    init_playerdata(); init_profile();
    $view_main='profile';
    $enemy_id=$p['hp']<=0?$p['bid']:$p['clbpara']['flash']['target'];
    if ($enemy_id && ($enemy=fetch_playerdata_by_pid((int)$enemy_id))) {
        init_battle_rev($p,$enemy,1); $view_main='battle_rev';
    }
    extract($GLOBALS,EXTR_SKIP);
    extract($p,EXTR_OVERWRITE);
    $pdata=$p; $main=$view_main; $mode='command'; $command='enter';
    $alivenum=$hp>0?1:0;
    $battle_title=$hp<=0?'你被击杀了':'战斗发生';
    $dtime=date('Y年m月d日H时i分s秒',$deathtime?:$endtime);
    $death_entry=$clbpara['flash']['deaths_log'][$clbpara['flash']['deaths']]??array();
    $kname=flash_html($death_entry['reason']??'');
    if ($hp<=0) {
        $stateinfo[$state]='生命信号中断';
        $dinfo[$state]=$state===27?'你踩到了地雷。爆炸吞没了眼前的路。':($bid?'你没能挡下'. $kname .'的最后一击。':'你的旅途在这里中断了。');
        if (!$bid && $kname!=='') $dinfo[$state].='<br>死因：'.$kname;
        ob_start(); include template('death'); $cmd=ob_get_clean();
    } else $cmd=flash_panel($p);
    if ($ajax) {
        ob_start(); include template($main); $profile=ob_get_clean();
        return array('innerHTML'=>array('main'=>$profile,'cmd'=>$cmd,'log'=>$log,'pls'=>flash_html($plsinfo[$pls]),'anum'=>$alivenum,'notice'=>'','dialogue-container'=>'','flash-companion'=>flash_companion($p)),'clbpara'=>$clbpara,'locationId'=>(int)$pls);
    }
    $chatdata=getchat(0,$teamID); $emdata=get_emdata();
    ob_start(); include template('game'); return ob_get_clean();
}
