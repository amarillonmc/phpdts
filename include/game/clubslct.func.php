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
	$t=$db->fetch_array($result); $curpid=$result['pid']+3;
	
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
	$clubid = array(6,7,8,99,10,11,13,14,16,18,19,7,99,13,14,18,6,19,13,14,18);
	$c3%=21; $c3=$clubid[$c3];
	if ($c1==$c3 || $c2==$c3) $c3=99;

	if ($c1>$c2) swap($c1,$c2);
	if ($c1>$c3) swap($c1,$c3);
	if ($c2>$c3) swap($c2,$c3);
}

function updateskill()
{
	global $club, $wp, $wk, $wc, $wg, $wd, $wf, $money, $hp, $mhp, $att, $def;
	if ($club==1) $wp+=30;
	if ($club==2) $wk+=30;
	if ($club==3) $wc+=30;
	if ($club==4) $wg+=30;
	if ($club==5) $wd+=20;
	if ($club==9) $wf+=20;
	if ($club==11) $money+=480;
	if ($club==16) { $wp+=15; $wk+=15; $wc+=15; $wg+=15; $wd+=15; $wf+=15; }
	if ($club==13) { $mhp+=200; $hp+=200; }
	if ($club==14) { $att+=200; $def+=200; }
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
