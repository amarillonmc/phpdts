<?php

define('CURSCRIPT', 'botservice');
require './include/common.inc.php';
require GAME_ROOT.'./include/game.func.php';
require config('combatcfg',$gamecfg);

$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");

echo "botservice_version=0.1\n";

if(!$db->num_rows($result)) 
{ 
	echo "not_in_game=1\n";
	exit(); 
}

$pdata = $db->fetch_array($result);
if($pdata['pass'] != md5($cpass)) 
{
	echo "wrong_passwd=1\n";
	exit();
}

if ($gamestate==0)
{
	echo "game_ended=1\n";
	exit();
}

extract($pdata,EXTR_REFS);
init_playerdata();

$log = '';

if($hp > 0){
	if(($now <= $noisetime+$noiselimit)&&$noisemode&&($noiseid!=$pid)&&($noiseid2!=$pid)) {
		echo "noisetime=$noisetime\n";
		echo "noisepls=$noisepls\n";
		echo "noiseinfo=$noisemode\n";
	}
}
else
{
	echo "dead=1\n";
	exit();
}

$inf='';

$cmd = $main = ''; $corpseflag=0;
if((strpos($action,'corpse')===0 || strpos($action,'pacorpse')===0) && $gamestate<40 && $command!='getcorpse'){
	$cid = strpos($action,'corpse')===0 ? str_replace('corpse','',$action) : str_replace('pacorpse','',$action);
	if($cid){
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$cid' AND hp=0");
		if($db->num_rows($result)>0){
			$edata = $db->fetch_array($result);
			include_once GAME_ROOT.'./include/game/battle.func.php';
			findcorpse($edata);
			extract($edata,EXTR_PREFIX_ALL,'w');
			init_battle(1);
			$corpseflag=1;
		}
	}	
}

if (isset($command) && (!$corpseflag || $command=='getcorpse'))
{
	if ($command == 'move') {
		include_once GAME_ROOT.'./include/game/search.func.php';
		move($var1);
		$cmdnum ++;
	}
	else if ($command == 'search')
	{
		include_once GAME_ROOT.'./include/game/search.func.php';
		search();
		$cmdnum ++;
	}
	else if ($command == 'itemget')
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		itemget();
	}
	else  if ($command == 'getcorpse')
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		getcorpse($var1);
	}
	else  if ($command == 'attack')
	{
		include_once GAME_ROOT.'./include/game/combat.func.php';
		if ($var1=='back') combat(1,$var1); else combat(1); 
	}
	else  if(strpos($command,'drop') === 0) 
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		$drop_item = substr($command,4);
		itemdrop($drop_item);
	} 
	else  if(strpos($command,'off') === 0) 
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		$off_item = substr($command,3);
		itemoff($off_item);
	} 
	else  if(strpos($command,'swap') === 0) 
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		$swap_item = substr($command,4);
		itemdrop($swap_item);
		itemadd();
	} 
	else  if(strpos($command,'itm') === 0) 
	{
		include_once GAME_ROOT.'./include/game/item.func.php';
		$item = substr($command,3);
		itemuse($item);
		$cmdnum ++;
	}
	else  if ($command=="shopquery")
	{
		$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE item = '$item'");
		if(!$db->num_rows($result)) 
			echo "shopitemnum=0\n";
		else
		{
			$iteminfo = $db->fetch_array($result);
			if($iteminfo['area']> $areanum/$areaadd)
				echo "shopitemnum=0\n";
			else  echo "shopitemnum={$iteminfo['num']}\n";
		}
	}
	else  if ($command=="shopbuy")
	{
		$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE item = '$item'");
		if(!$db->num_rows($result)) 
			echo "nosuchshopitem=1\n";
		else
		{
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			$iteminfo = $db->fetch_array($result);
			$bnum = (int)$bnum;
			if($iteminfo['num'] <= 0 || $bnum<=0 || $bnum>$iteminfo['num'] || $money < $price*$bnum || (!preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|H|V|M)/',$iteminfo['itmk']) && $bnum>1) || $iteminfo['area']> $areanum/$areaadd)
			{
				echo "buyfailed=1\n";
			}
			else
			{
				$price = $club == 11 ? round($iteminfo['price']*0.75) : $iteminfo['price'];
				$inum = $iteminfo['num']-$bnum;
				$sid = $iteminfo['sid'];
				$db->query("UPDATE {$tablepre}shopitem SET num = '$inum' WHERE sid = '$sid'");
				$money -= $price*$bnum;
				addnews($now,'itembuy',$name,$iteminfo['item']);
				$itm0 = $iteminfo['item'];
				$itmk0 = $iteminfo['itmk'];
				$itme0 = $iteminfo['itme'];
				$itms0 = $iteminfo['itms']*$bnum;
				$itmsk0 = $iteminfo['itmsk'];
				itemget();
				echo "buysuccess=1\n";
			}	
		}
	}
	else  if ($command=="itemmerge")
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		itemmerge($merge1,$merge2);
	}
	else  if ($command=="itemmix")
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		$mixlist = array(); $mask=(int)$mask;
		for($i=1;$i<=6;$i++) if ($mask&(1<<($i-1))) $mixlist[] = $i;
		itemmix($mixlist);
	}
	else  if ($command=="itemadd")
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		itemadd();
	}
	else  if (strpos($command,'off') === 0) 
	{
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		$off_item = substr($command,3);
		itemoff($off_item);
	}
	else  if ($command=="selectclub")
	{
		if ($club==0 && isset($var1) && ((int)$var1<=16)) 
		{
			$club=(int)$var1;
			include_once GAME_ROOT.'./include/game/clubslct.func.php';
			updateskill();
		}
	}
	else  if ($command=="verify")
	{
		$endtime=1;		//用于程序开始时调用，获取全部消息列表
	}
}

if ($itms0)
{
	echo "itm0=$itm0\n";
	echo "itmk0=$itmk0\n";
	echo "itme0=$itme0\n";
	echo "itms0=$itms0\n";
	echo "itmsk0=$itmsk0\n";
	echo "mode=itemfind\n";
}

foreach (Array('wep','arb','arh','ara','arf','art') as $value) 
	if (${$value.'s'})
	{
		echo "{$value}=".${$value}."\n";
		echo "{$value}k=".${$value.'k'}."\n";
		echo "{$value}e=".${$value.'e'}."\n";
		echo "{$value}s=".${$value.'s'}."\n";
		echo "{$value}sk=".${$value.'sk'}."\n";
	}
	
foreach (Array('1','2','3','4','5','6') as $itm_id) 
	if (${'itms'.$itm_id})
	{
		echo "itm{$itm_id}=".${'itm'.$itm_id}."\n";
		echo "itmk{$itm_id}=".${'itmk'.$itm_id}."\n";
		echo "itme{$itm_id}=".${'itme'.$itm_id}."\n";
		echo "itms{$itm_id}=".${'itms'.$itm_id}."\n";
		echo "itmsk{$itm_id}=".${'itmsk'.$itm_id}."\n";
	}

foreach (Array('mhp','hp','msp','sp','rage','money','club','inf','mss','ss','skillpoint','att','def','pls','lvl','pose','tactic','wp','wk','wc','wg','wd','wf','action') as $value) 
	echo "{$value}=".${$value}."\n";

if ($club==0)
{
	echo "clubselect=1\n";
	include_once GAME_ROOT.'./include/game/clubslct.func.php';
	getclub($name,$c1,$c2,$c3);
	echo "clubchoice1=$c1\n";
	echo "clubchoice2=$c2\n";
	echo "clubchoice3=$c3\n";
}

$z=(int)$areanum/$areaadd;
echo "areanum=".$z."\n";
$plsnum=sizeof($plsinfo)-1;
echo "plsnum=$plsnum\n";
echo "starttime=$starttime\n";
echo "now=$now\n";
$gametime=$now-$starttime;
echo "gametime=$gametime\n";
echo "nextareaaddtime=$areatime\n";
echo "areaaddnum=$areaadd\n";
$alis="";
for ($i=1; $i<=$plsnum; $i++) $alis.=$arealist[$i].",";
echo "arealist=$alis\n";
echo "hacked=$hack\n";

$result = $db->query("SELECT type,sNo,pls,name,state,bid FROM {$tablepre}players WHERE type > 0 AND deathtime >= $endtime");
$rows=$db->num_rows($result);

echo "npcdeathnum=$rows\n";

$i=0;
while($data = $db->fetch_array($result)) 
{
	$i++;
	echo "deathnpctype$i={$data['type']}\n";
	echo "deathnpcsNo$i={$data['sNo']}\n";
	echo "deathnpcpls$i={$data['pls']}\n";
	echo "deathnpcname$i={$data['name']}\n";
	$t=$data['state'];
	if ($t==20 || $t==21 || $t==22 || $t==23 || $t==24 || $t==29)
	{
		$rs = $db->query("SELECT sNo,name FROM {$tablepre}players WHERE pid='{$data['bid']}'");
		$dd = $db->fetch_array($rs);
		echo "deathnpckillersNo$i={$dd['sNo']}\n";
		echo "deathnpckillername$i={$dd['name']}\n";
	}
	else
	{
		echo "deathnpckillersNo$i=-1\n";
		echo "deathnpckillername$i=-1\n";
	}
}

$result = $db->query("SELECT sNo,pls,name,state,bid FROM {$tablepre}players WHERE type = 0 AND state >= 10 AND deathtime >= $endtime");
$rows=$db->num_rows($result);
echo "pcdeathnum=$rows\n";
$i=0;
while($data = $db->fetch_array($result)) 
{
	$i++;
	echo "deathpcsNo$i={$data['sNo']}\n";
	echo "deathpcpls$i={$data['pls']}\n";
	echo "deathpcname$i={$data['name']}\n";
	echo "deathpcstate$i={$data['state']}\n";
	$t=$data['state'];
	if ($t==20 || $t==21 || $t==22 || $t==23 || $t==24 || $t==29)
	{
		$rs = $db->query("SELECT type,sNo,name FROM {$tablepre}players WHERE pid='{$data['bid']}'");
		$dd = $db->fetch_array($rs);
		echo "deathpckillertype$i={$dd['type']}\n";
		echo "deathpckillersNo$i={$dd['sNo']}\n";
		echo "deathpckillername$i={$dd['name']}\n";
	}
	else
	{
		echo "deathpckillertype$i=-1\n";
		echo "deathpckillersNo$i=-1\n";
		echo "deathpckillername$i=-1\n";
	}
}

echo "weather=$weather\n";
//echo "log=$log\n";

$endtime = $now;

player_save($pdata);

?>
