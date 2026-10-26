<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_intrusion_names() {
    return array('echo'=>'钻探层回响','watcher'=>'缝隙中的窥视者','rule'=>'电梯规则自我执行','fans'=>'阁楼粉丝入侵',
        'fragment'=>'幻境碎片入侵','overflow'=>'幻境底层溢出','passenger'=>'乘客残影');
}
function elevator_intrusion_arbiter(&$e,&$players,$at,$force='') {
    if ($e['event'] || $e['floor']===-8 || $e['state']==='ended') return false;
    if (!$force && ($e['stops']-$e['last_event_stop']<2 || $at-$e['last_event_time']<60)) return false;
    $ids=array(); $curse=false;
    foreach ($players as $id=>$p) if (elevator_active($p) && $p['clbpara']['elevator']['in']) {
        $ids[]=$id;
        foreach (array('wepsk','arbsk','arhsk','arask','arfsk','artsk','itmsk1','itmsk2','itmsk3','itmsk4','itmsk5','itmsk6') as $k) if (strpos($p[$k]??'','V')!==false) $curse=true;
    }
    $attention=$e['attention']+(int)floor(($at-($e['attention_since']??$e['start_time']))/30)+($curse?3:0)+(count($ids)>3?2:0);
    if (!$force && mt_rand(1,100)>($attention>=6?75:8+$attention*6)) return false;
    $types=$ids?array_keys(elevator_intrusion_names()):array('overflow','passenger');
    if ($e['floor']>0 && $ids) $types=array_merge($types,array('fans','fans'));
    $type=$force?:rr_pick($types);
    if (!isset(elevator_intrusion_names()[$type])) return false;
    $e['event']=array('id'=>bin2hex(random_bytes(8)),'type'=>$type,'stage'=>'announce','deadline'=>$at+elevator_config('announce'),
        'participants'=>array_fill_keys($ids,''),'actions'=>0,'npcs'=>array(),'next_action'=>array(),'result'=>null);
    $e['state']='hold'; $e['door']='closed'; $e['attention']=0; $e['attention_since']=$at;
    foreach ($players as &$p) if (!empty($p['clbpara']['elevator']['trade'])) elevator_trade_cancel($p);
    unset($p);
    elevator_notice($e,'广播：'.elevator_intrusion_names()[$type].'。车门锁定，20秒后异象将进入车厢。',$at);
    return true;
}
function elevator_intrusion_reward(&$p,$e,$kind='supply') {
    if ($kind==='relic') elevator_give($p,elevator_relic($e['floor'],$e['round']));
    else {
        $pool=array();
        if (is_array($kind)) foreach (elevator_catalog() as $id=>$spec) { if (!$spec['unique'] && in_array($spec['pool'],$kind,true)) $pool[]=elevator_item($id,$e['round']); }
        else $pool=elevator_reward_pool($e['floor'],$e);
        elevator_give($p,rr_pick($pool));
    }
}
function elevator_intrusion_finish(&$e,&$players,$at,$won=true) {
    global $db,$tablepre;
    $event=$e['event']; if (!$event) return;
    $battle=!empty($event['npcs']);
    if ($battle) {
        foreach ($event['npcs'] as $id) $db->query("DELETE FROM {$tablepre}players WHERE pid=".(int)$id." AND type=97");
        foreach ($players as &$p) if (elevator_active($p) && isset($event['participants'][$p['pid']])) {
            $p['action']=''; $p['bid']=0;
            if ($won) {
                $loot=array(); foreach (elevator_catalog() as $key=>$spec) if ($spec['pool']===8 && !$spec['unique']) $loot[]=$key;
                for ($i=0,$n=mt_rand(1,2);$i<$n;$i++) elevator_give($p,elevator_item(rr_pick($loot),$e['round']));
                $p['clbpara']['elevator']['buff_until']=$at+30;
            } else {
                $p['hp']=max(1,$p['hp']-(int)ceil($p['mhp']*.3));
                elevator_personal($p,'回响撞开了车门。你失去三成生命，但可以离开。');
            }
        }
        unset($p);
        $e['last_event_stop']=$e['stops']; $e['last_event_time']=$at;
    }
    $e['event']=null;
    elevator_notice($e,$won?'广播恢复正常。车门重新打开。':'异象散去，车门强制打开。',$at);
    elevator_open($e,$players,$at);
}
function elevator_intrusion_spawn(&$e,$at,$count) {
    for ($i=0;$i<$count;$i++) {
        $npc=rr_generate_npc(max(1,elevator_rank($e['floor'])-1),$e['floor']<0?25:0,0,10);
        $npc['type']=97; $npc['name']=($e['event']['type']==='rule'?'规则执行者':'钻探回响').'·'.($i+1);
        $npc['pls']=9000; $npc['pgroup']=250; $npc['clbpara']['elevator_npc']['event']=$e['event']['id'];
        // 战斗奖励由事件结算统一发放，尸体不留下第二份奖励。
        for ($s=1;$s<=6;$s++) elevator_set_slot($npc,$s);
        $e['event']['npcs'][]=elevator_insert_npc($npc,$at);
    }
}
function elevator_intrusion_tick(&$e,&$players,$at) {
    $v=&$e['event']; if (!$v) return;
    if ($v['stage']==='announce') {
        $v['stage']='resolve'; $v['deadline']=$at+30;
        if ($v['type']==='echo') { elevator_intrusion_spawn($e,$at,mt_rand(2,3)); $v['deadline']=$at+elevator_config('event_limit'); return; }
        if ($v['type']==='rule') {
            $paid=false;
            foreach ($v['participants'] as $id=>$choice) {
                if (!isset($players[$id]) || !elevator_active($players[$id])) continue;
                if ($choice==='volunteer') {
                    elevator_entrance($players[$id],$e['floor'],$at,false);
                    $players[$id]['clbpara']['elevator']['no_protection_until']=$at+60;
                    elevator_personal($players[$id],'你自愿留下。本层60秒内候梯保护失效。'); $paid=true; break;
                }
                if ($choice==='offer') for ($s=0;$s<=6;$s++) {
                    $item=elevator_slot($players[$id],$s); $key=$item['itmpara']['elevator_item']??0; $catalog=elevator_catalog();
                    if ($key && !empty($catalog[$key]['unique']) && !empty($item['itms'])) { elevator_consume($players[$id],$s); $paid=true; break 2; }
                }
            }
            if ($paid) { elevator_intrusion_finish($e,$players,$at,true); return; }
            elevator_intrusion_spawn($e,$at,1); $e['event']['deadline']=$at+elevator_config('event_limit'); return;
        }
        if ($v['type']==='passenger') {
            $hint=elevator_next_hint($e);
            foreach ($v['participants'] as $id=>$choice) if (isset($players[$id])) elevator_personal($players[$id],'残影说：'.$hint);
        }
        return;
    }
    if ($v['npcs']) { elevator_intrusion_finish($e,$players,$at,false); return; }
    $success=true;
    foreach ($v['participants'] as $id=>$choice) {
        if (!isset($players[$id]) || !elevator_active($players[$id])) continue;
        $p=&$players[$id]; $s=&$p['clbpara']['elevator'];
        if ($v['type']==='watcher') {
            if ($choice==='look' && mt_rand(1,100)<=30) elevator_personal($p,elevator_next_hint($e));
            if ($choice==='touch') { $s['blind_until']=$at+30; if (mt_rand(1,100)<=10) elevator_intrusion_reward($p,$e); }
        } elseif ($v['type']==='fans') {
            if ($choice==='pose') { elevator_intrusion_reward($p,$e); $s['buff_until']=$at+30; }
            else $e['slow_buttons']=2;
        } elseif ($v['type']==='fragment') {
            if ($v['actions']>=max(3,count($v['participants'])*2)) for ($i=0,$n=mt_rand(1,2);$i<$n;$i++) elevator_intrusion_reward($p,$e,array(5,6));
            else { $s['stun_until']=$at+5; $success=false; }
        } elseif ($v['type']==='overflow') {
            if ($choice==='take' || $choice==='expel') {
                elevator_intrusion_reward($p,$e,array(8));
                if ($choice==='expel' && mt_rand(1,100)<=40) elevator_intrusion_reward($p,$e,array(8));
            } else $e['pollution']=6;
        }
        unset($p,$s);
    }
    if (!$v['participants'] && $v['type']==='overflow') $e['pollution']=6;
    elevator_intrusion_finish($e,$players,$at,$success);
}
function elevator_intrusion_choice(&$e,&$p,$choice,$id,$at) {
    $v=&$e['event']; $pid=$p['pid'];
    if (!$v || $v['id']!==$id || !isset($v['participants'][$pid]) || !$p['clbpara']['elevator']['in']) throw new RuntimeException('这场异象已经散去，或你不在车厢中。');
    $allowed=array('echo'=>array(),'watcher'=>array('look','away','touch'),'rule'=>array('volunteer','offer','refuse'),
        'fans'=>array('pose','refuse'),'fragment'=>array('hit'),'overflow'=>array('take','expel','ignore'),'passenger'=>array('listen'));
    if (!in_array($choice,$allowed[$v['type']],true)) throw new RuntimeException('请选择广播中给出的回应。');
    if ($choice==='hit') {
        if ($v['stage']!=='resolve') throw new RuntimeException('异象尚未进入车厢。');
        if (($v['next_action'][$pid]??0)>$at) throw new RuntimeException('让敲击的回声落下，再试一次。');
        $v['actions']++; $v['next_action'][$pid]=$at+1;
        if ($v['actions']>=max(3,count($v['participants'])*2)) $v['deadline']=$at;
    } else $v['participants'][$pid]=$choice;
}
function elevator_next_hint($e) {
    $next=$e['floor']+($e['dir']?:1); if ($next===0) $next=$e['dir']?:1;
    if ($next===-8) return '钻探尽头之下，是没有战斗的灰色会客厅。';
    $plan=$e['plan'][$next]??array(); $maps=elevator_maps();
    if (empty($plan['tpls'])) return '更远处的门牌仍是空白。深度越大，返回电梯口越要及时。';
    $names=array(); foreach ($plan['tpls'] as $tpl) $names[]=$maps[$tpl]['name'];
    return elevator_floor_label($next).'：'.implode('、',$names).'；预计敌人强度 '.elevator_rank($next).'。';
}
