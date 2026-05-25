<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
# TODO: NO GLOBAL

function get_ruleset_plain_resource_file($filename)
{
	if(function_exists('get_current_ruleset_id'))
	{
		$ruleset_id = get_current_ruleset_id();
		if(!empty($ruleset_id))
		{
			$ruleset_file = GAME_ROOT.'./gamedata/ruleset/'.$ruleset_id.'/'.$filename;
			if(file_exists($ruleset_file)) return $ruleset_file;
		}
	}
	return GAME_ROOT.'./gamedata/'.$filename;
}

# resources_1.php
function get_equip_list($mode=0)
{
	//装备列表
	$equip_list = Array('wep','wep2','arb','arh','ara','arf','art');
	$equip_list2 = Array
	(
		'W' => 'wep',
		'DB' => 'arb',
		'DH' => 'arh',
		'DA' => 'ara',
		'DF' => 'arf',
		'A' => 'art',
	);
	if($mode) return $equip_list2;
	return $equip_list;
}

# mixitem_1.php; vnmixitem_1.php;
function get_mixinfo()
{
	global $gamecfg,$gamevars;
	include config("mixitem",$gamecfg);

	# 向合成列表中加入玩家自定义合成
	if(file_exists(config("vnmixitem",$gamecfg)))
	{
		include config("vnmixitem",$gamecfg);
	}
	if(!empty($vn_mixinfo)) $mixinfo = array_merge($mixinfo,$vn_mixinfo);

	# 向合成列表中加入每局随机合成
	if(!empty($gamevars['random_mixlist']))  $mixinfo = array_merge($mixinfo,$gamevars['random_mixlist']);

	return $mixinfo;
}
function get_syncmixinfo()
{
	global $gamecfg;
	$file = config("synitem",$gamecfg);
	$slist = openfile($file);
	$n = count($slist);
	$prp_res = array();
	for ($i=0;$i<$n;$i++){
		$prp_res[] = explode(',',$slist[$i]);
	}
	return $prp_res;
}
function get_overlaymixinfo()
{
	global $gamecfg;
	$file = config("overlay",$gamecfg);
	$olist = openfile($file);
	$n = count($olist);
	$prp_res = array();
	for ($i=0;$i<$n;$i++){
		$prp_res[] = explode(',',$olist[$i]);
	}
	return $prp_res;
}

# addnpc_1.php
function get_addnpcinfo()
{
	global $gamecfg;
	include config("addnpc",$gamecfg);

	// 追加QUEST NPC定义 / Append QUEST NPC definitions
	$quest_npc_file = get_ruleset_plain_resource_file('addnpc_quest_1.php');
	if (file_exists($quest_npc_file)) {
		include $quest_npc_file;
		if (!empty($quest_anpcinfo) && is_array($quest_anpcinfo)) {
			foreach ($quest_anpcinfo as $qtype => $qinfo) {
				if (!isset($anpcinfo[$qtype])) {
					$anpcinfo[$qtype] = $qinfo;
					continue;
				}
				// 仅合并sub，避免覆盖原有基础设定 / Merge subs only, keep base settings
				if (isset($qinfo['sub']) && is_array($qinfo['sub'])) {
					if (empty($anpcinfo[$qtype]['sub'])) {
						$anpcinfo[$qtype]['sub'] = array();
					}
					$anpcinfo[$qtype]['sub'] = array_merge($anpcinfo[$qtype]['sub'], $qinfo['sub']);
				}
			}
		}
	}
	return $anpcinfo;
}

function get_npcinit()
{
	global $gamecfg;
	include config("npc",$gamecfg);
	return $npcinit;
}

# achievement_1.php
function get_achtype($mode=0)
{
	include config("achievement",1);
	if($mode) return $diary_type;
	return $ach_type;
}
function get_hidden_achtype()
{
	include config("achievement",1);
	return $hidden_ach_type;
}
function get_achlist($a=NULL)
{
	include config("achievement",1);
	if(isset($a) && isset($ach_list[$a])) return $ach_list[$a];
	return $ach_list;
}

# setitems_1.php
function get_set_items()
{
	include config('setitems',1);
	return $set_items;
}
function get_set_items_info()
{
	include config('setitems',1);
	return $set_items_info;
}

// QUEST config / 任务配置
function get_questcfg()
{
	$questcfg = array();
	$questcfg_global = array();
	$quest_file = get_ruleset_plain_resource_file('questcfg_1.php');
	if (file_exists($quest_file)) {
		include $quest_file;
	}
	return array($questcfg, $questcfg_global);
}

// QUEST item templates / 任务物品模板
function get_questiteminfo()
{
	$questiteminfo = array();
	$quest_item_file = get_ruleset_plain_resource_file('questitem_1.php');
	if (file_exists($quest_item_file)) {
		include $quest_item_file;
	}
	return $questiteminfo;
}


?>
