<?php
// php -d short_open_tag=1 bot/elevator_tick.php ROOM_ID [--watch]
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!isset($argv[1]) || !preg_match('/^\d+$/',$argv[1])) exit("Usage: php -d short_open_tag=1 bot/elevator_tick.php ROOM_ID [--watch]\n");
define('CURSCRIPT','elevator_tick');
define('ELEVATOR_TICK_ROOM',(int)$argv[1]);
chdir(dirname(__DIR__));
require './include/common.inc.php';
require_once GAME_ROOT.'include/game.func.php';
$watch=in_array('--watch',$argv,true);
do {
    $now=time()+$moveut*3600+$moveutmin*60;
    if (!elevator_lock()) exit("Unable to lock room.\n");
    if ($gamestate==10 && $now>=$starttime) {
        $gamestate=20;
        if ($groomid) addnews($starttime,'newroomgame',$gamenum,$groomid);
        else addnews($starttime,'newgame',$gamenum);
        systemputchat($starttime,'newgame'); save_gameinfo();
    }
    elevator_request_tick();
    if (is_resource($elevator_lock_handle)) { flock($elevator_lock_handle,LOCK_UN); fclose($elevator_lock_handle); $elevator_lock_handle=null; }
    if (!$watch || $gamestate<10 || ($gamevars['elevator']['state']??'ended')==='ended') break;
    usleep(1000000);
} while (true);
echo 'ELEVATOR room '.ELEVATOR_TICK_ROOM.' ticked at '.date('Y-m-d H:i:s',$now)."\n";
