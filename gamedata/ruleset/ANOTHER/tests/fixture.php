<?php
if (!defined('IN_GAME')) exit('Access Denied');

// 独立回归的内存数据库；不连接、不重置部署数据库。
class AnotherTestResult { public $rows; public $offset=0; function __construct($rows){$this->rows=array_values($rows);} }
class AnotherTestDB {
    public $tables=array('bra_players'=>array(),'bra_mapitem'=>array(),'bra_maptrap'=>array(),'bra_winners'=>array(),'bra_users'=>array());
    public $last_id=0;
    function array_insert($table,$row) {
        $key=$this->key($table); if(empty($row[$key])) $row[$key]=$this->tables[$table]?max(array_keys($this->tables[$table]))+1:1;
        if(isset($this->tables[$table][$row[$key]])) throw new RuntimeException('Duplicate '.$table.' '.$row[$key]);
        $this->last_id=$row[$key]; $this->tables[$table][$row[$key]]=$row;
    }
    function key($table) { return array('bra_players'=>'pid','bra_mapitem'=>'iid','bra_maptrap'=>'tid','bra_winners'=>'gid','bra_users'=>'uid')[$table]; }
    function insert_id(){return $this->last_id;}
    function array_update($table,$row,$where) {
        if(!preg_match('/^(\w+)=(\d+)$/',$where,$m)) throw new RuntimeException('Unsupported where '.$where);
        $id=(int)$m[2]; if(isset($this->tables[$table][$id])) $this->tables[$table][$id]=array_merge($this->tables[$table][$id],$row);
    }
    function query($sql) {
        if(preg_match('/^SELECT \* FROM (bra_players|bra_mapitem|bra_maptrap)$/',$sql,$m)) return new AnotherTestResult($this->tables[$m[1]]);
        if(strpos($sql,'SELECT gid,clbpara FROM bra_winners')===0) return new AnotherTestResult($this->tables['bra_winners']);
        if(preg_match('/^DELETE FROM (bra_mapitem|bra_maptrap) WHERE \w+=(\d+)$/',$sql,$m)) { unset($this->tables[$m[1]][(int)$m[2]]); return true; }
        if(preg_match("/^UPDATE bra_users SET wingames=wingames\+1 WHERE username=X'([0-9a-f]*)'$/",$sql,$m)) {
            foreach($this->tables['bra_users'] as &$user) if($user['username']===hex2bin($m[1])) $user['wingames']++;
            unset($user); return true;
        }
        throw new RuntimeException('Unsupported query '.$sql);
    }
    function fetch_array($result){return isset($result->rows[$result->offset])?$result->rows[$result->offset++]:false;}
    function num_rows($result){return count($result->rows);}
}
function create_dummy_playerdata() {
    $p=array(); $sql=file_get_contents(GAME_ROOT.'gamedata/sql/players.sql');
    foreach(explode("\n",$sql) as $line) if(preg_match('/^\s+`?(\w+)`?\s+(\w+)/',$line,$m)) {
        if(in_array(strtolower($m[2]),array('smallint','tinyint','mediumint','int','bigint'))) $p[$m[1]]=0;
        elseif(in_array(strtolower($m[2]),array('char','varchar','text','mediumtext'))) $p[$m[1]]='';
    }
    return $p;
}
function player_format_with_db_structure($p) {
    $keys=create_dummy_playerdata(); $out=array();
    foreach($keys as $key=>$default) $out[$key]=isset($p[$key])?(is_array($p[$key])?json_encode($p[$key],JSON_UNESCAPED_UNICODE):$p[$key]):$default;
    return $out;
}
function player_save($p) { global $db,$tablepre; $db->array_update($tablepre.'players',player_format_with_db_structure($p),'pid='.(int)$p['pid']); }
function save_gameinfo() { global $gamevars,$test_gameinfo; $test_gameinfo=json_encode($gamevars); }
function load_gameinfo() { global $gamevars,$test_gameinfo; $gamevars=json_decode($test_gameinfo,true); }
function gameover($at,$mode,$winner) {
    global $db,$gamenum,$gamestate,$test_ends;
    $test_ends[]=array($at,$mode,$winner); $gamestate=0;
    $db->array_insert('bra_winners',array('gid'=>$gamenum,'clbpara'=>'','name'=>$winner,'mode'=>$mode));
}
function an_fixture_reset() {
    global $db,$tablepre,$gtablepre,$gamevars,$test_gameinfo,$gamenum,$gamestate,$starttime,$now,$test_ends;
    $db=new AnotherTestDB(); $tablepre=$gtablepre='bra_'; $gamevars=array(); $test_gameinfo='{}';
    $gamenum=1; $gamestate=20; $starttime=$now=10000; $test_ends=array();
}
function an_fixture_player($id,$owner,$talents=array()) {
    $p=another_empty_player(); $p['pid']=$id; $p['name']=$owner; $p['teamID']='AN'.$id;
    $p['clbpara']['another']=array('owner'=>$owner,'owner_uid'=>$id,'talents'=>$talents,'rep'=>0,'messages'=>array(),'known_players'=>array(),'request_seq'=>0);
    $p['hp']=another_max_hp($p); $p['sp']=another_max_sp($p); return $p;
}
