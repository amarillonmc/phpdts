<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

/*
 * 本模式的搜打撤与封印路线本身就是任务，因此关闭普通QUEST分配。
 * Extraction and sealing are the quests of this mode, so ordinary QUEST assignment is disabled.
 */
$questcfg_global = Array(
	'max_active' => 0,
	'assign_obbs' => 0,
	'offer_threshold' => PHP_INT_MAX,
	'assign_cooldown' => 0,
	'reject_cooldown_steps' => 0,
);
$questcfg = Array();

?>
