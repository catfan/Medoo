<?php
/*!
 * Medoo database framework
 * https://medoo.in
 * Version 1.4.4
 *
 * Copyright 2017, Angel Lai
 * Released under the MIT license
 */

namespace Medoo;

use PDO;
use Exception;
use PDOException;

class Medoo
{
	protected $database_type;

	protected $prefix;

	protected $statement;

	protected $option = [];

	protected $logs = [];

	protected $logging = false;

	protected $debug_mode = false;

	protected $guid = 0;

	public function __construct($options = null)
	{
		try {
			if (is_array($options))
			{
				if (isset($options[ 'database_type' ]))
				{
					$this->database_type = strtolower($options[ 'database_type' ]);
				}
			}
			else
			{
				return false;
			}

			if (isset($options[ 'prefix' ]))
			{
				$this->prefix = $options[ 'prefix' ];
			}

			if (isset($options[ 'option' ]))
			{
				$this->option = $options[ 'option' ];
			}

			if (isset($options[ 'logging' ]) && is_bool($options[ 'logging' ]))
			{
				$this->logging = $options[ 'logging' ];
			}

			if (isset($options[ 'command' ]) && is_array($options[ 'command' ]))
			{
				$commands = $options[ 'command' ];
			}
			else
			{
				$commands = [];
			}

			if (isset($options[ 'dsn' ]))
			{
				if (isset($options[ 'dsn' ][ 'driver' ]))
				{
					$attr = $options[ 'dsn' ];
				}
				else
				{
					return false;
				}
			}
			else
			{
				if (
					isset($options[ 'port' ]) &&
					is_int($options[ 'port' ] * 1)
				)
				{
					$port = $options[ 'port' ];
				}

				$is_port = isset($port);

				switch ($this->database_type)
				{
					case 'mariadb':
					case 'mysql':
						$attr = [
							'driver' => 'mysql',
							'dbname' => $options[ 'database_name' ]
						];

						if (isset($options[ 'socket' ]))
						{
							$attr[ 'unix_socket' ] = $options[ 'socket' ];
						}
						else
						{
							$attr[ 'host' ] = $options[ 'server' ];

							if ($is_port)
							{
								$attr[ 'port' ] = $port;
							}
						}

						// Make MySQL using standard quoted identifier
						$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
						break;

					case 'pgsql':
						$attr = [
							'driver' => 'pgsql',
							'host' => $options[ 'server' ],
							'dbname' => $options[ 'database_name' ]
						];

						if ($is_port)
						{
							$attr[ 'port' ] = $port;
						}

						break;

					case 'sybase':
						$attr = [
							'driver' => 'dblib',
							'host' => $options[ 'server' ],
							'dbname' => $options[ 'database_name' ]
						];

						if ($is_port)
						{
							$attr[ 'port' ] = $port;
						}

						break;

					case 'oracle':
						$attr = [
							'driver' => 'oci',
							'dbname' => $options[ 'server' ] ?
								'//' . $options[ 'server' ] . ($is_port ? ':' . $port : ':1521') . '/' . $options[ 'database_name' ] :
								$options[ 'database_name' ]
						];

						if (isset($options[ 'charset' ]))
						{
							$attr[ 'charset' ] = $options[ 'charset' ];
						}

						break;

					case 'mssql':
						if (strstr(PHP_OS, 'WIN'))
						{
							$attr = [
								'driver' => 'sqlsrv',
								'Server' => $options[ 'server' ] . ($is_port ? ',' . $port : ''),
								'Database' => $options[ 'database_name' ]
							];
						}
						else
						{
							$attr = [
								'driver' => 'dblib',
								'host' => $options[ 'server' ] . ($is_port ? ':' . $port : ''),
								'dbname' => $options[ 'database_name' ]
							];
						}

						// Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
						$commands[] = 'SET QUOTED_IDENTIFIER ON';

						// Make ANSI_NULLS is ON for NULL value
						$commands[] = 'SET ANSI_NULLS ON';
						break;

					case 'sqlite':
						$this->pdo = new PDO('sqlite:' . $options[ 'database_file' ], null, null, $this->option);

						return;
				}
			}

			$driver = $attr[ 'driver' ];

			unset($attr[ 'driver' ]);

			$stack = [];

			foreach ($attr as $key => $value)
			{
				if (is_int($key))
				{
					$stack[] = $value;
				}
				else
				{
					$stack[] = $key . '=' . $value;
				}
			}

			$dsn = $driver . ':' . implode($stack, ';');

			if (
				in_array($this->database_type, ['mariadb', 'mysql', 'pgsql', 'sybase', 'mssql']) &&
				isset($options[ 'charset' ])
			)
			{
				$commands[] = "SET NAMES '" . $options[ 'charset' ] . "'";
			}

			$this->pdo = new PDO(
				$dsn,
				$options[ 'username' ],
				$options[ 'password' ],
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

	public function query($query, $map = [])
	{
		if (!empty($map))
		{
			foreach ($map as $key => $value)
			{
				switch (gettype($value))
				{
					case 'NULL':
						$map[ $key ] = [null, PDO::PARAM_NULL];
						break;

					case 'resource':
						$map[ $key ] = [$value, PDO::PARAM_LOB];
						break;

					case 'boolean':
						$map[ $key ] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
						break;

					case 'integer':
					case 'double':
						$map[ $key ] = [$value, PDO::PARAM_INT];
						break;

					case 'string':
						$map[ $key ] = [$value, PDO::PARAM_STR];
						break;
				}
			}
		}

		return $this->exec($query, $map);
	}

	public function exec($query, $map = [])
	{
		if ($this->debug_mode)
		{
			echo $this->generate($query, $map);

			$this->debug_mode = false;

			return false;
		}

		if ($this->logging)
		{
			$this->logs[] = [$query, $map];
		}
		else
		{
			$this->logs = [[$query, $map]];
		}

		$statement = $this->pdo->prepare($query);

		foreach ($map as $key => $value)
		{
			$statement->bindValue($key, $value[ 0 ], $value[ 1 ]);
		}

		$statement->execute();

		$this->statement = $statement;

		return $statement;
	}

	protected function generate($query, $map)
	{
		foreach ($map as $key => $value)
		{
			if ($value[ 1 ] === PDO::PARAM_STR)
			{
				$query = str_replace($key, $this->quote($value[ 0 ]), $query);
			}
			elseif ($value[ 1 ] === PDO::PARAM_NULL)
			{
				$query = str_replace($key, 'NULL', $query);
			}
			else
			{
				$query = str_replace($key, $value[ 0 ], $query);
			}
		}

		return $query;
	}

	public function quote($string)
	{
		return $this->pdo->quote($string);
	}

	protected function tableQuote($table)
	{
		return '"' . $this->prefix . $table . '"';
	}

	protected function mapKey()
	{
		return ':MeDoOmEdOo_' . $this->guid++;
	}

	protected function columnQuote($string)
	{
		preg_match('/(^#)?([a-zA-Z0-9_]*)\.([a-zA-Z0-9_]*)(\s*\[JSON\]$)?/', $string, $column_match);

		if (isset($column_match[ 2 ], $column_match[ 3 ]))
		{
			return '"' . $this->prefix . $column_match[ 2 ] . '"."' . $column_match[ 3 ] . '"';
		}

		return '"' . $string . '"';
	}

	protected function columnPush(&$columns)
	{
		if ($columns === '*')
		{
			return $columns;
		}

		$stack = [];

		if (is_string($columns))
		{
			$columns = [$columns];
		}

		foreach ($columns as $key => $value)
		{
			if (is_array($value))
			{
				$stack[] = $this->columnPush($value);
			}
			else
			{
				preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\)|\s*\[(?<type>(String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);

				if (!empty($match[ 'alias' ]))
				{
					$stack[] = $this->columnQuote( $match[ 'column' ] ) . ' AS ' . $this->columnQuote( $match[ 'alias' ] );

					$columns[ $key ] = $match[ 'alias' ];
				}
				else
				{
					$stack[] = $this->columnQuote( $match[ 'column' ] );
				}
			}
		}

		return implode($stack, ',');
	}

	protected function arrayQuote($array)
	{
		$stack = [];

		foreach ($array as $value)
		{
			$stack[] = is_int($value) ? $value : $this->pdo->quote($value);
		}

		return implode($stack, ',');
	}

	protected function innerConjunct($data, $map, $conjunctor, $outer_conjunctor)
	{
		$stack = [];

		foreach ($data as $value)
		{
			$stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
		}

		return implode($outer_conjunctor . ' ', $stack);
	}

	protected function fnQuote($column, $string)
	{
		return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ?

			$string :

			$this->quote($string);
	}

	protected function dataImplode($data, &$map, $conjunctor)
	{
		$wheres = [];

		foreach ($data as $key => $value)
		{
			$map_key = $this->mapKey();

			$type = gettype($value);

			if (
				preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) &&
				$type === 'array'
			)
			{
				$wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
					'(' . $this->dataImplode($value, $map, ' ' . $relation_match[ 1 ]) . ')' :
					'(' . $this->innerConjunct($value, $map, ' ' . $relation_match[ 1 ], $conjunctor) . ')';
			}
			else
			{
				if (
					is_int($key) &&
					preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>|\>\=|\<|\<\=|\!|\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)
				)
				{
					$wheres[] = $this->columnQuote($match[ 1 ]) . ' ' . $match[ 'operator' ] . ' ' . $this->columnQuote($match[ 3 ]);
				}
				else
				{
					preg_match('/(#?)([a-zA-Z0-9_\.]+)(\[(?<operator>\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
					$column = $this->columnQuote($match[ 2 ]);

					if (!empty($match[ 1 ]))
					{
						$wheres[] = $column .
							(isset($match[ 'operator' ]) ? ' ' . $match[ 'operator' ] . ' ' : ' = ') .
							$this->fnQuote($key, $value);

						continue;
					}

					if (isset($match[ 'operator' ]))
					{
						$operator = $match[ 'operator' ];

						if ($operator === '!')
						{
							switch ($type)
							{
								case 'NULL':
									$wheres[] = $column . ' IS NOT NULL';
									break;

								case 'array':
									$wheres[] = $column . ' NOT IN (' . $this->arrayQuote($value) . ')';
									break;

								case 'integer':
								case 'double':
									$wheres[] = $column . ' != ' . $map_key;
									$map[ $map_key ] = [$value, PDO::PARAM_INT];
									break;

								case 'boolean':
									$wheres[] = $column . ' != ' . $map_key;
									$map[ $map_key ] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
									break;

								case 'string':
									$wheres[] = $column . ' != ' . $map_key;
									$map[ $map_key ] = [$value, PDO::PARAM_STR];
									break;
							}
						}

						if ($operator === '<>' || $operator === '><')
						{
							if ($type === 'array')
							{
								if ($operator === '><')
								{
									$column .= ' NOT';
								}

								$wheres[] = '(' . $column . ' BETWEEN ' . $map_key . 'a AND ' . $map_key . 'b)';

								$data_type = (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ])) ? PDO::PARAM_INT : PDO::PARAM_STR;

								$map[ $map_key . 'a' ] = [$value[ 0 ], $data_type];
								$map[ $map_key . 'b' ] = [$value[ 1 ], $data_type];
							}
						}

						if ($operator === '~' || $operator === '!~')
						{
							if ($type !== 'array')
							{
								$value = [ $value ];
							}

							$connector = ' OR ';
							$stack = array_values($value);

							if (is_array($stack[ 0 ]))
							{
								if (isset($value[ 'AND' ]) || isset($value[ 'OR' ]))
								{
									$connector = ' ' . array_keys($value)[ 0 ] . ' ';
									$value = $stack[ 0 ];
								}
							}

							$like_clauses = [];

							foreach ($value as $index => $item)
							{
								$map_key .= 'L' . $index;

								$item = strval($item);

								if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item))
								{
									$item = '%' . $item . '%';
								}

								$like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $map_key;
								$map[ $map_key ] = [$item, PDO::PARAM_STR];
							}

							$wheres[] = '(' . implode($connector, $like_clauses) . ')';
						}

						if (in_array($operator, ['>', '>=', '<', '<=']))
						{
							$condition = $column . ' ' . $operator . ' ';

							if (is_numeric($value))
							{
								$condition .= $map_key;
								$map[ $map_key ] = [$value, PDO::PARAM_INT];
							}
							else
							{
								$condition .= $map_key;
								$map[ $map_key ] = [$value, PDO::PARAM_STR];
							}

							$wheres[] = $condition;
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
								$wheres[] = $column . ' IN (' . $this->arrayQuote($value) . ')';
								break;

							case 'integer':
							case 'double':
								$wheres[] = $column . ' = ' . $map_key;
								$map[ $map_key ] = [$value, PDO::PARAM_INT];
								break;

							case 'boolean':
								$wheres[] = $column . ' = ' . $map_key;
								$map[ $map_key ] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
								break;

							case 'string':
								$wheres[] = $column . ' = ' . $map_key;
								$map[ $map_key ] = [$value, PDO::PARAM_STR];
								break;
						}
					}
				}
			}
		}

		return implode($conjunctor . ' ', $wheres);
	}

	protected function whereClause($where, &$map)
	{
		$where_clause = '';

		if (is_array($where))
		{
			$where_keys = array_keys($where);
			$where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
			$where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);

			$single_condition = array_diff_key($where, array_flip(
				['AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
			));

			if (!empty($single_condition))
			{
				$condition = $this->dataImplode($single_condition, $map, ' AND');

				if ($condition !== '')
				{
					$where_clause = ' WHERE ' . $condition;
				}
			}

			if (!empty($where_AND))
			{
				$value = array_values($where_AND);
				$where_clause = ' WHERE ' . $this->dataImplode($where[ $value[ 0 ] ], $map, ' AND');
			}

			if (!empty($where_OR))
			{
				$value = array_values($where_OR);
				$where_clause = ' WHERE ' . $this->dataImplode($where[ $value[ 0 ] ], $map, ' OR');
			}

			if (isset($where[ 'MATCH' ]))
			{
				$MATCH = $where[ 'MATCH' ];

				if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ]))
				{
					$mode = '';

					$mode_array = [
						'natural' => 'IN NATURAL LANGUAGE MODE',
						'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
						'boolean' => 'IN BOOLEAN MODE',
						'query' => 'WITH QUERY EXPANSION'
					];

					if (isset($MATCH[ 'mode' ], $mode_array[ $MATCH[ 'mode' ] ]))
					{
						$mode = ' ' . $mode_array[ $MATCH[ 'mode' ] ];
					}

					$columns = implode(array_map([$this, 'columnQuote'], $MATCH[ 'columns' ]), ', ');
					$map_key = $this->mapKey();
					$map[ $map_key ] = [$MATCH[ 'keyword' ], PDO::PARAM_STR];

					$where_clause .= ($where_clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $map_key . $mode . ')';
				}
			}

			if (isset($where[ 'GROUP' ]))
			{
				$GROUP = $where[ 'GROUP' ];

				if (is_array($GROUP))
				{
					$stack = [];

					foreach ($GROUP as $column => $value)
					{
						$stack[] = $this->columnQuote($value);
					}

					$where_clause .= ' GROUP BY ' . implode($stack, ',');
				}
				else
				{
					$where_clause .= ' GROUP BY ' . $this->columnQuote($where[ 'GROUP' ]);
				}

				if (isset($where[ 'HAVING' ]))
				{
					$where_clause .= ' HAVING ' . $this->dataImplode($where[ 'HAVING' ], $map, ' AND');
				}
			}

			if (isset($where[ 'ORDER' ]))
			{
				$ORDER = $where[ 'ORDER' ];

				if (is_array($ORDER))
				{
					$stack = [];

					foreach ($ORDER as $column => $value)
					{
						if (is_array($value))
						{
							$stack[] = 'FIELD(' . $this->columnQuote($column) . ', ' . $this->arrayQuote($value) . ')';
						}
						else if ($value === 'ASC' || $value === 'DESC')
						{
							$stack[] = $this->columnQuote($column) . ' ' . $value;
						}
						else if (is_int($column))
						{
							$stack[] = $this->columnQuote($value);
						}
					}

					$where_clause .= ' ORDER BY ' . implode($stack, ',');
				}
				else
				{
					$where_clause .= ' ORDER BY ' . $this->columnQuote($ORDER);
				}

				if (
					isset($where[ 'LIMIT' ]) &&
					in_array($this->database_type, ['oracle', 'mssql'])
				)
				{
					$LIMIT = $where[ 'LIMIT' ];

					if (is_numeric($LIMIT))
					{
						$where_clause .= ' FETCH FIRST ' . $LIMIT . ' ROWS ONLY';
					}

					if (
						is_array($LIMIT) &&
						is_numeric($LIMIT[ 0 ]) &&
						is_numeric($LIMIT[ 1 ])
					)
					{
						$where_clause .= ' OFFSET ' . $LIMIT[ 0 ] . ' ROWS FETCH NEXT ' . $LIMIT[ 1 ] . ' ROWS ONLY';
					}
				}
			}

			if (isset($where[ 'LIMIT' ]) && !in_array($this->database_type, ['oracle', 'mssql']))
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
					$where_clause .= ' LIMIT ' . $LIMIT[ 1 ] . ' OFFSET ' . $LIMIT[ 0 ];
				}
			}
		}
		else
		{
			if ($where !== null)
			{
				$where_clause .= ' ' . $where;
			}
		}

		return $where_clause;
	}

	protected function selectContext($table, &$map, $join, &$columns = null, $where = null, $column_fn = null)
	{
		preg_match('/(?<table>[a-zA-Z0-9_]+)\s*\((?<alias>[a-zA-Z0-9_]+)\)/i', $table, $table_match);

		if (isset($table_match[ 'table' ], $table_match[ 'alias' ]))
		{
			$table = $this->tableQuote($table_match[ 'table' ]);

			$table_query = $table . ' AS ' . $this->tableQuote($table_match[ 'alias' ]);
		}
		else
		{
			$table = $this->tableQuote($table);

			$table_query = $table;
		}

		$join_key = is_array($join) ? array_keys($join) : null;

		if (
			isset($join_key[ 0 ]) &&
			strpos($join_key[ 0 ], '[') === 0
		)
		{
			$table_join = [];

			$join_array = [
				'>' => 'LEFT',
				'<' => 'RIGHT',
				'<>' => 'FULL',
				'><' => 'INNER'
			];

			foreach($join as $sub_table => $relation)
			{
				preg_match('/(\[(?<join>\<|\>|\>\<|\<\>)\])?(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $sub_table, $match);

				if ($match[ 'join' ] !== '' && $match[ 'table' ] !== '')
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
							$joins = [];

							foreach ($relation as $key => $value)
							{
								$joins[] = (
									strpos($key, '.') > 0 ?
										// For ['tableB.column' => 'column']
										$this->columnQuote($key) :

										// For ['column1' => 'column2']
										$table . '."' . $key . '"'
								) .
								' = ' .
								$this->tableQuote(isset($match[ 'alias' ]) ? $match[ 'alias' ] : $match[ 'table' ]) . '."' . $value . '"';
							}

							$relation = 'ON ' . implode($joins, ' AND ');
						}
					}

					$table_name = $this->tableQuote($match[ 'table' ]) . ' ';

					if (isset($match[ 'alias' ]))
					{
						$table_name .= 'AS ' . $this->tableQuote($match[ 'alias' ]) . ' ';
					}

					$table_join[] = $join_array[ $match[ 'join' ] ] . ' JOIN ' . $table_name . $relation;
				}
			}

			$table_query .= ' ' . implode($table_join, ' ');
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
			if ($column_fn === 1)
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

				$column = $column_fn . '(' . $this->columnPush($columns) . ')';
			}
		}
		else
		{
			$column = $this->columnPush($columns);
		}

		return 'SELECT ' . $column . ' FROM ' . $table_query . $this->whereClause($where, $map);
	}

	protected function columnMap($columns, &$stack)
	{
		if ($columns === '*')
		{
			return $stack;
		}

		foreach ($columns as $key => $value)
		{
			if (is_int($key))
			{
				preg_match('/(?<column>[a-zA-Z0-9_\.]*)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\)|\s*\[(?<type>(String|Bool|Int|Number|Object|JSON))\])?/i', $value, $key_match);

				$column_key = !empty($key_match[ 'alias' ]) ?
					$key_match[ 'alias' ] :
					preg_replace('/^[\w]*\./i', '', $key_match[ 'column' ]);

				if (isset($key_match[ 'type' ]))
				{
					$stack[ $value ] = [$column_key, $key_match[ 'type' ]];
				}
				else
				{
					$stack[ $value ] = [$column_key, 'String'];
				}
			}
			else
			{
				$this->columnMap($value, $stack);
			}
		}

		return $stack;
	}

	protected function dataMap($data, $columns, $column_map, &$stack)
	{
		foreach ($columns as $key => $value)
		{
			if (is_int($key))
			{
				$map = $column_map[ $value ];
				$column_key = $map[ 0 ];

				if (isset($map[ 1 ]))
				{
					switch ($map[ 1 ])
					{
						case 'Number':
						case 'Int':
							$stack[ $column_key ] = (int) $data[ $column_key ];
							break;

						case 'Bool':
							$stack[ $column_key ] = (bool) $data[ $column_key ];
							break;

						case 'Object':
							$stack[ $column_key ] = unserialize($data[ $column_key ]);
							break;

						case 'JSON':
							$stack[ $column_key ] = json_decode($data[ $column_key ], true);
							break;

						case 'String':
							$stack[ $column_key ] = $data[ $column_key ];
							break;
					}
				}
				else
				{
					$stack[ $column_key ] = $data[ $column_key ];
				}
			}
			else
			{
				$current_stack = [];

				$this->dataMap($data, $value, $column_map, $current_stack);

				$stack[ $key ] = $current_stack;
			}
		}
	}

	public function select($table, $join, $columns = null, $where = null)
	{
		$map = [];
		$stack = [];
		$column_map = [];

		$index = 0;

		$column = $where === null ? $join : $columns;

		$is_single_column = (is_string($column) && $column !== '*');

		$query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

		$this->columnMap($columns, $column_map);

		if (!$query)
		{
			return false;
		}

		if ($columns === '*')
		{
			return $query->fetchAll(PDO::FETCH_ASSOC);
		}

		if ($is_single_column)
		{
			return $query->fetchAll(PDO::FETCH_COLUMN);
		}

		while ($data = $query->fetch(PDO::FETCH_ASSOC))
		{
			$current_stack = [];

			$this->dataMap($data, $columns, $column_map, $current_stack);

			$stack[ $index ] = $current_stack;

			$index++;
		}

		return $stack;
	}

	public function insert($table, $datas)
	{
		$stack = [];
		$columns = [];
		$fields = [];
		$map = [];

		if (!isset($datas[ 0 ]))
		{
			$datas = [$datas];
		}

		foreach ($datas as $data)
		{
			foreach ($data as $key => $value)
			{
				$columns[] = $key;
			}
		}

		$columns = array_unique($columns);

		foreach ($datas as $data)
		{
			$values = [];

			foreach ($columns as $key)
			{
				if (strpos($key, '#') === 0)
				{
					$values[] = $this->fnQuote($key, $data[ $key ]);	
					continue;
				}

				$map_key =$this->mapKey();

				$values[] = $map_key;

				if (!isset($data[ $key ]))
				{
					$map[ $map_key ] = [null, PDO::PARAM_NULL];
				}
				else
				{
					$value = $data[ $key ];

					switch (gettype($value))
					{
						case 'NULL':
							$map[ $map_key ] = [null, PDO::PARAM_NULL];
							break;

						case 'array':
							$map[ $map_key ] = [
								strpos($key, '[JSON]') === strlen($key) - 6 ?
									json_encode($value) :
									serialize($value),
								PDO::PARAM_STR
							];
							break;

						case 'object':
							$map[ $map_key ] = [serialize($value), PDO::PARAM_STR];
							break;

						case 'resource':
							$map[ $map_key ] = [$value, PDO::PARAM_LOB];
							break;

						case 'boolean':
							$map[ $map_key ] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
							break;

						case 'integer':
						case 'double':
							$map[ $map_key ] = [$value, PDO::PARAM_INT];
							break;

						case 'string':
							$map[ $map_key ] = [$value, PDO::PARAM_STR];
							break;
					}
				}
			}

			$stack[] = '(' . implode($values, ', ') . ')';
		}

		foreach ($columns as $key)
		{
			$fields[] = $this->columnQuote(preg_replace("/(^#|\s*\[JSON\]$)/i", '', $key));
		}

		return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
	}

	public function update($table, $data, $where = null)
	{
		$fields = [];
		$map = [];

		foreach ($data as $key => $value)
		{
			$column = $this->columnQuote(preg_replace("/(^#|\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key));

			if (strpos($key, '#') === 0)
			{
				$fields[] = $column . ' = ' . $value;
				continue;
			}

			$map_key = $this->mapKey();

			preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);

			if (isset($match[ 'operator' ]))
			{
				if (is_numeric($value))
				{
					$fields[] = $column . ' = ' . $column . ' ' . $match[ 'operator' ] . ' ' . $value;
				}
			}
			else
			{
				$fields[] = $column . ' = ' . $map_key;

				switch (gettype($value))
				{
					case 'NULL':
						$map[ $map_key ] = [null, PDO::PARAM_NULL];
						break;

					case 'array':
						$map[ $map_key ] = [
							strpos($key, '[JSON]') === strlen($key) - 6 ?
								json_encode($value) :
								serialize($value),
							PDO::PARAM_STR
						];
						break;

					case 'object':
						$map[ $map_key ] = [serialize($value), PDO::PARAM_STR];
						break;

					case 'resource':
						$map[ $map_key ] = [$value, PDO::PARAM_LOB];
						break;

					case 'boolean':
						$map[ $map_key ] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
						break;

					case 'integer':
					case 'double':
						$map[ $map_key ] = [$value, PDO::PARAM_INT];
						break;

					case 'string':
						$map[ $map_key ] = [$value, PDO::PARAM_STR];
						break;
				}
			}
		}

		return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
	}

	public function delete($table, $where)
	{
		$map = [];

		return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
	}

	public function replace($table, $columns, $where = null)
	{
		$map = [];

		if (is_array($columns))
		{
			$replace_query = [];

			foreach ($columns as $column => $replacements)
			{
				if (is_array($replacements[ 0 ]))
				{
					foreach ($replacements as $replacement)
					{
						$map_key = $this->mapKey();

						$replace_query[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';

						$map[ $map_key . 'a' ] = [$replacement[ 0 ], PDO::PARAM_STR];
						$map[ $map_key . 'b' ] = [$replacement[ 1 ], PDO::PARAM_STR];
					}
				}
				else
				{
					$map_key = $this->mapKey();

					$replace_query[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';

					$map[ $map_key . 'a' ] = [$replacements[ 0 ], PDO::PARAM_STR];
					$map[ $map_key . 'b' ] = [$replacements[ 1 ], PDO::PARAM_STR];
				}
			}

			$replace_query = implode(', ', $replace_query);
		}

		return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . $replace_query . $this->whereClause($where, $map), $map);
	}

	public function get($table, $join = null, $columns = null, $where = null)
	{
		$map = [];
		$stack = [];
		$column_map = [];

		$column = $where === null ? $join : $columns;

		$is_single_column = (is_string($column) && $column !== '*');

		$query = $this->exec($this->selectContext($table, $map, $join, $columns, $where) . ' LIMIT 1', $map);

		if ($query)
		{
			$data = $query->fetchAll(PDO::FETCH_ASSOC);

			if (isset($data[ 0 ]))
			{
				if ($column === '*')
				{
					return $data[ 0 ];
				}

				$this->columnMap($columns, $column_map);

				$this->dataMap($data[ 0 ], $columns, $column_map, $stack);

				if ($is_single_column)
				{
					return $stack[ $column_map[ $column ][ 0 ] ];
				}

				return $stack;
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

	public function has($table, $join, $where = null)
	{
		$map = [];
		$column = null;

		$query = $this->exec('SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')', $map);

		if ($query)
		{
			return $query->fetchColumn() === '1';
		}
		else
		{
			return false;
		}
	}

	public function count($table, $join = null, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'COUNT'), $map);

		return $query ? 0 + $query->fetchColumn() : false;
	}

	public function max($table, $join, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'MAX'), $map);

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

	public function min($table, $join, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'MIN'), $map);

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

	public function avg($table, $join, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'AVG'), $map);

		return $query ? 0 + $query->fetchColumn() : false;
	}

	public function sum($table, $join, $column = null, $where = null)
	{
		$map = [];

		$query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'SUM'), $map);

		return $query ? 0 + $query->fetchColumn() : false;
	}

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

	public function id()
	{
		$type = $this->database_type;

		if ($type === 'oracle')
		{
			return 0;
		}
		elseif ($type === 'mssql')
		{
			return $this->pdo->query('SELECT SCOPE_IDENTITY()')->fetchColumn();
		}
		elseif ($type === 'pgsql')
		{
			return $this->pdo->query('SELECT LASTVAL()')->fetchColumn();
		}

		return $this->pdo->lastInsertId();
	}

	public function debug()
	{
		$this->debug_mode = true;

		return $this;
	}

	public function error()
	{
		return $this->statement ? $this->statement->errorInfo() : null;
	}

	public function last()
	{
		$log = end($this->logs);

		return $this->generate($log[ 0 ], $log[ 1 ]);
	}

	public function log()
	{
		return array_map(function ($log)
			{
				return $this->generate($log[ 0 ], $log[ 1 ]);
			},
			$this->logs
		);
	}

	public function info()
	{
		$output = [
			'server' => 'SERVER_INFO',
			'driver' => 'DRIVER_NAME',
			'client' => 'CLIENT_VERSION',
			'version' => 'SERVER_VERSION',
			'connection' => 'CONNECTION_STATUS'
		];

		foreach ($output as $key => $value)
		{
			$output[ $key ] = @$this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
		}

		return $output;
	}
}
?>