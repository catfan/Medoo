<?php
/*!
 * Medoo database framework
 * http://medoo.in
 * Version 0.9.x
 * 
 * Copyright 2014, Angel Lai
 * Released under the MIT license
 */
class medoo
{
	protected $database_type = 'mysql';

	// For MySQL, MSSQL, Sybase
	protected $server = 'localhost';

	protected $username = 'username';

	protected $password = 'password';

	// For SQLite
	protected $database_file = '';

	// Optional
	protected $port = 3306;

	protected $charset = 'utf8';

	protected $database_name = '';

	protected $option = array();

	// Variable 
	protected $queryString;

	private static $AGGREGATIONS = array ('AVG','SUM','MIN','COUNT','MAX');	
	
	public function __construct($options)
	{
		try {
			$commands = array();

			if (is_string($options))
			{
				if (strtolower($this->database_type) == 'sqlite')
				{
					$this->database_file = $options;
				}
				else
				{
					$this->database_name = $options;
				}
			}
			else
			{
				foreach ($options as $option => $value)
				{
					$this->$option = $value;
				}
			}

			$type = strtolower($this->database_type);

			if (
				isset($this->port) &&
				is_int($this->port * 1)
			)
			{
				$port = $this->port;
			}

			$set_charset = "SET NAMES '" . $this->charset . "'";

			switch ($type)
			{
				case 'mariadb':
					$type = 'mysql';

				case 'mysql':
					// Make MySQL using standard quoted identifier
					$commands[] = 'SET SQL_MODE=ANSI_QUOTES';

				case 'pgsql':
					$dsn = $type . ':host=' . $this->server . (isset($port) ? ';port=' . $port : '') . ';dbname=' . $this->database_name;
					$commands[] = $set_charset;

					break;

				case 'sybase':
					$dsn = $type . ':host=' . $this->server . (isset($port) ? ',' . $port : '') . ';dbname=' . $this->database_name;
					$commands[] = $set_charset;

					break;

				case 'mssql':
					$dsn = strpos(PHP_OS, 'WIN') !== false ?
						'sqlsrv:server=' . $this->server . (isset($port) ? ',' . $port : '') . ';database=' . $this->database_name :
						'dblib:host=' . $this->server . (isset($port) ? ':' . $port : '') . ';dbname=' . $this->database_name;

					// Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
					$commands[] = 'SET QUOTED_IDENTIFIER ON';
					$commands[] = $set_charset;

					break;

				case 'sqlite':
					$dsn = $type . ':' . $this->database_file;
					$this->username = null;
					$this->password = null;

					break;
			}

			$this->pdo = new PDO(
				$dsn, 
				$this->username,
				$this->password,
				$this->option
			);

			foreach ($commands as $value)
			{
				$this->pdo->exec($value);	
			}
		}
		catch (PDOException $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function query($query)
	{
		$this->queryString = $query;
		
		return $this->pdo->query($query);
	}

	public function exec($query)
	{
		$this->queryString = $query;

		return $this->pdo->exec($query);
	}

	public function quote($string, $is_function = false) {
		return $is_function ? $string : $this->pdo->quote($string);
	}

	protected function column_quote($string)
	{
		return ' "' . str_replace('.', '"."', $string) . '" ';
	}

	protected function column_push($columns)
	{
		
		if ($columns == '*')
		{
			return $columns;
		}

		if (is_string($columns) || is_int($columns))
		{
			$columns = array((string) $columns);
		}
		$stack = array();

		foreach ($columns as $key => $value)
		{
			preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-\*]*)\)/i', $value, $match);
		
			if (
				isset($match[1]) &&
				isset($match[2])
			)
			{	
				if(in_array(strtoupper($match[1]), Medoo::$AGGREGATIONS)){
					$a = $match[1]  . '(' .( $match[2] == '*' ? $match[2] : $this->column_quote( $match[2] )) . ')';
					array_push($stack, $a);
				} else {
					array_push($stack, $this->column_quote( $match[1] ) . ' AS ' . $this->column_quote( $match[2] ));
				}
			}
			else
			{
				if($value == "1"){
					array_push($stack, $value);
				} else {
					array_push($stack, $this->column_quote( $value ));
				}
			}
		}
		return implode($stack, ',');
	}

	function data_implode($part, $separator = null){
		$result = array();
		$separator = isset($separator) ? trim($separator): $separator; 
		
		if(is_array($part)){
			// boolean block and value lists
			foreach($part as $key=>$value){
				$key = is_string($key) ? trim($key) : $key; 
				if($key === 'ORDER' || $key === 'GROUP' ||$key === 'HAVING' 
				 ||$key === 'LIMIT' ||$key === 'LIKE' ||$key === 'MATCH' ){
					break;
				} elseif($key === 'AND' || $key === 'OR') {
					if( isset($separator) ) {
						$result[] = ' ('.$this->data_implode($value, $key). ') '; 
					} else {
						$result[] = $this->data_implode($value, $key); 
					}
				} elseif( is_int($key) ){
					if( $separator === 'OR' ) {
						$result[] = ' ('.$this->data_implode($value, 'AND'). ') '; 
					} elseif( $separator === 'AND' ) {	
						$result[] = ' ('.$this->data_implode($value, 'OR'). ') '; 
					} else {
						$result[] = $this->data_implode($value, ',') ; 
					}
				} else {
					// not a key less array or a boolean block
					$result[] = $this->get_term($key, $value); 
				}
			}
		} else {
			// single value
			$result[] = $this->quote($part); 
		}
 		return str_replace("  ", " " , implode( (isset($separator) ? $separator : 'AND'), $result)); 
	}
	
	function get_term($key, $value){
		$not = ''; 
		preg_match('/([\w\.]+)(\[(#?)(\>|\<|\=|\!|\>\=|\<\=|\<\>)\])?/', $key, $match);

		$is_function = isset($match[3]); 
		
		if (isset($match[4])) {
			if ($match[4] == '') {
				 return $this->column_quote($key).'='.$this->quote($value, $is_function); 
			} else {
				// not block
				if ($match[4] == '!') {
					switch (gettype($value)) {
						case 'NULL':
						case 'array':
							$not = 'NOT ';
							break;
						case 'integer':
						case 'double':
						case 'string':
							$not = '!'; 
							break;
					}
				}
			}
		}
		switch (gettype($value)){
			case 'NULL':
				return $this->column_quote($key).' IS '.$not.$this->quote($value, $is_function); 
				break;

			case 'array':
				if (isset($match[4]) && $match[3] === '<>' && count($value) == 2) {
					return ' ('.$this->column_quote($match[1]).' BETWEEN '.$this->quote($value[0], $is_function).' AND '.$this->quote($value[1], $is_function).') '; 
				} else {
					return $this->column_quote($match[1]).$not.' IN ('.$this->data_implode($value, ',').') '; 
				}
				break;
			case 'string':
				// for the date feature you need a condition (e.g. [=] or [!=])
				if(isset($match[3])){
					$datetime = strtotime($value);
					if($datetime){
						$value = date('Y-m-d H:i:s', $datetime); 
					}
				}
			case 'integer':
			case 'double':
				if (isset($match[4]) && $match[4] !== '!') {
					return  $this->column_quote($match[1]) . ' ' . $match[4] . ' ' . $this->quote($value, $is_function). ' ';
				} else {
					return  $this->column_quote($match[1]).$not.'= '.$this->quote($value, $is_function). ' '; 
				}
				break;
		}
		throw new Exception('Unknown term type'); 
	}

	
	public function where_clause($where)
	{
		$where_clause = '';

		if (is_array($where))
		{
			$single_condition = array_diff_key($where, array_flip(
				explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
			));

			if ($single_condition != array())
			{
				$where_clause = ' WHERE ' . $this->data_implode($single_condition, '');
			}
			if (isset($where['AND']))
			{
				$where_clause = ' WHERE ' . $this->data_implode($where['AND'], 'AND ');
			}
			if (isset($where['OR']))
			{
				$where_clause = ' WHERE ' . $this->data_implode($where['OR'], 'OR ');
			}
			if (isset($where['LIKE']))
			{
				$like_query = $where['LIKE'];
				if (is_array($like_query))
				{
					$is_OR = isset($like_query['OR']);

					if ($is_OR || isset($like_query['AND']))
					{
						$connector = $is_OR ? 'OR' : 'AND';
						$like_query = $is_OR ? $like_query['OR'] : $like_query['AND'];
					}
					else
					{
						$connector = 'AND';
					}

					$clause_wrap = array();
					foreach ($like_query as $column => $keyword)
					{
						if (is_array($keyword))
						{
							foreach ($keyword as $key)
							{
								$clause_wrap[] = $this->column_quote($column) . ' LIKE ' . $this->quote('%' . $key . '%');
							}
						}
						else
						{
							$clause_wrap[] = $this->column_quote($column) . ' LIKE ' . $this->quote('%' . $keyword . '%');
						}
					}
					$where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . '(' . implode($clause_wrap, ' ' . $connector . ' ') . ')';
				}
			}
			if (isset($where['MATCH']))
			{
				$match_query = $where['MATCH'];
				if (is_array($match_query) && isset($match_query['columns']) && isset($match_query['keyword']))
				{
					$where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . ' MATCH ("' . str_replace('.', '"."', implode($match_query['columns'], '", "')) . '") AGAINST (' . $this->quote($match_query['keyword']) . ')';
				}
			}
			if (isset($where['GROUP']))
			{
				$where_clause .= ' GROUP BY ' . $this->column_quote($where['GROUP']);

        if (isset($where['HAVING']))
				{
					$where_clause .= ' HAVING ' . $this->data_implode($where['HAVING'], '');
				}
			}
			if (isset($where['ORDER']))
			{
        
        $where_clause .= ' ORDER BY ';
        $order_by_declaration = $where['ORDER'];
        
        if(is_string($order_by_declaration)){
          $order_by_declaration = explode(',',$where['ORDER']);
        }
        
        $order = array();
        
        foreach($order_by_declaration as $value) {
          preg_match('/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/', trim($value), $order_match);
          $order_by[] = $this->column_quote($value) .' '. (isset($order_match[3]) ? $order_match[3] : '');
        }
        $where_clause .= implode(' , ', $order_by);
        
			}
			if (isset($where['LIMIT']))
			{
				if (is_numeric($where['LIMIT']))
				{
					$where_clause .= ' LIMIT ' . $where['LIMIT'];
				}
				if (
					is_array($where['LIMIT']) &&
					is_numeric($where['LIMIT'][0]) &&
					is_numeric($where['LIMIT'][1])
				)
				{
					$where_clause .= ' LIMIT ' . $where['LIMIT'][0] . ',' . $where['LIMIT'][1];
				}
			}
		}
		else
		{
			if ($where != null)
			{
				$where_clause .= ' ' . $where;
			}
		}

		return $where_clause;
	}

	private function select_query($table, $join, $columns = null, $where = null)
	{
		$table = '"' . $table . '"';
		$join_key = is_array($join) ? array_keys($join) : null;

		if (strpos($join_key[0], '[') !== false)
		{
			$table_join = array();

			$join_array = array(
				'>' => 'LEFT',
				'<' => 'RIGHT',
				'<>' => 'FULL',
				'><' => 'INNER'
			);

			foreach($join as $sub_table => $relation)
			{
				preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)/', $sub_table, $match);

				if ($match[2] != '' && $match[3] != '')
				{
					if (is_string($relation))
					{
						$relation = 'USING ("' . $relation . '")';
					}

					if (is_array($relation))
					{
						// For ['column1', 'column2']
						if (isset($relation[0]))
						{
							$relation = 'USING ("' . implode($relation, '", "') . '")';
						}
						// For ['column1' => 'column2']
						else
						{
							$relation = 'ON ' . $table . '."' . key($relation) . '" = "' . $match[3] . '"."' . current($relation) . '"';
						}
					}

					$table_join[] = $join_array[ $match[2] ] . ' JOIN "' . $match[3] . '" ' . $relation;
				}
			}

			$table .= ' ' . implode($table_join, ' ');
		}
		else
		{
			$where = $columns;
			$columns = $join;
		}

		$query = 'SELECT ' . $this->column_push($columns) . ' FROM ' . $table . $this->where_clause($where);
		return $query; 
	}
	
	
	public function select($table, $join, $columns = null, $where = null) {
		$query = $this->query($this->select_query($table, $join, $columns, $where));
		return $query ? $query->fetchAll(
			(is_string($columns) && $columns != '*') ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC
		) : false;
	}

	public function insert($table, $datas)
	{
		$lastId = array();

		// Check indexed or associative array
		if (!isset($datas[0]))
		{
			$datas = array($datas);
		}

		foreach ($datas as $data)
		{
			$keys = array();
			$values = array();

			foreach ($data as $key => $value)
			{
				switch (gettype($value)) {
					case 'NULL':
						$values[] = 'NULL';
						break;

					case 'array':
						$values[] = $this->quote(serialize($value));
						break;

					case 'string':
            preg_match('/([\w\.]+)(\[(#?)\])?/', $key, $match);
						$is_function = isset($match[3]) && $match[3] === '#'; 
            $key = $match[1];
					case 'integer':
					case 'double':
						$values[] = $this->quote($value, $is_function);
						break;
				}
        $keys[] = $key; 
			}
			
			
			$this->exec('INSERT INTO "' . $table . '" ("' . implode('", "', $keys ) . '") VALUES (' . implode($values, ', ') . ')');

			$lastId[] = $this->pdo->lastInsertId();
		}

		return count($lastId) > 1 ? $lastId : $lastId[ 0 ];
	}

	public function update($table, $data, $where = null)
	{
		$fields = array();

		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
        $fields[] = $this->column_quote($key) . ' = ' . $this->quote(serialize($value));
			}
			else
			{
				preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);
				if (isset($match[3]))
				{
					if (is_numeric($value))
					{
						$fields[] = $this->column_quote($match[1]) . ' = ' . $this->column_quote($match[1]) . ' ' . $match[3] . ' ' . $value;
					}
				}
				else
				{
					$column = $this->column_quote($key);
					switch (gettype($value))
					{
						case 'NULL':
							$fields[] = $column . ' = NULL';
							break;

						case 'array':
              $fields[] = $this->column_quote($key) . ' = ' . $this->quote(serialize($value));
							break;

						case 'string':
              preg_match('/([\w\.]+)(\[(#?)\])?/', $key, $match);
              $is_function = isset($match[3]) && $match[3] === '#'; 
              $column = $this->column_quote($match[1]);
						case 'integer':
						case 'double':
							$fields[] = $column . ' = ' . $this->quote($value, $is_function);
							break;
					}
				}
			}
		}

		return $this->exec('UPDATE "' . $table . '" SET ' . implode(', ', $fields) . $this->where_clause($where));
	}

	public function delete($table, $where)
	{
		return $this->exec('DELETE FROM "' . $table . '"' . $this->where_clause($where));
	}

	public function replace($table, $columns, $search = null, $replace = null, $where = null)
	{
		if (is_array($columns))
		{
			$replace_query = array();

			foreach ($columns as $column => $replacements)
			{
				foreach ($replacements as $replace_search => $replace_replacement)
				{
					$replace_query[] = $column . ' = REPLACE("' . $column . '", ' . $this->quote($replace_search) . ', ' . $this->quote($replace_replacement) . ')';
				}
			}
			$replace_query = implode(', ', $replace_query);
			$where = $search;
		}
		else
		{
			if (is_array($search))
			{
				$replace_query = array();

				foreach ($search as $replace_search => $replace_replacement)
				{
					$replace_query[] = $columns . ' = REPLACE("' . $columns . '", ' . $this->quote($replace_search) . ', ' . $this->quote($replace_replacement) . ')';
				}
				$replace_query = implode(', ', $replace_query);
				$where = $replace;
			}
			else
			{
				$replace_query = $columns . ' = REPLACE("' . $columns . '", ' . $this->quote($search) . ', ' . $this->quote($replace) . ')';
			}
		}

		return $this->exec('UPDATE "' . $table . '" SET ' . $replace_query . $this->where_clause($where));
	}

	public function get($table, $join, $columns = null, $where = null)
	{
		if (!isset($where) && !isset($columns))
		{
			$columns = array();
			$columns['LIMIT'] = 1;
		} else if (!isset($where) ) {
			$columns['LIMIT'] = 1;
		}
		$data = $this->select($table, $join, $columns, $where);
		return isset($data[0]) ? $data[0] : false;
	}

	private function generate_aggregation_query($aggregation, $table, $column, $join , $where = null){
		if($where == null) {
			$statement =  $this->select_query($table,  $aggregation.'('.$column.')' , $join); 
		} else {
			$statement =  $this->select_query($table, $join, $aggregation.'('.$column.')' , $where) ; 
		}
		return $statement; 
	}
	
	public function has($table, $join , $where = null)
	{ 
		if($where == null) {
			$statement = 'SELECT EXISTS(' . $this->select_query($table, "1" , $join) . ')'; 
		} else {
			$statement = 'SELECT EXISTS(' . $this->select_query($table, $join, "1" , $where) . ')'; 
		}
		return $this->query($statement)->fetchColumn() === '1';
	}

	public function count($table, $join, $where = null)
	{
		return 0 + ($this->query($this->generate_aggregation_query('COUNT', $table, '*', $join , $where = null))->fetchColumn());
	}

	public function max($table, $join, $column, $where = null)
	{
		return 0 + ($this->query($this->generate_aggregation_query('MAX', $table, '*', $join , $where = null))->fetchColumn());
	}

	public function min($table, $join, $column, $where = null)
	{
		return 0 + ($this->query($this->generate_aggregation_query('MIN', $table, '*', $join , $where = null))->fetchColumn());
	}

	public function avg($table, $join, $column, $where = null)
	{
		return 0 + ($this->query($this->generate_aggregation_query('AVG', $table, '*', $join , $where = null))->fetchColumn());
	}

	public function sum($table, $join, $column, $where = null)
	{
		return 0 + ($this->query($this->generate_aggregation_query('SUM', $table, '*', $join , $where = null))->fetchColumn());
	}

	public function error()
	{
		return $this->pdo->errorInfo();
	}

	public function last_query()
	{
		// return str_replace('"', '', $this->queryString);
		return "SET SQL_MODE=ANSI_QUOTES; " .$this->queryString;
	}

	public function info()
	{
		return array(
			'server' => $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO),
			'client' => $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
			'driver' => $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
			'version' => $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
			'connection' => $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)
		);
	}
}
?>
