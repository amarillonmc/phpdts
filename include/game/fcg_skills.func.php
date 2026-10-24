<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_skill_has($p, $sk) {
    $c = isset($p['clbpara']) ? $p['clbpara'] : array();
    if(!is_array($c)) $c = json_decode($c, true);
    return !empty($c['skill']) && in_array('tl_fcg_'.$sk, $c['skill'], true);
}
function fcg_attuned($p, $color) {
    return fcg_skill_has($p, 'attune') && ($p['clbpara']['fcg_color'] ?? 'W') === $color;
}
function fcg_phantom($para) {
    if(!is_array($para)) $para = json_decode((string)$para, true);
    return !empty($para['fcg_phantom']);
}
function fcg_copyable($name, $kind, $attrs, $para) {
    if(in_array($name,array('游戏解除钥匙','『G.A.M.E.O.V.E.R』','奇怪的按钮','【E.S.C.A.P.E】'),true)) return false;
    if(in_array($kind,array('WN','DN')) || preg_match('/[Vv^]/',$attrs) || fcg_phantom($para) || !empty($para['unique']) || !empty($para['ending']) || !empty($para['quest'])) return false;
    if(preg_match('/^(W|D|H|M|A)/',$kind)) return true;
    // 材料按当前规则集的实际配方判定，避免将所有 Y 类剧情道具开放复制。
    if($kind==='Y') foreach(get_mixinfo() as $recipe) if(in_array($name,$recipe['stuff'],true)) return true;
    return false;
}
function fcg_skill_log($p, $message) {
    global $log;
    $who = htmlspecialchars($p['nm'] ?? $p['name'] ?? '你', ENT_QUOTES, 'UTF-8');
    $line = '<span class="yellow">'.$who.'：'.$message.'</span><br>';
    $log .= $line;
}
function fcg_memory_card(&$p) {
    $colors = array('W','R','Y','G','B'); $color = $colors[mt_rand(0,4)];
    // 图鉴只提升优质卡出现率，品质上限固定为120%，不会无限放大常规局数值。
    $chance = $p['clbpara']['fcg_quality'][$color] ?? 0;
    return array('color'=>$color, 'quality'=>mt_rand(1,100)<= $chance ? 1.2 : 1);
}
function fcg_skills_initialize(&$p, $user) {
    if(!is_array($p['clbpara'])) $p['clbpara'] = get_clbpara($p['clbpara']);
    $c = &$p['clbpara'];
    if(fcg_skill_has($p,'attune')) { $c['fcg_color']='W'; $c['fcg_charge']=0; }
    if(fcg_skill_has($p,'thorns')) { $c['fcg_thorns_charges']=2; $c['fcg_thorns_steps']=0; }
    if(fcg_skill_has($p,'evade')) { $c['fcg_evade_gauge']=0; $c['fcg_evade_ready']=0; }
    if(fcg_skill_has($p,'imprint')) { $c['fcg_imprint_uses']=2; $c['fcg_imprint_steps']=0; $c['fcg_echo_charge']=0; }
    if(fcg_skill_has($p,'mimic')) { $c['fcg_mimic']='active'; $c['fcg_mimic_steps']=0; $c['fcg_growth']=0; }
    if(fcg_skill_has($p,'chain')) {
        require_once GAME_ROOT.'./include/fcg/engine.php';
        $collection = json_decode($user['fcgdata'] ?? '',true);
        $c['fcg_quality']=array_fill_keys(array('W','R','Y','G','B'),0);
        foreach(fcg_cards() as $card) if(!empty($collection['collection'][$card['id']])) $c['fcg_quality'][$card['color']] += 2;
        $c['fcg_hand']=array(fcg_memory_card($p),fcg_memory_card($p)); $c['fcg_draw_steps']=0; $c['fcg_ready']=0;
    }
}
function fcg_purify(&$p) {
    if(fcg_attuned($p,'G') && ($p['clbpara']['fcg_charge'] ?? 0)>=5 && strpos($p['inf'],'p')!==false) {
        $p['inf']=str_replace('p','',$p['inf']); $p['clbpara']['fcg_charge']-=5;
        $p['hp']=min($p['mhp'],$p['hp']+ceil($p['mhp']*.25));
        fcg_skill_log($p,'绿火将毒素转化为生机，恢复25%最大生命。');
    }
}
function fcg_skills_on_action(&$p) {
    $c=&$p['clbpara'];
    if(fcg_skill_has($p,'attune')) {
        $c['fcg_charge']=min(10,($c['fcg_charge'] ?? 0)+1);
        if(fcg_attuned($p,'G')) { fcg_purify($p); $p['hp']=min($p['mhp'],$p['hp']+max(1,ceil($p['mhp']*.02))); $p['sp']=min($p['msp'],$p['sp']+max(1,ceil($p['msp']*.02))); }
    }
    if(fcg_skill_has($p,'thorns') && ($c['fcg_thorns_charges'] ?? 0)<2) {
        $c['fcg_thorns_steps']=($c['fcg_thorns_steps'] ?? 0)+1;
        if($c['fcg_thorns_steps']>=45) { $c['fcg_thorns_steps']=0; ++$c['fcg_thorns_charges']; fcg_skill_log($p,'苦味剂补充了一层。'); }
    }
    if(fcg_skill_has($p,'evade') && empty($c['fcg_evade_ready'])) {
        $c['fcg_evade_gauge']=($c['fcg_evade_gauge'] ?? 0)+1;
        if($c['fcg_evade_gauge']>=30) { $c['fcg_evade_gauge']=0; $c['fcg_evade_ready']=1; fcg_skill_log($p,'哑弹已经待命。'); }
    }
    if(fcg_skill_has($p,'chain') && count($c['fcg_hand'] ?? array())<3) {
        $c['fcg_draw_steps']=($c['fcg_draw_steps'] ?? 0)+1;
        if($c['fcg_draw_steps']>=15) { $c['fcg_draw_steps']=0; $c['fcg_hand'][]=fcg_memory_card($p); fcg_skill_log($p,'一张新的记忆卡浮现在视野边缘。'); }
    }
    if(fcg_skill_has($p,'imprint') && empty($c['fcg_echo_charge'])) {
        $c['fcg_imprint_steps']=($c['fcg_imprint_steps'] ?? 0)+1;
        if($c['fcg_imprint_steps']>=35) { $c['fcg_imprint_steps']=0; $c['fcg_echo_charge']=1; }
    }
    if(fcg_skill_has($p,'mimic')) {
        $c['fcg_growth']=min(100,($c['fcg_growth'] ?? 0)+.2);
        if(($c['fcg_mimic'] ?? '')==='rebuilding' && ++$c['fcg_mimic_steps']>=40) { $c['fcg_mimic']='active'; $c['fcg_mimic_steps']=0; fcg_skill_log($p,'拟态种火重新凝聚在身旁。'); }
    }
}
function fcg_skill_command(&$p, $command, $input, $blocked=false) {
    global $log,$db,$tablepre,$now,$nosta,$cskills;
    $c=&$p['clbpara'];
    if($blocked || !empty($p['action']) || !empty($p['itms0']) || $p['hp']<=0) { $log.='请先结束当前遭遇并收起手中的物品，再操作种火技能。<br>'; return; }
    if(!isset($input['fcg_nonce']) || !is_scalar($input['fcg_nonce']) || (string)$input['fcg_nonce']!==(string)($c['fcg_nonce'] ?? 0)) { $log.='技能面板已变化，请刷新后重试。<br>'; return; }
    $c['fcg_nonce']=($c['fcg_nonce'] ?? 0)+1;
    if(strpos($command,'fcg_color_')===0 && fcg_skill_has($p,'attune')) {
        $color=substr($command,10); if(in_array($color,array('W','R','Y','G','B'),true)) { $c['fcg_color']=$color; fcg_skill_log($p,'切换了共鸣火苗。'); }
    } elseif($command==='fcg_ready' && fcg_skill_has($p,'chain')) { $c['fcg_ready']=empty($c['fcg_ready']) ? 1:0; fcg_skill_log($p,$c['fcg_ready']?'手牌已装填，将在下次交战按顺序倾泻。':'取消了手牌装填。');
    } elseif(strpos($command,'fcg_card_')===0 && fcg_skill_has($p,'chain')) {
        $index=substr($command,9); if(!ctype_digit($index) || !isset($c['fcg_hand'][(int)$index])) return;
        $card=$c['fcg_hand'][(int)$index]; $q=$card['quality'];
        if($card['color']==='W' && (!$p['wepe'] || $p['wepk']==='WN')) { $log.='请先装备武器。<br>'; return; }
        switch($card['color']) {
            case 'W': $p['wepe']+=ceil($p['wepe']*.15*$q); if($p['weps']!=$nosta) $p['weps']+=ceil(15*$q); break;
            case 'R': $p['rage']=min(255,$p['rage']+round(60*$q)); break;
            case 'Y': $count=(int)$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE hp>0 AND pls=".(int)$p['pls']),0); $log.='此地共有'.$count.'名存活角色（包括你）。<br>'; $p['money']+=round(500*$q); break;
            case 'G': $p['hp']=min($p['mhp'],$p['hp']+ceil($p['mhp']*.6*$q)); $p['sp']=min($p['msp'],$p['sp']+ceil($p['msp']*.6*$q)); $p['inf']=''; foreach($c['skill'] as $i=>$skill) if(strpos($skill,'inf_')===0) { unset($c['skill'][$i],$c['starttimes'][$skill],$c['lasttimes'][$skill]); } $c['skill']=array_values($c['skill']); break;
            case 'B': foreach($c['skill'] as $skill) if(strpos($skill,'tl_fcg_')!==0 && isset($cskills[$skill]['unlock']['skillcooldown'])) $c['starttimes'][$skill]=0; $c['fcg_stealth_until']=$now+round(30*$q); break;
        }
        array_splice($c['fcg_hand'],(int)$index,1); fcg_skill_log($p,'使用了一张记忆卡。');
    } elseif($command==='fcg_echo' && fcg_skill_has($p,'imprint')) {
        if(!empty($c['fcg_echo_ready'])) { $log.='覆唱已经装填。<br>'; return; }
        if(empty($c['fcg_last_hit_dmg'])) { $log.='尚未记录到承受后存活的重击。<br>'; return; }
        if(!empty($c['fcg_echo_charge'])) $c['fcg_echo_charge']=0;
        elseif(!empty($c['fcg_imprint_uses'])) --$c['fcg_imprint_uses'];
        else { $log.='没有可用的拓印权限或覆唱充能。<br>'; return; }
        $c['fcg_echo_ready']=$c['fcg_last_hit_dmg']; fcg_skill_log($p,'覆唱已装填，将在下一次主动交战释放。');
    } elseif(strpos($command,'fcg_copy_')===0 && fcg_skill_has($p,'imprint')) {
        $slot=substr($command,9); if(!preg_match('/^[1-6]$/',$slot) || empty($c['fcg_imprint_uses'])) return;
        $kind=$p['itmk'.$slot]; $para=get_itmpara($p['itmpara'.$slot]); $attrs=$p['itmsk'.$slot];
        // 仅普通装备、恢复品与材料；剧情、权限、容器、诅咒及灵魂绑定物品不属于可拓印器物。
        if(empty($p['itms'.$slot]) || !fcg_copyable($p['itm'.$slot],$kind,$attrs,$para)) { $log.='这件物品无法拓印。<br>'; return; }
        $to=0; for($i=1;$i<=6;$i++) if(empty($p['itms'.$i])) { $to=$i; break; }
        if(!$to) { $log.='请先空出一个背包位置。<br>'; return; }
        foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $p[$key.$to]=$p[$key.$slot];
        $p['itm'.$to]='★幻影·'.$p['itm'.$slot]; $p['itms'.$to]=$p['itms'.$slot]==$nosta?25:min(25,(int)$p['itms'.$slot]);
        $para['fcg_phantom']=1; $p['itmpara'.$to]=$para; $p['itmsk'.$to].='v'; --$c['fcg_imprint_uses']; fcg_skill_log($p,'记忆中的器物在掌心重现。');
    }
}

function fcg_battle_begin(&$p) {
    if(!isset($p['ex_keys'])) $p['ex_keys']=array_merge(\revattr\get_equip_ex_array($p),\revattr\get_wep_ex_array($p));
    $p['fcg_battle']=array('hp'=>$p['hp'],'heal'=>0,'att'=>1,'def'=>1,'floor'=>0,'pierce'=>0,'shield'=>0,'red'=>false,'crit'=>false);
    fcg_purify($p);
    if(fcg_attuned($p,'W') && ($p['clbpara']['fcg_charge'] ?? 0)>=5) {
        $p['clbpara']['fcg_charge']-=5;
        $p['fcg_battle']['shield']=round((($p['arbe'] ?? 0)+($p['arhe'] ?? 0)+($p['arae'] ?? 0)+($p['arfe'] ?? 0))*.3);
        fcg_skill_log($p,'白火凝成了'.$p['fcg_battle']['shield'].'点抵伤护盾。');
    }
}
function fcg_battle_opening(&$p,&$enemy,$active,$initiator) {
    if(fcg_skill_has($p,'chain') && !empty($p['clbpara']['fcg_ready'])) {
        $hand=$p['clbpara']['fcg_hand']; $p['clbpara']['fcg_hand']=array(); $p['clbpara']['fcg_ready']=0;
        foreach($hand as $card) {
            $q=$card['quality'];
            switch($card['color']) {
                case 'W': $p['fcg_battle']['att']+=.25*$q; $p['fcg_battle']['def']+=.25*$q; fcg_skill_log($p,'甩出【折叠铁】，本场攻防提升。'); break;
                case 'R': $dmg=min(350,round(\revattr\get_base_att($p,$enemy,$active)*.4*$q)); if(!fcg_direct_hit($p,$enemy,$dmg,$active,'弹开的射线')) return false; break;
                case 'Y':
                    foreach(array('arb','arh','ara','arf') as $slot) if(!empty($enemy[$slot.'s']) && $enemy[$slot.'s']!==$GLOBALS['nosta']) armor_hurt($enemy,$slot,ceil(5*$q));
                    $p['fcg_battle']['pierce']=min(.75,$p['fcg_battle']['pierce']+.5*$q); fcg_skill_log($p,'甩出【十三号钻头】，削损敌方防具并获得物理穿透。'); break;
                case 'G': $p['fcg_battle']['heal']=min(1,$p['fcg_battle']['heal']+.5*$q); fcg_skill_log($p,'甩出【黄金秋刀鱼】，将在战后回收损耗的生命。'); break;
                case 'B': $own=\revattr\get_base_att($p,$enemy,$active); $other=\revattr\get_base_att($enemy,$p,1-$active); if($other>$own) $p['fcg_battle']['floor']=max($p['fcg_battle']['floor'],round($other*min(1,.9*$q))); fcg_skill_log($p,'甩出【镜面同名者】，映出敌人的攻击姿态。'); break;
            }
            if($p['hp']<=0 || $enemy['hp']<=0) return false;
        }
    }
    if($initiator && fcg_skill_has($p,'imprint') && !empty($p['clbpara']['fcg_echo_ready'])) {
        $record=$p['clbpara']['fcg_echo_ready']; unset($p['clbpara']['fcg_echo_ready']);
        $dmg=min(600,round($record['damage']*.8)); $keys=$record['elements'] ?? array();
        // 以原击的物理/属性占比覆唱；属性部分重新经过当前目标的属性防御。
        $elementDamage=0;
        if($keys) {
            $copy=$p; $copy['ex_attack_keys']=$keys;
            $enemy['ex_keys']=array_merge(\revattr\get_equip_ex_array($enemy),\revattr\get_wep_ex_array($enemy));
            \revattr\deal_ex_damage_prepare_events($copy,$enemy,$active);
            $element=$dmg*min(1,max(0,$record['element_ratio'] ?? 1)); $damage=$dmg-$element;
            foreach($keys as $key) {
                if(($enemy['ex_def_flag'] ?? null)===2) $damage+=1;
                else $damage+=max(0,\revattr\get_ex_base_dmg_p($copy,$enemy,$active,$key,$element/count($keys)))*(fcg_attuned($enemy,'B')?.8:1);
            }
            $elementDamage=max(0,$damage-($dmg-$element)); $dmg=round($damage);
        }
        $snapshot=array('ex_attack_keys'=>$keys,'ex_damage'=>$elementDamage,'final_damage'=>$dmg);
        if(!fcg_direct_hit($p,$enemy,$dmg,$active,'模因覆唱',true,$snapshot)) return false;
    }
    return true;
}
function fcg_defend_damage(&$attacker,&$defender,$damage,$react=true) {
    if($damage<=0) return $damage;
    $c=&$defender['clbpara'];
    $shield=$defender['fcg_battle']['shield'] ?? 0;
    if($shield>0) { $absorbed=min($shield,$damage); $damage-=$absorbed; $defender['fcg_battle']['shield']-=$absorbed; fcg_skill_log($defender,'白火护盾抵消了'.$absorbed.'点伤害。'); }
    $danger=!empty($attacker['bskill']) || !empty($attacker['skill_c1_crit']) || !empty($attacker['fcg_critical']) || ($attacker['hitrate_times'] ?? 0)>1 || $damage >= $defender['hp']*.6;
    if($react && $damage>0 && fcg_skill_has($defender,'evade') && !empty($c['fcg_evade_ready']) && $danger) {
        $c['fcg_evade_ready']=0; $defender['rage']=min(255,$defender['rage']+50); $defender['fcg_battle']['crit']=true;
        fcg_skill_log($defender,'「咔哒——不可思议的巧合发生了，致命的攻势像哑弹一样卡在了半空！」'); return 1;
    }
    if($react && fcg_skill_has($defender,'thorns') && !empty($c['fcg_thorns_charges']) && $damage>$defender['mhp']*.35 && ceil($damage/2)<$defender['hp']) {
        --$c['fcg_thorns_charges']; $cut=floor($damage/2); $damage-=$cut;
        $defender['fcg_reflect']=min($defender['mhp'],round($cut*1.5));
        fcg_skill_log($defender,'苦味剂截断了'.$cut.'点重创。');
    }
    return max(0,(int)$damage);
}
function fcg_record_hit(&$p,$attacker,$damage) {
    if($p['hp']>0 && $damage>=150 && fcg_skill_has($p,'imprint')) {
        $keys=$attacker['ex_attack_keys'] ?? array();
        $p['clbpara']['fcg_last_hit_dmg']=array('damage'=>$damage,'elements'=>$keys,'element_ratio'=>min(1,($attacker['ex_damage'] ?? 0)/max(1,$attacker['final_damage'] ?? $damage)));
    }
}
function fcg_mimic_guard(&$p) {
    if($p['hp']<=0 && fcg_skill_has($p,'mimic') && ($p['clbpara']['fcg_mimic'] ?? '')==='active') {
        $p['hp']=1; $p['clbpara']['fcg_mimic']='rebuilding'; $p['clbpara']['fcg_mimic_steps']=0;
        fcg_skill_log($p,'拟态种火挡下了致命一击，化作等待重构的代码光点。'); return true;
    }
    return false;
}
function fcg_resolve_reflect(&$p,&$enemy,$active) {
    if(empty($p['fcg_reflect'])) return true;
    $damage=$p['fcg_reflect']; unset($p['fcg_reflect']);
    if($enemy['hp']<=0) return false;
    if(strpos($enemy['inf'],'p')===false) $enemy['inf'].='p';
    return fcg_direct_hit($p,$enemy,$damage,$active,'因果反噬',false);
}
function fcg_direct_hit(&$p,&$enemy,$damage,$active,$label,$react=true,$snapshot=array()) {
    if($p['hp']<=0 || $enemy['hp']<=0) return false;
    // 独立伤害不走物理防御，但仍尊重模式免疫、种火伤害制御等现有最终伤害规则。
    $p['fcg_packet']=true;
    $multipliers=\revattr\get_final_dmg_p($p,$enemy,$active);
    foreach($multipliers as $multiplier) if($multiplier<1) $damage=round($damage*$multiplier);
    $damage=\revattr\get_final_dmg_fix($p,$enemy,$active,$damage);
    unset($p['fcg_packet']);
    $damage=club23_adjust_damage($p,$enemy,$damage);
    $damage=fcg_defend_damage($p,$enemy,$damage,$react);
    $enemy['hp']=max(0,$enemy['hp']-$damage); fcg_skill_log($p,$label.'造成'.$damage.'点独立伤害。');
    \revcombat\checkdmg($p['name'],$enemy['name'],$damage);
    $result=\revcombat\rev_combat_result($p,$enemy,$active);
    fcg_record_hit($enemy,$snapshot,$damage);
    if($react && !fcg_resolve_reflect($enemy,$p,1-$active)) return false;
    return $result && $p['hp']>0 && $enemy['hp']>0;
}
function fcg_battle_after_attack(&$p,&$enemy,$damage,$active) {
    fcg_record_hit($enemy,$p,$damage);
    if(!fcg_resolve_reflect($enemy,$p,1-$active)) return false;
    if($p['hp']<=0 || $enemy['hp']<=0) return false;
    if($damage>0 && fcg_attuned($p,'R') && empty($p['is_counter']) && empty($p['fcg_battle']['red']) && ($p['clbpara']['fcg_charge'] ?? 0)>=5) {
        $p['clbpara']['fcg_charge']-=5; $p['fcg_battle']['red']=true;
        if(!fcg_direct_hit($p,$enemy,round($damage*.35),$active,'红火践踏')) return false;
    }
    if($damage>0 && ($p['clbpara']['fcg_charge'] ?? 0)>=5) {
        if(fcg_attuned($p,'Y')) { $take=min(15,$enemy['rage']); $enemy['rage']-=$take; $p['rage']=min(255,$p['rage']+$take); $p['clbpara']['fcg_charge']-=5; fcg_skill_log($p,'黄火窃取了'.$take.'点怒气。'); }
        elseif(fcg_attuned($p,'B')) { $enemy['clbpara']['fcg_counter_debuff']=1; $p['clbpara']['fcg_charge']-=5; fcg_skill_log($p,'蓝火令对手的下一次反击概率减半。'); }
    }
    if(fcg_skill_has($p,'mimic') && ($p['clbpara']['fcg_mimic'] ?? '')==='active') {
        $skill=max($p['wp'],$p['wk'],$p['wg'],$p['wc'],$p['wd'],$p['wf']);
        $power=round(2*$p['lvl']+.15*$skill+($p['clbpara']['fcg_growth'] ?? 0));
        if(!fcg_direct_hit($p,$enemy,$power,$active,'种火追击')) return false;
    }
    return true;
}
function fcg_battle_end(&$p,$enemy) {
    if(empty($p['fcg_battle'])) return;
    if($p['hp']>0 && $p['fcg_battle']['heal']>0) {
        $restore=ceil(max(0,$p['fcg_battle']['hp']-$p['hp'])*$p['fcg_battle']['heal']); $p['hp']=min($p['mhp'],$p['hp']+$restore);
        fcg_skill_log($p,'黄金秋刀鱼回收了'.$restore.'点损耗的生命。');
    }
    if($enemy['hp']<=0 && fcg_skill_has($p,'mimic')) $p['clbpara']['fcg_growth']=min(100,($p['clbpara']['fcg_growth'] ?? 0)+5);
    unset($p['fcg_battle'],$p['fcg_reflect'],$p['fcg_critical']);
}
