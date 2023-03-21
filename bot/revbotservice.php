<?php
define('CURSCRIPT', 'revbotservice');
include './include/common.inc.php';
include GAME_ROOT.'./include/game.func.php';
include GAME_ROOT.'./bot/revbot.func.php';

if($gamestate > 10)
{
	if(!empty($gamevars['botplayer']))
	{
		# bot初始化
		$ids = bot_player_valid($gamevars['botplayer']);
		unset($gamevars['botplayer']);
		$gamevars['botid'] = $ids;
		save_gameinfo();
		echo "所有BOT初始化完成，共计：".(count($ids))."个";
	}
	elseif(!empty($gamevars['botid']))
	{
		foreach($gamevars['botid'] as $botid)
		{
			$flag = bot_acts($botid);
			if($flag == 0)
			{
				unset($gamevars['botid'][array_search($botid,$gamevars['botid'])]);
				save_gameinfo();
				if(empty($gamevars['botid'])) break;
			}
			sleep(1);
		}
		echo "所有BOT行动完成";
	}
}

?>
