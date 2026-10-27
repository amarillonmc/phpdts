<?php
if (!defined('IN_GAME')) exit('Access Denied');

// 同一张类别表用于尸体携带物和任务奖励；唯一剧情物品不能被随机复制。
function elevator_loot_config($pool,$tier) {
    $themes=array(1=>'食品|学生卡|报纸|饮料',2=>'电子|零件|电路|螺栓|数据',3=>'书|笔|文具|纸|M.D.',
        4=>'子弹|军|武器|口粮|匕首',5=>'猫|饮料|密码|钥匙|谜',6=>'立方|钥匙|镜|递归|画框|时钟',
        7=>'糖|应援|票|截图|签名',8=>'矿|零件|碎片|钻|子弹');
    $cfg=array('count'=>array(array(1,2),array(2,3),array(2,4))[$tier],
        'weights'=>array('supply'=>35,'theme'=>45,'equipment'=>12,'weapon'=>$tier?18:6,'blueprint'=>$tier?3:1,'relic'=>$tier===2?5:1,'skill'=>$tier===2?5:1),
        'theme'=>$themes[$pool]??'零件|食品');
    return array_replace_recursive($cfg,$GLOBALS['elevator_loot_config'][$pool][$tier]??array());
}
function elevator_pool_loot($pool,$tier,$floor=1,$enemy=false) {
    global $gamevars;
    $tier=max(0,min(2,(int)$tier)); $cfg=elevator_loot_config($pool,$tier); $round=$gamevars['elevator']['round']??'';
    $buckets=array();
    foreach (elevator_catalog() as $id=>$s) {
        if ($s['unique']) continue;
        if (in_array($s['kind'],array('HH','HB','HS'),true)) $buckets['supply'][]=$id;
        if ($s['pool']===$pool) {
            $buckets['theme'][]=$id;
            if (preg_match('/'.$cfg['theme'].'/u',$s['name'])) { $buckets['theme'][]=$id; $buckets['theme'][]=$id; }
        }
        if (preg_match('/^[DA]/',$s['kind'])) $buckets['equipment'][]=$id;
        if (strpos($s['kind'],'W')===0) $buckets['weapon'][]=$id;
    }
    $weights=$cfg['weights'];
    foreach ($weights as $cat=>$weight) if (in_array($cat,array('supply','theme','equipment','weapon'),true) && empty($buckets[$cat])) unset($weights[$cat]);
    if (empty($gamevars['rr_recipes'])) unset($weights['blueprint']);
    if (!function_exists('elevator_round_skill_pool') || !elevator_round_skill_pool()) unset($weights['skill']);
    $out=array();
    for ($i=0,$n=mt_rand($cfg['count'][0],$cfg['count'][1]);$i<$n;$i++) {
        $roll=mt_rand(1,array_sum($weights)); $cat='supply';
        foreach ($weights as $key=>$weight) { $roll-=$weight; if ($roll<=0) { $cat=$key; break; } }
        if ($cat==='blueprint') $out[]=rr_blueprint_item(array_rand($gamevars['rr_recipes']),$floor);
        elseif ($cat==='relic') $out[]=elevator_relic($floor,$round);
        elseif ($cat==='skill') $out[]=elevator_skill_book(rr_pick(elevator_round_skill_pool()));
        else {
            $item=elevator_item(rr_pick($buckets[$cat]),$round);
            if (preg_match('/^[WDH]/',$item['itmk'])) $item['itme']=(int)round($item['itme']*(1+.1*floor(max(0,abs($floor)-8)/3)));
            $out[]=$item;
        }
    }
    // 钥匙卡仅由敌人携带；旧红杀的第二次判定保持独立。
    if ($enemy && (mt_rand(1,100)<=elevator_config('armory_key_rate') || ($pool===4 && mt_rand(1,100)<=elevator_config('armory_elite_key_bonus')))) $out[0]=elevator_keycard();
    return $out;
}
function elevator_npc_presets() {
    $normal=array(1=>'巷子里的混混',2=>'模因残留体',3=>'噪点体',4=>'旧红杀残兵',5=>'复制猫毛球',6=>'回声体',7=>'失控应援牌',8=>'数据碎片聚合体');
    $elite=array(1=>'通缉名单第零位',2=>'断链的执行体',3=>'M.D. 借阅者',4=>'旧红杀残部小队长',5=>'水龙头之影',6=>'镜中人',7=>'过气主播的怒火',8=>'钻头自律机');
    $result=array();
    foreach ($normal as $pool=>$name) $result[$pool]=array('normal'=>array('name'=>$name,'icon'=>0), 'elite'=>array('name'=>$elite[$pool],'icon'=>$pool));
    return array_replace_recursive($result,$GLOBALS['elevator_npc_presets']??array());
}
function elevator_elite_rules() {
    return $GLOBALS['elevator_elite_rules']??array(
        'assault'=>array('name'=>'攻击特化','att'=>1.3,'mhp'=>1.2),
        'guard'=>array('name'=>'防御特化','def'=>1.3,'mhp'=>1.3),
        'swift'=>array('name'=>'迅捷','wepe'=>1.2,'mhp'=>1.2,'hit'=>1.2),
        'loot'=>array('name'=>'夺命','att'=>1.1,'def'=>1.1,'mhp'=>1.1,'wepe'=>1.1));
}
function elevator_floor_npc($floor,$pool,$elite=false,$rule='') {
    $rank=elevator_rank($floor)+($elite?2:0); $presets=elevator_npc_presets();
    $rules=elevator_elite_rules(); if ($elite && !isset($rules[$rule])) $rule=rr_pick(array_keys($rules));
    $tier=$elite?($rule==='loot'?2:1):0;
    $npc=rr_generate_npc($rank,$floor<0?25:0,$floor>0?15:0,min(20,$rank*2),$pool,$tier,$floor);
    $skin=$presets[$pool][$elite?'elite':'normal']; $npc['name']=$skin['name']; $npc['icon']=$skin['icon'];
    $npc['clbpara']['elevator_npc']+=array('floor'=>$floor,'pool'=>$pool,'elite'=>$elite,'rule'=>$rule);
    if ($elite) {
        foreach (array('att','def','mhp','wepe') as $key) if (isset($rules[$rule][$key])) $npc[$key]=(int)round($npc[$key]*$rules[$rule][$key]);
        $npc['mhp']=min($npc['mhp'],(int)round(8000*(1+max(0,$rank-20)*.12))); $npc['hp']=$npc['mhp'];
        $npc['name'].=' · '.$rules[$rule]['name'];
        // 首版不投放 NPC 诅咒；部署者可配置这两个对战词条。
        $npc['clbpara']['elevator_npc']['curses']=$GLOBALS['elevator_npc_curses']??array();
    }
    return $npc;
}
