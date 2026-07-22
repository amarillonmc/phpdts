<?php

define('CURSCRIPT', 'revbotservice');

$gameRoot = dirname(__DIR__).DIRECTORY_SEPARATOR;
if(is_dir($gameRoot)) {
	chdir($gameRoot);
}

require_once $gameRoot.'include/common.inc.php';
require_once GAME_ROOT.'./include/game.func.php';
require_once GAME_ROOT.'./bot/revbot.func.php';

// 所有远程worker先竞争BOT行动锁，避免非RuleSet模式并发覆盖gameinfo与BOT队列。
// All remote workers first contend on the bot action lock so non-RuleSet modes cannot overwrite shared state.
function revbot_acquire_action_lock()
{
	global $revbot_action_lock_handle;
	if (is_resource($revbot_action_lock_handle)) return true;

	$handle = @fopen(GAME_ROOT.'./gamedata/revbot_action.lock', 'ab');
	if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
		if ($handle) fclose($handle);
		return false;
	}
	$revbot_action_lock_handle = $handle;
	return true;
}

function revbot_release_action_lock()
{
	global $revbot_action_lock_handle;
	if (!is_resource($revbot_action_lock_handle)) return;
	flock($revbot_action_lock_handle, LOCK_UN);
	fclose($revbot_action_lock_handle);
	$revbot_action_lock_handle = null;
}

// 每次BOT原子行动同时复用当前RuleSet的请求级锁。
// Each atomic bot action also reuses the active RuleSet request lock.
function revbot_ruleset_action_begin()
{
	if (!revbot_acquire_action_lock()) return false;
	if (function_exists('ruleset_command_request_begin_hook')
		&& ruleset_command_request_begin_hook() === false) {
		revbot_release_action_lock();
		return false;
	}
	return true;
}

function revbot_ruleset_action_end()
{
	if(function_exists('ruleset_command_request_end_hook')) ruleset_command_request_end_hook();
	revbot_release_action_lock();
}

$bot_respawn_chance = isset($_GET['respawn_chance']) ? (int)$_GET['respawn_chance'] : 35;
if($bot_respawn_chance < 0) $bot_respawn_chance = 0;
if($bot_respawn_chance > 100) $bot_respawn_chance = 100;

$oneshot = isset($_GET['oneshot']) ? (int)$_GET['oneshot'] : 0;
$oneshot = $oneshot ? 1 : 0;

# 注意：因为进程锁的存在，运行bot脚本时必须确保游戏处于未开始状态
# 否则请先中止游戏，并手动清空lock目录下所有文件，然后确保游戏正处于未开始状态下运行脚本

# 单次执行模式：执行一次初始化或一次行动后立即退出，避免长连接占用游戏锁
if($oneshot)
{
	if(!revbot_ruleset_action_begin()) {
		echo "ruleset_busy=1\n";
		exit();
	}
	load_gameinfo();
	echo "oneshot=1
";
	echo "当前游戏状态:{$gamestate}
";
	if($gamestate <= 10) {
		echo "游戏未开始，跳过。
";
		revbot_ruleset_action_end();
		exit();
	}

	if (!empty($gamevars['botplayer']))
	{
		$ids = bot_player_valid(1);
		$id = $ids[0];
		$gamevars['botid'][] = $id;
		$gamevars['botplayer'] --;
		save_gameinfo();
		echo "BOT初始化完成，id：" . ($id) . "
剩余待初始化bot数量：{$gamevars['botplayer']}
";
		revbot_ruleset_action_end();
		exit();
	}

	if (!empty($gamevars['botid']))
	{
		$id = $gamevars['botid'][array_rand($gamevars['botid'])];
		$flag = bot_acts($id);
		if ($flag == 0) {
			$index = array_search($id, $gamevars['botid']);
			if($index !== false) unset($gamevars['botid'][$index]);
			$roll = mt_rand(1,100);
			if($bot_respawn_chance > 0 && $roll <= $bot_respawn_chance) {
				$gamevars['botplayer'] = isset($gamevars['botplayer']) ? (int)$gamevars['botplayer'] + 1 : 1;
				echo "BOT：{$id} 已死亡；已加入重生队列。roll={$roll}, chance={$bot_respawn_chance}
";
			} else {
				echo "BOT：{$id} 已死亡；不加入重生队列。roll={$roll}, chance={$bot_respawn_chance}
";
			}
			save_gameinfo();
			save_combatinfo();
			revbot_ruleset_action_end();
			exit();
		}
		echo "BOT：{$id} 行动完成
";
		revbot_ruleset_action_end();
		exit();
	}

	echo "当前无可行动BOT。
";
	revbot_ruleset_action_end();
	exit();
}

# 进程初始化
bot_prepare_flag:
$id = 0;
$dir = GAME_ROOT.'./bot/lock/';
if(!is_dir($dir)) {
	mkdir($dir, 0777, true);
}
$scdir = scandir($dir);
# 为进程创建对应编号的进程锁
$process_id = $scdir ? count($scdir)+1 : 1;
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
		$scnums = count($scdir);
		echo "当前进程锁数量:".$scnums."\n";
		ob_end_flush();
		# 进程锁数量等于当前编号ID时，才会进行初始化
		if($process_id == $scnums)
		{
			if(!revbot_ruleset_action_begin()) {
				echo "RAID状态正忙，BOT初始化将重试。\n";
				sleep(1);
				continue;
			}
			// 等锁期间状态可能改变，锁内重读并再次确认。
			// State may change while waiting; reload and recheck it inside the lock.
			load_gameinfo();
			if($gamestate <= 10 || empty($gamevars['botplayer'])) {
				revbot_ruleset_action_end();
				continue;
			}
			$ids = bot_player_valid(1);
			$id = $ids[0];
			//unset($gamevars['botplayer']);
			$gamevars['botid'][] = $id;
			$gamevars['botplayer'] --;
			save_gameinfo();
			# 在sleep/goto前释放RuleSet锁
			revbot_ruleset_action_end();
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
	if(!revbot_ruleset_action_begin()) {
		echo "RAID状态正忙，BOT行动将重试。\n";
		sleep(1);
		continue;
	}
	load_gameinfo();
	if ($gamestate > 10) 
	{
		if (!empty($gamevars['botid']))
		{
			$flag = bot_acts($id);
			if ($flag == 0) {
				$index = array_search($id, $gamevars['botid']);
				if($index !== false) unset($gamevars['botid'][$index]);
				$roll = mt_rand(1,100);
				if($gamestate > 10 && $bot_respawn_chance > 0 && $roll <= $bot_respawn_chance) {
					$gamevars['botplayer'] = isset($gamevars['botplayer']) ? (int)$gamevars['botplayer'] + 1 : 1;
					echo "BOT：{$id} 已死亡；已加入重生队列。roll={$roll}, chance={$bot_respawn_chance}\n";
				} else {
					echo "BOT：{$id} 已死亡；不加入重生队列。roll={$roll}, chance={$bot_respawn_chance}\n";
				}
				save_gameinfo();
				save_combatinfo();
				revbot_ruleset_action_end();
				ob_end_flush();
				break;
			}
			revbot_ruleset_action_end();
			echo "\nBOT：{$id} 行动完成\n";
			ob_end_flush();
		}
		else
		{
			revbot_ruleset_action_end();
			echo "BOT：{$id} 不在活动队列，进程退出。\n";
			ob_end_flush();
			break;
		}
		sleep(1);
	}
	else 
	{
		revbot_ruleset_action_end();
		goto bot_prepare_flag;
	}
}
