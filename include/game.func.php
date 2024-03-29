<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function init_playerdata($data=NULL)
{
	global $baseexp,$weather,$fog,$log,$upexp,$lvlupexp,$iconImg,$iconImgB;
	global $pls,$weather;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$upexp = round(($lvl*$baseexp)+(($lvl+1)*$baseexp));
	$lvlupexp = $upexp - $exp;

	$iconImg = $gd.'_'.$icon.'.gif'; 
	if(file_exists('img/'.$gd.'_'.$icon.'a.gif')) $iconImgB = $gd.'_'.$icon.'a.gif'; 

	if(($weather == 8)||($weather == 9)||($weather == 12)) 
	{
		$fog = true;
	}

	$clbpara = get_clbpara($clbpara);
}

function init_profile($data=NULL)
{
	global $infinfo,$hpcolor,$spcolor,$newhpimg,$newspimg,$karma;
	global $notim,$nospk,$iteminfo,$itemspkinfo,$weather,$definfo,$atkinfo;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	foreach (Array('wep','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $value) 
	{
		if(strpos($value,'itm')!==false)
		{
			$k_value = str_replace('itm','itmk',$value);
			$s_value = str_replace('itm','itms',$value);
			$sk_value = str_replace('itm','itmsk',$value);
		}
		else 
		{
			$k_value = $value.'k';
			$s_value = $value.'s';
			$sk_value = $value.'sk';
		}

		global ${$value.'_words'},${$k_value.'_words'},${$s_value.'_words'},${$sk_value.'_words'};

		# 初始化名称样式
		${$value.'_words'} = parse_nameinfo_desc($$value,$horizon);
		# 初始化类别样式
		${$k_value.'_words'} = parse_kinfo_desc($$k_value,$$sk_value);
		# 初始化属性样式
		${$sk_value.'_words'} = parse_skinfo_desc($$sk_value,$$k_value,1);
	}

	$karma = ($rp * $killnum - $def )+ $att;

	$hpcolor = 'clan';
	if($hp <= 0 ){
		$hpcolor = 'red';
	} elseif($hp <= $mhp*0.2){
		$hpcolor = 'red';
	} elseif($hp <= $mhp*0.5){
		$hpcolor = 'yellow';
	}
	
	if($sp <= $msp*0.2){
		$spcolor = 'grey';
	} elseif($sp <= $msp*0.5){
		$spcolor = 'yellow';
	} else {
		$spcolor = 'clan';
	}
	
	$newhppre = 5+floor(151*(1-$hp/$mhp));
	$newhpimg = '<img src="img/red2.gif" style="position:absolute; clip:rect('.$newhppre.'px,55px,160px,0px);">';
	$newsppre = 5+floor(151*(1-$sp/$msp));
	$newspimg = '<img src="img/yellow2.gif" style="position:absolute; clip:rect('.$newsppre.'px,55px,160px,0px);">';

	$clbpara = get_clbpara($clbpara);

	if(!check_skill_unlock('buff_shield',$pdata))
	{
		global $shield_info;
		$shield_info = "<span class=\"blueseed\" tooltip2=\"【护盾】：可抵消等同于护盾值的伤害。护盾值只在抵消属性伤害时消耗，抵消电击伤害时双倍消耗。护盾存在时不会受到反噬伤害或陷入异常状态。\">(".get_skillpara('buff_shield','svar',$clbpara).")</span>";
	}

	include_once GAME_ROOT.'./include/game/revattr.func.php';
	$atkinfo = \revattr\get_base_att($pdata,$pdata,1,1);
	$definfo = \revattr\get_base_def($pdata,$pdata,1,1);
	return;
}

function init_battle_rev($pa,$pd,$ismeet=0)
{
	global $sdata,$tdata,$battle_title,$hpcolor;
	include_once GAME_ROOT.'./include/init.func.php';
	//初始化头像显示
	init_icon_states($pa,$pd,1); init_icon_states($pd,$pa,$ismeet);
	//初始化状态显示
	init_hp_states($pa,$pd,1); init_hp_states($pd,$pa,$ismeet);
	//初始化武器信息
	init_wep_states($pa,$pd,1); init_wep_states($pd,$pa,$ismeet);
	//初始化异常状态信息
	init_inf_states($pa,$pd,1); init_inf_states($pd,$pa,$ismeet);
	//传参
	$sdata = $pa; $tdata = $pd;
}

function init_bgm($force_update=0)
{
	global $command,$gamecfg,$bgmname;
	global $default_volume,$event_bgm,$pls_bgm,$parea_bgm,$regular_bgm,$bgmbook,$bgmlist;
	
	global $pdata;
	extract($pdata,EXTR_REFS);
	$clbpara = get_clbpara($clbpara);

	# 初始化
	$event_flag = 0;
	$bgmid = $bgmlink = $bgmtype = $bgmplayer = $bgmnums = '';

	# 存在最优先的事件BGM队列
	if(isset($clbpara['event_bgmbook']))
	{
		# 检查是否需要更新播放列表
		if(array_diff($clbpara['bgmbook'],$clbpara['event_bgmbook']))
		{
			# 重置当前播放列表
			$clbpara['bgmbook'] = $clbpara['event_bgmbook'];
			$force_update = 1;
		}
	}
	# 存在次优先的地图BGM队列
	elseif(isset($clbpara['pls_bgmbook']))
	{
		# 检查是否需要更新播放列表
		if(array_diff($clbpara['bgmbook'],$clbpara['pls_bgmbook']))
		{
			# 重置当前播放列表
			$clbpara['bgmbook'] = $clbpara['pls_bgmbook'];
			$force_update = 1;
		}
	}
	# 检查是否需要更新默认BGM列表
	else
	{
		if(empty($clbpara['bgmbook']) || array_diff($clbpara['bgmbook'],$clbpara['valid_bgmbook']))
		{
			# 重置当前播放列表
			$clbpara['bgmbook'] = $clbpara['valid_bgmbook'];
			$force_update = 1;
		}
	}

	# 刷新页面或输入强制重载参数时，重载播放器
	if($command == 'enter' || $force_update)
	{
		$booklist = $bgmarr = Array();	
		# 为播放列表中的曲集关联对应BGM名、链接与种类
		foreach($clbpara['bgmbook'] as $book)
		{
			foreach($bgmbook[$book] as $bgmid)
			{
				$bgmarr[$bgmid]['name'] = $bgmlist[$bgmid]['name'];
				$bgmarr[$bgmid]['url'] = $bgmlist[$bgmid]['url'];
				$bgmarr[$bgmid]['type'] = $bgmlist[$bgmid]['type'];
				$bgmarr[$bgmid]['id'] = $bgmid;
			}
		}
		# 计数当前播放队列中的BGM数
		$bgmnums = count($bgmarr)-1;
		$nowbgm = 0;
		# 初始化首位BGM
		shuffle($bgmarr);
		$bgmname = $bgmarr[0]['name'];
		$bgmlink = $bgmarr[0]['url'];
		$bgmtype = $bgmarr[0]['type'];
		$bgmid = $bgmarr[0]['id'];
		$json_bgmarr = json_encode($bgmarr);
		# 将当前播放的BGM编号保存于缓存内 留待以后用作播放记忆
		gsetcookie('nowbgmid',$bgmid,0,0);
		#初始化默认音量
		$volume = isset($_COOKIE["volume"]) ? filter_var($_COOKIE["volume"],FILTER_VALIDATE_FLOAT)*100 : $default_volume;
		$volume_r = isset($volume) ? round($volume/100,2) : round($default_volume/100,2);
		# 生成播放器与播放队列 太野蛮了……嘻嘻……
		if(!empty($bgmlink) && !empty($bgmtype))
		{
$bgmplayer = <<<EOT
			<audio id="gamebgm" autoplay controls=1" onplay="$('gamebgm').volume=$('nowbgmvolume').innerHTML;">
				<source id="gbgm" src="$bgmlink" type="$bgmtype">
			</audio>
			<div id="bgmlist">$json_bgmarr</div>
			<div id="nowbgm">0</div>
			<div id="nowbgmvolume">$volume_r</div>
			<script>
				gamebgm.addEventListener('ended', function () {
					changeBGM();
				}, false);
			</script>
EOT;
		}
		return $bgmplayer;
	}
	else
	{
		return;
	}
}

function init_mapdata(){
	global $pls,$plsinfo,$xyinfo,$hack,$arealist,$areanum,$areaadd;

	$mpp = Array();
	$mapvcoordinate = Array('A','B','C','D','E','F','G','H','I','J');
	for($i=0;$i<count($plsinfo);$i++)
	{
		if($hack || array_search($i,$arealist) > ($areanum + $areaadd)){
			$plscolor[$i] = 'minimapspanlime';
		} elseif(array_search($i,$arealist) <= $areanum) {
			$plscolor[$i] = 'minimapspanred';
		} else {
			$plscolor[$i] = 'minimapspanyellow';
		}
		$position=explode('-',$xyinfo[$i]);
		$mpp[$position[0]][$position[1]]=$i;
	}

	$mapcontent = '<TABLE border="1" cellspacing="0" cellpadding="0" background="map/neomap.jpg" style="background-size:478px 418px;position:relative;background-repeat:no-repeat;background-position:right bottom;">';	
	$mapcontent .= '<TR align="center"><TD colspan="11" height="24" class=b1 align=center>战场地图</TD></TR>';
	$mapcontent .= '<TR align="center">
			<TD width="42" height="36" class=map align=center><div class=nttx>坐标</div></TD>';
	for($x=1;$x<=10;$x++)
	{
		$mapcontent .= '<TD width="42" height="36" class=map align=center><div class=nttx>'.$x.'</div></TD>';
	}
	$mapcontent .= '</TR>';
	for($i=0;$i<10;$i++){
		$mapcontent .= '<tr align="center"><TD class=map align=center><div class=nttx>'.$mapvcoordinate[$i].'</div></TD>';
		for($j=1;$j<=10;$j++){
			if(isset($mpp[$mapvcoordinate[$i]][$j]))
			{
				$mapcontent .="<td width=\"42\" height=\"36\" class=\"map2\" align=\"middle\"><a onclick=\"closeDialog($('terminal'));$('mode').value='command';$('command').value='move';$('moveto').value='{$mpp[$mapvcoordinate[$i]][$j]}';postCmd('gamecmd','command.php');this.disabled=true;\"><span class=\"{$plscolor[$mpp[$mapvcoordinate[$i]][$j]]}\">{$plsinfo[$mpp[$mapvcoordinate[$i]][$j]]}</span></a></td>";
			}else{
				$mapcontent .= '<td width="42" height="36" class="map2" align=middle><IMG src="map/blank.gif" width="42" height="36" border=0></td>';
			}
		}
		$mapcontent .= '</tr>';
	}
	$mapcontent .= '</table>';
	return $mapcontent;
}

function init_clubskillsdata($sk,$data)
{
	global $cskills;
	$sk_dir = 'skill_'.$sk;
	# 本地存在对应的技能模板，返回模板
	if(file_exists(GAME_ROOT."./templates/default/".$sk_dir.".htm"))
	{
		return Array($sk_dir);
	}
	# 本地不存在模板，按照预设信息生成一个
	elseif(array_key_exists($sk,$cskills))
	{
		return $sk;
	}
	return 0;
}

function check_add_searchmemory($id,$itp,$nm,&$data=NULL)
{
	global $allow_semo,$smeo_max,$log;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if($allow_semo)
	{
		$now_smeo = empty($data['clbpara']['smeo']) ? 0 : count($data['clbpara']['smeo']);
		if($now_smeo >= $smeo_max)
		{
			lost_searchmemory(NULL,$data);
		}
		$nm_desc = $itp == 'corpse' ? $nm.'的尸体' : $nm;
		$flag = 0;
		if(empty($data['clbpara']['smeo']))
		{
			$data['clbpara']['smeo'] = Array();
		}
		else
		{
			foreach($data['clbpara']['smeo'] as $sid => $sm)
			{
				if($sm[0] == $id && $sm[1] == $itp)
				{
					$log .= "<span class='grey'>{$nm_desc}本来就在你的视野里，不过这回你对它的印象更深了。</span><br>"; 
					lost_searchmemory($sid,$data);
					$flag = 1;
					break;
				}
			}
		}		
		array_push($data['clbpara']['smeo'], Array($id,$itp,$nm));
		if(!$flag) $log .= "<span class='grey'>你设法将{$nm_desc}保持在视野范围内。</span><br>"; 
	}
	return;
}

function lost_searchmemory($key=NULL,&$data=NULL)
{
	global $allow_semo,$smeo_max,$log;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	if(!empty($data['clbpara']['smeo']))
	{
		if($key == 'all')
		{
			$data['clbpara']['smeo'] = Array();
			$log .= '<span class="grey">你先前所见的一切东西都离开了视线。</span><br>';
		}
		elseif(isset($key))
		{
			unset($data['clbpara']['smeo'][$key]);
		}
		else
		{
			$n0 = reset($data['clbpara']['smeo']);
			$n0_nm_desc = $n0[1] == 'corpse' ? $n0[2].'的尸体' : $n0[2];
			$log .= "<span class=\"grey\">{$n0_nm_desc}从你的视野里消失了。</span><br>";
			array_shift($data['clbpara']['smeo']);
		}
	}
	return;
}

function get_remaincdtime($pid){
	$psdata = get_pstate($pid);
	if($psdata){
		$cdover = $psdata['cdsec']*1000 + $psdata['cdmsec'] + $psdata['cdtime'];
		$nowmtime = floor(getmicrotime()*1000);
		$rmtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
		return floor($rmtime);
	}else{
		return 0;
	}	
}

// 检查时效性技能是否达到时限
function check_skilllasttimes(&$data)
{
	global $cskills,$log,$now,$name;
	$nm = ($data['type'] || $data['name'] != $name) ? $data['name'] : '你';
	$data['pure_flag'] = 0;
	if(!empty($data['clbpara']['lasttimes']))
	{
		//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		foreach($data['clbpara']['lasttimes'] as $sk => $lts)
		{
			$stm = isset($data['clbpara']['starttimes'][$sk]) ? $data['clbpara']['starttimes'][$sk] : 0;
			# 技能已达到时效
			if($now > $lts+$stm)
			{
				$sk_name = $cskills[$sk]['name'];
				if(get_skilltags($sk,'buff'))
				{
					$log.="<span class='yellow'>「{$sk_name}」</span>的效果结束了！<br>";
				}
				else 
				{
					$log.="{$nm}从<span class='yellow'>「{$sk_name}」</span>状态中恢复了！<br>";
				}
				lostclubskill($sk,$data['clbpara']);
				$data['pure_flag'] = 1;
			}
		}
	}
	if($nm != '你')
	{
		player_save($data);
		return $data;
	}
	return $data['pure_flag'];
}

function create_dummy_playerdata($clb=0)
{
	$data = update_db_player_structure(1);
	foreach($data as $key => $type)
	{
		if(strpos($type,'int')!==false) $v=0;
		else $v='';
		$data[$key] = $v;
	}
	$data['clbpara'] = get_clbpara($data['clbpara']);
	if(!empty($clb))
	{
		include_once GAME_ROOT.'./include/game/clubslct.func.php';
		changeclub($clb,$data);
		switch ($clb) {
			case 1:
				$data['wepk'] = 'WP';
				break;
			case 2:
				$data['wepk'] = 'WK';
				break;
			case 3:
				$data['wepk'] = 'WC';
				break;
			case 4:
				$data['wepk'] = 'WG';
				break;
			case 5:
				$data['wepk'] = 'WD';
				break;
			case 9:
				$data['wepk'] = 'WF';
				break;
			default:
				$data['wepk'] = 'WN';
		}
	}
	$data['money'] = 10000;
	return $data;
}
//通过名字抓取指定玩家数据，只能抓玩家
function fetch_playerdata_by_name($n)
{
	global $db,$gtablepre,$tablepre;
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$n' AND type = 0");
	if(!$db->num_rows($result)) return NULL;
	$data = $db->fetch_array($result);

	check_player_misc_states($data);

	return $data;
}
//通过pid抓取指定玩家/NPC数据
function fetch_playerdata_by_pid($id)
{
	global $db,$gtablepre,$tablepre;
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid = '$id'");
	if(!$db->num_rows($result)) return NULL;
	$data = $db->fetch_array($result);

	check_player_misc_states($data);

	return $data;
}
//用于将指定player数据存回数据库
function player_save($data){
	global $db,$gtablepre,$tablepre;
	$ndata = Array();
	if(isset($data['pid'])){
		$pid = $data['pid'];
		//检查杂项成就
		include_once GAME_ROOT.'./include/game/achievement.func.php';
		check_misc_achievement_rev($data);
		$ndata = player_format_with_db_structure($data);
		unset($data);
		$db->array_update("{$tablepre}players",$ndata,"pid='$pid'");
	}
	return;
}

function check_player_misc_states(&$data)
{
	# 转码clbpara
	if(!empty($data['clbpara'])) $data['clbpara'] = get_clbpara($data['clbpara']);
	# 刷新时效性状态
	if(!empty($data['clbpara']['lasttimes'])) check_skilllasttimes($data);
	# 刷新装备状态
	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	reload_equip_items($data);
	reload_set_items($data);
	return $data;
}

//获取安全地图范围
function get_safe_plslist($mode=1)
{
	global $areanum,$arealist,$hack,$deepzones;
	$r = $hack ? $arealist : array_slice($arealist,$areanum+1);
	if($mode) $r = array_diff($r, $deepzones);
	return $r;
}

/*function w_save($id){
	global $db,$tablepre,$w_name,$w_pass,$w_type,$w_endtime,$w_deathtime,$w_gd,$w_sNo,$w_icon,$w_club,$w_hp,$w_mhp,$w_sp,$w_msp,$w_att,$w_def,$w_pls,$w_lvl,$w_exp,$w_money,$w_bid,$w_inf,$w_rage,$w_pose,$w_tactic,$w_killnum,$w_state,$w_wp,$w_wk,$w_wg,$w_wc,$w_wd,$w_wf,$w_teamID,$w_teamPass,$w_wep,$w_wepk,$w_wepe,$w_weps,$w_arb,$w_arbk,$w_arbe,$w_arbs,$w_arh,$w_arhk,$w_arhe,$w_arhs,$w_ara,$w_arak,$w_arae,$w_aras,$w_arf,$w_arfk,$w_arfe,$w_arfs,$w_art,$w_artk,$w_arte,$w_arts,$w_itm0,$w_itmk0,$w_itme0,$w_itms0,$w_itm1,$w_itmk1,$w_itme1,$w_itms1,$w_itm2,$w_itmk2,$w_itme2,$w_itms2,$w_itm3,$w_itmk3,$w_itme3,$w_itms3,$w_itm4,$w_itmk4,$w_itme4,$w_itms4,$w_itm5,$w_itmk5,$w_itme5,$w_itms5,$w_itm6,$w_itmk6,$w_itme6,$w_itms6,$w_wepsk,$w_arbsk,$w_arhsk,$w_arask,$w_arfsk,$w_artsk,$w_itmsk0,$w_itmsk1,$w_itmsk2,$w_itmsk3,$w_itmsk4,$w_itmsk5,$w_itmsk6,$w_rp,$w_action,$w_achievement,$w_skillpoint;
	
	$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',deathtime='$w_deathtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',action='$w_action',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6',rp='$w_rp',achievement='$w_achievement',skillpoint='$w_skillpoint'    WHERE pid='$id'");
	//$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6' WHERE pid='$id'");

	return ;
}

function w_save2(&$data){
	global $db,$tablepre;
	if(isset($data)){
		extract($data,EXTR_PREFIX_ALL,'w');
		$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',deathtime='$w_deathtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6',rp='$w_rp',achievement='$w_achievement',skillpoint='$w_skillpoint'  WHERE pid='$w_pid'");
		//$db->query("UPDATE {$tablepre}players SET name='$w_name',pass='$w_pass',type='$w_type',endtime='$w_endtime',gd='$w_gd',sNo='$w_sNo',icon='$w_icon',club='$w_club',hp='$w_hp',mhp='$w_mhp',sp='$w_sp',msp='$w_msp',att='$w_att',def='$w_def',pls='$w_pls',lvl='$w_lvl',exp='$w_exp',money='$w_money',bid='$w_bid',inf='$w_inf',rage='$w_rage',pose='$w_pose',tactic='$w_tactic',state='$w_state',killnum='$w_killnum',wp='$w_wp',wk='$w_wk',wg='$w_wg',wc='$w_wc',wd='$w_wd',wf='$w_wf',teamID='$w_teamID',teamPass='$w_teamPass',wep='$w_wep',wepk='$w_wepk',wepe='$w_wepe',weps='$w_weps',wepsk='$w_wepsk',arb='$w_arb',arbk='$w_arbk',arbe='$w_arbe',arbs='$w_arbs',arbsk='$w_arbsk',arh='$w_arh',arhk='$w_arhk',arhe='$w_arhe',arhs='$w_arhs',arhsk='$w_arhsk',ara='$w_ara',arak='$w_arak',arae='$w_arae',aras='$w_aras',arask='$w_arask',arf='$w_arf',arfk='$w_arfk',arfe='$w_arfe',arfs='$w_arfs',arfsk='$w_arfsk',art='$w_art',artk='$w_artk',arte='$w_arte',arts='$w_arts',artsk='$w_artsk',itm0='$w_itm0',itmk0='$w_itmk0',itme0='$w_itme0',itms0='$w_itms0',itmsk0='$w_itmsk0',itm1='$w_itm1',itmk1='$w_itmk1',itme1='$w_itme1',itms1='$w_itms1',itmsk1='$w_itmsk1',itm2='$w_itm2',itmk2='$w_itmk2',itme2='$w_itme2',itms2='$w_itms2',itmsk2='$w_itmsk2',itm3='$w_itm3',itmk3='$w_itmk3',itme3='$w_itme3',itms3='$w_itms3',itmsk3='$w_itmsk3',itm4='$w_itm4',itmk4='$w_itmk4',itme4='$w_itme4',itms4='$w_itms4',itmsk4='$w_itmsk4',itm5='$w_itm5',itmk5='$w_itmk5',itme5='$w_itme5',itms5='$w_itms5',itmsk5='$w_itmsk5',itm6='$w_itm6',itmk6='$w_itmk6',itme6='$w_itme6',itms6='$w_itms6',itmsk6='$w_itmsk6' WHERE pid='$w_pid'");
	}
	return;
}*/

function addnoise($wp_kind, $wsk, $ntime, $npls, $nid1, $nid2, $nmode) 
{
	//在隐藏地图内不会传出声音信息
	global $plsinfo;
	if(!array_key_exists($npls,$plsinfo)) return;
	
	if ((($wp_kind == 'G') && (strpos ( $wsk, 'S' ) === false)) || ($wp_kind == 'F')) {
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = $nmode;
		save_combatinfo ();
	} elseif (strpos ( $wsk, 'd' ) !== false){
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = 'D';
		save_combatinfo ();
	}
	if (strlen($wp_kind)>=3){
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode,$wep;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = $wp_kind;
		save_combatinfo ();
	}
	return;
}

//销毁尸体
function destory_corpse(&$edata)
{
	if($edata && $edata['hp'] <= 0)
	{
		$edata['action'] = ''; $edata['bid'] = 0;
		$edata['state'] = 16; $edata['hp'] = 0; $edata['money'] = 0; $edata['pls'] = 254;
		$edata['weps'] = 0;$edata['arbs'] = 0;$edata['arhs'] = 0;$edata['aras'] = 0;$edata['arfs'] = 0;$edata['arts'] = 0;
		$edata['itms0'] = 0;$edata['itms1'] = 0;$edata['itms2'] = 0;$edata['itms3'] = 0;$edata['itms4'] = 0;$edata['itms5'] = 0;$edata['itms6'] = 0;
		player_save($edata);
	}
	return;
}

# NPC自动换装
#说实话没有完全看懂，但是能跑就行
function npc_changewep_rev(&$pa,&$pd,$acitve)
{
	global $now,$log;
	global $rangeinfo,$ex_dmg_def;

	if(!$pa['type'] || $pa['club'] != 98) return;

	$dice = diceroll(99);
	
	if($dice > 50)
	{
		$weplist = Array();
		$wepklist = Array($pa['wepk']); $weplist2 = Array();
		for($i=0;$i<=6;$i++)
		{
			if(!empty($pa['itms'.$i]) && !empty($pa['itme'.$i]) && strpos($pa['itmk'.$i],'W')===0)
			{
				$weplist[] = Array($i,$pa['itm'.$i],$pa['itmk'.$i],$pa['itme'.$i],$pa['itms'.$i],$pa['itmsk'.$i]);
				$wepklist[] = $pa['itmk'.$i];
			}
		}
		if(!empty($weplist))
		{
			$wepklist = array_unique($wepklist);
			$temp_pd_ex_keys = array_merge(\revattr\get_equip_ex_array($pd),\revattr\get_wep_ex_array($pd));
			$wepkAI = $wepskAI = true;
			if(!empty($temp_pd_ex_keys))
			{
				if(count($wepklist)<=1) $wepkAI = false;
				foreach($temp_pd_ex_keys as $ex)
				{
					if(in_array($ex,Array('A','B'))) $wepkAI = false;
					if(in_array($ex,Array('a','b'))) $wepskAI = false;
				}
			}
			if($wepkAI)
			{
				$wepk_temp = $pa['wepk'];
				foreach($weplist as $val)
				{
					if($rangeinfo[substr($val[2],1,1)] >= $rangeinfo[substr($wepk_temp,1,1)] && !in_array(substr($val[2],1,1),$temp_pd_ex_keys))
					{
						$weplist2[] = $val;
					}
				}
				if($weplist2)
				{
					$weplist = $weplist2;
				}
			}
			if($wepskAI && $weplist)
			{
				$minus = array();
				foreach($weplist as $val)
				{
					foreach($ex_dmg_def as $key => $val2){
						if(strpos($val[5],$key)!==false && !in_array($val2,$temp_pd_ex_keys)){
							$minus[] = $val;
						}
					}
				}
				if(count($minus) < count($weplist)){
					$weplist = array_diff($weplist,$minus);
				}				
			}
		}
		else 
		{
			//没有获取到可换装列表，直接返回
			return;
		}
		
		if(!empty($weplist))
		{
			$oldwep = $pa['wep'];
			shuffle($weplist);
			$chosen = $weplist[0];$c = $chosen[0];
			# 刷新套装效果
			reload_single_set_item($pa,'wep',$oldwep);
			reload_single_set_item($pa,'wep',$chosen[1],1);
			$pa['itm'.$c] = $pa['wep']; $pa['itmk'.$c] = $pa['wepk']; $pa['itmsk'.$c] = $pa['wepsk'];
			$pa['itme'.$c] = $pa['wepe']; $pa['itms'.$c] = $pa['weps'];
			$pa['wep'] = $chosen[1]; $pa['wepk'] = $chosen[2]; $pa['wepe'] = $chosen[3]; $pa['weps'] = $chosen[4]; $pa['wepsk'] = $chosen[5];
			# 重载基础攻击信息
			\revattr\get_wep_kind($pa);
			$pa['wep_range'] = \revattr\get_wep_range($pa);
			$pa['wep_skill'] = \revattr\get_wep_skill($pa);
			$pa['wep_name'] = $pa['wep'];
			$pa['change_wep_log'] = "<span class=\"yellow\">{$pa['nm']}</span>将手中的<span class=\"yellow\">{$oldwep}</span>卸下，装备了<span class=\"yellow\">{$pa['wep']}</span>！<br>";
		}
	}
	return;
}

# NPC喊话
# pa指npc pd指另一视角
function npc_chat_rev(&$pa,&$pd,$mode='') 
{
	global $npcchat;
	if(!empty($npcchat[$pa['type']][$pa['name']])) 
	{
		$nchat = $npcchat[$pa['type']][$pa['name']];
		$chatcolor = $nchat['color'];
		$npcwords = !empty($chatcolor) ? "<span class = \"{$chatcolor}\">" : '<span>';
		switch ($mode) 
		{
			case 'attack' :
				if (empty($pa['itmsk0'])) 
				{
					$npcwords .= "{$nchat[0]}";
					$pa['itmsk0'] = 1;
				}
				elseif ($pa['hp'] > ($pa['mhp'] / 2)) 
				{
					$dice = rand ( 1, 2 );
					$npcwords .= "{$nchat[$dice]}";
				} 
				else 
				{
					$dice = rand ( 3, 4 );
					$npcwords .= "{$nchat[$dice]}";
				}
				break;
			case 'defend' :
				if (empty($pa['itmsk0']))
				{
					$npcwords .= "{$nchat[0]}";
					$pa['itmsk0'] = 1;
				}
				elseif($pa['hp'] > ($pa['mhp'] / 2)) 
				{
					$dice = rand ( 5, 6 );
					$npcwords .= "{$nchat[$dice]}";
				} 
				else 
				{
					$dice = rand ( 7, 8 );
					$npcwords .= "{$nchat[$dice]}";
				}
				break;
			case 'death' :
				$npcwords .= "{$nchat[9]}";
				break;
			case 'escape' :
				$npcwords .= "{$nchat[10]}";
				break;
			case 'cannot' :
				$npcwords .= "{$nchat[11]}";
				break;
			case 'critical' :
				$npcwords .= "{$nchat[12]}";
				break;
			case 'kill' :
				$npcwords .= "{$pa['nm']}对{$pd['nm']}说道：{$nchat[13]}";
				break;
		}
		$npcwords .= '</span><br>';
		return $npcwords;
	} 
	elseif ($mode == 'death') 
	{
		global $lwinfo;
		if (is_array($lwinfo[$pa['type']])) 
		{
			$lastword = $lwinfo[$pa['type']][$pa['name']];
		} 
		else 
		{
			$lastword = $lwinfo[$pa['type']];
		}
		$npcwords = "<span class=\"yellow\">“{$lastword}”</span><br>";
		return $npcwords;
	}
	else 
	{
		return;
	}
}


?>
