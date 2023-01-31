<?php

define('CURSCRIPT', 'sp_ilist');

require './include/common.inc.php';

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
elseif(($udata['groupid'] <= 1)&&($cuser!==$gamefounder)) { gexit($_ERROR['no_admin'], __file__, __line__); }

//初始化道具名词库
get_itm_namelist();

function get_itm_namelist()
{
	global $checkstr,$gamecfg;
	$in_file = config('itmlist',$gamecfg);
	if(!file_exists($in_file))
	{
		$iarr=Array();
		//获取所有地图刷新道具道具名
		$file = config('mapitem',$gamecfg);
		$itemlist = openfile($file);
		$in = sizeof($itemlist);
		for($i = 1; $i < $in; $i++) 
		{
			if(!empty($itemlist[$i]) && strpos($itemlist[$i],',')!==false)
			{
				list($iarea,$imap,$inum,$iname) = explode(',',$itemlist[$i]);
				if(!in_array($iname,$iarr)) $iarr[] = $iname;
			}
		}
		//获取所有商店出售道具道具名
		$file = config('shopitem',$gamecfg);
		$shoplist = openfile($file);
		foreach($shoplist as $lst)
		{
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($kind,$num,$price,$area,$item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		}
		//获取所有合成列表中的合成结果
		include_once config('mixitem',$gamecfg);
		foreach($mixinfo as $lst)
		{
			if(!in_array($lst['result'][0],$iarr)) $iarr[] = $lst['result'][0];
		}	
		//获取同调结果
		$file=config('synitem',$gamecfg);
		$synlist = openfile($file);
		foreach($synlist as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//获取超量结果
		$file=config('overlay',$gamecfg);
		$ovllist = openfile($file);
		foreach($ovllist as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//获取礼品盒
		$file=config('present',$gamecfg);
		$prslist = openfile($file);
		foreach($prslist as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//获取游戏王卡包
		$file=config('box',$gamecfg);
		$boxlist = openfile($file);
		foreach($boxlist as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//浮云
		$file=config('fy',$gamecfg);
		$list = openfile($file);
		foreach($list as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//开局道具
		$file=config('stitem',$gamecfg);
		$list = openfile($file);
		foreach($list as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		$file=config('stwep',$gamecfg);
		$blist = openfile($file);
		foreach($list as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item)=explode(',',$lst);
				if(!in_array($item,$iarr)) $iarr[] = $item;
			}
		//多重武器
		$file=config('wepchange',$gamecfg);
		$boxlist = openfile($file);
		foreach($boxlist as $lst)
			if(!empty($lst) && strpos($lst,',')!==false)
			{
				list($item,$item2)=explode(',',$lst);
				if(!in_array($item,$iarr) && isset($item)) $iarr[] = $item;
				if(!in_array($item2,$iarr) && isset($item2)) $iarr[] = $item2;
			}
		//福袋 鹅鹅鹅鹅鹅鹅鹅鹅鹅鹅鹅鹅鹅鹅
		foreach(Array('00','O1','WC','WD','WF','WG','WK','WP','') as $rnm)
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
						if(!in_array($in,$iarr)) $iarr[] = $in;
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
						if(isset($npc[$nipval]) && !in_array($npc[$nipval],$iarr)) $iarr[] = $npc[$nipval];
					}
				}
				else 
				{
					if(isset($npcs[$nipval]) && !in_array($npcs[$nipval],$iarr)) $iarr[] = $npcs[$nipval];
				}
			}
		}
		//加入些特殊道具
		$sp_arr = Array('悲叹之种','面包','矿泉水','秋刀鱼罐头',);
		foreach($sp_arr as $spi)
		{
			if(!in_array($spi,$iarr)) $iarr[] = $spi;
		}
		$cont = '';
		$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
		$cont .= '$item_namelist = ' . var_export($iarr,1).";\r\n?>";
		writeover($in_file, $cont);
		chmod($in_file,0777);
		echo "成功生成了道具名列表。<br>";
	}
	else 
	{
		echo "道具名列表已存在，如需要重新生成，请删除{$in_file}后再次打开本页面。<br>";
	}
}

?>
