<?php
if(!defined('IN_GAME')) exit('Access Denied');
$schema_source=dirname(__DIR__,2).DIRECTORY_SEPARATOR;
$schema_root=sys_get_temp_dir().DIRECTORY_SEPARATOR.'phpdts-ending-schema-'.bin2hex(random_bytes(6)).DIRECTORY_SEPARATOR;
mkdir($schema_root.'gamedata',0700,true);
define('GAME_ROOT',$schema_root);
require $schema_source.'include/roommng.func.php';
require $schema_source.'include/global.func.php';

class EndingSchemaResult {
	public $rows,$index=0;
	function __construct($rows) { $this->rows=array_values($rows); }
}
class EndingSchemaDb {
	public $type='char(15)',$reads=0,$alters=0,$locked=false,$racing=false,$saved=array('groomid'=>'0','noisevars'=>'{}');
	function query($sql,$mode='') {
		if(strpos($sql,'DESCRIBE ')===0) return new EndingSchemaResult(array(array('Field'=>'existing')));
		if(strpos($sql,'SHOW INDEX ')===0) return new EndingSchemaResult(array(array('Column_name'=>'groomid')));
		if(strpos($sql,'SHOW COLUMNS ')===0) {
			$this->reads++;
			if($this->reads===2) {
				$probe=fopen(GAME_ROOT.'gamedata/process.lock','ab');
				$this->locked=!flock($probe,LOCK_EX|LOCK_NB);
				if(!$this->locked) flock($probe,LOCK_UN);
				fclose($probe);
				if($this->racing) $this->type='char(40)';
			}
			return new EndingSchemaResult(array(array('Type'=>$this->type)));
		}
		if(strpos($sql,'ALTER TABLE ')===0 && strpos($sql,'MODIFY winner char(40)')!==false) { $this->type='char(40)'; $this->alters++; return true; }
		if(strpos($sql,'SELECT * FROM schema_game')===0) return new EndingSchemaResult(array($this->saved));
		throw new RuntimeException('Unexpected schema query');
	}
	function num_rows($result) { return count($result->rows); }
	function fetch_array($result) { return $result->rows[$result->index++] ?? false; }
	function array_update($table,$data,$where) {
		preg_match('/\((\d+)\)/',$this->type,$width);
		if(strlen($data['winner'])>(int)$width[1]) throw new RuntimeException('Winner would be truncated');
		foreach($data as $key=>$value) $this->saved[$key]=(string)$value;
	}
}
function schema_assert($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
function schema_verify() { ob_start(); roommng_verify_db_game_structure(); return ob_get_clean(); }
$db=new EndingSchemaDb(); $gtablepre=$tablepre='schema_';
try {
	schema_assert(schema_verify()==='' && $db->type==='char(40)' && $db->alters===1 && $db->reads===2 && $db->locked,'窄字段在创建锁内复查后扩到40字符，成功迁移不输出页面文本');
	schema_assert(schema_verify()==='' && $db->alters===1 && $db->reads===3,'已经迁移的字段不重复ALTER');
	$db=new EndingSchemaDb(); $db->type='varchar(80)';
	schema_assert(schema_verify()==='' && $db->type==='varchar(80)' && !$db->alters,'部署已有更宽字段时不缩窄');
	$db=new EndingSchemaDb(); $db->racing=true;
	schema_assert(schema_verify()==='' && !$db->alters && $db->reads===2 && $db->locked,'等待锁时已由其他请求扩宽，锁内二次确认后不重复ALTER');
	$db=new EndingSchemaDb(); $plock=fopen(GAME_ROOT.'gamedata/process.lock','ab'); flock($plock,LOCK_EX);
	schema_assert(schema_verify()==='' && $db->alters===1 && is_resource($plock) && $db->locked,'已经持有公共锁时复用锁且不提前释放调用者的锁');
	flock($plock,LOCK_UN); fclose($plock); unset($plock);
	foreach(array('groomid','gamenum','gamestate','now','lastupdate','starttime','winmode','winner','areanum','areatime','areawarn','validnum','alivenum','deathnum','afktime','optime','weather','hack','combonum','hdamage','hplayer','noisetime','noisepls','noiseid','noiseid2','noisemode') as $key) $GLOBALS[$key]=0;
	$gamenum=1; $gamestate=0; $now=100; $arealist=array(0,1); $gamevars=array(); $winner='LongOwner123#AI1';
	save_gameinfo(); $winner=''; load_gameinfo();
	schema_assert($winner==='LongOwner123#AI1','真实save_gameinfo/load_gameinfo经过字符串型数据库结果仍完整重载长分体胜者名');
} finally {
	if(isset($plock) && is_resource($plock)) { flock($plock,LOCK_UN); fclose($plock); unset($plock); }
	if(is_file($schema_root.'gamedata/process.lock')) unlink($schema_root.'gamedata/process.lock');
	rmdir($schema_root.'gamedata'); rmdir($schema_root);
}
