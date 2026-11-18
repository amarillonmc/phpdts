<?php

define('CURSCRIPT', 'help');

require './include/common.inc.php';
require './include/game.func.php';
include_once GAME_ROOT.'./include/game/itemplace.func.php';

$no_skill_input = 1;
$help_ruleset_id = function_exists('get_current_ruleset_id') ? get_current_ruleset_id() : '';
$help_ending_v2 = function_exists('ending_routes_enabled') && ending_routes_enabled();
$help_ruleset_cfg = (!empty($help_ruleset_id) && function_exists('get_ruleset_config')) ? get_ruleset_config($help_ruleset_id) : false;
$help_ruleset_name = ($help_ruleset_cfg && !empty($help_ruleset_cfg['name'])) ? $help_ruleset_cfg['name'] : $help_ruleset_id;
if($help_ruleset_cfg && isset($help_ruleset_cfg['club_skills']) && empty($help_ruleset_cfg['club_skills'])) {
	$club_skillslist = array();
} elseif(!empty($club_skillslist) && is_array($club_skillslist)) {
	foreach(array_keys($club_skillslist) as $clb_k) {
		if(!isset($clubinfo[$clb_k])) unset($club_skillslist[$clb_k]);
	}
}

$mixfile = config('mixitem',$gamecfg);
$shopfile = config('shopitem',$gamecfg);
$mapitemfile = config('mapitem',$gamecfg);
$synfile = config('synitem',$gamecfg);
$ovlfile = config('overlay',$gamecfg);
$presentfile = config('present',$gamecfg);
$boxfile = config('box',$gamecfg);
$vnmixfile = config('vnmixitem',$gamecfg);
$npcfile = config('npc',$gamecfg);
$addnpcfile = config('addnpc',$gamecfg);
$evonpcfile = config('evonpc',$gamecfg);

$mixinfo = $vn_mixinfo = array();
if(file_exists($mixfile)) include $mixfile;
if(file_exists($vnmixfile)) include $vnmixfile;

$mix_cache_key = !empty($help_ruleset_id) ? preg_replace('/[^A-Za-z0-9_]/', '', $help_ruleset_id) : ($help_ending_v2 ? 'default' : 'legacy');
$help_cache_dir = GAME_ROOT.'./gamedata/templates';
if(!is_dir($help_cache_dir)) @mkdir($help_cache_dir, 0777, true);
$writefile = $help_cache_dir.'/mixhelp_'.$mix_cache_key.'.htm';

$npcinfo = $anpcinfo = $enpcinfo = array();
if(file_exists($npcfile)) include $npcfile;
if(file_exists($addnpcfile)) include $addnpcfile;
if(file_exists($evonpcfile)) include $evonpcfile;

for ($i=1; $i<=6; $i++) $itemlst[$i]=$i;

# 先将addnpc加入npc队列，确保无主类的类别（如type 89、12）继承addnpc外层默认属性
foreach($anpcinfo as $akey => $anpcs)
{
	if(!isset($npcinfo[$akey]))
	{
		$npcinfo[$akey] = $anpcs;
		unset($npcinfo[$akey]['sub']);
	}
	if(!empty($anpcs['sub']))
	{
		foreach($anpcs['sub'] as $aid => $anpc) $npcinfo[$akey]['asub'][$aid] = $anpc;
	}
}
# 再将evonpc加入npc队列
foreach($enpcinfo as $ekey => $enpcs)
{
	if(!isset($npcinfo[$ekey])) $npcinfo[$ekey] = array();
	foreach($enpcs as $sname => $enpc) $npcinfo[$ekey]['esub'][$sname] = $enpc;
}
# 拆分幻影执行官（旧版三执行官 0..2 与朱鹮执行官 3..5），并排除白凰的临时符灵幻象子项
if(!empty($npcinfo[7]['asub']))
{
	foreach($npcinfo[7]['asub'] as $aid => $anpc)
	{
		if($aid >= 3) $npcinfo[7]['asub_ibis'][$aid] = $anpc;
		else $npcinfo[7]['asub_classic'][$aid] = $anpc;
	}
}
if(isset($npcinfo[34]['asub'][8])) unset($npcinfo[34]['asub'][8]);

# RuleSet可收束帮助页中的兼容子类型，并补充本地说明。
# A RuleSet may collapse compatibility-only help variants and add local descriptions.
if(function_exists('ruleset_npc_help_filter_hook')) ruleset_npc_help_filter_hook($npcinfo,$npcdescription);
$npcinfo = get_npc_helpinfo($npcinfo);

$ty1 = Array(1=>1, 2=>88);
$ty2 = Array(1=>Array(5,'asub'), 2=>Array(6,'asub'), 3=>Array(9,'asub'));
$ty2a = Array(1=>Array(19,'asub')); #真红蓝
$ty7_ibis = Array(1=>Array(7,'asub_ibis')); #朱鹮执行官
$ty7_classic = Array(1=>Array(7,'asub_classic')); #旧版三执行官
$ty34 = Array(1=>Array(34,'asub')); #新红杀八大教头
$ty36 = Array(1=>36); #代码洪流·动物大排档成员
$ty3 = Array(1=>Array(11,'asub'));
$ty4 = Array(1=>90, 2=>92);
$ty5 = Array(1=>Array(2,'asub'));
$ty6 = Array(1=>14, 2=>4);
$ty6e = Array(1=>Array(14,'esub')); #女主第二形态情报
$ty7 = Array(1=>Array(13,'asub'));
$ty8 = Array(1=>Array(15,'asub'));
$ty9 = Array(1=>22);
$ty10 = Array(1=>21);
$ty11 = Array(1=>Array(89,'asub'));
$ty11e = Array(1=>Array(89,'esub')); #电掣NPC第二形态情报
$ty12 = Array(1=>24);
$ty13 = Array(1=>Array(93,'asub')); #可召唤/自然入场高级小兵
$ty25a = Array(1=>Array(25,'asub')); #佣兵NPC

$mix_need_rebuild = !file_exists($writefile);
if(!$mix_need_rebuild)
{
	$write_mtime = filemtime($writefile);
	foreach(array($vnmixfile,$mixfile,$shopfile,$mapitemfile,$synfile,$ovlfile,$presentfile,$boxfile,$npcfile,$addnpcfile,$evonpcfile) as $srcfile)
	{
		if($srcfile && file_exists($srcfile) && filemtime($srcfile) > $write_mtime)
		{
			$mix_need_rebuild = true;
			break;
		}
	}
}

if($mix_need_rebuild){
	$mixitem = array();
	$vmixitem = array();
	if(!empty($mixinfo) && is_array($mixinfo)){
		foreach($mixinfo as $mix){
			$mclass = isset($mix['class']) ? $mix['class'] : 'item';
			if($mclass !== 'hidden')
			{
				$mix['result'][0] = parse_nameinfo_desc($mix['result'][0]);
				$raw_sk = isset($mix['result'][4]) ? $mix['result'][4] : '';
				$mixitmsk = empty($raw_sk) ? '' : parse_skinfo_desc($raw_sk,$mix['result'][1]);
				$mixitmk = parse_kinfo_desc($mix['result'][1],$raw_sk);

				$mixitem[$mclass][] = array('stuff' => $mix['stuff'], 'result' => array($mix['result'][0],$mixitmk,$mix['result'][2],$mix['result'][3],$mixitmsk));
			}
		}
	}
	
	$mixclass = array(
		'wp'=> array('殴系武器','yellow'),
		'wk'=> array('斩系武器','yellow'),
		'wg'=> array('射系武器','yellow'),
		'wc'=> array('投系武器','yellow'),
		'wd'=> array('爆系武器','yellow'),
		'wf'=> array('灵系武器','yellow'),
		'wmu' => array('多重武器','yellow'),
		'w' => array('其他装备','yellow'),
		'h' => array('补给品','lime'),
		'pokemon'=> array('小黄系道具','yellow'),
		'fseed'=> array('种火系道具','lime'),
		'ocg'=> array('游戏王系道具','clan'),
		'key'=> array('KEY系道具','lime'),
		'cube'=> array('方块系道具','yellow'),
		'item'=> array('其他道具','yellow'),
		'titles'=> array('头衔奖励相关道具','sienna'),
		);
	$mixhelpinfo = '';
	foreach($mixitem as $class => $list){
		$classname = isset($mixclass[$class][0]) ? $mixclass[$class][0] : $class;
		$classcolor = isset($mixclass[$class][1]) ? $mixclass[$class][1] : 'yellow';
		$mixhelpinfo .= "<p><span class=\"$classcolor\">{$classname}合成表</span>：</p>\n";
		$mixhelpinfo .= 
		"<table>
			<tr>
				<td class=\"b1\" height=20px><span>合成材料一</span></td>
				<td class=\"b1\"><span>合成材料二</span></td>
				<td class=\"b1\"><span>合成材料三</span></td>
				<td class=\"b1\"><span>合成材料四</span></td>
				<td class=\"b1\"><span>合成材料五</span></td>
				<td class=\"b1\"></td>
				<td class=\"b1\"><span>合成结果</span></td>
				<td class=\"b1\"><span>物品属性</span></td>
			</tr>
			";
		foreach($list as $val){
			if(!empty($val['result'][4])){$itmskword = '/'.$val['result'][4];}
			else{$itmskword = '';}
			for($si=0;$si<=4;$si++){
				if(!isset($val['stuff'][$si])){$val['stuff'][$si] = '-';}
			}
			$mixhelpinfo .= "<tr>";
			for ($i=0; $i<=4; $i++)
			{
				$mixhelpinfo .= "<td class=\"b3\" ";
				if ($i==0)  $mixhelpinfo .= "height=20px";
				if ($val['stuff'][$i]!='-')
				{
					$tooltipinfo = get_item_place($val['stuff'][$i]);
					if(!empty($tooltipinfo)) $mixhelpinfo .= "><span tooltip=\"".$tooltipinfo."\" ";
				}
				$mixhelpinfo .= ">{$val['stuff'][$i]}</span></td>";
			}
			$mixhelpinfo .= "<td class=\"b3\">→</td>
			<td class=\"b3\"><span>{$val['result'][0]}</span></td>
			<td class=\"b3\"><span>{$val['result'][1]}/{$val['result'][2]}/{$val['result'][3]}{$itmskword}</span></td>
				</tr>
				";
		}
		$mixhelpinfo .= "</table>\n";
	}

	# 玩家原创合成部分
	if(!empty($vn_mixinfo) && is_array($vn_mixinfo))
	{
		foreach($vn_mixinfo as $mix)
		{
			$mclass = isset($mix['class']) ? $mix['class'] : 'item';
			if($mclass !== 'hidden')
			{
				$mix['result'][0] = parse_nameinfo_desc($mix['result'][0]);
				$raw_sk = isset($mix['result'][4]) ? $mix['result'][4] : '';
				$mixitmsk = empty($raw_sk) ? '' : parse_skinfo_desc($raw_sk,$mix['result'][1]);
				$mixitmk = parse_kinfo_desc($mix['result'][1],$raw_sk);

				$vmixitem[$mclass][] = array('name' => $mix['name'], 'stuff' => $mix['stuff'], 'result' => array($mix['result'][0],$mixitmk,$mix['result'][2],$mix['result'][3],$mixitmsk));
			}
		}
	}

	if(!empty($vmixitem))
	{
		$mixhelpinfo .= "\r <br><span class='evergreen'>此外，游戏中还包含由玩家提交的原创合成：</span><br> \r";
		foreach($vmixitem as $class => $list){
			$classname = isset($mixclass[$class][0]) ? $mixclass[$class][0] : $class;
			$classcolor = isset($mixclass[$class][1]) ? $mixclass[$class][1] : 'yellow';
			$mixhelpinfo .= "<p><span class=\"$classcolor\">{$classname}合成表 - 玩家原创</span>：</p>\n";
			$mixhelpinfo .=
			"<table>
				<tr>
					<td class=\"b1\" width=100px><span>配方作者</span></td>
					<td class=\"b1\" height=20px><span>合成材料一</span></td>
					<td class=\"b1\"><span>合成材料二</span></td>
					<td class=\"b1\"><span>合成材料三</span></td>
					<td class=\"b1\"><span>合成材料四</span></td>
					<td class=\"b1\"><span>合成材料五</span></td>
					<td class=\"b1\"></td>
					<td class=\"b1\"><span>合成结果</span></td>
					<td class=\"b1\"><span>物品属性</span></td>
				</tr>
				";
			foreach($list as $val){
				if(!empty($val['result'][4])){$itmskword = '/'.$val['result'][4];}
				else{$itmskword = '';}
				for($si=0;$si<=4;$si++){
					if(!isset($val['stuff'][$si])){$val['stuff'][$si] = '-';}
				}
				$mixhelpinfo .= "<tr><td class=\"b3\">{$val['name']}</td>";
				for ($i=0; $i<=4; $i++)
				{
					$mixhelpinfo .= "<td class=\"b3\" ";
					if ($i==0)  $mixhelpinfo .= "height=20px";
					if ($val['stuff'][$i]!='-')
					{
						$tooltipinfo = get_item_place($val['stuff'][$i]);
						if(!empty($tooltipinfo)) $mixhelpinfo .= "><span tooltip=\"".$tooltipinfo."\" ";
					}
					$mixhelpinfo .= ">{$val['stuff'][$i]}</span></td>";
				}
				$mixhelpinfo .= "<td class=\"b3\">→</td>
				<td class=\"b3\"><span>{$val['result'][0]}</span></td>
				<td class=\"b3\"><span>{$val['result'][1]}/{$val['result'][2]}/{$val['result'][3]}{$itmskword}</span></td>
					</tr>
					";
			}
			$mixhelpinfo .= "</table>\n";
		}
	}
	
	writeover($writefile,$mixhelpinfo);
} else {
	$mixhelpinfo = file_get_contents($writefile);
}

$extrahead = <<<EOT
<STYLE type=text/css>
BODY:not(.nouveau-ui) {
	FONT-SIZE: 10pt;MARGIN: 0; color:#eee; FONT-FAMILY: "Trebuchet MS","Gill Sans","Microsoft Sans Serif",sans-serif;
}
BODY:not(.nouveau-ui) A {
	COLOR: #eee
}
BODY:not(.nouveau-ui) A:visited {
	COLOR: #eee
}
BODY:not(.nouveau-ui) A:active {
	color: #98fb98;text-decoration:underline
}
DIV.help-body P {
	line-height: 18px;
}

DIV.help {
	PADDING-LEFT: 1em;PADDING-right: 1em
}

.subtitle2 {
	font-family: "微软雅黑"; color: #98fb98; width: 100%;font-size: 16px;font-weight:900;
}

DIV.FAQ {
	PADDING-LEFT: 1em; line-height:18px
}
DIV.FAQ DT {
	COLOR: #98fb98; margin-top: 6px;
}
DIV.FAQ DD {
	margin-bottom: 6px;
}

</STYLE>
EOT;

include template('help');

?>
