<?php
include 'sql.php';
/**
 * 数据库类
 */
class Db {
	
	/**
	 * 数据库连接
	 */
	public static $_conn;

	/**
	 * 查询次数
	 */
	private static $_countQueries = 0;

	/**
	 * 连接数据库
	 */
	public static function connect($conf){
		if (!self::$_conn = @mysqli_connect($conf['host'].(empty($conf['port'])?'':':'.$conf['port']), $conf['user'], $conf['pass'])){
			throw new Exception('无法连接数据库');
		}
		if (!mysqli_select_db(self::$_conn, $conf['database'])){
			throw new Exception('无法选择数据库');
		}
		if (!mysqli_query(self::$_conn,'SET NAMES \'UTF8\'')){
			throw new Exception('无法设置数据库编码');
		}
	}

	/**
	 * 快速创建SELECT查询
	 */
	public static function select(){
		$args = func_get_args();
		return call_user_func_array(array(new Sql, 'select'), $args);
	}

	/**
	 * 快速创建INSERT查询
	 */
	public static function insert(){
		$args = func_get_args();
		return call_user_func_array(array(new Sql, 'insert'), $args);
	}

	/**
	 * 快速创建UPDATE查询
	 */
	public static function update(){
		$args = func_get_args();
		return call_user_func_array(array(new Sql, 'update'), $args);
	}

	/**
	 * 快速创建DELETE查询
	 */
	public static function delete(){
		$args = func_get_args();
		return call_user_func_array(array(new Sql, 'delete'), $args);
	}

	/**
	 * 执行SQL查询
	 */
	public static function query($sql){
		self::$_countQueries ++;
		return mysqli_query(self::$_conn, $sql);
	}

	/**
	 * 获取一组数据
	 */
	public static function fetch($result){
		return $result->fetch_assoc();
	}

	/**
	 * 获取全部数据
	 */
	public static function fetchAll($result){
		$data = array();
		while ($i = $result->fetch_assoc()){
			$data[] = $i;
		}
		return $data;
	}

	/**
	 * 获取单个字段
	 */
	public static function fetchOne($result){
		return ($row = mysqli_fetch_row($result)) ? $row[0] : null;
	}

	/**
	 * 统计结果条数
	 */
	public static function countRows($result){
		return mysqli_num_rows($result);
	}

	/**
	 * 最后插入的自动递增值
	 */
	public static function insertId(){
		return mysqli_insert_id(self::$_conn);
	}

	/**
	 * 统计影响行数
	 */
	public static function affectedRows(){
		return mysqli_affected_rows(self::$_conn);
	}

	/**
	 * 统计查询次数
	 */
	public static function countQueries(){
		return self::$_countQueries;
	}

}