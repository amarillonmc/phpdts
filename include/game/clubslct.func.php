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

function getrandclbKeys($randclblist,$mkey1, $mkey2, $uid, $gid, $gamenum) 
{
    $keys = array();
    
    // 确定第一个键名
    $key1 = ($mkey1 + $uid + $gid) % count($randclblist);
    $keys[] = $randclblist[$key1];
    
    // 确定第二个键名
    $key2 = ($mkey2 + $uid + $gamenum) % count($randclblist);
    while($key2 == $key1)
    {
        $key2++;
        if($key2 >= count($randclblist)) $key2 = 0; 
    }
    $keys[] = $randclblist[$key2];
    
    // 确定第三个键名
    $key3 = ($mkey1 + $mkey2 + $gid + $gamenum) % count($randclblist);
    while($key3 == $key1 || $key3 == $key2)
    {
        $key3++;
        if($key3 >= count($randclblist)) $key3 = 0; 
    }
    $keys[] = $randclblist[$key3];

    return $keys;
}

# 在入场界面确定可选社团列表

# 普通社团列表
function valid_getclublist_t2($udata)
{
	# 固定可选：0 1-殴 2-斩 3-投 4-射 5-爆 9-灵 7-锡安 8-黑衣
	$t2_list = Array(0,1,2,3,4,5,9,7,8);
	return $t2_list;
}

# 特殊社团列表
function valid_getclublist_t1($udata)
{
	# 随机可选范围（选3）：6-疾风 10-天赋 11-富家 12-全能 19-晶莹
	$temp_t1_list = Array(6,10,11,12,19);

	global $db,$gtablepre;

	# 种子生成器看不懂……让gpt帮我整一个……
	$mkey1 = 11131;
	$mkey2 = 6397;
	$uid = $udata['uid'] + 1; 
	$result = $db->query("SELECT gid FROM {$gtablepre}winners ORDER BY gid desc LIMIT 1");
	$gid = $db->fetch_array($result)['gid'] + 2;
	$result = $db->query("SELECT gamenum FROM {$gtablepre}game WHERE groomid='{$udata['roomid']}'");
	$gamenum = $db->fetch_array($result)['gamenum'] + 3;
	
	$t1_list = getrandclbKeys($temp_t1_list,$mkey1, $mkey2, $uid, $gid, $gamenum);
	return $t1_list;
}

function getclub($who, &$c1, &$c2, &$c3)
{
	global $db,$gtablepre,$tablepre,$starttime,$validtime;
	$result = $db->query("SELECT gid FROM {$gtablepre}winners ORDER BY gid desc LIMIT 1");
	$t=$db->fetch_array($result); $curgid=$t['gid']+1;
	$result = $db->query("SELECT uid FROM {$gtablepre}users WHERE username='$who'");
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
	$clubid = array(6,7,8,10,11,12,19,6,7,8,10,11,12,19);
	$c3%=14; $c3=$clubid[$c3];
	if ($c1==$c3 || $c2==$c3) $c3=19;

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
	//global $club, $wp, $wk, $wc, $wg, $wd, $wf, $money, $hp, $mhp, $att, $def ,$clbpara, $club_skillslist;
	global $club_skillslist;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

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
	if (!$id) return 2;

	$t1_list = valid_getclublist_t1($udata);
	$t2_list = valid_getclublist_t2($udata);
	if(in_array($id,$t1_list) || in_array($id,$t2_list))
	{
		$club = $id; 
		updateskill(); 
		return 0;
	}

	/*getclub($name,$c1,$c2,$c3);
	if ($id==1) { $club=$c1; updateskill(); return 0; }
	if ($id==2) { $club=$c2; updateskill(); return 0; }
	if ($id==3) { $club=$c3; updateskill(); return 0; }*/
	return 3;
}

?>
