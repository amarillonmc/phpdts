<?php
if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

//include_once GAME_ROOT.'./include/game/dice.func.php';

function getskills(&$arr)
{
	$arr=Array(
		//铁拳无敌称号技能
		1=>Array(
			//格档，花费[0]点技能，使武器效果按百分之[1]比率算入防御力
			"sk1"=>Array(	
				0=>Array(0,0),
				1=>Array(2,5),
				2=>Array(2,10),
				3=>Array(3,15),
				4=>Array(3,20),
				5=>Array(4,30),
				6=>Array(6,40)),
			//暴击，花费[0]点技能，增强百分之[1]的攻击力
			//有百分之[2]的几率在计算伤害时减少敌人百分之[3]的防御
			"sk2"=>Array(	
				0=>Array(0,0,0,0),
				1=>Array(2,2,4,5),
				2=>Array(3,4,6,10),
				3=>Array(3,6,8,15),
				4=>Array(4,9,10,20),
				5=>Array(5,12,20,20),
				6=>Array(5,15,25,25))),
		//见敌必斩称号技能
		2=>Array(
			//精准，花费[0]点技能，命中率和连击命中系数提升百分之[1]
			"sk1"=>Array(	
				0=>Array(0,0),
				1=>Array(2,2),
				2=>Array(3,4),
				3=>Array(4,6),
				4=>Array(5,8),
				5=>Array(6,10),
				6=>Array(6,12)),
			//保养，花费[0]点技能，武器损坏率降低百分之[1]
			"sk2"=>Array(
				0=>Array(0,0),
				1=>Array(2,6),
				2=>Array(3,13),
				3=>Array(3,20),
				4=>Array(4,30),
				5=>Array(4,40),
				6=>Array(5,50))),
		//灌篮高手称号技能
		3=>Array(
			//臂力，花费[0]点技能，反击率提升百分之[1]
			"sk1"=>Array(	
				0=>Array(0,0),
				1=>Array(1,20),
				2=>Array(2,40),
				3=>Array(2,60),
				4=>Array(3,80),
				5=>Array(3,100),
				6=>Array(4,120)),
			//潜能，花费[0]点技能，攻击力提高[1]，伤害浮动增加[2]
			"sk2"=>Array(	
				0=>Array(0,0,0),
				1=>Array(2,2,4),
				2=>Array(3,4,8),
				3=>Array(3,6,13),
				4=>Array(4,8,19),
				5=>Array(5,10,25),
				6=>Array(5,12,33))),
		//狙击鹰眼称号技能
		4=>Array(
			//静息，花费[0]点技能，命中率和连击命中系数提升百分之[1]
			"sk1"=>Array(
				0=>Array(0,0),
				1=>Array(2,2),
				2=>Array(3,4),
				3=>Array(4,6),
				4=>Array(5,8),
				5=>Array(6,10),
				6=>Array(7,12)),
			//重击，花费[0]点技能，防具损坏率提高[1]，
			//防具损坏效果变为[2]倍，有百分之[3]的几率造成额外百分之[4]的伤害
			"sk2"=>Array(
				0=>Array(0,0,0,0,0),
				1=>Array(2,20,1,5,10),
				2=>Array(3,40,1,10,15),
				3=>Array(4,60,2,15,20),
				4=>Array(5,80,2,20,20),
				5=>Array(5,100,2,35,25),
				6=>Array(7,125,3,50,30))),
		//拆弹专家称号技能
		5=>Array(
			//隐蔽，花费[0]点技能，隐蔽率提升百分之[1]，先攻率提升百分之[2]
			"sk1"=>Array(
				0=>Array(0,0,0),
				1=>Array(2,4,3),
				2=>Array(3,8,6),
				3=>Array(3,12,9),
				4=>Array(4,16,12),
				5=>Array(5,20,15),
				6=>Array(5,24,18)),
			//冷静，花费[0]点技能，陷阱回避率提升百分之[1]，陷阱再利用率提升百分之[2]
			"sk2"=>Array(
				0=>Array(0,0,0),
				1=>Array(1,7,5),
				2=>Array(2,14,10),
				3=>Array(2,21,15),
				4=>Array(3,28,20),
				5=>Array(3,35,25),
				6=>Array(4,44,30))),
		//宛如疾风称号技能
		6=>Array(
			//敏捷，花费[0]点技能，隐蔽率提升百分之[1]，先攻率提升百分之[2]，反击率提升百分之[3]
			"sk1"=>Array(
				0=>Array(0,0,0,0),
				1=>Array(2,3,1,5),
				2=>Array(2,6,2,10),
				3=>Array(3,9,3,15),
				4=>Array(3,12,5,20),
				5=>Array(4,16,8,25),
				6=>Array(5,20,13,30)),
			//冷静，花费[0]点技能，对方命中率下降[1]
			"sk2"=>Array(
				0=>Array(0,0),
				1=>Array(2,3),
				2=>Array(3,7),
				3=>Array(3,11),
				4=>Array(4,15),
				5=>Array(4,18),
				6=>Array(5,20))),
		//超能力者称号技能
		9=>Array(
			//灵力，花费[0]点技能，灵系体力消耗减少百分之[1]，敌人对灵系反击率降低[2]
			"sk1"=>Array(
				0=>Array(0,0,0),
				1=>Array(1,3,8),
				2=>Array(2,6,16),
				3=>Array(3,9,24),
				4=>Array(3,12,32),
				5=>Array(4,16,40),
				6=>Array(5,20,50))),
		19=>Array(
			//晶莹，花费[0]点技能，给敌人的最终伤害减少百分之[1]，自己受到的最终伤害减少百分之[2]，RP增长率下降百分之[3]
			"sk1"=>Array(
				0=>Array(0,0,0,0),
				1=>Array(1,50,10,10),
				2=>Array(2,75,20,20),
				3=>Array(3,88,30,30),
				4=>Array(4,94,40,40),
				5=>Array(5,97,50,50),
				6=>Array(6,98,60,60)),
			//剔透，花费[0]点技能，主动攻击时有[3]%概率对敌人造成额外的伤害
			"sk2"=>Array(
				0=>Array(0,0),
				1=>Array(1,2),
				2=>Array(2,5),
				3=>Array(3,8),
				4=>Array(4,12),
				5=>Array(5,16),
				6=>Array(6,20))),
	);
}

function getskills2(&$arr)
{
	$arr=Array(
		0=>0,
		1=>Array(
				0=>Array(0,0),
				1=>Array(2,5),
				2=>Array(2,10),
				3=>Array(3,15),
				4=>Array(3,20),
				5=>Array(4,30),
				6=>Array(6,40)),
		2=>Array(	
				0=>Array(0,0,0,0),
				1=>Array(2,2,4,5),
				2=>Array(3,4,6,10),
				3=>Array(3,6,8,15),
				4=>Array(4,9,10,20),
				5=>Array(5,12,20,20),
				6=>Array(5,15,25,25)),
		3=>Array(
				0=>Array(0,0),
				1=>Array(2,2),
				2=>Array(3,4),
				3=>Array(4,6),
				4=>Array(5,8),
				5=>Array(6,10),
				6=>Array(6,12)),
		4=>Array(
				0=>Array(0,0),
				1=>Array(2,6),
				2=>Array(3,13),
				3=>Array(3,20),
				4=>Array(4,30),
				5=>Array(4,40),
				6=>Array(5,50)),
		7=>Array(	
				0=>Array(0,0),
				1=>Array(1,20),
				2=>Array(2,40),
				3=>Array(2,60),
				4=>Array(3,80),
				5=>Array(3,100),
				6=>Array(4,120)),
		8=>Array(	
				0=>Array(0,0,0),
				1=>Array(2,2,4),
				2=>Array(3,4,8),
				3=>Array(3,6,13),
				4=>Array(4,8,19),
				5=>Array(5,10,25),
				6=>Array(5,12,33)),
		5=>Array(
				0=>Array(0,0),
				1=>Array(2,2),
				2=>Array(3,4),
				3=>Array(4,6),
				4=>Array(5,8),
				5=>Array(6,10),
				6=>Array(7,12)),
		6=>Array(
				0=>Array(0,0,0,0,0),
				1=>Array(2,20,1,5,10),
				2=>Array(3,40,1,10,15),
				3=>Array(4,60,2,15,20),
				4=>Array(5,80,2,20,20),
				5=>Array(5,100,2,35,25),
				6=>Array(7,125,3,50,30)),
		9=>Array(
				0=>Array(0,0,0),
				1=>Array(2,4,3),
				2=>Array(3,8,6),
				3=>Array(3,12,9),
				4=>Array(4,16,12),
				5=>Array(5,20,15),
				6=>Array(5,24,18)),
		10=>Array(
				0=>Array(0,0,0),
				1=>Array(1,7,5),
				2=>Array(2,14,10),
				3=>Array(2,21,15),
				4=>Array(3,28,20),
				5=>Array(3,35,25),
				6=>Array(4,44,30)),
		11=>Array(
				0=>Array(0,0,0,0),
				1=>Array(2,3,1,5),
				2=>Array(2,6,2,10),
				3=>Array(3,9,3,15),
				4=>Array(3,12,5,20),
				5=>Array(4,16,8,25),
				6=>Array(5,20,13,30)),
		12=>Array(
				0=>Array(0,0),
				1=>Array(2,3),
				2=>Array(3,7),
				3=>Array(3,11),
				4=>Array(4,15),
				5=>Array(4,18),
				6=>Array(5,20)),
		13=>Array(
				0=>Array(0,0,0),
				1=>Array(1,3,8),
				2=>Array(2,6,16),
				3=>Array(3,9,24),
				4=>Array(3,12,32),
				5=>Array(4,16,40),
				6=>Array(5,20,50)),
		14=>Array(
				0=>Array(0,0,0,0),
				1=>Array(1,50,10,10),
				2=>Array(2,75,20,20),
				3=>Array(3,88,30,30),
				4=>Array(4,94,40,40),
				5=>Array(5,97,50,50),
				6=>Array(6,98,60,60)),
		15=>Array(
				0=>Array(0,0),
				1=>Array(1,2),
				2=>Array(2,5),
				3=>Array(3,8),
				4=>Array(4,12),
				5=>Array(5,16),
				6=>Array(6,20)),
	);
}

function get_research_cost(&$arr)
{
	$arr=Array(0,1,2,2,1,2,1,1,1,2,2,2,3,1,3,3);
}

function gskill(&$arr,$club,$kind,$sk1lv)
{
	getskills($clskl);
	$sk2lv=$sk1lv;
	if ($club==9 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curles']=$clskl[$club]['sk1'][$sk1lv][1];
		$arr['curcnt']=$clskl[$club]['sk1'][$sk1lv][2];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newles']=$clskl[$club]['sk1'][$sk1lv+1][1];
			$arr['newcnt']=$clskl[$club]['sk1'][$sk1lv+1][2];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==1 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curdef']=$clskl[$club]['sk1'][$sk1lv][1];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newdef']=$clskl[$club]['sk1'][$sk1lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==1 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curatt']=$clskl[$club]['sk2'][$sk2lv][1];
		$arr['curpro']=$clskl[$club]['sk2'][$sk2lv][2];
		$arr['curdec']=$clskl[$club]['sk2'][$sk2lv][3];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newatt']=$clskl[$club]['sk2'][$sk2lv+1][1];
			$arr['newpro']=$clskl[$club]['sk2'][$sk2lv+1][2];
			$arr['newdec']=$clskl[$club]['sk2'][$sk2lv+1][3];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==2 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curacc']=$clskl[$club]['sk1'][$sk1lv][1];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newacc']=$clskl[$club]['sk1'][$sk1lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==2 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curpro']=$clskl[$club]['sk2'][$sk2lv][1];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newpro']=$clskl[$club]['sk2'][$sk2lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==3 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curpro']=$clskl[$club]['sk1'][$sk1lv][1];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newpro']=$clskl[$club]['sk1'][$sk1lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==3 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curatt']=$clskl[$club]['sk2'][$sk2lv][1];
		$arr['curfluc']=$clskl[$club]['sk2'][$sk2lv][2];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newatt']=$clskl[$club]['sk2'][$sk2lv+1][1];
			$arr['newfluc']=$clskl[$club]['sk2'][$sk2lv+1][2];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==4 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curacc']=$clskl[$club]['sk1'][$sk1lv][1];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newacc']=$clskl[$club]['sk1'][$sk1lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==4 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curpro']=$clskl[$club]['sk2'][$sk2lv][1];
		$arr['cureff']=$clskl[$club]['sk2'][$sk2lv][2];
		$arr['curpro2']=$clskl[$club]['sk2'][$sk2lv][3];
		$arr['curdmg']=$clskl[$club]['sk2'][$sk2lv][4];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newpro']=$clskl[$club]['sk2'][$sk2lv+1][1];
			$arr['neweff']=$clskl[$club]['sk2'][$sk2lv+1][2];
			$arr['newpro2']=$clskl[$club]['sk2'][$sk2lv+1][3];
			$arr['newdmg']=$clskl[$club]['sk2'][$sk2lv+1][4];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==5 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curhid']=$clskl[$club]['sk1'][$sk1lv][1];
		$arr['curact']=$clskl[$club]['sk1'][$sk1lv][2];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newhid']=$clskl[$club]['sk1'][$sk1lv+1][1];
			$arr['newact']=$clskl[$club]['sk1'][$sk1lv+1][2];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==5 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curmis']=$clskl[$club]['sk2'][$sk2lv][1];
		$arr['curpic']=$clskl[$club]['sk2'][$sk2lv][2];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newmis']=$clskl[$club]['sk2'][$sk2lv+1][1];
			$arr['newpic']=$clskl[$club]['sk2'][$sk2lv+1][2];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==6 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['curhid']=$clskl[$club]['sk1'][$sk1lv][1];
		$arr['curact']=$clskl[$club]['sk1'][$sk1lv][2];
		$arr['curcnt']=$clskl[$club]['sk1'][$sk1lv][3];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newhid']=$clskl[$club]['sk1'][$sk1lv+1][1];
			$arr['newact']=$clskl[$club]['sk1'][$sk1lv+1][2];
			$arr['newcnt']=$clskl[$club]['sk1'][$sk1lv+1][3];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==6 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['curmis']=$clskl[$club]['sk2'][$sk2lv][1];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newmis']=$clskl[$club]['sk2'][$sk2lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==19 && $kind==1)
	{
		$arr['lv']=$sk1lv;
		$arr['wdmgdown']=$clskl[$club]['sk1'][$sk1lv][1];
		$arr['dmgdown']=$clskl[$club]['sk1'][$sk1lv][2];
		$arr['rpdec']=100-$clskl[$club]['sk1'][$sk1lv][3];
		if ($sk1lv<6) 
		{
			$arr['nextlv']=$sk1lv+1; 
			$arr['cost']=$clskl[$club]['sk1'][$sk1lv+1][0];
			$arr['newwdmgdown']=$clskl[$club]['sk1'][$sk1lv+1][1];
			$arr['newdmgdown']=$clskl[$club]['sk1'][$sk1lv+1][2];
			$arr['newrpdec']=100-$clskl[$club]['sk1'][$sk1lv+1][3];
		}
		else  $arr['nextlv']=-1;
	}
	else  if ($club==19 && $kind==2)
	{
		$arr['lv']=$sk2lv;
		$arr['rpdmgr']=$clskl[$club]['sk2'][$sk2lv][1];
		if ($sk2lv<6) 
		{
			$arr['nextlv']=$sk2lv+1; 
			$arr['cost']=$clskl[$club]['sk2'][$sk2lv+1][0];
			$arr['newrpdmgr']=$clskl[$club]['sk2'][$sk2lv+1][1];
		}
		else  $arr['nextlv']=-1;
	}
}

function getclubavd(&$arr,$club)
{
	$arr['learn1']=0; $arr['learn2']=0;
	if ($club==1)
	{
		$arr['learn1']=1; $arr['learn2']=2;
	}
	else  if ($club==2)
	{
		$arr['learn1']=3; $arr['learn2']=4;
	}
	else  if ($club==3)
	{
		$arr['learn1']=7; $arr['learn2']=8;
	}
	else  if ($club==4)
	{
		$arr['learn1']=5; $arr['learn2']=6;
	}
	else  if ($club==5)
	{
		$arr['learn1']=9; $arr['learn2']=10;
	}
	else  if ($club==6)
	{
		$arr['learn1']=11; $arr['learn2']=12;
	}
	else  if ($club==9) $arr['learn1']=13;
	else  if ($club==19)
	{
		$arr['learn1']=14;$arr['learn2']=15;
	}
}

function getck($x,&$c,&$k)
{
	$c1=Array(0,1,1,2,2,4,4,3,3,5,5,6,6,9,19,19);
	$k1=Array(0,1,2,1,2,1,2,1,2,1,2,1,2,1,1,2);
	$c=$c1[$x]; $k=$k1[$x];
}

function getlearnt(&$arr,$club,$skills)
{
	if ($club!=18 && $club!=98)	//天赋异禀和换装迷宫称号（供NPC）可以任意学习技能
		getclubavd($arr,$club);
	else 
	{
		$learn1=(int)(((int)($skills/100))/16);
		$learn2=((int)($skills/100))%16;
		$arr['learn1']=$learn1; $arr['learn2']=$learn2;
	}
}

function calcskills(&$arr)
{
	getskills($clskl); get_research_cost($rcost);
	global $club,$skills;
	$sk1lv=((int)($skills/10))%10;
	$sk2lv=$skills%10;
	getlearnt($arr,$club,$skills);
		
	if ($arr['learn1']) 
	{
		getck($arr['learn1'],$c,$k);
		gskill($arr['sk1'],$c,$k,$sk1lv);
	}
	if ($arr['learn2']) 
	{
		getck($arr['learn2'],$c,$k);
		gskill($arr['sk2'],$c,$k,$sk2lv);
	}

	if ($club==18)
	{
		for ($i=1; $i<=13; $i++)
		{
			if ($i!=$arr['learn1'] && $i!=$arr['learn2'])
			{
				$arr['rs'.$i]=0;
				$arr['rs'.$i.'cost']=$rcost[$i];
			}
			else  $arr['rs'.$i]=1;
		}
		$arr['learn']=2;
		if ($arr['learn1']) $arr['learn']--;
		if ($arr['learn2']) $arr['learn']--;
	}
}

function upgradeclubskills($cmd)
{
	getskills($clskl);
	global $hp,$mhp,$sp,$msp,$att,$def,$inf,$skillpoint,$log,$club,$skills;
	if ($cmd=="clubbasic1")	//生命
	{
		if ($club==17)
		{
			$log.="你不能使用本技能。<br>";
			return;
		}
		
		if ($skillpoint<1)
		{
			$log.="技能点不足。<br>";
			return;
		}
		else
		{
			$skillpoint--;
			if ($club==13) 
			{
				$log.="消耗了<span class='lime'>1</span>点技能点，你的生命上限增加了<span class='yellow'>6</span>点。<br>";
				$hp+=6; $mhp+=6;
			}
			else 
			{
				$log.="消耗了<span class='lime'>1</span>点技能点，你的生命上限增加了<span class='yellow'>3</span>点。<br>";
				$hp+=3; $mhp+=3;
			}
		}
	}
	else  if ($cmd=="clubbasic2")	//攻防
	{
		if ($club==17)
		{
			$log.="你不能使用本技能。<br>";
			return;
		}
		
		if ($skillpoint<1)
		{
			$log.="技能点不足。<br>";
			return;
		}
		else
		{
			$skillpoint--;
			if ($club==14) 
			{
				$log.="消耗了<span class='lime'>1</span>点技能点，你的基础攻击增加了<span class='yellow'>9</span>点，基础防御增加了<span class='yellow'>12</span>点。<br>";
				$att+=9; $def+=12;
			}
			else 
			{
				$log.="消耗了<span class='lime'>1</span>点技能点，你的基础攻击增加了<span class='yellow'>4</span>点，基础防御增加了<span class='yellow'>6</span>点。<br>";
				$att+=4; $def+=6;
			}
		}
	}
	else  if ($cmd=="clubbasic3")	//治疗
	{
		if ($skillpoint<1)
		{
			$log.="技能点不足。<br>";
			return;
		}
		else
		{
			$skillpoint--;
			$log.="消耗了<span class='lime'>1</span>点技能点，你的所有受伤和异常状态都解除了。<br>";
			$inf="";
		}
	}
	else  if (strpos($cmd,'clubskill') === 0)
	{
		getlearnt($ac,$club,$skills);
		$sk1lv=((int)($skills/10))%10;
		$sk2lv=$skills%10;
		
		$which=0;
		$which=intval(substr($cmd,9,1),10);
		if (strlen($cmd)>=11) $which=$which*10+intval(substr($cmd,10,1),10);
		
		if ($which<1 || $which>15)
		{
			$log.="技能不合法。<br>";
			return;
		}
		if ($which!=$ac['learn1'] && $which!=$ac['learn2'])
		{
			$log.="你不能升级此技能。<br>";
			return;
		}
		if ($which==$ac['learn1'])
		{
			if ($sk1lv==6)
			{
				$log.="你已经升到了最高级。<br>";
				return;
			}
			getck($ac['learn1'],$c,$k);
			if ($skillpoint<$clskl[$c]['sk'.$k][$sk1lv+1][0])
			{
				$log.="技能点不足。<br>";
				return;
			}
			$skillpoint-=$clskl[$c]['sk'.$k][$sk1lv+1][0];
			$skills+=10;
//			if($which == 14){//晶莹剔透
//				$msp=round($mhp*(100-$clskl[$c]['sk'.$k][$sk1lv+1][2])/100);if($sp > $msp){$sp = $msp;}
//				$att=round($att*(100-$clskl[$c]['sk'.$k][$sk1lv+1][2])/100);
//				$mhp=round($mhp*(100+$clskl[$c]['sk'.$k][$sk1lv+1][1])/100);
//				$def=round($def*(100+$clskl[$c]['sk'.$k][$sk1lv+1][1])/100);
//			}
			$log.="升级成功。<br>";
		}
		else
		{
			if ($ac['learn2']==0) 
			{
				$log.="你不能使用此技能。<br>";
				return;
			}
			if ($sk2lv==6)
			{
				$log.="你已经升到了最高级。<br>";
				return;
			}
			getck($ac['learn2'],$c,$k);
			if ($skillpoint<$clskl[$c]['sk'.$k][$sk2lv+1][0])
			{
				$log.="技能点不足。<br>";
				return;
			}
			$skillpoint-=$clskl[$c]['sk'.$k][$sk2lv+1][0];
			$skills++;
			
			$log.="升级成功。<br>";
		}
	}
	else  
	{
		if ($club!=18)
		{
			$log.="你不能研发技能。<br>";
			return;
		}
		
		$sk1lv=((int)($skills/10))%10;
		$sk2lv=$skills%10;
		$learn1=(int)(((int)($skills/100))/16);
		$learn2=((int)($skills/100))%16;
		if ($learn1 && $learn2)
		{
			$log.="你不能研发更多的技能了。<br>";
			return;
		}
		
		$which=0;
		$which=intval(substr($cmd,7,1),10);
		if (strlen($cmd)>=9) $which=$which*10+intval(substr($cmd,8,1),10);
		if ($which<1 || $which>13)
		{
			$log.="技能不合法。<br>";
			return;
		}
		if ($which==$learn1 || $which==$learn2)
		{
			$log.="你已经研发过本技能了。<br>";
			return;
		}
		get_research_cost($rcost);
		if ($skillpoint<$rcost[$which])
		{
			$log.="技能点不足。<br>";
			return;
		}
		$skillpoint-=$rcost[$which];
		if (!$learn1) $skills+=1600*$which; else $skills+=100*$which;
	}
}

#适配新版战斗函数的社团技能判定函数：
function rev_get_clubskill_bonus_hitrate($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//命中率系数
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==3 && $pa['wep_kind']=="K")	//见敌必斩称号
		{
			$r*=(1+$clskl[3][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==5 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
		{
			$r*=(1+$clskl[5][${'a'.$i}][1]/100);
		}
		if ($blearn['learn'.$i]==12)						//宛如疾风称号
		{
			$r*=(1-$clskl[12][${'b'.$i}][1]/100);
		}
	}
	return $r;
}

function rev_get_clubskill_bonus_imfrate($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//防具损坏率系数
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
		{
			$r*=(1+$clskl[6][${'a'.$i}][1]/100);
		}
	}
	return $r;
}

function rev_get_clubskill_bonus_imftime($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//防具损坏效果系数
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
		{
			$r+=$clskl[6][${'a'.$i}][2];
		}
	}
	return $r;
}

function rev_get_clubskill_bonus_imprate($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//武器损坏率系数
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==4 && $pa['wep_kind']=="K")	//见敌必斩称号
		{
			$r*=(1-$clskl[4][${'a'.$i}][1]/100);
		}
	}
	return $r;
}

function rev_get_clubskill_bonus($aclub,$askl,$pa,$bclub,$bskl,$pd,&$att,&$def)
{
	//攻击防御力加成
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$att=0; $def=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($blearn['learn'.$i]==1 && $pd['wep_kind']=="P")	//铁拳无敌称号
		{
			$dup=$clskl[1][${'b'.$i}][1]/100*$pd['wepe'];
			if ($dup>2000) $dup=2000;
			$def+=$dup;
		}
	}
}

function rev_get_clubskill_bonus_p($aclub,$askl,$pa,$bclub,$bskl,$pd,&$att,&$def)
{
	//攻击防御加成系数
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$att=1; $def=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==2 && $pa['wep_kind']=="P")	//铁拳无敌称号
		{
			$att*=(1+$clskl[2][${'a'.$i}][1]/100);
			if (rand(0,99)<$clskl[2][${'a'.$i}][2]) $def*=(1-$clskl[2][${'a'.$i}][3]/100);
		}
		if ($alearn['learn'.$i]==8 && $pa['wep_kind']=="C")	//灌篮高手称号
		{
			$att*=(1+$clskl[8][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
		{
			if (rand(0,99)<$clskl[6][${'a'.$i}][3]) $att*=(1+$clskl[6][${'a'.$i}][4]/100);
		}
	}
}

function rev_get_clubskill_bonus_fluc($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//伤害浮动值
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==8 && $pa['wep_kind']=="C")	//灌篮高手称号
		{
			$r+=$clskl[8][${'a'.$i}][2];
		}
	}
	return $r;
}
function rev_get_clubskill_bonus_counter($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//反击率加成
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==7 && $pa['wep_kind']=='C')	//灌篮高手称号
		{
			$r*=(1+$clskl[7][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==11)						//宛如疾风称号
		{
			$r*=(1+$clskl[11][${'a'.$i}][3]/100);
		}
		if ($blearn['learn'.$i]==13 && $pd['wep_kind']=='F')	//超能力者称号
		{
			$r*=(1-$clskl[13][${'b'.$i}][2]/100);
		}
	}
	return $r;
}
function rev_get_clubskill_bonus_dmg_rate($aclub,$askl,$pa,$bclub,$bskl,$pd)
{
	//最终伤害加成/减成，a为攻击方，b为防御方
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$ar=100;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==14)	//攻击方有晶莹技能，伤害大幅下降
		{
			$ar-=$clskl[14][${'a'.$i}][1];
		}
	}
	$br=100;
	for ($i=1; $i<=2; $i++)
	{
		if ($blearn['learn'.$i]==14)	//防御方有晶莹技能，伤害下降
		{
			$br-=$clskl[14][${'b'.$i}][2];
		}
	}
	$r = round($ar*$br)/10000*100; //高端的算法往往以低调的姿态示人
	return $r;
}
function rev_get_clubskill_bonus_dmg_val($club,$skl,$pa,$pd)
{
	//最终伤害增加值
	getskills2($clskl);
	getlearnt($learn,$club,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$rate = 0;
	for ($i=1; $i<=2; $i++)
	{
		if ($learn['learn'.$i]==15)	//攻击方有剔透技能，得到概率
		{
			$rate=$clskl[15][${'a'.$i}][1];
		}
	}
	$rate -= round($pa['rp']/20);
	if($rate < 0){$rate = 0;}
	$rpdmg = $pd['rp'] - $pa['rp'];
	$dice = diceroll(99);
	if($rpdmg > 0 && $dice < $rate){
		return $rpdmg;
	}
	return 0;
}
function rev_get_clubskill_rp_dec($clb,$skl)
{
	//RP增长率下降
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==14) $r=$clskl[14][${'a'.$i}][3];	//晶莹剔透1
	}
	//echo $r;
	return $r;
}

#### 原社团技能函数：
function get_clubskill_bonus_p($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2,&$att,&$def)
{
	//攻击防御加成系数
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$att=1; $def=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==2 && ${$prefix1.'wepk'}=="WP")	//铁拳无敌称号
		{
			$att*=(1+$clskl[2][${'a'.$i}][1]/100);
			if (rand(0,99)<$clskl[2][${'a'.$i}][2]) $def*=(1-$clskl[2][${'a'.$i}][3]/100);
		}
		if ($alearn['learn'.$i]==8 && ${$prefix1.'wepk'}=="WC")	//灌篮高手称号
		{
			$att*=(1+$clskl[8][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==6 && (${$prefix1.'wepk'}=="WG" || ${$prefix1.'wepk'}=="WJ"))	//狙击鹰眼称号
		{
			if (rand(0,99)<$clskl[6][${'a'.$i}][3]) $att*=(1+$clskl[6][${'a'.$i}][4]/100);
		}
	}
}

function get_clubskill_bonus($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2,&$att,&$def)
{
	//攻击防御力加成
	getskills2($clskl);
	global ${$prefix2.'wepk'}, ${$prefix2.'wepe'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$att=0; $def=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($blearn['learn'.$i]==1 && ${$prefix2.'wepk'}=="WP")	//铁拳无敌称号
		{
			$dup=$clskl[1][${'b'.$i}][1]/100*${$prefix2.'wepe'};
			if ($dup>2000) $dup=2000;
			$def+=$dup;
		}
	}
}

function get_clubskill_bonus_hitrate($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//命中率系数
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==3 && ${$prefix1.'wepk'}=="WK")	//见敌必斩称号
		{
			$r*=(1+$clskl[3][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==5 && (${$prefix1.'wepk'}=="WG" || ${$prefix1.'wepk'}=="WJ"))	//狙击鹰眼称号
		{
			$r*=(1+$clskl[5][${'a'.$i}][1]/100);
		}
		if ($blearn['learn'.$i]==12)						//宛如疾风称号
		{
			$r*=(1-$clskl[12][${'b'.$i}][1]/100);
		}
	}
	return $r;
}

function get_clubskill_bonus_imprate($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//武器损坏率系数
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==4 && ${$prefix1.'wepk'}=="WK")	//见敌必斩称号
		{
			$r*=(1-$clskl[4][${'a'.$i}][1]/100);
		}
	}
	return $r;
}

function get_clubskill_bonus_imfrate($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//防具损坏率系数
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==6 && (${$prefix1.'wepk'}=="WG" || ${$prefix1.'wepk'}=="WJ"))	//狙击鹰眼称号
		{
			$r*=(1+$clskl[6][${'a'.$i}][1]/100);
		}
	}
	return $r;
}

function get_clubskill_bonus_imftime($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//防具损坏效果系数
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==6 && (${$prefix1.'wepk'}=="WG" || ${$prefix1.'wepk'}=="WJ"))	//狙击鹰眼称号
		{
			$r+=$clskl[6][${'a'.$i}][2];
		}
	}
	return $r;
}

function get_clubskill_bonus_fluc($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//伤害浮动值
	getskills2($clskl);
	global ${$prefix1.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==8 && ${$prefix1.'wepk'}=="WC")	//灌篮高手称号
		{
			$r+=$clskl[8][${'a'.$i}][2];
		}
	}
	return $r;
}



function get_clubskill_bonus_counter($aclub,$askl,$prefix1,$bclub,$bskl,$prefix2)
{
	//反击率加成
	getskills2($clskl);
	global ${$prefix1.'wepk'}, ${$prefix2.'wepk'};
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==7 && ${$prefix1.'wepk'}=="WC")	//灌篮高手称号
		{
			$r*=(1+$clskl[7][${'a'.$i}][1]/100);
		}
		if ($alearn['learn'.$i]==11)						//宛如疾风称号
		{
			$r*=(1+$clskl[11][${'a'.$i}][3]/100);
		}
		if ($blearn['learn'.$i]==13 && ${$prefix2.'wepk'}=="WF")	//超能力者称号
		{
			$r*=(1-$clskl[13][${'b'.$i}][2]/100);
		}
	}
	return $r;
}	

function get_clubskill_bonus_hide($clb,$skl)
{
	//躲避率加成
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==9) $r*=(1+$clskl[9][${'a'.$i}][1]/100);	//拆蛋专家称号
		if ($alearn['learn'.$i]==11) $r*=(1+$clskl[11][${'a'.$i}][1]/100);	//宛如疾风称号
	}
	return $r;
}

function get_clubskill_bonus_active($aclub,$askl,$bclub,$bskl)
{
	//先攻率加成
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==9)	//拆蛋专家称号
		{
			$r*=(1+$clskl[9][${'a'.$i}][2]/100);
		}
		if ($alearn['learn'.$i]==11)	//宛如疾风称号
		{
			$r*=(1+$clskl[11][${'a'.$i}][2]/100);
		}
		if ($blearn['learn'.$i]==9)	//拆蛋专家称号
		{
			$r/=(1+$clskl[9][${'b'.$i}][2]/100);
		}
		if ($blearn['learn'.$i]==11)	//宛如疾风称号
		{
			$r/=(1+$clskl[11][${'b'.$i}][2]/100);
		}
	}
	return $r;
}	

function get_clubskill_bonus_escrate($clb,$skl)
{
	//陷阱回避率加成
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==10) $r*=(1+$clskl[10][${'a'.$i}][1]/100);	//拆蛋专家称号
	}
	return $r;
}

function get_clubskill_bonus_reuse($clb,$skl)
{
	//陷阱再利用率加成
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==10) $r*=(1+$clskl[10][${'a'.$i}][2]/100);	//拆蛋专家称号
	}
	return $r;
}

function get_clubskill_bonus_spd($clb,$skl)
{
	//体力消耗减少
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=1;
	for ($i=1; $i<=2; $i++)
	{
		if ($clb==9) $r*=(1-$clskl[13][${'a'.$i}][1]/100);	//超能力者称号
	}
	return $r;
}

//function get_clubskill_bonus_dmg($club,$skl,$w=0){
//	//最终伤害加成/减成
//	getskills2($clskl);
//	$a1=((int)($skl/10))%10; $a2=$skl%10;
//	$r=1;
//	for ($i=1; $i<=2; $i++)
//	{
//		if ($club==13)	//晶莹剔透称号
//		{
//			$r+=$w ? $clskl[14][${'a'.$i}][1] : $clskl[14][${'a'.$i}][2];//$w=1表示对敌伤害，反之是所受伤害
//		}
//	}
//	return $r;
//}

function get_clubskill_bonus_dmg_rate($aclub,$askl,$bclub,$bskl)
{
	//最终伤害加成/减成，a为攻击方，b为防御方
	getskills2($clskl);
	getlearnt($alearn,$aclub,$askl);
	getlearnt($blearn,$bclub,$bskl);
	$a1=((int)($askl/10))%10; $a2=$askl%10;
	$b1=((int)($bskl/10))%10; $b2=$bskl%10;
	$ar=100;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==14)	//攻击方有晶莹技能，伤害大幅下降
		{
			$ar-=$clskl[14][${'a'.$i}][1];
		}
	}
	$br=100;
	for ($i=1; $i<=2; $i++)
	{
		if ($blearn['learn'.$i]==14)	//防御方有晶莹技能，伤害下降
		{
			$br-=$clskl[14][${'b'.$i}][2];
		}
	}
	$r = round($ar*$br)/10000;
	return $r;
}

function get_clubskill_bonus_dmg_val($club,$skl,$rp,$w_rp)
{
	//最终伤害增加值
	getskills2($clskl);
	getlearnt($learn,$club,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$rate = 0;
	for ($i=1; $i<=2; $i++)
	{
		if ($learn['learn'.$i]==15)	//攻击方有剔透技能，得到概率
		{
			$rate=$clskl[15][${'a'.$i}][1];
		}
	}
	$rate -= round($rp/20);
	if($rate < 0){$rate = 0;}
	$rpdmg = $w_rp - $rp;
	if($rpdmg > 0 && rand(0,99) < $rate){
		return $rpdmg;
	}
	return 0;
}

function get_clubskill_rp_dec($clb,$skl)
{
	//RP增长率下降
	getskills2($clskl);
	getlearnt($alearn,$clb,$skl);
	$a1=((int)($skl/10))%10; $a2=$skl%10;
	$r=0;
	for ($i=1; $i<=2; $i++)
	{
		if ($alearn['learn'.$i]==14) $r=$clskl[14][${'a'.$i}][3];	//晶莹剔透1
	}
	//echo $r;
	return $r;
}

?>
