<?php

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
if (!defined('IN_GAME')) {
    define('IN_GAME', true);
}
if (!defined('CURSCRIPT')) {
    define('CURSCRIPT', 'game');
}
if (!defined('GAME_ROOT')) {
    define('GAME_ROOT', dirname(__DIR__, 2) . '/');
}
if (!defined('TEMPLATEID')) {
    define('TEMPLATEID', 1);
}
if (!defined('TPLDIR')) {
    define('TPLDIR', './templates/default');
}

require_once GAME_ROOT . 'include/global.func.php';
require_once GAME_ROOT . 'include/template.func.php';
require_once GAME_ROOT . 'include/game/titles.func.php';
require_once GAME_ROOT . 'include/game/ending_routes.func.php';
require_once GAME_ROOT . 'include/game/severe_status.func.php';
require_once GAME_ROOT . 'templates/mobile/mobile_helpers.inc.php';

function mb_assert($cond, $msg) {
    if (!$cond) {
        throw new RuntimeException('FAIL: ' . $msg);
    }
    echo 'PASS: ' . $msg . "\n";
}

// 1. Test mobile / tablet detection
mb_assert(dts_is_mobile_client(array('HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')), 'iPhone UA recognized as mobile');
mb_assert(dts_is_mobile_client(array('HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 16_6 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1')), 'iPad UA recognized as mobile');
mb_assert(dts_is_mobile_client(array('HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/124.0.0.0 Mobile Safari/537.36')), 'Android phone UA recognized as mobile');
mb_assert(dts_is_mobile_client(array('HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 13; SM-X900) AppleWebKit/537.36 Tablet Safari/537.36')), 'Android tablet UA recognized as mobile');
mb_assert(dts_is_mobile_client(array('HTTP_USER_AGENT' => 'Mozilla/5.0 (Phone; OpenHarmony 5.0) AppleWebKit/537.36 ArkWeb/4.1.6.1 Mobile')), 'HarmonyOS UA recognized as mobile');
mb_assert(dts_is_mobile_client(array('HTTP_SEC_CH_UA_MOBILE' => '?1', 'HTTP_USER_AGENT' => 'CustomBrowser/1.0')), 'Sec-CH-UA-Mobile ?1 recognized as mobile');
mb_assert(!dts_is_mobile_client(array('HTTP_SEC_CH_UA_MOBILE' => '?0', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0.0.0 Safari/537.36')), 'Windows desktop UA not recognized as mobile');

// 2. Test UI template resolution & switching
$iphone_srv = array('HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) Mobile/15E148');
$desktop_srv = array('HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0.0.0');

mb_assert(dts_resolve_ui_template(null, $iphone_srv, array(), array()) === array(3, './templates/mobile'), 'Mobile guest defaults to mobile template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 0), $iphone_srv, array(), array()) === array(3, './templates/mobile'), 'Mobile classic user defaults to mobile template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 2), $iphone_srv, array(), array()) === array(3, './templates/mobile'), 'Mobile nouveau user defaults to mobile template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 0), $iphone_srv, array('ui_mode' => 'desktop'), array()) === array(null, null), 'Mobile classic user switching to desktop gets classic template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 2), $iphone_srv, array(), array('dts_ui_mode' => 'desktop')) === array(2, './templates/nouveau'), 'Mobile nouveau user with desktop cookie gets nouveau template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 3), $iphone_srv, array(), array('dts_ui_mode' => 'desktop')) === array(null, null), 'User with u_templateid=3 switching to desktop gets classic template');
mb_assert(dts_resolve_ui_template(null, $desktop_srv, array('ui_mode' => 'mobile'), array()) === array(3, './templates/mobile'), 'Desktop user switching to mobile gets mobile template');
mb_assert(dts_resolve_ui_template(array('u_templateid' => 3), $desktop_srv, array(), array()) === array(3, './templates/mobile'), 'Desktop user with u_templateid=3 gets mobile template');
mb_assert(dts_resolve_ui_template(null, $desktop_srv, array(), array()) === array(null, null), 'Desktop guest gets default template');
unset($_COOKIE['dts_ui_mode']);

// 3. Compile all mobile templates & verify PHP parse trees
$ending_routes_v2 = 1;
$club_skills = 1;
$current_ruleset_config = array();
$tplrefresh = 1;
$TEMPLATEID_OVERRIDE = 3;
$TPLDIR_OVERRIDE = './templates/mobile';
$compiled_files = array();

$preexisting_tpls = glob(GAME_ROOT . 'gamedata/templates/*.tpl.php') ?: array();
$mobile_tpls = array('header', 'footer', 'css', 'index', 'game', 'profile', 'battle_rev', 'battle', 'command', 'sp_terminal', 'slidingpanel', 'chat', 'rest', 'death', 'usergdicon');
try {
    foreach ($mobile_tpls as $name) {
        $tag = 'mbtest_' . getmypid();
        $obj = GAME_ROOT . 'gamedata/templates/' . $tag . '_' . $name . '.tpl.php';
        parse_template($name, $tag, './templates/mobile');
        $compiled_files[] = $obj;
        token_get_all(file_get_contents($obj), TOKEN_PARSE);
    }
    foreach (array(array('header', './templates/default'), array('usergdicon', './templates/default'), array('header', './templates/nouveau')) as $pair) {
        $tag = 'mbtest_ext_' . getmypid() . '_' . md5($pair[1]);
        $obj = GAME_ROOT . 'gamedata/templates/' . $tag . '_' . $pair[0] . '.tpl.php';
        parse_template($pair[0], $tag, $pair[1]);
        $compiled_files[] = $obj;
        token_get_all(file_get_contents($obj), TOKEN_PARSE);
    }
    mb_assert(true, 'All 15 mobile templates and modified default/nouveau templates compiled cleanly');

    // 4. Test mobile_build_live_state for normal map AND PvE hidden zones (pgroup=3 Bay City & pgroup=4 Legacy Hall)
    include GAME_ROOT . 'gamedata/cache/gamecfg_1.php';
    include GAME_ROOT . 'gamedata/cache/combatcfg_1.php';
    include GAME_ROOT . 'gamedata/cache/resources_1.php';
    include GAME_ROOT . 'gamedata/cache/titles_1.php';
    include GAME_ROOT . 'gamedata/cache/tooltip_1.php';

    $sample_vars = array(
        'hp' => 320, 'mhp' => 450, 'sp' => 280, 'msp' => 400, 'rage' => 65,
        'ss' => 40, 'mss' => 100, 'inf' => 'hp', 'club' => 16, 'skillpoint' => 3,
        'mode' => 'command', 'pls' => 14, 'pgroup' => 0, 'weather' => 1, 'hack' => 0,
        'arealist' => array(0, 5, 14, 21, 22), 'areanum' => 1, 'areaadd' => 2,
        'plsinfo' => $plsinfo, 'hplsinfo' => $hplsinfo, 'xyinfo' => $xyinfo,
        'wthinfo' => $wthinfo, 'infinfo' => $infinfo,
    );
    $decoded1 = json_decode(mobile_build_live_state($sample_vars), true);
    mb_assert(is_array($decoded1) && $decoded1['hp'] === 320 && $decoded1['inWarn'] === true && !$decoded1['isHiddenGroup'], 'Live state computes normal map warning zone accurately');
    mb_assert(count($decoded1['wounds']) === 1 && $decoded1['wounds'][0]['key'] === 'h' && $decoded1['canBandage'] === true, 'Live state extracts bandageable wound');
    mb_assert(count($decoded1['ailments']) === 1 && $decoded1['ailments'][0]['key'] === 'p' && $decoded1['canCureAilment'] === true, 'Live state extracts Club 16 curable ailment');

    $bay_vars = $sample_vars;
    $bay_vars['pgroup'] = 3;
    $bay_vars['pls'] = 120;
    $decoded_bay = json_decode(mobile_build_live_state($bay_vars), true);
    mb_assert($decoded_bay['isHiddenGroup'] === true && count($decoded_bay['mapNodes']) === count($hplsinfo[3]), 'Live state switches map nodes to hidden PvE Bay City group when pgroup=3');

    $hall_vars = $sample_vars;
    $hall_vars['pgroup'] = 4;
    $hall_vars['pls'] = 134;
    $decoded_hall = json_decode(mobile_build_live_state($hall_vars), true);
    mb_assert($decoded_hall['isHiddenGroup'] === true && $decoded_hall['mapNodes'][0]['id'] === 134, 'Live state switches map nodes to Legacy Hall when pgroup=4');

    // 5. Render header, footer & profile under strict error handler to ensure zero undefined notices
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    $charset = 'utf-8';
    $hotkeyon = 1;
    $cuser = 'TestMobileUser';
    $cpass = 'hash';
    $new_messages = 1;
    $gameversion = 'GE942';
    $error = '';

    ob_start();
    include GAME_ROOT . 'gamedata/templates/mbtest_' . getmypid() . '_header.tpl.php';
    include GAME_ROOT . 'gamedata/templates/mbtest_' . getmypid() . '_footer.tpl.php';
    $shell_html = ob_get_clean();
    mb_assert(strpos($shell_html, '?ui_mode=desktop') !== false && strpos($shell_html, 'dtsSwitchUiMode') !== false, 'Mobile shell renders desktop UI switcher in header & footer');
    mb_assert(strpos($shell_html, 'fireseed_shop.php') !== false && strpos($shell_html, 'vnworld.php') !== false && strpos($shell_html, 'messages.php') !== false, 'Mobile navigation drawer contains full site navigation');

    // Render profile.htm under strict ErrorException mode
    $pdata = array(
        'hp' => 320, 'mhp' => 450, 'sp' => 280, 'msp' => 400, 'rage' => 65,
        'ss' => 40, 'mss' => 100, 'lvl' => 12, 'exp' => 110, 'upexp' => 140,
        'att' => 200, 'def' => 180, 'money' => 1500, 'killnum' => 4, 'skillpoint' => 2,
        'wp' => 120, 'wk' => 40, 'wg' => 20, 'wc' => 15, 'wd' => 10, 'wf' => 80,
        'inf' => 'hp', 'club' => 12, 'pose' => 2, 'tactic' => 3, 'horizon' => 0,
        'pls' => 34, 'pgroup' => 0, 'name' => 'TestAgent', 'gd' => 'f', 'sNo' => 7,
        'nick' => 0, 'teamID' => '', 'iconImg' => 'f_1.gif',
        'wep' => '激光剑', 'wepk' => 'WK', 'wepe' => 240, 'weps' => 80, 'wepsk' => '',
        'wep2' => '小刀', 'wep2c' => 1, 'wep2e' => 30, 'wep2s' => 10,
        'arb' => '作战服', 'arbk' => 'DB', 'arbe' => 80, 'arbs' => 40, 'arbsk' => '',
        'arh' => '', 'arhk' => '', 'arhe' => 0, 'arhs' => 0, 'arhsk' => '',
        'ara' => '', 'arak' => '', 'arae' => 0, 'aras' => 0, 'arask' => '',
        'arf' => '', 'arfk' => '', 'arfe' => 0, 'arfs' => 0, 'arfsk' => '',
        'art' => 'Untainted Glory', 'artk' => 'A', 'arte' => 1, 'arts' => 1, 'artsk' => '',
        'itm1' => '游戏解除钥匙', 'itmk1' => 'Y', 'itme1' => 1, 'itms1' => 1, 'itmsk1' => 'Zv',
        'itm2' => '', 'itmk2' => '', 'itme2' => 0, 'itms2' => 0, 'itmsk2' => '',
        'itm3' => '', 'itmk3' => '', 'itme3' => 0, 'itms3' => 0, 'itmsk3' => '',
        'itm4' => '', 'itmk4' => '', 'itme4' => 0, 'itms4' => 0, 'itmsk4' => '',
        'itm5' => '', 'itmk5' => '', 'itme5' => 0, 'itms5' => 0, 'itmsk5' => '',
        'itm6' => '', 'itmk6' => '', 'itme6' => 0, 'itms6' => 0, 'itmsk6' => '',
        'clbpara' => array(),
    );
    extract($pdata);
    $mode = 'command';
    $weather = 1;
    $hack = 0;
    $arealist = array(0, 5, 14);
    $areanum = 1;
    $areaadd = 2;
    $gamestate = 20;
    $gamenum = 99;
    $now = 1000;
    $hpgroup = 'clan';
    $spgroup = 'clan';
    $atkinfo = '200+240';
    $definfo = '180+80';
    $apose = array(0, 1, 2, 3, 4, 5);
    $atac = array(0, 2, 3, 4);
    $wep_words = '激光剑'; $wepk_words = '斩系武器'; $wepsk_words = ''; $nowep = '拳头';
    $arb_words = '作战服'; $arbk_words = '身体防具'; $arbsk_words = ''; $noarb = '内衣';
    $arh_words = ''; $arhk_words = ''; $arhsk_words = '';
    $ara_words = ''; $arak_words = ''; $arask_words = '';
    $arf_words = ''; $arfk_words = ''; $arfsk_words = '';
    $art_words = 'Untainted Glory'; $artk_words = '饰品'; $artsk_words = '';
    $itm1_words = '游戏解除钥匙'; $itmk1_words = '特殊'; $itmsk1_words = '真解禁';
    $itm2_words = $itm3_words = $itm4_words = $itm5_words = $itm6_words = '';
    $itmk2_words = $itmk3_words = $itmk4_words = $itmk5_words = $itmk6_words = '';
    $itmsk2_words = $itmsk3_words = $itmsk4_words = $itmsk5_words = $itmsk6_words = '';
    $paperdoll_profile_json = '{}';

    ob_start();
    include GAME_ROOT . 'gamedata/templates/mbtest_' . getmypid() . '_profile.tpl.php';
    $profile_html = ob_get_clean();
    mb_assert(strpos($profile_html, 'id="pose"') !== false && strpos($profile_html, 'id="tactic"') !== false && strpos($profile_html, 'id="horizon"') !== false, 'Mobile profile renders pose, tactic, and horizon controls');
    mb_assert(strpos($profile_html, 'offwep') !== false && strpos($profile_html, 'changewep') !== false && strpos($profile_html, 'useBagItem(1)') !== false, 'Mobile profile renders unequip, swap weapon, and bag slot 1 actions');

    // Render battle_rev.htm under strict ErrorException mode
    $battle_title = '发现敌人';
    $hpcolor = 'clan';
    $sdata = $pdata + array('nameinfo' => 'TestAgent', 'shield_info' => '', 'infdata' => '无', 'wep_words' => '激光剑', 'wepk_words' => '斩系武器');
    $tdata = array(
        'hp' => 1500, 'iconImg' => 'n_1.gif', 'name' => '红杀教头', 'nameinfo' => '红杀教头',
        'type' => 34, 'typeinfo' => '红杀教头', 'sNoinfo' => '', 'lvlinfo' => 'Lv.35',
        'hpstate' => '正常', 'spstate' => '充沛', 'ragestate' => '愤怒',
        'wep_words' => '红杀制式刃', 'wepk' => 'WK', 'wepk_words' => '斩系', 'wepestate' => '极强',
        'poseinfo' => '强袭姿态', 'tacinfo' => '反击策略', 'infdata' => '无', 'rp' => 0,
        'clbpara' => array('skill' => array()),
    );
    ob_start();
    include GAME_ROOT . 'gamedata/templates/mbtest_' . getmypid() . '_battle_rev.tpl.php';
    $battle_html = ob_get_clean();
    mb_assert(strpos($battle_html, 'data-mb-main-kind="battle_rev"') !== false && strpos($battle_html, 'id="mb-live-state"') !== false, 'Mobile battle_rev renders compact VS card and live HUD state');

    restore_error_handler();
} finally {
    foreach ($compiled_files as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    foreach (glob(GAME_ROOT . 'gamedata/templates/*.tpl.php') ?: array() as $f) {
        if (!in_array($f, $preexisting_tpls, true) && is_file($f)) {
            unlink($f);
        }
    }
}

echo "Mobile template regression suite complete.\n";
