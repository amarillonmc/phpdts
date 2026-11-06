<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle cure/medicine items
function item_cure($itmn, &$data) {
	global $log, $exdmginf, $ex_inf, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	$ck=substr($itmk,1,1);
	if($ck == 'a'){
		$flag=false;
		$log .= "服用了<span class=\"red\">$itm</span>。<br>";
		foreach ($ex_inf as $value) {
			if(strpos($inf, $value) !== false){
				$inf = str_replace($value, '', $inf);
				$log .= "{$exdmginf[$value]}状态解除了。<br>";
				$flag=true;
			}
		}
		if(!$flag){
			$log .= '但是什么也没发生。<br>';
		}
	}elseif(in_array($ck,$ex_inf)){
		if(strpos($inf, $ck) !== false){
			$inf = str_replace($ck, '', $inf);
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf[$ck]}状态解除了。<br>";
		}else{
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
	}elseif ($ck == 'x'){
		$inf = "puiewhbaf";
		$log .= "服用了<span class=\"red\">$itm</span>，<br>";
		$log .= "但是，假冒伪劣的<span class=\"red\">$itm</span>导致你{$exdmginf['p']}了！<br>";
		$log .= "假冒伪劣的<span class=\"red\">$itm</span>导致你{$exdmginf['u']}了！<br>";
		$log .= "假冒伪劣的<span class=\"red\">$itm</span>导致你{$exdmginf['i']}了！<br>";
		$log .= "假冒伪劣的<span class=\"red\">$itm</span>导致你{$exdmginf['e']}了！<br>";
		$log .= "而且，假冒伪劣的<span class=\"red\">$itm</span>还导致你{$exdmginf['w']}了！<br>";
		$log .= "你遍体鳞伤地站了起来。<br>";
		$log .= "真是大快人心啊！<br>";
	}else{
		$log .= "服用了<span class=\"red\">$itm</span>……发生了什么？<br>";
	}

	if(strpos($inf,'p') === false) unset($clbpara['c25_poison_owner']);
	if ($itms != $nosta) {
		$itms --;
	}
	/*if (strpos ( $itm, '烧伤药剂' ) === 0) {
		if (strpos ( $inf, 'u' ) !== false) {
			$inf = str_replace ( 'u', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['u']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	} elseif (strpos ( $itm, '麻痹药剂' ) === 0) {
		if (strpos ( $inf, 'e' ) !== false) {
			$inf = str_replace ( 'e', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['e']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	
	} elseif (strpos ( $itm, '解冻药水' ) === 0) {
		if (strpos ( $inf, 'i' ) !== false) {
			$inf = str_replace ( 'i', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['i']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	
	} elseif (strpos ( $itm, '解毒剂' ) === 0) {
		if (strpos ( $inf, 'p' ) !== false) {
			$inf = str_replace ( 'p', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['p']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	
	} elseif (strpos ( $itm, '清醒药剂' ) === 0) {
		if (strpos ( $inf, 'w' ) !== false) {
			$inf = str_replace ( 'w', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['w']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	
	} elseif (strpos ( $itm, '全恢复药剂' ) === 0) {
		if (strpos ( $inf, 'w' ) !== false) {
			$inf = str_replace ( 'w', '', $inf );
			$log .= "服用了<span class=\"red\">$itm</span>，{$exdmginf['w']}状态解除了。<br>";
		} else {
			$log .= "服用了<span class=\"red\">$itm</span>，但是什么效果也没有。<br>";
		}
		$itms --;
	
	} else {
		$log .= "服用了<span class=\"red\">$itm</span>……发生了什么？<br>";
		$itms --;
	}*/
	if ($itms <= 0) {
		$log .= "<span class=\"red\">$itm</span>用光了。<br>";
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
	}
}
