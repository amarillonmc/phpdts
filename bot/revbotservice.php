<?php
define('CURSCRIPT', 'revbotservice');
include './include/common.inc.php';
include GAME_ROOT . './include/game.func.php';
include GAME_ROOT . './bot/revbot.func.php';

# 注意：因为进程锁的存在，运行bot脚本时必须确保游戏处于未开始状态
# 否则请先中止游戏，并手动清空lock目录下所有文件，然后确保游戏正处于未开始状态下运行脚本

# 进程初始化
bot_prepare_flag:
$id = 0;
$dir = GAME_ROOT.'./bot/lock/';
$scdir = scandir($dir);
# 为进程创建对应编号的进程锁
$process_id = $scdir ? count($scdir)-1 : 1;
touch($dir.$process_id.'.lock');

while(true)
{
	load_gameinfo();
	echo "进程id【{$process_id}】正在运行，当前游戏状态:{$gamestate}\n";
	ob_end_flush();
	sleep(1);
	# bot初始化阶段
	if ($gamestate > 10 && !empty($gamevars['botplayer']))
	{
		$scdir = scandir($dir);
		# 在这个阶段 进程锁数量应该是与进程id一一对应的，建议先只运行一个脚本校对进程锁数量
		# 如果发现进程锁数量与进程id不能对应，则可能是系统原因，文件夹lock内存在其他隐藏文件，记得根据差值自己调整$scnums后面的 + -
		$scnums = count($scdir)-2;
		echo "当前进程锁数量:".$scnums\n;
		ob_end_flush();
		# 进程锁数量等于当前编号ID时，才会进行初始化
		if($process_id == $scnums)
		{
			$ids = bot_player_valid(1);
			$id = $ids[0];
			//unset($gamevars['botplayer']);
			$gamevars['botid'][] = $id;
			$gamevars['botplayer'] --;
			save_gameinfo();
			# 解锁
			sleep(1);
			unlink($dir.$process_id.'.lock');
			echo "BOT初始化完成，id：" . ($id) . "\n剩余待初始化bot数量：{$gamevars['botplayer']}";
			ob_end_flush();
			goto bot_act_flag;
		}
		else
		{
			echo "有其他进程正在进行初始化，等待中...\n";
			ob_end_flush();
			sleep(1);
		}
	}
}

# bot开始行动
bot_act_flag:
while($id)
{
	load_gameinfo();
	if ($gamestate > 10) 
	{
		if (!empty($gamevars['botid']))
		{
			$flag = bot_acts($id);
			if ($flag == 0) {
				unset($gamevars['botid'][array_search($botid, $gamevars['botid'])]);
				save_gameinfo();
				if (empty($gamevars['botid'])) break;
			}
			echo "\nBOT：{$id} 行动完成\n";
			ob_end_flush();
		}
		sleep(1);
	}
	else 
	{
		goto bot_prepare_flag;
	}
}