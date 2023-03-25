<?php
if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

function calc($modval, $baseval, $curgid, $curuid, $curpid, $sttime, $vatime)
{
	$curgid%=$modval; $curgid=($curgid*$curgid+$curgid+10234)%$modval;
	$curuid=(($curuid^183658692)%$modval+$curuid%$modval*1901+999)%$modval;
	$curpid=(($curpid^10294888)%$modval+$curpid%$modval*$curgid)%$modval;
	$hashval=(($sttime%$modval*$baseval%$modval)*$baseval+$vatime%$modval)%$modval;
	$hashval=(($hashval*$curgid+$curuid)%$modval*$baseval+$curpid)%$modval;
	$hashval^=$vatime; $hashval%=$modval;
	return $hashval;
}

function swap(&$a,&$b)
{
	$t=$a; $a=$b; $b=$t;
}

function getclub($who, &$c1, &$c2, &$c3)
{
	global $db,$tablepre,$starttime,$validtime;
	$result = $db->query("SELECT gid FROM {$tablepre}winners ORDER BY gid desc LIMIT 1");
	$t=$db->fetch_array($result); $curgid=$t['gid']+1;
	$result = $db->query("SELECT uid FROM {$tablepre}users WHERE username='$who'");
	$t=$db->fetch_array($result); $curuid=$t['uid']+2;
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE name='$who' AND type=0");
	$t=$db->fetch_array($result); $curpid=$t['pid']+3;
	
	$c1=calc(12347,10007,$curgid,$curuid,$curpid,$starttime,$validtime);
	$c1%=6; if ($c1==0) $c1=9;	//超能称号为9号
	
	$delt=0;
	while ($delt<=30)
	{
		$c2=calc(10009,7789+$delt,$curgid,$curuid,$curpid,$starttime,$validtime);
		$c2%=5; $c2++;			//第二个称号不允许超能
		if ($c1!=$c2) break;
		$delt++;
	}
	if ($delt>30) if ($c1==1) $c2=2; else $c2=1;
	
	$c3=calc(11131,6397,$curgid,$curuid,$curpid,$starttime,$validtime);
	//$clubid = array(6,7,8,99,10,11,13,14,16,18,19,7,99,13,14,18,6,19,13,14,18);
	//$c3%=21; $c3=$clubid[$c3];
	$clubid = array(6,7,8,10,11,12,19,99,6,7,8,10,11,12,19,99);
	$c3%=16; $c3=$clubid[$c3];
	if ($c1==$c3 || $c2==$c3) $c3=99;

	if ($c1>$c2) swap($c1,$c2);
	if ($c1>$c3) swap($c1,$c3);
	if ($c2>$c3) swap($c2,$c3);
}

function changeclub($clb,&$data=NULL)
{
	if(!isset($data))
	{
		global $club;
		lostclub();
		$club = $clb;
		updateskill();
	}
	else 
	{
		lostclub($data);
		$data['club'] = $clb;
		updateskill($data);
	}
}

function updateskill(&$data=NULL)
{
	
	global $club, $wp, $wk, $wc, $wg, $wd, $wf, $money, $hp, $mhp, $att, $def ,$clbpara, $club_skillslist;
	if ($club==1) {$wp+=50;}
	if ($club==2) $wk+=50;
	if ($club==3) $wc+=50;
	if ($club==4) $wg+=50;
	if ($club==5) $wd+=50;
	if ($club==9) $wf+=40;
	if ($club==11) $money+=680;
	if ($club==12) {$wp+=25; $wk+=25; $wc+=25; $wg+=25; $wd+=25; $wf+=25; $mhp+=250; $hp+=250; $att+=300; $def+=300;}
	/*if ($club==16) { $wp+=25; $wk+=25; $wc+=25; $wg+=25; $wd+=25; $wf+=25; }
	if ($club==13) { $mhp+=250; $hp+=250; }
	if ($club==14) { $att+=300; $def+=300; }*/
	
	# 变更社团时 获取社团技能
	//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
	if(!isset($data))
	{
		$cks = $club_skillslist[$club];
		foreach($cks as $sk) getclubskill($sk,$clbpara);
	}
	else 
	{
		$cks = $club_skillslist[$data['club']];
		foreach($cks as $sk) getclubskill($sk,$data['clbpara']);
	}
}

function lostclub(&$data=NULL)
{
	global $club ,$clbpara, $club_skillslist;
	if(!$club) return 0;
	# 丢失原社团时 注销社团技能
	//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
	if(!isset($data))
	{
		$cks = $club_skillslist[$club];
		foreach($cks as $sk) lostclubskill($sk,$clbpara);
		$club = 0;
	}
	else 
	{
		$cks = $club_skillslist[$data['club']];
		foreach($cks as $sk) lostclubskill($sk,$data['clbpara']);
		$data['club'] = 0;
	}
}

function selectclub($id)
{
	global $name, $club;
	if ($club!=0) return 1;
	if ($id==0) return 2;
	getclub($name,$c1,$c2,$c3);
	if ($id==1) { $club=$c1; updateskill(); return 0; }
	if ($id==2) { $club=$c2; updateskill(); return 0; }
	if ($id==3) { $club=$c3; updateskill(); return 0; }
	return 3;
}

?>
