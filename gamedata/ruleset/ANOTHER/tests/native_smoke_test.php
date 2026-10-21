<?php
if (!defined('IN_GAME')) exit('Access Denied');
error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$source = dirname(__DIR__,4).DIRECTORY_SEPARATOR;
$temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'another-native-test-'.bin2hex(random_bytes(6)).DIRECTORY_SEPARATOR;
mkdir($temp); define('GAME_ROOT',$temp);
$copied=array();
function an_native_file($relative,$contents) {
    global $temp,$copied;
    $path=$temp.$relative; if(!is_dir(dirname($path))) mkdir(dirname($path),0777,true);
    file_put_contents($path,$contents); $copied[]=$path;
}
foreach(array('include/game.func.php','include/game/club23.func.php','include/game/itemmain.func.php',
    'include/game/achievement.func.php','include/game/clubslct.func.php','include/game/depot.func.php','include/news.func.php',
    'gamedata/cache/resources_1.php','gamedata/cache/gamecfg_1.php','gamedata/cache/combatcfg_1.php',
    'gamedata/ruleset/ruleset_config.php','gamedata/ruleset/ANOTHER/include/ruleset.func.php',
    'gamedata/ruleset/ANOTHER/cache/resources_1.php','gamedata/ruleset/ANOTHER/cache/gamecfg_1.php','gamedata/ruleset/ANOTHER/cache/npc_1.php') as $file) {
    an_native_file($file,'<?php require_once '.var_export($source.$file,true).';');
}
an_native_file('gamedata/ruleset/ANOTHER/cache/npc_1.php','<?php require '.var_export($source.'gamedata/ruleset/ANOTHER/cache/npc_1.php',true).';');
an_native_file('gamedata/cache/achievement_1.php','<?php require '.var_export($source.'gamedata/cache/achievement_1.php',true).';');
foreach(array('gamedata/sql/reset.sql','gamedata/sql/players.sql','gamedata/sql/shopitem.sql',
    'gamedata/ruleset/ANOTHER/cache/mapitem_1.php','gamedata/ruleset/ANOTHER/cache/shopitem_1.php') as $file) an_native_file($file,file_get_contents($source.$file));
$types=array();
foreach(explode("\n",file_get_contents($source.'gamedata/sql/players.sql')) as $line) {
    if(preg_match('/^\s+`?(\w+)`?\s+((?:tinyint|smallint|mediumint|int|bigint|char|varchar|text|mediumtext)[^\s,]*)/',$line,$m)) $types[$m[1]]=$m[2];
}
an_native_file('gamedata/bak/db_player_structure.config.php','<?php $db_player_structure='.var_export(array_keys($types),true).'; $db_player_structure_types='.var_export($types,true).';');
touch($temp.'gamedata/bak/db_player_structure.config.php',time()+10);
class AnNativeRows { public $rows; public function __construct($rows) {$this->rows=array_values($rows);} }
class AnNativeDB {
    public $tables=array('bra_players'=>array(),'bra_mapitem'=>array(),'bra_maptrap'=>array(),'bra_winners'=>array(),'bra_users'=>array(),'bra_game'=>array(),'bra_newsinfo'=>array(),'bra_chat'=>array());
    public $ruleset=''; public $last_id=0;
    public function key($table) { return array('bra_players'=>'pid','bra_mapitem'=>'iid','bra_maptrap'=>'tid','bra_winners'=>'gid','bra_users'=>'uid','bra_game'=>'groomid','bra_newsinfo'=>'nid','bra_chat'=>'cid')[$table]; }
    public function array_insert($table,$row) {
        $key=$this->key($table); if(empty($row[$key])) $row[$key]=$this->tables[$table]?max(array_keys($this->tables[$table]))+1:1;
        if(isset($this->tables[$table][$row[$key]])) throw new RuntimeException('Duplicate '.$table.' '.$row[$key]);
        $this->last_id=(int)$row[$key]; $this->tables[$table][$this->last_id]=$row;
    }
    public function insert_id() {return $this->last_id;}
    public function array_update($table,$row,$where) {
        if(!preg_match('/^\w+\s*=\s*[\x27]?(\d+)/',$where,$m)) throw new RuntimeException('Unsupported update: '.$where);
        $id=(int)$m[1]; $this->tables[$table][$id]=array_merge($this->tables[$table][$id]??array(),$row);
    }
    public function fetch_array($result) {return array_shift($result->rows);}
    public function num_rows($result) {return count($result->rows);}
    public function result($result,$row=0,$column=0) {return isset($result->rows[$row]) ? array_values($result->rows[$row])[$column] : null;}
    public function queries($sql) {
        if(preg_match_all('/DROP TABLE IF EXISTS (bra_\w+)/',$sql,$m)) foreach($m[1] as $table) $this->tables[$table]=array();
        return true;
    }
    public function query($sql) {
        if(strpos($sql,'SELECT * FROM bra_users RIGHT JOIN bra_players')===0) {
            $rows=array(); foreach($this->tables['bra_players'] as $p) if(!$p['type']) foreach($this->tables['bra_users'] as $u) if($u['username']===$p['name']) $rows[]=array_merge($u,$p);
            return new AnNativeRows($rows);
        }
        if(strpos($sql,'SELECT gruleset FROM bra_game')===0) return new AnNativeRows(array(array('gruleset'=>$this->ruleset)));
        if(preg_match('/^SELECT \* FROM (bra_players|bra_mapitem|bra_maptrap)$/',$sql,$m)) return new AnNativeRows($this->tables[$m[1]]);
        if(strpos($sql,'SELECT * FROM bra_game')===0) return new AnNativeRows($this->tables['bra_game']);
        if(preg_match("/^SELECT (daily|achrev|motto) FROM bra_users WHERE username\\s*=\\s*'([^']+)'$/",$sql,$m)) {
            foreach($this->tables['bra_users'] as $user) if($user['username']===$m[2]) return new AnNativeRows(array(array($m[1]=>$user[$m[1]])));
            return new AnNativeRows(array());
        }
        if(preg_match("/^UPDATE bra_users SET achrev='(.*)' WHERE username='([^']+)'$/",$sql,$m)) {
            foreach($this->tables['bra_users'] as &$user) if($user['username']===$m[2]) $user['achrev']=$m[1]; unset($user); return true;
        }
        if(preg_match("/^UPDATE bra_users SET (credits2?)=\\1\\+([0-9]+) WHERE username='([^']+)'$/",$sql,$m)) {
            foreach($this->tables['bra_users'] as &$user) if($user['username']===$m[3]) $user[$m[1]]+=(int)$m[2]; unset($user); return true;
        }
        if(preg_match("/^SELECT \\* FROM bra_players WHERE name='([^']+)' AND type=0$/",$sql,$m)) return new AnNativeRows(array_filter($this->tables['bra_players'],function($p)use($m){return $p['name']===$m[1] && !$p['type'];}));
        if(strpos($sql,'SELECT pid FROM bra_players WHERE')===0) {
            $rows=$this->tables['bra_players'];
            if(strpos($sql,'type=0')!==false) $rows=array_filter($rows,function($p){return !$p['type'];});
            if(strpos($sql,'hp>0')!==false) $rows=array_filter($rows,function($p){return $p['hp']>0;});
            if(strpos($sql,'hp<=0')!==false) $rows=array_filter($rows,function($p){return $p['hp']<=0 || $p['state']>=10;});
            return new AnNativeRows($rows);
        }
        if(strpos($sql,'DELETE FROM bra_players WHERE type>0')===0) { $this->tables['bra_players']=array_filter($this->tables['bra_players'],function($p){return !$p['type'];}); return true; }
        if(preg_match('/^DELETE FROM (bra_mapitem|bra_maptrap) WHERE \w+=(\d+)$/',$sql,$m)) {unset($this->tables[$m[1]][(int)$m[2]]); return true;}
        if(strpos($sql,'SELECT gid,clbpara FROM bra_winners')===0) return new AnNativeRows($this->tables['bra_winners']);
        if(strpos($sql,'SELECT gid FROM bra_winners')===0) return new AnNativeRows($this->tables['bra_winners']?array(array('gid'=>max(array_keys($this->tables['bra_winners'])))):array());
        if(strpos($sql,'SELECT name,killnum FROM bra_players')===0) return new AnNativeRows(array(array('name'=>'Native','killnum'=>0)));
        if(strpos($sql,'SELECT * FROM bra_newsinfo')===0) return new AnNativeRows(array_reverse($this->tables['bra_newsinfo']));
        if(preg_match('/^INSERT INTO bra_newsinfo .* VALUES \((.*)\)$/',$sql,$m)) {
            $this->array_insert('bra_newsinfo',array_combine(array('time','news','a','b','c','d','e'),str_getcsv($m[1],',',"'",'\\'))); return true;
        }
        if(strpos($sql,'INSERT INTO bra_chat ')===0) return true;
        if(preg_match('/^INSERT INTO bra_winners \((.*?)\) VALUES \((.*)\)$/',$sql,$m)) {
            $columns=array_map(function($v){return trim($v,' `');},explode(',',$m[1]));
            $this->array_insert('bra_winners',array_combine($columns,str_getcsv($m[2],',',"'",'\\'))); return true;
        }
        if(preg_match("/^SELECT \\* FROM bra_users WHERE username=X'([0-9a-f]+)'$/",$sql,$m)) return new AnNativeRows(array_filter($this->tables['bra_users'],function($u)use($m){return $u['username']===hex2bin($m[1]);}));
        throw new RuntimeException('Unsupported native query: '.$sql);
    }
    public function multi_update($table,$updates,$key) {
        foreach($updates as $data) foreach($this->tables[$table] as &$row) if($row[$key]===$data[$key]) $row=array_merge($row,$data);
        unset($row);
    }
}
$checks=0;
function an_native_check($ok,$message) {global $checks; $checks++; if(!$ok) throw new RuntimeException($message);}
$oldcwd=getcwd(); chdir($temp);
try {
    require_once $source.'include/global.func.php';
    require_once $source.'include/game.func.php';
    require_once $source.'include/system.func.php';
    require_once $source.'include/news.func.php';
    require_once $source.'include/resources.func.php';
    $db=new AnNativeDB(); $gtablepre=$tablepre='bra_'; $groomid=0; $gamecfg=1; $now=$starttime=10000;
    $gamestate=20; $gamenum=1; $gamevars=array(); $arealist=array(0); $areanum=$areawarn=$hack=$combonum=$validnum=$alivenum=$deathnum=0;
    $areatime=10600; $weather=0; $winner=''; $winmode=0; $afktime=$optime=$now; $magic_quotes_gpc=false; $gshoplist=array();
    $hdamage=$noisetime=$noisepls=$noiseid=$noiseid2=0; $hplayer=$noisemode='';
    require config('resources',1); require config('gamecfg',1); require config('combatcfg',1);
    an_native_check(strpos(config('resources',1),'ruleset')===false && $baseexp===9,'无规则集仍加载正式公共配置');
    rs_game(2);
    an_native_check(count($arealist)===count($plsinfo) && !function_exists('ruleset_round_init_hook'),'无钩子仍按原生初始化禁区');
    $startmode=$startmin=$starthour=0;
    $old_player=create_dummy_playerdata(); $old_player['pid']=1; $old_player['name']='Old'; $old_player['hp']=$old_player['mhp']=400; $old_player['lvl']=1; $old_player['money']=0;
    $db->array_insert('bra_players',player_format_with_db_structure($old_player));
    $db->tables['bra_users'][1]=array('uid'=>1,'username'=>'Old','daily'=>'','motto'=>'','credits'=>100,'credits2'=>20,'validgames'=>5,'wingames'=>1,'achrev'=>'{}');
    $gamblingon=0; gameover($now,'end2','Old');
    an_native_check($db->tables['bra_users'][1]['credits']==301 && $db->tables['bra_users'][1]['credits2']===107 && $db->tables['bra_users'][1]['wingames']===2,'无钩子原生优胜成就、积分和胜局结果保持');
    an_native_check(strpos(file_get_contents($temp.'gamedata/bak/'.$gamenum.'_newsinfo.html'),'优胜者——Old')!==false,'无钩子原生新闻格式保持');
    $gamestate=20; $starttime=$now; $db->tables['bra_users']=array();
    $original=array('talents1'=>array('E1'=>'5','EU'=>'1'),'label'=>"<\"test'>");
    $_POST=$original; extract(gstrfilter($_POST));
    an_native_check($_POST===$original && $talents1===array('E1'=>'5','EU'=>'1'),'common的过滤和extract保留嵌套天赋且不覆写POST');
    $db->ruleset='ANOTHER';
    require config('resources',1); require config('gamecfg',1);
    require_once $source.'include/ruleset_override.func.php';
    init_ruleset_override(); load_ruleset_override_functions();
    an_native_check($current_ruleset_config['name']==='AN:OTHER' && $plsinfo[1]==='旧商业街集市','函数内注册表及模式资源作用域正确');
    an_native_check($baseexp===9 && $rsgame_bots===0 && !$gamblingon,'模式配置覆盖公共默认');
    $dummy=create_dummy_playerdata();
    an_native_check(is_array($dummy['clbpara']) && array_keys($dummy)===array_keys($types),'原生dummy完整符合players.sql结构');
    rs_game(63);
    $npcs=count($db->tables['bra_players']); $ground=count($db->tables['bra_mapitem']);
    an_native_check($npcs>0 && $ground>0 && count($db->tables['bra_maptrap'])>0,'原生rs_game完整重置并投放模式世界');
    $players=another_read_players(); $npc=reset($players);
    an_native_check(is_array($npc['weppara']) && $npc['weppara']['another']['uid']!=='' && $npc['clbpara']['another']['npc_role']!=='','真实格式化后NPC武器与角色标记保留');
    $p=another_empty_player(); $p['name']='Native'; $p['clbpara']['another']['owner']='Native';
    $p['clbpara']['another']['talents']=array('E1'=>5); $p['hp']=another_max_hp($p);
    another_item_set($p,'wep',another_item_make('gun'));
    $p=another_insert_player($p); $pid=$p['pid']; $uid=$p['weppara']['another']['uid'];
    $p['weppara']['another']['ammo']=7; $p['clbpara']['another']['earth_left']=2; player_save($p);
    $players=another_read_players(); $p=$players[$pid];
    an_native_check($p['hp']===600 && $p['mhp']===400 && $p['weppara']['another']['ammo']===7 && $p['weppara']['another']['uid']===$uid && $p['clbpara']['another']['earth_left']===2,'原生player_save保留有效血量、裸上限、弹药、UID及保护');
    $counts=array(count($db->tables['bra_players']),count($db->tables['bra_mapitem'])); rs_game(16);
    an_native_check(count($db->tables['bra_players'])===$counts[0] && count($db->tables['bra_mapitem'])===$counts[1],'地图单刷不重复NPC或物资');
    rs_game(8);
    an_native_check(count($db->tables['bra_players'])===$npcs+1 && isset($db->tables['bra_players'][$pid]),'原生NPC单刷保留玩家并重新投放NPC');
    $room=another_read_room(); $room['sample_saved']=true; another_save_world($room,$players,$room,$players);
    $gamevars=array(); load_gameinfo();
    an_native_check(!empty($gamevars['another']['sample_saved']) && $validnum===1 && $alivenum===1,'真实save/load_gameinfo保存房间进度及角色计数');
    $gamestate=20; $startmode=0; $startmin=0; $starthour=0;
    gameover($now,'end1','');
    an_native_check($gamestate===0 && $winmode==='1' && file_exists($temp.'gamedata/bak/'.$gamenum.'_newsinfo.html'),'真实gameover写新闻归档并结束对局');
    an_native_check(!empty($gamevars['another']['accounts_settled']),'真实gameover走模式账户结算');
    an_native_check(strpos(file_get_contents($temp.'gamedata/bak/'.$gamenum.'_newsinfo.html'),'本局无人脱出')!==false,'模式全灭和时限新闻不使用错误的原生结局文案');
    $db->tables['bra_users'][1]=array('uid'=>1,'username'=>'Native','daily'=>'','motto'=>'','credits'=>100,'credits2'=>20,'validgames'=>5,'wingames'=>1,'achrev'=>'{}');
    $db->tables['bra_users'][2]=array('uid'=>2,'username'=>'Mate','daily'=>'','motto'=>'','credits'=>200,'credits2'=>30,'validgames'=>6,'wingames'=>2,'achrev'=>'{}');
    $p2=another_empty_player(); $p2['name']='AN2:1:fixture'; $p2['clbpara']['another']['owner']='Native'; $p2['hp']=0; $p2=another_insert_player($p2);
    $p3=another_empty_player(); $p3['name']='Mate'; $p3['clbpara']['another']['owner']='Mate'; $p3['hp']=0; $p3=another_insert_player($p3);
    $gamestate=20; $gamevars['another']['accounts_settled']=false; $gamevars['another']['result']=array('winners'=>array($pid,$p2['pid'],$p3['pid']));
    gameover($now,'end2','Native');
    $achievement=json_decode($db->tables['bra_users'][1]['achrev'],true);
    an_native_check($achievement[16]['v']===1 && $db->tables['bra_users'][1]['wingames']===2 && $db->tables['bra_users'][2]['wingames']===3,'真实优胜结算全队执行原生成就并计胜一次');
    an_native_check($db->tables['bra_users'][1]['credits2']===107 && $db->tables['bra_users'][2]['credits2']===117,'真实成就奖励不被账户积分结算覆盖，双控不双发');
    an_native_check(strpos(file_get_contents($temp.'gamedata/bak/'.$gamenum.'_newsinfo.html'),'幸存脱出')!==false,'真实优胜新闻使用ANOTHER文案');
    an_native_check($db->tables['bra_winners'][$gamenum]['teamMate']==='Native+Mate','真实战绩用实际账户去重，不写AN2内部名字');
    $gamestate=20; $gamevars['another']['accounts_settled']=false;
    gameover($now,'end3','Native');
    $achievement=json_decode($db->tables['bra_users'][1]['achrev'],true);
    $mate_achievement=json_decode($db->tables['bra_users'][2]['achrev'],true);
    an_native_check($achievement[18]['v']===1 && $mate_achievement[18]['v']===1 && !isset($achievement[202]) && !isset($achievement[206]) && !isset($achievement[207]),'异常终止全队基础成就一次，不误发旧房间个人挑战');
    an_native_check($db->tables['bra_users'][1]['credits2']===417 && $db->tables['bra_users'][2]['credits2']===427,'异常终止全队成就与参与切糕正确');
    an_native_check(strpos(file_get_contents($temp.'gamedata/bak/'.$gamenum.'_newsinfo.html'),'异常终止')!==false,'异常终止真实新闻不写精神锁定');
    $db->tables['bra_players']=array(); $gamestate=20; $gamevars['another']=array(); $validnum=0;
    gameover($now,'end4','');
    an_native_check($gamestate===0 && $db->tables['bra_winners'][$gamenum]['wmode']==='4','无人截止走原生end4，不读取空击杀榜');
    echo 'AN:OTHER native integration: '.$checks." checks passed; native configs, reset, player persistence and gameover exercised.\n";
} finally {
    chdir($oldcwd);
    $resolved=realpath($temp); $temp_root=realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR;
    if($resolved===false || strpos($resolved,$temp_root)!==0 || strpos(basename($resolved),'another-native-test-')!==0) throw new RuntimeException('Unexpected fixture cleanup path');
    $walk=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($walk as $path) { if($path->isDir()) rmdir($path->getPathname()); else unlink($path->getPathname()); }
    rmdir($temp);
}
