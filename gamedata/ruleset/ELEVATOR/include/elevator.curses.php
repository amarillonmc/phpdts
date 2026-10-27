<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_curse_level($p,$key) {
    $c=elevator_decode($p['clbpara']??array());
    if (!empty($p['type'])) return in_array((string)$key,array('904','906'),true)?max(0,(int)($c['elevator_npc']['curses'][$key]??0)):0;
    return !empty($c['elevator']['on'])?max(0,(int)($c['elevator']['curses'][$key]??0)):0;
}
function elevator_curse_names() { return array('903'=>'厄运','904'=>'脆弱','906'=>'蚀骨','erosion'=>'夹层同化'); }
function elevator_endless_offer(&$p) {
    $s=&$p['clbpara']['elevator'];
    if (!empty($s['endless_step']) || ($s['curse_claimed']??0)>=($s['curse_depth']??0)) return;
    $pool=array_values(array_filter(elevator_round_skill_pool(),function($sk)use($p){return !elevator_has_skill($p,(int)substr($sk,3));}));
    shuffle($pool);
    if (count($pool)<elevator_config('curse_skill_choices')) {
        $known=array_values(array_diff(elevator_round_skill_pool(),$pool)); shuffle($known); $pool=array_merge($pool,$known);
    }
    $s['endless_step']=array('step_id'=>bin2hex(random_bytes(12)), 'depth'=>($s['curse_claimed']??0)+1,
        'choices'=>array_slice($pool,0,max(1,elevator_config('curse_skill_choices'))));
    $s['view']=array('kind'=>'endless_step');
}
function elevator_endless_step(&$e,&$players,$at) {
    if ($e['phase']!=='endless' || $e['floor']*$e['endless_dir']<=7) return;
    $depth=max(0,(int)floor((abs($e['floor'])-7)/max(1,elevator_config('curse_step_interval'))));
    foreach ($players as &$p) {
        $s=&$p['clbpara']['elevator'];
        if (!elevator_active($p) || empty($s['endless']) || !$s['in']) continue;
        if (!isset($s['curses'])) $s['curses']=array('903'=>0,'904'=>0,'906'=>0,'erosion'=>0);
        for ($step=(int)($s['curse_depth']??0);$step<$depth;$step++) {
            $available=array(); foreach (array('903'=>3,'904'=>3,'906'=>2) as $key=>$max) if ($s['curses'][$key]<$max) $available[]=$key;
            $key=$available?rr_pick($available):'erosion'; $s['curses'][$key]++;
            elevator_personal($p,elevator_curse_names()[$key].'升至'.$s['curses'][$key].'级。深入的代价已留下，也有一份力量等你选择。');
        }
        $s['curse_depth']=max($s['curse_depth']??0,$depth); elevator_endless_offer($p);
    }
    unset($p,$s);
}
function elevator_choose_endless_skill(&$p,$choice,$token,$e) {
    $s=&$p['clbpara']['elevator']; $offer=$s['endless_step']??array();
    if (empty($offer) || !hash_equals($offer['step_id'],$token) || !isset($offer['choices'][$choice])) throw new RuntimeException('这次命运选择已失效，请查看新的选项。');
    if (!elevator_grant_skill($p,$offer['choices'][$choice])) elevator_give($p,elevator_skill_book($offer['choices'][$choice]));
    $s['curse_claimed']=($s['curse_claimed']??0)+1; unset($s['endless_step']);
    if (($s['view']['kind']??'')==='endless_step') unset($s['view']);
    elevator_endless_offer($p);
}
function elevator_curse_hurt(&$a,&$b) {
    global $now;
    if (($a['final_damage']??0)<=0) return;
    $lvl=elevator_curse_level($b,'906');
    if ($lvl>=1 && !elevator_has_skill($b,803)) {
        // 只清理由蚀骨新施加的异常；已有的异常不被倒计时错误清除。
        if (strpos($b['inf']??'','u')===false) $b['clbpara']['elevator_bone_inf']=true;
        if (!empty($b['clbpara']['elevator_bone_inf'])) $b['clbpara']['elevator_bone_until']=$now+10;
        if (strpos($b['inf']??'','u')===false) $b['inf']=($b['inf']??'').'u';
    }
    if ($lvl>=2) { $b['mhp']=max(1,$b['mhp']-10); $b['hp']=min($b['hp'],$b['mhp']); }
}
function elevator_bone_tick(&$p,$from,$to,$safe) {
    $c=&$p['clbpara']; if (empty($c['elevator_bone_inf'])) return;
    if ($safe) $c['elevator_bone_until']+=max(0,$to-$from);
    if (($c['elevator_bone_until']??0)<=$to) {
        $p['inf']=str_replace('u','',$p['inf']); unset($c['elevator_bone_until'],$c['elevator_bone_inf']);
    }
}
function elevator_fate_panel($p) {
    $s=$p['clbpara']['elevator']; if (empty($s['curses'])) return '';
    $html='<section class="el-card"><h3>命运 · 力量的代价</h3><p>';
    foreach (elevator_curse_names() as $key=>$name) $html.=elevator_html($name).' '.($s['curses'][$key]?$s['curses'][$key].'级':'未觉醒').'　';
    $html.='</p><p class="el-muted">厄运：陷阱更易触发，随后降低发现、遭遇和命中。脆弱：受伤增加10%／20%／35%。蚀骨：受击异常10秒，二级每次受击生命上限−10。同化：每级预算消耗加快10%。车厢暂停蚀骨异常倒计时。</p>';
    if (!empty($s['endless_step'])) {
        $offer=$s['endless_step']; $html.='<h4>第'.$offer['depth'].'次馈赠 · 选择一项力量</h4><p>尚待领取 '.($s['curse_depth']-($s['curse_claimed']??0)).' 次，选项不会因刷新或查看其他物品而改变。</p><div class="el-goods">';
        foreach ($offer['choices'] as $i=>$sk) $html.='<div>'.elevator_skill_description($sk).'<br>'.elevator_button('接受这份力量','elevator_endless_skill',array('value'=>$i,'token'=>$offer['step_id'])).'</div>';
        $html.='</div>';
    }
    return $html.'</section>';
}
