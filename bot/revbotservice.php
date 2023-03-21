<?php
define('CURSCRIPT', 'revbotservice');
include './include/common.inc.php';
include GAME_ROOT . './include/game.func.php';
include GAME_ROOT . './bot/revbot.func.php';
$id = 0;
if ($gamestate > 10) {
	$ids = bot_player_valid(1);
	$id = $ids[0];
	unset($gamevars['botplayer']);
	$gamevars['botid'] = $ids;
	save_gameinfo();
	echo "BOT初始化完成，id：" . ($id) . "\n";
	ob_end_flush();
}
while (true) {
	if ($gamestate > 10) {
		var_dump($gamevars['botplayer']);
		if (!empty($gamevars['botplayer'])) {
			# bot初始化
			$ids = bot_player_valid(1);
			$id = $ids[0];
			unset($gamevars['botplayer']);
			$gamevars['botid'] = $ids;
			save_gameinfo();
			echo "BOT初始化完成，id：" . ($id) . "\n";
			ob_end_flush();
		} elseif (!empty($gamevars['botid'])) { {
				$flag = bot_acts($id);
				if ($flag == 0) {
					unset($gamevars['botid'][array_search($botid, $gamevars['botid'])]);
					save_gameinfo();
					if (empty($gamevars['botid'])) break;
				}
				echo "所有BOT行动完成\n";
				ob_end_flush();
			}
		}
		sleep(2);
	}
}