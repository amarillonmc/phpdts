<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once __DIR__.'/another.talents.php';

function another_combat_setting($key, $default)
{
    $cfg = function_exists('another_config') ? another_config() : array();
    return isset($cfg['combat'][$key]) ? $cfg['combat'][$key] : $default;
}

function another_combat_para($value)
{
    if (is_array($value)) return $value;
    $decoded = json_decode((string)$value, true);
    return is_array($decoded) ? $decoded : array();
}

function another_combat_weapon($p)
{
    $para = another_combat_para(isset($p['weppara']) ? $p['weppara'] : array());
    $an = isset($para['another']) && is_array($para['another']) ? $para['another'] : array();
    $kind = isset($p['wepk']) && strlen($p['wepk']) > 1 ? strtoupper(substr($p['wepk'],1,1)) : 'N';
    if (empty($p['weps']) || empty($p['wep']) || !in_array($kind, array('G','K','C'), true)) {
        return array('name'=>'徒手','kind'=>'N','effect'=>15,'count'=>1,'para'=>array(),'uid'=>'','wear'=>0,'ammo'=>0,'range'=>0,'effects'=>array());
    }
    return array('name'=>$p['wep'],'kind'=>$kind,'effect'=>max(0,floatval($p['wepe'])),'count'=>intval($p['weps']),
        'para'=>$para,'uid'=>isset($an['uid']) ? (string)$an['uid'] : '',
        'wear'=>isset($an['wear']) ? max(0,min(100,floatval($an['wear']))) : 0,
        'ammo'=>isset($an['ammo']) ? max(0,intval($an['ammo'])) : (!empty($p['type']) ? 99999 : 0),
        'range'=>isset($an['range']) ? intval($an['range']) : ($kind === 'G' ? 3 : ($kind === 'C' ? 2 : 1)),
        'effects'=>isset($an['effects']) && is_array($an['effects']) ? $an['effects'] : array());
}

function another_clear_weapon(&$p)
{
    $p['wep'] = $p['wepk'] = $p['wepsk'] = '';
    $p['wepe'] = $p['weps'] = 0;
    $p['weppara'] = array();
    unset($p['clbpara']['another']['moon']);
}

function another_weapon_usable($weapon)
{
    if ($weapon['kind'] === 'N') return true;
    if ($weapon['count'] === 0 || $weapon['wear'] >= 100) return false;
    return $weapon['kind'] !== 'G' || $weapon['ammo'] > 0;
}

function another_pay_weapon(&$p, $weapon, $sun, &$messages)
{
    if ($sun) {
        another_clear_weapon($p);
        $messages[] = another_talent_name($p).'舍弃了'.$weapon['name'].'，发动日之愤怒。';
        return;
    }
    if ($weapon['kind'] === 'N') return;
    if ($weapon['kind'] === 'C') {
        if ($p['weps'] > 0) $p['weps']--;
        if ($p['weps'] === 0) another_clear_weapon($p);
        return;
    }
    $p['weppara'] = $weapon['para'];
    $an =& $p['weppara']['another'];
    if ($weapon['kind'] === 'G') $an['ammo'] = max(0, $weapon['ammo'] - 1);
    $an['wear'] = round(min(100, $weapon['wear'] + another_combat_setting('wear_per_hit',2) * (1 - another_talent_value($p,'I3') / 100)), 4);
    if ($an['wear'] >= 100) {
        $messages[] = another_talent_name($p).'的'.$weapon['name'].'已经损毁。';
        another_clear_weapon($p);
    }
}

function another_effective_stats($p, $players, $now)
{
    $bonus = another_talent($p,'C4') ? -5 : 0;
    $others = 0;
    foreach ($players as $mate) {
        if (empty($mate['hp']) || !empty($mate['type']) || intval($mate['pid']) === intval($p['pid'])) continue;
        $others++;
        if (another_talent_same_team($p, $mate)) $bonus += another_talent_value($mate,'C5');
    }
    if (another_talent($p,'C3')) $bonus += $others;
    if (isset($p['clbpara']['another']['ground_place']) && intval($p['clbpara']['another']['ground_place']) === intval($p['pls'])) $bonus += another_talent_value($p,'L2');
    $weak = another_status_value($p,'weaken',$now);
    $attackbonus = $bonus + another_status_value($p,'attack',$now) - $weak;
    $defensebonus = $bonus + another_status_value($p,'defense',$now) - $weak;
    $armor = 0;
    $nullify = false;
    foreach (array('arb','arh','ara','arf','art') as $slot) {
        if (empty($p[$slot.'s'])) continue;
        $armor += isset($p[$slot.'e']) ? max(0,floatval($p[$slot.'e'])) : 0;
        $para = another_combat_para(isset($p[$slot.'para']) ? $p[$slot.'para'] : array());
        if (!empty($para['another']['nullify'])) $nullify = true;
    }
    $armor *= 1 + another_talent_value($p,'E2') / 100;
    return array('attack'=>max(1,floatval($p['att']) * max(0.05,1 + $attackbonus / 100)),
        'base_defense'=>max(1,floatval($p['def'])), 'armor'=>$armor,
        'defense_multiplier'=>max(0.05,1 + $defensebonus / 100),
        'nullify'=>$nullify, 'attribute_bonus'=>$bonus);
}

function another_attack_damage($a, $d, $players, $now, $weapon = null, $sun = false, $variance = 1)
{
    if ($weapon === null) $weapon = another_combat_weapon($a);
    $aa = another_effective_stats($a,$players,$now);
    $dd = another_effective_stats($d,$players,$now);
    $enemyweapon = another_combat_weapon($d);
    $bonus = 0;
    $spec = array('K'=>'S1','G'=>'P1','C'=>'A1');
    if (isset($spec[$weapon['kind']])) $bonus += another_talent_value($a,$spec[$weapon['kind']]);
    if ($d['hp'] < another_max_hp($d) * 0.25) $bonus += another_talent_value($a,'S2');
    if ($a['hp'] < another_max_hp($a) * 0.25) $bonus += another_talent_value($a,'S3');
    if ($enemyweapon['kind'] !== 'K') $bonus += another_talent_value($a,'S4');
    if ($d['hp'] > another_max_hp($d) * 0.75) $bonus += another_talent_value($a,'P2');
    if ($a['hp'] < another_max_hp($a) * 0.15 && $weapon['kind'] === 'C' && $enemyweapon['kind'] !== 'C') $bonus += another_talent_value($a,'A5');
    $pierce = !$dd['nullify'] && isset($weapon['effects']['pierce']) ? max(0,min(100,$weapon['effects']['pierce'])) / 100 : 0;
    $defense = max(1,($dd['base_defense'] + $dd['armor'] * (1 - $pierce)) * $dd['defense_multiplier']);
    $growth = 1;
    $role = isset($a['clbpara']['another']['npc_role']) ? $a['clbpara']['another']['npc_role'] : '';
    if (empty($a['type']) || $role === 'mirror') $growth += max(0,intval($a['lvl']) - 1) * another_combat_setting('growth_per_level',0.025);
    $wear = $weapon['kind'] === 'C' ? 1 : 1 - 0.005 * $weapon['wear'];
    $damage = $weapon['effect'] * $growth * $wear * $aa['attack'] / $defense * $variance * (1 + $bonus / 100);
    if ($sun) $damage *= 2;
    if ($weapon['kind'] === 'G' || $weapon['kind'] === 'C') $damage *= 1 - another_talent_value($d,'E4') / 100;
    if (!$dd['nullify'] && !empty($weapon['effects']['bonus'])) $damage += max(0,$weapon['effects']['bonus']);
    return max(0,intval(floor($damage + 0.00000001)));
}

function another_mark_player(&$p, $target, $kind, $now, &$messages)
{
    if (empty($target['hp']) || !empty($target['type']) || !in_array($kind,array('moon','fire'),true)) return false;
    if (!another_talent($p,$kind === 'moon' ? 'PU' : 'CU')) return false;
    if ($kind === 'moon') {
        $w = another_combat_weapon($p);
        if (!$w['uid'] || $w['kind'] === 'N') { $messages[] = '月之寂静需要先装备一件武器。'; return false; }
        $p['clbpara']['another']['moon'] = array('target'=>intval($target['pid']),'weapon'=>$w['uid']);
        $messages[] = another_talent_name($p).'以月之寂静标记了'.another_talent_name($target).'。';
    } else {
        $p['clbpara']['another']['fire'] = intval($target['pid']);
        $messages[] = another_talent_name($p).'以火的热情标记了'.another_talent_name($target).'。';
    }
    return true;
}

function another_attack_hits($a, $d, $weapon, $priority)
{
    $moon = isset($a['clbpara']['another']['moon']) ? $a['clbpara']['another']['moon'] : array();
    $sure = $weapon['kind'] === 'C' || (!empty($moon) && $moon['target'] == $d['pid'] && $moon['weapon'] !== '' && $moon['weapon'] === $weapon['uid']);
    if ($sure && !empty($priority['sure_hit_before_evade'])) return true;
    $evade = another_evasion($d,'attack');
    if ($evade >= 100 || ($evade > 0 && mt_rand(1,10000) <= $evade * 100)) return false;
    if ($sure) return true;
    $hit = $weapon['kind'] === 'G' ? another_combat_setting('shot_hit',90) : ($weapon['kind'] === 'K' ? another_combat_setting('slash_hit',95) : 85);
    return mt_rand(1,10000) <= $hit * 100;
}

function another_sky_damage($a, $damage, $hp)
{
    return another_talent($a,'L4') && $damage < $hp && $damage >= $hp * 0.95 ? $hp : $damage;
}

function another_strike(&$a, &$d, &$players, &$room, $sun, $now, &$messages, $priority)
{
    if ($a['hp'] <= 0 || $d['hp'] <= 0) return false;
    $w = another_combat_weapon($a);
    if (!another_weapon_usable($w)) {
        $messages[] = another_talent_name($a).'的武器无法继续攻击'.($w['kind'] === 'G' && $w['ammo'] <= 0 ? '，需要补弹' : '').'。';
        return false;
    }
    // 这一击先保留被日消耗武器的身份，命中判定之后再真正移除。
    $hit = another_attack_hits($a,$d,$w,$priority);
    $variance = 1;
    if ($w['kind'] === 'K') {
        $range = another_combat_setting('slash_variance',array(0.95,1.05));
        $variance = mt_rand(intval($range[0] * 10000),intval($range[1] * 10000)) / 10000;
    }
    $raw = $hit ? another_attack_damage($a,$d,$players,$now,$w,$sun,$variance) : 0;
    another_pay_weapon($a,$w,$sun,$messages);
    $before = $d['hp'];
    if ($hit) {
        if (!empty($priority['sky_before_earth'])) {
            $raw = another_sky_damage($a,$raw,$before);
            $lost = another_damage($d,$raw,'attack',$now,$messages);
        } else {
            // 可配置的另一顺序：先土后天运；原本不到致死的伤害经天运补足不会再补判土。
            $lost = another_damage($d,$raw,'attack',$now,$messages);
            if (another_sky_damage($a,$raw,$before) > $raw && $d['hp'] > 0 && empty($d['clbpara']['another']['earth_left'])) {
                $lost += $d['hp']; $d['hp'] = 0;
            }
        }
        $messages[] = another_talent_name($a).'以'.$w['name'].'命中'.another_talent_name($d).'，造成'.$lost.'点伤害。';
        if ($d['hp'] <= 0) $d['clbpara']['another']['killer'] = intval($a['pid']);
        $xp = max(1,intval(round((intval($d['lvl']) - intval($a['lvl'])) / 3)));
        another_gain_exp($a,$xp,$messages);
    } else {
        $messages[] = another_talent_name($a).'以'.$w['name'].'攻击'.another_talent_name($d).'，未能命中。';
    }
    if (!isset($room['traces'])) $room['traces'] = array();
    $room['traces'][] = array('time'=>$now,'pls'=>intval($a['pls']),'kind'=>$w['kind'] === 'G' ? 'gunshot' : 'fight','pid'=>intval($a['pid']),'to'=>null);
    $room['traces'] = array_slice($room['traces'],-40);
    $dd = another_effective_stats($d,$players,$now);
    if ($hit && $d['hp'] > 0 && !$dd['nullify']) {
        foreach (array('poison','burn','slow') as $effect) {
            if (empty($w['effects'][$effect]) || mt_rand(1,10000) > $w['effects'][$effect] * 100) continue;
            another_apply_status($d,$effect,array('power'=>$effect === 'slow' ? 25 : another_combat_setting('poison_percent',5),
                'duration'=>$effect === 'slow' ? 30 : another_combat_setting('poison_duration',20),'source'=>$a['pid']),$players,$now,$messages);
        }
    }
    return true;
}

function another_mirror_marks(&$p, $target, $now, &$messages)
{
    if (empty($p['type']) || !isset($p['clbpara']['another']['npc_role']) || $p['clbpara']['another']['npc_role'] !== 'mirror') return;
    if (another_talent($p,'PU')) {
        $w = another_combat_weapon($p);
        $old = isset($p['clbpara']['another']['moon']) ? $p['clbpara']['another']['moon'] : array();
        if (!$old || $old['target'] != $target['pid'] || $old['weapon'] !== $w['uid']) another_mark_player($p,$target,'moon',$now,$messages);
    }
    if (another_talent($p,'CU') && (!isset($p['clbpara']['another']['fire']) || $p['clbpara']['another']['fire'] != $target['pid'])) {
        another_mark_player($p,$target,'fire',$now,$messages);
    }
}

function another_mirror_uses_sun($p, $target, $players, $now)
{
    if (empty($p['type']) || !isset($p['clbpara']['another']['npc_role']) || $p['clbpara']['another']['npc_role'] !== 'mirror' || !another_talent($p,'SU')) return false;
    $w = another_combat_weapon($p);
    if ($w['kind'] !== 'K' || $w['wear'] > 50 || !another_weapon_usable($w) || !empty($target['clbpara']['another']['earth_left'])) return false;
    $after = $p;
    $after['hp'] -= intval(floor($after['hp'] / 2));
    $lethal = another_attack_damage($after,$target,$players,$now,$w,true,1) >= $target['hp'];
    return $lethal || $p['hp'] < another_max_hp($p) * 0.4;
}

// 只改变传入的玩家和房间数组；身份、发现与距离校验由请求入口完成并在同一把锁内保存。
function another_battle(&$attacker, &$defender, &$players, &$room, $options, $now, &$messages)
{
    if ($attacker['hp'] <= 0 || $defender['hp'] <= 0 || $attacker['pid'] == $defender['pid'] || $attacker['pls'] != $defender['pls']) return false;
    $aw = another_combat_weapon($attacker);
    if (!another_weapon_usable($aw)) { $messages[] = '武器无法攻击，请先补弹或更换武器。'; return false; }
    $sun = !empty($options['sun']);
    if ($sun && (!another_talent($attacker,'SU') || $aw['kind'] !== 'K' || $aw['wear'] > 50)) {
        $messages[] = '日之愤怒需要磨损不超过50%的斩系武器。'; return false;
    }
    if ($sun) $attacker['hp'] -= intval(floor($attacker['hp'] / 2));
    another_mirror_marks($attacker,$defender,$now,$messages);
    another_mirror_marks($defender,$attacker,$now,$messages);
    $priority = another_combat_setting('talent_priority',array('sure_hit_before_evade'=>true,'sky_before_earth'=>true));
    if (isset($options['talent_priority']) && is_array($options['talent_priority'])) $priority = array_merge($priority,$options['talent_priority']);
    $dw = another_combat_weapon($defender);
    // 射程决定先手；进入同地点遭遇后，短程一方若存活仍有正常反击。
    $defenderfirst = another_weapon_usable($dw) && $dw['range'] > $aw['range'];
    if ($defenderfirst) $messages[] = another_talent_name($defender).'凭借射程优势抢先出手。';
    $order = $defenderfirst ? array('defender','attacker') : array('attacker','defender');
    $sunpaid = false;
    foreach ($order as $who) {
        if ($attacker['hp'] <= 0 || $defender['hp'] <= 0) break;
        if ($who === 'attacker') { $a =& $attacker; $d =& $defender; $usesun = $sun; }
        else { $a =& $defender; $d =& $attacker; $usesun = false; }
        if (!$usesun && another_mirror_uses_sun($a,$d,$players,$now)) {
            $usesun = true;
            $a['hp'] -= intval(floor($a['hp'] / 2));
        }
        $flurry = another_talent($a,'A3') && $a['hp'] >= another_max_hp($a) * 0.85;
        $didstrike = another_strike($a,$d,$players,$room,$usesun,$now,$messages,$priority);
        if ($didstrike && $usesun && $who === 'attacker') $sunpaid = true;
        if ($didstrike && $a['hp'] > 0 && $d['hp'] > 0 && $flurry && !$usesun && mt_rand(1,10000) <= another_combat_setting('flurry_chance',20) * 100) {
            $messages[] = another_talent_name($a).'发动踏雪无痕，追加一次攻击。';
            another_strike($a,$d,$players,$room,false,$now,$messages,$priority);
        }
        if ($didstrike && $who === 'defender' && isset($a['clbpara']['another']['npc_role']) && $a['clbpara']['another']['npc_role'] === 'poisoner' && $d['hp'] > 0) {
            $stats = another_effective_stats($d,$players,$now);
            if (!$stats['nullify']) another_apply_status($d,'poison',array('power'=>another_combat_setting('poison_percent',5),
                'duration'=>another_combat_setting('poison_duration',20),'source'=>$a['pid']),$players,$now,$messages);
        }
        unset($a,$d);
    }
    if ($sun && !$sunpaid) {
        another_clear_weapon($attacker);
        $messages[] = another_talent_name($attacker).'没能挥出日之愤怒；作为代价的武器仍被消耗。';
    }
    return true;
}

// 每个死亡角色仅首次结算：基础奖励归击杀者，每枚火标记独立按受益身份去重。
function another_combat_rewards(&$killer, &$dead, &$players, &$room, $now, &$messages)
{
    if ($dead['hp'] > 0 || !empty($dead['clbpara']['another']['death_awarded'])) return false;
    $dead['clbpara']['another']['death_awarded'] = true;
    $dead['clbpara']['another']['killer'] = intval($killer['pid']);
    $dead['endtime'] = $now;
    if (intval($dead['state']) !== 11) $dead['state'] = 20;
    $role = !empty($dead['type']) && isset($dead['clbpara']['another']['npc_role']) ? $dead['clbpara']['another']['npc_role'] : 'player';
    $income = array('player'=>array(120,30),'grunt'=>array(40,5),'elite'=>array(120,25),'hunter'=>array(200,80),
        'poisoner'=>array(200,80),'engineer'=>array(200,80),'mirror'=>array(180,60));
    $cfg = function_exists('another_config') ? another_config() : array();
    if (isset($cfg['economy']['rewards'])) foreach ($cfg['economy']['rewards'] as $key => $entry) {
        $income[$key] = array($entry['money'],$entry['rep']);
    }
    $reward = isset($income[$role]) ? $income[$role] : $income['grunt'];
    if (empty($killer['type']) && $killer['pid'] != $dead['pid']) {
        $killer['money'] += $reward[0];
        $rep = another_add_rep($killer,$reward[1]);
        $killer['killnum'] = isset($killer['killnum']) ? $killer['killnum'] + 1 : 1;
        $messages[] = another_talent_name($killer).'击败'.another_talent_name($dead).'，获得'.$reward[0].'金钱与'.$rep.' REP。';
        another_gain_exp($killer,isset($cfg['kill_exp']) ? intval($cfg['kill_exp']) : 8,$messages);
    }
    foreach ($players as &$marker) {
        if (empty($marker['clbpara']['another']['fire']) || $marker['clbpara']['another']['fire'] != $dead['pid']) continue;
        unset($marker['clbpara']['another']['fire']);
        foreach ($players as &$beneficiary) {
            if (!empty($beneficiary['type'])) continue;
            if ($beneficiary['pid'] != $marker['pid'] && $beneficiary['pid'] != $killer['pid'] && !another_talent_same_team($marker,$beneficiary)) continue;
            $rep = another_add_rep($beneficiary,another_talent_value($marker,'CU'));
            $notice = another_talent_name($marker).'的火的热情使'.another_talent_name($beneficiary).'获得'.$rep.' REP。';
            $messages[] = $notice;
            if ((int)$beneficiary['pid'] !== (int)$killer['pid']) {
                $beneficiary['clbpara']['another']['messages'][] = array('time'=>$now,'text'=>$notice);
                $beneficiary['clbpara']['another']['messages'] = array_slice($beneficiary['clbpara']['another']['messages'],-25);
            }
        }
        unset($beneficiary);
    }
    unset($marker);
    return true;
}
