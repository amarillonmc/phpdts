<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_config($key = '') {
    $cfg = array('budget_base'=>150, 'budget_min'=>100, 'door_open'=>8, 'door_close'=>5,
        'travel_unit'=>3, 'vote_window'=>120, 'limit_finite'=>3600, 'limit_endless'=>5400,
        'join_window'=>600, 'protection'=>60, 'presence'=>12, 'announce'=>20, 'event_limit'=>60,
        'max_players'=>8, 'recipe_count'=>10, 'erosion'=>8, 'tap_turns'=>6);
    foreach ($cfg as $name => $value) if (isset($GLOBALS['elevator_'.$name])) $cfg[$name] = max(1,(int)$GLOBALS['elevator_'.$name]);
    return $key === '' ? $cfg : $cfg[$key];
}

// 车厢不占用任何楼层地址。正负楼层交错映射，地图模板编号只用于取素材。
function elevator_pls($floor, $index = 0) { return 10000 + (abs((int)$floor)*2 + ($floor<0?1:0))*16 + (int)$index; }
function elevator_floor_label($floor) {
    if ($floor === -8) return '−8 · 灰色会客厅';
    return ($floor>0?'+':'−').abs($floor).' 层'.($floor===7?' · 阁楼':($floor===-7?' · 钻探尽头':''));
}
function elevator_budget($floor) { return max(elevator_config('budget_min'),elevator_config('budget_base')-max(0,abs($floor)-1)*8); }
function elevator_rank($floor) {
    // 阁楼之上增加谜题而不继续抬高战斗数值；钻探20档之后线性增长。
    return (int)ceil(min($floor>7?7:abs($floor),100000)*1.5);
}
function elevator_cipher($text) {
    return strtr(strtolower($text), array_combine(
        preg_split('//u','abcdefghijklmnopqrstuvwxyzéèœ',-1,PREG_SPLIT_NO_EMPTY),
        preg_split('//u','ΑΗΛРΨΩΜДВΞЁЙПХЖЫКТЦЩИЛОФΔΘЮЪЭ',-1,PREG_SPLIT_NO_EMPTY)));
}
function elevator_questions() {
    return array(
        array('Where did these children come from?', array('Creatures born in this dream.','Words left behind, lamps lit with care.','Relics of an old laboratory.','Discarded pieces of data.'), 1,
            'Every child was once a sentence. Someone left it here, and it learned to shine.'),
        array('What kind of person left the children here?', array('Someone afraid of the dark.','Someone who wanted to open the box.','A stranger who passed by.','A dream dreamed by the box.'), 1,
            'He never finished saying everything. But he wrote down the words that mattered.'),
        array('What was the silver box to that person?', array('A prison.','A box that gives back what you put inside.','A bottomless well.','A mirror with no reflection.'), 1,
            'What he put inside was the gentlest part of himself.')
    );
}
