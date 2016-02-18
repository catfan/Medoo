<?php
/*!
 * Medoo database framework
 * http://medoo.in
 * Version 1.0.2
 *
 * Copyright 2016, Angel Lai
 * Released under the MIT license
 */
class medoo
{
    	/**
     	 * @var string $database_type
     	 * MySQL, MariaDB, MSSQL, Sybase, PostgreSQL, Oracle, sqlite
     	 */
	protected $database_type;
    	/**
     	 * @var $charset
     	 * Character set
     	 */
	protected $charset;
	/**
	 * @var string $database_name
	 * Database name used for connection
	 */
	protected $database_name;
	/**
	 * @var string $server
	 * For MySQL, MariaDB, MSSQL, Sybase, PostgreSQL, Oracle
	 */
	protected $server;
	/**
	 * @var string $username
	 * Database user connection
	 */
	protected $username;
	/**
	 * @var string $password
	 * Database password connection
	 */
	protected $password;
    	/**
     	 * @var string $database_file
     	 * database file for SQLITE only
     	 */
	protected $database_file;
    	/**
     	 * @var string $socket
     	 * For MySQL or MariaDB with unix_socket
     	 */
	protected $socket;
    	/**
     	 * @var integer $port
     	 * port connection as Optional
     	 */
	protected $port;
    	/**
     	 * @var string $prefix
     	 * Database table prefix
         */
	protected $prefix;
    	/**
     	 * @var array $options
     	 * record of options PDO Attribute
     	 */
	protected $option = array();
	/**
	 * @var array $logs
     	 * record of logs query
     	 */
	protected $logs = array();
	/**
	 * @var boolean
	 * Set Medoo in debug mode or not
	 */
	protected $debug_mode = false;
    	/**
     	 * PHP5 contructor called first called class
     	 * build connection the PDO
     	 *
     	 * @param mixed array $options the options configuration
     	 */
	public function __construct($options = null)
	{
		try {
			$commands = array();
			$dsn = '';

			if (is_array($options))
			{
				foreach ($options as $option => $value)
				{
					$this->$option = $value;
				}
			}
			else
			{
				return false;
			}

			if (
				isset($this->port) &&
				is_int($this->port * 1)
			)
			{
				$port = $this->port;
			}

			$type = strtolower($this->database_type);
			$is_port = isset($port);

			if (isset($options[ 'prefix' ]))
			{
				$this->prefix = $options[ 'prefix' ];
			}

			switch ($type)
			{
				case 'mariadb':
					$type = 'mysql';

				case 'mysql':
					if ($this->socket)
					{
						$dsn = $type . ':unix_socket=' . $this->socket . ';dbname=' . $this->database_name;
					}
					else
					{
						$dsn = $type . ':host=' . $this->server . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->database_name;
					}

					// Make MySQL using standard quoted identifier
					$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
					break;

				case 'pgsql':
					$dsn = $type . ':host=' . $this->server . ($is_port ? ';port=' . $port : '') . ';dbname=' . $this->database_name;
					break;

				case 'sybase':
					$dsn = 'dblib:host=' . $this->server . ($is_port ? ':' . $port : '') . ';dbname=' . $this->database_name;
					break;

				case 'oracle':
					$dbname = $this->server ?
						'//' . $this->server . ($is_port ? ':' . $port : ':1521') . '/' . $this->database_name :
						$this->database_name;

					$dsn = 'oci:dbname=' . $dbname . ($this->charset ? ';charset=' . $this->charset : '');
					break;

				case 'mssql':
					$dsn = strstr(PHP_OS, 'WIN') ?
						'sqlsrv:server=' . $this->server . ($is_port ? ',' . $port : '') . ';database=' . $this->database_name :
						'dblib:host=' . $this->server . ($is_port ? ':' . $port : '') . ';dbname=' . $this->database_name;

					// Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
					$commands[] = 'SET QUOTED_IDENTIFIER ON';
					break;

				case 'sqlite':
					$dsn = $type . ':' . $this->database_file;
					$this->username = null;
					$this->password = null;
					break;
			}

			if (
				in_array($type, explode(' ', 'mariadb mysql pgsql sybase mssql')) &&
				$this->charset
			)
			{
				$commands[] = "SET NAMES '" . $this->charset . "'";
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
	/**
	 * Compile Bindings
	 *     Take From CI 3 Database Query Builder, default string Binding use Question mark ( ? )
	 * @param   string  the sql statement
	 * @param   string|array   an array/string of binding data
	 * @return  string
	 */
	public function compile_bind($sql, $binds = null)
	{
		if (empty($binds) || strpos($sql, '?') === false) {
	    		return $sql;
		} elseif ( ! is_array($binds)) {
	    		$binds = array($binds);
	    		$bind_count = 1;
		} else {
	    		// Make sure we're using numeric keys
	    		$binds = array_values($binds);
	    		$bind_count = count($binds);
		}
	
		// Make sure not to replace a chunk inside a string that happens to match the bind marker
		if ($c = preg_match_all("/'[^']*'/i", $sql, $matches)) {
		    $c = preg_match_all('/\?/i',
		        str_replace($matches[0],
		            str_replace('?', str_repeat(' ', 1), $matches[0]),
		            $sql, $c),
		        $matches, PREG_OFFSET_CAPTURE);
		
		    // Bind values' count must match the count of markers in the query
		    if ($bind_count !== $c) {
		        return $sql;
		    }
		} elseif (($c = preg_match_all('/\?/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count) {
		    return $sql;
		}
	
		do {
		    $c--;
		    $escaped_value = $this->quote($binds[$c]);
		    if (is_array($escaped_value)) {
		        $escaped_value = '('.implode(',', $escaped_value).')';
		    }
		    $sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], 1);
		} while ($c !== 0);
		
		return $sql;
	}
	/**
     	 * Execute Query using PDO::query
     	 *
      	 * @param  string $query the query to run
     	 * @param string|array  virtual binding proccess if query has question mark
     	 * @return object  pdo statement result
     	 */
	public function query($query, $statements = null)
	{
		$query = $this->compile_bind($query, $statements);
		if ($this->debug_mode)
		{
			echo $query;

			$this->debug_mode = false;

			return false;
		}

		array_push($this->logs, $query);

		return $this->pdo->query($query);
	}
	/**
	 * Execute Query using PDO::exec method
	 *
	 * @param  string 	$query the query to run
	 * @param string|array  virtual binding proccess if query has question mark
	 */
	public function exec($query,  $statements = null)
	{
		$query = $this->compile_bind($query, $statements);
		if ($this->debug_mode)
		{
			echo $query;

			$this->debug_mode = false;

			return false;
		}

		array_push($this->logs, $query);

		return $this->pdo->exec($query);
	}
        /**
         * Quote string using PDO::quote function
         *
         * @param  string $string the value to quoted
         * @return string
         */
	public function quote($string)
	{
		return $this->pdo->quote($string);
	}
        /**
         * Quote the column to make sure safe and valid for database execution
         *
         * @param  string $string the column string to quote
         * @return string quoted column
         */
	protected function column_quote($string)
	{
		return '"' . str_replace('.', '"."', preg_replace('/(^#|\(JSON\)\s*)/', '', $string)) . '"';
	}
        /**
         * converting columns into rights
         * structures
         * @param  string $columns
         * @return string
         */
	protected function column_push($columns)
	{
		if ($columns == '*')
		{
			return $columns;
		}

		if (is_string($columns))
		{
			$columns = array($columns);
		}

		$stack = array();

		foreach ($columns as $key => $value)
		{
			preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

			if (isset($match[ 1 ], $match[ 2 ]))
			{
				array_push($stack, $this->column_quote( $match[ 1 ] ) . ' AS ' . $this->column_quote( $match[ 2 ] ));
			}
			else
			{
				array_push($stack, $this->column_quote( $value ));
			}
		}

		return implode($stack, ',');
	}
        /**
         * Deep quotes of array values
         *
         * @param  array  $array as value to quoted
         * @return string
         */
	protected function array_quote($array)
	{
		$temp = array();

		foreach ($array as $value)
		{
			$temp[] = is_int($value) ? $value : $this->pdo->quote($value);
		}

		return implode($temp, ',');
	}
        /**
         * fix conjunction of sql value
         *
         * @return string
         */
	protected function inner_conjunct($data, $conjunctor, $outer_conjunctor)
	{
		$haystack = array();

		foreach ($data as $value)
		{
			$haystack[] = '(' . $this->data_implode($value, $conjunctor) . ')';
		}

		return implode($outer_conjunctor . ' ', $haystack);
	}
	/**
	 * Quoting Function
	 *
	 * @param string $column the column target
	 * @param string $string the string to be quoted
	 */
	protected function fn_quote($column, $string)
	{
		return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ?

			$string :

			$this->quote($string);
	}
	/**
	 * Implode data as where clause parser
	 * @param  array  	$data
	 * @param  string 	$conjunctor
	 * @return string
	 */
	protected function data_implode($data, $conjunctor, $outer_conjunctor = null)
	{
		$wheres = array();

		foreach ($data as $key => $value)
		{
			$type = gettype($value);

			if (
				preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) &&
				$type == 'array'
			)
			{
				$wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
					'(' . $this->data_implode($value, ' ' . $relation_match[ 1 ]) . ')' :
					'(' . $this->inner_conjunct($value, ' ' . $relation_match[ 1 ], $conjunctor) . ')';
			}
			else
			{
				preg_match('/(#?)([\w\.\-]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
				$column = $this->column_quote($match[ 2 ]);

				if (isset($match[ 4 ]))
				{
					$operator = $match[ 4 ];

					if ($operator == '!')
					{
						switch ($type)
						{
							case 'NULL':
								$wheres[] = $column . ' IS NOT NULL';
								break;

							case 'array':
								$wheres[] = $column . ' NOT IN (' . $this->array_quote($value) . ')';
								break;

							case 'integer':
							case 'double':
								$wheres[] = $column . ' != ' . $value;
								break;

							case 'boolean':
								$wheres[] = $column . ' != ' . ($value ? '1' : '0');
								break;

							case 'string':
								$wheres[] = $column . ' != ' . $this->fn_quote($key, $value);
								break;
						}
					}

					if ($operator == '<>' || $operator == '><')
					{
						if ($type == 'array')
						{
							if ($operator == '><')
							{
								$column .= ' NOT';
							}

							if (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ]))
							{
								$wheres[] = '(' . $column . ' BETWEEN ' . $value[ 0 ] . ' AND ' . $value[ 1 ] . ')';
							}
							else
							{
								$wheres[] = '(' . $column . ' BETWEEN ' . $this->quote($value[ 0 ]) . ' AND ' . $this->quote($value[ 1 ]) . ')';
							}
						}
					}

					if ($operator == '~' || $operator == '!~')
					{
						if ($type != 'array')
						{
							$value = array($value);
						}

						$like_clauses = array();

						foreach ($value as $item)
						{
							$item = strval($item);
							$suffix = mb_substr($item, -1, 1);

							if ($suffix === '_')
							{
								$item = substr_replace($item, '%', -1);
							}
							elseif ($suffix === '%')
							{
								$item = '%' . substr_replace($item, '', -1, 1);
							}
							elseif (preg_match('/^(?!%).+(?<!%)$/', $item))
							{
								$item = '%' . $item . '%';
							}

							$like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $this->fn_quote($key, $item);
						}

						$wheres[] = implode(' OR ', $like_clauses);
					}

					if (in_array($operator, array('>', '>=', '<', '<=')))
					{
						if (is_numeric($value))
						{
							$wheres[] = $column . ' ' . $operator . ' ' . $value;
						}
						elseif (strpos($key, '#') === 0)
						{
							$wheres[] = $column . ' ' . $operator . ' ' . $this->fn_quote($key, $value);
						}
						else
						{
							$wheres[] = $column . ' ' . $operator . ' ' . $this->quote($value);
						}
					}
				}
				else
				{
					switch ($type)
					{
						case 'NULL':
							$wheres[] = $column . ' IS NULL';
							break;

						case 'array':
							$wheres[] = $column . ' IN (' . $this->array_quote($value) . ')';
							break;

						case 'integer':
						case 'double':
							$wheres[] = $column . ' = ' . $value;
							break;

						case 'boolean':
							$wheres[] = $column . ' = ' . ($value ? '1' : '0');
							break;

						case 'string':
							$wheres[] = $column . ' = ' . $this->fn_quote($key, $value);
							break;
					}
				}
			}
		}

		return implode($conjunctor . ' ', $wheres);
	}
        /**
         * Build Where Clause for database execution context that used for
         *
         * @param  mixed  $where array|string the context of Where clouse to database execution
         * @return string $where_clause
         */
	protected function where_clause($where)
	{
		$where_clause = '';

		if (is_array($where))
		{
			$where_keys = array_keys($where);
			$where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
			$where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);

			$single_condition = array_diff_key($where, array_flip(
				explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
			));

			if ($single_condition != array())
			{
				$condition = $this->data_implode($single_condition, '');

				if ($condition != '')
				{
					$where_clause = ' WHERE ' . $condition;
				}
			}

			if (!empty($where_AND))
			{
				$value = array_values($where_AND);
				$where_clause = ' WHERE ' . $this->data_implode($where[ $value[ 0 ] ], ' AND');
			}

			if (!empty($where_OR))
			{
				$value = array_values($where_OR);
				$where_clause = ' WHERE ' . $this->data_implode($where[ $value[ 0 ] ], ' OR');
			}

			if (isset($where[ 'MATCH' ]))
			{
				$MATCH = $where[ 'MATCH' ];

				if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ]))
				{
					$where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . ' MATCH ("' . str_replace('.', '"."', implode($MATCH[ 'columns' ], '", "')) . '") AGAINST (' . $this->quote($MATCH[ 'keyword' ]) . ')';
				}
			}

			if (isset($where[ 'GROUP' ]))
			{
				$where_clause .= ' GROUP BY ' . $this->column_quote($where[ 'GROUP' ]);

				if (isset($where[ 'HAVING' ]))
				{
					$where_clause .= ' HAVING ' . $this->data_implode($where[ 'HAVING' ], ' AND');
				}
			}

			if (isset($where[ 'ORDER' ]))
			{
				$rsort = '/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/';
				$ORDER = $where[ 'ORDER' ];

				if (is_array($ORDER))
				{
					if (
						isset($ORDER[ 1 ]) &&
						is_array($ORDER[ 1 ])
					)
					{
						$where_clause .= ' ORDER BY FIELD(' . $this->column_quote($ORDER[ 0 ]) . ', ' . $this->array_quote($ORDER[ 1 ]) . ')';
					}
					else
					{
						$stack = array();

						foreach ($ORDER as $column)
						{
							preg_match($rsort, $column, $order_match);

							array_push($stack, '"' . str_replace('.', '"."', $order_match[ 1 ]) . '"' . (isset($order_match[ 3 ]) ? ' ' . $order_match[ 3 ] : ''));
						}

						$where_clause .= ' ORDER BY ' . implode($stack, ',');
					}
				}
				else
				{
					preg_match($rsort, $ORDER, $order_match);

					$where_clause .= ' ORDER BY "' . str_replace('.', '"."', $order_match[ 1 ]) . '"' . (isset($order_match[ 3 ]) ? ' ' . $order_match[ 3 ] : '');
				}
			}

			if (isset($where[ 'LIMIT' ]))
			{
				$LIMIT = $where[ 'LIMIT' ];

				if (is_numeric($LIMIT))
				{
					$where_clause .= ' LIMIT ' . $LIMIT;
				}

				if (
					is_array($LIMIT) &&
					is_numeric($LIMIT[ 0 ]) &&
					is_numeric($LIMIT[ 1 ])
				)
				{
					if ($this->database_type === 'pgsql')
					{
						$where_clause .= ' OFFSET ' . $LIMIT[ 0 ] . ' LIMIT ' . $LIMIT[ 1 ];
					}
					else
					{
						$where_clause .= ' LIMIT ' . $LIMIT[ 0 ] . ',' . $LIMIT[ 1 ];
					}
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
        /**
         * Build select context to database execution select used for select database operation
         * as select proccessed with right rule
         *
         * @param  string             $table     table name
         * @param  array              $join      Table relativity for table joining.
         *                                       Ignore it if no table joining required. ( optional )
         * @param  mixed string|array $columns   The target columns of data will be fetched.
         * @param  array              $where     The WHERE clause to filter records. (optional)
         * @param  string             $column_fn the column function
         * @return string             context
         */
	protected function select_context($table, $join, &$columns = null, $where = null, $column_fn = null)
	{
		$table = '"' . $this->prefix . $table . '"';
		$join_key = is_array($join) ? array_keys($join) : null;

		if (
			isset($join_key[ 0 ]) &&
			strpos($join_key[ 0 ], '[') === 0
		)
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
				preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $sub_table, $match);

				if ($match[ 2 ] != '' && $match[ 3 ] != '')
				{
					if (is_string($relation))
					{
						$relation = 'USING ("' . $relation . '")';
					}

					if (is_array($relation))
					{
						// For ['column1', 'column2']
						if (isset($relation[ 0 ]))
						{
							$relation = 'USING ("' . implode($relation, '", "') . '")';
						}
						else
						{
							$joins = array();

							foreach ($relation as $key => $value)
							{
								$joins[] = $this->prefix . (
									strpos($key, '.') > 0 ?
										// For ['tableB.column' => 'column']
										'"' . str_replace('.', '"."', $key) . '"' :

										// For ['column1' => 'column2']
										$table . '."' . $key . '"'
								) .
								' = ' .
								'"' . (isset($match[ 5 ]) ? $match[ 5 ] : $match[ 3 ]) . '"."' . $value . '"';
							}

							$relation = 'ON ' . implode($joins, ' AND ');
						}
					}

					$table_join[] = $join_array[ $match[ 2 ] ] . ' JOIN "' . $this->prefix . $match[ 3 ] . '" ' . (isset($match[ 5 ]) ?  'AS "' . $match[ 5 ] . '" ' : '') . $relation;
				}
			}

			$table .= ' ' . implode($table_join, ' ');
		}
		else
		{
			if (is_null($columns))
			{
				if (is_null($where))
				{
					if (
						is_array($join) &&
						isset($column_fn)
					)
					{
						$where = $join;
						$columns = null;
					}
					else
					{
						$where = null;
						$columns = $join;
					}
				}
				else
				{
					$where = $join;
					$columns = null;
				}
			}
			else
			{
				$where = $columns;
				$columns = $join;
			}
		}

		if (isset($column_fn))
		{
			if ($column_fn == 1)
			{
				$column = '1';

				if (is_null($where))
				{
					$where = $columns;
				}
			}
			else
			{
				if (empty($columns))
				{
					$columns = '*';
					$where = $join;
				}

				$column = $column_fn . '(' . $this->column_push($columns) . ')';
			}
		}
		else
		{
			$column = $this->column_push($columns);
		}

		return 'SELECT ' . $column . ' FROM ' . $table . $this->where_clause($where);
	}

        /**
         * As select operation for database execution and get data
         *
         * @param  string             $table   table name
         * @param  array              $join    Table relativity for table joining.
         *                                     Ignore it if no table joining required. ( optional )
         * @param  mixed string|array $columns The target columns of data will be fetched.
         * @param  array              $where   The WHERE clause to filter records. (optional)
         * @param  integer            $param   the PDO constant attribute fetch
         * @return array
         */
	public function select($table, $join, $columns = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $columns, $where));

		return $query ? $query->fetchAll(
			(is_string($columns) && $columns != '*') ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC
		) : false;
	}
        /**
         * Insert data to database as operation
         *
         * @param  string $table  the database table
         * @param  array  $datas  the data to be exexute
         * @return integer        the last insert id
         */
	public function insert($table, $datas)
	{
		$lastId = array();

		// Check indexed or associative array
		if (!isset($datas[ 0 ]))
		{
			$datas = array($datas);
		}

		foreach ($datas as $data)
		{
			$values = array();
			$columns = array();

			foreach ($data as $key => $value)
			{
				array_push($columns, $this->column_quote($key));

				switch (gettype($value))
				{
					case 'NULL':
						$values[] = 'NULL';
						break;

					case 'array':
						preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

						$values[] = isset($column_match[ 0 ]) ?
							$this->quote(json_encode($value)) :
							$this->quote(serialize($value));
						break;

					case 'boolean':
						$values[] = ($value ? '1' : '0');
						break;

					case 'integer':
					case 'double':
					case 'string':
						$values[] = $this->fn_quote($key, $value);
						break;
				}
			}

			$this->exec('INSERT INTO "' . $this->prefix . $table . '" (' . implode(', ', $columns) . ') VALUES (' . implode($values, ', ') . ')');

			$lastId[] = $this->pdo->lastInsertId();
		}

		return count($lastId) > 1 ? $lastId : $lastId[ 0 ];
	}
        /**
         * Update data on database with new determined value
         *
         * @param  string             $table the database table
         * @param  array              $data  the data to be execute
         * @param  mixed array|string $where the where clause
         * @return integer            count the last affected updated row
         */
	public function update($table, $data, $where = null)
	{
		$fields = array();

		foreach ($data as $key => $value)
		{
			preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);

			if (isset($match[ 3 ]))
			{
				if (is_numeric($value))
				{
					$fields[] = $this->column_quote($match[ 1 ]) . ' = ' . $this->column_quote($match[ 1 ]) . ' ' . $match[ 3 ] . ' ' . $value;
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
						preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

						$fields[] = $column . ' = ' . $this->quote(
								isset($column_match[ 0 ]) ? json_encode($value) : serialize($value)
							);
						break;

					case 'boolean':
						$fields[] = $column . ' = ' . ($value ? '1' : '0');
						break;

					case 'integer':
					case 'double':
					case 'string':
						$fields[] = $column . ' = ' . $this->fn_quote($key, $value);
						break;
				}
			}
		}

		return $this->exec('UPDATE "' . $this->prefix . $table . '" SET ' . implode(', ', $fields) . $this->where_clause($where));
	}
        /**
         * Deleting data on database
         *
         * @param  string             $table the table name
         * @param  mixed array|string $where the where clause
         * @return integer            count the last affected deleted rows
         */
	public function delete($table, $where)
	{
		return $this->exec('DELETE FROM "' . $this->prefix . $table . '"' . $this->where_clause($where));
	}
        /**
         * Replacing data on database
         *
         * @param  string             $table   the table name
         * @param  string             $search  the contains value to search
         * @param  string             $replace the replace query
         * @param  mixed array|string $where   the where clause
         * @return integer            count the last affected replaced rows
         */
	public function replace($table, $columns, $search = null, $replace = null, $where = null)
	{
		if (is_array($columns))
		{
			$replace_query = array();

			foreach ($columns as $column => $replacements)
			{
				foreach ($replacements as $replace_search => $replace_replacement)
				{
					$replace_query[] = $column . ' = REPLACE(' . $this->column_quote($column) . ', ' . $this->quote($replace_search) . ', ' . $this->quote($replace_replacement) . ')';
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
					$replace_query[] = $columns . ' = REPLACE(' . $this->column_quote($columns) . ', ' . $this->quote($replace_search) . ', ' . $this->quote($replace_replacement) . ')';
				}

				$replace_query = implode(', ', $replace_query);
				$where = $replace;
			}
			else
			{
				$replace_query = $columns . ' = REPLACE(' . $this->column_quote($columns) . ', ' . $this->quote($search) . ', ' . $this->quote($replace) . ')';
			}
		}

		return $this->exec('UPDATE "' . $this->prefix . $table . '" SET ' . $replace_query . $this->where_clause($where));
	}
        /**
         * As get and select operation for database execution and get data
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return array
         */
	public function get($table, $join = null, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where) . ' LIMIT 1');

		if ($query)
		{
			$data = $query->fetchAll(PDO::FETCH_ASSOC);

			if (isset($data[ 0 ]))
			{
				$column = $where == null ? $join : $column;

				if (is_string($column) && $column != '*')
				{
					return $data[ 0 ][ $column ];
				}

				return $data[ 0 ];
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
        /**
         * As select operation for database execution and get data if data value is exist
         *
         * @param string $table table name
         * @param array  $join  Table relativity for table joining.
         *                      Ignore it if no table joining required. ( optional )
         * @param array  $where The WHERE clause to filter records. (optional)
         *
         * @return boolean true if exists
         */
	public function has($table, $join, $where = null)
	{
		$column = null;

		$query = $this->query('SELECT EXISTS(' . $this->select_context($table, $join, $column, $where, 1) . ')');

		if ($query)
		{
			return $query->fetchColumn() === '1';
		}
		else
		{
			return false;
		}
	}
        /**
         * Getting Count from Query execution request
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return integer
         */
	public function count($table, $join = null, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where, 'COUNT'));

		return $query ? 0 + $query->fetchColumn() : false;
	}
        /**
         * Getting Max from Query execution request
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return integer
         */
	public function max($table, $join, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where, 'MAX'));

		if ($query)
		{
			$max = $query->fetchColumn();

			return is_numeric($max) ? $max + 0 : $max;
		}
		else
		{
			return false;
		}
	}
        /**
         * Getting Min from Query execution request
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return integer
         */
	public function min($table, $join, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where, 'MIN'));

		if ($query)
		{
			$min = $query->fetchColumn();

			return is_numeric($min) ? $min + 0 : $min;
		}
		else
		{
			return false;
		}
	}
        /**
         * Getting Average from Query execution request
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return integer
         */
	public function avg($table, $join, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where, 'AVG'));

		return $query ? 0 + $query->fetchColumn() : false;
	}
        /**
         * Getting Sum from Query execution request
         *
         * @param string             $table   table name
         * @param array              $join    Table relativity for table joining.
         *                                    Ignore it if no table joining required. ( optional )
         * @param mixed string|array $columns The target columns of data will be fetched.
         * @param array              $where   The WHERE clause to filter records. (optional)
         *
         * @return integer
         */
	public function sum($table, $join, $column = null, $where = null)
	{
		$query = $this->query($this->select_context($table, $join, $column, $where, 'SUM'));

		return $query ? 0 + $query->fetchColumn() : false;
	}
        /**
         * Begin transaction action
         *
         * @param  callable $actions the callable function
         */
	public function action($actions)
	{
		if (is_callable($actions))
		{
			$this->pdo->beginTransaction();

			$result = $actions($this);

			if ($result === false)
			{
				$this->pdo->rollBack();
			}
			else
			{
				$this->pdo->commit();
			}
		}
		else
		{
			return false;
		}
	}
	/**
	 * Call direct as Debug mode
	 *
	 * @return object current called class ($this)
	 */
	public function debug()
	{
		$this->debug_mode = true;

		return $this;
	}
	/**
	 * Get PDO connection error information
	 *
	 * @return array
	 */
	public function error()
	{
		return $this->pdo->errorInfo();
	}
	/**
	 * Getting last query from logs
	 *
	 * @return string
	 */
	public function last_query()
	{
		return end($this->logs);
	}
	/**
	 * Get all recorded logs
	 *
	 * @return array
	 */
	public function log()
	{
		return $this->logs;
	}
	/**
	 * Show Current connection Information
	 * 
	 * @return array
	 */
	public function info()
	{
		$output = array(
			'server' => 'SERVER_INFO',
			'driver' => 'DRIVER_NAME',
			'client' => 'CLIENT_VERSION',
			'version' => 'SERVER_VERSION',
			'connection' => 'CONNECTION_STATUS'
		);

		foreach ($output as $key => $value)
		{
			$output[ $key ] = $this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
		}

		return $output;
	}
}
?>
