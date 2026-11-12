<?php
if(!defined('IN_GAME')) exit('Access Denied');
include GAME_ROOT.'./gamedata/cache/talent_1.php';
foreach($talent_trees as $talent_tree) {
	$talent_title_id = $talent_tree['title_id'];
	$titles_list[$talent_title_id] = $talent_tree['title'];
	$title_desc[$talent_title_id] = array('class'=>'tclan','title'=>'在支持天赋的大房间或标准房，佩戴入场即可使用'.$talent_tree['name'].'树已永久收集的专属技能。');
	// 白值和入场福利由账户当前投入决定，头衔本身不重复发放数值。
	$title_valid[$talent_title_id] = array();
}
unset($talent_trees,$talent_tree,$talent_title_id,$talent_race_rules);
