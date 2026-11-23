<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

/**
 * Xharmer 装备改造系列道具处理
 */

function xmod_has_sk($sk_str, $flag)
{
	return strpos((string)$sk_str, $flag) !== false;
}

function xmod_add_sk(&$sk_str, $flag)
{
	$sk_str = (string)$sk_str;
	if (strpos($sk_str, $flag) === false) {
		$sk_str .= $flag;
	}
}

function xmod_del_sk(&$sk_str, $flag)
{
	$sk_str = str_replace($flag, '', (string)$sk_str);
}

function xmod_add_inf(&$inf_str, $inj)
{
	$inf_str = (string)$inf_str;
	if (strpos($inf_str, $inj) === false) {
		$inf_str .= $inj;
	}
}

function xmod_mark_modified(&$name_str, &$sk_str, $strip_elite = true, $prefix = '')
{
	if ($strip_elite) {
		$sk_str = str_replace('Z', '', (string)$sk_str);
	}
	if (strpos((string)$sk_str, '🔰') === false) {
		$sk_str .= '🔰';
	}
	// 保留妖精种族绑定羽翼原名，避免破坏种族羽翼识别
	if (strpos((string)$name_str, '妖精羽翼·') === 0) {
		return;
	}
	if ($prefix !== '') {
		if (strpos((string)$name_str, $prefix) === false && mb_strlen($prefix . $name_str, 'UTF-8') <= 30) {
			$name_str = $prefix . $name_str;
		}
	} else {
		if (strpos((string)$name_str, '-改') === false && mb_strlen((string)$name_str, 'UTF-8') <= 28) {
			$name_str .= '-改';
		}
	}
}

function xmod_append_lore(&$para_arr, $line)
{
	if (!is_array($para_arr)) {
		$para_arr = get_itmpara($para_arr);
	}
	if (empty($para_arr['lore'])) {
		$para_arr['lore'] = $line;
	} elseif (strpos($para_arr['lore'], $line) === false) {
		$para_arr['lore'] .= "\r" . $line;
	}
}

function xmod_is_item_name($itm)
{
	return in_array($itm, array(
		'「X」牌金属活性诱导膏',
		'「X」印恶魔角铆钉组',
		'漫展淘来的「非法电弧谐振器」',
		'心尾烙印·契约回火钳',
		'「X」牌超韧记忆金属内衬',
		'「X」式重装手足拘束具',
		'「X」印折翼偏折镀层',
		'「X」印噬魔虹彩涂装',
		'「X」印·魅魔的心核匣',
	), true);
}

function xmod_club21_reject($itm)
{
	global $log;
	$log .= "<span class=\"yellow\">突然，你的眼前出现了扭曲的字符！</span><br>";
	$log .= "<span class=\"glitchb\">
	“歪歪扭扭小问号，<br>
	弯角尾巴哪一套？<br>
	来路不明黑科技，<br>
	休想往我包里套！”<br></span><br>";
	$log .= "<span class=\"yellow\">唔，看起来<span class=\"tmagenta\">{$itm}</span>对你似乎没有什么意义……</span><br>";
}

function xmod_has_valid_weapon($data)
{
	global $nowep, $nosta;
	$wep = isset($data['wep']) ? $data['wep'] : '';
	$wepk = isset($data['wepk']) ? $data['wepk'] : '';
	$wepe = isset($data['wepe']) ? (int)$data['wepe'] : 0;
	$weps = isset($data['weps']) ? $data['weps'] : 0;
	if (empty($wep) || $wep === $nowep || $wepk === 'WN' || strpos($wepk, 'W') !== 0) {
		return false;
	}
	if (empty($weps) && $weps !== $nosta) {
		return false;
	}
	if ($wepe <= 0 && strpos($wepk, 'WG') !== 0 && strpos($wepk, 'WJ') !== 0 && strpos($wepk, 'WB') !== 0) {
		return false;
	}
	return true;
}

function xmod_check_weapon_common($data)
{
	global $log;
	if (!xmod_has_valid_weapon($data)) {
		$log .= '请先装备一件有效的武器。<br>';
		return false;
	}
	if (strpos((string)$data['wepsk'], 'j') !== false) {
		$log .= '多重武器结构过于复杂，不能进行此类改造。<br>';
		return false;
	}
	if ($data['wepk'] === 'WFq') {
		$log .= '这段剥落的记忆没有实体，连焊枪和铆钉都无从下手。<br>';
		return false;
	}
	return true;
}

function xmod_has_valid_armor($data, $slot)
{
	global $noarb, $nosta;
	$allowed = array('arb' => 'DB', 'arh' => 'DH', 'ara' => 'DA', 'arf' => 'DF');
	if (!isset($allowed[$slot])) {
		return false;
	}
	$nm = isset($data[$slot]) ? $data[$slot] : '';
	$kd = isset($data[$slot . 'k']) ? $data[$slot . 'k'] : '';
	$st = isset($data[$slot . 's']) ? $data[$slot . 's'] : 0;
	$sk = isset($data[$slot . 'sk']) ? $data[$slot . 'sk'] : '';
	if (empty($nm) || (empty($st) && $st !== $nosta)) {
		return false;
	}
	if ($slot === 'arb' && ($nm === $noarb || $kd === 'DN')) {
		return false;
	}
	if (strpos($kd, 'D') !== 0 || $kd === 'DN') {
		return false;
	}
	if (strpos($sk, '^') !== false) {
		return false;
	}
	return true;
}

function xmod_slot_label($slot)
{
	$map = array(
		'arb' => '身体防具',
		'arh' => '头部防具',
		'ara' => '手臂防具',
		'arf' => '腿部防具',
	);
	return isset($map[$slot]) ? $map[$slot] : $slot;
}

function xmod_is_valid_sacrifice($data, $slot, $exclude_slot)
{
	global $nosta;
	if ($slot < 1 || $slot > 6 || $slot === (int)$exclude_slot) {
		return false;
	}
	$nm = isset($data['itm' . $slot]) ? $data['itm' . $slot] : '';
	$kd = isset($data['itmk' . $slot]) ? $data['itmk' . $slot] : '';
	$ef = isset($data['itme' . $slot]) ? (int)$data['itme' . $slot] : 0;
	$st = isset($data['itms' . $slot]) ? $data['itms' . $slot] : 0;
	$sk = isset($data['itmsk' . $slot]) ? $data['itmsk' . $slot] : '';
	if (empty($nm) || (empty($st) && $st !== $nosta) || $ef <= 0) {
		return false;
	}
	if ((strpos($kd, 'W') !== 0 && strpos($kd, 'D') !== 0) || $kd === 'WN' || $kd === 'DN' || $kd === 'WFq') {
		return false;
	}
	if (strpos($sk, '^') !== false || strpos($sk, 'V') !== false) {
		return false;
	}
	$para = get_itmpara(isset($data['itmpara' . $slot]) ? $data['itmpara' . $slot] : '');
	if (!empty($para['IsQuestItem']) || !empty($para['QuestID']) || !empty($para['fcg_phantom'])) {
		return false;
	}
	return true;
}

function xmod_apply_cursed_skill(&$data)
{
	if (function_exists('check_skill_unlock') && function_exists('getclubskill')) {
		if (check_skill_unlock('inf_cursed', $data)) {
			getclubskill('inf_cursed', $data['clbpara']);
		}
	}
}

function xmod_trigger_dialogue(&$data, $dlg_id)
{
	$data['clbpara'] = get_clbpara($data['clbpara']);
	if (empty($data['clbpara']['dialogue']) && empty($data['clbpara']['noskip_dialogue'])) {
		$data['clbpara']['dialogue'] = $dlg_id;
	}
}

function xmod_build_menu($itmn, $prompt, $options)
{
	$html = '<input type="hidden" name="mode" value="item">'
		. '<input type="hidden" name="usemode" value="xharmer_mod">'
		. '<input type="hidden" name="itmp" value="' . (int)$itmn . '">'
		. $prompt . '<br>'
		. '<input type="radio" name="command" id="menu" value="menu" checked>'
		. '<a onclick=sl("menu"); href="javascript:void(0);">返回</a><br><br>';
	foreach ($options as $val => $label) {
		$id = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
		$html .= '<input type="radio" name="command" id="' . $id . '" value="' . $id . '">'
			. '<a onclick=sl("' . $id . '"); href="javascript:void(0);">' . $label . '</a><br>';
	}
	$html .= '<br><input type="button" class="cmdbutton" onclick="postCmd(\'gamecmd\',\'command.php\');" value="提交">';
	return $html;
}

function xmod_consume_mod_item(&$data, $itmn, $used_name, $target_name)
{
	global $log, $nosta, $now;
	$itms = &$data['itms' . $itmn];
	$itm = &$data['itm' . $itmn];
	$itmk = &$data['itmk' . $itmn];
	$itme = &$data['itme' . $itmn];
	$itmsk = &$data['itmsk' . $itmn];
	$itmpara = &$data['itmpara' . $itmn];

	if (function_exists('addnews')) {
		addnews($now, 'newwep', $data['name'], $used_name, $target_name, isset($data['nick']) ? $data['nick'] : 0);
	}
	if ($itms !== $nosta) {
		$itms--;
		if ($itms <= 0) {
			$log .= "<span class=\"red\">{$used_name}</span>用光了。<br>";
			$itm = $itmk = $itmsk = $itmpara = '';
			$itme = $itms = 0;
		}
	}
	if ((int)$itmn === 0 && (int)$data['club'] === 24 && function_exists('club24_item_signature')) {
		if (!empty($data['itms0'])) {
			$data['clbpara']['c24_pickup'] = club24_item_signature($data);
		} else {
			unset($data['clbpara']['c24_pickup']);
		}
	}
	if (function_exists('ai93_trigger')) {
		ai93_trigger(2, $data);
	}
}

/**
 * 初次点击使用 Xharmer 改造物品入口
 */
function item_xharmer_mod($itmn, &$data)
{
	global $log, $cmd, $nosta, $now, $itemspkinfo;
	extract($data, EXTR_REFS);

	$itm = &${'itm' . $itmn};
	$itmk = &${'itmk' . $itmn};
	$itme = &${'itme' . $itmn};
	$itms = &${'itms' . $itmn};
	$itmsk = &${'itmsk' . $itmn};

	if (!xmod_is_item_name($itm)) {
		return false;
	}
	if ($club == 21) {
		xmod_club21_reject($itm);
		return true;
	}

	// ① 「X」牌金属活性诱导膏（二级菜单选择背包祭品）
	if ($itm === '「X」牌金属活性诱导膏') {
		if (!xmod_check_weapon_common($data)) {
			return true;
		}
		$wpara = get_itmpara($weppara);
		if (!empty($wpara['x_fed'])) {
			$log .= "你的<span class=\"yellow\">{$wep}</span>已经喂过一次金属活性诱导膏了，金属纹路吃得饱饱的，再涂就要撑裂啦！<br>";
			return true;
		}
		if ($hp < 150 || $sp < 150) {
			$log .= '你的生命或体力不足150点，压不住活化金属咬合时的反扑！先吃点东西垫垫吧。<br>';
			return true;
		}
		$opts = array();
		for ($i = 1; $i <= 6; $i++) {
			if (xmod_is_valid_sacrifice($data, $i, $itmn)) {
				$s_nm = htmlspecialchars(${'itm' . $i}, ENT_QUOTES, 'UTF-8');
				$opts['xm_' . $i] = "吞噬包裹[{$i}]：{$s_nm}（效:{${'itme'.$i}} / 耐:{${'itms'.$i}}）";
			}
		}
		if (empty($opts)) {
			$log .= '你的包裹里没有可供熔解吞噬的其他武器或防具（任务道具、幻影器物与诅咒物不可作为祭品）。<br>';
			return true;
		}
		$log .= "<span class=\"tmagenta\">你拧开「X」牌金属活性诱导膏，银紫色的膏体竟像活物一样微微蠕动起来……</span><br>"
			. "<span class=\"grey\">标签小字：「涂上去两块铁自己就抱一块儿了，绝对是正经冷焊技术！……你问它为什么在吧唧嘴？错觉，全是错觉！」</span><br>";
		$cmd = xmod_build_menu($itmn, "要拿包裹里的哪件装备喂给<span class=\"yellow\">" . htmlspecialchars($wep, ENT_QUOTES, 'UTF-8') . "</span>？（消耗当前生命与体力各50%，主武器将灵魂绑定）", $opts);
		return true;
	}

	// ② 「X」印恶魔角铆钉组（直接敲击物理武器，可重复使用）
	if ($itm === '「X」印恶魔角铆钉组') {
		if (!xmod_check_weapon_common($data)) {
			return true;
		}
		if (!preg_match('/^W[PKGCJB]/', $wepk)) {
			$log .= '<span class="tmagenta">喂喂，往符纸和炸药包上敲铆钉是想原地升天吗？！这玩意儿只能钉在物理实体系武器上啦！</span><br>';
			return true;
		}
		if ($sp < 120) {
			$log .= '敲入恶魔角铆钉至少需要120点当前体力，你现在连锤子都快拎不动啦！<br>';
			return true;
		}
		$wpara = get_itmpara($weppara);
		$rivets = isset($wpara['x_rivets']) ? (int)$wpara['x_rivets'] + 1 : 1;
		$wpara['x_rivets'] = $rivets;

		$base_eff = max(1, (int)$itme);
		$is_gun = (strpos($wepk, 'WG') === 0 || strpos($wepk, 'WJ') === 0);
		$eff_gain = $is_gun ? (int)round($base_eff * 1.5) : $base_eff;
		$wepe += $eff_gain;

		$sk_msg = '';
		if (!xmod_has_sk($wepsk, 'N')) {
			xmod_add_sk($wepsk, 'N');
			$sk_msg = "并贯通出了<span class=\"yellow\">【{$itemspkinfo['N']}】</span>劲力！";
		} elseif (!xmod_has_sk($wepsk, 'n')) {
			xmod_add_sk($wepsk, 'n');
			$sk_msg = "双角共振进一步撕裂障壁，升阶觉醒了<span class=\"yellow\">【{$itemspkinfo['n']}】</span>属性！";
		}

		$cur_pct = isset($wpara['AddDamagePercentage']) ? (int)$wpara['AddDamagePercentage'] : 0;
		$pct_msg = '';
		if ($cur_pct < 24) {
			$add_pct = min(8, 24 - $cur_pct);
			$wpara['AddDamagePercentage'] = $cur_pct + $add_pct;
			$pct_msg = "最终伤害加成提升至<span class=\"yellow\">{$wpara['AddDamagePercentage']}%</span>！";
		}

		$old_wep = $wep;
		xmod_mark_modified($wep, $wepsk, true);
		xmod_append_lore($wpara, '「X」恶魔角铆接：超密度弯角配重改造');
		$weppara = $wpara;

		// 基础消耗：体力120、腕伤、非枪弓有限耐磨损15%
		$sp = max(0, $sp - 120);
		xmod_add_inf($inf, 'a');
		$wear_msg = '';
		if ($weps !== $nosta && !$is_gun && strpos($wepk, 'WB') !== 0) {
			$new_weps = max(1, (int)floor((int)$weps * 0.85));
			if ($new_weps < (int)$weps) {
				$weps = $new_weps;
				$wear_msg = "猛烈敲击使武器耐久磨损至<span class=\"red\">{$weps}</span>点；";
			}
		}

		$log .= "你抡起锤子，将第<span class=\"yellow\">{$rivets}</span>枚<span class=\"tmagenta\">恶魔角铆钉</span>狠狠砸进<span class=\"yellow\">{$old_wep}</span>！<br>"
			. "<span class=\"yellow\">{$wep}</span>的效果值提升了<span class=\"yellow\">{$eff_gain}</span>点（当前<span class=\"yellow\">{$wepe}</span>）！{$sk_msg}{$pct_msg}<br>"
			. "{$wear_msg}反震力震伤了你的手腕，消耗了<span class=\"red\">120</span>点体力。<br>";

		// 第3枚起贪心过载反咬判定（不伤武器，反咬使用者）
		if ($rivets >= 3) {
			$recoil_obbs = min(90, 15 * ($rivets - 2));
			if (rand(1, 100) <= $recoil_obbs) {
				$recoil_dmg = max(1, (int)floor($hp * 0.45));
				$hp = max(1, $hp - $recoil_dmg);
				xmod_add_inf($inf, 'b');
				$log .= "<span class=\"red\">【过载崩飞！】密集的弯角应力突然失控，一枚滚烫的碎角擦着你的胸口崩飞出去！你受到 {$recoil_dmg} 点伤害，胸部挂了彩——但武器硬是扛住了！</span><br>";
			} else {
				$log .= "<span class=\"grey\">武器表面发出危险的金属悲鸣……再继续往上钉的话，小心崩到自己胸口哦！</span><br>";
			}
		}

		if (function_exists('addnews')) {
			addnews($now, 'newwep', $name, $itm, $wep, isset($nick) ? $nick : 0);
		}
		if ($itms !== $nosta) {
			$itms--;
		}
		return true;
	}

	// ③ 漫展淘来的「非法电弧谐振器」（直接安装于 WF/WD/WG/WC）
	if ($itm === '漫展淘来的「非法电弧谐振器」') {
		if (!xmod_check_weapon_common($data)) {
			return true;
		}
		if (!preg_match('/^W[FDGC]/', $wepk)) {
			$log .= '<span class="tmagenta">这台电弧谐振器只适配灵力、爆破、普通枪械或投掷兵器，近战刀棍和弓弩可没地方接高压线圈！</span><br>';
			return true;
		}
		$wpara = get_itmpara($weppara);
		if (!empty($wpara['x_arc'])) {
			$log .= "你的<span class=\"yellow\">{$wep}</span>已经挂着一台滋滋冒火花的非法电弧谐振器了，串联两台会当场炸膛的！<br>";
			return true;
		}
		if ($sp < 280) {
			$log .= '安装非法电弧谐振器至少需要280点当前体力来硬扛启动漏电！<br>';
			return true;
		}

		$had_fire_elec = (xmod_has_sk($wepsk, 'e') || xmod_has_sk($wepsk, 'u') || xmod_has_sk($wepsk, 'f'));
		$had_ice = (xmod_has_sk($wepsk, 'i') || xmod_has_sk($wepsk, 'k'));
		$had_silencer = xmod_has_sk($wepsk, 'S');

		$wepe += 420;
		$wpara['AddDamageRaw'] = (isset($wpara['AddDamageRaw']) ? (int)$wpara['AddDamageRaw'] : 0) + 220;
		$wpara['x_arc'] = 1;

		xmod_add_sk($wepsk, 'y');
		xmod_add_sk($wepsk, 'e');
		$extra_sk = array($itemspkinfo['y'], $itemspkinfo['e']);
		if ($had_fire_elec && !xmod_has_sk($wepsk, 'f')) {
			xmod_add_sk($wepsk, 'f');
			$extra_sk[] = $itemspkinfo['f'];
		} elseif ($had_fire_elec) {
			$extra_sk[] = $itemspkinfo['f'];
		}
		if ($had_ice && !xmod_has_sk($wepsk, 'k')) {
			xmod_add_sk($wepsk, 'k');
			$extra_sk[] = $itemspkinfo['k'];
		}
		if ($had_silencer) {
			xmod_del_sk($wepsk, 'S');
		}

		$old_wep = $wep;
		xmod_mark_modified($wep, $wepsk, true);
		xmod_append_lore($wpara, '「X」非法电弧谐振：展会限定声光漏电改件');
		$weppara = $wpara;

		$sp = max(0, $sp - 280);
		xmod_add_inf($inf, 'e');
		xmod_add_inf($inf, 'u');

		$log .= "<span class=\"tmagenta\">你用绝缘胶带把「非法电弧谐振器」强行绑在{$old_wep}上，按下开关的瞬间紫红色电弧冲天而起！</span><br>"
			. "<span class=\"grey\">「别管漏不漏电，你就说这特效酷不酷吧！」</span><br>"
			. "<span class=\"yellow\">{$wep}</span>的效果值提升了<span class=\"yellow\">420</span>点（当前<span class=\"yellow\">{$wepe}</span>），最终伤害固定增加<span class=\"yellow\">220</span>点，并赋予了<span class=\"yellow\">【" . implode(' / ', array_unique($extra_sk)) . "】</span>属性！<br>";
		if ($had_silencer) {
			$log .= '<span class="red">震耳欲聋的电弧噼啪声彻底报废了原有的消音结构！</span><br>';
		}
		$log .= '狂暴的漏电顺着虎口窜遍全身，你消耗了<span class="red">280</span>点体力，并陷入了<span class="yellow">麻痹</span>与<span class="red">烧伤</span>状态！<br>';

		if (function_exists('addnews')) {
			addnews($now, 'newwep', $name, $itm, $wep, isset($nick) ? $nick : 0);
		}
		if ($itms !== $nosta) {
			$itms--;
		}
		return true;
	}

	// ④ 心尾烙印·契约回火钳（二级菜单二选一）
	if ($itm === '心尾烙印·契约回火钳') {
		if (!xmod_check_weapon_common($data)) {
			return true;
		}
		if ($weps === $nosta || (int)$weps <= 0) {
			$log .= '契约回火钳只能用于拥有有限耐久（或弹药）的武器！<br>';
			return true;
		}
		if ((int)$wepe <= 0) {
			$log .= '这把武器已经没有剩余威力可供回火重铸了。<br>';
			return true;
		}
		$wpara = get_itmpara($weppara);
		if (!empty($wpara['x_temper'])) {
			$log .= "你的<span class=\"yellow\">{$wep}</span>上已经烙着一枚滚烫的心形箭尾印记了，一器不签二约哦！<br>";
			return true;
		}
		$opts = array(
			'xm_burn' => '【燃尽契约】削减80%耐久（最多留12，枪械锁死装弹），效果×1.75（上限+900）、最终固伤+280、附加重击辅助（需耐久≥10且非弓，耗250生命/200体力）',
			'xm_eternal' => '【永伴契约】降低20%效果，换取无限耐久（枪弓改为灌注150发并锁死枪械装弹；需生命≥300且体力≥300，使用后当前生命降至1、体力归0并烧伤）',
		);
		$log .= "<span class=\"tmagenta\">烧得暗红的心形箭尾回火钳在空气中滋滋作响，左右两档刻度散发着截然不同的危险气息……</span><br>"
			. "<span class=\"grey\">「左边档位叫『爽一把就碎』，右边档位叫『凑合过一辈子』。想清楚再烙，心形印子盖上去可没后悔药！」</span><br>";
		$cmd = xmod_build_menu($itmn, "要为<span class=\"yellow\">" . htmlspecialchars($wep, ENT_QUOTES, 'UTF-8') . "</span>（效:{$wepe} / 耐:{$weps}）烙下哪道契约？", $opts);
		return true;
	}

	// ⑤ 「X」牌超韧记忆金属内衬（二级菜单选四部位防具）
	if ($itm === '「X」牌超韧记忆金属内衬') {
		if ($hp <= 200) {
			$log .= '你的当前生命不足201点，记忆金属内衬咬合勒紧的瞬间会直接把你勒断气！先回点血吧。<br>';
			return true;
		}
		$opts = array();
		foreach (array('arb', 'arh', 'ara', 'arf') as $slot) {
			if (!xmod_has_valid_armor($data, $slot)) {
				continue;
			}
			$apara = get_itmpara(${$slot . 'para'});
			if (!empty($apara['x_liner'])) {
				continue;
			}
			$s_nm = htmlspecialchars(${$slot}, ENT_QUOTES, 'UTF-8');
			$opts['xm_' . $slot] = xmod_slot_label($slot) . "：{$s_nm}（效:{${$slot.'e'}} / 耐:{${$slot.'s'}}）";
		}
		if (empty($opts)) {
			$log .= '你身上没有尚未贴过内衬的有效防具（内衣与背包不可改造）。<br>';
			return true;
		}
		$log .= "<span class=\"tmagenta\">你展开「X」牌超韧记忆金属内衬，内侧细密的倒刺挂钩在灯光下泛着令人头皮发麻的寒光……</span><br>"
			. "<span class=\"grey\">「漫展重甲COS服同款黑科技内衬！显瘦、抗揍、刀枪不入——唯一的缺点是贴上肉就脱不下来了，字面意思。」</span><br>";
		$cmd = xmod_build_menu($itmn, '要将记忆金属内衬焊死在哪件防具内侧？（+550防/+150耐/终伤减免18%/随机1元素抗性；附加【诅咒】不可脱换，消耗200生命）', $opts);
		return true;
	}

	// ⑥ 「X」式重装手足拘束具（二级菜单选 DA 或 DF）
	if ($itm === '「X」式重装手足拘束具') {
		if ($hp <= 250 || $sp < 250) {
			$log .= '锁紧重装手足拘束具需要当前生命大于250点且当前体力不少于250点！<br>';
			return true;
		}
		$opts = array();
		foreach (array('ara', 'arf') as $slot) {
			if (!xmod_has_valid_armor($data, $slot)) {
				continue;
			}
			$apara = get_itmpara(${$slot . 'para'});
			if (!empty($apara['x_shackle'])) {
				continue;
			}
			$s_nm = htmlspecialchars(${$slot}, ENT_QUOTES, 'UTF-8');
			$extra_desc = ($slot === 'ara')
				? '赋予【HP制御】并无效「上肢破坏」负面效果'
				: '赋予【陷阱迎击+探测】并无效「下肢破坏」负面效果';
			$opts['xm_' . $slot] = xmod_slot_label($slot) . "：{$s_nm}（效:{${$slot.'e'}} / 耐:{${$slot.'s'}} → +500防/+120耐/固减200/{$extra_desc}）";
		}
		if (empty($opts)) {
			$log .= '你尚未穿戴可改造的手臂防具或腿部防具（每件手足防具限装一次）。<br>';
			return true;
		}
		$log .= "<span class=\"tmagenta\">沉甸甸的黑紫液压笼手与厚底拘束靴扣碰撞出清脆的金属咬合声……</span><br>"
			. "<span class=\"grey\">「手套和厚底鞋才是一身行头的灵魂！把这副液压锁扣死，就算手脚骨头断了它都能拽着你接着挥刀跑路！」</span><br>";
		$cmd = xmod_build_menu($itmn, '要将重装外骨骼拘束具锁在哪一处？（消耗250生命与250体力，附加灵魂绑定与磨合轻伤）', $opts);
		return true;
	}

	// ⑦A 「X」印折翼偏折镀层（身体/头部防具，赋予 B 伤害抹消，永久扣 30% mhp）
	if ($itm === '「X」印折翼偏折镀层') {
		if ($mhp < 550) {
			$log .= '你的最大生命上限不足550点，强行抽取心血张开折翼偏折膜会当场暴毙！<br>';
			return true;
		}
		$opts = array();
		foreach (array('arb', 'arh') as $slot) {
			if (!xmod_has_valid_armor($data, $slot)) {
				continue;
			}
			if (xmod_has_sk(${$slot . 'sk'}, 'B') || xmod_has_sk(${$slot . 'sk'}, 'b')) {
				continue;
			}
			$s_nm = htmlspecialchars(${$slot}, ENT_QUOTES, 'UTF-8');
			$opts['xm_' . $slot] = xmod_slot_label($slot) . "：{$s_nm}（效:{${$slot.'e'}} / 耐:{${$slot.'s'}}）";
		}
		if (empty($opts)) {
			$log .= '你尚未穿戴可镀膜的身体或头部防具，或目标防具已带有抹消类属性（不可与已有伤害/属性抹消叠加）。<br>';
			return true;
		}
		$est_loss = max(350, (int)ceil($mhp * 0.30));
		$log .= "<span class=\"spitem2\">薄如蝉翼的金属镀膜在掌心高频震颤，隐约映出一对残缺小翅膀的张力纹路……</span><br>"
			. "<span class=\"grey\">「刀砍上来自己会滑走！……贴完觉得少了一截命？那叫轻量化代价！」</span><br>";
		$cmd = xmod_build_menu($itmn, "要将【伤害抹消】偏折镀层焊死在哪件防具上？<br><span class=\"red\">警告：将永久扣除 {$est_loss} 点生命上限，且该防具防御减半、耐久压至≤35并附加【诅咒】！</span>", $opts);
		return true;
	}

	// ⑦B 「X」印噬魔虹彩涂装（手/足/身体防具，赋予 b 属性抹消，永久扣 35% msp + 20% att）
	if ($itm === '「X」印噬魔虹彩涂装') {
		if ($msp < 350) {
			$log .= '你的最大体力上限不足350点，扛不住噬魔虹彩涂装对精气的永久抽吸！<br>';
			return true;
		}
		$opts = array();
		foreach (array('ara', 'arf', 'arb') as $slot) {
			if (!xmod_has_valid_armor($data, $slot)) {
				continue;
			}
			if (xmod_has_sk(${$slot . 'sk'}, 'B') || xmod_has_sk(${$slot . 'sk'}, 'b')) {
				continue;
			}
			$s_nm = htmlspecialchars(${$slot}, ENT_QUOTES, 'UTF-8');
			$opts['xm_' . $slot] = xmod_slot_label($slot) . "：{$s_nm}（效:{${$slot.'e'}} / 耐:{${$slot.'s'}}）";
		}
		if (empty($opts)) {
			$log .= '你尚未穿戴可涂装的手臂、腿部或身体防具，或目标防具已带有抹消类属性（不可与已有伤害/属性抹消叠加）。<br>';
			return true;
		}
		$est_msp_loss = max(220, (int)ceil($msp * 0.35));
		$est_att_loss = max(1, (int)ceil($att * 0.20));
		$log .= "<span class=\"spitem2\">罐中七彩流转的诡异漆料仿佛有生命般追逐着你的呼吸，连周围的空气都变得异常绝缘……</span><br>"
			. "<span class=\"grey\">「独家炼金绝缘镀层！火球雷电炸弹全给你吞成哑炮！千万别舔，把你精气吸干了我可不赔！」</span><br>";
		$cmd = xmod_build_menu($itmn, "要将【属性抹消】虹彩涂装刷在哪件防具上？<br><span class=\"red\">警告：将永久扣除 {$est_msp_loss} 点体力上限与 {$est_att_loss} 点基础攻击力，且该防具防御减半、耐久压至≤35并附加【诅咒】！</span>", $opts);
		return true;
	}

	// ⑧ 「X」印·魅魔的心核匣（仅身体防具 arb，赋予 Bb 双抹消 + 80 耐，腰斩 mhp/msp/def）
	if ($itm === '「X」印·魅魔的心核匣') {
		if (!xmod_has_valid_armor($data, 'arb')) {
			$log .= '你必须先穿戴一件真正的身体防具（非内衣、非背包），才能嵌合「X」印·魅魔的心核匣！<br>';
			return true;
		}
		if (xmod_has_sk($arbsk, 'B') && xmod_has_sk($arbsk, 'b')) {
			$log .= "你的<span class=\"yellow\">{$arb}</span>已经兼具伤害抹消与属性抹消了，没必要再把半条命卖给心核匣啦！<br>";
			return true;
		}
		if ($mhp < 600 || $msp < 400) {
			$log .= '你的体魄不足以承载心核匣寄生（至少需要最大生命≥600且最大体力≥400）！<br>';
			return true;
		}
		$s_nm = htmlspecialchars($arb, ENT_QUOTES, 'UTF-8');
		$opts = array(
			'xm_core' => "【签下卖身契】将心核匣嵌入「{$s_nm}」：赋予【伤害抹消+属性抹消】并重铸为80耐；永久腰斩50%生命上限、50%体力上限与50%基础防御，胸甲防御归1并诅咒焊死！",
		);
		$log .= "<span class=\"spitem2\">掌心的小铁盒传来清晰的、仿佛另一颗心脏般的搏动声，黑紫与七彩的纹路正顺着指尖向你胸口蔓延……</span><br>"
			. "<span class=\"grey\">「把这颗还在跳的小铁盒嵌进胸甲里，刀砍雷劈都伤不到你一根汗毛！……无非就是以后你半条命都归它管、稍微变脆那么亿点点嘛！」</span><br>";
		$cmd = xmod_build_menu($itmn, '确定要与这枚心核匣签下不可逆转的契约吗？', $opts);
		return true;
	}

	return false;
}

/**
 * 二级菜单提交执行入口（mode=item, usemode=xharmer_mod）
 */
function item_xharmer_mod_exec($choice, &$data = NULL)
{
	global $mode, $log, $nosta, $itmp, $itemspkinfo;
	if (!isset($data)) {
		global $pdata;
		$data = &$pdata;
	}
	extract($data, EXTR_REFS);

	if ($choice === '' || $choice === 'menu' || $choice === 'u') {
		$mode = 'command';
		return;
	}
	if ($hp <= 0) {
		$log .= '你已经死亡，无法使用道具。<br>';
		$mode = 'command';
		return;
	}
	if (!isset($itmp) || !is_numeric($itmp)) {
		$log .= '道具位置参数错误，请重新选择。<br>';
		$mode = 'command';
		return;
	}
	$itmn = (int)$itmp;
	if ($itmn < 0 || $itmn > 6) {
		$log .= '此道具不存在，请重新选择。<br>';
		$mode = 'command';
		return;
	}

	$itm = &${'itm' . $itmn};
	$itmk = &${'itmk' . $itmn};
	$itme = &${'itme' . $itmn};
	$itms = &${'itms' . $itmn};
	$itmsk = &${'itmsk' . $itmn};
	$itmpara_raw = &${'itmpara' . $itmn};

	if ((empty($itms) && $itms !== $nosta) || !xmod_is_item_name($itm)) {
		$log .= '强化道具选择错误或已耗尽，请重新选择。<br>';
		$mode = 'command';
		return;
	}
	if (function_exists('erc_can_electronic') && !erc_can_electronic($data, $itmk, $itm)) {
		$mode = 'command';
		return;
	}
	if (function_exists('ending_routes_enabled') && ending_routes_enabled()
		&& function_exists('severe_can_use_item') && !severe_can_use_item($data, $itmk, $itm)) {
		$mode = 'command';
		return;
	}
	if ($club == 21) {
		xmod_club21_reject($itm);
		$mode = 'command';
		return;
	}

	$used_name = $itm;
	$orig_ie = $itme;
	$orig_is = $itms;
	$orig_ik = $itmk;
	$orig_isk = $itmsk;

	// ① 「X」牌金属活性诱导膏
	if ($used_name === '「X」牌金属活性诱导膏') {
		if (!ctype_digit((string)$choice)) {
			$log .= '祭品选择无效。<br>';
			$mode = 'command';
			return;
		}
		$sac_slot = (int)$choice;
		if (!xmod_check_weapon_common($data) || !xmod_is_valid_sacrifice($data, $sac_slot, $itmn)) {
			$log .= '被吞噬的祭品或主武器状态无效，请重新选择。<br>';
			$mode = 'command';
			return;
		}
		$wpara = get_itmpara($weppara);
		if (!empty($wpara['x_fed'])) {
			$log .= '这把武器已经吞噬过一次金属祭品了！<br>';
			$mode = 'command';
			return;
		}
		if ($hp < 150 || $sp < 150) {
			$log .= '你的生命或体力不足150点，无法完成活性熔接！<br>';
			$mode = 'command';
			return;
		}

		$sac_name = ${'itm' . $sac_slot};
		$sac_e = (int)${'itme' . $sac_slot};
		$sac_s = ${'itms' . $sac_slot};
		$sac_sk = ${'itmsk' . $sac_slot};

		$eff_gain = min(650, max(1, (int)floor($sac_e * 0.85)));
		$wepe += $eff_gain;

		$dur_gain = 0;
		if ($weps !== $nosta && strpos($wepk, 'WG') !== 0 && strpos($wepk, 'WJ') !== 0 && strpos($wepk, 'WB') !== 0 && !xmod_has_sk($wepsk, 'o')) {
			$sac_s_num = ($sac_s === $nosta) ? 250 : (int)$sac_s;
			$dur_gain = min(150, max(1, (int)floor($sac_s_num * 0.60)));
			$weps += $dur_gain;
		}

		$elem_candidates = array();
		foreach (array('u', 'i', 'e', 'w', 'p', 'd') as $ek) {
			if (xmod_has_sk($sac_sk, $ek) && !xmod_has_sk($wepsk, $ek)) {
				$elem_candidates[] = $ek;
			}
		}
		$inherited_msg = '';
		if (!empty($elem_candidates)) {
			$picked_ek = $elem_candidates[array_rand($elem_candidates)];
			xmod_add_sk($wepsk, $picked_ek);
			$inherited_msg = "并从残骸中咀嚼继承了<span class=\"yellow\">【{$itemspkinfo[$picked_ek]}】</span>属性！";
		}

		$wpara['x_fed'] = 1;
		xmod_add_sk($wepsk, 'v');
		xmod_mark_modified($wep, $wepsk, false, '贪食的');
		xmod_append_lore($wpara, "「X」金属活性熔接：已吞噬「{$sac_name}」");
		$weppara = $wpara;

		include_once GAME_ROOT . './include/game/itemmain.func.php';
		destory_single_item($data, $sac_slot, 0);

		$hp = max(1, (int)floor($hp * 0.5));
		$sp = max(1, (int)floor($sp * 0.5));

		$dur_msg = $dur_gain > 0 ? "，耐久度提升了<span class=\"yellow\">{$dur_gain}</span>点（当前<span class=\"yellow\">{$weps}</span>）" : '';
		$log .= "<span class=\"tmagenta\">银紫色的膏体发出令人牙酸的咀嚼声，转眼间便将「{$sac_name}」熔解并彻底揉进了你的武器里！</span><br>"
			. "<span class=\"yellow\">{$wep}</span>的效果值提升了<span class=\"yellow\">{$eff_gain}</span>点（当前<span class=\"yellow\">{$wepe}</span>）{$dur_msg}，附加了<span class=\"yellow\">【灵魂绑定】</span>{$inherited_msg}<br>"
			. "活化金属顺着掌心猛抽一口精血，你的当前生命与体力各流失了一半（剩余生命<span class=\"red\">{$hp}</span> / 体力<span class=\"red\">{$sp}</span>）！<br>";

		xmod_consume_mod_item($data, $itmn, $used_name, $wep);
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ④ 心尾烙印·契约回火钳
	if ($used_name === '心尾烙印·契约回火钳') {
		if (!in_array($choice, array('burn', 'eternal'), true) || !xmod_check_weapon_common($data)) {
			$log .= '契约选项或武器状态无效。<br>';
			$mode = 'command';
			return;
		}
		if ($weps === $nosta || (int)$weps <= 0 || (int)$wepe <= 0) {
			$log .= '目标武器不满足契约回火条件。<br>';
			$mode = 'command';
			return;
		}
		$wpara = get_itmpara($weppara);
		if (!empty($wpara['x_temper'])) {
			$log .= '这把武器已经签过一次回火契约了！<br>';
			$mode = 'command';
			return;
		}

		if ($choice === 'burn') {
			if ((int)$weps < 10) {
				$log .= '武器剩余耐久不足10点，撑不住【燃尽契约】的高温淬炼！<br>';
				$mode = 'command';
				return;
			}
			if (strpos($wepk, 'WB') === 0) {
				$log .= '弓类武器的弦身结构无法承受【燃尽契约】！<br>';
				$mode = 'command';
				return;
			}
			if ($hp <= 250 || $sp < 200) {
				$log .= '签订【燃尽契约】需要当前生命大于250点且当前体力不少于200点！<br>';
				$mode = 'command';
				return;
			}

			$weps = min(12, max(1, (int)floor((int)$weps * 0.20)));
			$eff_gain = min(900, max(1, (int)round($wepe * 0.75)));
			$wepe += $eff_gain;
			$wpara['AddDamageRaw'] = (isset($wpara['AddDamageRaw']) ? (int)$wpara['AddDamageRaw'] : 0) + 280;
			$wpara['x_temper'] = 'burn';
			xmod_add_sk($wepsk, 'c');
			xmod_add_sk($wepsk, 'v');
			$gun_lock_msg = '';
			if (strpos($wepk, 'WG') === 0 || strpos($wepk, 'WJ') === 0) {
				xmod_add_sk($wepsk, 'o');
				$gun_lock_msg = '供弹机构被高温彻底焊死（附加【一发】）；';
			}
			xmod_mark_modified($wep, $wepsk, true);
			xmod_append_lore($wpara, '「X」心尾烙印·燃尽契约：刹那芳华决战重铸');
			$weppara = $wpara;

			$hp = max(1, $hp - 250);
			$sp = max(0, $sp - 200);

			$log .= "<span class=\"tmagenta\">赤红的心尾回火钳狠狠夹住武器，刺耳的白烟中烙下了一枚滚烫的桃心箭尾印记！</span><br>"
				. "【燃尽契约】成立！<span class=\"yellow\">{$wep}</span>的耐久骤降至<span class=\"red\">{$weps}</span>，换来效果值暴涨<span class=\"yellow\">{$eff_gain}</span>点（当前<span class=\"yellow\">{$wepe}</span>）、最终伤害固定增加<span class=\"yellow\">280</span>点，并获得<span class=\"yellow\">【{$itemspkinfo['c']}】</span>！<br>"
				. "{$gun_lock_msg}灼热烙印抽走了你<span class=\"red\">250</span>点生命与<span class=\"red\">200</span>点体力。<br>";
		} else {
			if ($hp < 300 || $sp < 300) {
				$log .= '签订【永伴契约】需要当前生命与当前体力均不少于300点作为淬火祭礼！<br>';
				$mode = 'command';
				return;
			}
			$wepe = max(1, (int)floor($wepe * 0.80));
			$is_gun = (strpos($wepk, 'WG') === 0 || strpos($wepk, 'WJ') === 0);
			$is_bow = (strpos($wepk, 'WB') === 0);
			if ($is_gun) {
				$weps = 150;
				xmod_add_sk($wepsk, 'o');
				$dur_desc = '弹仓被灌入<span class="yellow">150</span>发超量契约魔弹并焊死供弹口（附加【一发】）';
			} elseif ($is_bow) {
				$weps = 150;
				$dur_desc = '箭袋内凝结出<span class="yellow">150</span>支契约魔矢';
			} else {
				$weps = $nosta;
				$dur_desc = '耐久度化作了永不枯竭的<span class="yellow">∞</span>';
			}
			$wpara['x_temper'] = 'eternal';
			xmod_add_sk($wepsk, 'v');
			xmod_mark_modified($wep, $wepsk, true);
			xmod_append_lore($wpara, '「X」心尾烙印·永伴契约：献血淬火永续相伴');
			$weppara = $wpara;

			$hp = 1;
			$sp = 0;
			xmod_add_inf($inf, 'u');

			$log .= "<span class=\"tmagenta\">你将鲜血与精力尽数倾注进心尾回火钳，暗紫色的魔火顺着钳口彻底浸透了整把武器！</span><br>"
				. "【永伴契约】成立！<span class=\"yellow\">{$wep}</span>的效果值收敛至<span class=\"yellow\">{$wepe}</span>，{$dur_desc}，并附加了<span class=\"yellow\">【灵魂绑定】</span>！<br>"
				. "<span class=\"red\">淬火在一瞬间榨干了你当下的气血——你的当前生命降至 1 点、体力归零，双手留下了火辣辣的烧伤！快找补给缓一口气吧！</span><br>";
		}

		xmod_consume_mod_item($data, $itmn, $used_name, $wep);
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ⑤ 「X」牌超韧记忆金属内衬
	if ($used_name === '「X」牌超韧记忆金属内衬') {
		if (!in_array($choice, array('arb', 'arh', 'ara', 'arf'), true) || !xmod_has_valid_armor($data, $choice)) {
			$log .= '目标防具状态无效，请重新选择。<br>';
			$mode = 'command';
			return;
		}
		$apara = get_itmpara(${$choice . 'para'});
		if (!empty($apara['x_liner'])) {
			$log .= '这件防具已经贴过超韧记忆金属内衬了！<br>';
			$mode = 'command';
			return;
		}
		if ($hp <= 200) {
			$log .= '你的当前生命不足201点，无法承受内衬咬合！<br>';
			$mode = 'command';
			return;
		}

		${$choice . 'e'} += 550;
		if (${$choice . 's'} !== $nosta) {
			${$choice . 's'} += 150;
		}
		$apara['DecreaseDamagePercentage'] = (isset($apara['DecreaseDamagePercentage']) ? (int)$apara['DecreaseDamagePercentage'] : 0) + 18;
		$apara['x_liner'] = 1;

		$res_pool = array();
		foreach (array('U', 'I', 'E', 'W', 'q') as $rk) {
			if (!xmod_has_sk(${$choice . 'sk'}, $rk)) {
				$res_pool[] = $rk;
			}
		}
		$res_msg = '';
		if (!empty($res_pool)) {
			$picked_rk = $res_pool[array_rand($res_pool)];
			xmod_add_sk(${$choice . 'sk'}, $picked_rk);
			$res_msg = "，额外激发了<span class=\"yellow\">【{$itemspkinfo[$picked_rk]}】</span>抗性";
		}

		xmod_add_sk(${$choice . 'sk'}, 'V');
		xmod_mark_modified(${$choice}, ${$choice . 'sk'}, true);
		xmod_append_lore($apara, '「X」超韧记忆金属内衬：贴肉焊死重装化');
		${$choice . 'para'} = $apara;
		xmod_apply_cursed_skill($data);

		$hp = max(1, $hp - 200);

		$log .= "<span class=\"tmagenta\">记忆金属内衬「咔嚓」一声死死咬合在你的防具内壁上，冰冷的金属丝严丝合缝地贴紧了皮肤！</span><br>"
			. "<span class=\"yellow\">{${$choice}}</span>的防御力提升了<span class=\"yellow\">550</span>点（当前<span class=\"yellow\">{${$choice.'e'}}</span>），耐久度<span class=\"yellow\">{${$choice.'s'}}</span>，最终受伤减少提升至<span class=\"yellow\">{$apara['DecreaseDamagePercentage']}%</span>{$res_msg}！<br>"
			. "<span class=\"red\">倒刺内衬已与你融为一体（附加【诅咒】不可卸下），剧烈的勒痛夺走了你 200 点当前生命！</span><br>";

		xmod_consume_mod_item($data, $itmn, $used_name, ${$choice});
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ⑥ 「X」式重装手足拘束具
	if ($used_name === '「X」式重装手足拘束具') {
		if (!in_array($choice, array('ara', 'arf'), true) || !xmod_has_valid_armor($data, $choice)) {
			$log .= '目标手足防具状态无效，请重新选择。<br>';
			$mode = 'command';
			return;
		}
		$apara = get_itmpara(${$choice . 'para'});
		if (!empty($apara['x_shackle'])) {
			$log .= '这件手足防具已经加装过外骨骼拘束具了！<br>';
			$mode = 'command';
			return;
		}
		if ($hp <= 250 || $sp < 250) {
			$log .= '锁死重装手足拘束具需要当前生命大于250点且当前体力不少于250点！<br>';
			$mode = 'command';
			return;
		}

		${$choice . 'e'} += 500;
		if (${$choice . 's'} !== $nosta) {
			${$choice . 's'} += 120;
		}
		$apara['DecreaseDamageRaw'] = (isset($apara['DecreaseDamageRaw']) ? (int)$apara['DecreaseDamageRaw'] : 0) + 200;
		$apara['x_shackle'] = 1;

		if ($choice === 'ara') {
			xmod_add_sk($arask, 'H');
			$apara['IgnoreUpperBreak'] = 1;
			xmod_add_inf($inf, 'a');
			xmod_append_lore($apara, '「X」重装外骨骼笼手：液压锁扣代偿上肢');
			$limb_msg = "获得<span class=\"yellow\">【{$itemspkinfo['H']}】</span>，且穿戴期间可强行代偿并无效化<span class=\"lime\">「上肢破坏」</span>负面效果！磨合冲击令你的手腕受了轻伤。";
		} else {
			xmod_add_sk($arfsk, 'm');
			xmod_add_sk($arfsk, 'M');
			$apara['IgnoreLowerBreak'] = 1;
			xmod_add_inf($inf, 'f');
			xmod_append_lore($apara, '「X」厚底玛丽珍战靴：液压锁扣代偿下肢');
			$limb_msg = "获得<span class=\"yellow\">【{$itemspkinfo['m']}+{$itemspkinfo['M']}】</span>，且穿戴期间可强行代偿并无效化<span class=\"lime\">「下肢破坏」</span>负面效果！磨合冲击令你的双足受了轻伤。";
		}

		xmod_add_sk(${$choice . 'sk'}, 'v');
		xmod_mark_modified(${$choice}, ${$choice . 'sk'}, true);
		${$choice . 'para'} = $apara;

		$hp = max(1, $hp - 250);
		$sp = max(0, $sp - 250);

		$log .= "<span class=\"tmagenta\">液压拘束锁扣轰然合拢，厚重的黑紫合金支架紧紧箍住了你的" . xmod_slot_label($choice) . "！</span><br>"
			. "<span class=\"yellow\">{${$choice}}</span>的防御力提升了<span class=\"yellow\">500</span>点（当前<span class=\"yellow\">{${$choice.'e'}}</span>），耐久度<span class=\"yellow\">{${$choice.'s'}}</span>，最终受伤固定减少增加<span class=\"yellow\">200</span>点！<br>"
			. "{$limb_msg}（消耗了<span class=\"red\">250</span>点生命与<span class=\"red\">250</span>点体力）<br>";

		xmod_consume_mod_item($data, $itmn, $used_name, ${$choice});
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ⑦A 「X」印折翼偏折镀层
	if ($used_name === '「X」印折翼偏折镀层') {
		if (!in_array($choice, array('arb', 'arh'), true) || !xmod_has_valid_armor($data, $choice)) {
			$log .= '目标防具状态无效，请重新选择。<br>';
			$mode = 'command';
			return;
		}
		if (xmod_has_sk(${$choice . 'sk'}, 'B') || xmod_has_sk(${$choice . 'sk'}, 'b')) {
			$log .= '目标防具已带有抹消类属性，无法再覆写折翼偏折镀层！<br>';
			$mode = 'command';
			return;
		}
		if ($mhp < 550) {
			$log .= '你的最大生命上限不足550点，无法承受镀层抽髓！<br>';
			$mode = 'command';
			return;
		}

		$mhp_loss = max(350, (int)ceil($mhp * 0.30));
		$mhp = max(1, $mhp - $mhp_loss);
		if ($hp > $mhp) {
			$hp = $mhp;
		}

		${$choice . 'e'} = max(1, (int)floor(${$choice . 'e'} * 0.5));
		if (${$choice . 's'} === $nosta) {
			${$choice . 's'} = 25;
		} else {
			${$choice . 's'} = min(35, max(1, (int)floor((int)${$choice . 's'} * 0.40)));
		}

		$apara = get_itmpara(${$choice . 'para'});
		$apara['x_null'] = 'B';
		xmod_add_sk(${$choice . 'sk'}, 'B');
		xmod_add_sk(${$choice . 'sk'}, 'V');
		xmod_mark_modified(${$choice}, ${$choice . 'sk'}, true);
		xmod_append_lore($apara, '「X」折翼偏折镀层：献祭心血张开物理偏折翼膜');
		${$choice . 'para'} = $apara;
		xmod_apply_cursed_skill($data);
		xmod_trigger_dialogue($data, 'xharmer_wing_coat');

		$log .= "<span class=\"spitem2\">折翼偏折镀层刺入你的血管汲取心血，将防具表层重塑为一层近乎透明的偏折薄膜！</span><br>"
			. "<span class=\"yellow\">{${$choice}}</span>获得了顶级防御属性<span class=\"yellow\">【{$itemspkinfo['B']}】</span>！作为薄膜脆化代价，其防御力降至<span class=\"red\">{${$choice.'e'}}</span>、耐久度压缩至<span class=\"red\">{${$choice.'s'}}</span>，并被<span class=\"red\">【诅咒】</span>焊死！<br>"
			. "<span class=\"red\">你的最大生命上限永久减少了 {$mhp_loss} 点（当前上限 {$mhp}）！</span><br>";

		xmod_consume_mod_item($data, $itmn, $used_name, ${$choice});
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ⑦B 「X」印噬魔虹彩涂装
	if ($used_name === '「X」印噬魔虹彩涂装') {
		if (!in_array($choice, array('ara', 'arf', 'arb'), true) || !xmod_has_valid_armor($data, $choice)) {
			$log .= '目标防具状态无效，请重新选择。<br>';
			$mode = 'command';
			return;
		}
		if (xmod_has_sk(${$choice . 'sk'}, 'B') || xmod_has_sk(${$choice . 'sk'}, 'b')) {
			$log .= '目标防具已带有抹消类属性，无法再刷涂噬魔虹彩涂装！<br>';
			$mode = 'command';
			return;
		}
		if ($msp < 350) {
			$log .= '你的最大体力上限不足350点，无法承受虹彩涂装的抽吸！<br>';
			$mode = 'command';
			return;
		}

		$msp_loss = max(220, (int)ceil($msp * 0.35));
		$msp = max(1, $msp - $msp_loss);
		if ($sp > $msp) {
			$sp = $msp;
		}
		$att_loss = max(1, (int)ceil($att * 0.20));
		$att = max(1, $att - $att_loss);

		${$choice . 'e'} = max(1, (int)floor(${$choice . 'e'} * 0.5));
		if (${$choice . 's'} === $nosta) {
			${$choice . 's'} = 25;
		} else {
			${$choice . 's'} = min(35, max(1, (int)floor((int)${$choice . 's'} * 0.40)));
		}

		$apara = get_itmpara(${$choice . 'para'});
		$apara['x_null'] = 'b';
		xmod_add_sk(${$choice . 'sk'}, 'b');
		xmod_add_sk(${$choice . 'sk'}, 'V');
		xmod_mark_modified(${$choice}, ${$choice . 'sk'}, true);
		xmod_append_lore($apara, '「X」噬魔虹彩涂装：榨取精气吞没异种元素');
		${$choice . 'para'} = $apara;
		xmod_apply_cursed_skill($data);
		xmod_trigger_dialogue($data, 'xharmer_iris_coat');

		$log .= "<span class=\"spitem2\">七彩反光漆在防具表面贪婪地蔓延开来，你的精气与杀意被涂层一并吞噬殆尽！</span><br>"
			. "<span class=\"yellow\">{${$choice}}</span>获得了顶级防御属性<span class=\"yellow\">【{$itemspkinfo['b']}】</span>！作为脆化代价，其防御力降至<span class=\"red\">{${$choice.'e'}}</span>、耐久度压缩至<span class=\"red\">{${$choice.'s'}}</span>，并被<span class=\"red\">【诅咒】</span>焊死！<br>"
			. "<span class=\"red\">你的最大体力上限永久减少了 {$msp_loss} 点（当前上限 {$msp}），基础攻击力永久降低了 {$att_loss} 点！</span><br>";

		xmod_consume_mod_item($data, $itmn, $used_name, ${$choice});
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	// ⑧ 「X」印·魅魔的心核匣
	if ($used_name === '「X」印·魅魔的心核匣') {
		if ($choice !== 'core' || !xmod_has_valid_armor($data, 'arb')) {
			$log .= '契约确认失败或身体防具无效。<br>';
			$mode = 'command';
			return;
		}
		if (xmod_has_sk($arbsk, 'B') && xmod_has_sk($arbsk, 'b')) {
			$log .= '你的身体防具已同时具备双抹消属性！<br>';
			$mode = 'command';
			return;
		}
		if ($mhp < 600 || $msp < 400) {
			$log .= '你的体魄不足以承载心核匣寄生（需最大生命≥600且最大体力≥400）！<br>';
			$mode = 'command';
			return;
		}

		$mhp_loss = (int)ceil($mhp * 0.50);
		$msp_loss = (int)ceil($msp * 0.50);
		$def_loss = (int)ceil($def * 0.50);
		$mhp = max(1, $mhp - $mhp_loss);
		$msp = max(1, $msp - $msp_loss);
		$def = max(0, $def - $def_loss);
		if ($hp > $mhp) {
			$hp = $mhp;
		}
		if ($sp > $msp) {
			$sp = $msp;
		}

		$arbe = 1;
		$arbs = 80;
		$apara = get_itmpara($arbpara);
		$apara['x_null'] = 'Bb';
		xmod_add_sk($arbsk, 'B');
		xmod_add_sk($arbsk, 'b');
		xmod_add_sk($arbsk, 'V');
		xmod_mark_modified($arb, $arbsk, true);
		xmod_append_lore($apara, '「X」魅魔的心核匣：半身归魔·双抹蝉衣终极契约');
		$arbpara = $apara;
		xmod_apply_cursed_skill($data);

		include_once GAME_ROOT . './include/state.func.php';
		if (function_exists('rpup_rev')) {
			rpup_rev($data, 666);
		} else {
			$rp += 666;
		}
		xmod_trigger_dialogue($data, 'xharmer_corebox');

		$log .= "<span class=\"spitem2\">搏动的铁匣破开胸甲嵌入正中，黑紫与虹彩交织的力场瞬间取代了原本笨重的甲片！</span><br>"
			. "<span class=\"yellow\">{$arb}</span>同时获得了<span class=\"yellow\">【{$itemspkinfo['B']}+{$itemspkinfo['b']}】</span>双顶级属性，耐久度重铸为<span class=\"yellow\">80</span>点！<br>"
			. "<span class=\"red\">【半身归魔】你的生命上限永久减半（-{$mhp_loss}，剩 {$mhp}）、体力上限永久减半（-{$msp_loss}，剩 {$msp}）、基础防御永久减半（-{$def_loss}），胸甲面板归 1 并被【诅咒】焊死在胸口！</span><br>";

		xmod_consume_mod_item($data, $itmn, $used_name, $arb);
		include_once GAME_ROOT . './include/game/achievement.func.php';
		check_item_achievement_rev($name, $used_name, $orig_ie, $orig_is, $orig_ik, $orig_isk);
		$mode = 'command';
		return;
	}

	$mode = 'command';
}
