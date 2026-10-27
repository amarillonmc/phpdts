<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_quest_config() { include __DIR__.'/../questcfg_1.php'; return $questcfg; }
function elevator_quest_board(&$data) {
    if (isset($data['quests'])) return;
    $data['quests']=array(); $cfg=elevator_quest_config(); $pools=array();
    foreach ($data['tpls'] as $i=>$tpl) if (!isset($pools[(int)floor($tpl/100)])) $pools[(int)floor($tpl/100)]=$i+1;
    foreach ($cfg as $key=>$spec) if (isset($pools[$spec['pool']])) {
        // 钻探每层在护送和样本提交之间选一种，错误清除始终存在。
        if ($key==='prospector' && $data['bias']%2) continue;
        if ($key==='ore' && !($data['bias']%2)) continue;
        $need=$spec['need']+(abs($data['floor'])>=9 && $spec['need']>1?1:0);
        if ($spec['kind']==='explore') $need=min($need,count($data['tpls']));
        $qid='EL'.$data['floor'].'_'.$key;
        $data['quests'][$qid]=array('key'=>$key,'need'=>$need,'index'=>$pools[$spec['pool']],'owner'=>0,'npc'=>0,'voided'=>false);
    }
}
function elevator_quest_fail(&$p,$qid,$reason) {
    require_once GAME_ROOT.'include/game/quest.func.php';
    quest_fail($qid,$p,$reason); elevator_personal($p,'委托结束：'.$reason);
}
function elevator_quest_sync(&$p,$e) {
    if (empty($p['clbpara']['elevator'])) return;
    foreach ($p['clbpara']['quest']['active']??array() as $qid=>$q) {
        if (empty($q['elevator'])) continue;
        $data=elevator_floor_read($q['floor']); $row=$data['quests'][$qid]??null;
        if (!$row || !empty($row['voided'])) { elevator_quest_fail($p,$qid,$row?'楼层的命运已被另一份委托改变。':'这处楼层已被大楼收回。'); continue; }
        $spec=elevator_quest_config()[$row['key']];
        if ($spec['kind']==='escort' && !empty($q['linked_npc_id']) && empty($q['ready_to_claim'])) {
            $npc=fetch_playerdata_by_pid($q['linked_npc_id']);
            if (!$npc || $npc['hp']<=0) { elevator_quest_fail($p,$qid,'护送目标已经死亡。'); continue; }
            $progress=min($q['progress_max'],max(0,$e['stops']-$q['start_stop']));
            $p['clbpara']['quest']['active'][$qid]['progress']=$progress;
            if ($progress>=$q['progress_max']) elevator_quest_ready($p,$qid);
        }
    }
    // 楼层回收后的个人任务记录也随之回收，不积累无限历史。
    foreach (array('completed','failed') as $kind) foreach ($p['clbpara']['quest'][$kind]??array() as $qid=>$q) {
        if (preg_match('/^EL(-?\d+)_/',$qid,$m) && abs((int)$m[1])>8 && !elevator_floor_read((int)$m[1])) unset($p['clbpara']['quest'][$kind][$qid]);
    }
}
function elevator_quest_ready(&$p,$qid) {
    $q=&$p['clbpara']['quest']['active'][$qid]; if (!$q || !empty($q['ready_to_claim'])) return;
    $data=elevator_floor_read($q['floor']); if (empty($data['quests'][$qid]) || $data['quests'][$qid]['voided']) { elevator_quest_fail($p,$qid,'目标已不存在。'); return; }
    $cfg=elevator_quest_config(); $spec=$cfg[$data['quests'][$qid]['key']];
    $group=$spec['conflict_group']??'';
    if ($group) foreach ($data['quests'] as $other=>&$row) if ($other!==$qid && ($cfg[$row['key']]['conflict_group']??'')===$group) $row['voided']=true;
    unset($row);
    $q['progress']=$q['progress_max']; $q['ready_to_claim']=true; $q['step_desc']='委托已完成，可在本层任务板领取奖励。';
    // 目标恢复待命，下一位已接取乘客可继续使用同一个 NPC。
    $data['quests'][$qid]['owner']=0;
    if ($data['quests'][$qid]['npc']) {
        $npc=fetch_playerdata_by_pid($data['quests'][$qid]['npc']);
        if ($npc) { $npc['clbpara']['linked_player_id']=0; $npc['action']=''; $npc['bid']=0; player_save($npc); }
    }
    elevator_floor_save($q['floor'],$data);
    elevator_personal($p,'「'.$q['title'].'」完成了。奖励在本层任务板等你。');
}
function elevator_quest_item(&$p,$key,$qid,$floor) {
    include __DIR__.'/../questitem_1.php'; $item=$questiteminfo[$key];
    $item['itmpara']+=array('QuestID'=>$qid,'elevator_quest_floor'=>$floor,'elevator_quest_owner'=>$p['pid']); elevator_give($p,$item);
}
function elevator_quest_match($item,$match) {
    if (!empty($match['names']) && !preg_match('/'.$match['names'].'/u',$item['itm'])) return false;
    if (!empty($match['kinds']) && !in_array($item['itmk'],$match['kinds'],true)) return false;
    return !empty($item['itms']);
}
function elevator_quest_action(&$p,$qid,$action,$answer,$e,$at) {
    require_once GAME_ROOT.'include/game/quest.func.php';
    $s=&$p['clbpara']['elevator']; if ($s['in']) throw new RuntimeException('请下车后查看本层任务板。');
    elevator_quest_sync($p,$e); $data=elevator_floor_read($s['floor']); $row=$data['quests'][$qid]??null;
    if (!$row || $row['voided']) throw new RuntimeException('这份委托已经失效。');
    $spec=elevator_quest_config()[$row['key']]; quest_init_state($p['clbpara']);
    if (!empty($p['clbpara']['quest']['completed'][$qid]) || !empty($p['clbpara']['quest']['failed'][$qid])) throw new RuntimeException('你已经结束了这份委托。');
    if ($action==='reject') {
        if (!empty($p['clbpara']['quest']['active'][$qid])) throw new RuntimeException('已经接下的委托不能用忽略重新抽取。');
        $s['floors'][$s['floor']]['quest_skip'][$qid]=elevator_config('quest_reject_steps'); return;
    }
    if ($action==='accept') {
        if (!empty($p['clbpara']['quest']['active'][$qid])) throw new RuntimeException('已经接取这份委托。');
        if (!empty($s['floors'][$s['floor']]['quest_skip'][$qid])) throw new RuntimeException('你刚刚忽略了这份委托，先探索几次再回来。');
        $p['clbpara']['quest']['active'][$qid]=array('id'=>$qid,'title'=>$spec['title'],'tier'=>'normal','step'=>1,'step_desc'=>$spec['desc'],'progress'=>0,'progress_max'=>$row['need'],'elevator'=>true,'floor'=>$s['floor'],'key'=>$row['key'],'linked_npc_id'=>0,'start_time'=>$at,'start_stop'=>$e['stops']);
        if (!empty($spec['item'])) elevator_quest_item($p,$spec['item'],$qid,$s['floor']);
        elevator_personal($p,'你接下了「'.$spec['title'].'」。'); return;
    }
    if (empty($p['clbpara']['quest']['active'][$qid])) throw new RuntimeException('请先接取委托。');
    $q=&$p['clbpara']['quest']['active'][$qid];
    if ($action==='claim') {
        if (empty($q['ready_to_claim'])) throw new RuntimeException('委托尚未完成。');
        $cash=elevator_config('quest_cash')+abs($s['floor'])*elevator_config('quest_cash_depth');
        quest_complete($qid,$p,'elevator_claim'); $p['money']+=$cash;
        foreach (elevator_pool_loot($spec['pool'],2,$s['floor']) as $item) elevator_give($p,$item);
        elevator_personal($p,'领到了'.$cash.'现金与楼层馈赠。'); return;
    }
    if (!empty($q['ready_to_claim'])) throw new RuntimeException('委托已完成，请领取奖励。');
    if ($action==='submit' && $spec['kind']==='submit') {
        while ($q['progress']<$q['progress_max']) {
            $slot=quest_find_item_slot($p,$qid,'submit',$spec['match']); if (!$slot) break;
            $item=elevator_slot($p,$slot); if ($item['itms']==='∞') break;
            $whole=preg_match('/^(WP|WK|WG|D|A)/',$item['itmk']);
            if ($whole) elevator_set_slot($p,$slot); else elevator_consume($p,$slot);
            $q['progress']++;
        }
        if (!$q['progress']) throw new RuntimeException('背包中没有符合要求的道具。');
    } elseif ($action==='answer' && $spec['kind']==='puzzle') {
        if (trim($answer)!==elevator_cipher($spec['word'])) throw new RuntimeException('广播没有接上这个答案。'); $q['progress']=$q['progress_max'];
    } elseif (!empty($spec['npc']) && in_array($action,array('contact','fight','use'),true)) {
        $pls=elevator_pls($s['floor'],$row['index']); if ((int)$p['pls']!==$pls) throw new RuntimeException('请先前往任务板标出的目标地点。');
        $npc=$row['npc']?fetch_playerdata_by_pid($row['npc']):null;
        if ($row['owner'] && $row['owner']!=$p['pid']) {
            $owner=fetch_playerdata_by_pid($row['owner']);
            if ($owner && elevator_active($owner) && !empty($owner['clbpara']['quest']['active'][$qid])) throw new RuntimeException('目标正在回应另一位乘客，请等这次相遇结束。');
            $row['owner']=0;
        }
        if (!$npc) {
            $npc=elevator_floor_npc($s['floor'],$spec['pool']); $npc['name']=$spec['npc']; $npc['money']=0;
            $npc['clbpara']['skill']=array(); for ($i=1;$i<=6;$i++) elevator_set_slot($npc,$i);
            $npc['clbpara']['elevator_npc']['quest']=$qid; $npc['clbpara']['quest_id']=$qid;
            $npc['pls']=$pls; $row['npc']=elevator_insert_npc($npc,$at); $npc=fetch_playerdata_by_pid($row['npc']);
        }
        if (!$row['owner']) {
            $npc['hp']=$npc['mhp']; $npc['state']=0; $npc['inf']=''; unset($npc['clbpara']['elevator_rewarded'],$npc['clbpara']['el_skill_death'],$npc['clbpara']['elevator_quest_dead']);
            $row['owner']=$p['pid']; $npc['clbpara']['linked_player_id']=$p['pid']; $q['start_stop']=$e['stops'];
        }
        $npc['pls']=$pls; $q['linked_npc_id']=$row['npc']; $data['quests'][$qid]=$row;
        elevator_floor_save($s['floor'],$data); player_save($npc);
        if ($spec['kind']==='purify' || $spec['kind']==='cheer') {
            if ($action==='fight') { elevator_focus($p,$row['npc']); return; }
            if ($spec['kind']==='cheer') { elevator_focus($p,$row['npc']); return; }
            if ($action!=='use') { elevator_personal($p,'回忆体仍在挣扎。先在战斗中把生命降至20%，再使用净化匣。'); return; }
            if ($npc['hp']<=0 || $npc['hp']>round($npc['mhp']*$spec['hp_threshold']/100)) throw new RuntimeException('回忆体的生命还没有降到净化阈值。');
        }
        if ($spec['kind']==='comfort' || $spec['kind']==='purify') {
            $slot=quest_find_item_slot($p,$qid,$spec['item']); if (!$slot) throw new RuntimeException('请把委托道具放进背包。');
            $item=elevator_slot($p,$slot); if (($item['itmpara']['elevator_quest_owner']??0)!=$p['pid']) throw new RuntimeException('这份道具属于另一位乘客。');
            elevator_consume($p,$slot); $q['progress']=$q['progress_max'];
        } elseif ($spec['kind']==='collect') {
            $steps=$s['floors'][$s['floor']]['actions']??0;
            if (isset($q['last_collect']) && $q['last_collect']===$steps) throw new RuntimeException('先在楼层探索一次，再来寻找下一份灯纸。');
            $q['last_collect']=$steps; $q['progress']++; elevator_quest_item($p,'paper',$qid,$s['floor']);
        } elseif ($spec['kind']==='escort') elevator_personal($p,'你与目标会合了。再停靠'.$q['progress_max'].'次，存活的目标便能循着灯光离开。');
    } else throw new RuntimeException('这份委托不能这样完成。');
    if ($q['progress']>=$q['progress_max']) elevator_quest_ready($p,$qid);
}
function elevator_quest_event(&$p,$event,$value=array()) {
    if (empty($p['clbpara']['elevator']) || $p['clbpara']['elevator']['in']) return;
    $floor=$p['clbpara']['elevator']['floor']; $cfg=elevator_quest_config();
    foreach ($p['clbpara']['quest']['active']??array() as $qid=>$q) {
        if (empty($q['elevator']) || $q['floor']!==$floor || !empty($q['ready_to_claim'])) continue;
        $spec=$cfg[$q['key']]; $state=&$p['clbpara']['quest']['active'][$qid];
        if ($event==='explore' && $spec['kind']==='explore' && $p['pls']>elevator_pls($floor)) { $state['visited'][$p['pls']]=true; $state['progress']=count($state['visited']); }
        if ($event==='use' && $spec['kind']==='use' && elevator_quest_match($value,$spec['match'])) $state['progress']++;
        if ($event==='kill' && isset($spec['kill_count']) && empty($value['clbpara']['elevator_npc']['quest'])) {
            $c=$spec['kill_count']; $meta=$value['clbpara']['elevator_npc']??array();
            if (in_array((int)$value['type'],$c['type'],true) && ($meta['floor']??0)===$floor && ($meta['pool']??0)===$c['pool'] && (empty($c['wepk']) || in_array($value['wepk'],$c['wepk'],true))) $state['progress']++;
        }
        if ($state['progress']>=$state['progress_max']) elevator_quest_ready($p,$qid);
    }
    unset($state);
}
function elevator_quest_npc_death(&$a,&$b) {
    if (empty($b['type'])) return;
    if (!empty($b['clbpara']['elevator_quest_dead'])) return;
    $b['clbpara']['elevator_quest_dead']=true;
    elevator_quest_event($a,'kill',$b);
    $qid=$b['clbpara']['elevator_npc']['quest']??''; if (!$qid) return;
    $owner=$b['clbpara']['linked_player_id']??0; if (!$owner) return;
    if ($owner==$a['pid']) $p=&$a; else $p=fetch_playerdata_by_pid($owner);
    $q=$p['clbpara']['quest']['active'][$qid]??array(); if (!$q) return;
    if ($q['key']==='cheer' && $owner==$a['pid']) elevator_quest_ready($p,$qid);
    else elevator_quest_fail($p,$qid,$owner==$a['pid']?'目标已死亡，委托无法继续。':'目标被另一位乘客击杀。');
    $data=elevator_floor_read($q['floor']); if (isset($data['quests'][$qid])) { $data['quests'][$qid]['owner']=0; elevator_floor_save($q['floor'],$data); }
    if ($owner!=$a['pid']) player_save($p);
}
function elevator_quest_reinforcements(&$p,$at) {
    $floor=$p['clbpara']['elevator']['floor']; $cfg=elevator_quest_config(); $wanted=array();
    foreach ($p['clbpara']['quest']['active']??array() as $q) if (!empty($q['elevator']) && $q['floor']===$floor && empty($q['ready_to_claim']) && $cfg[$q['key']]['kind']==='kill') $wanted[$cfg[$q['key']]['pool']]=true;
    if (!$wanted) return;
    $data=elevator_floor_read($floor); $data['reinforce_steps']=($data['reinforce_steps']??0)+1;
    if ($data['reinforce_steps']>=max(1,elevator_config('quest_respawn_steps'))) {
        $data['reinforce_steps']=0;
        foreach ($wanted as $pool=>$unused) {
            // 每个主题最多保留一名任务增援，继续探索不会无界增殖。
            $old=fetch_playerdata_by_pid($data['reinforcements'][$pool]??0); if ($old && $old['hp']>0 && $old['pls']>0) continue;
            $index=1; foreach ($data['tpls'] as $i=>$tpl) if ((int)floor($tpl/100)===$pool) { $index=$i+1; break; }
            $npc=elevator_floor_npc($floor,$pool); $npc['pls']=elevator_pls($floor,$index);
            $data['reinforcements'][$pool]=elevator_insert_npc($npc,$at);
        }
    }
    elevator_floor_save($floor,$data);
}
function elevator_quest_battle(&$a,&$b) {
    if ($a['type']) { $npc=&$a; $p=&$b; } else { $npc=&$b; $p=&$a; }
    $qid=$npc['clbpara']['elevator_npc']['quest']??'';
    if (!$qid || ($npc['clbpara']['linked_player_id']??0)!=$p['pid']) return;
    if (empty($p['clbpara']['quest']['active'][$qid])) return;
    $q=&$p['clbpara']['quest']['active'][$qid];
    if (!$q || $q['key']!=='cheer' || !empty($q['ready_to_claim']) || !elevator_active($p)) return;
    $q['progress']++; if ($q['progress']>=$q['progress_max']) { elevator_quest_ready($p,$qid); $npc['clbpara']['linked_player_id']=0; $npc['action']=''; $p['action']=''; $p['bid']=0; }
}
function elevator_quest_panel($p) {
    $s=$p['clbpara']['elevator']; $html='<div class="el-quest-board">';
    if ($s['in']) return $html.'<p>下车后，QUEST 窗体会显示该层的公共任务板。每层探索两处的遗物奖励始终保留。</p></div>';
    $d=elevator_floor_read($s['floor']); $cfg=elevator_quest_config(); $maps=elevator_maps();
    foreach ($d['quests']??array() as $qid=>$row) {
        $spec=$cfg[$row['key']]; $q=$p['clbpara']['quest']['active'][$qid]??array();
        $done=!empty($p['clbpara']['quest']['completed'][$qid]); $failed=!empty($p['clbpara']['quest']['failed'][$qid]);
        $html.='<div class="el-object"><b>'.elevator_html($spec['title']).'</b><p>'.elevator_html($spec['desc']).'</p>';
        if (!empty($spec['conflict_group'])) $html.='<p class="el-warning">公共抉择：另一份同组委托一旦完成，本委托对所有乘客失效。</p>';
        if ($row['voided'] || $done || $failed) { $html.='<p>'.($done?'奖励已领取':($failed?'委托失败':'委托已失效')).'</p></div>'; continue; }
        $args=array('qid'=>$qid);
        if (!$q) $html.=elevator_button('接取 · 目标'.$row['need'],'elevator_quest',$args+array('value'=>'accept')).elevator_button('暂时忽略','elevator_quest',$args+array('value'=>'reject'));
        else {
            $html.='<p>进度 '.(int)$q['progress'].' / '.(int)$q['progress_max'].'</p>';
            if (!empty($q['ready_to_claim'])) $html.=elevator_button('领取现金与楼层馈赠','elevator_quest',$args+array('value'=>'claim'));
            elseif ($spec['kind']==='submit') $html.=elevator_button('提交背包中的匹配道具','elevator_quest',$args+array('value'=>'submit'));
            elseif ($spec['kind']==='puzzle') {
                if (!empty($s['dictionary'])) $html.='<p>abcdefghijklmnopqrstuvwxyz<br>'.elevator_html(elevator_cipher('abcdefghijklmnopqrstuvwxyz')).'</p>';
                $html.='<input id="el-q-'.elevator_html($qid).'" aria-label="任务种火语答案" placeholder="种火语">'.elevator_button('回答广播','elevator_quest',$args+array('value'=>'answer'),array('answer'=>'el-q-'.$qid));
            } elseif (!empty($spec['npc'])) {
                $html.='<p>目标位于：'.elevator_html($maps[$d['tpls'][$row['index']-1]]['name']).'。'.($row['owner'] && $row['owner']!=$p['pid']?'正在回应另一位乘客。':'').'</p>';
                $html.=elevator_native_button('前往目标','move',array('moveto'=>elevator_pls($s['floor'],$row['index'])));
                $html.=elevator_button(in_array($spec['kind'],array('purify','cheer'))?'进入挑战':'会合 / 互动','elevator_quest',$args+array('value'=>in_array($spec['kind'],array('purify','cheer'))?'fight':'contact'));
                if ($spec['kind']==='purify') $html.=elevator_button('生命20%以下：净化','elevator_quest',$args+array('value'=>'use'));
            }
        }
        $html.='</div>';
    }
    return $html.'<p>每人可各接一次。目标为共享单实例，依次回应乘客。击杀任务可在继续探索时遇到增援。</p></div>';
}
