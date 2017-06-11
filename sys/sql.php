<?php
/**
 * 查询语句类
 */
class Sql {
	/**
	 * 数据库关键字
	 */
	const KEYWORDS = '*PRIMARY|AND|OR|LIKE|BINARY|BY|DISTINCT|AS|IN|IS|NULL|NOT';

	/**
	 * 准备中的语句
	 */
	private $_sqlPreBuild = array(
		'action' => NULL,
		'table'  => NULL,
		'fields' => '*',
		'join'   => array(),
		'where'  => NULL,
		'orWhere'=> NULL,
		'limit'  => NULL,
		'offset' => NULL,
		'order'  => NULL,
		'group'  => NULL,
		'having' => NULL,
		'rows'   => array(),
	);

	/**
	 * 自动括字段
	 */
	public function quoteColumn($str){
		$str = $str.' 0';
		$length = strlen($str);
		$lastIsAlnum = false;
		$result = '';
		$word = '';
		$split = '';
		$quotes = 0;

		for ($i = 0; $i < $length; $i ++) {
			$cha = $str[$i];

			if (ctype_alnum($cha) || false !== strpos('_*', $cha)) {
				if (!$lastIsAlnum) {
					if ($quotes > 0 && !ctype_digit($word) && '.' != $split
					&& false === strpos(self::KEYWORDS, strtoupper($word))) {
						$word = '`'.$word.'`';
					}

					$result .= $word.$split;
					$word = '';
					$quotes = 0;
				}

				$word .= $cha;
				$lastIsAlnum = true;
			} else {
				if ($lastIsAlnum) {
					if (0 == $quotes) {
						if (false !== strpos(' ,)=<>.+-*/', $cha)) {
							$quotes = 1;
						} else if ('(' == $cha) {
							$quotes = -1;
						}
					}
					$split = '';
				}
				$split .= $cha;
				$lastIsAlnum = false;
			}
		}

		return $result;
	}

	/**
	 * 转义参数
	 */
	public function quoteValues(array $values){
		foreach ($values as &$value) {
			if (is_array($value)) {
				$str = '';
				foreach ($values as $value) {
					$str .= '\''.addslashes($value).'\',';
				}
				$value = '('.substr($str, 0, -1).')';
			} else {
				$value = '\''.addslashes($value).'\'';
			}
		}
		return $values;
	}

	/**
	 * 连接表
	 */
	public function join($table, $condition, $op = 'INNER'){
		$this->_sqlPreBuild['join'][] = array('table'=>$table, 'condition'=>$this->quoteColumn($condition), 'op'=>$op);
		return $this;
	}
	
	/**
	 * AND条件查询语句
	 */
	public function where(){
		$condition = func_get_arg(0);
		$condition = str_replace('?', '%s', $this->quoteColumn($condition));
		$operator = empty($this->_sqlPreBuild['where']) ? '' : ' AND';

		if (func_num_args() <= 1) {
			$this->_sqlPreBuild['where'] .= $operator.' ('.$condition.')';
		} else {
			$args = func_get_args();
			array_shift($args);
			$this->_sqlPreBuild['where'] .= $operator.' ('.vsprintf($condition, $this->quoteValues($args)).')';
		}

		return $this;
	}

	/**
	 * OR条件查询语句
	 */
	public function orWhere(){
		$condition = func_get_arg(0);
		$condition = str_replace('?', '%s', $this->quoteColumn($condition));
		$operator = ' OR';

		if (func_num_args() <= 1) {
			$this->_sqlPreBuild['orWhere'] .= $operator.' ('.$condition.')';
		} else {
			$args = func_get_args();
			array_shift($args);
			$this->_sqlPreBuild['orWhere'] .= $operator.' ('.vsprintf($condition, $this->quoteValues($args)).')';
		}

		return $this;
	}

	/**
	 * 查询行数限制
	 */
	public function limit($offset, $limit=''){
		if (empty($limit)){
			$this->_sqlPreBuild['limit'] = intval($offset);
		} else {
			$this->_sqlPreBuild['offset'] = intval($offset);
			$this->_sqlPreBuild['limit'] = intval($limit);
		}
		return $this;
	}

	/**
	 * 查询行数偏移量
	 */
	public function offset($offset){
		$this->_sqlPreBuild['offset'] = intval($offset);
		return $this;
	}

	/**
	 * 分页查询
	 */
	public function page($pageSize, $page){
		$pageSize = intval($pageSize);
		$this->_sqlPreBuild['limit'] = $pageSize;
		$this->_sqlPreBuild['offset'] = (max(intval($page), 1) - 1) * $pageSize;
		return $this;
	}

	/**
	 * 指定需要写入的栏目及其值
	 */
	public function row($key, $value){
		$this->_sqlPreBuild['rows'][$this->quoteColumn($key)] = is_null($value) ? 'NULL' : '\''.addslashes($value).'\'';
		return $this;
	}

	/**
	 * 写入多组
	 */
	public function rows(array $rows){
		foreach ($rows as $key => $row) {
			$this->_sqlPreBuild['rows'][$this->quoteColumn($key)] = is_null($row) ? 'NULL' : '\''.addslashes($row).'\'';
		}
		return $this;
	}

	/**
	 * 指定需要写入栏目及其值
	 * 单行且不会转义引号
	 */
	public function expression($key, $value, $escape = true){
		$this->_sqlPreBuild['rows'][$this->quoteColumn($key)] = $escape ? $this->quoteColumn($value) : $value;
		return $this;
	}

	/**
	 * 排序顺序(ORDER BY)
	 *
	 * @param string $orderby 排序的索引
	 * @param string $sort 排序的方式(ASC, DESC)
	 * @return Typecho_Db_Query
	 */
	public function order($orderby, $sort = 'ASC'){
		$this->_sqlPreBuild['order'] = ' ORDER BY '.$this->quoteColumn($orderby).(empty($sort) ? NULL : ' '.$sort);
		return $this;
	}

	/**
	 * 集合聚集(GROUP BY)
	 */
	public function group($key){
		$this->_sqlPreBuild['group'] = ' GROUP BY '.$this->quoteColumn($key);
		return $this;
	}

	/**
	 * HAVING (HAVING)
	 */
	public function having(){
		$condition = func_get_arg(0);
		$condition = str_replace('?', '%s', $this->quoteColumn($condition));
		$operator = empty($this->_sqlPreBuild['having']) ? ' HAVING ' : ' AND';

		if (func_num_args() <= 1) {
			$this->_sqlPreBuild['having'] .= $operator.' ('.$condition.')';
		} else {
			$args = func_get_args();
			array_shift($args);
			$this->_sqlPreBuild['having'] .= $operator.' ('.vsprintf($condition, $this->quoteValues($args)).')';
		}

		return $this;
	}

	/**
	 * 选择查询字段
	 */
	public function select($field = '*'){
		$this->_sqlPreBuild['action'] = 'SELECT';

		if (0 == func_num_args()){
			$this->_sqlPreBuild['fields'] = '*';
		} else {
			$args = func_get_args();
			$fields = array();
			foreach ($args as $value) {
				if (is_array($value)) {
					foreach ($value as $key => $val) {
						$fields[] = $key . ' AS ' . $val;
					}
				} else {
					 $fields[] = $value;
				}
			}
			$this->_sqlPreBuild['fields'] = $this->quoteColumn(implode(',', $fields));
		}

		return $this;
	}

	/**
	 * 查询记录操作(SELECT)
	 */
	public function from($table){
		$this->_sqlPreBuild['table'] = $table;
		return $this;
	}

	/**
	 * 更新记录操作(UPDATE)
	 */
	public function update($table){
		$this->_sqlPreBuild['action'] = 'UPDATE';
		$this->_sqlPreBuild['table'] = $table;
		return $this;
	}

	/**
	 * 删除记录操作(DELETE)
	 */
	public function delete($table){
		$this->_sqlPreBuild['action'] = 'DELETE';
		$this->_sqlPreBuild['table'] = $table;
		return $this;
	}

	/**
	 * 插入记录操作(INSERT)
	 */
	public function insert($table){
		$this->_sqlPreBuild['action'] = 'INSERT';
		$this->_sqlPreBuild['table'] = $table;
		return $this;
	}

	/**
	 * 执行SQL查询
	 */
	public function query(){
		if ($rs = Db::query($this)){
			if ('INSERT' == $this->_sqlPreBuild['action']){
				if ($id = Db::insertId()){
					return $id;
				} else {
					return true;
				}
			} else if ('UPDATE' == $this->_sqlPreBuild['action'] || 'DELETE' == $this->_sqlPreBuild['action']){
				return Db::affectedRows();
			} else {
				return $rs;
			}
		} else {
			return false;
		}
	}

	/**
	 * 快速获取一行
	 */
	public function fetch(){
		$this->_sqlPreBuild['limit'] = 1;
		if ($rs = Db::query($this)){
			return Db::fetch($rs);
		} else {
			return false;
		}
	}

	/**
	 * 快速获取一个字段
	 */
	public function fetchOne(){
		$this->_sqlPreBuild['limit'] = 1;
		if ($rs = Db::query($this)){
			return Db::fetchOne($rs);
		} else {
			return false;
		}
	}

	/**
	 * 快速获取所有行
	 */
	public function fetchAll(){
		if ($rs = Db::query($this)){
			return Db::fetchAll($rs);
		} else {
			return false;
		}
	}

	/**
	 * 构造最终查询语句
	 */
	public function __toString(){
		$sql = $this->_sqlPreBuild;
		switch ($this->_sqlPreBuild['action']) {
			case 'SELECT':
				if (!empty($sql['join'])) {
					foreach ($sql['join'] as $join) {
						$sql['table'] .= " {$join['op']} JOIN {$join['table']} ON {$join['condition']}";
					}
				}
				$sql['limit'] = (0 == strlen($sql['limit'])) ? NULL : ' LIMIT ' . $sql['limit'];
				$sql['offset'] = (0 == strlen($sql['offset'])) ? NULL : ' OFFSET ' . $sql['offset'];
				return 'SELECT ' . $sql['fields'] . ' FROM ' . $sql['table']
				. (('' == $sql['where'].$sql['orWhere']) ? '' : ' WHERE'.$sql['where'].$sql['orWhere'])
				. $sql['group'] . $sql['having'] . $sql['order'] . $sql['limit'] . $sql['offset'];
			case 'INSERT':
				return 'INSERT INTO '
				. $sql['table']
				. '('.implode(' , ', array_keys($sql['rows'])).')'
				. ' VALUES '
				. '('.implode(' , ', array_values($sql['rows'])).')'
				. $sql['limit'];
			case 'DELETE':
				return 'DELETE FROM '
				. $sql['table']
				. (('' == $sql['where'].$sql['orWhere']) ? '' : ' WHERE'.$sql['where'].$sql['orWhere']);
			case 'UPDATE':
				$columns = array();
				if (isset($sql['rows'])) {
					foreach ($sql['rows'] as $key => $val) {
						$columns[] = "$key = $val";
					}
				}
				return 'UPDATE '
				. $sql['table']
				. ' SET '.implode(' , ', $columns)
				. (('' == $sql['where'].$sql['orWhere']) ? '' : ' WHERE'.$sql['where'].$sql['orWhere']);
			default:
				return '';
		}
	}
}