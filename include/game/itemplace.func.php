<?php
if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

function get_npc_helpinfo($nlist)
{
	global $plsinfo,$hplsinfo,$gamecfg,$iteminfo,$clubinfo;
	//登记非功能性地点信息时合并隐藏地点
	foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
	$tnlist = $nlist;
	foreach ($tnlist as $i => $npcs)
	{
		if(!empty($npcs)) 
		{
			foreach($npcs['sub'] as $n => $npc)
			{
				$snpc = array_merge($npcs,$npc);
				unset($snpc['sub']);
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
				if($snpc['gd'] == 'm' || $snpc['gd'] == 'f')
				{
					$snpc['gd'] = $snpc['gd']=='m' ? '男' : '女';
				}
				else 
				{
					$snpc['gd'] = '未知';
				}
				$snpc['pls'] = $snpc['pls']==99 ? '随机' : $plsinfo[$snpc['pls']];
				$snpc['club'] = $snpc['club']==99 ? '第一形态' : $clubinfo[$snpc['club']];
				//合并装备名
				foreach(Array('wep','arb','arh','ara','arf','art') as $t1) 
				{
					foreach(Array('','k','e','s','sk') as $t2)
					{
						if(isset($snpc[$t1.$t2]))
						{
							//为装备名添加tooltip效果
							if($t2 == '')
							{
								$snpc[$t1.$t2] = parse_itm_desc($snpc[$t1.$t2],'m');
							}
							//为装备类别添加tooltip效果
							elseif($t2 == 'k')
							{
								foreach($iteminfo as $info_key => $info_value)
								{
									if(strpos($snpc[$t1.$t2],$info_key)===0)
									{
										$snpc[$t1.$t2] = parse_itm_desc($info_key,'k');
										break;
									}
								}
							}
							//为装备属性添加tooltip效果
							elseif($t2 == 'sk')
							{
								$tmpsk = get_itmsk_array($snpc[$t1.$t2]);
								foreach($tmpsk as $sk)
								{
									if(!empty($snpc[$t1.$t2.'_words']))
									{
										$snpc[$t1.$t2.'_words'] .= "+".parse_itm_desc($sk,'sk');
									}
									else
									{
										$snpc[$t1.$t2.'_words'] = parse_itm_desc($sk,'sk');
									}
								}
							}
						}
						else 
						{
							$snpc[$t1.$t2] = '-';
						}
					}
				}
				//合并道具名
				for($ni=0;$ni<=6;$ni++)
				{
					foreach(Array('','k','e','s','sk') as $t2)
					{
						if(isset($snpc['itm'.$t2.$ni]))
						{
							//为装备名添加tooltip效果
							if($t2 == '')
							{
								$snpc['itm'.$t2.$ni] = parse_itm_desc($snpc['itm'.$t2.$ni],'m');
							}
							//为装备类别添加tooltip效果
							elseif($t2 == 'k')
							{
								foreach($iteminfo as $info_key => $info_value)
								{
									if(strpos($snpc['itm'.$t2.$ni],$info_key)===0)
									{
										$snpc['itm'.$t2.$ni] = parse_itm_desc($info_key,'k');
										break;
									}
								}
							}
							//为装备属性添加tooltip效果
							elseif($t2 == 'sk')
							{
								$tmpsk = get_itmsk_array($snpc['itm'.$t2.$ni]);
								foreach($tmpsk as $sk)
								{
									if(!empty($snpc['itm'.$t2.$ni.'_words']))
									{
										$snpc['itm'.$t2.$ni.'_words'] .= "+".parse_itm_desc($sk,'sk');
									}
									else
									{
										$snpc['itm'.$t2.$ni.'_words'] = parse_itm_desc($sk,'sk');
									}
								}
							}
						}
					}
				}
				$tnlist[$i]['sub'][$n] = $snpc;
				unset($snpc);
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
	include_once config('mixitem',$gamecfg);
	global $mixinfo;
	if(is_array($mixinfo))
	{
		foreach($mixinfo as $lst)
		{
			if ($lst['result'][0]==$which || $lst['result'][0]==$which.' ')
			{
				$result.="通过合成获取 \r";
				break;
			}
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
			include_once config('random'.$rnm,$gamecfg);
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
	}
	//NPC掉落
	include_once config('npc',$gamecfg);
	include_once config('addnpc',$gamecfg);
	include_once config('evonpc',$gamecfg);
	$nownpclist = Array();
	$nownpclist = $npcinfo+$anpcinfo;
	foreach($enpcinfo as $ekey => $enpcs)
	{
		foreach($enpcs as $sname => $enpc)
		{
			$nownpclist[$ekey]['sub'][$sname] = $enpc;
		}
	}
	foreach($nownpclist as $npcs)
	{
		foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $nipval)
		{
			if(!empty($npcs['sub'])) 
			{
				foreach($npcs['sub'] as $npc)
				{
					if (isset($npc[$nipval]) && $npc[$nipval]==$which)
					{
						$result.="击败NPC {$npc['name']}时获得 \r";
						break;
					}
				}
			}
		}
	}*/
	if ($which=="悲叹之种") $result.="通过使用『灵魂宝石』强化物品失败获得 \r";
	return $result;
}
?>
