<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once dirname(__DIR__, 2).'/include/game/achievement_routes.func.php';

class EndingAchievementTestDb {
	public $rows = array('player'=>''), $affected = 0, $conflict = false;
	function query($sql) {
		if(strpos($sql, 'SELECT') === 0) {
			preg_match("/username=X'([0-9a-f]*)'/", $sql, $m);
			$name = hex2bin($m[1]);
			return isset($this->rows[$name]) ? array('achrev'=>$this->rows[$name]) : false;
		}
		preg_match("/SET achrev=X'([0-9a-f]*)'.*username=X'([0-9a-f]*)'.*=X'([0-9a-f]*)'/", $sql, $m);
		$name = hex2bin($m[2]); $before = hex2bin($m[3]);
		if($this->conflict) {
			$this->rows[$name] = json_encode(array(620=>array('v'=>1,'l'=>1)));
			$this->conflict = false;
		}
		$this->affected = $this->rows[$name] === $before ? 1 : 0;
		if($this->affected) $this->rows[$name] = hex2bin($m[1]);
		return true;
	}
	function fetch_array($row) { return $row; }
	function affected_rows() { return $this->affected; }
}
function ending_ach_assert($ok, $message) {
	if(!$ok) throw new RuntimeException($message);
}
$db = new EndingAchievementTestDb(); $gtablepre = 'test_';
$ending_routes_v2 = $club_skills = 1;
$db->conflict = true;
ending_ach_assert(ending_routes_award_achievement(630, 'player'), '首次路线成就应完成');
$saved = json_decode($db->rows['player'], true);
ending_ach_assert(isset($saved[620]) && $saved[630]['l'] == 1, '并发的其他成就不得被覆盖');
ending_ach_assert(!ending_routes_award_achievement(630, 'player'), '重复结算不可重复完成');
ending_ach_assert(!ending_routes_award_achievement(649, 'player', 1), '善德单独击破尚未完成两人挑战');
ending_ach_assert(!ending_routes_award_achievement(649, 'player', 1), '重复善德不可抵作恶德');
ending_ach_assert(ending_routes_award_achievement(649, 'player', 2), '恶德击破应合并保存进度');
$attacker = array('type'=>0,'name'=>'player#AI1','clbpara'=>array('ai_master'=>'player'));
ending_routes_check_kill_achievement($attacker, array('type'=>34,'clbpara'=>json_encode(array('er_sub'=>7))));
ending_ach_assert(!empty(json_decode($db->rows['player'], true)[641]['l']), '分体首杀应计主账号');
ending_routes_check_kill_achievement($attacker, array('type'=>36,'clbpara'=>array('er_sub'=>0)));
ending_ach_assert(empty(json_decode($db->rows['player'], true)[642]), '大排档角色不可当作湾城BOSS');
$gamevars = array('ending_subroute'=>'jieli_true');
ending_ach_assert(!ending_routes_check_end_achievement('player', 3, null), '模式与子路线必须一致');
ending_ach_assert(ending_routes_check_end_achievement('player', 7, null), '真实解离应完成新路线成就');
$ending_routes_v2 = 0;
ending_routes_check_kill_achievement($attacker, array('type'=>34,'clbpara'=>array('er_sub'=>0)));
ending_ach_assert(empty(json_decode($db->rows['player'], true)[634]), '关闭新路线不触发新成就');
echo "Ending route achievements: OK\n";
