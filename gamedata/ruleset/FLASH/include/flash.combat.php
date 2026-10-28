<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_role($p) { return $p['clbpara']['flash_npc']['role']??''; }
function flash_is_final($p) { return in_array(flash_role($p),array('final','hunter','guardian'),true); }
function flash_clean(&$p) {
    // 同一点击内多次走原生战斗时，不把上一战的临时技能标记带入下一战。
    $fields=player_format_with_db_structure($p);
    foreach (array_keys($p) as $key) if (!array_key_exists($key,$fields)) unset($p[$key]);
    // 不替换整个 clbpara 数组：调用方仍持有其中 flash/quest 的引用。
    if (!is_array($p['clbpara'])) $p['clbpara']=get_clbpara($p['clbpara']);
    foreach (get_equip_list() as $slot) $p[$slot.'para']=get_itmpara($p[$slot.'para']);
    for ($i=0;$i<=6;$i++) $p['itmpara'.$i]=get_itmpara($p['itmpara'.$i]);
}
function flash_prepare_fighter(&$a,&$b) {
    global $pdata;
    if (!$a['type'] && !empty($a['clbpara']['flash']['food_buff'])) $a['bskill_c2_raiding']=1;
    if (flash_is_final($a) || flash_role($a)==='jojo') {
        // 第一击三段连击，再用既有海虎/快拳形式追加四次单段攻击，共七段。
        $a['flash_chain']=true; $a['skill_c12_swell']=4;
    }
    if (flash_role($a)==='jojo' && $a['rage']>=100) {
        $a['rage']-=70; $a['bskill_c2_raiding']=1;
        $pdata['inf']=''; flash_say('乔乔：「哇哇，开饭了的说——！！」熟悉的声音把疼痛和晕眩赶了出去。');
    }
    if (flash_is_final($a)) {
        $meta=&$a['clbpara']['flash_npc']; $meta['turns']=($meta['turns']??0)+1;
        if ($meta['turns']%3===1) {
            $pdata['clbpara']['flash']['snare']=3; $a['bskill_c7_electric']=1;
            flash_say('藤蔓收紧。接下来三次交战中，你无法移动、离开或沿旧路返回。');
        }
    }
}
function ruleset_combat_allowed_hook(&$a,&$b) {
    if ($a['hp']<=0 || $b['hp']<=0 || (int)$a['pls']!==(int)$b['pls']) return false;
    if (!$a['type'] && !$b['type']) return false;
    flash_prepare_fighter($a,$b); flash_prepare_fighter($b,$a);
    $owner=&$GLOBALS['pdata']; $s=$owner['clbpara']['flash']; $j=flash_npc('jojo');
    if ($j && $j['hp']>0 && !$s['jojo_missing'] && (int)$j['pls']===(int)$owner['pls']) {
        $enemy=&$a; if (!$a['type'] || flash_role($a)==='jojo') $enemy=&$b;
        if ($s['stance']==='suppress') { $enemy['flash_suppressed']=true; flash_say('乔乔：「这就是大人的乐趣的说……！」她绕到敌人背后，打乱了连续出招的节奏。'); }
        if ($s['fed']>=6 && $j['hp']>=$j['mhp']*.8) {
            if (!$a['type']) $a['skill_fireseed1']=1;
            if (!$b['type']) $b['skill_fireseed1']=1;
        }
    }
    return true;
}
function ruleset_hit_count_hook(&$a,&$b,$active,$count) {
    if (!empty($a['flash_suppressed'])) {
        unset($a['skill_c12_swell'],$a['skill_c13_quick'],$a['bskill_c5_double']); return 1;
    }
    if (!empty($a['flash_chain'])) return isset($a['skill_c12_swell']) && $a['skill_c12_swell']===4?3:1;
    return $count;
}
function ruleset_final_damage_fix_hook(&$a,&$b,$active,$damage) {
    $meta=$b['clbpara']['flash_npc']??array();
    if (($meta['rule']??'')==='weak') {
        $weak=$GLOBALS['gamevars']['flash']['weak'][$meta['evidence']];
        if (($a['wep_kind']??'')!==$weak) { flash_say('攻击在镜面上滑开，没留下伤口。'); return 0; }
    }
    return null;
}
function ruleset_get_fix_damage_hook(&$a,&$b,$active) {
    $meta=$b['clbpara']['flash_npc']??array();
    if (($meta['rule']??'')==='weak' && ($a['wep_kind']??'')!==$GLOBALS['gamevars']['flash']['weak'][$meta['evidence']]) return 0;
    return null;
}
function ruleset_attack_damage_hook(&$a,&$b,$active,$damage) {
    $meta=$b['clbpara']['flash_npc']??array();
    if (($meta['rule']??'')==='weak' && ($a['wep_kind']??'')!==$GLOBALS['gamevars']['flash']['weak'][$meta['evidence']]) return 0;
    if (!$a['type'] && flash_ready($a) && $a['clbpara']['flash']['stance']==='attack' && mt_rand(1,100)<=40) {
        $j=flash_npc('jojo');
        if ($j && $j['hp']>0 && !$a['clbpara']['flash']['jojo_missing'] && (int)$j['pls']===(int)$a['pls']) {
            $j['nm']='乔乔'; flash_prepare_fighter($j,$b);
            \revcombat\get_attr_wepbase($j,$b,1);
            $extra=0;
            for ($i=0;$i<5;$i++) {
                $extra+=max(0,\revcombat\rev_attack($j,$b,1));
                if (!\revcombat\attack_check_can_loop($j,$b,1)) break;
            }
            $j['rage']=min($GLOBALS['mrage'],$j['rage']+20); flash_limit_stats($j); player_save($j);
            flash_say('乔乔从侧面跃起，利爪追上了你的攻击。');
            return $damage+$extra;
        }
    }
    return $damage;
}
function ruleset_backlash_damage_hook(&$a,&$b,$damage) { return flash_is_final($a)?0:$damage; }
function ruleset_combat_result_hook(&$a,&$b,$active) {
    if ($b['hp']>0) return null;
    if (!$b['type'] && !empty($b['clbpara']['flash'])) { flash_die($b,$a['name'],$a); return 0; }
    $role=flash_role($b); if ($role==='') return null;
    $owner=&$GLOBALS['pdata']; $s=&$owner['clbpara']['flash']; $meta=&$b['clbpara']['flash_npc'];
    if (flash_is_final($b) && ($meta['rewinds']??0)<2 && strpos($owner['inf'],'e')!==false) {
        $meta['rewinds']=($meta['rewinds']??0)+1; $b['hp']=$b['mhp'];
        flash_say('麻痹使你松开了手。剪影把这一瞬折回原处，伤口全部消失。交战前清除麻痹，可以截断回溯。'); return 0;
    }
    if (!empty($meta['rewarded'])) return 0;
    $meta['rewarded']=true; $b['hp']=0; $b['action']=''; $b['bid']=0;
    if ($role==='jojo') {
        $s['jojo_deaths']++; $s['jojo_missing']=true;
        flash_story($owner,'没能握住的手','少女倒下时，你伸手去接，却抓到一片空白。视野一同暗了下去。');
        flash_die($owner,'乔乔倒下'); return 0;
    }
    $owner['killnum']++; $owner['money']+=(int)$b['money']; $b['money']=0;
    $owner['exp']+=max(20,(int)$b['lvl']*4);
    lvlup_rev($owner,$b,1);
    flash_job_event($owner,$role);
    if ($role==='boss') {
        if (flash_mark($owner,'FL_evidence_'.$meta['evidence'],$b['name'])) { flash_stage($owner); flash_say('一片证据落在你的掌心。它会跟着你回去。'); }
        $b['clbpara']['flash_npc']['looted']=true;
        flash_loot($owner,array(flash_item('折好的补给纸包','HB',max(200,(int)($owner['mhp']*.4)),4),flash_item('稳定的制御环','A',1,200,'H')));
    } elseif ($role==='friend' && empty($meta['silence'])) {
        $key=str_replace('friend_','',$meta['key']); $s['friends_killed'][$key]=true;
        if (empty($owner['clbpara']['quest']['completed']['FL_report_wrong'])) {
            flash_report($owner,'wrong'); flash_story($owner,'杀错人','她倒下时仍指着路口。你还没有看清她想说什么，背后的刀已经刺了过来。'); flash_die($owner,'错误的杀意');
        }
    } elseif ($role==='final') {
        $s['target']=0; $s['snare']=0; flash_stage($owner);
        flash_story($owner,'门开了','重兵器垂了下去。剪影让开半步，身后的光里传来清晨的声音。你可以离开。乔乔却看向光线之外，像听见了另一个脚步。');
    } elseif ($role==='guardian') {
        $hunter=flash_npc('hunter');
        if (!$hunter || $hunter['hp']<=0) flash_finish($owner,'true');
    } elseif ($role==='hunter') {
        $guardian=flash_npc('guardian');
        if (!$guardian || $guardian['hp']<=0) flash_finish($owner,'true');
    }
    return 0;
}
function ruleset_battle_finish_hook(&$a,&$b) {
    foreach (array(&$a,&$b) as &$n) {
        $n['action']=''; $n['bid']=0;
        flash_limit_stats($n);
        // 怒气与清理临时标记的时机与原生战斗相同，永不把回溯后的旧位置写回来。
        if (flash_role($n)==='jojo') $n['rage']=min($GLOBALS['mrage'],$n['rage']+20);
        if (!$n['type'] && !empty($n['clbpara']['flash']['food_buff'])) $n['clbpara']['flash']['food_buff']--;
    }
    unset($n);
}
function ruleset_natural_death_hook(&$p,$death,$name='',$type=0,$annex='') {
    if (!$p['type'] && !empty($p['clbpara']['flash'])) {
        $causes=array('trap'=>'陷阱','poison'=>'中毒','hack'=>'通路崩塌','injured'=>'伤势恶化','area'=>'崩坏区域');
        flash_die($p,$name?:($annex?:($causes[$death]??'意外'))); return true;
    }
    return null;
}
function ruleset_move_allowed_hook(&$p,$dest) {
    return flash_ready($p) && empty($p['clbpara']['flash']['snare']) && isset($GLOBALS['xyinfo'][$dest]) && ($dest===0 || !in_array($dest,flash_closed(),true) || !empty($p['clbpara']['flash']['path']));
}
function flash_fight(&$p,$id,$jojo=false,$rounds=1,$charge=true,$ambush=false) {
    $s=&$p['clbpara']['flash']; $enemy=fetch_playerdata_by_pid($id);
    if (!$enemy || $enemy['hp']<=0 || !$enemy['type'] || (int)$enemy['pls']!==(int)$p['pls'] || flash_role($enemy)==='jojo') throw new RuntimeException('目标已经不在这里。');
    if (flash_is_final($enemy) && !$s['gate'] && !(flash_role($enemy)==='hunter' && $s['trial']) && !($ambush && flash_role($enemy)==='final' && !empty($s['events']['gate_opened']))) throw new RuntimeException('先到门前，拼好五片证据。');
    if (in_array(flash_role($enemy),array('hunter','guardian'),true) && !$s['trial']) throw new RuntimeException('那是门另一边的脚步。');
    if ($jojo && ($s['jojo_missing'] || !($j=flash_npc('jojo')) || $j['hp']<=0 || (int)$j['pls']!==(int)$p['pls'])) throw new RuntimeException('乔乔现在不能替你出击。');
    $s['target']=$id;
    require_once GAME_ROOT.'include/game/revcombat.func.php';
    for ($i=0;$i<$rounds && flash_ready($p);$i++) {
        if ($charge && $p['sp']<30) { if (!$i) throw new RuntimeException('战斗需要 30 体力。'); break; }
        if ($charge) flash_pay($p,30);
        flash_clean($p); $GLOBALS['pdata']=&$p;
        $enemy=fetch_playerdata_by_pid($id); if (!$enemy || $enemy['hp']<=0) break;
        $fighter=&$p;
        if ($jojo) { $j=flash_npc('jojo'); $fighter=&$j; }
        // 先制规则落在每次行动，不靠真人抢点；挠痒会中止连续先制。
        $near=flash_npc('jojo');
        $suppress=$s['stance']==='suppress' && !$s['jojo_missing'] && $near && $near['hp']>0 && (int)$near['pls']===(int)$p['pls'];
        $first=(flash_is_final($enemy) || $ambush) && !$suppress;
        if ($first) \revcombat\rev_combat_prepare($enemy,$fighter,0,'',0);
        else \revcombat\rev_combat_prepare($fighter,$enemy,1,'',0);
        unset($fighter);
        if (flash_ready($p)) {
            if ($s['snare']>0) $s['snare']--;
            flash_erosion($p);
        }
        flash_clean($p); $GLOBALS['pdata']=&$p;
        if ($enemy['hp']<=0 || !flash_ready($p) || $p['hp']<=$p['mhp']*flash_cfg('sweep_hp')) break;
    }
    if ($enemy['hp']<=0) $p['clbpara']['flash']['target']=0;
    player_save($p);
}
function ruleset_ai_event_hook($rate,&$p) {
    if (!$p || !flash_ready($p)) return true;
    $s=&$p['clbpara']['flash'];
    foreach (flash_npcs() as $npc) {
        if ($npc['hp']<=0) continue;
        $m=$npc['clbpara']['flash_npc']??array();
        if (!empty($m['flee']) && (int)$npc['pls']===(int)$p['pls'] && $s['target']!==(int)$npc['pid']) {
            $safe=array_values(array_diff(flash_safe(),array(0,(int)$p['pls'])));
            if ($safe) { $npc['pls']=$safe[array_rand($safe)]; player_save($npc); }
        }
        $chase=!empty($m['silence']) || (($m['role']??'')==='hunter' && $s['trial'] && $s['actions']%3===0)
            || (($m['role']??'')==='final' && !empty($s['events']['gate_opened']) && !$s['gate'] && $s['actions']%3===0);
        if ($chase) {
            $npc['pls']=$p['pls']; player_save($npc); $s['target']=(int)$npc['pid'];
            flash_say('追赶你的脚步到了。接下来的行动被迫中止。');
            // NPC 的主动追击不扣玩家的行动体力；每次大行动只执行一轮。
            flash_fight($p,(int)$npc['pid'],false,1,false,true); break;
        }
    }
    return true;
}
