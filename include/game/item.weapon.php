<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}
include_once GAME_ROOT.'./include/game/club25.items.php';

// Handle weapons and equipment
function item_weapon($itmn, &$data) {
	global $log, $mode, $nosta;
	extract($data, EXTR_REFS);

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	$itmpara = & get_itmpara(${'itmpara' . $itmn});

	if(strpos($itmk, 'W') === 0) {
		$eqp = 'wep';
		$noeqp = 'WN';
	} elseif(strpos($itmk, 'DB') === 0) {
		$eqp = 'arb';
		$noeqp = 'DN';
	} elseif(strpos($itmk, 'DH') === 0) {
		$eqp = 'arh';
		$noeqp = '';
	} elseif(strpos($itmk, 'DA') === 0) {
		$eqp = 'ara';
		$noeqp = '';
	} elseif(strpos($itmk, 'DF') === 0) {
		$eqp = 'arf';
		$noeqp = '';
	} elseif(strpos($itmk, 'A') === 0) {
		$eqp = 'art';
		$noeqp = '';
	} elseif(strpos($itmk, 'ss') === 0) {
		$eqp = 'art';
		$noeqp = '';
	} elseif(strpos($itmk, 'XX') === 0) {
		$eqp = 'art';
		$noeqp = '';
	} elseif(strpos($itmk, 'XY') === 0) {
		$eqp = 'art';
		$noeqp = '';
	}

	//global ${$eqp}, ${$eqp.'k'}, ${$eqp.'e'}, ${$eqp.'s'}, ${$eqp.'sk'};
	//global $artk;
	if((($artk=='XX')||($artk=='XY'))&&($eqp == 'art')){
		$log .= '你的饰品不能替换！<br>';
		$mode = 'command';
		return;
	}
	# 诅咒装备不能主动卸下
	if(in_array('V',get_itmsk_array(${$eqp.'sk'})))
	{
		$log .= "你尝试着将{$$eqp}替换下来……但它就像长在了你身上一样，纹丝不动！<br>";
		$mode = 'command';
		return;
	}
	# 主动装备诅咒装备时，会变得不幸！
	if(in_array('V',get_itmsk_array($itmsk)))
	{
		$log .= "<span class=\"grey\">你感觉自己要倒大霉了……</span><br>";
		getclubskill('inf_cursed',$clbpara);
	}

	//PORT
	if(strpos($itmsk,'^')!==false){
		//global $itmnumlimit;
		$itmnumlimit = $itme>=$itms ? $itms : $itme;
	}
	if (($noeqp && strpos(${$eqp.'k'}, $noeqp) === 0) || (empty(${$eqp.'s'}) && ${$eqp.'s'} !== $nosta)) {

		// 装备道具时，进行单次套装检测
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		reload_single_set_item($data,$eqp,$itm,1);

		${$eqp} = $itm;
		${$eqp.'k'} = $itmk;
		${$eqp.'e'} = $itme;
		${$eqp.'s'} = $itms;
		${$eqp.'sk'} = $itmsk;
		${$eqp.'para'} = $itmpara;
		$log .= "装备了<span class=\"yellow\">$itm</span>。<br>";
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
		// 清除背包中的itmpara字段，但保留itmpara变量中的值
		${'itmpara' . $itmn} = '';
	} else {

		// 替换装备时，进行单次套装检测
		// 先检测目前穿的装备
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		reload_single_set_item($data,$eqp,${$eqp});
		// 再检测要替换的装备，类型为1，表示装备
		reload_single_set_item($data,$eqp,$itm,1);

		$itmt = ${$eqp};
		$itmkt = ${$eqp.'k'};
		$itmet = ${$eqp.'e'};
		$itmst = ${$eqp.'s'};
		$itmskt = ${$eqp.'sk'};
		$itmparat = ${$eqp.'para'};
		${$eqp} = $itm;
		${$eqp.'k'} = $itmk;
		${$eqp.'e'} = $itme;
		${$eqp.'s'} = $itms;
		${$eqp.'sk'} = $itmsk;
		${$eqp.'para'} = $itmpara;
		$itm = $itmt;
		$itmk = $itmkt;
		$itme = $itmet;
		$itms = $itmst;
		$itmsk = $itmskt;
		$itmpara = $itmparat;
		// 将背包中的itmpara字段设置为卸下装备的itmpara值
		${'itmpara' . $itmn} = $itmparat;
		$log .= "卸下了<span class=\"red\">$itm</span>，装备了<span class=\"yellow\">{${$eqp}}</span>。<br>";
	}
	club25_item_trip($data,$eqp);
}
