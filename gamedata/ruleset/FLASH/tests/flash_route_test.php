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
    if (isset($GLOBALS['fr_trace'])) $GLOBALS['fr_trace'][]=array('action'=>$action,'arg'=>$arg,'extra'=>$extra,'stage_end'=>$p['clbpara']['flash']['stage_clicks']===0);
}
function fr_loot(&$p,$equip=false) {
    foreach (array_keys($p['clbpara']['flash']['loot']) as $id) {
        $item=$p['clbpara']['flash']['loot'][$id]??null; if (!$item) continue;
        if ($equip && preg_match('/^(W|DB)/',$item['itmk'])) fr_step($p,'loot_use',$id);
        elseif (preg_match('/^H[HSB]/',$item['itmk'])) {
            $space=false; for ($i=1;$i<=6;$i++) if (!$p['itms'.$i]) $space=true;
            if ($space) fr_step($p,'take',$id);
        }
    }
    if ($p['clbpara']['flash']['loot']) fr_step($p,'leave_loot');
}
function fr_food($p) { $n=0; for ($i=1;$i<=6;$i++) if (preg_match('/^H[HSB]/',$p['itmk'.$i])) $n+=(int)$p['itms'.$i]; return $n; }
function fr_job(&$p,$choice) {
    if (fr_food($p)<2) {
        if ($p['pls']!==0) fr_step($p,'move',0);
        fr_step($p,'talk','cook'); fr_loot($p);
        if (fr_food($p)<2) throw new RuntimeException('route ran out of supplies');
    }
    if (fr_food($p)<=4 && $choice!=='supplies') fr_job($p,'supplies');
    fr_step($p,'accept','collect'); fr_step($p,'claim','FL_job_collect',$choice); fr_loot($p,strpos($choice,'equipment')===0);
}
function fr_heal(&$p) {
    for ($i=1;$i<=6;$i++) {
        while ($p['itms'.$i] && preg_match('/^H[HB]/',$p['itmk'.$i]) && $p['hp']<$p['mhp']*.85) fr_step($p,'use',$i);
        while ($p['itms'.$i] && preg_match('/^H[SB]/',$p['itmk'.$i]) && $p['sp']<150) fr_step($p,'use',$i);
        if ($p['itms'.$i] && $p['itmk'.$i]==='Ca' && $p['inf']) fr_step($p,'use',$i);
    }
}
function fr_revival(&$p) {
    if ($p['clbpara']['flash']['result']) throw new RuntimeException('route failed: '.$p['clbpara']['flash']['result']);
    if ($p['clbpara']['flash']['pending']) {
        if ($p['hp']<=0) fr_step($p,'revive');
        fr_step($p,'keep','stats'); fr_step($p,'talk','cook'); fr_loot($p);
        fr_job($p,'equipment_WK');
    }
}
function fr_boss(&$p,$key) {
    $kind=0; $kinds=array('WK','WP','WG','WC','WD','WF'); $attempts=0;
    while (($n=flash_npc($key))['hp']>0) {
        if (++$attempts>40) throw new RuntimeException('stalled at '.$key.' hp '.$n['hp'].' player '.json_encode(array_intersect_key($p,array_flip(array('att','def','hp','wepe','wepk','sp','inf')))));
        fr_revival($p); fr_heal($p);
        if ($p['hp']<$p['mhp']*.5 || $p['sp']<150) { fr_job($p,'supplies'); fr_heal($p); }
        if ($p['pls']!==(int)$n['pls'] || $p['clbpara']['flash']['target']!==(int)$n['pid']) { fr_step($p,'track',$n['pid']); continue; }
        $hp=$n['hp']; fr_step($p,'battle');
        if ($p['clbpara']['flash']['pending']) continue;
        $n=flash_npc($key);
        // 只根据实际交战是否造成伤害换武器，不读取房间的弱点答案。
        if ($n['hp']===$hp && in_array($key,array('boss2','boss3'),true)) { $kind++; fr_job($p,'equipment_'.$kinds[$kind%6]); }
    }
    fr_loot($p);
}
function fr_route($seed,$true=false) {
    global $pdata;
    $GLOBALS['fr_seed']=$seed;
    $p=ft_start($seed); $pdata=&$p;
    fr_step($p,'move',flash_neighbours(0)[0]);
    if (!$p['clbpara']['flash']['pending']) fr_step($p,'rewind');
    fr_revival($p);
    if ($true) {
        fr_step($p,'event','drift'); fr_revival($p);
        fr_step($p,'event','kind'); fr_revival($p);
        fr_step($p,'event','chain'); fr_revival($p);
    }
    // 先完成容易的片段，再用逐步提升的委托报酬准备下一片证据。
    foreach (array(4,2,3,5,1) as $i) {
        $goal=$i===4?1200:2500;
        while ($p['att']<$goal) fr_job($p,'stats');
        fr_job($p,'equipment_WK');
        fr_boss($p,'boss'.$i);
    }
    if ($true) {
        if ($p['pls']!==0) fr_step($p,'move',0);
        // 明确选择袭击带路者，走正式的“杀错人”收尾。
        while (!isset($p['clbpara']['quest']['completed']['FL_report_wrong'])) {
            fr_heal($p); fr_step($p,'attack_friend','porter'); fr_revival($p);
        }
        while ($p['att']<220000) fr_job($p,'stats');
        fr_job($p,'equipment_WK');
    }
    fr_heal($p); if ($p['pls']!==0) fr_step($p,'move',0);
    fr_step($p,'stance','suppress'); fr_step($p,'gate');
    $rounds=0;
    while (flash_npc('final')['hp']>0) {
        if (++$rounds>30) throw new RuntimeException('stalled final');
        fr_heal($p); if ($p['sp']<150) { fr_job($p,'supplies'); fr_heal($p); }
        fr_step($p,'battle'); fr_revival($p);
        if (!$p['clbpara']['flash']['gate']) fr_step($p,'gate');
    }
    if ($true) {
        ft_assert(flash_true_available($p),'route earned all true flags'); fr_step($p,'trial'); $rounds=0;
        while (!$p['clbpara']['flash']['result']) {
            if (++$rounds>30) throw new RuntimeException('stalled true guardians');
            fr_heal($p); if ($p['sp']<150) { fr_job($p,'supplies'); fr_heal($p); }
            fr_step($p,'battle');
            if ($p['clbpara']['flash']['pending']) throw new RuntimeException('true route died at final challenge');
        }
    } else fr_step($p,'escape');
    $s=$p['clbpara']['flash'];
    ft_assert($s['result']===($true?'true':'normal'),'route completed');
    ft_assert($s['clicks']<=1000 && $s['stage_max']<=100,'route action click budgets');
    echo ($true?'True':'Normal').' seed '.$seed.': '.$s['clicks'].' action clicks, max stage '.$s['stage_max'].', deaths '.$s['deaths']."\n";
}
if (defined('FLASH_ROUTE_HELPERS')) return;
fr_route(2810);
fr_route(1031,true);
$p=ft_start(); $pdata=&$p;
for ($i=1;$i<=12;$i++) { fr_step($p,'rewind'); if ($i<12) { fr_step($p,'revive'); fr_step($p,'keep','stats'); } }
ft_assert($p['clbpara']['flash']['result']==='lost' && $p['clbpara']['flash']['clicks']===34,'death budget route ends on twelfth death');
echo "Failure: 34 action clicks, 12 deaths, 34 collapsed maps.\n";
