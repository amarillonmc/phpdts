<?php
if (!defined('IN_GAME')) exit('Access Denied');
require_once __DIR__.'/elevator.skills.config.php';

function elevator_has_skill($p,$id) {
    $c=elevator_decode($p['clbpara']??array());
    if (empty($c['elevator']['on']) && !isset($c['elevator_npc'])) return false;
    if (in_array('el_'.$id,$c['skill']??array(),true)) return true;
    foreach (array('wep','wep2','arb','arh','ara','arf','art') as $slot) {
        $para=elevator_decode($p[$slot.'para']??array());
        if (!empty($p[$slot.'s']) && ($para['elevator_skill']??'')==='el_'.$id) return true;
    }
    return false;
}
function elevator_skill_value($id,$key) { $defs=elevator_skill_definitions(); return $defs[$id][2][$key]; }
function elevator_round_skill_pool() {
    global $gamevars;
    if (!isset($gamevars['elevator']['skills'])) {
        $defs=elevator_skill_definitions(); $ids=array_values(array_filter(elevator_skill_pool_config(),function($id)use($defs){ return isset($defs[$id]) && empty($defs[$id][2]['npc']); }));
        shuffle($ids); $gamevars['elevator']['skills']=array_map(function($id){return 'el_'.$id;},array_slice($ids,0,max(3,elevator_config('skill_pool_size'))));
    }
    return $gamevars['elevator']['skills'];
}
function elevator_skill_book($skill) { return array('itm'=>'夹层技能笔记','itmk'=>'VS','itme'=>1,'itms'=>1,'itmsk'=>$skill,'itmpara'=>array()); }
function elevator_skill_description($skill) {
    global $cskills;
    $defs=elevator_skill_definitions(); $def=$defs[(int)substr($skill,3)]??array($cskills[$skill]['name']??$skill,strip_tags($cskills[$skill]['desc']??''));
    return '<b>'.elevator_html($def[0]).'</b><p>'.elevator_html($def[1]).'</p>';
}
function elevator_grant_skill(&$p,$skill) {
    global $cskills,$gamevars,$db,$tablepre;
    $id=(int)substr($skill,3); $defs=elevator_skill_definitions();
    if (!isset($defs[$id])) return getclubskill($skill,$p['clbpara']);
    if ($id===572) {
        $q=$db->query("SELECT data FROM {$tablepre}elevator_floor");
        while ($row=$db->fetch_array($q)) {
            $d=elevator_decode($row['data']); foreach ($d['tpls'] as $i=>$tpl) elevator_ground(array('itm'=>'丰收补给','itmk'=>'HB','itme'=>120,'itms'=>1,'itmsk'=>'','itmpara'=>array()),elevator_pls($d['floor'],$i+1));
        }
        elevator_personal($p,'丰收的回声散入大楼，每处已抵达的地点都多了一份补给。'); return true;
    }
    if (in_array($skill,$p['clbpara']['skill']??array(),true)) { elevator_personal($p,'你已经掌握「'.$defs[$id][0].'」，笔记仍可赠给同行者。'); return false; }
    // 技能配置由 RuleSet cache 加载，独立脚本也能使用同一份定义。
    if (!isset($cskills[$skill])) $cskills[$skill]=array('name'=>$defs[$id][0],'tags'=>array('passive'),'desc'=>$defs[$id][1]);
    getclubskill($skill,$p['clbpara']);
    if ($id===755 || $id===455) $p['clbpara']['skillpara'][$skill]['uses']=elevator_skill_value($id,'uses');
    elevator_personal($p,'学会了「'.$defs[$id][0].'」。'.$defs[$id][1]); return true;
}
function elevator_find_rate($p,$rate) {
    if (elevator_curse_level($p,'903')>=2) $rate*=.8;
    if (elevator_has_skill($p,582)) $rate*=elevator_skill_value(582,'find');
    return $rate;
}
function elevator_skill_action(&$p,$id,$args,$e,$at) {
    if (!elevator_has_skill($p,$id)) throw new RuntimeException('你尚未掌握这项技能。');
    $s=&$p['clbpara']['skillpara']['el_'.$id]; $choice=(string)($args['choice']??'');
    if ($id===458) {
        $slot=(int)($args['slot']??-1);
        if ($slot<1 || $slot>6 || empty($p['itms'.$slot]) || preg_match('/^[HP]/',$p['itmk'.$slot]) || !hash_equals(elevator_slot_token($p,$slot),(string)($args['token']??''))) throw new RuntimeException('请选择背包中一件尚未变化的非补给物品。');
        $item=elevator_slot($p,$slot); $item['itm']='鸡肉'; $item['itmk']='PB2'; $item['itmsk']=(string)$p['pid'];
        $item['itmpara']=array(); if ($item['itms']==='∞') $item['itms']=elevator_skill_value(458,'infinite_count'); elevator_set_slot($p,$slot,$item);
    } elseif ($id===738) {
        if (!in_array($choice,array('1','2','3'),true)) throw new RuntimeException('请选择低挡、普通或高挡。'); $s['gear']=(int)$choice;
    } elseif ($id===422) { $s['melee']=empty($s['melee']); }
    elseif ($id===570) {
        if (!empty($s['burst'])) throw new RuntimeException('入道已经准备好，等待下一次命中。');
        if ($p['rage']<elevator_skill_value(570,'cost')) throw new RuntimeException('怒气不足50。');
        $p['rage']-=elevator_skill_value(570,'cost'); $s['burst']=true;
    } elseif ($id===455) {
        if (($s['uses']??0)<1) throw new RuntimeException('本局的无敌次数已用尽。');
        $s['uses']--; $s['until']=$at+elevator_skill_value(455,'duration');
    } elseif ($id===430) {
        $slot=(int)($args['slot']??-1); $token=(string)($args['token']??'');
        if ($choice==='store') {
            if ($slot<0 || $slot>6 || empty($p['itms'.$slot]) || !hash_equals(elevator_slot_token($p,$slot),$token)) throw new RuntimeException('物品已发生变化。');
            if (count($s['bag']??array())>=elevator_skill_value(430,'slots')) throw new RuntimeException('随身行囊已满。');
            $s['bag'][]=elevator_slot($p,$slot); elevator_set_slot($p,$slot);
        } elseif ($choice==='take') {
            if (!isset($s['bag'][$slot]) || !hash_equals(hash('sha256',json_encode($s['bag'][$slot],JSON_UNESCAPED_UNICODE)),$token)) throw new RuntimeException('行囊中的物品已变化。');
            if (!empty($p['itms0'])) throw new RuntimeException('请先处理手中的物品。');
            $item=$s['bag'][$slot]; array_splice($s['bag'],$slot,1); elevator_give($p,$item);
        } else throw new RuntimeException('请选择存入或取回。');
    } elseif ($id===443) {
        $npc=fetch_playerdata_by_pid((int)($args['target']??0));
        if (!$npc || !$npc['type'] || $npc['hp']<=0 || !elevator_can_fight($p,$npc)) throw new RuntimeException('无法与这个目标决斗。');
        if (!empty($npc['clbpara']['elevator_npc']['quest'])) throw new RuntimeException('请按这份委托的要求与目标互动。');
        $npc['clbpara']['elevator_npc']['duel']=array('owner'=>$p['pid'],'until'=>$at+elevator_skill_value(443,'duration'));
        player_save($npc); elevator_focus($p,(int)$npc['pid']);
    } else throw new RuntimeException('这是一项自动生效的能力。');
}
function elevator_sp_cost($p,$cost) {
    if (empty($p['clbpara']['elevator']) && empty($p['clbpara']['elevator_npc'])) return $cost;
    if (elevator_has_skill($p,738)) {
        $gear=$p['clbpara']['skillpara']['el_738']['gear']??2;
        if ($gear===1) $cost+=elevator_skill_value(738,'low'); elseif ($gear===3) $cost+=elevator_skill_value(738,'high');
    }
    if (elevator_has_skill($p,761) && $p['rage']<=elevator_skill_value(761,'rage')) $cost-=elevator_skill_value(761,'sp');
    return max(1,$cost);
}
function elevator_skill_physical($a,$b) {
    $rate=1;
    if (elevator_has_skill($b,401)) { $values=elevator_skill_value(401,'reduce'); $rate*=1-$values[min(count($values)-1,(int)floor(($b['lvl']??0)/10))]/100; }
    if (elevator_has_skill($a,760) && !empty($a['is_counter'])) $rate*=1+min(elevator_skill_value(760,'cap'),$a['clbpara']['skillpara']['el_760']['hits']??0)*elevator_skill_value(760,'gain');
    return $rate;
}
function elevator_final_damage(&$a,&$b,$damage) {
    global $now;
    if (!isset($a['clbpara']['elevator']) && !isset($a['clbpara']['elevator_npc'])) return $damage;
    if (elevator_has_skill($b,455) && ($b['clbpara']['skillpara']['el_455']['until']??0)>$now) return 0;
    foreach (array(515,449,456,558,471) as $id) {
        if (!elevator_has_skill($a,$id)) continue;
        if ($id===449 && !in_array((int)$b['type'],array(96,97,98),true)) continue;
        if ($id===456 && !empty($a['is_counter'])) continue;
        if ($id===558 && $b['mhp']<=$a['mhp']) continue;
        if ($id===471 && ($a['wep_kind']??substr($a['wepk'],1,1))!=='F') continue;
        $damage*=elevator_skill_value($id,'damage');
    }
    if ($damage>0 && elevator_has_skill($a,570) && !empty($a['clbpara']['skillpara']['el_570']['burst'])) { $damage*=elevator_skill_value(570,'damage'); unset($a['clbpara']['skillpara']['el_570']['burst']); }
    foreach (array($a,$b) as $p) if (elevator_has_skill($p,761) && $p['rage']>elevator_skill_value(761,'rage')) $damage*=elevator_skill_value(761,'damage');
    $npc=$a['type']?$a:$b; $duel=$npc['clbpara']['elevator_npc']['duel']??array();
    if (($duel['until']??0)>$now && in_array($duel['owner'],array($a['pid'],$b['pid']))) $damage*=elevator_skill_value(443,'damage');
    $lvl=elevator_curse_level($b,'904'); if ($lvl) $damage*=array(1,1.1,1.2,1.35)[min(3,$lvl)];
    if (elevator_has_skill($b,513) && !empty($b['clbpara']['skillpara']['el_513']['barrier'])) {
        $barrier=&$b['clbpara']['skillpara']['el_513']['barrier']; $absorb=min($barrier,$damage); $barrier-=$absorb; $damage-=$absorb;
    }
    return max(0,(int)round($damage));
}
function elevator_hurt_events(&$a,&$b,$active) {
    elevator_curse_hurt($a,$b);
    if (($a['final_damage']??0)<=0) return;
    if (elevator_has_skill($b,464)) { $best='wp'; foreach (array('wk','wg','wc','wd','wf') as $key) if ($b[$key]>$b[$best]) $best=$key; $b[$best]+=elevator_skill_value(464,'gain'); }
    if (elevator_has_skill($b,760)) $b['clbpara']['skillpara']['el_760']['hits']=min(elevator_skill_value(760,'cap'),1+($b['clbpara']['skillpara']['el_760']['hits']??0));
}
function elevator_skill_revive(&$p,$at) {
    if (!elevator_has_skill($p,755)) return false;
    $s=&$p['clbpara']['skillpara']['el_755'];
    if (!isset($s['uses'])) $s['uses']=elevator_skill_value(755,'uses');
    if ($s['uses']<1) return false;
    $s['uses']--; $p['hp']=max(1,(int)ceil($p['mhp']*elevator_skill_value(755,'hp'))); $p['state']=0; $p['inf']='';
    if (!$p['type']) elevator_personal($p,'「重生」唤回了你，还剩'.$s['uses'].'次额外复活。');
    return true;
}
function elevator_skill_kill(&$a,&$b) {
    global $db,$tablepre;
    if (!empty($b['clbpara']['el_skill_death'])) return;
    $b['clbpara']['el_skill_death']=true;
    if (elevator_has_skill($a,437)) $a['hp']=min($a['mhp'],$a['hp']+max(1,$b['lvl'])*elevator_skill_value(437,'heal'));
    if (elevator_has_skill($a,552)) { $loot=elevator_pool_loot($b['clbpara']['elevator_npc']['pool']??0,0,$b['clbpara']['elevator_npc']['floor']??1); if (!$a['type']) elevator_give($a,$loot[0]); else elevator_set_slot($a,6,$loot[0]); }
    if (elevator_has_skill($b,804)) foreach (elevator_pool_loot($b['clbpara']['elevator_npc']['pool']??0,2,$b['clbpara']['elevator_npc']['floor']??1) as $i=>$item) elevator_set_slot($b,$i+1,$item);
    if (elevator_has_skill($b,801) && !empty($b['clbpara']['skillpara']['el_801']['leader'])) {
        foreach ($b['clbpara']['skillpara']['el_801']['followers']??array() as $pid) {
            $npc=fetch_playerdata_by_pid((int)$pid);
            if ($npc && $npc['hp']>0 && $npc['type'] && elevator_has_skill($npc,801)) $db->query("DELETE FROM {$tablepre}players WHERE pid=".(int)$pid.' AND type>0');
        }
    }
}
function elevator_skill_itemuse(&$p,$slot,$item) {
    if (elevator_has_skill($p,458) && strpos($item['itmk'],'P')===0) {
        require_once GAME_ROOT.'include/game/item.recovery.php';
        $kind=$item['itmk']; $p['itmk'.$slot]='H'.substr($kind,1);
        if (strpos($kind,'PB')===0) item_recovery_both($slot,$p);
        elseif (strpos($kind,'PS')===0) item_recovery_stamina($slot,$p);
        else item_recovery_health($slot,$p);
        if (!empty($p['itms'.$slot]) && $p['itm'.$slot]===$item['itm']) $p['itmk'.$slot]=$kind;
        return true;
    }
    if (elevator_has_skill($p,710) && preg_match('/^V[VPKGCDF]$/',$item['itmk'])) {
        $map=array('VP'=>'wp','VK'=>'wk','VG'=>'wg','VC'=>'wc','VD'=>'wd','VF'=>'wf');
        $keys=$item['itmk']==='VV'?array_values($map):array($map[$item['itmk']]);
        foreach ($keys as $key) $p[$key]+=$item['itme']; elevator_consume($p,$slot);
        elevator_personal($p,'解限使手册完整生效，熟练度增加'.$item['itme'].'。'); return true;
    }
    return false;
}
function elevator_supply_delivery(&$e,$floor,$at) {
    if (!in_array(804,elevator_skill_pool_config(),true)) return;
    if (!$e['stops'] || $e['stops']%max(1,elevator_skill_value(804,'stops')) || ($e['supply_stop']??-1)===$e['stops'] || $floor===-8) return;
    $d=elevator_floor_read($floor); if (empty($d['tpls'])) return;
    $e['supply_stop']=$e['stops']; $pool=(int)floor($d['tpls'][0]/100);
    $npc=rr_generate_npc(1,0,0,0,$pool,0,$floor); $npc['name']='夹层速递补给箱'; $npc['hp']=$npc['mhp']=100;
    $npc['att']=0; $npc['money']=0; $npc['pls']=elevator_pls($floor,1);
    $npc['clbpara']['skill']=array('el_803','el_804'); $npc['clbpara']['elevator_npc']+=array('floor'=>$floor,'pool'=>$pool,'supply'=>true);
    for ($i=1;$i<=6;$i++) elevator_set_slot($npc,$i);
    elevator_insert_npc($npc,$at); elevator_notice($e,'速递补给箱落在了'.elevator_floor_label($floor).'的第一个探索点。击破铁壳即可领取。',$at);
}
function elevator_skills_panel($p,$at) {
    $html='<details id="el-skills"><summary>夹层能力与随身行囊</summary>'; $defs=elevator_skill_definitions(); $found=false;
    foreach ($defs as $id=>$def) if (elevator_has_skill($p,$id)) {
        $found=true; $html.='<div class="el-object">'.elevator_skill_description('el_'.$id);
        if ($id===738) foreach (array(1=>'低挡',2=>'普通',3=>'高挡') as $choice=>$label) $html.=elevator_button($label,'elevator_skill',array('value'=>$id,'choice'=>$choice));
        if ($id===422) $html.=elevator_button(!empty($p['clbpara']['skillpara']['el_422']['melee'])?'切回射击':'切为枪托','elevator_skill',array('value'=>$id));
        if ($id===570 || $id===455) $html.=elevator_button('发动'.$def[0],'elevator_skill',array('value'=>$id));
        if ($id===755 || $id===455) $html.='<p>剩余次数：'.(int)($p['clbpara']['skillpara']['el_'.$id]['uses']??0).'</p>';
        if ($id===458 || $id===430) for ($i=1;$i<=6;$i++) if (!empty($p['itms'.$i])) $html.=elevator_button(($id===458?'转化：':'存入：').$p['itm'.$i],'elevator_skill',array('value'=>$id,'slot'=>$i,'token'=>elevator_slot_token($p,$i),'choice'=>'store'));
        if ($id===430) foreach ($p['clbpara']['skillpara']['el_430']['bag']??array() as $i=>$item) $html.=elevator_button('取回：'.$item['itm'],'elevator_skill',array('value'=>$id,'slot'=>$i,'token'=>hash('sha256',json_encode($item,JSON_UNESCAPED_UNICODE)),'choice'=>'take'));
        if ($id===443 && !empty($p['bid'])) $html.=elevator_button('与当前目标决斗','elevator_skill',array('value'=>$id,'target'=>$p['bid']));
        $html.='</div>';
    }
    return $html.($found?'':'<p>技能笔记、遗物和无尽馈赠能带来新的能力。</p>').'</details>';
}
