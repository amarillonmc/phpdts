<?php
if (!defined('IN_GAME')) exit('Access Denied');
define('FLASH_TEST_BOOTSTRAP',true);
require_once __DIR__.'/flash_ruleset_test.php';

// 每步经过同一命令校验、操作、轮回刷新和数据库序列化；不修改玩家战力或任务进度。
function fr_step(&$p,$action,$arg='',$extra='') {
    global $now,$log;
    $log=''; $now+=2;
    list($action,$arg,$extra)=flash_validate_command($p,array('command'=>'flash_'.$action,'arg'=>(string)$arg,'extra'=>$extra,
        'flash_csrf'=>$p['clbpara']['flash']['csrf'],'flash_rev'=>(string)$p['clbpara']['flash']['revision']));
    $p['clbpara']['flash']['revision']++; $p['clbpara']['flash']['clicks']++; $p['clbpara']['flash']['stage_clicks']++;
    if (isset($GLOBALS['fr_seed'])) mt_srand($GLOBALS['fr_seed']+$p['clbpara']['flash']['revision']);
    if ($p['clbpara']['flash']['clicks']>950) throw new RuntimeException('route exceeds click budget');
    flash_action($p,$action,$arg,$extra); flash_tick($p); ft_reload($p);
}
function fr_loot(&$p,$equip=false) {
    foreach (array_keys($p['clbpara']['flash']['loot']) as $id) {
        $item=$p['clbpara']['flash']['loot'][$id]??null; if (!$item) continue;
        if ($equip && preg_match('/^(W|DB)/',$item['itmk'])) fr_step($p,'loot_use',$id);
        elseif (preg_match('/^H[HSB]/',$item['itmk'])) {
            $space=false; for ($i=1;$i<=6;$i++) if (!$p['itms'.$i] || ($p['itm'.$i]===$item['itm'] && $p['itmk'.$i]===$item['itmk'] && $p['itme'.$i]==$item['itme'])) $space=true;
            if ($space) fr_step($p,'take',$id);
            else while (isset($p['clbpara']['flash']['loot'][$id]) && ((preg_match('/^H[HB]/',$item['itmk']) && $p['hp']<$p['mhp']) || (preg_match('/^H[SB]/',$item['itmk']) && $p['sp']<$p['msp']))) fr_step($p,'loot_use',$id);
        }
    }
    if ($p['clbpara']['flash']['loot']) fr_step($p,'leave_loot');
}
function fr_use_food(&$p) {
    for ($i=1;$i<=6;$i++) {
        while ($p['itms'.$i] && preg_match('/^H[HB]/',$p['itmk'.$i]) && $p['hp']<$p['mhp']*.85) fr_step($p,'use',$i);
        while ($p['itms'.$i] && preg_match('/^H[SB]/',$p['itmk'.$i]) && $p['sp']<150) fr_step($p,'use',$i);
        if ($p['itms'.$i] && $p['itmk'.$i]==='Ca' && $p['inf']) fr_step($p,'use',$i);
    }
}
// 一次性任务的实走路线：不改战力、不重复任务、不直接写入任务进度。
function fr_home(&$p) { if ($p['pls']!==0) fr_step($p,'move',0); }
function fr_buy(&$p,$kind,$amount=1) {
    fr_loot($p); fr_home($p);
    $stock=array_filter($GLOBALS['db']->shopitem,function($i)use($p,$kind,$amount){return $i['itmk']===$kind && $i['price']*$amount<=$p['money'] && $i['area']<=$GLOBALS['areanum']/$GLOBALS['areaadd'] && $i['num']>=$amount;});
    usort($stock,function($a,$b){return $b['itme']<=>$a['itme'];});
    if (!$stock) throw new RuntimeException('No affordable stock '.$kind.' money '.$p['money']);
    $i=$stock[0]; $GLOBALS['now']+=2; $p['clbpara']['flash']['clicks']++; $p['clbpara']['flash']['stage_clicks']++;
    ft_command($p,array('mode'=>'shop','command'=>(string)$i['sid'],'shoptype'=>(string)$i['kind'],'buynum'=>(string)$amount));
    fr_loot($p,strpos($kind,'W')===0);
    if (strpos($kind,'W')===0 && $p['wep']!==$i['item']) for ($slot=1;$slot<=6;$slot++) if ($p['itm'.$slot]===$i['item'] && $p['itms'.$slot]) { fr_step($p,'use',(string)$slot); break; }
}
function fr_recover(&$p) {
    if ($p['clbpara']['flash']['result']) throw new RuntimeException('Once-only route ended: '.$p['clbpara']['flash']['result']);
    if ($p['clbpara']['flash']['pending']) {
        fr_step($p,'revive'); fr_step($p,'keep','stats');
        fr_step($p,'talk','cook'); fr_loot($p);
    }
    foreach ($p['clbpara']['quest']['active'] as $qid=>$q) if (strpos($qid,'FL_recover_')===0 && $q['progress']>=$q['need']) {
        fr_loot($p); fr_step($p,'claim',$qid,'stats'); fr_loot($p,true);
    }
}
function fr_heal(&$p) {
    fr_loot($p); fr_use_food($p);
    if ($p['sp']<150) {
        fr_home($p);
        if (empty($p['clbpara']['flash']['events']['cooked'])) { fr_step($p,'talk','cook'); fr_loot($p); fr_use_food($p); }
        if ($p['sp']<150) { fr_buy($p,'HS',3); fr_use_food($p); }
    }
}
function fr_job(&$p,$key,$choice) {
    $qid='FL_job_'.$key;
    for ($attempt=0;$attempt<40;$attempt++) {
        fr_recover($p); fr_heal($p);
        $q=$p['clbpara']['quest']['active'][$qid]??null;
        if (!$q) throw new RuntimeException('Missing once-only quest '.$key.' level '.$p['lvl']);
        if ($q['kind']==='collect' || $q['progress']>=$q['need']) {
            fr_step($p,'claim',$qid,$choice); fr_loot($p,strpos($choice,'equipment')===0); return;
        }
        if ($key==='explore') { fr_home($p); fr_step($p,'explore','3'); fr_loot($p); }
        elseif ($key==='escort') { fr_step($p,'move',$p['pls']===0?array_values(array_intersect(flash_neighbours(0),flash_safe()))[0]:0); }
        elseif ($key==='feed') {
            fr_home($p); $fed=false;
            for ($i=1;$i<=6;$i++) if ($p['itms'.$i] && preg_match('/^H[HSB]/',$p['itmk'.$i])) { fr_step($p,'feed',$i); $fed=true; break; }
            if (!$fed) {
                if ($p['itms1']) fr_step($p,'drop','1');
                fr_buy($p,'HH',2);
            }
        } elseif ($key==='track') { fr_step($p,'track',flash_npc('friend_cook')['pid']); }
        else {
            $targets=array_filter(flash_npcs(),function($n)use($q){return $n['hp']>0 && flash_role($n)===$q['kind'] && in_array((int)$n['pls'],flash_safe(),true);});
            $n=reset($targets); if (!$n) throw new RuntimeException('No target for '.$key);
            if ($p['pls']!==(int)$n['pls'] || $p['clbpara']['flash']['target']!==(int)$n['pid']) fr_step($p,'track',$n['pid']);
            else fr_step($p,'battle');
        }
    }
    throw new RuntimeException('Stalled quest '.$key);
}
function fr_boss(&$p,$key) {
    $kind=0; $kinds=array('WK','WP','WG','WC','WD','WF');
    for ($attempt=0;$attempt<60;$attempt++) {
        fr_recover($p); fr_heal($p); $n=flash_npc($key);
        if ($n['hp']<=0) { fr_loot($p); return; }
        if ($p['wepk']==='WN') fr_buy($p,$kinds[$kind]);
        if ($p['pls']!==(int)$n['pls'] || $p['clbpara']['flash']['target']!==(int)$n['pid']) { fr_step($p,'track',$n['pid']); continue; }
        $hp=$n['hp']; fr_step($p,'battle');
        if (!$p['clbpara']['flash']['pending'] && flash_npc($key)['hp']===$hp && in_array($key,array('boss2','boss3'),true)) { $kind=($kind+1)%6; fr_buy($p,$kinds[$kind]); }
    }
    throw new RuntimeException('Stalled boss '.$key.' '.json_encode(array($p['att'],$p['def'],$p['hp'],$p['wepe'],$p['wk'])));
}
function fr_route($seed,$true) {
    $GLOBALS['fr_seed']=$seed; $p=ft_start($seed); $GLOBALS['pdata']=&$p;
    fr_step($p,'move',flash_neighbours(0)[0]); if (!$p['clbpara']['flash']['pending']) fr_step($p,'rewind'); fr_recover($p);
    fr_job($p,'collect','equipment_WK'); fr_job($p,'explore','stats'); fr_job($p,'patrol','stats'); fr_job($p,'escort','stats');
    fr_step($p,'stance','suppress');
    fr_boss($p,'boss4'); fr_job($p,'holo','stats');
    foreach (array('boss2','boss3','boss5','boss1') as $key) fr_boss($p,$key);
    if ($true) {
        foreach (array('drift','kind','chain') as $event) { fr_home($p); fr_step($p,'event',$event); fr_recover($p); }
        for ($i=0;$i<20 && !isset($p['clbpara']['quest']['completed']['FL_report_wrong']);$i++) { fr_heal($p); fr_home($p); fr_step($p,'attack_friend','porter'); fr_recover($p); }
    }
    if ($p['clbpara']['flash']['deaths']<2) { fr_step($p,'event','drift'); fr_recover($p); }
    fr_job($p,'feed','stats'); fr_job($p,'track','equipment_WK'); fr_heal($p); fr_home($p);
    fr_step($p,'stance','suppress'); fr_step($p,'gate');
    for ($i=0;$i<30 && flash_npc('final')['hp']>0;$i++) { fr_use_food($p); fr_step($p,'battle'); if ($p['clbpara']['flash']['pending']) throw new RuntimeException('Died at final'); }
    if ($true) {
        ft_assert(flash_true_available($p),'once-only route earned true flags'); fr_step($p,'trial');
        for ($i=0;$i<30 && !$p['clbpara']['flash']['result'];$i++) { fr_use_food($p); fr_step($p,'battle'); if ($p['clbpara']['flash']['pending']) throw new RuntimeException('Died at true guardians'); }
    } else fr_step($p,'escape');
    $s=$p['clbpara']['flash']; ft_assert($s['result']===($true?'true':'normal'),'once-only route ending');
    ft_assert($s['clicks']<=1000 && $s['stage_max']<=100,'once-only click budgets: '.$s['clicks'].' total, '.$s['stage_max'].' stage');
    echo ($true?'True':'Normal').' once-only: '.$s['clicks'].' clicks; max stage '.$s['stage_max'].'; deaths '.$s['deaths'].PHP_EOL;
}
fr_route(2810,false);
fr_route(1031,true);

$p=ft_start(); $pdata=&$p;
for ($i=1;$i<=12;$i++) { fr_step($p,'rewind'); if ($i<12) { fr_step($p,'revive'); fr_step($p,'keep','stats'); } }
ft_assert($p['clbpara']['flash']['result']==='lost' && $p['clbpara']['flash']['clicks']===34,'death budget route ends on twelfth death');
echo "Failure: 34 action clicks, 12 deaths, 34 collapsed maps.\n";
