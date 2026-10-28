<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_lock() {
    global $plock,$flash_lock_handle;
    if (is_resource($plock) || is_resource($flash_lock_handle)) return true;
    $flash_lock_handle=@fopen(GAME_ROOT.'gamedata/process.lock','ab');
    if (!$flash_lock_handle || !flock($flash_lock_handle,LOCK_EX)) return false;
    load_gameinfo(); return true;
}
function flash_room($at) {
    $order=array_values(array_filter(array_keys($GLOBALS['xyinfo']),function($id){return is_int($id) && $id>0;})); shuffle($order);
    $kinds=array('P','K','G','C','D','F'); shuffle($kinds);
    return array('start'=>$at,'order'=>$order,'weak'=>array(2=>$kinds[0],3=>$kinds[1]),'npcs'=>array(),'owner'=>0);
}
function flash_closed() { return array_slice($GLOBALS['gamevars']['flash']['order'],0,max(0,(int)$GLOBALS['areanum'])); }
function flash_safe() { return array_merge(array(0),array_values(array_diff($GLOBALS['gamevars']['flash']['order'],flash_closed()))); }
function flash_npcs() {
    global $db,$tablepre;
    $all=array(); $q=$db->query("SELECT * FROM {$tablepre}players WHERE type>0");
    while ($row=$db->fetch_array($q)) { $row['clbpara']=get_clbpara($row['clbpara']); $all[(int)$row['pid']]=$row; }
    return $all;
}
function flash_npc($key) {
    $id=$GLOBALS['gamevars']['flash']['npcs'][$key]??0;
    return $id?fetch_playerdata_by_pid((int)$id):null;
}
function flash_source($pool,$type,$sub) {
    static $pools;
    if (!$pools) {
        require GAME_ROOT.'gamedata/cache/npc_1.php'; $pools['npc']=$npcinfo;
        require GAME_ROOT.'gamedata/cache/addnpc_1.php'; $pools['add']=$anpcinfo;
        require GAME_ROOT.'gamedata/ruleset/ACDTS_298SP4/cache/npc_1.php'; $pools['old']=$npcinfo;
    }
    $base=$pools[$pool][$type]; $npc=array_merge($base,$base['sub'][$sub]); unset($npc['sub']);
    return $npc;
}
function flash_spawn($spec,$key,$role,$place,$persist=false) {
    global $db,$tablepre,$gamevars,$now,$baseexp;
    $n=array_merge(create_dummy_playerdata(),$spec);
    $n['type']=$role==='jojo'?97:($role==='friend'?98:96);
    $n['pls']=$place; $n['pgroup']=0; $n['endtime']=$now; $n['validtime']=$now;
    $n['hp']=$n['mhp']; $n['sp']=$n['msp']; $n['exp']=($n['lvl']*2+1)*$baseexp;
    $n['clbpara']=array('skill'=>array(),'skillpara'=>array(),'charge1'=>0,'charge2'=>0,'charge3'=>0,'charge4'=>0);
    $n['teamID']=$n['teamPass']=''; $n['action']=''; $n['bid']=0; $n['state']=0;
    foreach (array('wp','wk','wg','wc','wd','wf') as $field) if (!$n[$field]) $n[$field]=$n['skill']??50;
    require_once GAME_ROOT.'include/game/clubslct.func.php';
    if ($n['club']>=90) $n['club']=0;
    if ($n['club']) changeclub($n['club'],$n);
    if (!empty($n['clubskill']) || !empty($n['clubskillpara'])) customtclubskill($n);
    $n['clbpara']['flash_npc']=array('key'=>$key,'role'=>$role,'persist'=>$persist);
    // 本模式的奖励由尸体掉落和委托发放，源 NPC 的解禁道具不带入。
    for ($slot=0;$slot<=6;$slot++) if (!preg_match('/^(W|D|A|H[HSB]|C|GB)/',$n['itmk'.$slot])) flash_set_item($n,$slot);
    $row=player_format_with_db_structure($n); unset($row['pid']); $db->array_insert($tablepre.'players',$row);
    $n['pid']=(int)$db->insert_id();
    if ($key!=='') $gamevars['flash']['npcs'][$key]=$n['pid'];
    return $n;
}
function flash_world_reset(&$p=null,$initial=false) {
    global $gamevars,$db,$tablepre;
    $safe=array_values(array_diff(flash_safe(),array(0))); if (!$safe) $safe=array(0);
    foreach (flash_npcs() as $n) {
        $meta=$n['clbpara']['flash_npc']??array();
        if (empty($meta['persist'])) { $db->query("DELETE FROM {$tablepre}players WHERE pid=".(int)$n['pid']); continue; }
        if ($meta['role']==='final' || $meta['role']==='hunter' || $meta['role']==='guardian') {
            $n['hp']=$n['mhp']; $n['sp']=$n['msp']; $n['pls']=0; $n['inf']='';
            $n['clbpara']['flash_npc']['rewinds']=0; $n['clbpara']['flash_npc']['turns']=0;
            unset($n['clbpara']['flash_npc']['rewarded']);
        } elseif ($meta['role']==='jojo') { $n['pls']=0; if ($n['hp']>0) { $n['hp']=$n['mhp']; $n['sp']=$n['msp']; $n['inf']=''; } }
        elseif (in_array((int)$n['pls'],flash_closed(),true)) $n['pls']=$safe[array_rand($safe)];
        $n['action']=''; $n['bid']=0; player_save($n);
    }
    if ($initial) {
        $j=flash_spawn(array('name'=>'乔乔','gd'=>'f','icon'=>0,'club'=>0,'mhp'=>3200,'msp'=>400,'att'=>360,'def'=>280,'lvl'=>30,'skill'=>900,
            'wep'=>'利爪','wepk'=>'WK','wepe'=>25,'weps'=>'∞','wepsk'=>'r','arb'=>'小小的探险斗篷','arbk'=>'DB','arbe'=>180,'arbs'=>'∞','arbsk'=>'E'),
            'jojo','jojo',0,true);
        foreach (flash_boss_specs() as $id=>$spec) {
            $n=call_user_func_array('flash_source',$spec['source']); $n['name']=$spec['name'];
            foreach (array('hp'=>'mhp','att'=>'att','def'=>'def','skill'=>'skill','wepe'=>'wepe') as $from=>$to) if (isset($spec[$from])) $n[$to]=$spec[$from];
            if ($spec['rule']==='recoil') {
                $n['pose']=1; $n['tactic']=2;
                $n['club']=0; $n['clubskill']=array(); $n['clubskillpara']=array();
                foreach (array('wepsk','arbsk','arhsk','arask','arfsk','artsk') as $key) $n[$key]=str_replace(array('H','Z','n'),'', $n[$key]??'');
            }
            $n=flash_spawn($n,'boss'.$id,'boss',$safe[($id*5)%count($safe)],true);
            $n['clbpara']['flash_npc']+=array('evidence'=>$id,'rule'=>$spec['rule']); player_save($n);
        }
        $n=flash_source('npc',1,0);
        $n=array_merge($n,array('name'=>'无面黑袍剪影','gd'=>'f','icon'=>0,'club'=>0,'clubskill'=>array(),'clubskillpara'=>array(),
            'mhp'=>18000,'att'=>770,'def'=>770,'skill'=>770,'wep'=>'缠藤重兵','wepk'=>'WK','wepe'=>770,'weps'=>'∞','wepsk'=>'re',
            'arbe'=>770,'arhe'=>300,'arae'=>300,'arfe'=>300,'arte'=>1,'artsk'=>'H'));
        for ($slot=0;$slot<=6;$slot++) flash_set_item($n,$slot);
        flash_spawn($n,'final','final',0,true);
        $h=flash_source('add',19,0); $h['name']='一步之外的剪影'; $h['club']=0; $h['clubskillpara']=array();
        $h['mhp']=20000; $h['att']=3000; $h['wep']='缠藤重兵'; $h['wepk']='WK'; $h['wepsk']='re'; $h['icon']=0;
        for ($slot=0;$slot<=6;$slot++) flash_set_item($h,$slot);
        flash_spawn($h,'hunter','hunter',0,true);
        $g=flash_source('npc',22,0); $g['name']='门尽头的剪影'; $g['club']=0; $g['wep']='无声的门闩'; $g['wepk']='WK'; $g['wepsk']='re'; $g['icon']=0;
        for ($slot=0;$slot<=6;$slot++) flash_set_item($g,$slot);
        flash_spawn($g,'guardian','guardian',0,true);
    }
    foreach (flash_friends() as $key=>$source) if ($initial || in_array($key,array('blue','red'),true)) {
        $place=in_array($key,array('blue','red','tracker','eci'),true)?$safe[array_rand($safe)]:0;
        flash_spawn(flash_source('add',$source[0],$source[1]),'friend_'.$key,'friend',$place,!in_array($key,array('blue','red'),true));
    }
    foreach ($safe as $place) for ($i=0;$i<3;$i++) flash_spawn(flash_source('npc',90,mt_rand(0,3)),'','minion',$place);
    for ($i=0;$i<12;$i++) {
        $n=flash_spawn(flash_source('add',2,$i%8),'','holo',$safe[array_rand($safe)]);
        if ($i%3===0) { $n['clbpara']['flash_npc']['flee']=true; player_save($n); }
    }
    foreach (array(array('old',5,0),array('old',5,1),array('old',6,0)) as $i=>$source) flash_spawn(call_user_func_array('flash_source',$source),'hazard'.$i,'hazard',$safe[array_rand($safe)]);
    foreach (array(array('npc',91,0),array('add',5,1),array('add',4,0),array('npc',20,0),array('npc',1,0)) as $source) flash_spawn(call_user_func_array('flash_source',$source),'','elite',$safe[array_rand($safe)]);
    flash_mapitems_reset($safe);
    if ($p) {
        $s=&$p['clbpara']['flash']; $s['events']=array(); $s['loot']=array(); $s['target']=0; $s['snare']=0; $s['gate']=false; $s['trial']=false;
        unset($s['reset_world']);
    }
    save_gameinfo();
}
function flash_mapitems_reset($safe) {
    global $db,$tablepre;
    $db->query("DELETE FROM {$tablepre}mapitem"); $db->query("DELETE FROM {$tablepre}maptrap");
    // 与公共资源相同的列顺序、数量权重和随机地点；不复制其他模式的资源文件。
    foreach (openfile(GAME_ROOT.'gamedata/cache/mapitem_1.php') as $line) {
        $parts=explode(',',$line,9); if (count($parts)<8 || !in_array((int)$parts[0],array(0,99),true)) continue;
        list($ban,$place,$count,$name,$kind,$effect,$durability,$keys)=$parts;
        if (!preg_match('/^(W[PKGCDNFJ]|D[BHAF]|A$|H[HSB]$|C[a-z]*$|GB|EE$|B$)/',$kind)) continue;
        for ($i=0;$i<(int)$count;$i++) {
            $dest=(int)$place===99?$safe[array_rand($safe)]:(int)$place;
            if ($dest && !in_array($dest,$safe,true)) continue;
            $db->array_insert($tablepre.'mapitem',array('itm'=>$name,'itmk'=>$kind,'itme'=>$effect,'itms'=>$durability,'itmsk'=>$keys,'pls'=>$dest,'itmpara'=>trim($parts[8]??'')));
        }
    }
    foreach ($safe as $place) {
        $db->array_insert($tablepre.'maptrap',array('itm'=>'阔剑地雷','itmk'=>'TO','itme'=>1200,'itms'=>1,'itmsk'=>'','pls'=>$place,'itmpara'=>''));
        foreach (array(flash_item('压缩饼干','HB',100,4),flash_item('混沌人肉探雷车','Y',1,1)) as $item) { $item['pls']=$place; $db->array_insert($tablepre.'mapitem',$item); }
    }
    $pc=flash_item('移动PC','EE',5,1); $pc['pls']=0; $db->array_insert($tablepre.'mapitem',$pc);
}
function flash_neighbours($place) {
    global $xyinfo;
    $origin=explode('-',$xyinfo[$place]); $dist=array();
    foreach ($xyinfo as $id=>$coords) if (is_int($id) && $id!==$place) { $xy=explode('-',$coords); $dist[$id]=abs(ord($xy[0])-ord($origin[0]))+abs((int)$xy[1]-(int)$origin[1]); }
    asort($dist); return array_slice(array_keys($dist),0,6);
}
function flash_item($name,$kind,$effect,$amount,$keys='',$para=array()) { return array('itm'=>$name,'itmk'=>$kind,'itme'=>$effect,'itms'=>$amount,'itmsk'=>$keys,'itmpara'=>$para); }
function flash_item_at($p,$slot) {
    $out=array(); foreach (array('itm','itmk','itme','itms','itmsk','itmpara') as $f) $out[$f]=$p[$f.$slot]??'';
    $out['itmpara']=get_itmpara($out['itmpara']); return $out;
}
function flash_set_item(&$p,$slot,$item=array()) {
    foreach (array('itm','itmk','itme','itms','itmsk','itmpara') as $f) $p[$f.$slot]=$item[$f]??(in_array($f,array('itme','itms'))?0:'');
}
function flash_equipment($p,$slot) {
    $out=array(); foreach (array(''=>'itm','k'=>'itmk','e'=>'itme','s'=>'itms','sk'=>'itmsk','para'=>'itmpara') as $suffix=>$f) $out[$f]=$p[$slot.$suffix]??'';
    $out['itmpara']=get_itmpara($out['itmpara']); return $out;
}
function flash_set_equipment(&$p,$slot,$item) {
    foreach (array(''=>'itm','k'=>'itmk','e'=>'itme','s'=>'itms','sk'=>'itmsk','para'=>'itmpara') as $suffix=>$f) $p[$slot.$suffix]=$item[$f]??(in_array($suffix,array('e','s'))?0:'');
}
function flash_take(&$p,$item) {
    // 精确相同的物品才能叠加，保留所有 para 和模式标记；绝不覆盖 itm0。
    if (preg_match('/^(H|C|GB|WC|WD|WF)/',$item['itmk']) && $item['itms']!=='∞') for ($i=1;$i<=6;$i++) {
        $old=flash_item_at($p,$i); if (!$old['itms'] || $old['itms']==='∞') continue;
        $a=$old; $b=$item; unset($a['itms'],$b['itms']);
        if ($a==$b) { $p['itms'.$i]+=$item['itms']; return true; }
    }
    for ($i=1;$i<=6;$i++) if (empty($p['itms'.$i])) {
        if (!empty($p['itms0'])) return false;
        flash_set_item($p,0,$item); itemadd($p); return empty($p['itms0']);
    }
    return false;
}
function flash_loot(&$p,$items) {
    // 一次操作产生的奖励最多留下一个有界选择盘，包满时仍可从盘中装备/使用/喂食。
    foreach ($items as $item) if (!empty($item['itms'])) $p['clbpara']['flash']['loot'][]=$item;
}
