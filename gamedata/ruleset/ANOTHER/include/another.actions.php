<?php
if (!defined('IN_GAME')) exit('Access Denied');

function another_discover_player(&$actor,$target,$now) {
    $actor['clbpara']['another']['known_players'][(int)$target['pid']]=array('pls'=>(int)$target['pls'],'time'=>$now);
}
function another_can_target($actor,$target) {
    return (int)$actor['pls']===(int)$target['pls'] && $actor['pid']!=$target['pid'] &&
        (another_talent_same_team($actor,$target) || isset($actor['clbpara']['another']['known_players'][$target['pid']]));
}
function another_discovery_environment($room,$pls) {
    $config=another_config(); $map=$config['maps'][$pls];
    $weather=isset($room['weather'])?$room['weather']:0;
    $weather_multiplier=isset($config['weather_effects'][$weather]['discovery'])?$config['weather_effects'][$weather]['discovery']:1;
    return $map['cover']*($map['indoor']?1:$weather_multiplier);
}

function another_search(&$actor,&$room,&$players,$now,&$messages) {
    $config=another_config(); $an=&$actor['clbpara']['another'];
    $an['ground_place']=(int)$actor['pls'];
    if (another_talent($actor,'L1') && mt_rand(1,10000)<=another_talent_value($actor,'L1')*100) {
        $actor['money']+=$config['economy']['fortune_money'];
        $messages[]='财运：捡到了'.$config['economy']['fortune_money'].'金钱。';
    }
    $candidates=array();
    foreach($players as $pid=>$target) if($pid!=$actor['pid'] && (int)$target['pls']===(int)$actor['pls']) $candidates[]=$pid;
    if($candidates && mt_rand(1,100)<=55) {
        $pid=$candidates[array_rand($candidates)]; $target=&$players[$pid];
        $kind=$target['hp']<=0?'corpse':($target['type']?'npc':'player');
        $base=$config['discovery'][$kind]*another_discovery_environment($room,$actor['pls']);
        if(another_item_find($actor,'binoculars')!==false) $base*=1.1;
        if(mt_rand(1,10000)<=another_discovery_chance($actor,$base,$kind,$target)*100) {
            another_discover_player($actor,$target,$now);
            $messages[]='发现了'.another_name($target).($target['hp']<=0?'的遗留装备。':'。');
            another_gain_exp($actor,1,$messages);
            return;
        }
    }
    if(!another_items_search($actor,$room,$players,$now,$messages)) $messages[]='这次搜索没有找到新的东西。';
    another_gain_exp($actor,1,$messages);
}

function another_trace(&$room,$actor,$kind,$now,$to=null) {
    $config=another_config();
    $room['traces'][]=array('pid'=>(int)$actor['pid'],'pls'=>(int)$actor['pls'],'to'=>$to,'kind'=>$kind,'time'=>$now);
    $life=max($config['discovery']['noise_lifetime'],$config['discovery']['trail_lifetime']);
    $room['traces']=array_values(array_filter($room['traces'],function($trace)use($now,$life){return $trace['time']+$life>$now;}));
    $room['traces']=array_slice($room['traces'],-40);
}

function another_action(&$room,&$players,$pid,$action,$args,$now,&$messages,$now_ms=null) {
    if(!isset($players[$pid]) || $players[$pid]['type'] || $players[$pid]['hp']<=0 || !empty($room['result'])) { $messages[]='当前角色不能行动。'; return false; }
    $actor=&$players[$pid]; $an=&$actor['clbpara']['another']; $config=another_config();
    if($now_ms===null) $now_ms=$now*1000;
    if(($an['ready_at']??0)>$now_ms) { $messages[]='上一次行动尚未完成。'; return false; }
    $before=another_action_guard($actor); $success=false;
    $target_id=isset($args['target_pid'])?(int)$args['target_pid']:0;
    $target=isset($players[$target_id])?$players[$target_id]:null;
    if(in_array($action,array('attack','sun','moon','fire','loot','invite','give','give_money','give_rep'),true) && (!$target || !another_can_target($actor,$target))) {
        $messages[]='目标已不在这里，或尚未被发现。'; return false;
    }
    if($action==='move') {
        $to=isset($args['to'])?(int)$args['to']:0;
        if(!in_array($to,$config['maps'][$actor['pls']]['neighbors'],true) || in_array($to,$room['closed'],true)) { $messages[]='只能移动到相邻的安全地点。'; return false; }
        if($actor['sp']<$config['action']['move_sp']) { $messages[]='体力不足，请补给或休息。'; return false; }
        another_trace($room,$actor,'footstep',$now,$to);
        $actor['sp']-=$config['action']['move_sp']; $actor['pls']=$to;
        unset($an['ground_place']); $an['known_players']=array();
        $messages[]='抵达'.$config['maps'][$to]['name'].'。';
        another_items_search($actor,$room,$players,$now,$messages);
        $success=true;
    } elseif($action==='search') {
        if($actor['sp']<$config['action']['search_sp']) { $messages[]='体力不足，请补给或休息。'; return false; }
        $actor['sp']-=$config['action']['search_sp']; another_search($actor,$room,$players,$now,$messages); $success=true;
    } elseif($action==='attack' || $action==='sun') {
        if($target['hp']<=0) { $messages[]='这个目标已经被淘汰。'; return false; }
        if($actor['sp']<$config['action']['attack_sp']) { $messages[]='体力不足。'; return false; }
        $success=another_battle($actor,$players[$target_id],$players,$room,array('sun'=>$action==='sun'),$now,$messages);
        if($success) {
            $actor['sp']=max(0,$actor['sp']-$config['action']['attack_sp']);
            another_discover_player($players[$target_id],$actor,$now);
            $messages_for_target=$messages;
            foreach($messages_for_target as $message) another_personal_notice($players[$target_id],$message,$now);
        }
    } elseif($action==='moon' || $action==='fire') {
        $success=another_mark_player($actor,$target,$action,$now,$messages);
    } elseif($action==='rest') {
        if(!empty($an['rest'])) { $messages[]='你已经在休息。'; return false; }
        $an['rest']=true; $an['rest_tick']=$now; $success=true; $messages[]='开始休息，生命和体力随时间恢复；你仍可能被发现。';
    } elseif($action==='hack') {
        if(!another_talent($actor,'LU') || !empty($an['hack_used']) || another_item_find($actor,'satellite')===false) { $messages[]='需要木之多彩、卫星电话，以及本角色尚未使用的机会。'; return false; }
        $an['hack_used']=true; $success=true;
        if(mt_rand(1,100)<=another_talent_value($actor,'LU')) { $room['result']=array('kind'=>'escape','winners'=>another_team_members($players,$actor),'time'=>$now); $messages[]='黑入成功，队伍已找到异常出口。'; }
        else $messages[]='黑入失败。本角色的这次机会已用尽。';
    } elseif($action==='loot') {
        if($target['hp']>0) { $messages[]='只能拾取已被淘汰角色的遗留物。'; return false; }
        $slot=$args['slot']??'';
        if($slot==='money') {
            if($players[$target_id]['money']<=0) { $messages[]='没有剩余金钱。'; return false; }
            $actor['money']+=$players[$target_id]['money']; $players[$target_id]['money']=0; $success=true;
        } else {
            $item=another_item_get($players[$target_id],$slot);
            if(!another_item_present($item)) { $messages[]='这件物品已经不在了。'; return false; }
            if(!another_item_add($actor,$item)) { $messages[]='背包空间不足。'; return false; }
            another_item_set($players[$target_id],$slot,array()); $success=true;
        }
        $messages[]='拾取了遗留物。';
    } elseif($action==='invite') {
        if($target['type'] || $target['hp']<=0 || another_talent_same_team($actor,$target)) { $messages[]='请选择尚未同队的存活玩家。'; return false; }
        $players[$target_id]['clbpara']['another']['invites'][$pid]=$now;
        another_personal_notice($players[$target_id],another_name($actor).'邀请你加入队伍。',$now); $success=true;
    } elseif($action==='accept_team') {
        if(!$target || empty($an['invites'][$target_id]) || $target['type'] || $target['hp']<=0) { $messages[]='邀请已经失效。'; return false; }
        $team=$target['teamID']?:'ANP'.$target_id;
        foreach($players as &$member) if(!$member['type'] && another_owner($member)===another_owner($actor)) $member['teamID']=$team;
        unset($member); $players[$target_id]['teamID']=$team; unset($an['invites'][$target_id]);
        $messages[]='已加入队伍；同账号的两个角色共同加入。'; $success=true;
    } elseif($action==='leave_team') {
        $has_other_owner=false;
        foreach($players as $member) if(!$member['type'] && another_talent_same_team($actor,$member) && another_owner($member)!==another_owner($actor)) $has_other_owner=true;
        if(!$has_other_owner) { $messages[]='当前已经是独立参赛单位。'; return false; }
        $team='AN'.$an['owner_uid'].':'.bin2hex(random_bytes(4));
        foreach($players as &$member) if(!$member['type'] && another_owner($member)===another_owner($actor)) $member['teamID']=$team;
        unset($member); $success=true; $messages[]='已离开队伍。';
    } elseif(in_array($action,array('give','give_money','give_rep'),true)) {
        if($target['type'] || $target['hp']<=0) { $messages[]='对方不能接收物资。'; return false; }
        if($action==='give') {
            $slot=$args['slot']??''; $item=another_item_get($actor,$slot);
            if(!another_item_present($item) || !another_item_add($players[$target_id],$item)) { $messages[]='物品不存在，或对方背包已满。'; return false; }
            another_item_set($actor,$slot,array());
            if($slot==='wep') unset($an['moon']);
        } else {
            $amount=isset($args['amount'])?round((float)$args['amount'],2):0;
            if($action==='give_money') $amount=(int)$amount;
            $balance=$action==='give_money'?$actor['money']:($an['rep']??0);
            if(!is_finite($amount) || $amount<=0 || $amount>$balance) { $messages[]='转交数量无效。'; return false; }
            if($action==='give_money') { $actor['money']-=$amount; $players[$target_id]['money']+=$amount; }
            else { $an['rep']=round($an['rep']-$amount,2); $players[$target_id]['clbpara']['another']['rep']=round(($players[$target_id]['clbpara']['another']['rep']??0)+$amount,2); }
        }
        another_personal_notice($players[$target_id],another_name($actor).'向你转交了物资。',$now); $messages[]='已转交。'; $success=true;
    } else {
        $success=another_items_action($actor,$room,$players,$action,$args,$now,$messages);
    }
    if($success) {
        if($action!=='rest') unset($an['rest']);
        $slow=another_status_value($actor,'slow',$now);
        $an['ready_at']=$now_ms+(int)round($config['action']['cooldown_ms']*(1+$slow/100));
        $actor['cmdnum']++;
        another_finish_action($actor,$before);
        another_settle_deaths($room,$players,$now);
        another_check_survival($room,$players,$now);
    }
    return $success;
}
