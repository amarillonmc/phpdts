<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

function mobile_build_map_nodes($vars) {
    $pls = isset($vars['pls']) ? (int)$vars['pls'] : 0;
    $pgroup = isset($vars['pgroup']) ? (int)$vars['pgroup'] : 0;
    $plsinfo = isset($vars['plsinfo']) && is_array($vars['plsinfo']) ? $vars['plsinfo'] : array();
    $hplsinfo = isset($vars['hplsinfo']) && is_array($vars['hplsinfo']) ? $vars['hplsinfo'] : array();
    $xyinfo = isset($vars['xyinfo']) && is_array($vars['xyinfo']) ? $vars['xyinfo'] : array();
    $arealist = isset($vars['arealist']) && is_array($vars['arealist']) ? $vars['arealist'] : array();
    $areanum = isset($vars['areanum']) ? (int)$vars['areanum'] : -1;
    $areaadd = isset($vars['areaadd']) ? (int)$vars['areaadd'] : 0;
    $hack = !empty($vars['hack']);

    $is_hidden_group = (!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]) && is_array($hplsinfo[$pgroup]));
    $source = $is_hidden_group ? $hplsinfo[$pgroup] : $plsinfo;

    $danger_set = (!$is_hidden_group && !empty($arealist) && $areanum >= 0)
        ? array_slice($arealist, 0, $areanum + 1)
        : array();
    $warn_set = (!$is_hidden_group && !empty($arealist) && $areanum >= 0 && $areaadd > 0)
        ? array_slice($arealist, $areanum + 1, $areaadd)
        : array();

    $nodes = array();
    foreach ($source as $key => $name) {
        $id = (int)$key;
        $is_danger = in_array($id, $danger_set);
        $is_warn = !$is_danger && in_array($id, $warn_set);
        $coord = $is_hidden_group
            ? (isset($xyinfo['hidden']) ? (string)$xyinfo['hidden'] : '??')
            : (isset($xyinfo[$id]) ? (string)$xyinfo[$id] : '#' . $id);
        $nodes[] = array(
            'id' => $id,
            'name' => (string)$name,
            'coord' => $coord,
            'current' => ($id === $pls),
            'danger' => $is_danger,
            'warn' => $is_warn,
            'canMove' => ($id !== $pls) && ($is_hidden_group || !$is_danger || $hack),
        );
    }

    return array(
        'isHiddenGroup' => $is_hidden_group,
        'dangerSet' => array_values($danger_set),
        'warnSet' => array_values($warn_set),
        'nodes' => $nodes,
    );
}

function mobile_build_live_state($vars) {
    $src = (isset($vars['sdata']) && is_array($vars['sdata']) && isset($vars['sdata']['hp']))
        ? $vars['sdata']
        : $vars;

    $hp = isset($src['hp']) ? (int)$src['hp'] : (isset($vars['hp']) ? (int)$vars['hp'] : 0);
    $mhp = isset($src['mhp']) ? (int)$src['mhp'] : (isset($vars['mhp']) ? (int)$vars['mhp'] : 1);
    $sp = isset($src['sp']) ? (int)$src['sp'] : (isset($vars['sp']) ? (int)$vars['sp'] : 0);
    $msp = isset($src['msp']) ? (int)$src['msp'] : (isset($vars['msp']) ? (int)$vars['msp'] : 1);
    $rage = isset($src['rage']) ? (int)$src['rage'] : (isset($vars['rage']) ? (int)$vars['rage'] : 0);
    $ss = isset($src['ss']) ? (int)$src['ss'] : (isset($vars['ss']) ? (int)$vars['ss'] : 0);
    $mss = isset($src['mss']) ? (int)$src['mss'] : (isset($vars['mss']) ? (int)$vars['mss'] : 0);
    $inf = isset($src['inf']) ? (string)$src['inf'] : (isset($vars['inf']) ? (string)$vars['inf'] : '');
    $club = isset($src['club']) ? (int)$src['club'] : (isset($vars['club']) ? (int)$vars['club'] : 0);
    $pls = isset($src['pls']) ? (int)$src['pls'] : (isset($vars['pls']) ? (int)$vars['pls'] : 0);
    $pgroup = isset($src['pgroup']) ? (int)$src['pgroup'] : (isset($vars['pgroup']) ? (int)$vars['pgroup'] : 0);
    $skillpoint = isset($src['skillpoint']) ? (int)$src['skillpoint'] : (isset($vars['skillpoint']) ? (int)$vars['skillpoint'] : 0);
    $mode = isset($vars['mode']) ? (string)$vars['mode'] : '';

    $plsinfo = isset($vars['plsinfo']) && is_array($vars['plsinfo']) ? $vars['plsinfo'] : array();
    $hplsinfo = isset($vars['hplsinfo']) && is_array($vars['hplsinfo']) ? $vars['hplsinfo'] : array();
    $wthinfo = isset($vars['wthinfo']) && is_array($vars['wthinfo']) ? $vars['wthinfo'] : array();
    $infinfo = isset($vars['infinfo']) && is_array($vars['infinfo']) ? $vars['infinfo'] : array();
    $weather = isset($vars['weather']) ? (int)$vars['weather'] : 0;

    $pls_name = (!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup][$pls]))
        ? (string)$hplsinfo[$pgroup][$pls]
        : (isset($plsinfo[$pls]) ? (string)$plsinfo[$pls] : '');
    $weather_name = isset($wthinfo[$weather]) ? (string)$wthinfo[$weather] : '';

    $wounds = array();
    $wound_labels = array('h' => '头伤', 'b' => '胸伤', 'a' => '腕伤', 'f' => '足伤');
    foreach ($wound_labels as $wk => $wlabel) {
        if (strpos($inf, $wk) !== false) {
            $wounds[] = array('key' => $wk, 'label' => $wlabel);
        }
    }

    $ailments = array();
    foreach (array('p', 'u', 'i', 'e', 'w') as $ak) {
        if (strpos($inf, $ak) !== false) {
            $ailments[] = array(
                'key' => $ak,
                'label' => isset($infinfo[$ak]) ? strip_tags((string)$infinfo[$ak]) : $ak,
            );
        }
    }

    $map_info = mobile_build_map_nodes($vars);

    $payload = array(
        'hp' => $hp,
        'mhp' => max(1, $mhp),
        'hpPct' => $mhp > 0 ? max(0, min(100, (int)round($hp / $mhp * 100))) : 0,
        'sp' => $sp,
        'msp' => max(1, $msp),
        'spPct' => $msp > 0 ? max(0, min(100, (int)round($sp / $msp * 100))) : 0,
        'rage' => $rage,
        'ragePct' => max(0, min(100, $rage)),
        'ss' => $ss,
        'mss' => $mss,
        'ssPct' => $mss > 0 ? max(0, min(100, (int)round($ss / $mss * 100))) : 0,
        'club' => $club,
        'skillpoint' => $skillpoint,
        'mode' => $mode,
        'locationId' => $pls,
        'locationName' => $pls_name,
        'pgroup' => $pgroup,
        'weatherName' => $weather_name,
        'isHiddenGroup' => $map_info['isHiddenGroup'],
        'inDanger' => in_array($pls, $map_info['dangerSet']),
        'inWarn' => in_array($pls, $map_info['warnSet']),
        'hack' => !empty($vars['hack']),
        'wounds' => $wounds,
        'ailments' => $ailments,
        'canBandage' => ($mode === 'command' && $hp > 0),
        'canCureAilment' => ($mode === 'command' && $hp > 0 && $club === 16),
        'mapNodes' => $map_info['nodes'],
    );

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

?>
