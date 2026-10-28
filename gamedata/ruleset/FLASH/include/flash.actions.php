<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_require_empty_loot($p) {
    if (!empty($p['clbpara']['flash']['loot']) || !empty($p['itms0'])) throw new RuntimeException('先整理眼前的物品，或把它们留在这里。');
}
function flash_after_action(&$p,$kind) {
    if (!flash_ready($p)) return;
    flash_job_event($p,$kind);
    if ($kind!=='battle') flash_erosion($p,$kind==='explore');
    if (!flash_ready($p)) return;
    $j=flash_npc('jojo');
    if ($j && $j['hp']>0 && !$p['clbpara']['flash']['jojo_missing']) {
        $j['pls']=in_array((int)$p['pls'],flash_closed(),true)?0:$p['pls']; player_save($j);
    }
    require_once GAME_ROOT.'include/game/aievent.func.php'; aievent(25,$p);
}
function flash_trap(&$p,$certain=false) {
    global $db,$tablepre;
    if ((int)$p['pls']===0 || (!$certain && mt_rand(1,100)>15)) return;
    $q=$db->query("SELECT * FROM {$tablepre}maptrap WHERE pls=".(int)$p['pls']);
    $item=$db->fetch_array($q); if (!$item) return;
    $held=flash_item_at($p,0);
    flash_set_item($p,0,$item); $db->query("DELETE FROM {$tablepre}maptrap WHERE tid=".(int)$item['tid']);
    // 原生陷阱负责回避、防御与致死判定；拆出的雷仍作为待选物品显示。
    trap($p);
    if (flash_ready($p) && !empty($p['itms0'])) flash_loot($p,array(flash_item_at($p,0)));
    flash_set_item($p,0,$held);
}
function flash_move(&$p,$dest,$back=false) {
    $s=&$p['clbpara']['flash'];
    if (!ruleset_move_allowed_hook($p,$dest)) throw new RuntimeException($s['snare']?'藤蔓还没松开。':'那里已经崩坏，需要移动PC维持通路。');
    if (!$back && $dest!==0 && !in_array($dest,flash_neighbours((int)$p['pls']),true)) throw new RuntimeException('先沿附近的路线前进。远方的人可以用追踪寻找。');
    if ($dest===(int)$p['pls']) throw new RuntimeException('你就在这里。');
    flash_pay($p,$back?45:15); $p['pls']=$dest; $s['target']=0;
    if ($s['gate']) { $s['gate']=false; $s['gate_returns']++; }
    flash_say('你抵达了'.$GLOBALS['plsinfo'][$dest].'。');
    if (in_array($dest,flash_closed(),true)) flash_say('乔乔停在边界外：「乔乔在这里等你的说！一定要回来牵手的说！」');
    if (!$s['deaths'] && empty($s['events']['first_step']) && $dest!==0) {
        $s['events']['first_step']=true;
        if (mt_rand(1,100)<=80) {
            $hazard=mt_rand(0,3);
            if ($hazard===3) flash_trap($p,true);
            else {
                $n=flash_npc('hazard'.$hazard); $n['pls']=$dest; player_save($n);
                flash_say('你还没认清脚下的路，熟悉又陌生的身影已先一步出手。');
                flash_fight($p,(int)$n['pid'],false,1,false,true);
            }
        }
    } else flash_trap($p);
    flash_after_action($p,(!$s['jojo_missing'] && !in_array($dest,flash_closed(),true))?'move':'travel');
}
function flash_explore(&$p) {
    global $db,$tablepre;
    flash_require_empty_loot($p); flash_pay($p,45); $p['pose']=3;
    $rows=array(); $q=$db->query("SELECT * FROM {$tablepre}mapitem WHERE pls=".(int)$p['pls']);
    while ($row=$db->fetch_array($q)) $rows[]=$row;
    shuffle($rows); $count=mt_rand(1,3);
    foreach (array_slice($rows,0,$count) as $row) {
        $item=array_intersect_key($row,array_flip(array('itm','itmk','itme','itms','itmsk','itmpara'))); $item['itmpara']=get_itmpara($item['itmpara']);
        flash_loot($p,array($item)); $db->query("DELETE FROM {$tablepre}mapitem WHERE iid=".(int)$row['iid']);
    }
    flash_say($rows?'你沿着她记下的路线搜完了一带。挑些带得走的东西。':'这片地方已经搜空了。换条街道，或等下一次回溯。');
    flash_after_action($p,'explore');
}
function flash_track(&$p,$id) {
    $s=&$p['clbpara']['flash']; $npc=fetch_playerdata_by_pid($id);
    if ($s['snare']) throw new RuntimeException('藤蔓束住了脚踝，暂时无法追踪。');
    if (!$npc || $npc['hp']<=0 || empty($npc['clbpara']['flash_npc']) || flash_is_final($npc) || flash_role($npc)==='jojo') throw new RuntimeException('找不到这样的足迹。');
    if (!ruleset_move_allowed_hook($p,(int)$npc['pls'])) throw new RuntimeException('足迹通向崩坏区域。先让移动PC维持一条通路。');
    flash_pay($p,45);
    if (mt_rand(1,100)<=flash_cfg('track_chance')) {
        $p['pls']=(int)$npc['pls']; $s['target']=(int)$npc['pid'];
        if ($s['gate']) { $s['gate_returns']++; $s['gate']=false; }
        flash_say('你循着足迹找到了'.$npc['name'].'。'); flash_after_action($p,'track');
    } else { flash_say('足迹在路口断了。花掉的体力不会回来。'); flash_after_action($p,'track_fail'); }
}
function flash_battle_action(&$p,$sweep=false,$jojo=false) {
    flash_require_empty_loot($p); $s=&$p['clbpara']['flash'];
    $p['pose']=1; $p['tactic']=$s['stance']==='suppress'?1:2;
    $batches=$sweep?flash_cfg('sweep_batch'):1;
    for ($i=0;$i<$batches && flash_ready($p);$i++) {
        if ($p['sp']<30) break;
        if ($sweep && $p['hp']<=$p['mhp']*flash_cfg('sweep_hp')) { flash_say('生命不足三成，扫荡已经停下。先治疗再出发。'); break; }
        $enemy=$s['target']?fetch_playerdata_by_pid((int)$s['target']):null;
        if (!$enemy || $enemy['hp']<=0 || (int)$enemy['pls']!==(int)$p['pls']) {
            $pool=array();
            if ($s['gate']) foreach ($s['trial']?array('guardian','hunter'):array('final') as $key) {
                $n=flash_npc($key); if ($n && $n['hp']>0 && (int)$n['pls']===(int)$p['pls']) $pool[]=$n;
            }
            foreach (flash_npcs() as $n) if ($n['hp']>0 && (int)$n['pls']===(int)$p['pls'] && !in_array(flash_role($n),array('jojo','friend','final','hunter','guardian'),true)) $pool[]=$n;
            if (!$pool) { flash_say('附近的敌人已经清空。'); break; }
            $enemy=$pool[array_rand($pool)];
        }
        if ($sweep && flash_role($enemy)!=='minion') {
            $s['target']=(int)$enemy['pid']; flash_say('扫荡停下了。前面是'.$enemy['name'].'；选择交战或换路。'); break;
        }
        if (flash_role($enemy)==='friend') throw new RuntimeException('这是带路者。若要袭击，请明确选择「攻击这位旅人」。');
        if ($sweep) flash_pay($p,30);
        flash_fight($p,(int)$enemy['pid'],$jojo,flash_cfg('battle_batch'),!$sweep);
        $latest=fetch_playerdata_by_pid((int)$enemy['pid']);
        if ($latest && $latest['hp']<=0) $s['corpse']=(int)$latest['pid'];
        // 每个被压缩的交战都是一次大行动；追击抵达即停止后面的扫荡。
        $old_target=$s['target']; flash_after_action($p,'battle');
        if (!$sweep || !flash_ready($p) || !empty($s['loot']) || $s['target'] || ($old_target!==$s['target'])) break;
    }
    if ($p['sp']<30) flash_say('体力不足以继续交战了。');
}
function flash_consume(&$p,$slot,$all=false) {
    if ($all || ($p['itms'.$slot]!=='∞' && --$p['itms'.$slot]<=0)) destory_single_item($p,$slot);
}
function flash_feed(&$p,$slot) {
    $s=&$p['clbpara']['flash']; $j=flash_npc('jojo'); $item=flash_item_at($p,$slot);
    if (!$j || $j['hp']<=0 || $s['jojo_missing'] || (int)$j['pls']!==(int)$p['pls']) throw new RuntimeException('先把乔乔找回来，或回到她身边。');
    if (empty($item['itms']) || !preg_match('/^(W|D|A|H[HSB])/',$item['itmk'])) throw new RuntimeException('她想要补给，或可以磨砺爪子的武器、防具。');
    $power=max(1,(int)$item['itme']);
    if (strpos($item['itmk'],'W')===0) { $j['att']+=(int)ceil($power/5); $j['wk']+=(int)ceil($power/10); $j['mhp']+=$power; }
    elseif (preg_match('/^[DA]/',$item['itmk'])) { $j['def']+=(int)ceil($power/3); $j['mhp']+=$power*4; }
    else { $j['hp']=min($j['mhp'],$j['hp']+$power*2); $s['food_buff']=min(6,$s['food_buff']+2); }
    $j['hp']=min($j['mhp'],$j['hp']+$power); $j['rage']=min($GLOBALS['mrage'],$j['rage']+30);
    $s['fed']++; flash_consume($p,$slot,!preg_match('/^H/',$item['itmk'])); flash_limit_stats($j); player_save($j);
    flash_say('乔乔：「乔乔真的可以收下的说！？诶嘿，下次换乔乔帮忙的说！」'); flash_job_event($p,'feed');
    if ($s['fed']===6) flash_story($p,'踮起脚尖','「乔乔有一位憧憬的大姐姐的说。总有一天，乔乔也会成为那样的大人的说！」她努力踮起脚，替你挡住迎面吹来的冷风。她健康的时候，会帮你减轻受到的伤害。');
}
function flash_use(&$p,$slot) {
    if ($slot<0 || $slot>6 || empty($p['itms'.$slot])) throw new RuntimeException('这个位置没有物品。');
    $item=flash_item_at($p,$slot);
    // 旧道具中的全局解禁、召唤和结局按钮不能绕过本模式的五片证据。
    if (!preg_match('/^(W|D|A|H[HSB]|C|GB|GA|EE|B)/',$item['itmk']) && empty($item['itmpara']['flash_reward']) && $item['itm']!=='混沌人肉探雷车') throw new RuntimeException('这件东西留作纪念吧。这里的路要用报告册来找。');
    if (!function_exists('itemuse')) require_once GAME_ROOT.'include/game/item.main.php';
    itemuse($slot,$p);
}
function ruleset_itemuse_hook(&$p,$slot,$item) {
    global $db,$tablepre,$hack_obbs;
    $s=&$p['clbpara']['flash'];
    if ($item['itm']==='移动PC' || $item['itmk']==='EE') {
        if ($item['itme']<=0) throw new RuntimeException('移动PC没电了。需要电池。');
        $p['itme'.$slot]--;
        if (mt_rand(0,99)<$hack_obbs) {
            $s['path']=true; flash_say('连接失败——这里根本没有「系统」。PC 强行维持了一条临时通路。崩坏区域仍然存在。');
        } else flash_say('屏幕亮了一下。通路没能接上，电力却少了一格。');
        $dice=mt_rand(0,99);
        if ($dice<5) { destory_single_item($p,$slot); flash_say('外壳裂开，移动PC在你手中碎成了一地零件。'); }
        elseif ($dice<8) { flash_say('屏幕里的裂缝伸了出来。'); flash_die($p,'通路崩塌'); }
        return true;
    }
    if ($item['itm']==='混沌人肉探雷车') {
        $q=$db->query("SELECT * FROM {$tablepre}maptrap WHERE pls=".(int)$p['pls']); $count=0;
        while ($trap=$db->fetch_array($q)) if (strpos($trap['itm'],'阔剑')!==false) { $db->query("DELETE FROM {$tablepre}maptrap WHERE tid=".(int)$trap['tid']); $count++; }
        if (!$count) throw new RuntimeException('这一带没有阔剑地雷。');
        flash_consume($p,$slot); flash_job_event($p,'disarm'); flash_say('探雷车驶过路口。'.$count.' 枚阔剑地雷被清除了。'); return true;
    }
    $para=get_itmpara($item['itmpara']);
    if (!empty($para['flash_reward'])) {
        if ($para['flash_reward']==='temper') {
            if ($p['wepk']==='WN' || !$p['weps']) throw new RuntimeException('先装备一件武器。');
            $p['wepe']+=(int)$item['itme']; flash_say('你把重新找回的锋芒压进武器。');
        } else {
            getclubskill($para['skill'],$p['clbpara']); flash_say('纸上记着你曾经会用的招式。');
        }
        flash_consume($p,$slot); return true;
    }
    return false;
}
function flash_reward_tier($p) { return min(7,1+(int)floor($p['lvl']/10)+(int)floor($p['clbpara']['flash']['deaths']/2)+flash_fragments($p)); }
function flash_dream_reward($level,$stage) {
    // 电波 skill984 的原骰表；不支持的核心/卡包映射为本模式锻片与现成技能书。
    if ($level===1) return mt_rand(0,1)?flash_item('留给同伴的点心','HB',120*$stage,4):null;
    if ($level===2) {
        $dice=round(mt_rand(0,999)*(.03*$stage+.9)); $rank=$dice<700?0:($dice<850?1:($dice<950?2:3));
        return flash_item(array('C','B','A','S')[$rank].'级锋芒','Y',(int)ceil(flash_cfg('reward_power')[$stage]*(.1+.1*$rank)),1,'',array('flash_reward'=>'temper'));
    }
    $dice=round(mt_rand(0,999)*(.01*$stage+.98)); $rank=$dice<900?0:($dice<960?1:($dice<989?2:($dice<999?3:4)));
    $skills=array('c2_intuit','c4_stable','c8_expert','c12_garrison','fireseed2');
    return flash_item(array('沾灰','陈旧','普通','精致','闪耀')[$rank].'的招式札记','Y',1,1,'',array('flash_reward'=>'skill','skill'=>$skills[$rank]));
}
function flash_reward(&$p,$tier,$choice,$weapon='') {
    $power=flash_cfg('reward_power')[$tier]; $gain=flash_cfg('reward_stats')[$tier]; $items=array();
    if ($choice==='stats') {
        $p['att']+=$gain; $p['def']+=$gain; $p['mhp']+=3*$gain; $p['hp']+=3*$gain; $p['msp']+=40; $p['sp']=min($p['msp'],$p['sp']+80);
        foreach (array('wp','wk','wg','wc','wd','wf') as $k) $p[$k]+=max(30,(int)floor($gain*.08));
        $p['lvl']+=2; flash_limit_stats($p);
        flash_say('熟悉的动作回到了身体里。攻击、防御、生命上限与熟练度都提高了。');
    } elseif ($choice==='equipment') {
        $kind=$weapon?: (in_array($p['wepk'],array('WP','WK','WG','WC','WD','WF'),true)?$p['wepk']:'WK');
        $items[]=flash_item('失而复得的'.array('WP'=>'撬棍','WK'=>'长刀','WG'=>'长枪','WC'=>'飞刃','WD'=>'爆破包','WF'=>'音叉')[$kind],$kind,$power,300,'rH');
        $items[]=flash_item('缝好的探险外套','DB',max(30,(int)($power*.7)),300,'AH');
    } else {
        $items[]=flash_item('热乎乎的行军餐','HB',max(150,(int)ceil($p['mhp']*.6)),8);
        $items[]=flash_item('装满的水壶','HS',max(200,(int)ceil($p['msp']*.6)),8);
        $items[]=flash_item('万能药','Ca',1,3);
    }
    $extra=flash_dream_reward(min(3,1+(int)floor(($tier-1)/2)),$tier); if ($extra) $items[]=$extra;
    flash_loot($p,$items);
}
function flash_accept(&$p,$key) {
    $s=&$p['clbpara']['flash']; $jobs=flash_jobs();
    if (strpos($key,'FL_recover_')===0) {
        if (empty($p['clbpara']['quest']['active'][$key]['loss']) || !empty($p['clbpara']['quest']['active'][$key]['accepted'])) throw new RuntimeException('这份找回委托已经接下，或已经完成。');
        $q=&$p['clbpara']['quest']['active'][$key];
        $q['accepted']=true; $q['at_revive']=$s['deaths']; $q['need']=2+(int)floor($s['deaths']/2); $q['progress']=0;
        flash_say('搬运工记下了现在的路况。完成 '.$q['need'].' 次探索或击杀，就能找回这一页约四分之三的损失。以后回溯不会提高这份委托的难度。'); return;
    }
    if (!isset($jobs[$key])) throw new RuntimeException('没有这份委托。');
    $spec=$jobs[$key]; $qid='FL_job_'.$key;
    if ($p['lvl']<$spec['lvl'] || $s['deaths']<$spec['rev']) throw new RuntimeException('还没有到接下这份委托的时候。');
    if (!empty($p['clbpara']['quest']['active'][$qid])) throw new RuntimeException('这份委托已经接下了。');
    // 同一委托完成后可再次接取；奖励与目标只在接取时确定。
    $p['clbpara']['quest']['active'][$qid]=array('kind'=>$spec['kind'],'need'=>$spec['need'],'progress'=>0,'tier'=>flash_reward_tier($p),'at_revive'=>$s['deaths']);
    flash_say($spec['text']);
}
function flash_claim(&$p,$qid,$choice) {
    $weapon='';
    if (preg_match('/^equipment_(W[PKGCDF])$/D',$choice,$m)) { $choice='equipment'; $weapon=$m[1]; }
    if (!in_array($choice,array('stats','equipment','supplies'),true)) throw new RuntimeException('选择一种委托报酬。');
    flash_require_empty_loot($p);
    $q=$p['clbpara']['quest']['active'][$qid]??null;
    if (!$q || (strpos($qid,'FL_job_')!==0 && strpos($qid,'FL_recover_')!==0)) throw new RuntimeException('这份委托不在进行中。');
    if (($q['kind']??'')==='collect') {
        $available=0; for ($i=1;$i<=6;$i++) if (preg_match('/^H[HSB]/',$p['itmk'.$i]) && $p['itms'.$i]!=='∞') $available+=(int)$p['itms'.$i];
        if ($available<$q['need']) throw new RuntimeException('背包里还没有足够的补给。');
        $left=$q['need'];
        for ($i=1;$i<=6 && $left;$i++) if (preg_match('/^H[HSB]/',$p['itmk'.$i]) && $p['itms'.$i]!=='∞') {
            $take=min($left,(int)$p['itms'.$i]); $p['itms'.$i]-=$take; $left-=$take; if (!$p['itms'.$i]) destory_single_item($p,$i);
        }
        $q['progress']=$q['need'];
    }
    if (empty($q['need']) || ($q['progress']??0)<$q['need']) throw new RuntimeException('委托还没有完成。');
    // 先从 active 移走；请求序号与 process.lock 共同挡住重复提交。
    quest_complete($qid,$p);
    if (strpos($qid,'FL_recover_')===0) {
        foreach ($q['loss']['stats'] as $key=>$value) $p[$key]+=(int)floor($value*.75);
        foreach ($q['loss']['skill'] as $sk) getclubskill($sk,$p['clbpara']);
        foreach ($q['loss']['skillpara'] as $sk=>$params) foreach ($params as $key=>$value) if (is_numeric($value)) $p['clbpara']['skillpara'][$sk][$key]=max($p['clbpara']['skillpara'][$sk][$key]??0,$value);
        $items=array();
        foreach (array_merge(array_values($q['loss']['equipment']),$q['loss']['bag']) as $item) { $item['itme']=max(1,(int)floor($item['itme']*.75)); $items[]=$item; }
        flash_limit_stats($p); flash_loot($p,$items); flash_say('包裹找回来了。破损的部分补不全，但大约四分之三还在。');
    } else flash_reward($p,$q['tier'],$choice,$weapon);
}
function flash_talk(&$p,$key) {
    $n=flash_npc('friend_'.$key); $s=&$p['clbpara']['flash'];
    if (!$n || $n['hp']<=0 || (int)$n['pls']!==(int)$p['pls']) throw new RuntimeException('那位旅人不在身旁。');
    if ($key==='porter') flash_story($p,'迷之搬运工','「快递单上写着你的名字，那就没跑儿了。这地方送件比什么都危险。不过，你已经回来过一次了吧？那这活儿就有人干了。」委托随时可在右侧接取与提交。');
    elseif ($key==='nurse') {
        if (empty($s['events']['nursed'])) { $p['hp']=$p['mhp']; $p['inf']=''; $s['events']['nursed']=true; }
        flash_story($p,'看护女仆 · 黑河','「我替您包扎好了。药箱这一回只够用一次，请好好走到下个路口。」她的指尖很冷，绷带却是暖的。');
    } elseif ($key==='cook') {
        if (empty($s['events']['cooked'])) { flash_require_empty_loot($p); flash_loot($p,array(flash_item('两人份压缩饼干','HB',max(100,(int)($p['mhp']*.5)),8))); $s['events']['cooked']=true; }
        flash_story($p,'烹饪女仆 · 红烁','「给您留了一份，还有一份给猫耳朵的小姑娘。她上次在这里站了很久，最后只要了半块。」乔乔在你背后拼命摇头。');
    } elseif ($key==='tracker') flash_story($p,'蓝色星辰 · 粼粼水','「脚印断了，就听声音。声音断了，就看树叶。」她绕到另一条街去了。追踪会花 45 体力，即使失败也一样；成功时直接抵达目标身边。');
    elseif ($key==='eci') flash_story($p,'是被检体ECI！','「是被检体ECI！」它重复了一遍，盯着你攥紧报告册的手。「这本书，不要借给别人。」你再问时，它已经忘了刚才说过什么。');
    elseif ($key==='guide') flash_story($p,'迎宾女仆 · 紫梦','「门就在无月之影的尽头。五片证据，少一片都不能进去。门后的伤会随您的回归复原，请在同一次醒来中走完。」她指了指药包，「别带着麻痹去接那最后一击。」');
    else flash_story($p,$n['name'],$key==='blue'?'青鬼递来一张折起的路线图。她说赤鬼今天有些不对劲，请你先看看她的刀。':'赤鬼垂着眼，看起来正在等人。灼敏丸的刀鞘上沾着一点烧焦的纸。');
}
function flash_event(&$p,$event) {
    $s=&$p['clbpara']['flash'];
    if (!in_array($event,array('chain','drift','kind'),true)) throw new RuntimeException('没有这样的路口。');
    if (!empty($s['events'][$event])) throw new RuntimeException('这一回，这件事已经发生过了。');
    $s['events'][$event]=true;
    if ($event==='chain') {
        $red=flash_npc('friend_red'); $blue=flash_npc('friend_blue');
        if (!$red || !$blue || $red['hp']<=0 || $blue['hp']<=0) { flash_report($p,'chain'); flash_say('你赶在刀落下前截断了这场争执。原来那一刀的去向，仍值得记下来。'); return; }
        $blue['hp']=0; player_save($blue); flash_report($p,'chain');
        $red['clbpara']['flash_npc']['silence']=true; $red['pls']=$p['pls']; $red['att']=max(3000,$red['att']); $red['wepe']=1500;
        getclubskill('buff_duel',$red['clbpara']); player_save($red);
        flash_story($p,'互杀断链','你赶到时，赤鬼已经收刀。青鬼倒在地上，火光中连灰都没有留下。报告册自动多了一页。赤鬼回头：「……你也看见了吗？」');
        flash_fight($p,(int)$red['pid'],false,1,false,true);
    } elseif ($event==='drift') {
        flash_report($p,'drift'); flash_story($p,'世界线漂移','你跟着自己的脚步声拐过第二个路口。声音从身后响起来，而前面走来的，还是你。两条路同时合拢。'); flash_die($p,'世界线漂移');
    } else {
        flash_report($p,'kind'); flash_story($p,'善意陷阱','门里的人说有热茶。茶香是真的，伸来的手也是真的。你跨过门槛时，地板却落向没有底的黑暗。她只来得及说一声抱歉。'); flash_die($p,'善意陷阱');
    }
}
function flash_action(&$p,$action,$arg,$extra='') {
    $s=&$p['clbpara']['flash'];
    if ($s['result']) throw new RuntimeException('这一段旅程已经结束。');
    if ($action==='keep') { flash_keep($p,$arg); return; }
    if ($s['pending']) throw new RuntimeException('先选择这次回溯要保留的东西。');
    if (!flash_ready($p)) throw new RuntimeException('你现在无法行动。');
    switch ($action) {
        case 'move': flash_move($p,(int)$arg); break;
        case 'return': flash_move($p,(int)$s['last_place'],true); break;
        case 'explore': flash_explore($p); break;
        case 'battle': flash_battle_action($p); break;
        case 'sweep': flash_battle_action($p,true); break;
        case 'jojo': flash_battle_action($p,false,true); break;
        case 'track': flash_track($p,(int)$arg); break;
        case 'stance':
            if (!in_array($arg,array('attack','suppress'),true)) throw new RuntimeException('选择一起攻击或挠痒牵制。');
            $s['stance']=$arg; $p['pose']=$arg==='attack'?1:0; $p['tactic']=$arg==='attack'?2:1; break;
        case 'use': flash_use($p,(int)$arg); break;
        case 'feed': flash_feed($p,(int)$arg); break;
        case 'swap': change_subwep(2,$p); break;
        case 'drop':
            if ((int)$arg<0 || (int)$arg>6 || empty($p['itms'.(int)$arg])) throw new RuntimeException('没有这件物品。');
            itemdrop((int)$arg,$p); break;
        case 'take': case 'loot_use': case 'loot_feed':
            if (!ctype_digit((string)$arg) || !isset($s['loot'][(int)$arg])) throw new RuntimeException('这件物品已经取走了。');
            $item=$s['loot'][(int)$arg];
            if ($action==='take') { if (!flash_take($p,$item)) throw new RuntimeException('六格背包已满。可以直接装备、使用或喂给乔乔。'); }
            else {
                if (!empty($p['itms0'])) throw new RuntimeException('先处理手里的物品。');
                flash_set_item($p,0,$item);
                try { if ($action==='loot_feed') flash_feed($p,0); else flash_use($p,0); }
                catch (RuntimeException $e) { flash_set_item($p,0); throw $e; }
                if (!empty($p['itms0'])) $s['loot'][(int)$arg]=flash_item_at($p,0); else unset($s['loot'][(int)$arg]);
                flash_set_item($p,0); break;
            }
            unset($s['loot'][(int)$arg]); break;
        case 'leave_loot':
            foreach ($s['loot'] as $item) { $item['pls']=$p['pls']; $item['itmpara']=json_encode($item['itmpara'],JSON_UNESCAPED_UNICODE); $GLOBALS['db']->array_insert($GLOBALS['tablepre'].'mapitem',$item); }
            $s['loot']=array(); break;
        case 'corpse':
            flash_require_empty_loot($p); $n=fetch_playerdata_by_pid((int)($s['corpse']??0));
            if (!$n || $n['hp']>0 || (int)$n['pls']!==(int)$p['pls'] || !empty($n['clbpara']['flash_npc']['looted'])) throw new RuntimeException('这具遗骸已经没有可以带走的东西。');
            $n['clbpara']['flash_npc']['looted']=true; $items=array();
            foreach (array('wep','arb','art') as $slot) if (!empty($n[$slot.'s'])) $items[]=flash_equipment($n,$slot);
            for ($i=1;$i<=6;$i++) if (!empty($n['itms'.$i])) $items[]=flash_item_at($n,$i);
            flash_loot($p,$items); player_save($n); break;
        case 'accept': flash_accept($p,$arg); break;
        case 'claim': flash_claim($p,$arg,$extra); break;
        case 'talk': flash_talk($p,$arg); break;
        case 'event': flash_event($p,$arg); break;
        case 'attack_friend':
            $n=flash_npc('friend_'.$arg); if (!$n || $n['hp']<=0 || (int)$n['pls']!==(int)$p['pls']) throw new RuntimeException('这位旅人不在这里。');
            flash_fight($p,(int)$n['pid'],false,flash_cfg('battle_batch')); flash_after_action($p,'battle'); break;
        case 'recover_jojo':
            if (!$s['jojo_missing'] || (int)$p['pls']!==0) throw new RuntimeException('回到无月之影，找一找走散的她。');
            flash_pay($p,45); $j=flash_npc('jojo'); $j['hp']=$j['mhp']; $j['sp']=$j['msp']; $j['pls']=0; $j['inf']=''; unset($j['clbpara']['flash_npc']['rewarded']); player_save($j); $s['jojo_missing']=false;
            flash_story($p,'衣角上的小手','「乔乔在这里的说……才、才没有迷路的说！」她抓住了你的衣角。这次先别松开。'); break;
        case 'gate':
            if ((int)$p['pls']!==0 || $s['snare']) throw new RuntimeException('门在无月之影的尽头。');
            if (flash_fragments($p)<5) { flash_story($p,'缺了一角的门','你把手伸进门缝。门没有打开，影子却先一步穿过了你的身体。'); flash_die($p,'无证闯门'); break; }
            flash_mark($p,'FL_gate'); $s['gate']=true; $s['events']['gate_opened']=true;
            $n=flash_npc($s['trial']?'guardian':'final'); $s['target']=$n['hp']>0?(int)$n['pid']:0;
            $n['pls']=0; player_save($n);
            flash_story($p,'门后的剪影','五片证据拼在一起，报告册微微发烫。乔乔躲到你身后：「大人们就是要一边害怕一边往前走的说。」门里站着一个高挑的黑袍剪影，兜帽下露出两片猫耳尖，藤蔓兵器缀着七点光。她没有说话。'); flash_stage($p); break;
        case 'retreat':
            if (!$s['gate'] || $s['snare']) throw new RuntimeException('藤蔓尚未松开，无法折返。');
            flash_pay($p,15); $s['gate']=false; $s['target']=0; $s['gate_returns']++; break;
        case 'trial':
            $n=flash_npc('final');
            if (!$s['gate'] || $n['hp']>0 || !flash_true_available($p) || $s['trial']) throw new RuntimeException('光线之外的脚步已经听不清了。');
            $s['trial']=true; $n=flash_npc('guardian'); $s['target']=(int)$n['pid'];
            flash_story($p,'一步之外','你没有走向清晨。远处的门闩仍垂着，另一个脚步却正向你逼近。一个追赶，一个守候。乔乔把你的手握紧了。'); flash_stage($p); break;
        case 'escape':
            $n=flash_npc('final'); if (!$s['gate'] || $n['hp']>0 || $s['snare']) throw new RuntimeException('剪影还守在门前。');
            flash_finish($p,'normal'); break;
        case 'rewind': flash_die($p,'合上这一页'); break;
        default: throw new RuntimeException('无法执行这项行动，请刷新页面。');
    }
}
