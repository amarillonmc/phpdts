<?php
if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

//以道具名反查mixinfo数据
//tp & 1 以原料反查，tp & 2 以产物反查
//返回mixinfo里的单个array
function smartmix_find_recipe($itm, $tp=0)
{
	include_once GAME_ROOT.'./include/game/itemmix.func.php';
	$mix_res = array();		
	$itm = htmlspecialchars_decode(itemmix_name_proc($itm));
	$mixinfo = get_mixinfo();
	foreach ($mixinfo as $ma)
	{
		$ma['type'] = 'normal';
		//隐藏合成是无法查到的
		if(($tp & 1 && in_array($itm, $ma['stuff']) && $ma['class']!='hidden') || ($tp & 2 && $itm == $ma['result'][0])){
			$mix_res[] = $ma;
		}
	}
	return $mix_res;
}

//检查玩家包裹，返回可合成的道具列表
function smartmix_check_available($data)
{
	include_once GAME_ROOT.'./include/game/itemmix.func.php';
	extract($data);
	//itms为零的道具不参与判断
	$packn = array();
	for($i=1;$i<=6;$i++){
		if(!empty(${'itms'.$i})){
			$packn[] = $i;
			//$packname[] = \itemmix\itemmix_name_proc(${'itm'.$i});
		}
	}
	//生成道具序号的全组合
	$fc = full_combination($packn, 2);
	
	//所有的组合全部判断一遍是否可以合成，最简单粗暴和兼容
	$mix_available = $mix_overlay_available = $mix_sync_available = array();
	foreach($fc as $fcval){

		$mix_res = itemmix_get_result($fcval,$data);
		if($mix_res){
			//$mix_res['type'] = 'normal';
			$mix_available[] = $mix_res;
		}
	}
	foreach($fc as $fval){
		$mix_overlay_res = itemmix_overlay_check($fval);
		if($mix_overlay_res){
			foreach($mix_overlay_res as $mkey => $mval){
				//$mval['type'] = 'overlay';
				if(!isset($mix_overlay_available[$mkey])){
					$mix_overlay_available[$mkey] = array($mval);
				}else{
					$mix_overlay_available[$mkey][] = $mval;
				}
			}
		}
		$mix_sync_res = itemmix_sync_check($fval);
		if($mix_sync_res){
			foreach($mix_sync_res as $mkey => $mval){
				//$mval['type'] = 'sync';
				if(!isset($mix_sync_available[$mkey])){
					$mix_sync_available[$mkey] = array($mval);
				}else{
					$mix_sync_available[$mkey][] = $mval;
				}
			}
		}
	}
	return array($mix_available,$mix_overlay_available,$mix_sync_available);
}

function init_itemmix_tips($itemindex='',&$data=NULL)
{
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	$mix_type = Array('normal' => '通常','sync' => '同调', 'overlay' => '超量');
	$mhint = ''; $smhint = '';
	if(!empty($itemindex))
	{
		$mix_res = smartmix_find_recipe($itemindex, 1 + 2);				
		if($mix_res){
			$smhint .= '<span class="blueseed b">'.$itemindex.'</span>涉及的合成公式：<br><ul>';
			foreach($mix_res as $mval){
				if(!isset($mval['type']) || $mval['type'] == 'normal'){
					foreach($mval['stuff'] as $key => $ms){
						if($key == 0) $smhint .= '<li>';
						if($ms == $itemindex) $smhint .= parse_smartmix_recipelink($ms).' + ';
						else $smhint .= parse_smartmix_recipelink($ms,'','grey').' + ';
					}
					$smhint = substr($smhint,0,-3);
				}
				$mr = $mval['result'][0];
				$smhint .= ' → '.parse_smartmix_recipelink($mr, parse_itemmix_resultshow($mval['result']),'grey').'</li>';
			}
			$smhint .= "</ul>";
		}
		else 
		{
			$smhint .= '没有找到<span class="blueseed b">'.$itemindex.'</span>的相关合成公式<span class="grey">（不会显示隐藏公式）</span>';
		}
		return $smhint;
	}
	list($mix_available,$mix_overlay_available,$mix_sync_available) = smartmix_check_available($data);
	if(empty($mix_available) && empty($mix_overlay_available) && empty($mix_sync_available)){
		$mhint .= '';
	}else{
		$mhint .= '<span class="blueseed b">可合成</span>：<br>';
		$shown_list = array();
		foreach($mix_available as $mlist){//第一层：不同配方
			$mstuff = $mresult = '';
			$o_type = '';
			foreach ($mlist as $mval){//第二层：不同结果
				if(!empty($o_type) && $o_type != $mval['type']) {//换类型时把上一合成类别显示，并且清空显示的配方和结果列表
					$mtstr = '';
					if(isset($mix_type[$o_type])) $mtstr = $mix_type[$o_type];
					$show_str = '<span>'.$mstuff.'</span>可'.$mtstr.'合成：<ul>'.$mresult.'</ul><br>';
					if(!in_array($show_str, $shown_list)){
						$shown_list[] = $show_str;
						$mhint .= $show_str;
					}
					$mstuff = $mresult = '';
				}
				$o_type = $mval['type'];
				if(!$mstuff) {//配方只显示1次					
					sort($mval['stuff']);			
					foreach($mval['stuff'] as $ms){
						$mstuff .= parse_smartmix_recipelink($ms).' + ';
					}
					$mstuff = substr($mstuff,0,-3);
				}
				$mresult .= '<li>'.parse_smartmix_recipelink($mval['result'][0], parse_itemmix_resultshow($mval['result']), 'yellow').'</li>';
			}
			$mtstr = '';
			if(isset($mix_type[$o_type])) $mtstr = $mix_type[$o_type];
			$show_str = '<span class="b">'.$mstuff.'</span>可'.$mtstr.'合成：<ul>'.$mresult.'</ul>';
			if(!in_array($show_str, $shown_list)){
				$shown_list[] = $show_str;
				$mhint .= $show_str;
			}
		}
	}
	for($i=0;$i<=6;$i++)
	{
		$itemindex = ${'itm'.$i};
		$mix_res = smartmix_find_recipe($itemindex, 1 + 2);				
		if($mix_res){
			$smhint .= '<span class="blueseed b">'.$itemindex.'</span>涉及的合成公式：<br><ul>';
			foreach($mix_res as $mval){
				if(!isset($mval['type']) || $mval['type'] == 'normal'){
					foreach($mval['stuff'] as $key => $ms){
						if($key == 0) $smhint .= '<li>';
						if($ms == ${'itm'.$i}) $smhint .= parse_smartmix_recipelink($ms).' + ';
						else $smhint .= parse_smartmix_recipelink($ms,'','grey').' + ';
					}
					$smhint = substr($smhint,0,-3);
				}
				$mr = $mval['result'][0];
				$smhint .= ' → '.parse_smartmix_recipelink($mr, parse_itemmix_resultshow($mval['result']),'grey').'</li>';
			}
			$smhint .= "</ul>";
		}
	}
	if(!empty($smhint)) 
	{
		//$smhint = "<span class=\"b\">素材不足：</span><br>".$smhint;
		$mhint .= $smhint;
	}
	$mhint .= '<br>';
	return $mhint;
}

function parse_smartmix_recipelink($itemindex, $stext = '', $sstyle = ''){
	$tt = get_item_place($itemindex);
	return "<span tooltip2=\"{$tt}\"><a ".($sstyle ? "class=\"{$sstyle}\" " : '')."onclick=\"$('itemindex').value='$itemindex';postCmd('maincmd','command.php');\">".($stext ? $stext : $itemindex).'</a></span>';
}
function parse_itemmix_resultshow($rarr){
	$ret = $rarr[0].'/'.parse_info_desc($rarr[1],'k','',0,'none').'/'.$rarr[2].'/'.$rarr[3];
	$itmskw = !empty($rarr[4]) ? parse_info_desc($rarr[4],'sk',$rarr[1],0,'none') : '';
	if($itmskw) $ret .= '/'.$itmskw;
	return $ret;
}

function get_npc_helpinfo($nlist,$tooltip=1)
{
	global $plsinfo,$hplsinfo,$gamecfg,$iteminfo,$clubinfo;
	global $posetips,$tactips,$poseinfo,$tacinfo;
	//登记非功能性地点信息时合并隐藏地点
	foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;

	$tnlist = $nlist;
	foreach ($tnlist as $i => $npcs)
	{
		if(!empty($npcs)) 
		{
			foreach(Array('sub','asub','esub') as $tsub)
			{
				if(!empty($npcs[$tsub]))
				{
					foreach($npcs[$tsub] as $n => $npc)
					{
						$snpc = array_merge($npcs,$npc);
						unset($snpc['sub']);unset($snpc['asub']);unset($snpc['esub']);
						foreach(Array('p','k','g','c','d','f') as $val)
						{
							if(isset($snpc['w'.$val]))
							{
								if(isset($snpc['skill']))
								{
									$snpc['skill'] .= '(?)';
								}
								else 
								{
									$snpc['skill'] = '不定';
								}
								break;
							}
						}
						if(!empty($snpc['gd']) && ($snpc['gd'] == 'm' || $snpc['gd'] == 'f'))
						{
							$snpc['gd'] = $snpc['gd']=='m' ? '男' : '女';
						}
						else 
						{
							$snpc['gd'] = '未知';
						}
						if(isset($snpc['pls']))
						{
							if($tsub == 'esub')
							{
								$snpc['pls'] = '原地';
							}
							else 
							{
								$snpc['pls'] = $snpc['pls']==99 ? '随机' : $plsinfo[$snpc['pls']];
							}
							
						}
						if(isset($snpc['pose']))$snpc['poseinfo'] = "<span tooltip=\"{$posetips[$snpc['pose']]}\">".$poseinfo[$snpc['pose']]."</span>";
						if(isset($snpc['tactic']))$snpc['tacinfo'] = "<span tooltip=\"{$tactips[$snpc['tactic']]}\">".$tacinfo[$snpc['tactic']]."</span>";
						if(isset($snpc['club'])) $snpc['club'] = $snpc['club']==99 ? '第一形态' : $clubinfo[$snpc['club']];
						//格式化装备、道具名
						foreach (Array('wep','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $value) 
						{
							if(strpos($value,'itm')!==false)
							{
								$k_value = str_replace('itm','itmk',$value);
								$e_value = str_replace('itm','itme',$value);
								$s_value = str_replace('itm','itms',$value);
								$sk_value = str_replace('itm','itmsk',$value);
							}
							else 
							{
								$e_value = $value.'e';
								$k_value = $value.'k';
								$s_value = $value.'s';
								$sk_value = $value.'sk';
							}
							if(!empty($snpc[$s_value]))
							{
								//添加tooltip效果
								if($tooltip)
								{
									if(!empty($snpc[$value])) $snpc[$value] = parse_info_desc($snpc[$value],'m');
									if(!empty($snpc[$sk_value])) $snpc[$sk_value.'_words'] = parse_info_desc($snpc[$sk_value],'sk',$snpc[$k_value]);
									if(!empty($snpc[$k_value])) $snpc[$k_value] = parse_info_desc($snpc[$k_value],'k');
								}
							}
						}
						$tnlist[$i][$tsub][$n] = $snpc;
						unset($snpc);
					}
				}
			}
		}
	}
	return $tnlist;
}

function get_item_place($which)
{
	global $plsinfo,$hplsinfo,$gamecfg;
	//登记非功能性地点信息时合并隐藏地点
	foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
	//获取某物品的获取方式，如刷新地点或商店是否有卖等
	$result="";
	$file = config('mapitem',$gamecfg);
	$itemlist = openfile($file);
	$in = sizeof($itemlist);
	for($i = 1; $i < $in; $i++) 
		if(!empty($itemlist[$i]) && strpos($itemlist[$i],',')!==false)
		{
			list($iarea,$imap,$inum,$iname,$ikind,$ieff,$ista,$iskind) = explode(',',$itemlist[$i]);
			if ($iname==$which)
			{
				if ($iarea==99) $result.="每禁"; else $result.="{$iarea}禁";
				if ($imap==99) $result.="全图随机"; else $result.="于{$plsinfo[$imap]}";
				$result.="刷新{$inum}个 \r";
			}
		}
	$file = config('shopitem',$gamecfg);
	$shoplist = openfile($file);
	foreach($shoplist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($kind,$num,$price,$area,$item)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="{$area}禁起在商店中出售({$price}元) \r";
			}
		}
	//include config('mixitem',$gamecfg);
	$mixinfo = get_mixinfo();
	foreach($mixinfo as $lst)
	{
		if ($lst['result'][0]==$which || $lst['result'][0]==$which.' ')
		{
			$result.="通过合成获取 \r";
			break;
		}
	}
	$file=config('synitem',$gamecfg);
	$synlist = openfile($file);
	foreach($synlist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="通过同调合成获取 \r";
				break;
			}
		}
	$file=config('overlay',$gamecfg);
	$ovllist = openfile($file);
	foreach($ovllist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="通过超量合成获取 \r";
				break;
			}
		}
	$file=config('present',$gamecfg);
	$prslist = openfile($file);
	foreach($prslist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="打开礼品盒时有概率获得 \r";
				break;
			}
		}
	$file=config('box',$gamecfg);
	$boxlist = openfile($file);
	foreach($boxlist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="打开游戏王卡包时有概率获得 \r";
				break;
			}
		}
	//打开福袋有几率获得
	/*foreach(Array('00','O1','WC','WD','WF','WG','WK','WP','') as $rnm)
	{
		if(file_exists(config('random'.$rnm,$gamecfg)))
		{
			include config('random'.$rnm,$gamecfg);
			foreach(Array('itmlow','itmmedium','itmhigh','antimeta') as $rlvl)
			{
				$item = explode("\r\n",$$rlvl);
				foreach($item as $oi)
				{
					list($in) = explode(',',$oi);
					if ($in==$which)
					{
						$result.="打开福袋时有概率获得 \r";
						break;
					}
				}
			}				
		}
	}*/
	//NPC掉落
	$result .= get_item_npcdrop($which);
	//头衔附赠
	global $title_valid;
	foreach($title_valid as $tv => $tvarr)
	{
		foreach($tvarr as $tvkey => $tvitm)
		{
			if(in_array($tvkey,array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6')) && ($which == $tvitm))
			{
				$result.="头衔【{$tv}】的入场奖励 \r";
				break;
			}
		}
	}
	if ($which == "悲叹之种") $result.="通过使用『灵魂宝石』强化物品失败获得 \r";
	return $result;
}

function get_item_npcdrop($which)
{
	include config('npc',1);
	include config('addnpc',1);
	include config('evonpc',1);

	$result = '';
	$nownpclist = $npcinfo;
	foreach($enpcinfo as $ekey => $enpcs)
	{
		foreach($enpcs as $sname => $enpc)
		{
			$nownpclist[$ekey]['sub'][$sname] = $enpc;
		}
	}
	foreach($anpcinfo as $akey => $anpcs)
	{
		foreach($anpcs['sub'] as $aid => $anpc)
		{
			$nownpclist[$akey]['sub']['a'.$aid] = $anpc;
		}
	}
	foreach($nownpclist as $ntype => $npcs)
	{
		foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $nipval)
		{
			if(!empty($npcs['sub'])) 
			{
				foreach($npcs['sub'] as $npc)
				{
					$npc = array_merge($npcs,$npc);
					if(isset($npc[$nipval]) && ($which == $npc[$nipval]))
					{
						$nresult ="击败{$npc['name']}后拾取 \r";
						if(strpos($result,$nresult)===false)
						{
							$result .= $nresult;
						}
					}
				}
			}
		}
	}

	return $result;
}
?>
