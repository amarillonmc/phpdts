<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_state() {
    return array('csrf'=>bin2hex(random_bytes(24)),'revision'=>0,'deaths'=>0,'pending'=>false,'result'=>'','finished'=>0,
        'jojo_deaths'=>0,'jojo_missing'=>false,'fed'=>0,'food_buff'=>0,'stance'=>'attack','target'=>0,'snare'=>0,'gate'=>false,'trial'=>false,
        'gate_returns'=>0,'death_places'=>array(),'friends_killed'=>array(),'events'=>array(),'loot'=>array(),'erosion'=>0,'eroded_actions'=>0,
        'path'=>false,'clues'=>array(),'actions'=>0,'clicks'=>0,'stage_clicks'=>0,'stage_max'=>0,'last_place'=>0,
        'scene'=>array('title'=>'大人同盟探险队','text'=>'你在无月之影醒来，却记不起自己的名字。猫耳少女蹲在身旁，始终没有放开你的衣角。「乔乔是大人同盟探险队的乔乔的说！不记得名字的话，就先叫旅人酱的说。醒了的话，就是同伴了的说！」'));
}
function flash_stats() { return array('mhp','msp','att','def','lvl','exp','wp','wk','wg','wc','wd','wf','skills','skillpoint'); }
function flash_limit_stats(&$p) {
    // 沿用当前 players 表与原生升级的上限；高阶委托/喂食不能溢出 smallint。
    $p['lvl']=min(255,max(1,(int)$p['lvl']));
    foreach (array('exp','skillpoint','skills','wp','wk','wg','wc','wd','wf') as $key) $p[$key]=min(65535,max(0,(int)$p[$key]));
}
function flash_baseline($p) {
    $wear=(int)($p['clbpara']['flash']['erosion']??0);
    return array('mhp'=>max(20,$GLOBALS['hplimit']-$wear),'msp'=>max(60,$GLOBALS['splimit']-$wear),'att'=>100,'def'=>100,'lvl'=>1,'exp'=>0,
        'wp'=>50,'wk'=>50,'wg'=>50,'wc'=>50,'wd'=>50,'wf'=>50,'skills'=>0,'skillpoint'=>0);
}
function flash_snapshot($p) {
    $out=array('stats'=>array_intersect_key($p,array_flip(flash_stats())),'equipment'=>array(),'bag'=>array(),
        'skill'=>$p['clbpara']['skill']??array(),'skillpara'=>$p['clbpara']['skillpara']??array());
    foreach (get_equip_list() as $slot) $out['equipment'][$slot]=flash_equipment($p,$slot);
    for ($i=0;$i<=6;$i++) if (!empty($p['itms'.$i])) $out['bag'][]=flash_item_at($p,$i);
    return $out;
}
function flash_mark(&$p,$qid,$reason='') {
    quest_init_state($p['clbpara']);
    if (isset($p['clbpara']['quest']['completed'][$qid])) return false;
    $p['clbpara']['quest']['active'][$qid]=array('start'=>$GLOBALS['now']);
    quest_complete($qid,$p,$reason); return true;
}
function flash_report(&$p,$key,$text='') {
    if (flash_mark($p,'FL_report_'.$key,$text?:flash_report_text($key))) flash_say('报告册多了一页。');
}
function flash_report_count($p) {
    $n=0; foreach (array('first','chain','drift','wrong','kind') as $key) if (isset($p['clbpara']['quest']['completed']['FL_report_'.$key])) $n++;
    return $n;
}
function flash_fragments($p) {
    $n=0; for ($i=1;$i<=5;$i++) if (isset($p['clbpara']['quest']['completed']['FL_evidence_'.$i])) $n++;
    return $n;
}
function flash_stage(&$p) {
    $s=&$p['clbpara']['flash']; $s['stage_max']=max($s['stage_max'],$s['stage_clicks']); $s['stage_clicks']=0;
}
function flash_die(&$p,$reason,$enemy=null) {
    $s=&$p['clbpara']['flash'];
    if (!empty($s['result']) || !empty($s['pending'])) return;
    if ($GLOBALS['now'] >= $GLOBALS['gamevars']['flash']['start']+flash_cfg('seconds')) { flash_finish($p,'lost','timeout'); return; }
    $s['last_place']=(int)$p['pls'];
    $s['death_places'][$p['pls']]=($s['death_places'][$p['pls']]??0)+1;
    $s['deaths']++;
    $step=flash_cfg('ban_steps')[$s['deaths']-1];
    $GLOBALS['areanum']=min(count($GLOBALS['gamevars']['flash']['order']),$GLOBALS['areanum']+$step);
    $s['deaths_log'][$s['deaths']]=array('time'=>$GLOBALS['now'],'place'=>$p['pls'],'reason'=>$reason,'text'=>flash_revival_text($s['deaths']));
    if ($s['deaths']>flash_cfg('revivals')) { flash_finish($p,'lost','deaths'); return; }
    // 门内的死亡不承担情报教学；其它第一次死亡才写第一页。
    if (!$s['gate']) {
        flash_report($p,'first');
        $meta=$enemy['clbpara']['flash_npc']??array();
        if (($meta['rule']??'')==='weak') {
            $weak=$GLOBALS['gamevars']['flash']['weak'][$meta['evidence']];
            $kind=array('P'=>'殴','K'=>'斩','G'=>'射','C'=>'投','D'=>'爆','F'=>'灵')[$weak];
            flash_report($p,'weak'.$meta['evidence'],$enemy['name'].'在'.$kind.'系攻击面前会露出破绽；其它攻击无法穿透那层镜面。弱点在本局不会改变。');
        }
    }
    $s['loss']=flash_snapshot($p); $s['pending']=true; $s['reset_world']=true; $s['path']=false;
    $p['hp']=1; $p['sp']=0; $p['pls']=0; $p['inf']=''; $p['state']=0; $p['action']=''; $p['bid']=0;
    flash_stage($p); flash_story($p,'第 '.$s['deaths'].' 次醒来 · +'.$step.' 片崩坏',flash_revival_text($s['deaths']));
    // 战斗中的另一方还会在引擎末尾保存；世界刷新必须等战斗返回后再做。
}
function flash_keep(&$p,$choice) {
    $s=&$p['clbpara']['flash'];
    if (!$s['pending'] || !in_array($choice,array('weapon','armor','stats'),true)) throw new RuntimeException('请选择这一次要带回的东西。');
    $old=$s['loss']; $lost=array('stats'=>array(),'equipment'=>array(),'bag'=>array(),'skill'=>array(),'skillpara'=>array());
    $base=flash_baseline($p);
    if ($choice!=='stats') {
        foreach ($base as $key=>$value) { $lost['stats'][$key]=max(0,$old['stats'][$key]-$value); $p[$key]=$value; }
        $lost['skill']=$old['skill']; $lost['skillpara']=$old['skillpara'];
        $p['clbpara']['skill']=array(); $p['clbpara']['skillpara']=array();
    }
    foreach ($old['equipment'] as $slot=>$item) {
        $keep=(strpos($slot,'wep')===0?$choice==='weapon':$choice==='armor');
        if (!$keep) {
            if (!empty($item['itms']) && $item['itme']>0) $lost['equipment'][$slot]=$item;
            flash_set_equipment($p,$slot,strpos($slot,'wep')===0?flash_item('拳头','WN',0,'∞'):array());
        }
    }
    for ($i=0;$i<=6;$i++) flash_set_item($p,$i);
    $slot=1;
    foreach ($old['bag'] as $item) {
        $category=strpos($item['itmk'],'W')===0?'weapon':(preg_match('/^[DA]/',$item['itmk'])?'armor':'');
        if ($category!=='' && $category===$choice && $slot<=6) flash_set_item($p,$slot++,$item);
        elseif ($category!=='') $lost['bag'][]=$item;
    }
    // 每次损失只有一份找回委托；最多 11 份，任务对象不随下一次回溯重置。
    $qid='FL_recover_'.$s['deaths'];
    $p['clbpara']['quest']['active'][$qid]=array('accepted'=>false,'progress'=>0,'loss'=>$lost,'death'=>$s['deaths']);
    unset($s['loss']); $s['pending']=false; $p['hp']=$p['mhp']; $p['sp']=$p['msp']; $p['rage']=0;
    $p['money']=max(20,(int)floor($p['money']*.25));
    $p['action']=''; $p['bid']=0;
    flash_say('你把选中的东西攥在手里。剩下的，让搬运工帮你找回来。');
}
function flash_ready($p) { return !empty($p['clbpara']['flash']) && empty($p['clbpara']['flash']['result']) && empty($p['clbpara']['flash']['pending']); }
function flash_true_available($p) {
    $s=$p['clbpara']['flash'];
    return !$s['jojo_deaths'] && !$s['jojo_missing'] && flash_report_count($p)>=flash_cfg('true_reports') && $s['deaths']<=flash_cfg('true_revives');
}
function flash_finish(&$p,$result,$reason='') {
    $s=&$p['clbpara']['flash']; if ($s['result']) return;
    $s['result']=$result; $s['reason']=$reason; $s['finished']=$GLOBALS['now']; $s['pending']=false; $s['target']=0; unset($s['reset_world']);
    $p['hp']=$result==='lost'?0:max(1,$p['hp']); $p['state']=$result==='lost'?10:5; $p['endtime']=$GLOBALS['now']; $p['action']=''; $p['bid']=0;
    flash_stage($p);
}
function flash_tick(&$p) {
    if (empty($p['clbpara']['flash']['result']) && $GLOBALS['now']>=$GLOBALS['gamevars']['flash']['start']+flash_cfg('seconds')) flash_finish($p,'lost','timeout');
    if (!empty($p['clbpara']['flash']['reset_world'])) flash_world_reset($p);
}
function flash_settle(&$p) {
    global $gamestate;
    flash_tick($p); flash_limit_stats($p); player_save($p); save_gameinfo();
    if (!empty($p['clbpara']['flash']['result']) && $gamestate>=10) {
        // 普通归档沿用 3/5/1；模式内用 FL1/FL2/FL3 区分剧情，避免占用公共结局编号。
        $result=$p['clbpara']['flash']['result'];
        gameover($GLOBALS['now'],$result==='lost'?'end1':($result==='true'?'end5':'end3'),$result==='lost'?'':$p['name']);
    }
}
function flash_pay(&$p,$cost) {
    if ($p['sp']<$cost) throw new RuntimeException('体力不足。使用补给，或回溯后再出发。');
    $p['sp']-=$cost; $p['clbpara']['flash']['actions']+=(int)($cost/15);
}
function flash_erosion(&$p,$search=false) {
    $s=&$p['clbpara']['flash']; if (!in_array((int)$p['pls'],flash_closed(),true)) return;
    $p['hp']-=max(1,(int)ceil($p['hp']*flash_cfg('erosion_hp')));
    $s['erosion']+=flash_cfg('erosion_max'); $s['eroded_actions']++;
    $p['mhp']=max(20,$p['mhp']-flash_cfg('erosion_max')); $p['msp']=max(60,$p['msp']-flash_cfg('erosion_max'));
    $p['hp']=min($p['hp'],$p['mhp']); $p['sp']=min($p['sp'],$p['msp']);
    if ($s['eroded_actions']>=3) { $inf=$s['eroded_actions']%2?'u':'w'; if (strpos($p['inf'],$inf)===false) $p['inf'].=$inf; }
    flash_say('空白掠过皮肤。你失去了一些生命，命体上限各减少 2；回溯也填不回这一小块缺口。');
    if ($search) {
        $s['clues'][$p['pls']]=min(flash_cfg('clue_searches'),($s['clues'][$p['pls']]??0)+1);
        $clue='被擦掉一半的街道上躺着一张学生证。照片中的人你不认识，背面的折痕却像自己的指印。乔乔没有跟进来，只在外面喊：「别看太久的说！那里的东西会记住你的说！」';
        if ($s['clues'][$p['pls']]===flash_cfg('clue_searches') && flash_mark($p,'FL_clue_'.$p['pls'],$clue)) flash_story($p,'空白里的学生证',$clue);
    }
    if ($p['hp']<=0) flash_die($p,'崩坏侵蚀');
}
function flash_job_event(&$p,$kind,$count=1) {
    quest_init_state($p['clbpara']);
    foreach ($p['clbpara']['quest']['active'] as $qid=>&$q) {
        if (strpos($qid,'FL_job_')===0 && ($q['kind']??'')===$kind) $q['progress']=min($q['need'],$q['progress']+$count);
        if (strpos($qid,'FL_recover_')===0 && !empty($q['accepted']) && in_array($kind,array('minion','holo','elite','boss','explore'),true)) $q['progress']=min($q['need'],$q['progress']+$count);
    }
    unset($q);
}
