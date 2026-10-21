<?php
if (!defined('IN_GAME')) exit('Access Denied');

// 旧BOT协议不能绕过本模式的地图、行动次数和天赋战斗。
function ruleset_legacy_bot_allowed_hook() { return false; }

function ruleset_end_achievement_hook($owner,$winmode,$player) {
    // 异常湾城没有旧房间的合成、NPC和解禁步骤，不判断那些个人挑战。
    check_end_achievement_rev($owner,$winmode);
    return true;
}

function ruleset_format_news_hook($news,$time,$a,$b,$c,$d,$e) {
    $name=htmlspecialchars((string)$a,ENT_QUOTES,'UTF-8');
    if($news==='end1') $text='AN:OTHER 已结束，本局无人脱出。';
    elseif($news==='end2') $text=$name.'所在队伍达成幸存脱出，离开了异常湾城。';
    elseif($news==='end3') $text=$name.'所在队伍触发异常终止，一同离开了异常湾城。';
    elseif($news==='newgame' || $news==='newroomgame') $text='第'.(int)$a.'局 AN:OTHER 开始，湾城异常区域已载入。';
    elseif($news==='gameover' || $news==='roomgameover') $text='第'.(int)$a.'局 AN:OTHER 已结束。';
    else return null;
    return '<li>'.date('H时i分s秒',(int)$time).'，<span class="yellow">'.$text.'</span><br>'."\n";
}

function ruleset_set_credits_hook() {
    global $db, $gtablepre, $gamevars, $winner, $winmode;
    if (!empty($gamevars['another']['accounts_settled'])) return true;
    $players = another_read_players();
    $accounts = array(); $victors = array();
    foreach ($gamevars['another']['result']['winners'] ?? array() as $pid) {
        if (isset($players[$pid])) $victors[another_owner($players[$pid])] = true;
    }
    foreach ($players as $p) {
        $owner = another_owner($p);
        if ((int)$p['type'] !== 0 || $owner === '') continue;
        if (!isset($accounts[$owner])) {
            $accounts[$owner] = $p;
            $accounts[$owner]['name'] = $owner;
            continue;
        }
        // 每个账户只算一局；两角色的击杀和剩余金钱合计，等级与熟练取最高。
        foreach (array('killnum','money') as $key) $accounts[$owner][$key] += $p[$key];
        foreach (array('hp','lvl','wp','wk','wc','wg','wd','wf') as $key) {
            $accounts[$owner][$key] = max($accounts[$owner][$key], $p[$key]);
        }
    }
    $updates = array();
    foreach ($accounts as $owner => $p) {
        $won = isset($victors[$owner]);
        // 主胜者已由gameover检查；其余队友沿用原生团队结局的基础成就。
        if ($won && $owner !== $winner) check_end_achievement_rev($owner, $winmode);
        $result = $db->query('SELECT * FROM '.$gtablepre.'users WHERE username='.another_sql_text($owner));
        $user = $db->fetch_array($result);
        if (!$user) continue;
        $updates[$owner] = array('username'=>$owner,
            'credits'=>(int)$user['credits'] + get_credit_up($p, $won ? $owner : '', $winmode),
            'credits2'=>(int)$user['credits2'] + 10,
            'validgames'=>(int)$user['validgames'] + 1,
            'wingames'=>(int)$user['wingames'] + ($won ? 1 : 0));
    }
    if ($updates) $db->multi_update($gtablepre.'users', $updates, 'username');
    $gamevars['another']['accounts_settled'] = true;
    return true;
}
