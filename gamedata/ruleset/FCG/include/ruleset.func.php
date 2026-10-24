<?php
if(!defined('IN_GAME')) exit('Access Denied');

function ruleset_round_clock_hook() { return true; }

function ruleset_game_page_hook()
{
	header('Location: fcg.php'); return true;
}

function ruleset_valid_page_hook()
{
	header('Location: fcg.php'); return true;
}

function ruleset_command_page_hook()
{
	ob_clean(); header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('url'=>'fcg.php')); return true;
}
