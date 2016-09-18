<?php
class db{
	private $host;
	private $user;
	private $password;
	private $name;

	private $connect = null;
	
	public function __construct($host, $user, $pwd, $name) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $pwd;
		$this->name = $name;
		$this->connect();
		$this->select_db();
	}
	
	private function connect(){
		$this->connect = mysql_connect($this->host, $this->user, $this->password);
	}
	
	private function select_db(){
		if(!$this->connect) error(10006, "数据库未连接(The database is not connected)");
		mysql_select_db($this->name, $this->connect);
		mysql_query("set names utf8");
	}
	
	/**
	 * @desc 关闭数据库
	 */
	public function close(){
		return mysql_close($this->connect);
	}
	
	/**
	 * @desc 执行SQL语句
	 * @param string $sql SQL语句
	 * @return boolean
	 */
	public function query($sql) {
		if(!$this->connect) error(10006, "数据库未连接(The database is not connected)");
		return mysql_query($sql, $this->connect);
	}


	/**
	 * @desc 获取列表
	 */
	public function fetch_array($query) {
		return mysql_fetch_array($query);
	}
	
	public function fetch_assoc($query){
		return mysql_fetch_assoc($query);
	}
	
	/**
	 * @desc 启用事务
	 */
	public function start_trans(){
		mysql_query("START TRANSACTION", $this->connect);
	}
	
	/**
	 * @desc 回滚事务
	 */
	public function rollback(){
		if(!$this->connect) error(10006, "数据库未连接(The database is not connected)");
		$this->query = mysql_query("ROLLBACK", $this->connect);
		$this->close();
	}
	
	/**
	 * @desc 提交事务
	 */
	public function commit(){
		if(!$this->connect) error(10006, "数据库未连接(The database is not connected)");
		$this->query = mysql_query("COMMIT", $this->connect);
	}
}