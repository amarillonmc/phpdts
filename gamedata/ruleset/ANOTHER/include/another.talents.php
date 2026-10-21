<?php
if (!defined('IN_GAME')) exit('Access Denied');

// AN:OTHER 的天赋只使用本模式数据，不挂载社团或称号技能。
function another_talent_definitions()
{
    return array(
        'S1' => array('tree'=>'S','name'=>'斩系专精','max'=>3,'values'=>array(5,8,14),'text'=>'斩系直接伤害提高5%／8%／14%。'),
        'S2' => array('tree'=>'S','name'=>'恐惧盛宴','max'=>1,'values'=>array(15),'text'=>'对生命低于25%的目标伤害提高15%。'),
        'S3' => array('tree'=>'S','name'=>'愤怒重击','max'=>3,'values'=>array(5,7,9),'text'=>'自身生命低于25%时伤害提高5%／7%／9%。'),
        'S4' => array('tree'=>'S','name'=>'铮铮铁骨','max'=>1,'values'=>array(3),'text'=>'对非斩系目标伤害提高3%。'),
        'S5' => array('tree'=>'S','name'=>'快速消化','max'=>1,'values'=>array(20),'text'=>'消化时间降低20%，正向回复降低5%。'),
        'SU' => array('tree'=>'S','name'=>'日之愤怒','max'=>1,'values'=>array(100),'ultimate'=>true,'text'=>'消耗磨损不超过50%的斩武器与当前一半生命，本击伤害翻倍。'),
        'P1' => array('tree'=>'P','name'=>'射系专精','max'=>3,'values'=>array(4,7,14),'text'=>'射系直接伤害提高4%／7%／14%。'),
        'P2' => array('tree'=>'P','name'=>'先手必胜','max'=>1,'values'=>array(15),'text'=>'对生命高于75%的目标伤害提高15%，反击也有效。'),
        'P3' => array('tree'=>'P','name'=>'威胁探知','max'=>3,'values'=>array(5,10,15),'text'=>'提前发现陷阱的概率相对提高5%／10%／15%。'),
        'P4' => array('tree'=>'P','name'=>'感官强化','max'=>2,'values'=>array(6,12),'text'=>'发现其他玩家的概率相对提高6%／12%。'),
        'PU' => array('tree'=>'P','name'=>'月之寂静','max'=>1,'values'=>array(100),'ultimate'=>true,'text'=>'标记一名玩家。保持同一件武器时，对其攻击必中。'),
        'E1' => array('tree'=>'E','name'=>'勇者无敌','max'=>5,'values'=>array(10,20,30,40,50),'text'=>'最大生命提高10%／20%／30%／40%／50%。'),
        'E2' => array('tree'=>'E','name'=>'防具改造','max'=>2,'values'=>array(5,10),'text'=>'防具的防御效果提高5%／10%。'),
        'E3' => array('tree'=>'E','name'=>'毒素耐受','max'=>1,'values'=>array(5),'text'=>'中毒伤害降低5%。'),
        'E4' => array('tree'=>'E','name'=>'冲击耐受','max'=>1,'values'=>array(5),'text'=>'受到的射／投直接伤害降低5%。'),
        'EU' => array('tree'=>'E','name'=>'土的坚韧','max'=>1,'values'=>array(1),'ultimate'=>true,'text'=>'生命大于1时遭致命伤留下1生命，随后3次自身有效行动内免死，再冷却6次行动。禁区不受保护。'),
        'C1' => array('tree'=>'C','name'=>'自我包装','max'=>3,'values'=>array(5,7,10),'text'=>'新获得的REP提高5%／7%／10%。'),
        'C2' => array('tree'=>'C','name'=>'砍价狂人','max'=>1,'values'=>array(10),'text'=>'金钱商店价格降低10%，不影响REP价格。'),
        'C3' => array('tree'=>'C','name'=>'大众偶像','max'=>1,'values'=>array(1),'text'=>'每名其他存活玩家角色使自身攻防提高1%。'),
        'C4' => array('tree'=>'C','name'=>'权术游戏','max'=>1,'values'=>array(50),'text'=>'起始金钱变为150%，攻防属性修正降低5%。'),
        'C5' => array('tree'=>'C','name'=>'团队玩家','max'=>3,'values'=>array(2,4,6),'text'=>'其他存活队友的攻防提高2%／4%／6%；不同来源相加，不作用本人。'),
        'CU' => array('tree'=>'C','name'=>'火的热情','max'=>1,'values'=>array(50),'ultimate'=>true,'text'=>'标记玩家死亡时，自己、队友与击杀者各得50REP。同一枚内身份去重，不同标记各自结算。'),
        'I1' => array('tree'=>'I','name'=>'冷静分析','max'=>3,'values'=>array(10,20,30),'text'=>'强化失败概率相对降低10%／20%／30%。'),
        'I2' => array('tree'=>'I','name'=>'行踪消灭','max'=>2,'values'=>array(5,10),'text'=>'本人及所设陷阱被发现概率相对降低5%／10%。'),
        'I3' => array('tree'=>'I','name'=>'保养整备','max'=>3,'values'=>array(5,10,15),'text'=>'武器磨损增量降低5%／10%／15%。'),
        'I4' => array('tree'=>'I','name'=>'急救专精','max'=>1,'values'=>array(10),'text'=>'休息和静养的生命、体力回复提高10%。'),
        'IU' => array('tree'=>'I','name'=>'金之闪耀','max'=>1,'values'=>array(100),'ultimate'=>true,'text'=>'所设陷阱触发后不可回避，仍可提前发现和拆除。'),
        'A1' => array('tree'=>'A','name'=>'投系专精','max'=>3,'values'=>array(8,12,16),'text'=>'投系直接伤害提高8%／12%／16%。'),
        'A2' => array('tree'=>'A','name'=>'凌波微步','max'=>3,'values'=>array(15,25,35),'text'=>'最大体力提高15%／25%／35%。'),
        'A3' => array('tree'=>'A','name'=>'踏雪无痕','max'=>1,'values'=>array(20),'text'=>'本轮首次出手时生命不低于85%，有20%概率追加一击；反击也能触发。'),
        'A4' => array('tree'=>'A','name'=>'化吉猫步','max'=>1,'values'=>array(50),'text'=>'生命低于15%时攻击回避增加50个百分点，可与水叠至100%。'),
        'A5' => array('tree'=>'A','name'=>'孤注一掷','max'=>1,'values'=>array(80),'text'=>'生命低于15%时，投系攻击非投系目标的伤害提高80%。'),
        'AU' => array('tree'=>'A','name'=>'水之流动','max'=>1,'values'=>array(50),'ultimate'=>true,'text'=>'攻击和陷阱回避增加50个百分点，全部发现概率相对提高30%。'),
        'L1' => array('tree'=>'L','name'=>'财运','max'=>2,'values'=>array(5,10),'text'=>'每次有效探索有5%／10%概率捡到20金钱。'),
        'L2' => array('tree'=>'L','name'=>'地运','max'=>2,'values'=>array(1,3),'text'=>'在本地点探索后攻防提高1%／3%，不累层，离开清除。'),
        'L3' => array('tree'=>'L','name'=>'人运','max'=>1,'values'=>array(1),'text'=>'获得的临时状态同步给其他存活队友；分享所得状态不再转发。'),
        'L4' => array('tree'=>'L','name'=>'天运','max'=>1,'values'=>array(95),'text'=>'最终直接伤害达到目标当前生命95%时补足至致死，再判土的坚韧。'),
        'L5' => array('tree'=>'L','name'=>'业运','max'=>1,'values'=>array(5),'text'=>'扣费前REP低于50时，本次气球被发现概率相对降低5%。'),
        'L6' => array('tree'=>'L','name'=>'卦运','max'=>1,'values'=>array(5),'text'=>'吃有毒补给时有5%概率不会中毒。'),
        'L7' => array('tree'=>'L','name'=>'星运','max'=>1,'values'=>array(5),'text'=>'触发野生陷阱时有5%概率不广播位置，不免伤和状态。'),
        'LU' => array('tree'=>'L','name'=>'木之多彩','max'=>1,'values'=>array(50),'ultimate'=>true,'text'=>'持卫星电话可尝试黑入，50%成功并全队脱出；每个持有角色每局一次。')
    );
}

function another_talent_points($talents)
{
    return is_array($talents) ? array_sum($talents) : 0;
}

function another_talent_name($p)
{
    return isset($p['clbpara']['another']['display_name']) ? $p['clbpara']['another']['display_name'] : $p['name'];
}

function another_validate_talents($input, &$error)
{
    $error = '';
    if (!is_array($input)) { $error = '天赋配点格式不正确。'; return false; }
    $defs = another_talent_definitions();
    $out = array();
    $trees = array();
    foreach ($input as $id => $level) {
        if (!isset($defs[$id]) || (!is_int($level) && !(is_string($level) && preg_match('/^[0-9]+$/D', $level)))) {
            $error = '天赋或等级不正确。'; return false;
        }
        $level = intval($level);
        if ($level < 0 || $level > $defs[$id]['max']) { $error = $defs[$id]['name'].'的等级超出范围。'; return false; }
        if (!$level) continue;
        $out[$id] = $level;
        if (empty($defs[$id]['ultimate'])) {
            $tree = $defs[$id]['tree'];
            $trees[$tree] = (isset($trees[$tree]) ? $trees[$tree] : 0) + $level;
        }
    }
    if (another_talent_points($out) > 35) { $error = '每个角色最多分配35点天赋。'; return false; }
    foreach ($out as $id => $level) {
        if (!empty($defs[$id]['ultimate']) && (!isset($trees[$defs[$id]['tree']]) || $trees[$defs[$id]['tree']] !== 9)) {
            $error = '学习'.$defs[$id]['name'].'前，须先投入该树全部9点普通天赋。'; return false;
        }
    }
    return $out;
}

function another_talent($p, $id)
{
    $defs = another_talent_definitions();
    if (!isset($defs[$id])) return 0;
    $cp = isset($p['clbpara']) ? $p['clbpara'] : array();
    if (!is_array($cp)) $cp = json_decode($cp, true);
    $n = isset($cp['another']['talents'][$id]) ? intval($cp['another']['talents'][$id]) : 0;
    return max(0, min($defs[$id]['max'], $n));
}

function another_talent_value($p, $id)
{
    $n = another_talent($p, $id);
    if (!$n) return 0;
    $defs = another_talent_definitions();
    $cfg = function_exists('another_config') ? another_config() : array();
    $values = isset($cfg['talents'][$id]) && is_array($cfg['talents'][$id]) ? $cfg['talents'][$id] : $defs[$id]['values'];
    return isset($values[$n - 1]) ? $values[$n - 1] : $defs[$id]['values'][$n - 1];
}

function another_max_hp($p)
{
    return max(1, intval(floor((isset($p['mhp']) ? $p['mhp'] : 400) * (1 + another_talent_value($p, 'E1') / 100))));
}

function another_max_sp($p)
{
    return max(1, intval(floor((isset($p['msp']) ? $p['msp'] : 400) * (1 + another_talent_value($p, 'A2') / 100))));
}

function another_talent_same_team($a, $b)
{
    return !empty($a['teamID']) && isset($b['teamID']) && (string)$a['teamID'] === (string)$b['teamID'];
}

function another_add_rep(&$p, $amount)
{
    $amount = round(max(0, floatval($amount)) * (1 + another_talent_value($p, 'C1') / 100), 2);
    if (!isset($p['clbpara']['another']['rep'])) $p['clbpara']['another']['rep'] = 0;
    $p['clbpara']['another']['rep'] = round($p['clbpara']['another']['rep'] + $amount, 2);
    return $amount;
}

function another_discovery_chance($p, $base, $kind, $target = array())
{
    $bonus = another_talent($p, 'AU') ? 30 : 0;
    if ($kind === 'player') $bonus += another_talent_value($p, 'P4');
    if ($kind === 'trap') $bonus += another_talent_value($p, 'P3');
    $hidden = in_array($kind, array('player','npc','trap'), true) ? another_talent_value($target, 'I2') : 0;
    return max(0, min(100, $base * (1 + $bonus / 100) * (1 - $hidden / 100)));
}

function another_status_value($p, $kind, $now)
{
    $st = isset($p['clbpara']['another']['statuses'][$kind]) ? $p['clbpara']['another']['statuses'][$kind] : array();
    return !empty($st) && $st['until'] > $now ? floatval($st['power']) : 0;
}

function another_apply_status(&$p, $kind, $spec, &$players, $now, &$messages)
{
    $names = array('poison'=>'中毒','burn'=>'灼伤','slow'=>'迟缓','weaken'=>'削弱','attack'=>'攻击强化','defense'=>'防御强化');
    if (!isset($names[$kind]) || empty($p['hp'])) return false;
    $duration = max(1, intval(isset($spec['duration']) ? $spec['duration'] : 20));
    $incoming = array('until'=>$now + $duration, 'power'=>max(0, floatval(isset($spec['power']) ? $spec['power'] : 5)),
        'source'=>intval(isset($spec['source']) ? $spec['source'] : 0));
    $cfg = function_exists('another_config') ? another_config() : array();
    $interval = max(1, intval(isset($cfg['combat']['poison_interval']) ? $cfg['combat']['poison_interval'] : 5));
    if ($kind === 'poison' || $kind === 'burn') $incoming['next'] = $now + $interval;
    $old = isset($p['clbpara']['another']['statuses'][$kind]) ? $p['clbpara']['another']['statuses'][$kind] : array();
    if (!empty($old) && $old['until'] > $now) {
        $incoming['power'] = max($incoming['power'], $old['power']);
        $incoming['until'] = max($incoming['until'], $old['until']);
        if (isset($old['next'])) $incoming['next'] = $old['next'];
    }
    $p['clbpara']['another']['statuses'][$kind] = $incoming;
    $messages[] = another_talent_name($p).'获得了'.$names[$kind].'状态。';
    if (another_talent($p, 'L3')) {
        foreach ($players as &$mate) {
            if (empty($mate['hp']) || intval($mate['pid']) === intval($p['pid']) || !another_talent_same_team($p, $mate)) continue;
            $copy = $incoming;
            $existing = isset($mate['clbpara']['another']['statuses'][$kind]) ? $mate['clbpara']['another']['statuses'][$kind] : array();
            if (!empty($existing) && $existing['until'] > $now) {
                $copy['power'] = max($copy['power'], $existing['power']);
                $copy['until'] = max($copy['until'], $existing['until']);
                if (isset($existing['next'])) $copy['next'] = min($copy['next'], $existing['next']);
            }
            $mate['clbpara']['another']['statuses'][$kind] = $copy;
            $messages[] = '人运将'.$names[$kind].'传给了'.another_talent_name($mate).'。';
        }
        unset($mate);
    }
    return true;
}

// 返回实际扣除生命。受击不推进土的保护；只有主动动作完成后才计数。
function another_damage(&$p, $amount, $source, $now, &$messages)
{
    $before = max(0, intval($p['hp']));
    if (!$before) return 0;
    $amount = max(0, floatval($amount));
    if ($source === 'poison') $amount *= 1 - another_talent_value($p, 'E3') / 100;
    $amount = intval(floor($amount));
    $an =& $p['clbpara']['another'];
    $remaining = isset($an['earth_left']) ? intval($an['earth_left']) : 0;
    $cooldown = isset($an['earth_cd']) ? intval($an['earth_cd']) : 0;
    if ($source !== 'zone' && another_talent($p, 'EU') && $amount >= $before && ($remaining > 0 || ($cooldown <= 0 && $before > 1))) {
        if ($remaining <= 0) {
            $cfg = function_exists('another_config') ? another_config() : array();
            $an['earth_left'] = max(1, intval(isset($cfg['combat']['earth_actions']) ? $cfg['combat']['earth_actions'] : 3));
            $an['earth_generation'] = isset($an['earth_generation']) ? intval($an['earth_generation']) + 1 : 1;
            $messages[] = another_talent_name($p).'的土的坚韧被触发，生命保留为1。';
        } else {
            $messages[] = '土的坚韧保护着'.another_talent_name($p).'。';
        }
        $p['hp'] = 1;
    } else {
        $p['hp'] = max(0, $before - $amount);
    }
    return $before - $p['hp'];
}

function another_action_guard($p)
{
    $an = isset($p['clbpara']['another']) ? $p['clbpara']['another'] : array();
    return array('left'=>isset($an['earth_left']) ? intval($an['earth_left']) : 0,
        'cd'=>isset($an['earth_cd']) ? intval($an['earth_cd']) : 0,
        'generation'=>isset($an['earth_generation']) ? intval($an['earth_generation']) : 0);
}

function another_finish_action(&$p, $before)
{
    $an =& $p['clbpara']['another'];
    $generation = isset($an['earth_generation']) ? intval($an['earth_generation']) : 0;
    if ($generation !== $before['generation']) return; // 触发所在整次结算免费。
    if ($before['left'] > 0) {
        $an['earth_left'] = max(0, $before['left'] - 1);
        if ($an['earth_left'] === 0) {
            $cfg = function_exists('another_config') ? another_config() : array();
            $an['earth_cd'] = max(0, intval(isset($cfg['combat']['earth_cooldown']) ? $cfg['combat']['earth_cooldown'] : 6));
        }
    } elseif ($before['cd'] > 0) {
        $an['earth_cd'] = max(0, $before['cd'] - 1);
    }
}

function another_tick_statuses(&$p, $now, &$messages)
{
    if (empty($p['clbpara']['another']['statuses'])) return;
    $cfg = function_exists('another_config') ? another_config() : array();
    $interval = max(1, intval(isset($cfg['combat']['poison_interval']) ? $cfg['combat']['poison_interval'] : 5));
    foreach ($p['clbpara']['another']['statuses'] as $kind => &$st) {
        if (($kind === 'poison' || $kind === 'burn') && isset($st['next'])) {
            while ($st['next'] <= $now && $st['next'] <= $st['until'] && $p['hp'] > 0) {
                $lost = another_damage($p, another_max_hp($p) * $st['power'] / 100, $kind, $st['next'], $messages);
                $messages[] = another_talent_name($p).'因'.($kind === 'poison' ? '中毒' : '灼伤').'失去'.$lost.'生命。';
                if ($p['hp'] <= 0) $p['clbpara']['another']['killer'] = intval($st['source']);
                $st['next'] += $interval;
            }
        }
    }
    unset($st);
    foreach ($p['clbpara']['another']['statuses'] as $kind => $st) {
        if ($st['until'] <= $now) unset($p['clbpara']['another']['statuses'][$kind]);
    }
}

function another_evasion($p, $kind)
{
    $rate = another_talent($p, 'AU') ? another_talent_value($p, 'AU') : 0;
    if ($kind === 'attack' && $p['hp'] < another_max_hp($p) * 0.15) $rate += another_talent_value($p, 'A4');
    return min(100, max(0, $rate));
}

function another_gain_exp(&$p, $amount, &$messages)
{
    global $baseexp;
    if (!empty($p['type']) || $amount <= 0) return;
    $cfg = function_exists('another_config') ? another_config() : array();
    $base = max(1, intval(isset($cfg['base_exp']) ? $cfg['base_exp'] : (isset($baseexp) ? $baseexp : 9)));
    $p['exp'] += intval($amount);
    $level = max(0, intval($p['lvl']));
    $threshold = (2 * $level + 1) * $base;
    if ($p['exp'] < $threshold || $level >= 255) {
        $p['upexp'] = $threshold;
        if ($level >= 255) $p['exp'] = $threshold;
        return;
    }
    // 与 lvlup_rev 普通角色相同的随机成长和累计经验公式；本模式不产生旧技能点。
    $levels = min(255 - $level, 1 + intval(floor(($p['exp'] - $threshold) / $base / 2)));
    $oldhp = another_max_hp($p);
    $oldsp = another_max_sp($p);
    $dhp = $dsp = $datt = $ddef = $spref = 0;
    for ($i = 0; $i < $levels; $i++) {
        $dhp += rand(8,10);
        $dsp += rand(4,6);
        $datt += rand(2,4);
        $ddef += rand(3,5);
        $spref += round($oldsp * 0.1);
    }
    $p['lvl'] += $levels;
    $p['mhp'] += $dhp;
    $p['msp'] += $dsp;
    $p['att'] += $datt;
    $p['def'] += $ddef;
    if ($p['hp'] > 0) $p['hp'] = min(another_max_hp($p), $p['hp'] + another_max_hp($p) - $oldhp);
    $p['sp'] = min(another_max_sp($p), $p['sp'] + another_max_sp($p) - $oldsp + $spref);
    $p['skillpoint'] = 0;
    $p['upexp'] = (2 * $p['lvl'] + 1) * $base;
    if ($p['lvl'] >= 255) $p['exp'] = $p['upexp'];
    $messages[] = another_talent_name($p).'提升'.$levels.'级：生命上限+'.$dhp.'，体力上限+'.$dsp.'，攻击+'.$datt.'，防御+'.$ddef.'。';
}
