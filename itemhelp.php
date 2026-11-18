<?php

define('CURSCRIPT', 'help');

require './include/common.inc.php';

$help_ruleset_id = function_exists('get_current_ruleset_id') ? get_current_ruleset_id() : '';
$help_ending_v2 = function_exists('ending_routes_enabled') && ending_routes_enabled();
$help_ruleset_cfg = (!empty($help_ruleset_id) && function_exists('get_ruleset_config')) ? get_ruleset_config($help_ruleset_id) : false;
$help_ruleset_name = ($help_ruleset_cfg && !empty($help_ruleset_cfg['name'])) ? $help_ruleset_cfg['name'] : $help_ruleset_id;

$mapitemfile = config('mapitem',$gamecfg);
$resourcesfile = config('resources',$gamecfg);
$item_cache_key = !empty($help_ruleset_id) ? preg_replace('/[^A-Za-z0-9_]/', '', $help_ruleset_id) : ($help_ending_v2 ? 'default' : 'legacy');
$help_cache_dir = GAME_ROOT.'./gamedata/templates';
if(!is_dir($help_cache_dir)) @mkdir($help_cache_dir, 0777, true);
$writefile = $help_cache_dir.'/itemhelp_'.$item_cache_key.'.htm';

if(!file_exists($writefile) || (file_exists($mapitemfile) && filemtime($mapitemfile) > filemtime($writefile)) || (file_exists($resourcesfile) && filemtime($resourcesfile) > filemtime($writefile)))
{
	$itemhelpinfo =
	"<div align=\"middle\"><table>
		<tr>
			<td class=\"b1\" height=20px><span>所在地点</span></td>
			<td class=\"b1\"><span>物品名称</span></td>
			<td class=\"b1\"><span>物品类型</span></td>
			<td class=\"b1\"><span>效/耐</span></td>
			<td class=\"b1\"><span>物品属性</span></td>
			<td class=\"b1\"><span>刷新时间与数量</span></td>
		</tr>
		";
	$itemlist = file_exists($mapitemfile) ? openfile($mapitemfile) : array();
	$in = sizeof($itemlist);
	//登记非功能性地点信息时合并隐藏地点
	if(!empty($hplsinfo) && is_array($hplsinfo)){
		foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
	}
	for($i = 1; $i < $in; $i++) 
		if(!empty($itemlist[$i]) && strpos($itemlist[$i],',')!==false)
		{
			list($iarea,$imap,$inum,$iname,$ikind,$ieff,$ista,$iskind) = array_pad(explode(',',$itemlist[$i]), 8, '');
			$iskind = trim($iskind);
			$pls_label = ($imap==99) ? '全图随机' : (isset($plsinfo[$imap]) ? $plsinfo[$imap] : "地点#{$imap}");
			$itemhelpinfo.="<tr><td class=\"b3\" height=20px><span>{$pls_label}</span></td>\n";
			$itemhelpinfo.="<td class=\"b3\"><span>{$iname}</span></td>\n";
			$itemhelpinfo.="<td class=\"b3\"><span>";
			if (substr($ikind,0,2)=="GB")
			{
				if ($ikind=="GBr") $itemhelpinfo.="机枪弹药";
				elseif ($ikind=="GBi") $itemhelpinfo.="气体弹药";
				elseif ($ikind=="GBh") $itemhelpinfo.="重型弹药";
				elseif ($ikind=="GBe") $itemhelpinfo.="能源弹药";
				else $itemhelpinfo.="手枪弹药";
			}
			elseif ($ikind=="GA")
			{
				$itemhelpinfo.="箭矢弹药";
			}
			else
			{
				$matched_ikind = false;
				for ($k=strlen($ikind); $k>=1; $k--)
					if (isset($iteminfo[substr($ikind,0,$k)]))
					{
						$itemhelpinfo.=$iteminfo[substr($ikind,0,$k)];
						$matched_ikind = true;
						break;
					}
				if (!$matched_ikind) $itemhelpinfo.=$ikind;

				if (substr($ikind,0,2)=="TO")
						$itemhelpinfo.="（已埋设）";
				else  if (substr($ikind,0,2)=="TN")
						$itemhelpinfo.="（可拾取）";
				else  if (isset($ikind[0]) && $ikind[0]=="P")
				{
					if ($ikind[strlen($ikind)-1]=="2") $itemhelpinfo.="（猛毒）"; else $itemhelpinfo.="（有毒）";
				}
			}
			$itemhelpinfo.="</span></td>\n";
			$itemhelpinfo.="<td class=\"b3\"><span>{$ieff}/{$ista}</span></td>\n";
			$itemhelpinfo.="<td class=\"b3\"><span>";
			$sk_parts = array();
			if ($iskind !== '')
			{
				$sk_chars = preg_split('//u', $iskind, -1, PREG_SPLIT_NO_EMPTY);
				foreach ($sk_chars as $skc)
				{
					if (isset($itemspkinfo[$skc])) $sk_parts[] = $itemspkinfo[$skc];
				}
			}
			$itemhelpinfo.=implode('+',$sk_parts);
			$itemhelpinfo.="</span></td>\n";
			$itemhelpinfo.="<td class=\"b3\"><span>";
			if ($iarea==99) $itemhelpinfo.="每禁"; else $itemhelpinfo.="{$iarea}禁";
			$itemhelpinfo.="刷新{$inum}个</span></td></tr>\n";
		}
	$itemhelpinfo.="</table></div><br>\n";
	writeover($writefile,$itemhelpinfo);
}
else
{
	$itemhelpinfo = file_get_contents($writefile);
}

include template('itemhelpmain');
