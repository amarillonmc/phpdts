<?php

if (! defined ( 'IN_GAME' )) { exit ( 'Access Denied' ); }

class dbstuff {
	private $con = NULL;
	private $stmt = NULL;
	private $result = NULL;
	public $query_log = array();
	
	function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0) {
        try {
            $dsn = "mysql:host=$dbhost;dbname=$dbname";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->con = new PDO($dsn, $dbuser, $dbpw, $options);
        } catch (PDOException $e) {
            $this->halt($e->getMessage());
        }

        global $charset, $dbcharset;

        if (!$dbcharset && in_array(strtolower($charset), array('gbk', 'big5', 'utf-8'))) {
            $dbcharset = str_replace('-', '', $charset);
        }
        if ($dbcharset) {
            $this->con->exec("SET character_set_connection=$dbcharset, character_set_results=$dbcharset, character_set_client=$dbcharset");
        }

        $this->con->exec("SET sql_mode=''");
    }
    
    function select_db($dbname) {
        return true; // PDO selects the database upon connection
    }
    
    function fetch_array($query, $result_type = PDO::FETCH_ASSOC) {
        return $query->fetch($result_type);
    }
    
    function query($sql, $type = '') {
        if (!empty(debug_backtrace()[0]['file'])) {
            $this->query_log[] = $sql . ' from ' . debug_backtrace()[0]['file'] . ' : ' . debug_backtrace()[0]['line'];
        } else {
            $this->query_log[] = $sql;
        }

        try {
			
            $this->stmt = $this->con->prepare($sql);
            $this->result = $this->stmt->execute();

            if (strpos($sql, 'UPDATE') === 0) {
                if (strpos($sql, 'users') !== false && strpos($sql, 'room') !== false) {
                    $bk = debug_backtrace();
                    global $now;
                }
            }

            if (!$this->result && $type != 'SILENT') {
                $this->halt('MySQL Query Error', $sql);
            }

            return $this->stmt;
        } catch (PDOException $e) {
            $this->halt($e->getMessage(), $sql);
        }
    }
    
    function queries($queries, $ignore_result = true) {
        try {
            $this->con->beginTransaction();
            $this->con->exec($this->parse_create_table($queries));
            $this->con->commit();
        } catch (PDOException $e) {
            $this->con->rollBack();
            $this->halt($e->getMessage());
        }
    }
	
	function parse_create_table($sql) {
		global $dbcharset;
		if(!$dbcharset) include GAME_ROOT.'./include/modules/core/sys/config/server.config.php';
		$sql = preg_replace("/ENGINE\s*=\s*([a-z]+)/i", "ENGINE=$1 DEFAULT CHARSET=".$dbcharset, $sql);
		return $sql;

	}
	
	function array_insert($dbname, $data, $on_duplicate_update = 0, $keycol=''){
		$tp = 1;//单记录插入
		if(is_array(array_values($data)[0])) $tp = 2;//多记录插入 
		$query = "INSERT INTO {$dbname} ";
		$fieldlist = $valuelist = '';
		if(2!=$tp){//单记录插入
			if(!$data) return;
			foreach ($data as $key => $value) {
				$fieldlist .= "{$key},";
				$valuelist .= "'{$value}',";
			}
			if(!empty($fieldlist) && !empty($valuelist)){
				$query .= '(' . substr($fieldlist, 0, -1) . ') VALUES (' . substr($valuelist, 0, -1) .')';
			}
		}else{//多记录插入
			foreach (array_keys(array_values($data)[0]) as $key) {
				$fieldlist .= "{$key},";
			}
			foreach ($data as $dv){
				if(!$dv) continue;
				$valuelist .= "(";
				foreach ($dv as $value) {
					$valuelist .= "'{$value}',";
				}
				$valuelist = substr($valuelist, 0, -1).'),';
			}
			if(!empty($valuelist)) {
				$query .= '(' . substr($fieldlist, 0, -1) . ') VALUES '.substr($valuelist, 0, -1);
			}
		}
		if(!empty($query) && $on_duplicate_update && $keycol) {
			$query .= ' ON DUPLICATE KEY UPDATE ';
			$tmp = 2==$tp ? reset($data) : $data;
			foreach($tmp as $key => $value){
				if($key !== $keycol){
					$query .= '`'.$key.'`=VALUES(`'.$key.'`),';
				}
			}
			$query = substr($query, 0, -1);
		}
		
		if(!empty($query)) {
			$querystrlen = mb_strlen($query);
			if(2==$tp && sizeof($data) > 1 && $querystrlen > 1073000000) {
				//如果长度超过1M，从中断成两个数组再尝试
				//留一点冗余所以不是1073741824
				list($data1, $data2) = $this->arr_query_divide($data);
				$this->array_insert($dbname, $data1, $on_duplicate_update, $keycol);
				$this->array_insert($dbname, $data2, $on_duplicate_update, $keycol);
			}else{
				$this->query ($query);
			}
		}
		return $query;
	}
	
	function arr_query_divide($data)
	{
		if(sizeof($data) <= 1) return $data;
		$offset = (int)floor(sizeof($data)/2);
		return array(array_slice($data, 0, $offset), array_slice($data, $offset));
	}
	
	function array_update($dbname, $data, $where, $o_data=NULL){ //根据$data的键和键值更新数据
		$query = '';
		foreach ($data as $key => $value) {
			if(!is_array($o_data) || !isset($o_data[$key]) || $value !== $o_data[$key])
				$query .= "{$key} = '{$value}',";
		}
		if(!empty($query)){
			$query = "UPDATE {$dbname} SET ".substr($query, 0, -1) . " WHERE {$where}";
			$this->query ($query);
		}
		return $query;
	}
	
	function multi_update($dbname, $data, $confield, $singleqry = ''){
		$fields = $range = Array();
		foreach($data as $rval){
			$con = $rval[$confield];
			$range[] = "'$con'";
			foreach($rval as $fkey => $fval){
				if($fkey != $confield){
					if(isset(${$fkey.'qry'})){
						${$fkey.'qry'} .= "WHEN '$con' THEN '$fval' ";
					}else{
						$fields[] = $fkey;
						${$fkey.'qry'} = "(CASE $confield WHEN '$con' THEN '$fval' ";
					}
				}				
			}
		}
		$query = '';
		foreach($fields as $val){
			if(!empty(${$val.'qry'})){
				${$val.'qry'} .= "END) ";
				$query .= "$val = ${$val.'qry'},";
			}
		}
		
		if(!empty($query)) {
			if($singleqry){$singleqry = ','.$singleqry;}
			$query = "UPDATE {$dbname} SET ".substr($query,0,-1)."$singleqry WHERE $confield IN (".implode(',',$range).")";
			
			$querystrlen = mb_strlen($query);
			if(sizeof($data) > 1 && $querystrlen > 1073000000) {
				//如果长度超过1M，从中断成两个数组再尝试
				list($data1, $data2) = $this->arr_query_divide($data);
				$this->multi_update($dbname, $data1, $confield, $singleqry);
				$this->multi_update($dbname, $data2, $confield, $singleqry);
			}else{
				$this->query ($query);
			}
		}
		
		return $query;
	}
	
	function affected_rows() {
		return $this->stmt->rowCount();
	}
	
	function error() {
		return $this->con->errorInfo()[2];
	}
	
	function errno() {
		return intval($this->con->errorCode());
	}
	
	function result($query, $row) {
		$query->execute();
		$result = $query->fetchAll(PDO::FETCH_NUM);
		return $result[$row][0];
	}
	
	
	function data_seek($query, $row) {
		if ($row >= $query->rowCount()) {
			return false;
		}
		return $query->fetchColumn(0, PDO::FETCH_ORI_ABS, $row);
	}
	
	
	function num_rows($query) {
		return $query->rowCount();
	}
	
	function num_fields($query) {
		return $query->columnCount();
	}
	
	function next_result(){
		return $this->stmt->nextRowset();
	}
	
	function more_results(){
		return $this->stmt->nextRowsetExists();
	}
	
	function store_result() {
		return $this->con->fetchAll(PDO::FETCH_ASSOC);
	}
	
	function free_result($query) {
		$query->closeCursor();
	}
	
	function insert_id() {
		return $this->con->lastInsertId();
	}
	
	function fetch_row($query) {
		return $query->fetch(PDO::FETCH_NUM);
	}
	
	function fetch_fields($query) {
		return $query->fetch(PDO::FETCH_OBJ);
	}
	
	function version() {
		return $this->con->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	
	function close() {
		$this->con = null;
	}
	
	
	function halt($message = '', $sql = '') {
		header('Content-Type: text/HTML; charset=utf-8');
		echo '数据库错误。请联系管理员。<br><br>';
		echo '类错误信息：'.$message.'<br>';
		if(!empty($sql)) echo 'SQL语句：'.$sql;
		echo '<br><br>';
		$dberror = $this->errno().' '.$this->error();
		echo '数据库错误提示：'.$dberror.'<br><br>';
		//echo '以下是stack dump<br>';
		//var_export(debug_backtrace());
		die();
		require_once GAME_ROOT . './include/db/db_mysqli_error.inc.php';
	}
	
	function __destruct() {
		$this->close();
		//file_put_contents(GAME_ROOT.'/query_log.txt', implode("\r\n",$this->query_log)."\r\n\r\n", FILE_APPEND);
	}
}

/* End of file db_mysqli.class.php */
/* Location: /include/db/db_mysqli.class.php */
